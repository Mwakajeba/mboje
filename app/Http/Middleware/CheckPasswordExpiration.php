<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\PasswordService;
use App\Services\SystemSettingService;

class CheckPasswordExpiration
{
    protected $passwordService;

    public function __construct()
    {
        $this->passwordService = new PasswordService();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for guest users
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $expirationDays = (int) SystemSettingService::get('password_expiration_days', 0);

        // Skip if password expiration is disabled
        if ($expirationDays <= 0) {
            return $next($request);
        }

        // Skip password change routes
        if ($request->routeIs('password.change*') || 
            $request->routeIs('password.reset*') ||
            $request->routeIs('logout')) {
            return $next($request);
        }

        // Check if password has expired
        if ($this->passwordService->isPasswordExpired($user)) {
            $user->update([
                'password_expired' => true,
                'force_password_change' => true,
            ]);

            // Redirect to password change page
            return redirect()->route('password.change')
                ->with('warning', 'Your password has expired. Please change it to continue.');
        }

        // Check if password is expiring soon
        $daysUntilExpiration = $this->passwordService->getDaysUntilExpiration($user);
        $warningDays = (int) SystemSettingService::get('password_expiration_warning_days', 7);

        if ($daysUntilExpiration !== null && $daysUntilExpiration <= $warningDays && $daysUntilExpiration > 0) {
            $request->session()->flash('password_warning', 
                "Your password will expire in {$daysUntilExpiration} day(s). Please consider changing it soon.");
        }

        return $next($request);
    }
}
