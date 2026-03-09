<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\ProductPrice;

interface BillingProvider
{
    /**
     * @return array{provider: string, customer_reference: string, metadata?: array<string, mixed>}
     */
    public function createCustomer(Account $account): array;

    /**
     * @return array{provider: string, subscription_reference: string, customer_reference?: string, metadata?: array<string, mixed>}
     */
    public function createSubscription(Account $account, ProductPrice $price): array;

    public function cancelSubscription(AccountSubscription $subscription): void;

    /**
     * @param  array<string, mixed>  $context
     * @return array{provider: string, charged?: bool, charge_reference?: string|null, metadata?: array<string, mixed>}
     */
    public function reportUsage(AccountSubscription $subscription, string $metric, int $quantity, array $context = []): array;
}
