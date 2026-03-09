<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UsagePolicyDecision;
use App\Events\ProductEngineEvent;
use App\Models\Account;
use App\Models\AccountSubscription;

final class UsagePolicyService
{
    public function __construct(
        private readonly FeatureResolver $featureResolver,
    ) {}

    public function check(
        Account $account,
        string $metricKey,
        int $quantity = 1,
        ?AccountSubscription $subscription = null,
    ): UsagePolicyDecision {
        $subscription ??= $account->activeSubscriptions()
            ->with('productPlan.product')
            ->latest('started_at')
            ->latest('id')
            ->first();

        $limitFeatureKey = $this->limitFeatureKey($metricKey, $subscription);
        $limit = $this->featureResolver->integer($account, $limitFeatureKey);

        if ($limit === null) {
            return UsagePolicyDecision::Allowed;
        }

        $used = $this->featureResolver->usage($account, $metricKey, $subscription);
        $projected = $used + $quantity;

        if ($projected <= $limit) {
            return UsagePolicyDecision::Allowed;
        }

        $decision = $this->policyMode($metricKey, $subscription) === 'soft'
            ? UsagePolicyDecision::AllowedWithOverage
            : UsagePolicyDecision::Blocked;

        ProductEngineEvent::dispatch(
            'limits.exceeded',
            $account,
            $subscription?->productPlan?->product,
            $subscription,
            [
                'metric_key' => $metricKey,
                'limit_feature_key' => $limitFeatureKey,
                'quantity' => $quantity,
                'used' => $used,
                'projected' => $projected,
                'limit' => $limit,
                'decision' => $decision->value,
            ],
            $decision === UsagePolicyDecision::Blocked ? 'warning' : 'info',
        );

        return $decision;
    }

    private function limitFeatureKey(string $metricKey, ?AccountSubscription $subscription): string
    {
        return (string) (data_get($subscription?->productPlan?->metadata, "usage_policies.{$metricKey}.limit_feature_key")
            ?: "{$metricKey}_limit");
    }

    private function policyMode(string $metricKey, ?AccountSubscription $subscription): string
    {
        return (string) (data_get($subscription?->productPlan?->metadata, "usage_policies.{$metricKey}.mode")
            ?: config('product-engine.usage.default_policy', 'hard'));
    }
}
