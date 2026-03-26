<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\User;
use App\Support\PanelScreenshotAuthPaths;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use ReflectionProperty;
use Spatie\LaravelScreenshot\Facades\Screenshot;
use Spatie\LaravelScreenshot\FakeScreenshotBuilder;
use Tests\TestCase;

class CapturePanelScreenshotsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_triggers_screenshot_builder_per_device(): void
    {
        Screenshot::fake();

        $this->artisan('panel:capture-screenshots', [
            '--url' => ['/login'],
            '--device' => ['desktop', 'mobile'],
        ])->assertSuccessful();

        /** @var FakeScreenshotBuilder $fake */
        $fake = Screenshot::getFacadeRoot();
        $fake->assertSaved();

        $expectedUrl = rtrim((string) config('app.url'), '/').'/login';

        $fake->assertSaved(function ($builder, $path) use ($expectedUrl): bool {
            return $builder->url === $expectedUrl
                && str_ends_with((string) $path, '-browsershot.png');
        });
    }

    public function test_command_rejects_unknown_device(): void
    {
        Screenshot::fake();

        $this->artisan('panel:capture-screenshots', [
            '--url' => ['/login'],
            '--device' => ['not-a-device'],
        ])->assertFailed();
    }

    public function test_command_auth_fails_without_credentials(): void
    {
        Screenshot::fake();

        config([
            'panel-screenshot.login_email' => '',
            'panel-screenshot.login_password' => '',
        ]);

        $this->artisan('panel:capture-screenshots', [
            '--url' => ['/login'],
            '--auth' => true,
        ])->assertFailed();
    }

    public function test_command_all_requires_auth(): void
    {
        Screenshot::fake();

        $this->artisan('panel:capture-screenshots', [
            '--all' => true,
        ])->assertFailed();
    }

    public function test_command_all_with_auth_invokes_screenshot_per_path_and_device(): void
    {
        Screenshot::fake();

        $user = User::factory()->create([
            'password' => Hash::make('secret'),
        ]);

        config([
            'panel-screenshot.login_email' => $user->email,
            'panel-screenshot.login_password' => 'secret',
        ]);

        $pathCount = count(PanelScreenshotAuthPaths::all());

        $this->artisan('panel:capture-screenshots', [
            '--all' => true,
            '--auth' => true,
            '--device' => ['desktop'],
        ])->assertSuccessful();

        /** @var FakeScreenshotBuilder $fake */
        $fake = Screenshot::getFacadeRoot();
        $savedProperty = new ReflectionProperty(FakeScreenshotBuilder::class, 'savedScreenshots');
        $savedProperty->setAccessible(true);
        $saved = $savedProperty->getValue($fake);

        $this->assertCount($pathCount, $saved);
    }

    public function test_command_with_auth_succeeds_after_kernel_login(): void
    {
        Screenshot::fake();

        $user = User::factory()->create([
            'password' => Hash::make('secret'),
        ]);

        config([
            'panel-screenshot.login_email' => $user->email,
            'panel-screenshot.login_password' => 'secret',
        ]);

        $this->artisan('panel:capture-screenshots', [
            '--url' => ['/dashboard'],
            '--device' => ['desktop'],
            '--auth' => true,
        ])->assertSuccessful();

        /** @var FakeScreenshotBuilder $fake */
        $fake = Screenshot::getFacadeRoot();
        $fake->assertSaved();
    }
}
