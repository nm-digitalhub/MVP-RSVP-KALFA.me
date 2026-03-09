<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Models\Guest;
use App\Models\Invitation;
use App\Support\Feature;
use Illuminate\Support\Str;
use Twilio\Rest\Client as TwilioClient;

class CallingService
{
    public function __construct(
        private readonly TwilioClient $twilio
    ) {}

    /**
     * Find a guest by phone suffix across upcoming events.
     * Optimized to use SQL instead of PHP loops.
     */
    public function findGuestByPhone(string $normalizedPhone): ?Guest
    {
        $phoneSuffix = substr(preg_replace('/\D/', '', $normalizedPhone), -9);

        return Guest::whereHas('event', function ($query) {
            $query->where('event_date', '>=', now()->startOfDay());
        })
            ->where('phone', 'like', '%'.$phoneSuffix)
            ->first();
    }

    /**
     * Normalize phone to E.164 format.
     */
    public function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $len = strlen($digits);

        if ($len === 10 && str_starts_with($digits, '0')) {
            return '+972'.substr($digits, 1);
        }
        if ($len === 9 && str_starts_with($digits, '5')) {
            return '+972'.$digits;
        }
        if ($len >= 11 && $len <= 12 && str_starts_with($digits, '972')) {
            return '+'.$digits;
        }

        return str_starts_with($phone, '+') ? '+'.$digits : '+'.$digits;
    }

    /**
     * Get or create invitation for a guest.
     */
    public function ensureInvitation(Guest $guest): Invitation
    {
        return $guest->invitation ?: Invitation::create([
            'event_id' => $guest->event_id,
            'guest_id' => $guest->id,
            'token' => Str::random(32),
            'slug' => Str::random(10),
            'status' => InvitationStatus::Sent,
        ]);
    }

    /**
     * Ensure the account associated with the guest's event has permission to call.
     */
    protected function ensureAccountCanCall(Guest $guest): void
    {
        $organization = $guest->event->organization;
        $account = $organization->account;

        if (! $account) {
            throw new \RuntimeException(__('No billing account attached to this organization. Calling is disabled.'));
        }

        if (! Feature::enabled($account, 'voice_rsvp_enabled')) {
            throw new \RuntimeException(__('AI Voice RSVP is not enabled for this account.'));
        }

        $limit = Feature::integer($account, 'voice_rsvp_limit');

        if ($limit !== null) {
            $usage = $account->featureUsage()
                ->where('feature_key', 'voice_rsvp_calls')
                ->where('period_key', now()->format('Ym'))
                ->sum('usage_count');

            if ($usage >= $limit) {
                throw new \RuntimeException(__('Monthly AI Voice RSVP call limit reached.'));
            }
        }
    }

    /**
     * Initiate the Twilio call.
     * Guest phone is normalized to E.164 so Twilio accepts it (e.g. 0532743588 → +972532743588).
     */
    public function initiateCall(Guest $guest, Invitation $invitation): string
    {
        $this->ensureAccountCanCall($guest);

        $toE164 = $this->normalizePhoneNumber($guest->phone);
        if (! preg_match('/^\+[1-9]\d{8,14}$/', $toE164)) {
            throw new \InvalidArgumentException('Invalid guest phone for Twilio: '.$guest->phone);
        }

        $twimlUrl = route('twilio.rsvp.connect', [
            'guest_id' => $guest->id,
            'event_id' => $guest->event_id,
            'invitation_id' => $invitation->id,
        ]);

        $statusCallbackUrl = route('twilio.calling.status', ['invitation_id' => $invitation->id]);

        $call = $this->twilio->calls->create(
            $toE164,
            config('services.twilio.number'),
            [
                'url' => $twimlUrl,
                'statusCallback' => $statusCallbackUrl,
                'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
            ]
        );

        return $call->sid;
    }
}
