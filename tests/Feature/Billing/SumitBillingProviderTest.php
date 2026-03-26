<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\Account;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\Billing\SumitBillingProvider;
use App\Services\Sumit\SumitUsageChargePayable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use OfficeGuy\LaravelSumitGateway\Models\OfficeGuyToken;
use OfficeGuy\LaravelSumitGateway\Models\Subscription as OfficeGuySubscription;
use OfficeGuy\LaravelSumitGateway\Services\PaymentService;
use OfficeGuy\LaravelSumitGateway\Services\SubscriptionService as OfficeGuySubscriptionService;
use Tests\TestCase;

class SumitBillingProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_customer_reuses_existing_sumit_customer_id_without_api_calls(): void
    {
        $owner = User::factory()->create();
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Kalfa',
            'owner_user_id' => $owner->id,
            'sumit_customer_id' => 778899,
        ]);

        $result = app(SumitBillingProvider::class)->createCustomer($account);

        $this->assertSame('sumit', $result['provider']);
        $this->assertSame('778899', $result['customer_reference']);
        $this->assertTrue($result['metadata']['existing']);
    }

    public function test_create_customer_requires_owner_email_when_customer_is_not_synced(): void
    {
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'No Email Account',
        ]);

        $this->expectExceptionMessage(sprintf('Account %d must have an owner email before syncing to SUMIT.', $account->id));

        app(SumitBillingProvider::class)->createCustomer($account);
    }

    public function test_create_subscription_includes_product_plan_sku_in_metadata(): void
    {
        // Override the default billing provider binding
        $this->app->bind(\App\Contracts\BillingProvider::class, SumitBillingProvider::class);

        // Fake events to prevent listener errors
        Event::fake();

        $owner = User::factory()->create();
        $account = Account::factory()->create([
            'owner_user_id' => $owner->id,
            'sumit_customer_id' => 12345,
        ]);

        $product = Product::factory()->create(['name' => 'Test Product']);
        $productPlan = ProductPlan::factory()->create([
            'product_id' => $product->id,
            'name' => 'Pro Plan',
            'sku' => 'PRO-MONTHLY-001',
        ]);
        $productPrice = ProductPrice::factory()->create([
            'product_plan_id' => $productPlan->id,
            'amount' => 9900,
            'currency' => 'ILS',
        ]);

        // Create a default payment token for the account
        OfficeGuyToken::query()->create([
            'owner_type' => $account::class,
            'owner_id' => $account->id,
            'token' => 'test_token_123',
            'citizen_id' => '123456789',
            'expiry_month' => 12,
            'expiry_year' => 2030,
            'last_four' => '4242',
            'is_default' => true,
        ]);

        // Capture the OfficeGuy subscription to inspect metadata
        $capturedSubscription = null;

        // Partially mock the SubscriptionService to capture the created subscription
        $partialMock = \Mockery::mock(OfficeGuySubscriptionService::class)->makePartial();
        $partialMock->shouldReceive('create')
            ->once()
            ->with(
                \Mockery::type(Account::class),
                \Mockery::type('string'),
                \Mockery::type('float'),
                \Mockery::type('string'),
                \Mockery::type('integer'),
                null,
                \Mockery::type('integer'),
                \Mockery::on(function (array $metadata) use (&$capturedSubscription) {
                    $capturedSubscription = (object) ['metadata' => $metadata];
                    return true;
                }),
            )
            ->andReturnUsing(function (...$args) {
                // Call the real create method to actually create the subscription
                return OfficeGuySubscriptionService::create(...$args);
            });

        $this->instance(OfficeGuySubscriptionService::class, $partialMock);

        // Mock PaymentService
        $this->instance(PaymentService::class, \Mockery::mock(PaymentService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processCharge')
                ->once()
                ->andReturn([
                    'success' => true,
                    'response' => [
                        'Data' => [
                            'RecurringID' => 'REC-123',
                        ],
                    ],
                ]);
        }));

        $result = app(SumitBillingProvider::class)->createSubscription($account, $productPrice);

        // Verify the subscription was created
        $this->assertIsArray($result);
        $this->assertArrayHasKey('provider', $result);
        $this->assertSame('sumit', $result['provider']);

        // Verify metadata contains product_plan_sku
        $this->assertNotNull($capturedSubscription, 'Subscription should have been captured');
        $this->assertIsArray($capturedSubscription->metadata);
        $this->assertArrayHasKey('product_plan_sku', $capturedSubscription->metadata);
        $this->assertSame('PRO-MONTHLY-001', $capturedSubscription->metadata['product_plan_sku']);
    }
}
