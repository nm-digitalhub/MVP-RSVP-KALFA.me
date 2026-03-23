<?php

declare(strict_types=1);

namespace App\Livewire\System;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\User;
use App\Services\Database\ReadWriteConnection;
use App\Services\OfficeGuy\SystemBillingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
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

    protected ReadWriteConnection $db;

    public function boot(
        SystemBillingService $billing,
        ReadWriteConnection $db,
    ): void {
        $this->billing = $billing;
        $this->db = $db;
    }

    public function render(): View
    {
        $readConn = $this->db->read()->getName();

        // --- KPI metrics ---
        $totalOrganizations = Organization::on($readConn)->count();
        $totalUsers = User::on($readConn)->count();
        $totalEvents = Event::on($readConn)->count();
        $totalGuests = Guest::on($readConn)->count();

        $activeOrganizations = Organization::on($readConn)->where('is_suspended', false)->count();
        $newUsers7 = User::on($readConn)->where('created_at', '>=', now()->subDays(7))->count();
        $newUsers30 = User::on($readConn)->where('created_at', '>=', now()->subDays(30))->count();
        $newOrgs7 = Organization::on($readConn)->where('created_at', '>=', now()->subDays(7))->count();
        $newOrgs30 = Organization::on($readConn)->where('created_at', '>=', now()->subDays(30))->count();
        $events30d = Event::on($readConn)->where('created_at', '>=', now()->subDays(30))->count();

        // --- Health signals ---
        $usersWithoutOrg = User::on($readConn)->whereDoesntHave('organizations')->count();
        $orgsWithoutEvents = Organization::on($readConn)->whereDoesntHave('events')->count();
        $orgsWithoutOwner = Organization::on($readConn)->whereDoesntHave('users', fn ($q) => $q->where('organization_users.role', 'owner'))->count();
        $systemAdminsCount = User::on($readConn)->where('is_system_admin', true)->count();
        $suspendedOrgCount = Organization::on($readConn)->where('is_suspended', true)->count();

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
        $recentOrganizations = Organization::on($readConn)->latest()->limit(5)->get();
        $recentUsers = User::on($readConn)->latest()->limit(5)->get();

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
                return (int) $this->db->read()->table('jobs')->count();
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
            return (int) $this->db->read()->table('failed_jobs')->count();
        } catch (\Exception) {
            return -1;
        }
    }

    /** Ping the database — returns true if query succeeds. */
    private function checkDbAlive(): bool
    {
        try {
            $this->db->read()->select('SELECT 1');

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
            $oldestTimestamp = $this->db->read()->table('jobs')->min('created_at');

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
            $count = $this->db->read()->table('pulse_entries')
                ->where('type', 'exception')
                ->where('timestamp', '>=', now()->subHours(24)->timestamp)
                ->count();

            return (int) $count;
        } catch (\Exception) {
            // Pulse table missing — try Telescope
        }

        try {
            return (int) $this->db->read()->table('telescope_entries')
                ->where('type', 'exception')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
        } catch (\Exception) {
            return -1;
        }
    }
}
