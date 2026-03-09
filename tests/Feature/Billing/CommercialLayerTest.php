<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Contracts\BillingProvider;
use App\Enums\EntitlementType;
use App\Enums\ProductStatus;
use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Support\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_activating_a_subscription_creates_account_assignment_and_exposes_plan_limits(): void
    {
        $this->useFakeBillingProvider();
        $product = Product::query()->create([
            'name' => 'Voice RSVP',
            'slug' => 'voice-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Enabled',
            'value' => 'true',
            'type' => EntitlementType::Boolean,
            'is_active' => true,
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Professional tier',
            'is_active' => true,
            'metadata' => [
                'limits' => [
                    'sms_confirmation_limit' => 1200,
                ],
            ],
        ]);

        $plan->prices()->create([
            'currency' => 'ILS',
            'amount' => 9900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $subscription = $account->subscribeToPlan($plan);
        $subscription->activate();

        $this->assertDatabaseHas('account_subscriptions', [
            'id' => $subscription->id,
            'account_id' => $account->id,
            'product_plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('account_products', [
            'account_id' => $account->id,
            'product_id' => $product->id,
            'status' => 'active',
        ]);
        $this->assertTrue(Feature::enabled($account, 'voice_rsvp_enabled'));
        $this->assertSame(1200, Feature::integer($account, 'sms_confirmation_limit'));
    }

    public function test_account_override_takes_priority_over_plan_limit(): void
    {
        $this->useFakeBillingProvider();
        $product = Product::query()->create([
            'name' => 'Twilio SMS',
            'slug' => 'twilio-sms',
            'status' => ProductStatus::Active,
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'is_active' => true,
            'metadata' => [
                'limits' => [
                    'sms_confirmation_limit' => 5000,
                ],
            ],
        ]);

        $plan->prices()->create([
            'currency' => 'USD',
            'amount' => 24900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $account->subscribeToPlan($plan)->activate();
        $account->overrideFeature('sms_confirmation_limit', 200);

        $this->assertSame(200, Feature::integer($account, 'sms_confirmation_limit'));
    }

    public function test_cancelled_subscription_is_ignored_by_feature_resolution(): void
    {
        $this->useFakeBillingProvider();
        $product = Product::query()->create([
            'name' => 'Voice RSVP',
            'slug' => 'voice-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'is_active' => true,
            'metadata' => [
                'limits' => [
                    'voice_rsvp_limit' => 50,
                ],
            ],
        ]);

        $plan->prices()->create([
            'currency' => 'ILS',
            'amount' => 4900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $subscription = $account->subscribeToPlan($plan);
        $subscription->activate();
        $subscription->cancel();

        $this->assertNull(Feature::integer($account, 'voice_rsvp_limit'));
    }

    public function test_usage_records_aggregate_by_current_subscription_billing_period(): void
    {
        $this->useFakeBillingProvider();
        $product = Product::query()->create([
            'name' => 'Voice RSVP',
            'slug' => 'voice-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'is_active' => true,
            'metadata' => [
                'limits' => [
                    'voice_minutes_limit' => 300,
                ],
            ],
        ]);

        $plan->prices()->create([
            'currency' => 'USD',
            'amount' => 9900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $subscription = $account->subscribeToPlan($plan, startedAt: now()->subDays(10));
        $subscription->activate();

        app(\App\Services\UsageMeter::class)->record($account, $product, 'voice_minutes', 120, now()->subDays(2));
        app(\App\Services\UsageMeter::class)->record($account, $product, 'voice_minutes', 30, now()->subDay());
        app(\App\Services\UsageMeter::class)->record($account, $product, 'voice_minutes', 50, now()->subMonths(2));

        $this->assertSame(150, Feature::usage($account, 'voice_minutes', $subscription));
        $this->assertSame(150, app(\App\Services\UsageMeter::class)->sumForCurrentBillingPeriod($subscription, 'voice_minutes'));
        $this->assertSame(150, Feature::remaining($account, 'voice_minutes_limit', 'voice_minutes', $subscription));
        $this->assertTrue(Feature::allowsUsage($account, 'voice_minutes_limit', 'voice_minutes', 100, $subscription));
        $this->assertFalse(Feature::allowsUsage($account, 'voice_minutes_limit', 'voice_minutes', 151, $subscription));
    }

    private function useFakeBillingProvider(): void
    {
        $this->app->bind(BillingProvider::class, static fn (): BillingProvider => new class implements BillingProvider
        {
            public function createCustomer(Account $account): array
            {
                return [
                    'provider' => 'sumit',
                    'customer_reference' => '12345',
                ];
            }

            public function createSubscription(Account $account, ProductPrice $price): array
            {
                return [
                    'provider' => 'sumit',
                    'subscription_reference' => 'sub-12345',
                    'customer_reference' => '12345',
                ];
            }

            public function cancelSubscription(AccountSubscription $subscription): void {}

            public function reportUsage(AccountSubscription $subscription, string $metric, int $quantity, array $context = []): array
            {
                return [
                    'provider' => 'sumit',
                    'charged' => false,
                ];
            }
        });
    }
}
