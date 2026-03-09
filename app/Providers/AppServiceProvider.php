<?php

namespace App\Providers;

use App\Contracts\BillingProvider;
use App\Contracts\PaymentGatewayInterface;
use App\Events\ProductEngineEvent;
use App\Listeners\LogProductEngineEvent;
use App\Services\Billing\SumitBillingProvider;
use App\Services\FeatureResolver;
use App\Services\ProductEngineOperationsMonitor;
use App\Services\ProductIntegrityChecker;
use App\Services\StubPaymentGateway;
use App\Services\SubscriptionManager;
use App\Services\SubscriptionService;
use App\Services\SumitPaymentGateway;
use App\Services\UsageMeter;
use App\Services\UsagePolicyService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
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
        $this->app->bind(BillingProvider::class, SumitBillingProvider::class);
        $this->app->singleton(UsageMeter::class, fn (): UsageMeter => new UsageMeter(app(BillingProvider::class)));
        $this->app->singleton(FeatureResolver::class, fn (): FeatureResolver => new FeatureResolver(app(UsageMeter::class)));
        $this->app->singleton(SubscriptionService::class, fn (): SubscriptionService => new SubscriptionService(app(BillingProvider::class), app(FeatureResolver::class)));
        $this->app->singleton(SubscriptionManager::class, fn (): SubscriptionManager => new SubscriptionManager(app(SubscriptionService::class)));
        $this->app->singleton(UsagePolicyService::class, fn (): UsagePolicyService => new UsagePolicyService(app(FeatureResolver::class)));
        $this->app->singleton(ProductEngineOperationsMonitor::class, fn (): ProductEngineOperationsMonitor => new ProductEngineOperationsMonitor);
        $this->app->singleton(ProductIntegrityChecker::class, fn (): ProductIntegrityChecker => new ProductIntegrityChecker);

        $this->app->singleton(TwilioClient::class, function (): TwilioClient {
            $sid = config('services.twilio.sid');
            $apiKey = config('services.twilio.api_key');
            $apiSecret = config('services.twilio.api_secret');
            $token = config('services.twilio.token');

            \Illuminate\Support\Facades\Log::info('Twilio Initialization', [
                'sid' => $sid,
                'has_api_key' => ! empty($apiKey),
                'has_api_secret' => ! empty($apiSecret),
                'token_preview' => $token ? substr((string) $token, 0, 4).'...'.substr((string) $token, -4) : 'NONE',
            ]);

            if ($apiKey && $apiSecret) {
                return new TwilioClient((string) $apiKey, (string) $apiSecret, (string) $sid);
            }

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
        Event::listen(ProductEngineEvent::class, LogProductEngineEvent::class);
        Event::listen(MigrationsEnded::class, fn (): ProductIntegrityChecker => tap(app(ProductIntegrityChecker::class), fn (ProductIntegrityChecker $checker) => $checker->reportAll()));

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->is_system_admin ? true : null;
        });

        \Illuminate\Support\Facades\Gate::define('viewPulse', function ($user) {
            return $user->is_system_admin === true;
        });

        RateLimiter::for('rsvp_show', fn () => Limit::perMinute(60));
        RateLimiter::for('rsvp_submit', fn () => Limit::perMinute(10));
        RateLimiter::for('webhooks', fn () => Limit::perMinute(120));

        if (app()->environment('production')) {
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
