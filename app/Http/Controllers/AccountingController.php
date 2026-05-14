<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\BankAccount;
use App\Models\AccountClassGroup;
use App\Models\MainGroup;
use App\Models\ChartAccount;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Budget;
use App\Models\BankReconciliation;
use Illuminate\Support\Facades\Auth;
use App\Services\ManagementReportService;
use App\Models\Shares\ShareClass;
use App\Models\Shares\Shareholder;
use App\Models\Shares\ShareIssue;
use App\Models\Shares\ShareDividend;
use App\Models\Provision;

class AccountingController extends Controller
{
    /**
     * Display the Accounting management dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $branch_id = session('branch_id') ?? Auth::user()->branch_id ?? 1;

        $user = Auth::user();
        $companyId = $user->company_id;

        $mainGroups = MainGroup::count();
        $chartAccountFsli = AccountClassGroup::count();
        $chartAccounts = ChartAccount::count();
        $banks = BankAccount::count();
        $journals = Journal::where('branch_id',$branch_id)->count();
        $paymentVouchers = Payment::where('branch_id',$branch_id)->count();
        $receiptVouchers = Receipt::where('branch_id',$branch_id)->count();
        $bankReconciliations = BankReconciliation::where('branch_id',$branch_id)->count();
        $budgets = Budget::count();
        $fxRates = \App\Models\FxRate::where('company_id', $companyId)->count();
        $fxRevaluations = \App\Models\GlRevaluationHistory::where('company_id', $companyId)->count();
        
        // Petty Cash Units count
        $pettyCashUnits = \App\Models\PettyCash\PettyCashUnit::where('company_id', $companyId)->count();
        
        // Account Transfers count
        $accountTransfers = \App\Models\AccountTransfer::where('company_id', $companyId)->count();
        
        // Cashflow Forecasts count
        $cashflowForecasts = \App\Models\CashflowForecast::where('company_id', $companyId)->count();
        
        // Accruals & Prepayments count
        $accrualSchedules = \App\Models\AccrualSchedule::where('company_id', $companyId)->count();

        // Share Capital Management counts
        $shareCapitalShareClasses = ShareClass::where('company_id', $companyId)->where('is_active', true)->count();
        $shareCapitalShareholders = Shareholder::where('company_id', $companyId)->where('is_active', true)->count();
        $shareCapitalIssues = ShareIssue::where('company_id', $companyId)->where('status', 'posted')->count();
        $shareCapitalDividends = ShareDividend::where('company_id', $companyId)->where('status', '!=', 'cancelled')->count();

        // IAS 37 Provisions count
        $provisionsCount = Provision::where('company_id', $companyId)->count();

        return view('accounting.index', compact(
            'mainGroups',
            'chartAccountFsli',
            'chartAccounts', 
            'banks',
            'journals',
            'paymentVouchers',
            'receiptVouchers',
            'bankReconciliations',
            'budgets',
            'fxRates',
            'fxRevaluations',
            'pettyCashUnits',
            'accountTransfers',
            'cashflowForecasts',
            'accrualSchedules',
            'shareCapitalShareClasses',
            'shareCapitalShareholders',
            'shareCapitalIssues',
            'shareCapitalDividends',
            'provisionsCount'
        ));
    }

    /**
     * Consolidated Management Report landing page.
     */
    public function consolidatedManagementReport(Request $request)
    {
        $period = $request->get('period', 'month');
        $year = (int)($request->get('year', date('Y')));
        $month = (int)($request->get('month', date('n')));
        $quarter = (int)($request->get('quarter', 0));

        [$startDate, $endDate] = $this->resolvePeriodRange($period, $year, $month, $quarter);

        $user = Auth::user();
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $service = new ManagementReportService();
        $data = $service->compute([
            'company_id' => $user->company_id,
            'branch_ids' => $permittedBranchIds,
            'branch_id' => $branchId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return view('accounting.reports.consolidated-management-report', [
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'kpis' => $data['kpis'],
            'summary' => $data['summary'],
        ]);
    }

    private function resolvePeriodRange(string $period, int $year, int $month, int $quarter = 0): array
    {
        if ($period === 'year') {
            return [sprintf('%d-01-01', $year), sprintf('%d-12-31', $year)];
        }
        if ($period === 'quarter') {
            $q = $quarter > 0 ? $quarter : (int)ceil($month / 3);
            $startMonth = ($q - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            $start = sprintf('%d-%02d-01', $year, $startMonth);
            $end = date('Y-m-t', strtotime(sprintf('%d-%02d-01', $year, $endMonth)));
            return [$start, $end];
        }
        // month (default)
        $start = sprintf('%d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
        return [$start, $end];
    }

    private function resolvePreviousPeriodRange(string $period, int $year, int $month, int $quarter = 0): array
    {
        if ($period === 'year') {
            $prevYear = $year - 1;
            return [sprintf('%d-01-01', $prevYear), sprintf('%d-12-31', $prevYear)];
        }
        if ($period === 'quarter') {
            $q = $quarter > 0 ? $quarter : (int)ceil($month / 3);
            $prevQ = $q - 1;
            $prevYear = $year;
            if ($prevQ < 1) { $prevQ = 4; $prevYear = $year - 1; }
            $startMonth = ($prevQ - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            $start = sprintf('%d-%02d-01', $prevYear, $startMonth);
            $end = date('Y-m-t', strtotime(sprintf('%d-%02d-01', $prevYear, $endMonth)));
            return [$start, $end];
        }
        // month
        $ref = strtotime(sprintf('%d-%02d-01', $year, $month));
        $prev = strtotime('-1 month', $ref);
        $start = date('Y-m-01', $prev);
        $end = date('Y-m-t', $prev);
        return [$start, $end];
    }

    public function exportConsolidatedManagementReport(Request $request)
    {
        $period = $request->get('period', 'month');
        $year = (int)($request->get('year', date('Y')));
        $month = (int)($request->get('month', date('n')));
        $quarter = (int)($request->get('quarter', 0));
        [$startDate, $endDate] = $this->resolvePeriodRange($period, $year, $month, $quarter);

        $user = Auth::user();
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $service = new ManagementReportService();
        $data = $service->compute([
            'company_id' => $user->company_id,
            'branch_ids' => $permittedBranchIds,
            'branch_id' => $branchId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $company = \App\Models\Company::find($user->company_id);
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        // Build statements (income first so we can include net profit in balance sheet like dashboard)
        $incomeSummary = $this->buildIncomeStatement($user, $permittedBranchIds, $branchId, $startDate, $endDate);
        // Previous period for comparisons
        [$prevStartDate, $prevEndDate] = $this->resolvePreviousPeriodRange($period, $year, $month, $quarter);
        $prevIncomeSummary = $this->buildIncomeStatement($user, $permittedBranchIds, $branchId, $prevStartDate, $prevEndDate);
        // For balance sheet, use year-to-date profit (from Jan 1 to end date), not just selected period
        $yearStart = date('Y-01-01', strtotime($endDate));
        $ytdIncome = $this->buildIncomeStatement($user, $permittedBranchIds, $branchId, $yearStart, $endDate);
        $balanceSnapshot = $this->buildBalanceSheetSnapshot($user, $permittedBranchIds, $branchId, $endDate, ($ytdIncome['net_profit'] ?? 0));
        // Previous snapshot as of previous period end
        $prevYearStart = date('Y-01-01', strtotime($prevEndDate));
        $prevYtdIncome = $this->buildIncomeStatement($user, $permittedBranchIds, $branchId, $prevYearStart, $prevEndDate);
        $prevBalanceSnapshot = $this->buildBalanceSheetSnapshot($user, $permittedBranchIds, $branchId, $prevEndDate, ($prevYtdIncome['net_profit'] ?? 0));

        $pdf = \PDF::loadView('accounting.reports.consolidated-management-report-pdf', [
            'company' => $company,
            'branch' => $branch,
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'kpis' => $data['kpis'],
            'summary' => $data['summary'],
            'balanceSheet' => $balanceSnapshot,
            'incomeStatement' => $incomeSummary,
            'prevStartDate' => $prevStartDate,
            'prevEndDate' => $prevEndDate,
            'prevIncomeStatement' => $prevIncomeSummary,
            'prevBalanceSheet' => $prevBalanceSnapshot,
            'balanceSheetDetailed' => $this->buildBalanceSheetDetailed($user, $permittedBranchIds, $branchId, $endDate),
            'incomeStatementDetailed' => $this->buildIncomeStatementDetailed($user, $permittedBranchIds, $branchId, $startDate, $endDate),
            'prevBalanceSheetDetailed' => $this->buildBalanceSheetDetailed($user, $permittedBranchIds, $branchId, $prevEndDate),
            'prevIncomeStatementDetailed' => $this->buildIncomeStatementDetailed($user, $permittedBranchIds, $branchId, $prevStartDate, $prevEndDate),
            'generatedBy' => $user->name ?? 'System',
            'generatedOn' => now(),
            'systemName' => \App\Models\SystemSetting::getValue('application_name', config('app.name')),
        ]);

        // Apply document settings if available
        $pageSize = \App\Models\SystemSetting::getValue('document_page_size', 'A4');
        $orientation = \App\Models\SystemSetting::getValue('document_orientation', 'portrait');
        $pdf->setPaper($pageSize, $orientation);

        $filename = 'Consolidated_Management_Report_' . $year . ($period === 'month' ? ('_' . sprintf('%02d', $month)) : '') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportConsolidatedManagementReportWord(Request $request)
    {
        $period = $request->get('period', 'month');
        $year = (int)($request->get('year', date('Y')));
        $month = (int)($request->get('month', date('n')));
        $quarter = (int)($request->get('quarter', 0));
        [$startDate, $endDate] = $this->resolvePeriodRange($period, $year, $month, $quarter);

        $user = Auth::user();
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $service = new ManagementReportService();
        $data = $service->compute([
            'company_id' => $user->company_id,
            'branch_ids' => $permittedBranchIds,
            'branch_id' => $branchId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $company = \App\Models\Company::find($user->company_id);
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        // Build statements (same as PDF export)
        $incomeSummary = $this->buildIncomeStatement($user, $permittedBranchIds, $branchId, $startDate, $endDate);
        [$prevStartDate, $prevEndDate] = $this->resolvePreviousPeriodRange($period, $year, $month, $quarter);
        $prevIncomeSummary = $this->buildIncomeStatement($user, $permittedBranchIds, $branchId, $prevStartDate, $prevEndDate);
        $yearStart = date('Y-01-01', strtotime($endDate));
        $ytdIncome = $this->buildIncomeStatement($user, $permittedBranchIds, $branchId, $yearStart, $endDate);
        $balanceSnapshot = $this->buildBalanceSheetSnapshot($user, $permittedBranchIds, $branchId, $endDate, ($ytdIncome['net_profit'] ?? 0));
        $prevYearStart = date('Y-01-01', strtotime($prevEndDate));
        $prevYtdIncome = $this->buildIncomeStatement($user, $permittedBranchIds, $branchId, $prevYearStart, $prevEndDate);
        $prevBalanceSnapshot = $this->buildBalanceSheetSnapshot($user, $permittedBranchIds, $branchId, $prevEndDate, ($prevYtdIncome['net_profit'] ?? 0));
        $balanceSheetDetailed = $this->buildBalanceSheetDetailed($user, $permittedBranchIds, $branchId, $endDate);
        $incomeStatementDetailed = $this->buildIncomeStatementDetailed($user, $permittedBranchIds, $branchId, $startDate, $endDate);
        $prevBalanceSheetDetailed = $this->buildBalanceSheetDetailed($user, $permittedBranchIds, $branchId, $prevEndDate);
        $prevIncomeStatementDetailed = $this->buildIncomeStatementDetailed($user, $permittedBranchIds, $branchId, $prevStartDate, $prevEndDate);

        // Use Word Export Service
        $wordService = new \App\Services\WordExportService();
        $phpWord = $wordService->generateConsolidatedManagementReport([
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'prevStartDate' => $prevStartDate,
            'prevEndDate' => $prevEndDate,
            'summary' => $data['summary'] ?? '',
            'kpis' => $data['kpis'] ?? [],
            'balanceSheet' => $balanceSnapshot,
            'prevBalanceSheet' => $prevBalanceSnapshot,
            'balanceSheetDetailed' => $balanceSheetDetailed,
            'prevBalanceSheetDetailed' => $prevBalanceSheetDetailed,
            'incomeStatement' => $incomeSummary,
            'prevIncomeStatement' => $prevIncomeSummary,
            'incomeStatementDetailed' => $incomeStatementDetailed,
            'prevIncomeStatementDetailed' => $prevIncomeStatementDetailed,
        ]);
        
        // Save and download
        $filename = 'Consolidated_Management_Report_' . $year . ($period === 'month' ? ('_' . sprintf('%02d', $month)) : '') . '.docx';
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        
        $response = response()->streamDownload(function() use ($objWriter) {
            $objWriter->save('php://output');
        }, $filename);
        
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        
        return $response;
    }

    private function buildIncomeStatement($user, array $permittedBranchIds, $branchId, string $startDate, string $endDate): array
    {
        $base = \DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->leftJoin('journals as j', function($join) {
                $join->on('gt.transaction_id', '=', 'j.id')
                     ->where('gt.transaction_type', '=', 'journal');
            })
            ->where('acg.company_id', $user->company_id)
            ->whereBetween(\DB::raw('DATE(gt.date)'), [$startDate, $endDate])
            // Exclude year-end closing entries from income statement calculations
            ->where(function($query) {
                $query->whereNull('j.reference_type')
                      ->orWhere('j.reference_type', '!=', 'Year-End Close');
            });
        if (!empty($permittedBranchIds) && !$branchId) { $base->whereIn('gt.branch_id', $permittedBranchIds); }
        if ($branchId) { $base->where('gt.branch_id', $branchId); }

        $revRow = (clone $base)
            ->whereIn(\DB::raw('LOWER(ac.name)'), ['income','revenue'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="credit" THEN gt.amount ELSE 0 END),0) as credits, COALESCE(SUM(CASE WHEN gt.nature="debit" THEN gt.amount ELSE 0 END),0) as debits')
            ->first();
        $revenue = (float)($revRow->credits ?? 0) - (float)($revRow->debits ?? 0);

        $expRow = (clone $base)
            ->whereIn(\DB::raw('LOWER(ac.name)'), ['expense','expenses'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature="credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();
        $expenses = (float)($expRow->debits ?? 0) - (float)($expRow->credits ?? 0);

        $cogsRow = (clone $base)
            ->whereIn(\DB::raw('LOWER(ac.name)'), ['expense','expenses'])
            ->where(function($q){
                $q->whereRaw('LOWER(ca.account_name) LIKE ?', ['cost of goods sold%'])
                  ->orWhereRaw('LOWER(ca.account_name) LIKE ?', ['cogs%'])
                  ->orWhereRaw('LOWER(acg.name) LIKE ?', ['cost of goods sold%'])
                  ->orWhereRaw('LOWER(acg.name) LIKE ?', ['cogs%']);
            })
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature="credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();
        $cogs = (float)($cogsRow->debits ?? 0) - (float)($cogsRow->credits ?? 0);

        // Operating expenses = total expenses - COGS
        $operatingExpenses = $expenses - $cogs;
        
        $grossProfit = $revenue - $cogs;
        $netProfit = $revenue - $cogs - $operatingExpenses; // or: $grossProfit - $operatingExpenses

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $operatingExpenses, // Return operating expenses (excluding COGS)
            'net_profit' => $netProfit,
        ];
    }

    private function buildBalanceSheetSnapshot($user, array $permittedBranchIds, $branchId, string $asOfDate, float $currentNetProfit = 0.0): array
    {
        // Balance sheet shows cumulative balances up to asOfDate
        // This includes all historical transactions including retained earnings from previous years
        $base = \DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $user->company_id)
            ->whereDate('gt.date', '<=', $asOfDate);
        if (!empty($permittedBranchIds) && !$branchId) { $base->whereIn('gt.branch_id', $permittedBranchIds); }
        if ($branchId) { $base->where('gt.branch_id', $branchId); }

        $rows = (clone $base)
            ->select('ac.name as class_name')
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="debit" THEN gt.amount ELSE 0 END),0) as debit_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="credit" THEN gt.amount ELSE 0 END),0) as credit_total')
            ->groupBy('ac.name')
            ->get();

        $assets = 0; $liabilities = 0; $equity = 0;
        foreach ($rows as $r) {
            $cls = strtolower($r->class_name);
            if ($cls === 'assets') { $assets = (float)$r->debit_total - (float)$r->credit_total; }
            if ($cls === 'liabilities') { $liabilities = (float)$r->credit_total - (float)$r->debit_total; }
            if ($cls === 'equity') { $equity = (float)$r->credit_total - (float)$r->debit_total; }
        }

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'net_profit' => $currentNetProfit,
            'equity_including_profit' => $equity + $currentNetProfit,
        ];
    }

    private function buildBalanceSheetDetailed($user, array $permittedBranchIds, $branchId, string $asOfDate): array
    {
        $base = \DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->leftJoin('main_groups as mg', 'acg.main_group_id', '=', 'mg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $user->company_id)
            ->whereDate('gt.date', '<=', $asOfDate);
        if (!empty($permittedBranchIds) && !$branchId) { $base->whereIn('gt.branch_id', $permittedBranchIds); }
        if ($branchId) { $base->where('gt.branch_id', $branchId); }

        $rows = (clone $base)
            ->select(
                'ac.name as class_name',
                'mg.name as main_group_name',
                'acg.name as fsli_name',
                'ca.id as account_id',
                'ca.account_name',
                'ca.account_code'
            )
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="debit" THEN gt.amount ELSE 0 END),0) as debit_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="credit" THEN gt.amount ELSE 0 END),0) as credit_total')
            ->groupBy('ac.name', 'mg.name', 'acg.name', 'ca.id', 'ca.account_name', 'ca.account_code')
            ->orderBy('ac.name')
            ->orderBy('mg.name')
            ->orderBy('acg.name')
            ->orderBy('ca.account_code')
            ->get();

        $result = [];
        foreach ($rows as $r) {
            $cls = strtolower($r->class_name);
            $mainGroupName = $r->main_group_name ?? 'Uncategorized';
            $fsliName = $r->fsli_name ?? 'Uncategorized';
            
            $amount = 0.0;
            if ($cls === 'assets') { 
                $amount = (float)$r->debit_total - (float)$r->credit_total; 
            } elseif ($cls === 'liabilities' || $cls === 'equity') { 
                $amount = (float)$r->credit_total - (float)$r->debit_total; 
            }
            
            // Organize by: Account Class → Main Group → FSLI → Chart Account
            if (!isset($result[$r->class_name])) {
                $result[$r->class_name] = [];
            }
            if (!isset($result[$r->class_name]['main_groups'][$mainGroupName])) {
                $result[$r->class_name]['main_groups'][$mainGroupName] = [
                    'fslis' => [],
                    'total' => 0
                ];
            }
            if (!isset($result[$r->class_name]['main_groups'][$mainGroupName]['fslis'][$fsliName])) {
                $result[$r->class_name]['main_groups'][$mainGroupName]['fslis'][$fsliName] = [
                    'accounts' => [],
                    'total' => 0
                ];
            }
            
            $result[$r->class_name]['main_groups'][$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                'account_id' => $r->account_id,
                'account_name' => $r->account_name,
                'account_code' => $r->account_code,
                'amount' => $amount
            ];
            
            // Calculate totals
            $result[$r->class_name]['main_groups'][$mainGroupName]['fslis'][$fsliName]['total'] += $amount;
            $result[$r->class_name]['main_groups'][$mainGroupName]['total'] += $amount;
            
            if (!isset($result[$r->class_name]['total'])) {
                $result[$r->class_name]['total'] = 0;
            }
            $result[$r->class_name]['total'] += $amount;
        }
        return $result;
    }

    private function buildIncomeStatementDetailed($user, array $permittedBranchIds, $branchId, string $startDate, string $endDate): array
    {
        $base = \DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->leftJoin('main_groups as mg', 'acg.main_group_id', '=', 'mg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->leftJoin('journals as j', function($join) {
                $join->on('gt.transaction_id', '=', 'j.id')
                     ->where('gt.transaction_type', '=', 'journal');
            })
            ->where('acg.company_id', $user->company_id)
            ->whereBetween(\DB::raw('DATE(gt.date)'), [$startDate, $endDate])
            // Exclude year-end closing entries from income statement calculations
            ->where(function($query) {
                $query->whereNull('j.reference_type')
                      ->orWhere('j.reference_type', '!=', 'Year-End Close');
            });
        if (!empty($permittedBranchIds) && !$branchId) { $base->whereIn('gt.branch_id', $permittedBranchIds); }
        if ($branchId) { $base->where('gt.branch_id', $branchId); }

        $rows = (clone $base)
            ->select(
                'ac.name as class_name',
                'mg.name as main_group_name',
                'acg.name as fsli_name',
                'ca.id as account_id',
                'ca.account_name',
                'ca.account_code'
            )
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="debit" THEN gt.amount ELSE 0 END),0) as debit_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="credit" THEN gt.amount ELSE 0 END),0) as credit_total')
            ->groupBy('ac.name', 'mg.name', 'acg.name', 'ca.id', 'ca.account_name', 'ca.account_code')
            ->orderBy('ac.name')
            ->orderBy('mg.name')
            ->orderBy('acg.name')
            ->orderBy('ca.account_code')
            ->get();

        $result = [
            'revenue' => [],
            'expenses' => [],
            'cogs' => []
        ];
        
        foreach ($rows as $r) {
            $cls = strtolower($r->class_name);
            $mainGroupName = $r->main_group_name ?? 'Uncategorized';
            $fsliName = $r->fsli_name ?? 'Uncategorized';
            $accountNameLower = strtolower($r->account_name);
            $fsliNameLower = strtolower($fsliName);
            
            // Check both account name and FSLI name for COGS
            $isCogs = (strpos($accountNameLower, 'cost of goods sold') !== false 
                      || strpos($accountNameLower, 'cogs') !== false
                      || strpos($fsliNameLower, 'cost of goods sold') !== false
                      || strpos($fsliNameLower, 'cogs') !== false);
            
            if ($cls === 'income' || $cls === 'revenue') {
                $amount = (float)$r->credit_total - (float)$r->debit_total;
                
                // Organize by: Main Group → FSLI → Chart Account
                if (!isset($result['revenue']['main_groups'][$mainGroupName])) {
                    $result['revenue']['main_groups'][$mainGroupName] = [
                        'fslis' => [],
                        'total' => 0
                    ];
                }
                if (!isset($result['revenue']['main_groups'][$mainGroupName]['fslis'][$fsliName])) {
                    $result['revenue']['main_groups'][$mainGroupName]['fslis'][$fsliName] = [
                        'accounts' => [],
                        'total' => 0
                    ];
                }
                
                $result['revenue']['main_groups'][$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                    'account_id' => $r->account_id,
                    'account_name' => $r->account_name,
                    'account_code' => $r->account_code,
                    'amount' => $amount
                ];
                
                $result['revenue']['main_groups'][$mainGroupName]['fslis'][$fsliName]['total'] += $amount;
                $result['revenue']['main_groups'][$mainGroupName]['total'] += $amount;
                
                if (!isset($result['revenue']['total'])) {
                    $result['revenue']['total'] = 0;
                }
                $result['revenue']['total'] += $amount;
                
            } elseif ($cls === 'expenses' || $cls === 'expense') {
                $amount = (float)$r->debit_total - (float)$r->credit_total;
                
                $category = $isCogs ? 'cogs' : 'expenses';
                
                // Organize by: Main Group → FSLI → Chart Account
                if (!isset($result[$category]['main_groups'][$mainGroupName])) {
                    $result[$category]['main_groups'][$mainGroupName] = [
                        'fslis' => [],
                        'total' => 0
                    ];
                }
                if (!isset($result[$category]['main_groups'][$mainGroupName]['fslis'][$fsliName])) {
                    $result[$category]['main_groups'][$mainGroupName]['fslis'][$fsliName] = [
                        'accounts' => [],
                        'total' => 0
                    ];
                }
                
                $result[$category]['main_groups'][$mainGroupName]['fslis'][$fsliName]['accounts'][] = [
                    'account_id' => $r->account_id,
                    'account_name' => $r->account_name,
                    'account_code' => $r->account_code,
                    'amount' => $amount
                ];
                
                $result[$category]['main_groups'][$mainGroupName]['fslis'][$fsliName]['total'] += $amount;
                $result[$category]['main_groups'][$mainGroupName]['total'] += $amount;
                
                if (!isset($result[$category]['total'])) {
                    $result[$category]['total'] = 0;
                }
                $result[$category]['total'] += $amount;
            }
        }
        return $result;
    }

    public function updateCmrKpis(Request $request)
    {
        $this->authorize('view dashboard'); // basic gate; adjust as needed for admin-only
        $validated = $request->validate([
            'kpis' => 'array',
            'kpis.*' => 'in:revenue,expenses,net_profit,cash_flow,net_profit_margin,expense_ratio,receivables,dso,gross_profit_margin,dio,dpo,current_ratio,quick_ratio,cash_ratio,debt_to_equity,asset_turnover,inventory_turnover,receivables_turnover,payables_turnover,roa,roe,operating_profit_margin,ebitda_margin,revenue_growth_rate,net_profit_growth_rate,expense_growth_rate,operating_cash_flow_ratio,free_cash_flow,cash_conversion_cycle',
        ]);
        $keys = array_values(array_unique($validated['kpis'] ?? []));
        \App\Models\SystemSetting::setValue('cmr_enabled_kpis', json_encode($keys));
        return redirect()->route('accounting.reports.consolidated-management-report', $request->only(['period','year','month']))
            ->with('success', 'KPIs updated');
    }
}
