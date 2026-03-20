<?php

namespace App\Providers;

use App\Contracts\BillingProvider;
use App\Contracts\PaymentGatewayInterface;
use App\Events\Billing\SubscriptionCancelled;
use App\Events\Billing\TrialExtended;
use App\Events\ProductEngineEvent;
use App\Listeners\Billing\AuditBillingEvent;
use App\Listeners\LogProductEngineEvent;
use App\Listeners\StoreWebAuthnCredentialInSession;
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
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laragear\WebAuthn\Events\CredentialAsserted;
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

            Log::info('Twilio Initialization', [
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
        if (! $this->isIsolatedMobileShellRequest()) {
            $this->configureScramble();
        }

        Event::listen(ProductEngineEvent::class, LogProductEngineEvent::class);
        Event::listen(CredentialAsserted::class, StoreWebAuthnCredentialInSession::class);
        // Do not run integrity checks during NativePHP deferred init (migrate --force fires
        // MigrationsEnded against local SQLite; ProductIntegrityChecker is a server-side concern).
        Event::listen(MigrationsEnded::class, function (): void {
            if (config('nativephp-internal.running', false)) {
                return;
            }
            tap(app(ProductIntegrityChecker::class), fn (ProductIntegrityChecker $checker) => $checker->reportAll());
        });

        // Billing domain events → audit log
        Event::listen(SubscriptionCancelled::class, AuditBillingEvent::class);
        Event::listen(TrialExtended::class, AuditBillingEvent::class);

        Gate::before(function ($user, $ability) {
            if (! $user->is_system_admin) {
                return null; // Normal users — let policies decide
            }

            // System-level Spatie permissions: always granted for system admins
            $systemAbilities = [
                'manage-system',
                'manage-organizations',
                'manage-users',
                'impersonate-users',
                'viewPulse',
                'viewTelescope',
            ];

            if (in_array($ability, $systemAbilities, true)) {
                return true;
            }

            // Tenant-scoped abilities: require active impersonation session
            if (session()->has('impersonation.original_organization_id')) {
                return true;
            }

            // System admin without impersonation on a tenant ability: let policy decide
            // Policy will likely deny because admin may not be an org member
            return null;
        });

        Gate::define('viewPulse', function ($user) {
            return $user->is_system_admin === true;
        });

        RateLimiter::for('rsvp_show', fn () => Limit::perMinute(60));
        RateLimiter::for('rsvp_submit', fn () => Limit::perMinute(10));
        RateLimiter::for('webhooks', fn () => Limit::perMinute(120));
        RateLimiter::for('webauthn', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('mobile_session', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('mobile_auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        if (app()->environment('production') && ! $this->isIsolatedMobileShellRequest()) {
            $this->validateSumitConfig();
        }
    }

    protected function isIsolatedMobileShellRequest(): bool
    {
        // NativePHP mobile runtime — always isolated (Artisan commands run in console context).
        if (config('nativephp-internal.running', false)) {
            return true;
        }

        if ($this->app->runningInConsole() || ! $this->app->bound('request')) {
            return false;
        }

        /** @var Request $request */
        $request = $this->app->make('request');

        return $request->is('mobile') || $request->is('mobile/session') || $request->is('mobile/session/*');
    }

    /**
     * Configure Scramble API documentation.
     * - Restricts routes to only API controllers (excludes Twilio, Telescope, Pulse, Webhook internal routes)
     * - Adds Bearer token security scheme (Sanctum)
     * - Resolves tags from controller class names for clean grouping
     */
    private function configureScramble(): void
    {
        Scramble::configure()
            ->routes(function (Route $route) {
                $uri = $route->uri();
                // Include only /api/* routes, excluding internal/vendor routes
                if (! str_starts_with($uri, 'api/')) {
                    return false;
                }
                // Exclude Twilio integration routes (secured via secret key, not Sanctum)
                if (str_starts_with($uri, 'api/twilio/')) {
                    return false;
                }

                return true;
            })
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });

        // Resolve tags from controller class name segments
        Scramble::resolveTagsUsing(function ($routeInfo) {
            $action = $routeInfo->route->getAction('controller');
            if (! $action) {
                return ['General'];
            }

            $controller = is_string($action) ? explode('@', $action)[0] : (is_array($action) ? $action[0] : '');
            $parts = explode('\\', $controller);
            $className = end($parts);
            // Remove "Controller" suffix
            $tag = str_replace('Controller', '', (string) $className);
            // Convert CamelCase to spaced: EventTable → Event Tables
            $tag = (string) preg_replace('/([a-z])([A-Z])/', '$1 $2', $tag);

            return [$tag ?: 'General'];
        });
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
