<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\ProductEngine\ProductEngineHealthCommand;
use App\Services\ProductEngineOperationsMonitor;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductEngineHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_engine_health_command_reports_healthy_operations(): void
    {
        $monitor = app(ProductEngineOperationsMonitor::class);

        $monitor->recordSchedulerHeartbeat(now());
        $monitor->recordTaskFinished('trial_expirations', true, now());
        $monitor->recordTaskFinished('integrity_checks', true, now());

        $this->artisan(ProductEngineHealthCommand::class)
            ->expectsOutputToContain('scheduler')
            ->expectsOutputToContain('HEALTHY')
            ->expectsOutput('Product engine operations are healthy.')
            ->assertSuccessful();
    }

    public function test_product_engine_health_command_fails_when_scheduler_or_tasks_are_stale(): void
    {
        $monitor = app(ProductEngineOperationsMonitor::class);

        $monitor->recordSchedulerHeartbeat(now()->subMinutes(10));
        $monitor->recordTaskFinished('trial_expirations', true, now()->subMinutes(20));

        $this->artisan(ProductEngineHealthCommand::class, ['--fail-on-unhealthy' => true])
            ->expectsOutputToContain('STALE')
            ->expectsOutput('Product engine operations require attention.')
            ->assertFailed();
    }

    public function test_product_engine_health_command_is_not_blocked_by_disabled_tasks(): void
    {
        config()->set('product-engine.operations.integrity_checks.enabled', false);

        $monitor = app(ProductEngineOperationsMonitor::class);
        $monitor->recordSchedulerHeartbeat(now());
        $monitor->recordTaskFinished('trial_expirations', true, now());

        $this->artisan(ProductEngineHealthCommand::class, ['--fail-on-unhealthy' => true])
            ->expectsOutputToContain('DISABLED')
            ->expectsOutput('Product engine operations are healthy.')
            ->assertSuccessful();
    }

    public function test_scheduler_registers_product_engine_heartbeat(): void
    {
        $heartbeatEvent = collect(app(Schedule::class)->events())->first(
            fn ($event): bool => $event->description === 'Product engine scheduler heartbeat'
        );

        $this->assertNotNull($heartbeatEvent);
        $this->assertSame('* * * * *', $heartbeatEvent->getExpression());
    }
}
