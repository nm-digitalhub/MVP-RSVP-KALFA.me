<?php

use App\Providers\AppServiceProvider;
use App\Providers\MjmlServiceProvider;
use App\Providers\NativeServiceProvider;
use App\Providers\SystemSettingsServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    MjmlServiceProvider::class,
    NativeServiceProvider::class,
    SystemSettingsServiceProvider::class,
    TelescopeServiceProvider::class,
];
