<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\UsagePolicyDecision;
use App\Models\Account;
use App\Models\AccountSubscription;
use App\Services\UsagePolicyService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static UsagePolicyDecision check(Account $account, string $metricKey, int $quantity = 1, ?AccountSubscription $subscription = null)
 */
final class UsagePolicy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UsagePolicyService::class;
    }
}
