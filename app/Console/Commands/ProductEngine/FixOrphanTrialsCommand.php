<?php

declare(strict_types=1);

namespace App\Console\Commands\ProductEngine;

use App\Enums\AccountSubscriptionStatus;
use App\Models\AccountSubscription;
use Illuminate\Console\Command;

class FixOrphanTrialsCommand extends Command
{
    protected $signature = 'app:fix-orphan-trials
                            {--dry-run : Preview affected accounts without applying fixes}';

    protected $description = 'Fix trial subscriptions that were created before grantProduct was added to startTrial';

    public function handle(): int
    {
        $orphans = AccountSubscription::query()
            ->with(['account', 'productPlan.product'])
            ->where('status', AccountSubscriptionStatus::Trial->value)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->get()
            ->filter(function (AccountSubscription $subscription): bool {
                return ! $subscription->account
                    ->accountProducts()
                    ->where('product_id', $subscription->productPlan->product_id)
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->exists();
            });

        if ($orphans->isEmpty()) {
            $this->info('No orphan trial subscriptions found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Account', 'Name', 'Plan', 'Trial Ends', 'Product'],
            $orphans->map(fn (AccountSubscription $s): array => [
                $s->account_id,
                $s->account->name,
                $s->productPlan->name,
                (string) $s->trial_ends_at,
                $s->productPlan->product->name,
            ])->all(),
        );

        if ($this->option('dry-run')) {
            $this->info(sprintf('Dry run: %d orphan trial(s) would be fixed.', $orphans->count()));

            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($orphans as $subscription) {
            $subscription->account->grantProduct(
                $subscription->productPlan->product,
                expiresAt: $subscription->trial_ends_at,
                metadata: [
                    'source' => 'trial',
                    'subscription_id' => $subscription->id,
                    'product_plan_id' => $subscription->product_plan_id,
                    'retroactive_fix' => true,
                ],
            );

            $fixed++;

            $this->line(sprintf(
                '  Fixed account #%d (%s) — granted %s with expiry %s',
                $subscription->account_id,
                $subscription->account->name,
                $subscription->productPlan->product->name,
                $subscription->trial_ends_at,
            ));
        }

        $this->info(sprintf('Fixed %d orphan trial subscription(s).', $fixed));

        return self::SUCCESS;
    }
}
