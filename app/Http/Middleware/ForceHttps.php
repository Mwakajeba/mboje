<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SystemSettingService;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if HTTPS enforcement is enabled via system settings
        $forceHttps = SystemSettingService::get('security_force_https', env('FORCE_HTTPS', false));
        
        // Also check environment variable for override
        $envForceHttps = env('FORCE_HTTPS', false);
        
        // Enable HTTPS enforcement if either system setting or env is true
        $shouldForceHttps = filter_var($forceHttps, FILTER_VALIDATE_BOOLEAN) || filter_var($envForceHttps, FILTER_VALIDATE_BOOLEAN);
        
        // Skip HTTPS enforcement in local/testing environments unless explicitly enabled
        if (app()->environment(['local', 'testing']) && !filter_var(env('FORCE_HTTPS_IN_LOCAL', false), FILTER_VALIDATE_BOOLEAN)) {
            $shouldForceHttps = false;
        }
        
        // Redirect to HTTPS if not already secure and enforcement is enabled
        if ($shouldForceHttps && !$request->secure() && !$request->isMethod('GET')) {
            // For non-GET requests, return error instead of redirecting
            return response()->json([
                'error' => 'HTTPS required',
                'message' => 'This application requires HTTPS connections.'
            ], 400);
        }
        
        if ($shouldForceHttps && !$request->secure() && $request->isMethod('GET')) {
            // Redirect GET requests to HTTPS
            return redirect()->secure($request->getRequestUri(), 301);
        }
        
        return $next($request);
    }
}

