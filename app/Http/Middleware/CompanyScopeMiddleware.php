<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CompanyScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Set company and branch context using session branch
            if ($user->company_id) {
                config(['app.current_company_id' => $user->company_id]);
                $branchId = session('branch_id');
                if ($branchId) {
                    config(['app.current_branch_id' => $branchId]);
                }
            }
        }

        return $next($request);
    }
}
