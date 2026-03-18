<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\BillingProvider;
use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\ProductPrice;

final class StubBillingProvider implements BillingProvider
{
    public function createCustomer(Account $account): array
    {
        return [
            'provider' => 'stub',
            'customer_reference' => 'stub-customer-'.$account->id,
        ];
    }

    public function createSubscription(Account $account, ProductPrice $price): array
    {
        return [
            'provider' => 'stub',
            'subscription_reference' => 'stub-subscription-'.$price->id,
            'customer_reference' => 'stub-customer-'.$account->id,
        ];
    }

    public function cancelSubscription(AccountSubscription $subscription): void {}

    public function reportUsage(AccountSubscription $subscription, string $metric, int $quantity, array $context = []): array
    {
        return [
            'provider' => 'stub',
            'charged' => false,
        ];
    }
}
