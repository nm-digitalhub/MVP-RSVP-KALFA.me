<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laragear\WebAuthn\Models\WebAuthnCredential;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ManagePasskeys extends Component
{
    public const int MAX_PASSKEYS = 10;

    public ?string $editingId = null;

    public string $editingAlias = '';

    public function prepareLatest(): void
    {
        $latest = auth()->user()->webAuthnCredentials()->latest()->first();

        if ($latest) {
            $device = $this->resolveDeviceInfo($latest);
            $this->editingId = $latest->id;
            $this->editingAlias = $latest->alias ?? $device['name'];
        }
    }

    public function beginRename(string $id): void
    {
        $cred = auth()->user()->webAuthnCredentials()->where('id', $id)->firstOrFail();
        $device = $this->resolveDeviceInfo($cred);

        $this->editingId = $id;
        $this->editingAlias = $cred->alias ?? $device['name'];
    }

    public function saveAlias(): void
    {
        $this->validate(['editingAlias' => ['nullable', 'string', 'max:64']]);

        auth()->user()
            ->webAuthnCredentials()
            ->where('id', $this->editingId)
            ->update(['alias' => trim($this->editingAlias)]);

        $this->editingId = null;
        $this->editingAlias = '';

        $this->dispatch('passkey-renamed');
    }

    public function cancelRename(): void
    {
        $this->editingId = null;
        $this->editingAlias = '';
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

        $this->dispatch('passkey-deleted');
    }

    /** @return Collection<int, WebAuthnCredential> */
    #[Computed]
    public function credentials(): Collection
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user->webAuthnCredentials()->latest()->limit(25)->get();
    }

    #[Computed]
    public function currentCredentialId(): ?string
    {
        return session('webauthn.current_credential_id');
    }

    /** @return array{name: string, icon: string, color: string} */
    public function resolveDeviceInfo(\Laragear\WebAuthn\Models\WebAuthnCredential $credential): array
    {
        $aaguid = strtolower((string) $credential->aaguid);
        $transports = $credential->transports ?? [];

        // AAGUID → known authenticator name
        $aaguidMap = [
            'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d4' => ['name' => 'Touch ID (Safari/Chrome)',       'icon' => 'biometric'],
            'adce0002-35bc-c60a-648b-0b25f1f05503' => ['name' => 'Touch ID (Chrome)',               'icon' => 'biometric'],
            'dd4ec289-e01d-41c9-bb89-70fa845d4bf2' => ['name' => 'iCloud Keychain',                 'icon' => 'cloud'],
            '08987058-cadc-4b81-b6e1-30de50dcbe96' => ['name' => 'Windows Hello',                   'icon' => 'windows'],
            '9ddd1817-af5a-4672-a2b9-3e3dd95000a9' => ['name' => 'Windows Hello',                   'icon' => 'windows'],
            '6028b017-b1d4-4c02-b4b3-afcdafc96bb2' => ['name' => 'Windows Hello',                   'icon' => 'windows'],
            'b93fd961-f2e6-462f-b122-82002247de78' => ['name' => 'Android Fingerprint',             'icon' => 'biometric'],
            'ee882879-721c-4913-9775-3dfcce97072a' => ['name' => 'YubiKey 5 NFC',                   'icon' => 'key'],
            'f8a011f3-8c0a-4d15-8006-17111f9edc7d' => ['name' => 'YubiKey Security Key NFC',        'icon' => 'key'],
            '2fc0579f-8113-47ea-b116-bb5a8db9202a' => ['name' => 'YubiKey 5Ci',                     'icon' => 'key'],
            'cb69481e-8ff7-4039-93ec-0a2729a154a8' => ['name' => 'YubiKey 5',                       'icon' => 'key'],
            '531126d6-e717-415c-9320-3d9aa6981239' => ['name' => 'Dashlane',                        'icon' => 'cloud'],
            '0ea242b4-43c4-4a1b-8b17-dd6d0b6baec6' => ['name' => 'Keeper',                         'icon' => 'cloud'],
            'b84e4048-15dc-4dd0-8640-f4f60813c8af' => ['name' => 'NordPass',                        'icon' => 'cloud'],
            // Google
            'ea9b8d66-4d01-1d21-3ce4-b6b48cb575d5' => ['name' => 'Google Password Manager',        'icon' => 'chrome'],
            'f09a6114-8f13-4f56-9a72-9f163cf6ae50' => ['name' => 'Google Password Manager',        'icon' => 'chrome'],
            // 1Password
            'bada5566-a7aa-401f-bd96-45619a55120d' => ['name' => '1Password',                      'icon' => 'shield'],
        ];

        if (isset($aaguidMap[$aaguid])) {
            return $aaguidMap[$aaguid];
        }

        // Fallback: derive from transports
        return match (true) {
            in_array('internal', $transports) => ['name' => 'ביומטריה (FaceID / TouchID)', 'icon' => 'biometric'],
            in_array('usb', $transports) => ['name' => 'USB Security Key', 'icon' => 'key'],
            in_array('nfc', $transports) => ['name' => 'NFC Key', 'icon' => 'key'],
            in_array('ble', $transports) => ['name' => 'Bluetooth Key', 'icon' => 'key'],
            default => ['name' => 'מפתח זיהוי', 'icon' => 'key'],
        };
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.profile.manage-passkeys');
    }
}
