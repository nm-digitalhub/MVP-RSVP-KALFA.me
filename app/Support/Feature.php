<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Account;
use App\Models\AccountSubscription;
use App\Services\FeatureResolver;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool has(Account $account, string $featureKey)
 * @method static bool allows(Account $account, string $featureKey)
 * @method static bool enabled(Account $account, string $featureKey)
 * @method static mixed value(Account $account, string $featureKey, mixed $default = null)
 * @method static ?int integer(Account $account, string $featureKey, ?int $default = null)
 * @method static int usage(Account $account, string $metricKey, ?AccountSubscription $subscription = null)
 * @method static ?int remaining(Account $account, string $limitFeatureKey, string $metricKey, ?AccountSubscription $subscription = null)
 * @method static bool allowsUsage(Account $account, string $limitFeatureKey, string $metricKey, int $quantity = 1, ?AccountSubscription $subscription = null)
 */
final class Feature extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FeatureResolver::class;
    }
}
