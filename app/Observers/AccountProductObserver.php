<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AccountProductStatus;
use App\Models\AccountProduct;
use App\Services\PermissionSyncService;

/**
 * Keeps Spatie team-scoped permissions in sync whenever an AccountProduct
 * changes status (active ↔ suspended / revoked).
 *
 * Covers both flows:
 *  A – Subscription: SubscriptionService::activate() → grantProduct() → active
 *  B – Manual admin:  Account::grantProduct($grantedBy=adminId) → active
 */
final class AccountProductObserver
{
    public function __construct(
        private readonly PermissionSyncService $sync,
    ) {}

    /** New product grant — sync immediately if already active. */
    public function created(AccountProduct $accountProduct): void
    {
        if ($accountProduct->status === AccountProductStatus::Active) {
            $this->sync->syncForAccount($accountProduct->account);
        }
    }

    /** Status change (active ↔ suspended / revoked). */
    public function updated(AccountProduct $accountProduct): void
    {
        if ($accountProduct->wasChanged('status') || $accountProduct->wasChanged('expires_at')) {
            $this->sync->syncForAccount($accountProduct->account);
        }
    }

    /** Hard-deleted product — revoke if no other active products remain. */
    public function deleted(AccountProduct $accountProduct): void
    {
        $this->sync->syncForAccount($accountProduct->account);
    }
}
