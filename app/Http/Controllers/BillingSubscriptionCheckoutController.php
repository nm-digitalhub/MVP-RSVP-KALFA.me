<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use App\Models\ProductPlan;
use App\Services\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Renders the subscription checkout page (PaymentsJS tokenization).
 * The JS form submits a single-use token to POST /api/billing/checkout.
 */
class BillingSubscriptionCheckoutController extends Controller
{
    public function __invoke(Request $request, OrganizationContext $context, int $plan): View
    {
        $organization = $context->current();
        abort_if($organization === null, 403);
        Gate::authorize('update', $organization);

        $productPlan = ProductPlan::with(['activePrices', 'product'])
            ->where('is_active', true)
            ->findOrFail($plan);

        $price = $productPlan->primaryPrice();
        abort_if($price === null, 404, 'This plan has no active price.');

        $bearerToken = $request->user()->createToken('billing-checkout')->plainTextToken;

        return view('billing.subscription-checkout', [
            'organization' => $organization,
            'plan' => $productPlan,
            'price' => $price,
            'apiUrl' => url('/api/billing/checkout'),
            'couponValidateUrl' => url('/api/billing/coupon/validate'),
            'bearerToken' => $bearerToken,
            'companyId' => config('officeguy.company_id'),
            'publicKey' => config('officeguy.public_key'),
        ]);
    }
}
