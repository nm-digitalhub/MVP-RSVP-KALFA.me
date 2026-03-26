<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\PanelScreenshotSessionCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PanelScreenshotSessionCookiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_web_password_login_returns_session_cookie(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $cookies = PanelScreenshotSessionCookies::fromWebPasswordLogin(
            $user->email,
            'correct-password',
        );

        $sessionName = (string) config('session.cookie');

        $this->assertArrayHasKey($sessionName, $cookies);
        $this->assertNotSame('', $cookies[$sessionName]);
    }

    public function test_from_web_password_login_rejects_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $this->expectException(\RuntimeException::class);

        PanelScreenshotSessionCookies::fromWebPasswordLogin($user->email, 'wrong-password');
    }
}
