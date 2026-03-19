<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class MobileContractConfigTest extends TestCase
{
    public function test_mobile_contract_freezes_the_phase_two_bootstrap_payload(): void
    {
        $this->assertSame('/api/bootstrap', config('mobile.bootstrap.endpoint'));
        $this->assertSame([
            'user',
            'current_organization',
            'memberships',
            'abilities',
            'flags',
            'server_time',
        ], config('mobile.bootstrap.payload'));
    }

    public function test_mobile_contract_exposes_an_explicit_remote_api_base_url_and_paths(): void
    {
        $this->assertSame('https://kalfa.me', config('mobile.api.base_url'));
        $this->assertSame([
            'login' => '/api/mobile/auth/login',
            'logout' => '/api/mobile/auth/logout',
            'logout_others' => '/api/mobile/auth/logout/others',
            'bootstrap' => '/api/bootstrap',
        ], config('mobile.api.endpoints'));
    }

    public function test_mobile_contract_limits_local_cache_to_read_only_entities(): void
    {
        $this->assertSame('read-only', config('mobile.cache.mode'));
        $this->assertSame([
            'user',
            'organizations',
            'events',
            'guests',
            'invitations',
        ], array_keys(config('mobile.cache.entities')));
    }

    public function test_mobile_contract_keeps_offline_mutations_disabled_for_phase_two(): void
    {
        $this->assertFalse(config('mobile.offline.mutations_enabled'));
        $this->assertFalse(config('mobile.offline.outbox_enabled'));
    }

    public function test_mobile_contract_marks_sensitive_or_complex_domains_as_remote_only(): void
    {
        $this->assertSame([
            'event_tables',
            'seat_assignments',
            'payments',
            'checkout',
            'organization_settings',
            'billing',
            'webauthn',
            'twilio',
        ], config('mobile.cache.remote_only'));
    }
}
