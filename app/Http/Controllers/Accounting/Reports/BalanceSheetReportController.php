<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BalanceSheetReportController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('view balance sheet report')) {
            abort(403, 'Unauthorized access to this report.');
        }
        
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            abort(404, 'Company not found.');
        }
        
        // Get branches visible to the user: only assigned branches
        $branches = $user->branches()
            ->where('branches.company_id', $company->id)
            ->select('branches.id', 'branches.name')
            ->get();
        
        // Set default values
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));
        $reportingType = $request->get('reporting_type', 'accrual'); // accrual or cash
        $reportType = $request->get('report_type', 'detailed'); // summary or detailed
        $branchParam = $request->get('branch_id');
        $branchId = ($branches->count() > 1 && $branchParam === 'all') ? 'all' : ($branchParam ?: ($branches->first()->id ?? null));
        
        // Get permitted branch IDs
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }
        
        // Get current period data (as of date)
        // Use null for start date to show cumulative balances from beginning, not just YTD
        $currentYear = Carbon::parse($asOfDate)->year;
        $ytdStart = null; // Changed from startOfYear() to show all transactions up to as of date
        $financialReportData = $this->getFinancialReportData($branchId, $permittedBranchIds, $ytdStart, $asOfDate, $reportingType);
        
        // Calculate YTD profit/loss for equity
        $netProfitYtd = $financialReportData['profitLoss'] ?? 0;
        
        // Get previous year comparative data (same date in previous year) - default comparative
        $previousYearDate = Carbon::parse($asOfDate)->subYear()->format('Y-m-d');
        $previousYearYtdStart = null; // Changed from startOfYear() to show all transactions up to previous year date
        $previousYearData = $this->getPreviousYearData($branchId, $permittedBranchIds, $previousYearYtdStart, $previousYearDate, $reportingType);
        
        // Get comparative dates from request
        $comparativeDates = $request->get('comparative_dates', []);
        $comparativeData = [];
        
        foreach ($comparativeDates as $idx => $compDate) {
            if (!empty($compDate['as_of_date'])) {
                $compAsOfDate = $compDate['as_of_date'];
                $compName = $compDate['name'] ?? ('Comparative ' . ($idx + 1));
                $compYtdStart = null; // Changed from startOfYear() to show all transactions up to comparative date
                $compData = $this->getFinancialReportData($branchId, $permittedBranchIds, $compYtdStart, $compAsOfDate, $reportingType);
                $compNetProfitYtd = $compData['profitLoss'] ?? 0;
                
                $comparativeData[$compName] = [
                    'data' => $compData,
                    'netProfitYtd' => $compNetProfitYtd,
                    'asOfDate' => $compAsOfDate
                ];
            }
        }
        
        return view('accounting.reports.balance-sheet.index', compact(
            'branches',
            'asOfDate',
            'reportingType',
            'reportType',
            'branchId',
            'financialReportData',
            'previousYearData',
            'comparativeData',
            'comparativeDates',
            'netProfitYtd',
            'user',
            'company'
        ));
    }
    
    private function getFinancialReportData($branchId = null, array $permittedBranchIds = [], $startDate = null, $endDate = null, $reportingType = 'accrual')
    {
        $company = auth()->user()->company;
        
        // Get all chart accounts with their balances grouped by account class
        // Using hierarchical structure: main_groups -> fslis (account_class_groups) -> accounts
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('main_groups', 'account_class_groups.main_group_id', '=', 'main_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id);
        
        // Apply date filter
        // If startDate is null, show all transactions up to endDate (cumulative balance as of date)
        // If both dates provided, show transactions between dates (YTD)
        if ($startDate && $endDate) {
            $query->whereBetween(DB::raw('DATE(gl_transactions.date)'), [$startDate, $endDate]);
        } elseif ($endDate) {
            $query->where(DB::raw('DATE(gl_transactions.date)'), '<=', $endDate);
        }

        // Add branch filter
        if (!empty($permittedBranchIds)) {
            if ($branchId === 'all') {
                $query->whereIn('gl_transactions.branch_id', $permittedBranchIds);
            } elseif ($branchId) {
                $query->where('gl_transactions.branch_id', $branchId)
                      ->whereIn('gl_transactions.branch_id', $permittedBranchIds);
            } else {
                $query->whereIn('gl_transactions.branch_id', $permittedBranchIds);
            }
        } elseif ($branchId && $branchId !== 'all') {
            $query->where('gl_transactions.branch_id', $branchId);
        }
        
        // Add reporting type filter (cash vs accrual)
        if ($reportingType === 'cash') {
            // For cash basis, only include transactions that involve bank accounts
            $query->whereExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('gl_transactions as gl2')
                    ->whereColumn('gl2.transaction_id', 'gl_transactions.transaction_id')
                    ->whereColumn('gl2.transaction_type', 'gl_transactions.transaction_type')
                    ->whereIn('gl2.chart_account_id', function($bankSubquery) {
                        $bankSubquery->select('chart_account_id')
                            ->from('bank_accounts');
                    });
            });
        }
        
        $query->select(
                'chart_accounts.id as account_id',
                'chart_accounts.account_name as account',
                'chart_accounts.account_code',
                'account_class.name as class_name',
                'account_class_groups.id as fsli_id',
                'account_class_groups.name as fsli_name',
                'main_groups.id as main_group_id',
                'main_groups.name as main_group_name',
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE 0 END) as debit_total'),
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE 0 END) as credit_total')
            )
            ->groupBy('chart_accounts.id', 'chart_accounts.account_name', 'chart_accounts.account_code', 
                     'account_class.name', 'account_class_groups.id', 'account_class_groups.name',
                     'main_groups.id', 'main_groups.name');

        $chartAccountsData = $query->get();
            
        // Group by account class using hierarchical structure: main_groups -> fslis -> accounts
        $chartAccountsAssets = [];
        $chartAccountsLiabilities = [];
        $chartAccountsEquitys = [];
        $chartAccountsRevenues = [];
        $chartAccountsExpense = [];
        
        foreach ($chartAccountsData as $account) {
            // Calculate balance based on account class
            $balance = 0;
            
            // Get main group name (fallback to 'Uncategorized' if null)
            $mainGroupName = $account->main_group_name ?? 'Uncategorized';
            $fsliName = $account->fsli_name ?? 'Uncategorized';
            
            // Categorize based on account class
            switch (strtolower($account->class_name)) {
                case 'assets':
                    $balance = $account->debit_total - $account->credit_total; // Assets: debit increases
                    $chartAccountsAssets[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($chartAccountsAssets[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $chartAccountsAssets[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $chartAccountsAssets[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($chartAccountsAssets[$mainGroupName]['total'])) {
                        $chartAccountsAssets[$mainGroupName]['total'] = 0;
                    }
                    $chartAccountsAssets[$mainGroupName]['total'] += $balance;
                    break;
                case 'liabilities':
                    $balance = $account->credit_total - $account->debit_total; // Liabilities: credit increases
                    $chartAccountsLiabilities[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($chartAccountsLiabilities[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $chartAccountsLiabilities[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $chartAccountsLiabilities[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($chartAccountsLiabilities[$mainGroupName]['total'])) {
                        $chartAccountsLiabilities[$mainGroupName]['total'] = 0;
                    }
                    $chartAccountsLiabilities[$mainGroupName]['total'] += $balance;
                    break;
                case 'equity':
                    $balance = $account->credit_total - $account->debit_total; // Equity: credit increases
                    $chartAccountsEquitys[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($chartAccountsEquitys[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $chartAccountsEquitys[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $chartAccountsEquitys[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($chartAccountsEquitys[$mainGroupName]['total'])) {
                        $chartAccountsEquitys[$mainGroupName]['total'] = 0;
                    }
                    $chartAccountsEquitys[$mainGroupName]['total'] += $balance;
                    break;
                case 'income':
                case 'revenue':
                    $balance = $account->credit_total - $account->debit_total; // Revenue: credit increases
                    $chartAccountsRevenues[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($chartAccountsRevenues[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $chartAccountsRevenues[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $chartAccountsRevenues[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($chartAccountsRevenues[$mainGroupName]['total'])) {
                        $chartAccountsRevenues[$mainGroupName]['total'] = 0;
                    }
                    $chartAccountsRevenues[$mainGroupName]['total'] += $balance;
                    break;
                case 'expenses':
                case 'expense':
                    $balance = $account->debit_total - $account->credit_total; // Expenses: debit increases
                    $chartAccountsExpense[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($chartAccountsExpense[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $chartAccountsExpense[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $chartAccountsExpense[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($chartAccountsExpense[$mainGroupName]['total'])) {
                        $chartAccountsExpense[$mainGroupName]['total'] = 0;
                    }
                    $chartAccountsExpense[$mainGroupName]['total'] += $balance;
                    break;
            }
        }
        
        // Calculate profit/loss (sum all main group totals) - cumulative from beginning to as of date
        $sumRevenue = collect($chartAccountsRevenues)->sum(function($mainGroup) {
            return $mainGroup['total'] ?? 0;
        });
        $sumExpense = collect($chartAccountsExpense)->sum(function($mainGroup) {
            return $mainGroup['total'] ?? 0;
        });
        $profitLoss = $sumRevenue - $sumExpense;
        
        return [
            'chartAccountsAssets' => $chartAccountsAssets,
            'chartAccountsLiabilities' => $chartAccountsLiabilities,
            'chartAccountsEquitys' => $chartAccountsEquitys,
            'chartAccountsRevenues' => $chartAccountsRevenues,
            'chartAccountsExpense' => $chartAccountsExpense,
            'profitLoss' => $profitLoss
        ];
    }
    
    private function getPreviousYearData($branchId = null, array $permittedBranchIds = [], $startDate = null, $endDate = null, $reportingType = 'accrual')
    {
        $company = auth()->user()->company;
        
        // Get previous year financial data by account
        // Using hierarchical structure: main_groups -> fslis (account_class_groups) -> accounts
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('main_groups', 'account_class_groups.main_group_id', '=', 'main_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id);
        
        // Apply date filter
        // If startDate is null, show all transactions up to endDate (cumulative balance as of date)
        // If both dates provided, show transactions between dates (YTD)
        if ($startDate && $endDate) {
            $query->whereBetween(DB::raw('DATE(gl_transactions.date)'), [$startDate, $endDate]);
        } elseif ($endDate) {
            $query->where(DB::raw('DATE(gl_transactions.date)'), '<=', $endDate);
        }

        // Add branch filter
        if (!empty($permittedBranchIds)) {
            if ($branchId === 'all') {
                $query->whereIn('gl_transactions.branch_id', $permittedBranchIds);
            } elseif ($branchId) {
                $query->where('gl_transactions.branch_id', $branchId)
                      ->whereIn('gl_transactions.branch_id', $permittedBranchIds);
            } else {
                $query->whereIn('gl_transactions.branch_id', $permittedBranchIds);
            }
        } elseif ($branchId && $branchId !== 'all') {
            $query->where('gl_transactions.branch_id', $branchId);
        }
        
        // Add reporting type filter (cash vs accrual)
        if ($reportingType === 'cash') {
            // For cash basis, only include transactions that involve bank accounts
            $query->whereExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('gl_transactions as gl2')
                    ->whereColumn('gl2.transaction_id', 'gl_transactions.transaction_id')
                    ->whereColumn('gl2.transaction_type', 'gl_transactions.transaction_type')
                    ->whereIn('gl2.chart_account_id', function($bankSubquery) {
                        $bankSubquery->select('chart_account_id')
                            ->from('bank_accounts');
                    });
            });
        }
        
        $query->select(
                'chart_accounts.id as account_id',
                'chart_accounts.account_name as account',
                'chart_accounts.account_code',
                'account_class.name as class_name',
                'account_class_groups.id as fsli_id',
                'account_class_groups.name as fsli_name',
                'main_groups.id as main_group_id',
                'main_groups.name as main_group_name',
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE 0 END) as debit_total'),
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE 0 END) as credit_total')
            )
            ->groupBy('chart_accounts.id', 'chart_accounts.account_name', 'chart_accounts.account_code', 
                     'account_class.name', 'account_class_groups.id', 'account_class_groups.name',
                     'main_groups.id', 'main_groups.name');

        $previousYearData = $query->get();
            
        // Group by account class using hierarchical structure: main_groups -> fslis -> accounts
        $previousYearAssets = [];
        $previousYearLiabilities = [];
        $previousYearEquitys = [];
        $previousYearRevenues = [];
        $previousYearExpense = [];
        
        foreach ($previousYearData as $account) {
            // Calculate balance based on account class
            $balance = 0;
            
            // Get main group name (fallback to 'Uncategorized' if null)
            $mainGroupName = $account->main_group_name ?? 'Uncategorized';
            $fsliName = $account->fsli_name ?? 'Uncategorized';
            
            // Categorize based on account class
            switch (strtolower($account->class_name)) {
                case 'assets':
                    $balance = $account->debit_total - $account->credit_total; // Assets: debit increases
                    $previousYearAssets[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($previousYearAssets[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $previousYearAssets[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $previousYearAssets[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($previousYearAssets[$mainGroupName]['total'])) {
                        $previousYearAssets[$mainGroupName]['total'] = 0;
                    }
                    $previousYearAssets[$mainGroupName]['total'] += $balance;
                    break;
                case 'liabilities':
                    $balance = $account->credit_total - $account->debit_total; // Liabilities: credit increases
                    $previousYearLiabilities[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($previousYearLiabilities[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $previousYearLiabilities[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $previousYearLiabilities[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($previousYearLiabilities[$mainGroupName]['total'])) {
                        $previousYearLiabilities[$mainGroupName]['total'] = 0;
                    }
                    $previousYearLiabilities[$mainGroupName]['total'] += $balance;
                    break;
                case 'equity':
                    $balance = $account->credit_total - $account->debit_total; // Equity: credit increases
                    $previousYearEquitys[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($previousYearEquitys[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $previousYearEquitys[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $previousYearEquitys[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($previousYearEquitys[$mainGroupName]['total'])) {
                        $previousYearEquitys[$mainGroupName]['total'] = 0;
                    }
                    $previousYearEquitys[$mainGroupName]['total'] += $balance;
                    break;
                case 'income':
                case 'revenue':
                    $balance = $account->credit_total - $account->debit_total; // Revenue: credit increases
                    $previousYearRevenues[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($previousYearRevenues[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $previousYearRevenues[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $previousYearRevenues[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($previousYearRevenues[$mainGroupName]['total'])) {
                        $previousYearRevenues[$mainGroupName]['total'] = 0;
                    }
                    $previousYearRevenues[$mainGroupName]['total'] += $balance;
                    break;
                case 'expenses':
                case 'expense':
                    $balance = $account->debit_total - $account->credit_total; // Expenses: debit increases
                    $previousYearExpense[$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                        'account_id' => $account->account_id,
                        'account' => $account->account,
                        'account_code' => $account->account_code ?? '',
                        'sum' => $balance
                    ];
                    // Calculate totals
                    if (!isset($previousYearExpense[$mainGroupName]['fslis'][$fsliName]['total'])) {
                        $previousYearExpense[$mainGroupName]['fslis'][$fsliName]['total'] = 0;
                    }
                    $previousYearExpense[$mainGroupName]['fslis'][$fsliName]['total'] += $balance;
                    if (!isset($previousYearExpense[$mainGroupName]['total'])) {
                        $previousYearExpense[$mainGroupName]['total'] = 0;
                    }
                    $previousYearExpense[$mainGroupName]['total'] += $balance;
                    break;
            }
        }
        
        // Calculate previous year profit/loss (sum all main group totals) - cumulative from beginning to previous year date
        $sumRevenue = collect($previousYearRevenues)->sum(function($mainGroup) {
            return $mainGroup['total'] ?? 0;
        });
        $sumExpense = collect($previousYearExpense)->sum(function($mainGroup) {
            return $mainGroup['total'] ?? 0;
        });
        $previousYearProfitLoss = $sumRevenue - $sumExpense;
        
        $previousYear = Carbon::parse($endDate)->year;
        
        return [
            'year' => $previousYear,
            'chartAccountsAssets' => $previousYearAssets,
            'chartAccountsLiabilities' => $previousYearLiabilities,
            'chartAccountsEquitys' => $previousYearEquitys,
            'chartAccountsRevenues' => $previousYearRevenues,
            'chartAccountsExpense' => $previousYearExpense,
            'profitLoss' => $previousYearProfitLoss
        ];
    }
    
    public function export(Request $request)
    {
        if (!auth()->user()->can('view balance sheet report')) {
            abort(403, 'Unauthorized access to this report.');
        }
        
        $user = Auth::user();
        $company = $user->company;
        
        if (!$company) {
            abort(404, 'Company not found.');
        }
        
        // Get filter parameters
        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));
        $reportingType = $request->get('reporting_type', 'accrual');
        $reportType = $request->get('report_type', 'detailed');
        $branchParam = $request->get('branch_id');
        $exportType = $request->get('export_type', 'pdf');
        
        // Get branches
        $branches = $user->branches()
            ->where('branches.company_id', $company->id)
            ->select('branches.id', 'branches.name')
            ->get();
        
        $branchId = ($branches->count() > 1 && $branchParam === 'all') ? 'all' : ($branchParam ?: ($branches->first()->id ?? null));
        
        // Get permitted branch IDs
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }
        
        // Get current period data
        $ytdStart = null; // Changed from startOfYear() to show all transactions up to as of date
        $financialReportData = $this->getFinancialReportData($branchId, $permittedBranchIds, $ytdStart, $asOfDate, $reportingType);
        $netProfitYtd = $financialReportData['profitLoss'] ?? 0;
        
        // Get previous year comparative data
        $previousYearDate = Carbon::parse($asOfDate)->subYear()->format('Y-m-d');
        $previousYearYtdStart = null; // Changed from startOfYear() to show all transactions up to previous year date
        $previousYearData = $this->getPreviousYearData($branchId, $permittedBranchIds, $previousYearYtdStart, $previousYearDate, $reportingType);
        
        // Get comparative dates from request
        $comparativeDates = $request->get('comparative_dates', []);
        $comparativeData = [];
        
        foreach ($comparativeDates as $idx => $compDate) {
            if (!empty($compDate['as_of_date'])) {
                $compAsOfDate = $compDate['as_of_date'];
                $compName = $compDate['name'] ?? ('Comparative ' . ($idx + 1));
                $compYtdStart = null; // Changed from startOfYear() to show all transactions up to comparative date
                $compData = $this->getFinancialReportData($branchId, $permittedBranchIds, $compYtdStart, $compAsOfDate, $reportingType);
                $compNetProfitYtd = $compData['profitLoss'] ?? 0;
                
                $comparativeData[$compName] = [
                    'data' => $compData,
                    'netProfitYtd' => $compNetProfitYtd,
                    'asOfDate' => $compAsOfDate
                ];
            }
        }
        
        if ($exportType === 'excel') {
            return $this->exportExcel($financialReportData, $previousYearData, $comparativeData, $netProfitYtd, $asOfDate, $reportingType, $reportType, $branchId, $branches, $company);
        } else {
            return $this->exportPdf($financialReportData, $previousYearData, $comparativeData, $netProfitYtd, $asOfDate, $reportingType, $reportType, $branchId, $branches, $company);
        }
    }
    
    private function exportPdf($financialReportData, $previousYearData, $comparativeData, $netProfitYtd, $asOfDate, $reportingType, $reportType, $branchId, $branches, $company)
    {
        $pdf = Pdf::loadView('accounting.reports.balance-sheet.pdf', compact(
            'financialReportData',
            'previousYearData',
            'comparativeData',
            'netProfitYtd',
            'asOfDate',
            'reportingType',
            'reportType',
            'branchId',
            'branches',
            'company'
        ));
        
        $pdf->setPaper('A4', 'landscape');
        $filename = 'balance_sheet_' . date('Y-m-d', strtotime($asOfDate)) . '.pdf';
        return $pdf->download($filename);
    }
    
    private function exportExcel($financialReportData, $previousYearData, $comparativeData, $netProfitYtd, $asOfDate, $reportingType, $reportType, $branchId, $branches, $company)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Calculate number of comparative columns
        $numComparatives = count($comparativeData);
        $totalColumns = 3 + $numComparatives + 1; // Account + Current + Previous + Comparatives + Change
        
        // Helper function to get column letter
        $getColumnLetter = function($index) {
            $letters = range('A', 'Z');
            if ($index < 26) {
                return $letters[$index];
            }
            $first = $letters[floor(($index - 26) / 26)];
            $second = $letters[($index - 26) % 26];
            return $first . $second;
        };
        
        // Set title
        $lastCol = $getColumnLetter($totalColumns - 1);
        $sheet->setCellValue('A1', 'BALANCE SHEET REPORT');
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Set headers
        $sheet->setCellValue('A3', 'As of Date: ' . Carbon::parse($asOfDate)->format('d-m-Y'));
        $sheet->setCellValue('A4', 'Method: ' . ucfirst($reportingType));
        $sheet->setCellValue('A5', 'Type: ' . ucfirst($reportType));
        
        $currentBranchName = 'All Branches';
        if ($branchId && $branchId !== 'all') {
            $currentBranchName = optional($branches->firstWhere('id', $branchId))->name ?? 'All Branches';
        }
        $sheet->setCellValue('A6', 'Branch: ' . $currentBranchName);
        
        // Set column headers
        $row = 8;
        $sheet->setCellValue('A' . $row, 'Account');
        $sheet->setCellValue('B' . $row, 'Current (' . Carbon::parse($asOfDate)->format('d-m-Y') . ')');
        $sheet->setCellValue('C' . $row, 'Previous Year (' . $previousYearData['year'] . ')');
        
        $colIndex = 3; // Start after C (0=A, 1=B, 2=C)
        foreach ($comparativeData as $compName => $compInfo) {
            $colLetter = $getColumnLetter($colIndex);
            $sheet->setCellValue($colLetter . $row, $compName . ' (' . Carbon::parse($compInfo['asOfDate'])->format('d-m-Y') . ')');
            $colIndex++;
        }
        
        $changeCol = $getColumnLetter($colIndex);
        $sheet->setCellValue($changeCol . $row, 'Change');
        
        $headerRange = 'A' . $row . ':' . $changeCol . $row;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        
        $row++;
        
        // Helper function to write account row with comparative data
        $writeAccountRow = function($accountName, $currentAmount, $prevAmount, $comparativeAmounts, $row) use ($sheet, $getColumnLetter, $changeCol) {
            $sheet->setCellValue('A' . $row, $accountName);
            $sheet->setCellValue('B' . $row, $currentAmount);
            $sheet->setCellValue('C' . $row, $prevAmount);
            
            $colIndex = 3;
            foreach ($comparativeAmounts as $compName => $compAmount) {
                $colLetter = $getColumnLetter($colIndex);
                $sheet->setCellValue($colLetter . $row, $compAmount);
                $colIndex++;
            }
            
            $change = $currentAmount - $prevAmount;
            $sheet->setCellValue($changeCol . $row, $change);
        };
        
        // Assets
        $sheet->setCellValue('A' . $row, 'ASSETS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sumAsset = 0;
        $sumAssetPrev = 0;
        $comparativeAssetTotals = [];
        foreach ($comparativeData as $compName => $compInfo) {
            $comparativeAssetTotals[$compName] = 0;
        }
        
        foreach ($financialReportData['chartAccountsAssets'] as $mainGroupName => $mainGroup) {
            if (isset($mainGroup['total']) && $mainGroup['total'] != 0) {
                $sheet->setCellValue('A' . $row, $mainGroupName);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
                
                if (isset($mainGroup['fslis'])) {
                    foreach ($mainGroup['fslis'] as $fsliName => $fsli) {
                        if (isset($fsli['total']) && $fsli['total'] != 0) {
                            if ($reportType === 'detailed' && isset($fsli['accounts'])) {
                                foreach ($fsli['accounts'] as $account) {
                                    if ($account['sum'] != 0) {
                                        $sumAsset += $account['sum'];
                                        
                                        // Get previous year amount
                                        $prevYearMainGroup = $previousYearData['chartAccountsAssets'][$mainGroupName] ?? [];
                                        $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                        $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                        $prevYearAccounts = $prevYearFsli['accounts'] ?? [];
                                        $prevYearAccount = collect($prevYearAccounts)->firstWhere('account_id', $account['account_id']);
                                        $prevYearAmount = $prevYearAccount['sum'] ?? 0;
                                        $sumAssetPrev += $prevYearAmount;
                                        
                                        // Get comparative amounts
                                        $comparativeAmounts = [];
                                        foreach ($comparativeData as $compName => $compInfo) {
                                            $compMainGroup = $compInfo['data']['chartAccountsAssets'][$mainGroupName] ?? [];
                                            $compFslis = $compMainGroup['fslis'] ?? [];
                                            $compFsli = $compFslis[$fsliName] ?? [];
                                            $compAccounts = $compFsli['accounts'] ?? [];
                                            $compAccount = collect($compAccounts)->firstWhere('account_id', $account['account_id']);
                                            $compAmount = $compAccount['sum'] ?? 0;
                                            $comparativeAmounts[$compName] = $compAmount;
                                            $comparativeAssetTotals[$compName] += $compAmount;
                                        }
                                        
                                        $accountName = ($account['account_code'] ?? '') . ' - ' . $account['account'];
                                        $writeAccountRow($accountName, $account['sum'], $prevYearAmount, $comparativeAmounts, $row);
                                        $row++;
                                    }
                                }
                            } else {
                                $fsliTotal = $fsli['total'] ?? 0;
                                $sumAsset += $fsliTotal;
                                
                                $prevYearMainGroup = $previousYearData['chartAccountsAssets'][$mainGroupName] ?? [];
                                $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                $prevYearFsliTotal = $prevYearFsli['total'] ?? 0;
                                $sumAssetPrev += $prevYearFsliTotal;
                                
                                // Get comparative totals
                                $comparativeAmounts = [];
                                foreach ($comparativeData as $compName => $compInfo) {
                                    $compMainGroup = $compInfo['data']['chartAccountsAssets'][$mainGroupName] ?? [];
                                    $compFslis = $compMainGroup['fslis'] ?? [];
                                    $compFsli = $compFslis[$fsliName] ?? [];
                                    $compFsliTotal = $compFsli['total'] ?? 0;
                                    $comparativeAmounts[$compName] = $compFsliTotal;
                                    $comparativeAssetTotals[$compName] += $compFsliTotal;
                                }
                                
                                $writeAccountRow($fsliName, $fsliTotal, $prevYearFsliTotal, $comparativeAmounts, $row);
                                $row++;
                            }
                        }
                    }
                }
            }
        }
        
        // Calculate comparative totals for assets
        foreach ($comparativeData as $compName => $compInfo) {
            $compTotal = 0;
            foreach ($compInfo['data']['chartAccountsAssets'] as $compMainGroup) {
                if (isset($compMainGroup['total'])) {
                    $compTotal += $compMainGroup['total'];
                }
            }
            $comparativeAssetTotals[$compName] = $compTotal;
        }
        
        $assetChange = $sumAsset - $sumAssetPrev;
        $writeAccountRow('TOTAL ASSETS', $sumAsset, $sumAssetPrev, $comparativeAssetTotals, $row);
        $sheet->getStyle('A' . $row . ':' . $changeCol . $row)->getFont()->setBold(true);
        $row += 2;
        
        // Equity
        $sheet->setCellValue('A' . $row, 'EQUITY');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sumEquity = 0;
        $sumEquityPrev = 0;
        $comparativeEquityTotals = [];
        foreach ($comparativeData as $compName => $compInfo) {
            $comparativeEquityTotals[$compName] = 0;
        }
        
        foreach ($financialReportData['chartAccountsEquitys'] as $mainGroupName => $mainGroup) {
            if (isset($mainGroup['total']) && $mainGroup['total'] != 0) {
                $sheet->setCellValue('A' . $row, $mainGroupName);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
                
                if (isset($mainGroup['fslis'])) {
                    foreach ($mainGroup['fslis'] as $fsliName => $fsli) {
                        if (isset($fsli['total']) && $fsli['total'] != 0) {
                            if ($reportType === 'detailed' && isset($fsli['accounts'])) {
                                foreach ($fsli['accounts'] as $account) {
                                    if ($account['sum'] != 0) {
                                        $sumEquity += $account['sum'];
                                        
                                        $prevYearMainGroup = $previousYearData['chartAccountsEquitys'][$mainGroupName] ?? [];
                                        $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                        $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                        $prevYearAccounts = $prevYearFsli['accounts'] ?? [];
                                        $prevYearAccount = collect($prevYearAccounts)->firstWhere('account_id', $account['account_id']);
                                        $prevYearAmount = $prevYearAccount['sum'] ?? 0;
                                        $sumEquityPrev += $prevYearAmount;
                                        
                                        $comparativeAmounts = [];
                                        foreach ($comparativeData as $compName => $compInfo) {
                                            $compMainGroup = $compInfo['data']['chartAccountsEquitys'][$mainGroupName] ?? [];
                                            $compFslis = $compMainGroup['fslis'] ?? [];
                                            $compFsli = $compFslis[$fsliName] ?? [];
                                            $compAccounts = $compFsli['accounts'] ?? [];
                                            $compAccount = collect($compAccounts)->firstWhere('account_id', $account['account_id']);
                                            $compAmount = $compAccount['sum'] ?? 0;
                                            $comparativeAmounts[$compName] = $compAmount;
                                            $comparativeEquityTotals[$compName] += $compAmount;
                                        }
                                        
                                        $accountName = ($account['account_code'] ?? '') . ' - ' . $account['account'];
                                        $writeAccountRow($accountName, $account['sum'], $prevYearAmount, $comparativeAmounts, $row);
                                        $row++;
                                    }
                                }
                            } else {
                                $fsliTotal = $fsli['total'] ?? 0;
                                $sumEquity += $fsliTotal;
                                
                                $prevYearMainGroup = $previousYearData['chartAccountsEquitys'][$mainGroupName] ?? [];
                                $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                $prevYearFsliTotal = $prevYearFsli['total'] ?? 0;
                                $sumEquityPrev += $prevYearFsliTotal;
                                
                                $comparativeAmounts = [];
                                foreach ($comparativeData as $compName => $compInfo) {
                                    $compMainGroup = $compInfo['data']['chartAccountsEquitys'][$mainGroupName] ?? [];
                                    $compFslis = $compMainGroup['fslis'] ?? [];
                                    $compFsli = $compFslis[$fsliName] ?? [];
                                    $compFsliTotal = $compFsli['total'] ?? 0;
                                    $comparativeAmounts[$compName] = $compFsliTotal;
                                    $comparativeEquityTotals[$compName] += $compFsliTotal;
                                }
                                
                                $writeAccountRow($fsliName, $fsliTotal, $prevYearFsliTotal, $comparativeAmounts, $row);
                                $row++;
                            }
                        }
                    }
                }
            }
        }
        
        // Calculate comparative totals for equity
        foreach ($comparativeData as $compName => $compInfo) {
            $compTotal = 0;
            foreach ($compInfo['data']['chartAccountsEquitys'] as $compMainGroup) {
                if (isset($compMainGroup['total'])) {
                    $compTotal += $compMainGroup['total'];
                }
            }
            $comparativeEquityTotals[$compName] = $compTotal;
        }
        
        // Profit And Loss (YTD)
        $comparativeProfitLoss = [];
        foreach ($comparativeData as $compName => $compInfo) {
            $comparativeProfitLoss[$compName] = $compInfo['netProfitYtd'];
        }
        $writeAccountRow('Profit And Loss (YTD)', $netProfitYtd, $previousYearData['profitLoss'], $comparativeProfitLoss, $row);
        $row++;
        
        // Total Equity (including P&L)
        $totalEquityComparatives = [];
        foreach ($comparativeData as $compName => $compInfo) {
            $totalEquityComparatives[$compName] = $comparativeEquityTotals[$compName] + $compInfo['netProfitYtd'];
        }
        $writeAccountRow('TOTAL EQUITY', $sumEquity + $netProfitYtd, $sumEquityPrev + $previousYearData['profitLoss'], $totalEquityComparatives, $row);
        $sheet->getStyle('A' . $row . ':' . $changeCol . $row)->getFont()->setBold(true);
        $row += 2;
        
        // Liabilities
        $sheet->setCellValue('A' . $row, 'LIABILITIES');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sumLiability = 0;
        $sumLiabilityPrev = 0;
        $comparativeLiabilityTotals = [];
        foreach ($comparativeData as $compName => $compInfo) {
            $comparativeLiabilityTotals[$compName] = 0;
        }
        
        foreach ($financialReportData['chartAccountsLiabilities'] as $mainGroupName => $mainGroup) {
            if (isset($mainGroup['total']) && $mainGroup['total'] != 0) {
                $sheet->setCellValue('A' . $row, $mainGroupName);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
                
                if (isset($mainGroup['fslis'])) {
                    foreach ($mainGroup['fslis'] as $fsliName => $fsli) {
                        if (isset($fsli['total']) && $fsli['total'] != 0) {
                            if ($reportType === 'detailed' && isset($fsli['accounts'])) {
                                foreach ($fsli['accounts'] as $account) {
                                    if ($account['sum'] != 0) {
                                        $sumLiability += $account['sum'];
                                        
                                        $prevYearMainGroup = $previousYearData['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                        $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                        $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                        $prevYearAccounts = $prevYearFsli['accounts'] ?? [];
                                        $prevYearAccount = collect($prevYearAccounts)->firstWhere('account_id', $account['account_id']);
                                        $prevYearAmount = $prevYearAccount['sum'] ?? 0;
                                        $sumLiabilityPrev += $prevYearAmount;
                                        
                                        $comparativeAmounts = [];
                                        foreach ($comparativeData as $compName => $compInfo) {
                                            $compMainGroup = $compInfo['data']['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                            $compFslis = $compMainGroup['fslis'] ?? [];
                                            $compFsli = $compFslis[$fsliName] ?? [];
                                            $compAccounts = $compFsli['accounts'] ?? [];
                                            $compAccount = collect($compAccounts)->firstWhere('account_id', $account['account_id']);
                                            $compAmount = $compAccount['sum'] ?? 0;
                                            $comparativeAmounts[$compName] = $compAmount;
                                            $comparativeLiabilityTotals[$compName] += $compAmount;
                                        }
                                        
                                        $accountName = ($account['account_code'] ?? '') . ' - ' . $account['account'];
                                        $writeAccountRow($accountName, $account['sum'], $prevYearAmount, $comparativeAmounts, $row);
                                        $row++;
                                    }
                                }
                            } else {
                                $fsliTotal = $fsli['total'] ?? 0;
                                $sumLiability += $fsliTotal;
                                
                                $prevYearMainGroup = $previousYearData['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                $prevYearFsliTotal = $prevYearFsli['total'] ?? 0;
                                $sumLiabilityPrev += $prevYearFsliTotal;
                                
                                $comparativeAmounts = [];
                                foreach ($comparativeData as $compName => $compInfo) {
                                    $compMainGroup = $compInfo['data']['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                    $compFslis = $compMainGroup['fslis'] ?? [];
                                    $compFsli = $compFslis[$fsliName] ?? [];
                                    $compFsliTotal = $compFsli['total'] ?? 0;
                                    $comparativeAmounts[$compName] = $compFsliTotal;
                                    $comparativeLiabilityTotals[$compName] += $compFsliTotal;
                                }
                                
                                $writeAccountRow($fsliName, $fsliTotal, $prevYearFsliTotal, $comparativeAmounts, $row);
                                $row++;
                            }
                        }
                    }
                }
            }
        }
        
        // Calculate comparative totals for liabilities
        foreach ($comparativeData as $compName => $compInfo) {
            $compTotal = 0;
            foreach ($compInfo['data']['chartAccountsLiabilities'] as $compMainGroup) {
                if (isset($compMainGroup['total'])) {
                    $compTotal += $compMainGroup['total'];
                }
            }
            $comparativeLiabilityTotals[$compName] = $compTotal;
        }
        
        $writeAccountRow('TOTAL LIABILITIES', $sumLiability, $sumLiabilityPrev, $comparativeLiabilityTotals, $row);
        $sheet->getStyle('A' . $row . ':' . $changeCol . $row)->getFont()->setBold(true);
        $row++;
        
        // Total Equity & Liability
        $totalEquityLiabilityComparatives = [];
        foreach ($comparativeData as $compName => $compInfo) {
            $totalEquityLiabilityComparatives[$compName] = $comparativeLiabilityTotals[$compName] + $comparativeEquityTotals[$compName] + $compInfo['netProfitYtd'];
        }
        $writeAccountRow('TOTAL EQUITY & LIABILITY', $sumLiability + $sumEquity + $netProfitYtd, $sumLiabilityPrev + $sumEquityPrev + $previousYearData['profitLoss'], $totalEquityLiabilityComparatives, $row);
        $sheet->getStyle('A' . $row . ':' . $changeCol . $row)->getFont()->setBold(true);
        
        // Format columns
        $sheet->getColumnDimension('A')->setWidth(40);
        for ($i = 1; $i < $totalColumns; $i++) {
            $colLetter = $getColumnLetter($i);
            $sheet->getColumnDimension($colLetter)->setWidth(15);
        }
        
        // Format numbers
        $numberRange = 'B8:' . $changeCol . $row;
        $sheet->getStyle($numberRange)->getNumberFormat()->setFormatCode('#,##0.00');
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'balance_sheet_' . date('Y-m-d', strtotime($asOfDate)) . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
