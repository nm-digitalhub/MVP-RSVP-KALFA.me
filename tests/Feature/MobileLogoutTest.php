<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MobileLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_logout_requires_a_valid_sanctum_token(): void
    {
        $this->postJson(route('mobile.auth.logout'))->assertUnauthorized();
    }

    public function test_mobile_logout_revokes_only_the_current_access_token(): void
    {
        $user = User::factory()->create();

        $currentPlainTextToken = $user->createToken('Current iPhone', ['mobile:base', 'mobile:read'])->plainTextToken;
        $otherPlainTextToken = $user->createToken('Other iPad', ['mobile:base', 'mobile:read'])->plainTextToken;

        $currentTokenId = PersonalAccessToken::findToken($currentPlainTextToken)?->id;
        $otherTokenId = PersonalAccessToken::findToken($otherPlainTextToken)?->id;

        $response = $this->withHeader('Authorization', 'Bearer '.$currentPlainTextToken)
            ->postJson(route('mobile.auth.logout'));

        $response->assertOk()
            ->assertJson([
                'message' => 'Current device signed out.',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $currentTokenId]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherTokenId]);
    }

    public function test_mobile_logout_other_devices_revokes_all_other_tokens_but_keeps_the_current_one(): void
    {
        $user = User::factory()->create();

        $currentPlainTextToken = $user->createToken('Current iPhone', ['mobile:base', 'mobile:read', 'mobile:write'])->plainTextToken;
        $otherPhoneToken = $user->createToken('Other iPhone', ['mobile:base', 'mobile:read'])->plainTextToken;
        $otherTabletToken = $user->createToken('Other iPad', ['mobile:base'])->plainTextToken;

        $currentTokenId = PersonalAccessToken::findToken($currentPlainTextToken)?->id;
        $otherPhoneTokenId = PersonalAccessToken::findToken($otherPhoneToken)?->id;
        $otherTabletTokenId = PersonalAccessToken::findToken($otherTabletToken)?->id;

        $response = $this->withHeader('Authorization', 'Bearer '.$currentPlainTextToken)
            ->postJson(route('mobile.auth.logout.others'));

        $response->assertOk()
            ->assertJson([
                'message' => 'Other devices signed out.',
                'revoked_tokens_count' => 2,
            ]);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentTokenId]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherPhoneTokenId]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherTabletTokenId]);
    }

    public function test_mobile_logout_other_devices_returns_zero_when_no_other_tokens_exist(): void
    {
        $user = User::factory()->create();

        $currentPlainTextToken = $user->createToken('Current iPhone', ['mobile:base'])->plainTextToken;
        $currentTokenId = PersonalAccessToken::findToken($currentPlainTextToken)?->id;

        $response = $this->withHeader('Authorization', 'Bearer '.$currentPlainTextToken)
            ->postJson(route('mobile.auth.logout.others'));

        $response->assertOk()
            ->assertJson([
                'message' => 'Other devices signed out.',
                'revoked_tokens_count' => 0,
            ]);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentTokenId]);
    }

    public function test_mobile_logout_returns_a_clear_error_when_no_current_access_token_is_available(): void
    {
        $response = $this->postJson(route('mobile.auth.logout'));

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_mobile_logout_other_devices_returns_a_clear_error_when_no_current_access_token_is_available(): void
    {
        $response = $this->postJson(route('mobile.auth.logout.others'));

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_mobile_logout_denies_tokens_without_mobile_base_ability(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('Current iPhone', ['mobile:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('mobile.auth.logout'));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Invalid ability provided.',
            ]);
    }

    public function test_mobile_logout_other_devices_denies_tokens_without_mobile_base_ability(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('Current iPhone', ['mobile:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('mobile.auth.logout.others'));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Invalid ability provided.',
            ]);
    }
}
