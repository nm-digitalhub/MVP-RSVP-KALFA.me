<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrganizationUserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EntTopBarOrganizationSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_organization_switcher_without_error_when_user_has_multiple_organizations(): void
    {
        $user = User::factory()->create();

        $orgA = Organization::query()->create([
            'name' => 'Alpha Switcher Org',
            'slug' => 'alpha-switcher-'.Str::lower(Str::random(8)),
            'billing_email' => null,
            'settings' => null,
            'is_suspended' => false,
        ]);
        $orgB = Organization::query()->create([
            'name' => 'Beta Switcher Org',
            'slug' => 'beta-switcher-'.Str::lower(Str::random(8)),
            'billing_email' => null,
            'settings' => null,
            'is_suspended' => false,
        ]);

        $orgA->users()->attach($user->id, ['role' => OrganizationUserRole::Owner->value]);
        $orgB->users()->attach($user->id, ['role' => OrganizationUserRole::Owner->value]);
        $user->update(['current_organization_id' => $orgA->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Alpha Switcher Org', false);
        $response->assertSee('Beta Switcher Org', false);
    }
}
