<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Sales\CreditNote;
use App\Models\Sales\CreditNoteItem;
use App\Models\Sales\DeliveryNote;
use App\Models\Sales\DeliveryItem;
use App\Models\Sales\PosSale;
use App\Models\Sales\PosSaleItem;
use App\Models\Sales\CashSale;
use App\Models\Sales\CashSaleItem;
use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Branch;
use App\Models\User;
use App\Models\ChartAccount;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class SalesReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'require.branch', 'company.scope']);
    }

    /**
     * Get user's accessible branches for filtering
     */
    private function getUserAccessibleBranches()
    {
        $user = auth()->user();
        
        // Get all branches for the user's company (like in ChangeBranchController)
        $branches = Branch::where('company_id', $user->company_id)->get();
        
        // If user has only one branch, return just that branch
        if ($branches->count() === 1) {
            return $branches;
        }
        
        // If user has multiple branches, add "All Branches" option
        $allBranchesOption = (object) [
            'id' => 'all',
            'name' => 'All Branches',
            'company_id' => $user->company_id
        ];
        
        return $branches->prepend($allBranchesOption);
    }

    /**
     * Apply branch filtering to a query
     */
    private function applyBranchFilter($query, $branchId)
    {
        // Handle "all" branches selection
        if ($branchId === 'all') {
            $user = auth()->user();
            $companyBranches = Branch::where('company_id', $user->company_id)->pluck('id')->toArray();
            $query->whereIn('branch_id', $companyBranches);
        } else {
            $query->where('branch_id', $branchId);
        }
        
        return $query;
    }

    /**
     * Default branch filter for customer statement (matches customerStatement()).
     */
    private function defaultCustomerStatementBranchId(): mixed
    {
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();

        return $userBranches->count() > 1 ? 'all' : ($sessionBranchId ?? $userBranchId);
    }

    private function customerStatementOpeningBalance(int $customerId, Carbon $dateFrom, $branchId): float
    {
        $openingBalanceQuery = SalesInvoice::where('customer_id', $customerId)
            ->where('invoice_date', '<', $dateFrom)
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($openingBalanceQuery, $branchId);

        return (float) $openingBalanceQuery->sum('total_amount');
    }

    /**
     * Build invoice-period transactions including bank/cash receipts, payment vouchers, and cash-deposit journal payments.
     *
     * @return array{transactions: \Illuminate\Support\Collection, invoices: \Illuminate\Support\Collection, creditNotes: \Illuminate\Support\Collection}
     */
    private function buildCustomerStatementCore(int $customerId, Carbon $dateFrom, Carbon $dateTo, $branchId): array
    {
        $invoicesQuery = SalesInvoice::where('customer_id', $customerId)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($invoicesQuery, $branchId);
        $invoices = $invoicesQuery->with(['receipts', 'payments', 'cashDepositPaymentJournals.items', 'cashDepositPaymentJournals.user'])
            ->orderBy('invoice_date')
            ->get();

        $creditNotesQuery = CreditNote::where('customer_id', $customerId)
            ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($creditNotesQuery, $branchId);
        $creditNotes = $creditNotesQuery->orderBy('credit_note_date')->get();

        $transactions = collect();

        foreach ($invoices as $invoice) {
            $transactions->push((object) [
                'date' => $invoice->invoice_date,
                'reference' => $invoice->invoice_number,
                'reference_id' => $invoice->id,
                'description' => 'Invoice - ' . $invoice->invoice_number,
                'amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'balance_due' => $invoice->balance_due,
                'status' => $invoice->status,
                'type' => 'invoice',
                'invoice_id' => $invoice->id,
            ]);

            foreach ($invoice->receipts as $receipt) {
                $transactions->push((object) [
                    'date' => $receipt->date,
                    'reference' => $receipt->reference_number ?? 'RCP-' . $receipt->id,
                    'reference_id' => $receipt->id,
                    'description' => 'Payment - ' . ($receipt->description ?? 'Receipt for Invoice ' . $invoice->invoice_number),
                    'amount' => $receipt->amount,
                    'paid_amount' => $receipt->amount,
                    'balance_due' => 0,
                    'status' => 'paid',
                    'type' => 'payment',
                    'payment_source' => 'receipt',
                    'invoice_id' => $invoice->id,
                    'payment_method' => $receipt->bankAccount ? $receipt->bankAccount->account_name : 'Cash',
                ]);
            }

            foreach ($invoice->payments as $payment) {
                $transactions->push((object) [
                    'date' => $payment->date,
                    'reference' => $payment->reference_number ?? 'PAY-' . $payment->id,
                    'reference_id' => $payment->id,
                    'description' => 'Payment Voucher - ' . ($payment->description ?? 'Payment for Invoice ' . $invoice->invoice_number),
                    'amount' => $payment->amount,
                    'paid_amount' => $payment->amount,
                    'balance_due' => 0,
                    'status' => 'paid',
                    'type' => 'payment',
                    'payment_source' => 'voucher',
                    'invoice_id' => $invoice->id,
                    'payment_method' => $payment->bankAccount ? $payment->bankAccount->account_name : 'Manual Payment',
                ]);
            }

            foreach ($invoice->cashDepositPaymentJournals as $journal) {
                if ((int) $journal->branch_id !== (int) $invoice->branch_id) {
                    continue;
                }
                $amount = (float) $journal->items->where('nature', 'debit')->sum('amount');
                if ($amount <= 0) {
                    continue;
                }
                $transactions->push((object) [
                    'date' => $journal->date,
                    'reference' => 'JRN-' . $journal->id,
                    'reference_id' => $journal->id,
                    'description' => 'Payment - ' . ($journal->description ?? 'Cash deposit payment for Invoice ' . $invoice->invoice_number),
                    'amount' => $amount,
                    'paid_amount' => $amount,
                    'balance_due' => 0,
                    'status' => 'paid',
                    'type' => 'payment',
                    'payment_source' => 'journal_cash_deposit',
                    'invoice_id' => $invoice->id,
                    'payment_method' => 'Cash deposit',
                ]);
            }
        }

        foreach ($creditNotes as $creditNote) {
            $transactions->push((object) [
                'date' => $creditNote->credit_note_date,
                'reference' => $creditNote->credit_note_number,
                'reference_id' => $creditNote->id,
                'description' => 'Credit Note - ' . $creditNote->credit_note_number,
                'amount' => $creditNote->total_amount,
                'type' => 'credit_note',
            ]);
        }

        $transactions = $transactions->sortBy('date')->values();

        return [
            'transactions' => $transactions,
            'invoices' => $invoices,
            'creditNotes' => $creditNotes,
        ];
    }

    public function index()
    {
        $this->authorize('view sales reports');
        return view('sales.reports.index');
    }

    public function salesSummary(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        $customerId = $request->get('customer_id');
        
        // Get user's assigned branches
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
        $branchId = $request->get('branch_id', $defaultBranchId);
        $groupBy = $request->get('group_by', 'day'); // day, week, month, year

        // Ensure Carbon instances
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        // Get combined sales data (invoices + POS + cash)
        $combinedSales = $this->getCombinedSalesData($dateFrom, $dateTo, $branchId, [$this, 'applyAssignedBranchFilter']);

        // Filter by customer if specified
        if ($customerId) {
            $combinedSales = $combinedSales->filter(function ($sale) use ($customerId) {
                return $sale->customer_id == $customerId;
            });
        }

        // Group by time period
        $groupedData = $combinedSales->groupBy(function ($sale) use ($groupBy) {
            $date = Carbon::parse($sale->date);
            switch ($groupBy) {
                case 'week':
                    return $date->format('o-\WW'); // ISO week, e.g., 2025-W02
                case 'month':
                    return $date->format('Y-m'); // 2025-01
                case 'year':
                    return $date->format('Y');
                default:
                    return $date->format('Y-m-d');
            }
        });

        // Aggregate metrics per group
        $summaryData = $groupedData->map(function ($group, $key) use ($groupBy) {
            // Totals
            $totalQuantity = $group->sum(function ($sale) {
                return $sale->items->sum('quantity');
            });
            $invoiceCount = $group->count();
            // Gross sales = subtotal + VAT (ignore discounts/withholding)
            $grossSales = $group->sum(function ($sale) {
                return (float)($sale->subtotal ?? 0) + (float)($sale->vat_amount ?? 0);
            });
            $totalDiscounts = $group->sum(function ($sale) {
                return (float)($sale->discount_amount ?? 0);
            });
            $netSales = $grossSales - $totalDiscounts;

            // Period label
            $anyDate = Carbon::parse($group->first()->date);
            $periodLabel = match ($groupBy) {
                'week' => $anyDate->isoFormat('GGGG-[W]WW'),
                'month' => $anyDate->format('M Y'),
                'year' => $anyDate->format('Y'),
                default => $anyDate->format('d M Y'),
            };

            // Average daily sales (use distinct active days within this group)
            $uniqueDays = $group->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->unique()->count();
            $averageDailySales = $uniqueDays > 0 ? ($netSales / $uniqueDays) : 0;

            return [
                'key' => $key,
                'period_label' => $periodLabel,
                'invoice_count' => $invoiceCount,
                'total_quantity' => (float)$totalQuantity,
                'total_sales' => (float)$grossSales,
                'total_discounts' => (float)$totalDiscounts,
                'net_sales' => (float)$netSales,
                'average_daily_sales' => (float)$averageDailySales,
                // growth_vs_prev will be computed after sorting
            ];
        })->sortKeys(); // ensure chronological order by key

        // Compute growth vs previous period based on net sales
        $previousNet = null;
        $summaryData = $summaryData->values()->map(function ($row) use (&$previousNet) {
            $growth = null;
            if ($previousNet !== null && $previousNet != 0) {
                $growth = (($row['net_sales'] - $previousNet) / $previousNet) * 100.0;
            }
            $previousNet = $row['net_sales'];
            $row['growth_vs_prev'] = $growth; // percent
            return $row;
        });

        $customers = Customer::where('company_id', auth()->user()->company_id)
            ->when($branchId && $branchId !== 'all', function($q) use ($branchId) { 
                $q->where('branch_id', $branchId); 
            })
            ->orderBy('name')->get();
        $branches = $this->getUserAssignedBranches();

        return view('sales.reports.sales-summary', compact(
            'summaryData', 'customers', 'branches', 'dateFrom', 'dateTo', 
            'customerId', 'branchId', 'groupBy'
        ));
    }

    public function salesByProduct(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from'))->startOfDay() : Carbon::now()->startOfMonth()->startOfDay();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to'))->endOfDay() : Carbon::now()->endOfMonth()->endOfDay();
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Get combined product sales (from invoices + POS + cash)
        $productSales = $this->getCombinedProductSales($dateFrom, $dateTo, $branchId, [$this, 'applyBranchFilter']);

        // Compute derived metrics
        $totalRevenueAll = $productSales->sum('total_revenue') ?: 0;
        $productSales = $productSales->map(function ($item) use ($totalRevenueAll) {
            $item->gross_profit = (float)($item->total_revenue ?? 0) - (float)($item->total_cogs ?? 0);
            $item->profit_margin_percentage = ($item->total_revenue ?? 0) > 0
                ? ($item->gross_profit / $item->total_revenue) * 100
                : 0;
            $item->contribution_percentage = $totalRevenueAll > 0
                ? (($item->total_revenue ?? 0) / $totalRevenueAll) * 100
                : 0;
            return $item;
        });

        // For Sales by Product Report, prioritize assigned branch
        if ($userBranches->count() > 1) {
            // If user has multiple branches, show only assigned branch + all branches option
            $assignedBranch = null;
            if ($sessionBranchId) {
                $assignedBranch = $userBranches->where('id', $sessionBranchId)->first();
            }
            
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => auth()->user()->company_id
            ];
            
            if ($assignedBranch) {
                // Show only assigned branch + all branches option
                $branches = collect([$assignedBranch])->prepend($allBranchesOption);
            } else {
                // Fallback to showing all branches with "All Branches" first
                $branches = $userBranches->prepend($allBranchesOption);
            }
        } else {
            // Single branch - just show that branch
            $branches = $userBranches;
        }

        return view('sales.reports.sales-by-product', compact(
            'productSales', 'branches', 'dateFrom', 'dateTo', 'branchId'
        ));
    }

    public function salesByCustomer(Request $request)
    {
        $this->authorize('view customer reports');
        
        $dateFrom = $request->get('date_from', Carbon::today()->startOfDay());
        $dateTo = $request->get('date_to', Carbon::today()->endOfDay());
        // Branch selection: use assigned branches; if more than one, default to 'all'
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? auth()->user()->branch_id);
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Ensure Carbon instances
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        // Get combined sales data (invoices + POS + cash)
        $combinedSales = $this->getCombinedSalesData($dateFrom, $dateTo, $branchId, [$this, 'applyBranchFilter']);

        // Group by customer
        $customerSales = $combinedSales->groupBy('customer_id')->map(function ($sales, $customerId) {
            $customer = $sales->first()->customer;
            $totalSales = $sales->sum('total_amount');
            $invoiceCount = $sales->count();
            $avgInvoiceValue = $invoiceCount > 0 ? ($totalSales / $invoiceCount) : 0;
            $firstDate = $sales->min('date');
            $lastDate = $sales->max('date');
            
            // Calculate total cost from items
            $totalCost = 0;
            foreach ($sales as $sale) {
                $totalCost += $this->calculateItemsCost($sale->items);
            }
            
            return (object)[
                'customer_id' => $customerId,
                'customer' => $customer,
                'total_sales' => $totalSales,
                'total_cost' => $totalCost,
                'gross_profit' => $totalSales - $totalCost,
                'invoice_count' => $invoiceCount,
                'avg_invoice_value' => $avgInvoiceValue,
                'first_invoice_date' => $firstDate,
                'last_invoice_date' => $lastDate,
            ];
        })->sortByDesc('total_sales')->values();

        $totalSales = $customerSales->sum('total_sales');
        
        $customerSales = $customerSales->map(function ($customer) use ($totalSales) {
            $customer->contribution_percentage = $totalSales > 0 
                ? ($customer->total_sales / $totalSales) * 100 
                : 0;
            
            // Add customer details and outstanding balance (invoices only - POS and cash have no outstanding)
            if ($customer->customer) {
                $customer->outstanding_balance = SalesInvoice::where('customer_id', $customer->customer->id)
                    ->where('status', '!=', 'cancelled')
                    ->where('balance_due', '>', 0)
                    ->sum('balance_due');
                
                $customer->customer_phone = $customer->customer->phone ?? 'N/A';
            } else {
                $customer->outstanding_balance = 0;
                $customer->customer_phone = 'N/A';
            }
            
            return $customer;
        });

        $branches = $this->getUserAccessibleBranches();

        return view('sales.reports.sales-by-customer', compact(
            'customerSales', 'branches', 'dateFrom', 'dateTo', 'branchId', 'totalSales'
        ));
    }

    public function salesByBranch(Request $request)
    {
        $this->authorize('view branch performance');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        // Ensure Carbon
        if (is_string($dateFrom)) { $dateFrom = Carbon::parse($dateFrom)->startOfDay(); }
        if (is_string($dateTo)) { $dateTo = Carbon::parse($dateTo)->endOfDay(); }

        // Use combined sales (Invoices + POS + Cash)
        // For sales-by-branch we want all branches, so pass 'all' to branch filter to avoid restricting
        $combined = $this->getCombinedSalesData($dateFrom, $dateTo, 'all');

        // Group by branch_id
        $branchGroups = $combined->groupBy('branch_id');
        $branchSales = $branchGroups->map(function ($sales, $branchId) {
            $totalSales = $sales->sum('total_amount');
            $invoiceCount = $sales->count();
            $customersServed = $sales->pluck('customer_id')->filter()->unique()->count();
            $avgInvoiceValue = $invoiceCount > 0 ? ($totalSales / $invoiceCount) : 0;
            // Total cost from items
            $totalCost = 0;
            foreach ($sales as $sale) {
                $totalCost += $this->calculateItemsCost($sale->items);
            }
            $grossProfit = $totalSales - $totalCost;
            $marginPct = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;
            return (object)[
                'branch_id' => $branchId,
                'branch' => \App\Models\Branch::find($branchId),
                'total_sales' => (float)$totalSales,
                'total_cost' => (float)$totalCost,
                'gross_profit' => (float)$grossProfit,
                'margin_percentage' => (float)$marginPct,
                'invoice_count' => $invoiceCount,
                'customers_served' => $customersServed,
                'avg_invoice_value' => (float)$avgInvoiceValue,
            ];
        })->values()->sortByDesc('total_sales');

        $totalSales = $branchSales->sum('total_sales');
        $branchSales = $branchSales->map(function ($branch) use ($totalSales) {
            $branch->contribution_percentage = $totalSales > 0 ? ($branch->total_sales / $totalSales) * 100 : 0;
            return $branch;
        });

        return view('sales.reports.sales-by-branch', compact(
            'branchSales', 'dateFrom', 'dateTo', 'totalSales'
        ));
    }

    public function branchProfitability(Request $request)
    {
        $this->authorize('view branch performance');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        if (is_string($dateFrom)) { $dateFrom = Carbon::parse($dateFrom)->startOfDay(); }
        if (is_string($dateTo)) { $dateTo = Carbon::parse($dateTo)->endOfDay(); }

        // 1) Revenue: combined sales (Invoices + POS + Cash) grouped by branch
        $combined = $this->getCombinedSalesData($dateFrom, $dateTo, 'all');
        $revenueByBranch = $combined->groupBy('branch_id')->map(function ($sales) {
            return (float)$sales->sum('total_amount');
        });

        // 2) Cost of Sales (COGS) per branch from GL
        $user = auth()->user();
        $company = $user->company;
        $costOfGoodsSoldAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_cost_account')->value('value') ?? 173;
        $cogsQuery = DB::table('gl_transactions')
            ->where('chart_account_id', $costOfGoodsSoldAccountId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->select('branch_id',
                DB::raw('SUM(CASE WHEN nature = "debit" THEN amount ELSE -amount END) as total_cogs'))
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        // 3) Operating Expenses per branch from GL (exclude COGS)
        $expensesQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereBetween('gl_transactions.date', [$dateFrom, $dateTo])
            ->whereIn('account_class.name', ['expenses', 'expense'])
            ->when($costOfGoodsSoldAccountId, function($q) use ($costOfGoodsSoldAccountId) {
                $q->where('chart_accounts.id', '!=', $costOfGoodsSoldAccountId);
            })
            ->select('gl_transactions.branch_id',
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as net_expense'))
            ->groupBy('gl_transactions.branch_id')
            ->get()
            ->keyBy('branch_id');

        // 4) Build branch profitability rows
        $allBranchIds = collect(array_unique(array_merge(
            $revenueByBranch->keys()->toArray(), 
            $cogsQuery->keys()->toArray(),
            $expensesQuery->keys()->toArray()
        )));
        $branchData = $allBranchIds->map(function ($branchId) use ($revenueByBranch, $cogsQuery, $expensesQuery) {
            $totalRevenue = (float)($revenueByBranch[$branchId] ?? 0);
            $costOfSales = (float)($cogsQuery[$branchId]->total_cogs ?? 0);
            $grossProfit = $totalRevenue - $costOfSales;
            $operatingExpenses = (float)($expensesQuery[$branchId]->net_expense ?? 0);
            $netProfit = $grossProfit - $operatingExpenses;
            $marginPct = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;
            // Staff count: users assigned to this branch
            $staffCount = \App\Models\User::whereHas('branches', function($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })->count();
            $profitPerStaff = $staffCount > 0 ? ($netProfit / $staffCount) : $netProfit;
            return (object)[
                'branch_id' => $branchId,
                'branch' => \App\Models\Branch::find($branchId),
                'total_revenue' => $totalRevenue,
                'cost_of_sales' => $costOfSales,
                'gross_profit' => $grossProfit,
                'operating_expenses' => $operatingExpenses,
                'net_profit' => $netProfit,
                'profit_margin_percentage' => $marginPct,
                'staff_count' => $staffCount,
                'profit_per_staff' => $profitPerStaff,
            ];
        })->sortByDesc('net_profit')->values();

        return view('sales.reports.branch-profitability', compact(
            'branchData', 'dateFrom', 'dateTo'
        ));
    }

    public function salesTrend(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->subMonths(12));
        $dateTo = $request->get('date_to', Carbon::now());
        $branchId = $request->get('branch_id', auth()->user()->branch_id);
        $period = $request->get('period', 'month'); // day, week, month

        $trendData = SalesInvoice::where('branch_id', $branchId)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->select([
                DB::raw('DATE(invoice_date) as period'),
                DB::raw('SUM(total_amount) as sales_amount'),
                DB::raw('COUNT(*) as invoice_count')
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Calculate moving averages
        $trendData = $trendData->map(function ($item, $index) use ($trendData) {
            $window = 3; // 3-period moving average
            $start = max(0, $index - $window + 1);
            $end = $index + 1;
            $periodData = $trendData->slice($start, $end);
            $item->moving_average = $periodData->avg('sales_amount');
            return $item;
        });

        $branches = $this->getUserAccessibleBranches();

        return view('sales.reports.sales-trend', compact(
            'trendData', 'branches', 'dateFrom', 'dateTo', 'branchId', 'period'
        ));
    }


    /**
     * Get user's assigned branches for Sales by Salesperson report
     */
    private function getUserAssignedBranches()
    {
        $user = auth()->user();
        $assignedBranches = $user->branches()->get();
        
        // If user has more than one assigned branch, add "All My Branches" option
        if ($assignedBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All My Branches',
                'company_id' => $user->company_id
            ];
            return $assignedBranches->prepend($allBranchesOption);
        }
        
        return $assignedBranches;
    }

    /**
     * Apply branch filter for assigned branches (for Sales by Salesperson)
     */
    private function applyAssignedBranchFilter($query, $branchId)
    {
        $user = auth()->user();
        $assignedBranchIds = $user->branches()->pluck('branches.id')->toArray();
        
        // Handle "all" branches selection - use all assigned branches
        if ($branchId === 'all') {
            if (!empty($assignedBranchIds)) {
                $query->whereIn('branch_id', $assignedBranchIds);
            } else {
                // If no assigned branches, return empty result
                $query->whereRaw('1 = 0');
            }
        } else {
            // Ensure the selected branch is in the user's assigned branches
            if (in_array($branchId, $assignedBranchIds)) {
                $query->where('branch_id', $branchId);
            } else {
                // If branch not assigned, return empty result
                $query->whereRaw('1 = 0');
            }
        }
        
        return $query;
    }

    /**
     * Get combined sales data from SalesInvoice, PosSale, and CashSale
     * Returns a collection with unified structure for reporting
     */
    private function getCombinedSalesData($dateFrom, $dateTo, $branchId, $applyBranchFilter = null)
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        
        // Determine branch filter function
        $branchFilter = $applyBranchFilter ?? [$this, 'applyBranchFilter'];
        
        // Get Sales Invoices
        $invoiceQuery = SalesInvoice::with(['items', 'customer'])
            ->where('company_id', $companyId)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $branchFilter($invoiceQuery, $branchId);
        $invoices = $invoiceQuery->get();
        
        // Get POS Sales
        $posQuery = PosSale::with(['items', 'customer'])
            ->where('company_id', $companyId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $branchFilter($posQuery, $branchId);
        $posSales = $posQuery->get();
        
        // Get Cash Sales
        $cashQuery = CashSale::with(['items', 'customer'])
            ->where('company_id', $companyId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);
        $branchFilter($cashQuery, $branchId);
        $cashSales = $cashQuery->get();
        
        // Transform to unified structure
        $combined = collect();
        
        // Add invoices
        foreach ($invoices as $invoice) {
            $combined->push((object)[
                'type' => 'invoice',
                'id' => $invoice->id,
                'date' => $invoice->invoice_date,
                'customer_id' => $invoice->customer_id,
                'customer' => $invoice->customer,
                'created_by' => $invoice->created_by,
                'branch_id' => $invoice->branch_id,
                'total_amount' => $invoice->total_amount,
                'subtotal' => $invoice->subtotal ?? 0,
                'vat_amount' => $invoice->vat_amount ?? 0,
                'discount_amount' => $invoice->discount_amount ?? 0,
                'paid_amount' => $invoice->paid_amount ?? 0,
                'balance_due' => $invoice->balance_due ?? 0,
                'items' => $invoice->items,
                'invoice_count' => 1,
            ]);
        }
        
        // Add POS sales
        foreach ($posSales as $pos) {
            $combined->push((object)[
                'type' => 'pos',
                'id' => $pos->id,
                'date' => $pos->sale_date,
                'customer_id' => $pos->customer_id,
                'customer' => $pos->customer,
                'created_by' => $pos->operator_id ?? $pos->created_by,
                'branch_id' => $pos->branch_id,
                'total_amount' => $pos->total_amount,
                'subtotal' => $pos->subtotal ?? 0,
                'vat_amount' => $pos->vat_amount ?? 0,
                'discount_amount' => $pos->discount_amount ?? 0,
                'paid_amount' => $pos->total_amount, // POS is always fully paid
                'balance_due' => 0, // POS has no outstanding
                'items' => $pos->items,
                'invoice_count' => 1,
            ]);
        }
        
        // Add Cash sales
        foreach ($cashSales as $cash) {
            $combined->push((object)[
                'type' => 'cash',
                'id' => $cash->id,
                'date' => $cash->sale_date,
                'customer_id' => $cash->customer_id,
                'customer' => $cash->customer,
                'created_by' => $cash->created_by,
                'branch_id' => $cash->branch_id,
                'total_amount' => $cash->total_amount,
                'subtotal' => $cash->subtotal ?? 0,
                'vat_amount' => $cash->vat_amount ?? 0,
                'discount_amount' => $cash->discount_amount ?? 0,
                'paid_amount' => $cash->total_amount, // Cash sales are always fully paid
                'balance_due' => 0, // Cash sales have no outstanding
                'items' => $cash->items,
                'invoice_count' => 1,
            ]);
        }
        
        return $combined;
    }

    /**
     * Calculate total cost for a collection of items (works for SalesInvoiceItem, PosSaleItem, CashSaleItem)
     */
    private function calculateItemsCost($items, $branchId = null, $locationId = null)
    {
        $totalCost = 0;
        foreach ($items as $item) {
            $costPrice = 0;
            if ($item->inventoryItem) {
                // Use resolved cost price (location → branch → default)
                $costPrice = (float)$item->inventoryItem->getCostPriceForBranchOrLocation($branchId, $locationId);
            }
            $totalCost += (float)$item->quantity * $costPrice;
        }
        return $totalCost;
    }

    /**
     * Get combined product sales data from SalesInvoiceItem, PosSaleItem, and CashSaleItem
     * Returns aggregated data grouped by inventory_item_id
     */
    private function getCombinedProductSales($dateFrom, $dateTo, $branchId, $applyBranchFilter = null)
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        $locationId = session('location_id');
        
        // Determine the branch ID for cost resolution
        $costBranchId = ($branchId === 'all') ? session('branch_id') : $branchId;
        
        // Determine branch filter function
        $branchFilter = $applyBranchFilter ?? [$this, 'applyBranchFilter'];
        
        // Get Sales Invoice Items with branch prices eager loaded
        $invoiceItemsQuery = SalesInvoiceItem::with(['inventoryItem.branchPrices', 'inventoryItem.locationPrices'])
            ->whereHas('salesInvoice', function ($query) use ($dateFrom, $dateTo, $branchId, $branchFilter, $companyId) {
                $query->where('company_id', $companyId)
                      ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                      ->where('status', '!=', 'cancelled');
                $branchFilter($query, $branchId);
            });
        
        // Get POS Sale Items with branch prices eager loaded
        $posItemsQuery = PosSaleItem::with(['inventoryItem.branchPrices', 'inventoryItem.locationPrices'])
            ->whereHas('posSale', function ($query) use ($dateFrom, $dateTo, $branchId, $branchFilter, $companyId) {
                $query->where('company_id', $companyId)
                      ->whereBetween('sale_date', [$dateFrom, $dateTo]);
                $branchFilter($query, $branchId);
            });
        
        // Get Cash Sale Items with branch prices eager loaded
        $cashItemsQuery = CashSaleItem::with(['inventoryItem.branchPrices', 'inventoryItem.locationPrices'])
            ->whereHas('cashSale', function ($query) use ($dateFrom, $dateTo, $branchId, $branchFilter, $companyId) {
                $query->where('company_id', $companyId)
                      ->whereBetween('sale_date', [$dateFrom, $dateTo]);
                $branchFilter($query, $branchId);
            });
        
        // Get all items
        $invoiceItems = $invoiceItemsQuery->get();
        $posItems = $posItemsQuery->get();
        $cashItems = $cashItemsQuery->get();
        
        // Combine and group by inventory_item_id
        $combined = collect();
        
        // Process invoice items
        foreach ($invoiceItems as $item) {
            $key = $item->inventory_item_id ?? 'unknown';
            if (!isset($combined[$key])) {
                $combined[$key] = (object)[
                    'inventory_item_id' => $item->inventory_item_id,
                    'inventoryItem' => $item->inventoryItem,
                    'total_quantity' => 0,
                    'total_revenue' => 0,
                    'total_cogs' => 0,
                ];
            }
            // Use resolved cost price (location → branch → default)
            $costPrice = $item->inventoryItem ? (float)$item->inventoryItem->getCostPriceForBranchOrLocation($costBranchId, $locationId) : 0;
            $combined[$key]->total_quantity += (float)$item->quantity;
            $combined[$key]->total_revenue += (float)$item->quantity * (float)$item->unit_price;
            $combined[$key]->total_cogs += (float)$item->quantity * $costPrice;
        }
        
        // Process POS items
        foreach ($posItems as $item) {
            $key = $item->inventory_item_id ?? 'unknown';
            if (!isset($combined[$key])) {
                $combined[$key] = (object)[
                    'inventory_item_id' => $item->inventory_item_id,
                    'inventoryItem' => $item->inventoryItem,
                    'total_quantity' => 0,
                    'total_revenue' => 0,
                    'total_cogs' => 0,
                ];
            }
            // Use resolved cost price (location → branch → default)
            $costPrice = $item->inventoryItem ? (float)$item->inventoryItem->getCostPriceForBranchOrLocation($costBranchId, $locationId) : 0;
            $combined[$key]->total_quantity += (float)$item->quantity;
            $combined[$key]->total_revenue += (float)$item->quantity * (float)$item->unit_price;
            $combined[$key]->total_cogs += (float)$item->quantity * $costPrice;
        }
        
        // Process Cash sale items
        foreach ($cashItems as $item) {
            $key = $item->inventory_item_id ?? 'unknown';
            if (!isset($combined[$key])) {
                $combined[$key] = (object)[
                    'inventory_item_id' => $item->inventory_item_id,
                    'inventoryItem' => $item->inventoryItem,
                    'total_quantity' => 0,
                    'total_revenue' => 0,
                    'total_cogs' => 0,
                ];
            }
            // Use resolved cost price (location → branch → default)
            $costPrice = $item->inventoryItem ? (float)$item->inventoryItem->getCostPriceForBranchOrLocation($costBranchId, $locationId) : 0;
            $combined[$key]->total_quantity += (float)$item->quantity;
            $combined[$key]->total_revenue += (float)$item->quantity * (float)$item->unit_price;
            $combined[$key]->total_cogs += (float)$item->quantity * $costPrice;
        }
        
        // Convert to collection and calculate averages
        return $combined->map(function ($item) {
            $item->avg_unit_price = $item->total_quantity > 0 ? ($item->total_revenue / $item->total_quantity) : 0;
            return $item;
        })->sortByDesc('total_revenue')->values();
    }

    public function salesBySalesperson(Request $request)
    {
        $this->authorize('view staff performance');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Get user's assigned branches
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        // Get combined sales data (invoices + POS + cash)
        $combinedSales = $this->getCombinedSalesData($dateFrom, $dateTo, $branchId, [$this, 'applyAssignedBranchFilter']);

        // Group by salesperson (created_by)
        $salespersonData = $combinedSales->groupBy('created_by')->map(function ($sales, $salespersonId) {
            $totalSales = $sales->sum('total_amount');
            $amountCollected = $sales->sum('paid_amount');
            $outstandingAmount = $sales->sum('balance_due'); // Will be 0 for POS/cash
            $invoiceCount = $sales->count();
            $customersServed = $sales->pluck('customer_id')->filter()->unique()->count();
            
            return (object)[
                'created_by' => $salespersonId,
                'createdBy' => $salespersonId ? \App\Models\User::find($salespersonId) : null,
                'total_sales' => $totalSales,
                'amount_collected' => $amountCollected,
                'outstanding_amount' => $outstandingAmount,
                'invoice_count' => $invoiceCount,
                'customers_served' => $customersServed,
            ];
        })->sortByDesc('total_sales')->values();

        $totalSales = $salespersonData->sum('total_sales');
        $totalCollected = $salespersonData->sum('amount_collected');
        $totalOutstanding = $salespersonData->sum('outstanding_amount');
        
        $salespersonData = $salespersonData->map(function ($salesperson) use ($totalSales) {
            $salesperson->contribution_percentage = $totalSales > 0 
                ? ($salesperson->total_sales / $totalSales) * 100 
                : 0;
            $salesperson->collection_percentage = $salesperson->total_sales > 0 
                ? (($salesperson->amount_collected ?? 0) / $salesperson->total_sales) * 100 
                : 0;
            // Ensure amounts are numeric
            $salesperson->amount_collected = (float)($salesperson->amount_collected ?? 0);
            $salesperson->outstanding_amount = (float)($salesperson->outstanding_amount ?? 0);
            return $salesperson;
        });

        $branches = $this->getUserAssignedBranches();

        return view('sales.reports.sales-by-salesperson', compact(
            'salespersonData', 'branches', 'dateFrom', 'dateTo', 'branchId', 
            'totalSales', 'totalCollected', 'totalOutstanding'
        ));
    }

    public function discountEffectiveness(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Smart branch selection logic
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Get detailed discount data for the table
        $discountDataQuery = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->where('discount_amount', '>', 0);
        
        $this->applyBranchFilter($discountDataQuery, $branchId);
        
        $discountData = $discountDataQuery->select([
                'id',
                'invoice_number',
                'customer_id',
                'invoice_date',
                'total_amount',
                'discount_amount'
            ])
            ->get()
            ->map(function ($invoice) {
                $invoice->gross_sales = $invoice->total_amount + $invoice->discount_amount;
                $invoice->net_sales = $invoice->total_amount;
                $invoice->discount_percentage = $invoice->gross_sales > 0 
                    ? ($invoice->discount_amount / $invoice->gross_sales) * 100 
                    : 0;
                return $invoice;
            });

        // Calculate summary statistics
        $totalSales = $discountData->sum('gross_sales');
        $totalDiscounts = $discountData->sum('discount_amount');
        $discountPercentage = $totalSales > 0 ? ($totalDiscounts / $totalSales) * 100 : 0;
        $invoicesWithDiscounts = $discountData->count();
        
        $totalInvoicesQuery = SalesInvoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($totalInvoicesQuery, $branchId);
        $totalInvoices = $totalInvoicesQuery->count();
        
        $averageDiscountPerInvoice = $invoicesWithDiscounts > 0 ? $totalDiscounts / $invoicesWithDiscounts : 0;
        $averageDiscountRate = $invoicesWithDiscounts > 0 ? $discountData->avg('discount_percentage') : 0;

        // Smart branch dropdown logic
        if ($userBranches->count() > 1) {
            // If user has multiple branches, show only assigned branch + all branches option
            $assignedBranch = null;
            if ($sessionBranchId) {
                $assignedBranch = $userBranches->where('id', $sessionBranchId)->first();
            }
            
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => auth()->user()->company_id
            ];
            
            if ($assignedBranch) {
                // Show only assigned branch + all branches option
                $branches = collect([$assignedBranch])->prepend($allBranchesOption);
            } else {
                // Fallback to showing all branches with "All Branches" first
                $branches = $userBranches->prepend($allBranchesOption);
            }
        } else {
            // Single branch - just show that branch
            $branches = $userBranches;
        }

        return view('sales.reports.discount-effectiveness', compact(
            'discountData', 'branches', 'dateFrom', 'dateTo', 'branchId',
            'totalSales', 'totalDiscounts', 'discountPercentage', 'invoicesWithDiscounts',
            'totalInvoices', 'averageDiscountPerInvoice', 'averageDiscountRate'
        ));
    }

    public function salesReturn(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        $branchId = $request->get('branch_id', auth()->user()->branch_id);
        $reason = $request->get('reason');

        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        // Build query for return data
        $query = CreditNote::with(['customer', 'items.inventoryItem'])
            ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        
        // Apply branch filtering
        // Apply assigned branch filtering
        $this->applyAssignedBranchFilter($query, $branchId);

        // Apply reason filter if provided
        if ($reason) {
            $query->where('reason_code', $reason);
        }

        $returnData = $query->get();

        // Transform return data for display
        $returnData = $returnData->map(function ($creditNote) {
            $creditNote->return_date = $creditNote->credit_note_date;
            $creditNote->original_invoice_id = $creditNote->sales_invoice_id;
            $creditNote->original_invoice_number = $creditNote->sales_invoice_number ?? 'N/A';
            $creditNote->customer_name = $creditNote->customer->name ?? 'Unknown Customer';
            
            // Get first item for display (in real system, you might want to show all items)
            $firstItem = $creditNote->items->first();
            if ($firstItem) {
                $creditNote->item_name = $firstItem->item_name ?? 'Unknown Item';
                $creditNote->item_code = $firstItem->item_code ?? 'N/A';
                $creditNote->quantity = $firstItem->quantity;
                $creditNote->unit_price = $firstItem->unit_price;
                $creditNote->return_value = $firstItem->line_total;
            } else {
                $creditNote->item_name = 'No Items';
                $creditNote->item_code = 'N/A';
                $creditNote->quantity = 0;
                $creditNote->unit_price = 0;
                $creditNote->return_value = 0;
            }
            
            $creditNote->reason = $creditNote->reason_code ?? 'unknown';
            $creditNote->status = $creditNote->status ?? 'processed';
            
            return $creditNote;
        });

        // Calculate summary statistics
        $totalReturnValue = $returnData->sum('return_value');
        $totalReturns = $returnData->count();
        
        // Get gross sales for comparison
        $grossSalesQuery = SalesInvoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($grossSalesQuery, $branchId);
        $grossSales = $grossSalesQuery->sum('total_amount');

        $returnRate = $grossSales > 0 ? ($totalReturnValue / $grossSales) * 100 : 0;
        $netSales = $grossSales - $totalReturnValue;

        // Get returns by reason
        $returnsByReason = $returnData->groupBy('reason')->map(function ($group, $reason) use ($totalReturns) {
            return (object) [
                'reason' => $reason,
                'count' => $group->count(),
                'percentage' => $totalReturns > 0 ? ($group->count() / $totalReturns) * 100 : 0
            ];
        })->values();

        // Get top return reason
        $topReturnReason = $returnsByReason->sortByDesc('count')->first();

        $branches = $this->getUserAccessibleBranches();

        return view('sales.reports.sales-return', compact(
            'returnData', 'totalReturnValue', 'totalReturns', 'grossSales', 
            'returnRate', 'netSales', 'returnsByReason', 'topReturnReason',
            'branches', 'dateFrom', 'dateTo', 'branchId', 'reason'
        ));
    }

    public function profitabilityByProduct(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Get user's assigned branches
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        // Get combined product sales (from invoices + POS + cash)
        $profitabilityData = $this->getCombinedProductSales($dateFrom, $dateTo, $branchId, [$this, 'applyAssignedBranchFilter']);
        
        // Calculate additional metrics
        $profitabilityData = $profitabilityData->map(function ($item) {
            $item->avg_selling_price = $item->avg_unit_price;
            $item->avg_cost_price = $item->total_quantity > 0 ? ($item->total_cogs / $item->total_quantity) : 0;
            return $item;
        });

        $profitabilityData = $profitabilityData->map(function ($item) {
            $item->gross_profit = $item->total_revenue - $item->total_cogs;
            $item->margin_percentage = $item->total_revenue > 0 
                ? ($item->gross_profit / $item->total_revenue) * 100 
                : 0;
            $item->profit_per_unit = $item->total_quantity > 0 
                ? $item->gross_profit / $item->total_quantity 
                : 0;
            return $item;
        });

        // Calculate summary statistics
        $totalRevenue = $profitabilityData->sum('total_revenue');
        $totalCogs = $profitabilityData->sum('total_cogs');
        $totalGrossProfit = $profitabilityData->sum('gross_profit');
        $averageMarginPercentage = $totalRevenue > 0 ? ($totalGrossProfit / $totalRevenue) * 100 : 0;

        // Categorize products by margin
        $highMarginProducts = $profitabilityData->where('margin_percentage', '>=', 30)->count();
        $mediumMarginProducts = $profitabilityData->whereBetween('margin_percentage', [15, 30])->count();
        $lowMarginProducts = $profitabilityData->where('margin_percentage', '<', 15)->count();

        // Get top performers
        $topProduct = $profitabilityData->sortByDesc('margin_percentage')->first();
        $revenueLeader = $profitabilityData->sortByDesc('total_revenue')->first();

        $branches = $this->getUserAssignedBranches();

        return view('sales.reports.profitability-by-product', compact(
            'profitabilityData', 'branches', 'dateFrom', 'dateTo', 'branchId',
            'totalRevenue', 'totalCogs', 'totalGrossProfit', 'averageMarginPercentage',
            'highMarginProducts', 'mediumMarginProducts', 'lowMarginProducts',
            'topProduct', 'revenueLeader'
        ));
    }

    public function receivablesAging(Request $request)
    {
        $this->authorize('view collection report');
        
        $branchId = $request->get('branch_id', auth()->user()->branch_id);
        $customerId = $request->get('customer_id');
        $asOfDate = $request->get('as_of_date', Carbon::now()->format('Y-m-d'));
        $viewType = $request->get('view_type', 'summary'); // summary | detailed | trend
        $bucket = $request->get('bucket', '0-30'); // 0-30 | 31-60 | 61-90 | 90+
        if (!in_array($bucket, ['0-30','31-60','61-90','90+'])) {
            $bucket = '0-30';
        }

        $query = SalesInvoice::with(['customer'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $asOfDate);
        
        $this->applyBranchFilter($query, $branchId);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $outstandingInvoices = $query->get()->map(function ($invoice) use ($asOfDate) {
            $asOf = Carbon::parse($asOfDate);
            $invoiceDate = Carbon::parse($invoice->invoice_date);
            // Determine due date: prefer stored due_date, else payment_days, else +30
            $dueDate = !empty($invoice->due_date)
                ? Carbon::parse($invoice->due_date)
                : $invoiceDate->copy()->addDays($invoice->payment_days ?? 30);
            // Days overdue (0 if not yet due)
            $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;
            // Outstanding amount, prefer balance_due
            $outstandingAmount = $invoice->balance_due ?? (($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0));
            $invoice->due_date = $dueDate;
            $invoice->days_overdue = $daysOverdue;
            $invoice->aging_bucket = $this->getAgingBucket($daysOverdue);
            $invoice->outstanding_amount = (float)$outstandingAmount;
            return $invoice;
        })->filter(function ($inv) {
            return (($inv->outstanding_amount ?? 0) > 0);
        });

        // Executive summary buckets
        $buckets = ['0-30','31-60','61-90','90+'];
        $agingSummary = collect($buckets)->mapWithKeys(function($b) use ($outstandingInvoices) {
            // Match detailed logic: include ONLY overdue items (days_overdue > 0)
            $filtered = $outstandingInvoices
                ->where('aging_bucket', $b)
                ->filter(function($inv){ return ($inv->days_overdue ?? 0) > 0; });
            return [$b => [
                'count' => $filtered->count(),
                'total_amount' => (float)$filtered->sum('outstanding_amount'),
                'invoices' => $filtered,
            ]];
        });
        // Totals that reflect the current filtered summary (overdue-only)
        $summaryTotalAmount = (float)collect($agingSummary)->sum('total_amount');
        $summaryTotalCount = (int)collect($agingSummary)->sum('count');
        // Overall outstanding (including current, not just overdue) if needed elsewhere
        $totalOutstanding = (float)$outstandingInvoices->sum('outstanding_amount');

        // Detailed: build all buckets grouped by customer, ONLY overdue (days_overdue > 0)
        $bucketLabels = ['0-30' => '0 – 30 Days', '31-60' => '31 – 60 Days', '61-90' => '61 – 90 Days', '90+' => 'Over 90 Days'];
        $detailedAllBuckets = collect($bucketLabels)->map(function($label, $key) use ($outstandingInvoices) {
            $bucketInvoices = $outstandingInvoices->where('aging_bucket', $key)
                ->filter(function($inv){ return ($inv->days_overdue ?? 0) > 0; });
            $groups = $bucketInvoices
                ->groupBy(function($inv){ return $inv->customer->name ?? 'Unknown'; })
                ->map(function($group, $customerName) {
                    $subtotal = (float)$group->sum('outstanding_amount');
                    return [
                        'customer_name' => $customerName,
                        'invoices' => $group->map(function($inv){
                            return [
                                'customer_name' => $inv->customer->name ?? 'Unknown',
                                'invoice_number' => $inv->invoice_number,
                                'invoice_date' => $inv->invoice_date,
                                'due_date' => $inv->due_date,
                                'outstanding_amount' => (float)$inv->outstanding_amount,
                                'days_overdue' => $inv->days_overdue,
                                'status' => $inv->status,
                            ];
                        }),
                        'subtotal' => $subtotal,
                    ];
                })->values();
            $bucketTotal = (float)$bucketInvoices->sum('outstanding_amount');
            return [
                'label' => $label,
                'key' => $key,
                'groups' => $groups,
                'bucket_total' => $bucketTotal,
            ];
        })->values();

        // Trend: current vs previous month
        $currentMonthStart = Carbon::parse($asOfDate)->copy()->startOfMonth();
        $prevMonthEnd = $currentMonthStart->copy()->subDay();
        $prevMonthStart = $prevMonthEnd->copy()->startOfMonth();

        $prevInvoices = SalesInvoice::with(['customer'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $prevMonthEnd);
        $this->applyAssignedBranchFilter($prevInvoices, $branchId);
        if ($customerId) { $prevInvoices->where('customer_id', $customerId); }
        $prevAging = $prevInvoices->get()->map(function ($inv) use ($prevMonthEnd) {
            $asOf = Carbon::parse($prevMonthEnd);
            $invoiceDate = Carbon::parse($inv->invoice_date);
            $dueDate = !empty($inv->due_date)
                ? Carbon::parse($inv->due_date)
                : $invoiceDate->copy()->addDays($inv->payment_days ?? 30);
            $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;
            $outstandingAmount = ($inv->balance_due ?? 0) ?: (($inv->total_amount ?? 0) - ($inv->paid_amount ?? 0));
            return [
                'aging_bucket' => $this->getAgingBucket($daysOverdue),
                'outstanding_amount' => $outstandingAmount,
                'days_overdue' => $daysOverdue,
            ];
        })->filter(function ($i) { return (($i['outstanding_amount'] ?? 0) > 0) && (($i['days_overdue'] ?? 0) > 0); });

        // Use overdue-only for current buckets, to match summary/detailed
        $overdueOnly = $outstandingInvoices->filter(function($inv){ return ($inv->days_overdue ?? 0) > 0; });
        $currentByBucket = collect($buckets)->mapWithKeys(function($b) use ($overdueOnly) {
            return [$b => (float)$overdueOnly->where('aging_bucket', $b)->sum('outstanding_amount')];
        });
        $prevByBucket = collect($buckets)->mapWithKeys(function($b) use ($prevAging) {
            return [$b => (float)$prevAging->where('aging_bucket', $b)->sum('outstanding_amount')];
        });
        $trend = collect($buckets)->map(function($b) use ($currentByBucket, $prevByBucket) {
            $current = $currentByBucket[$b] ?? 0;
            $prev = $prevByBucket[$b] ?? 0;
            $pctChange = $prev > 0 ? (($current - $prev) / $prev) * 100 : ($current > 0 ? 100 : 0);
            return [
                'bucket' => $b,
                'current' => $current,
                'previous' => $prev,
                'pct_change' => $pctChange,
            ];
        });

        // Customers: if 'all', show only those in user's assigned branches; otherwise specific branch
        $assignedBranchIds = auth()->user()->branches()->pluck('branches.id')->toArray();
        $customers = Customer::where('company_id', auth()->user()->company_id)
            ->when($branchId !== 'all', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            }, function($q) use ($assignedBranchIds) {
                if (!empty($assignedBranchIds)) {
                    $q->whereIn('branch_id', $assignedBranchIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->orderBy('name')->get();
        // Branches list: assigned branches with 'All My Branches' when >1
        $branches = $this->getUserAssignedBranches();

        return view('sales.reports.receivables-aging', compact(
            'agingSummary', 'customers', 'branches', 'customerId', 'branchId', 'asOfDate',
            'totalOutstanding', 'summaryTotalAmount', 'summaryTotalCount',
            'detailedAllBuckets', 'trend', 'viewType'
        ));
    }

    public function collectionEfficiency(Request $request)
    {
        $this->authorize('view collection report');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        $branchId = $request->get('branch_id', auth()->user()->branch_id);

        // Get all invoices for collection analysis
        $collectionData = SalesInvoice::with(['customer'])
            ->where('branch_id', $branchId)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->get()
            ->map(function ($invoice) {
                $invoice->days_outstanding = Carbon::now()->diffInDays($invoice->due_date);
                return $invoice;
            });

        // Calculate summary statistics
        $totalCreditSales = $collectionData->sum('total_amount');
        $totalCollected = $collectionData->sum('paid_amount');
        $totalOutstanding = $collectionData->sum('balance_due');
        
        // Calculate DSO (Days Sales Outstanding)
        $accountsReceivable = $totalOutstanding;
        $daysInPeriod = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        $dso = $totalCreditSales > 0 ? ($accountsReceivable / $totalCreditSales) * $daysInPeriod : 0;

        // Calculate collection efficiency
        $totalInvoices = $collectionData->count();
        $paidInvoicesCount = $collectionData->where('status', 'paid')->count();
        $collectionRate = $totalCreditSales > 0 ? ($totalCollected / $totalCreditSales) * 100 : 0;

        // Calculate collection performance metrics
        $onTimeCollections = $collectionData->where('days_outstanding', '<=', 30)->count();
        $overdueCollections = $collectionData->where('days_outstanding', '>', 30)->count();
        $onTimePercentage = $totalInvoices > 0 ? ($onTimeCollections / $totalInvoices) * 100 : 0;
        $overduePercentage = $totalInvoices > 0 ? ($overdueCollections / $totalInvoices) * 100 : 0;
        
        // Calculate average collection period
        $paidInvoices = $collectionData->where('status', 'paid');
        $averageCollectionPeriod = $paidInvoices->count() > 0 
            ? $paidInvoices->avg('days_outstanding') 
            : 0;

        $branches = $this->getUserAccessibleBranches();

        return view('sales.reports.collection-efficiency', compact(
            'collectionData', 'dso', 'collectionRate', 'totalCreditSales', 'totalCollected', 'totalOutstanding',
            'accountsReceivable', 'paidInvoicesCount', 'totalInvoices', 'onTimeCollections', 'overdueCollections',
            'onTimePercentage', 'overduePercentage', 'averageCollectionPeriod',
            'branches', 'dateFrom', 'dateTo', 'branchId'
        ));
    }

    public function invoiceRegister(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);
        $status = $request->get('status');

        $query = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo]);

        // Apply branch filtering
        $this->applyBranchFilter($query, $branchId);

        if ($status) {
            $query->where('status', $status);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $summary = [
            'total_invoices' => $invoices->count(),
            'total_value' => $invoices->sum('total_amount'),
            'paid_value' => $invoices->where('status', 'paid')->sum('total_amount'),
            'outstanding_value' => $invoices->where('status', '!=', 'paid')->sum('total_amount')
        ];

        // For Invoice Register Report, prioritize assigned branch
        if ($userBranches->count() > 1) {
            // If user has multiple branches, show only assigned branch + all branches option
            $assignedBranch = null;
            if ($sessionBranchId) {
                $assignedBranch = $userBranches->where('id', $sessionBranchId)->first();
            }
            
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => auth()->user()->company_id
            ];
            
            if ($assignedBranch) {
                // Show only assigned branch + all branches option
                $branches = collect([$assignedBranch])->prepend($allBranchesOption);
            } else {
                // Fallback to showing all branches with "All Branches" first
                $branches = $userBranches->prepend($allBranchesOption);
            }
        } else {
            // Single branch - just show that branch
            $branches = $userBranches;
        }

        return view('sales.reports.invoice-register', compact(
            'invoices', 'summary', 'branches', 'dateFrom', 'dateTo', 'branchId', 'status'
        ));
    }

    public function customerStatement(Request $request)
    {
        $this->authorize('view customer reports');
        
        // Default dates to current month if not provided
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $customerId = $request->get('customer_id');
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Initialize variables
        $customer = null;
        $openingBalance = 0;
        $closingBalance = 0;
        $totalInvoices = 0;
        $totalPayments = 0;
        $transactions = collect();
        $errorMessage = null;
        
        // Get branches for filter
        if ($userBranches->count() > 1) {
            $assignedBranch = null;
            if ($sessionBranchId) {
                $assignedBranch = $userBranches->where('id', $sessionBranchId)->first();
            }
            
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => auth()->user()->company_id
            ];
            
            if ($assignedBranch) {
                $branches = collect([$assignedBranch])->prepend($allBranchesOption);
            } else {
                $branches = $userBranches->prepend($allBranchesOption);
            }
        } else {
            $branches = $userBranches;
        }
        
        // Get customers for the selected branch
        $customers = Customer::where('company_id', auth()->user()->company_id)
            ->when($branchId && $branchId !== 'all', function($q) use ($branchId) { 
                $q->where('branch_id', $branchId); 
            })
            ->orderBy('name')->get();

        // Only process report data if customer is selected and dates are valid
        if ($customerId && $dateFrom && $dateTo) {
            try {
                $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
                $dateToCarbon = Carbon::parse($dateTo)->endOfDay();
                
                if ($dateFromCarbon->gt($dateToCarbon)) {
                    $errorMessage = 'Start date cannot be later than end date. Please select a valid date range.';
                } else {
                    $customer = Customer::find($customerId);
                    
                    if ($customer) {
                        $openingBalance = $this->customerStatementOpeningBalance((int) $customerId, $dateFromCarbon, $branchId);
                        $core = $this->buildCustomerStatementCore((int) $customerId, $dateFromCarbon, $dateToCarbon, $branchId);
                        $invoices = $core['invoices'];
                        $creditNotes = $core['creditNotes'];
                        $transactions = $core['transactions'];

                        $totalInvoices = $invoices->sum('total_amount');
                        $totalPayments = $transactions->where('type', 'payment')->sum('amount');
                        $totalCreditNotes = $creditNotes->sum('total_amount');
                        $closingBalance = $openingBalance + $totalInvoices - $totalCreditNotes - $totalPayments;
                    } else {
                        $errorMessage = 'Customer not found.';
                    }
                }
            } catch (\Exception $e) {
                $errorMessage = 'Invalid date format. Please select valid dates.';
            }
        }

        $totalCreditNotes = isset($totalCreditNotes) ? $totalCreditNotes : 0;
        
        return view('sales.reports.customer-statement', compact(
            'customer', 'openingBalance', 'closingBalance', 'totalInvoices', 'totalPayments', 'totalCreditNotes',
            'transactions', 'customers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'customerId', 'errorMessage'
        ));
    }

    public function paidInvoice(Request $request)
    {
        $this->authorize('view sales reports');
        
        // Default to today's date
        $dateFrom = $request->get('date_from', Carbon::today()->startOfDay());
        $dateTo = $request->get('date_to', Carbon::today()->endOfDay());
        if (is_string($dateFrom)) { $dateFrom = Carbon::parse($dateFrom)->startOfDay(); }
        if (is_string($dateTo)) { $dateTo = Carbon::parse($dateTo)->endOfDay(); }
        // Assigned branches with 'all' option
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? auth()->user()->branch_id);
        $branchId = $request->get('branch_id', $defaultBranchId);
        $customerId = $request->get('customer_id');

        $query = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('paid_amount', '>', 0);
        // Apply assigned branch filter
        $this->applyAssignedBranchFilter($query, $branchId);
        // Customer filter by branch
        if (!empty($customerId)) {
            $query->where('customer_id', $customerId);
        }
        $paidInvoices = $query->orderBy('invoice_date', 'desc')->get();

        $summary = [
            'total_paid_invoices' => $paidInvoices->count(),
            'total_paid_value' => $paidInvoices->sum('paid_amount'),
            'total_invoice_value' => $paidInvoices->sum('total_amount'),
            'fully_paid_count' => $paidInvoices->where('status', 'paid')->count(),
            'partially_paid_count' => $paidInvoices->where('status', '!=', 'paid')->where('paid_amount', '>', 0)->count()
        ];

        // Branches and customers (by assigned branches or selected branch)
        $branches = $this->getUserAssignedBranches();
        $assignedBranchIds = auth()->user()->branches()->pluck('branches.id')->toArray();
        $customers = \App\Models\Customer::where('company_id', auth()->user()->company_id)
            ->when($branchId !== 'all', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            }, function($q) use ($assignedBranchIds) {
                if (!empty($assignedBranchIds)) {
                    $q->whereIn('branch_id', $assignedBranchIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->orderBy('name')->get();

        return view('sales.reports.paid-invoice', compact(
            'paidInvoices', 'summary', 'branches', 'customers', 'dateFrom', 'dateTo', 'branchId', 'customerId'
        ));
    }

    public function creditNote(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        // Parse dates if provided as strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }
        // Branch handling: assigned branches with 'all' option
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? auth()->user()->branch_id);
        $branchId = $request->get('branch_id', $defaultBranchId);
        $customerId = $request->get('customer_id');
        $reason = $request->get('reason');

        $query = CreditNote::with(['customer'])
            ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        // Apply assigned branch filter (supports 'all')
        $this->applyAssignedBranchFilter($query, $branchId);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($reason) {
            $query->where('reason_code', $reason);
        }

        $creditNotes = $query->orderBy('credit_note_date', 'desc')->get();

        // Calculate return rate based on total sales in the period
        $totalSalesQuery = SalesInvoice::query()
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyAssignedBranchFilter($totalSalesQuery, $branchId);
        $totalSales = $totalSalesQuery->sum('total_amount');

        $returnRate = $totalSales > 0 ? ($creditNotes->sum('total_amount') / $totalSales) * 100 : 0;

        // Get top reason
        $topReason = $creditNotes->groupBy('reason_code')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        $summary = [
            'total_credit_notes' => $creditNotes->count(),
            'total_credit_value' => $creditNotes->sum('total_amount'),
            'return_rate' => $returnRate,
            'top_reason' => $topReason
        ];

        // Customers within assigned branches or specific branch
        $assignedBranchIds = auth()->user()->branches()->pluck('branches.id')->toArray();
        $customers = Customer::where('company_id', auth()->user()->company_id)
            ->when($branchId !== 'all', function($q) use ($branchId) { 
                $q->where('branch_id', $branchId); 
            }, function($q) use ($assignedBranchIds) {
                if (!empty($assignedBranchIds)) {
                    $q->whereIn('branch_id', $assignedBranchIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->orderBy('name')->get();
        $branches = $this->getUserAssignedBranches();

        return view('sales.reports.credit-note', compact(
            'creditNotes', 'summary', 'customers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'customerId', 'reason'
        ));
    }

    public function taxInvoice(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        $branchId = $request->get('branch_id', auth()->user()->branch_id);
        $taxType = $request->get('tax_type', 'both'); // Default to 'both' if not specified

        $query = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        
        $this->applyBranchFilter($query, $branchId);

        if ($taxType !== null && $taxType !== '') {
            if ($taxType === 'vat') {
                // Show only invoices with VAT (no WHT)
                $query->where('vat_amount', '>', 0)
                      ->where(function($q) {
                          $q->where('withholding_tax_amount', 0)
                            ->orWhereNull('withholding_tax_amount');
                      });
            } elseif ($taxType === 'wht') {
                // Show only invoices with WHT (no VAT)
                $query->where('withholding_tax_amount', '>', 0)
                      ->where(function($q) {
                          $q->where('vat_amount', 0)
                            ->orWhereNull('vat_amount');
                      });
            } elseif ($taxType === 'both') {
                // Show only invoices with both VAT and WHT
                $query->where('vat_amount', '>', 0)
                      ->where('withholding_tax_amount', '>', 0);
            } elseif ($taxType === 'none') {
                // Show only invoices with no tax
                $query->where(function($q) {
                    $q->where('vat_amount', 0)
                      ->orWhereNull('vat_amount');
                })->where(function($q) {
                    $q->where('withholding_tax_amount', 0)
                      ->orWhereNull('withholding_tax_amount');
                });
            }
        }

        $taxInvoices = $query->orderBy('invoice_date', 'desc')->get();

        $summary = [
            'total_invoices' => $taxInvoices->count(),
            'total_taxable_amount' => $taxInvoices->sum('subtotal'),
            'total_vat_amount' => $taxInvoices->sum('vat_amount'),
            'total_wht_amount' => $taxInvoices->sum('withholding_tax_amount'),
            'total_tax_amount' => $taxInvoices->sum('vat_amount') + $taxInvoices->sum('withholding_tax_amount'),
            'total_invoice_amount' => $taxInvoices->sum('total_amount')
        ];

        $branches = $this->getUserAccessibleBranches();

        return view('sales.reports.tax-invoice', compact(
            'taxInvoices', 'summary', 'branches', 'dateFrom', 'dateTo', 'branchId', 'taxType'
        ));
    }

    /**
     * Export Sales Summary Report to PDF
     */
    public function exportSalesSummaryPdf(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from'))->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to'))->endOfDay() : Carbon::now()->endOfMonth();
        $customerId = $request->get('customer_id');
        
        // Get user's assigned branches
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
        $branchId = $request->get('branch_id', $defaultBranchId);
        $groupBy = $request->get('group_by', 'day');

        // Get combined sales data (invoices + POS + cash)
        $combinedSales = $this->getCombinedSalesData($dateFrom, $dateTo, $branchId, [$this, 'applyAssignedBranchFilter']);

        // Filter by customer if specified
        if ($customerId) {
            $combinedSales = $combinedSales->filter(function ($sale) use ($customerId) {
                return $sale->customer_id == $customerId;
            });
        }

        $groupedData = $combinedSales->groupBy(function ($sale) use ($groupBy) {
            $date = \Carbon\Carbon::parse($sale->date);
            return match ($groupBy) {
                'week' => $date->format('o-\WW'),
                'month' => $date->format('Y-m'),
                'year' => $date->format('Y'),
                default => $date->format('Y-m-d'),
            };
        });
        $summaryData = $groupedData->map(function ($group, $key) use ($groupBy) {
            $totalQuantity = $group->sum(function ($sale) {
                return $sale->items->sum('quantity');
            });
            $invoiceCount = $group->count();
            $grossSales = $group->sum(function ($sale) {
                return (float)($sale->subtotal ?? 0) + (float)($sale->vat_amount ?? 0);
            });
            $totalDiscounts = $group->sum(function ($sale) {
                return (float)($sale->discount_amount ?? 0);
            });
            $netSales = $grossSales - $totalDiscounts;
            $anyDate = \Carbon\Carbon::parse($group->first()->date);
            $periodLabel = match ($groupBy) {
                'week' => $anyDate->isoFormat('GGGG-[W]WW'),
                'month' => $anyDate->format('M Y'),
                'year' => $anyDate->format('Y'),
                default => $anyDate->format('d M Y'),
            };
            $uniqueDays = $group->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))->unique()->count();
            $averageDailySales = $uniqueDays > 0 ? ($netSales / $uniqueDays) : 0;
            return [
                'key' => $key,
                'period_label' => $periodLabel,
                'invoice_count' => $invoiceCount,
                'total_quantity' => (float)$totalQuantity,
                'total_sales' => (float)$grossSales,
                'total_discounts' => (float)$totalDiscounts,
                'net_sales' => (float)$netSales,
                'average_daily_sales' => (float)$averageDailySales,
            ];
        })->sortKeys();
        $prev = null;
        $summaryData = $summaryData->values()->map(function ($row) use (&$prev) {
            $growth = null;
            if (!is_null($prev) && $prev != 0) {
                $growth = (($row['net_sales'] - $prev) / $prev) * 100.0;
            }
            $prev = $row['net_sales'];
            $row['growth_vs_prev'] = $growth;
            return $row;
        });

        $branch = ($branchId && $branchId !== 'all') ? Branch::find($branchId) : null;
        $customer = $customerId ? Customer::find($customerId) : null;
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.sales-summary-pdf', compact(
            'summaryData', 'dateFrom', 'dateTo', 'branch', 'customer', 'groupBy', 'company', 'branchId'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('sales-summary-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Sales Summary Report to Excel
     */
    public function exportSalesSummaryExcel(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from'))->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to'))->endOfDay() : Carbon::now()->endOfMonth();
        $customerId = $request->get('customer_id');
        
        // Get user's assigned branches
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
        $branchId = $request->get('branch_id', $defaultBranchId);
        $groupBy = $request->get('group_by', 'day');

        // Get combined sales data (invoices + POS + cash)
        $combinedSales = $this->getCombinedSalesData($dateFrom, $dateTo, $branchId, [$this, 'applyAssignedBranchFilter']);

        // Filter by customer if specified
        if ($customerId) {
            $combinedSales = $combinedSales->filter(function ($sale) use ($customerId) {
                return $sale->customer_id == $customerId;
            });
        }

        $groupedData = $combinedSales->groupBy(function ($sale) use ($groupBy) {
            $date = \Carbon\Carbon::parse($sale->date);
            return match ($groupBy) {
                'week' => $date->format('o-\WW'),
                'month' => $date->format('Y-m'),
                'year' => $date->format('Y'),
                default => $date->format('Y-m-d'),
            };
        });
        $summaryData = $groupedData->map(function ($group, $key) use ($groupBy) {
            $totalQuantity = $group->sum(function ($sale) {
                return $sale->items->sum('quantity');
            });
            $invoiceCount = $group->count();
            $grossSales = $group->sum(function ($sale) {
                return (float)($sale->subtotal ?? 0) + (float)($sale->vat_amount ?? 0);
            });
            $totalDiscounts = $group->sum(function ($sale) {
                return (float)($sale->discount_amount ?? 0);
            });
            $netSales = $grossSales - $totalDiscounts;
            $anyDate = \Carbon\Carbon::parse($group->first()->date);
            $periodLabel = match ($groupBy) {
                'week' => $anyDate->isoFormat('GGGG-[W]WW'),
                'month' => $anyDate->format('M Y'),
                'year' => $anyDate->format('Y'),
                default => $anyDate->format('d M Y'),
            };
            $uniqueDays = $group->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))->unique()->count();
            $averageDailySales = $uniqueDays > 0 ? ($netSales / $uniqueDays) : 0;
            return [
                'period_label' => $periodLabel,
                'invoice_count' => $invoiceCount,
                'total_quantity' => (float)$totalQuantity,
                'total_sales' => (float)$grossSales,
                'total_discounts' => (float)$totalDiscounts,
                'net_sales' => (float)$netSales,
                'average_daily_sales' => (float)$averageDailySales,
            ];
        })->sortKeys();
        $prev = null;
        $summaryData = $summaryData->values()->map(function ($row) use (&$prev) {
            $growth = null;
            if (!is_null($prev) && $prev != 0) {
                $growth = (($row['net_sales'] - $prev) / $prev) * 100.0;
            }
            $prev = $row['net_sales'];
            $row['growth_vs_prev'] = $growth;
            return $row;
        });

        $branch = ($branchId && $branchId !== 'all') ? Branch::find($branchId) : null;
        $customer = $customerId ? Customer::find($customerId) : null;
        $company = current_company();

        return Excel::download(new \App\Exports\SalesSummaryExport($summaryData, $dateFrom, $dateTo, $branch, $customer, $groupBy, $company), 
            'sales-summary-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Sales by Product Report to PDF
     */
    public function exportSalesByProductPdf(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from'))->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to'))->endOfDay() : Carbon::now()->endOfMonth();
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Get combined product sales (from invoices + POS + cash) - same as web view
        $productSales = $this->getCombinedProductSales($dateFrom, $dateTo, $branchId, [$this, 'applyBranchFilter']);

        // Compute derived metrics - same as web view
        $totalRevenueAll = $productSales->sum('total_revenue') ?: 0;
        $productSales = $productSales->map(function ($item) use ($totalRevenueAll) {
            $item->gross_profit = (float)($item->total_revenue ?? 0) - (float)($item->total_cogs ?? 0);
            $item->profit_margin_percentage = ($item->total_revenue ?? 0) > 0
                ? ($item->gross_profit / $item->total_revenue) * 100
                : 0;
            $item->contribution_percentage = $totalRevenueAll > 0
                ? (($item->total_revenue ?? 0) / $totalRevenueAll) * 100
                : 0;
            return $item;
        });

        // Handle branch display
        $branch = null;
        if ($branchId !== 'all') {
            $branch = Branch::find($branchId);
        }
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.sales-by-product-pdf', compact(
            'productSales', 'dateFrom', 'dateTo', 'branch', 'company', 'branchId'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('sales-by-product-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Sales by Product Report to Excel
     */
    public function exportSalesByProductExcel(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);

        $productSales = SalesInvoiceItem::with(['inventoryItem', 'salesInvoice'])
            ->whereHas('salesInvoice', function ($query) use ($dateFrom, $dateTo, $branchId) {
                $query->whereBetween('invoice_date', [$dateFrom, $dateTo])
                      ->where('status', '!=', 'cancelled');
                $this->applyBranchFilter($query, $branchId);
            })
            ->select([
                'inventory_item_id',
                DB::raw('SUM(sales_invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(sales_invoice_items.quantity * sales_invoice_items.unit_price) as total_revenue'),
                DB::raw('SUM(sales_invoice_items.quantity * (sales_invoice_items.unit_price - COALESCE(inventory_items.cost_price, 0))) as gross_margin'),
                DB::raw('AVG(sales_invoice_items.unit_price) as avg_unit_price')
            ])
            ->leftJoin('inventory_items', 'sales_invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->groupBy('inventory_item_id')
            ->orderBy('total_revenue', 'desc')
            ->get();

        $branch = Branch::find($branchId);
        $company = current_company();

        return Excel::download(new \App\Exports\SalesByProductExport($productSales, $dateFrom, $dateTo, $branch, $company), 
            'sales-by-product-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Sales by Customer Report to PDF
     */
    public function exportSalesByCustomerPdf(Request $request)
    {
        $this->authorize('view customer reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from'))->startOfDay() : Carbon::today()->startOfDay();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to'))->endOfDay() : Carbon::today()->endOfDay();
        $branchId = $request->get('branch_id', auth()->user()->branch_id);

        $customerSales = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($customerSales, $branchId);

        $customerSales = $customerSales->select([
            'customer_id',
            DB::raw('SUM(total_amount) as total_sales'),
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('AVG(total_amount) as avg_invoice_value'),
            DB::raw('MIN(invoice_date) as first_invoice_date'),
            DB::raw('MAX(invoice_date) as last_invoice_date')
        ])
        ->groupBy('customer_id')
        ->orderBy('total_sales', 'desc')
        ->get();

        // Compute total cost per customer from invoice items
        $costsByCustomer = SalesInvoiceItem::query()
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
            })
            ->leftJoin('inventory_items', 'sales_invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->leftJoin('sales_invoices', 'sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
            ->select([
                'sales_invoices.customer_id',
                DB::raw('SUM(sales_invoice_items.quantity * COALESCE(inventory_items.cost_price, 0)) as total_cost')
            ])
            ->groupBy('sales_invoices.customer_id')
            ->pluck('total_cost', 'customer_id');

        // Calculate outstanding balance and add customer details and profitability
        $customerSales = $customerSales->map(function ($item) use ($costsByCustomer) {
            $customer = $item->customer;
            if ($customer) {
                // Get outstanding balance (unpaid invoices)
                $item->outstanding_balance = SalesInvoice::where('customer_id', $customer->id)
                    ->where('status', '!=', 'cancelled')
                    ->where('balance_due', '>', 0)
                    ->sum('balance_due');
                
                $item->customer_phone = $customer->phone ?? 'N/A';
            } else {
                $item->outstanding_balance = 0;
                $item->customer_phone = 'N/A';
            }
            // Profitability
            $item->total_cost = (float)($costsByCustomer[$item->customer_id] ?? 0);
            $item->gross_profit = (float)$item->total_sales - $item->total_cost;
            return $item;
        });

        $branch = Branch::find($branchId);
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.sales-by-customer-pdf', compact(
            'customerSales', 'dateFrom', 'dateTo', 'branch', 'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('sales-by-customer-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Sales by Customer Report to Excel
     */
    public function exportSalesByCustomerExcel(Request $request)
    {
        $this->authorize('view customer reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from'))->startOfDay() : Carbon::today()->startOfDay();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to'))->endOfDay() : Carbon::today()->endOfDay();
        $branchId = $request->get('branch_id', auth()->user()->branch_id);

        $customerSales = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($customerSales, $branchId);

        $customerSales = $customerSales->select([
            'customer_id',
            DB::raw('SUM(total_amount) as total_sales'),
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('AVG(total_amount) as avg_invoice_value'),
            DB::raw('MIN(invoice_date) as first_invoice_date'),
            DB::raw('MAX(invoice_date) as last_invoice_date')
        ])
        ->groupBy('customer_id')
        ->orderBy('total_sales', 'desc')
        ->get();

        // Compute total cost per customer from invoice items
        $costsByCustomer = SalesInvoiceItem::query()
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
            })
            ->leftJoin('inventory_items', 'sales_invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->leftJoin('sales_invoices', 'sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
            ->select([
                'sales_invoices.customer_id',
                DB::raw('SUM(sales_invoice_items.quantity * COALESCE(inventory_items.cost_price, 0)) as total_cost')
            ])
            ->groupBy('sales_invoices.customer_id')
            ->pluck('total_cost', 'customer_id');

        // Calculate outstanding balance and add customer details and profitability
        $customerSales = $customerSales->map(function ($item) use ($costsByCustomer) {
            $customer = $item->customer;
            if ($customer) {
                // Get outstanding balance (unpaid invoices)
                $item->outstanding_balance = SalesInvoice::where('customer_id', $customer->id)
                    ->where('status', '!=', 'cancelled')
                    ->where('balance_due', '>', 0)
                    ->sum('balance_due');
                
                $item->customer_phone = $customer->phone ?? 'N/A';
            } else {
                $item->outstanding_balance = 0;
                $item->customer_phone = 'N/A';
            }
            // Profitability
            $item->total_cost = (float)($costsByCustomer[$item->customer_id] ?? 0);
            $item->gross_profit = (float)$item->total_sales - $item->total_cost;
            return $item;
        });

        $branch = Branch::find($branchId);
        $company = current_company();

        return Excel::download(new \App\Exports\SalesByCustomerExport($customerSales, $dateFrom, $dateTo, $branch, $company), 
            'sales-by-customer-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Sales by Branch Report to PDF
     */
    public function exportSalesByBranchPdf(Request $request)
    {
        $this->authorize('view branch performance');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from'))->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to'))->endOfDay() : Carbon::now()->endOfMonth();

        $branchSales = SalesInvoice::with(['branch'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->select([
                'branch_id',
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('COUNT(DISTINCT customer_id) as customers_served'),
                DB::raw('AVG(total_amount) as avg_invoice_value')
            ])
            ->groupBy('branch_id')
            ->orderBy('total_sales', 'desc')
            ->get();

        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.sales-by-branch-pdf', compact(
            'branchSales', 'dateFrom', 'dateTo', 'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('sales-by-branch-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Sales by Branch Report to Excel
     */
    public function exportSalesByBranchExcel(Request $request)
    {
        $this->authorize('view branch performance');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

        $branchSales = SalesInvoice::with(['branch'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->select([
                'branch_id',
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('COUNT(DISTINCT customer_id) as customers_served'),
                DB::raw('AVG(total_amount) as avg_invoice_value')
            ])
            ->groupBy('branch_id')
            ->orderBy('total_sales', 'desc')
            ->get();

        $company = current_company();

        return Excel::download(new \App\Exports\SalesByBranchExport($branchSales, $dateFrom, $dateTo, $company), 
            'sales-by-branch-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Receivables Aging Report to PDF
     */
    public function exportReceivablesAgingPdf(Request $request)
    {
        $this->authorize('view collection report');
        
        $asOfDate = $request->get('as_of_date', Carbon::now()->format('Y-m-d'));
        $branchId = $request->get('branch_id', auth()->user()->branch_id);
        $viewType = $request->get('view_type', 'summary'); // summary | detailed | trend

        $agingData = SalesInvoice::with(['customer'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $asOfDate);

        $this->applyBranchFilter($agingData, $branchId);

        $agingData = $agingData->get()->map(function ($invoice) use ($asOfDate) {
            $asOf = Carbon::parse($asOfDate);
            $invoiceDate = Carbon::parse($invoice->invoice_date);
            $dueDate = !empty($invoice->due_date)
                ? Carbon::parse($invoice->due_date)
                : $invoiceDate->copy()->addDays($invoice->payment_days ?? 30);
            $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;
            $outstandingAmount = ($invoice->balance_due ?? 0) ?: (($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0));
            
            return [
                'customer_name' => $invoice->customer->name ?? 'Unknown',
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'due_date' => $dueDate,
                'total_amount' => (float)$invoice->total_amount,
                'paid_amount' => (float)($invoice->paid_amount ?? 0),
                'outstanding_amount' => (float)$outstandingAmount,
                'days_overdue' => $daysOverdue,
                'aging_bucket' => $this->getAgingBucket($daysOverdue),
                'status' => $invoice->status,
            ];
        })->filter(function ($item) {
            return $item['outstanding_amount'] > 0;
        });

        $branch = Branch::find($branchId);
        $company = current_company();

        // Build executive summary buckets (overdue-only)
        $buckets = ['0-30','31-60','61-90','90+'];
        $overdueOnly = collect($agingData)->filter(function ($i) { return ($i['days_overdue'] ?? 0) > 0; });
        $summary = collect($buckets)->mapWithKeys(function($b) use ($overdueOnly) {
            $filtered = $overdueOnly->where('aging_bucket', $b);
            return [$b => [
                'count' => $filtered->count(),
                'amount' => (float)$filtered->sum('outstanding_amount'),
            ]];
        });
        $totalOutstanding = (float)$overdueOnly->sum('outstanding_amount');
        $summary = $summary->map(function($row) use ($totalOutstanding) {
            $row['pct'] = $totalOutstanding > 0 ? ($row['amount'] / $totalOutstanding) * 100 : 0;
            return $row;
        });

        // Detailed ALL buckets grouped by customer (overdue only)
        $bucketLabels = ['0-30' => '0 – 30 Days', '31-60' => '31 – 60 Days', '61-90' => '61 – 90 Days', '90+' => 'Over 90 Days'];
        $detailedAllBuckets = collect($bucketLabels)->map(function($label, $key) use ($overdueOnly) {
            $bucketInvoices = $overdueOnly->where('aging_bucket', $key);
            $groups = $bucketInvoices
                ->groupBy('customer_name')
                ->map(function($invoices, $customerName) {
                    $subtotal = (float)collect($invoices)->sum('outstanding_amount');
                    return [
                        'customer_name' => $customerName,
                        'invoices' => $invoices,
                        'subtotal' => $subtotal,
                    ];
                })->values();
            $bucketTotal = (float)$bucketInvoices->sum('outstanding_amount');
            return [
                'label' => $label,
                'key' => $key,
                'groups' => $groups,
                'bucket_total' => $bucketTotal,
            ];
        })->values();

        // Trend comparison: current month vs previous month by bucket
        $currentMonthStart = Carbon::parse($asOfDate)->copy()->startOfMonth();
        $prevMonthEnd = $currentMonthStart->copy()->subDay();
        $prevMonthStart = $prevMonthEnd->copy()->startOfMonth();

        $prevAgingData = SalesInvoice::with(['customer'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $prevMonthEnd)
            ->get()
            ->map(function ($invoice) use ($prevMonthEnd) {
                $asOf = Carbon::parse($prevMonthEnd);
                $invoiceDate = Carbon::parse($invoice->invoice_date);
                $dueDate = !empty($invoice->due_date)
                    ? Carbon::parse($invoice->due_date)
                    : $invoiceDate->copy()->addDays($invoice->payment_days ?? 30);
                $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;
                $outstandingAmount = ($invoice->balance_due ?? 0) ?: (($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0));
                return [
                    'outstanding_amount' => (float)$outstandingAmount,
                    'aging_bucket' => $this->getAgingBucket($daysOverdue)
                ];
            })->filter(function ($item) {
                return $item['outstanding_amount'] > 0;
            });

        $currentByBucket = collect($buckets)->mapWithKeys(function($b) use ($overdueOnly) {
            return [$b => (float)$overdueOnly->where('aging_bucket', $b)->sum('outstanding_amount')];
        });
        $prevByBucket = collect($buckets)->mapWithKeys(function($b) use ($prevAgingData) {
            return [$b => (float)$prevAgingData->where('aging_bucket', $b)->sum('outstanding_amount')];
        });
        $trend = collect($buckets)->map(function($b) use ($currentByBucket, $prevByBucket) {
            $current = $currentByBucket[$b] ?? 0;
            $prev = $prevByBucket[$b] ?? 0;
            $pctChange = $prev > 0 ? (($current - $prev) / $prev) * 100 : ($current > 0 ? 100 : 0);
            return [
                'bucket' => $b,
                'current' => $current,
                'previous' => $prev,
                'pct_change' => $pctChange,
            ];
        });

        $pdf = Pdf::loadView('sales.reports.exports.receivables-aging-pdf', compact(
            'summary', 'totalOutstanding', 'detailedAllBuckets', 'trend', 'asOfDate', 'branch', 'company', 'viewType'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('receivables-aging-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Receivables Aging Report to Excel
     */
    public function exportReceivablesAgingExcel(Request $request)
    {
        $this->authorize('view collection report');
        
        $asOfDate = $request->get('as_of_date', Carbon::now()->format('Y-m-d'));
        $branchId = $request->get('branch_id', auth()->user()->branch_id);

        $agingData = SalesInvoice::with(['customer'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $asOfDate);

        $this->applyBranchFilter($agingData, $branchId);

        $agingData = $agingData->get()->map(function ($invoice) use ($asOfDate) {
            $daysOutstanding = Carbon::parse($asOfDate)->diffInDays(Carbon::parse($invoice->invoice_date));
            $outstandingAmount = $invoice->total_amount - $invoice->paid_amount;
            
            return [
                'customer_name' => $invoice->customer->name ?? 'Unknown',
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'outstanding_amount' => $outstandingAmount,
                'days_outstanding' => $daysOutstanding,
                'aging_bucket' => $this->getAgingBucket($daysOutstanding)
            ];
        })->filter(function ($item) {
            return $item['outstanding_amount'] > 0;
        });

        $branch = Branch::find($branchId);
        $company = current_company();

        return Excel::download(new \App\Exports\ReceivablesAgingExport($agingData, $asOfDate, $branch, $company), 
            'receivables-aging-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Tax Invoice Report to PDF
     */
    public function exportTaxInvoicePdf(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }
        
        $branchId = $request->get('branch_id', auth()->user()->branch_id);
        $taxType = $request->get('tax_type', 'both');

        $query = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($query, $branchId);

        // Apply tax type filter
        if ($taxType === 'vat') {
            $query->where('vat_amount', '>', 0)->where('withholding_tax_amount', 0);
        } elseif ($taxType === 'wht') {
            $query->where('withholding_tax_amount', '>', 0)->where('vat_amount', 0);
        } elseif ($taxType === 'both') {
            $query->where(function($q) {
                $q->where('vat_amount', '>', 0)->orWhere('withholding_tax_amount', '>', 0);
            });
        } elseif ($taxType === 'none') {
            $query->where('vat_amount', 0)->where('withholding_tax_amount', 0);
        }

        $taxInvoices = $query->orderBy('invoice_date', 'desc')->get();

        $branch = Branch::find($branchId);
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.tax-invoice-pdf', compact(
            'taxInvoices', 'dateFrom', 'dateTo', 'branch', 'taxType', 'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('tax-invoice-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Tax Invoice Report to Excel
     */
    public function exportTaxInvoiceExcel(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }
        
        $branchId = $request->get('branch_id', auth()->user()->branch_id);
        $taxType = $request->get('tax_type', 'both');

        $query = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($query, $branchId);

        // Apply tax type filter
        if ($taxType === 'vat') {
            $query->where('vat_amount', '>', 0)->where('withholding_tax_amount', 0);
        } elseif ($taxType === 'wht') {
            $query->where('withholding_tax_amount', '>', 0)->where('vat_amount', 0);
        } elseif ($taxType === 'both') {
            $query->where(function($q) {
                $q->where('vat_amount', '>', 0)->orWhere('withholding_tax_amount', '>', 0);
            });
        } elseif ($taxType === 'none') {
            $query->where('vat_amount', 0)->where('withholding_tax_amount', 0);
        }

        $taxInvoices = $query->orderBy('invoice_date', 'desc')->get();

        $branch = Branch::find($branchId);
        $company = current_company();

        return Excel::download(new \App\Exports\TaxInvoiceExport($taxInvoices, $dateFrom, $dateTo, $branch, $taxType, $company), 
            'tax-invoice-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Sales by Salesperson Report to PDF
     */
    public function exportSalesBySalespersonPdf(Request $request)
    {
        $this->authorize('view staff performance');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Get user's assigned branches
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        // Use combined sales (invoices + POS + cash)
        $combinedSales = $this->getCombinedSalesData($dateFrom, $dateTo, $branchId, [$this, 'applyAssignedBranchFilter']);
        $salespersonSales = $combinedSales->groupBy('created_by')->map(function ($sales, $salespersonId) {
            $totalSales = $sales->sum('total_amount');
            $amountCollected = $sales->sum('paid_amount');
            $outstandingAmount = $sales->sum('balance_due');
            $invoiceCount = $sales->count();
            $customersServed = $sales->pluck('customer_id')->filter()->unique()->count();
            return (object)[
                'created_by' => $salespersonId,
                'createdBy' => $salespersonId ? \App\Models\User::find($salespersonId) : null,
                'total_sales' => (float)$totalSales,
                'amount_collected' => (float)$amountCollected,
                'outstanding_amount' => (float)$outstandingAmount,
                'invoice_count' => $invoiceCount,
                'customers_served' => $customersServed,
            ];
        })->values()->sortByDesc('total_sales');
        
        // Calculate collection percentage for each salesperson
        $salespersonSales = $salespersonSales->map(function ($salesperson) {
            $salesperson->collection_percentage = $salesperson->total_sales > 0 
                ? (($salesperson->amount_collected ?? 0) / $salesperson->total_sales) * 100 
                : 0;
            $salesperson->amount_collected = (float)($salesperson->amount_collected ?? 0);
            $salesperson->outstanding_amount = (float)($salesperson->outstanding_amount ?? 0);
            return $salesperson;
        });

        $branch = ($branchId !== 'all') ? Branch::find($branchId) : null;
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.sales-by-salesperson-pdf', compact(
            'salespersonSales', 'dateFrom', 'dateTo', 'branch', 'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('sales-by-salesperson-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Sales by Salesperson Report to Excel
     */
    public function exportSalesBySalespersonExcel(Request $request)
    {
        $this->authorize('view staff performance');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Get user's assigned branches
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        $salespersonSales = SalesInvoice::with(['createdBy'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        // Apply assigned branch filtering
        $this->applyAssignedBranchFilter($salespersonSales, $branchId);

        $salespersonSales = $salespersonSales->select([
            'created_by',
            DB::raw('SUM(total_amount) as total_sales'),
            DB::raw('SUM(paid_amount) as amount_collected'),
            DB::raw('SUM(balance_due) as outstanding_amount'),
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('COUNT(DISTINCT customer_id) as customers_served')
        ])
        ->groupBy('created_by')
        ->orderBy('total_sales', 'desc')
        ->get();
        
        // Calculate collection percentage for each salesperson
        $salespersonSales = $salespersonSales->map(function ($salesperson) {
            $salesperson->collection_percentage = $salesperson->total_sales > 0 
                ? (($salesperson->amount_collected ?? 0) / $salesperson->total_sales) * 100 
                : 0;
            $salesperson->amount_collected = (float)($salesperson->amount_collected ?? 0);
            $salesperson->outstanding_amount = (float)($salesperson->outstanding_amount ?? 0);
            return $salesperson;
        });

        $branch = ($branchId !== 'all') ? Branch::find($branchId) : null;
        $company = current_company();

        return Excel::download(new \App\Exports\SalesBySalespersonExport($salespersonSales, $dateFrom, $dateTo, $branch, $company), 
            'sales-by-salesperson-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Discount Effectiveness Report to PDF
     */
    public function exportDiscountEffectivenessPdf(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Smart branch selection logic
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        $branchId = $request->get('branch_id', $defaultBranchId);

        $invoices = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($invoices, $branchId);

        $invoices = $invoices->get();

        $discountData = $invoices->map(function ($invoice) {
            $grossSales = $invoice->total_amount + $invoice->discount_amount;
            $netSales = $invoice->total_amount;
            $discountPercentage = $grossSales > 0 ? ($invoice->discount_amount / $grossSales) * 100 : 0;
            
            return [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name ?? 'Unknown',
                'invoice_date' => $invoice->invoice_date,
                'gross_sales' => $grossSales,
                'discount_amount' => $invoice->discount_amount,
                'net_sales' => $netSales,
                'discount_percentage' => $discountPercentage
            ];
        })->filter(function ($item) {
            return $item['discount_amount'] > 0;
        });

        $branch = $branchId !== 'all' ? Branch::find($branchId) : null;
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.discount-effectiveness-pdf', compact(
            'discountData', 'dateFrom', 'dateTo', 'branch', 'company', 'branchId'
        ));

        return $pdf->download('discount-effectiveness-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Discount Effectiveness Report to Excel
     */
    public function exportDiscountEffectivenessExcel(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Smart branch selection logic
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        $branchId = $request->get('branch_id', $defaultBranchId);

        $invoices = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($invoices, $branchId);

        $invoices = $invoices->get();

        $discountData = $invoices->map(function ($invoice) {
            $grossSales = $invoice->total_amount + $invoice->discount_amount;
            $netSales = $invoice->total_amount;
            $discountPercentage = $grossSales > 0 ? ($invoice->discount_amount / $grossSales) * 100 : 0;
            
            return [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name ?? 'Unknown',
                'invoice_date' => $invoice->invoice_date,
                'gross_sales' => $grossSales,
                'discount_amount' => $invoice->discount_amount,
                'net_sales' => $netSales,
                'discount_percentage' => $discountPercentage
            ];
        })->filter(function ($item) {
            return $item['discount_amount'] > 0;
        });

        $branch = $branchId !== 'all' ? Branch::find($branchId) : null;
        $company = current_company();

        return Excel::download(new \App\Exports\DiscountEffectivenessExport($discountData, $dateFrom, $dateTo, $branch, $company), 
            'discount-effectiveness-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Profitability by Product Report to PDF
     */
    public function exportProfitabilityByProductPdf(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Get user's assigned branches
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        $productProfitability = SalesInvoiceItem::with(['inventoryItem', 'salesInvoice'])
            ->whereHas('salesInvoice', function ($query) use ($dateFrom, $dateTo, $branchId) {
                $query->whereBetween('invoice_date', [$dateFrom, $dateTo])
                      ->where('status', '!=', 'cancelled');
                $this->applyAssignedBranchFilter($query, $branchId);
            })
            ->select([
                'inventory_item_id',
                DB::raw('SUM(sales_invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(sales_invoice_items.quantity * sales_invoice_items.unit_price) as total_revenue'),
                DB::raw('SUM(sales_invoice_items.quantity * COALESCE(inventory_items.cost_price, 0)) as total_cogs'),
                DB::raw('SUM(sales_invoice_items.quantity * (sales_invoice_items.unit_price - COALESCE(inventory_items.cost_price, 0))) as gross_profit'),
                DB::raw('AVG(sales_invoice_items.unit_price) as avg_unit_price')
            ])
            ->leftJoin('inventory_items', 'sales_invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->groupBy('inventory_item_id')
            ->orderBy('gross_profit', 'desc')
            ->get();

        $branch = ($branchId !== 'all') ? Branch::find($branchId) : null;
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.profitability-by-product-pdf', compact(
            'productProfitability', 'dateFrom', 'dateTo', 'branch', 'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('profitability-by-product-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Profitability by Product Report to Excel
     */
    public function exportProfitabilityByProductExcel(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        
        // Get user's assigned branches
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        $productProfitability = SalesInvoiceItem::with(['inventoryItem', 'salesInvoice'])
            ->whereHas('salesInvoice', function ($query) use ($dateFrom, $dateTo, $branchId) {
                $query->whereBetween('invoice_date', [$dateFrom, $dateTo])
                      ->where('status', '!=', 'cancelled');
                $this->applyAssignedBranchFilter($query, $branchId);
            })
            ->select([
                'inventory_item_id',
                DB::raw('SUM(sales_invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(sales_invoice_items.quantity * sales_invoice_items.unit_price) as total_revenue'),
                DB::raw('SUM(sales_invoice_items.quantity * COALESCE(inventory_items.cost_price, 0)) as total_cogs'),
                DB::raw('SUM(sales_invoice_items.quantity * (sales_invoice_items.unit_price - COALESCE(inventory_items.cost_price, 0))) as gross_profit'),
                DB::raw('AVG(sales_invoice_items.unit_price) as avg_unit_price')
            ])
            ->leftJoin('inventory_items', 'sales_invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->groupBy('inventory_item_id')
            ->orderBy('gross_profit', 'desc')
            ->get();

        $branch = ($branchId !== 'all') ? Branch::find($branchId) : null;
        $company = current_company();

        return Excel::download(new \App\Exports\ProfitabilityByProductExport($productProfitability, $dateFrom, $dateTo, $branch, $company), 
            'profitability-by-product-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Collection Efficiency Report to PDF
     */
    public function exportCollectionEfficiencyPdf(Request $request)
    {
        $this->authorize('view collection report');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        $branchId = $request->get('branch_id', auth()->user()->branch_id);

        $invoices = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($invoices, $branchId);

        $invoices = $invoices->get();

        $collectionData = $invoices->map(function ($invoice) {
            $outstandingAmount = $invoice->total_amount - $invoice->paid_amount;
            $collectionRate = $invoice->total_amount > 0 ? ($invoice->paid_amount / $invoice->total_amount) * 100 : 0;
            $daysOutstanding = Carbon::now()->diffInDays($invoice->due_date);
            
            return [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name ?? 'Unknown',
                'invoice_date' => $invoice->invoice_date,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'outstanding_amount' => $outstandingAmount,
                'collection_rate' => $collectionRate,
                'days_outstanding' => $daysOutstanding
            ];
        });

        $branch = Branch::find($branchId);
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.collection-efficiency-pdf', compact(
            'collectionData', 'dateFrom', 'dateTo', 'branch', 'company'
        ));

        return $pdf->download('collection-efficiency-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Collection Efficiency Report to Excel
     */
    public function exportCollectionEfficiencyExcel(Request $request)
    {
        $this->authorize('view collection report');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        $branchId = $request->get('branch_id', auth()->user()->branch_id);

        $invoices = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($invoices, $branchId);

        $invoices = $invoices->get();

        $collectionData = $invoices->map(function ($invoice) {
            $outstandingAmount = $invoice->total_amount - $invoice->paid_amount;
            $collectionRate = $invoice->total_amount > 0 ? ($invoice->paid_amount / $invoice->total_amount) * 100 : 0;
            $daysOutstanding = Carbon::now()->diffInDays($invoice->due_date);
            
            return [
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name ?? 'Unknown',
                'invoice_date' => $invoice->invoice_date,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'outstanding_amount' => $outstandingAmount,
                'collection_rate' => $collectionRate,
                'days_outstanding' => $daysOutstanding
            ];
        });

        $branch = Branch::find($branchId);
        $company = current_company();

        return Excel::download(new \App\Exports\CollectionEfficiencyExport($collectionData, $dateFrom, $dateTo, $branch, $company), 
            'collection-efficiency-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Invoice Register Report to PDF
     */
    public function exportInvoiceRegisterPdf(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);
        $status = $request->get('status');

        $query = SalesInvoice::with(['customer', 'createdBy'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo]);

        $this->applyBranchFilter($query, $branchId);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $branch = Branch::find($branchId);
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.invoice-register-pdf', compact(
            'invoices', 'dateFrom', 'dateTo', 'branch', 'status', 'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('invoice-register-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Invoice Register Report to Excel
     */
    public function exportInvoiceRegisterExcel(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = auth()->user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', auth()->user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);
        $status = $request->get('status');

        $query = SalesInvoice::with(['customer', 'createdBy'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo]);

        $this->applyBranchFilter($query, $branchId);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $branch = Branch::find($branchId);
        $company = current_company();

        return Excel::download(new \App\Exports\InvoiceRegisterExport($invoices, $dateFrom, $dateTo, $branch, $status, $company), 
            'invoice-register-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Paid Invoice Report to PDF
     */
    public function exportPaidInvoicePdf(Request $request)
    {
        $this->authorize('view sales reports');
        
        // Default to today's date
        $dateFrom = $request->get('date_from', Carbon::today()->startOfDay());
        $dateTo = $request->get('date_to', Carbon::today()->endOfDay());
        if (is_string($dateFrom)) { $dateFrom = Carbon::parse($dateFrom)->startOfDay(); }
        if (is_string($dateTo)) { $dateTo = Carbon::parse($dateTo)->endOfDay(); }
        
        // Assigned branches with 'all' option
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? auth()->user()->branch_id);
        $branchId = $request->get('branch_id', $defaultBranchId);
        $paymentStatus = $request->get('payment_status', 'all');

        $query = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->where('paid_amount', '>', 0);

        $this->applyAssignedBranchFilter($query, $branchId);

        if ($paymentStatus === 'fully_paid') {
            $query->whereRaw('paid_amount >= total_amount');
        } elseif ($paymentStatus === 'partially_paid') {
            $query->whereRaw('paid_amount < total_amount');
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $branch = $branchId === 'all' ? null : Branch::find($branchId);
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.paid-invoice-pdf', compact(
            'invoices', 'dateFrom', 'dateTo', 'branch', 'paymentStatus', 'company', 'branchId'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('paid-invoice-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Paid Invoice Report to Excel
     */
    public function exportPaidInvoiceExcel(Request $request)
    {
        $this->authorize('view sales reports');
        
        // Default to today's date
        $dateFrom = $request->get('date_from', Carbon::today()->startOfDay());
        $dateTo = $request->get('date_to', Carbon::today()->endOfDay());
        if (is_string($dateFrom)) { $dateFrom = Carbon::parse($dateFrom)->startOfDay(); }
        if (is_string($dateTo)) { $dateTo = Carbon::parse($dateTo)->endOfDay(); }
        
        // Assigned branches with 'all' option
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? auth()->user()->branch_id);
        $branchId = $request->get('branch_id', $defaultBranchId);
        $paymentStatus = $request->get('payment_status', 'all');

        $query = SalesInvoice::with(['customer'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->where('paid_amount', '>', 0);

        $this->applyAssignedBranchFilter($query, $branchId);

        if ($paymentStatus === 'fully_paid') {
            $query->whereRaw('paid_amount >= total_amount');
        } elseif ($paymentStatus === 'partially_paid') {
            $query->whereRaw('paid_amount < total_amount');
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $branch = $branchId === 'all' ? null : Branch::find($branchId);
        $company = current_company();

        return Excel::download(new \App\Exports\PaidInvoiceExport($invoices, $dateFrom, $dateTo, $branch, $paymentStatus, $company), 
            'paid-invoice-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Credit Note Report to PDF
     */
    public function exportCreditNotePdf(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        // Parse dates if provided as strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? auth()->user()->branch_id);
        $branchId = $request->get('branch_id', $defaultBranchId);

        $query = \App\Models\Sales\CreditNote::with(['customer'])
            ->whereBetween('credit_note_date', [$dateFrom, $dateTo]);

        $this->applyAssignedBranchFilter($query, $branchId);

        $creditNotes = $query->orderBy('credit_note_date', 'desc')->get();

        $branch = ($branchId !== 'all') ? Branch::find($branchId) : null;
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.credit-note-pdf', compact(
            'creditNotes', 'dateFrom', 'dateTo', 'branch', 'company'
        ));

        return $pdf->download('credit-note-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Credit Note Report to Excel
     */
    public function exportCreditNoteExcel(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }
        $assignedBranches = auth()->user()->branches()->get();
        $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? auth()->user()->branch_id);
        $branchId = $request->get('branch_id', $defaultBranchId);

        $query = \App\Models\Sales\CreditNote::with(['customer'])
            ->whereBetween('credit_note_date', [$dateFrom, $dateTo]);

        $this->applyAssignedBranchFilter($query, $branchId);

        $creditNotes = $query->orderBy('credit_note_date', 'desc')->get();

        $branch = ($branchId !== 'all') ? Branch::find($branchId) : null;
        $company = current_company();

        return Excel::download(new \App\Exports\CreditNoteExport($creditNotes, $dateFrom, $dateTo, $branch, $company), 
            'credit-note-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Helper method to get aging bucket
     */
    private function getAgingBucket($daysOutstanding)
    {
        if ($daysOutstanding <= 30) return '0-30';
        if ($daysOutstanding <= 60) return '31-60';
        if ($daysOutstanding <= 90) return '61-90';
        return '90+';
    }

    /**
     * Export Branch Profitability Report to PDF
     */
    public function exportBranchProfitabilityPdf(Request $request)
    {
        $this->authorize('view branch performance');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        if (is_string($dateFrom)) { $dateFrom = Carbon::parse($dateFrom)->startOfDay(); }
        if (is_string($dateTo)) { $dateTo = Carbon::parse($dateTo)->endOfDay(); }

        // Revenue: combined sales (Invoices + POS + Cash) grouped by branch
        $combined = $this->getCombinedSalesData($dateFrom, $dateTo, 'all');
        $revenueByBranch = $combined->groupBy('branch_id')->map(function ($sales) {
            return (float)$sales->sum('total_amount');
        });

        // Cost of Sales (COGS) per branch from GL
        $user = auth()->user();
        $company = $user->company;
        $costOfGoodsSoldAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_cost_account')->value('value') ?? 173;
        $cogsQuery = DB::table('gl_transactions')
            ->where('chart_account_id', $costOfGoodsSoldAccountId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->select('branch_id',
                DB::raw('SUM(CASE WHEN nature = "debit" THEN amount ELSE -amount END) as total_cogs'))
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        // Operating expenses from GL per branch (exclude COGS)
        $expensesQuery = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereBetween('gl_transactions.date', [$dateFrom, $dateTo])
            ->whereIn('account_class.name', ['expenses', 'expense'])
            ->when($costOfGoodsSoldAccountId, function($q) use ($costOfGoodsSoldAccountId) {
                $q->where('chart_accounts.id', '!=', $costOfGoodsSoldAccountId);
            })
            ->select('gl_transactions.branch_id',
                DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE -gl_transactions.amount END) as net_expense'))
            ->groupBy('gl_transactions.branch_id')
            ->get()
            ->keyBy('branch_id');

        // Build dataset
        $allBranchIds = collect(array_unique(array_merge(
            $revenueByBranch->keys()->toArray(), 
            $cogsQuery->keys()->toArray(),
            $expensesQuery->keys()->toArray()
        )));
        $branchData = $allBranchIds->map(function ($branchId) use ($revenueByBranch, $cogsQuery, $expensesQuery) {
            $totalRevenue = (float)($revenueByBranch[$branchId] ?? 0);
            $costOfSales = (float)($cogsQuery[$branchId]->total_cogs ?? 0);
            $grossProfit = $totalRevenue - $costOfSales;
            $operatingExpenses = (float)($expensesQuery[$branchId]->net_expense ?? 0);
            $netProfit = $grossProfit - $operatingExpenses;
            $marginPct = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;
            $staffCount = \App\Models\User::whereHas('branches', function($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            })->count();
            $profitPerStaff = $staffCount > 0 ? ($netProfit / $staffCount) : $netProfit;
            return (object)[
                'branch_id' => $branchId,
                'branch' => \App\Models\Branch::find($branchId),
                'total_revenue' => $totalRevenue,
                'cost_of_sales' => $costOfSales,
                'gross_profit' => $grossProfit,
                'operating_expenses' => $operatingExpenses,
                'net_profit' => $netProfit,
                'profit_margin_percentage' => $marginPct,
                'staff_count' => $staffCount,
                'profit_per_staff' => $profitPerStaff,
            ];
        })->sortByDesc('net_profit')->values();

        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.branch-profitability-pdf', compact(
            'branchData', 'dateFrom', 'dateTo', 'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('branch-profitability-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Branch Profitability Report to Excel
     */
    public function exportBranchProfitabilityExcel(Request $request)
    {
        $this->authorize('view branch performance');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

        $branchData = SalesInvoice::with(['branch'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->select([
                'branch_id',
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('COUNT(*) as invoice_count')
            ])
            ->groupBy('branch_id')
            ->get();

        // Add placeholder expenses (would need actual expense data)
        $branchData = $branchData->map(function ($branch) {
            $branch->estimated_expenses = $branch->total_revenue * 0.3; // 30% placeholder
            $branch->net_profit = $branch->total_revenue - $branch->estimated_expenses;
            $branch->net_margin_percentage = $branch->total_revenue > 0 
                ? ($branch->net_profit / $branch->total_revenue) * 100 
                : 0;
            return $branch;
        });

        $company = current_company();

        return Excel::download(new \App\Exports\BranchProfitabilityExport($branchData, $dateFrom, $dateTo, $company), 
            'branch-profitability-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Customer Statement Report to PDF
     */
    public function exportCustomerStatementPdf(Request $request)
    {
        $this->authorize('view customer reports');
        
        $customerId = $request->get('customer_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$customerId || !$dateFrom || !$dateTo) {
            abort(400, 'Customer, date from, and date to are required for export');
        }

        $customer = Customer::findOrFail($customerId);
        $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        $dateTo = Carbon::parse($dateTo)->endOfDay();
        $branchId = $request->get('branch_id', $this->defaultCustomerStatementBranchId());

        $openingBalance = $this->customerStatementOpeningBalance((int) $customerId, $dateFrom, $branchId);
        $core = $this->buildCustomerStatementCore((int) $customerId, $dateFrom, $dateTo, $branchId);
        $invoices = $core['invoices'];
        $creditNotes = $core['creditNotes'];
        $transactions = $core['transactions'];

        $runningBalance = $openingBalance;
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            if ($transaction->type == 'invoice') {
                $runningBalance += $transaction->amount;
            } elseif ($transaction->type == 'payment') {
                $runningBalance -= $transaction->amount;
            } elseif ($transaction->type == 'credit_note') {
                $runningBalance -= $transaction->amount;
            }
            $transaction->balance = $runningBalance;

            return $transaction;
        });

        $totalInvoices = $invoices->sum('total_amount');
        $totalPayments = $transactions->where('type', 'payment')->sum('amount');
        $totalCreditNotes = $creditNotes->sum('total_amount');
        $closingBalance = $openingBalance + $totalInvoices - $totalCreditNotes - $totalPayments;

        $company = current_company();
        $branchName = $branchId === 'all'
            ? 'All Branches'
            : (optional(Branch::find($branchId))->name);

        $pdf = Pdf::loadView('sales.reports.exports.customer-statement-pdf', compact(
            'customer',
            'transactions',
            'dateFrom',
            'dateTo',
            'company',
            'openingBalance',
            'totalInvoices',
            'totalPayments',
            'totalCreditNotes',
            'closingBalance',
            'branchName'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('customer-statement-' . $customer->name . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Customer Statement Report to Excel
     */
    public function exportCustomerStatementExcel(Request $request)
    {
        $this->authorize('view customer reports');
        
        $customerId = $request->get('customer_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$customerId || !$dateFrom || !$dateTo) {
            abort(400, 'Customer, date from, and date to are required for export');
        }

        $customer = Customer::findOrFail($customerId);
        $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        $dateTo = Carbon::parse($dateTo)->endOfDay();
        $branchId = $request->get('branch_id', $this->defaultCustomerStatementBranchId());

        $openingBalance = $this->customerStatementOpeningBalance((int) $customerId, $dateFrom, $branchId);
        $core = $this->buildCustomerStatementCore((int) $customerId, $dateFrom, $dateTo, $branchId);
        $invoices = $core['invoices'];
        $creditNotes = $core['creditNotes'];
        $transactions = $core['transactions'];

        $runningBalance = $openingBalance;
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            if ($transaction->type == 'invoice') {
                $runningBalance += $transaction->amount;
            } elseif ($transaction->type == 'payment') {
                $runningBalance -= $transaction->amount;
            } elseif ($transaction->type == 'credit_note') {
                $runningBalance -= $transaction->amount;
            }
            $transaction->balance = $runningBalance;

            return $transaction;
        });

        $totalInvoices = $invoices->sum('total_amount');
        $totalPayments = $transactions->where('type', 'payment')->sum('amount');
        $closingBalance = $openingBalance + $totalInvoices - $creditNotes->sum('total_amount') - $totalPayments;

        $company = current_company();

        return Excel::download(new \App\Exports\CustomerStatementExport($customer, $transactions, $dateFrom, $dateTo, $company),
            'customer-statement-' . $customer->name . '-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export Sales Return Report to PDF
     */
    public function exportSalesReturnPdf(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        $branchId = $request->get('branch_id', auth()->user()->branch_id);
        $reason = $request->get('reason');

        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        // Build query for return data
        $query = CreditNote::with(['customer', 'items.inventoryItem'])
            ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        
        // Apply branch filtering
        $this->applyBranchFilter($query, $branchId);

        // Apply reason filter if provided
        if ($reason) {
            $query->where('reason_code', $reason);
        }

        $returnData = $query->get();

        // Transform return data for display
        $returnData = $returnData->map(function ($creditNote) {
            $creditNote->return_date = $creditNote->credit_note_date;
            $creditNote->original_invoice_id = $creditNote->sales_invoice_id;
            $creditNote->original_invoice_number = $creditNote->sales_invoice_number ?? 'N/A';
            $creditNote->customer_name = $creditNote->customer->name ?? 'Unknown Customer';
            
            // Get first item for display
            $firstItem = $creditNote->items->first();
            if ($firstItem) {
                $creditNote->item_name = $firstItem->item_name ?? 'Unknown Item';
                $creditNote->item_code = $firstItem->item_code ?? 'N/A';
                $creditNote->quantity = $firstItem->quantity;
                $creditNote->unit_price = $firstItem->unit_price;
                $creditNote->return_value = $firstItem->line_total;
            } else {
                $creditNote->item_name = 'No Items';
                $creditNote->item_code = 'N/A';
                $creditNote->quantity = 0;
                $creditNote->unit_price = 0;
                $creditNote->return_value = 0;
            }
            
            $creditNote->reason = $creditNote->reason_code ?? 'unknown';
            $creditNote->status = $creditNote->status ?? 'processed';
            
            return $creditNote;
        });

        // Calculate summary statistics
        $totalReturnValue = $returnData->sum('return_value');
        $totalReturns = $returnData->count();
        
        // Get gross sales for comparison
        $grossSalesQuery = SalesInvoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($grossSalesQuery, $branchId);
        $grossSales = $grossSalesQuery->sum('total_amount');

        $returnRate = $grossSales > 0 ? ($totalReturnValue / $grossSales) * 100 : 0;
        $netSales = $grossSales - $totalReturnValue;

        $branch = ($branchId !== 'all') ? Branch::find($branchId) : null;
        $company = current_company();

        $pdf = Pdf::loadView('sales.reports.exports.sales-return-pdf', compact(
            'returnData', 'dateFrom', 'dateTo', 'branch', 'reason', 'company', 'grossSales'
        ));

        return $pdf->download('sales-return-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Sales Return Report to Excel
     */
    public function exportSalesReturnExcel(Request $request)
    {
        $this->authorize('view sales reports');
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());
        $branchId = $request->get('branch_id', auth()->user()->branch_id);
        $reason = $request->get('reason');

        // Parse dates if they are strings
        if (is_string($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
        }
        if (is_string($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->endOfDay();
        }

        // Build query for return data
        $query = CreditNote::with(['customer', 'items.inventoryItem'])
            ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        
        // Apply branch filtering
        $this->applyBranchFilter($query, $branchId);

        // Apply reason filter if provided
        if ($reason) {
            $query->where('reason_code', $reason);
        }

        $returnData = $query->get();

        // Transform return data for display
        $returnData = $returnData->map(function ($creditNote) {
            $creditNote->return_date = $creditNote->credit_note_date;
            $creditNote->original_invoice_id = $creditNote->sales_invoice_id;
            $creditNote->original_invoice_number = $creditNote->sales_invoice_number ?? 'N/A';
            $creditNote->customer_name = $creditNote->customer->name ?? 'Unknown Customer';
            
            // Get first item for display
            $firstItem = $creditNote->items->first();
            if ($firstItem) {
                $creditNote->item_name = $firstItem->item_name ?? 'Unknown Item';
                $creditNote->item_code = $firstItem->item_code ?? 'N/A';
                $creditNote->quantity = $firstItem->quantity;
                $creditNote->unit_price = $firstItem->unit_price;
                $creditNote->return_value = $firstItem->line_total;
            } else {
                $creditNote->item_name = 'No Items';
                $creditNote->item_code = 'N/A';
                $creditNote->quantity = 0;
                $creditNote->unit_price = 0;
                $creditNote->return_value = 0;
            }
            
            $creditNote->reason = $creditNote->reason_code ?? 'unknown';
            $creditNote->status = $creditNote->status ?? 'processed';
            
            return $creditNote;
        });

        $branch = ($branchId !== 'all') ? Branch::find($branchId) : null;
        $company = current_company();

        return Excel::download(new \App\Exports\SalesReturnExport($returnData, $dateFrom, $dateTo, $branch, $reason, $company), 
            'sales-return-report-' . now()->format('Y-m-d') . '.xlsx');
    }

}
