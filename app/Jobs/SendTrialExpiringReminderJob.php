<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\TrialExpiringReminder;
use App\Models\AccountSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a reminder email when a trial subscription is about to expire.
 */
class SendTrialExpiringReminderJob implements ShouldQueue
{
    use Queueable;

    /** Number of days before trial end to send reminder. */
    public function __construct(
        public readonly AccountSubscription $subscription,
        public int $daysRemaining,
    ) {
        $this->daysRemaining = (int) round($daysRemaining);
    }

    public function handle(): void
    {
        $account = $this->subscription->account;

        if ($account === null) {
            Log::warning('Trial reminder skipped: subscription has no account', [
                'subscription_id' => $this->subscription->id,
            ]);

            return;
        }

        // Get the organization owner to send the email to
        $owner = $account->owner;

        if ($owner === null) {
            Log::warning('Trial reminder skipped: account has no owner', [
                'account_id' => $account->id,
                'subscription_id' => $this->subscription->id,
            ]);

            return;
        }

        // Skip if user has no email
        if (empty($owner->email)) {
            Log::warning('Trial reminder skipped: owner has no email', [
                'account_id' => $account->id,
                'owner_id' => $owner->id,
            ]);

            return;
        }

        try {
            Mail::to($owner->email)->send(
                new TrialExpiringReminder($account, $this->subscription, $this->daysRemaining)
            );

            Log::info('Trial reminder sent successfully', [
                'account_id' => $account->id,
                'subscription_id' => $this->subscription->id,
                'owner_id' => $owner->id,
                'days_remaining' => $this->daysRemaining,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send trial reminder email', [
                'account_id' => $account->id,
                'subscription_id' => $this->subscription->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
