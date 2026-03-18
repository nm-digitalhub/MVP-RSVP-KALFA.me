<?php

declare(strict_types=1);

namespace Tests;

use App\Contracts\BillingProvider;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\StubBillingProvider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(BillingProvider::class, StubBillingProvider::class);
    }

    /**
     * Authenticate as a user with a full tenant context (org + current_organization_id).
     */
    protected function actingAsTenant(?User $user = null): static
    {
        $org = Organization::factory()->create();

        if ($user === null) {
            $user = User::factory()->create();
        }

        $org->users()->attach($user->id, ['role' => 'owner']);

        $user->update(['current_organization_id' => $org->id]);

        return $this->actingAs($user);
    }
}
