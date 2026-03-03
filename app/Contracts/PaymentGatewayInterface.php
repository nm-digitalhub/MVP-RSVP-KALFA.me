<?php

declare(strict_types=1);

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Create a one-time payment intent (redirect flow). Returns redirect_url and/or transaction_id.
     *
     * @param  array<string, mixed>  $metadata
     * @return array{redirect_url?: string, transaction_id?: string, payment_id?: int, event_billing_id?: int}
     */
    public function createOneTimePayment(int $organizationId, int $amount, array $metadata = []): array;

    /**
     * Charge using a single-use token (PaymentsJS). No redirect. Sync success/failure.
     *
     * @param  array<string, mixed>  $metadata  Must include event_billing_id, payment_id
     * @return array{success: bool, transaction_id?: string, message?: string}
     */
    public function chargeWithToken(int $organizationId, int $amount, array $metadata, string $token): array;

    /**
     * Verify signature and process webhook payload. Updates payment/event state internally.
     */
    public function handleWebhook(array $payload, string $signature): void;
}
