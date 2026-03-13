<?php

declare(strict_types=1);

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

use function response;

class WebAuthnRegisterController
{
    use PasskeyAuditContext;

    /** Maximum passkeys allowed per user. */
    private const MAX_CREDENTIALS = 10;

    /**
     * Returns a challenge to be verified by the user device.
     * Blocks if user already has MAX_CREDENTIALS registered.
     */
    public function options(AttestationRequest $request): Responsable|Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->webAuthnCredentials()->count() >= self::MAX_CREDENTIALS) {
            Log::warning('passkey.register', $this->auditContext($request) + [
                'flow_stage' => 'register.options',
                'outcome' => 'blocked',
                'user_id' => $user->id,
                'credential_count' => self::MAX_CREDENTIALS,
            ]);

            return response()->json(['message' => 'הגעת למספר המקסימלי של מפתחות זיהוי.'], 422);
        }

        return $request->fastRegistration()->toCreate();
    }

    /**
     * Registers a device for further WebAuthn authentication.
     */
    public function register(AttestedRequest $request): Response
    {
        $request->save();

        /** @var \App\Models\User $user */
        $user = $request->user();

        Log::info('passkey.register', $this->auditContext($request) + [
            'flow_stage' => 'register.save',
            'outcome' => 'success',
            'user_id' => $user->id,
            'credential_count' => $user->webAuthnCredentials()->count(),
        ]);

        return response()->noContent();
    }
}
