<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;

/**
 * Stub gateway for development. Does not charge; webhook can be simulated.
 */
class StubPaymentGateway implements PaymentGatewayInterface
{
    public function createOneTimePayment(int $organizationId, int $amount, array $metadata = []): array
    {
        $id = 'stub_'.uniqid('', true);

        return [
            'transaction_id' => $id,
            'redirect_url' => url("/api/rsvp/stub-success?tid={$id}"),
            'client_secret' => null,
        ];
    }

    public function chargeWithToken(int $organizationId, int $amount, array $metadata, string $token): array
    {
        return [
            'success' => false,
            'message' => 'Stub gateway does not support tokenization. Use SUMIT gateway.',
        ];
    }

    public function handleWebhook(array $payload, string $signature): void
    {
        // No-op; implement when integrating real gateway.
    }
}
