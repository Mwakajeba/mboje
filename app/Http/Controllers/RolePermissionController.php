<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Menu;
use App\Models\PermissionGroup;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with(['permissions', 'users'])->get();
        $permissions = Permission::all();
        $activeUsers = User::where('status', 'active')->count();
        $systemRoles = Role::whereIn('name', ['super-admin', 'admin', 'manager', 'user', 'viewer'])->count();

        // Get permission groups from database
        $permissionGroups = $this->getPermissionGroupsFromDatabase();

        return view('roles.index', compact('roles', 'permissions', 'permissionGroups', 'activeUsers', 'systemRoles'));
    }

    public function create()
    {
        $permissions = Permission::all();
        $permissionGroups = $this->getPermissionGroupsFromDatabase();
        info('all permissions', ['permissions' => $permissions]);
        return view('roles.create', compact('permissions', 'permissionGroups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => strtolower($request->name),
                'description' => $request->description,
            ]);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully!'
                ]);
            }

            return redirect()->route('roles.index')
                ->with('success', 'Role created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create role: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()
                ->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);
        $permissionGroups = $this->groupPermissions($role->permissions);

        return view('roles.show', compact('role', 'permissionGroups'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $permissionGroups = $this->getPermissionGroupsFromDatabase();

        return view('roles.edit', compact('role', 'permissions', 'permissionGroups'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::beginTransaction();

            // Update role basic information
            $role->update([
                'name' => strtolower($request->name),
                'description' => $request->description,
            ]);

            // Handle permissions - if no permissions are selected, sync with empty collection
            if ($request->has('permissions') && is_array($request->permissions)) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
            } else {
                $permissions = collect();
            }

            // Sync permissions (this will add new ones and remove old ones)
            $role->syncPermissions($permissions);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role updated successfully!',
                    'debug' => [
                        'role_id' => $role->id,
                        'permissions_count' => $permissions->count(),
                        'permissions' => $permissions->pluck('name')->toArray()
                    ]
                ]);
            }

            return redirect()->route('roles.index')
                ->with('success', 'Role updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update role: ' . $e->getMessage(),
                    'debug' => [
                        'error' => $e->getMessage(),
                        'line' => $e->getLine(),
                        'file' => $e->getFile()
                    ]
                ], 422);
            }

            return back()->withInput()
                ->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        // Prevent deletion of system roles
        if (in_array($role->name, ['super-admin', 'admin'])) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete system roles.'
                ], 422);
            }
            return back()->with('error', 'Cannot delete system roles.');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role that has assigned users.'
                ], 422);
            }
            return back()->with('error', 'Cannot delete role that has assigned users.');
        }

        try {
            $role->delete();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role deleted successfully!'
                ]);
            }

            return redirect()->route('roles.index')
                ->with('success', 'Role deleted successfully!');

        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete role: ' . $e->getMessage()
                ], 422);
            }

            return back()->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    public function assignToUser(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ]);

        try {
            $roles = Role::whereIn('id', $request->roles)->get();
            $user->syncRoles($roles);

            return back()->with('success', 'Roles assigned to user successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to assign roles: ' . $e->getMessage());
        }
    }

    public function removeFromUser(Request $request, User $user)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            $role = Role::find($request->role_id);
            $user->removeRole($role);

            return back()->with('success', 'Role removed from user successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to remove role: ' . $e->getMessage());
        }
    }

    public function permissions()
    {
        $permissions = Permission::with('roles')->get();
        $permissionGroups = $this->groupPermissions($permissions);

        return view('roles.permissions', compact('permissions', 'permissionGroups'));
    }

    public function createPermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'permission_group_id' => 'nullable|exists:permission_groups,id',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $permission = Permission::create([
                'name' => strtolower($request->name),
                'description' => $request->description,
                'permission_group_id' => $request->permission_group_id,
            ]);

            // Store group information in a custom field or use the name pattern
            // For now, we'll use the group to organize permissions in the UI

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission created successfully!',
                    'permission' => $permission
                ]);
            }

            return back()->with('success', 'Permission created successfully!');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create permission: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()
                ->with('error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    public function deletePermission(Permission $permission)
    {
        try {
            $permission->delete();
            return back()->with('success', 'Permission deleted successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }

    /**
     * Get permission groups from database with their permissions
     */
    private function getPermissionGroupsFromDatabase()
    {
        // Get all permissions with their permission groups
        $permissions = Permission::with('permissionGroup')->get();

        // $groups = [
        //     'dashboard' => [],
        //     'settings' => [],
        //     'customers' => [],
        //     'inventory' => [],
        //     'purchases' => [],
        //     'sales' => [],
        //     'cash_deposits' => [],
        //     'accounting' => [],
        //     'reports' => [],
        //     // 'chat' => [],
        // ];
        $groups = PermissionGroup::all()->keyBy('name')->mapWithKeys(function ($group) {
            return [$group->name => []];
        })->toArray();

        info('Permission groups fetched from database', ['groups' => array_keys($groups)]);

        foreach ($permissions as $permission) {
            $groupName = $permission->permissionGroup ? $permission->permissionGroup->name : null;

            if ($groupName && isset($groups[$groupName])) {
                $groups[$groupName][] = $permission;
            } else {
                // Default to settings for any unmatched permissions
                $groups['settings'][] = $permission;
            }
        }

        // Remove empty groups
        return array_filter($groups);
    }

    /**
     * Group permissions by category based on their stored group or names (fallback method)
     */
    /**
     * Check if user has menu access via role (backward compatibility)
     */
    private function hasMenuAccessViaRoleCheck($user, $menu, $checkChildren = true)
    {
        $userRoleIds = $user->roles->pluck('id')->toArray();
        if (empty($userRoleIds)) {
            return false;
        }
        
        $userMenuIds = DB::table('menu_role')
            ->whereIn('role_id', $userRoleIds)
            ->pluck('menu_id')
            ->unique();
        
        return $userMenuIds->contains($menu->id);
    }

    private function groupPermissions($permissions)
    {
        $groups = [
            'dashboard' => [],
            'settings' => [],
            'customers' => [],
            'inventory' => [],
            'purchases' => [],
            'sales' => [],
            'cash_deposits' => [],
            'accounting' => [],
            'reports' => [],
            'chat' => [],
        ];

        foreach ($permissions as $permission) {
            // First, try to use the stored group field
            if ($permission->group && isset($groups[$permission->group])) {
                $groups[$permission->group][] = $permission;
                continue;
            }

            // Fallback to name-based grouping for existing permissions without group
            $name = strtolower($permission->name);

            if (str_contains($name, 'dashboard') || str_contains($name, 'home')) {
                $groups['dashboard'][] = $permission;
            } elseif (str_contains($name, 'setting') || str_contains($name, 'config')) {
                $groups['settings'][] = $permission;
            } elseif (str_contains($name, 'customer')) {
                $groups['customers'][] = $permission;
            } elseif (str_contains($name, 'inventory') || str_contains($name, 'item') || str_contains($name, 'category') || str_contains($name, 'movement')) {
                $groups['inventory'][] = $permission;
            } elseif (str_contains($name, 'purchase') || str_contains($name, 'supplier') || str_contains($name, 'bill')) {
                $groups['purchases'][] = $permission;
            } elseif (str_contains($name, 'sale') || str_contains($name, 'invoice') || str_contains($name, 'proforma') || str_contains($name, 'delivery') || str_contains($name, 'credit_note')) {
                $groups['sales'][] = $permission;
            } elseif (str_contains($name, 'cash_deposit') || str_contains($name, 'deposit')) {
                $groups['cash_deposits'][] = $permission;
            } elseif (str_contains($name, 'account') || str_contains($name, 'journal') || str_contains($name, 'payment') || str_contains($name, 'receipt') || str_contains($name, 'bank') || str_contains($name, 'fee') || str_contains($name, 'penalty') || str_contains($name, 'budget')) {
                $groups['accounting'][] = $permission;
            } elseif (str_contains($name, 'report')) {
                $groups['reports'][] = $permission;
            } elseif (str_contains($name, 'chat') || str_contains($name, 'message')) {
                $groups['chat'][] = $permission;
            }
        }

        // Remove empty groups
        return array_filter($groups);
    }

    /**
     * Get role statistics for dashboard
     */
    public function getStats()
    {
        $stats = [
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'total_users' => User::count(),
            'system_roles' => Role::whereIn('name', ['super-admin', 'admin', 'manager', 'user', 'viewer'])->count(),
        ];

        return response()->json($stats);
    }

    // Menu Management Methods
    public function manageMenus(Role $role)
    {
        $role->load([
            'menus' => function ($query) {
                $query->with('children');
            }
        ]);

        $user = auth()->user();
        
        // If user is super-admin, show all menus
        // Otherwise, show only menus that the user has permission to access
        if ($user->hasRole('super-admin')) {
            $allMenus = Menu::with('children')
                ->whereNull('parent_id')
                ->get();
        } else {
            // Get menu IDs assigned to user's roles via menu_role
            // User can only assign menus that are assigned to their own role(s)
            $userRoleIds = $user->roles->pluck('id')->toArray();
            $userMenuIds = DB::table('menu_role')
                ->whereIn('role_id', $userRoleIds)
                ->pluck('menu_id')
                ->unique();

            // Exclude menus that are already assigned to the target role
            // We only want to show menus that this role DOESN'T have yet
            $roleMenuIds = $role->menus->pluck('id')->toArray();
            $availableMenuIds = $userMenuIds->diff($roleMenuIds);
            
            if ($availableMenuIds->isEmpty()) {
                // No new menus the user can assign to this role
                $allMenus = collect();
            } else {
                // Get ONLY parent menus that are directly available to assign
                $directParentMenuIds = Menu::whereIn('id', $availableMenuIds)
                        ->whereNull('parent_id')
                        ->pluck('id');
                    
                // Get child menus that are directly available to assign
                $assignedChildMenuIds = Menu::whereIn('id', $availableMenuIds)
                        ->whereNotNull('parent_id')
                        ->pluck('id');
                    
                    // Get parent IDs of assigned children (to show parent structure for assigned children)
                    $parentIdsFromAssignedChildren = Menu::whereIn('id', $assignedChildMenuIds)
                        ->whereNotNull('parent_id')
                        ->pluck('parent_id')
                        ->unique();
                    
                    // Combine: directly assigned parent menus + parents of assigned children
                    $allParentMenuIds = $directParentMenuIds->merge($parentIdsFromAssignedChildren)->unique();
                    
                    // Load parent menus with ONLY the children that are directly assigned
                    $allMenus = Menu::with(['children' => function($query) use ($availableMenuIds) {
                            // Only load children that are directly available to assign
                            $query->whereIn('id', $availableMenuIds);
                        }])
                        ->whereIn('id', $allParentMenuIds)
                        ->whereNull('parent_id')
                        ->get();
                    
                    // Final filter: Only show parent menus if:
                    // 1. Parent menu is directly available to assign, OR
                    // 2. At least one child menu is directly available to assign
                    // And only show children that are directly available to assign
                    $allMenus = $allMenus->filter(function($menu) use ($availableMenuIds) {
                        // Filter children to only show directly available menus
                        $assignedChildren = $menu->children->filter(function($child) use ($availableMenuIds) {
                            return $availableMenuIds->contains($child->id);
                        });
                        
                        // If parent menu is directly available, show it with available children
                        if ($availableMenuIds->contains($menu->id)) {
                            $menu->setRelation('children', $assignedChildren);
                            return true;
                        }
                        
                        // If parent is not directly available, only show if at least one child is available
                        if ($assignedChildren->count() > 0) {
                            $menu->setRelation('children', $assignedChildren);
                            return true;
                        }
                        
                        // No assigned children and parent not assigned, hide this menu
                        return false;
                    })->values();
                }
            }

        return view('roles.manage-menus', compact('role', 'allMenus'));
    }

    public function assignMenus(Request $request, Role $role)
    {
        $request->validate([
            'menu_ids' => 'required|array',
            'menu_ids.*' => 'exists:menus,id'
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user()->load(['roles.menus']);
            
            // If user is not super-admin, validate they can only assign menus they have access to
            if (!$user->hasRole('super-admin')) {
                // Get all menu IDs that the current user's roles have access to
                $userMenuIds = collect();
                foreach ($user->roles as $userRole) {
                    $userMenuIds = $userMenuIds->merge($userRole->menus->pluck('id'));
                }
                $userMenuIds = $userMenuIds->unique();
                
                // Check if all requested menu IDs are in the user's accessible menus
                $invalidMenuIds = array_diff($request->menu_ids, $userMenuIds->toArray());
                
                if (!empty($invalidMenuIds)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only assign menus that you have access to.'
                    ], 403);
                }
            }

            // Use syncWithoutDetaching to add menus without removing existing ones
            $role->menus()->syncWithoutDetaching($request->menu_ids);

            DB::commit();

            $addedCount = count($request->menu_ids);
            $message = $addedCount === 1 
                ? 'Menu assigned successfully!'
                : "{$addedCount} menus assigned successfully!";

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign menus: ' . $e->getMessage()
            ], 422);
        }
    }

    public function removeMenu(Request $request, Role $role)
    {
        $request->validate([
            'menu_id' => 'required|exists:menus,id'
        ]);

        try {
            $role->menus()->detach($request->menu_id);

            return response()->json([
                'success' => true,
                'message' => 'Menu removed successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove menu: ' . $e->getMessage()
            ], 422);
        }
    }

    public function removeAllSubmenus(Request $request, Role $role)
    {
        $request->validate([
            'parent_menu_id' => 'required|exists:menus,id'
        ]);

        try {
            $parentMenu = \App\Models\Menu::findOrFail($request->parent_menu_id);
            $childMenuIds = $parentMenu->children->pluck('id')->toArray();
            
            if (empty($childMenuIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No submenus found for this menu.'
                ], 422);
            }

            // Get only the child menus that are actually assigned to this role
            $assignedChildIds = $role->menus()->whereIn('menus.id', $childMenuIds)->pluck('menus.id')->toArray();
            
            if (empty($assignedChildIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No submenus are currently assigned to this role.'
                ], 422);
            }

            $role->menus()->detach($assignedChildIds);

            return response()->json([
                'success' => true,
                'message' => count($assignedChildIds) . ' submenu(s) removed successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove submenus: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove all menus (parents and children) from the given role.
     */
    public function removeAllMenus(Role $role)
    {
        try {
            $assignedCount = $role->menus()->count();

            if ($assignedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This role has no menus assigned.'
                ], 422);
            }

            $role->menus()->detach();

            return response()->json([
                'success' => true,
                'message' => "All {$assignedCount} menus have been removed from this role."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove all menus: ' . $e->getMessage()
            ], 422);
        }
    }
}

