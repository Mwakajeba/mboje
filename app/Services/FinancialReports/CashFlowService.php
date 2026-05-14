<?php

namespace App\Services\FinancialReports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CashFlowService
{
    protected $directMethodService;
    protected $indirectMethodService;
    
    public function __construct(
        CashFlowDirectMethodService $directMethodService,
        CashFlowIndirectMethodService $indirectMethodService
    ) {
        $this->directMethodService = $directMethodService;
        $this->indirectMethodService = $indirectMethodService;
    }
    
    /**
     * Get cash flow statement data
     * 
     * @param string $method 'direct' or 'indirect'
     * @param string $startDate
     * @param string $endDate
     * @param mixed $branchId
     * @param array $comparativePeriods
     * @return array
     */
    public function getCashFlowStatement(
        string $method,
        string $startDate,
        string $endDate,
        $branchId = null,
        array $comparativePeriods = []
    ): array {
        // Get opening and closing cash balances
        $openingCash = $this->getCashAndCashEquivalents($startDate, true, $branchId);
        $closingCash = $this->getCashAndCashEquivalents($endDate, false, $branchId);
        
        // Get cash flows by activity
        $cashFlows = $method === 'direct'
            ? $this->directMethodService->getCashFlows($startDate, $endDate, $branchId)
            : $this->indirectMethodService->getCashFlows($startDate, $endDate, $branchId);
        
        // Calculate net increase/decrease
        $netCashFlow = $cashFlows['operating']['net'] 
                     + $cashFlows['investing']['net'] 
                     + $cashFlows['financing']['net'];
        
        // Get comparative periods data
        $comparativeData = [];
        foreach ($comparativePeriods as $index => $period) {
            if (!empty($period['start_date']) && !empty($period['end_date'])) {
                $compData = $this->getCashFlowStatement(
                    $method,
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
            'method' => $method,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'cash_flows' => $cashFlows,
            'opening_cash' => $openingCash,
            'closing_cash' => $closingCash,
            'net_cash_flow' => $netCashFlow,
            'reconciliation' => $closingCash - $openingCash,
            'comparative_periods' => $comparativeData,
            'notes' => $this->getCashFlowNotes(),
        ];
    }
    
    /**
     * Get cash and cash equivalents balance
     */
    public function getCashAndCashEquivalents(
        string $date,
        bool $isOpening = false,
        $branchId = null
    ): float {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('cash_flow_categories', 'chart_accounts.cash_flow_category_id', '=', 'cash_flow_categories.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('cash_flow_categories.name', 'Cash and Cash Equivalent');
        
        // Date filter
        if ($isOpening) {
            $query->where('gl_transactions.date', '<', $date);
        } else {
            $query->where('gl_transactions.date', '<=', $date);
        }
        
        // Branch filter
        $this->applyBranchFilter($query, $branchId);
        
        $result = $query->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE 0 END) as debit_total'),
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE 0 END) as credit_total')
        )->first();
        
        // Cash accounts have debit balance (Asset)
        return ($result->debit_total ?? 0) - ($result->credit_total ?? 0);
    }
    
    /**
     * Apply branch filter based on user permissions
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
     * Get cash flow notes
     */
    protected function getCashFlowNotes(): array
    {
        return [
            'Cash and cash equivalents comprise cash at bank and in hand and short-term deposits with an original maturity of three months or less.',
            'The company considers all liquid investments with a maturity of 90 days or less to be cash equivalents.',
            'Bank overdrafts are shown within borrowings in current liabilities on the balance sheet.',
        ];
    }
    
    /**
     * Get non-cash transactions for notes
     */
    public function getNonCashTransactions(string $startDate, string $endDate, $branchId = null): array
    {
        $user = Auth::user();
        $company = $user->company;
        
        // This would identify significant non-cash transactions like:
        // - Assets acquired through finance leases
        // - Debt to equity conversions
        // - Business acquisitions through share exchange
        
        $nonCashTransactions = [];
        
        // Example: Get lease additions
        $leaseAdditions = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('gl_transactions.transaction_type', 'lease_recognition')
            ->whereBetween('gl_transactions.date', [$startDate, $endDate])
            ->sum('gl_transactions.amount');
        
        if ($leaseAdditions > 0) {
            $nonCashTransactions[] = [
                'description' => 'Acquisition of right-of-use assets under lease arrangements',
                'amount' => $leaseAdditions,
            ];
        }
        
        return $nonCashTransactions;
    }
}
