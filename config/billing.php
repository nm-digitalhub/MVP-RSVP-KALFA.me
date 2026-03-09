<?php

return [
    'default_gateway' => env('BILLING_GATEWAY', 'stub'),

    /*
    |--------------------------------------------------------------------------
    | SUMIT redirect URLs
    |--------------------------------------------------------------------------
    | Legacy one-time payments still use redirect mode through the OfficeGuy
    | package, while subscriptions use saved payment methods and recurring
    | charges. These URLs must stay absolute when redirect mode is enabled.
    */
    'sumit' => [
        'redirect_success_url' => env('BILLING_SUMIT_SUCCESS_URL'),
        'redirect_cancel_url' => env('BILLING_SUMIT_CANCEL_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook signature secret (SUMIT and other gateways)
    |--------------------------------------------------------------------------
    | When set, incoming webhooks for the configured gateway must include
    | X-Webhook-Signature: HMAC-SHA256(json_encode(payload), secret).
    */
    'webhook_secret' => env('BILLING_WEBHOOK_SECRET'),
];
