<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class TwilioSettings extends Settings
{
    public string $sid;

    public string $token;

    public string $number;

    public string $messaging_service_sid;

    public string $verify_sid;

    public string $api_key;

    public string $api_secret;

    public bool $is_active;

    public static function group(): string
    {
        return 'twilio';
    }
}
