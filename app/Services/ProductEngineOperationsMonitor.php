<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

final class ProductEngineOperationsMonitor
{
    private const SCHEDULER_HEARTBEAT_KEY = 'product_engine:operations:scheduler_heartbeat_at';

    /**
     * @var array<string, string>
     */
    private const TASK_CONFIG_PATHS = [
        'trial_expirations' => 'product-engine.operations.trial_expirations',
        'integrity_checks' => 'product-engine.operations.integrity_checks',
    ];

    public function recordSchedulerHeartbeat(?CarbonInterface $recordedAt = null): void
    {
        $this->cache()->put(
            self::SCHEDULER_HEARTBEAT_KEY,
            ($recordedAt ?? now())->toIso8601String(),
            $this->stateTtlSeconds(),
        );
    }

    public function recordTaskStarted(string $task, ?CarbonInterface $recordedAt = null): void
    {
        $state = $this->taskState($task);
        $state['last_started_at'] = ($recordedAt ?? now())->toIso8601String();
        $state['status'] = 'running';

        $this->storeTaskState($task, $state);
    }

    public function recordTaskFinished(string $task, bool $successful, ?CarbonInterface $recordedAt = null, ?int $exitCode = null): void
    {
        $timestamp = ($recordedAt ?? now())->toIso8601String();
        $state = $this->taskState($task);

        $state['status'] = $successful ? 'ok' : 'failed';
        $state['last_finished_at'] = $timestamp;
        $state['last_exit_code'] = $exitCode ?? ($successful ? 0 : 1);

        if ($successful) {
            $state['last_success_at'] = $timestamp;
        } else {
            $state['last_failure_at'] = $timestamp;
        }

        $this->storeTaskState($task, $state);
    }

    /**
     * @return array{component:string,status:string,healthy:bool,last_seen_at:?CarbonImmutable,age_seconds:?int,max_age_seconds:int,details:string}
     */
    public function schedulerStatus(?CarbonInterface $now = null): array
    {
        $checkedAt = CarbonImmutable::instance($now ?? now());
        $lastHeartbeatAt = $this->parseTimestamp($this->cache()->get(self::SCHEDULER_HEARTBEAT_KEY));
        $ageSeconds = $lastHeartbeatAt?->diffInSeconds($checkedAt);
        $maxAgeSeconds = (int) config('product-engine.operations.monitor.max_scheduler_heartbeat_age_seconds', 120);
        $healthy = $lastHeartbeatAt !== null && $ageSeconds !== null && $ageSeconds <= $maxAgeSeconds;

        return [
            'component' => 'scheduler',
            'status' => $healthy ? 'healthy' : 'stale',
            'healthy' => $healthy,
            'last_seen_at' => $lastHeartbeatAt,
            'age_seconds' => $ageSeconds,
            'max_age_seconds' => $maxAgeSeconds,
            'details' => $healthy ? 'Scheduler heartbeat is current.' : 'Scheduler heartbeat is missing or stale.',
        ];
    }

    /**
     * @return list<array{component:string,status:string,healthy:bool,last_seen_at:?CarbonImmutable,age_seconds:?int,max_age_seconds:int,details:string}>
     */
    public function taskStatuses(?CarbonInterface $now = null): array
    {
        return array_map(
            fn (string $task): array => $this->taskStatus($task, $now),
            array_keys(self::TASK_CONFIG_PATHS),
        );
    }

    /**
     * @return array{component:string,status:string,healthy:bool,last_seen_at:?CarbonImmutable,age_seconds:?int,max_age_seconds:int,details:string}
     */
    public function taskStatus(string $task, ?CarbonInterface $now = null): array
    {
        $config = $this->taskConfig($task);
        $checkedAt = CarbonImmutable::instance($now ?? now());
        $maxAgeSeconds = $this->maxAgeSecondsForTask($task);

        if (($config['enabled'] ?? true) !== true) {
            return [
                'component' => $task,
                'status' => 'disabled',
                'healthy' => true,
                'last_seen_at' => null,
                'age_seconds' => null,
                'max_age_seconds' => $maxAgeSeconds,
                'details' => 'Task is disabled in configuration.',
            ];
        }

        $state = $this->taskState($task);
        $lastFinishedAt = $this->parseTimestamp($state['last_finished_at'] ?? null);
        $ageSeconds = $lastFinishedAt?->diffInSeconds($checkedAt);
        $lastExitCode = isset($state['last_exit_code']) ? (int) $state['last_exit_code'] : null;
        $status = (string) ($state['status'] ?? 'missing');
        $healthy = $lastFinishedAt !== null && $lastExitCode === 0 && $ageSeconds !== null && $ageSeconds <= $maxAgeSeconds;

        if ($healthy) {
            $status = 'healthy';
        } elseif ($lastFinishedAt === null) {
            $status = 'missing';
        } elseif ($lastExitCode !== 0) {
            $status = 'failed';
        } elseif ($ageSeconds !== null && $ageSeconds > $maxAgeSeconds) {
            $status = 'stale';
        }

        return [
            'component' => $task,
            'status' => $status,
            'healthy' => $healthy,
            'last_seen_at' => $lastFinishedAt,
            'age_seconds' => $ageSeconds,
            'max_age_seconds' => $maxAgeSeconds,
            'details' => $this->taskDetails($config, $status),
        ];
    }

    private function taskDetails(array $config, string $status): string
    {
        $frequency = (string) ($config['frequency'] ?? 'hourly');

        return match ($status) {
            'healthy' => "Task completed on schedule ({$frequency}).",
            'failed' => "Last task run failed ({$frequency}).",
            'stale' => "No recent successful run detected ({$frequency}).",
            'missing' => "Task has not completed yet ({$frequency}).",
            default => "Task state: {$status} ({$frequency}).",
        };
    }

    private function maxAgeSecondsForTask(string $task): int
    {
        $graceSeconds = (int) config('product-engine.operations.monitor.task_grace_seconds', 120);

        return $this->frequencyToSeconds((string) ($this->taskConfig($task)['frequency'] ?? 'hourly')) + $graceSeconds;
    }

    private function frequencyToSeconds(string $frequency): int
    {
        return match ($frequency) {
            'everyMinute' => 60,
            'everyFiveMinutes' => 300,
            'everyTenMinutes' => 600,
            'everyFifteenMinutes' => 900,
            'everyThirtyMinutes' => 1800,
            'daily' => 86400,
            default => 3600,
        };
    }

    private function taskConfig(string $task): array
    {
        return (array) config(self::TASK_CONFIG_PATHS[$task] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function taskState(string $task): array
    {
        return (array) $this->cache()->get($this->taskKey($task), []);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function storeTaskState(string $task, array $state): void
    {
        $this->cache()->put($this->taskKey($task), $state, $this->stateTtlSeconds());
    }

    private function parseTimestamp(?string $timestamp): ?CarbonImmutable
    {
        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        return CarbonImmutable::parse($timestamp);
    }

    private function taskKey(string $task): string
    {
        return "product_engine:operations:task:{$task}";
    }

    private function stateTtlSeconds(): int
    {
        return (int) config('product-engine.operations.monitor.state_ttl_seconds', 172800);
    }

    private function cache(): CacheRepository
    {
        $store = config('product-engine.cache_store');

        return $store ? Cache::store($store) : Cache::store();
    }
}
