<?php

/**
 * Script to fix cash collateral permissions
 * Run this via: php artisan tinker < fix_cash_collateral_permissions.php
 * OR copy and paste the code below into tinker
 */

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

echo "=== Fixing Cash Collateral Permissions ===\n\n";

// Step 1: Create permissions if they don't exist
echo "Step 1: Creating permissions...\n";
$permissions = [
    'deposit cash collateral',
    'withdraw cash collateral',
    'view cash collateral details',
    'edit cash collateral',
    'delete cash collateral',
];

foreach ($permissions as $permName) {
    $permission = Permission::firstOrCreate(
        ['name' => $permName, 'guard_name' => 'web'],
        ['guard_name' => 'web']
    );
    echo "  ✓ Permission '{$permName}' exists or created (ID: {$permission->id})\n";
}

echo "\n";

// Step 2: Get current user
echo "Step 2: Checking current user...\n";
$user = User::find(auth()->id() ?? 1);
if (!$user) {
    echo "  ✗ No user found! Please login or specify user ID.\n";
    exit;
}
echo "  ✓ User: {$user->name} (ID: {$user->id})\n";

// Step 3: Check user's roles
echo "\nStep 3: Checking user roles...\n";
$userRoles = $user->roles;
if ($userRoles->isEmpty()) {
    echo "  ✗ User has no roles assigned!\n";
    echo "  Assigning 'admin' role...\n";
    $adminRole = Role::where('name', 'admin')->first();
    if ($adminRole) {
        $user->assignRole($adminRole);
        echo "  ✓ Assigned 'admin' role to user\n";
    } else {
        echo "  ✗ Admin role not found! Creating it...\n";
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());
        $user->assignRole($adminRole);
        echo "  ✓ Created and assigned 'admin' role with all permissions\n";
    }
} else {
    foreach ($userRoles as $role) {
        echo "  ✓ Role: {$role->name} (ID: {$role->id})\n";
    }
}

echo "\n";

// Step 4: Assign permissions to all roles or specific role
echo "Step 4: Assigning permissions to roles...\n";
$rolesToUpdate = $userRoles->isEmpty() ? [Role::where('name', 'admin')->first()] : $userRoles;

foreach ($rolesToUpdate as $role) {
    if (!$role) continue;
    
    echo "  Assigning to role '{$role->name}'...\n";
    foreach ($permissions as $permName) {
        $permission = Permission::where('name', $permName)->first();
        if ($permission && !$role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
            echo "    ✓ Added '{$permName}'\n";
        } elseif ($permission) {
            echo "    - Already has '{$permName}'\n";
        }
    }
}

echo "\n";

// Step 5: Verify user has permissions
echo "Step 5: Verifying user permissions...\n";
$allGood = true;
foreach ($permissions as $permName) {
    if ($user->can($permName)) {
        echo "  ✓ User CAN '{$permName}'\n";
    } else {
        echo "  ✗ User CANNOT '{$permName}'\n";
        $allGood = false;
    }
}

echo "\n";

// Step 6: Alternative - Assign directly to user if role-based doesn't work
if (!$allGood) {
    echo "Step 6: Assigning permissions directly to user...\n";
    foreach ($permissions as $permName) {
        $permission = Permission::where('name', $permName)->first();
        if ($permission && !$user->hasPermissionTo($permission)) {
            $user->givePermissionTo($permission);
            echo "  ✓ Directly assigned '{$permName}' to user\n";
        }
    }
}

echo "\n";
echo "=== Summary ===\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
echo "Can deposit cash collateral: " . ($user->can('deposit cash collateral') ? 'YES ✓' : 'NO ✗') . "\n";
echo "Can withdraw cash collateral: " . ($user->can('withdraw cash collateral') ? 'YES ✓' : 'NO ✗') . "\n";
echo "\n";
echo "Next steps:\n";
echo "1. Logout and login again to refresh permissions\n";
echo "2. Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)\n";
echo "3. Refresh the Cash Deposits page\n";
echo "\n";

