<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\StubPaymentGateway;
use App\Services\SumitPaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $gateway = config('billing.default_gateway', 'stub');
        $implementation = $gateway === 'sumit' ? SumitPaymentGateway::class : StubPaymentGateway::class;
        $this->app->bind(PaymentGatewayInterface::class, $implementation);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('rsvp_show', fn () => Limit::perMinute(60));
        RateLimiter::for('rsvp_submit', fn () => Limit::perMinute(10));
        RateLimiter::for('webhooks', fn () => Limit::perMinute(120));

        if (app()->environment('production') && config('billing.default_gateway') === 'sumit') {
            $this->validateSumitConfig();
        }
    }

    /**
     * Fail fast in production when SUMIT is selected but required config is missing.
     */
    private function validateSumitConfig(): void
    {
        $missing = [];
        if (blank(config('officeguy.company_id'))) {
            $missing[] = 'OFFICEGUY_COMPANY_ID';
        }
        if (blank(config('officeguy.private_key'))) {
            $missing[] = 'OFFICEGUY_PRIVATE_KEY';
        }
        if (blank(config('billing.sumit.redirect_success_url'))) {
            $missing[] = 'BILLING_SUMIT_SUCCESS_URL';
        }
        if (blank(config('billing.sumit.redirect_cancel_url'))) {
            $missing[] = 'BILLING_SUMIT_CANCEL_URL';
        }
        if ($missing !== []) {
            throw new \RuntimeException(
                'SUMIT gateway is selected (BILLING_GATEWAY=sumit) but required config is missing in production: ' . implode(', ', $missing) . '. Set these in .env.'
            );
        }
    }
}
