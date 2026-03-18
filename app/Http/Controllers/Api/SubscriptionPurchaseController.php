<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AccountSubscriptionStatus;
use App\Enums\CouponTargetType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPurchaseRequest;
use App\Models\Coupon;
use App\Models\ProductPlan;
use App\Services\CouponService;
use App\Services\OrganizationContext;
use App\Services\SubscriptionService;
use App\Services\Sumit\AccountPaymentMethodManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/billing/checkout
 *
 * Receives a single-use PaymentsJS token, authorizes it against SUMIT (₪1 auth),
 * stores a recurring OfficeGuyToken, creates an AccountSubscription, and activates it.
 * Requires: auth:sanctum + ensure.organization middleware.
 */
final class SubscriptionPurchaseController extends Controller
{
    public function __construct(
        private readonly AccountPaymentMethodManager $paymentMethodManager,
        private readonly SubscriptionService $subscriptionService,
        private readonly CouponService $couponService,
    ) {}

    public function __invoke(SubscriptionPurchaseRequest $request, OrganizationContext $context): JsonResponse
    {
        $organization = $context->current();

        if ($organization === null) {
            return response()->json(['success' => false, 'message' => 'No active organization.'], 403);
        }

        $this->authorize('update', $organization);

        $plan = ProductPlan::with(['activePrices', 'product'])
            ->where('is_active', true)
            ->find((int) $request->validated('plan_id'));

        if ($plan === null) {
            return response()->json(['success' => false, 'message' => 'Plan not found.'], 404);
        }

        $price = $plan->primaryPrice();
        if ($price === null) {
            return response()->json(['success' => false, 'message' => 'No active price for this plan.'], 422);
        }

        $account = $organization->account;
        if ($account === null) {
            return response()->json(['success' => false, 'message' => 'No billing account found.'], 422);
        }

        if ($account->activeSubscriptions()->exists()) {
            return response()->json(['success' => false, 'message' => 'כבר קיים מנוי פעיל לחשבון זה.'], 409);
        }

        // Validate optional coupon code before entering the DB transaction.
        $coupon = null;
        $couponCode = $request->validated('coupon_code');
        if ($couponCode) {
            try {
                $coupon = $this->couponService->validate(
                    code: $couponCode,
                    account: $account,
                    targetType: CouponTargetType::Subscription,
                    planId: $plan->id,
                );
            } catch (\InvalidArgumentException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }

        try {
            $subscription = DB::transaction(function () use ($account, $plan, $request, $coupon): \App\Models\AccountSubscription {
                // 1. Authorize card and store as recurring OfficeGuyToken (₪1 auth charge).
                $this->paymentMethodManager->storeSingleUseToken(
                    $account,
                    $request->validated('payment_token'),
                );

                // 2. Create subscription record in Trial status (will be immediately activated).
                $subscription = $account->subscriptions()->create([
                    'product_plan_id' => $plan->id,
                    'status' => AccountSubscriptionStatus::Trial,
                    'started_at' => now(),
                    'metadata' => ['source' => 'plan_selection_purchase'],
                ]);

                // 3. Redeem coupon if provided.
                if ($coupon instanceof Coupon) {
                    $this->couponService->redeem(
                        coupon: $coupon,
                        account: $account,
                        redeemedBy: $request->user(),
                        redeemable: $subscription,
                        discountApplied: 0, // actual discount applied at billing layer
                    );
                }

                return $subscription;
            });

            // 3. Activate: bills first charge via stored token, grants product entitlements.
            $subscription = $this->subscriptionService->activate($subscription, $request->user()?->id);

            if ($subscription->status === AccountSubscriptionStatus::PastDue) {
                return response()->json([
                    'success' => false,
                    'message' => 'התשלום נכשל. נא לבדוק את פרטי הכרטיס ולנסות שוב.',
                ], 422);
            }

            $account->invalidateBillingAccessCache();

            return response()->json([
                'success' => true,
                'redirect_url' => route('dashboard'),
            ]);
        } catch (\RuntimeException $e) {
            Log::warning('SubscriptionPurchaseController: payment failed', [
                'account_id' => $account->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
