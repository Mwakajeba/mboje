<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SetDefaultLocation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only set default location for authenticated users
        if (Auth::check() && (!session('location_id') || session('location_id') === null || session('location_id') === '')) {
            $user = Auth::user();
            
            \Log::info('SetDefaultLocation middleware running for user: ' . $user->name);
            
            // Try to get user's default location
            $defaultLocation = $user->defaultLocation()->first();
            
            if ($defaultLocation) {
                session(['location_id' => $defaultLocation->id]);
                session(['branch_id' => $defaultLocation->branch_id]);
                \Log::info('Set default location: ' . $defaultLocation->name . ' (ID: ' . $defaultLocation->id . ')');
            } else {
                // Fallback to first available location
                $firstLocation = $user->locations()->first();
                if ($firstLocation) {
                    session(['location_id' => $firstLocation->id]);
                    session(['branch_id' => $firstLocation->branch_id]);
                    \Log::info('Set fallback location: ' . $firstLocation->name . ' (ID: ' . $firstLocation->id . ')');
                }
            }
        }

        return $next($request);
    }
}
