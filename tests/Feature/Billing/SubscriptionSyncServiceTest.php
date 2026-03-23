<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\AccountSubscription;
use App\Models\Account;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\User;
use App\Services\FeatureResolver;
use App\Services\SubscriptionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OfficeGuy\LaravelSumitGateway\Models\Subscription as OfficeGuySubscription;
use Tests\TestCase;

final class SubscriptionSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionSyncService $syncService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncService = new SubscriptionSyncService(app(FeatureResolver::class));
    }

    public function test_sync_creates_new_account_subscription_from_sumit(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Test Org',
            'owner_user_id' => User::factory()->create()->id,
        ]);

        $organization = Organization::factory()->create([
            'account_id' => $account->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Events Platform',
            'slug' => 'events',
            'status' => 'active',
        ]);

        $plan = ProductPlan::query()->create([
            'product_id' => $product->id,
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'sku' => 'EVENTS_PREMIUM',
            'is_active' => true,
        ]);

        OfficeGuySubscription::query()->create([
            'subscriber_type' => $organization->getMorphClass(),
            'subscriber_id' => $organization->id,
            'name' => 'Premium Plan',
            'status' => 'active',
            'amount' => 99.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'created_at' => now(),
            'trial_ends_at' => now()->addDays(14),
            'expires_at' => now()->addYear(),
        ]);

        $result = $this->syncService->syncAccountSubscriptions($account);

        $this->assertSame(1, $result['synced']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['errors']);

        $this->assertDatabaseHas('account_subscriptions', [
            'account_id' => $account->id,
            'product_plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }

    public function test_sync_updates_existing_subscription_status(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Test Org',
            'owner_user_id' => User::factory()->create()->id,
        ]);

        $organization = Organization::factory()->create([
            'account_id' => $account->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Events Platform',
            'slug' => 'events',
            'status' => 'active',
        ]);

        $plan = ProductPlan::query()->create([
            'product_id' => $product->id,
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'sku' => 'EVENTS_PREMIUM',
            'is_active' => true,
        ]);

        $localSub = AccountSubscription::query()->create([
            'account_id' => $account->id,
            'product_plan_id' => $plan->id,
            'status' => 'trial',
            'started_at' => now()->subDays(10),
            'trial_ends_at' => now()->subDays(1),
            'ends_at' => null,
            'metadata' => ['sumit_subscription_id' => 999],
        ]);

        OfficeGuySubscription::query()->create([
            'id' => 999,
            'subscriber_type' => $organization->getMorphClass(),
            'subscriber_id' => $organization->id,
            'name' => 'Premium Plan',
            'status' => 'active',
            'amount' => 99.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'created_at' => now()->subDays(10),
            'trial_ends_at' => now()->subDays(1),
            'expires_at' => now()->addYear(),
        ]);

        $result = $this->syncService->syncAccountSubscriptions($account);

        $this->assertSame(1, $result['synced']);

        $localSub->refresh();
        $this->assertSame('active', $localSub->status->value);
    }

    public function test_sync_skips_when_status_unchanged(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Test Org',
            'owner_user_id' => User::factory()->create()->id,
        ]);

        $organization = Organization::factory()->create([
            'account_id' => $account->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Events Platform',
            'slug' => 'events',
            'status' => 'active',
        ]);

        $plan = ProductPlan::query()->create([
            'product_id' => $product->id,
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'sku' => 'EVENTS_PREMIUM',
            'is_active' => true,
        ]);

        AccountSubscription::query()->create([
            'account_id' => $account->id,
            'product_plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now()->subDays(10),
            'ends_at' => now()->addYear(),
            'metadata' => ['sumit_subscription_id' => 888],
        ]);

        OfficeGuySubscription::query()->create([
            'id' => 888,
            'subscriber_type' => $organization->getMorphClass(),
            'subscriber_id' => $organization->id,
            'name' => 'Premium Plan',
            'status' => 'active',
            'amount' => 99.00,
            'currency' => 'ILS',
            'interval_months' => 1,
            'created_at' => now()->subDays(10),
            'expires_at' => now()->addYear(),
        ]);

        $result = $this->syncService->syncAccountSubscriptions($account);

        $this->assertSame(0, $result['synced']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_sync_skips_when_no_matching_product_plan(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Test Org',
            'owner_user_id' => User::factory()->create()->id,
        ]);

        $organization = Organization::factory()->create([
            'account_id' => $account->id,
        ]);

        OfficeGuySubscription::query()->create([
            'subscriber_type' => $organization->getMorphClass(),
            'subscriber_id' => $organization->id,
            'name' => 'Nonexistent Plan',
            'status' => 'active',
        ]);

        $result = $this->syncService->syncAccountSubscriptions($account);

        $this->assertSame(0, $result['synced']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_sync_handles_multiple_organizations(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Parent Org',
            'owner_user_id' => User::factory()->create()->id,
        ]);

        $org1 = Organization::factory()->create([
            'account_id' => $account->id,
        ]);

        $org2 = Organization::factory()->create([
            'account_id' => $account->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Events Platform',
            'slug' => 'events',
            'status' => 'active',
        ]);

        $plan = ProductPlan::query()->create([
            'product_id' => $product->id,
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'sku' => 'EVENTS_PREMIUM',
            'is_active' => true,
        ]);

        OfficeGuySubscription::query()->create([
            'subscriber_type' => $org1->getMorphClass(),
            'subscriber_id' => $org1->id,
            'name' => 'Premium Plan',
            'status' => 'active',
        ]);

        OfficeGuySubscription::query()->create([
            'subscriber_type' => $org2->getMorphClass(),
            'subscriber_id' => $org2->id,
            'name' => 'Premium Plan',
            'status' => 'active',
        ]);

        $result = $this->syncService->syncAccountSubscriptions($account);

        $this->assertSame(2, $result['synced']);
    }

    public function test_sync_invalidates_billing_cache(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Test Org',
            'owner_user_id' => User::factory()->create()->id,
        ]);

        $organization = Organization::factory()->create([
            'account_id' => $account->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Events Platform',
            'slug' => 'events',
            'status' => 'active',
        ]);

        $plan = ProductPlan::query()->create([
            'product_id' => $product->id,
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'sku' => 'EVENTS_PREMIUM',
            'is_active' => true,
        ]);

        OfficeGuySubscription::query()->create([
            'subscriber_type' => $organization->getMorphClass(),
            'subscriber_id' => $organization->id,
            'name' => 'Premium Plan',
            'status' => 'active',
        ]);

        $account->setBillingAccessCache(true);

        $this->syncService->syncAccountSubscriptions($account);

        $this->assertNull($account->getBillingAccessCache());
    }

    public function test_sync_maps_sumit_statuses_correctly(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Test Org',
            'owner_user_id' => User::factory()->create()->id,
        ]);

        $organization = Organization::factory()->create([
            'account_id' => $account->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Events Platform',
            'slug' => 'events',
            'status' => 'active',
        ]);

        $plan = ProductPlan::query()->create([
            'product_id' => $product->id,
            'name' => 'Plan',
            'slug' => 'plan',
            'sku' => 'PLAN',
            'is_active' => true,
        ]);

        $statuses = [
            'active' => 'active',
            'pending' => 'past_due',
            'paused' => 'past_due',
            'cancelled' => 'cancelled',
            'expired' => 'cancelled',
            'failed' => 'past_due',
        ];

        foreach ($statuses as $sumitStatus => $expectedLocalStatus) {
            AccountSubscription::query()->delete();
            OfficeGuySubscription::query()->where('subscriber_id', $organization->id)->delete();

            OfficeGuySubscription::query()->create([
                'subscriber_type' => $organization->getMorphClass(),
                'subscriber_id' => $organization->id,
                'name' => 'Plan',
                'status' => $sumitStatus,
            ]);

            $this->syncService->syncAccountSubscriptions($account);

            $this->assertDatabaseHas('account_subscriptions', [
                'account_id' => $account->id,
                'product_plan_id' => $plan->id,
                'status' => $expectedLocalStatus,
            ]);
        }
    }
}
