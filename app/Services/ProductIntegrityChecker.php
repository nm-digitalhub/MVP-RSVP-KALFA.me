<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class ProductIntegrityChecker
{
    /**
     * @return list<string>
     */
    public function issuesForProduct(Product $product): array
    {
        $issues = [];

        $product->loadMissing('entitlements', 'productPlans.activePrices');

        $duplicateFeatureKeys = $product->entitlements
            ->groupBy('feature_key')
            ->filter(fn ($group) => $group->count() > 1)
            ->keys()
            ->all();

        foreach ($duplicateFeatureKeys as $featureKey) {
            $issues[] = "Duplicate entitlement feature key detected: {$featureKey}.";
        }

        $inconsistentTypeKeys = $product->entitlements
            ->groupBy('feature_key')
            ->filter(fn ($group) => $group->pluck('type')->filter()->unique()->count() > 1)
            ->keys()
            ->all();

        foreach ($inconsistentTypeKeys as $featureKey) {
            $issues[] = "Inconsistent entitlement types detected for feature key: {$featureKey}.";
        }

        foreach ($product->productPlans as $plan) {
            if ($plan->is_active && $plan->activePrices->isEmpty()) {
                $issues[] = "Active product plan [{$plan->slug}] is missing an active price.";
            }

            foreach ((array) data_get($plan->metadata, 'limits', []) as $featureKey => $value) {
                if (! is_numeric($value)) {
                    $issues[] = "Plan [{$plan->slug}] has a non-numeric limit for [{$featureKey}].";
                }
            }
        }

        return array_values(array_unique($issues));
    }

    public function assertProductCanPublish(Product $product): void
    {
        $issues = $this->issuesForProduct($product);

        if ($issues === []) {
            return;
        }

        throw ValidationException::withMessages([
            'product' => $issues,
        ]);
    }

    public function assertProductSeedable(Product $product): void
    {
        $issues = $this->issuesForProduct($product);

        if ($issues === []) {
            return;
        }

        throw new \RuntimeException('Product integrity check failed: '.implode(' ', $issues));
    }

    public function reportAll(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Product::query()
            ->with('entitlements', 'productPlans.activePrices')
            ->each(function (Product $product): void {
                $issues = $this->issuesForProduct($product);

                if ($issues === []) {
                    return;
                }

                Log::warning('Product integrity issues detected', [
                    'product_id' => $product->id,
                    'slug' => $product->slug,
                    'issues' => $issues,
                ]);
            });
    }
}
