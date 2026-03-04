<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use Spatie\CalendarLinks\Link;

class EventLinks
{
    /**
     * Google Calendar "Add to calendar" URL for an all-day event.
     * Returns null when the event has no date.
     */
    public function addToCalendarUrl(Event $event): ?string
    {
        if (! $event->event_date) {
            return null;
        }

        $link = Link::createAllDay(
            $event->name,
            $event->event_date,
            1
        );

        $address = $this->navigationQuery($event);
        if ($address !== '') {
            $link->address($address);
        }

        return $link->google();
    }

    /**
     * Navigation links (e.g. Google Maps, Waze) from config.
     * Each entry has 'url', 'label_key', and optionally 'id'.
     * Returns empty array when the event has no venue/address for the query.
     *
     * @return array<int, array{url: string, label_key: string, id?: string}>
     */
    /**
     * Single query string for maps/calendar: venue address from form if set, else venue name.
     */
    public function navigationQuery(Event $event): string
    {
        $address = trim((string) ($event->settings['venue_address'] ?? ''));
        if ($address !== '') {
            return $address;
        }

        return trim((string) ($event->venue_name ?? ''));
    }

    public function navigationLinks(Event $event): array
    {
        $query = $this->navigationQuery($event);
        if ($query === '') {
            return [];
        }

        $encoded = rawurlencode($query);
        $providers = config('events.navigation', []);
        $links = [];

        foreach ($providers as $provider) {
            $url = str_replace('{query}', $encoded, (string) ($provider['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            if (isset($provider['utm_source']) && $provider['utm_source'] !== '') {
                $url .= (str_contains($url, '?') ? '&' : '?')
                    .'utm_source='.rawurlencode((string) $provider['utm_source']);
            }
            $entry = [
                'url' => $url,
                'label_key' => (string) ($provider['label_key'] ?? 'Navigate to event'),
            ];
            if (isset($provider['id'])) {
                $entry['id'] = (string) $provider['id'];
            }
            $links[] = $entry;
        }

        return $links;
    }
}
