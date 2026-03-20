<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Models\EventBilling;
use App\Models\Payment;
use App\Services\Sumit\EventBillingPayable;
use Illuminate\Support\Facades\Log;
use OfficeGuy\LaravelSumitGateway\Services\PaymentService;

/**
 * Thin adapter: our PaymentGatewayInterface → officeguy/laravel-sumit-gateway PaymentService.
 * Headless/API-only: no package UI, no Filament, no package checkout routes.
 * One-time payment per event only (redirect mode).
 */
final class SumitPaymentGateway implements PaymentGatewayInterface
{
    public function createOneTimePayment(int $organizationId, int $amount, array $metadata = []): array
    {
        $eventBillingId = (int) ($metadata['event_billing_id'] ?? 0);
        if ($eventBillingId < 1) {
            throw new \InvalidArgumentException('SumitPaymentGateway requires event_billing_id in metadata.');
        }

        $eventBilling = EventBilling::with(['event', 'organization'])
            ->where('organization_id', $organizationId)
            ->findOrFail($eventBillingId);

        $payable = new EventBillingPayable($eventBilling);

        $successUrl = config('billing.sumit.redirect_success_url');
        $cancelUrl = config('billing.sumit.redirect_cancel_url');
        if ($successUrl === null || $cancelUrl === null) {
            throw new \RuntimeException('billing.sumit.redirect_success_url and billing.sumit.redirect_cancel_url must be set when using SUMIT gateway.');
        }

        $extra = [
            'RedirectURL' => $successUrl,
            'CancelRedirectURL' => $cancelUrl,
        ];

        $result = PaymentService::processCharge(
            $payable,
            1,
            false,
            true,
            null,
            $extra
        );

        if (! ($result['success'] ?? false)) {
            Log::warning('SumitPaymentGateway::createOneTimePayment failed', [
                'event_billing_id' => $eventBillingId,
                'message' => $result['message'] ?? 'Unknown',
            ]);
            throw new \RuntimeException($result['message'] ?? 'SUMIT payment initiation failed.');
        }

        $response = $result['response'] ?? [];
        $data = $response['Data'] ?? [];
        $transactionId = $data['PaymentID'] ?? $data['TransactionID'] ?? $data['ID'] ?? null;
        if (is_numeric($transactionId)) {
            $transactionId = (string) $transactionId;
        }

        return array_filter([
            'redirect_url' => $result['redirect_url'] ?? null,
            'transaction_id' => $transactionId,
            'raw' => $result,
        ], fn ($v) => $v !== null);
    }

    /**
     * Charge using single-use token (PaymentsJS). No redirect; endpoint /billing/payments/charge/.
     * Return structure normalized: success, transaction_id, raw (no redirect_url logic).
     */
    public function chargeWithToken(int $organizationId, int $amount, array $metadata, string $token): array
    {
        $eventBillingId = (int) ($metadata['event_billing_id'] ?? 0);
        if ($eventBillingId < 1) {
            throw new \InvalidArgumentException('SumitPaymentGateway chargeWithToken requires event_billing_id in metadata.');
        }

        $eventBilling = EventBilling::with(['event', 'organization'])
            ->where('organization_id', $organizationId)
            ->findOrFail($eventBillingId);

        $payable = new EventBillingPayable($eventBilling);

        $result = PaymentService::processCharge(
            $payable,
            1,
            false,
            false,
            null,
            [],
            null,
            $token
        );

        $response = $result['response'] ?? [];
        $data = $response['Data'] ?? [];
        $paymentData = $data['Payment'] ?? [];
        $transactionId = $paymentData['ID'] ?? $data['PaymentID'] ?? $data['TransactionID'] ?? null;
        if (is_numeric($transactionId)) {
            $transactionId = (string) $transactionId;
        }

        if (! ($result['success'] ?? false)) {
            return [
                'success' => false,
                'transaction_id' => $transactionId,
                'message' => $result['message'] ?? 'SUMIT token charge failed.',
                'raw' => $result,
            ];
        }

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'raw' => $result,
        ];
    }

    public function handleWebhook(array $payload, string $signature): void
    {
        $transactionId = $payload['PaymentID'] ?? $payload['TransactionID'] ?? $payload['ID'] ?? $payload['id'] ?? $payload['gateway_transaction_id'] ?? null;
        if ($transactionId === null) {
            Log::warning('SumitPaymentGateway::handleWebhook missing transaction id', ['payload_keys' => array_keys($payload)]);

            return;
        }

        if (is_numeric($transactionId)) {
            $transactionId = (string) $transactionId;
        }

        $payment = Payment::where('gateway_transaction_id', $transactionId)->first();

        if ($payment === null) {
            Log::warning('SumitPaymentGateway::handleWebhook no matching payment', ['transaction_id' => $transactionId]);

            return;
        }

        if (in_array($payment->status, [PaymentStatus::Succeeded->value, PaymentStatus::Failed->value], true)) {
            return;
        }

        $succeeded = $this->normalizeWebhookStatus($payload);
        $billingService = app(BillingService::class);

        if ($succeeded) {
            $billingService->markPaymentSucceeded($payment);
        } else {
            $billingService->markPaymentFailed($payment);
        }
    }

    private function normalizeWebhookStatus(array $payload): bool
    {
        if (isset($payload['ValidPayment'])) {
            return (bool) $payload['ValidPayment'];
        }
        if (isset($payload['Status'])) {
            return (int) $payload['Status'] === 0;
        }
        if (isset($payload['status'])) {
            $s = strtolower((string) $payload['status']);

            return in_array($s, ['succeeded', 'success', 'completed', 'paid'], true);
        }
        $payment = $payload['Payment'] ?? $payload['payment'] ?? null;
        if (is_array($payment) && isset($payment['ValidPayment'])) {
            return (bool) $payment['ValidPayment'];
        }

        return false;
    }
}
