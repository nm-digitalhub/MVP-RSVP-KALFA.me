<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | RSVP (public invitation page)
    |--------------------------------------------------------------------------
    |
    | Date format for the numeric date line (per locale). Fallback used when
    | locale not in list. Default welcome message is a translation key; override
    | per event via event settings 'rsvp_welcome_message'.
    |
    */
    'rsvp' => [
        'numeric_date_format' => [
            'he' => 'd.m.Y',
            'en' => 'Y-m-d',
        ],
        'default_welcome_message_key' => "We'd love to see you among our guests.",
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation links (no hardcoded URLs in views)
    |--------------------------------------------------------------------------
    |
    | Each provider has 'url' with placeholder {query} (venue/address, URL-encoded
    | by the app). Optional 'utm_source' appended for Waze partner support.
    | See: https://developers.google.com/waze/api/
    |      https://support.google.com/waze/partners/answer/7422931
    |
    */
    'navigation' => [
        [
            'id' => 'google_maps',
            'label_key' => 'Navigate with Google Maps',
            'url' => 'https://www.google.com/maps/search/?api=1&query={query}',
        ],
        [
            'id' => 'waze',
            'label_key' => 'Navigate with Waze',
            'url' => 'https://waze.com/ul?q={query}',
            'utm_source' => 'kalfa_rsvp',
        ],
    ],

];
