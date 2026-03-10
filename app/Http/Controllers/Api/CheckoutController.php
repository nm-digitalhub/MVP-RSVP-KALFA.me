<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InitiateCheckoutRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Plan;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CheckoutController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {}

    /**
     * Initiate one-time payment for an event. Delegates to BillingService.
     * If token (PaymentsJS single-use) is provided: token flow → returns { status: "processing", payment_id }.
     * Otherwise: redirect flow (redirect_url).
     * PCI: Do not log request payload (token/card data must not appear in logs).
     */
    public function initiate(InitiateCheckoutRequest $request, Organization $organization, Event $event): JsonResponse
    {
        Gate::authorize('initiatePayment', $event);

        $plan = Plan::findOrFail($request->validated('plan_id'));
        $token = $request->validated('token');

        if ($token !== null && $token !== '') {
            $result = $this->billingService->initiateEventPaymentWithToken($event, $plan, $token);
            if (($result['status'] ?? '') === 'processing') {
                return response()->json([
                    'status' => 'processing',
                    'payment_id' => $result['payment_id'],
                ], 200);
            }

            return response()->json([
                'status' => 'failed',
                'payment_id' => $result['payment_id'] ?? null,
                'message' => $result['message'] ?? 'Payment failed.',
            ], 422);
        }

        $result = $this->billingService->initiateEventPayment($event, $plan);

        return response()->json($result, 201);
    }
}
