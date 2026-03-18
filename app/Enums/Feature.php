<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Canonical registry of feature keys used across the product engine.
 *
 * These values must match the `feature_key` strings stored in
 * `product_entitlements` and `account_entitlements` tables.
 *
 * Usage:
 *   // Gate (controller / Livewire)
 *   Gate::authorize('feature', Feature::TwilioEnabled->value);
 *   Gate::allows('feature', Feature::VoiceRsvpCalls->value);
 *
 *   // Blade
 *
 *   @can('feature', \App\Enums\Feature::TwilioEnabled->value) … @endcan
 *
 *   // Feature facade (direct FeatureResolver)
 *   Feature::enabled($account, \App\Enums\Feature::TwilioEnabled->value);
 *
 *   // Route middleware
 *   Route::middleware('ensure.feature:' . Feature::TwilioEnabled->value)
 */
enum Feature: string
{
    // ── Voice / Calling ─────────────────────────────────────────────────────
    /** Master switch: Twilio calling and SMS features are available. */
    case TwilioEnabled = 'twilio_enabled';

    /** Account may initiate outbound AI-powered voice RSVP calls. */
    case VoiceRsvpCalls = 'voice_rsvp_calls';

    // ── SMS ──────────────────────────────────────────────────────────────────
    /** Post-RSVP SMS confirmation messages may be sent. */
    case SmsConfirmationEnabled = 'sms_confirmation_enabled';

    /** Maximum number of SMS confirmation messages per billing period. */
    case SmsConfirmationLimit = 'sms_confirmation_limit';

    /** Usage metric key: counts SMS confirmation messages sent. */
    case SmsConfirmationMessages = 'sms_confirmation_messages';

    // ── Events ───────────────────────────────────────────────────────────────
    /** Account may create new events. */
    case CreateEvent = 'create_event';

    /** Maximum number of active events the account may have at one time. */
    case MaxActiveEvents = 'max_active_events';

    // ── Guests ───────────────────────────────────────────────────────────────
    /** Maximum number of guests per event. */
    case MaxGuestsPerEvent = 'max_guests_per_event';

    /** Account may bulk-import guests from CSV/Excel. */
    case GuestImport = 'guest_import';

    // ── Seating ──────────────────────────────────────────────────────────────
    /** Account may use the seating/tables assignment feature. */
    case SeatingManagement = 'seating_management';

    // ── Invitations ──────────────────────────────────────────────────────────
    /** Account may send RSVP invitation messages (WhatsApp / SMS). */
    case InvitationSending = 'invitation_sending';
}
