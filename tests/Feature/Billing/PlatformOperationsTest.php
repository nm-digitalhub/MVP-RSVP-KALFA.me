<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Contracts\BillingProvider;
use App\Enums\EntitlementType;
use App\Enums\ProductStatus;
use App\Enums\UsagePolicyDecision;
use App\Events\ProductEngineEvent;
use App\Models\Account;
use App\Models\Product;
use App\Services\Billing\SumitBillingProvider;
use App\Services\ProductIntegrityChecker;
use App\Services\SubscriptionService;
use App\Support\UsagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PlatformOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_provider_binding_resolves_sumit_provider(): void
    {
        // Re-bind the real provider (TestCase setUp overrides with StubBillingProvider)
        $this->app->bind(BillingProvider::class, SumitBillingProvider::class);
        $this->app->forgetInstance(BillingProvider::class);
        $this->assertInstanceOf(SumitBillingProvider::class, app(BillingProvider::class));
    }

    public function test_subscription_lifecycle_transitions_emit_product_engine_events(): void
    {
        $this->useFakeBillingProvider();
        Event::fake([ProductEngineEvent::class]);

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
            'is_active' => true,
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

        $subscription = $account->startTrial($plan, now()->addDay());
        $subscription->activate();
        $subscription->suspend();
        $subscription->renew();
        $subscription->cancel();

        Event::assertDispatched(ProductEngineEvent::class, fn (ProductEngineEvent $event): bool => $event->action === 'subscription.trial_started');
        Event::assertDispatched(ProductEngineEvent::class, fn (ProductEngineEvent $event): bool => $event->action === 'subscription.activated');
        Event::assertDispatched(ProductEngineEvent::class, fn (ProductEngineEvent $event): bool => $event->action === 'product.granted');
        Event::assertDispatched(ProductEngineEvent::class, fn (ProductEngineEvent $event): bool => $event->action === 'subscription.suspended');
        Event::assertDispatched(ProductEngineEvent::class, fn (ProductEngineEvent $event): bool => $event->action === 'subscription.renewed');
        Event::assertDispatched(ProductEngineEvent::class, fn (ProductEngineEvent $event): bool => $event->action === 'subscription.cancelled');
    }

    public function test_trial_expiration_transitions_to_active_or_cancelled(): void
    {
        $this->useFakeBillingProvider();
        $account = Account::query()->create([
            'type' => 'organization',
        ]);

        $billableProduct = Product::query()->create([
            'name' => 'Billable',
            'slug' => 'billable',
            'status' => ProductStatus::Active,
        ]);

        $billablePlan = $billableProduct->productPlans()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'is_active' => true,
        ]);

        $billablePlan->prices()->create([
            'currency' => 'USD',
            'amount' => 1000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $freeProduct = Product::query()->create([
            'name' => 'Free Trial',
            'slug' => 'free-trial',
            'status' => ProductStatus::Active,
        ]);

        $freePlan = $freeProduct->productPlans()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'is_active' => true,
        ]);

        $activates = $account->startTrial($billablePlan, now()->subMinute());
        $cancels = $account->startTrial($freePlan, now()->subMinute());

        $processed = app(SubscriptionService::class)->processTrialExpirations();

        $this->assertSame(2, $processed);
        $this->assertSame('active', $activates->fresh()->status->value);
        $this->assertSame('cancelled', $cancels->fresh()->status->value);
    }

    public function test_usage_policy_supports_soft_and_hard_limit_modes_and_logs_exceeded_limits(): void
    {
        $this->useFakeBillingProvider();
        Event::fake([ProductEngineEvent::class]);

        $product = Product::query()->create([
            'name' => 'Voice RSVP',
            'slug' => 'voice-rsvp',
            'status' => ProductStatus::Active,
        ]);

        $softPlan = $product->productPlans()->create([
            'name' => 'Soft',
            'slug' => 'soft',
            'is_active' => true,
            'metadata' => [
                'limits' => [
                    'voice_minutes_limit' => 100,
                ],
                'usage_policies' => [
                    'voice_minutes' => [
                        'mode' => 'soft',
                    ],
                ],
            ],
        ]);

        $softPlan->prices()->create([
            'currency' => 'ILS',
            'amount' => 5000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $hardPlan = $product->productPlans()->create([
            'name' => 'Hard',
            'slug' => 'hard',
            'is_active' => true,
            'metadata' => [
                'limits' => [
                    'voice_minutes_limit' => 100,
                ],
                'usage_policies' => [
                    'voice_minutes' => [
                        'mode' => 'hard',
                    ],
                ],
            ],
        ]);

        $hardPlan->prices()->create([
            'currency' => 'ILS',
            'amount' => 7000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $softAccount = Account::query()->create(['type' => 'organization']);
        $softSubscription = $softAccount->subscribeToPlan($softPlan);
        $softSubscription->activate();
        app(\App\Services\UsageMeter::class)->record($softAccount, $product, 'voice_minutes', 90);

        $hardAccount = Account::query()->create(['type' => 'organization']);
        $hardSubscription = $hardAccount->subscribeToPlan($hardPlan);
        $hardSubscription->activate();
        app(\App\Services\UsageMeter::class)->record($hardAccount, $product, 'voice_minutes', 90);

        $this->assertSame(UsagePolicyDecision::AllowedWithOverage, UsagePolicy::check($softAccount, 'voice_minutes', 20, $softSubscription));
        $this->assertSame(UsagePolicyDecision::Blocked, UsagePolicy::check($hardAccount, 'voice_minutes', 20, $hardSubscription));

        Event::assertDispatched(ProductEngineEvent::class, fn (ProductEngineEvent $event): bool => $event->action === 'limits.exceeded');
    }

    public function test_product_engine_events_are_written_to_audit_log_and_integrity_checker_reports_issues(): void
    {
        $product = Product::query()->create([
            'name' => 'Broken Product',
            'slug' => 'broken-product',
            'status' => ProductStatus::Draft,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Enabled',
            'value' => 'true',
            'type' => EntitlementType::Boolean,
            'is_active' => true,
        ]);

        $product->entitlements()->create([
            'feature_key' => 'voice_rsvp_enabled',
            'label' => 'Voice RSVP Enabled Duplicate',
            'value' => 'enabled',
            'type' => EntitlementType::Text,
            'is_active' => true,
        ]);

        $product->productPlans()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'is_active' => true,
        ]);

        $issues = app(ProductIntegrityChecker::class)->issuesForProduct($product->fresh(['entitlements', 'productPlans.activePrices']));

        $this->assertNotEmpty($issues);
        $this->assertTrue(collect($issues)->contains(fn (string $issue): bool => str_contains($issue, 'Duplicate entitlement feature key')));
        $this->assertTrue(collect($issues)->contains(fn (string $issue): bool => str_contains($issue, 'missing an active price')));

        $account = Account::query()->create(['type' => 'organization']);
        $validProduct = Product::query()->create([
            'name' => 'Valid Product',
            'slug' => 'valid-product',
            'status' => ProductStatus::Active,
        ]);
        $validProduct->entitlements()->create([
            'feature_key' => 'twilio_enabled',
            'label' => 'Twilio',
            'value' => 'true',
            'type' => EntitlementType::Boolean,
            'is_active' => true,
        ]);

        $account->grantProduct($validProduct);

        $this->assertDatabaseHas('system_audit_logs', [
            'action' => 'product_engine.product.granted',
        ]);
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

            public function createSubscription(Account $account, \App\Models\ProductPrice $price): array
            {
                return [
                    'provider' => 'sumit',
                    'subscription_reference' => 'sub-12345',
                    'customer_reference' => '12345',
                    'metadata' => [
                        'officeguy_subscription_id' => 1,
                    ],
                ];
            }

            public function cancelSubscription(\App\Models\AccountSubscription $subscription): void {}

            public function reportUsage(\App\Models\AccountSubscription $subscription, string $metric, int $quantity, array $context = []): array
            {
                return [
                    'provider' => 'sumit',
                    'charged' => false,
                ];
            }
        });
    }
}
