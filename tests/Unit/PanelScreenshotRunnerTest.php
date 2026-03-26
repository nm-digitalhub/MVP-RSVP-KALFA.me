<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PanelScreenshotRunner;
use Tests\TestCase;

class PanelScreenshotRunnerTest extends TestCase
{
    public function test_device_presets_include_desktop_mobile_and_galaxy(): void
    {
        $presets = PanelScreenshotRunner::devicePresets();

        foreach (['desktop', 'mobile', 'galaxy'] as $key) {
            $this->assertArrayHasKey($key, $presets);
            $this->assertArrayHasKey('width', $presets[$key]);
            $this->assertArrayHasKey('height', $presets[$key]);
            $this->assertArrayHasKey('device_scale_factor', $presets[$key]);
            $this->assertArrayHasKey('user_agent', $presets[$key]);
        }
    }

    public function test_cookie_domain_for_browsershot_uses_url_host_when_session_domain_empty(): void
    {
        config(['session.domain' => null]);

        $this->assertSame(
            'kalfa.me',
            PanelScreenshotRunner::cookieDomainForBrowsershot('https://kalfa.me/system/dashboard'),
        );
    }
}
