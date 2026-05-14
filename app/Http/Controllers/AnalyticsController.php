<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ChartAccount;
use App\Models\GlTransaction;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Sales\CashSale;
use App\Models\Sales\PosSale;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Receipt;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Inventory\Item;
use App\Services\ManagementReportService;

class AnalyticsController extends Controller
{
    protected $managementReportService;

    public function __construct(ManagementReportService $managementReportService)
    {
        $this->managementReportService = $managementReportService;
    }

    /**
     * Display the analytics dashboard.
     */
    public function analytics()
    {
        $user = auth()->user();
        $company = $user->company;
        
        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Company not found.');
        }

        $branchId = session('branch_id') ?? Auth::user()->branch_id ?? null;
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }

        return view('analytics.index', compact('company', 'branchId', 'permittedBranchIds'));
    }

    /**
     * Get dashboard data for the selected period.
     */
    public function getDashboardData(Request $request)
    {
        $user = auth()->user();
        $company = $user->company;
        
        if (!$company) {
            return response()->json(['error' => 'Company not found.'], 404);
        }

        $period = $request->input('period', 'month'); // daily, weekly, month, quarter, year, custom
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $month = $request->input('month');
        $quarter = $request->input('quarter');
        $year = $request->input('year', now()->year);
        $branchId = $request->input('branch_id', session('branch_id') ?? Auth::user()->branch_id ?? null);
        
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }

        // Calculate date range based on period
        if ($period === 'custom' && $startDate && $endDate) {
            $start = $startDate;
            $end = $endDate;
        } else {
            list($start, $end) = $this->getDateRange($period, $month, $year, $quarter);
        }

        \Log::info('Analytics Date Range Calculation', [
            'period' => $period,
            'month' => $month,
            'year' => $year,
            'start_date' => $start,
            'end_date' => $end
        ]);

        // Calculate previous period
        $days = (new \DateTime($start))->diff(new \DateTime($end))->days + 1;
        $prevEnd = (new \DateTime($start))->modify('-1 day')->format('Y-m-d');
        $prevStart = (new \DateTime($prevEnd))->modify('-' . ($days - 1) . ' day')->format('Y-m-d');

        // Use ManagementReportService to compute KPIs
        $options = [
            'company_id' => $company->id,
            'branch_ids' => $permittedBranchIds,
            'branch_id' => $branchId,
            'start_date' => $start,
            'end_date' => $end,
        ];

        $result = $this->managementReportService->compute($options);
        
        // Transform KPIs array into associative array by key
        $kpisArray = $result['kpis'] ?? [];
        $kpis = [];
        foreach ($kpisArray as $kpi) {
            $kpis[$kpi['key']] = [
                'current' => $kpi['value'] ?? 0,
                'previous' => $kpi['previous'] ?? null,
                'change_percent' => $kpi['change_percent'] ?? 0,
                'trend' => $kpi['trend'] ?? 'flat',
                'label' => $kpi['label'] ?? '',
            ];
        }
        
        // Add gross_profit if not present (calculate from revenue and COGS)
        if (!isset($kpis['gross_profit'])) {
            $revenue = $kpis['revenue']['current'] ?? 0;
            // Calculate COGS from revenue and gross profit margin
            $grossProfitMargin = $kpis['gross_profit_margin']['current'] ?? 0;
            $grossProfit = $revenue != 0 ? ($revenue * ($grossProfitMargin / 100)) : 0;
            $prevRevenue = $kpis['revenue']['previous'] ?? null;
            $prevGrossProfitMargin = $kpis['gross_profit_margin']['previous'] ?? null;
            $prevGrossProfit = ($prevRevenue && $prevGrossProfitMargin) ? ($prevRevenue * ($prevGrossProfitMargin / 100)) : null;
            
            $grossProfitChange = $prevGrossProfit && $prevGrossProfit != 0 
                ? (($grossProfit - $prevGrossProfit) / abs($prevGrossProfit)) * 100 
                : ($grossProfit != 0 ? 100.0 : 0.0);
            $grossProfitTrend = $prevGrossProfit ? ($grossProfit > $prevGrossProfit ? 'up' : ($grossProfit < $prevGrossProfit ? 'down' : 'flat')) : 'flat';
            
            $kpis['gross_profit'] = [
                'current' => $grossProfit,
                'previous' => $prevGrossProfit,
                'change_percent' => $grossProfitChange,
                'trend' => $grossProfitTrend,
                'label' => 'Gross Profit',
            ];
        }

        // Get chart data
        $revenueTrend = $this->getRevenueTrend($start, $end, $branchId, $permittedBranchIds);
        $expenseComposition = $this->getExpenseComposition($start, $end, $branchId, $permittedBranchIds);
        $cashFlowMovement = $this->getCashFlowMovement($start, $end, $branchId, $permittedBranchIds);
        $topCustomers = $this->getTopCustomers($start, $end, $branchId, $permittedBranchIds, 5);
        $topProducts = $this->getTopProducts($start, $end, $branchId, $permittedBranchIds, 5);

        return response()->json([
            'success' => true,
            'period' => $period,
            'start_date' => $start,
            'end_date' => $end,
            'prev_start_date' => $prevStart,
            'prev_end_date' => $prevEnd,
            'kpis' => $kpis,
            'charts' => [
                'revenue_trend' => $revenueTrend,
                'expense_composition' => $expenseComposition,
                'cash_flow_movement' => $cashFlowMovement,
                'top_customers' => $topCustomers,
                'top_products' => $topProducts,
            ],
        ]);
    }

    /**
     * Export analytics dashboard to PDF (server-side).
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        $company = $user->company;
        
        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Company not found.');
        }

        $period = $request->input('period', 'month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $month = $request->input('month');
        $quarter = $request->input('quarter');
        $year = $request->input('year', now()->year);
        $branchId = $request->input('branch_id', session('branch_id') ?? Auth::user()->branch_id ?? null);
        
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }

        // Calculate date range based on period
        if ($period === 'custom' && $startDate && $endDate) {
            $start = $startDate;
            $end = $endDate;
        } else {
            list($start, $end) = $this->getDateRange($period, $month, $year, $quarter);
        }

        // Calculate previous period
        $days = (new \DateTime($start))->diff(new \DateTime($end))->days + 1;
        $prevEnd = (new \DateTime($start))->modify('-1 day')->format('Y-m-d');
        $prevStart = (new \DateTime($prevEnd))->modify('-' . ($days - 1) . ' day')->format('Y-m-d');

        // Use ManagementReportService to compute KPIs
        $options = [
            'company_id' => $company->id,
            'branch_ids' => $permittedBranchIds,
            'branch_id' => $branchId,
            'start_date' => $start,
            'end_date' => $end,
        ];

        $result = $this->managementReportService->compute($options);
        
        // Transform KPIs array into associative array by key
        $kpisArray = $result['kpis'] ?? [];
        $kpis = [];
        foreach ($kpisArray as $kpi) {
            $kpis[$kpi['key']] = [
                'current' => $kpi['value'] ?? 0,
                'previous' => $kpi['previous'] ?? null,
                'change_percent' => $kpi['change_percent'] ?? 0,
                'trend' => $kpi['trend'] ?? 'flat',
                'label' => $kpi['label'] ?? '',
            ];
        }
        
        // Add gross_profit if not present
        if (!isset($kpis['gross_profit'])) {
            $revenue = $kpis['revenue']['current'] ?? 0;
            $grossProfitMargin = $kpis['gross_profit_margin']['current'] ?? 0;
            $grossProfit = $revenue != 0 ? ($revenue * ($grossProfitMargin / 100)) : 0;
            $prevRevenue = $kpis['revenue']['previous'] ?? null;
            $prevGrossProfitMargin = $kpis['gross_profit_margin']['previous'] ?? null;
            $prevGrossProfit = ($prevRevenue && $prevGrossProfitMargin) ? ($prevRevenue * ($prevGrossProfitMargin / 100)) : null;
            
            $grossProfitChange = $prevGrossProfit && $prevGrossProfit != 0 
                ? (($grossProfit - $prevGrossProfit) / abs($prevGrossProfit)) * 100 
                : ($grossProfit != 0 ? 100.0 : 0.0);
            $grossProfitTrend = $prevGrossProfit ? ($grossProfit > $prevGrossProfit ? 'up' : ($grossProfit < $prevGrossProfit ? 'down' : 'flat')) : 'flat';
            
            $kpis['gross_profit'] = [
                'current' => $grossProfit,
                'previous' => $prevGrossProfit,
                'change_percent' => $grossProfitChange,
                'trend' => $grossProfitTrend,
                'label' => 'Gross Profit',
            ];
        }

        // Get chart data
        $revenueTrend = $this->getRevenueTrend($start, $end, $branchId, $permittedBranchIds);
        $expenseComposition = $this->getExpenseComposition($start, $end, $branchId, $permittedBranchIds);
        $cashFlowMovement = $this->getCashFlowMovement($start, $end, $branchId, $permittedBranchIds);
        $topCustomers = $this->getTopCustomers($start, $end, $branchId, $permittedBranchIds, 5);
        $topProducts = $this->getTopProducts($start, $end, $branchId, $permittedBranchIds, 5);

        // Get branch info
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('analytics.pdf', [
            'company' => $company,
            'branch' => $branch,
            'user' => $user,
            'period' => $period,
            'start_date' => $start,
            'end_date' => $end,
            'prev_start_date' => $prevStart,
            'prev_end_date' => $prevEnd,
            'kpis' => $kpis,
            'charts' => [
                'revenue_trend' => $revenueTrend,
                'expense_composition' => $expenseComposition,
                'cash_flow_movement' => $cashFlowMovement,
                'top_customers' => $topCustomers,
                'top_products' => $topProducts,
            ],
            'generatedBy' => $user->name ?? 'System',
            'generatedOn' => now(),
        ]);

        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-right', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);

        $filename = 'Analytics_Dashboard_' . $start . '_to_' . $end . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Get drill-down data for a specific metric.
     */
    public function getDrillDown(Request $request)
    {
        $user = auth()->user();
        $company = $user->company;
        
        if (!$company) {
            return response()->json(['error' => 'Company not found.'], 404);
        }

        $metric = $request->input('metric'); // revenue, expenses, etc.
        $groupBy = $request->input('group_by'); // product, customer, category, region
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branchId = $request->input('branch_id', session('branch_id') ?? Auth::user()->branch_id ?? null);
        
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();
        if (empty($permittedBranchIds) && $user->branch_id) {
            $permittedBranchIds = [(int)$user->branch_id];
        }

        $data = [];

        switch ($metric) {
            case 'revenue':
                if ($groupBy === 'customer') {
                    $data = $this->getRevenueByCustomer($startDate, $endDate, $branchId, $permittedBranchIds);
                } elseif ($groupBy === 'product') {
                    $data = $this->getRevenueByProduct($startDate, $endDate, $branchId, $permittedBranchIds);
                } elseif ($groupBy === 'category') {
                    $data = $this->getRevenueByCategory($startDate, $endDate, $branchId, $permittedBranchIds);
                }
                break;
            case 'expenses':
                if ($groupBy === 'category') {
                    $data = $this->getExpenseComposition($startDate, $endDate, $branchId, $permittedBranchIds);
                }
                break;
        }

        return response()->json([
            'success' => true,
            'metric' => $metric,
            'group_by' => $groupBy,
            'data' => $data,
        ]);
    }

    /**
     * Get date range based on period type.
     */
    private function getDateRange($period, $month = null, $year = null, $quarter = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        switch ($period) {
            case 'daily':
                $date = now();
                return [$date->toDateString(), $date->toDateString()];
            case 'weekly':
                $date = now();
                return [$date->copy()->startOfWeek()->toDateString(), $date->copy()->endOfWeek()->toDateString()];
            case 'month':
                $date = \Carbon\Carbon::create($year, $month, 1);
                return [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()];
            case 'quarter':
                $date = \Carbon\Carbon::create($year, 1, 1);
                // Use provided quarter or calculate from current month
                $selectedQuarter = $quarter ?? ceil(now()->month / 3);
                $startMonth = (($selectedQuarter - 1) * 3) + 1;
                return [
                    $date->copy()->month($startMonth)->startOfMonth()->toDateString(),
                    $date->copy()->month($startMonth + 2)->endOfMonth()->toDateString()
                ];
            case 'year':
                $date = \Carbon\Carbon::create($year, 1, 1);
                return [$date->copy()->startOfYear()->toDateString(), $date->copy()->endOfYear()->toDateString()];
            default:
                $date = \Carbon\Carbon::create($year, $month, 1);
                return [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()];
        }
    }

    /**
     * Get revenue trend data.
     */
    private function getRevenueTrend($startDate, $endDate, $branchId, $permittedBranchIds)
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;
        
        $base = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['income', 'revenue'])
            ->whereBetween(DB::raw('DATE(gt.date)'), [$startDate, $endDate])
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('gt.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('gt.branch_id', $branchId));

        $revenue = (clone $base)
            ->selectRaw('DATE(gt.date) as date, SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE -gt.amount END) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $baseExp = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['expense', 'expenses'])
            ->whereBetween(DB::raw('DATE(gt.date)'), [$startDate, $endDate])
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('gt.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('gt.branch_id', $branchId));

        $expenses = (clone $baseExp)
            ->selectRaw('DATE(gt.date) as date, SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE -gt.amount END) as expenses')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Merge and calculate profit
        $dates = collect($revenue)->merge($expenses)->pluck('date')->unique()->sort()->values();
        $result = $dates->map(function($date) use ($revenue, $expenses) {
            $rev = $revenue->where('date', $date)->first()->revenue ?? 0;
            $exp = $expenses->where('date', $date)->first()->expenses ?? 0;
            return [
                'date' => $date,
                'revenue' => (float)$rev,
                'expenses' => (float)$exp,
                'profit' => (float)$rev - (float)$exp,
            ];
        });

        return $result->values()->all();
    }

    /**
     * Get expense composition with hierarchical structure (categories and subcategories).
     */
    private function getExpenseComposition($startDate, $endDate, $branchId, $permittedBranchIds)
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;
        
        // Get expenses grouped by account_class_group (main category) and account_name (subcategory)
        $expenses = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['expense', 'expenses'])
            ->whereBetween(DB::raw('DATE(gt.date)'), [$startDate, $endDate])
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('gt.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('gt.branch_id', $branchId))
            ->selectRaw('
                acg.name as expense_category,
                ca.account_name as expense_subcategory,
                SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE -gt.amount END) as amount
            ')
            ->groupBy('acg.name', 'ca.account_name')
            ->orderByDesc('amount')
            ->get();

        // Build hierarchical structure
        $hierarchical = [];
        foreach ($expenses as $expense) {
            $category = $expense->expense_category ?? 'Other Expenses';
            $subcategory = $expense->expense_subcategory ?? 'Uncategorized';
            $amount = (float)$expense->amount;

            if (!isset($hierarchical[$category])) {
                $hierarchical[$category] = [
                    'category' => $category,
                    'amount' => 0,
                    'subcategories' => []
                ];
            }

            $hierarchical[$category]['amount'] += $amount;
            $hierarchical[$category]['subcategories'][] = [
                'subcategory' => $subcategory,
                'amount' => $amount
            ];
        }

        // Convert to array and sort by total amount
        $result = array_values($hierarchical);
        usort($result, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        return $result;
    }

    /**
     * Get cash flow movement.
     */
    private function getCashFlowMovement($startDate, $endDate, $branchId, $permittedBranchIds)
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;
        
        // Beginning balance (cash at start of period)
        $beginning = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->where('acg.company_id', $companyId)
            ->whereRaw('LOWER(ca.account_name) LIKE ?', ['%cash%'])
            ->where(DB::raw('DATE(gt.date)'), '<', $startDate)
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('gt.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('gt.branch_id', $branchId))
            ->selectRaw('SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE -gt.amount END) as balance')
            ->first();

        $inflows = DB::table('receipts')
            ->whereBetween(DB::raw('DATE(date)'), [$startDate, $endDate])
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $outflows = DB::table('payments')
            ->whereBetween(DB::raw('DATE(date)'), [$startDate, $endDate])
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $ending = (float)($beginning->balance ?? 0) + (float)$inflows - (float)$outflows;

        return [
            'beginning' => (float)($beginning->balance ?? 0),
            'inflows' => (float)$inflows,
            'outflows' => (float)$outflows,
            'ending' => $ending,
        ];
    }

    /**
     * Get top customers.
     */
    private function getTopCustomers($startDate, $endDate, $branchId, $permittedBranchIds, $limit = 5)
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;
        
        if (!$companyId) {
            \Log::warning('Analytics: No company_id found for user', ['user_id' => $user->id]);
            return [];
        }
        
        try {
            // Build base query conditions
            $branchCondition = function($query) use ($branchId, $permittedBranchIds) {
                if (!empty($permittedBranchIds) && !$branchId) {
                    $query->whereIn('branch_id', $permittedBranchIds);
                } elseif ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            };
            
            // Get Sales Invoices (include all statuses except cancelled)
            $invoicesQuery = SalesInvoice::with('customer')
                ->where('company_id', $companyId)
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled');
            $branchCondition($invoicesQuery);
            $invoices = $invoicesQuery->get();
            
            // Get Cash Sales
            $cashSalesQuery = CashSale::with('customer')
                ->where('company_id', $companyId)
                ->whereBetween('sale_date', [$startDate, $endDate]);
            $branchCondition($cashSalesQuery);
            $cashSales = $cashSalesQuery->get();
            
            // Get POS Sales
            $posSalesQuery = PosSale::with('customer')
                ->where('company_id', $companyId)
                ->whereBetween('sale_date', [$startDate, $endDate]);
            $branchCondition($posSalesQuery);
            $posSales = $posSalesQuery->get();
            
            // Combine all transactions
            $allTransactions = collect();
            
            // Process Sales Invoices
            foreach ($invoices as $invoice) {
                if ($invoice->customer_id && $invoice->customer) {
                    $allTransactions->push([
                        'customer_id' => $invoice->customer_id,
                        'customer_name' => $invoice->customer->name,
                        'revenue' => $invoice->subtotal ?? 0
                    ]);
                }
            }
            
            // Process Cash Sales
            foreach ($cashSales as $cashSale) {
                if ($cashSale->customer_id && $cashSale->customer) {
                    $allTransactions->push([
                        'customer_id' => $cashSale->customer_id,
                        'customer_name' => $cashSale->customer->name,
                        'revenue' => $cashSale->subtotal ?? 0
                    ]);
                }
            }
            
            // Process POS Sales (customer_id can be null, use customer_name if available)
            foreach ($posSales as $posSale) {
                if ($posSale->customer_id && $posSale->customer) {
                    $allTransactions->push([
                        'customer_id' => $posSale->customer_id,
                        'customer_name' => $posSale->customer->name ?? $posSale->customer_name,
                        'revenue' => $posSale->subtotal ?? 0
                    ]);
                } elseif ($posSale->customer_name) {
                    // POS sales might have customer_name without customer_id
                    $allTransactions->push([
                        'customer_id' => null,
                        'customer_name' => $posSale->customer_name,
                        'revenue' => $posSale->subtotal ?? 0
                    ]);
                }
            }
            
            // Group by customer and sum revenue
            $customersData = $allTransactions
                ->groupBy(function($item) {
                    // Group by customer_id if available, otherwise by name
                    return $item['customer_id'] ?? 'name_' . $item['customer_name'];
                })
                ->map(function($transactions, $key) {
                    $firstTransaction = $transactions->first();
                    return [
                        'customer' => $firstTransaction['customer_name'] ?? 'Unknown',
                        'revenue' => $transactions->sum('revenue')
                    ];
                })
                ->filter(function($item) {
                    return $item['revenue'] > 0 && !empty($item['customer']) && $item['customer'] !== 'Unknown';
                })
                ->sortByDesc('revenue')
                ->take($limit)
                ->values();

            \Log::info('Analytics Top Customers Query', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'invoices_count' => $invoices->count(),
                'cash_sales_count' => $cashSales->count(),
                'pos_sales_count' => $posSales->count(),
                'total_transactions' => $allTransactions->count(),
                'customers_count' => $customersData->count(),
                'results' => $customersData->toArray()
            ]);

            return $customersData->all();
        } catch (\Exception $e) {
            \Log::error('Analytics Top Customers Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get top products.
     */
    private function getTopProducts($startDate, $endDate, $branchId, $permittedBranchIds, $limit = 5)
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;
        
        // Get products from sales invoices
        $invoiceProducts = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->join('inventory_items as ii', 'sii.inventory_item_id', '=', 'ii.id')
            ->where('si.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(si.invoice_date)'), [$startDate, $endDate])
            ->whereNotIn('si.status', ['cancelled'])
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('si.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('si.branch_id', $branchId))
            ->selectRaw('ii.id as item_id, ii.name as product_name, SUM(sii.quantity * sii.unit_price) as revenue')
            ->groupBy('ii.id', 'ii.name')
            ->get();

        // Get products from cash sales
        $cashSaleProducts = DB::table('cash_sale_items as csi')
            ->join('cash_sales as cs', 'csi.cash_sale_id', '=', 'cs.id')
            ->join('inventory_items as ii', 'csi.inventory_item_id', '=', 'ii.id')
            ->where('cs.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(cs.sale_date)'), [$startDate, $endDate])
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('cs.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('cs.branch_id', $branchId))
            ->selectRaw('ii.id as item_id, ii.name as product_name, SUM(csi.quantity * csi.unit_price) as revenue')
            ->groupBy('ii.id', 'ii.name')
            ->get();

        // Get products from POS sales
        $posSaleProducts = DB::table('pos_sale_items as psi')
            ->join('pos_sales as ps', 'psi.pos_sale_id', '=', 'ps.id')
            ->join('inventory_items as ii', 'psi.inventory_item_id', '=', 'ii.id')
            ->where('ps.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(ps.sale_date)'), [$startDate, $endDate])
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('ps.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('ps.branch_id', $branchId))
            ->selectRaw('ii.id as item_id, ii.name as product_name, SUM(psi.quantity * psi.unit_price) as revenue')
            ->groupBy('ii.id', 'ii.name')
            ->get();

        // Combine all products
        $allProducts = collect();
        
        // Add invoice products
        foreach ($invoiceProducts as $product) {
            $allProducts->push([
                'item_id' => $product->item_id,
                'product_name' => $product->product_name,
                'revenue' => (float)$product->revenue
            ]);
        }
        
        // Add cash sale products
        foreach ($cashSaleProducts as $product) {
            $existing = $allProducts->firstWhere('item_id', $product->item_id);
            if ($existing) {
                $existing['revenue'] += (float)$product->revenue;
            } else {
                $allProducts->push([
                    'item_id' => $product->item_id,
                    'product_name' => $product->product_name,
                    'revenue' => (float)$product->revenue
                ]);
            }
        }
        
        // Add POS sale products
        foreach ($posSaleProducts as $product) {
            $existing = $allProducts->firstWhere('item_id', $product->item_id);
            if ($existing) {
                $existing['revenue'] += (float)$product->revenue;
            } else {
                $allProducts->push([
                    'item_id' => $product->item_id,
                    'product_name' => $product->product_name,
                    'revenue' => (float)$product->revenue
                ]);
            }
        }

        // Sort by revenue and take top N
        $topProducts = $allProducts->sortByDesc('revenue')->take($limit);

        return $topProducts->map(function($item) {
            return [
                'name' => $item['product_name'] ?? 'Unknown',
                'product' => $item['product_name'] ?? 'Unknown',
                'revenue' => (float)($item['revenue'] ?? 0),
            ];
        })->values()->all();
    }

    /**
     * Get revenue by customer (drill-down).
     */
    private function getRevenueByCustomer($startDate, $endDate, $branchId, $permittedBranchIds)
    {
        return $this->getTopCustomers($startDate, $endDate, $branchId, $permittedBranchIds, 20);
    }

    /**
     * Get revenue by product (drill-down).
     */
    private function getRevenueByProduct($startDate, $endDate, $branchId, $permittedBranchIds)
    {
        return $this->getTopProducts($startDate, $endDate, $branchId, $permittedBranchIds, 20);
    }

    /**
     * Get revenue by category (drill-down).
     */
    private function getRevenueByCategory($startDate, $endDate, $branchId, $permittedBranchIds)
    {
        $categories = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->join('inventory_items as ii', 'sii.inventory_item_id', '=', 'ii.id')
            ->join('inventory_categories as ic', 'ii.category_id', '=', 'ic.id')
            ->whereBetween(DB::raw('DATE(si.invoice_date)'), [$startDate, $endDate])
            ->whereNotIn('si.status', ['cancelled', 'draft'])
            ->when(!empty($permittedBranchIds) && !$branchId, fn($q) => $q->whereIn('si.branch_id', $permittedBranchIds))
            ->when($branchId, fn($q) => $q->where('si.branch_id', $branchId))
            ->selectRaw('ic.name as category_name, SUM(sii.quantity * sii.unit_price) as revenue')
            ->groupBy('ic.id', 'ic.name')
            ->orderByDesc('revenue')
            ->get();

        return $categories->map(function($item) {
            return [
                'category' => $item->category_name,
                'revenue' => (float)$item->revenue,
            ];
        })->all();
    }
}

