<?php

namespace App\Providers;

use App\Services\MjmlRenderer;
use Illuminate\Support\ServiceProvider;

class MjmlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MjmlRenderer::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
