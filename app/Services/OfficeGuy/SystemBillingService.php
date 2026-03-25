<?php

declare(strict_types=1);

namespace App\Services\OfficeGuy;

use App\Events\Billing\SubscriptionCancelled as SubscriptionCancelledEvent;
use App\Events\Billing\TrialExtended as TrialExtendedEvent;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use OfficeGuy\LaravelSumitGateway\Models\Subscription;
use OfficeGuy\LaravelSumitGateway\Services\SubscriptionService;

/**
 * System-level billing and OfficeGuy integration.
 * Adapter: all OfficeGuy/subscription access goes through this service.
 * System layer must never call OfficeGuy SDK directly.
 */
class SystemBillingService
{
    /** Cache TTL in seconds for subscription lookups — avoids repeated DB hits on re-renders. */
    private const SUBSCRIPTION_CACHE_TTL = 60;

    /**
     * Get active subscription for an organization.
     * Cached for 60s — call {@see forgetSubscriptionCache()} after any mutation.
     *
     * Note: SUMIT subscriptions are linked to Account (billing entity), not Organization.
     */
    public function getOrganizationSubscription(Organization $organization): ?Subscription
    {
        $account = $organization->account;

        if ($account === null) {
            return null;
        }

        return Cache::remember(
            "org:{$organization->id}:subscription",
            self::SUBSCRIPTION_CACHE_TTL,
            fn () => Subscription::where('subscriber_type', $account->getMorphClass())
                ->where('subscriber_id', $account->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->latest()
                ->first()
        );
    }

    /** Invalidate the cached subscription entry for an organization after a mutation. */
    public function forgetSubscriptionCache(Organization $organization): void
    {
        Cache::forget("org:{$organization->id}:subscription");
    }

    /**
     * Cancel subscription for an organization.
     *
     * @param  int|null  $actorId  User ID of the admin performing the cancellation (for audit log).
     */
    public function cancelSubscription(Organization $organization, ?int $actorId = null): bool
    {
        $subscription = $this->getOrganizationSubscription($organization);

        if (! $subscription) {
            return false;
        }

        try {
            SubscriptionService::cancel($subscription, 'Cancelled via System Admin');
            $this->forgetSubscriptionCache($organization);
            Event::dispatch(new SubscriptionCancelledEvent($organization, $actorId));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extend trial for an organization by given days.
     *
     * @param  int|null  $actorId  User ID of the admin performing the extension (for audit log).
     */
    public function extendTrial(Organization $organization, int $days, ?int $actorId = null): bool
    {
        $subscription = $this->getOrganizationSubscription($organization);

        if (! $subscription) {
            return false;
        }

        $subscription->trial_ends_at = ($subscription->trial_ends_at ?? now())->addDays($days);
        $result = $subscription->save();

        if ($result) {
            $this->forgetSubscriptionCache($organization);
            Event::dispatch(new TrialExtendedEvent($organization, $days, $actorId));
        }

        return $result;
    }

    /**
     * Apply credit (amount in smallest currency unit) to organization.
     * Note: This would typically sync with Sumit if their API supports it directly,
     * otherwise we record it locally or via a one-time adjustment.
     */
    public function applyCredit(Organization $organization, int $amount): bool
    {
        // Placeholder: Logic for manual credit adjustment
        return true;
    }

    /**
     * Retry failed payment for organization.
     *
     * Note: SUMIT subscriptions are linked to Account (billing entity), not Organization.
     */
    public function retryPayment(Organization $organization): bool
    {
        $account = $organization->account;

        if ($account === null) {
            return false;
        }

        $subscription = Subscription::where('subscriber_type', $account->getMorphClass())
            ->where('subscriber_id', $account->id)
            ->where('status', Subscription::STATUS_FAILED)
            ->latest()
            ->first();

        if (! $subscription) {
            return false;
        }

        $result = SubscriptionService::processRecurringCharge($subscription);

        return $result['success'] ?? false;
    }

    /**
     * Monthly recurring revenue in ILS from active subscriptions.
     * Filters by currency ILS to avoid mixed-currency sum errors.
     */
    public function getMRR(): float
    {
        return (float) Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('currency', 'ILS')
            ->sum('amount');
    }

    /**
     * Churn rate (0–1).
     * Formula: cancelled_in_last_30d / active_at_start_of_period
     * where active_at_start_of_period = current_active + cancelled_in_last_30d.
     */
    public function getChurnRate(): float
    {
        $cancelledLast30Days = Subscription::where('status', Subscription::STATUS_CANCELLED)
            ->where('cancelled_at', '>=', now()->subDays(30))
            ->count();

        if ($cancelledLast30Days === 0) {
            return 0.0;
        }

        $activeNow = Subscription::where('status', Subscription::STATUS_ACTIVE)->count();
        // active_at_start = those still active + those that cancelled during the period
        $activeAtStart = $activeNow + $cancelledLast30Days;

        return $activeAtStart > 0 ? (float) ($cancelledLast30Days / $activeAtStart) : 1.0;
    }

    /**
     * Sync subscriptions from SUMIT API for a specific organization.
     */
    public function syncOrganizationSubscriptions(Organization $organization): int
    {
        return SubscriptionService::syncFromSumit($organization);
    }

    /**
     * Count or list of active subscriptions.
     *
     * @return Collection<int, Subscription>
     */
    public function getActiveSubscriptions(): Collection
    {
        return Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->with('subscriber')
            ->get();
    }

    /**
     * Number of active subscriptions (aggregate only — avoids loading full rows + relations).
     */
    public function getActiveSubscriptionCount(): int
    {
        return (int) Subscription::where('status', Subscription::STATUS_ACTIVE)->count();
    }
}
