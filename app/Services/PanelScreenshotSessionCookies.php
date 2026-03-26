<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds session cookies for {@see PanelScreenshotRunner} using the same session
 * the browser would get after a successful web login.
 *
 * Spatie documents authenticated screenshots via Browsershot useCookies()
 * and optional authenticate() for HTTP Basic.
 *
 * @see https://spatie.be/docs/laravel-screenshot/v1/drivers/customizing-browsershot
 */
final class PanelScreenshotSessionCookies
{
    /**
     * Perform GET /login → POST /login inside the application kernel (no outbound HTTP).
     * Reliable when Artisan runs on the app host; avoids loopback issues to APP_URL.
     *
     * @return array<string, string> Cookie name => value for Browsershot::useCookies()
     */
    public static function fromWebPasswordLogin(string $email, string $password): array
    {
        $kernel = app(Kernel::class);

        $loginUrl = rtrim((string) config('app.url'), '/').'/login';

        $getRequest = Request::create($loginUrl, 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $getResponse = $kernel->handle($getRequest);

        try {
            if ($getResponse->getStatusCode() >= 400) {
                throw new RuntimeException('Failed to load login page: HTTP '.$getResponse->getStatusCode());
            }

            $html = $getResponse->getContent();
            if (! is_string($html) || ! preg_match('/name="_token"\s+value="([^"]+)"/', $html, $matches)) {
                throw new RuntimeException('Could not parse CSRF token from login page.');
            }

            $cookiesFromGet = self::cookiesFromResponse($getResponse);

            $postRequest = Request::create($loginUrl, 'POST', [
                '_token' => $matches[1],
                'email' => $email,
                'password' => $password,
                'remember' => 'on',
            ], $cookiesFromGet, [], [
                'HTTP_ACCEPT' => 'text/html',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_REFERER' => $loginUrl,
            ]);

            $postResponse = $kernel->handle($postRequest);

            try {
                if ($postResponse->getStatusCode() === 422) {
                    throw new RuntimeException('Login failed (validation rejected credentials).');
                }

                if ($postResponse->isRedirect()) {
                    $location = (string) $postResponse->headers->get('Location', '');
                    $path = parse_url($location, PHP_URL_PATH);
                    if ($path === null && str_starts_with($location, '/')) {
                        $path = parse_url('https://local.test'.$location, PHP_URL_PATH);
                    }
                    $path = $path !== null ? rtrim((string) $path, '/') : '';
                    if ($path !== '' && str_ends_with($path, '/login')) {
                        throw new RuntimeException('Login failed (redirected back to login).');
                    }
                }

                $finalCookies = self::cookiesFromResponse($postResponse);
                $sessionName = (string) config('session.cookie');

                if ($finalCookies === [] || ! array_key_exists($sessionName, $finalCookies)) {
                    throw new RuntimeException('Login response did not include the application session cookie.');
                }

                return $finalCookies;
            } finally {
                $kernel->terminate($postRequest, $postResponse);
            }
        } finally {
            $kernel->terminate($getRequest, $getResponse);
        }
    }

    /**
     * @return array<string, string>
     */
    private static function cookiesFromResponse(Response $response): array
    {
        $cookies = [];

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie instanceof Cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
        }

        return $cookies;
    }
}
