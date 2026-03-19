<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class MobileRefreshPolicyConfigTest extends TestCase
{
    public function test_mobile_refresh_policy_defines_numeric_ttls_in_seconds(): void
    {
        $ttl = config('mobile.refresh.ttl');

        $this->assertSame([
            'bootstrap_ttl_seconds',
            'organizations_ttl_seconds',
            'events_ttl_seconds',
            'guests_ttl_seconds',
            'invitations_ttl_seconds',
        ], array_keys($ttl));

        foreach ($ttl as $value) {
            $this->assertIsInt($value);
            $this->assertGreaterThan(0, $value);
        }
    }

    public function test_mobile_refresh_policy_defines_when_refreshes_are_attempted(): void
    {
        $this->assertSame([
            'app_launch' => true,
            'foreground_resume' => true,
            'manual_refresh' => true,
        ], config('mobile.refresh.triggers'));
    }

    public function test_mobile_refresh_policy_defines_stale_fallback_behavior(): void
    {
        $this->assertSame([
            'serve_stale_on_failure' => true,
            'mark_stale_after_ttl' => true,
            'background_refresh_on_stale' => true,
        ], config('mobile.refresh.stale_behavior'));
    }

    public function test_mobile_refresh_policy_uses_updated_at_replace_only_reads_without_offline_writes(): void
    {
        $this->assertSame('updated_at', config('mobile.refresh.strategy.comparison'));
        $this->assertSame('replace-only', config('mobile.refresh.strategy.write_mode'));
        $this->assertFalse(config('mobile.offline.mutations_enabled'));
        $this->assertFalse(config('mobile.offline.outbox_enabled'));
    }
}
