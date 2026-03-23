<?php

declare(strict_types=1);

namespace App\Services;

use App\AccountSubscription;
use App\Enums\AccountSubscriptionStatus;
use App\Models\Account;
use App\Models\OfficeGuy\Subscription as OfficeGuySubscription;
use App\Models\ProductPlan;
use Illuminate\Support\Facades\Log;

/**
 * Syncs SUMIT subscriptions (officeguy_subscriptions) to local AccountSubscription records.
 *
 * This bridges the gap between:
 * - officeguy_subscriptions (managed by SUMIT SDK)
 * - account_subscriptions (our local billing layer)
 */
final class SubscriptionSyncService
{
    public function __construct(
        private readonly FeatureResolver $featureResolver,
    ) {}

    /**
     * Sync all subscriptions for an account from SUMIT to local AccountSubscription.
     *
     * Creates/updates local AccountSubscription records based on officeguy_subscriptions.
     *
     * @return array{synced: int, skipped: int, errors: int}
     */
    public function syncAccountSubscriptions(Account $account): array
    {
        $synced = 0;
        $skipped = 0;
        $errors = 0;

        // Get all SUMIT subscriptions for this account's organizations
        $organizations = $account->organizations;

        foreach ($organizations as $organization) {
            $sumitSubs = OfficeGuySubscription::where('subscriber_type', $organization->getMorphClass())
                ->where('subscriber_id', $organization->id)
                ->whereIn('status', ['active', 'pending'])
                ->get();

            foreach ($sumitSubs as $sumitSub) {
                try {
                    $result = $this->syncSingleSubscription($account, $sumitSub);

                    if ($result === 'created') {
                        $synced++;
                    } elseif ($result === 'updated') {
                        $synced++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('Failed to sync SUMIT subscription', [
                        'account_id' => $account->id,
                        'org_id' => $organization->id,
                        'sumit_sub_id' => $sumitSub->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $account->invalidateBillingAccessCache();

        return [
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Sync a single SUMIT subscription to local AccountSubscription.
     *
     * @return 'created'|'updated'|'skipped'
     */
    private function syncSingleSubscription(Account $account, OfficeGuySubscription $sumitSub): string
    {
        // Find matching ProductPlan by subscription name or metadata
        $productPlan = $this->findProductPlanForSubscription($sumitSub);

        if ($productPlan === null) {
            Log::warning('No matching ProductPlan found for SUMIT subscription', [
                'account_id' => $account->id,
                'sumit_sub_id' => $sumitSub->id,
                'sumit_name' => $sumitSub->name,
            ]);

            return 'skipped';
        }

        // Find existing local subscription or create new one
        $localSub = AccountSubscription::where('account_id', $account->id)
            ->where('product_plan_id', $productPlan->id)
            ->where('metadata->sumit_subscription_id', $sumitSub->id)
            ->first();

        $status = $this->mapSumitStatusToLocalStatus($sumitSub->status);

        if ($localSub === null) {
            // Create new local subscription
            AccountSubscription::create([
                'account_id' => $account->id,
                'product_plan_id' => $productPlan->id,
                'status' => $status,
                'started_at' => $sumitSub->created_at,
                'trial_ends_at' => $sumitSub->trial_ends_at,
                'ends_at' => $sumitSub->expires_at,
                'metadata' => [
                    'sumit_subscription_id' => $sumitSub->id,
                    'sumit_recurring_id' => $sumitSub->recurring_id,
                    'synced_from_sumit' => true,
                    'synced_at' => now()->toIso8601String(),
                ],
            ]);

            return 'created';
        }

        // Update existing subscription if status changed
        if ($localSub->status->value !== $status->value) {
            $localSub->update([
                'status' => $status,
                'ends_at' => $sumitSub->expires_at,
                'metadata' => array_merge($localSub->metadata ?? [], [
                    'sumit_subscription_id' => $sumitSub->id,
                    'sumit_recurring_id' => $sumitSub->recurring_id,
                    'synced_from_sumit' => true,
                    'synced_at' => now()->toIso8601String(),
                ]),
            ]);

            // If subscription was reactivated, restore product access
            if ($status === AccountSubscriptionStatus::Active) {
                $localSub->account->grantProduct(
                    $productPlan->product,
                    metadata: [
                        'source' => 'subscription',
                        'subscription_id' => $localSub->id,
                        'product_plan_id' => $productPlan->id,
                    ],
                );

                $this->featureResolver->forgetMany($account,
                    $productPlan->product->activeEntitlements->pluck('feature_key')->all()
                );
            }

            return 'updated';
        }

        return 'skipped';
    }

    /**
     * Find ProductPlan that matches a SUMIT subscription.
     */
    private function findProductPlanForSubscription(OfficeGuySubscription $sumitSub): ?ProductPlan
    {
        // Try to find by name match
        $productPlan = ProductPlan::where('name', 'like', '%'.$sumitSub->name.'%')
            ->where('is_active', true)
            ->first();

        if ($productPlan !== null) {
            return $productPlan;
        }

        // Try to find by metadata reference
        $sumitSubId = data_get($sumitSub, 'metadata.product_plan_id');
        if ($sumitSubId) {
            return ProductPlan::find($sumitSubId);
        }

        return null;
    }

    /**
     * Map SUMIT status to local AccountSubscriptionStatus.
     */
    private function mapSumitStatusToLocalStatus(string $sumitStatus): AccountSubscriptionStatus
    {
        return match ($sumitStatus) {
            'active' => AccountSubscriptionStatus::Active,
            'pending' => AccountSubscriptionStatus::PastDue,
            'paused' => AccountSubscriptionStatus::PastDue,
            'cancelled' => AccountSubscriptionStatus::Cancelled,
            'expired' => AccountSubscriptionStatus::Cancelled,
            'failed' => AccountSubscriptionStatus::PastDue,
            default => AccountSubscriptionStatus::PastDue,
        };
    }
}
