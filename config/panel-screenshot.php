<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Settle time before capture (Browsershot setDelay)
    |--------------------------------------------------------------------------
    |
    | Extra milliseconds after load before taking a full-page screenshot.
    | Helps Livewire / fonts settle. See also config/laravel-screenshot.php.
    |
    */
    'wait_ms' => (int) env('PANEL_SCREENSHOT_WAIT_MS', 800),

    /*
    |--------------------------------------------------------------------------
    | Output directory (under public/)
    |--------------------------------------------------------------------------
    */
    'public_subdir' => env('PANEL_SCREENSHOT_OUTPUT_DIR', 'system-products/capture-browsershot'),

    /*
    |--------------------------------------------------------------------------
    | Default URL paths (relative to APP_URL) when --url is omitted
    |--------------------------------------------------------------------------
    |
    | Public paths work without auth. With `panel:capture-screenshots --auth`,
    | the command logs in via the web login form (Kernel sub-requests) and passes
    | session cookies to Browsershot (see Spatie customizing-browsershot: useCookies).
    |
    | System routes (/system/*) require a system admin user. Tenant routes may need
    | an active organization in session — prefer Playwright (scripts/script.cjs) for
    | complex multi-step session setup.
    |
    */
    'default_paths' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PANEL_SCREENSHOT_DEFAULT_PATHS', '/login,/terms,/privacy'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Optional HTTP Basic (Browsershot authenticate())
    |--------------------------------------------------------------------------
    |
    | For staging behind nginx basic auth. Matches Spatie docs for setExtraHttpHeaders
    | / Browsershot::authenticate().
    |
    */
    'http_basic_user' => env('PANEL_SCREENSHOT_HTTP_BASIC_USER'),
    'http_basic_password' => env('PANEL_SCREENSHOT_HTTP_BASIC_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Web login credentials for --auth (password session only)
    |--------------------------------------------------------------------------
    |
    | Falls back to CAPTURE_* used by scripts/script.cjs when unset.
    |
    */
    'login_email' => env('PANEL_SCREENSHOT_LOGIN_EMAIL', env('CAPTURE_EMAIL')),
    'login_password' => env('PANEL_SCREENSHOT_LOGIN_PASSWORD', env('CAPTURE_PASSWORD')),

    /*
    |--------------------------------------------------------------------------
    | Full capture set (--all --auth)
    |--------------------------------------------------------------------------
    |
    | Paths are defined in App\Support\PanelScreenshotAuthPaths. Override the
    | entire list: PANEL_SCREENSHOT_AUTH_ALL_PATHS=/a,/b
    | IDs for parameterized routes (same as scripts/script.cjs):
    | PANEL_SCREENSHOT_SYSTEM_ORG_ID, _ACCOUNT_ID, _USER_ID, PANEL_SCREENSHOT_PRODUCT_ID,
    | PANEL_SCREENSHOT_EVENT_ID (or CAPTURE_* equivalents where noted in code).
    |
    */

];
