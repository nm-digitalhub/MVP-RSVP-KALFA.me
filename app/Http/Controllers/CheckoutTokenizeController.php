<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * RSVP branded checkout: PaymentsJS tokenization (embedded, no redirect).
 * Renders the tokenization form; form submits token to API via JS.
 */
class CheckoutTokenizeController extends Controller
{
    public function __invoke(Request $request, Organization $organization, Event $event): View
    {
        $this->authorize('initiatePayment', $event);

        $plan = Plan::where('type', 'per_event')->firstOrFail();
        $token = $request->user()->createToken('checkout-tokenize')->plainTextToken;

        $apiUrl = url("/api/organizations/{$organization->id}/events/{$event->id}/checkout");

        return view('checkout.tokenize', [
            'organization' => $organization,
            'event' => $event,
            'plan' => $plan,
            'apiUrl' => $apiUrl,
            'bearerToken' => $token,
            'companyId' => config('officeguy.company_id'),
            'publicKey' => config('officeguy.public_key'),
        ]);
    }
}
