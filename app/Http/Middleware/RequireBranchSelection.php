<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireBranchSelection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // Honor explicit branch selection from request (dashboard branch filter uses GET).
            if ($request->has('branch_id')) {
                $requested = trim((string) $request->query('branch_id'));
                if ($requested !== '' && strtolower($requested) !== 'all' && strtolower($requested) !== 'null') {
                    session(['branch_id' => (int) $requested]);
                }
            }

            // Require branch selection via session context, fallback to user's branch_id
            $branchId = session('branch_id') ?: Auth::user()->branch_id;
            
            if (!$branchId) {
                // For AJAX requests, return JSON error instead of redirect
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Branch selection is required. Please select a branch first.',
                        'redirect' => route('change-branch')
                    ], 403);
                }
                return redirect()->route('change-branch');
            }

            // If we have user branch but no session branch, set the session branch
            if (!session('branch_id') && Auth::user()->branch_id) {
                session(['branch_id' => Auth::user()->branch_id]);
            }
        }

        return $next($request);
    }
}
