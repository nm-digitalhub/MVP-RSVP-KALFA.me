<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Organization;
use App\Services\OfficeGuy\SystemBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use OfficeGuy\LaravelSumitGateway\Models\Subscription;
use Tests\TestCase;

final class SystemBillingServiceGetOrganizationSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_organization_has_no_account(): void
    {
        $organization = Organization::factory()->create(['account_id' => null]);

        $result = app(SystemBillingService::class)->getOrganizationSubscription($organization);

        $this->assertNull($result);
    }

    public function test_returns_null_when_no_active_subscription(): void
    {
        $account = Account::factory()->create();
        $organization = Organization::factory()->create(['account_id' => $account->id]);

        $result = app(SystemBillingService::class)->getOrganizationSubscription($organization);

        $this->assertNull($result);
    }

    public function test_returns_hydrated_subscription_on_repeated_calls(): void
    {
        $account = Account::factory()->create();
        $organization = Organization::factory()->create(['account_id' => $account->id]);

        $created = Subscription::query()->create([
            'subscriber_type' => $account->getMorphClass(),
            'subscriber_id' => $account->id,
            'name' => 'Plan',
            'amount' => 99.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'completed_cycles' => 0,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        Cache::flush();

        $service = app(SystemBillingService::class);
        $first = $service->getOrganizationSubscription($organization);
        $second = $service->getOrganizationSubscription($organization);

        $this->assertInstanceOf(Subscription::class, $first);
        $this->assertSame($created->id, $first->id);
        $this->assertInstanceOf(Subscription::class, $second);
        $this->assertSame($created->id, $second->id);
    }

    public function test_forgets_stale_cache_when_cached_row_is_deleted(): void
    {
        $account = Account::factory()->create();
        $organization = Organization::factory()->create(['account_id' => $account->id]);

        $subscription = Subscription::query()->create([
            'subscriber_type' => $account->getMorphClass(),
            'subscriber_id' => $account->id,
            'name' => 'Plan',
            'amount' => 99.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'completed_cycles' => 0,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        Cache::flush();

        $service = app(SystemBillingService::class);
        $this->assertNotNull($service->getOrganizationSubscription($organization));

        $subscription->delete();

        $this->assertNull($service->getOrganizationSubscription($organization));

        $replacement = Subscription::query()->create([
            'subscriber_type' => $account->getMorphClass(),
            'subscriber_id' => $account->id,
            'name' => 'Plan B',
            'amount' => 49.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'completed_cycles' => 0,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $resolved = $service->getOrganizationSubscription($organization);
        $this->assertNotNull($resolved);
        $this->assertSame($replacement->id, $resolved->id);
    }

    public function test_forget_subscription_cache_forces_fresh_lookup(): void
    {
        $account = Account::factory()->create();
        $organization = Organization::factory()->create(['account_id' => $account->id]);

        $first = Subscription::query()->create([
            'subscriber_type' => $account->getMorphClass(),
            'subscriber_id' => $account->id,
            'name' => 'Plan',
            'amount' => 99.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'completed_cycles' => 0,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        Cache::flush();

        $service = app(SystemBillingService::class);
        $this->assertSame($first->id, $service->getOrganizationSubscription($organization)?->id);

        $service->forgetSubscriptionCache($organization);
        $first->delete();

        $second = Subscription::query()->create([
            'subscriber_type' => $account->getMorphClass(),
            'subscriber_id' => $account->id,
            'name' => 'Plan B',
            'amount' => 49.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'completed_cycles' => 0,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->assertSame($second->id, $service->getOrganizationSubscription($organization)?->id);
    }
}
