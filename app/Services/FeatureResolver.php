<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EntitlementType;
use App\Models\Account;
use App\Models\AccountEntitlement;
use App\Models\AccountSubscription;
use App\Models\ProductEntitlement;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

final class FeatureResolver
{
    public function __construct(
        private readonly UsageMeter $usageMeter,
    ) {}

    public function has(Account $account, string $featureKey): bool
    {
        return in_array($this->resolvedFeature($account, $featureKey)['source'], [
            'account_override',
            'propagated_entitlement',
            'plan_limit',
            'product_default',
        ], true);
    }

    public function allows(Account $account, string $featureKey): bool
    {
        return $this->enabled($account, $featureKey);
    }

    public function enabled(Account $account, string $featureKey): bool
    {
        return $this->normalizeBoolean($this->value($account, $featureKey, false));
    }

    public function value(Account $account, string $featureKey, mixed $default = null): mixed
    {
        $resolvedFeature = $this->resolvedFeature($account, $featureKey);

        if ($resolvedFeature['source'] === null) {
            return $default;
        }

        return $resolvedFeature['value'];
    }

    public function integer(Account $account, string $featureKey, ?int $default = null): ?int
    {
        $value = $this->value($account, $featureKey, $default);

        if ($value === null) {
            return $default;
        }

        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : $default;
    }

    public function usage(Account $account, string $metricKey, ?AccountSubscription $subscription = null): int
    {
        $activeSubscription = $subscription ?? $account->activeSubscriptions()->with('productPlan.prices', 'productPlan.product')->latest('started_at')->latest('id')->first();

        if ($activeSubscription === null) {
            return 0;
        }

        return $this->usageMeter->sumForCurrentBillingPeriod($activeSubscription, $metricKey);
    }

    public function remaining(Account $account, string $limitFeatureKey, string $metricKey, ?AccountSubscription $subscription = null): ?int
    {
        $limit = $this->integer($account, $limitFeatureKey);

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->usage($account, $metricKey, $subscription));
    }

    public function allowsUsage(
        Account $account,
        string $limitFeatureKey,
        string $metricKey,
        int $quantity = 1,
        ?AccountSubscription $subscription = null,
    ): bool {
        $remaining = $this->remaining($account, $limitFeatureKey, $metricKey, $subscription);

        if ($remaining === null) {
            return true;
        }

        return $remaining >= $quantity;
    }

    public function clearAccount(Account $account): void
    {
        foreach ([
            ...$account->entitlements()->pluck('feature_key')->all(),
            ...$account->activeAccountProducts()
                ->with('product.activeEntitlements')
                ->get()
                ->flatMap(fn ($assignment) => $assignment->product->activeEntitlements->pluck('feature_key'))
                ->all(),
            ...$account->activeSubscriptions()
                ->with('productPlan')
                ->get()
                ->flatMap(fn ($subscription) => array_keys((array) data_get($subscription->productPlan->metadata, 'limits', [])))
                ->all(),
        ] as $featureKey) {
            $this->forget($account, (string) $featureKey);
        }
    }

    public function entitlement(Account $account, string $featureKey): ?AccountEntitlement
    {
        return $this->accountOverrideEntitlement($account, $featureKey)
            ?? $this->propagatedEntitlement($account, $featureKey);
    }

    public function forget(Account $account, string $featureKey): void
    {
        $this->forgetByAccountId($account->id, $featureKey);
    }

    public function forgetByAccountId(int $accountId, string $featureKey): void
    {
        $this->cache()->forget($this->cacheKey($accountId, $featureKey));
    }

    public function forgetMany(Account $account, iterable $featureKeys): void
    {
        foreach ($featureKeys as $featureKey) {
            $this->forget($account, (string) $featureKey);
        }
    }

    private function resolvedFeature(Account $account, string $featureKey): array
    {
        return $this->cache()->remember(
            $this->cacheKey($account->id, $featureKey),
            config('product-engine.feature_cache_ttl', 300),
            fn (): array => $this->resolveUncached($account, $featureKey),
        );
    }

    private function resolveUncached(Account $account, string $featureKey): array
    {
        $override = $this->accountOverrideEntitlement($account, $featureKey);

        if ($override !== null) {
            return [
                'source' => 'account_override',
                'value' => $this->castAccountEntitlementValue($override),
            ];
        }

        $propagatedEntitlement = $this->propagatedEntitlement($account, $featureKey);

        if ($propagatedEntitlement !== null) {
            return [
                'source' => 'propagated_entitlement',
                'value' => $this->castAccountEntitlementValue($propagatedEntitlement),
            ];
        }

        $planLimit = $this->planLimitValue($account, $featureKey);

        if ($planLimit !== null) {
            return [
                'source' => 'plan_limit',
                'value' => $this->castPlanLimitValue($planLimit),
            ];
        }

        $productDefault = $this->productDefaultEntitlement($account, $featureKey);

        if ($productDefault !== null) {
            return [
                'source' => 'product_default',
                'value' => $this->castProductEntitlementValue($productDefault),
            ];
        }

        $defaults = config('product-engine.defaults', []);

        if (array_key_exists($featureKey, $defaults)) {
            return [
                'source' => 'system_default',
                'value' => $defaults[$featureKey],
            ];
        }

        return [
            'source' => null,
            'value' => null,
        ];
    }

    private function accountOverrideEntitlement(Account $account, string $featureKey): ?AccountEntitlement
    {
        return $account->entitlements()
            ->with('productEntitlement')
            ->where('feature_key', $featureKey)
            ->whereNull('product_entitlement_id')
            ->where($this->notExpired())
            ->latest('id')
            ->first();
    }

    private function propagatedEntitlement(Account $account, string $featureKey): ?AccountEntitlement
    {
        return $account->entitlements()
            ->with('productEntitlement')
            ->where('feature_key', $featureKey)
            ->whereNotNull('product_entitlement_id')
            ->whereHas('productEntitlement.product.accountProducts', function (Builder $query) use ($account): void {
                $query->where('account_id', $account->id)
                    ->where('status', \App\Enums\AccountProductStatus::Active->value)
                    ->where(function (Builder $builder): void {
                        $builder->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
            ->where($this->notExpired())
            ->latest('id')
            ->first();
    }

    private function planLimitValue(Account $account, string $featureKey): mixed
    {
        $subscription = $account->activeSubscriptions()
            ->with('productPlan')
            ->latest('started_at')
            ->latest('id')
            ->get()
            ->first(function (AccountSubscription $subscription) use ($featureKey): bool {
                return $subscription->productPlan?->limit($featureKey) !== null;
            });

        return $subscription?->productPlan?->limit($featureKey);
    }

    private function productDefaultEntitlement(Account $account, string $featureKey): ?ProductEntitlement
    {
        $accountProduct = $account->activeAccountProducts()
            ->with([
                'product.activeEntitlements' => fn ($query) => $query
                    ->where('feature_key', $featureKey)
                    ->latest('id'),
            ])
            ->latest('granted_at')
            ->latest('id')
            ->get()
            ->first(fn ($assignment) => $assignment->product->activeEntitlements->isNotEmpty());

        return $accountProduct?->product->activeEntitlements->first();
    }

    private function castAccountEntitlementValue(AccountEntitlement $entitlement): mixed
    {
        $rawValue = $entitlement->value;

        if ($rawValue === null) {
            return null;
        }

        return match ($entitlement->type ?? $entitlement->productEntitlement?->type) {
            EntitlementType::Boolean => $this->normalizeBoolean($rawValue),
            EntitlementType::Number => $this->normalizeNumber($rawValue),
            default => $rawValue,
        };
    }

    private function castProductEntitlementValue(ProductEntitlement $entitlement): mixed
    {
        if ($entitlement->value === null) {
            return null;
        }

        return match ($entitlement->type) {
            EntitlementType::Boolean => $this->normalizeBoolean($entitlement->value),
            EntitlementType::Number => $this->normalizeNumber($entitlement->value),
            default => $entitlement->value,
        };
    }

    private function castPlanLimitValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_numeric((string) $value)) {
            return $this->normalizeNumber($value);
        }

        return $value;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ?? false;
    }

    private function normalizeNumber(mixed $value): int|float|string
    {
        if (! is_numeric((string) $value)) {
            return $value;
        }

        $normalizedValue = (string) $value;

        return str_contains($normalizedValue, '.') ? (float) $normalizedValue : (int) $normalizedValue;
    }

    private function notExpired(): \Closure
    {
        return function (Builder $query): void {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        };
    }

    private function cache(): CacheRepository
    {
        $store = config('product-engine.cache_store');

        return $store ? Cache::memo($store) : Cache::memo();
    }

    private function cacheKey(int $accountId, string $featureKey): string
    {
        return "feature:{$accountId}:{$featureKey}";
    }
}
