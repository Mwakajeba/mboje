<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\Permission;
use Spatie\Permission\Models\Permission as SpatiePermission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Define permissions based on menu structure from MenuSeeder
        $permissions = [
            // Sales
            'view sales',
            'create sales',
            'edit sales',
            'delete sales',
            'approve sales order',
            'view cash sales',
            'create cash sales',
            'edit cash sales',
            'delete cash sales',
            'view sales invoices',
            'create sales invoices',
            'edit sales invoices',
            'delete sales invoices',
            'record sales payment',
            'delete sales payment',
            'view sales invoices stats',

            // Purchases
            'view purchases',
            'create purchases',
            'edit purchases',
            'delete purchases',
            'view cash purchases',
            'create cash purchases',
            'edit cash purchases',
            'delete cash purchases',
            'view purchase invoices',
            'create purchase invoices',
            'edit purchase invoices',
            'delete purchase invoices',
            'record purchase payment',
            'delete purchase payment',
            // Debit Notes (purchases) - ensure these exist before role sync
            'view debit notes',
            'create debit notes',
            'edit debit notes',
            'delete debit notes',
            'approve debit notes',
            'apply debit notes',
            'cancel debit notes',
            // Dashboard
            'view dashboard',
            'view financial reports',
            'view graphs',
            'view inventory value card',
            'view sales today card',
            'view net profit ytd card',
            'view total expenses today card',
            'view total outstanding invoices card',
            'view revenue this month card',

            // Settings
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
            'manage payment terms',
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
            'change branches',
            'assign branches',
            'manage payment voucher approval',

            // Customers
            'view customers',
            'create customer',
            'edit customer',
            'delete customer',
            'view customer profile',
            'manage customer documents',
            'view customer history',
            'approve customer registration',


            // Cash Collaterals
            'view cash deposit types',
            'create cash deposit type',
            'edit cash deposit type',
            'delete cash deposit type',
            'view cash deposit type details',

            'view cash deposits',
            'create cash deposit',
            'edit cash deposit',
            'delete cash deposit',
            'view cash deposit details',
            'deposit cash deposit',
            'withdraw cash deposit',
            'deposit cash collateral',
            'withdraw cash collateral',
            'view cash collateral details',
            'edit cash collateral',
            'delete cash collateral',
            'print cash deposit transactions',

            // Accounting
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

            'view suppliers',
            'create supplier',
            'edit supplier',
            'delete supplier',
            'view supplier details',

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

            'view provisions',
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

            'view bill purchases',
            'create bill purchase',
            'edit bill purchase',
            'delete bill purchase',
            'view bill purchase details',

            'view budgets',
            'create budget',
            'edit budget',
            'delete budget',
            'view budget details',
            'submit budget for approval',
            'approve budget',
            'reject budget',
            'view budget approval history',

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
            'view petty cash units',
            'view inter-account transfers',
            'view cashflow forecasts',
            'view fx rates',
            'view fx revaluation',
            'view fx settings',
            'view accruals prepayments',


            // General Accounting
            'view accounting',
            'view general ledger',
            'manage financial year',
            'delete transaction',
            'edit transaction',

            // Reports
            'view reports',
            'generate reports',
            'export reports',
            'view accounting reports',
            'view sales reports',
            'view sales proformas',
            'create sales proforma',
            'edit sales proforma',
            'delete sales proforma',
            'approve sales proforma',
            'view sales orders',
            'create sales order',
            'edit sales order',
            'delete sales order',
            'approve sales order',
            'view credit notes',
            'create credit notes',
            'edit credit notes',
            'delete credit notes',
            'approve credit notes',
            'apply credit notes',
            'cancel credit notes',
            'view sales invoices',
            'create sales invoices',
            'edit sales invoices',
            'delete sales invoices',
            'view credit notes',
            'create credit notes',
            'edit credit notes',
            'delete credit notes',
            'approve credit notes',
            'apply credit notes',
            'cancel credit notes',
            'view deliveries',
            'create delivery',
            'edit delivery',
            'delete delivery',
            'manage delivery workflow',

            'view customer reports',
            'view inventory reports',
            'view financial report summary',

            // Chat
            // 'view chat',
            // 'send chat message',
            // 'view chat messages',

            // // AI Assistant
            // 'use AI assistant',
            // 'view AI assistant',

            // Analytics & Dashboard
            // 'view analytics',
            // 'view statistics',
            // 'view kpi reports',

            // Menu Management
            'view menus',
            'manage menus',
            'assign menu permissions',

            // User & Staff Management
            'view users',
            'create user',
            'edit user',
            'delete user',
            'assign roles',
            'view user profile',
            'change user status',
            'manage staff',

            // Company & Branch Management
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

            // Collections & Payments
            'view collections',
            'create collection',
            'edit collection',
            'delete collection',
            'process payments',
            'record cash payments',
            'record bank transfers',
            'manage payment schedules',
            'view payment history',
            'generate receipts',

            // Accounting & Financial
            'view accounting',
            'create journal entries',
            'edit journal entries',
            'delete journal entries',
            'view chart of accounts',
            'manage chart of accounts',
            'view bank accounts',
            'view bank reconciliation',
            'perform bank reconciliation',
            'view general ledger',
            'manage financial year',
            'close accounting period',

            // Savings & Deposits
            'view savings accounts',
            'create savings account',
            'edit savings account',
            'delete savings account',
            'process deposits',
            'process withdrawals',

            // Reports & Analytics
            'view reports',
            'generate reports',
            'export reports',
            'view collection report',
            'view delinquency report',
            'view financial statements',
            'view FINANCIAL REPORT SUMMARY',
            'view client reports',
            'view branch performance',
            'view staff performance',
            'view audit reports',
            'view compliance reports',

            // Risk Management
            'view risk assessment',
            'create risk assessment',
            'edit risk assessment',
            'view credit scores',
            'manage collateral',
            'view insurance policies',

            // Settings & Configuration
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
            'manage payment terms',
            'view backup settings',
            'manage filetype setting',
            'create backup',
            'restore backup',
            'delete backup',
            'manage user setting',
            'manage branch setting',
            'manage campany setting',
            'manage inventory settings',
            'manage inventory locations',

            // Inventory Management
            'view inventory items',
            'manage inventory items',
            'create inventory items',
            'edit inventory items',
            'delete inventory items',
            'view inventory categories',
            'manage inventory categories',
            'create inventory categories',
            'edit inventory categories',
            'delete inventory categories',
            'view inventory movements',
            'manage inventory movements',
            'create inventory movements',
            'edit inventory movements',
            'delete inventory movements',
            'view inventory adjustments',
            'create inventory adjustments',
            'edit inventory adjustments',
            'delete inventory adjustments',
            'approve inventory adjustments',
            'view inventory transfers',
            'view inventory transfer',
            'create inventory transfers',
            'edit inventory transfers',
            'delete inventory transfers',
            'view stock on hand report',
            'view movement register report',
            'view reorder report',
            'view over understock report',
            'view item ledger report',
            'view profit margin report',
            // Transfer Requests
            'view transfer requests',
            'create transfer requests',
            'edit transfer requests',
            'approve transfer requests',
            'reject transfer requests',
            // Opening Balance
            'manage inventory opening balances',
            // Write-offs
            'view inventory write-offs',
            'create inventory write-offs',
            'edit inventory write-offs',
            'delete inventory write-offs',
            'manage inventory counts',

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

            'delete role',
            'edit role',
            'view role',
            'create role',
            'create permission',
            'view charges',


            // AI Assistant
            'use AI assistant',
            'view AI assistant',

            // Dashboard & Analytics
            'view dashboard',
            'view analytics',
            'view statistics',
            'view kpi reports',

            // Menu Management
            'view menus',
            'manage menus',
            'assign menu permissions',
            'view logs activity',

            //bank accounts
            'view bank accounts',
            'create bank account',
            'edit bank account',
            'delete bank account',
            'view bank account details',
            'manage bank account transactions',

            ////CASH COLLATERAL PERMISSION////
            'delete transaction',
            'edit transaction',
            'deposit cash deposit',
            'withdraw cash deposit',
            'deposit cash collateral',
            'withdraw cash collateral',
            'view cash collateral details',
            'edit cash collateral',
            'delete cash collateral',
            'edit cash deposit',
            'delete cash deposit',
            'print cash deposit transations',
            'view cash deposits',
            'create cash deposit',

            ///group permission

            'view groups',
            'create group',
            'delete group',
            'edit group',
            'view group details',

            // Hotel & Property Management
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
            'view hotel property settings',
            'edit hotel property settings',
            'manage hotel property settings',

            //Assets Management
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
            // Asset Movements
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
            'manage disposal reason codes',

            // Other Income Management
            'view-other-income',
            'create-other-income',
            'edit-other-income',
            'delete-other-income',
            'approve-other-income',

            //HR & Payroll

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
            'view leave type details',
            'create leave type',
            'edit leave type',
            'delete leave type',
            'manage leave types',
            'view leave applications',
            'approve leave application',
            'reject leave application',
            'manage leave settings',

        ];

        // Create or update permissions in both custom Permission table and Spatie table
        foreach ($permissions as $permissionName) {
            // Create in custom Permission table
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ], [
                'permission_group_id' => null // Will be set by PermissionGroupsSeeder
            ]);

            // Also ensure it exists in Spatie permissions table
            try {
                SpatiePermission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);
            } catch (\Exception $e) {
                // Permission might already exist, ignore
            }
        }

        // Collect all permissions that might be referenced in roles but not in main list
        $additionalPermissions = [
            'view hr payroll',
            'manage hr payroll',
            'view leave types',
            'view leave type details',
            'manage leave types',
            'view leave requests',
            'manage leave requests',
            'create leave request',
            'edit leave request',
            'delete leave request',
            'submit leave request',
            'view leave request details',
            'cancel leave request',
            'approve leave request',
            'reject leave request',
            'view leave balances',
            'view employee leave balance',
            'view leave reports',
            'view leave history',
        ];

        $allReferencedPermissions = array_merge($permissions, $additionalPermissions);

        // Ensure all referenced permissions exist in Spatie table
        foreach ($allReferencedPermissions as $permName) {
            try {
                SpatiePermission::firstOrCreate([
                    'name' => $permName,
                    'guard_name' => 'web'
                ]);
            } catch (\Exception $e) {
                // Permission might already exist, ignore
            }
        }

        // Create system roles with their permissions
        $this->createSystemRoles();

        // Assign admin role to user with ID 1 (if exists)
        $user = User::find(1);
        if ($user && !$user->hasRole('admin')) {
            $user->assignRole('admin');
        }
    }

    private function createSystemRoles()
    {
        // Ensure debit note permissions exist in Spatie table before any sync
        foreach (['view', 'create', 'edit', 'delete', 'approve', 'apply', 'cancel'] as $action) {
            SpatiePermission::findOrCreate("$action debit notes", 'web');
        }
        // Super Admin Role - All permissions
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web'
        ]);
        $superAdminRole->description = 'Full system access with all Organization permissions';
        $superAdminRole->save();
        // Sync all Spatie permissions
        $allSpatiePermissions = SpatiePermission::all();
        $superAdminRole->syncPermissions($allSpatiePermissions);

        // Admin Role - Company level admin
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        $adminRole->description = 'Organization administrator with full access';
        $adminRole->save();

        // Sync all Spatie permissions
        $adminRole->syncPermissions($allSpatiePermissions);

        // Manager Role - Branch level management
        $managerRole = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'web'
        ]);
        $managerRole->description = 'Branch manager with operational organization access';
        $managerRole->save();

        $managerPermissions = [
            'view dashboard',
            // Sales
            'view sales',
            'create sales',
            'edit sales',
            'delete sales',
            'view cash sales',
            'create cash sales',
            'edit cash sales',
            'delete cash sales',
            'view sales invoices',
            'create sales invoices',
            'edit sales invoices',
            'delete sales invoices',
            'record sales payment',
            'delete sales payment',
            'view sales reports',
            'view sales proformas',
            'create sales proforma',
            'edit sales proforma',
            'delete sales proforma',
            'approve sales proforma',
            'view sales orders',
            'create sales order',
            'edit sales order',
            'delete sales order',
            'approve sales order',
            'view credit notes',
            'create credit notes',
            'edit credit notes',
            'delete credit notes',
            'approve credit notes',
            'apply credit notes',
            'cancel credit notes',
            'view deliveries',
            'create delivery',
            'edit delivery',
            'delete delivery',
            'manage delivery workflow',

            // Purchases
            'view purchases',
            'create purchases',
            'edit purchases',
            'delete purchases',
            'view cash purchases',
            'create cash purchases',
            'edit cash purchases',
            'delete cash purchases',
            'view purchase invoices',
            'create purchase invoices',
            'edit purchase invoices',
            'delete purchase invoices',
            'record purchase payment',
            'delete purchase payment',
            'view debit notes',
            'create debit notes',
            'edit debit notes',
            'delete debit notes',
            'approve debit notes',
            'apply debit notes',
            'cancel debit notes',
            'view users',
            'create user',
            'edit user',
            'view user profile',
            'manage staff',
            'view branches',
            'edit branch',
            'view customers',
            'create customer',
            'edit customer',
            'view customer profile',
            'manage customer documents',
            'view customer history',
            'approve customer registration',

            // Cash Deposits
            'view cash deposits',
            'create cash deposit',
            'edit cash deposit',
            'delete cash deposit',
            'view cash deposit details',
            'deposit cash deposit',
            'withdraw cash deposit',
            'deposit cash collateral',
            'withdraw cash collateral',
            'view cash collateral details',
            'edit cash collateral',
            'delete cash collateral',
            'print cash deposit transactions',
            'view cash deposit types',
            'create cash deposit type',
            'edit cash deposit type',
            'delete cash deposit type',
            'view cash deposit type details',

            'view groups',
            'create group',
            'edit group',
            'view group details',
            'view collections',
            'create collection',
            'edit collection',
            'process payments',
            'record cash payments',
            'record bank transfers',
            'manage payment schedules',
            'view payment history',
            'generate receipts',
            'view accounting',
            'create journal entries',
            'edit journal entries',
            'view chart accounts',
            'view bank accounts',
            'view bank reconciliation',
            'view general ledger',
            'view reports',
            'generate reports',
            'export reports',
            'view collection report',
            'view delinquency report',
            'view financial statements',
            'view financial report summary',
            'view client reports',
            'view inventory reports',
            'view inventory write-offs',
            'view branch performance',
            'view staff performance',
            'view settings',
            'view backup settings',
            'create backup',
            'use AI assistant',
            'view AI assistant',
            'view analytics',
            'view statistics',
            'view kpi reports',
            'view menus',

            // Purchase Quotations
            'view purchase quotations',
            'create purchase quotations',
            'edit purchase quotations',
            'view purchase quotation details',
            'send purchase quotations',

            // Hotel & Property Management
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
            'view hotel property settings',
            'edit hotel property settings',
            'manage hotel property settings',

            // HR & Payroll - Leave Management
            'view hr payroll',
            'manage hr payroll',
            'view leave types',
            'view leave type details',
            'create leave type',
            'edit leave type',
            'delete leave type',
            'manage leave types',
            'view leave requests',
            'create leave request',
            'submit leave request',
            'approve leave request',
            'reject leave request',
            'view leave balances',
            'view employee leave balance',
            'view leave reports',
            'view leave history',

            // Assets Management
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
            'manage disposal reason codes',
        ];
        $managerRole->syncPermissions($managerPermissions);

        // User Role - Standard user
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web'
        ]);
            $userRole->description = 'Standard organization user with basic access';
        $userRole->save();

        $userPermissions = [
            'view dashboard',
            // Sales
            'view sales',
            'view cash sales',

            // Purchases
            'view purchases',
            'view cash purchases',
            'view debit notes',
            'view users',
            'view user profile',
            'view branches',
            'view customers',
            'view customer profile',
            'view customer history',
            'view groups',
            'view group details',
            'view collections',
            'view payment history',
            'view accounting',
            'create journal entries',
            'view chart accounts',
            'view bank accounts',
            'view settings',
            'use AI assistant',
            'view AI assistant',
            'view reports',
            'view collection report',
            'view customer reports',
            'view inventory reports',
            'view inventory write-offs',
            'view statistics',
            'view menus',

            // Purchase Quotations
            'view purchase quotations',
            'view purchase quotation details',

            // Hotel & Property Management (view only)
            'view hotel management',
            'view rooms',
            'view room details',
            'view bookings',
            'view booking details',
            'view guests',
            'view guest details',
            'view hotel reports',
            'view real estate',
            'view properties',
            'view property details',
            'view leases',
            'view lease details',
            'view tenants',
            'view tenant details',
            'view property reports',

            // HR & Payroll - Leave Management (basic)
            'view leave requests',
            'create leave request',
            'submit leave request',
            'view leave balances',
            'view employee leave balance',

            // Assets Management (view only)
            'view assets',
            'view asset details',
            'view asset categories',
            'view asset category details',
            'view depreciation schedules',
            'view depreciation schedule details',
            'view asset reports',
            'view asset movements',

            // Asset Revaluations (view only)
            'view asset revaluations',

            // Asset Impairments (view only)
            'view asset impairments',

            // Asset Disposals (view only)
            'view asset disposals',
        ];
        $userRole->syncPermissions($userPermissions);

        // Viewer Role - Read-only access
        $viewerRole = Role::firstOrCreate([
            'name' => 'viewer',
            'guard_name' => 'web'
        ]);
        $viewerRole->description = 'Read-only access to organization data';
        $viewerRole->save();

        $viewerPermissions = [
            'view dashboard',
            'view users',
            'view user profile',
            'view branches',
            'view customers',
            'view customer profile',
            'view customer history',
            'view groups',
            'view group details',
            'view collections',
            'view payment history',
            'view accounting',
            'view chart accounts',
            'view bank accounts',
            'view settings',
            'view AI assistant',
            'view reports',
            'view collection report',
            'view customer reports',
            'view statistics',
            'view menus',

            // Assets Management (view only)
            'view assets',
            'view asset details',
            'view asset categories',
            'view asset category details',
            'view depreciation schedules',
            'view depreciation schedule details',
            'view asset reports',
            'view asset movements',

            // Asset Revaluations (view only)
            'view asset revaluations',

            // Asset Impairments (view only)
            'view asset impairments',

            // Asset Disposals (view only)
            'view asset disposals',
        ];
        $viewerRole->syncPermissions($viewerPermissions);

        // Employee Role - Basic employee access
        $employeeRole = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web'
        ]);
        $employeeRole->description = 'Basic employee with limited access to personal and work-related features';
        $employeeRole->save();

        $employeePermissions = [
            'view dashboard',
            'view user profile',
            'view branches',
            'view customers',
            'view customer profile',
            'view customer history',
            'view groups',
            'view group details',
            'view collections',
            'view payment history',
            'view accounting',
            'view chart accounts',
            'view bank accounts',
            'view settings',
            'view AI assistant',
            'view reports',
            'view collection report',
            'view customer reports',
            'view inventory reports',
            'view statistics',
            'view menus',
            'view purchase quotations',
            'view purchase quotation details',
            'view hotel management',
            'view rooms',
            'view room details',
            'view bookings',
            'view booking details',
            'view guests',
            'view guest details',
            'view hotel reports',
            'view real estate',
            'view properties',
            'view property details',
            'view leases',
            'view lease details',
            'view tenants',
            'view tenant details',
            'view property reports',

            // HR & Payroll - Leave Management (employee self-service)
            'view leave requests',
            'create leave request',
            'edit leave request',
            'delete leave request',
            'submit leave request',
            'view leave request details',
            'cancel leave request',
            'view leave balances',
            'view employee leave balance',
            'view leave history',

            // Assets Management (view only)
            'view assets',
            'view asset details',
            'view asset categories',
            'view asset category details',
            'view depreciation schedules',
            'view depreciation schedule details',
            'view asset reports',
            'view asset movements',

            // Asset Revaluations (view only)
            'view asset revaluations',

            // Asset Impairments (view only)
            'view asset impairments',

            // Asset Disposals (view only)
            'view asset disposals',
        ];
        $employeeRole->syncPermissions($employeePermissions);
    }
}
