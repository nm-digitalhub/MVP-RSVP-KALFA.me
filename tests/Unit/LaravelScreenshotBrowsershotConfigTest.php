<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class LaravelScreenshotBrowsershotConfigTest extends TestCase
{
    public function test_browsershot_no_sandbox_config_is_boolean(): void
    {
        $value = config('laravel-screenshot.browsershot.no_sandbox');

        $this->assertIsBool($value);
    }
}
