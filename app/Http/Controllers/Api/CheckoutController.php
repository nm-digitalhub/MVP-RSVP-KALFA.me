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

class CheckoutController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {}

    /**
     * Initiate one-time payment for an event.
     *
     * **Token flow** (PaymentsJS): provide `token` → returns `{ status: "processing", payment_id }`.
     * **Redirect flow**: omit `token` → returns `{ redirect_url }` to send the user to the payment page.
     *
     * ⚠️ PCI: Never send raw card data. Only single-use tokens from PaymentsJS are accepted.
     */
    public function initiate(InitiateCheckoutRequest $request, Organization $organization, Event $event): JsonResponse
    {
        $this->authorize('initiatePayment', $event);

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
