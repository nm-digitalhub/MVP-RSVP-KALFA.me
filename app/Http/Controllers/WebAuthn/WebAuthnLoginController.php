<?php

declare(strict_types=1);

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;

use function response;

class WebAuthnLoginController
{
    use PasskeyAuditContext;

    /**
     * Returns the challenge to assertion.
     */
    public function options(AssertionRequest $request): Responsable
    {
        return $request->toVerify($request->validate(['email' => 'sometimes|email|string']));
    }

    /**
     * Log the user in.
     */
    public function login(AssertedRequest $request): Response
    {
        $success = $request->login();

        $rawCredentialId = $request->input('id', '');
        $credentialHash = $rawCredentialId ? substr(hash('sha256', $rawCredentialId), 0, 16) : null;

        $context = $this->auditContext($request) + [
            'flow_stage' => 'login.verify',
            'outcome' => $success ? 'success' : 'fail',
            'user_id' => $success ? auth()->id() : null,
            'credential_hash' => $credentialHash,
        ];

        $success
            ? Log::info('passkey.login', $context)
            : Log::warning('passkey.login', $context);

        return response()->noContent($success ? 204 : 422);
    }
}
