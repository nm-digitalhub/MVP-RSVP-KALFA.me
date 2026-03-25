<?php

use App\Http\Controllers\BillingSubscriptionCheckoutController;
use App\Http\Controllers\CheckoutStatusController;
use App\Http\Controllers\CheckoutTokenizeController;
use App\Http\Controllers\Dashboard\EventController;
use App\Http\Controllers\Dashboard\EventGuestsController;
use App\Http\Controllers\Dashboard\EventInvitationsController;
use App\Http\Controllers\Dashboard\EventSeatAssignmentsController;
use App\Http\Controllers\Dashboard\EventTablesController;
use App\Http\Controllers\Dashboard\OrganizationSettingsController;
use App\Http\Controllers\Mobile\MobileSecureStorageSessionController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\PublicEventController;
use App\Http\Controllers\PublicRsvpViewController;
use App\Http\Controllers\System\AccountPaymentMethodController;
use App\Http\Controllers\System\SystemImpersonationController;
use App\Http\Controllers\System\SystemImpersonationExitController;
use App\Http\Controllers\Twilio\CallingController;
use App\Http\Controllers\Twilio\RsvpVoiceController;
use App\Http\Controllers\TwilioController;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
use App\Livewire\AcceptInvitation;
use App\Livewire\Billing\PlanSelection;
use App\Livewire\Dashboard\OrganizationMembers;
use App\Livewire\System\Accounts\CreateAccountWizard;
use App\Livewire\System\Dashboard;
use App\Livewire\System\Organizations\Show;
use App\Livewire\System\Products\CreateProductWizard;
use App\Livewire\System\Settings\Index;
use App\Livewire\System\TrialReminders;
use App\Livewire\Dashboard\EventList;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WebAuthn / Passkey Routes
|--------------------------------------------------------------------------
| Login routes: no auth required (user is unauthenticated during assertion).
| Register routes: auth required — only logged-in users may add credentials.
*/

// Assertion (login) — unauthenticated, rate-limited
Route::controller(WebAuthnLoginController::class)
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:webauthn')
    ->group(function () {
        Route::post('webauthn/login/options', 'options')->name('webauthn.login.options');
        Route::post('webauthn/login', 'login')->name('webauthn.login');
    });

// Attestation (register) — auth required + rate-limited
Route::controller(WebAuthnRegisterController::class)
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware(['auth', 'throttle:webauthn'])
    ->group(function () {
        Route::post('webauthn/register/options', 'options')->name('webauthn.register.options');
        Route::post('webauthn/register', 'register')->name('webauthn.register');
    });

/*
|--------------------------------------------------------------------------
| Twilio & Calling Routes
|--------------------------------------------------------------------------
*/
Route::prefix('twilio')->group(function () {
    Route::middleware(['auth', 'verified', 'ensure.organization'])->group(function () {
        Route::get('/calling', [CallingController::class, 'index'])->name('twilio.calling.index');
        Route::post('/calling/initiate', [CallingController::class, 'call'])->name('twilio.calling.initiate');
        Route::get('/calling/logs', [CallingController::class, 'getLogs'])->name('twilio.calling.logs');
    });

    // Public webhooks/callbacks from Twilio
    Route::post('/calling/status', [CallingController::class, 'statusCallback'])->name('twilio.calling.status');
    Route::match(['get', 'post'], '/rsvp/connect', [RsvpVoiceController::class, 'connect'])->name('twilio.rsvp.connect');
    Route::post('/rsvp/response', [RsvpVoiceController::class, 'digitResponse'])->name('twilio.rsvp.digit_response');
});

Route::match(['get', 'post'], '/mvp-rsvp/webhook/callcomes', [TwilioController::class, 'callComes']);

/*
|--------------------------------------------------------------------------
| Web Routes — Event SaaS only
|--------------------------------------------------------------------------
| Auth, Dashboard, Organizations, Events, Public Event, RSVP, Checkout.
*/

// Home: redirect to dashboard if authenticated, else login
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::view('/mobile', 'mobile.shell')->name('mobile.entry');

Route::controller(MobileSecureStorageSessionController::class)->prefix('mobile/session')->middleware(['throttle:mobile_session'])->group(function () {
    Route::get('/', 'show')->name('mobile.session.status');
    Route::put('/', 'store')->name('mobile.session.store');
    Route::delete('/', 'destroy')->name('mobile.session.destroy');
});

// Checkout tokenization (event payment)
Route::middleware(['auth'])->group(function () {
    Route::get('checkout/{organization}/{event}', CheckoutTokenizeController::class)
        ->name('checkout.tokenize')
        ->scopeBindings();
});

// Authenticated: dashboard + organization management + profile (clear separation)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => view('pages.dashboard'))->name('dashboard');
    Route::get('/organizations', fn () => view('pages.organizations.index'))->name('organizations.index');
    Route::get('/organizations/create', fn () => view('pages.organizations.create'))->name('organizations.create');
    Route::get('/select-plan', PlanSelection::class)->name('select-plan');
    Route::get('/billing/checkout/{plan}', BillingSubscriptionCheckoutController::class)->name('billing.checkout');
    Route::middleware('ensure.organization')->group(function () {
        Route::get('/organization/settings', [OrganizationSettingsController::class, 'edit'])->name('dashboard.organization-settings.edit');
        Route::put('/organization/settings', [OrganizationSettingsController::class, 'update'])->name('dashboard.organization-settings.update');
    });
    Route::get('/profile', fn () => view('profile'))->name('profile');
    Route::post('/organizations/switch/{organization}', [OrganizationSwitchController::class, '__invoke'])
        ->name('organizations.switch')
        ->scopeBindings();

    // Organization-scoped routes (require active organization)
    Route::middleware('ensure.organization')->group(function () {
        // Billing & Entitlements pages (no billing check required - users need to see their billing status)
        Route::get('billing', fn () => view('pages.billing.account'))->name('billing.account');
        Route::get('billing/entitlements', fn () => view('pages.billing.entitlements'))->name('billing.entitlements');
        Route::get('billing/usage', fn () => view('pages.billing.usage'))->name('billing.usage');
        Route::get('billing/intents', fn () => view('pages.billing.intents'))->name('billing.intents');

        // Protected routes require active billing (product, subscription, or trial)
        Route::middleware('ensure.account_active')->group(function () {
            Route::livewire('team', OrganizationMembers::class)->name('dashboard.team');
            Route::livewire('dashboard/events', EventList::class)->name('dashboard.events.index');
            Route::get('dashboard/events/create', [EventController::class, 'create'])->name('dashboard.events.create');
            Route::post('dashboard/events', [EventController::class, 'store'])->name('dashboard.events.store');
            Route::get('dashboard/events/{event}', [EventController::class, 'show'])
                ->name('dashboard.events.show')
                ->scopeBindings();
            Route::get('dashboard/events/{event}/edit', [EventController::class, 'edit'])
                ->name('dashboard.events.edit')
                ->scopeBindings();
            Route::put('dashboard/events/{event}', [EventController::class, 'update'])
                ->name('dashboard.events.update')
                ->scopeBindings();
            Route::delete('dashboard/events/{event}', [EventController::class, 'destroy'])
                ->name('dashboard.events.destroy')
                ->scopeBindings();
            Route::get('dashboard/events/{event}/guests', [EventGuestsController::class, 'index'])
                ->name('dashboard.events.guests.index')
                ->scopeBindings();
            Route::get('dashboard/events/{event}/tables', [EventTablesController::class, 'index'])
                ->name('dashboard.events.tables.index')
                ->scopeBindings();
            Route::get('dashboard/events/{event}/invitations', [EventInvitationsController::class, 'index'])
                ->name('dashboard.events.invitations.index')
                ->scopeBindings();
            Route::get('dashboard/events/{event}/seat-assignments', [EventSeatAssignmentsController::class, 'index'])
                ->name('dashboard.events.seat-assignments.index')
                ->scopeBindings();
        });
    });
});

// System Admin (global authority; no tenant middleware)
Route::prefix('system')
    ->middleware(['auth', 'verified', 'system.admin'])
    ->group(function () {
        Route::livewire('/dashboard', Dashboard::class)->name('system.dashboard');
        Route::livewire('/settings', Index::class)->name('system.settings.index');
        Route::livewire('/organizations', App\Livewire\System\Organizations\Index::class)->name('system.organizations.index');
        Route::livewire('/organizations/{organization}', Show::class)->name('system.organizations.show')->scopeBindings();
        Route::livewire('/users', App\Livewire\System\Users\Index::class)->name('system.users.index');
        Route::livewire('/users/{user}', App\Livewire\System\Users\Show::class)->name('system.users.show')->scopeBindings();
        Route::livewire('/accounts', App\Livewire\System\Accounts\Index::class)->name('system.accounts.index');
        Route::livewire('/accounts/create', CreateAccountWizard::class)->name('system.accounts.create');
        Route::livewire('/accounts/{account}', App\Livewire\System\Accounts\Show::class)->name('system.accounts.show')->scopeBindings();
        Route::post('/accounts/{account}/payment-methods', [AccountPaymentMethodController::class, 'store'])
            ->name('system.accounts.payment-methods.store')
            ->scopeBindings();
        Route::post('/accounts/{account}/payment-methods/{paymentMethod}/default', [AccountPaymentMethodController::class, 'setDefault'])
            ->name('system.accounts.payment-methods.default')
            ->scopeBindings();
        Route::delete('/accounts/{account}/payment-methods/{paymentMethod}', [AccountPaymentMethodController::class, 'destroy'])
            ->name('system.accounts.payment-methods.destroy')
            ->scopeBindings();

        Route::livewire('/products', App\Livewire\System\Products\Index::class)->name('system.products.index');
        Route::livewire('/products/create', CreateProductWizard::class)->name('system.products.create');
        Route::livewire('/products/{product}', App\Livewire\System\Products\Show::class)->name('system.products.show')->scopeBindings();

        // Trial Management
        Route::livewire('/trial-reminders', TrialReminders::class)->name('system.trial-reminders');

        Route::post('/impersonate/{organization}', [SystemImpersonationController::class, '__invoke'])
            ->name('system.impersonate')
            ->scopeBindings();
        Route::post('/impersonation/exit', SystemImpersonationExitController::class)
            ->name('system.impersonation.exit');
    });

// Public event page (no auth)
Route::get('event/{slug}', [PublicEventController::class, 'show'])
    ->name('event.show');

// Public RSVP page (no auth)
Route::livewire('invitations/{token}', AcceptInvitation::class)->name('invitations.accept');
Route::get('rsvp/{slug}', [PublicRsvpViewController::class, 'show'])
    ->name('rsvp.show');
Route::post('rsvp/{slug}/responses', [PublicRsvpViewController::class, 'store'])
    ->name('rsvp.responses.store');

// Checkout payment status (auth required)
Route::get('checkout/status/{payment}', [CheckoutStatusController::class, 'show'])
    ->middleware(['auth'])
    ->name('checkout.status');

// Authentication
require __DIR__.'/auth.php';

// Legal pages (stub — replace with real content views when ready)
Route::get('terms', fn () => view('legal.terms'))->name('terms');
Route::get('privacy', fn () => view('legal.privacy'))->name('privacy');
Route::get('refund-policy', fn () => view('legal.refund-policy'))->name('refund.policy');
