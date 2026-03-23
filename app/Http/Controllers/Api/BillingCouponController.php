<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\ProductPlan;
use App\Services\CouponService;
use App\Services\OrganizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final readonly class BillingCouponController
{
    public function __construct(
        private CouponService $couponService,
    ) {}

    public function validate(Request $request, OrganizationContext $context): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'plan_id' => ['required', 'integer', 'exists:product_plans,id'],
            'amount_minor' => ['required', 'integer', 'min:0'],
        ]);

        $organization = $context->current();

        if ($organization === null) {
            return response()->json([
                'valid' => false,
                'message' => 'Organization not found.',
            ], 403);
        }

        Gate::authorize('update', $organization);

        $plan = ProductPlan::with('product')
            ->where('is_active', true)
            ->findOrFail($request->plan_id);

        $result = $this->couponService->validate(
            $organization->account,
            $request->code,
            $plan,
            $request->amount_minor,
        );

        if ($result['valid']) {
            return response()->json([
                'valid' => true,
                'coupon' => [
                    'code' => $result['coupon']->code,
                    'description' => $result['coupon']->description,
                    'discount_type' => $result['coupon']->discount_type->value,
                    'discount_duration_months' => $result['coupon']->discount_duration_months,
                ],
                'discount_amount' => $result['discount_amount'],
                'final_amount' => $result['final_amount'],
                'message' => 'Coupon applied successfully.',
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => $result['message'] ?? 'Invalid coupon code.',
        ]);
    }
}
