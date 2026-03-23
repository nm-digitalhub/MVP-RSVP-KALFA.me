<?php

declare(strict_types=1);

namespace App\Console\Commands\LoadTest;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Facades\Pulse;

/**
 * Load test Pulse APM monitoring.
 *
 * Usage: php artisan load-test:pulse {iterations}
 * Example: php artisan load-test:pulse 1000
 */
final class PulseApmLoadTest extends Command
{
    protected $signature = 'load-test:pulse {iterations} {--clear}';

    protected $description = 'Test Pulse APM monitoring under load.';

    public function handle(): int
    {
        $iterations = (int) $this->argument('iterations');

        $this->info("Starting Pulse APM load test with {$iterations} operations");

        // Check if Pulse is enabled
        if (! config('pulse.enabled')) {
            $this->warn('Pulse is not enabled. Skipping test.');

            return self::INVALID;
        }

        // Clear old data if requested
        if ($this->option('clear')) {
            $this->info('Clearing old Pulse data...');
            DB::table(config('pulse.storage.table_prefix').'pulse_entries')->truncate();
            DB::table(config('pulse.storage.table_prefix').'pulse_aggregates')->truncate();
        }

        $this->newLine(2);
        $this->info('Phase 1: Recording baseline metrics...');

        // Record some baseline operations
        $baselineStart = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            Pulse::record('test_baseline', 'operation')->count();
        }
        $baselineDuration = microtime(true) - $baselineStart;

        $this->info('Baseline: 10 operations in '.sprintf('%.4f', $baselineDuration).'s');

        $this->newLine();
        $this->info("Phase 2: Load testing with {$iterations} operations...");

        // Simulate various operations
        $operations = [
            'user_query' => fn () => DB::table('users')->count(),
            'org_query' => fn () => DB::table('organizations')->count(),
            'cache_read' => fn () => cache()->get('test_key', 'default'),
            'cache_write' => fn () => cache()->put('test_key', 'value', 60),
            'slow_query' => function () {
                // Intentionally slower query
                usleep(10000); // 10ms

                return DB::table('organizations')->limit(10)->get();
            },
        ];

        $operationCounts = array_fill_keys(array_keys($operations), 0);
        $operationTimes = array_fill_keys(array_keys($operations), 0);

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $operationName = array_rand($operations);
            $operation = $operations[$operationName];

            $opStart = microtime(true);

            try {
                $operation();
                $operationCounts[$operationName]++;
            } catch (\Throwable $e) {
                // Record error
                Pulse::record('load_test_error', $operationName)->count();
            }

            $operationTimes[$operationName] += microtime(true) - $opStart;

            // Record sample metrics to Pulse
            if ($i % 100 == 0) {
                Pulse::record('load_test_progress', (string) $i)->count();
            }
        }

        $totalDuration = microtime(true) - $startTime;

        $this->newLine(2);
        $this->info('=== Load Test Results ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Operations', number_format($iterations)],
                ['Total Duration', sprintf('%.2f seconds', $totalDuration)],
                ['Ops/Second', number_format($iterations / $totalDuration, 2)],
            ]
        );

        $this->newLine();
        $this->info('=== Operation Breakdown ===');
        foreach ($operationCounts as $op => $count) {
            if ($count > 0) {
                $avgTime = ($operationTimes[$op] / $count) * 1000;
                $this->line($op.': '.$count.' ops, avg '.sprintf('%.2f', $avgTime).'ms');
            }
        }

        $this->newLine();
        $this->info('=== Pulse Dashboard Access ===');
        $pulseUrl = config('pulse.path', 'pulse');
        $this->info("View real-time metrics at: /{$pulseUrl}");
        $this->info('Run: php artisan pulse:check');

        // Check Pulse tables
        $entryCount = DB::table(config('pulse.storage.table_prefix').'pulse_entries')->count();
        $aggregateCount = DB::table(config('pulse.storage.table_prefix').'pulse_aggregates')->count();

        $this->table(
            ['Pulse Table', 'Records'],
            [
                ['Entries', number_format($entryCount)],
                ['Aggregates', number_format($aggregateCount)],
            ]
        );

        return self::SUCCESS;
    }
}
