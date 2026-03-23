<?php

declare(strict_types=1);

namespace App\Services\Database;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;

final class ReadReplicaHealthService
{
    private const WRITE_CONNECTION = 'pgsql';

    private const READ_CONNECTION = 'pgsql_read';

    private const CACHE_KEY_PREFIX = 'read_replica_health:';

    private const CACHE_TTL = 60; // seconds

    private bool $forcePrimary = false;

    private int $consecutiveFailures = 0;

    private const MAX_CONSECUTIVE_FAILURES = 3;

    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Get the appropriate read connection (replica if healthy, otherwise primary).
     */
    public function getReadConnection(): string
    {
        // Force primary if replica has failed too many times
        if ($this->forcePrimary) {
            return self::WRITE_CONNECTION;
        }

        // Check if replica is healthy (with cache to avoid checking every request)
        if ($this->isReplicaHealthy()) {
            return self::READ_CONNECTION;
        }

        // Replica is unhealthy, use primary
        return self::WRITE_CONNECTION;
    }

    /**
     * Check if the read replica is healthy.
     * Uses cache to avoid checking on every request.
     */
    public function isReplicaHealthy(): bool
    {
        $cacheKey = self::CACHE_KEY_PREFIX.'status';

        // Check cache first
        $cachedStatus = cache()->get($cacheKey);
        if ($cachedStatus !== null) {
            return (bool) $cachedStatus;
        }

        // Perform health check
        $isHealthy = $this->checkReplicaConnection();

        // Cache the result
        cache()->put($cacheKey, $isHealthy, self::CACHE_TTL);

        return $isHealthy;
    }

    /**
     * Perform actual connection check to read replica.
     */
    private function checkReplicaConnection(): bool
    {
        try {
            $connection = $this->db->connection(self::READ_CONNECTION);

            // Simple ping query
            $connection->select('SELECT 1');

            // Reset consecutive failures on success
            $this->consecutiveFailures = 0;
            $this->forcePrimary = false;

            return true;
        } catch (\Throwable $e) {
            $this->consecutiveFailures++;

            // Force primary if too many consecutive failures
            if ($this->consecutiveFailures >= self::MAX_CONSECUTIVE_FAILURES) {
                $this->forcePrimary = true;

                // Log that we're switching to primary
                Log::warning('Read replica unhealthy, forcing primary connection', [
                    'connection' => self::READ_CONNECTION,
                    'consecutive_failures' => $this->consecutiveFailures,
                    'error' => $e->getMessage(),
                ]);
            }

            return false;
        }
    }

    /**
     * Manually mark replica as unhealthy (for forced failover).
     */
    public function markReplicaUnhealthy(): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX.'status';
        cache()->put($cacheKey, false, self::CACHE_TTL);

        Log::info('Read replica manually marked as unhealthy');
    }

    /**
     * Manually mark replica as healthy (for recovery).
     */
    public function markReplicaHealthy(): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX.'status';
        cache()->put($cacheKey, true, self::CACHE_TTL);

        // Reset failover state
        $this->consecutiveFailures = 0;
        $this->forcePrimary = false;

        Log::info('Read replica manually marked as healthy, failover state reset');
    }

    /**
     * Reset the failover state and attempt to use replica again.
     */
    public function resetFailover(): void
    {
        $this->forcePrimary = false;
        $this->consecutiveFailures = 0;

        // Clear cache to force recheck
        $cacheKey = self::CACHE_KEY_PREFIX.'status';
        cache()->forget($cacheKey);

        Log::info('Read replica failover state reset, attempting to use replica');
    }

    /**
     * Get health status information for monitoring.
     */
    public function getHealthStatus(): array
    {
        $isHealthy = $this->isReplicaHealthy();
        $currentConnection = $this->getReadConnection();

        return [
            'replica_healthy' => $isHealthy,
            'current_connection' => $currentConnection,
            'using_primary' => $currentConnection === self::WRITE_CONNECTION,
            'consecutive_failures' => $this->consecutiveFailures,
            'force_primary' => $this->forcePrimary,
            'replica_connection' => self::READ_CONNECTION,
            'primary_connection' => self::WRITE_CONNECTION,
        ];
    }

    /**
     * Execute a callback with retry logic on connection failure.
     * Uses exponential backoff: 1s, 2s, 4s, 8s, 16s
     */
    public function executeWithRetry(callable $callback, int $maxRetries = 5): mixed
    {
        $lastException = null;
        $baseDelay = 1000; // 1 second in milliseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                $lastException = $e;

                // Log the retry attempt
                Log::warning('Database query failed, retrying with exponential backoff', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage(),
                ]);

                // Don't sleep after the last attempt
                if ($attempt < $maxRetries) {
                    // Exponential backoff: 2^attempt seconds
                    $delay = $baseDelay * (2 ** ($attempt - 1));
                    usleep($delay * 1000); // Convert to microseconds

                    // Switch to primary if we haven't already
                    if (! $this->forcePrimary) {
                        $this->markReplicaUnhealthy();
                    }
                }
            }
        }

        // All retries exhausted, throw the last exception
        throw $lastException ?? new \RuntimeException('Database query failed after retries');
    }
}
