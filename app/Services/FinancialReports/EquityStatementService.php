<?php

namespace App\Services\FinancialReports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EquityStatementService
{
    /**
     * Get statement of changes in equity
     */
    public function getEquityStatement(
        string $startDate,
        string $endDate,
        $branchId = null,
        array $comparativePeriods = []
    ): array {
        // Get equity components
        $equityComponents = $this->getEquityComponents();
        
        // Get opening balances
        $openingBalances = $this->getOpeningBalances($startDate, $branchId, $equityComponents);
        
        // Get movements during the period
        $movements = $this->getEquityMovements($startDate, $endDate, $branchId, $equityComponents);
        
        // Calculate closing balances
        $closingBalances = [];
        foreach ($equityComponents as $component) {
            $closingBalances[$component['key']] = ($openingBalances[$component['key']] ?? 0) 
                                                 + ($movements[$component['key']]['total'] ?? 0);
        }
        
        // Get comparative periods data
        $comparativeData = [];
        foreach ($comparativePeriods as $index => $period) {
            if (!empty($period['start_date']) && !empty($period['end_date'])) {
                $compData = $this->getEquityStatement(
                    $period['start_date'],
                    $period['end_date'],
                    $branchId,
                    [] // Don't nest comparatives
                );
                
                $label = isset($period['name']) && trim($period['name']) !== '' 
                    ? trim($period['name']) 
                    : ('Period ' . ($index + 1));
                    
                $comparativeData[$label] = $compData;
            }
        }
        
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'equity_components' => $equityComponents,
            'opening_balances' => $openingBalances,
            'movements' => $movements,
            'closing_balances' => $closingBalances,
            'total_opening' => array_sum($openingBalances),
            'total_movement' => array_sum(array_map(function($m) { return $m['total'] ?? 0; }, $movements)),
            'total_closing' => array_sum($closingBalances),
            'comparative_periods' => $comparativeData,
            'notes' => $this->getEquityNotes(),
        ];
    }
    
    /**
     * Get equity components structure
     */
    protected function getEquityComponents(): array
    {
        return [
            [
                'key' => 'share_capital',
                'name' => 'Share Capital',
                'equity_category_id' => 10,  // Share Capital (IFRS)
            ],
            [
                'key' => 'share_premium',
                'name' => 'Share Premium',
                'equity_category_id' => 11,  // Share Premium (IFRS)
            ],
            [
                'key' => 'revaluation_reserve',
                'name' => 'Revaluation Reserve',
                'equity_category_id' => 12,  // Revaluation Reserve (IFRS)
            ],
            [
                'key' => 'retained_earnings',
                'name' => 'Retained Earnings',
                'equity_category_id' => 13,  // Retained Earnings (IFRS)
            ],
            [
                'key' => 'other_reserves',
                'name' => 'Other Reserves',
                'equity_category_id' => 14,  // Other Reserves (IFRS)
            ],
        ];
    }
    
    /**
     * Get opening balances for all equity components
     */
    protected function getOpeningBalances(string $startDate, $branchId, array $equityComponents): array
    {
        $balances = [];
        
        foreach ($equityComponents as $component) {
            $balances[$component['key']] = $this->getComponentBalance(
                $component['equity_category_id'],
                $startDate,
                true, // isOpening
                $branchId
            );
        }
        
        return $balances;
    }
    
    /**
     * Get equity movements during the period
     */
    protected function getEquityMovements(
        string $startDate,
        string $endDate,
        $branchId,
        array $equityComponents
    ): array {
        $movements = [];
        
        foreach ($equityComponents as $component) {
            $movements[$component['key']] = $this->getComponentMovements(
                $component['key'],
                $component['equity_category_id'],
                $startDate,
                $endDate,
                $branchId
            );
        }
        
        // Add profit for the year to retained earnings
        $profitForYear = $this->getProfitForYear($startDate, $endDate, $branchId);
        if (!isset($movements['retained_earnings']['line_items'])) {
            $movements['retained_earnings']['line_items'] = [];
        }
        
        // Add profit as the first line item in retained earnings
        array_unshift($movements['retained_earnings']['line_items'], [
            'name' => 'Profit for the year',
            'amount' => $profitForYear,
            'category' => 'comprehensive_income',
        ]);
        $movements['retained_earnings']['total'] = ($movements['retained_earnings']['total'] ?? 0) + $profitForYear;
        
        return $movements;
    }
    
    /**
     * Get balance for a specific equity component
     */
    protected function getComponentBalance(
        int $equityCategoryId,
        string $date,
        bool $isOpening,
        $branchId
    ): float {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('chart_accounts.equity_category_id', $equityCategoryId);
        
        if ($isOpening) {
            $query->where('gl_transactions.date', '<', $date);
        } else {
            $query->where('gl_transactions.date', '<=', $date);
        }
        
        $this->applyBranchFilter($query, $branchId);
        
        $result = $query->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as balance')
        )->first();
        
        return $result->balance ?? 0;
    }
    
    /**
     * Get movements for a specific equity component
     */
    protected function getComponentMovements(
        string $componentKey,
        int $equityCategoryId,
        string $startDate,
        string $endDate,
        $branchId
    ): array {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('equity_categories', 'chart_accounts.equity_category_id', '=', 'equity_categories.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('chart_accounts.equity_category_id', $equityCategoryId)
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        $transactions = $query->select(
            'equity_categories.name as equity_category_name',
            'gl_transactions.description',
            'gl_transactions.nature',
            'gl_transactions.amount',
            'gl_transactions.transaction_type',
            'gl_transactions.date'
        )->get();
        
        // Group by equity category
        $lineItems = [];
        $categoryTotals = [];
        
        foreach ($transactions as $transaction) {
            $categoryName = $transaction->equity_category_name ?? $this->getCategoryFromTransactionType($transaction->transaction_type);
            
            $amount = $transaction->nature === 'credit' ? $transaction->amount : -$transaction->amount;
            
            if (!isset($categoryTotals[$categoryName])) {
                $categoryTotals[$categoryName] = 0;
            }
            $categoryTotals[$categoryName] += $amount;
        }
        
        foreach ($categoryTotals as $category => $amount) {
            if (abs($amount) > 0.01) { // Only include if not zero
                $lineItems[] = [
                    'name' => $category,
                    'amount' => $amount,
                    'category' => $this->categorizeMovement($category),
                ];
            }
        }
        
        return [
            'line_items' => $lineItems,
            'total' => array_sum($categoryTotals),
        ];
    }
    
    /**
     * Get category from transaction type
     */
    protected function getCategoryFromTransactionType(string $transactionType): string
    {
        $mapping = [
            'share_issuance' => 'Issuance of Shares',
            'capital_contribution' => 'Issuance of Shares',
            'dividend_payment' => 'Dividends Paid',
            'revaluation' => 'Revaluation Reserve',
            'revaluation_reversal' => 'Revaluation Reverse',
        ];
        
        return $mapping[$transactionType] ?? 'Other movements';
    }
    
    /**
     * Categorize movement type
     */
    protected function categorizeMovement(string $movementName): string
    {
        $comprehensiveIncome = ['Profit and Loss', 'Revaluation Reserve', 'Revaluation Reverse'];
        $transactions = ['Issuance of Shares', 'Dividends Paid'];
        
        if (in_array($movementName, $comprehensiveIncome)) {
            return 'comprehensive_income';
        } elseif (in_array($movementName, $transactions)) {
            return 'transactions_with_owners';
        }
        
        return 'other';
    }
    
    /**
     * Get profit for the year
     */
    protected function getProfitForYear(string $startDate, string $endDate, $branchId): float
    {
        $user = Auth::user();
        $company = $user->company;
        
        // Get total revenue
        $revenueQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('account_class.name', 'Revenue')
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($revenueQuery, $branchId);
        
        $revenue = $revenueQuery->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as total')
        )->first();
        
        // Get total expenses
        $expenseQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('account_class.name', 'Expenses')
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($expenseQuery, $branchId);
        
        $expenses = $expenseQuery->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as total')
        )->first();
        
        return ($revenue->total ?? 0) - ($expenses->total ?? 0);
    }
    
    /**
     * Get other comprehensive income items
     */
    public function getOtherComprehensiveIncome(string $startDate, string $endDate, $branchId = null): array
    {
        // Get revaluation gains/losses
        $revaluation = $this->getComponentMovements(
            'revaluation_reserve',
            '3030',
            $startDate,
            $endDate,
            $branchId
        );
        
        // Get foreign currency translation
        $fxTranslation = $this->getForeignCurrencyTranslation($startDate, $endDate, $branchId);
        
        // Get actuarial gains/losses on defined benefit plans
        $actuarialGains = $this->getActuarialGains($startDate, $endDate, $branchId);
        
        return [
            'revaluation' => $revaluation['total'] ?? 0,
            'fx_translation' => $fxTranslation,
            'actuarial_gains' => $actuarialGains,
            'total' => ($revaluation['total'] ?? 0) + $fxTranslation + $actuarialGains,
        ];
    }
    
    /**
     * Get foreign currency translation gains/losses
     */
    protected function getForeignCurrencyTranslation(string $startDate, string $endDate, $branchId): float
    {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('chart_accounts.account_code', 'LIKE', '3051%') // FX translation reserve
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        $result = $query->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as total')
        )->first();
        
        return $result->total ?? 0;
    }
    
    /**
     * Get actuarial gains/losses
     */
    protected function getActuarialGains(string $startDate, string $endDate, $branchId): float
    {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('chart_accounts.account_code', 'LIKE', '3052%') // Actuarial reserve
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        $result = $query->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as total')
        )->first();
        
        return $result->total ?? 0;
    }
    
    /**
     * Apply branch filter
     */
    protected function applyBranchFilter($query, $branchId)
    {
        $user = Auth::user();
        $assignedBranchIds = $user->branches()->pluck('branches.id')->toArray();
        
        if ($branchId === 'all') {
            if (!empty($assignedBranchIds)) {
                $query->whereIn('gl_transactions.branch_id', $assignedBranchIds);
            }
        } elseif ($branchId) {
            $query->where('gl_transactions.branch_id', $branchId);
        } else {
            if (!empty($assignedBranchIds)) {
                $query->whereIn('gl_transactions.branch_id', $assignedBranchIds);
            }
        }
    }
    
    /**
     * Get equity notes
     */
    protected function getEquityNotes(): array
    {
        return [
            'The revaluation reserve relates to the revaluation of land and buildings in accordance with IAS 16.',
            'Other reserves include foreign currency translation reserve in accordance with IAS 21 and share-based payment reserve in accordance with IFRS 2.',
            'All amounts are presented in the company\'s functional currency.',
            'Dividends paid during the period were approved by shareholders at the Annual General Meeting.',
        ];
    }
}
