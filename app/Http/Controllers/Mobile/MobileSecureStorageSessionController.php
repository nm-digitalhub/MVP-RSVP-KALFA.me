<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreMobileSessionTokenRequest;
use App\Services\MobileSecureTokenStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MobileSecureStorageSessionController extends Controller
{
    public function __construct(protected MobileSecureTokenStore $tokenStore) {}

    public function show(): JsonResponse
    {
        $available = $this->tokenStore->isAvailable();
        $hasToken = $available && $this->tokenStore->hasToken();

        Log::debug('[NativePHP/Session] show', [
            'available' => $available,
            'has_token' => $hasToken,
        ]);

        return response()->json([
            'available' => $available,
            'has_token' => $hasToken,
            'state' => $hasToken ? 'credential_stored' : 'unauthenticated',
        ]);
    }

    public function store(StoreMobileSessionTokenRequest $request): JsonResponse
    {
        $available = $this->tokenStore->isAvailable();
        Log::debug('[NativePHP/Session] store: attempt', ['available' => $available]);

        if (! $available) {
            Log::warning('[NativePHP/Session] store: bridge unavailable → 409');

            return response()->json([
                'message' => 'Secure storage is unavailable.',
            ], 409);
        }

        $stored = $this->tokenStore->putToken($request->validated('access_token'));
        Log::debug('[NativePHP/Session] store: putToken result', ['stored' => $stored]);

        if (! $stored) {
            Log::warning('[NativePHP/Session] store: putToken returned false → 500');

            return response()->json([
                'message' => 'Unable to store mobile token securely.',
            ], 500);
        }

        return response()->json([
            'message' => 'Mobile token stored securely.',
            'available' => true,
            'has_token' => true,
            'state' => 'credential_stored',
        ]);
    }

    public function destroy(): JsonResponse
    {
        $available = $this->tokenStore->isAvailable();
        Log::debug('[NativePHP/Session] destroy: attempt', ['available' => $available]);

        if (! $available) {
            Log::warning('[NativePHP/Session] destroy: bridge unavailable → 409');

            return response()->json([
                'message' => 'Secure storage is unavailable.',
            ], 409);
        }

        $deleted = $this->tokenStore->deleteToken();
        Log::debug('[NativePHP/Session] destroy: deleteToken result', ['deleted' => $deleted]);

        if (! $deleted) {
            Log::warning('[NativePHP/Session] destroy: deleteToken returned false → 500');

            return response()->json([
                'message' => 'Unable to clear secure mobile token.',
            ], 500);
        }

        return response()->json([
            'message' => 'Mobile token removed from secure storage.',
            'available' => true,
            'has_token' => false,
            'state' => 'unauthenticated',
        ]);
    }
}
