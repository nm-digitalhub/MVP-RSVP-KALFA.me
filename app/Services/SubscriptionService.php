<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BillingProvider;
use App\Enums\AccountProductStatus;
use App\Enums\AccountSubscriptionStatus;
use App\Events\ProductEngineEvent;
use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\ProductPlan;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class SubscriptionService
{
    public function __construct(
        private readonly BillingProvider $billingProvider,
        private readonly FeatureResolver $featureResolver,
    ) {}

    public function startTrial(
        Account $account,
        ProductPlan $plan,
        ?CarbonInterface $trialEndsAt = null,
        ?CarbonInterface $startedAt = null,
        array $metadata = [],
    ): AccountSubscription {
        $subscription = $account->subscriptions()->create([
            'product_plan_id' => $plan->id,
            'status' => AccountSubscriptionStatus::Trial,
            'started_at' => $startedAt ?? now(),
            'trial_ends_at' => $trialEndsAt,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);

        ProductEngineEvent::dispatch(
            'subscription.trial_started',
            $account,
            $plan->product,
            $subscription,
            [
                'product_plan_id' => $plan->id,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            ],
        );

        return $subscription;
    }

    /**
     * Activate a paid subscription (from checkout with payment token).
     * Creates the AccountSubscription record and activates it with billing metadata.
     */
    public function activatePaid(
        Account $account,
        ProductPlan $plan,
        array $billingMetadata = [],
        ?int $grantedBy = null,
        ?CarbonInterface $startedAt = null,
        array $metadata = [],
    ): AccountSubscription {
        // Merge billing metadata into subscription metadata
        $metadata['billing'] = $billingMetadata;

        $subscription = $account->subscriptions()->create([
            'product_plan_id' => $plan->id,
            'status' => AccountSubscriptionStatus::Active,
            'started_at' => $startedAt ?? now(),
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);

        // Grant product immediately (no need to call activate() since we already have billing metadata)
        $subscription->account->grantProduct(
            $plan->product,
            $grantedBy,
            $subscription->ends_at,
            [
                'source' => 'subscription',
                'subscription_id' => $subscription->id,
                'product_plan_id' => $subscription->product_plan_id,
            ],
        );

        $this->clearFeatureCache($subscription);

        ProductEngineEvent::dispatch(
            'subscription.activated',
            $subscription->account,
            $plan->product,
            $subscription,
            [
                'product_plan_id' => $plan->id,
                'billing_provider' => $billingMetadata['provider'] ?? null,
            ],
        );

        return $subscription->fresh(['account', 'productPlan.product']);
    }

    public function activate(AccountSubscription $subscription, ?int $grantedBy = null): AccountSubscription
    {
        return DB::transaction(function () use ($subscription, $grantedBy): AccountSubscription {
            $subscription->loadMissing('account', 'productPlan.product', 'productPlan.activePrices');

            $billingMetadata = (array) data_get($subscription->metadata, 'billing', []);
            $price = $subscription->productPlan->primaryPrice();

            if ($price !== null && $billingMetadata === []) {
                $customer = $this->billingProvider->createCustomer($subscription->account);
                $providerSubscription = $this->billingProvider->createSubscription($subscription->account, $price);

                if (($providerSubscription['failed'] ?? false) === true) {

                    $subscription->update([
                        'status' => AccountSubscriptionStatus::PastDue,
                    ]);

                    ProductEngineEvent::dispatch(
                        'subscription.payment_failed',
                        $subscription->account,
                        $subscription->productPlan->product,
                        $subscription,
                        [
                            'error' => $providerSubscription['error'] ?? 'Payment failed',
                        ],
                        'warning',
                    );

                    return $subscription->fresh(['account', 'productPlan.product']);
                }

                $billingMetadata = [
                    'provider' => $providerSubscription['provider'] ?? $customer['provider'] ?? 'internal',
                    'customer_reference' => $providerSubscription['customer_reference'] ?? $customer['customer_reference'],
                    'subscription_reference' => $providerSubscription['subscription_reference'] ?? null,
                    'price_id' => $price->id,
                    'currency' => $price->currency,
                    'amount' => $price->amount,
                    'billing_cycle' => $price->billing_cycle?->value,
                ];
            }

            $metadata = (array) $subscription->metadata;
            if ($billingMetadata !== []) {
                $metadata['billing'] = $billingMetadata;
            }

            $subscription->update([
                'status' => AccountSubscriptionStatus::Active,
                'started_at' => $subscription->started_at ?? now(),
                'metadata' => $metadata !== [] ? $metadata : null,
            ]);

            $subscription->account->grantProduct(
                $subscription->productPlan->product,
                $grantedBy,
                $subscription->ends_at,
                [
                    'source' => 'subscription',
                    'subscription_id' => $subscription->id,
                    'product_plan_id' => $subscription->product_plan_id,
                ],
            );

            $this->clearFeatureCache($subscription);

            ProductEngineEvent::dispatch(
                'subscription.activated',
                $subscription->account,
                $subscription->productPlan->product,
                $subscription,
                [
                    'product_plan_id' => $subscription->product_plan_id,
                    'billing_provider' => $billingMetadata['provider'] ?? null,
                ],
            );

            return $subscription->fresh(['account', 'productPlan.product']);
        });
    }

    public function cancel(AccountSubscription $subscription): AccountSubscription
    {
        return DB::transaction(function () use ($subscription): AccountSubscription {
            $subscription->loadMissing('account', 'productPlan.product');

            $this->billingProvider->cancelSubscription($subscription);

            $subscription->update([
                'status' => AccountSubscriptionStatus::Cancelled,
                'ends_at' => $subscription->ends_at ?? now(),
            ]);

            $subscription->account->accountProducts()
                ->where('product_id', $subscription->productPlan->product_id)
                ->where('metadata->subscription_id', $subscription->id)
                ->update([
                    'status' => AccountProductStatus::Revoked->value,
                    'expires_at' => now(),
                ]);

            if (! $this->hasAnotherActiveSubscriptionForProduct($subscription)) {
                $subscription->account->entitlements()
                    ->whereHas('productEntitlement', fn ($query) => $query->where('product_id', $subscription->productPlan->product_id))
                    ->update([
                        'expires_at' => now(),
                    ]);
            }

            $this->clearFeatureCache($subscription);

            ProductEngineEvent::dispatch(
                'subscription.cancelled',
                $subscription->account,
                $subscription->productPlan->product,
                $subscription,
                [
                    'product_plan_id' => $subscription->product_plan_id,
                ],
            );

            return $subscription->fresh(['account', 'productPlan.product']);
        });
    }

    public function suspend(AccountSubscription $subscription): AccountSubscription
    {
        return DB::transaction(function () use ($subscription): AccountSubscription {
            $subscription->loadMissing('account', 'productPlan.product');

            $subscription->update([
                'status' => AccountSubscriptionStatus::PastDue,
            ]);

            $subscription->account->accountProducts()
                ->where('product_id', $subscription->productPlan->product_id)
                ->where('metadata->subscription_id', $subscription->id)
                ->update([
                    'status' => AccountProductStatus::Suspended->value,
                ]);

            $this->clearFeatureCache($subscription);

            ProductEngineEvent::dispatch(
                'subscription.suspended',
                $subscription->account,
                $subscription->productPlan->product,
                $subscription,
                [
                    'product_plan_id' => $subscription->product_plan_id,
                ],
                'warning',
            );

            return $subscription->fresh(['account', 'productPlan.product']);
        });
    }

    public function renew(AccountSubscription $subscription): AccountSubscription
    {
        return DB::transaction(function () use ($subscription): AccountSubscription {
            $subscription->loadMissing('account', 'productPlan.product', 'productPlan.activePrices');

            $subscription->update([
                'status' => AccountSubscriptionStatus::Active,
                'ends_at' => $this->nextEndDate($subscription),
            ]);

            $assignment = $subscription->account->accountProducts()
                ->where('product_id', $subscription->productPlan->product_id)
                ->where('metadata->subscription_id', $subscription->id)
                ->latest('id')
                ->first();

            if ($assignment !== null) {
                $assignment->update([
                    'status' => AccountProductStatus::Active->value,
                    'expires_at' => $subscription->ends_at,
                ]);
            } else {
                $subscription->account->grantProduct(
                    $subscription->productPlan->product,
                    expiresAt: $subscription->ends_at,
                    metadata: [
                        'source' => 'subscription',
                        'subscription_id' => $subscription->id,
                        'product_plan_id' => $subscription->product_plan_id,
                    ],
                );
            }

            $this->clearFeatureCache($subscription);

            ProductEngineEvent::dispatch(
                'subscription.renewed',
                $subscription->account,
                $subscription->productPlan->product,
                $subscription,
                [
                    'product_plan_id' => $subscription->product_plan_id,
                    'ends_at' => $subscription->ends_at?->toIso8601String(),
                ],
            );

            return $subscription->fresh(['account', 'productPlan.product']);
        });
    }

    public function processTrialExpirations(): int
    {
        $processed = 0;

        AccountSubscription::query()
            ->with('account', 'productPlan.product', 'productPlan.activePrices')
            ->where('status', AccountSubscriptionStatus::Trial->value)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->each(function (AccountSubscription $subscription) use (&$processed): void {
                if ($subscription->productPlan->activePrices()->exists()) {
                    $this->activate($subscription);
                } else {
                    $this->cancel($subscription);
                }

                $processed++;
            });

        return $processed;
    }

    private function clearFeatureCache(AccountSubscription $subscription): void
    {
        $featureKeys = array_unique(array_filter([
            ...$subscription->productPlan->product->activeEntitlements()->pluck('feature_key')->all(),
            ...array_keys((array) data_get($subscription->productPlan->metadata, 'limits', [])),
        ]));

        $this->featureResolver->forgetMany($subscription->account, $featureKeys);
    }

    private function hasAnotherActiveSubscriptionForProduct(AccountSubscription $subscription): bool
    {
        return $subscription->account->activeSubscriptions()
            ->where('id', '!=', $subscription->id)
            ->whereHas('productPlan', fn ($query) => $query->where('product_id', $subscription->productPlan->product_id))
            ->exists();
    }

    private function nextEndDate(AccountSubscription $subscription): ?CarbonInterface
    {
        $price = $subscription->productPlan->primaryPrice();
        $anchor = ($subscription->ends_at ?? $subscription->started_at ?? now())->copy();

        if ($price?->billing_cycle?->value === 'yearly') {
            return $anchor->addYear();
        }

        if ($price?->billing_cycle?->value === 'usage') {
            return null;
        }

        return $anchor->addMonth();
    }
}
