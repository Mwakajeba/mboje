<?php

namespace Database\Seeders;

use App\Models\PermissionGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            // Core company + structure
            CompanySeeder::class,
            BranchSeeder::class,
            RolePermissionSeeder::class,
            PermissionGroupsSeeder::class,
            MenuSeeder::class,
            // Accounting structure + chart of accounts
            AccountClassSeeder::class,
            MainGroupSeeder::class,
            AccountClassGroupSeeder::class,
            ChartAccountSeeder::class,
            CashFlowCategorySeeder::class,
            EquityCategorySeeder::class,
            CurrencySeeder::class,
            // Initialize system settings with defaults FIRST
            SystemSettingSeeder::class,
            // Seed FX + cash deposit related configuration
            // Seed FX settings (Chart of Accounts Configuration)
            FxSettingsSeeder::class,
            // Seed default Cash Deposit / Cash Collateral Types
            CashCollateralTypeSeeder::class,
            // Run inventory settings AFTER chart accounts exist so defaults can be set
            InventorySettingsSeeder::class,
            HotelSettingsSeeder::class,
            // Ensure users exist before seeding inventory locations
            UserSeeder::class,
            InventoryLocationSeeder::class,
            InventoryCategorySeeder::class,
            LocationUserSeeder::class,
            SupplierSeeder::class,
            BankAccountSeeder::class,
            TransportRevenueAccountSeeder::class,
            // Create default one-year subscriptions for all companies
            DefaultSubscriptionSeeder::class,
            // TestInventoryDataSeeder::class,
        ]);
    }
}
