<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CouponValidationRequest;
use App\Enums\CouponTargetType;
use App\Http\Controllers\Controller;
use App\Services\CouponService;
use App\Services\OrganizationContext;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

/**
 * POST /api/billing/coupon/validate
 *
 * Validates a coupon code against the caller's account without redeeming it.
 * Returns the discount details so the UI can show the adjusted price.
 */
final class CouponValidationController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly OrganizationContext $context,
    ) {}

    public function __invoke(CouponValidationRequest $request): JsonResponse
    {

        $organization = $this->context->current();

        if ($organization === null) {
            return response()->json(['valid' => false, 'message' => 'No active organization.'], 403);
        }

        $account = $organization->account;

        if ($account === null) {
            return response()->json(['valid' => false, 'message' => 'No billing account.'], 422);
        }

        try {
            $coupon = $this->couponService->validate(
                code: $request->string('code')->value(),
                account: $account,
                targetType: CouponTargetType::Subscription,
                planId: $request->integer('plan_id') ?: null,
            );

            $result = $this->couponService->applyToCharge(
                $coupon,
                $request->integer('amount_minor'),
            );

            return response()->json([
                'valid' => true,
                'coupon' => [
                    'code' => $coupon->code,
                    'description' => $coupon->description,
                    'discount_type' => $coupon->discount_type->value,
                    'discount_value' => $coupon->discount_value,
                    'discount_duration_months' => $coupon->discount_duration_months,
                    'unit' => $coupon->discount_type->unit(),
                    'trial_days' => $coupon->getTrialDaysToAdd(),
                ],
                'original_amount' => $request->integer('amount_minor'),
                'discount_amount' => $result['discount'],
                'final_amount' => $result['amount'],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['valid' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
