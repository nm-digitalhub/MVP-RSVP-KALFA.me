<?php

declare(strict_types=1);

namespace App\Livewire\System;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\User;
use App\Services\OfficeGuy\SystemBillingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Dashboard extends Component
{
    #[Layout('layouts.app')]
    #[Title('System Dashboard')]

    /** Seconds between automatic data refreshes via wire:poll. */
    public int $pollInterval = 15;

    protected SystemBillingService $billing;

    public function boot(SystemBillingService $billing): void
    {
        $this->billing = $billing;
    }

    public function render(): View
    {
        // --- KPI metrics ---
        $totalOrganizations = Organization::count();
        $totalUsers = User::count();
        $totalEvents = Event::count();
        $totalGuests = Guest::count();

        $activeOrganizations = Organization::where('is_suspended', false)->count();
        $newUsers7 = User::where('created_at', '>=', now()->subDays(7))->count();
        $newUsers30 = User::where('created_at', '>=', now()->subDays(30))->count();
        $newOrgs7 = Organization::where('created_at', '>=', now()->subDays(7))->count();
        $newOrgs30 = Organization::where('created_at', '>=', now()->subDays(30))->count();
        $events30d = Event::where('created_at', '>=', now()->subDays(30))->count();

        // --- Health signals ---
        $usersWithoutOrg = User::whereDoesntHave('organizations')->count();
        $orgsWithoutEvents = Organization::whereDoesntHave('events')->count();
        $orgsWithoutOwner = Organization::whereDoesntHave('users', fn ($q) => $q->where('organization_users.role', 'owner'))->count();
        $systemAdminsCount = User::where('is_system_admin', true)->count();
        $suspendedOrgCount = Organization::where('is_suspended', true)->count();

        // --- Billing ---
        $mrr = $this->billing->getMRR();
        $activeSubscriptions = $this->billing->getActiveSubscriptions();
        $churn = $this->billing->getChurnRate();

        // --- Real-time monitoring ---
        $queueDriver = config('queue.default', 'sync');
        $queuePending = $this->getQueuePending($queueDriver);
        $queueFailed = $this->getFailedJobsCount();
        $cacheDriver = config('cache.default', 'file');
        $dbDriver = config('database.default', 'sqlite');
        $dbAlive = $this->checkDbAlive();
        $cacheAlive = $this->checkCacheAlive();
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $appEnv = app()->environment();
        $lastRefreshedAt = now()->format('H:i:s');

        // --- Critical signals ---
        $queueWorkerStatus = $this->getQueueWorkerStatus($queueDriver);
        [$diskFreeGb, $diskTotalGb, $diskFreePercent] = $this->getDiskStats();
        $recentExceptions = $this->getRecentExceptionsCount();

        // --- Recent activity ---
        $recentOrganizations = Organization::latest()->limit(5)->get();
        $recentUsers = User::latest()->limit(5)->get();

        return view('livewire.system.dashboard', compact(
            'totalOrganizations', 'totalUsers', 'totalEvents', 'totalGuests',
            'activeOrganizations', 'newUsers7', 'newUsers30', 'newOrgs7', 'newOrgs30', 'events30d',
            'usersWithoutOrg', 'orgsWithoutEvents', 'orgsWithoutOwner', 'systemAdminsCount', 'suspendedOrgCount',
            'mrr', 'activeSubscriptions', 'churn',
            'queueDriver', 'queuePending', 'queueFailed', 'cacheDriver', 'dbDriver',
            'dbAlive', 'cacheAlive', 'phpVersion', 'laravelVersion', 'appEnv', 'lastRefreshedAt',
            'queueWorkerStatus', 'diskFreeGb', 'diskTotalGb', 'diskFreePercent', 'recentExceptions',
            'recentOrganizations', 'recentUsers',
        ));
    }

    /** Count pending jobs (only meaningful for database/redis queue drivers). */
    private function getQueuePending(string $driver): int
    {
        if ($driver === 'database') {
            try {
                return (int) DB::table('jobs')->count();
            } catch (\Exception) {
                return -1;
            }
        }

        return -1; // Cannot introspect sync/redis without extra tooling
    }

    /** Count failed jobs from the failed_jobs table. */
    private function getFailedJobsCount(): int
    {
        try {
            return (int) DB::table('failed_jobs')->count();
        } catch (\Exception) {
            return -1;
        }
    }

    /** Ping the database — returns true if query succeeds. */
    private function checkDbAlive(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /** Ping the cache store — returns true if a test write/read succeeds. */
    private function checkCacheAlive(): bool
    {
        try {
            Cache::put('system.ping', 1, 5);

            return Cache::get('system.ping') === 1;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine queue worker health.
     *
     * Returns one of: 'healthy' | 'stalled' | 'idle' | 'unknown'
     * - healthy : pending jobs exist and the oldest is < 10 minutes old (worker is processing)
     * - stalled : a job has been waiting > 10 minutes (worker may be down)
     * - idle    : queue is empty (no pending work — worker may be resting or not needed)
     * - unknown : cannot introspect (non-database driver)
     *
     * @return array{status: string, oldestAgeMinutes: int|null}
     */
    private function getQueueWorkerStatus(string $driver): array
    {
        if ($driver !== 'database') {
            return ['status' => 'unknown', 'oldestAgeMinutes' => null];
        }

        try {
            $oldestTimestamp = DB::table('jobs')->min('created_at');

            if ($oldestTimestamp === null) {
                return ['status' => 'idle', 'oldestAgeMinutes' => null];
            }

            $ageMinutes = (int) round((time() - (int) $oldestTimestamp) / 60);

            return [
                'status' => $ageMinutes > 10 ? 'stalled' : 'healthy',
                'oldestAgeMinutes' => $ageMinutes,
            ];
        } catch (\Exception) {
            return ['status' => 'unknown', 'oldestAgeMinutes' => null];
        }
    }

    /**
     * Disk statistics for the storage partition.
     *
     * @return array{float, float, float} [freeGb, totalGb, freePercent]
     */
    private function getDiskStats(): array
    {
        try {
            $path = storage_path();
            $free = disk_free_space($path);
            $total = disk_total_space($path);

            if ($free === false || $total === false || $total === 0.0) {
                return [0.0, 0.0, 0.0];
            }

            return [
                round($free / 1024 ** 3, 1),
                round($total / 1024 ** 3, 1),
                round($free / $total * 100, 1),
            ];
        } catch (\Exception) {
            return [0.0, 0.0, 0.0];
        }
    }

    /**
     * Count exceptions recorded in the last 24 hours.
     * Reads from Laravel Pulse (pulse_entries) if available, falls back to Telescope.
     */
    private function getRecentExceptionsCount(): int
    {
        // Try Pulse first (lightweight, production-friendly)
        try {
            $count = DB::table('pulse_entries')
                ->where('type', 'exception')
                ->where('timestamp', '>=', now()->subHours(24)->timestamp)
                ->count();

            return (int) $count;
        } catch (\Exception) {
            // Pulse table missing — try Telescope
        }

        try {
            return (int) DB::table('telescope_entries')
                ->where('type', 'exception')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
        } catch (\Exception) {
            return -1;
        }
    }
}
