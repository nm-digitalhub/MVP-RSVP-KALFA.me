<?php

use App\Http\Controllers\CheckoutTokenizeController;
use App\Http\Controllers\TwilioController;
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
Route::controller(\App\Http\Controllers\WebAuthn\WebAuthnLoginController::class)
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:webauthn')
    ->group(function () {
        Route::post('webauthn/login/options', 'options')->name('webauthn.login.options');
        Route::post('webauthn/login', 'login')->name('webauthn.login');
    });

// Attestation (register) — auth required + rate-limited
Route::controller(\App\Http\Controllers\WebAuthn\WebAuthnRegisterController::class)
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
        Route::get('/calling', [\App\Http\Controllers\Twilio\CallingController::class, 'index'])->name('twilio.calling.index');
        Route::post('/calling/initiate', [\App\Http\Controllers\Twilio\CallingController::class, 'call'])->name('twilio.calling.initiate');
        Route::get('/calling/logs', [\App\Http\Controllers\Twilio\CallingController::class, 'getLogs'])->name('twilio.calling.logs');
    });

    // Public webhooks/callbacks from Twilio
    Route::post('/calling/status', [\App\Http\Controllers\Twilio\CallingController::class, 'statusCallback'])->name('twilio.calling.status');
    Route::match(['get', 'post'], '/rsvp/connect', [\App\Http\Controllers\Twilio\RsvpVoiceController::class, 'connect'])->name('twilio.rsvp.connect');
    Route::post('/rsvp/response', [\App\Http\Controllers\Twilio\RsvpVoiceController::class, 'digitResponse'])->name('twilio.rsvp.digit_response');
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
    Route::middleware('ensure.organization')->group(function () {
        Route::get('/organization/settings', [\App\Http\Controllers\Dashboard\OrganizationSettingsController::class, 'edit'])->name('dashboard.organization-settings.edit');
        Route::put('/organization/settings', [\App\Http\Controllers\Dashboard\OrganizationSettingsController::class, 'update'])->name('dashboard.organization-settings.update');
    });
    Route::get('/profile', fn () => view('profile'))->name('profile');
    Route::post('/organizations/switch/{organization}', [\App\Http\Controllers\OrganizationSwitchController::class, '__invoke'])
        ->name('organizations.switch')
        ->scopeBindings();

    // Organization-scoped routes (require active organization)
    Route::middleware('ensure.organization')->group(function () {
        Route::livewire('team', \App\Livewire\Dashboard\OrganizationMembers::class)->name('dashboard.team');
        Route::get('dashboard/events', [\App\Http\Controllers\Dashboard\DashboardController::class, 'index'])->name('dashboard.events.index');
        Route::get('dashboard/events/create', [\App\Http\Controllers\Dashboard\EventController::class, 'create'])->name('dashboard.events.create');
        Route::post('dashboard/events', [\App\Http\Controllers\Dashboard\EventController::class, 'store'])->name('dashboard.events.store');
        Route::get('dashboard/events/{event}', [\App\Http\Controllers\Dashboard\EventController::class, 'show'])
            ->name('dashboard.events.show')
            ->scopeBindings();
        Route::get('dashboard/events/{event}/edit', [\App\Http\Controllers\Dashboard\EventController::class, 'edit'])
            ->name('dashboard.events.edit')
            ->scopeBindings();
        Route::put('dashboard/events/{event}', [\App\Http\Controllers\Dashboard\EventController::class, 'update'])
            ->name('dashboard.events.update')
            ->scopeBindings();
        Route::delete('dashboard/events/{event}', [\App\Http\Controllers\Dashboard\EventController::class, 'destroy'])
            ->name('dashboard.events.destroy')
            ->scopeBindings();
        Route::get('dashboard/events/{event}/guests', [\App\Http\Controllers\Dashboard\EventGuestsController::class, 'index'])
            ->name('dashboard.events.guests.index')
            ->scopeBindings();
        Route::get('dashboard/events/{event}/tables', [\App\Http\Controllers\Dashboard\EventTablesController::class, 'index'])
            ->name('dashboard.events.tables.index')
            ->scopeBindings();
        Route::get('dashboard/events/{event}/invitations', [\App\Http\Controllers\Dashboard\EventInvitationsController::class, 'index'])
            ->name('dashboard.events.invitations.index')
            ->scopeBindings();
        Route::get('dashboard/events/{event}/seat-assignments', [\App\Http\Controllers\Dashboard\EventSeatAssignmentsController::class, 'index'])
            ->name('dashboard.events.seat-assignments.index')
            ->scopeBindings();

        // Billing & Entitlements (tenant, org-scoped)
        Route::get('billing', fn () => view('pages.billing.account'))->name('billing.account');
        Route::get('billing/entitlements', fn () => view('pages.billing.entitlements'))->name('billing.entitlements');
        Route::get('billing/usage', fn () => view('pages.billing.usage'))->name('billing.usage');
        Route::get('billing/intents', fn () => view('pages.billing.intents'))->name('billing.intents');
    });
});

// System Admin (global authority; no tenant middleware)
Route::prefix('system')
    ->middleware(['auth', 'verified', 'system.admin'])
    ->group(function () {
        Route::livewire('/dashboard', \App\Livewire\System\Dashboard::class)->name('system.dashboard');
        Route::livewire('/settings', \App\Livewire\System\Settings\Index::class)->name('system.settings.index');
        Route::livewire('/organizations', \App\Livewire\System\Organizations\Index::class)->name('system.organizations.index');
        Route::livewire('/organizations/{organization}', \App\Livewire\System\Organizations\Show::class)->name('system.organizations.show')->scopeBindings();
        Route::livewire('/users', \App\Livewire\System\Users\Index::class)->name('system.users.index');
        Route::livewire('/users/{user}', \App\Livewire\System\Users\Show::class)->name('system.users.show')->scopeBindings();
        Route::livewire('/accounts', \App\Livewire\System\Accounts\Index::class)->name('system.accounts.index');
        Route::livewire('/accounts/create', \App\Livewire\System\Accounts\CreateAccountWizard::class)->name('system.accounts.create');
        Route::livewire('/accounts/{account}', \App\Livewire\System\Accounts\Show::class)->name('system.accounts.show')->scopeBindings();
        Route::post('/accounts/{account}/payment-methods', [\App\Http\Controllers\System\AccountPaymentMethodController::class, 'store'])
            ->name('system.accounts.payment-methods.store')
            ->scopeBindings();
        Route::post('/accounts/{account}/payment-methods/{paymentMethod}/default', [\App\Http\Controllers\System\AccountPaymentMethodController::class, 'setDefault'])
            ->name('system.accounts.payment-methods.default')
            ->scopeBindings();
        Route::delete('/accounts/{account}/payment-methods/{paymentMethod}', [\App\Http\Controllers\System\AccountPaymentMethodController::class, 'destroy'])
            ->name('system.accounts.payment-methods.destroy')
            ->scopeBindings();

        Route::livewire('/products', \App\Livewire\System\Products\Index::class)->name('system.products.index');
        Route::livewire('/products/create', \App\Livewire\System\Products\CreateProductWizard::class)->name('system.products.create');
        Route::livewire('/products/{product}', \App\Livewire\System\Products\Show::class)->name('system.products.show')->scopeBindings();

        Route::post('/impersonate/{organization}', [\App\Http\Controllers\System\SystemImpersonationController::class, '__invoke'])
            ->name('system.impersonate')
            ->scopeBindings();
        Route::post('/impersonation/exit', \App\Http\Controllers\System\SystemImpersonationExitController::class)
            ->name('system.impersonation.exit');
    });

// Public event page (no auth)
Route::get('event/{slug}', [\App\Http\Controllers\PublicEventController::class, 'show'])
    ->name('event.show');

// Public RSVP page (no auth)
Route::livewire('invitations/{token}', \App\Livewire\AcceptInvitation::class)->name('invitations.accept');
Route::get('rsvp/{slug}', [\App\Http\Controllers\PublicRsvpViewController::class, 'show'])
    ->name('rsvp.show');
Route::post('rsvp/{slug}/responses', [\App\Http\Controllers\PublicRsvpViewController::class, 'store'])
    ->name('rsvp.responses.store');

// Checkout payment status (auth required)
Route::get('checkout/status/{payment}', [\App\Http\Controllers\CheckoutStatusController::class, 'show'])
    ->middleware(['auth'])
    ->name('checkout.status');

// Authentication
require __DIR__.'/auth.php';

// Legal pages (stub — replace with real content views when ready)
Route::get('terms', fn () => view('legal.terms'))->name('terms');
Route::get('privacy', fn () => view('legal.privacy'))->name('privacy');
Route::get('refund-policy', fn () => view('legal.refund-policy'))->name('refund.policy');
