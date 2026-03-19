<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MobileLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class MobileAuthController extends Controller
{
    private const TOKEN_ABILITIES = [
        'mobile:base',
        'mobile:read',
        'mobile:write',
    ];

    public function login(MobileLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->is_disabled) {
            throw ValidationException::withMessages([
                'email' => [__('This account has been disabled.')],
            ]);
        }

        $user->update(['last_login_at' => now()]);

        $accessToken = $user->createToken($validated['device_name'], self::TOKEN_ABILITIES)->plainTextToken;

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'device_name' => $validated['device_name'],
            'abilities' => self::TOKEN_ABILITIES,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'current_organization_id' => $user->current_organization_id,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $currentAccessToken = $request->user()?->currentAccessToken();

        if (! $currentAccessToken instanceof PersonalAccessToken) {
            return response()->json([
                'message' => 'No active access token found.',
            ], 401);
        }

        $currentAccessToken->delete();

        return response()->json([
            'message' => 'Current device signed out.',
        ]);
    }

    public function logoutOtherDevices(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentAccessToken = $user?->currentAccessToken();

        if (! $user instanceof User || ! $currentAccessToken instanceof PersonalAccessToken) {
            return response()->json([
                'message' => 'No active access token found.',
            ], 401);
        }

        $revokedTokensCount = $user->tokens()
            ->where('id', '!=', $currentAccessToken->id)
            ->delete();

        return response()->json([
            'message' => 'Other devices signed out.',
            'revoked_tokens_count' => $revokedTokensCount,
        ]);
    }
}
