<?php

return [
    'default_gateway' => env('BILLING_GATEWAY', 'stub'),

    /*
    |--------------------------------------------------------------------------
    | SUMIT redirect URLs (required when BILLING_GATEWAY=sumit)
    |--------------------------------------------------------------------------
    | Where to send the user after payment success/cancel. API-only: set to
    | your frontend or a thank-you page. Must be absolute URLs.
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
