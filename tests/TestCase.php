<?php

declare(strict_types=1);

namespace Tests;

use App\Contracts\BillingProvider;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\StubBillingProvider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->forceTestingDatabase();
        $this->guardAgainstUnsafeDatabase();

        $this->app->bind(BillingProvider::class, StubBillingProvider::class);
    }

    /**
     * Force PostgreSQL test database and testing env so PHPUnit env vars (e.g. sqlite :memory:)
     * cannot point Eloquent at the wrong driver/database.
     */
    protected function forceTestingDatabase(): void
    {
        config()->set('app.env', 'testing');
        $this->app->instance('env', 'testing');

        $database = env('DB_DATABASE');
        if (! is_string($database) || $database === '' || $database === ':memory:' || ! str_contains($database, 'test')) {
            $database = (string) env('DB_TEST_DATABASE', 'kalfa_rsvp_test');
        }

        config()->set('database.default', 'pgsql');

        config()->set('database.connections.pgsql', array_merge(
            config('database.connections.pgsql', []),
            [
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5432'),
                'database' => $database,
                'username' => env('DB_USERNAME', config('database.connections.pgsql.username', 'root')),
                'password' => env('DB_PASSWORD', config('database.connections.pgsql.password', '')),
            ],
        ));
    }

    protected function guardAgainstUnsafeDatabase(): void
    {
        $env = app()->environment();
        $default = config('database.default');
        $database = config("database.connections.{$default}.database");

        if ($env !== 'testing') {
            throw new RuntimeException("Unsafe test run: APP_ENV is [$env], expected [testing].");
        }

        if ($default !== 'pgsql') {
            throw new RuntimeException("Unsafe test run: database.default is [$default], expected [pgsql].");
        }

        if (! is_string($database) || $database === '' || ! str_contains($database, 'test')) {
            throw new RuntimeException("Unsafe test run: connected database is [$database], expected a *_test database.");
        }
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
