<?php

declare(strict_types=1);

namespace App\Providers;

use App\Settings\GeminiSettings;
use App\Settings\SumitSettings;
use App\Settings\TwilioSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

class SystemSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->shouldSkipForMobileShell() || ! Schema::hasTable('settings')) {
            return;
        }

        try {
            $sumit = app(SumitSettings::class);
        } catch (MissingSettings) {
            $sumit = null;
        }

        try {
            $twilio = app(TwilioSettings::class);
        } catch (MissingSettings) {
            $twilio = null;
        }

        try {
            $gemini = app(GeminiSettings::class);
        } catch (MissingSettings) {
            $gemini = null;
        }

        try {
            if ($sumit?->is_active) {
                config([
                    'officeguy.company_id' => $sumit->company_id ?: config('officeguy.company_id'),
                    'officeguy.private_key' => $sumit->private_key ?: config('officeguy.private_key'),
                    'officeguy.public_key' => $sumit->public_key ?: config('officeguy.public_key'),
                    'officeguy.environment' => $sumit->environment ?: config('officeguy.environment'),
                    'officeguy.testing' => $sumit->is_test_mode,
                ]);
            }
        } catch (MissingSettings $e) {
            // Allow tests to run even when settings records were not created yet.
            if (! app()->environment('testing')) {
                throw $e;
            }
        }

        try {
            if ($twilio?->is_active) {
                config([
                    'services.twilio.sid' => $twilio->sid ?: config('services.twilio.sid'),
                    'services.twilio.token' => $twilio->token ?: config('services.twilio.token'),
                    'services.twilio.number' => $twilio->number ?: config('services.twilio.number'),
                    'services.twilio.messaging_service_sid' => $twilio->messaging_service_sid ?: config('services.twilio.messaging_service_sid'),
                    'services.twilio.verify_sid' => $twilio->verify_sid ?: config('services.twilio.verify_sid'),
                    'services.twilio.api_key' => $twilio->api_key ?: config('services.twilio.api_key'),
                    'services.twilio.api_secret' => $twilio->api_secret ?: config('services.twilio.api_secret'),
                ]);
            }
        } catch (MissingSettings $e) {
            // Allow tests to run even when settings records were not created yet.
            if (! app()->environment('testing')) {
                throw $e;
            }
        }

        try {
            if ($gemini?->is_active) {
                config([
                    'services.gemini.key' => $gemini->api_key ?: config('services.gemini.key'),
                    'services.gemini.model' => $gemini->model ?: config('services.gemini.model'),
                ]);
            }
        } catch (MissingSettings $e) {
            // Allow tests to run even when settings records were not created yet.
            if (! app()->environment('testing')) {
                throw $e;
            }
        }
    }

    protected function shouldSkipForMobileShell(): bool
    {
        // Always skip when running inside the NativePHP mobile runtime.
        // Artisan commands (migrate --force, view:clear, storage:link) fired during
        // NativePHP deferred init run in console context where the settings table
        // and records may not yet exist in the local SQLite database.
        if (config('nativephp-internal.running', false)) {
            return true;
        }

        if ($this->app->runningInConsole() || ! $this->app->bound('request')) {
            return false;
        }

        /** @var Request $request */
        $request = $this->app->make('request');

        return $request->is('mobile') || $request->is('mobile/session') || $request->is('mobile/session/*');
    }
}
