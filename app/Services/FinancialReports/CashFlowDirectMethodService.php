<?php

namespace App\Services\FinancialReports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CashFlowDirectMethodService
{
    /**
     * Get cash flows using direct method
     */
    public function getCashFlows(string $startDate, string $endDate, $branchId = null): array
    {
        $operating = $this->getOperatingActivities($startDate, $endDate, $branchId);
        $investing = $this->getInvestingActivities($startDate, $endDate, $branchId);
        $financing = $this->getFinancingActivities($startDate, $endDate, $branchId);
        
        return [
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
        ];
    }
    
    /**
     * Get operating activities - Direct method
     */
    protected function getOperatingActivities(string $startDate, string $endDate, $branchId): array
    {
        // Get ACTUAL cash movements from cash accounts (the proper Direct Method approach)
        // Cash receipts = Debits to cash accounts (cash increasing)
        $cashReceiptsFromCustomers = $this->getActualCashMovements($startDate, $endDate, $branchId, 'receipts', 'Operating Activities');
        
        // Cash payments = Credits to cash accounts (cash decreasing)
        $cashPaidForOperations = $this->getActualCashMovements($startDate, $endDate, $branchId, 'payments', 'Operating Activities');
        
        // Calculate cash generated from operations
        $cashGenerated = $cashReceiptsFromCustomers - $cashPaidForOperations;
        
        // Get specific operating items
        $interestPaid = $this->getSpecificCashFlow('interest_payment', $startDate, $endDate, $branchId);
        $incomeTaxPaid = $this->getSpecificCashFlow('tax_payment', $startDate, $endDate, $branchId);
        
        // Net cash from operating activities
        $netCashFromOperating = $cashGenerated - $interestPaid - $incomeTaxPaid;
        
        return [
            'line_items' => [
                [
                    'name' => 'Cash receipts from customers',
                    'amount' => $cashReceiptsFromCustomers,
                    'level' => 1,
                    'detail' => $this->getOperatingReceiptDetail($startDate, $endDate, $branchId),
                ],
                [
                    'name' => 'Cash paid to suppliers and employees',
                    'amount' => -$cashPaidForOperations,
                    'level' => 1,
                    'detail' => $this->getOperatingPaymentDetail($startDate, $endDate, $branchId),
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
            'receipts_total' => $cashReceiptsFromCustomers,
            'payments_total' => $cashPaidForOperations + $interestPaid + $incomeTaxPaid,
        ];
    }
    
    /**
     * Get investing activities
     */
    public function getInvestingActivities(string $startDate, string $endDate, $branchId): array
    {
        $user = Auth::user();
        $company = $user->company;
        
        // Get investing category transactions
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('cash_flow_categories', 'chart_accounts.cash_flow_category_id', '=', 'cash_flow_categories.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('cash_flow_categories.name', 'Investing Activities')
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        $transactions = $query->select(
            'gl_transactions.transaction_type',
            'gl_transactions.description',
            'gl_transactions.nature',
            'gl_transactions.amount'
        )->get();
        
        // Group by transaction type
        $lineItems = [];
        $purchasePPE = 0;
        $proceedsSalePPE = 0;
        $purchaseInvestments = 0;
        $proceedsSaleInvestments = 0;
        $interestReceived = 0;
        $dividendsReceived = 0;
        
        foreach ($transactions as $txn) {
            $amount = $txn->amount;
            
            switch ($txn->transaction_type) {
                case 'asset_purchase':
                case 'fixed_asset_acquisition':
                    $purchasePPE += $amount;
                    break;
                case 'asset_disposal':
                case 'fixed_asset_sale':
                    $proceedsSalePPE += $amount;
                    break;
                case 'investment_purchase':
                    $purchaseInvestments += $amount;
                    break;
                case 'investment_sale':
                    $proceedsSaleInvestments += $amount;
                    break;
                case 'interest_receipt':
                    $interestReceived += $amount;
                    break;
                case 'dividend_receipt':
                    $dividendsReceived += $amount;
                    break;
            }
        }
        
        $lineItems = [
            [
                'name' => 'Purchase of property, plant and equipment',
                'amount' => -$purchasePPE,
                'level' => 1,
            ],
            [
                'name' => 'Proceeds from sale of property, plant and equipment',
                'amount' => $proceedsSalePPE,
                'level' => 1,
            ],
            [
                'name' => 'Purchase of investments',
                'amount' => -$purchaseInvestments,
                'level' => 1,
            ],
            [
                'name' => 'Proceeds from sale of investments',
                'amount' => $proceedsSaleInvestments,
                'level' => 1,
            ],
            [
                'name' => 'Interest received',
                'amount' => $interestReceived,
                'level' => 1,
            ],
            [
                'name' => 'Dividends received',
                'amount' => $dividendsReceived,
                'level' => 1,
            ],
        ];
        
        $netCashFromInvesting = -$purchasePPE + $proceedsSalePPE 
                              - $purchaseInvestments + $proceedsSaleInvestments
                              + $interestReceived + $dividendsReceived;
        
        return [
            'line_items' => $lineItems,
            'net' => $netCashFromInvesting,
        ];
    }
    
    /**
     * Get financing activities
     */
    public function getFinancingActivities(string $startDate, string $endDate, $branchId): array
    {
        $user = Auth::user();
        $company = $user->company;
        
        // Get financing category transactions
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('cash_flow_categories', 'chart_accounts.cash_flow_category_id', '=', 'cash_flow_categories.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('cash_flow_categories.name', 'Financing Activities')
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        $transactions = $query->select(
            'gl_transactions.transaction_type',
            'gl_transactions.description',
            'gl_transactions.nature',
            'gl_transactions.amount'
        )->get();
        
        // Group by transaction type
        $proceedsFromShares = 0;
        $proceedsFromBorrowings = 0;
        $repaymentOfBorrowings = 0;
        $paymentOfLeases = 0;
        $dividendsPaid = 0;
        
        foreach ($transactions as $txn) {
            $amount = $txn->amount;
            
            switch ($txn->transaction_type) {
                case 'share_issuance':
                case 'capital_contribution':
                    $proceedsFromShares += $amount;
                    break;
                case 'loan_receipt':
                case 'borrowing_proceeds':
                    $proceedsFromBorrowings += $amount;
                    break;
                case 'loan_repayment':
                case 'borrowing_repayment':
                    $repaymentOfBorrowings += $amount;
                    break;
                case 'lease_payment':
                    $paymentOfLeases += $amount;
                    break;
                case 'dividend_payment':
                    $dividendsPaid += $amount;
                    break;
            }
        }
        
        $lineItems = [
            [
                'name' => 'Proceeds from issuance of share capital',
                'amount' => $proceedsFromShares,
                'level' => 1,
            ],
            [
                'name' => 'Proceeds from long-term borrowings',
                'amount' => $proceedsFromBorrowings,
                'level' => 1,
            ],
            [
                'name' => 'Repayment of borrowings',
                'amount' => -$repaymentOfBorrowings,
                'level' => 1,
            ],
            [
                'name' => 'Payment of lease liabilities',
                'amount' => -$paymentOfLeases,
                'level' => 1,
            ],
            [
                'name' => 'Dividends paid',
                'amount' => -$dividendsPaid,
                'level' => 1,
            ],
        ];
        
        $netCashFromFinancing = $proceedsFromShares + $proceedsFromBorrowings 
                              - $repaymentOfBorrowings - $paymentOfLeases 
                              - $dividendsPaid;
        
        return [
            'line_items' => $lineItems,
            'net' => $netCashFromFinancing,
        ];
    }
    
    /**
     * Get cash flow by category and type
     */
    protected function getCashFlowByCategory(
        string $categoryName,
        string $startDate,
        string $endDate,
        $branchId,
        string $flowType = 'all'
    ): float {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('cash_flow_categories', 'chart_accounts.cash_flow_category_id', '=', 'cash_flow_categories.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('cash_flow_categories.name', $categoryName)
            ->whereBetween('gl_transactions.date', [$startDate, $endDate])
            ->whereNotIn('gl_transactions.transaction_type', ['opening_balance', 'asset_opening']);
        
        // Filter by flow type - ONLY actual cash transactions for Direct Method
        if ($flowType === 'receipts') {
            // Only count transactions where CASH/BANK accounts are CREDITED (cash coming in)
            // This is the proper Direct Method approach
            $query->whereIn('gl_transactions.transaction_type', [
                'receipt',           // Actual cash/bank receipts
                'cash_sale',         // Direct cash sales
                'pos_sale',          // Point of sale cash
                'customer_payment',  // Customer paying cash
                'cash_receipt',      // Cash receipts
                'bank_receipt',      // Bank receipts
            ]);
        } elseif ($flowType === 'payments') {
            // Only count transactions where CASH/BANK accounts are DEBITED (cash going out)
            $query->whereIn('gl_transactions.transaction_type', [
                'payment',           // Actual cash/bank payments
                'cash_purchase',     // Cash purchases
                'supplier_payment',  // Paying suppliers
                'payroll_payment',   // Payroll cash payments
                'salary_payment',    // Salary payments
                'cash_payment',      // Cash payments
                'bank_payment',      // Bank payments
                'expense_payment',   // Cash expense payments
            ]);
        }
        
        $this->applyBranchFilter($query, $branchId);
        
        $result = $query->select(
            DB::raw('SUM(gl_transactions.amount) as total')
        )->first();
        
        return abs($result->total ?? 0);
    }
    
    /**
     * Get specific cash flow by transaction type
     */
    protected function getSpecificCashFlow(
        string $transactionType,
        string $startDate,
        string $endDate,
        $branchId
    ): float {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('gl_transactions.transaction_type', $transactionType)
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        $result = $query->sum('gl_transactions.amount');
        
        return abs($result ?? 0);
    }
    
    /**
     * Get operating receipt detail
     */
    protected function getOperatingReceiptDetail(string $startDate, string $endDate, $branchId): array
    {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('gl_transactions.transaction_type', ['receipt', 'cash_sale', 'pos_sale', 'customer_payment'])
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        return $query->select(
            'gl_transactions.transaction_type',
            DB::raw('SUM(gl_transactions.amount) as total'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('gl_transactions.transaction_type')
        ->get()
        ->toArray();
    }
    
    /**
     * Get operating payment detail
     */
    protected function getOperatingPaymentDetail(string $startDate, string $endDate, $branchId): array
    {
        $user = Auth::user();
        $company = $user->company;
        
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('gl_transactions.transaction_type', ['payment', 'cash_purchase', 'supplier_payment', 'payroll_payment', 'salary_payment'])
            ->whereBetween('gl_transactions.date', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $branchId);
        
        return $query->select(
            'gl_transactions.transaction_type',
            DB::raw('SUM(gl_transactions.amount) as total'),
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('gl_transactions.transaction_type')
        ->get()
        ->toArray();
    }
    
    /**
     * Get ACTUAL cash movements by looking at transactions on cash accounts
     * This is the proper Direct Method approach - we look at what actually moved cash
     */
    protected function getActualCashMovements(
        string $startDate,
        string $endDate,
        $branchId,
        string $flowType,
        string $categoryName
    ): float {
        $user = Auth::user();
        $company = $user->company;
        
        // Query transactions on CASH accounts (category 4)
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('chart_accounts.cash_flow_category_id', 4) // Cash accounts only
            ->whereBetween('gl_transactions.date', [$startDate, $endDate])
            ->whereNotIn('gl_transactions.transaction_type', ['opening_balance', 'asset_opening']);
        
        // Filter by receipt or payment
        if ($flowType === 'receipts') {
            // Cash receipts = Debits to cash accounts (cash increases)
            $query->where('gl_transactions.nature', 'debit');
        } elseif ($flowType === 'payments') {
            // Cash payments = Credits to cash accounts (cash decreases)
            $query->where('gl_transactions.nature', 'credit');
        }
        
        $this->applyBranchFilter($query, $branchId);
        
        $result = $query->sum('gl_transactions.amount');
        
        return abs($result ?? 0);
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
}
