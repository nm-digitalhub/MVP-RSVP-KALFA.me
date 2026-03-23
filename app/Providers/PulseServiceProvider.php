<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Pulse;

/**
 * Pulse APM service provider.
 */
final class PulseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configurePulseGates();
        $this->registerCustomRecorders();
    }

    /**
     * Configure Pulse authorization gates.
     */
    private function configurePulseGates(): void
    {
        // Pulse uses "viewPulse" gate - already defined in AppServiceProvider
        // System admins already have access via Gate::before
    }

    /**
     * Register custom Pulse recorders for RSVP-specific metrics.
     */
    private function registerCustomRecorders(): void
    {
        // Register custom recorders here when needed
        // $this->app->get(Pulse::class)->register(...)
    }
}
