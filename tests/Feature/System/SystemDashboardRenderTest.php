<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Livewire\System\Dashboard;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use OfficeGuy\LaravelSumitGateway\Models\Subscription;
use Tests\TestCase;

final class SystemDashboardRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_render_system_dashboard_livewire_component(): void
    {
        App::setLocale('en');

        $this->withoutVite();

        $user = User::factory()->create([
            'is_system_admin' => true,
            'email_verified_at' => now(),
        ]);

        $account = Account::factory()->create();
        for ($i = 0; $i < 42; $i++) {
            Subscription::query()->create([
                'subscriber_type' => $account->getMorphClass(),
                'subscriber_id' => $account->id,
                'name' => 'Sub '.$i,
                'amount' => 10.00,
                'currency' => 'ILS',
                'interval_months' => 1,
                'completed_cycles' => 0,
                'status' => Subscription::STATUS_ACTIVE,
            ]);
        }

        $html = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee(__('Key metrics'))
            ->html();

        $this->assertMatchesRegularExpression(
            '/'.preg_quote(__('Active subscriptions').':', '/').'<\/span>\s*<strong>42<\/strong>/s',
            $html
        );
    }

    /**
     * Regression guard: billing card should aggregate subscriptions (count) instead of loading all rows.
     */
    public function test_system_dashboard_uses_count_aggregate_for_active_subscriptions(): void
    {
        App::setLocale('en');

        $this->withoutVite();

        $user = User::factory()->create([
            'is_system_admin' => true,
            'email_verified_at' => now(),
        ]);

        DB::enableQueryLog();

        Livewire::actingAs($user)->test(Dashboard::class)->assertOk();

        $subscriptionSql = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $q): bool => str_contains(strtolower($q), 'officeguy_subscriptions'))
            ->implode(' ');

        $this->assertNotSame('', $subscriptionSql);
        $this->assertStringContainsStringIgnoringCase('count', $subscriptionSql);
    }
}
