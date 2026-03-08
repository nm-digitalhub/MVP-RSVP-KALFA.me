<?php

/**
 * Twilio webhook — handles the digit press from the RSVP call.
 * Creates an RsvpResponse record in the database.
 */

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use App\Enums\InvitationStatus;
use App\Enums\RsvpResponseType;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\RsvpResponse;

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$guestId = (int) ($_GET['guest_id'] ?? 0);
$eventId = (int) ($_GET['event_id'] ?? 0);
$invitationId = (int) ($_GET['invitation_id'] ?? 0);
$digits = $_POST['Digits'] ?? null;

$guest = Guest::find($guestId);
$event = Event::find($eventId);
$invitation = $invitationId > 0 ? Invitation::find($invitationId) : null;

header('Content-Type: text/xml; charset=utf-8');

if (! $guest || ! $event || ! $invitation || $digits === null || $digits === '') {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say language="he-IL">אירעה שגיאה. להתראות.</Say></Response>';
    exit;
}

if ($invitation->guest_id !== $guestId || $invitation->event_id !== $eventId) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say language="he-IL">אירעה שגיאה. להתראות.</Say></Response>';
    exit;
}

$responseType = match ($digits) {
    '1' => RsvpResponseType::Yes,
    '2' => RsvpResponseType::No,
    default => null,
};

if (! $responseType) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say language="he-IL">בחירה לא תקינה. להתראות.</Say></Response>';
    exit;
}

RsvpResponse::updateOrCreate(
    [
        'guest_id' => $guest->id,
        'invitation_id' => $invitation->id,
    ],
    [
        'response' => $responseType,
        'attendees_count' => $responseType === RsvpResponseType::Yes ? 1 : 0,
        'message' => 'Twilio voice RSVP',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => 'Twilio-Voice',
    ]
);

$invitation->update([
    'status' => InvitationStatus::Responded,
    'responded_at' => now(),
]);

// If the guest said Yes, send an SMS with event details
if ($responseType === RsvpResponseType::Yes && $guest->phone) {
    try {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.number');

        $client = new \Twilio\Rest\Client($sid, $token);

        $eventDate = $event->event_date->format('d/m/Y');
        $venue = $event->venue_name;
        $address = $event->settings['venue_address'] ?? '';

        $smsBody = "אישור ההגעה התקבל בהצלחה! איזה כיף!\n";
        $smsBody .= "נשמח לראותך באירוע: {$event->name}\n";
        $smsBody .= "בתאריך: {$eventDate}\n";
        if ($venue) {
            $smsBody .= "מקום: {$venue}\n";
        }
        if ($address) {
            $smsBody .= "כתובת: {$address}\n";
        }
        $smsBody .= 'להתראות!';

        $client->messages->create(
            $guest->phone,
            [
                'from' => $from,
                'body' => $smsBody,
            ]
        );
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Twilio SMS Confirmation failed: '.$e->getMessage());
    }
}

$confirmMsg = match ($responseType) {
    RsvpResponseType::Yes => 'תודה רבה! אישור ההגעה שלך נקלט בהצלחה. נתראה באירוע!',
    RsvpResponseType::No => 'תודה על העדכון. מקווים לראותך באירוע הבא. להתראות.',
};
$confirmMsgEscaped = htmlspecialchars($confirmMsg, ENT_XML1 | ENT_QUOTES, 'UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
    <Say language="he-IL" voice="Google.he-IL-Standard-A"><?= $confirmMsgEscaped ?></Say>
</Response>
