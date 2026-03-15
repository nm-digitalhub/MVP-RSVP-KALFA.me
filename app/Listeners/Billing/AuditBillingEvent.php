<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Events\Billing\SubscriptionCancelled;
use App\Events\Billing\TrialExtended;
use App\Models\User;
use App\Services\SystemAuditLogger;

/**
 * Centralized audit listener for billing domain events.
 * Decouples SystemBillingService from SystemAuditLogger.
 */
class AuditBillingEvent
{
    public function handle(SubscriptionCancelled|TrialExtended $event): void
    {
        $actor = $event->actorId ? User::find($event->actorId) : null;

        match (true) {
            $event instanceof SubscriptionCancelled => SystemAuditLogger::log(
                $actor,
                'organization.subscription_cancelled',
                $event->organization,
            ),
            $event instanceof TrialExtended => SystemAuditLogger::log(
                $actor,
                'organization.trial_extended',
                $event->organization,
                ['days' => $event->days],
            ),
        };
    }
}
