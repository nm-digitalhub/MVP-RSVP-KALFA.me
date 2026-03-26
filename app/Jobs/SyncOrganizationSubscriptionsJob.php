<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Organization;
use App\Models\User;
use App\Services\OfficeGuy\SystemBillingService;
use App\Services\SubscriptionSyncService;
use App\Services\SystemAuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncOrganizationSubscriptionsJob implements ShouldQueue
{
    use Queueable;

    /** Retry up to 3 times on transient SUMIT API failures. */
    public int $tries = 3;

    /** Exponential-ish backoff: 30s → 2min → 5min between retries. */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly Organization $organization,
        public readonly ?int $actorId = null,
    ) {}

    public function handle(
        SystemBillingService $billingService,
        SubscriptionSyncService $syncService,
    ): void {
        $account = $this->organization->account;

        if ($account === null) {
            Log::warning('Organization has no account to sync subscriptions', [
                'organization_id' => $this->organization->id,
            ]);

            return;
        }

        $result = $syncService->syncAccountSubscriptions($account);

        // Bust cached subscription after sync so the next read reflects fresh data.
        $billingService->forgetSubscriptionCache($this->organization);

        $actor = $this->actorId ? User::find($this->actorId) : null;
        SystemAuditLogger::log($actor, 'organization.subscriptions_synced', $this->organization, [
            'synced' => $result['synced'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
        ]);
    }
}
