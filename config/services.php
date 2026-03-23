<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'api_key' => env('TWILIO_API_KEY'),
        'api_secret' => env('TWILIO_API_SECRET'),
        'token' => env('TWILIO_AUTH_TOKEN_LIVE'), // Environment variable cleaned of system overrides
        'number' => env('TWILIO_NUMBER'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'), // e.g. +14155238886 for Sandbox, or same as TWILIO_NUMBER if WhatsApp-enabled
        'verify_sid' => env('TWILIO_VERIFY_SID'),
        'log_level' => env('TWILIO_LOG_LEVEL', 'debug'),
        'rsvp_node_ws_url' => env('RSVP_NODE_WS_URL', 'wss://voice-bridge.kalfa.me/media'),
        'call_log_secret' => env('CALL_LOG_SECRET', ''),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_LIVE_MODEL', 'models/gemini-2.0-flash-exp'),
    ],

];
