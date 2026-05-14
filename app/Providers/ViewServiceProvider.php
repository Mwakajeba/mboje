<?php

namespace App\Providers;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Menu;

use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('incs.sideMenu', function ($view) {
            $user = Auth::user();

            if (!$user) {
                $view->with('menus', []);
                return;
            }

            // Load roles with their menus
            $user->load('roles.menus');

            // Collect all menu IDs assigned to any of the user's roles
            $roleMenus = collect();
            foreach ($user->roles as $role) {
                $roleMenus = $roleMenus->merge($role->menus->pluck('id'));
            }
            $roleMenuIds = $roleMenus->unique();

            if ($roleMenuIds->isEmpty()) {
                $view->with('menus', []);
                return;
            }

            // Get only parent menus that are assigned to the user (via any of their roles)
            $parentMenus = Menu::whereNull('parent_id')
                ->whereIn('id', $roleMenuIds)
                ->get();

            // Attach only assigned child menus to each parent
            $parentMenus->each(function ($menu) use ($roleMenuIds) {
                $assignedChildren = $menu->children()
                    ->whereIn('id', $roleMenuIds)
                    ->get();
                $menu->setRelation('children', $assignedChildren);
            });

            $view->with('menus', $parentMenus);
        });
    }
}
