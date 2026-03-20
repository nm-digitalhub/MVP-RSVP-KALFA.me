<?php

declare(strict_types=1);

namespace App\Http\Controllers\Twilio;

use App\Enums\InvitationStatus;
use App\Enums\RsvpResponseType;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\RsvpResponse;
use App\Support\Feature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Twilio\Rest\Client as TwilioClient;

final class RsvpVoiceController extends Controller
{
    public function __construct(
        private readonly TwilioClient $twilio
    ) {}

    /**
     * TwiML endpoint for Twilio Media Stream connection.
     * Builds TwiML in PHP only (< 64KB). No Blade view to avoid layout/debug output.
     */
    public function connect(Request $request): Response
    {
        try {
            $guestId = (int) $request->input('guest_id', 0);
            $eventId = (int) $request->input('event_id', 0);
            $invitationId = (int) $request->input('invitation_id', 0);

            $guest = Guest::find($guestId);
            $event = Event::find($eventId);
            $invitation = Invitation::find($invitationId);

            if (! $guest || ! $event || ! $invitation || $invitation->guest_id !== $guestId || $invitation->event_id !== $eventId) {
                return $this->twimlResponse($this->errorTwiML());
            }

            $wsUrl = config('services.twilio.rsvp_node_ws_url', 'wss://node.kalfa.me/media');
            $eventDate = $event->event_date
                ? Str::limit($event->event_date->locale('he')->translatedFormat('j בF Y'), 80)
                : '';
            $guestName = Str::limit($guest->name ?? '', 200);
            $eventName = Str::limit($event->name ?? '', 200);
            $venueName = Str::limit($event->venue_name ?? '', 200);

            $settings = $event->settings ?? [];
            $venueAddress = Str::limit($settings['venue_address'] ?? '', 200);
            $description = Str::limit($settings['description'] ?? '', 500);
            $program = Str::limit($settings['program'] ?? '', 500);
            $customQuestions = json_encode($settings['custom'] ?? [], JSON_UNESCAPED_UNICODE);

            // Fetch seating info
            $seating = $guest->seatAssignment()->with('eventTable')->first();
            $seatingInfo = $seating
                ? 'שולחן: '.($seating->eventTable->name ?? 'כללי').($seating->seat_number ? ', כיסא: '.$seating->seat_number : '')
                : 'טרם נקבע';

            $twiml = $this->buildConnectTwiML(
                $wsUrl,
                $guest->id,
                $event->id,
                $invitation->id,
                $guestName,
                $eventName,
                $eventDate,
                $venueName,
                $venueAddress,
                $description,
                $program,
                $customQuestions,
                $seatingInfo
            );

            return $this->twimlResponse($twiml);
        } catch (\Throwable $e) {
            Log::warning('Twilio connect TwiML failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return $this->twimlResponse($this->errorTwiML());
        }
    }

    private function buildConnectTwiML(
        string $wsUrl,
        int $guestId,
        int $eventId,
        int $invitationId,
        string $guestName,
        string $eventName,
        string $eventDate,
        string $venueName,
        string $venueAddress = '',
        string $description = '',
        string $program = '',
        string $customQuestions = '[]',
        string $seatingInfo = 'טרם נקבע'
    ): string {
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        // Voice-to-voice: invite speech answer; no "press 1 or 2" (Stream connects to Gemini which understands speech).
        $guestPart = "שלום {$guestName}. ";
        $eventPart = $eventName !== '' ? "זה אישור הגעה לאירוע {$eventName}. " : 'זה אישור הגעה לאירוע. ';
        $invitePart = 'תגיד בבקשה אם אתה מגיע וכמה אנשים.';
        // SSML: slight slowdown and short breaks for clearer Hebrew pronunciation.
        $greetingSsml = '<prosody rate="92%">'.$e($guestPart).'</prosody>'
            .'<break time="300ms"/>'
            .'<prosody rate="92%">'.$e($eventPart).$e($invitePart).'</prosody>';

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<Response>'."\n"
            .'  <Say language="he-IL" voice="Google.he-IL-Standard-A">'.$greetingSsml.'</Say>'."\n"
            .'  <Connect>'."\n"
            .'    <Stream url="'.$e($wsUrl).'">'."\n"
            .'      <Parameter name="guest_id" value="'.$guestId.'"/>'."\n"
            .'      <Parameter name="event_id" value="'.$eventId.'"/>'."\n"
            .'      <Parameter name="invitation_id" value="'.$invitationId.'"/>'."\n"
            .'      <Parameter name="guest_name" value="'.$e($guestName).'"/>'."\n"
            .'      <Parameter name="event_name" value="'.$e($eventName).'"/>'."\n"
            .'      <Parameter name="event_date" value="'.$e($eventDate).'"/>'."\n"
            .'      <Parameter name="event_venue" value="'.$e($venueName).'"/>'."\n"
            .'      <Parameter name="event_address" value="'.$e($venueAddress).'"/>'."\n"
            .'      <Parameter name="event_description" value="'.$e($description).'"/>'."\n"
            .'      <Parameter name="event_program" value="'.$e($program).'"/>'."\n"
            .'      <Parameter name="event_custom" value="'.$e($customQuestions).'"/>'."\n"
            .'      <Parameter name="guest_seating" value="'.$e($seatingInfo).'"/>'."\n"
            .'    </Stream>'."\n"
            .'  </Connect>'."\n"
            .'</Response>';
    }

    private function twimlResponse(string $twiml): Response
    {
        return response($twiml)
            ->header('Content-Type', 'text/xml; charset=UTF-8')
            ->header('Content-Length', (string) strlen($twiml));
    }

    /**
     * Process RSVP result from Node.js server (Gemini Live voice flow).
     * Expects JSON: guest_id, invitation_id, intent (yes|no), number_of_guests.
     */
    public function process(Request $request): JsonResponse
    {
        $guestId = (int) $request->input('guest_id', 0);
        $invitationId = (int) $request->input('invitation_id', 0);
        $intent = $request->input('intent');
        $numberOfGuests = $request->integer('number_of_guests', 0);
        $notes = $request->input('notes', '');

        if ($guestId < 1 || $invitationId < 1 || ! is_string($intent)) {
            return response()->json(['error' => 'Missing or invalid guest_id, invitation_id, or intent'], 422);
        }

        $invitation = Invitation::with(['guest', 'event'])->find($invitationId);
        if (! $invitation || $invitation->guest_id !== $guestId) {
            return response()->json(['error' => 'Invitation not found or guest mismatch'], 404);
        }

        $guest = $invitation->guest;
        $event = $invitation->event;
        if (! $guest || ! $event) {
            return response()->json(['error' => 'Guest or event missing'], 404);
        }

        $responseType = match (strtolower($intent)) {
            'yes' => RsvpResponseType::Yes,
            'no' => RsvpResponseType::No,
            default => null,
        };
        if (! $responseType) {
            return response()->json(['error' => 'Invalid intent; use yes or no'], 422);
        }

        $attendeesCount = $responseType === RsvpResponseType::Yes
            ? max(0, $numberOfGuests)
            : 0;
        if ($attendeesCount === 0 && $responseType === RsvpResponseType::Yes) {
            $attendeesCount = 1;
        }

        try {
            DB::transaction(function () use ($guest, $invitation, $event, $responseType, $attendeesCount, $notes, $request): void {
                RsvpResponse::updateOrCreate(
                    ['guest_id' => $guest->id, 'invitation_id' => $invitation->id],
                    [
                        'response' => $responseType,
                        'attendees_count' => $attendeesCount,
                        'message' => $notes ?: 'Twilio voice RSVP (Gemini Live)',
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent() ?? 'Node-Gemini',
                    ]
                );

                $invitation->update([
                    'status' => InvitationStatus::Responded,
                    'responded_at' => now(),
                ]);

                \App\Events\RsvpReceived::dispatch($invitation);

                // Log resource usage
                $account = $event->organization->account;
                if ($account) {
                    $account->featureUsage()->updateOrCreate(
                        ['feature_key' => 'voice_rsvp_calls', 'period_key' => now()->format('Ym')],
                        ['usage_count' => \Illuminate\Support\Facades\DB::raw('usage_count + 1')]
                    );
                }
            });

            if ($responseType === RsvpResponseType::Yes) {
                $this->sendSmsConfirmation($guest, $event, $responseType);
            }

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('RsvpVoiceController::process failed', [
                'guest_id' => $guestId,
                'invitation_id' => $invitationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Handle DTMF (digit press) RSVP response.
     */
    public function digitResponse(Request $request): Response
    {
        $guestId = (int) $request->input('guest_id', 0);
        $eventId = (int) $request->input('event_id', 0);
        $invitationId = (int) $request->input('invitation_id', 0);
        $digits = $request->input('Digits');

        $guest = Guest::find($guestId);
        $event = Event::find($eventId);
        $invitation = Invitation::find($invitationId);

        if (! $guest || ! $event || ! $invitation || $digits === null || $digits === '') {
            return response($this->errorTwiML())->header('Content-Type', 'text/xml');
        }

        $responseType = match ($digits) {
            '1' => RsvpResponseType::Yes,
            '2' => RsvpResponseType::No,
            default => null,
        };

        if (! $responseType) {
            return response('<?xml version="1.0" encoding="UTF-8"?><Response><Say language="he-IL" voice="Google.he-IL-Standard-A">בחירה לא תקינה. להתראות.</Say></Response>')->header('Content-Type', 'text/xml');
        }

        DB::transaction(function () use ($guest, $invitation, $responseType) {
            RsvpResponse::updateOrCreate(
                ['guest_id' => $guest->id, 'invitation_id' => $invitation->id],
                [
                    'response' => $responseType,
                    'attendees_count' => $responseType === RsvpResponseType::Yes ? 1 : 0,
                    'message' => 'Twilio voice RSVP (DTMF)',
                    'ip' => $request->ip(),
                    'user_agent' => 'Twilio-Voice',
                ]
            );

            $invitation->update([
                'status' => InvitationStatus::Responded,
                'responded_at' => now(),
            ]);
        });

        $this->sendSmsConfirmation($guest, $event, $responseType);

        $confirmMsg = match ($responseType) {
            RsvpResponseType::Yes => 'תודה רבה! אישור ההגעה שלך נקלט בהצלחה. נתראה באירוע!',
            RsvpResponseType::No => 'תודה על העדכון. מקווים לראותך באירוע הבא. להתראות.',
        };

        $twiml = '<?xml version="1.0" encoding="UTF-8"?><Response><Say language="he-IL" voice="Google.he-IL-Standard-A">'.htmlspecialchars($confirmMsg).'</Say></Response>';

        return response($twiml)->header('Content-Type', 'text/xml');
    }

    private function sendSmsConfirmation(Guest $guest, ?Event $event, RsvpResponseType $responseType): void
    {
        if ($responseType !== RsvpResponseType::Yes || ! $guest->phone || ! $event) {
            return;
        }

        $account = $event->organization->account;
        if (! $account) {
            return;
        }

        $twilioEnabled = Feature::enabled($account, 'twilio_enabled');
        $smsEnabled = Feature::enabled($account, 'sms_confirmation_enabled');

        if (! $twilioEnabled || ! $smsEnabled) {
            return;
        }

        $limit = Feature::integer($account, 'sms_confirmation_limit');

        if ($limit !== null) {
            $usage = $account->featureUsage()
                ->where('feature_key', 'sms_confirmation_messages')
                ->where('period_key', now()->format('Ym'))
                ->sum('usage_count');

            if ($usage >= $limit) {
                return;
            }
        }

        try {
            $sms = "אישור ההגעה התקבל בהצלחה!\n".
                   "אירוע: {$event->name}\n".
                   'תאריך: '.$event->event_date?->format('d/m/Y');

            $params = ['body' => $sms];
            $messagingServiceSid = config('services.twilio.messaging_service_sid');

            if ($messagingServiceSid) {
                $params['messagingServiceSid'] = $messagingServiceSid;
            } else {
                $params['from'] = config('services.twilio.number');
            }

            $this->twilio->messages->create($guest->phone, $params);

            $usage = $account->featureUsage()->firstOrCreate(
                [
                    'feature_key' => 'sms_confirmation_messages',
                    'period_key' => (int) now()->format('Ym'),
                ],
                [
                    'usage_count' => 0,
                ]
            );

            $usage->increment('usage_count');
        } catch (\Throwable $e) {
            Log::error('Twilio SMS confirmation failed', ['error' => $e->getMessage()]);
        }
    }

    private function errorTwiML(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Response><Say language="he-IL" voice="Google.he-IL-Standard-A">אירעה שגיאה. להתראות.</Say></Response>';
    }
}
