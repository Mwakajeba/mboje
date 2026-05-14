<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Menu;
use App\Models\Role;

// Find the Reports parent menu
$reportsMenu = Menu::where('name', 'Reports')->whereNull('parent_id')->first();

if (!$reportsMenu) {
    echo "Reports menu not found. Please run MenuSeeder first.\n";
    exit(1);
}

// Check if Hotel Reports already exists
$hotelReportsMenu = Menu::where('name', 'Hotel Reports')
    ->where('parent_id', $reportsMenu->id)
    ->first();

if ($hotelReportsMenu) {
    echo "Hotel Reports menu already exists.\n";
    exit(0);
}

// Create the Hotel Reports menu item
$hotelReportsMenu = Menu::create([
    'name' => 'Hotel Reports',
    'route' => 'hotel.reports.index',
    'parent_id' => $reportsMenu->id,
    'icon' => 'bx bx-right-arrow-alt',
]);

// Get admin and super-admin roles
$adminRole = Role::where('name', 'admin')->first();
$superAdminRole = Role::where('name', 'super-admin')->first();

if ($adminRole) {
    $adminRole->menus()->syncWithoutDetaching([$hotelReportsMenu->id]);
    echo "Added Hotel Reports menu to admin role.\n";
}

if ($superAdminRole) {
    $superAdminRole->menus()->syncWithoutDetaching([$hotelReportsMenu->id]);
    echo "Added Hotel Reports menu to super-admin role.\n";
}

echo "Hotel Reports menu item created successfully!\n";
