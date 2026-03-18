<?php

return array_filter([
    App\Providers\AppServiceProvider::class,
    App\Providers\NativeServiceProvider::class,
    App\Providers\SystemSettingsServiceProvider::class,
    class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)
        ? App\Providers\TelescopeServiceProvider::class
        : null,
]);
