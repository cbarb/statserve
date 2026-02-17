<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            ...$this->subscriptionService->getUserStatus($user),
            'subscription' => $user->subscription('pro')?->only(['name', 'stripe_status', 'ends_at']),
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'interval' => 'required|in:monthly,yearly',
        ]);

        $url = $this->subscriptionService->createProCheckout(
            $request->user(),
            $request->input('interval'),
        );

        return response()->json(['checkout_url' => $url]);
    }

    public function update(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->subscriptionService->cancelSubscription($request->user());

        return response()->json(['message' => 'Subscription cancelled']);
    }

    public function boost(Request $request, Group $group): JsonResponse
    {
        $request->validate([
            'interval' => 'required|in:monthly,yearly',
        ]);

        $url = $this->subscriptionService->createBoostCheckout(
            $request->user(),
            $group,
            $request->input('interval'),
        );

        return response()->json(['checkout_url' => $url]);
    }
}
