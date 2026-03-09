<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BillingProvider;
use App\Enums\ProductPriceBillingCycle;
use App\Events\ProductEngineEvent;
use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\Product;
use App\Models\UsageRecord;
use Carbon\CarbonInterface;

final class UsageMeter
{
    public function __construct(
        private readonly BillingProvider $billingProvider,
    ) {}

    public function record(
        Account $account,
        Product $product,
        string $metricKey,
        int $quantity = 1,
        ?CarbonInterface $recordedAt = null,
        array $metadata = [],
    ): UsageRecord {
        $record = UsageRecord::query()->create([
            'account_id' => $account->id,
            'product_id' => $product->id,
            'metric_key' => $metricKey,
            'quantity' => $quantity,
            'recorded_at' => $recordedAt ?? now(),
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);

        $subscription = $account->activeSubscriptions()
            ->with('productPlan.product')
            ->whereHas('productPlan', fn ($query) => $query->where('product_id', $product->id))
            ->latest('started_at')
            ->latest('id')
            ->first();

        if ($subscription !== null) {
            $billingMetadata = $this->billOverageIfRequired($record, $subscription);

            if ($billingMetadata !== []) {
                $record->forceFill([
                    'metadata' => array_merge((array) $record->metadata, ['billing' => $billingMetadata]),
                ])->saveQuietly();
            }
        }

        ProductEngineEvent::dispatch(
            'usage.recorded',
            $account,
            $product,
            $subscription,
            [
                'metric_key' => $metricKey,
                'quantity' => $quantity,
                'usage_record_id' => $record->id,
            ],
        );

        return $record;
    }

    public function sumForPeriod(
        Account $account,
        string $metricKey,
        CarbonInterface $start,
        CarbonInterface $end,
        ?Product $product = null,
    ): int {
        $query = UsageRecord::query()
            ->where('account_id', $account->id)
            ->where('metric_key', $metricKey)
            ->where('recorded_at', '>=', $start)
            ->where('recorded_at', '<', $end);

        if ($product !== null) {
            $query->where('product_id', $product->id);
        }

        return (int) $query->sum('quantity');
    }

    public function sumForCurrentBillingPeriod(AccountSubscription $subscription, string $metricKey): int
    {
        [$start, $end] = $this->billingWindow($subscription, now());

        return $this->sumForPeriod(
            $subscription->account,
            $metricKey,
            $start,
            $end,
            $subscription->productPlan->product,
        );
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function billingWindow(AccountSubscription $subscription, CarbonInterface $asOf): array
    {
        $subscription->loadMissing('productPlan.prices');

        $anchor = ($subscription->started_at ?? $subscription->created_at ?? now())->copy();
        $billingCycle = $subscription->productPlan->primaryPrice()?->billing_cycle ?? ProductPriceBillingCycle::Monthly;

        if ($billingCycle === ProductPriceBillingCycle::Yearly) {
            $start = $anchor->copy();

            while ($start->copy()->addYear() <= $asOf) {
                $start->addYear();
            }

            return [$start, $start->copy()->addYear()];
        }

        if ($billingCycle === ProductPriceBillingCycle::Usage) {
            return [$asOf->copy()->startOfMonth(), $asOf->copy()->startOfMonth()->addMonth()];
        }

        $start = $anchor->copy();

        while ($start->copy()->addMonth() <= $asOf) {
            $start->addMonth();
        }

        return [$start, $start->copy()->addMonth()];
    }

    /**
     * @return array<string, mixed>
     */
    private function billOverageIfRequired(UsageRecord $record, AccountSubscription $subscription): array
    {
        $subscription->loadMissing('account', 'productPlan.product', 'productPlan.prices');

        $policyMode = (string) data_get($subscription->productPlan->metadata, "usage_policies.{$record->metric_key}.mode", config('product-engine.usage.default_policy', 'hard'));

        if ($policyMode !== 'soft') {
            return [];
        }

        $overageMetricKey = (string) data_get($subscription->productPlan->metadata, 'commercial.overage_metric_key', '');

        if ($overageMetricKey === '' || $overageMetricKey !== $record->metric_key) {
            return [];
        }

        $unitAmountMinor = (int) data_get($subscription->productPlan->metadata, 'commercial.overage_amount_minor', 0);

        if ($unitAmountMinor < 1) {
            return [];
        }

        $limitFeatureKey = (string) (data_get($subscription->productPlan->metadata, "usage_policies.{$record->metric_key}.limit_feature_key")
            ?: "{$record->metric_key}_limit");
        $limit = app(FeatureResolver::class)->integer($subscription->account, $limitFeatureKey);

        if ($limit === null) {
            return [];
        }

        $used = $this->sumForCurrentBillingPeriod($subscription, $record->metric_key);
        $previouslyUsed = max(0, $used - $record->quantity);
        $newOverageQuantity = max(0, $used - $limit) - max(0, $previouslyUsed - $limit);

        if ($newOverageQuantity < 1) {
            return [];
        }

        $amountMinor = $unitAmountMinor * $newOverageQuantity;
        $currency = (string) data_get($subscription->productPlan->metadata, 'commercial.currency', $subscription->productPlan->primaryPrice()?->currency ?? 'ILS');
        $unit = (string) data_get($subscription->productPlan->metadata, 'commercial.overage_unit', 'unit');

        $billingResult = $this->billingProvider->reportUsage($subscription, $record->metric_key, $newOverageQuantity, [
            'usage_record_id' => $record->id,
            'account_id' => $record->account_id,
            'product_id' => $record->product_id,
            'usage_quantity' => $record->quantity,
            'billing_quantity' => $newOverageQuantity,
            'limit' => $limit,
            'used' => $used,
            'previously_used' => $previouslyUsed,
            'unit_amount_minor' => $unitAmountMinor,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'unit' => $unit,
        ]);

        ProductEngineEvent::dispatch(
            'usage.overage_charged',
            $subscription->account,
            $subscription->productPlan->product,
            $subscription,
            [
                'usage_record_id' => $record->id,
                'metric_key' => $record->metric_key,
                'quantity' => $newOverageQuantity,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'charge_reference' => $billingResult['charge_reference'] ?? null,
                'provider' => $billingResult['provider'] ?? null,
            ],
        );

        return array_filter([
            'provider' => $billingResult['provider'] ?? null,
            'charged' => $billingResult['charged'] ?? true,
            'charge_reference' => $billingResult['charge_reference'] ?? null,
            'billing_quantity' => $newOverageQuantity,
            'unit_amount_minor' => $unitAmountMinor,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'unit' => $unit,
            'metadata' => $billingResult['metadata'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
