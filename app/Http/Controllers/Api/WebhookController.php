<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingWebhookEvent;
use App\Models\Payment;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OfficeGuy\LaravelSumitGateway\Services\WebhookService;

class WebhookController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {}

    /**
     * Handle gateway webhook. Idempotent: checks gateway_transaction_id, stores payload, prevents double-processing.
     * Signature validation (when configured) runs before any DB mutation.
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('X-Webhook-Signature', $request->header('Stripe-Signature', ''));

        if ($gateway === 'sumit') {
            $secret = config('billing.webhook_secret');
            if ($secret !== null && $secret !== '') {
                if (! WebhookService::verifySignature($signature, $payload, (string) $secret)) {
                    return response()->json(['error' => 'Invalid signature'], 403);
                }
            }
        }

        $transactionId = $payload['PaymentID'] ?? $payload['id'] ?? $payload['transaction_id'] ?? $payload['gateway_transaction_id'] ?? null;
        if ($transactionId && Payment::where('gateway_transaction_id', $transactionId)->whereIn('status', ['succeeded', 'failed'])->exists()) {
            return response()->json(['message' => 'Already processed'], 200);
        }

        $webhookEvent = BillingWebhookEvent::create([
            'source' => $gateway,
            'event_type' => $payload['type'] ?? $payload['event_type'] ?? null,
            'payload' => $payload,
        ]);

        try {
            $gatewayInstance = app(\App\Contracts\PaymentGatewayInterface::class);
            $gatewayInstance->handleWebhook($payload, $signature);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }

        $webhookEvent->update(['processed_at' => now()]);

        return response()->json(['message' => 'OK'], 200);
    }
}
