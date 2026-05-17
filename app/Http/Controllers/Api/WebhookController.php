<?php

namespace App\Http\Controllers\Api;

use App\Services\SubscriptionService;
use App\Services\TournamentService;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends CashierWebhookController
{
    public function handleWebhook(Request $request): Response
    {
        // Let Cashier handle all standard subscription/customer events first
        $response = parent::handleWebhook($request);

        // Then handle our custom logic
        $payload = json_decode($request->getContent(), true);
        $type = $payload['type'] ?? null;

        $subscriptionService = app(SubscriptionService::class);

        if (in_array($type, ['customer.subscription.created', 'customer.subscription.updated'])) {
            $subscriptionService->handleSubscriptionCreated($payload);
        }

        if ($type === 'customer.subscription.deleted') {
            $subscriptionService->handleSubscriptionDeleted($payload);
        }

        if ($type === 'checkout.session.completed') {
            $session = $payload['data']['object'] ?? [];
            $metadata = $session['metadata'] ?? [];

            if (($metadata['type'] ?? null) === 'tournament_entry') {
                app(TournamentService::class)->completeEntryPayment($session);
            }
        }

        return $response;
    }
}
