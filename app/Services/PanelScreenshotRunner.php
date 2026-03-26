<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelScreenshot\Enums\WaitUntil;
use Spatie\LaravelScreenshot\Facades\Screenshot;

/**
 * Panel screenshots via Spatie Laravel Screenshot (Browsershot / Puppeteer).
 *
 * Requirements: Node, npm, Puppeteer/Chrome per
 * https://spatie.be/docs/browsershot/v4/requirements
 * and package setup https://spatie.be/docs/laravel-screenshot/v1/installation-setup
 *
 * Authenticated captures: pass session cookies from {@see PanelScreenshotSessionCookies}
 * so Browsershot sends the same session as a logged-in browser (Spatie: useCookies).
 * Optional HTTP Basic via config `panel-screenshot.http_basic_*` (Browsershot::authenticate).
 */
class PanelScreenshotRunner
{
    /**
     * Viewport + UA presets (desktop, iPhone-class, Samsung Galaxy-class).
     *
     * @return array<string, array{width: int, height: int, device_scale_factor: int, user_agent: string}>
     */
    public static function devicePresets(): array
    {
        return [
            'desktop' => [
                'width' => 1600,
                'height' => 1200,
                'device_scale_factor' => 1,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            ],
            'mobile' => [
                'width' => 393,
                'height' => 852,
                'device_scale_factor' => 3,
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
            ],
            'galaxy' => [
                'width' => 412,
                'height' => 915,
                'device_scale_factor' => 3,
                'user_agent' => 'Mozilla/5.0 (Linux; Android 14; SM-S921B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36',
            ],
        ];
    }

    /**
     * @param  array<string, string>|null  $sessionCookies  name => value for Browsershot::useCookies()
     */
    public function captureUrl(string $absoluteUrl, string $outputFullPath, string $deviceKey, ?array $sessionCookies = null): void
    {
        $presets = self::devicePresets();

        if (! isset($presets[$deviceKey])) {
            throw new InvalidArgumentException(
                "Unknown device [{$deviceKey}]. Allowed: ".implode(', ', array_keys($presets)),
            );
        }

        $preset = $presets[$deviceKey];
        $waitMs = (int) config('panel-screenshot.wait_ms', 800);

        $directory = dirname($outputFullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $cookieDomain = self::cookieDomainForBrowsershot($absoluteUrl);

        Screenshot::url($absoluteUrl)
            ->size($preset['width'], $preset['height'])
            ->deviceScaleFactor($preset['device_scale_factor'])
            ->fullPage()
            ->waitForTimeout($waitMs)
            ->waitUntil(WaitUntil::NetworkIdle2)
            ->withBrowsershot(function (Browsershot $browsershot) use ($preset, $sessionCookies, $cookieDomain): void {
                $browsershot->userAgent($preset['user_agent']);

                $basicUser = config('panel-screenshot.http_basic_user');
                $basicPass = config('panel-screenshot.http_basic_password');
                if (is_string($basicUser) && $basicUser !== '' && is_string($basicPass)) {
                    $browsershot->authenticate($basicUser, $basicPass);
                }

                if ($sessionCookies !== null && $sessionCookies !== []) {
                    $browsershot->useCookies($sessionCookies, $cookieDomain);
                }

                if (config('laravel-screenshot.browsershot.no_sandbox')) {
                    $browsershot->noSandbox();
                }
            })
            ->save($outputFullPath);
    }

    /**
     * Domain argument for {@see Browsershot::useCookies()} (Puppeteer setCookie).
     */
    public static function cookieDomainForBrowsershot(string $absoluteUrl): string
    {
        $configured = config('session.domain');

        if (is_string($configured) && $configured !== '') {
            return ltrim($configured, '.');
        }

        $host = parse_url($absoluteUrl, PHP_URL_HOST);

        return is_string($host) ? $host : '';
    }

    /**
     * @param  array<int, string>  $relativePaths  e.g. ['/login']
     * @param  array<int, string>  $deviceKeys  e.g. ['desktop','mobile']
     * @param  array<string, string>|null  $sessionCookies  from {@see PanelScreenshotSessionCookies}
     */
    public function runToPublic(array $relativePaths, string $publicSubdir, array $deviceKeys, ?array $sessionCookies = null): void
    {
        $base = rtrim((string) config('app.url'), '/');
        $root = public_path(trim($publicSubdir, '/'));

        foreach ($relativePaths as $path) {
            $url = $base.'/'.ltrim($path, '/');
            $slug = trim(str_replace('/', '-', $path), '-') ?: 'home';

            foreach ($deviceKeys as $device) {
                $file = $root.'/'.$device.'-'.$slug.'-browsershot.png';
                $this->captureUrl($url, $file, $device, $sessionCookies);
            }
        }
    }
}
