<?php

declare(strict_types=1);

namespace App\Services\OfficeGuy;

use App\Models\Organization;

/**
 * System-level billing and OfficeGuy integration.
 * Adapter: all OfficeGuy/subscription access goes through this service.
 * System layer must never call OfficeGuy SDK directly.
 */
class SystemBillingService
{
    /**
     * Get subscription for an organization. Returns null until OfficeGuy is wired.
     */
    public function getOrganizationSubscription(Organization $organization): ?object
    {
        return null;
    }

    /**
     * Cancel subscription for an organization.
     */
    public function cancelSubscription(Organization $organization): bool
    {
        return false;
    }

    /**
     * Extend trial for an organization by given days.
     */
    public function extendTrial(Organization $organization, int $days): bool
    {
        return false;
    }

    /**
     * Apply credit (amount in smallest currency unit) to organization.
     */
    public function applyCredit(Organization $organization, int $amount): bool
    {
        return false;
    }

    /**
     * Retry failed payment for organization.
     */
    public function retryPayment(Organization $organization): bool
    {
        return false;
    }

    /**
     * Monthly recurring revenue. Aggregate until OfficeGuy ready.
     */
    public function getMRR(): float
    {
        return 0.0;
    }

    /**
     * Churn rate (e.g. 0–1). Until OfficeGuy ready.
     */
    public function getChurnRate(): float
    {
        return 0.0;
    }

    /**
     * Count or list of active subscriptions. Until OfficeGuy ready.
     */
    public function getActiveSubscriptions(): array
    {
        return [];
    }
}
