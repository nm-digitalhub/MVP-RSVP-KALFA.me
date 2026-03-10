<?php

declare(strict_types=1);

namespace OfficeGuy\LaravelSumitGateway\Http\DTOs;

/**
 * Recurring Charge Data Transfer Object
 *
 * Represents recurring payment charge data for SUMIT API (billing/recurring/charge).
 * Uses nested PaymentMethod structure and excludes CVV.
 */
class RecurringChargeData
{
    public function __construct(
        public readonly float $amount,
        public readonly string $token,
        public readonly string $citizenId,
        public readonly string $expirationMonth,
        public readonly string $expirationYear,
        public readonly ?string $orderId = null,
        public readonly ?string $description = null,
        public readonly ?string $currency = 'ILS',
        public readonly ?string $sumitEntityId = null,
    ) {}

    /**
     * Convert to SUMIT API request array for recurring charging
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'Amount' => $this->amount,
            'Currency' => $this->currency,
            'PaymentMethod' => [
                'CreditCard_Token' => $this->token,
                'CreditCard_CitizenID' => $this->citizenId,
                'ExpirationMonth' => $this->expirationMonth,
                'ExpirationYear' => $this->expirationYear,
            ],
        ];

        if ($this->orderId !== null) {
            $data['OrderID'] = $this->orderId;
        }

        if ($this->description !== null) {
            $data['Description'] = $this->description;
        }

        if ($this->sumitEntityId !== null) {
            $data['SumitEntityID'] = $this->sumitEntityId;
        }

        return $data;
    }

    /**
     * Create from array
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: (float) $data['amount'],
            token: (string) $data['token'],
            citizenId: (string) $data['citizen_id'],
            expirationMonth: (string) $data['expiration_month'],
            expirationYear: (string) $data['expiration_year'],
            orderId: $data['order_id'] ?? null,
            description: $data['description'] ?? null,
            currency: $data['currency'] ?? 'ILS',
            sumitEntityId: $data['sumit_entity_id'] ?? null,
        );
    }
}
