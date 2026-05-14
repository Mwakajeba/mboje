<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;

class CheckInventoryCostMethod
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if inventory cost method is set
        $costMethod = SystemSetting::where('key', 'inventory_cost_method')->value('value');
        
        if (!$costMethod) {
            // If cost method is not set, redirect to inventory settings
            if ($request->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Inventory cost method must be set before performing inventory operations. Please configure it in Settings > Inventory Settings.',
                    'redirect' => route('settings.inventory')
                ], 403);
            }
            
            return redirect()->route('settings.inventory')
                ->with('error', 'Inventory cost method must be set before performing inventory operations. Please configure your preferred cost method (FIFO or Weighted Average Cost) first.');
        }
        
        return $next($request);
    }
} 