<?php

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventTableController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\GuestImportController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PublicRsvpController;
use App\Http\Controllers\Api\SeatAssignmentController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RSVP + Seating API (MVP Phase 1)
|--------------------------------------------------------------------------
| Multi-tenant via organization_id. Auth required except public RSVP.
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::patch('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');

    Route::get('organizations/{organization}/events', [EventController::class, 'index'])->name('events.index');
    Route::post('organizations/{organization}/events', [EventController::class, 'store'])->name('events.store');
    Route::get('organizations/{organization}/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::put('organizations/{organization}/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::patch('organizations/{organization}/events/{event}', [EventController::class, 'update'])->name('events.patch');
    Route::delete('organizations/{organization}/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    Route::get('organizations/{organization}/events/{event}/guests', [GuestController::class, 'index'])->name('guests.index');
    Route::post('organizations/{organization}/events/{event}/guests', [GuestController::class, 'store'])->name('guests.store');
    Route::post('organizations/{organization}/events/{event}/guests/import', [GuestImportController::class, '__invoke'])->name('guests.import')->scopeBindings();
    Route::get('organizations/{organization}/events/{event}/guests/{guest}', [GuestController::class, 'show'])->name('guests.show');
    Route::put('organizations/{organization}/events/{event}/guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
    Route::patch('organizations/{organization}/events/{event}/guests/{guest}', [GuestController::class, 'update'])->name('guests.patch');
    Route::delete('organizations/{organization}/events/{event}/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');

    Route::get('organizations/{organization}/events/{event}/event-tables', [EventTableController::class, 'index'])->name('event-tables.index');
    Route::post('organizations/{organization}/events/{event}/event-tables', [EventTableController::class, 'store'])->name('event-tables.store');
    Route::get('organizations/{organization}/events/{event}/event-tables/{eventTable}', [EventTableController::class, 'show'])->name('event-tables.show');
    Route::put('organizations/{organization}/events/{event}/event-tables/{eventTable}', [EventTableController::class, 'update'])->name('event-tables.update');
    Route::patch('organizations/{organization}/events/{event}/event-tables/{eventTable}', [EventTableController::class, 'update'])->name('event-tables.patch');
    Route::delete('organizations/{organization}/events/{event}/event-tables/{eventTable}', [EventTableController::class, 'destroy'])->name('event-tables.destroy');

    Route::get('organizations/{organization}/events/{event}/seat-assignments', [SeatAssignmentController::class, 'index'])->name('seat-assignments.index');
    Route::put('organizations/{organization}/events/{event}/seat-assignments', [SeatAssignmentController::class, 'update'])->name('seat-assignments.update');

    Route::get('organizations/{organization}/events/{event}/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('organizations/{organization}/events/{event}/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::post('organizations/{organization}/events/{event}/invitations/{invitation}/send', [InvitationController::class, 'send'])->name('invitations.send');

    Route::post('organizations/{organization}/events/{event}/checkout', [CheckoutController::class, 'initiate'])
        ->name('checkout.initiate')
        ->scopeBindings();

    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
});

Route::middleware('throttle:rsvp_show')->get('rsvp/{slug}', [PublicRsvpController::class, 'showBySlug'])->name('api.rsvp.show');
Route::middleware('throttle:rsvp_submit')->post('rsvp/{slug}/responses', [PublicRsvpController::class, 'storeResponse'])->name('api.rsvp.responses.store');

Route::get('webhooks/{gateway}', fn (string $gateway) => response()->json([
    'error' => 'Method Not Allowed',
    'message' => 'This endpoint accepts POST only.',
], 405))->name('webhooks.get');

Route::post('webhooks/{gateway}', [WebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('webhooks.handle')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
