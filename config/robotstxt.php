<?php

declare(strict_types=1);

/**
 * robots.txt configuration for Kalfa RSVP + Seating SaaS.
 *
 * Environment-specific: production allows public event/RSVP pages;
 * staging/local/testing disallow all crawling.
 *
 * @see https://github.com/daikazu/robotstxt
 */
return [
    'environments' => [
        'production' => [
            'content_signals_policy' => [
                'enabled' => true,
                'custom_policy' => null,
            ],
            'content_signals' => [
                'search' => true,
                'ai_input' => false,
                'ai_train' => false,
            ],
            'paths' => [
                '*' => [
                    'disallow' => [
                        '/api',
                        '/dashboard',
                        '/system',
                        '/checkout',
                        '/organizations',
                        '/organization',
                        '/billing',
                        '/profile',
                        '/login',
                        '/register',
                        '/reset-password',
                        '/verify-email',
                        '/sanctum',
                        '/livewire',
                        '/storage',
                        '/officeguy',
                        '/_boost',
                    ],
                    'allow' => [
                        '/',
                        '/event',
                        '/rsvp',
                    ],
                ],
            ],
            'sitemaps' => [
                'sitemap.xml',
            ],
            'host' => env('APP_URL'),
            'custom_text' => null,
        ],

        'staging' => [
            'paths' => [
                '*' => [
                    'disallow' => ['/'],
                ],
            ],
            'sitemaps' => [],
        ],

        'local' => [
            'paths' => [
                '*' => [
                    'disallow' => ['/'],
                ],
            ],
            'sitemaps' => [],
        ],

        'testing' => [
            'paths' => [
                '*' => [
                    'disallow' => ['/'],
                ],
            ],
            'sitemaps' => [],
        ],
    ],
];
