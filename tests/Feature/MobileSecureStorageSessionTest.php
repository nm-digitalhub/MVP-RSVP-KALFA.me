<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\MobileSecureTokenStore;
use Mockery\MockInterface;
use Tests\TestCase;

class MobileSecureStorageSessionTest extends TestCase
{
    public function test_mobile_secure_storage_status_reports_a_stored_credential_without_claiming_authenticated_state(): void
    {
        $this->mock(MobileSecureTokenStore::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAvailable')->once()->andReturn(true);
            $mock->shouldReceive('hasToken')->once()->andReturn(true);
        });

        $this->getJson(route('mobile.session.status'))
            ->assertOk()
            ->assertJson([
                'available' => true,
                'has_token' => true,
                'state' => 'credential_stored',
            ])
            ->assertJsonMissingPath('access_token');
    }

    public function test_mobile_secure_storage_status_reports_when_secure_storage_is_unavailable(): void
    {
        $this->mock(MobileSecureTokenStore::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAvailable')->once()->andReturn(false);
            $mock->shouldReceive('hasToken')->never();
        });

        $this->getJson(route('mobile.session.status'))
            ->assertOk()
            ->assertJson([
                'available' => false,
                'has_token' => false,
                'state' => 'unauthenticated',
            ]);
    }

    public function test_mobile_secure_storage_can_store_a_mobile_token_securely(): void
    {
        $this->mock(MobileSecureTokenStore::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAvailable')->once()->andReturn(true);
            $mock->shouldReceive('putToken')->once()->with('plain-text-mobile-token')->andReturn(true);
        });

        $this->putJson(route('mobile.session.store'), [
            'access_token' => 'plain-text-mobile-token',
        ])->assertOk()
            ->assertJson([
                'message' => 'Mobile token stored securely.',
                'available' => true,
                'has_token' => true,
                'state' => 'credential_stored',
            ]);
    }

    public function test_mobile_secure_storage_store_route_requires_an_access_token(): void
    {
        $this->putJson(route('mobile.session.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['access_token']);
    }

    public function test_mobile_secure_storage_returns_a_conflict_when_secure_storage_is_unavailable(): void
    {
        $this->mock(MobileSecureTokenStore::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAvailable')->once()->andReturn(false);
            $mock->shouldReceive('putToken')->never();
        });

        $this->putJson(route('mobile.session.store'), [
            'access_token' => 'plain-text-mobile-token',
        ])->assertStatus(409)
            ->assertJson([
                'message' => 'Secure storage is unavailable.',
            ]);
    }

    public function test_mobile_secure_storage_can_delete_a_stored_token(): void
    {
        $this->mock(MobileSecureTokenStore::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAvailable')->once()->andReturn(true);
            $mock->shouldReceive('deleteToken')->once()->andReturn(true);
        });

        $this->deleteJson(route('mobile.session.destroy'))
            ->assertOk()
            ->assertJson([
                'message' => 'Mobile token removed from secure storage.',
                'available' => true,
                'has_token' => false,
                'state' => 'unauthenticated',
            ]);
    }
}
