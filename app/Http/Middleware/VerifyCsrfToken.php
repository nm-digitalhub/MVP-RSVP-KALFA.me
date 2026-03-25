<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

class VerifyCsrfToken extends PreventRequestForgery
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'officeguy/webhook/*',
        'mvp-rsvp/webhook/*',
        'twilio/*',
        'mobile/session',
        'mobile/session/*',
    ];
}
