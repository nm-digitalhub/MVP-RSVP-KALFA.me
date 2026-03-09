<?php

declare(strict_types=1);

namespace App\Services\OfficeGuy;

use App\Models\Organization;
use OfficeGuy\LaravelSumitGateway\Models\Subscription;
use OfficeGuy\LaravelSumitGateway\Services\SubscriptionService;

/**
 * System-level billing and OfficeGuy integration.
 * Adapter: all OfficeGuy/subscription access goes through this service.
 * System layer must never call OfficeGuy SDK directly.
 */
class SystemBillingService
{
    /**
     * Get subscription for an organization.
     */
    public function getOrganizationSubscription(Organization $organization): ?Subscription
    {
        return Subscription::where('subscriber_type', $organization->getMorphClass())
            ->where('subscriber_id', $organization->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->latest()
            ->first();
    }

    /**
     * Cancel subscription for an organization.
     */
    public function cancelSubscription(Organization $organization): bool
    {
        $subscription = $this->getOrganizationSubscription($organization);

        if (! $subscription) {
            return false;
        }

        try {
            SubscriptionService::cancel($subscription, 'Cancelled via System Admin');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extend trial for an organization by given days.
     */
    public function extendTrial(Organization $organization, int $days): bool
    {
        $subscription = $this->getOrganizationSubscription($organization);

        if (! $subscription) {
            return false;
        }

        $subscription->trial_ends_at = ($subscription->trial_ends_at ?? now())->addDays($days);

        return $subscription->save();
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
     */
    public function retryPayment(Organization $organization): bool
    {
        $subscription = Subscription::where('subscriber_type', $organization->getMorphClass())
            ->where('subscriber_id', $organization->id)
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
     * Monthly recurring revenue. Aggregate from active subscriptions.
     */
    public function getMRR(): float
    {
        return (float) Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->sum('amount');
    }

    /**
     * Churn rate (0–1). Rough estimate based on cancellations in the last 30 days.
     */
    public function getChurnRate(): float
    {
        $activeCount = Subscription::where('status', Subscription::STATUS_ACTIVE)->count();
        $cancelledLast30Days = Subscription::where('status', Subscription::STATUS_CANCELLED)
            ->where('cancelled_at', '>=', now()->subDays(30))
            ->count();

        if ($activeCount === 0) {
            return $cancelledLast30Days > 0 ? 1.0 : 0.0;
        }

        return (float) ($cancelledLast30Days / ($activeCount + $cancelledLast30Days));
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
     */
    public function getActiveSubscriptions(): array
    {
        return Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->with('subscriber')
            ->get()
            ->toArray();
    }
}
