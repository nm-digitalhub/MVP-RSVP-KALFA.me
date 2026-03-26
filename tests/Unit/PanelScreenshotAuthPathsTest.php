<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\PanelScreenshotAuthPaths;
use PHPUnit\Framework\TestCase;

class PanelScreenshotAuthPathsTest extends TestCase
{
    public function test_default_paths_include_dashboard_system_and_event_routes(): void
    {
        $paths = PanelScreenshotAuthPaths::defaultPaths([
            'org' => '2',
            'account' => '3',
            'user' => '4',
            'product' => '5',
            'event' => '9',
        ]);

        $this->assertContains('/dashboard', $paths);
        $this->assertContains('/system/dashboard', $paths);
        $this->assertContains('/system/organizations/2', $paths);
        $this->assertContains('/system/products/5', $paths);
        $this->assertContains('/dashboard/events/9/guests', $paths);
        $this->assertSame($paths, array_unique($paths));
    }
}
