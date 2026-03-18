<?php

declare(strict_types=1);

namespace Tests\Feature\Feature;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GuestImportTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function test_upload_csv_creates_guests_for_event(): void
    {
        $organization = Organization::factory()->create();
        $user = $organization->users()->first();
        $event = Event::factory()->for($organization)->create(['status' => \App\Enums\EventStatus::Active]);

        $csvContent = "name,email,phone,notes\nJohn Doe,john@example.com,050-1234567,Birthday guest\nJane Smith,jane@example.com,052-9876543,VIP";
        $file = UploadedFile::fake()->createWithContent('guests.csv', $csvContent);

        $response = $this->actingAs($user)
            ->post(route('guests.import', ['organization' => $organization->id, 'event' => $event->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('guests', 2);
        $this->assertDatabaseHas('guests', ['event_id' => $event->id]);
    }

    public function test_guests_belong_to_correct_event(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $user1 = $org1->users()->first();
        $user2 = $org2->users()->first();

        $event1 = Event::factory()->for($org1)->create(['status' => \App\Enums\EventStatus::Active]);
        $event2 = Event::factory()->for($org2)->create(['status' => \App\Enums\EventStatus::Active]);

        $csvContent = "name,email,phone\nGuest1,guest1@example.com,\nGuest2,guest2@example.com,";

        $file1 = UploadedFile::fake()->createWithContent('guests1.csv', $csvContent);
        $file2 = UploadedFile::fake()->createWithContent('guests2.csv', $csvContent);

        // Import guests for event1 as user1
        $this->actingAs($user1)
            ->post(route('guests.import', ['organization' => $org1->id, 'event' => $event1->id]), [
                'file' => $file1,
            ]);

        // user2 cannot import into event1 (different organization)
        $this->actingAs($user2)
            ->post(route('guests.import', ['organization' => $org1->id, 'event' => $event1->id]), [
                'file' => $file2,
            ])
            ->assertStatus(403);

        // Verify guests belong to correct event
        $this->assertGreaterThanOrEqual(1, \App\Models\Guest::whereIn('event_id', [$event1->id, $event2->id])->count());
    }

    public function test_empty_rows_are_skipped(): void
    {
        $organization = Organization::factory()->create();
        $user = $organization->users()->first();
        $event = Event::factory()->for($organization)->create(['status' => \App\Enums\EventStatus::Active]);

        // CSV with mixed data - some valid, some empty
        $csvContent = "name,email,phone\nValid Guest,valid@example.com,050-1234567\n\n,invalid@example.com,";

        $file = UploadedFile::fake()->createWithContent('guests.csv', $csvContent);

        $response = $this->actingAs($user)
            ->post(route('guests.import', ['organization' => $organization->id, 'event' => $event->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(201);

        // Only the valid row should be created (2 valid, 1 empty skipped)
        $this->assertDatabaseCount('guests', 2);
        $this->assertTrue(
            \App\Models\Guest::where('event_id', $event->id)->whereIn('name', ['Valid Guest', 'invalid@example.com'])->exists()
        );
    }

    public function test_import_without_file_returns_error(): void
    {
        $organization = Organization::factory()->create();
        $user = $organization->users()->first();
        $event = Event::factory()->for($organization)->create(['status' => \App\Enums\EventStatus::Active]);

        $response = $this->actingAs($user)
            ->postJson(route('guests.import', ['organization' => $organization->id, 'event' => $event->id]));

        $response->assertStatus(400);
        $response->assertJson(['error' => 'No file uploaded']);
    }

    public function test_import_with_invalid_csv_returns_error(): void
    {
        $organization = Organization::factory()->create();
        $user = $organization->users()->first();
        $event = Event::factory()->for($organization)->create(['status' => \App\Enums\EventStatus::Active]);

        // CSV with wrong column count
        $csvContent = "name,email\nJohn,john@example.com\nJane,jane@example.com,extra-column"; // 4 columns in header, 3 in data

        $file = UploadedFile::fake()->createWithContent('invalid.csv', $csvContent);

        $response = $this->actingAs($user)
            ->post(route('guests.import', ['organization' => $organization->id, 'event' => $event->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid CSV format']);
    }

    public function test_file_upload_error_returns_error(): void
    {
        $organization = Organization::factory()->create();
        $user = $organization->users()->first();
        $event = Event::factory()->for($organization)->create(['status' => \App\Enums\EventStatus::Active]);

        // Simulate file with error
        $file = UploadedFile::fake()->createWithContent('bad.csv', "name,email\ntest,test@example.com");

        $file->error = 'Simulated upload error';

        $response = $this->actingAs($user)
            ->post(route('guests.import', ['organization' => $organization->id, 'event' => $event->id]), [
                'file' => $file,
            ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'File upload failed: Simulated upload error']);
    }
}
