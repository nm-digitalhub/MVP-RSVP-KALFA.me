<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrganizationUserRole;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureOrganizationSelected;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationMemberService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSlugGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_forms_do_not_expose_the_slug_field(): void
    {
        [$user, $organization] = $this->createAuthorizedTenant();

        $createResponse = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.create'));

        $createResponse->assertOk()
            ->assertDontSee('URL slug')
            ->assertDontSee('name="slug"', false);

        $event = Event::factory()->for($organization)->create([
            'status' => EventStatus::Draft,
            'slug' => 'existing-event-slug',
        ]);

        $editResponse = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.edit', $event));

        $editResponse->assertOk()
            ->assertDontSee('URL slug')
            ->assertDontSee('name="slug"', false);
    }

    public function test_event_create_form_uses_hebrew_translations_for_user_facing_copy(): void
    {
        [$user] = $this->createAuthorizedTenant();

        app()->setLocale('he');

        $response = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.create'));

        $response->assertOk()
            ->assertSeeText('הוסיפו אירוע חדש לארגון שלכם')
            ->assertSeeText('פרטי האירוע')
            ->assertSeeText('שם ותאריך לאירוע. הקישור הציבורי ייווצר אוטומטית.')
            ->assertSeeText('בחרו את התאריך שבו האירוע יתקיים.')
            ->assertSeeText('כאן תגדירו היכן האירוע יתקיים.')
            ->assertSeeText('JPEG, PNG, GIF או WebP. עד 5 MB. ייחתך ל-16:9.')
            ->assertSeeText('גררו להזזה, בצעו זום או צביטה. החיתוך הוא ביחס 16:9.')
            ->assertSee('aria-label="הקטן"', false)
            ->assertSee('aria-label="הגדל"', false)
            ->assertSee('aria-label="פעולות הטופס"', false);
    }

    public function test_event_edit_form_uses_hebrew_translations_for_user_facing_copy(): void
    {
        [$user, $organization] = $this->createAuthorizedTenant();

        $event = Event::factory()->for($organization)->create([
            'name' => 'אירוע לדוגמה',
            'slug' => 'sample-event',
            'status' => EventStatus::Draft,
        ]);

        app()->setLocale('he');

        $response = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.edit', $event));

        $response->assertOk()
            ->assertSeeText('עריכת אירוע')
            ->assertSeeText('פרטי האירוע')
            ->assertSeeText('כאן תגדירו היכן האירוע יתקיים.')
            ->assertSeeText('תצוגה מקדימה')
            ->assertSeeText('עדכון אירוע')
            ->assertSee('aria-label="הקטן"', false)
            ->assertSee('aria-label="הגדל"', false);
    }

    public function test_event_forms_render_consistent_text_input_and_textarea_styles(): void
    {
        [$user, $organization] = $this->createAuthorizedTenant();

        $event = Event::factory()->for($organization)->create([
            'name' => 'Styled Event',
            'slug' => 'styled-event',
            'status' => EventStatus::Draft,
        ]);

        $createResponse = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.create'));

        $editResponse = $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->get(route('dashboard.events.edit', $event));

        $createResponse->assertOk()
            ->assertSee('focus:ring-primary-600', false)
            ->assertSee('focus:outline-hidden', false)
            ->assertDontSee('class="input-base', false)
            ->assertDontSee('class="textarea-base', false);

        $editResponse->assertOk()
            ->assertSee('focus:ring-primary-600', false)
            ->assertSee('focus:outline-hidden', false)
            ->assertDontSee('class="input-base', false)
            ->assertDontSee('class="textarea-base', false);
    }

    public function test_event_store_generates_a_unique_slug_automatically(): void
    {
        [$user, $organization] = $this->createAuthorizedTenant();

        $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->post(route('dashboard.events.store'), [
                'name' => 'Summer Party',
            ])
            ->assertRedirect(route('dashboard.events.index'))
            ->assertSessionHasNoErrors();

        $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->post(route('dashboard.events.store'), [
                'name' => 'Summer Party',
            ])
            ->assertRedirect(route('dashboard.events.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('events', [
            'organization_id' => $organization->id,
            'name' => 'Summer Party',
            'slug' => 'summer-party',
        ]);

        $this->assertDatabaseHas('events', [
            'organization_id' => $organization->id,
            'name' => 'Summer Party',
            'slug' => 'summer-party-2',
        ]);
    }

    public function test_event_update_ignores_slug_input_and_preserves_existing_public_url(): void
    {
        [$user, $organization] = $this->createAuthorizedTenant();

        $event = Event::factory()->for($organization)->create([
            'name' => 'Original Event',
            'slug' => 'original-event',
            'status' => EventStatus::Active,
        ]);

        $this->withoutMiddleware([
            EnsureOrganizationSelected::class,
            EnsureAccountActive::class,
        ])->actingAs($user)
            ->put(route('dashboard.events.update', $event), [
                'name' => 'Renamed Event',
                'slug' => 'attempted-slug-change',
            ])
            ->assertRedirect(route('dashboard.events.show', $event))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Renamed Event',
            'slug' => 'original-event',
        ]);

        $this->get(route('event.show', ['slug' => 'original-event']))
            ->assertOk();
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

        return [$user->fresh(), $organization->fresh()];
    }
}
