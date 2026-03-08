<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\StubPaymentGateway;
use App\Services\SumitPaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Pail\Handler as PailHandler;
use Twilio\Rest\Client as TwilioClient;

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

        $this->app->singleton(TwilioClient::class, function (): TwilioClient {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            if (blank($sid) || blank($token)) {
                throw new \RuntimeException('Twilio credentials not set (TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN).');
            }

            return new TwilioClient((string) $sid, (string) $token);
        });

        $this->wrapPailHandlerInProduction();
    }

    /**
     * In production, prevent Pail from writing to .pail files on web requests
     * so we avoid "Permission denied" when the web server user cannot write
     * to files created by the CLI user (e.g. php artisan pail).
     */
    private function wrapPailHandlerInProduction(): void
    {
        if (! $this->app->bound(PailHandler::class)) {
            return;
        }

        $this->app->extend(PailHandler::class, function (PailHandler $handler): object {
            if (! app()->environment('production') || app()->runningInConsole()) {
                return $handler;
            }

            return new class($handler)
            {
                public function __construct(
                    private readonly PailHandler $wrapped,
                ) {}

                public function log(MessageLogged $messageLogged): void
                {
                    // No-op in production for web requests to avoid writing to storage/pail.
                }

                public function setLastLifecycleEvent(CommandStarting|JobProcessing|JobExceptionOccurred|null $event): void
                {
                    $this->wrapped->setLastLifecycleEvent($event);
                }
            };
        });
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
                'SUMIT gateway is selected (BILLING_GATEWAY=sumit) but required config is missing in production: '.implode(', ', $missing).'. Set these in .env.'
            );
        }
    }
}
