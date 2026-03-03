<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

/**
 * Returns only payment status for polling. No raw payload or sensitive data.
 */
class PaymentController extends Controller
{
    /**
     * Return only status enum. Auth + org scope enforced via Policy.
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return response()->json([
            'status' => $payment->status->value,
        ]);
    }
}
