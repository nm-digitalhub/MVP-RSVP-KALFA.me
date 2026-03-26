<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Services\OfficeGuy\SystemBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use OfficeGuy\LaravelSumitGateway\Models\Subscription;
use Tests\TestCase;

final class SystemBillingSubscriptionCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_active_subscription_count_is_zero_without_rows(): void
    {
        $this->assertSame(0, app(SystemBillingService::class)->getActiveSubscriptionCount());
    }

    public function test_get_active_subscription_count_matches_number_of_active_rows(): void
    {
        $account = Account::factory()->create();

        Subscription::query()->create([
            'subscriber_type' => $account->getMorphClass(),
            'subscriber_id' => $account->id,
            'name' => 'Plan A',
            'amount' => 99.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'completed_cycles' => 0,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $service = app(SystemBillingService::class);

        $this->assertSame(1, $service->getActiveSubscriptionCount());

        Subscription::query()->create([
            'subscriber_type' => $account->getMorphClass(),
            'subscriber_id' => $account->id,
            'name' => 'Plan B',
            'amount' => 49.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'completed_cycles' => 0,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->assertSame(2, $service->getActiveSubscriptionCount());
    }

    public function test_get_active_subscription_count_uses_count_aggregate_on_subscriptions_table(): void
    {
        DB::enableQueryLog();

        app(SystemBillingService::class)->getActiveSubscriptionCount();

        $aggregateQuery = collect(DB::getQueryLog())->first(
            fn (array $q): bool => str_contains(strtolower($q['query']), 'count(')
                && str_contains(strtolower($q['query']), 'officeguy_subscriptions')
        );

        $this->assertNotNull($aggregateQuery, 'Expected a COUNT aggregate query against officeguy_subscriptions.');
    }
}
