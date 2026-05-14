<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\Role;

class MenuSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            $this->command->warn('Admin role not found.');
            return;
        }

        $entities = [
            'Dashboard' => [
                'icon' => 'bx bx-home',
                'visibleRoutes' => [
                    ['name' => 'Dashboard', 'route' => 'dashboard'],
                    ['name' => 'Analytics', 'route' => 'analytics.index'],
                ],
                'hiddenRoutes' => [],
            ],
            'Accounting' => [
                'icon' => 'bx bx-calculator',
                'visibleRoutes' => [
                    ['name' => 'Accounting Management', 'route' => 'accounting.index'],
                   
                ],
                'hiddenRoutes' => [
                    'accounting.chart-accounts.create',
                    'accounting.chart-accounts.edit',
                    'accounting.chart-accounts.destroy',
                    'accounting.journals.edit',
                    'accounting.journals.destroy',
                    'accounting.journals.create',
                    'accounting.journals.show',
                    'accounting.fx-rates.create',
                    'accounting.fx-rates.edit',
                    'accounting.fx-rates.import',
                    'accounting.fx-rates.lock',
                    'accounting.fx-rates.unlock',
                    'accounting.fx-rates.process-import',
                    'accounting.fx-rates.download-sample',
                    'accounting.fx-rates.get-rate',
                    'accounting.fx-revaluation.create',
                    'accounting.fx-revaluation.preview',
                    'accounting.fx-revaluation.store',
                    'accounting.fx-revaluation.show',
                    'accounting.fx-revaluation.reverse',
                    'accounting.fx-settings.update',
                    'accounting.accruals-prepayments.create',
                    'accounting.accruals-prepayments.edit',
                    'accounting.accruals-prepayments.destroy',
                    'accounting.accruals-prepayments.show',
                    'accounting.accruals-prepayments.submit',
                    'accounting.accruals-prepayments.approve',
                    'accounting.accruals-prepayments.reject',
                    'accounting.accruals-prepayments.post-journal',
                    'accounting.accruals-prepayments.post-all-pending',
                    'accounting.accruals-prepayments.amortisation-schedule',
                    'accounting.accruals-prepayments.export-pdf',
                    'accounting.accruals-prepayments.export-excel'
                ],
            ],
            'Inventory' => [
                'icon' => 'bx bx-package',
                'visibleRoutes' => [
                    ['name' => 'Inventory Management', 'route' => 'inventory.index'],
                ],
                'hiddenRoutes' => ['inventory.items.index', 'inventory.items.create', 'inventory.items.edit', 'inventory.items.destroy', 'inventory.items.show', 'inventory.categories.index', 'inventory.categories.create', 'inventory.categories.edit', 'inventory.categories.destroy', 'inventory.movements.index', 'inventory.movements.create', 'inventory.movements.edit', 'inventory.movements.destroy'],
            ],

            'Cash Deposits' => [
                'icon' => 'bx bx-outline',
                'visibleRoutes' => [
                    ['name' => 'Cash Deposit Accounts', 'route' => 'cash_collateral_types.index'],
                    ['name' => 'Cash Deposits', 'route' => 'cash_collaterals.index'],
                ],
                'hiddenRoutes' => ['cash_collateral_types.create', 'cash_collateral_types.edit', 'cash_collateral_types.destroy', 'cash_collateral_types.show', 'cash_collaterals.create', 'cash_collaterals.edit', 'cash_collaterals.destroy', 'cash_collaterals.show'],
            ],

            'Sales' => [
                'icon' => 'bx bx-shopping-bag',
                'visibleRoutes' => [
                    ['name' => 'Sales Management', 'route' => 'sales.index'],
                ],
                'hiddenRoutes' => ['sales.proformas.index', 'sales.proformas.create', 'sales.proformas.edit', 'sales.proformas.destroy', 'sales.proformas.show', 'sales.proformas.store', 'sales.proformas.update', 'sales.test-auth'],
            ],
            'Purchases' => [
                'icon' => 'bx bx-shopping-bag',
                'visibleRoutes' => [
                    ['name' => 'Purchases Management', 'route' => 'purchases.index'],
                ],
                'hiddenRoutes' => [
                    'purchases.quotations.index',
                    'purchases.quotations.create',
                    'purchases.quotations.edit',
                    'purchases.quotations.destroy',
                    'purchases.quotations.show'
                ],
            ],

            'Hotel & Property Management' => [
                'icon' => 'bx bx-building-house',
                'visibleRoutes' => [
                    ['name' => 'Hotel Management', 'route' => 'hotel.management.index'],
                    ['name' => 'Real Estate', 'route' => 'real.estate.index'],
                    ['name' => 'Property Settings', 'route' => 'hotel.property.settings'],
                ],
                'hiddenRoutes' => [
                    // Hotel Management Routes
                    'rooms.index',
                    'rooms.create',
                    'rooms.edit',
                    'rooms.destroy',
                    'rooms.show',
                    'bookings.index',
                    'bookings.create',
                    'bookings.edit',
                    'bookings.destroy',
                    'bookings.show',
                    'guests.index',
                    'guests.create',
                    'guests.edit',
                    'guests.destroy',
                    'guests.show',
                    'hotel.reports.index',

                    // Real Estate Routes
                    'properties.index',
                    'properties.create',
                    'properties.edit',
                    'properties.destroy',
                    'properties.show',
                    'leases.index',
                    'leases.create',
                    'leases.edit',
                    'leases.destroy',
                    'leases.show',
                    'tenants.index',
                    'tenants.create',
                    'tenants.edit',
                    'tenants.destroy',
                    'tenants.show',
                    'property.reports.index',
                ],
            ],

            'Reports' => [
                'icon' => 'bx bx-file',
                'visibleRoutes' => [
                    ['name' => 'Accounting Reports', 'route' => 'reports.index'],
                    ['name' => 'Inventory Reports', 'route' => 'inventory.reports.index'],
                    ['name' => 'Sales Reports', 'route' => 'sales.reports.index'],
                    ['name' => 'Purchase Reports', 'route' => 'reports.purchases'],
                    ['name' => 'Hotel Reports', 'route' => 'hotel.reports.index'],
                ],
                'hiddenRoutes' => [],
            ],

            'Settings' => [
                'icon' => 'bx bx-cog',
                'visibleRoutes' => [
                    ['name' => 'General Settings', 'route' => 'settings.index'],
                ],
                'hiddenRoutes' => ['settings.company', 'settings.branches', 'settings.user', 'settings.system', 'settings.backup', 'settings.branches.create', 'settings.branches.edit', 'settings.branches.destroy', 'settings.filetypes.index', 'settings.filetypes.create', 'settings.filetypes.edit', 'settings.filetypes.destroy', 'settings.inventory.index', 'settings.inventory.update', 'settings.inventory.locations.index', 'settings.inventory.locations.create', 'settings.inventory.locations.edit', 'settings.inventory.locations.destroy'],
            ],
            // 'Chat' => [
            //     'icon' => 'bx bx-message',
            //     'visibleRoutes' => [
            //         ['name' => 'Chat', 'route' => 'chat.index'],
            //     ],
            //     'hiddenRoutes' => ['chat.messages', 'chat.send'],
            // ],

            // Add Change Branch menu under Dashboard
            'Change Branch' => [
                'icon' => 'bx bx-transfer',
                'visibleRoutes' => [
                    ['name' => 'Change Branch', 'route' => 'change-branch'],
                ],
                'hiddenRoutes' => [],
            ],

        ];

        foreach ($entities as $parentName => $data) {
            $parent = Menu::firstOrCreate([
                'name' => $parentName,
                'route' => null,
                'parent_id' => null,
                'icon' => $data['icon'],
            ]);

            $menuIds = [$parent->id];

            // Only visible menu entries
            foreach ($data['visibleRoutes'] as $child) {
                $childMenu = Menu::updateOrCreate(
                    [
                        'name' => $child['name'],
                        'parent_id' => $parent->id,
                    ],
                    [
                        'route' => $child['route'],
                        'icon' => 'bx bx-right-arrow-alt',
                    ]
                );

                $menuIds[] = $childMenu->id;
            }

            // Hidden permission-only routes (not shown in menu)
            // These routes are for permissions only and should not be created as menu entries
            // They are handled by the permission system directly

            $superAdminRole = Role::where('name', 'super-admin')->first();

            $superAdminRole->menus()->syncWithoutDetaching($menuIds);

            $adminRole->menus()->syncWithoutDetaching($menuIds);
        }
    }
}
