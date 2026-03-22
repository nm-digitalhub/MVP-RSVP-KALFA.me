<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\InvitationStatus;
use App\Enums\OrganizationUserRole;
use App\Enums\ProductStatus;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureOrganizationSelected;
use App\Http\Middleware\SpatiePermissionTeam;
use App\Livewire\Organizations\Create;
use App\Models\Account;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\User;
use App\Services\OrganizationMemberService;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventCreationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_creator_can_open_event_creation_after_livewire_creation_flow(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('name', 'Trial Organization')
            ->call('save')
            ->assertRedirect(route('select-plan'));

        $organization = Organization::query()
            ->where('name', 'Trial Organization')
            ->firstOrFail();

        $response = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user->fresh())
            ->get(route('dashboard.events.create'));

        $response->assertOk();
        $this->assertSame($organization->id, $user->fresh()->current_organization_id);
    }

    public function test_event_creation_returns_forbidden_when_user_lacks_permission_instead_of_billing_redirect(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $user = $organization->users()->firstOrFail();
        $user->update(['current_organization_id' => $organization->id]);

        $response = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
            SpatiePermissionTeam::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.create'));

        $response->assertForbidden();
        $response->assertSessionMissing('warning');
    }

    public function test_existing_organization_owner_without_synced_team_role_is_healed_by_team_middleware(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $user = $organization->users()->firstOrFail();
        $user->update(['current_organization_id' => $organization->id]);

        $this->assertDatabaseMissing('model_has_roles', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.create'));

        $response->assertOk();
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
    }

    public function test_billing_account_page_is_accessible_without_billing_access(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $user = $organization->users()->firstOrFail();
        $user->update(['current_organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->get(route('billing.account'));

        $response->assertOk();
    }

    public function test_event_show_uses_the_event_organization_billing_access_instead_of_the_current_organization(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();

        $unpaidOrganization = $this->createOrganizationForUser($user, 'Unpaid Org');
        $trialOrganization = $this->createOrganizationForUser($user, 'Trial Org');

        $this->attachAccount($unpaidOrganization);
        $trialAccount = $this->attachAccount($trialOrganization);
        $this->startTrial($trialAccount);

        $user->update(['current_organization_id' => $unpaidOrganization->id]);

        $event = Event::factory()->for($trialOrganization)->create([
            'status' => EventStatus::Draft,
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard.events.show', $event));

        $response->assertOk()
            ->assertSeeText($event->name);
    }

    public function test_trial_covered_event_is_activated_on_show_without_rendering_payment_call_to_action(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, 'Trial Covered Org');
        $account = $this->attachAccount($organization);
        $this->startTrial($account);

        $user->update(['current_organization_id' => $organization->id]);

        $event = Event::factory()->for($organization)->create([
            'status' => EventStatus::Draft,
        ]);

        $invitation = Invitation::query()->create([
            'event_id' => $event->id,
            'guest_id' => null,
            'token' => str()->random(64),
            'slug' => 'trial-covered-'.$event->id,
            'status' => InvitationStatus::Pending,
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard.events.show', $event));

        $response->assertOk()
            ->assertDontSeeText('Proceed to payment');

        $this->assertSame(EventStatus::Active, $event->fresh()->status);

        $this->get(route('rsvp.show', $invitation->slug))
            ->assertOk();
    }

    public function test_trial_covered_dashboard_event_creation_starts_active(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, 'Trial Creation Org');
        $account = $this->attachAccount($organization);
        $this->startTrial($account);

        $user->update(['current_organization_id' => $organization->id]);

        $this->actingAs($user)
            ->post(route('dashboard.events.store'), [
                'name' => 'Covered Launch Event',
            ])
            ->assertRedirect(route('dashboard.events.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('events', [
            'organization_id' => $organization->id,
            'name' => 'Covered Launch Event',
            'status' => EventStatus::Active->value,
        ]);
    }

    public function test_trial_covered_event_checkout_redirects_back_to_event_instead_of_rendering_payment_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $organization = $this->createOrganizationForUser($user, 'Trial Checkout Org');
        $account = $this->attachAccount($organization);
        $this->startTrial($account);

        $user->update(['current_organization_id' => $organization->id]);

        $event = Event::factory()->for($organization)->create([
            'status' => EventStatus::Draft,
        ]);

        $this->actingAs($user)
            ->get(route('checkout.tokenize', [$organization, $event]))
            ->assertRedirect(route('dashboard.events.show', $event));
    }

    private function createOrganizationForUser(User $user, string $name): Organization
    {
        $organization = Organization::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->value(),
        ]);

        app(OrganizationMemberService::class)->addMember($organization, $user, OrganizationUserRole::Owner);

        return $organization;
    }

    private function attachAccount(Organization $organization): Account
    {
        $account = Account::query()->create([
            'type' => 'organization',
            'name' => $organization->name,
            'owner_user_id' => $organization->owner()?->id,
        ]);

        $organization->update(['account_id' => $account->id]);

        return $account;
    }

    private function startTrial(Account $account): void
    {
        $product = Product::query()->create([
            'name' => 'Trial Product '.$account->id,
            'slug' => 'trial-product-'.$account->id,
            'status' => ProductStatus::Active,
        ]);

        $plan = ProductPlan::query()->create([
            'product_id' => $product->id,
            'name' => 'Trial Plan '.$account->id,
            'slug' => 'trial-plan-'.$account->id,
            'is_active' => true,
        ]);

        app(SubscriptionService::class)->startTrial(
            account: $account,
            plan: $plan,
            trialEndsAt: now()->addDays(14),
        );

        $account->invalidateBillingAccessCache();
    }
}
