<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeminiSettings extends Settings
{
    public string $api_key;

    public string $model;

    public bool $is_active;

    public static function group(): string
    {
        return 'gemini';
    }
}
