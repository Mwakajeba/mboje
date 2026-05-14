<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquityCategoriesIFRSSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates IFRS-compliant equity categories for the Statement of Changes in Equity
     */
    public function run(): void
    {
        // Create IFRS-compliant equity categories (using IDs that don't conflict)
        $categories = [
            [
                'id' => 10,
                'name' => 'Share Capital (IFRS)',
                'description' => 'Ordinary and preference share capital issued',
            ],
            [
                'id' => 11,
                'name' => 'Share Premium (IFRS)',
                'description' => 'Amount received in excess of par value of shares',
            ],
            [
                'id' => 12,
                'name' => 'Revaluation Reserve (IFRS)',
                'description' => 'Surplus on revaluation of property, plant and equipment (IAS 16)',
            ],
            [
                'id' => 13,
                'name' => 'Retained Earnings (IFRS)',
                'description' => 'Accumulated profits retained in the business',
            ],
            [
                'id' => 14,
                'name' => 'Other Reserves (IFRS)',
                'description' => 'Fair value reserves, translation reserves, and other comprehensive income',
            ],
        ];
        
        foreach ($categories as $category) {
            DB::table('equity_categories')->updateOrInsert(
                ['id' => $category['id']],
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
        
        $this->command->info('IFRS equity categories seeded successfully!');
        
        // Now map the equity accounts to the correct categories
        $this->command->info('Mapping equity accounts to categories...');
        
        // Share Capital (Category 10)
        DB::table('chart_accounts')
            ->whereIn('account_code', ['3101', '3103'])
            ->update(['equity_category_id' => 10]);
        
        // Share Premium (Category 11)
        DB::table('chart_accounts')
            ->where('account_code', '3530')
            ->update(['equity_category_id' => 11]);
        
        // Revaluation Reserve (Category 12)
        DB::table('chart_accounts')
            ->where('account_code', '3105')
            ->update(['equity_category_id' => 12]);
        
        // Retained Earnings (Category 13)
        DB::table('chart_accounts')
            ->whereIn('account_code', ['3001', '3002'])
            ->update(['equity_category_id' => 13]);
        
        // Other Reserves (Category 14)
        DB::table('chart_accounts')
            ->where('account_code', '3124')
            ->update(['equity_category_id' => 14]);
        
        // Dividends accounts (keep with original categories or set to Retained Earnings)
        DB::table('chart_accounts')
            ->whereIn('account_code', ['3120', '3125'])
            ->update(['equity_category_id' => 2]); // Dividends Paid category
        
        $this->command->info('Equity accounts mapped successfully!');
        
        // Show summary
        $this->command->info('');
        $this->command->info('Summary of mapped accounts:');
        $mappedAccounts = DB::table('chart_accounts')
            ->join('equity_categories', 'chart_accounts.equity_category_id', '=', 'equity_categories.id')
            ->where('chart_accounts.has_equity', 1)
            ->whereIn('equity_categories.id', [10, 11, 12, 13, 14])
            ->select('chart_accounts.account_code', 'chart_accounts.account_name', 'equity_categories.name as category')
            ->orderBy('equity_categories.id')
            ->orderBy('chart_accounts.account_code')
            ->get();
        
        foreach ($mappedAccounts as $account) {
            $this->command->info("  {$account->account_code} - {$account->account_name} => {$account->category}");
        }
    }
}
