<?php

declare(strict_types=1);

namespace Tests\Feature\System\Accounts;

use App\Contracts\BillingProvider;
use App\Models\Account;
use App\Models\User;
use App\Services\Sumit\AccountPaymentMethodManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OfficeGuy\LaravelSumitGateway\Models\OfficeGuyToken;
use Tests\TestCase;

class AccountPaymentMethodControllerTest extends TestCase
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

            public function createSubscription(Account $account, \App\Models\ProductPrice $price): array
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

    /**
     * @return array{0: User, 1: Account}
     */
    private function systemAdminAndAccount(): array
    {
        $admin = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $account = Account::query()->create([
            'type' => 'organization',
            'name' => 'Billing Account',
            'owner_user_id' => $admin->id,
            'sumit_customer_id' => 1712737324,
        ]);

        return [$admin, $account];
    }

    public function test_system_admin_can_add_a_payment_method_to_an_account(): void
    {
        [$admin, $account] = $this->systemAdminAndAccount();

        $fakeManager = new class extends AccountPaymentMethodManager
        {
            public function __construct() {}

            public function storeSingleUseToken(Account $account, string $singleUseToken): OfficeGuyToken
            {
                return OfficeGuyToken::query()->create([
                    'owner_type' => $account::class,
                    'owner_id' => $account->id,
                    'token' => 'tok_12345678',
                    'gateway_id' => 'officeguy',
                    'card_type' => '1',
                    'last_four' => '4242',
                    'expiry_month' => '12',
                    'expiry_year' => '2030',
                    'is_default' => true,
                    'metadata' => [],
                ]);
            }
        };

        $this->app->instance(AccountPaymentMethodManager::class, $fakeManager);

        $response = $this->actingAs($admin)->post(route('system.accounts.payment-methods.store', $account), [
            'og-token' => 'single-use-token-123',
        ]);

        $response->assertRedirect(route('system.accounts.show', $account).'#billing-methods');
        $response->assertSessionHas('success', __('Payment method added and set as default.'));
        $this->assertDatabaseHas('officeguy_tokens', [
            'owner_type' => Account::class,
            'owner_id' => $account->id,
            'last_four' => '4242',
            'is_default' => true,
        ]);
    }

    public function test_system_admin_is_redirected_back_to_billing_methods_when_sumit_token_is_missing(): void
    {
        [$admin, $account] = $this->systemAdminAndAccount();

        $response = $this
            ->from(route('system.accounts.show', $account).'#billing-methods')
            ->actingAs($admin)
            ->post(route('system.accounts.payment-methods.store', $account), []);

        $response->assertRedirect(route('system.accounts.show', $account).'#billing-methods');
        $response->assertSessionHasErrors(['og-token']);
        $this->assertDatabaseCount('officeguy_tokens', 0);
    }

    public function test_system_admin_sees_the_real_sumit_decline_message_when_saving_a_payment_method_fails(): void
    {
        [$admin, $account] = $this->systemAdminAndAccount();

        $fakeManager = new class extends AccountPaymentMethodManager
        {
            public function __construct() {}

            public function storeSingleUseToken(Account $account, string $singleUseToken): OfficeGuyToken
            {
                throw new \RuntimeException('חברת כרטיס האשראי של הלקוח/ה לא אישרה את החיוב. ניתן להתקשר לחברת כרטיסי האשראי (של הלקוח/ה) או לנסות לחייב כרטיס אחר.');
            }
        };

        $this->app->instance(AccountPaymentMethodManager::class, $fakeManager);

        $response = $this->actingAs($admin)->post(route('system.accounts.payment-methods.store', $account), [
            'og-token' => 'single-use-token-123',
        ]);

        $response->assertRedirect(route('system.accounts.show', $account).'#billing-methods');
        $response->assertSessionHas(
            'error',
            'חברת כרטיס האשראי של הלקוח/ה לא אישרה את החיוב. ניתן להתקשר לחברת כרטיסי האשראי (של הלקוח/ה) או לנסות לחייב כרטיס אחר.',
        );
        $this->assertDatabaseCount('officeguy_tokens', 0);
    }

    public function test_system_admin_can_set_a_saved_payment_method_as_default(): void
    {
        [$admin, $account] = $this->systemAdminAndAccount();

        $firstToken = OfficeGuyToken::query()->create([
            'owner_type' => Account::class,
            'owner_id' => $account->id,
            'token' => 'tok_first',
            'gateway_id' => 'officeguy',
            'card_type' => '1',
            'last_four' => '1111',
            'expiry_month' => '12',
            'expiry_year' => '2030',
            'is_default' => true,
            'metadata' => [],
        ]);

        $secondToken = OfficeGuyToken::query()->create([
            'owner_type' => Account::class,
            'owner_id' => $account->id,
            'token' => 'tok_second',
            'gateway_id' => 'officeguy',
            'card_type' => '1',
            'last_four' => '2222',
            'expiry_month' => '12',
            'expiry_year' => '2031',
            'is_default' => false,
            'metadata' => [],
        ]);

        $fakeManager = new class extends AccountPaymentMethodManager
        {
            public function __construct() {}

            public function setDefault(Account $account, OfficeGuyToken $token): void
            {
                $token->setAsDefault();
            }
        };

        $this->app->instance(AccountPaymentMethodManager::class, $fakeManager);

        $response = $this->actingAs($admin)->post(route('system.accounts.payment-methods.default', [$account, $secondToken]));

        $response->assertRedirect(route('system.accounts.show', $account).'#billing-methods');
        $response->assertSessionHas('success', __('Default payment method updated successfully.'));
        $this->assertDatabaseHas('officeguy_tokens', [
            'id' => $firstToken->id,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('officeguy_tokens', [
            'id' => $secondToken->id,
            'is_default' => true,
        ]);
    }

    public function test_system_admin_can_remove_a_saved_payment_method(): void
    {
        [$admin, $account] = $this->systemAdminAndAccount();

        $token = OfficeGuyToken::query()->create([
            'owner_type' => Account::class,
            'owner_id' => $account->id,
            'token' => 'tok_delete',
            'gateway_id' => 'officeguy',
            'card_type' => '1',
            'last_four' => '3333',
            'expiry_month' => '12',
            'expiry_year' => '2032',
            'is_default' => true,
            'metadata' => [],
        ]);

        $fakeManager = new class extends AccountPaymentMethodManager
        {
            public function __construct() {}

            public function delete(Account $account, OfficeGuyToken $token): void
            {
                $token->delete();
            }
        };

        $this->app->instance(AccountPaymentMethodManager::class, $fakeManager);

        $response = $this->actingAs($admin)->delete(route('system.accounts.payment-methods.destroy', [$account, $token]));

        $response->assertRedirect(route('system.accounts.show', $account).'#billing-methods');
        $response->assertSessionHas('success', __('Payment method removed successfully.'));
        $this->assertSoftDeleted('officeguy_tokens', [
            'id' => $token->id,
        ]);
    }
}
