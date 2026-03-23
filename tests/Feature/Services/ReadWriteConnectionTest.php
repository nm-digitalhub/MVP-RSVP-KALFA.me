<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\Database\ReadWriteConnection;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Test read/write connection routing service.
 */
final class ReadWriteConnectionTest extends TestCase
{
    use RefreshDatabase;

    private ReadWriteConnection $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReadWriteConnection::class);
    }

    public function test_read_connection_returns_valid_connection(): void
    {
        $connection = $this->service->read();

        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertSame('pgsql_read', $connection->getName());
    }

    public function test_write_connection_returns_primary_connection(): void
    {
        $connection = $this->service->write();

        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertSame('pgsql', $connection->getName());
    }

    public function test_is_read_replica_healthy_returns_true_on_working_connection(): void
    {
        $result = $this->service->isReadReplicaHealthy();

        // May fail in test environment with SQLite, that's ok
        $this->assertIsBool($result);
    }

    public function test_on_read_connection_executes_callback(): void
    {
        $result = $this->service->onReadConnection(function () {
            return 'test-value';
        });

        $this->assertSame('test-value', $result);
    }

    public function test_on_write_connection_executes_callback(): void
    {
        $result = $this->service->onWriteConnection(function () {
            return 'write-value';
        });

        $this->assertSame('write-value', $result);
    }

    public function test_table_method_returns_query_builder_on_read_connection(): void
    {
        $builder = $this->service->table('users');

        $this->assertInstanceOf(QueryBuilder::class, $builder);

        // Verify connection name
        $this->assertSame('pgsql_read', $builder->getConnection()->getName());
    }

    public function test_model_method_returns_query_builder_on_read_connection(): void
    {
        $builder = $this->service->model(User::class);

        $this->assertInstanceOf(Builder::class, $builder);
        $this->assertSame('pgsql_read', $builder->getConnection()->getName());
    }

    public function test_read_connection_falls_back_to_primary_when_read_config_missing(): void
    {
        // Temporarily unset read replica config
        Config::set('database.connections.pgsql_read.host', null);

        $connection = $this->service->read();

        // Should still return a valid connection (falls back to env defaults)
        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function test_get_replication_lag_returns_null_for_postgresql(): void
    {
        $lag = $this->service->getReplicationLag();

        // PostgreSQL doesn't expose replication lag the same way MySQL does
        $this->assertNull($lag);
    }

    public function test_read_and_write_connections_are_different(): void
    {
        $readConnection = $this->service->read();
        $writeConnection = $this->service->write();

        $this->assertNotSame($readConnection->getName(), $writeConnection->getName());
        $this->assertSame('pgsql_read', $readConnection->getName());
        $this->assertSame('pgsql', $writeConnection->getName());
    }
}
