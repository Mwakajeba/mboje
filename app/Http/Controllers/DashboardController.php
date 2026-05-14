<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ChartAccount;
use App\Models\AccountClassGroup;
use App\Models\GlTransaction;
use App\Models\BankReconciliation;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\Penalty;
use App\Models\Receipt;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Services\InventoryStockService;
use App\Services\InventoryCostService;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Sales\CashSale;
use App\Models\Sales\PosSale;
use Carbon\Carbon;


class DashboardController extends Controller
{
    public function index()
    {
        $startedAt = microtime(true);
        $user = auth()->user();
        $company = $user->company;
        
        if (!$company) {
            return view('dashboard', [
                'balanceSheetData' => [],
                'financialReportData' => [],
                'recentJournals' => collect(),
                'recentPayments' => collect(),
                'recentReceipts' => collect(),
                'previousYearData' => [],
                'totalInventoryValue' => 0,
                'totalInventoryItemsCount' => 0,
                'totalSalesToday' => 0,
                'grossProfitMtd' => 0,
                'totalExpensesToday' => 0,
                'outstandingInvoicesAmount' => 0,
                'outstandingInvoicesCount' => 0,
                'totalCustomers' => 0,
                'roomsOccupied' => 0,
                'totalRooms' => 0,
                'todaysBookingsValue' => 0,
                'todaysBookingsCount' => 0,
                'receivablesAging' => [],
                'branches' => collect(),
                'selectedBranchId' => null,
                'pendingApprovalsCount' => 0,
            ]);
        }
        // Resolve permitted branches for this user
        $permittedBranchIds = $this->getPermittedBranchIds($user);
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }
        // Selected branch logic: default to user's only branch, otherwise allow All (null)
        $defaultSelected = count($permittedBranchIds) === 1 ? $permittedBranchIds[0] : null;
        $selectedBranchId = request()->has('branch_id') ? request('branch_id') : $defaultSelected;
        $branchId = $this->normalizeBranchId($selectedBranchId);
        // Persist specific selection for header badge.
        // Do not clear session branch here because require.branch middleware needs it for route access.
        if ($branchId) {
            session(['branch_id' => $branchId]);
        }
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        // Get recent activities - filter by company and branch and current month
        $recentJournals = Journal::whereHas('branch', function($query) use ($company) {
            $query->where('company_id', $company->id);
        })
        ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->with(['user', 'branch'])
        ->latest()
        ->take(5)
        ->get();
        
        $recentPayments = Payment::whereHas('branch', function($query) use ($company) {
            $query->where('company_id', $company->id);
        })
        ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->with(['user', 'branch'])
        ->latest()
        ->take(5)
        ->get();
        
        $recentReceipts = Receipt::whereHas('branch', function($query) use ($company) {
            $query->where('company_id', $company->id);
        })
        ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->with(['user', 'branch', 'customer'])
        ->latest()
        ->take(5)
        ->get();
            


        
        // Restore essential financial payload (cache-backed) so dashboard is populated.
        $currentFiscalYear = FiscalYear::forCompany($company->id)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date', 'desc')
            ->first();

        if ($currentFiscalYear) {
            $ytdStart = $currentFiscalYear->start_date->toDateString();
            $ytdEnd = $today;
        } else {
            $ytdStart = now()->startOfYear()->toDateString();
            $ytdEnd = $today;
        }

        $balanceSheetData = $this->getBalanceSheetData($branchId, $permittedBranchIds);
        $financialReportData = $this->getFinancialReportData($branchId, $permittedBranchIds, $ytdEnd, $ytdEnd, $ytdStart);
        $cumulativeProfitLoss = $this->getCumulativeProfitLoss($branchId, $permittedBranchIds, $ytdEnd);
        $netProfitYtd = $financialReportData['profitLoss'] ?? 0;

        $previousYear = date('Y') - 1;
        $previousYearEndDate = Carbon::create($previousYear, 12, 31)->toDateString();
        $previousYearStartDate = Carbon::create($previousYear, 1, 1)->toDateString();
        $previousYearData = $this->getPreviousYearData($branchId, $permittedBranchIds, $previousYearEndDate, $previousYearStartDate, $previousYearEndDate);

        $totalInventoryValue = 0;
        $totalInventoryItemsCount = 0;
        $cards = json_decode($this->dashboardCardsSummary(request())->getContent(), true) ?: [];
        $totalSalesToday = (float) ($cards['totalSalesToday'] ?? 0);
        $grossProfitMtd = 0;
        $totalExpensesToday = (float) ($cards['totalExpensesToday'] ?? 0);
        $outstandingInvoicesAmount = (float) ($cards['outstandingInvoicesAmount'] ?? 0);
        $outstandingInvoicesCount = (int) ($cards['outstandingInvoicesCount'] ?? 0);
        $totalCustomers = (int) ($cards['totalCustomers'] ?? 0);
        $cashCollectedToday = (float) ($cards['cashCollectedToday'] ?? 0);
        $revenueThisMonth = (float) ($cards['revenueThisMonth'] ?? 0);
        $receivablesAging = [
            'current' => 0,
            'overdue_1_30' => 0,
            'overdue_31_60' => 0,
            'overdue_60_plus' => 0,
        ];
            
        // Branch list for filter dropdown (restricted to user's permitted branches)
        $branches = Branch::whereIn('id', $permittedBranchIds)
            ->orderBy('name')
            ->get();

        // Get pending approvals count
        $pendingApprovalsCount = \App\Http\Controllers\ApprovalQueueController::getPendingApprovalsCount($user->id);

        Log::info('dashboard.index.completed', [
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return view('dashboard', [
            'balanceSheetData' => $balanceSheetData,
            'financialReportData' => $financialReportData,
            'recentJournals' => $recentJournals,
            'recentPayments' => $recentPayments,
            'recentReceipts' => $recentReceipts,
            'previousYearData' => $previousYearData,
            'cumulativeProfitLoss' => $cumulativeProfitLoss,
            'totalInventoryValue' => $totalInventoryValue,
            'totalInventoryItemsCount' => $totalInventoryItemsCount,
            'totalSalesToday' => $totalSalesToday,
            'grossProfitMtd' => $grossProfitMtd,
            'netProfitYtd' => $netProfitYtd,
            'totalExpensesToday' => $totalExpensesToday,
            'outstandingInvoicesAmount' => $outstandingInvoicesAmount,
            'outstandingInvoicesCount' => $outstandingInvoicesCount,
            'totalCustomers' => $totalCustomers,
            'cashCollectedToday' => $cashCollectedToday,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'revenueThisMonth' => $revenueThisMonth,
            'receivablesAging' => $receivablesAging,
            'branches' => $branches,
            'selectedBranchId' => $branchId,
        ]);
    }

    /**
     * Show expiry alerts page
     */
    public function expiryAlerts()
    {
        $user = auth()->user();
        $company = $user->company;
        
        if (!$company) {
            return redirect()->back()->with('error', 'Company not found.');
        }

        // Get user's permitted branches
        $permittedBranchIds = $this->getPermittedBranchIds($user);
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }
        
        // Get branch filter from request
        $branchId = request('branch_id', session('branch_id') ?? $user->branch_id);
        
        // Get expiring items
        $expiringItems = $this->getExpiringItems($branchId, $permittedBranchIds);
        
        // Get branches for filter dropdown
        $branches = \App\Models\Branch::whereIn('id', $permittedBranchIds)
            ->orderBy('name')
            ->get();

        return view('expiry-alerts', [
            'expiringItems' => $expiringItems,
            'branches' => $branches,
            'selectedBranchId' => $branchId,
        ]);
    }

    /**
     * Get expiry alerts data for DataTables
     */
    public function expiryAlertsData()
    {
        $user = auth()->user();
        $company = $user->company;
        
        if (!$company) {
            return response()->json(['data' => []]);
        }

        // Get user's permitted branches
        $permittedBranchIds = $this->getPermittedBranchIds($user);
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }
        
        // Get branch filter from request
        $branchId = request('branch_id', session('branch_id') ?? $user->branch_id);
        
        // Get expiring items
        $expiringItems = $this->getExpiringItems($branchId, $permittedBranchIds);
        
        // Format data for DataTables
        $data = $expiringItems->map(function($item) {
            $statusBadge = '';
            if ($item['status'] === 'critical') {
                $statusBadge = '<span class="badge bg-danger"><i class="bx bx-error-circle me-1"></i>Critical</span>';
            } elseif ($item['status'] === 'warning') {
                $statusBadge = '<span class="badge bg-warning text-dark"><i class="bx bx-error me-1"></i>Warning</span>';
            } else {
                $statusBadge = '<span class="badge bg-info"><i class="bx bx-info-circle me-1"></i>Notice</span>';
            }

            $daysBadge = '';
            if ($item['status'] === 'critical') {
                $daysBadge = '<span class="badge bg-danger">' . $item['days_until_expiry'] . ' days</span>';
            } elseif ($item['status'] === 'warning') {
                $daysBadge = '<span class="badge bg-warning text-dark">' . $item['days_until_expiry'] . ' days</span>';
            } else {
                $daysBadge = '<span class="badge bg-info">' . $item['days_until_expiry'] . ' days</span>';
            }

            return [
                'item_name' => '<div><strong>' . e($item['item_name']) . '</strong><br><small class="text-muted">' . e($item['item_code']) . '</small></div>',
                'batch_number' => '<span class="badge bg-secondary">' . e($item['batch_number']) . '</span>',
                'location_name' => e($item['location_name']),
                'expiry_date' => \Carbon\Carbon::parse($item['expiry_date'])->format('M d, Y'),
                'days_until_expiry' => $daysBadge,
                'available_quantity' => '<span class="fw-bold">' . number_format($item['available_quantity'], 0) . '</span>',
                'status' => $statusBadge,
            ];
        });

        return response()->json([
            'data' => $data,
            'recordsTotal' => $data->count(),
            'recordsFiltered' => $data->count(),
        ]);
    }
    
    private function getBalanceSheetData($branchId = null, array $permittedBranchIds = [])
    {
        $company = auth()->user()->company;
        
        if (!$company) {
            return [];
        }
        
        $cacheKey = 'dashboard:balanceSheetData:' . $company->id . ':' . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds));
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($company, $branchId, $permittedBranchIds) {
        // Get balance sheet data directly from gl_transactions
        // Balance sheet shows cumulative balances up to today (no date filter)
        // This ensures all historical balances are included, including retained earnings from previous years
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            // Filter to only include transactions up to today
            ->where('gl_transactions.date', '<=', now()->endOfDay())
            ->select(
                'account_class.name as class_name',
                'account_class_groups.group_code as class_code',
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE 0 END) as total_debit'),
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE 0 END) as total_credit'),
                DB::raw('COUNT(DISTINCT chart_accounts.id) as account_count')
            )
            ->groupBy('account_class.id', 'account_class.name', 'account_class_groups.group_code');

        if (!empty($permittedBranchIds)) {
            $query->whereIn('gl_transactions.branch_id', $permittedBranchIds);
        }
        if ($branchId) {
            $query->where('gl_transactions.branch_id', $branchId);
        }

        $balanceSheetData = $query->get()
            ->map(function ($item) {
                // Calculate balance based on account class
                $balance = 0;
                switch (strtolower($item->class_name)) {
                    case 'assets':
                        $balance = $item->total_debit - $item->total_credit; // Assets: debit increases
                        break;
                    case 'liabilities':
                        $balance = $item->total_credit - $item->total_debit; // Liabilities: credit increases
                        break;
                    case 'equity':
                        $balance = $item->total_credit - $item->total_debit; // Equity: credit increases
                        break;
                    case 'income':
                    case 'revenue':
                        $balance = $item->total_credit - $item->total_debit; // Revenue: credit increases
                        break;
                    case 'expenses':
                    case 'expense':
                        $balance = $item->total_debit - $item->total_credit; // Expenses: debit increases
                        break;
                    default:
                        $balance = $item->total_debit - $item->total_credit;
                }
                
                return [
                    'class_name' => $item->class_name,
                    'class_code' => $item->class_code,
                    'balance' => $balance,
                    'account_count' => $item->account_count
                ];
            })
            ->sortByDesc(function ($item) {
                return abs($item['balance']);
            })
            ->values()
            ->toArray();
            
        return $balanceSheetData;
        });
    }
    
    private function getFinancialReportData($branchId = null, array $permittedBranchIds = [], $balanceSheetEndDate = null, $incomeStatementEndDate = null, $incomeStatementStartDate = null)
    {
        $company = auth()->user()->company;
        $cacheKey = 'dashboard:financialReportData:' . $company->id . ':' . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds)) . ':' . implode('|', [$balanceSheetEndDate, $incomeStatementEndDate, $incomeStatementStartDate]);
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($company, $branchId, $permittedBranchIds, $balanceSheetEndDate, $incomeStatementEndDate, $incomeStatementStartDate) {
        
        // Balance Sheet Query: Cumulative balances up to end date (Assets, Liabilities, Equity)
        // Balance Sheet accounts carry forward, so we need ALL transactions up to the end date
        $balanceSheetQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('main_groups', 'account_class_groups.main_group_id', '=', 'main_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('account_class.name', ['assets', 'liabilities', 'equity']);
        
        // Balance Sheet: Filter by end date only (cumulative up to that date)
        if ($balanceSheetEndDate) {
            $balanceSheetQuery->where('gl_transactions.date', '<=', Carbon::parse($balanceSheetEndDate)->endOfDay());
        }
        
        // Income Statement Query: Period-based (Revenue and Expenses)
        // Income Statement accounts reset each year, so we need transactions from start to end date
        $incomeStatementQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('main_groups', 'account_class_groups.main_group_id', '=', 'main_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->leftJoin('journals', function($join) {
                $join->on('gl_transactions.transaction_id', '=', 'journals.id')
                     ->where('gl_transactions.transaction_type', '=', 'journal');
            })
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('account_class.name', ['income', 'revenue', 'expenses', 'expense']);
        
        // Income Statement: Filter by date range (YTD: from year start to end date)
        if ($incomeStatementStartDate && $incomeStatementEndDate) {
            $incomeStatementQuery->whereBetween('gl_transactions.date', [Carbon::parse($incomeStatementStartDate)->startOfDay(), Carbon::parse($incomeStatementEndDate)->endOfDay()])
                  // Exclude year-end closing entries from income statement calculations
                  ->where(function($q) {
                      $q->whereNull('journals.reference_type')
                        ->orWhere('journals.reference_type', '!=', 'Year-End Close');
                  });
        } elseif ($incomeStatementEndDate) {
            $incomeStatementQuery->where('gl_transactions.date', '<=', Carbon::parse($incomeStatementEndDate)->endOfDay());
        }
        
        // Common select and group by
        $selectFields = [
                'chart_accounts.id as account_id',
                'chart_accounts.account_name as account',
                'chart_accounts.account_code',
                'chart_accounts.parent_id',
                'account_class.name as class_name',
                'account_class_groups.id as fsli_id',
                'account_class_groups.name as fsli_name',
                'main_groups.id as main_group_id',
                'main_groups.name as main_group_name',
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE 0 END) as debit_total'),
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE 0 END) as credit_total')
        ];
        
        $groupByFields = [
            'chart_accounts.id', 'chart_accounts.account_name', 'chart_accounts.account_code', 'chart_accounts.parent_id',
                     'account_class.name', 'account_class_groups.id', 'account_class_groups.name',
            'main_groups.id', 'main_groups.name'
        ];

        // Apply branch filters
        if (!empty($permittedBranchIds)) {
            $balanceSheetQuery->whereIn('gl_transactions.branch_id', $permittedBranchIds);
            $incomeStatementQuery->whereIn('gl_transactions.branch_id', $permittedBranchIds);
        }
        if ($branchId) {
            $balanceSheetQuery->where('gl_transactions.branch_id', $branchId);
            $incomeStatementQuery->where('gl_transactions.branch_id', $branchId);
        }

        // Execute queries
        $balanceSheetData = (clone $balanceSheetQuery)
            ->select($selectFields)
            ->groupBy($groupByFields)
            ->get();
        
        $incomeStatementData = (clone $incomeStatementQuery)
            ->select($selectFields)
            ->groupBy($groupByFields)
            ->get();
        
        // Fetch ALL accounts for this company to ensure parent accounts are included even if they have no transactions
        $allCompanyAccounts = DB::table('chart_accounts')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('main_groups', 'account_class_groups.main_group_id', '=', 'main_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->select([
                'chart_accounts.id as account_id',
                'chart_accounts.account_name as account',
                'chart_accounts.account_code',
                'chart_accounts.parent_id',
                'account_class.name as class_name',
                'account_class_groups.id as fsli_id',
                'account_class_groups.name as fsli_name',
                'main_groups.id as main_group_id',
                'main_groups.name as main_group_name',
            ])
            ->get()
            ->keyBy('account_id');

        // Combine transaction data
        $transactionBalances = $balanceSheetData->merge($incomeStatementData)->keyBy('account_id');
        
        // Map balances to the full account list
        $chartAccountsData = $allCompanyAccounts->map(function ($account) use ($transactionBalances) {
            $balanceData = $transactionBalances->get($account->account_id);
            $account->debit_total = $balanceData->debit_total ?? 0;
            $account->credit_total = $balanceData->credit_total ?? 0;
            return $account;
        });

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
                        'parent_id' => $account->parent_id,
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
                        'parent_id' => $account->parent_id,
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
                        'parent_id' => $account->parent_id,
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
                        'parent_id' => $account->parent_id,
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
                        'parent_id' => $account->parent_id,
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
        
        // Calculate profit/loss (sum all main group totals)
        $sumRevenue = collect($chartAccountsRevenues)->sum(function($mainGroup) {
            return $mainGroup['total'] ?? 0;
        });
        $sumExpense = collect($chartAccountsExpense)->sum(function($mainGroup) {
            return $mainGroup['total'] ?? 0;
        });
        $profitLoss = $sumRevenue - $sumExpense;

        // Apply nesting to all categories
        $categories = [
            'chartAccountsAssets' => &$chartAccountsAssets,
            'chartAccountsLiabilities' => &$chartAccountsLiabilities,
            'chartAccountsEquitys' => &$chartAccountsEquitys,
            'chartAccountsRevenues' => &$chartAccountsRevenues,
            'chartAccountsExpense' => &$chartAccountsExpense,
        ];

        foreach ($categories as $key => &$category) {
            foreach ($category as $mgName => &$mg) {
                if (isset($mg['fslis'])) {
                    foreach ($mg['fslis'] as $fsliName => &$fsli) {
                        if (isset($fsli['accounts'])) {
                            $fsli['accounts'] = $this->nestAccounts($fsli['accounts']);
                        }
                    }
                }
            }
        }
        
        return [
            'chartAccountsAssets' => $chartAccountsAssets,
            'chartAccountsLiabilities' => $chartAccountsLiabilities,
            'chartAccountsEquitys' => $chartAccountsEquitys,
            'chartAccountsRevenues' => $chartAccountsRevenues,
            'chartAccountsExpense' => $chartAccountsExpense,
            'profitLoss' => $profitLoss
        ];
        });
    }
    
    /**
     * Calculate cumulative profit/loss from ALL income statement transactions up to end date
     * This is used for Balance Sheet to show accumulated profits from all years
     * Excludes year-end closing entries
     */
    private function getCumulativeProfitLoss($branchId = null, array $permittedBranchIds = [], $endDate = null)
    {
        $company = auth()->user()->company;
        $cacheKey = 'dashboard:cumulativeProfitLoss:' . $company->id . ':' . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds)) . ':' . ($endDate ?: 'none');
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($company, $branchId, $permittedBranchIds, $endDate) {
        
        // Query ALL income statement transactions up to end date (cumulative)
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->leftJoin('journals', function($join) {
                $join->on('gl_transactions.transaction_id', '=', 'journals.id')
                     ->where('gl_transactions.transaction_type', '=', 'journal');
            })
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('account_class.name', ['income', 'revenue', 'expenses', 'expense']);
        
        // Filter by end date (cumulative up to that date)
        if ($endDate) {
            $query->where('gl_transactions.date', '<=', Carbon::parse($endDate)->endOfDay());
        }
        
        // Exclude year-end closing entries (these zero out accounts, so we don't want them in cumulative calculation)
        $query->where(function($q) {
            $q->whereNull('journals.reference_type')
              ->orWhere('journals.reference_type', '!=', 'Year-End Close');
        });
        
        // Apply branch filters
        if (!empty($permittedBranchIds)) {
            $query->whereIn('gl_transactions.branch_id', $permittedBranchIds);
        }
        if ($branchId) {
            $query->where('gl_transactions.branch_id', $branchId);
        }
        
        // Calculate revenue and expenses
        $revenueRow = (clone $query)
            ->whereIn('account_class.name', ['income', 'revenue'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE -gl_transactions.amount END), 0) as revenue')
            ->first();
        
        $expenseRow = (clone $query)
            ->whereIn('account_class.name', ['expenses', 'expense'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE -gl_transactions.amount END), 0) as expense')
            ->first();
        
        $revenue = (float)($revenueRow->revenue ?? 0);
        $expense = (float)($expenseRow->expense ?? 0);
        
        return $revenue - $expense;
        });
    }
    
    private function getPreviousYearData($branchId = null, array $permittedBranchIds = [], $balanceSheetEndDate = null, $incomeStatementStartDate = null, $incomeStatementEndDate = null)
    {
        $company = auth()->user()->company;
        $currentYear = date('Y');
        $previousYear = $currentYear - 1;
        $cacheKey = 'dashboard:previousYearData:' . $company->id . ':' . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds)) . ':' . implode('|', [$balanceSheetEndDate, $incomeStatementStartDate, $incomeStatementEndDate]);
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($company, $branchId, $permittedBranchIds, $balanceSheetEndDate, $incomeStatementStartDate, $incomeStatementEndDate, $previousYear) {
        
        // Balance Sheet Query: Cumulative balances up to Dec 31 of previous year
        // Balance Sheet accounts carry forward, so we need ALL transactions up to Dec 31
        $balanceSheetQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('main_groups', 'account_class_groups.main_group_id', '=', 'main_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('account_class.name', ['assets', 'liabilities', 'equity']);
        
        // Balance Sheet: Cumulative up to Dec 31 of previous year
        if ($balanceSheetEndDate) {
            $balanceSheetQuery->where('gl_transactions.date', '<=', Carbon::parse($balanceSheetEndDate)->endOfDay());
        } else {
            // Fallback: use Dec 31 of previous year
            $balanceSheetQuery->where('gl_transactions.date', '<=', Carbon::create($previousYear, 12, 31)->endOfDay());
        }
        
        // Income Statement Query: Period-based from Jan 1 to Dec 31 of previous year
        // Income Statement accounts reset each year, so we need transactions for the full previous year
        $incomeStatementQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('main_groups', 'account_class_groups.main_group_id', '=', 'main_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->leftJoin('journals', function($join) {
                $join->on('gl_transactions.transaction_id', '=', 'journals.id')
                     ->where('gl_transactions.transaction_type', '=', 'journal');
            })
            ->where('account_class_groups.company_id', $company->id)
            ->whereIn('account_class.name', ['income', 'revenue', 'expenses', 'expense']);
        
        // Income Statement: Full year (Jan 1 to Dec 31 of previous year)
        if ($incomeStatementStartDate && $incomeStatementEndDate) {
            $incomeStatementQuery->whereBetween('gl_transactions.date', [Carbon::parse($incomeStatementStartDate)->startOfDay(), Carbon::parse($incomeStatementEndDate)->endOfDay()])
                  // Exclude year-end closing entries
                  ->where(function($q) {
                      $q->whereNull('journals.reference_type')
                        ->orWhere('journals.reference_type', '!=', 'Year-End Close');
                  });
        } else {
            // Fallback: use full previous year
            $prevYearStart = \Carbon\Carbon::create($previousYear, 1, 1)->toDateString();
            $prevYearEnd = \Carbon\Carbon::create($previousYear, 12, 31)->toDateString();
            $incomeStatementQuery->whereBetween('gl_transactions.date', [Carbon::parse($prevYearStart)->startOfDay(), Carbon::parse($prevYearEnd)->endOfDay()])
                  ->where(function($q) {
                      $q->whereNull('journals.reference_type')
                        ->orWhere('journals.reference_type', '!=', 'Year-End Close');
                  });
        }
        
        // Common select and group by
        $selectFields = [
                'chart_accounts.id as account_id',
                'chart_accounts.account_name as account',
                'chart_accounts.account_code',
                'chart_accounts.parent_id',
                'account_class.name as class_name',
                'account_class_groups.id as fsli_id',
                'account_class_groups.name as fsli_name',
                'main_groups.id as main_group_id',
                'main_groups.name as main_group_name',
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE 0 END) as debit_total'),
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE 0 END) as credit_total')
        ];
        
        $groupByFields = [
            'chart_accounts.id', 'chart_accounts.account_name', 'chart_accounts.account_code', 'chart_accounts.parent_id',
                     'account_class.name', 'account_class_groups.id', 'account_class_groups.name',
            'main_groups.id', 'main_groups.name'
        ];

        // Apply branch filters
        if (!empty($permittedBranchIds)) {
            $balanceSheetQuery->whereIn('gl_transactions.branch_id', $permittedBranchIds);
            $incomeStatementQuery->whereIn('gl_transactions.branch_id', $permittedBranchIds);
        }
        if ($branchId) {
            $balanceSheetQuery->where('gl_transactions.branch_id', $branchId);
            $incomeStatementQuery->where('gl_transactions.branch_id', $branchId);
        }

        // Execute queries
        $balanceSheetData = (clone $balanceSheetQuery)
            ->select($selectFields)
            ->groupBy($groupByFields)
            ->get();
        
        $incomeStatementData = (clone $incomeStatementQuery)
            ->select($selectFields)
            ->groupBy($groupByFields)
            ->get();
        
        // Fetch ALL accounts for this company to ensure parent accounts are included even if they have no transactions
        $allCompanyAccounts = DB::table('chart_accounts')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->leftJoin('main_groups', 'account_class_groups.main_group_id', '=', 'main_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->select([
                'chart_accounts.id as account_id',
                'chart_accounts.account_name as account',
                'chart_accounts.account_code',
                'chart_accounts.parent_id',
                'account_class.name as class_name',
                'account_class_groups.id as fsli_id',
                'account_class_groups.name as fsli_name',
                'main_groups.id as main_group_id',
                'main_groups.name as main_group_name',
            ])
            ->get()
            ->keyBy('account_id');

        // Combine transaction data
        $transactionBalances = $balanceSheetData->merge($incomeStatementData)->keyBy('account_id');
        
        // Map balances to the full account list
        $previousYearDataFlat = $allCompanyAccounts->map(function ($account) use ($transactionBalances) {
            $balanceData = $transactionBalances->get($account->account_id);
            $account->debit_total = $balanceData->debit_total ?? 0;
            $account->credit_total = $balanceData->credit_total ?? 0;
            return $account;
        });
            
        // Group by account class using hierarchical structure: main_groups -> fslis -> accounts
        $previousYearAssets = [];
        $previousYearLiabilities = [];
        $previousYearEquitys = [];
        $previousYearRevenues = [];
        $previousYearExpense = [];
        
        foreach ($previousYearDataFlat as $account) {
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
                        'parent_id' => $account->parent_id,
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
                        'parent_id' => $account->parent_id,
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
                        'parent_id' => $account->parent_id,
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
                        'parent_id' => $account->parent_id,
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
                        'parent_id' => $account->parent_id,
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
        
        // Calculate previous year profit/loss (sum all main group totals)
        $sumRevenue = collect($previousYearRevenues)->sum(function($mainGroup) {
            return $mainGroup['total'] ?? 0;
        });
        $sumExpense = collect($previousYearExpense)->sum(function($mainGroup) {
            return $mainGroup['total'] ?? 0;
        });
        $previousYearProfitLoss = $sumRevenue - $sumExpense;

        // Apply nesting to all categories
        $categories = [
            'chartAccountsAssets' => &$previousYearAssets,
            'chartAccountsLiabilities' => &$previousYearLiabilities,
            'chartAccountsEquitys' => &$previousYearEquitys,
            'chartAccountsRevenues' => &$previousYearRevenues,
            'chartAccountsExpense' => &$previousYearExpense,
        ];

        foreach ($categories as $key => &$category) {
            foreach ($category as $mgName => &$mg) {
                if (isset($mg['fslis'])) {
                    foreach ($mg['fslis'] as $fsliName => &$fsli) {
                        if (isset($fsli['accounts'])) {
                            $fsli['accounts'] = $this->nestAccounts($fsli['accounts']);
                        }
                    }
                }
            }
        }
        
        return [
            'year' => $previousYear,
            'chartAccountsAssets' => $previousYearAssets,
            'chartAccountsLiabilities' => $previousYearLiabilities,
            'chartAccountsEquitys' => $previousYearEquitys,
            'chartAccountsRevenues' => $previousYearRevenues,
            'chartAccountsExpense' => $previousYearExpense,
            'profitLoss' => $previousYearProfitLoss
        ];
        });
    }

    // Lightweight KPIs endpoint (branch-aware)
    public function dashboardKpis(Request $request)
    {
        $company = auth()->user()->company;
        if (!$company) {
            return response()->json([
                'totalRevenue' => 0,
                'totalOrders' => 0,
                'avgOrderValue' => 0,
                'revenueChange' => 0,
                'ordersChange' => 0,
                'avgOrderChange' => 0,
                'customerSatisfaction' => 0,
                'satisfactionChange' => 0,
            ]);
        }
        $actor = auth()->user();
        $permittedBranchIds = $this->getPermittedBranchIds($actor);
        if (empty($permittedBranchIds) && $actor->branch_id) {
            $permittedBranchIds = [(int)$actor->branch_id];
        }
        $branchId = $this->resolveBranchIdFromRequest($request);
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();
        $cacheKey = "dashboard:kpis:{$company->id}:" . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds)) . ':' . $startOfMonth;

        $data = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($company, $branchId, $permittedBranchIds, $startOfMonth, $endOfMonth) {
            $totalRevenue = \App\Models\Sales\SalesInvoice::where('company_id', $company->id)
                ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
                ->whereNotIn('status', ['cancelled'])
                ->sum('total_amount');

            $totalOrders = \App\Models\Sales\SalesOrder::where('company_id', $company->id)
                ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                ->count();

            $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;

            return [
                'totalRevenue' => (float)$totalRevenue,
                'totalOrders' => (int)$totalOrders,
                'avgOrderValue' => (float)$avgOrderValue,
                'revenueChange' => 0,
                'ordersChange' => 0,
                'avgOrderChange' => 0,
                'customerSatisfaction' => 0,
                'satisfactionChange' => 0,
            ];
        });

        return response()->json($data);
    }

    // Revenue trend (current year by month)
    public function revenueTrend(Request $request)
    {
        $company = auth()->user()->company;
        $actor = auth()->user();
        $permittedBranchIds = $this->getPermittedBranchIds($actor);
        if (empty($permittedBranchIds) && $actor->branch_id) {
            $permittedBranchIds = [(int)$actor->branch_id];
        }
        $branchId = $this->resolveBranchIdFromRequest($request);
        $year = now()->year;
        $startDate = Carbon::create($year, 1, 1)->toDateString();
        $endDate = Carbon::create($year, 12, 31)->toDateString();
        $cacheKey = "dashboard:revenueTrend:{$company->id}:" . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds)) . ':' . $year;

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($company, $branchId, $permittedBranchIds, $startDate, $endDate) {
            $monthly = \App\Models\Sales\SalesInvoice::query()
                ->where('company_id', $company->id)
                ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->whereNotIn('status', ['cancelled'])
                ->selectRaw('MONTH(invoice_date) as month_no, SUM(total_amount) as total')
                ->groupBy('month_no')
                ->pluck('total', 'month_no');

            $labels = [];
            $data = [];
            for ($m = 1; $m <= 12; $m++) {
                $labels[] = date('M', mktime(0, 0, 0, $m, 1));
                $data[] = (float)($monthly[$m] ?? 0);
            }

            return [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Revenue',
                    'data' => $data,
                    'borderColor' => '#2ecc71',
                    'backgroundColor' => 'rgba(46,204,113,0.2)',
                ]],
            ];
        });

        return response()->json($payload);
    }

    // Order status distribution (current month)
    public function orderStatusDistribution(Request $request)
    {
        $company = auth()->user()->company;
        $actor = auth()->user();
        $permittedBranchIds = $this->getPermittedBranchIds($actor);
        if (empty($permittedBranchIds) && $actor->branch_id) {
            $permittedBranchIds = [(int)$actor->branch_id];
        }
        $branchId = $this->resolveBranchIdFromRequest($request);
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $statuses = ['draft','confirmed','invoiced','cancelled'];
        $values = [];
        foreach ($statuses as $status) {
            $values[] = \App\Models\Sales\SalesOrder::where('company_id', $company->id)
                ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                ->where('status', $status)
                ->count();
        }

        return response()->json([
            'labels' => array_map('ucfirst', $statuses),
            'datasets' => [[ 'data' => $values, 'backgroundColor' => ['#3498db','#2ecc71','#9b59b6','#e74c3c'] ]],
        ]);
    }

    // Top products (current month by quantity)
    public function topProducts(Request $request)
    {
        $company = auth()->user()->company;
        $actor = auth()->user();
        $permittedBranchIds = $this->getPermittedBranchIds($actor);
        if (empty($permittedBranchIds) && $actor->branch_id) {
            $permittedBranchIds = [(int)$actor->branch_id];
        }
        $branchId = $this->resolveBranchIdFromRequest($request);
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        // Query for sales invoice items
        $invoiceItems = \DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->leftJoin('inventory_items as ii', 'sii.inventory_item_id', '=', 'ii.id')
            ->where('si.company_id', $company->id)
            ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('si.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('si.branch_id', $branchId))
            ->whereBetween('si.invoice_date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('si.status', ['cancelled'])
            ->groupBy('ii.name', 'sii.item_name')
            ->select(\DB::raw("COALESCE(ii.name, sii.item_name) as name"), \DB::raw('SUM(sii.quantity) as qty'))
            ->get();

        // Query for POS sale items
        $posItems = \DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'psi.pos_sale_id', '=', 'ps.id')
            ->leftJoin('inventory_items as ii', 'psi.inventory_item_id', '=', 'ii.id')
            ->where('ps.company_id', $company->id)
            ->whereNull('ps.deleted_at') // Filter out soft deleted POS sales
            ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('ps.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('ps.branch_id', $branchId))
            ->whereBetween('ps.sale_date', [$startOfMonth, $endOfMonth])
            ->groupBy('ii.name', 'psi.item_name')
            ->select(\DB::raw("COALESCE(ii.name, psi.item_name) as name"), \DB::raw('SUM(psi.quantity) as qty'))
            ->get();

        // Query for cash sale items
        $cashItems = \DB::table('cash_sale_items as csi')
            ->join('cash_sales as cs', 'csi.cash_sale_id', '=', 'cs.id')
            ->leftJoin('inventory_items as ii', 'csi.inventory_item_id', '=', 'ii.id')
            ->where('cs.company_id', $company->id)
            ->whereNull('csi.deleted_at') // Filter out soft deleted cash sale items
            ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('cs.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('cs.branch_id', $branchId))
            ->whereBetween('cs.sale_date', [$startOfMonth, $endOfMonth])
            ->groupBy('ii.name', 'csi.item_name')
            ->select(\DB::raw("COALESCE(ii.name, csi.item_name) as name"), \DB::raw('SUM(csi.quantity) as qty'))
            ->get();

        // Combine and aggregate results
        $rows = collect([...$invoiceItems, ...$posItems, ...$cashItems])
            ->groupBy('name')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->name,
                    'qty' => $items->sum('qty')
                ];
            })
            ->sortByDesc('qty')
            ->take(10)
            ->values();

        return response()->json([
            'labels' => $rows->pluck('name'),
            'datasets' => [[ 'label' => 'Quantity', 'data' => $rows->pluck('qty'), 'backgroundColor' => '#27ae60' ]],
        ]);
    }

    // Top items sold (current year) structure expected by the blade
    public function topItemsSoldYear(Request $request)
    {
        $company = auth()->user()->company;
        $actor = auth()->user();
        $permittedBranchIds = $this->getPermittedBranchIds($actor);
        if (empty($permittedBranchIds) && $actor->branch_id) {
            $permittedBranchIds = [(int)$actor->branch_id];
        }
        $branchId = $this->resolveBranchIdFromRequest($request);
        $year = now()->year;

        // Query for sales invoice items
        $invoiceItems = \DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->leftJoin('inventory_items as ii', 'sii.inventory_item_id', '=', 'ii.id')
            ->where('si.company_id', $company->id)
            ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('si.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('si.branch_id', $branchId))
            ->whereYear('si.invoice_date', $year)
            ->whereNotIn('si.status', ['cancelled'])
            ->groupBy('ii.name', 'sii.item_name')
            ->select(
                \DB::raw("COALESCE(ii.name, sii.item_name) as name"),
                \DB::raw('SUM(sii.quantity) as qty')
            )
            ->get();

        // Query for POS sale items
        $posItems = \DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'psi.pos_sale_id', '=', 'ps.id')
            ->leftJoin('inventory_items as ii', 'psi.inventory_item_id', '=', 'ii.id')
            ->where('ps.company_id', $company->id)
            ->whereNull('ps.deleted_at') // Filter out soft deleted POS sales
            ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('ps.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('ps.branch_id', $branchId))
            ->whereYear('ps.sale_date', $year)
            ->groupBy('ii.name', 'psi.item_name')
            ->select(
                \DB::raw("COALESCE(ii.name, psi.item_name) as name"),
                \DB::raw('SUM(psi.quantity) as qty')
            )
            ->get();

        // Query for cash sale items
        $cashItems = \DB::table('cash_sale_items as csi')
            ->join('cash_sales as cs', 'csi.cash_sale_id', '=', 'cs.id')
            ->leftJoin('inventory_items as ii', 'csi.inventory_item_id', '=', 'ii.id')
            ->where('cs.company_id', $company->id)
            ->whereNull('csi.deleted_at') // Filter out soft deleted cash sale items
            ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('cs.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('cs.branch_id', $branchId))
            ->whereYear('cs.sale_date', $year)
            ->groupBy('ii.name', 'csi.item_name')
            ->select(
                \DB::raw("COALESCE(ii.name, csi.item_name) as name"),
                \DB::raw('SUM(csi.quantity) as qty')
            )
            ->get();

        // Combine and aggregate results
        $combined = collect([...$invoiceItems, ...$posItems, ...$cashItems])
            ->groupBy('name')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->name,
                    'qty' => $items->sum('qty')
                ];
            })
            ->sortByDesc('qty')
            ->take(10)
            ->values();

        return response()->json([
            'items' => $combined->pluck('name'),
            'quantities' => $combined->pluck('qty'),
        ]);
    }

    // Gross profit trend: revenue vs COGS (current year)
    public function grossProfitTrend(Request $request)
    {
        $company = auth()->user()->company;
        $actor = auth()->user();
        $permittedBranchIds = $this->getPermittedBranchIds($actor);
        if (empty($permittedBranchIds) && $actor->branch_id) {
            $permittedBranchIds = [(int)$actor->branch_id];
        }
        $branchId = $this->resolveBranchIdFromRequest($request);
        $year = now()->year;
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 12, 31)->endOfDay();
        $cacheKey = "dashboard:grossProfitTrend:{$company->id}:" . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds)) . ':' . $year;

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($company, $branchId, $permittedBranchIds, $startDate, $endDate) {
            $revRows = \App\Models\Sales\SalesInvoice::query()
                ->where('company_id', $company->id)
                ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->whereNotIn('status', ['cancelled'])
                ->selectRaw('MONTH(invoice_date) as month_no, SUM(total_amount) as total')
                ->groupBy('month_no')
                ->pluck('total', 'month_no');

            $cogsRows = GlTransaction::query()
                ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('date', [$startDate, $endDate])
                ->where('nature', 'debit')
                ->whereIn('transaction_type', ['sales_invoice', 'pos_sale', 'cash_sale'])
                ->selectRaw('MONTH(date) as month_no, SUM(amount) as total')
                ->groupBy('month_no')
                ->pluck('total', 'month_no');

            $months = [];
            $revenue = [];
            $cogs = [];
            $profit = [];
            for ($m = 1; $m <= 12; $m++) {
                $months[] = date('M', mktime(0, 0, 0, $m, 1));
                $rev = (float)($revRows[$m] ?? 0);
                $cg = (float)($cogsRows[$m] ?? 0);
                $revenue[] = $rev;
                $cogs[] = $cg;
                $profit[] = max(0, $rev - $cg);
            }

            return [
                'months' => $months,
                'revenue' => $revenue,
                'cogs' => $cogs,
                'profit' => $profit,
            ];
        });

        return response()->json($payload);
    }

    // Revenue vs Expenses vs Net Profit by year (last 5 years)
    public function profitByYear(Request $request)
    {
        try {
            $company = auth()->user()->company;
            if (!$company) {
                // Graceful response with zeros
                $currentYear = now()->year;
                $years = array_map(fn($y) => (string)$y, range($currentYear - 4, $currentYear));
                return response()->json(['years' => $years, 'revenue' => array_fill(0, 5, 0), 'expenses' => array_fill(0, 5, 0), 'profit' => array_fill(0, 5, 0)]);
            }

            $actor = auth()->user();
            $permittedBranchIds = $this->getPermittedBranchIds($actor);
            if (empty($permittedBranchIds) && $actor->branch_id) {
                $permittedBranchIds = [(int)$actor->branch_id];
            }
            $branchId = $this->resolveBranchIdFromRequest($request);

            // If a specific year is requested, return monthly breakdown for that year
            $requestedYear = $request->query('year');
            if ($requestedYear) {
                $year = (int)$requestedYear;
                $cacheKey = "dashboard:profitByYear:monthly:{$company->id}:" . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds)) . ':' . $year;
                $monthlyPayload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($company, $branchId, $permittedBranchIds, $year) {
                    $start = Carbon::create($year, 1, 1)->startOfDay();
                    $end = Carbon::create($year, 12, 31)->endOfDay();

                    $rows = DB::table('gl_transactions as gt')
                        ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
                        ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
                        ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
                        ->where('acg.company_id', $company->id)
                        ->whereBetween('gt.date', [$start, $end])
                        ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('gt.branch_id', $permittedBranchIds))
                        ->when($branchId, fn($q) => $q->where('gt.branch_id', $branchId))
                        ->whereIn(DB::raw('LOWER(ac.name)'), ['income', 'revenue', 'expense', 'expenses'])
                        ->selectRaw('MONTH(gt.date) as month_no, LOWER(ac.name) as class_name,
                            SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END) as credit_total,
                            SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END) as debit_total')
                        ->groupBy('month_no', 'class_name')
                        ->get();

                    $revenueByMonth = array_fill(1, 12, 0.0);
                    $expenseByMonth = array_fill(1, 12, 0.0);
                    foreach ($rows as $row) {
                        $month = (int)$row->month_no;
                        $class = (string)$row->class_name;
                        if (in_array($class, ['income', 'revenue'], true)) {
                            $revenueByMonth[$month] += ((float)$row->credit_total - (float)$row->debit_total);
                        } elseif (in_array($class, ['expense', 'expenses'], true)) {
                            $expenseByMonth[$month] += ((float)$row->debit_total - (float)$row->credit_total);
                        }
                    }

                    $labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    $revenue = [];
                    $expenses = [];
                    $profit = [];
                    for ($m = 1; $m <= 12; $m++) {
                        $rev = (float)$revenueByMonth[$m];
                        $exp = (float)$expenseByMonth[$m];
                        $revenue[] = $rev;
                        $expenses[] = $exp;
                        $profit[] = $rev - $exp;
                    }

                    return [
                        'labels' => $labels,
                        'revenue' => $revenue,
                        'expenses' => $expenses,
                        'profit' => $profit,
                        'year' => $year,
                    ];
                });

                return response()->json($monthlyPayload);
            }

            $currentYear = now()->year;
            $startYear = $currentYear - 4; // last 5 years

            $cacheKey = "dashboard:profitByYear:annual:{$company->id}:" . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds)) . ':' . $startYear . ':' . $currentYear;
            $annualPayload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($company, $branchId, $permittedBranchIds, $startYear, $currentYear) {
                $invoiceRows = \App\Models\Sales\SalesInvoice::query()
                    ->where('company_id', $company->id)
                    ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->whereBetween('invoice_date', [Carbon::create($startYear, 1, 1)->toDateString(), Carbon::create($currentYear, 12, 31)->toDateString()])
                    ->whereNotIn('status', ['cancelled'])
                    ->selectRaw('YEAR(invoice_date) as year_no, SUM(total_amount) as total')
                    ->groupBy('year_no')
                    ->pluck('total', 'year_no');

                $paymentRows = \App\Models\Payment::query()
                    ->when(!$branchId && !empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->whereBetween('date', [Carbon::create($startYear, 1, 1)->startOfDay(), Carbon::create($currentYear, 12, 31)->endOfDay()])
                    ->selectRaw('YEAR(date) as year_no, SUM(amount) as total')
                    ->groupBy('year_no')
                    ->pluck('total', 'year_no');

                $years = [];
                $revenue = [];
                $expenses = [];
                $profit = [];
                for ($y = $startYear; $y <= $currentYear; $y++) {
                    $rev = (float)($invoiceRows[$y] ?? 0);
                    $exp = (float)($paymentRows[$y] ?? 0);
                    $years[] = (string)$y;
                    $revenue[] = $rev;
                    $expenses[] = $exp;
                    $profit[] = $rev - $exp;
                }

                return [
                    'years' => $years,
                    'revenue' => $revenue,
                    'expenses' => $expenses,
                    'profit' => $profit,
                ];
            });

            return response()->json($annualPayload);
        } catch (\Throwable $e) {
            \Log::error('profitByYear failed', ['error' => $e->getMessage()]);
            $currentYear = now()->year;
            $years = array_map(fn($y) => (string)$y, range($currentYear - 4, $currentYear));
            return response()->json(['years' => $years, 'revenue' => array_fill(0, 5, 0), 'expenses' => array_fill(0, 5, 0), 'profit' => array_fill(0, 5, 0)]);
        }
    }

    // Receivables aging endpoint (cached)
    public function dashboardReceivablesAging(Request $request)
    {
        $company = auth()->user()->company;
        if (!$company) {
            return response()->json([
                'current' => 0,
                'overdue_1_30' => 0,
                'overdue_31_60' => 0,
                'overdue_60_plus' => 0,
            ]);
        }
        $actor = auth()->user();
        $permittedBranchIds = $this->getPermittedBranchIds($actor);
        if (empty($permittedBranchIds) && $actor->branch_id) {
            $permittedBranchIds = [(int)$actor->branch_id];
        }
        $branchId = $this->resolveBranchIdFromRequest($request);
        $today = now()->toDateString();
        $cacheKey = "dashboard:aging:{$company->id}:" . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds)) . ':' . $today;

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($company, $branchId, $permittedBranchIds, $today) {
            $aging = DB::table('sales_invoices')
                ->where('company_id', $company->id)
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('balance_due', '>', 0)
                ->whereNotIn('status', ['cancelled'])
                ->selectRaw(DB::getDriverName() === 'mysql'
                    ? 'SUM(CASE WHEN due_date >= ? THEN balance_due ELSE 0 END) as current,
                       SUM(CASE WHEN due_date < ? AND DATEDIFF(?, due_date) BETWEEN 1 AND 30 THEN balance_due ELSE 0 END) as overdue_1_30,
                       SUM(CASE WHEN DATEDIFF(?, due_date) BETWEEN 31 AND 60 THEN balance_due ELSE 0 END) as overdue_31_60,
                       SUM(CASE WHEN DATEDIFF(?, due_date) > 60 THEN balance_due ELSE 0 END) as overdue_60_plus'
                    : 'SUM(CASE WHEN due_date >= ? THEN balance_due ELSE 0 END) as current,
                       SUM(CASE WHEN due_date < ? AND CAST((julianday(?) - julianday(due_date)) AS INTEGER) BETWEEN 1 AND 30 THEN balance_due ELSE 0 END) as overdue_1_30,
                       SUM(CASE WHEN CAST((julianday(?) - julianday(due_date)) AS INTEGER) BETWEEN 31 AND 60 THEN balance_due ELSE 0 END) as overdue_31_60,
                       SUM(CASE WHEN CAST((julianday(?) - julianday(due_date)) AS INTEGER) > 60 THEN balance_due ELSE 0 END) as overdue_60_plus',
                    [$today, $today, $today, $today, $today])
                ->first();

            return [
                'current' => (float)($aging->current ?? 0),
                'overdue_1_30' => (float)($aging->overdue_1_30 ?? 0),
                'overdue_31_60' => (float)($aging->overdue_31_60 ?? 0),
                'overdue_60_plus' => (float)($aging->overdue_60_plus ?? 0),
            ];
        });

        return response()->json($data);
    }

    // Inventory summary endpoint (cached, branch-aware)
    public function dashboardInventorySummary(Request $request)
    {
        $company = auth()->user()->company;
        if (!$company) {
            return response()->json([
                'totalInventoryValue' => 0,
                'totalInventoryItemsCount' => 0,
            ]);
        }

        $actor = auth()->user();
        $permittedBranchIds = $this->getPermittedBranchIds($actor);
        if (empty($permittedBranchIds) && $actor->branch_id) {
            $permittedBranchIds = [(int)$actor->branch_id];
        }
        $branchId = $this->resolveBranchIdFromRequest($request);

        $cacheKey = "dashboard:inventorySummary:{$company->id}:" . ($branchId ?: 'all') . ':' . md5(json_encode($permittedBranchIds));
        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($company, $branchId, $permittedBranchIds) {
            $row = DB::table('inventory_stock_levels as isl')
                ->join('inventory_locations as il', 'isl.inventory_location_id', '=', 'il.id')
                ->join('inventory_items as ii', 'isl.item_id', '=', 'ii.id')
                ->where('ii.company_id', $company->id)
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('il.branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('il.branch_id', $branchId))
                ->selectRaw('
                    COALESCE(SUM(CASE WHEN isl.quantity > 0 THEN isl.quantity * ii.cost_price ELSE 0 END), 0) as total_value,
                    COUNT(DISTINCT CASE WHEN isl.quantity > 0 THEN isl.item_id END) as item_count
                ')
                ->first();

            $totalValue = (float) ($row->total_value ?? 0);
            $itemCount = (int) ($row->item_count ?? 0);

            // Fallback for environments where stock_levels is not populated yet:
            // compute from inventory_movements rollup.
            if ($itemCount === 0) {
                $movementRows = DB::table('inventory_movements as im')
                    ->join('inventory_locations as il', 'im.location_id', '=', 'il.id')
                    ->join('inventory_items as ii', 'im.item_id', '=', 'ii.id')
                    ->where('ii.company_id', $company->id)
                    ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('il.branch_id', $permittedBranchIds))
                    ->when($branchId, fn($q) => $q->where('il.branch_id', $branchId))
                    ->selectRaw('
                        im.item_id,
                        SUM(CASE
                            WHEN im.movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in")
                                THEN im.quantity
                            WHEN im.movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off")
                                THEN -im.quantity
                            ELSE 0
                        END) as stock_qty,
                        MAX(ii.cost_price) as unit_cost
                    ')
                    ->groupBy('im.item_id')
                    ->havingRaw('stock_qty > 0')
                    ->get();

                $itemCount = $movementRows->count();
                $totalValue = (float) $movementRows->sum(function ($r) {
                    return ((float) $r->stock_qty) * ((float) $r->unit_cost);
                });
            }

            return [
                'totalInventoryValue' => $totalValue,
                'totalInventoryItemsCount' => $itemCount,
            ];
        });

        return response()->json($data);
    }

    // Summary cards endpoint (branch-aware)
    public function dashboardCardsSummary(Request $request)
    {
        $company = auth()->user()->company;
        if (!$company) {
            return response()->json([
                'totalSalesToday' => 0,
                'totalExpensesToday' => 0,
                'outstandingInvoicesAmount' => 0,
                'outstandingInvoicesCount' => 0,
                'totalCustomers' => 0,
                'cashCollectedToday' => 0,
                'revenueThisMonth' => 0,
            ]);
        }

        $actor = auth()->user();
        $permittedBranchIds = $this->getPermittedBranchIds($actor);
        if (empty($permittedBranchIds) && $actor->branch_id) {
            $permittedBranchIds = [(int)$actor->branch_id];
        }
        $branchId = $this->resolveBranchIdFromRequest($request);

        $today = now()->toDateString();
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $data = (function () use ($company, $permittedBranchIds, $branchId, $today, $todayStart, $todayEnd, $startOfMonth, $endOfMonth) {
            $invoiceSalesToday = \App\Models\Sales\SalesInvoice::query()
                ->where('company_id', $company->id)
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('invoice_date', $today)
                ->whereNotIn('status', ['cancelled'])
                ->sum('total_amount');

            $cashSalesToday = \App\Models\Sales\CashSale::query()
                ->where('company_id', $company->id)
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('sale_date', $today)
                ->sum('total_amount');

            $posSalesToday = \App\Models\Sales\PosSale::query()
                ->where('company_id', $company->id)
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('sale_date', $today)
                ->whereNull('deleted_at')
                ->sum('total_amount');

            $totalSalesToday = (float)$invoiceSalesToday + (float)$cashSalesToday + (float)$posSalesToday;

            $totalExpensesToday = (float)\App\Models\Payment::query()
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('date', [$todayStart, $todayEnd])
                ->sum('amount');

            $outstandingInvoicesAmount = (float)\App\Models\Sales\SalesInvoice::query()
                ->where('company_id', $company->id)
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('balance_due', '>', 0)
                ->whereNotIn('status', ['cancelled'])
                ->sum('balance_due');

            $outstandingInvoicesCount = (int)\App\Models\Sales\SalesInvoice::query()
                ->where('company_id', $company->id)
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('balance_due', '>', 0)
                ->whereNotIn('status', ['cancelled'])
                ->count();

            $totalCustomers = (int)\App\Models\Customer::query()
                ->where('company_id', $company->id)
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();

            $receiptsToday = (float)\App\Models\Receipt::query()
                ->whereHas('branch', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereIn('reference_type', ['sales_invoice', 'manual'])
                ->whereBetween('date', [$todayStart, $todayEnd])
                ->sum('amount');

            $cashCollectedToday = $receiptsToday + (float)$cashSalesToday + (float)$posSalesToday;

            $revenueThisMonth = (float)\App\Models\Sales\SalesInvoice::query()
                ->where('company_id', $company->id)
                ->when(!empty($permittedBranchIds), fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('invoice_date', [$startOfMonth, $endOfMonth])
                ->whereNotIn('status', ['cancelled'])
                ->sum('total_amount');

            $result = [
                'totalSalesToday' => $totalSalesToday,
                'totalExpensesToday' => $totalExpensesToday,
                'outstandingInvoicesAmount' => $outstandingInvoicesAmount,
                'outstandingInvoicesCount' => $outstandingInvoicesCount,
                'totalCustomers' => $totalCustomers,
                'cashCollectedToday' => $cashCollectedToday,
                'revenueThisMonth' => $revenueThisMonth,
            ];
            Log::info('dashboard.cardsSummary', [
                'company_id' => $company->id,
                'branch_id' => $branchId,
                'permitted_branch_count' => count($permittedBranchIds),
                'totals' => $result,
            ]);
            return $result;
        })();

        return response()->json($data);
    }

        /**
     * Handle bulk SMS sending from dashboard
     */
    public function sendBulkSms(Request $request)
    {
        $rules = [
            'branch_id' => 'required',
            'message_title' => 'required|string|max:100',
            'bulk_message_content' => 'nullable|string|max:500',
            'custom_title' => 'nullable|string|max:100',
        ];
        if ($request->input('message_title') === 'Custom') {
            $rules['bulk_message_content'] = 'required|string|max:500';
        }
        $request->validate($rules);

        $branchId = $request->branch_id;
        $title = $request->message_title;
        $customTitle = $request->custom_title;
        $messageContent = $request->bulk_message_content;

        // If 'Custom' is selected, use the custom title
        if ($title === 'Custom' && $customTitle) {
            $title = $customTitle;
        }

        // Get customers for the selected branch or all branches
        $customersQuery = \App\Models\Customer::query();
        if ($branchId !== 'all') {
            $customersQuery->where('branch_id', $branchId);
        }
        $customers = $customersQuery->whereNotNull('phone')->get();

        $valid = 0;
        $invalid = 0;
        $duplicates = 0;
        $sentNumbers = [];
        $responses = [];

        foreach ($customers as $customer) {
            // Normalize to 255XXXXXXXXX format
            $rawPhone = $customer->phone;
            $phone = function_exists('normalize_phone_number') ? normalize_phone_number($rawPhone) : preg_replace('/[^0-9+]/', '', $rawPhone);
            if (empty($phone) || strlen($phone) < 9 || in_array($phone, $sentNumbers)) {
                $invalid++;
                if (in_array($phone, $sentNumbers)) $duplicates++;
                continue;
            }
            $sentNumbers[] = $phone;
            // Build message content
            if ($title === 'Payment Reminder') {
                // Calculate customer's total outstanding balance (optionally scoped to branch)
                $invoiceQuery = \App\Models\Sales\SalesInvoice::where('customer_id', $customer->id)
                    ->where('balance_due', '>', 0)
                    ->whereNotIn('status', ['cancelled']);
                if ($branchId !== 'all') {
                    $invoiceQuery->where('branch_id', $branchId);
                }
                $totalDue = (float) $invoiceQuery->sum('balance_due');
                // Skip customers with no due balance
                if ($totalDue <= 0) {
                    $invalid++;
                    continue;
                }
                $amountStr = number_format($totalDue, 2);
                $fullMessage = "Dear {$customer->name}, this is a friendly reminder that your outstanding balance is TZS {$amountStr}. Please make your payment at your earliest convenience. Thank you.";
            } else {
                $fullMessage = ($title ? ($title . ': ') : '') . $messageContent;
            }
            // Send SMS via Beem
            try {
                \Log::info('Sending bulk SMS to customer', [
                    'customer_id' => $customer->id,
                    'branch_id' => $branchId,
                    'phone' => $phone,
                    'title' => $title,
                ]);
                $smsResponse = \App\Helpers\SmsHelper::send($phone, $fullMessage);
            } catch (\Throwable $e) {
                $smsResponse = 'ERROR: ' . $e->getMessage();
            }
            $responses[] = $smsResponse;
            $valid++;
            // Log SMS
            \DB::table('sms_logs')->insert([
                'customer_id' => $customer->id,
                'phone_number' => $phone,
                'message' => $fullMessage,
                'response' => $smsResponse,
                'sent_by' => auth()->id(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk SMS sent successfully!",
            'response' => [
                'message' => 'Message Submitted Successfully',
                'valid' => $valid,
                'invalid' => $invalid,
                'duplicates' => $duplicates,
                'details' => $responses
            ]
        ]);
    }

    /**
     * Send SMS to a single customer from the customer profile page
     */
    public function sendBulkSmsToSingleCustomer(Request $request, $encodedId)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($encodedId)[0] ?? null;
        if (!$decoded) {
            return response()->json(['success' => false, 'message' => 'Invalid customer.'], 422);
        }

        $rules = [
            'message_title' => 'required|string|max:100',
            'bulk_message_content' => 'nullable|string|max:500',
        ];
        if ($request->input('message_title') === 'Custom') {
            $rules['bulk_message_content'] = 'required|string|max:500';
        }
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = \App\Models\Customer::findOrFail($decoded);
        $title = $request->message_title;
        $content = $request->bulk_message_content;

        // Normalize phone
        $rawPhone = $customer->phone;
        $phone = function_exists('normalize_phone_number') ? normalize_phone_number($rawPhone) : preg_replace('/[^0-9+]/', '', $rawPhone);
        if (empty($phone) || strlen($phone) < 9) {
            return response()->json(['success' => false, 'message' => 'Customer phone number is invalid.'], 422);
        }

        // Build message
        if ($title === 'Payment Reminder') {
            $totalDue = (float) \App\Models\Sales\SalesInvoice::where('customer_id', $customer->id)
                ->where('balance_due', '>', 0)
                ->whereNotIn('status', ['cancelled'])
                ->sum('balance_due');
            if ($totalDue <= 0) {
                return response()->json(['success' => false, 'message' => 'No outstanding balance for this customer.'], 422);
            }
            $amountStr = number_format($totalDue, 2);
            $fullMessage = "Dear {$customer->name}, this is a friendly reminder that your outstanding balance is TZS {$amountStr}. Please make your payment at your earliest convenience. Thank you.";
        } else {
            $fullMessage = $content;
        }

        try {
            \Log::info('Sending single SMS to customer', [
                'customer_id' => $customer->id,
                'phone' => $phone,
                'title' => $title,
            ]);
            $response = \App\Helpers\SmsHelper::send($phone, $fullMessage);
            // Log to sms_logs
            \DB::table('sms_logs')->insert([
                'customer_id' => $customer->id,
                'phone_number' => $phone,
                'message' => $fullMessage,
                'response' => $response,
                'sent_by' => auth()->id(),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'SMS submitted successfully.']);
        } catch (\Throwable $e) {
            \Log::error('Failed to send SMS to single customer', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to send SMS.'], 500);
        }
    }

    /**
     * Get items expiring within 30 days
     */
    private function getExpiringItems($branchId = null, array $permittedBranchIds = [])
    {
        // ... (existing code)
    }

    /**
     * Nest accounts within an FSLI based on parent_id
     */
    private function nestAccounts(array $accounts)
    {
        $tree = [];
        $lookup = [];

        // First pass: create lookup and initialize children
        foreach ($accounts as $account) {
            $id = $account['account_id'];
            $lookup[$id] = $account;
            $lookup[$id]['children'] = [];
        }

        // Second pass: build tree
        foreach ($lookup as $id => &$account) {
            $parentId = $account['parent_id'] ?? null;
            if ($parentId && isset($lookup[$parentId])) {
                $lookup[$parentId]['children'][] = &$account;
            } else {
                $tree[] = &$account;
            }
        }

        // Third pass: Return the tree without filtering empty branches
        // This allows the view to decide which accounts to show (e.g. if they have balance in previous year)
        return $this->rollupBalances($tree);
    }

    /**
     * Roll up child balances to parents without filtering
     */
    private function rollupBalances(array $tree)
    {
        foreach ($tree as &$account) {
            if (!empty($account['children'])) {
                $account['children'] = $this->rollupBalances($account['children']);
                
                // Roll up children balances to the parent sum
                $childrenSum = 0;
                foreach ($account['children'] as $child) {
                    $childrenSum += ($child['sum'] ?? 0);
                }
                $account['sum'] = ($account['sum'] ?? 0) + $childrenSum;
            }
            }
        return $tree;
    }

    /**
     * Normalize branch input to nullable int. Empty/all/null => null.
     */
    private function normalizeBranchId($rawBranchId): ?int
    {
        if ($rawBranchId === null) {
            return null;
        }
        $value = trim((string) $rawBranchId);
        if ($value === '' || strtolower($value) === 'all' || strtolower($value) === 'null') {
            return null;
        }
        return (int) $value;
    }

    /**
     * Resolve branch_id from request. If query param is present, honor it (including empty/all => null).
     * If param is absent, fallback to session branch.
     */
    private function resolveBranchIdFromRequest(Request $request): ?int
    {
        if ($request->has('branch_id')) {
            return $this->normalizeBranchId($request->query('branch_id'));
        }
        return $this->normalizeBranchId(session('branch_id'));
    }

    /**
     * Resolve branches user can access. Super-admin/admin can access all company branches.
     */
    private function getPermittedBranchIds($user): array
    {
        $isSuper = ($user && method_exists($user, 'hasRole') && (
            $user->hasRole('super-admin') || $user->hasRole('Super Admin') || $user->hasRole('admin')
        )) || (($user->is_admin ?? false) === true);

        if ($isSuper && $user->company) {
            return $user->company->branches()->pluck('id')->all();
        }

        return collect($user->branches ?? [])->pluck('id')->all();
    }
} 