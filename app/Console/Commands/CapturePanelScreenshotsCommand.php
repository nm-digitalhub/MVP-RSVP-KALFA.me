<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PanelScreenshotRunner;
use App\Services\PanelScreenshotSessionCookies;
use App\Support\PanelScreenshotAuthPaths;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CapturePanelScreenshotsCommand extends Command
{
    protected $signature = 'panel:capture-screenshots
                            {--url=* : Path(s) under APP_URL (repeatable), e.g. /login}
                            {--all : Capture the full auth path list (tenant + system); requires --auth}
                            {--output= : Subdirectory under public/ (default from config panel-screenshot.public_subdir)}
                            {--device=* : Presets: desktop, mobile, galaxy (repeatable)}
                            {--auth : Log in with panel-screenshot.login_* (or CAPTURE_*) and pass session cookies to Browsershot}';

    protected $description = 'Capture full-page screenshots via Spatie Browsershot. Use --auth --all for the full authenticated path set.';

    public function handle(PanelScreenshotRunner $runner): int
    {
        if ($this->option('all')) {
            if (! $this->option('auth')) {
                $this->error('--all requires --auth (all listed paths need a logged-in session).');

                return self::FAILURE;
            }

            $paths = PanelScreenshotAuthPaths::all();

            if ($this->option('url') !== []) {
                $this->warn('Ignoring --url because --all is set.');
            }
        } elseif ($this->option('url') !== []) {
            $paths = $this->option('url');
        } else {
            $paths = config('panel-screenshot.default_paths', ['/login']);
        }

        if ($paths === [] || $paths === ['']) {
            $this->error('No URLs to capture. Use --all --auth, pass --url=…, or set PANEL_SCREENSHOT_DEFAULT_PATHS.');

            return self::FAILURE;
        }

        $devices = $this->option('device') ?: array_keys(PanelScreenshotRunner::devicePresets());
        $output = $this->option('output') ?: config('panel-screenshot.public_subdir');

        $allowed = array_keys(PanelScreenshotRunner::devicePresets());

        foreach ($devices as $device) {
            if (! in_array($device, $allowed, true)) {
                $this->error("Unknown device [{$device}]. Use: ".implode(', ', $allowed));

                return self::FAILURE;
            }
        }

        $normalized = [];

        foreach ($paths as $path) {
            $normalized[] = Str::start(trim((string) $path, " \t\n\r\0\x0B"), '/');
        }

        $this->info('Capturing '.count($normalized).' path(s) × '.count($devices).' device(s) → public/'.trim((string) $output, '/'));

        $sessionCookies = null;

        if ($this->option('auth')) {
            $email = (string) config('panel-screenshot.login_email', '');
            $password = (string) config('panel-screenshot.login_password', '');

            if ($email === '' || $password === '') {
                $this->error('Missing credentials. Set PANEL_SCREENSHOT_LOGIN_EMAIL and PANEL_SCREENSHOT_LOGIN_PASSWORD (or CAPTURE_EMAIL / CAPTURE_PASSWORD).');

                return self::FAILURE;
            }

            $this->info('Authenticating via web login (Kernel) for Browsershot session cookies…');

            try {
                $sessionCookies = PanelScreenshotSessionCookies::fromWebPasswordLogin($email, $password);
            } catch (\Throwable $e) {
                $this->error('Login failed: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        try {
            $runner->runToPublic($normalized, $output, $devices, $sessionCookies);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Done. Pattern: public/'.trim((string) $output, '/').'/{device}-{path}-browsershot.png');

        return self::SUCCESS;
    }
}
