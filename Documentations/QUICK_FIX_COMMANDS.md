# Quick Fix Commands for Cash Collateral Buttons

## Option 1: Run the Fix Script (Easiest)

Copy and paste this entire block into your terminal:

```bash
php artisan tinker
```

Then paste this code:

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

// Create permissions
$perms = ['deposit cash collateral', 'withdraw cash collateral', 'view cash collateral details', 'edit cash collateral', 'delete cash collateral'];
foreach ($perms as $p) {
    Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    echo "Created: $p\n";
}

// Get your user (replace 1 with your user ID)
$user = User::find(1); // CHANGE THIS TO YOUR USER ID

// Assign to admin role (or your role)
$role = Role::where('name', 'admin')->first() ?? Role::where('name', 'manager')->first();
if ($role) {
    $role->givePermissionTo($perms);
    echo "Assigned to role: {$role->name}\n";
}

// Also assign directly to user
$user->givePermissionTo($perms);
echo "Assigned directly to user: {$user->name}\n";

// Verify
echo "\nVerification:\n";
echo "Can deposit: " . ($user->can('deposit cash collateral') ? 'YES' : 'NO') . "\n";
echo "Can withdraw: " . ($user->can('withdraw cash collateral') ? 'YES' : 'NO') . "\n";
```

## Option 2: One-Liner Commands

```bash
# Run seeder
php artisan db:seed --class=RolePermissionSeeder

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Option 3: Direct Database (if you have DB access)

```sql
-- Check if permissions exist
SELECT * FROM permissions WHERE name LIKE '%cash collateral%';

-- Insert if missing
INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at)
VALUES 
    ('deposit cash collateral', 'web', NOW(), NOW()),
    ('withdraw cash collateral', 'web', NOW(), NOW()),
    ('view cash collateral details', 'web', NOW(), NOW()),
    ('edit cash collateral', 'web', NOW(), NOW()),
    ('delete cash collateral', 'web', NOW(), NOW());

-- Assign to admin role (change role_id if needed)
INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id 
FROM permissions p, roles r
WHERE p.name IN ('deposit cash collateral', 'withdraw cash collateral', 'view cash collateral details', 'edit cash collateral', 'delete cash collateral')
AND r.name = 'admin';
```

## After Running Commands

1. **Logout and Login** to your application
2. **Clear browser cache**: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)
3. **Refresh the Cash Deposits page**

## Debug: Check if Permissions Work

Run in tinker:

```php
$user = \App\Models\User::find(YOUR_USER_ID);
$user->can('deposit cash collateral'); // Should return true
$user->can('withdraw cash collateral'); // Should return true

// Check what roles user has
$user->roles->pluck('name');

// Check what permissions user has
$user->getAllPermissions()->pluck('name')->filter(fn($n) => str_contains($n, 'collateral'));
```

