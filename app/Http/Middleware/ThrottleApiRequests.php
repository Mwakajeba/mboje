<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApiRequests
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rateLimitConfig = \App\Services\SystemSettingService::getRateLimitingConfig();
        
        // Different limits for authenticated vs unauthenticated users
        if ($request->user()) {
            $maxAttempts = $rateLimitConfig['api']['authenticated']['max_attempts'] ?? config('rate-limiting.api.authenticated.max_attempts', 60);
            $decayMinutes = $rateLimitConfig['api']['authenticated']['decay_minutes'] ?? config('rate-limiting.api.authenticated.decay_minutes', 1);
            $key = 'api|auth|' . $request->user()->id . '|' . $request->ip();
        } else {
            $maxAttempts = $rateLimitConfig['api']['unauthenticated']['max_attempts'] ?? config('rate-limiting.api.unauthenticated.max_attempts', 20);
            $decayMinutes = $rateLimitConfig['api']['unauthenticated']['decay_minutes'] ?? config('rate-limiting.api.unauthenticated.decay_minutes', 1);
            $key = 'api|guest|' . $request->ip();
        }

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            $seconds = ceil($retryAfter);

            Log::warning('API rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'user_id' => $request->user()?->id,
                'retry_after' => $retryAfter,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Too many API requests. Please try again in {$seconds} second(s).",
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - $this->limiter->attempts($key)),
        ]);

        return $response;
    }
}

