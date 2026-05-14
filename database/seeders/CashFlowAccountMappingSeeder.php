<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashFlowAccountMappingSeeder extends Seeder
{
    /**
     * Map chart accounts to cash flow categories based on account code ranges
     * This is essential for the cash flow statement to work correctly
     */
    public function run(): void
    {
        $this->command->info('Mapping chart accounts to cash flow categories...');
        $this->command->info('');
        
        // ============================================================================
        // CATEGORY 1: OPERATING ACTIVITIES
        // ============================================================================
        $this->command->info('1. Mapping OPERATING ACTIVITIES accounts...');
        
        // Current Assets (excluding cash) - Operating
        $operating1 = DB::table('chart_accounts')
            ->whereBetween('account_code', [1100, 1499]) // Receivables, Inventory, Prepayments
            ->update([
                'has_cash_flow' => 1,
                'cash_flow_category_id' => 1,
            ]);
        
        // Current Liabilities - Operating
        $operating2 = DB::table('chart_accounts')
            ->whereBetween('account_code', [2100, 2499]) // Payables, Accruals
            ->update([
                'has_cash_flow' => 1,
                'cash_flow_category_id' => 1,
            ]);
        
        // Revenue accounts - Operating
        $operating3 = DB::table('chart_accounts')
            ->whereBetween('account_code', [4000, 4999])
            ->update([
                'has_cash_flow' => 1,
                'cash_flow_category_id' => 1,
            ]);
        
        // Expense accounts - Operating
        $operating4 = DB::table('chart_accounts')
            ->whereBetween('account_code', [5000, 5999])
            ->update([
                'has_cash_flow' => 1,
                'cash_flow_category_id' => 1,
            ]);
        
        $totalOperating = $operating1 + $operating2 + $operating3 + $operating4;
        $this->command->info("   ✓ Mapped {$totalOperating} accounts to Operating Activities");
        
        // ============================================================================
        // CATEGORY 2: INVESTING ACTIVITIES
        // ============================================================================
        $this->command->info('2. Mapping INVESTING ACTIVITIES accounts...');
        
        // Fixed Assets - Investing
        $investing1 = DB::table('chart_accounts')
            ->whereBetween('account_code', [1500, 1899])
            ->update([
                'has_cash_flow' => 1,
                'cash_flow_category_id' => 2,
            ]);
        
        // Long-term Investments - Investing
        $investing2 = DB::table('chart_accounts')
            ->whereBetween('account_code', [1200, 1299])
            ->whereRaw('account_name LIKE "%investment%"')
            ->update([
                'has_cash_flow' => 1,
                'cash_flow_category_id' => 2,
            ]);
        
        $totalInvesting = $investing1 + $investing2;
        $this->command->info("   ✓ Mapped {$totalInvesting} accounts to Investing Activities");
        
        // ============================================================================
        // CATEGORY 3: FINANCING ACTIVITIES
        // ============================================================================
        $this->command->info('3. Mapping FINANCING ACTIVITIES accounts...');
        
        // Long-term Liabilities - Financing
        $financing1 = DB::table('chart_accounts')
            ->whereBetween('account_code', [2500, 2999])
            ->update([
                'has_cash_flow' => 1,
                'cash_flow_category_id' => 3,
            ]);
        
        // Equity accounts - Financing
        $financing2 = DB::table('chart_accounts')
            ->whereBetween('account_code', [3000, 3999])
            ->update([
                'has_cash_flow' => 1,
                'cash_flow_category_id' => 3,
            ]);
        
        $totalFinancing = $financing1 + $financing2;
        $this->command->info("   ✓ Mapped {$totalFinancing} accounts to Financing Activities");
        
        // ============================================================================
        // CATEGORY 4: CASH AND CASH EQUIVALENT (already done)
        // ============================================================================
        $this->command->info('4. Verifying CASH AND CASH EQUIVALENT accounts...');
        
        $cash = DB::table('chart_accounts')
            ->where('cash_flow_category_id', 4)
            ->count();
        $this->command->info("   ✓ {$cash} cash accounts already mapped");
        
        // ============================================================================
        // SUMMARY
        // ============================================================================
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('Cash Flow Account Mapping Complete!');
        $this->command->info('========================================');
        $this->command->info("Operating Activities:    {$totalOperating} accounts");
        $this->command->info("Investing Activities:    {$totalInvesting} accounts");
        $this->command->info("Financing Activities:    {$totalFinancing} accounts");
        $this->command->info("Cash & Cash Equivalent:  {$cash} accounts");
        $this->command->info('========================================');
        $this->command->info('');
        
        // Show some examples
        $this->command->info('Sample mapped accounts:');
        $this->command->info('');
        
        $samples = DB::table('chart_accounts')
            ->join('cash_flow_categories', 'chart_accounts.cash_flow_category_id', '=', 'cash_flow_categories.id')
            ->where('chart_accounts.has_cash_flow', 1)
            ->select('chart_accounts.account_code', 'chart_accounts.account_name', 'cash_flow_categories.name as category')
            ->orderBy('cash_flow_categories.id')
            ->orderBy('chart_accounts.account_code')
            ->limit(20)
            ->get();
        
        $currentCategory = '';
        foreach ($samples as $account) {
            if ($account->category !== $currentCategory) {
                $currentCategory = $account->category;
                $this->command->info("  [{$currentCategory}]");
            }
            $this->command->info("    {$account->account_code} - {$account->account_name}");
        }
        
        $this->command->info('');
        $this->command->info('✓ Ready to generate cash flow reports!');
    }
}
