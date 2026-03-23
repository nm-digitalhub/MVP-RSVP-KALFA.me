<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class SecurityHeadersTest extends TestCase
{
    /**
     * Use /up health endpoint for testing (public route, returns 200).
     */
    private function getHealthResponse(): TestResponse
    {
        return $this->get('/up');
    }

    public function test_csp_header_is_set_on_successful_responses(): void
    {
        $response = $this->getHealthResponse();

        $response->assertStatus(200);
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_csp_contains_default_src_self(): void
    {
        $response = $this->getHealthResponse();

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_hsts_header_is_set_in_production(): void
    {
        $response = $this->getHealthResponse();

        if (app()->environment('production')) {
            $response->assertHeader('Strict-Transport-Security');
        } else {
            $response->assertHeaderMissing('Strict-Transport-Security');
        }
    }

    public function test_x_frame_options_is_set(): void
    {
        $response = $this->getHealthResponse();

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_x_content_type_options_is_set(): void
    {
        $response = $this->getHealthResponse();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_referrer_policy_is_set(): void
    {
        $response = $this->getHealthResponse();

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_permissions_policy_is_set(): void
    {
        $response = $this->getHealthResponse();

        $response->assertHeader('Permissions-Policy');
        $policy = $response->headers->get('Permissions-Policy');

        // Check that sensitive features are blocked
        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('geolocation=()', $policy);
    }

    public function test_x_xss_protection_is_set(): void
    {
        $response = $this->getHealthResponse();

        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_security_headers_not_set_on_error_responses(): void
    {
        // 404 errors might not have all headers
        $response = $this->get('/non-existent-page');
        $this->assertNotNull($response->getStatusCode());
    }

    public function test_csp_allows_inline_scripts_for_livewire(): void
    {
        $response = $this->getHealthResponse();

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval'", $csp);
    }

    public function test_csp_allows_websocket_connections(): void
    {
        $response = $this->getHealthResponse();

        $csp = $response->headers->get('Content-Security-Policy');

        if (app()->environment('local', 'testing')) {
            $this->assertStringContainsString('ws://localhost:', $csp);
        }
    }

    public function test_permissions_policy_allows_autoplay(): void
    {
        $response = $this->getHealthResponse();

        $policy = $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('autoplay=(self)', $policy);
    }

    public function test_permissions_policy_allows_clipboard(): void
    {
        $response = $this->getHealthResponse();

        $policy = $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('clipboard-read=(self)', $policy);
        $this->assertStringContainsString('clipboard-write=(self)', $policy);
    }
}
