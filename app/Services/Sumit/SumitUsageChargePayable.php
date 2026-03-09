<?php

declare(strict_types=1);

namespace App\Services\Sumit;

use App\Models\Account;
use OfficeGuy\LaravelSumitGateway\Contracts\Payable;
use OfficeGuy\LaravelSumitGateway\Enums\PayableType;

final readonly class SumitUsageChargePayable implements Payable
{
    public function __construct(
        private Account $account,
        private int $amountMinor,
        private string $currency,
        private string $metricKey,
        private int $quantity,
        private string $description,
        private int|string $reference,
        private ?string $unit = null,
    ) {}

    public function getPayableId(): string|int
    {
        return sprintf('usage_%s', $this->reference);
    }

    public function getPayableAmount(): float
    {
        return $this->amountMinor / 100;
    }

    public function getPayableCurrency(): string
    {
        return $this->currency;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->account->getSumitCustomerEmail();
    }

    public function getCustomerPhone(): ?string
    {
        return $this->account->getSumitCustomerPhone();
    }

    public function getCustomerName(): string
    {
        return $this->account->getSumitCustomerName() ?? sprintf('Account %d', $this->account->id);
    }

    public function getCustomerAddress(): ?array
    {
        return null;
    }

    public function getCustomerCompany(): ?string
    {
        return $this->account->name;
    }

    public function getCustomerId(): string|int|null
    {
        return $this->account->id;
    }

    public function getLineItems(): array
    {
        return [[
            'name' => $this->description,
            'sku' => $this->metricKey,
            'quantity' => 1,
            'unit_price' => $this->getPayableAmount(),
            'product_id' => $this->getPayableId(),
            'variation_id' => null,
        ]];
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
        return null;
    }

    public function isTaxEnabled(): bool
    {
        return false;
    }

    public function getCustomerNote(): ?string
    {
        $unit = $this->unit ?? 'unit';

        return sprintf('%d %s overage units for %s.', $this->quantity, $unit, $this->metricKey);
    }

    public function getOrderKey(): ?string
    {
        return hash('sha256', sprintf('%s|%s|%s', $this->reference, $this->metricKey, $this->amountMinor));
    }

    public function getPayableType(): PayableType
    {
        return PayableType::DIGITAL;
    }
}
