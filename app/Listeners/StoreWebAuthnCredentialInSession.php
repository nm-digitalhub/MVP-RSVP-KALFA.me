<?php

declare(strict_types=1);

namespace App\Listeners;

use Laragear\WebAuthn\Events\CredentialAsserted;

class StoreWebAuthnCredentialInSession
{
    /**
     * Store the asserted credential ID in the session so that ManagePasskeys
     * can highlight which passkey was used for the current login.
     *
     * The key is cleared automatically when the session is invalidated on logout.
     */
    public function handle(CredentialAsserted $event): void
    {
        request()->session()->put('webauthn.current_credential_id', $event->credential->id);
    }
}
