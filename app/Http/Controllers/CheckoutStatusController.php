<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\View\View;

/**
 * Checkout payment status page (polling / result). Auth + ownership required.
 */
class CheckoutStatusController extends Controller
{
    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        return view('checkout.status', [
            'payment' => $payment,
        ]);
    }
}
