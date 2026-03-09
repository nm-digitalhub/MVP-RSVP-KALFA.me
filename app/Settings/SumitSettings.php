<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SumitSettings extends Settings
{
    public string $company_id;

    public string $private_key;

    public string $public_key;

    public string $environment;

    public bool $is_active;

    public bool $is_test_mode;

    public static function group(): string
    {
        return 'sumit';
    }
}
