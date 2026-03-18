---
date: 2026-03-16
tags: [architecture, service, events, calendar, navigation]
status: active
---

# EventLinks

> Related: [[Architecture/EventLifecycle|Event Lifecycle]] · [[Architecture/Services/Notifications|Notifications]]

Generates contextual links for events: Google Calendar "Add to calendar" URLs and navigation links (Google Maps, Waze) based on venue data.

---

## Class

`App\Services\EventLinks`

---

## Methods

### `addToCalendarUrl(Event $event): ?string`

Returns a **Google Calendar** deep-link URL for an all-day event.

Returns `null` if `event->event_date` is not set.

**Implementation:**
```php
Link::createAllDay($event->name, $event->event_date, 1)  // spatie/calendar-links
    ->address($navigationQuery)
    ->google()
```

The address is resolved via `navigationQuery()` — if no venue data exists, the link is returned without an address.

---

### `navigationLinks(Event $event): array`

Returns an array of navigation provider links (Maps, Waze, etc.) based on the event venue.

Returns `[]` if `navigationQuery()` is empty (no venue/address configured).

**Return shape:**
```php
[
    ['url' => string, 'label_key' => string, 'id?' => string],
    // one entry per configured navigation provider
]
```

Providers are configured in `config/events.php` under `navigation`:

```php
// config/events.php
'navigation' => [
    [
        'id'         => 'google-maps',
        'url'        => 'https://maps.google.com/?q={query}',
        'label_key'  => 'Navigate with Google Maps',
        'utm_source' => 'kalfa',
    ],
    [
        'id'         => 'waze',
        'url'        => 'https://waze.com/ul?q={query}',
        'label_key'  => 'Navigate with Waze',
    ],
],
```

`{query}` is replaced with `rawurlencode(navigationQuery())`. If `utm_source` is set, it is appended as a query parameter.

---

### `navigationQuery(Event $event): string`

Resolves the best venue string for maps/calendar:

1. `event->settings['venue_address']` (full formatted address from event form) — preferred
2. `event->venue_name` — fallback
3. Empty string if neither is set

---

## Dependency

Uses [`spatie/calendar-links`](https://github.com/spatie/calendar-links) for Google Calendar URL generation.

---

## Usage in Views / Controllers

```php
$links = app(EventLinks::class);

$calendarUrl = $links->addToCalendarUrl($event);  // null if no date
$navLinks    = $links->navigationLinks($event);    // [] if no venue
```

Typically called in event public page (`PublicEventController`) and invitation emails.
