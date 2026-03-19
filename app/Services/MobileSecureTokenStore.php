<?php

declare(strict_types=1);

namespace App\Services;

use Native\Mobile\Facades\SecureStorage;

class MobileSecureTokenStore
{
    public function isAvailable(): bool
    {
        return function_exists('nativephp_call');
    }

    public function hasToken(): bool
    {
        return $this->getToken() !== null;
    }

    public function getToken(): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        return SecureStorage::get($this->accessTokenKey());
    }

    public function putToken(string $token): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return SecureStorage::set($this->accessTokenKey(), $token);
    }

    public function deleteToken(): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return SecureStorage::delete($this->accessTokenKey());
    }

    protected function accessTokenKey(): string
    {
        return (string) config('mobile.secure_storage.access_token_key', 'kalfa.mobile.access_token');
    }
}
