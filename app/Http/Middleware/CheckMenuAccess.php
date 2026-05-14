<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for guest users (they'll be redirected by auth middleware)
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Super-admin bypasses menu access check
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // Get current route name
        $route = $request->route();
        if (!$route) {
            return $next($request);
        }

        $routeName = $route->getName();

        if (!$routeName) {
            // If no route name, allow (might be a direct URL without named route)
            return $next($request);
        }

        // Get user's role IDs
        $userRoleIds = $user->roles->pluck('id')->toArray();

        if (empty($userRoleIds)) {
            // User has no roles, deny access
            abort(403, 'You do not have access to this menu.');
        }

        // Find menu(s) with this route
        $menus = Menu::where('route', $routeName)->get();

        // If no menu found for this route, allow access (route might not be in menu system)
        if ($menus->isEmpty()) {
            return $next($request);
        }

        // Check if user has permission to access any of these menus
        $hasAccess = false;
        foreach ($menus as $menu) {
            // If menu has no permission_name, skip permission check (backward compatibility)
            if (!$menu->permission_name) {
                // Fallback to old menu_role check
                $userMenuIds = DB::table('menu_role')
                    ->whereIn('role_id', $userRoleIds)
                    ->pluck('menu_id')
                    ->unique();
                
                if ($userMenuIds->contains($menu->id)) {
                    $hasAccess = true;
                    break;
                }
                
                // Check parent menu
                if ($menu->parent_id) {
                    $parentMenu = Menu::find($menu->parent_id);
                    if ($parentMenu && $userMenuIds->contains($parentMenu->id)) {
                        $hasAccess = true;
                        break;
                    }
                }
                continue;
            }
            
            // Check if user has permission to access this menu
            if ($user->can($menu->permission_name)) {
                $hasAccess = true;
                break;
            }
            
            // Also check parent menu permission if this is a child menu
            if ($menu->parent_id) {
                $parentMenu = Menu::find($menu->parent_id);
                if ($parentMenu && $parentMenu->permission_name && $user->can($parentMenu->permission_name)) {
                    $hasAccess = true;
                    break;
                }
            }
        }

        if (!$hasAccess) {
            abort(403, 'You do not have access to this menu. Please contact your administrator.');
        }

        return $next($request);
    }
}
