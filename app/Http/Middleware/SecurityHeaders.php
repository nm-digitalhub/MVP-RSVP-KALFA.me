<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    /**
     *_domains that are allowed to load resources from.
     */
    private const ALLOWED_SOURCES = "'self'";

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Only add security headers to HTML responses (skip JSON/API)
        // and only on successful responses (not errors/redirects)
        if ($this->shouldAddSecurityHeaders($request, $response)) {
            $this->setSecurityHeaders($response);
        }

        return $response;
    }

    /**
     * Determine if security headers should be added.
     */
    private function shouldAddSecurityHeaders(Request $request, Response $response): bool
    {
        // Skip for JSON/API responses
        if ($request->expectsJson() || $request->isJson()) {
            return false;
        }

        // Skip for non-successful responses (errors, redirects)
        if (! $response->isSuccessful()) {
            return false;
        }

        // Skip if content type is not HTML
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'application/xhtml+xml')) {
            return false;
        }

        return true;
    }

    /**
     * Set security headers on the response.
     */
    private function setSecurityHeaders(Response $response): void
    {
        // Content-Security-Policy - Controls which resources the browser can load
        $response->headers->set('Content-Security-Policy', $this->getContentSecurityPolicy());

        // Strict-Transport-Security - Force HTTPS for 1 year (including subdomains)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // X-Frame-Options - Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-Content-Type-Options - Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer-Policy - Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy (formerly Feature-Policy) - Control browser features
        $response->headers->set('Permissions-Policy', $this->getPermissionsPolicy());

        // X-XSS-Protection - Enable XSS filter (legacy but still useful)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
    }

    /**
     * Get Content-Security-Policy header value.
     */
    private function getContentSecurityPolicy(): string
    {
        $isDev = app()->environment('local', 'testing');

        $directives = [
            // Default to same-origin for most content types
            "default-src 'self'",

            // Scripts: self + Vite dev server in development + Reverb/Pusher
            $isDev
                ? "script-src 'self' 'unsafe-inline' 'unsafe-eval' ws://localhost:5173 https://localhost:5173 ws://127.0.0.1:6001"
                : "script-src 'self' 'unsafe-inline' 'unsafe-eval' wss://*.kalfa.me wss://kalfa.me",

            // Styles: self + inline (Tailwind/Livewire use inline styles)
            "style-src 'self' 'unsafe-inline'",

            // Images: self + data: (for avatars, inline images) + Reverb/Pusher
            "img-src 'self' data: https: wss://*.kalfa.me",

            // Fonts: self + data: (for inline fonts)
            "font-src 'self' data:",

            // Connect: self + WebSocket connections (Reverb, Pusher)
            $isDev
                ? "connect-src 'self' ws://localhost:5173 ws://127.0.0.1:6001 ws://localhost:6001 wss://*.mt1.pusher.com wss://*.kalfa.me"
                : "connect-src 'self' wss://*.mt1.pusher.com wss://*.kalfa.me",

            // Media: self + data: (for audio/video)
            "media-src 'self' data:",

            // Objects: none (block Flash, Java, etc.)
            "object-src 'none'",

            // Base URI: same-origin
            "base-uri 'self'",

            // Form action: same-origin
            "form-action 'self'",

            // Frame ancestors: same-origin (prevent embedding)
            "frame-ancestors 'self'",
        ];

        // Report-only for development, enforce in production
        // In production, uncomment to use CSP reporting:
        // $directives[] = 'report-uri /csp-report';

        return implode('; ', $directives);
    }

    /**
     * Get Permissions-Policy header value.
     *
     * Controls which browser features and APIs can be used.
     * Format: <feature>=<allowlist> where * = allow all, self = allow same-origin, none = block
     */
    private function getPermissionsPolicy(): string
    {
        $policies = [
            // Camera: blocked (unless explicitly allowed in future)
            'camera=()',

            // Microphone: blocked (unless explicitly allowed in future)
            'microphone=()',

            // Geolocation: blocked (unless explicitly allowed in future)
            'geolocation=()',

            // Payment: blocked for now
            'payment=()',

            // USB: blocked
            'usb=()',

            // Magnetometer: blocked
            'magnetometer=()',

            // Gyroscope: blocked
            'gyroscope=()',

            // Accelerometer: blocked
            'accelerometer=()',

            // Ambient light sensor: blocked
            'ambient-light-sensor=()',

            // Allow autoplay (for audio notifications)
            'autoplay=(self)',

            // Allow clipboard read/write
            'clipboard-read=(self)',
            'clipboard-write=(self)',

            // Allow fullscreen
            'fullscreen=(self)',

            // Allow focus without user gesture (for accessibility)
            'focus-without-user-activation=(self)',
        ];

        return implode(', ', $policies);
    }
}
