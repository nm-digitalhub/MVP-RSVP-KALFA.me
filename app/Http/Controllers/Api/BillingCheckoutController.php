<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\BillingProvider;
use App\Enums\ProductPriceBillingCycle;
use App\Models\ProductPrice;
use App\Services\CouponService;
use App\Services\OrganizationContext;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OfficeGuy\LaravelSumitGateway\Models\OfficeGuyToken;

final readonly class BillingCheckoutController
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private BillingProvider $billingProvider,
        private CouponService $couponService,
    ) {}

    public function store(Request $request, OrganizationContext $context): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:product_plans,id'],
            'payment_token' => ['required', 'string'],
            'coupon_code' => ['nullable', 'string', 'max:64'],
        ]);

        $organization = $context->current();

        if ($organization === null) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found.',
            ], 403);
        }

        $this->authorize('update', $organization);

        $price = ProductPrice::with('productPlan.product')
            ->where('is_active', true)
            ->where('product_plan_id', $request->plan_id)
            ->firstOrFail();

        // Find monthly/yearly price if multiple cycles available
        if ($price->billing_cycle === null) {
            $price = ProductPrice::with('productPlan.product')
                ->where('is_active', true)
                ->where('product_plan_id', $request->plan_id)
                ->whereIn('billing_cycle', [ProductPriceBillingCycle::Monthly->value, ProductPriceBillingCycle::Yearly->value])
                ->orderByDesc('billing_cycle') // Prefer yearly
                ->firstOrFail();
        }

        $account = $organization->account;

        DB::beginTransaction();
        try {
            // Store the payment token first (required for subscription)
            $token = OfficeGuyToken::create([
                'owner_type' => $account::class,
                'owner_id' => $account->id,
                'token' => $request->payment_token,
                'gateway_id' => 'sumit',
                'is_default' => true,
            ]);

            // Apply coupon if provided
            if ($request->coupon_code) {
                $couponResult = $this->couponService->validate(
                    $account,
                    $request->coupon_code,
                    $price->productPlan,
                    $price->amount,
                );

                if (! $couponResult['valid']) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => $couponResult['message'] ?? 'Invalid coupon code.',
                    ], 422);
                }

                // Apply coupon discount as credit (for recurring subscriptions)
                if ($couponResult['discount_amount'] > 0) {
                    $this->couponService->applyCoupon(
                        $account,
                        $couponResult['coupon'],
                        redeemable: $price->productPlan,
                        appliedAmount: $couponResult['discount_amount'],
                    );
                }
            }

            // Create the subscription via billing provider
            $subscriptionResult = $this->billingProvider->createSubscription(
                $account,
                $price,
            );

            if (isset($subscriptionResult['failed']) && $subscriptionResult['failed']) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $subscriptionResult['error'] ?? 'Failed to create subscription.',
                ], 422);
            }

            // Activate the subscription locally with billing metadata
            $subscription = $this->subscriptionService->activatePaid(
                $account,
                $price->productPlan,
                billingMetadata: [
                    'provider' => $subscriptionResult['provider'] ?? 'sumit',
                    'customer_reference' => $subscriptionResult['customer_reference'] ?? null,
                    'subscription_reference' => $subscriptionResult['subscription_reference'] ?? null,
                    'officeguy_subscription_id' => $subscriptionResult['metadata']['officeguy_subscription_id'] ?? null,
                    'recurring_id' => $subscriptionResult['metadata']['recurring_id'] ?? null,
                    'officeguy_token_id' => $subscriptionResult['metadata']['officeguy_token_id'] ?? null,
                ],
            );

            $account->invalidateBillingAccessCache();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription activated successfully.',
                'redirect_url' => route('dashboard'),
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status->value,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('Subscription checkout failed', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
                'plan_id' => $request->plan_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your subscription.',
            ], 500);
        }
    }
}
