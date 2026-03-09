<?php

declare(strict_types=1);

namespace App\Console\Commands\ProductEngine;

use App\Services\ProductEngineOperationsMonitor;
use Illuminate\Console\Command;

class ProductEngineHealthCommand extends Command
{
    protected $signature = 'app:product-engine-health-command
                            {--fail-on-unhealthy : Return a non-zero exit code when operations are stale or failing}';

    protected $description = 'Report product engine scheduler and task health';

    public function handle(ProductEngineOperationsMonitor $monitor): int
    {
        $scheduler = $monitor->schedulerStatus();
        $tasks = $monitor->taskStatuses();
        $statuses = [$scheduler, ...$tasks];

        $this->table(
            ['Component', 'Status', 'Last Seen', 'Age', 'Details'],
            array_map(function (array $status): array {
                return [
                    $status['component'],
                    strtoupper($status['status']),
                    $status['last_seen_at']?->toDateTimeString() ?? 'never',
                    $status['age_seconds'] !== null ? $status['age_seconds'].'s' : 'n/a',
                    $status['details'],
                ];
            }, $statuses),
        );

        $healthy = collect($statuses)->every(fn (array $status): bool => $status['healthy'] === true);

        if ($healthy) {
            $this->info('Product engine operations are healthy.');

            return self::SUCCESS;
        }

        $this->warn('Product engine operations require attention.');

        return $this->option('fail-on-unhealthy') ? self::FAILURE : self::SUCCESS;
    }
}
