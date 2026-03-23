<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Api\WebhookController;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Business-layer trust enforcement for incoming webhook payloads.
 *
 * Transport layer (controller) always returns HTTP 200 to prevent retries.
 * This service decides whether the payload is trustworthy enough to trigger state changes.
 *
 * @see WebhookController
 */
final class WebhookPayloadValidator
{
    /**
     * Validate a webhook payload against local payment records.
     *
     * Returns the matched Payment if all checks pass, or null if validation fails.
     * Failures are logged as audit events — never thrown as exceptions.
     *
     * @param  array<string, mixed>  $payload
     * @return array{valid: bool, payment: ?Payment, reason: ?string}
     */
    public function validate(array $payload, string $gateway): array
    {
        $transactionId = $this->extractTransactionId($payload);

        if ($transactionId === null) {
            return $this->fail('missing_transaction_id', $gateway, $payload);
        }

        $payment = Payment::where('gateway_transaction_id', $transactionId)->first();

        if ($payment === null) {
            return $this->fail('payment_not_found', $gateway, $payload, ['transaction_id' => $transactionId]);
        }

        // Idempotency: already in terminal state — no-op, but valid transport
        if ($payment->status === PaymentStatus::Succeeded || $payment->status === PaymentStatus::Failed) {
            return ['valid' => false, 'payment' => $payment, 'reason' => 'already_terminal'];
        }

        // Gateway must match the payment's recorded gateway
        if ($payment->gateway !== $gateway) {
            return $this->fail('gateway_mismatch', $gateway, $payload, [
                'transaction_id' => $transactionId,
                'expected_gateway' => $payment->gateway,
                'received_gateway' => $gateway,
            ]);
        }

        // Amount verification (if payload contains amount)
        $payloadAmount = $this->extractAmount($payload);
        if ($payloadAmount !== null && $payloadAmount !== $payment->amount_cents) {
            return $this->fail('amount_mismatch', $gateway, $payload, [
                'transaction_id' => $transactionId,
                'expected_amount' => $payment->amount_cents,
                'received_amount' => $payloadAmount,
            ]);
        }

        return ['valid' => true, 'payment' => $payment, 'reason' => null];
    }

    private function extractTransactionId(array $payload): ?string
    {
        $id = $payload['PaymentID']
            ?? $payload['TransactionID']
            ?? $payload['ID']
            ?? $payload['id']
            ?? $payload['gateway_transaction_id']
            ?? null;

        if ($id === null) {
            return null;
        }

        return (string) $id;
    }

    /**
     * Extract amount from SUMIT webhook payload (agorot/cents).
     * Returns null if not present — amount check is best-effort.
     */
    private function extractAmount(array $payload): ?int
    {
        // SUMIT sends Amount in shekels (float), we store in agorot (int)
        $amount = $payload['Amount'] ?? $payload['amount'] ?? null;

        if ($amount === null) {
            $payment = $payload['Payment'] ?? $payload['payment'] ?? null;
            if (is_array($payment)) {
                $amount = $payment['Amount'] ?? $payment['amount'] ?? null;
            }
        }

        if ($amount === null) {
            return null;
        }

        // Convert shekels to agorot
        return (int) round((float) $amount * 100);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     * @return array{valid: bool, payment: null, reason: string}
     */
    private function fail(string $reason, string $gateway, array $payload, array $context = []): array
    {
        Log::warning('Webhook validation failed', [
            'reason' => $reason,
            'gateway' => $gateway,
            'payload_keys' => array_keys($payload),
            ...$context,
        ]);

        return ['valid' => false, 'payment' => null, 'reason' => $reason];
    }
}
