<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileEntryRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_mobile_entry_route_is_available_for_guests_without_redirecting_to_web_login(): void
    {
        $response = $this->get(route('mobile.entry'));

        $response->assertOk()
            ->assertViewIs('mobile.shell');
    }

    public function test_mobile_entry_route_is_available_for_authenticated_users_without_redirecting_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('mobile.entry'));

        $response->assertOk()
            ->assertViewIs('mobile.shell');
    }

    public function test_mobile_entry_route_exposes_the_mobile_auth_state_model_to_the_client(): void
    {
        $response = $this->get(route('mobile.entry'));

        $response->assertOk()
            ->assertSee('data-mobile-shell-runtime="isolated"', false)
            ->assertSee('mobile-shell-state-config', false)
            ->assertSee('"initial":"unauthenticated"', false)
            ->assertSee('"status_url":"\/mobile\/session"', false)
            ->assertSee('"store_url":"\/mobile\/session"', false)
            ->assertSee('"destroy_url":"\/mobile\/session"', false)
            ->assertSee('"access_token_key":"kalfa.mobile.access_token"', false)
            ->assertSee('"base_url":"https:\/\/kalfa.me"', false)
            ->assertSee('"login_url":"https:\/\/kalfa.me\/api\/mobile\/auth\/login"', false)
            ->assertSee('"bootstrap_url":"https:\/\/kalfa.me\/api\/bootstrap"', false)
            ->assertSee('"unauthenticated"', false)
            ->assertSee('"authenticated"', false)
            ->assertSee('"syncing"', false)
            ->assertSee('"offline-stale"', false)
            ->assertSee('"revoked"', false);
    }

    public function test_nativephp_start_url_defaults_to_the_mobile_entry_route(): void
    {
        $this->assertSame('/mobile', config('nativephp.start_url'));
        $this->assertSame('/mobile', route('mobile.entry', [], false));
    }
}
