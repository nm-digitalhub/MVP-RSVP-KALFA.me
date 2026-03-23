<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleWithCustomResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1', string $prefix = ''): Response
    {
        $key = $prefix.$this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key)) {
            return $this->buildThrottleResponse($request);
        }

        RateLimiter::hit($key, $maxAttempts);

        $response = $next($request);

        return $this->addHeaders($response, $maxAttempts, $request->ip());
    }

    /**
     * Resolve request signature.
     */
    private function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sha1($user->getAuthIdentifier());
        }

        return $request->fingerprint();
    }

    /**
     * Create a custom throttle response.
     */
    private function buildThrottleResponse(Request $request): Response
    {
        $message = match ($request->ajax() || $request->wantsJson()) {
            true => [
                'message' => 'Too many requests. Please slow down.',
                'error' => 'rate_limit_exceeded',
                'retry_after' => $this->getRetryAfter(),
            ],
            false => view('errors.rate-limited', [
                'retry_after' => $this->getRetryAfter(),
            ])->render(),
        };

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($message, 429);
        }

        return $message;
    }

    /**
     * Get retry after seconds.
     */
    private function getRetryAfter(): int
    {
        $availableIn = RateLimiter::availableIn(60);

        return $availableIn > 0 ? $availableIn : 60;
    }

    /**
     * Add rate limit headers to response.
     */
    private function addHeaders(Response $response, string $maxAttempts, string $limitKey): Response
    {
        if ($response->isSuccessful()) {
            $remaining = RateLimiter::retriesLeft($limitKey, $maxAttempts);
            $response->headers->set('X-RateLimit-Limit', $maxAttempts);
            $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
            $response->headers->set('X-RateLimit-Reset', RateLimiter::availableIn($maxAttempts));
        }

        return $response;
    }
}
