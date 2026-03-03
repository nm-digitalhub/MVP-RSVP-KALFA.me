<?php

use App\Http\Controllers\CheckoutTokenizeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
    Route::get('/profile', fn () => view('profile'))->name('profile');
    Route::post('/organizations/switch/{organization}', [\App\Http\Controllers\OrganizationSwitchController::class, '__invoke'])
        ->name('organizations.switch')
        ->scopeBindings();

    // Organization-scoped routes (require active organization)
    Route::middleware('ensure.organization')->group(function () {
        Route::get('dashboard/events', [\App\Http\Controllers\Dashboard\DashboardController::class, 'index'])->name('dashboard.events.index');
        Route::get('dashboard/events/{event}', [\App\Http\Controllers\Dashboard\EventController::class, 'show'])
            ->name('dashboard.events.show')
            ->scopeBindings();
    });
});

// System Admin (global authority; no tenant middleware)
Route::prefix('system')
    ->middleware(['auth', 'verified', 'system.admin'])
    ->group(function () {
        Route::get('/dashboard', \App\Livewire\System\Dashboard::class)->name('system.dashboard');
        Route::get('/organizations', \App\Livewire\System\Organizations\Index::class)->name('system.organizations.index');
        Route::get('/organizations/{organization}', \App\Livewire\System\Organizations\Show::class)->name('system.organizations.show')->scopeBindings();
        Route::get('/users', \App\Livewire\System\Users\Index::class)->name('system.users.index');
        Route::get('/users/{user}', \App\Livewire\System\Users\Show::class)->name('system.users.show')->scopeBindings();
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
