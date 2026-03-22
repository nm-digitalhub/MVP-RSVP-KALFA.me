<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AccountSubscriptionStatus;
use App\Jobs\SendTrialExpiringReminderJob;
use App\Models\AccountSubscription;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Checks for trials about to expire and sends reminder emails.
 *
 * Run daily via scheduler to send reminders at:
 * - 7 days before trial ends
 * - 3 days before trial ends
 * - 1 day before trial ends
 */
class CheckTrialExpiryAndSendReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trials:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for trials about to expire and send reminder emails';

    /**
     * Days before trial end to send reminders.
     */
    private array $reminderDays = [7, 3, 1];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for trials about to expire...');

        $remindersSent = 0;

        foreach ($this->reminderDays as $days) {
            $this->info("Checking for trials ending in {$days} day(s)...");

            $trials = $this->getTrialsEndingInDays($days);

            foreach ($trials as $trial) {
                $this->info("  - Account #{$trial->account_id}: Trial #{$trial->id}");

                try {
                    // Dispatch job to send reminder email
                    dispatch(new SendTrialExpiringReminderJob($trial, $days));
                    $remindersSent++;
                } catch (\Throwable $e) {
                    $this->error("  ✗ Failed: {$e->getMessage()}");
                    Log::error('Failed to dispatch trial reminder job', [
                        'subscription_id' => $trial->id,
                        'account_id' => $trial->account_id,
                        'days_remaining' => $days,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("  Found {$trials->count()} trial(s) ending in {$days} day(s)");
        }

        $this->info("Total reminders sent: {$remindersSent}");

        return Command::SUCCESS;
    }

    /**
     * Get trials that end exactly in the specified number of days.
     */
    private function getTrialsEndingInDays(int $days): Collection
    {
        $targetDate = now()->addDays($days)->startOfDay();

        return AccountSubscription::with(['account.owner', 'plan'])
            ->where('status', AccountSubscriptionStatus::Trial->value)
            ->whereDate('trial_ends_at', '=', $targetDate)
            ->whereHas('account', function ($query) {
                $query->whereHas('owner');
            })
            ->whereDoesntHave('account.activeAccountProducts')
            ->whereDoesntHave('account.activeSubscriptions')
            ->get();
    }
}
