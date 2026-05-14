<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ThrottleByConfig
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
     * @param  string  $configKey
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $configKey): Response
    {
        $rateLimitConfig = \App\Services\SystemSettingService::getRateLimitingConfig();
        $maxAttempts = $rateLimitConfig[$configKey]['max_attempts'] ?? config("rate-limiting.{$configKey}.max_attempts", 60);
        $decayMinutes = $rateLimitConfig[$configKey]['decay_minutes'] ?? config("rate-limiting.{$configKey}.decay_minutes", 1);

        // Create key based on IP address and config key
        $key = $this->resolveRequestSignature($request, $configKey);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            $minutes = ceil($retryAfter / 60);

            Log::warning("Rate limit exceeded for {$configKey}", [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'config_key' => $configKey,
                'retry_after' => $retryAfter,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Too many requests. Please try again in {$minutes} minute(s).",
                    'retry_after' => $retryAfter,
                ], 429)->withHeaders([
                    'Retry-After' => $retryAfter,
                    'X-RateLimit-Limit' => $maxAttempts,
                    'X-RateLimit-Remaining' => 0,
                ]);
            }

            return back()->withErrors([
                'rate_limit' => "Too many requests. Please try again in {$minutes} minute(s).",
            ])->withInput();
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

    /**
     * Resolve request signature based on IP address and config key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $configKey
     * @return string
     */
    protected function resolveRequestSignature(Request $request, string $configKey): string
    {
        $identifier = $request->ip();
        
        // If user is authenticated, include user ID
        if ($request->user()) {
            $identifier = $request->user()->id . '|' . $request->ip();
        }

        return sha1("{$configKey}|{$request->method()}|{$request->getHost()}|{$identifier}");
    }
}

