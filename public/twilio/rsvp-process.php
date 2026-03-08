<?php

/**
 * Receives RSVP result from Node server (Gemini tool call)
 *
 * Expects JSON payload via POST:
 * {
 *   "guest_id": 123,
 *   "invitation_id": 456,
 *   "intent": "yes" | "no" | "maybe",
 *   "number_of_guests": 2
 * }
 */

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use App\Enums\RsvpResponseType;
use App\Models\Event;
use App\Models\Guest;
use App\Models\RsvpResponse;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];

$guestId      = (int) ($data['guest_id'] ?? 0);
$invitationId = (int) ($data['invitation_id'] ?? 0);
$intent       = (string) ($data['intent'] ?? 'unknown');
$guests       = (int) ($data['number_of_guests'] ?? 0);

$guest = $guestId > 0 ? Guest::find($guestId) : null;
$event = $guest?->event_id ? Event::find($guest->event_id) : null;

if (! $guest || $invitationId <= 0) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'missing']);
    exit;
}

$responseType = match ($intent) {
    'yes' => RsvpResponseType::Yes,
    'no'  => RsvpResponseType::No,
    default => RsvpResponseType::Maybe,
};

RsvpResponse::updateOrCreate(
    [
        'guest_id'      => $guestId,
        'invitation_id' => $invitationId,
    ],
    [
        'response'        => $responseType,
        'attendees_count' => $guests,
        'message'         => 'Gemini Voice RSVP',
        'ip'              => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'      => 'Gemini-Live',
    ]
);

Log::info('RSVP saved', [
    'guest'  => $guestId,
    'intent' => $intent,
    'guests' => $guests,
]);

// Optional SMS confirmation when RSVP is "yes"
if ($responseType === RsvpResponseType::Yes && $guest->phone && $event) {
    try {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from  = config('services.twilio.number');

        if ($sid && $token && $from) {
            $client = new \Twilio\Rest\Client($sid, $token);

            $sms = "אישור ההגעה התקבל בהצלחה!\n".
                   "אירוע: {$event->name}\n".
                   'תאריך: '.$event->event_date?->format('d/m/Y');

            $client->messages->create(
                $guest->phone,
                [
                    'from' => $from,
                    'body' => $sms,
                ]
            );
        }
    } catch (\Throwable $e) {
        Log::error('Twilio SMS failed', [
            'error' => $e->getMessage(),
        ]);
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok']);
