<?php

declare(strict_types=1);

if (! function_exists('isRTL')) {
    /**
     * Whether the current locale is right-to-left (e.g. Hebrew, Arabic).
     *
     * Uses Laravel's locale configuration; RTL locales are defined in config/app.php.
     */
    function isRTL(): bool
    {
        return in_array(
            \Illuminate\Support\Facades\App::currentLocale(),
            config('app.rtl_locales', ['he']),
            true
        );
    }
}
