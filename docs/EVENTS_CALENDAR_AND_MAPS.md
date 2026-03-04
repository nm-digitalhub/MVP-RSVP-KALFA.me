# Event links: Calendar & maps (no hardcode)

## Packages

### Calendar – "Add to calendar"

**Package:** [spatie/calendar-links](https://github.com/spatie/calendar-links)  
**Packagist:** https://packagist.org/packages/spatie/calendar-links

- Generates links for **Google Calendar**, **Yahoo**, **Outlook (web)**, **Office 365**, and **ICS** (iCal/Apple/Outlook).
- No API keys; only builds URLs.
- Supports all-day events (`Link::createAllDay`) and timed events, description, address.
- PHP 8.1+ (1.x), 8.3+ (2.x). This project uses 1.x for PHP 8.2 compatibility.

**Usage (in app):**

```php
use Spatie\CalendarLinks\Link;

$link = Link::createAllDay($event->name, $event->event_date, 1)
    ->address($event->venue_name ?? '');

$link->google();   // Google Calendar
$link->yahoo();    // Yahoo
$link->webOutlook(); // outlook.live.com
$link->webOffice();  // outlook.office.com
$link->ics();      // data URI for .ics download
```

### Maps / navigation – Google Maps, Waze

There is **no standard PHP package** that generates "open in maps" / "navigate" links. We use **configurable URL templates** in `config/events.php` (no hardcode in views).

**Official documentation:**

- **Waze Deep Links:** [How to use Waze Deep Links](https://developers.google.com/waze/api/)  
  Base URL: `https://waze.com/ul`. Search by address: `?q=search_terms` (URL-encoded). To start navigation: `&navigate=yes`. Optional `utm_source` for [partner support](https://support.google.com/waze/partners/answer/7422931).
- **Google Maps:** [Maps URLs](https://developers.google.com/maps/documentation/urls) — search: `https://www.google.com/maps/search/?api=1&query={query}`

Config key `events.navigation` is an array of providers; each has `id`, `label_key` (translation key), `url` with `{query}` placeholder, and optional `utm_source` (e.g. for Waze). When building the link, replace `{query}` with the URL-encoded venue/address and, if `utm_source` is set, append `&utm_source=<value>` to the URL.

## Configuration

See `config/events.php`:

- **Calendar:** The app uses Spatie to build links; no calendar URL in config. Provider choice (Google vs Yahoo vs Outlook) can be driven by config or UI later (e.g. "Add to Google" / "Add to Outlook").
- **Maps:** `events.links.navigation` – list of providers, each with `label` and `url` template. Placeholder: `{query}` (venue name or address, URL-encoded by the app).

Example:

```php
'navigation' => [
    ['id' => 'google_maps', 'label_key' => 'Navigate with Google Maps', 'url' => 'https://www.google.com/maps/search/?api=1&query={query}'],
    ['id' => 'waze', 'label_key' => 'Navigate with Waze', 'url' => 'https://waze.com/ul?q={query}'],
],
```

Views and `EventLinks` service use this config so changing or adding a provider does not require code changes.
