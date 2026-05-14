<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ThrottleGlobalRequests
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
        $maxAttempts = $rateLimitConfig['global']['max_attempts'] ?? config('rate-limiting.global.max_attempts', 200);
        $decayMinutes = $rateLimitConfig['global']['decay_minutes'] ?? config('rate-limiting.global.decay_minutes', 1);

        // Create key based on IP address
        $key = 'global|' . $request->ip();

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            $seconds = ceil($retryAfter);

            Log::critical('Global rate limit exceeded - possible DDoS attack', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'retry_after' => $retryAfter,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Too many requests. Please try again in {$seconds} second(s).",
                    'retry_after' => $retryAfter,
                ], 429)->withHeaders([
                    'Retry-After' => $retryAfter,
                ]);
            }

            abort(429, "Too many requests. Please try again in {$seconds} second(s).");
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

