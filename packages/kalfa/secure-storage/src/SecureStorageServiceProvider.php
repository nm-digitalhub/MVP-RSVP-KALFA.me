<?php

declare(strict_types=1);

namespace Kalfa\SecureStorage;

use Illuminate\Support\ServiceProvider;
use Kalfa\SecureStorage\Commands\CopyAssetsCommand;

class SecureStorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SecureStorage::class, function () {
            return new SecureStorage;
        });
    }

    public function boot(): void
    {
        // Register plugin hook commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyAssetsCommand::class,
            ]);
        }
    }
}
