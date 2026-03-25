<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class PwaBladeComponentsTest extends TestCase
{
    public function test_pwa_head_renders_manifest_link_and_meta(): void
    {
        $html = Blade::render('<x-pwa-head />');

        $this->assertStringContainsString('rel="manifest"', $html);
        $this->assertStringContainsString('manifest.json', $html);
        $this->assertStringContainsString('name="theme-color"', $html);
        $this->assertStringNotContainsString('@PwaHead', $html);
    }

    public function test_register_service_worker_script_renders_registration_snippet_in_web_context(): void
    {
        $html = Blade::render('<x-register-service-worker-script />');

        $this->assertStringContainsString('serviceWorker', $html);
        $this->assertStringContainsString('sw.js', $html);
        $this->assertStringNotContainsString('@RegisterServiceWorkerScript', $html);
    }

    public function test_legacy_directive_tokens_are_absent_from_layout_sources(): void
    {
        $appLayout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
        $enterpriseLayout = file_get_contents(resource_path('views/components/layouts/enterprise-app.blade.php'));

        $this->assertStringNotContainsString('@PwaHead', $appLayout);
        $this->assertStringNotContainsString('@RegisterServiceWorkerScript', $appLayout);
        $this->assertStringNotContainsString('@PwaHead', $enterpriseLayout);
        $this->assertStringNotContainsString('@RegisterServiceWorkerScript', $enterpriseLayout);
    }
}
