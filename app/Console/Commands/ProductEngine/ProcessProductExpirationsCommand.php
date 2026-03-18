<?php

declare(strict_types=1);

namespace App\Console\Commands\ProductEngine;

use App\Enums\AccountProductStatus;
use App\Models\AccountProduct;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ProcessProductExpirationsCommand extends Command
{
    protected $signature = 'app:process-product-expirations
                            {--dry-run : Preview expired products without applying status transitions}
                            {--chunk=100 : Number of records to process per batch}';

    protected $description = 'Transition active AccountProducts with a past expires_at to Expired status, flushing caches and syncing permissions';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $processed = 0;

        if ($this->option('dry-run')) {
            $count = $this->expiredProductsQuery()->count();

            if ($count === 0) {
                $this->info('No expired products found.');

                return self::SUCCESS;
            }

            $this->table(
                ['ID', 'Account', 'Product', 'Status', 'Expires At'],
                $this->expiredProductsQuery()
                    ->with('account', 'product')
                    ->limit(200)
                    ->get()
                    ->map(fn (AccountProduct $ap): array => [
                        $ap->id,
                        $ap->account_id,
                        $ap->product?->name ?? $ap->product_id,
                        $ap->status->value,
                        (string) $ap->expires_at,
                    ])
                    ->all(),
            );

            $this->info(sprintf('Dry run: %d product(s) would be transitioned to Expired.', $count));

            return self::SUCCESS;
        }

        $this->expiredProductsQuery()
            ->with('account', 'product.productEntitlements')
            ->chunkById($chunkSize, function ($chunk) use (&$processed): void {
                foreach ($chunk as $accountProduct) {
                    // Update via model so booted() fires:
                    // - FeatureResolver::forgetMany() clears feature cache (uses loaded relation, no N+1)
                    // - account->invalidateBillingAccessCache() clears billing cache
                    // AccountProductObserver::updated() also fires → PermissionSyncService::syncForAccount()
                    $accountProduct->status = AccountProductStatus::Expired;
                    $accountProduct->save();

                    $processed++;
                }
            });

        if ($processed === 0) {
            $this->info('No expired products found.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Processed %d expired product(s): status → Expired, caches flushed, permissions synced.',
            $processed,
        ));

        return self::SUCCESS;
    }

    private function expiredProductsQuery(): Builder
    {
        return AccountProduct::query()
            ->where('status', AccountProductStatus::Active->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('expires_at');
    }
}
