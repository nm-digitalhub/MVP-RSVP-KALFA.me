<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_bootstrap_requires_a_valid_sanctum_token(): void
    {
        $response = $this->getJson(route('mobile.bootstrap'));

        $response->assertUnauthorized();
    }

    public function test_mobile_bootstrap_returns_minimal_context_for_an_authenticated_user(): void
    {
        $organization = Organization::factory()->create();
        $user = $organization->users()->first();
        $user->update(['current_organization_id' => $organization->id]);

        $token = $user->createToken('iPhone 17', ['mobile:base', 'mobile:read', 'mobile:write'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('mobile.bootstrap'));

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'current_organization_id', 'is_system_admin', 'last_login_at'],
                'current_organization' => ['id', 'name', 'slug', 'is_suspended'],
                'memberships' => [['id', 'name', 'slug', 'is_suspended', 'role', 'is_current']],
                'abilities',
                'flags' => ['can_use_mobile', 'has_current_organization', 'requires_organization_selection'],
                'server_time',
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('current_organization.id', $organization->id)
            ->assertJsonPath('memberships.0.id', $organization->id)
            ->assertJsonPath('memberships.0.role', 'owner')
            ->assertJsonPath('memberships.0.is_suspended', false)
            ->assertJsonPath('memberships.0.is_current', true)
            ->assertJsonPath('abilities.0', 'mobile:base')
            ->assertJsonPath('abilities.1', 'mobile:read')
            ->assertJsonPath('abilities.2', 'mobile:write')
            ->assertJsonPath('flags.can_use_mobile', true)
            ->assertJsonPath('flags.has_current_organization', true)
            ->assertJsonPath('flags.requires_organization_selection', false);

        $this->assertSame('Z', substr((string) $response->json('server_time'), -1));
        $this->assertSame(config('mobile.bootstrap.payload'), array_keys($response->json()));
    }

    public function test_mobile_bootstrap_can_return_null_current_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = $organization->users()->first();

        $token = $user->createToken('iPhone 17', ['mobile:base', 'mobile:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('mobile.bootstrap'));

        $response->assertOk()
            ->assertJsonPath('current_organization', null)
            ->assertJsonPath('memberships.0.id', $organization->id)
            ->assertJsonPath('memberships.0.is_suspended', false)
            ->assertJsonPath('flags.has_current_organization', false)
            ->assertJsonPath('flags.requires_organization_selection', true)
            ->assertJsonPath('abilities.0', 'mobile:base')
            ->assertJsonPath('abilities.1', 'mobile:read');
    }

    public function test_mobile_bootstrap_excludes_heavy_domain_and_sync_keys_from_phase_two_payload(): void
    {
        $organization = Organization::factory()->create();
        $user = $organization->users()->first();

        $token = $user->createToken('iPhone 17', ['mobile:base', 'mobile:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('mobile.bootstrap'));

        $response->assertOk()
            ->assertJsonMissingPath('events')
            ->assertJsonMissingPath('guests')
            ->assertJsonMissingPath('invitations')
            ->assertJsonMissingPath('sync')
            ->assertJsonMissingPath('outbox');
    }

    public function test_mobile_bootstrap_denies_tokens_without_mobile_read_ability(): void
    {
        $organization = Organization::factory()->create();
        $user = $organization->users()->first();

        $token = $user->createToken('iPhone 17', ['mobile:base'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('mobile.bootstrap'));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Invalid ability provided.',
            ]);
    }
}
