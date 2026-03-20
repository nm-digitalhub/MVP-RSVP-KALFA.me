<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Native\Mobile\Facades\SecureStorage;

class MobileSecureTokenStore
{
    public function isAvailable(): bool
    {
        $available = function_exists('nativephp_call');
        Log::debug('[NativePHP/SecureStore] isAvailable', ['nativephp_call_exists' => $available]);

        return $available;
    }

    public function hasToken(): bool
    {
        return $this->getToken() !== null;
    }

    public function getToken(): ?string
    {
        if (! $this->isAvailable()) {
            Log::debug('[NativePHP/SecureStore] getToken: bridge unavailable — returning null');

            return null;
        }

        $value = SecureStorage::get($this->accessTokenKey());
        Log::debug('[NativePHP/SecureStore] getToken', [
            'key' => $this->accessTokenKey(),
            'has_value' => $value !== null,
            'value_length' => $value !== null ? mb_strlen($value) : 0,
        ]);

        return $value;
    }

    public function putToken(string $token): bool
    {
        if (! $this->isAvailable()) {
            Log::warning('[NativePHP/SecureStore] putToken: bridge unavailable — cannot store credential');

            return false;
        }

        $result = SecureStorage::set($this->accessTokenKey(), $token);
        Log::debug('[NativePHP/SecureStore] putToken', [
            'key' => $this->accessTokenKey(),
            'token_length' => mb_strlen($token),
            'result' => $result,
        ]);

        return $result;
    }

    public function deleteToken(): bool
    {
        if (! $this->isAvailable()) {
            Log::debug('[NativePHP/SecureStore] deleteToken: bridge unavailable — returning false');

            return false;
        }

        $result = SecureStorage::delete($this->accessTokenKey());
        Log::debug('[NativePHP/SecureStore] deleteToken', ['result' => $result]);

        return $result;
    }

    protected function accessTokenKey(): string
    {
        return (string) config('mobile.secure_storage.access_token_key', 'kalfa.mobile.access_token');
    }
}
