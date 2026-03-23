<?php

use App\Providers\AppServiceProvider;
use App\Providers\MjmlServiceProvider;
use App\Providers\NativeServiceProvider;
use App\Providers\PulseServiceProvider;
use App\Providers\SystemSettingsServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    MjmlServiceProvider::class,
    NativeServiceProvider::class,
    PulseServiceProvider::class,
    SystemSettingsServiceProvider::class,
    TelescopeServiceProvider::class,
];
