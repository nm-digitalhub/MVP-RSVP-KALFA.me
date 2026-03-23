<?php

declare(strict_types=1);

namespace App\Console\Commands\LoadTest;

use App\Services\Database\ReadWriteConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Load test command for database read replicas.
 *
 * Usage: php artisan load-test:db-replica {concurrency} {requests}
 * Example: php artisan load-test:db-replica 10 1000
 */
final class DatabaseReadReplica extends Command
{
    protected $signature = 'load-test:db-replica {concurrency} {requests}';

    protected $description = 'Load test read replica performance with concurrent queries.';

    public function __construct(
        private readonly ReadWriteConnection $db,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $concurrency = (int) $this->argument('concurrency');
        $totalRequests = (int) $this->argument('requests');

        $this->info("Starting load test: {$concurrency} concurrent, {$totalRequests} total requests");

        $startTime = microtime(true);
        $successCount = 0;
        $failureCount = 0;
        $results = [];

        // Create process pool for concurrent execution
        $pids = [];
        $requestsPerProcess = (int) ceil($totalRequests / $concurrency);

        for ($i = 0; $i < $concurrency; $i++) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                $this->error('Failed to fork process');

                return self::FAILURE;
            } elseif ($pid == 0) {
                // Child process
                $this->runWorker($requestsPerProcess, $i);
                exit(0);
            } else {
                // Parent process
                $pids[] = $pid;
            }
        }

        // Wait for all child processes
        foreach ($pids as $pid) {
            $status = pcntl_waitpid($pid, $result);
            if ($status == -1) {
                $this->error('Failed to wait for child process');
            }
        }

        $duration = microtime(true) - $startTime;
        $requestsPerSecond = $totalRequests / $duration;

        $this->newLine(2);
        $this->info('=== Load Test Results ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests', number_format($totalRequests)],
                ['Concurrency', $concurrency],
                ['Duration', sprintf('%.2f seconds', $duration)],
                ['Requests/Second', number_format($requestsPerSecond, 2)],
                ['Avg Response Time', sprintf('%.2f ms', ($duration / $totalRequests) * 1000)],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Run worker process.
     */
    private function runWorker(int $requests, int $workerId): void
    {
        $startTime = microtime(true);
        $success = 0;
        $failed = 0;

        for ($i = 0; $i < $requests; $i++) {
            try {
                // Simulate dashboard query
                $result = DB::connection($this->db->read()->getName())
                    ->table('organizations')
                    ->select(['id', 'name', 'created_at'])
                    ->limit(10)
                    ->get();

                $success++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $duration = microtime(true) - $startTime;
        $rps = $requests / $duration;

        $this->line('Worker '.$workerId.': '.$success.' successful, '.$failed.' failed, '.number_format($rps, 2).' req/s');
    }
}
