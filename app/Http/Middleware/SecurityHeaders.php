<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SystemSettingService;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Get security header settings from system settings
        $enableHsts = filter_var(
            SystemSettingService::get('security_enable_hsts', env('SECURITY_ENABLE_HSTS', true)),
            FILTER_VALIDATE_BOOLEAN
        );
        
        $hstsMaxAge = (int) SystemSettingService::get('security_hsts_max_age', env('SECURITY_HSTS_MAX_AGE', 31536000)); // 1 year default
        
        $enableHstsIncludeSubdomains = filter_var(
            SystemSettingService::get('security_hsts_include_subdomains', env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true)),
            FILTER_VALIDATE_BOOLEAN
        );
        
        $enableHstsPreload = filter_var(
            SystemSettingService::get('security_hsts_preload', env('SECURITY_HSTS_PRELOAD', false)),
            FILTER_VALIDATE_BOOLEAN
        );
        
        // Only add HSTS header if HTTPS is being used
        if ($enableHsts && $request->secure()) {
            $hstsValue = "max-age={$hstsMaxAge}";
            
            if ($enableHstsIncludeSubdomains) {
                $hstsValue .= "; includeSubDomains";
            }
            
            if ($enableHstsPreload) {
                $hstsValue .= "; preload";
            }
            
            $response->headers->set('Strict-Transport-Security', $hstsValue);
        }
        
        // Add other security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Content Security Policy: use nonce-based policy (no unsafe-inline/unsafe-eval)
        $storedCsp = SystemSettingService::get('security_content_security_policy', env('SECURITY_CSP'));
        $nonce = $request->attributes->get('csp_nonce');

        if ($storedCsp !== 'disabled') {
             if ($storedCsp === null || $storedCsp === '' || $storedCsp === 'default') {
                // Use 'unsafe-inline' only (no nonce): with nonce present, browsers ignore 'unsafe-inline' and block inline style/script without nonce
                $csp = "default-src 'self'; " .
                    "script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net https://cdn.datatables.net https://cdnjs.cloudflare.com; " .
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdn.datatables.net; " .
                    "font-src 'self' https://fonts.gstatic.com data:; " .
                    "img-src 'self' data: https:; " .
                    "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net; " .
                    "frame-src 'self'; " .
                    "object-src 'none'; " .
                    "base-uri 'self'; " .
                    "form-action 'self'; " .
                    "frame-ancestors 'self';";
                $response->headers->set('Content-Security-Policy', $csp);
            } elseif ($storedCsp && $storedCsp !== 'default') {
                // Custom CSP from settings (legacy or admin-defined)
                $response->headers->set('Content-Security-Policy', $storedCsp);
            }
        }
        
        // Referrer Policy
        $referrerPolicy = SystemSettingService::get('security_referrer_policy', env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'));
        $response->headers->set('Referrer-Policy', $referrerPolicy);
        
        // Permissions Policy (formerly Feature-Policy)
        // Allow camera for QR scanning functionality
        $permissionsPolicy = SystemSettingService::get('security_permissions_policy', env('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=(self)'));
        if ($permissionsPolicy && $permissionsPolicy !== 'disabled') {
            $response->headers->set('Permissions-Policy', $permissionsPolicy);
        }
        
        return $response;
    }
}

