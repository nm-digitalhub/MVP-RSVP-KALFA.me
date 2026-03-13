<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\Models\WebAuthnCredential;
use Livewire\Component;

class ManagePasskeys extends Component
{
    /** @var Collection<int, WebAuthnCredential> */
    public Collection $credentials;

    public function mount(): void
    {
        $this->loadCredentials();
    }

    public function delete(string $id): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $user->webAuthnCredentials()->where('id', $id)->delete();

        Log::info('passkey.delete', [
            'event_version' => 'v1',
            'request_id' => request()->header('X-Request-Id'),
            'auth_method' => 'passkey',
            'flow_stage' => 'delete',
            'outcome' => 'success',
            'user_id' => $user->id,
            'credential_id' => substr(hash('sha256', $id), 0, 16),
            'ip' => request()->ip(),
            'ua_hash' => substr(hash('sha256', request()->userAgent() ?? ''), 0, 16),
        ]);

        $this->loadCredentials();

        $this->dispatch('passkey-deleted');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.profile.manage-passkeys');
    }

    private function loadCredentials(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->credentials = $user->webAuthnCredentials()->get();
    }
}
