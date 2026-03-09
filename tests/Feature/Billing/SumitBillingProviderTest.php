<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\Account;
use App\Models\User;
use App\Services\Billing\SumitBillingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
