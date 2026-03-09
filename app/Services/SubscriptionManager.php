<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\ProductPlan;
use Carbon\CarbonInterface;

final class SubscriptionManager
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function subscribe(
        Account $account,
        ProductPlan $plan,
        ?CarbonInterface $startedAt = null,
        array $metadata = [],
    ): AccountSubscription {
        return $this->subscriptionService->startTrial($account, $plan, startedAt: $startedAt, metadata: $metadata);
    }

    public function activate(AccountSubscription $subscription, ?int $grantedBy = null): AccountSubscription
    {
        return $this->subscriptionService->activate($subscription, $grantedBy);
    }

    public function cancel(AccountSubscription $subscription): AccountSubscription
    {
        return $this->subscriptionService->cancel($subscription);
    }
}
