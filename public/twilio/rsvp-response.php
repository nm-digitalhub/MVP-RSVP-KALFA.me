<?php

/**
 * Twilio webhook — handles the digit press from the RSVP call.
 * Creates an RsvpResponse record in the database.
 */

require __DIR__.'/../../vendor/autoload.php';

use App\Enums\RsvpResponseType;
use App\Models\Guest;
use App\Models\RsvpResponse;

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$guestId = (int) ($_GET['guest_id'] ?? 0);
$eventId = (int) ($_GET['event_id'] ?? 0);
$invitationId = (int) ($_GET['invitation_id'] ?? 0);
$digits = $_POST['Digits'] ?? null;

$guest = Guest::find($guestId);
$event = \App\Models\Event::find($eventId);

header('Content-Type: text/xml; charset=utf-8');

if (! $guest || ! $event || ! $invitationId || ! $digits) {
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

// Create or update the RSVP response for this guest using the invitation_id
RsvpResponse::updateOrCreate(
    [
        'guest_id' => $guestId,
        'invitation_id' => $invitationId,
    ],
    [
        'response' => $responseType,
        'attendees_count' => $responseType === RsvpResponseType::Yes ? 1 : 0,
        'message' => 'Twilio voice RSVP',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => 'Twilio-Voice',
    ]
);

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

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
    <Say language="he-IL" voice="Google.he-IL-Standard-A"><?= $confirmMsg ?></Say>
</Response>
