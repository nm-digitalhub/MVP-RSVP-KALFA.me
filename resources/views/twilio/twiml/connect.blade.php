<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Connect>
        <Stream url="{{ $wsUrl }}">
            <Parameter name="guest_id" value="{{ $guest->id }}" />
            <Parameter name="event_id" value="{{ $event->id }}" />
            <Parameter name="invitation_id" value="{{ $invitation->id }}" />
            <Parameter name="guest_name" value="{{ $guestName }}" />
            <Parameter name="event_name" value="{{ $eventName }}" />
            <Parameter name="event_date" value="{{ $eventDate }}" />
            <Parameter name="event_venue" value="{{ $venueName }}" />
        </Stream>
    </Connect>
</Response>
