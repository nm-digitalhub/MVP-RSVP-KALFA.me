<?php

declare(strict_types=1);

namespace App\Events\Billing;

use App\Models\Organization;

/**
 * Fired when a system admin cancels an organization's subscription.
 * Listeners: AuditBillingEvent (audit log)
 */
final class SubscriptionCancelled
{
    public function __construct(
        public readonly Organization $organization,
        public readonly ?int $actorId,
    ) {}
}
