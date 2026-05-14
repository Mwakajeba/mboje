<?php

namespace App\Services\FinancialReports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CashFlowIndirectMethodService
{
    protected $directMethodService;
    
    public function __construct(CashFlowDirectMethodService $directMethodService)
    {
        $this->directMethodService = $directMethodService;
    }
    
    /**
     * Get cash flows using indirect method
     */
    public function getCashFlows(string $startDate, string $endDate, $branchId = null): array
    {
        $operating = $this->getOperatingActivities($startDate, $endDate, $branchId);
        $investing = $this->directMethodService->getInvestingActivities($startDate, $endDate, $branchId);
        $financing = $this->directMethodService->getFinancingActivities($startDate, $endDate, $branchId);
        
        return [
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
        ];
    }
    
    /**
     * Get operating activities - Indirect method
     */
    protected function getOperatingActivities(string $startDate, string $endDate, $branchId): array
    {
        // Get profit before tax from income statement
        $profitBeforeTax = $this->getProfitBeforeTax($startDate, $endDate, $branchId);
        
        // Get adjustments for non-cash items
        $depreciation = $this->getDepreciation($startDate, $endDate, $branchId);
        $impairmentLosses = $this->getImpairmentLosses($startDate, $endDate, $branchId);
        $gainLossOnDisposal = $this->getGainLossOnDisposal($startDate, $endDate, $branchId);
        $financeCosts = $this->getFinanceCosts($startDate, $endDate, $branchId);
        $investmentIncome = $this->getInvestmentIncome($startDate, $endDate, $branchId);
        $unrealizedFxGains = $this->getUnrealizedFxGains($startDate, $endDate, $branchId);
        
        // Operating profit before working capital changes
        $operatingProfitBeforeWC = $profitBeforeTax 
                                  + $depreciation 
                                  + $impairmentLosses 
                                  - $gainLossOnDisposal 
                                  + $financeCosts 
                                  - $investmentIncome 
                                  + $unrealizedFxGains;
        
        // Get working capital changes
        $wcChanges = $this->getWorkingCapitalChanges($startDate, $endDate, $branchId);
        
        // Cash generated from operations
        $cashGenerated = $operatingProfitBeforeWC 
                       + $wcChanges['trade_receivables']
                       + $wcChanges['inventories']
                       + $wcChanges['prepayments']
                       + $wcChanges['trade_payables']
                       + $wcChanges['accruals'];
        
        // Interest and tax paid
        $interestPaid = $this->getCashPaid('interest_payment', $startDate, $endDate, $branchId);
        $incomeTaxPaid = $this->getCashPaid('tax_payment', $startDate, $endDate, $branchId);
        
        // Net cash from operating activities
        $netCashFromOperating = $cashGenerated - $interestPaid - $incomeTaxPaid;
        
        return [
            'line_items' => [
                [
                    'name' => 'Profit before income tax',
                    'amount' => $profitBeforeTax,
                    'level' => 1,
                    'is_bold' => true,
                ],
                [
                    'name' => 'Adjustments for:',
                    'amount' => null,
                    'level' => 1,
                    'is_header' => true,
                ],
                [
                    'name' => 'Depreciation and amortization',
                    'amount' => $depreciation,
                    'level' => 2,
                ],
                [
                    'name' => 'Impairment losses',
                    'amount' => $impairmentLosses,
                    'level' => 2,
                ],
                [
                    'name' => 'Loss/(gain) on disposal of assets',
                    'amount' => -$gainLossOnDisposal,
                    'level' => 2,
                ],
                [
                    'name' => 'Finance costs',
                    'amount' => $financeCosts,
                    'level' => 2,
                ],
                [
                    'name' => 'Investment income',
                    'amount' => -$investmentIncome,
                    'level' => 2,
                ],
                [
                    'name' => 'Unrealized foreign exchange losses/(gains)',
                    'amount' => $unrealizedFxGains,
                    'level' => 2,
                ],
                [
                    'name' => 'Operating profit before working capital changes',
                    'amount' => $operatingProfitBeforeWC,
                    'level' => 2,
                    'is_subtotal' => true,
                ],
                [
                    'name' => 'Changes in working capital:',
                    'amount' => null,
                    'level' => 1,
                    'is_header' => true,
                ],
                [
                    'name' => '(Increase)/decrease in trade receivables',
                    'amount' => $wcChanges['trade_receivables'],
                    'level' => 2,
                ],
                [
                    'name' => '(Increase)/decrease in inventories',
                    'amount' => $wcChanges['inventories'],
                    'level' => 2,
                ],
                [
                    'name' => '(Increase)/decrease in prepayments',
                    'amount' => $wcChanges['prepayments'],
                    'level' => 2,
                ],
                [
                    'name' => 'Increase/(decrease) in trade payables',
                    'amount' => $wcChanges['trade_payables'],
                    'level' => 2,
                ],
                [
                    'name' => 'Increase/(decrease) in accruals',
                    'amount' => $wcChanges['accruals'],
                    'level' => 2,
                ],
                [
                    'name' => 'Cash generated from operations',
                    'amount' => $cashGenerated,
                    'level' => 2,
                    'is_subtotal' => true,
                ],
                [
                    'name' => 'Interest paid',
                    'amount' => -$interestPaid,
                    'level' => 1,
                ],
                [
                    'name' => 'Income tax paid',
                    'amount' => -$incomeTaxPaid,
                    'level' => 1,
                ],
            ],
            'net' => $netCashFromOperating,
            'profit_before_tax' => $profitBeforeTax,
            'adjustments' => [
                'depreciation' => $depreciation,
                'impairment' => $impairmentLosses,
                'gain_loss_disposal' => $gainLossOnDisposal,
                'finance_costs' => $financeCosts,
                'investment_income' => $investmentIncome,
                'fx_gains' => $unrealizedFxGains,
            ],
            'working_capital_changes' => $wcChanges,
            'cash_generated' => $cashGenerated,
        ];
    }
    
    /**
     * Get profit before tax from income statement
     */
    protected function getProfitBeforeTax(string $startDate, string $endDate, $branchId): float
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
        
        // Get total expenses (excluding tax)
        $expenseQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('account_class.name', 'Expenses')
            ->where('chart_accounts.account_code', 'NOT LIKE', '6500%') // Exclude tax expense
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($expenseQuery, $branchId);
        
        $expenses = $expenseQuery->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as total')
        )->first();
        
        return ($revenue->total ?? 0) - ($expenses->total ?? 0);
    }
    
    /**
     * Get depreciation and amortization
     */
    protected function getDepreciation(string $startDate, string $endDate, $branchId): float
    {
        // Look for depreciation transactions
        return $this->getExpenseByTransactionType('asset_depreciation', $startDate, $endDate, $branchId);
    }
    
    /**
     * Get impairment losses
     */
    protected function getImpairmentLosses(string $startDate, string $endDate, $branchId): float
    {
        return $this->getExpenseByTransactionType('impairment_loss', $startDate, $endDate, $branchId);
    }
    
    /**
     * Get gain/loss on disposal (negative if gain, positive if loss)
     */
    protected function getGainLossOnDisposal(string $startDate, string $endDate, $branchId): float
    {
        $user = Auth::user();
        $company = $user->company;
        
        // Get gains (credit to income)
        $gainQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('gl_transactions.transaction_type', ['asset_disposal', 'fixed_asset_sale'])
            ->where('gl_transactions.nature', 'credit')
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($gainQuery, $branchId);
        $gain = $gainQuery->sum('gl_transactions.amount');
        
        // Get losses (debit to expense)
        $lossQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('gl_transactions.transaction_type', ['asset_disposal', 'fixed_asset_sale'])
            ->where('gl_transactions.nature', 'debit')
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($lossQuery, $branchId);
        $loss = $lossQuery->sum('gl_transactions.amount');
        
        return ($loss ?? 0) - ($gain ?? 0); // Positive if net loss, negative if net gain
    }
    
    /**
     * Get finance costs
     */
    protected function getFinanceCosts(string $startDate, string $endDate, $branchId): float
    {
        return $this->getExpenseByTransactionType('interest_expense', $startDate, $endDate, $branchId);
    }
    
    /**
     * Get investment income
     */
    protected function getInvestmentIncome(string $startDate, string $endDate, $branchId): float
    {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('gl_transactions.transaction_type', ['interest_receipt', 'dividend_receipt'])
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        return $query->sum('gl_transactions.amount') ?? 0;
    }
    
    /**
     * Get unrealized foreign exchange gains/losses
     */
    protected function getUnrealizedFxGains(string $startDate, string $endDate, $branchId): float
    {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('gl_transactions.transaction_type', 'unrealized_fx')
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        $result = $query->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as net')
        )->first();
        
        return $result->net ?? 0;
    }
    
    /**
     * Get working capital changes
     */
    protected function getWorkingCapitalChanges(string $startDate, string $endDate, $branchId): array
    {
        // Calculate change in working capital items between start and end dates
        return [
            'trade_receivables' => $this->getBalanceChange('1101', $startDate, $endDate, $branchId, 'decrease'),  // Trade Receivables
            'inventories' => $this->getBalanceChange('1170', $startDate, $endDate, $branchId, 'decrease'),  // Merchandise Inventory
            'prepayments' => $this->getBalanceChange('1134', $startDate, $endDate, $branchId, 'decrease'),  // Other Prepayments
            'trade_payables' => $this->getBalanceChange('2101', $startDate, $endDate, $branchId, 'increase'),  // Trade Payables
            'accruals' => $this->getBalanceChange('2103', $startDate, $endDate, $branchId, 'increase'),  // Accruals (using Net Salary Payable as proxy)
        ];
    }
    
    /**
     * Get balance change for an account
     */
    protected function getBalanceChange(
        string $accountCodePrefix,
        string $startDate,
        string $endDate,
        $branchId,
        string $direction
    ): float {
        $user = Auth::user();
        $company = $user->company;
        
        // Get opening balance
        $openingQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('chart_accounts.account_code', 'LIKE', $accountCodePrefix . '%')
            ->where('gl_transactions.date', '<', $startDate);
        
        $this->applyBranchFilter($openingQuery, $branchId);
        
        $opening = $openingQuery->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as balance')
        )->first();
        
        // Get closing balance
        $closingQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('chart_accounts.account_code', 'LIKE', $accountCodePrefix . '%')
            ->where('gl_transactions.date', '<=', $endDate);
        
        $this->applyBranchFilter($closingQuery, $branchId);
        
        $closing = $closingQuery->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as balance')
        )->first();
        
        $change = ($closing->balance ?? 0) - ($opening->balance ?? 0);
        
        // Return negative of change if we want decrease to be positive
        // (e.g., decrease in receivables is positive for cash flow)
        return $direction === 'decrease' ? -$change : $change;
    }
    
    /**
     * Helper methods
     */
    protected function getExpenseByTransactionType(string $transactionType, string $startDate, string $endDate, $branchId): float
    {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('gl_transactions.transaction_type', $transactionType)
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        return $query->sum('gl_transactions.amount') ?? 0;
    }
    
    protected function getCashPaid(string $transactionType, string $startDate, string $endDate, $branchId): float
    {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('gl_transactions.transaction_type', $transactionType)
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        return abs($query->sum('gl_transactions.amount') ?? 0);
    }
    
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
}
