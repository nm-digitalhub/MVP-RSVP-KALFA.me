<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrganizationUserRole;
use App\Enums\ProductPriceBillingCycle;
use App\Enums\ProductStatus;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureOrganizationSelected;
use App\Models\Event;
use App\Models\EventTable;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductPlan;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\OrganizationContext;
use App\Services\OrganizationMemberService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFormControlsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_users_filters_use_shared_input_styles(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $response = $this->actingAs($user)->get(route('system.users.index'));

        $response->assertOk()
            ->assertSee('class="input-base mt-1 w-full sm:min-w-[14rem] sm:w-auto"', false)
            ->assertSee('class="input-base mt-1 w-full sm:min-w-[10rem] sm:w-auto"', false);
    }

    public function test_event_management_pages_use_shared_input_styles_for_select_controls(): void
    {
        [$user, $organization] = $this->createAuthorizedTenant();

        $event = Event::factory()->for($organization)->create([
            'status' => EventStatus::Draft,
        ]);

        Guest::query()->create([
            'event_id' => $event->id,
            'name' => 'Dana Guest',
        ]);

        EventTable::query()->create([
            'event_id' => $event->id,
            'name' => 'Table 1',
            'capacity' => 10,
        ]);

        $seatAssignmentsResponse = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.seat-assignments.index', $event));

        $invitationsResponse = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.invitations.index', $event));

        $seatAssignmentsResponse->assertOk()
            ->assertSee('dusk="seat-assignment-select-'.$event->guests()->first()->id.'"', false)
            ->assertSee('max-w-[220px]', false);

        $invitationsResponse->assertOk()
            ->assertSee('class="w-full sm:min-w-[200px] sm:flex-1"', false)
            ->assertSee('wire:model="createForGuestId"', false);
    }

    public function test_payment_forms_use_the_shared_input_language(): void
    {
        [$user, $organization] = $this->createAuthorizedTenant();

        $event = Event::factory()->for($organization)->create([
            'status' => EventStatus::Draft,
        ]);

        Plan::query()->create([
            'name' => 'Per Event',
            'slug' => 'per-event',
            'type' => 'per_event',
            'price_cents' => 1900,
            'billing_interval' => null,
        ]);

        $checkoutTokenizeResponse = $this->actingAs($user)
            ->get(route('checkout.tokenize', [$organization, $event]));

        app(OrganizationContext::class)->set($organization);

        $product = Product::query()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'status' => ProductStatus::Active,
        ]);

        $productPlan = ProductPlan::query()->create([
            'product_id' => $product->id,
            'name' => 'Starter Monthly',
            'slug' => 'starter-monthly',
            'description' => 'Starter subscription',
            'is_active' => true,
        ]);

        ProductPrice::query()->create([
            'product_plan_id' => $productPlan->id,
            'currency' => 'ILS',
            'amount' => 4900,
            'billing_cycle' => ProductPriceBillingCycle::Monthly,
            'is_active' => true,
        ]);

        $subscriptionCheckoutResponse = $this->actingAs($user)
            ->get(route('billing.checkout', $productPlan));

        $checkoutTokenizeResponse->assertOk()
            ->assertSee('dusk="checkout-card-number"', false)
            ->assertSee('dusk="checkout-exp-month"', false)
            ->assertSee('dusk="checkout-exp-year"', false)
            ->assertSee('dusk="checkout-cvv"', false);

        $subscriptionCheckoutResponse->assertOk()
            ->assertSee('class="input-base flex-1 font-mono uppercase tracking-widest"', false)
            ->assertSee('class="flex flex-col gap-2 sm:flex-row"', false)
            ->assertSee('min-h-[48px] px-4 py-3 rounded-xl border border-brand', false);
    }

    private function createAuthorizedTenant(): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();

        $organization = Organization::query()->create([
            'name' => 'Acme Events',
            'slug' => 'acme-events',
        ]);

        app(OrganizationMemberService::class)->addMember($organization, $user, OrganizationUserRole::Owner);

        $user->update([
            'current_organization_id' => $organization->id,
        ]);

        return [$user, $organization];
    }
}
