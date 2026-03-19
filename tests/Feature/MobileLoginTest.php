<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_issues_sanctum_token_with_device_name(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('mobile.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Nuno iPhone 17',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'device_name',
                'abilities',
                'user' => ['id', 'name', 'email', 'current_organization_id'],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('device_name', 'Nuno iPhone 17')
            ->assertJsonPath('abilities.0', 'mobile:base')
            ->assertJsonPath('abilities.1', 'mobile:read')
            ->assertJsonPath('abilities.2', 'mobile:write');

        $user->refresh();

        $this->assertGuest();
        $this->assertNotNull($user->last_login_at);
        $this->assertCount(1, $user->tokens);
        $this->assertSame('Nuno iPhone 17', $user->tokens->first()->name);
        $this->assertSame(['mobile:base', 'mobile:read', 'mobile:write'], $user->tokens->first()->abilities);
    }

    public function test_mobile_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('mobile.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'Nuno iPhone 17',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
        $this->assertCount(0, $user->tokens);
    }

    public function test_disabled_users_can_not_log_in_to_mobile_api(): void
    {
        $user = User::factory()->create([
            'is_disabled' => true,
        ]);

        $response = $this->postJson(route('mobile.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Nuno iPhone 17',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
        $this->assertCount(0, $user->tokens);
    }
}
