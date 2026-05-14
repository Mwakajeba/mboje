# How to Seed Cash Deposit Permissions

## Option 1: Run the Seeder (Recommended)

Run the RolePermissionSeeder to create and assign permissions:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

This will:
- Create the permissions if they don't exist
- Assign them to the `manager` role (and `super-admin`/`admin` roles which have all permissions)

## Option 2: Assign Permissions Manually via Tinker

If you want to assign permissions to a specific role or user manually:

```bash
php artisan tinker
```

Then run:

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Create permissions if they don't exist (Cash Collateral permissions)
Permission::firstOrCreate(['name' => 'deposit cash collateral', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'withdraw cash collateral', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'view cash collateral details', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'edit cash collateral', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'delete cash collateral', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'view cash deposits', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'create cash deposit', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'edit cash deposit', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'delete cash deposit', 'guard_name' => 'web']);

// Assign to a role (replace 'manager' with your role name)
$role = Role::where('name', 'manager')->first();
if ($role) {
    $role->givePermissionTo([
        'deposit cash collateral',
        'withdraw cash collateral',
        'view cash collateral details',
        'edit cash collateral',
        'delete cash collateral',
        'view cash deposits',
        'create cash deposit',
        'edit cash deposit',
        'delete cash deposit'
    ]);
}

// Or assign directly to a user
$user = \App\Models\User::find(1); // Replace 1 with your user ID
if ($user) {
    $user->givePermissionTo([
        'deposit cash collateral',
        'withdraw cash collateral',
        'view cash collateral details',
        'edit cash collateral',
        'delete cash collateral',
        'view cash deposits',
        'create cash deposit',
        'edit cash deposit',
        'delete cash deposit'
    ]);
}
```

## Option 3: Via Database Directly

You can also insert directly into the database:

```sql
-- Insert permissions if they don't exist
INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at)
VALUES 
    ('deposit cash collateral', 'web', NOW(), NOW()),
    ('withdraw cash collateral', 'web', NOW(), NOW()),
    ('view cash collateral details', 'web', NOW(), NOW()),
    ('edit cash collateral', 'web', NOW(), NOW()),
    ('delete cash collateral', 'web', NOW(), NOW()),
    ('view cash deposits', 'web', NOW(), NOW()),
    ('create cash deposit', 'web', NOW(), NOW()),
    ('edit cash deposit', 'web', NOW(), NOW()),
    ('delete cash deposit', 'web', NOW(), NOW());

-- Assign to role_permissions table (replace role_id with your role ID)
INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id 
FROM permissions p
CROSS JOIN roles r
WHERE p.name IN ('deposit cash collateral', 'withdraw cash collateral', 'view cash collateral details', 'edit cash collateral', 'delete cash collateral', 'view cash deposits', 'create cash deposit', 'edit cash deposit', 'delete cash deposit')
AND r.name = 'manager'; -- Replace 'manager' with your role name
```

## Verify Permissions

After seeding, verify the permissions exist:

```bash
php artisan tinker
```

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Check if permissions exist
Permission::where('name', 'like', '%cash collateral%')->orWhere('name', 'like', '%cash deposit%')->get();

// Check if role has permissions
$role = Role::where('name', 'manager')->first();
$role->permissions()->where('name', 'like', '%cash collateral%')->orWhere('name', 'like', '%cash deposit%')->get();

// Check if user has permission
$user = \App\Models\User::find(1);
$user->can('deposit cash collateral'); // Should return true
$user->can('withdraw cash collateral'); // Should return true
```

## Required Permissions for Buttons to Show

- **Deposit Button**: `deposit cash collateral` permission
- **Withdraw Button**: `withdraw cash collateral` permission (only shows if balance > 0)

After assigning these permissions, the buttons will appear in the Cash Deposits list table.

