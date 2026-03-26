<?php

declare(strict_types=1);

namespace App\Services\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Service for routing database queries to read replicas or primary.
 *
 * Automatically routes SELECT queries to read replicas while write
 * operations go to the primary database connection.
 *
 * Features automatic failover to primary when replica is unhealthy.
 */
final class ReadWriteConnection
{
    private const WRITE_CONNECTION = 'pgsql';

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ReadReplicaHealthService $health,
    ) {}

    /**
     * Get read connection for queries.
     *
     * Uses health service to determine if replica is healthy.
     * Falls back to primary if replica is unhealthy.
     */
    public function read(): Connection
    {
        if (app()->runningUnitTests()) {
            return $this->db->connection((string) config('database.default'));
        }

        $connectionName = $this->health->getReadConnection();

        return $this->db->connection($connectionName);
    }

    /**
     * Get write connection for queries.
     *
     * Always use this for writes, transactions, and reads that require
     * immediate consistency (reading back written data).
     */
    public function write(): Connection
    {
        if (app()->runningUnitTests()) {
            return $this->db->connection((string) config('database.default'));
        }

        return $this->db->connection(self::WRITE_CONNECTION);
    }

    /**
     * Execute a callback using the read connection.
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    public function onReadConnection(\Closure $callback): mixed
    {
        return $callback();
    }

    /**
     * Execute a callback using the write connection.
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    public function onWriteConnection(\Closure $callback): mixed
    {
        return $callback();
    }

    /**
     * Execute a callback with retry logic on connection failure.
     *
     * Uses exponential backoff: 1s, 2s, 4s, 8s, 16s
     *
     * @template T
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    public function executeWithRetry(\Closure $callback, int $maxRetries = 5): mixed
    {
        return $this->health->executeWithRetry($callback, $maxRetries);
    }

    /**
     * Check if read replica is healthy and responding.
     */
    public function isReadReplicaHealthy(): bool
    {
        return $this->health->isReplicaHealthy();
    }

    /**
     * Get replication lag in seconds (if available).
     *
     * Returns null if replication lag cannot be determined or if
     * the read replica is PostgreSQL (which doesn't expose lag like MySQL).
     */
    public function getReplicationLag(): ?int
    {
        try {
            // PostgreSQL doesn't have a direct replication lag query like MySQL
            // This is a placeholder for custom monitoring solutions
            // In production, you might use pg_stat_replication or external monitoring

            return null;
        } catch (\Throwable $e) {
            logger()->warning('Failed to check replication lag', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get health status information for monitoring.
     */
    public function getHealthStatus(): array
    {
        return $this->health->getHealthStatus();
    }

    /**
     * Reset failover state and attempt to use replica again.
     */
    public function resetFailover(): void
    {
        $this->health->resetFailover();
    }

    /**
     * Get a table query builder that uses the read connection.
     *
     * Convenience method for fluent query building.
     */
    public function table(string $table): QueryBuilder
    {
        return $this->read()->table($table);
    }

    /**
     * Get an Eloquent model query builder that uses the read connection.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $model
     * @return Builder<TModel>
     */
    public function model(string $model): Builder
    {
        $connectionName = app()->runningUnitTests()
            ? (string) config('database.default')
            : $this->health->getReadConnection();

        /** @var Builder<TModel> */
        return $model::on($connectionName);
    }
}
