<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SystemSettingService;

class ApplySystemSettings
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apply security settings
        $this->applySecuritySettings();
        
        return $next($request);
    }

    /**
     * Apply security settings to the application
     */
    private function applySecuritySettings()
    {
        try {
            $securityConfig = SystemSettingService::getSecurityConfig();
            
            // Apply session lifetime
            if (isset($securityConfig['session_lifetime'])) {
                config(['session.lifetime' => $securityConfig['session_lifetime']]);
            }
            
            // Enforce secure cookies if HTTPS is enabled or if setting is enabled
            $forceHttps = SystemSettingService::get('security_force_https', env('FORCE_HTTPS', false));
            $enforceSecureCookies = SystemSettingService::get('security_enforce_secure_cookies', true);
            
            // Enable secure cookies if HTTPS is forced or if explicitly enabled
            // Also check if current request is secure
            $isSecure = request()->secure() || 
                       filter_var($forceHttps, FILTER_VALIDATE_BOOLEAN) || 
                       filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN);
            
            if ($isSecure && filter_var($enforceSecureCookies, FILTER_VALIDATE_BOOLEAN)) {
                config(['session.secure' => true]);
            } elseif (!app()->environment(['local', 'testing'])) {
                // In production, enforce secure cookies if HTTPS is available
                config(['session.secure' => true]);
            } else {
                // In local/testing, use environment variable or default to false
                config(['session.secure' => env('SESSION_SECURE_COOKIE', false)]);
            }
            
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::warning('Failed to apply system settings: ' . $e->getMessage());
        }
    }
}
