<?php

namespace App\Services;

use App\Enums\BoostStatus;
use App\Models\GameMatch;
use App\Models\Group;
use App\Models\GroupBoost;
use App\Models\User;
use App\Notifications\GroupBoostCancelledNotification;
use App\Notifications\GroupBoostConfirmedNotification;
use App\Notifications\ProSubscriptionCancelledNotification;
use App\Notifications\ProSubscriptionConfirmedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SubscriptionService
{
    public function getUserStatus(User $user): array
    {
        return [
            'is_pro' => $this->isProSubscriber($user),
            'has_any_boost' => $user->groups()
                ->whereHas('boosts', fn ($q) => $q->active())
                ->exists(),
        ];
    }

    public function isProSubscriber(User $user): bool
    {
        return true;
    }

    public function hasActiveBoost(Group $group): bool
    {
        return true;
    }

    public function canLogMatch(User $user, Group $group): array
    {
        return ['allowed' => true, 'reason' => null];
    }

    public function getWeeklyMatchCount(Group $group): int
    {
        $tz = $group->timezone ?? 'UTC';
        $weekStart = Carbon::now($tz)->startOfWeek(Carbon::MONDAY)->utc();

        return GameMatch::where('group_id', $group->id)
            ->where('played_at', '>=', $weekStart)
            ->count();
    }

    public function createProCheckout(User $user, string $interval = 'monthly'): string
    {
        $priceId = $interval === 'yearly'
            ? config('services.stripe.pro_yearly_price')
            : config('services.stripe.pro_monthly_price');

        return $user->newSubscription('pro', $priceId)
            ->checkout([
                'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.index'),
            ])
            ->url;
    }

    public function createBoostCheckout(User $user, Group $group, string $interval = 'monthly'): string
    {
        $priceId = $interval === 'yearly'
            ? config('services.stripe.boost_yearly_price')
            : config('services.stripe.boost_monthly_price');

        return $user->newSubscription('boost-' . $group->id, $priceId)
            ->withMetadata(['group_id' => $group->id])
            ->checkout([
                'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.index'),
            ])
            ->url;
    }

    public function cancelSubscription(User $user): void
    {
        if ($user->subscribed('pro')) {
            $user->subscription('pro')->cancel();
            $user->notify(new ProSubscriptionCancelledNotification);
        }
    }

    public function cancelBoost(User $user, Group $group): void
    {
        $subscriptionName = 'boost-' . $group->id;

        if ($user->subscribed($subscriptionName)) {
            $user->subscription($subscriptionName)->cancel();
            $user->notify(new GroupBoostCancelledNotification($group));
        }
    }

    public function resumeSubscription(User $user): void
    {
        $subscription = $user->subscription('pro');
        if ($subscription?->onGracePeriod()) {
            $subscription->resume();
        }
    }

    public function resumeBoost(User $user, Group $group): void
    {
        $subscriptionName = 'boost-' . $group->id;
        $subscription = $user->subscription($subscriptionName);
        if ($subscription?->onGracePeriod()) {
            $subscription->resume();
        }
    }

    public function handleSubscriptionCreated(array $payload): void
    {
        $subscription = $payload['data']['object'] ?? [];
        $metadata = $subscription['metadata'] ?? [];

        if (isset($metadata['group_id'])) {
            $this->activateBoost($subscription, $metadata);
        } else {
            $stripeCustomerId = $subscription['customer'] ?? null;
            $user = User::where('stripe_id', $stripeCustomerId)->first();
            if ($user) {
                $user->notify(new ProSubscriptionConfirmedNotification);
            }
        }

        Log::info('Subscription created', ['subscription_id' => $subscription['id'] ?? null]);
    }

    public function handleSubscriptionDeleted(array $payload): void
    {
        $subscription = $payload['data']['object'] ?? [];
        $stripeSubscriptionId = $subscription['id'] ?? null;

        if (!$stripeSubscriptionId) {
            return;
        }

        $boost = GroupBoost::where('stripe_subscription_id', $stripeSubscriptionId)->first();
        if ($boost) {
            $boost->update([
                'status' => BoostStatus::Expired,
                'ends_at' => now(),
            ]);
        }

        Log::info('Subscription deleted', ['subscription_id' => $stripeSubscriptionId]);
    }

    private function activateBoost(array $subscription, array $metadata): void
    {
        $groupId = $metadata['group_id'];
        $stripeSubscriptionId = $subscription['id'] ?? null;
        $stripeCustomerId = $subscription['customer'] ?? null;

        $user = User::where('stripe_id', $stripeCustomerId)->first();
        if (!$user) {
            Log::warning('Boost activation: user not found', ['stripe_customer' => $stripeCustomerId]);
            return;
        }

        GroupBoost::updateOrCreate(
            [
                'group_id' => $groupId,
                'stripe_subscription_id' => $stripeSubscriptionId,
            ],
            [
                'user_id' => $user->id,
                'status' => BoostStatus::Active,
                'starts_at' => now(),
                'ends_at' => null,
            ],
        );

        $group = Group::find($groupId);
        if ($group) {
            Notification::send($group->members, new GroupBoostConfirmedNotification($group, $user));
        }
    }
}
