<?php

declare(strict_types=1);

namespace App\Console\Commands\ProductEngine;

use App\Enums\AccountSubscriptionStatus;
use App\Models\AccountSubscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ProcessTrialExpirationsCommand extends Command
{
    protected $signature = 'app:process-trial-expirations-command
                            {--dry-run : Preview expired trials without applying lifecycle transitions}';

    protected $description = 'Process expired trial subscriptions and transition them to active or cancelled';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $expiredTrials = $this->expiredTrialsQuery()->get();

        if ($expiredTrials->isEmpty()) {
            $this->info('No expired trial subscriptions found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->table(
                ['Subscription', 'Account', 'Product', 'Plan', 'Trial Ended', 'Next Action'],
                $expiredTrials->map(function (AccountSubscription $subscription): array {
                    return [
                        $subscription->id,
                        $subscription->account_id,
                        $subscription->productPlan->product->name,
                        $subscription->productPlan->name,
                        (string) $subscription->trial_ends_at,
                        $subscription->productPlan->activePrices->isNotEmpty() ? 'activate' : 'cancel',
                    ];
                })->all(),
            );

            $this->info(sprintf(
                'Dry run complete. %d expired trial subscription(s) would be processed.',
                $expiredTrials->count(),
            ));

            return self::SUCCESS;
        }

        $billableCount = $expiredTrials->filter(fn (AccountSubscription $subscription): bool => $subscription->productPlan->activePrices->isNotEmpty())->count();
        $cancelledCount = $expiredTrials->count() - $billableCount;
        $processed = $subscriptionService->processTrialExpirations();

        $this->info(sprintf(
            'Processed %d expired trial subscription(s): %d activated, %d cancelled.',
            $processed,
            $billableCount,
            $cancelledCount,
        ));

        return self::SUCCESS;
    }

    private function expiredTrialsQuery(): Builder
    {
        return AccountSubscription::query()
            ->with('account', 'productPlan.product', 'productPlan.activePrices')
            ->where('status', AccountSubscriptionStatus::Trial->value)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->orderBy('trial_ends_at');
    }
}
