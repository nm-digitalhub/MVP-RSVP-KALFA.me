<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Database\ReadReplicaHealthService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class ReadReplicaHealthServiceTest extends TestCase
{
    private ReadReplicaHealthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReadReplicaHealthService(app('db'));
        Cache::flush();
    }

    public function test_returns_read_connection_when_replica_healthy(): void
    {
        // Mock successful connection
        Cache::put('read_replica_health:status', true, 60);

        $connection = $this->service->getReadConnection();

        $this->assertSame('pgsql_read', $connection);
    }

    public function test_returns_write_connection_when_replica_unhealthy(): void
    {
        // Mock unhealthy connection
        Cache::put('read_replica_health:status', false, 60);

        $connection = $this->service->getReadConnection();

        $this->assertSame('pgsql', $connection);
    }

    public function test_checks_replica_health_and_caches_result(): void
    {
        // Clear any cached value
        Cache::forget('read_replica_health:status');

        // In test environment, we expect this to work with SQLite
        // The actual connection check may fail, but we test the caching logic
        $isHealthy = $this->service->isReplicaHealthy();

        // Should have cached the result (either true or false depending on DB setup)
        $cached = Cache::get('read_replica_health:status');

        $this->assertNotNull($cached);
        $this->assertSame($isHealthy, $cached);
    }

    public function test_mark_replica_unhealthy_sets_cache(): void
    {
        $this->service->markReplicaUnhealthy();

        $this->assertFalse(Cache::get('read_replica_health:status'));
    }

    public function test_mark_replica_healthy_sets_cache(): void
    {
        $this->service->markReplicaHealthy();

        $this->assertTrue(Cache::get('read_replica_health:status'));
    }

    public function test_mark_replica_healthy_resets_failover_state(): void
    {
        // First mark as unhealthy to trigger failover
        $this->service->markReplicaUnhealthy();

        // Then mark as healthy to reset
        $this->service->markReplicaHealthy();

        // Should now return read connection
        $connection = $this->service->getReadConnection();

        $this->assertSame('pgsql_read', $connection);
    }

    public function test_reset_failover_clears_cache_and_state(): void
    {
        // Set unhealthy state
        $this->service->markReplicaUnhealthy();
        Cache::put('read_replica_health:status', false, 60);

        // Reset
        $this->service->resetFailover();

        // Cache should be cleared
        $this->assertNull(Cache::get('read_replica_health:status'));
    }

    public function test_get_health_status_returns_complete_info(): void
    {
        Cache::put('read_replica_health:status', true, 60);

        $status = $this->service->getHealthStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('replica_healthy', $status);
        $this->assertArrayHasKey('current_connection', $status);
        $this->assertArrayHasKey('using_primary', $status);
        $this->assertArrayHasKey('consecutive_failures', $status);
        $this->assertArrayHasKey('force_primary', $status);
        $this->assertArrayHasKey('replica_connection', $status);
        $this->assertArrayHasKey('primary_connection', $status);
    }

    public function test_execute_with_retry_returns_on_first_success(): void
    {
        $result = $this->service->executeWithRetry(fn () => 'success');

        $this->assertSame('success', $result);
    }

    public function test_execute_with_retry_retries_on_failure(): void
    {
        $attempts = 0;
        $maxAttempts = 2;

        $result = $this->service->executeWithRetry(
            function () use (&$attempts, $maxAttempts) {
                $attempts++;

                if ($attempts < $maxAttempts) {
                    throw new \RuntimeException('Temporary failure');
                }

                return 'success';
            },
            $maxAttempts
        );

        $this->assertSame('success', $result);
        $this->assertSame(2, $attempts);
    }

    public function test_execute_with_retry_throws_after_max_retries(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->executeWithRetry(
            function () {
                throw new \RuntimeException('Persistent failure');
            },
            maxRetries: 2
        );
    }

    public function test_health_status_shows_using_primary_when_unhealthy(): void
    {
        Cache::put('read_replica_health:status', false, 60);

        $status = $this->service->getHealthStatus();

        $this->assertTrue($status['using_primary']);
        $this->assertSame('pgsql', $status['current_connection']);
    }

    public function test_health_status_shows_using_replica_when_healthy(): void
    {
        Cache::put('read_replica_health:status', true, 60);

        $status = $this->service->getHealthStatus();

        $this->assertFalse($status['using_primary']);
        $this->assertSame('pgsql_read', $status['current_connection']);
    }
}
