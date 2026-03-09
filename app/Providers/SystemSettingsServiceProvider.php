<?php

declare(strict_types=1);

namespace App\Providers;

use App\Settings\GeminiSettings;
use App\Settings\SumitSettings;
use App\Settings\TwilioSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SystemSettingsServiceProvider extends ServiceProvider
{
    public function boot(SumitSettings $sumit, TwilioSettings $twilio, GeminiSettings $gemini): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        if ($sumit->is_active) {
            config([
                'officeguy.company_id' => $sumit->company_id ?: config('officeguy.company_id'),
                'officeguy.private_key' => $sumit->private_key ?: config('officeguy.private_key'),
                'officeguy.public_key' => $sumit->public_key ?: config('officeguy.public_key'),
                'officeguy.environment' => $sumit->environment ?: config('officeguy.environment'),
                'officeguy.testing' => $sumit->is_test_mode,
            ]);
        }

        if ($twilio->is_active) {
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

        if ($gemini->is_active) {
            config([
                'services.gemini.key' => $gemini->api_key ?: config('services.gemini.key'),
                'services.gemini.model' => $gemini->model ?: config('services.gemini.model'),
            ]);
        }
    }
}
