<?php

declare(strict_types=1);

namespace App\Console\Commands\LoadTest;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Load test with simulated concurrent users.
 *
 * Usage: php artisan load-test:concurrent-users {users} {duration}
 * Example: php artisan load-test:concurrent-users 50 60
 */
final class ConcurrentUsers extends Command
{
    protected $signature = 'load-test:concurrent-users {users} {duration} {--url=/}';

    protected $description = 'Simulate concurrent users accessing the application.';

    public function handle(): int
    {
        $concurrency = (int) $this->argument('users');
        $duration = (int) $this->argument('duration');
        $url = $this->option('url', '/');

        $this->info("Starting load test: {$concurrency} concurrent users for {$duration} seconds");
        $this->info("Target URL: {$url}");

        // Create progress callback
        $progress = $this->output->createProgressBar($duration);
        $progress->start();

        // Spawn processes
        $pids = [];
        $startTime = time();
        $results = [];

        for ($i = 0; $i < $concurrency; $i++) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                $this->error('Failed to fork process');

                return self::FAILURE;
            } elseif ($pid == 0) {
                // Child process - run requests for duration
                $this->runUserSimulation($duration, $url, $i);
                exit(0);
            } else {
                $pids[] = $pid;
            }
        }

        // Monitor progress in main process
        while (time() - $startTime < $duration) {
            sleep(1);
            $progress->advance();
        }

        $progress->finish();

        // Wait for all child processes
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        // Collect and display results
        $this->displayResults();

        return self::SUCCESS;
    }

    /**
     * Run user simulation requests.
     */
    private function runUserSimulation(int $duration, string $url, int $userId): void
    {
        $requests = 0;
        $errors = 0;
        $totalTime = 0;

        $endTime = time() + $duration;

        while (time() < $endTime) {
            $requestStart = microtime(true);

            try {
                // Make HTTP request to the application
                $response = $this->makeRequest($url);

                $requestTime = microtime(true) - $requestStart;
                $totalTime += $requestTime;
                $requests++;
            } catch (\Throwable $e) {
                $errors++;
            }

            // Random delay between requests (simulating real user behavior)
            usleep(rand(100000, 500000)); // 0.1-0.5 seconds
        }

        // Store results in shared memory or file for parent to collect
        $this->storeResults($userId, $requests, $errors, $totalTime);
    }

    /**
     * Make HTTP request to the application.
     */
    private function makeRequest(string $url): mixed
    {
        $fullUrl = url($url);
        $ch = curl_init($fullUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true, // HEAD request for speed
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

        curl_close($ch);

        if ($httpCode >= 500) {
            throw new \RuntimeException("Server error: {$httpCode}");
        }

        return $response;
    }

    /**
     * Store worker results.
     */
    private function storeResults(int $userId, int $requests, int $errors, float $totalTime): void
    {
        $filename = storage_path("app/load_test_results_{$userId}.json");
        $data = [
            'user_id' => $userId,
            'requests' => $requests,
            'errors' => $errors,
            'total_time' => $totalTime,
            'avg_time' => $requests > 0 ? $totalTime / $requests : 0,
        ];

        file_put_contents($filename, json_encode($data));
    }

    /**
     * Display collected results from all workers.
     */
    private function displayResults(): void
    {
        $results = [];
        $files = glob(storage_path('app/load_test_results_*.json'));

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $results[] = $data;
                unlink($file);
            }
        }

        if (empty($results)) {
            $this->warn('No results collected');

            return;
        }

        $totalRequests = array_sum(array_column($results, 'requests'));
        $totalErrors = array_sum(array_column($results, 'errors'));
        $totalTime = array_sum(array_column($results, 'total_time'));
        $avgResponseTime = ($totalTime / $totalRequests) * 1000;

        $this->newLine(2);
        $this->info('=== Concurrent Users Test Results ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Simulated Users', count($results)],
                ['Total Requests', number_format($totalRequests)],
                ['Total Errors', number_format($totalErrors)],
                ['Error Rate', sprintf('%.2f%%', ($totalErrors / $totalRequests) * 100)],
                ['Total Duration', sprintf('%.2f seconds', $totalTime)],
                ['Avg Response Time', sprintf('%.2f ms', $avgResponseTime)],
                ['Requests/Second', number_format($totalRequests / $totalTime, 2)],
            ]
        );

        // Performance thresholds
        $this->newLine();
        $this->info('=== Performance Thresholds ===');
        $this->table(
            ['Threshold', 'Target', 'Actual', 'Status'],
            [
                ['Error Rate', '< 1%', sprintf('%.2f%%', ($totalErrors / $totalRequests) * 100), $totalErrors / $totalRequests < 0.01 ? '✓' : '✗'],
                ['Avg Response Time', '< 500ms', sprintf('%.2f ms', $avgResponseTime), $avgResponseTime < 500 ? '✓' : '✗'],
            ]
        );
    }
}
