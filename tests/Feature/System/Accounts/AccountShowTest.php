<?php

declare(strict_types=1);

namespace Tests\Feature\System\Accounts;

use App\Contracts\BillingProvider;
use App\Enums\EntitlementType;
use App\Enums\ProductStatus;
use App\Livewire\System\Accounts\Show;
use App\Models\Account;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OfficeGuy\LaravelSumitGateway\Models\CrmEntity;
use OfficeGuy\LaravelSumitGateway\Models\CrmFolder;
use Tests\TestCase;

class AccountShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(BillingProvider::class, static fn (): BillingProvider => new class implements BillingProvider
        {
            public function createCustomer(Account $account): array
            {
                return [
                    'provider' => 'sumit',
                    'customer_reference' => 'customer-123',
                ];
            }

            public function createSubscription(Account $account, ProductPrice $price): array
            {
                return [
                    'provider' => 'sumit',
                    'subscription_reference' => 'subscription-123',
                    'customer_reference' => 'customer-123',
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

    public function test_system_admin_can_search_and_connect_a_sumit_customer_candidate_by_email(): void
    {
        $admin = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Target Account',
            'owner_user_id' => $admin->id,
        ]);

        $sourceAccount = Account::query()->create([
            'type' => 'organization',
            'name' => 'Source Account',
            'owner_user_id' => $admin->id,
            'sumit_customer_id' => 445566,
        ]);

        Organization::query()->create([
            'account_id' => $sourceAccount->id,
            'name' => 'Source Organization',
            'slug' => 'source-organization',
            'billing_email' => 'billing@source.test',
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['account' => $account])
            ->set('sumit_customer_search', 'billing@source.test')
            ->call('searchSumitCustomers')
            ->assertSee('Source Organization')
            ->assertSee('445566')
            ->call('connectSumitCustomer', 445566);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'sumit_customer_id' => 445566,
        ]);
    }

    public function test_system_admin_can_disconnect_a_linked_sumit_customer(): void
    {
        $admin = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Linked Account',
            'owner_user_id' => $admin->id,
            'sumit_customer_id' => 778899,
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['account' => $account])
            ->call('disconnectSumitCustomer');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'sumit_customer_id' => null,
        ]);
    }

    public function test_system_admin_can_search_and_connect_a_sumit_crm_candidate_by_email(): void
    {
        $admin = User::factory()->create([
            'is_system_admin' => true,
            'email' => 'admin@example.com',
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'CRM Target Account',
            'owner_user_id' => $admin->id,
        ]);

        $folder = CrmFolder::query()->create([
            'sumit_folder_id' => 1669129330,
            'name' => 'לקוחות',
            'name_plural' => 'לקוחות',
            'entity_type' => 'contact',
            'is_active' => true,
            'is_system' => false,
        ]);

        CrmEntity::query()->create([
            'crm_folder_id' => $folder->id,
            'sumit_entity_id' => 1712737324,
            'sumit_customer_id' => 1712737324,
            'entity_type' => 'contact',
            'name' => 'נתנאל מבורך קלפה',
            'email' => 'netanel.kalfa@kalfa.me',
            'phone' => '0532743588',
            'status' => 'active',
            'country' => 'Israel',
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['account' => $account])
            ->set('sumit_customer_search', 'netanel.kalfa@kalfa.me')
            ->call('searchSumitCustomers')
            ->assertSee('נתנאל מבורך קלפה')
            ->assertSee('1712737324')
            ->call('connectSumitCustomer', 1712737324);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'sumit_customer_id' => 1712737324,
        ]);
    }

    public function test_system_admin_activates_a_subscription_for_a_commercial_product_instead_of_free_grant(): void
    {
        $admin = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Commercial Account',
            'owner_user_id' => $admin->id,
        ]);

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
            'metadata' => [
                'limits' => [
                    'voice_rsvp_limit' => 100,
                ],
            ],
        ]);

        $plan->prices()->create([
            'currency' => 'ILS',
            'amount' => 9900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['account' => $account])
            ->set('selected_product_id', $product->id)
            ->set('selected_plan_id', $plan->id)
            ->call('grantSelectedProduct');

        $this->assertDatabaseHas('account_subscriptions', [
            'account_id' => $account->id,
            'product_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('account_products', [
            'account_id' => $account->id,
            'product_id' => $product->id,
            'status' => 'active',
        ]);
    }

    public function test_system_admin_must_select_a_plan_before_activating_a_commercial_product(): void
    {
        $admin = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Commercial Account',
            'owner_user_id' => $admin->id,
        ]);

        $product = Product::query()->create([
            'name' => 'Twilio SMS',
            'slug' => 'twilio-sms',
            'status' => ProductStatus::Active,
        ]);

        $product->productPlans()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'is_active' => true,
        ])->prices()->create([
            'currency' => 'ILS',
            'amount' => 4900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['account' => $account])
            ->set('selected_product_id', $product->id)
            ->call('grantSelectedProduct')
            ->assertHasErrors(['selected_plan_id']);

        $this->assertDatabaseCount('account_subscriptions', 0);
        $this->assertDatabaseCount('account_products', 0);
    }

    public function test_system_admin_sees_a_validation_error_when_sumit_token_is_missing_for_a_commercial_subscription(): void
    {
        $this->app->bind(BillingProvider::class, static fn (): BillingProvider => new class implements BillingProvider
        {
            public function createCustomer(Account $account): array
            {
                return [
                    'provider' => 'sumit',
                    'customer_reference' => 'customer-123',
                ];
            }

            public function createSubscription(Account $account, ProductPrice $price): array
            {
                throw new \RuntimeException('SUMIT subscription requires a default OfficeGuy payment token for the account.');
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

        $admin = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Tokenless Account',
            'owner_user_id' => $admin->id,
        ]);

        $product = Product::query()->create([
            'name' => 'AI Voice Agent',
            'slug' => 'ai-voice-agent',
            'status' => ProductStatus::Active,
        ]);

        $plan = $product->productPlans()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'is_active' => true,
        ]);

        $plan->prices()->create([
            'currency' => 'ILS',
            'amount' => 9900,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['account' => $account])
            ->set('selected_product_id', $product->id)
            ->set('selected_plan_id', $plan->id)
            ->call('grantSelectedProduct')
            ->assertHasErrors(['selected_plan_id']);

        $this->assertDatabaseCount('account_subscriptions', 0);
        $this->assertDatabaseCount('account_products', 0);
    }
}
