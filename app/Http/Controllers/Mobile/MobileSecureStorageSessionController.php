<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreMobileSessionTokenRequest;
use App\Services\MobileSecureTokenStore;
use Illuminate\Http\JsonResponse;

class MobileSecureStorageSessionController extends Controller
{
    public function __construct(protected MobileSecureTokenStore $tokenStore) {}

    public function show(): JsonResponse
    {
        $available = $this->tokenStore->isAvailable();
        $hasToken = $available && $this->tokenStore->hasToken();

        return response()->json([
            'available' => $available,
            'has_token' => $hasToken,
            'state' => $hasToken ? 'authenticated' : 'unauthenticated',
        ]);
    }

    public function store(StoreMobileSessionTokenRequest $request): JsonResponse
    {
        if (! $this->tokenStore->isAvailable()) {
            return response()->json([
                'message' => 'Secure storage is unavailable.',
            ], 409);
        }

        if (! $this->tokenStore->putToken($request->validated('access_token'))) {
            return response()->json([
                'message' => 'Unable to store mobile token securely.',
            ], 500);
        }

        return response()->json([
            'message' => 'Mobile token stored securely.',
            'available' => true,
            'has_token' => true,
        ]);
    }

    public function destroy(): JsonResponse
    {
        if (! $this->tokenStore->isAvailable()) {
            return response()->json([
                'message' => 'Secure storage is unavailable.',
            ], 409);
        }

        if (! $this->tokenStore->deleteToken()) {
            return response()->json([
                'message' => 'Unable to clear secure mobile token.',
            ], 500);
        }

        return response()->json([
            'message' => 'Mobile token removed from secure storage.',
            'available' => true,
            'has_token' => false,
        ]);
    }
}
