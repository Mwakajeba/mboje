<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Services\SystemSettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for guest users
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $companyId = $user->company_id;

        // Skip check for super admin or if user is already locked
        if ($user->status === 'locked' || !$companyId) {
            if ($user->status === 'locked') {
                Auth::logout();
                return redirect()->route('subscription.expired');
            }
            return $next($request);
        }

        // Check if company has an active subscription
        $activeSubscription = Subscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->first();

        // If no active subscription, check if user is trying to access subscription management
        if (!$activeSubscription) {
            $allowedRoutes = [
                'subscriptions.*',
                'logout',
                'login',
                'password.*',
                'verification.*',
                'subscription.expired',
            ];

            $currentRoute = $request->route()->getName();

            // Allow access to subscription management routes
            foreach ($allowedRoutes as $allowedRoute) {
                if (str_contains($allowedRoute, '*')) {
                    $pattern = str_replace('*', '.*', $allowedRoute);
                    if (preg_match('/^' . $pattern . '$/', $currentRoute)) {
                        return $next($request);
                    }
                } elseif ($currentRoute === $allowedRoute) {
                    return $next($request);
                }
            }

            // Redirect to expired subscription page
            return redirect()->route('subscription.expired');
        }

        // Check if subscription is expired (remaining days < 0)
            $daysUntilExpiry = $activeSubscription->daysUntilExpiry();

        if ($daysUntilExpiry < 0) {
            // Lock all users for this company (except super-admin)
            $this->lockCompanyUsers($companyId);
            
            // Logout and redirect to expired page
            Auth::logout();
            return redirect()->route('subscription.expired');
        }

        // Get notification days - check subscription-specific first, then system settings
        $subscriptionNotificationDays = null;
        if ($activeSubscription->features && isset($activeSubscription->features['notification_days'])) {
            $subscriptionNotificationDays = (int)$activeSubscription->features['notification_days'];
        }
        
        // Fall back to system settings if not set in subscription
        $notificationDays = $subscriptionNotificationDays ?? SystemSettingService::get('subscription_notification_days_30', 30);
        $notificationDays20 = SystemSettingService::get('subscription_notification_days_20', 20);

        // Check if subscription is expiring soon and show notification
        if ($daysUntilExpiry >= 0 && $daysUntilExpiry <= $notificationDays) {
            $notificationShown = $request->session()->get('subscription_notification_shown', false);
            
            if (!$notificationShown) {
                $message = "Your subscription will expire in {$daysUntilExpiry} day(s). Please renew to avoid service interruption.";
                
                // Different message for 20 days or less
                if ($daysUntilExpiry <= $notificationDays20) {
                    $message = "URGENT: Your subscription will expire in {$daysUntilExpiry} day(s). Please renew immediately to avoid service interruption.";
                }
                
                $request->session()->flash('warning', $message);
                $request->session()->put('subscription_notification_shown', true);
            }
        }

        return $next($request);
    }

    /**
     * Lock all users for a company (except super-admin)
     */
    private function lockCompanyUsers(int $companyId): void
    {
        $users = \App\Models\User::where('company_id', $companyId)
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'super-admin');
            })
            ->get();

        foreach ($users as $user) {
            $user->update([
                'status' => 'locked',
                'is_active' => 'no',
            ]);
        }

        \Log::info("Locked {$users->count()} users for company ID: {$companyId} due to expired subscription (super-admin users excluded)");
    }
}