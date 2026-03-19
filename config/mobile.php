<?php

return [

    'api' => [
        'base_url' => rtrim((string) env('VITE_MOBILE_API_BASE_URL', 'https://kalfa.me'), '/'),
        'endpoints' => [
            'login' => '/api/mobile/auth/login',
            'logout' => '/api/mobile/auth/logout',
            'logout_others' => '/api/mobile/auth/logout/others',
            'bootstrap' => '/api/bootstrap',
        ],
    ],

    'bootstrap' => [
        'endpoint' => '/api/bootstrap',
        'payload' => [
            'user',
            'current_organization',
            'memberships',
            'abilities',
            'flags',
            'server_time',
        ],
    ],

    'secure_storage' => [
        'access_token_key' => 'kalfa.mobile.access_token',
    ],

    'cache' => [
        'mode' => 'read-only',
        'entities' => [
            'user' => [
                'source' => 'bootstrap.user',
                'scope' => 'singleton',
                'strategy' => 'replace',
            ],
            'organizations' => [
                'source' => 'bootstrap.memberships',
                'scope' => 'membership-scoped',
                'strategy' => 'replace',
            ],
            'events' => [
                'source' => 'api.organizations.events.index',
                'scope' => 'organization-scoped',
                'strategy' => 'replace',
            ],
            'guests' => [
                'source' => 'api.organizations.events.guests.index',
                'scope' => 'event-scoped',
                'strategy' => 'replace',
            ],
            'invitations' => [
                'source' => 'api.organizations.events.invitations.index',
                'scope' => 'event-scoped',
                'strategy' => 'replace',
            ],
        ],
        'remote_only' => [
            'event_tables',
            'seat_assignments',
            'payments',
            'checkout',
            'organization_settings',
            'billing',
            'webauthn',
            'twilio',
        ],
    ],

    'refresh' => [
        'ttl' => [
            'bootstrap_ttl_seconds' => 300,
            'organizations_ttl_seconds' => 300,
            'events_ttl_seconds' => 300,
            'guests_ttl_seconds' => 120,
            'invitations_ttl_seconds' => 120,
        ],
        'triggers' => [
            'app_launch' => true,
            'foreground_resume' => true,
            'manual_refresh' => true,
        ],
        'stale_behavior' => [
            'serve_stale_on_failure' => true,
            'mark_stale_after_ttl' => true,
            'background_refresh_on_stale' => true,
        ],
        'strategy' => [
            'comparison' => 'updated_at',
            'write_mode' => 'replace-only',
        ],
    ],

    'offline' => [
        'mutations_enabled' => false,
        'outbox_enabled' => false,
    ],
];
