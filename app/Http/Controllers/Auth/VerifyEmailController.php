<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterVerification($user);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->redirectAfterVerification($user);
    }

    /**
     * Determine where to redirect after email verification.
     * Handles multi-tenant organization selection flow.
     */
    protected function redirectAfterVerification($user): RedirectResponse
    {
        $orgsCount = $user->organizations()->count();

        // No organizations yet → create one first
        if ($orgsCount === 0) {
            return redirect()->route('organizations.create')->with('status', __('Email verified! Please create an organization to continue.'));
        }

        // Has organizations but none selected → select one
        if ($user->current_organization_id === null) {
            return redirect()->route('organizations.index')->with('status', __('Email verified! Please select an organization to continue.'));
        }

        // Has selected organization → go to dashboard
        return redirect()->route('dashboard')->with('verified', '1');
    }
}
