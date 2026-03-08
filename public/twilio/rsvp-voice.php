<?php

/**
 * Twilio TwiML endpoint
 * Called when the call connects.
 * Opens Media Stream to Node server.
 *
 * Twilio does NOT support query params on <Stream url>. Custom data is passed
 * via <Parameter>; the Node WebSocket server receives them in the "Start" message.
 */

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$guestId = (int) ($_GET['guest_id'] ?? 0);
$eventId = (int) ($_GET['event_id'] ?? 0);
$invitationId = (int) ($_GET['invitation_id'] ?? 0);

$guest = Guest::find($guestId);
$event = Event::find($eventId);
$invitation = $invitationId > 0 ? Invitation::find($invitationId) : null;

header('Content-Type: text/xml; charset=utf-8');

if (! $guest || ! $event || ! $invitation) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say language="he-IL">אירעה שגיאה. להתראות.</Say></Response>';
    exit;
}

if ($invitation->guest_id !== (int) $guestId || $invitation->event_id !== (int) $eventId) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say language="he-IL">אירעה שגיאה. להתראות.</Say></Response>';
    exit;
}

$nodeWsUrl = config('services.twilio.rsvp_node_ws_url', 'wss://node.kalfa.me/media');
$escapedUrl = htmlspecialchars($nodeWsUrl, ENT_XML1, 'UTF-8');

$eventName = $event->name ?? '';
$eventDateFormatted = $event->event_date
    ? $event->event_date->locale('he')->translatedFormat('j בF Y')
    : '';
$eventVenue = $event->venue_name ?? '';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
    <Connect>
        <Stream url="<?= $escapedUrl ?>">
            <Parameter name="guest_id" value="<?= (int) $guest->id ?>" />
            <Parameter name="event_id" value="<?= (int) $event->id ?>" />
            <Parameter name="invitation_id" value="<?= (int) $invitation->id ?>" />
            <Parameter name="guest_name" value="<?= htmlspecialchars($guest->name, ENT_XML1, 'UTF-8') ?>" />
            <Parameter name="event_name" value="<?= htmlspecialchars($eventName, ENT_XML1, 'UTF-8') ?>" />
            <Parameter name="event_date" value="<?= htmlspecialchars($eventDateFormatted, ENT_XML1, 'UTF-8') ?>" />
            <Parameter name="event_venue" value="<?= htmlspecialchars($eventVenue, ENT_XML1, 'UTF-8') ?>" />
        </Stream>
    </Connect>
</Response>
