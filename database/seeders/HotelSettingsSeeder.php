<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SystemSetting;
use App\Models\ChartAccount;

class HotelSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use fixed seeded IDs for deterministic defaults (as originally configured)
        $RoomRevenueAccountId = DB::table('chart_accounts')->where('id', 194)->value('id'); // Hotel Room Revenue
        $serviceRevenueAccountId = DB::table('chart_accounts')->where('id', 195)->value('id'); // Service Revenue
        $fbRevenueAccountId = DB::table('chart_accounts')->where('id', 196)->value('id'); // Food and Bevarage Revenue
        $operatingExpenseAccountId = DB::table('chart_accounts')->where('id', 201)->value('id'); // Hotel Operating Expense
        $hotelMaintenanceAccountId = DB::table('chart_accounts')->where('id', 202)->value('id'); // HOtel maintenance expense
        $marketingExpenseAccountId = DB::table('chart_accounts')->where('id', 203)->value('id'); // HOtel Marketing Expenses
        $discountAccountId = DB::table('chart_accounts')->where('id', 172)->value('id'); // Discount Expense (fallback to 172)

        // $discountIncomeAccountId = DB::table('chart_accounts')->where('id', 52)->value('id'); // Discount Income (Revenue)
        // $earlyPaymentDiscountAccountId = DB::table('chart_accounts')->where('id', 172)->value('id'); // Early Payment Discount (Expense)
        // $latePaymentFeesAccountId = DB::table('chart_accounts')->where('id', 100)->value('id'); // Late Payment Fees / Penalty Income (Revenue)
        // $receivableAccountId = DB::table('chart_accounts')->where('id', 6)->value('id'); // Accounts Receivable (Asset)

        // Define inventory settings with proper default account mappings
        $hotelSettings = [
            
            // Default room revenue 
            'hotel_room_revenue_account_id' => [
                'value' => $RoomRevenueAccountId ?: null,
                'type' => 'integer',
                'group' => 'hotel',
                'label' => 'Default room revenue Account',
                'description' => 'Default chart account for room revenue'
            ],
            // Default hotel service revenue
            'hotel_service_revenue_account_id' => [
                'value' => $serviceRevenueAccountId ?: null,
                'type' => 'integer',
                'group' => 'hotel',
                'label' => 'Default hotel service revenue Account',
                'description' => 'Default chart account for hotel service revenue'
            ],
            // Default hotel food and beverage
            'hotel_food_beverage_account_id' => [
                'value' => $fbRevenueAccountId ?: null,
                'type' => 'integer',
                'group' => 'hotel',
                'label' => 'Default hotel food and beverage account',
                'description' => 'Default chart account for hotel food and beverage'
            ],
            // Default operating expense account
            'hotel_operating_expense_account_id' => [
                'value' => $operatingExpenseAccountId ?: null,
                'type' => 'integer',
                'group' => 'hotel',
                'label' => 'Default operating expense account',
                'description' => 'Default chart account for operating expense account'
            ],
            // Default Hotel Maintenance Expense Account
            'hotel_maintenance_expense_account_id' => [
                'value' => $hotelMaintenanceAccountId ?: null,
                'type' => 'integer',
                'group' => 'hotel',
                'label' => 'Default Hotel Maintenance Expense Account',
                'description' => 'Default chart account for Hotel Maintenance Expense Account'
            ],
              // Default Hotel Marketing Expense Account
            'hotel_marketing_expense_account_id' => [
                'value' => $marketingExpenseAccountId ?: null,
                'type' => 'integer',
                'group' => 'hotel',
                'label' => 'Default Hotel Marketing Expense Account',
                'description' => 'Default chart account for Hotel Marketing Expense Account'
            ],
            // Default Discount Account - Expense account for discounts given
            'hotel_discount_expense_account_id' => [
                'value' => $discountAccountId ?: null,
                'type' => 'integer',
                'group' => 'hotel',
                'label' => 'Default Discount Account',
                'description' => 'Default chart account for discount expense'
            ],
          
            // Default Late Payment Fees Account - Revenue
            // 'inventory_default_late_payment_fees_account' => [
            //     'value' => $latePaymentFeesAccountId ?: null,
            //     'type' => 'integer',
            //     'group' => 'inventory',
            //     'label' => 'Default Late Payment Fees Account',
            //     'description' => 'Default chart account for late payment fees income'
            // ],
            // Default Accounts Receivable Account - Asset
            // 'inventory_default_receivable_account' => [
            //     'value' => $receivableAccountId ?: null,
            //     'type' => 'integer',
            //     'group' => 'inventory',
            //     'label' => 'Default Accounts Receivable Account',
            //     'description' => 'Default chart account for trade receivables'
            // ],
            
        ];

        // Save or update each setting
        foreach ($hotelSettings as $key => $settingData) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                $settingData
            );
        }

        $this->command->info('hotel settings seeded successfully!');
    }
} 