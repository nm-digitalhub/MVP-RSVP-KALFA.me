<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Contracts\BillingProvider;
use App\Enums\EntitlementType;
use App\Enums\ProductStatus;
use App\Events\ProductEngineEvent;
use App\Models\Account;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UsageOverageBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_limit_overage_charges_only_the_newly_exceeded_units(): void
    {
        Event::fake([ProductEngineEvent::class]);

        $provider = new class implements BillingProvider
        {
            /** @var array<int, array<string, mixed>> */
            public array $calls = [];

            public function createCustomer(Account $account): array
            {
                return [
                    'provider' => 'sumit',
                    'customer_reference' => '123',
                ];
            }

            public function createSubscription(Account $account, ProductPrice $price): array
            {
                return [
                    'provider' => 'sumit',
                    'subscription_reference' => 'sub-123',
                    'customer_reference' => '123',
                ];
            }

            public function cancelSubscription(\App\Models\AccountSubscription $subscription): void {}

            public function reportUsage(\App\Models\AccountSubscription $subscription, string $metric, int $quantity, array $context = []): array
            {
                $this->calls[] = [
                    'subscription_id' => $subscription->id,
                    'metric' => $metric,
                    'quantity' => $quantity,
                    'context' => $context,
                ];

                return [
                    'provider' => 'sumit',
                    'charged' => true,
                    'charge_reference' => sprintf('charge-%d', count($this->calls)),
                ];
            }
        };

        $this->app->instance(BillingProvider::class, $provider);

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
            'name' => 'Growth',
            'slug' => 'growth',
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
                'commercial' => [
                    'overage_metric_key' => 'voice_minutes',
                    'overage_amount_minor' => 25,
                    'overage_unit' => 'minute',
                    'currency' => 'ILS',
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

        $first = app(\App\Services\UsageMeter::class)->record($account, $product, 'voice_minutes', 90);
        $second = app(\App\Services\UsageMeter::class)->record($account, $product, 'voice_minutes', 20);
        $third = app(\App\Services\UsageMeter::class)->record($account, $product, 'voice_minutes', 5);

        $this->assertCount(2, $provider->calls);
        $this->assertSame(10, $provider->calls[0]['quantity']);
        $this->assertSame(250, $provider->calls[0]['context']['amount_minor']);
        $this->assertSame(5, $provider->calls[1]['quantity']);
        $this->assertSame(125, $provider->calls[1]['context']['amount_minor']);

        $this->assertNull(data_get($first->fresh()->metadata, 'billing'));
        $this->assertSame(10, data_get($second->fresh()->metadata, 'billing.billing_quantity'));
        $this->assertSame(250, data_get($second->fresh()->metadata, 'billing.amount_minor'));
        $this->assertSame('charge-1', data_get($second->fresh()->metadata, 'billing.charge_reference'));
        $this->assertSame(5, data_get($third->fresh()->metadata, 'billing.billing_quantity'));
        $this->assertSame('charge-2', data_get($third->fresh()->metadata, 'billing.charge_reference'));

        Event::assertDispatched(ProductEngineEvent::class, fn (ProductEngineEvent $event): bool => $event->action === 'usage.overage_charged');
    }
}
