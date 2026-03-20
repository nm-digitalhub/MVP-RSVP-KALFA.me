<?php

declare(strict_types=1);

namespace App\Services\Sumit;

use App\Models\EventBilling;
use OfficeGuy\LaravelSumitGateway\Contracts\Payable;
use OfficeGuy\LaravelSumitGateway\Enums\PayableType;

/**
 * Payable adapter for EventBilling → SUMIT package PaymentService.
 * Used only for one-time event payment (redirect flow). Does not use package UI or Order model.
 */
final class EventBillingPayable implements Payable
{
    public function __construct(
        private EventBilling $eventBilling
    ) {}

    public function getPayableId(): string|int
    {
        return $this->eventBilling->id;
    }

    public function getPayableAmount(): float
    {
        return (float) ($this->eventBilling->amount_cents / 100);
    }

    public function getPayableCurrency(): string
    {
        return $this->eventBilling->currency ?? 'ILS';
    }

    public function getCustomerEmail(): ?string
    {
        return $this->eventBilling->organization?->billing_email;
    }

    public function getCustomerPhone(): ?string
    {
        return null;
    }

    public function getCustomerName(): string
    {
        $name = $this->eventBilling->organization?->name;

        return trim((string) $name) !== '' ? $name : 'Guest';
    }

    public function getCustomerAddress(): ?array
    {
        return null;
    }

    public function getCustomerCompany(): ?string
    {
        return null;
    }

    public function getCustomerId(): string|int|null
    {
        return $this->eventBilling->organization_id;
    }

    public function getLineItems(): array
    {
        $event = $this->eventBilling->event;
        $name = $event ? $event->name : 'Event payment';

        return [
            [
                'name' => $name,
                'sku' => null,
                'quantity' => 1,
                'unit_price' => $this->getPayableAmount(),
                'product_id' => null,
                'variation_id' => null,
            ],
        ];
    }

    public function getShippingAmount(): float
    {
        return 0.0;
    }

    public function getShippingMethod(): ?string
    {
        return null;
    }

    public function getFees(): array
    {
        return [];
    }

    public function getVatRate(): ?float
    {
        return 0.0;
    }

    public function isTaxEnabled(): bool
    {
        return false;
    }

    public function getCustomerNote(): ?string
    {
        return null;
    }

    public function getOrderKey(): ?string
    {
        return 'eb_'.$this->eventBilling->id.'_'.substr(md5((string) $this->eventBilling->updated_at?->timestamp), 0, 8);
    }

    public function getPayableType(): PayableType
    {
        return PayableType::GENERIC;
    }
}
