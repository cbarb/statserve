<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $isPro = $this->subscriptionService->isProSubscriber($user);
        $proSubscription = $user->subscription('pro');

        $groups = $user->groups()->get()->map(function ($group) use ($user) {
            $boostSub = $user->subscription('boost-' . $group->id);

            return [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'has_boost' => $this->subscriptionService->hasActiveBoost($group),
                'weekly_match_count' => $this->subscriptionService->getWeeklyMatchCount($group),
                'boost_on_grace_period' => $boostSub?->onGracePeriod() ?? false,
                'boost_ends_at' => $boostSub?->ends_at,
            ];
        });

        return Inertia::render('Billing/Index', [
            'isPro' => $isPro,
            'proSubscription' => $proSubscription ? [
                'name' => $proSubscription->name,
                'stripe_status' => $proSubscription->stripe_status,
                'on_grace_period' => $proSubscription->onGracePeriod(),
                'ends_at' => $proSubscription->ends_at,
            ] : null,
            'groups' => $groups,
            'prices' => [
                'pro_monthly' => 7,
                'pro_yearly' => 60,
                'boost_monthly' => 15,
                'boost_yearly' => 130,
            ],
        ]);
    }

    public function checkout(Request $request): HttpResponse|RedirectResponse
    {
        if ($this->subscriptionService->isProSubscriber($request->user())) {
            return redirect()->route('billing.index')
                ->with('error', 'You already have an active Pro subscription.');
        }

        $request->validate([
            'interval' => 'required|in:monthly,yearly',
        ]);

        $url = $this->subscriptionService->createProCheckout(
            $request->user(),
            $request->input('interval'),
        );

        return Inertia::location($url);
    }

    public function boostCheckout(Request $request, Group $group): HttpResponse|RedirectResponse
    {
        $this->authorize('view', $group);

        if ($this->subscriptionService->hasActiveBoost($group)) {
            return redirect()->route('billing.index')
                ->with('error', 'This group already has an active boost.');
        }

        $request->validate([
            'interval' => 'required|in:monthly,yearly',
        ]);

        $url = $this->subscriptionService->createBoostCheckout(
            $request->user(),
            $group,
            $request->input('interval'),
        );

        return Inertia::location($url);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('Billing/Success');
    }

    public function portal(Request $request): RedirectResponse
    {
        return $request->user()->redirectToBillingPortal(route('billing.index'));
    }

    public function cancelPro(Request $request): RedirectResponse
    {
        $this->subscriptionService->cancelSubscription($request->user());

        return redirect()->route('billing.index')
            ->with('success', 'Your Pro subscription has been cancelled.');
    }

    public function cancelBoost(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('view', $group);

        $this->subscriptionService->cancelBoost($request->user(), $group);

        return redirect()->route('billing.index')
            ->with('success', 'Group boost has been cancelled.');
    }

    public function resumePro(Request $request): RedirectResponse
    {
        $this->subscriptionService->resumeSubscription($request->user());

        return redirect()->route('billing.index')
            ->with('success', 'Your Pro subscription has been renewed.');
    }

    public function resumeBoost(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('view', $group);

        $this->subscriptionService->resumeBoost($request->user(), $group);

        return redirect()->route('billing.index')
            ->with('success', 'Group boost has been renewed.');
    }
}
