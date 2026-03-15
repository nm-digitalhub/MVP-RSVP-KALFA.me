<?php

declare(strict_types=1);

namespace App\Events\Billing;

use App\Models\Organization;

/**
 * Fired when a system admin extends an organization's trial period.
 * Listeners: AuditBillingEvent (audit log)
 */
final class TrialExtended
{
    public function __construct(
        public readonly Organization $organization,
        public readonly int $days,
        public readonly ?int $actorId,
    ) {}
}
