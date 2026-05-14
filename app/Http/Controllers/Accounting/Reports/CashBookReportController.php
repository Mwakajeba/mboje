<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class CashBookReportController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('view cash book report')) {
            abort(403, 'Unauthorized access to this report.');
        }
        
        $user = Auth::user();
        $company = $user->company;

        // Get filter parameters
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $bankAccountId = $request->get('bank_account_id', 'all');
        $branchId = $request->get('branch_id', 'all');

        // Get user's assigned branches for scoping
        $assignedBranchIds = $user->branches()
            ->where('branches.company_id', $company->id)
            ->pluck('branches.id')
            ->toArray();

        // Get bank accounts for filter, respecting branch scope
        $bankAccounts = DB::table('bank_accounts')
            ->join('chart_accounts', 'bank_accounts.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->when(!empty($assignedBranchIds), function ($query) use ($assignedBranchIds) {
                $query->where(function ($q) use ($assignedBranchIds) {
                    $q->where('bank_accounts.is_all_branches', true)
                      ->orWhereIn('bank_accounts.branch_id', $assignedBranchIds);
                });
            })
            ->select('bank_accounts.*', 'chart_accounts.account_name')
            ->get();

        // Get branches for filter: only user's assigned branches
        $branches = $user->branches()->where('branches.company_id', $company->id)->get();

        // Normalize branch selection: allow 'all' only if multiple assignments
        if ($branchId === 'all' && $branches->count() <= 1) {
            $branchId = optional($branches->first())->id;
        }

        // Get cash book data
        $cashBookData = $this->getCashBookData($startDate, $endDate, $bankAccountId, $branchId);

        return view('accounting.reports.cash-book.index', compact(
            'cashBookData',
            'startDate',
            'endDate',
            'bankAccountId',
            'branchId',
            'bankAccounts',
            'branches',
            'user'
        ));
    }

    private function getCashBookData($startDate, $endDate, $bankAccountId, $branchId)
    {
        $user = Auth::user();
        $company = $user->company;

        // Get chart_account_id(s) from bank_accounts based on filter
        $chartAccountIds = [];
        if ($bankAccountId && $bankAccountId != 'all') {
            // First, get the chart_account_id directly from bank_accounts
            $bankAccount = DB::table('bank_accounts')
                ->where('bank_accounts.id', $bankAccountId)
                ->select('bank_accounts.chart_account_id', 'bank_accounts.name as bank_name')
                ->first();
         
                
            
            if ($bankAccount) {
                // Verify the chart_account belongs to this company
                $chartAccount = DB::table('chart_accounts')
                    ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
                    ->where('chart_accounts.id', $bankAccount->chart_account_id)
                    ->where('account_class_groups.company_id', $company->id)
                    ->select('chart_accounts.id', 'chart_accounts.account_name')
                    ->first();
                
                if ($chartAccount) {
                    $chartAccountIds = [$bankAccount->chart_account_id];
                } else {
                   
                    
                    return [
                        'opening_balance' => 0,
                        'transactions' => [],
                        'total_receipts' => 0,
                        'total_payments' => 0,
                        'final_balance' => 0,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'bank_account_id' => $bankAccountId,
                        'branch_id' => $branchId
                    ];
                }
            } else {
               
                return [
                    'opening_balance' => 0,
                    'transactions' => [],
                    'total_receipts' => 0,
                    'total_payments' => 0,
                    'final_balance' => 0,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'bank_account_id' => $bankAccountId,
                    'branch_id' => $branchId
                ];
            }
        } else {
            // Get all chart_account_ids from bank_accounts for this company,
            // respecting branch scope (all branches vs specific branch)
            $assignedBranchIds = Auth::user()->branches()
                ->pluck('branches.id')
                ->toArray();

            $bankAccountQuery = DB::table('bank_accounts')
                ->join('chart_accounts', 'bank_accounts.chart_account_id', '=', 'chart_accounts.id')
                ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
                ->where('account_class_groups.company_id', $company->id);

            if (!empty($assignedBranchIds)) {
                $bankAccountQuery->where(function ($q) use ($assignedBranchIds) {
                    $q->where('bank_accounts.is_all_branches', true)
                      ->orWhereIn('bank_accounts.branch_id', $assignedBranchIds);
                });
            }

            $chartAccountIds = $bankAccountQuery
                ->pluck('bank_accounts.chart_account_id')
                ->unique()
                ->toArray();
        }

        // If no bank accounts found, return empty data
        if (empty($chartAccountIds)) {
            
            
            return [
                'opening_balance' => 0,
                'transactions' => [],
                'total_receipts' => 0,
                'total_payments' => 0,
                'final_balance' => 0,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'bank_account_id' => $bankAccountId,
                'branch_id' => $branchId
            ];
        }
        
    
        

        // Get opening balance (transactions before start date)
        // Filter gl_transactions by chart_account_id(s) from bank accounts
        $startDateWithTime = $startDate . ' 00:00:00';
        $openingBalanceQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('gl_transactions.chart_account_id', $chartAccountIds)
            ->where('gl_transactions.date', '<', $startDateWithTime);

        $assignedBranchIds = Auth::user()->branches()->pluck('branches.id')->toArray();
        if ($branchId === 'all') {
            if (!empty($assignedBranchIds)) {
                $openingBalanceQuery->whereIn('gl_transactions.branch_id', $assignedBranchIds);
            }
        } elseif ($branchId) {
            $openingBalanceQuery->where('gl_transactions.branch_id', $branchId);
        } else {
            if (!empty($assignedBranchIds)) {
                $openingBalanceQuery->whereIn('gl_transactions.branch_id', $assignedBranchIds);
            }
        }

        $openingBalance = $openingBalanceQuery->selectRaw('
            SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE 0 END) -
            SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE 0 END) as opening_balance
        ')->first()->opening_balance ?? 0;

        // Get transactions for the period
        // Filter gl_transactions by chart_account_id(s) from bank accounts
        // Use whereDate or extend end date to include full day (23:59:59)
        $endDateWithTime = $endDate . ' 23:59:59';
        $startDateWithTime = $startDate . ' 00:00:00';
   
        
        $transactionsQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('bank_accounts', 'chart_accounts.id', '=', 'bank_accounts.chart_account_id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('gl_transactions.chart_account_id', $chartAccountIds)
            ->where('gl_transactions.date', '>=', $startDateWithTime)
            ->where('gl_transactions.date', '<=', $endDateWithTime);

        if ($branchId === 'all') {
            if (!empty($assignedBranchIds)) {
                $transactionsQuery->whereIn('gl_transactions.branch_id', $assignedBranchIds);
            }
        } elseif ($branchId) {
            $transactionsQuery->where('gl_transactions.branch_id', $branchId);
        } else {
            if (!empty($assignedBranchIds)) {
                $transactionsQuery->whereIn('gl_transactions.branch_id', $assignedBranchIds);
            }
        }

        $transactions = $transactionsQuery->leftJoin('customers', 'gl_transactions.customer_id', '=', 'customers.id')
        ->select(
            'gl_transactions.*',
            'chart_accounts.account_name',
            'chart_accounts.account_code',
            'bank_accounts.name as bank_account_name',
            'bank_accounts.account_number',
            'bank_accounts.id as bank_account_id',
            'customers.name as customer_name'
        )
        ->orderBy('gl_transactions.date', 'asc')
        ->orderBy('gl_transactions.id', 'asc')
        ->get();
        
        \Log::info('Cash Book - Transactions Query Result', [
            'transactions_count' => $transactions->count(),
            'chart_account_ids' => $chartAccountIds,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'branch_id' => $branchId,
            'sample_transactions' => $transactions->take(5)->map(function($t) {
                return [
                    'id' => $t->id,
                    'date' => $t->date,
                    'chart_account_id' => $t->chart_account_id,
                    'transaction_type' => $t->transaction_type,
                    'transaction_id' => $t->transaction_id,
                    'nature' => $t->nature,
                    'amount' => $t->amount,
                    'branch_id' => $t->branch_id
                ];
            })
        ]);

        // Debug: Check for POS sale transactions that should appear
        $debugEndDateWithTime = $endDate . ' 23:59:59';
        $debugPosSales = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->leftJoin('bank_accounts', 'chart_accounts.id', '=', 'bank_accounts.chart_account_id')
            ->where('gl_transactions.transaction_type', 'pos_sale')
            ->where('gl_transactions.date', '>=', $startDate)
            ->where('gl_transactions.date', '<=', $debugEndDateWithTime)
            ->select(
                'gl_transactions.id',
                'gl_transactions.transaction_id',
                'gl_transactions.chart_account_id',
                'gl_transactions.date',
                'gl_transactions.nature',
                'gl_transactions.amount',
                'gl_transactions.branch_id',
                'gl_transactions.customer_id',
                'chart_accounts.account_name',
                'chart_accounts.account_code',
                'bank_accounts.id as bank_account_id',
                'bank_accounts.name as bank_account_name',
                'bank_accounts.account_number'
            )
            ->get();

        // Check specifically for the POS sale transaction with account code 1003
        $debugAccount1003 = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->leftJoin('bank_accounts', 'chart_accounts.id', '=', 'bank_accounts.chart_account_id')
            ->where('gl_transactions.transaction_type', 'pos_sale')
            ->where('chart_accounts.account_code', '1003')
            ->where('gl_transactions.date', '>=', $startDate)
            ->where('gl_transactions.date', '<=', $debugEndDateWithTime)
            ->select(
                'gl_transactions.id',
                'gl_transactions.transaction_id',
                'gl_transactions.chart_account_id',
                'chart_accounts.id as chart_account_table_id',
                'chart_accounts.account_code',
                'chart_accounts.account_name',
                'bank_accounts.id as bank_account_id',
                'bank_accounts.chart_account_id as bank_account_chart_account_id',
                'bank_accounts.name as bank_account_name'
            )
            ->get();

        \Log::info('Cash Book Report Query Debug', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'bank_account_id' => $bankAccountId,
            'branch_id' => $branchId,
            'transactions_count' => $transactions->count(),
            'pos_sales_count' => $transactions->where('transaction_type', 'pos_sale')->count(),
            'debug_pos_sales_count' => $debugPosSales->count(),
            'debug_pos_sales' => $debugPosSales->toArray(),
            'debug_account_1003' => $debugAccount1003->toArray(),
            'sample_pos_sale' => $transactions->where('transaction_type', 'pos_sale')->first(),
        ]);

        // Process transactions
        $processedTransactions = [];
        $runningBalance = $openingBalance;
        $totalReceipts = 0;
        $totalPayments = 0;

        foreach ($transactions as $transaction) {
            $debit = $transaction->nature === 'debit' ? $transaction->amount : 0;
            $credit = $transaction->nature === 'credit' ? $transaction->amount : 0;

            $totalReceipts += $debit;
            $totalPayments += $credit;
            $runningBalance += $debit - $credit;

            // Determine description and reference based on transaction type
            $description = $transaction->description ?? 'Transaction';
            $reference = '';
            $transactionNo = $transaction->transaction_type . '-' . $transaction->transaction_id;

            // Enhance description for POS sales
            $customerName = $transaction->customer_name ?? 'N/A';
            if ($transaction->transaction_type === 'pos_sale') {
                try {
                    $posSale = \App\Models\Sales\PosSale::find($transaction->transaction_id);
                    if ($posSale) {
                        // Use the POS sale number in the description
                        // For walk-in customers, customer_id is null, so use customer_name from pos_sales table
                        if ($posSale->customer_id) {
                            $customerName = $posSale->customer ? $posSale->customer->name : 'Unknown Customer';
                        } else {
                            // Walk-in customer - use customer_name from pos_sales table or default
                            $customerName = $posSale->customer_name ?? 'Walk-in Customer';
                        }
                        $description = "POS sale #{$posSale->pos_number} - {$customerName}";
                        $reference = $posSale->pos_number;
                    } else {
                        // POS sale not found, but keep the description from GL transaction
                        $customerName = $transaction->customer_name ?? 'Walk-in Customer';
                    }
                } catch (\Exception $e) {
                    // If POS sale not found, use the existing description
                    \Log::warning('POS Sale not found for GL transaction', [
                        'transaction_id' => $transaction->transaction_id,
                        'gl_transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                    $customerName = $transaction->customer_name ?? 'Walk-in Customer';
                }
            }

            // Get bank account name and account number (should always be present since we join with bank_accounts)
            $bankAccountName = $transaction->bank_account_name ?? 'N/A';
            $accountNumber = $transaction->account_number ?? '';

            $processedTransactions[] = [
                'date' => $transaction->date,
                'description' => $description,
                'customer_name' => $customerName,
                'bank_account' => $bankAccountName,
                'account_number' => $accountNumber,
                'transaction_no' => $transactionNo,
                'reference_no' => $reference,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance
            ];
        }

        return [
            'opening_balance' => $openingBalance,
            'transactions' => $processedTransactions,
            'total_receipts' => $totalReceipts,
            'total_payments' => $totalPayments,
            'final_balance' => $runningBalance,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'bank_account_id' => $bankAccountId,
            'branch_id' => $branchId
        ];
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        // Get filter parameters
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $bankAccountId = $request->get('bank_account_id', 'all');
        $branchId = $request->get('branch_id', 'all');
        $exportType = $request->get('export_type', 'pdf');

        // Get cash book data
        $cashBookData = $this->getCashBookData($startDate, $endDate, $bankAccountId, $branchId);

        if ($exportType === 'pdf') {
            return $this->exportPdf($cashBookData, $company, $startDate, $endDate);
        } else {
            return $this->exportExcel($cashBookData, $company, $startDate, $endDate);
        }
    }

    private function exportPdf($cashBookData, $company, $startDate, $endDate)
    {
        // Determine branch name for header
        $user = Auth::user();
        $branches = $user->branches()
            ->where('branches.company_id', $company->id)
            ->select('branches.id', 'branches.name')
            ->get();

        $branchId = $cashBookData['branch_id'] ?? null;
        $branchName = 'All Branches';
        if ($branchId && $branchId !== 'all') {
            $branch = $branches->firstWhere('id', $branchId);
            $branchName = $branch->name ?? 'Unknown Branch';
        } elseif (($branches->count() ?? 0) <= 1 && $branchId === 'all') {
            $branchName = optional($branches->first())->name ?? 'All Branches';
        }

        $pdf = Pdf::loadView('accounting.reports.cash-book.pdf', [
            'cashBookData' => $cashBookData,
            'company' => $company,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'branchName' => $branchName
        ]);

        $pdf->setPaper('A4', 'landscape');
        $filename = 'cash_book_' . $startDate . '_to_' . $endDate . '.pdf';
        return $pdf->download($filename);
    }

    private function exportExcel($cashBookData, $company, $startDate, $endDate)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', $company->name ?? 'SmartFinance');
        $sheet->setCellValue('A2', 'CASH BOOK');
        $sheet->setCellValue('A3', 'Period: ' . Carbon::parse($startDate)->format('M d, Y') . ' to ' . Carbon::parse($endDate)->format('M d, Y'));

        // Set column headers
        $sheet->setCellValue('A5', 'DATE');
        $sheet->setCellValue('B5', 'DESCRIPTION');
        $sheet->setCellValue('C5', 'BANK ACCOUNT');
        $sheet->setCellValue('D5', 'TRANSACTION NO');
        $sheet->setCellValue('E5', 'REFERENCE NO.');
        $sheet->setCellValue('G5', 'DEBIT');
        $sheet->setCellValue('F5', 'CREDIT');
        $sheet->setCellValue('H5', 'BALANCE');

        $row = 6;

        // Opening balance
        $sheet->setCellValue('A' . $row, 'Opening Balance');
        $sheet->setCellValue('H' . $row, number_format($cashBookData['opening_balance'], 2));
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
        $row++;

        // Transactions
        foreach ($cashBookData['transactions'] as $transaction) {
            $sheet->setCellValue('A' . $row, Carbon::parse($transaction['date'])->format('d/m/Y'));
            $sheet->setCellValue('B' . $row, $transaction['description']);
            $sheet->setCellValue('C' . $row, $transaction['bank_account']);
            $sheet->setCellValue('D' . $row, $transaction['transaction_no']);
            $sheet->setCellValue('E' . $row, $transaction['reference_no']);
            $sheet->setCellValue('G' . $row, $transaction['debit'] > 0 ? number_format($transaction['debit'], 2) : '');
            $sheet->setCellValue('F' . $row, $transaction['credit'] > 0 ? number_format($transaction['credit'], 2) : '');
            $sheet->setCellValue('H' . $row, number_format($transaction['balance'], 2));
            $row++;
        }

        // Totals
        $sheet->setCellValue('A' . $row, 'Total Debit');
        $sheet->setCellValue('F' . $row, number_format($cashBookData['total_receipts'], 2));
        $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Credit');
        $sheet->setCellValue('G' . $row, number_format($cashBookData['total_payments'], 2));
        $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Final Balance');
        $sheet->setCellValue('H' . $row, number_format($cashBookData['final_balance'], 2));
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'cash_book_' . $startDate . '_to_' . $endDate . '.xlsx';
        
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'cash_book');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend();
    }
}
