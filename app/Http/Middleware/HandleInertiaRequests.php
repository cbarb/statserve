<?php

namespace App\Http\Middleware;

use App\Services\BadgeService;
use App\Services\SubscriptionService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $subscriptionService = $user ? app(SubscriptionService::class) : null;
        $badgeService = $user ? app(BadgeService::class) : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'level' => $user?->level ? [
                    'current_level' => $user->level->current_level,
                    'total_xp' => $user->level->total_xp,
                    'xp_for_next' => $user->level->xpForNextLevel(),
                    'xp_progress' => XpService::xpProgress($user->level),
                    'level_name' => XpService::levelName($user->level->current_level),
                ] : null,
                'pinned_badges' => $user ? fn () => $badgeService->getPinnedBadges($user) : [],
            ],
            'subscription' => $user ? $subscriptionService->getUserStatus($user) : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'xp_awarded' => fn () => $request->session()->get('xp_awarded'),
                'badges_earned' => fn () => $request->session()->get('badges_earned'),
            ],
        ];
    }
}
