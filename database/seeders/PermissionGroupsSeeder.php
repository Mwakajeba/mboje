<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\PermissionGroup;

class PermissionGroupsSeeder extends Seeder
{
    public function run()
    {
        // Create permission groups based on menu structure
        $groups = [
            [
                'name' => 'dashboard',
                'display_name' => 'Dashboard',
                'description' => 'Dashboard and overview permissions',
                'color' => '#007bff',
                'icon' => 'bx bx-home',
                'sort_order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'accounting',
                'display_name' => 'Accounting',
                'description' => 'Accounting and financial management permissions',
                'color' => '#dc3545',
                'icon' => 'bx bx-calculator',
                'sort_order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'inventory',
                'display_name' => 'Inventory',
                'description' => 'Inventory management permissions',
                'color' => '#28a745',
                'icon' => 'bx bx-package',
                'sort_order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'production',
                'display_name' => 'Production',
                'description' => 'Production management permissions',
                'color' => '#ffc107',
                'icon' => 'bx bx-money',
                'sort_order' => 4,
                'is_active' => true
            ],
            [
                'name' => 'cash_deposits',
                'display_name' => 'Cash Deposits',
                'description' => 'Cash deposit management permissions',
                'color' => '#17a2b8',
                'icon' => 'bx bx-wallet',
                'sort_order' => 5,
                'is_active' => true
            ],
            [
                'name' => 'sales',
                'display_name' => 'Sales',
                'description' => 'Sales management permissions',
                'color' => '#17a2b8',
                'icon' => 'bx bx-cart',
                'sort_order' => 6,
                'is_active' => true
            ],
            [
                'name' => 'purchases',
                'display_name' => 'Purchases',
                'description' => 'Purchase management permissions',
                'color' => '#17a2b8',
                'icon' => 'bx bx-cart-download',
                'sort_order' => 7,
                'is_active' => true
            ],
            [
                'name' => 'reports',
                'display_name' => 'Reports',
                'description' => 'Reporting and analytics permissions',
                'color' => '#6610f2',
                'icon' => 'bx bx-bar-chart-alt-2',
                'sort_order' => 8,
                'is_active' => true
            ],
            [
                'name' => 'hotel_property',
                'display_name' => 'Hotel & Property Management',
                'description' => 'Hotel and property management permissions',
                'color' => '#fd7e14',
                'icon' => 'bx bx-building-house',
                'sort_order' => 9,
                'is_active' => true
            ],
            [
                'name' => 'assets',
                'display_name' => 'Assets',
                'description' => 'Assets and fixed asset management permissions',
                'color' => '#20c997',
                'icon' => 'bx bx-archive',
                'sort_order' => 11,
                'is_active' => true
            ],
            [
                'name' => 'settings',
                'display_name' => 'Settings',
                'description' => 'System settings and configuration permissions',
                'color' => '#6c757d',
                'icon' => 'bx bx-cog',
                'sort_order' => 10,
                'is_active' => true
            ],
            [
                'name' => 'hr_payroll',
                'display_name' => 'HR & Payroll',
                'description' => 'Permissions related to HR management, payroll, and leave management',
                'color' => '#0dcaf0',
                'icon' => 'bx bx-user-check',
                'sort_order' => 11,
            ],
        ];

        // Create permission groups
        foreach ($groups as $groupData) {
            PermissionGroup::firstOrCreate(
                ['name' => $groupData['name']],
                $groupData
            );
        }

        // Define permission groups mapping based on menu structure
        $groupMapping = [
            'dashboard' => [
                'view dashboard',
                'view financial report',
                'view charges',
                'view journals',
                'view payments',
                'view receipts',
                'view graphs',
                'view inventory value card',
                'view recent activities',
                'view sales today card',
                'view net profit ytd card',
                'view total expenses today card',
                'view total outstanding invoices card',
                'view revenue this month card'
            ],

            'accounting' => [
                'view account class groups',
                'create account class group',
                'edit account class group',
                'delete account class group',
                'view account class group details',
                'view chart accounts',
                'create chart account',
                'edit chart account',
                'delete chart account',
                'view chart account details',
                'manage chart of accounts',
                'view journals',
                'create journal',
                'edit journal',
                'delete journal',
                'view journal details',
                'view payment vouchers',
                'create payment voucher',
                'edit payment voucher',
                'delete payment voucher',
                'view payment voucher details',
                'view receipt vouchers',
                'create receipt voucher',
                'edit receipt voucher',
                'delete receipt voucher',
                'view receipt voucher details',
                'view bank accounts',
                'create bank account',
                'edit bank account',
                'delete bank account',
                'view bank account details',
                'manage bank account transactions',
                'view bank reconciliation',
                'create bank reconciliation',
                'edit bank reconciliation',
                'delete bank reconciliation',
                'view bank reconciliation details',
                'perform bank reconciliation',
                'submit bank reconciliation for approval',
                'approve bank reconciliation',
                'reject bank reconciliation',
                'view bank reconciliation approval history',
                'view budgets',
                'create budget',
                'edit budget',
                'delete budget',
                'view budget details',
                'submit budget for approval',
                'approve budget',
                'reject budget',
                'view budget approval history',
                'view petty cash units',
                'view inter-account transfers',
                'view cashflow forecasts',
                'view fx rates',
                'view fx revaluation',
                'view fx settings',
                'view accruals prepayments',
                'view provisions',
            ],
            'inventory' => [
                'view items',
                'create item',
                'edit item',
                'delete item',
                'view item details',
                'view categories',
                'create category',
                'edit category',
                'delete category',
                'view locations',
                'create location',
                'edit location',
                'delete location',
                'create movements',
                'view movements',
                'edit movements',
                'delete movements',
                'view movement details',
                'manage inventory',
                'view transfers',
                'create transfer',
                'edit transfer',
                'delete transfer',
                'view transfer details',
                'view adjustments',
                'create inventory categories',
                'edit inventory categories',
                'delete inventory categories',
                'view inventory transfers',
                'view inventory transfer',
                'create inventory transfers',
                'edit inventory transfers',
                'delete inventory transfers',
                'view inventory adjustments',
                'create inventory adjustments',
                'edit inventory adjustments',
                'delete inventory adjustments',
                'approve inventory adjustments',
                'view adjustment details',
                'manage inventory categories',
                'view transfer requests',
                'create transfer requests',
                'edit transfer requests',
                'manage inventory settings',
                'approve transfer requests',
                'reject transfer requests',
                'manage inventory locations',
                'manage inventory opening balances',
                'view inventory reports',
                'view inventory adjustments',
                'manage inventory movements',
                'manage inventory items',
                'view inventory write-offs',
                'create inventory write-offs',
                'edit inventory write-offs',
                'delete inventory write-offs',
                'manage inventory counts',
            ],
            'production' => [
                'view bills of materials',
                'create bill of material',
                'edit bill of material',
                'delete bill of material',
                'view bill of material details',
                'view production orders',
                'create production order',
                'edit production order',
                'delete production order',
                'view production order details',
                'manage production processes',
                'view work centers',
                'create work center',
                'edit work center',
                'delete work center',
                'view work center details',
                'manage work centers'
            ],
            'cash_deposits' => [
                'view cash deposit types',
                'create cash deposit type',
                'edit cash deposit type',
                'delete cash deposit type',
                'view cash deposit type details',
                'view cash deposits',
                'create cash deposit',
                'edit cash deposit',
                'delete cash deposit',
                'deposit cash collateral',
                'withdraw cash collateral',
                'view cash collateral details',
                'edit cash collateral',
                'delete cash collateral',
                'view cash deposit details',
                'deposit cash deposit',
                'withdraw cash deposit',
                'print cash deposit transactions'
            ],

            'sales' => [
                // General Sales Permissions
                'view sales',
                'create sales',
                'edit sales',
                'delete sales',

                // Sales Invoices
                'view sales invoices',
                'create sales invoices',
                'edit sales invoices',
                'delete sales invoices',
                'record sales payment',
                'delete sales payment',

                // Cash Sales
                'view cash sales',
                'create cash sales',
                'edit cash sales',
                'delete cash sales',

                // Sales Reports
                'view sales reports',

                // Sales Proformas
                'view sales proformas',
                'create sales proforma',
                'edit sales proforma',
                'delete sales proforma',
                'approve sales proforma',

                // Sales Orders
                'view sales orders',
                'create sales order',
                'edit sales order',
                'delete sales order',
                'approve sales order',

                // Credit Notes
                'view credit notes',
                'create credit notes',
                'edit credit notes',
                'delete credit notes',
                'approve credit notes',
                'apply credit notes',
                'cancel credit notes',

                // Deliveries
                'view deliveries',
                'create delivery',
                'edit delivery',
                'delete delivery',
                'manage delivery workflow',

                // Customers (Sales-related)
                'view customers',
                'create customer',
                'edit customer',
                'delete customer',
                'view customer profile',
                'manage customer documents',
                'view customer history',
                'approve customer registration',
                'view customer reports',
                'view sales invoices stats',
            ],

            'purchases' => [
                // General Purchase Permissions
                'view purchases',
                'create purchases',
                'edit purchases',
                'delete purchases',

                // Purchase Quotations
                'view purchase quotations',
                'create purchase quotations',
                'edit purchase quotations',
                'delete purchase quotations',
                'approve purchase quotations',
                'reject purchase quotations',
                'send purchase quotations',
                'view purchase quotation details',
                'manage purchase quotations',

                // Purchase Orders
                'view purchase orders',
                'create purchase orders',
                'edit purchase orders',
                'delete purchase orders',
                'approve purchase orders',
                'view purchase order details',
                'manage purchase orders',

                // Purchase Invoices
                'view purchase invoices',
                'create purchase invoices',
                'edit purchase invoices',
                'delete purchase invoices',
                'record purchase payment',
                'delete purchase payment',

                // Cash Purchases
                'view cash purchases',
                'create cash purchases',
                'edit cash purchases',
                'delete cash purchases',

                // Debit Notes
                'view debit notes',
                'create debit notes',
                'edit debit notes',
                'delete debit notes',
                'approve debit notes',
                'apply debit notes',
                'cancel debit notes',

                // Bill Purchases
                'view bill purchases',
                'create bill purchase',
                'edit bill purchase',
                'delete bill purchase',
                'view bill purchase details',

                // Suppliers (Purchase-related)
                'view suppliers',
                'create supplier',
                'edit supplier',
                'delete supplier',
                'view supplier details',
            ],

            'reports' => [
                'view financial reports',
                'view balance sheet report',
                'view income statement report',
                'view cash book report',
                'view trial balance report',
                'view cash flow report',
                'view general ledger report',
                'view expenses summary report',
                'view accounting notes report',
                'view changes in equity report',
                'view other income report',
                'view budget report',
                'view bank reconciliation report',
                'view accounting reports',
                'view customer reports',
                'view branch performance',
                'view staff performance',
                'view collection report',
                'view stock on hand report',
                'view movement register report',
                'view reorder report',
                'view over understock report',
                'view item ledger report',
                'view profit margin report',
            ],

            'hotel_property' => [
                // Hotel Management
                'view hotel management',
                'manage hotel management',
                'view rooms',
                'create room',
                'edit room',
                'delete room',
                'view room details',
                'view bookings',
                'create booking',
                'edit booking',
                'delete booking',
                'view booking details',
                'view guests',
                'create guest',
                'edit guest',
                'delete guest',
                'view guest details',
                'view hotel reports',

                // Property Management
                'view real estate',
                'manage real estate',
                'view properties',
                'create property',
                'edit property',
                'delete property',
                'view property details',
                'view leases',
                'create lease',
                'edit lease',
                'delete lease',
                'view lease details',
                'view tenants',
                'create tenant',
                'edit tenant',
                'delete tenant',
                'view tenant details',
                'view property reports',

                // Hotel & Property Settings
                'view hotel property settings',
                'edit hotel property settings',
                'manage hotel property settings',
            ],

            'assets' => [
                'view assets',
                'create asset',
                'edit asset',
                'delete asset',
                'view asset details',
                'view asset categories',
                'create asset category',
                'edit asset category',
                'delete asset category',
                'view asset category details',
                'manage asset categories',
                'view depreciation schedules',
                'create depreciation schedule',
                'edit depreciation schedule',
                'delete depreciation schedule',
                'view depreciation schedule details',
                'manage depreciation schedules',
                'process scheduled depreciation',
                'view asset reports',
                'manage asset settings',
                'view asset movements',
                'create asset movements',
                'approve asset movements',
                'complete asset movements',
                'reject asset movements',
                // Asset Revaluations
                'view asset revaluations',
                'create asset revaluations',
                'edit asset revaluations',
                'delete asset revaluations',
                'approve asset revaluations',
                'post asset revaluations',
                'submit asset revaluations',
                'view revaluation settings',
                'edit revaluation settings',
                'manage revaluation settings',
                // Asset Impairments
                'view asset impairments',
                'create asset impairments',
                'edit asset impairments',
                'delete asset impairments',
                'approve asset impairments',
                'post asset impairments',
                'submit asset impairments',
                'reverse asset impairments',
                // Asset Disposals
                'view asset disposals',
                'create asset disposals',
                'edit asset disposals',
                'delete asset disposals',
                'approve asset disposals',
                'post asset disposals',
                'submit asset disposals',
                'manage disposal reason codes'
            ],

            'hr_payroll' => [
                // HR & Payroll Permissions
                'view employees',
                'create employee',
                'edit employee',
                'delete employee',
                'view employee profile',
                'manage employee documents',
                'view payrolls',
                'create payroll',
                'edit payroll',
                'delete payroll',
                'process payroll',
                'view payslips',
                'generate payslip',
                'view leave types',
                'create leave type',
                'edit leave type',
                'delete leave type',
                'view leave applications',
                'approve leave application',
                'reject leave application',
                'manage leave settings',
            ],
            'settings' => [
                'view settings',
                'edit settings',
                'manage system settings',
                'view system configurations',
                'edit system configurations',
                'manage system configurations',
                'view system config',
                'edit system config',
                'manage system config',
                'manage role & permission',
                'view backup settings',
                'manage filetype setting',
                'create backup',
                'restore backup',
                'delete backup',
                'manage user setting',
                'manage branch setting',
                'manage company setting',
                'delete role',
                'edit role',
                'view role',
                'create role',
                'create permission',
                'view permission groups',
                'create permission group',
                'edit permission group',
                'delete permission group',
                'view logs activity',
                'view general ledger',
                'manage financial year',
                'close accounting period',
                'delete transaction',
                'edit transaction',
                'view transaction reports',
                'view collection report',
                'view delinquency report',
                'view financial statements',
                'view financial report summary',
                'view menus',
                'manage menus',
                'assign menu permissions',
                'view users',
                'create user',
                'edit user',
                'delete user',
                'assign roles',
                'view user profile',
                'change user status',
                'view companies',
                'create company',
                'edit company',
                'delete company',
                'manage company settings',
                'view branches',
                'create branch',
                'edit branch',
                'delete branch',
                'assign users to branches',
                'view collections',
                'create collection',
                'edit collection',
                'view credit scores',
                'manage company setting',
                'view charges',
                'print cash collateral transactions'
            ],

        ];

        // Update permissions with their groups
        $updatedCount = 0;
        foreach ($groupMapping as $group => $permissionNames) {
            $permissionGroup = PermissionGroup::where('name', $group)->first();

            foreach ($permissionNames as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission && $permissionGroup) {
                    $permission->update(['permission_group_id' => $permissionGroup->id]);
                    $updatedCount++;
                }
            }
        }

        // Set remaining permissions to 'settings' group
        $settingsGroup = PermissionGroup::where('name', 'settings')->first();
        if ($settingsGroup) {
            $remainingPermissions = Permission::whereNull('permission_group_id')->get();
            foreach ($remainingPermissions as $permission) {
                $permission->update(['permission_group_id' => $settingsGroup->id]);
                $updatedCount++;
            }
        }

        $this->command->info("Permission groups created and {$updatedCount} permissions assigned to groups.");
    }
}
