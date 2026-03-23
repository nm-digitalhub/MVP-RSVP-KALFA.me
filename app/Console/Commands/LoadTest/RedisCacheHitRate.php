<?php

declare(strict_types=1);

namespace App\Console\Commands\LoadTest;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Load test Redis cache hit rate.
 *
 * Usage: php artisan load-test:redis {iterations}
 * Example: php artisan load-test:redis 10000
 */
final class RedisCacheHitRate extends Command
{
    protected $signature = 'load-test:redis {iterations} {--unique-keys=1000}';

    protected $description = 'Test Redis cache hit rate with read/write operations.';

    public function handle(): int
    {
        $iterations = (int) $this->argument('iterations');
        $uniqueKeys = (int) $this->option('unique-keys', 1000);

        $this->info("Testing Redis cache with {$iterations} operations, {$uniqueKeys} unique keys");

        // Phase 1: Write operations (populate cache)
        $this->info("\n[Phase 1] Writing to cache...");
        $writeStart = microtime(true);

        for ($i = 0; $i < (int) ceil($iterations / 2); $i++) {
            $key = 'test:cache:'.($i % $uniqueKeys);
            $value = ['data' => 'value_'.$i, 'timestamp' => now()->toIso8601String()];
            Cache::put($key, $value, 60);
        }

        $writeDuration = microtime(true) - $writeStart;
        $writeOps = (int) ceil($iterations / 2);
        $this->info('Wrote '.$writeOps.' items in '.sprintf('%.2f', $writeDuration).'s ('.sprintf('%.2f', $writeOps / $writeDuration).' ops/sec)');

        // Phase 2: Read operations (test cache hits)
        $this->info("\n[Phase 2] Reading from cache...");
        $readStart = microtime(true);

        $hits = 0;
        $misses = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $key = 'test:cache:'.($i % $uniqueKeys);

            if (Cache::has($key)) {
                Cache::get($key);
                $hits++;
            } else {
                $misses++;
            }
        }

        $readDuration = microtime(true) - $readStart;
        $hitRate = ($hits / ($hits + $misses)) * 100;

        $this->info('Read '.$iterations.' items in '.sprintf('%.2f', $readDuration).'s ('.sprintf('%.2f', $iterations / $readDuration).' ops/sec)');
        $this->info('Cache Hits: '.$hits.', Misses: '.$misses);
        $this->info('Hit Rate: '.sprintf('%.2f', $hitRate).'%');

        // Phase 3: Mixed operations (read/write ratio 80/20)
        $this->info("\n[Phase 3] Mixed operations (80% read, 20% write)...");
        $mixedStart = microtime(true);

        $mixedHits = 0;
        $mixedMisses = 0;

        for ($i = 0; $i < $iterations; $i++) {
            if ($i % 5 == 0) {
                // 20% write
                $key = 'test:mixed:'.($i % $uniqueKeys);
                Cache::put($key, ['value' => $i], 60);
            } else {
                // 80% read
                $key = 'test:mixed:'.($i % $uniqueKeys);
                if (Cache::get($key) !== null) {
                    $mixedHits++;
                } else {
                    $mixedMisses++;
                }
            }
        }

        $mixedDuration = microtime(true) - $mixedStart;
        $mixedHitRate = ($mixedHits / ($mixedHits + $mixedMisses)) * 100;

        $this->info('Completed '.$iterations.' operations in '.sprintf('%.2f', $mixedDuration).'s');
        $this->info('Cache Hits: '.$mixedHits.', Misses: '.$mixedMisses);
        $this->info('Hit Rate: '.sprintf('%.2f', $mixedHitRate).'%');

        // Summary
        $totalDuration = microtime(true) - $writeStart;
        $this->newLine(2);
        $this->info('=== Summary ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Operations', number_format($iterations * 2)],
                ['Total Duration', sprintf('%.2f seconds', $totalDuration)],
                ['Overall Hit Rate', sprintf('%.2f%%', ($hits + $mixedHits) / ($hits + $misses + $mixedHits + $mixedMisses) * 100)],
                ['Target Hit Rate', '> 80%'],
                ['Status', $hitRate >= 80 ? '✓ PASS' : '✗ FAIL'],
            ]
        );

        // Clean up test data
        $this->info("\nCleaning up test data...");
        for ($i = 0; $i < $uniqueKeys; $i++) {
            Cache::forget("test:cache:{$i}");
            Cache::forget("test:mixed:{$i}");
        }

        return $hitRate >= 80 ? self::SUCCESS : self::FAILURE;
    }
}
