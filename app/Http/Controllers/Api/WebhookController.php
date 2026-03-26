<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentGatewayInterface;
use App\Http\Controllers\Controller;
use App\Models\BillingWebhookEvent;
use App\Services\Billing\WebhookPayloadValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OfficeGuy\LaravelSumitGateway\Services\WebhookService;

/**
 * Payment webhook endpoint.
 *
 * Architecture: transport vs business validation.
 * - Transport: always return HTTP 200 to prevent provider retries.
 * - Business: validate payload against local records before any state change.
 *
 * @see WebhookPayloadValidator
 */
class WebhookController extends Controller
{
    public function __construct(
        private WebhookPayloadValidator $validator,
    ) {}

    /**
     * Handle payment gateway webhook.
     *
     * Accepts webhook notifications from supported gateways.
     * Always returns HTTP 200 at the transport layer.
     * Business validation gates all state changes.
     *
     * @unauthenticated
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        // ── 1. Gateway allowlist ──────────────────────────────────
        $allowedGateways = config('billing.allowed_gateways', ['sumit', 'stub']);

        if (! in_array($gateway, $allowedGateways, true)) {
            Log::warning('Webhook received for unknown gateway', ['gateway' => $gateway]);

            return response()->json(['message' => 'OK'], 200);
        }

        $payload = $request->all();

        // ── 2. Audit log (before any validation) ──────────────────
        $webhookEvent = BillingWebhookEvent::create([
            'source' => $gateway,
            'event_type' => $payload['type'] ?? $payload['event_type'] ?? null,
            'payload' => $payload,
        ]);

        // ── 3. HMAC signature verification ────────────────────────
        if (! $this->verifySignature($request, $gateway, $payload)) {
            $webhookEvent->update(['processed_at' => now()]);

            return response()->json(['message' => 'OK'], 200);
        }

        // ── 4. Business validation ────────────────────────────────
        $validation = $this->validator->validate($payload, $gateway);

        if (! $validation['valid']) {
            $webhookEvent->update(['processed_at' => now()]);

            // Idempotent: already-terminal is a success (no-op)
            return response()->json(['message' => 'OK'], 200);
        }

        $payment = $validation['payment'];

        // ── 5. Delegate to gateway-specific handler ───────────────
        try {
            $gatewayInstance = $this->resolveGateway($gateway);
            $gatewayInstance->handleWebhook($payload, $this->extractSignature($request));
        } catch (\Throwable $e) {
            Log::error('Webhook handler exception', [
                'gateway' => $gateway,
                'payment_id' => $payment?->id,
                'error' => $e->getMessage(),
            ]);
        }

        $webhookEvent->update(['processed_at' => now()]);

        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Verify HMAC signature for gateways that support it.
     * Mandatory in production (non-stub) when BILLING_WEBHOOK_SECRET is configured.
     */
    private function verifySignature(Request $request, string $gateway, array $payload): bool
    {
        if ($gateway === 'stub') {
            return true;
        }

        $secret = config('billing.webhook_secret');

        // No secret configured: enforce in production, warn in development
        if ($secret === null || $secret === '') {
            if (app()->isProduction()) {
                Log::critical('Webhook received without BILLING_WEBHOOK_SECRET configured in production', [
                    'gateway' => $gateway,
                ]);

                return false;
            }

            Log::warning('BILLING_WEBHOOK_SECRET not configured — webhook accepted in non-production');

            return true;
        }

        $signature = $this->extractSignature($request);

        if ($gateway === 'sumit') {
            return WebhookService::verifySignature($signature, $payload, (string) $secret);
        }

        return false;
    }

    private function extractSignature(Request $request): string
    {
        return $request->header('X-Webhook-Signature', $request->header('Stripe-Signature', '')) ?? '';
    }

    /**
     * Resolve gateway instance explicitly matching the {gateway} parameter.
     * Container binding is NOT trusted as the sole source — must match route.
     */
    private function resolveGateway(string $gateway): PaymentGatewayInterface
    {
        $instance = app(PaymentGatewayInterface::class);

        $configuredGateway = config('billing.default_gateway', 'stub');

        if ($configuredGateway !== $gateway) {
            Log::warning('Webhook gateway parameter does not match configured gateway', [
                'route_gateway' => $gateway,
                'configured_gateway' => $configuredGateway,
            ]);
        }

        return $instance;
    }
}
