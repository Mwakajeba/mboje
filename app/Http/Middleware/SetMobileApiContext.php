<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies branch/location context from mobile API headers (mirrors web session).
 */
class SetMobileApiContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $branchId = $request->header('X-Branch-Id') ?? $request->query('branch_id');
        $locationId = $request->header('X-Location-Id') ?? $request->query('location_id');

        if ($branchId !== null && $branchId !== '') {
            session(['branch_id' => (int) $branchId]);
        }

        if ($locationId !== null && $locationId !== '') {
            session(['location_id' => (int) $locationId]);
        }

        return $next($request);
    }
}
