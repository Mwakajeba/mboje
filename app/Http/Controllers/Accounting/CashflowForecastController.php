<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CashflowForecast;
use App\Services\CashflowForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class CashflowForecastController extends Controller
{
    protected $forecastService;

    public function __construct(CashflowForecastService $forecastService)
    {
        $this->forecastService = $forecastService;
    }

    /**
     * Display a listing of cashflow forecasts
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Handle AJAX request for DataTables
        if ($request->ajax()) {
            $query = CashflowForecast::forCompany($companyId)
                ->with(['createdBy', 'branch', 'items']);
            
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            
            if ($request->filled('scenario')) {
                $query->where('scenario', $request->scenario);
            }
            
            return datatables($query)
                ->filter(function ($query) use ($request) {
                    if ($request->filled('search.value')) {
                        $searchValue = $request->input('search.value');
                        $query->where(function($q) use ($searchValue) {
                            $q->where('forecast_name', 'like', "%{$searchValue}%")
                              ->orWhereHas('branch', function($branchQuery) use ($searchValue) {
                                  $branchQuery->where('name', 'like', "%{$searchValue}%");
                              })
                              ->orWhereHas('createdBy', function($userQuery) use ($searchValue) {
                                  $userQuery->where('name', 'like', "%{$searchValue}%");
                              });
                        });
                    }
                })
                ->addColumn('branch_name', function ($forecast) {
                    return $forecast->branch->name ?? 'All Branches';
                })
                ->addColumn('scenario_badge', function ($forecast) {
                    $scenarios = [
                        'best_case' => ['label' => 'Best Case', 'class' => 'success'],
                        'base_case' => ['label' => 'Base Case', 'class' => 'info'],
                        'worst_case' => ['label' => 'Worst Case', 'class' => 'warning']
                    ];
                    $scenario = $scenarios[$forecast->scenario] ?? ['label' => ucfirst(str_replace('_', ' ', $forecast->scenario)), 'class' => 'secondary'];
                    return '<span class="badge bg-' . $scenario['class'] . '">' . $scenario['label'] . '</span>';
                })
                ->addColumn('timeline_badge', function ($forecast) {
                    $timelines = [
                        'daily' => ['label' => 'Daily', 'class' => 'primary'],
                        'weekly' => ['label' => 'Weekly', 'class' => 'info'],
                        'monthly' => ['label' => 'Monthly', 'class' => 'success'],
                        'quarterly' => ['label' => 'Quarterly', 'class' => 'warning']
                    ];
                    $timeline = $timelines[$forecast->timeline] ?? ['label' => ucfirst($forecast->timeline), 'class' => 'secondary'];
                    return '<span class="badge bg-' . $timeline['class'] . '">' . $timeline['label'] . '</span>';
                })
                ->addColumn('period', function ($forecast) {
                    return $forecast->start_date->format('d M Y') . ' - ' . $forecast->end_date->format('d M Y');
                })
                ->addColumn('starting_balance_formatted', function ($forecast) {
                    return number_format($forecast->starting_cash_balance, 2) . ' TZS';
                })
                ->addColumn('total_inflows', function ($forecast) {
                    $total = $forecast->getTotalInflows();
                    return '<span class="text-success fw-bold">' . number_format($total, 2) . ' TZS</span>';
                })
                ->addColumn('total_outflows', function ($forecast) {
                    $total = $forecast->getTotalOutflows();
                    return '<span class="text-danger fw-bold">' . number_format($total, 2) . ' TZS</span>';
                })
                ->addColumn('net_cashflow', function ($forecast) {
                    $net = $forecast->getNetCashflow();
                    $class = $net >= 0 ? 'text-success' : 'text-danger';
                    $icon = $net >= 0 ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt';
                    return '<span class="' . $class . ' fw-bold"><i class="bx ' . $icon . '"></i> ' . number_format($net, 2) . ' TZS</span>';
                })
                ->addColumn('status_badge', function ($forecast) {
                    $badgeClass = $forecast->is_active ? 'success' : 'secondary';
                    $statusText = $forecast->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badgeClass . '">' . $statusText . '</span>';
                })
                ->addColumn('actions', function ($forecast) {
                    $encodedId = $forecast->encoded_id;
                    $actions = '<div class="btn-group" role="group">';
                    
                    $actions .= '<a href="' . route('accounting.cashflow-forecasts.show', $encodedId) . '" class="btn btn-sm btn-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>';
                    
                    $actions .= '<form action="' . route('accounting.cashflow-forecasts.regenerate', $encodedId) . '" method="POST" class="d-inline regenerate-form">
                        ' . csrf_field() . '
                        <button type="button" class="btn btn-sm btn-warning regenerate-btn" title="Regenerate" data-forecast-id="' . $encodedId . '">
                            <i class="bx bx-refresh"></i>
                        </button>
                    </form>';
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['scenario_badge', 'timeline_badge', 'starting_balance_formatted', 'total_inflows', 'total_outflows', 'net_cashflow', 'status_badge', 'actions'])
                ->make(true);
        }
        
        // Calculate stats
        $totalForecasts = CashflowForecast::forCompany($companyId)->count();
        $activeForecasts = CashflowForecast::forCompany($companyId)->active()->count();
        
        // Calculate totals from all active forecasts
        $activeForecastsQuery = CashflowForecast::forCompany($companyId)->active();
        $totalInflows = 0;
        $totalOutflows = 0;
        
        foreach ($activeForecastsQuery->get() as $forecast) {
            $totalInflows += $forecast->getTotalInflows();
            $totalOutflows += $forecast->getTotalOutflows();
        }
        
        $netCashflow = $totalInflows - $totalOutflows;
        
        $branches = \App\Models\Branch::where('company_id', $companyId)->orderBy('name')->get();
        
        return view('accounting.cashflow-forecasts.index', compact('totalForecasts', 'activeForecasts', 'totalInflows', 'totalOutflows', 'netCashflow', 'branches'));
    }

    /**
     * Show the form for creating a new forecast
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $branches = \App\Models\Branch::where('company_id', $companyId)->orderBy('name')->get();
        
        // Calculate opening balance breakdown for default start date (today)
        $forecastService = app(\App\Services\CashflowForecastService::class);
        $defaultStartDate = now()->format('Y-m-d');
        $calculatedBalance = $forecastService->calculateOpeningCashBalance($companyId, null, $defaultStartDate);
        
        // Get breakdown details
        $balanceBreakdown = $this->getBalanceBreakdown($companyId, null, $defaultStartDate);
        
        return view('accounting.cashflow-forecasts.create', compact('branches', 'calculatedBalance', 'balanceBreakdown'));
    }

    /**
     * Get detailed breakdown of opening balance
     */
    private function getBalanceBreakdown($companyId, $branchId = null, $asOfDate = null)
    {
        $asOfDate = $asOfDate ? \Carbon\Carbon::parse($asOfDate) : now();
        // Format date with time to match database format (end of day to include all transactions on that date)
        $asOfDateWithTime = $asOfDate->format('Y-m-d') . ' 23:59:59';
        $breakdown = [
            'bank_accounts' => [],
            'total' => 0,
        ];
        
        // Bank Account Balances only (petty cash excluded)
        // Filter by company through chart_account -> account_class_group relationship
        // (since bank_accounts.company_id may be NULL, we use the chart account relationship)
        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();
        
        foreach ($bankAccounts as $bankAccount) {
            if (!$bankAccount->chart_account_id) {
                continue; // Skip if no chart account is linked
            }
            
            // Use DB::table() for consistency with other reports and proper date/time handling
            $balanceQuery = \Illuminate\Support\Facades\DB::table('gl_transactions')
                ->where('chart_account_id', $bankAccount->chart_account_id)
                ->where('date', '<=', $asOfDateWithTime);
            
            // Apply branch filter if specified
            if ($branchId) {
                $balanceQuery->where('branch_id', $branchId);
            }
            
            // Calculate balance using SQL aggregation
            $balance = $balanceQuery->selectRaw('
                SUM(CASE WHEN nature = "debit" THEN amount ELSE 0 END) -
                SUM(CASE WHEN nature = "credit" THEN amount ELSE 0 END) as balance
            ')->value('balance') ?? 0;
            
            // Include account even if balance is 0, or only if balance != 0
            if (abs($balance) > 0.01) { // Include if balance is not effectively zero
                $breakdown['bank_accounts'][] = [
                    'name' => $bankAccount->name,
                    'account_number' => $bankAccount->account_number,
                    'balance' => $balance,
                ];
                $breakdown['total'] += $balance;
            }
        }
        
        // Note: Petty cash is excluded from opening balance calculation
        
        return $breakdown;
    }

    /**
     * AJAX endpoint to calculate opening balance
     */
    public function calculateBalance(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = $request->input('branch_id');
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        
        \Log::info('Cashflow Forecast - Calculate Balance Request', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'start_date' => $startDate,
            'user_id' => $user->id,
        ]);
        
        $forecastService = app(\App\Services\CashflowForecastService::class);
        $calculatedBalance = $forecastService->calculateOpeningCashBalance($companyId, $branchId, $startDate);
        $breakdown = $this->getBalanceBreakdown($companyId, $branchId, $startDate);
        
        \Log::info('Cashflow Forecast - Calculate Balance Response', [
            'calculated_balance' => $calculatedBalance,
            'breakdown_total' => $breakdown['total'] ?? 0,
            'bank_accounts_count' => count($breakdown['bank_accounts'] ?? []),
        ]);
        
        return response()->json([
            'success' => true,
            'balance' => $calculatedBalance,
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Store a newly created forecast and generate forecast items
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'forecast_name' => 'required|string|max:255',
            'scenario' => 'required|in:best_case,base_case,worst_case',
            'timeline' => 'nullable|in:daily,weekly,monthly,quarterly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'starting_cash_balance' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',
        ]);
        
        // Set default timeline if not provided
        if (empty($validated['timeline'])) {
            $validated['timeline'] = 'monthly';
        }
        
        try {
            DB::beginTransaction();
            
            $validated['company_id'] = Auth::user()->company_id;
            $validated['created_by'] = Auth::id();
            $validated['is_active'] = true;
            
            // If balance method is auto or balance is 0, calculate it
            if ($request->input('balance_method') === 'auto' || ($validated['starting_cash_balance'] == 0)) {
                $calculatedBalance = $this->forecastService->calculateOpeningCashBalance(
                    $validated['company_id'],
                    $validated['branch_id'] ?? null,
                    $validated['start_date']
                );
                $validated['starting_cash_balance'] = $calculatedBalance;
            }
            
            $forecast = CashflowForecast::create($validated);
            
            // Generate forecast items
            $this->forecastService->generateForecastItems($forecast);
            
            DB::commit();
            
            return redirect()->route('accounting.cashflow-forecasts.show', $forecast->encoded_id)
                ->with('success', 'Cashflow forecast created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create forecast: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified forecast
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $forecast = CashflowForecast::with(['items' => function($q) {
            $q->orderBy('forecast_date');
        }])->findOrFail($id);
        
        // Group items by date for display
        $groupedItems = $forecast->items->groupBy('forecast_date');
        
        // Calculate running balance and detailed summary
        $runningBalance = $forecast->starting_cash_balance;
        $summary = [];
        
        foreach ($groupedItems->sortKeys() as $date => $items) {
            $openingBalance = $runningBalance;
            $inflows = $items->where('type', 'inflow')->sum('amount');
            $outflows = $items->where('type', 'outflow')->sum('amount');
            $net = $inflows - $outflows;
            $runningBalance += $net;
            
            // Build notes from items (first 2-3 major items with "+X more" if applicable)
            $notes = $this->buildDayNotes($items);
            
            $summary[$date] = [
                'opening_balance' => $openingBalance,
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $net,
                'closing_balance' => $runningBalance,
                'items' => $items,
                'notes' => $notes,
            ];
        }
        
        // Generate AI insights and anomalies
        $insights = $this->generateAIInsights($forecast, $summary);
        $anomalies = $this->detectAnomalies($forecast, $summary);
        
        // Prepare chart data
        $chartData = $this->prepareChartData($summary, $forecast);
        
        // Get upcoming large payments
        $upcomingPayments = $this->getUpcomingLargePayments($forecast);
        
        // Get overdue receivables
        $overdueReceivables = $this->getOverdueReceivables($forecast);
        
        return view('accounting.cashflow-forecasts.show', compact(
            'forecast', 
            'summary', 
            'insights', 
            'anomalies', 
            'chartData',
            'upcomingPayments',
            'overdueReceivables'
        ));
    }

    /**
     * Build notes for a day's forecast items
     */
    private function buildDayNotes($items)
    {
        $notes = [];
        $inflowItems = $items->where('type', 'inflow')->sortByDesc('amount')->take(2);
        $outflowItems = $items->where('type', 'outflow')->sortByDesc('amount')->take(2);
        
        foreach ($inflowItems as $item) {
            $notes[] = $item->description;
        }
        
        foreach ($outflowItems as $item) {
            $notes[] = $item->description;
        }
        
        $remainingCount = $items->count() - $inflowItems->count() - $outflowItems->count();
        if ($remainingCount > 0) {
            $notes[] = "+{$remainingCount} more transaction" . ($remainingCount > 1 ? 's' : '');
        }
        
        return implode(', ', $notes);
    }

    /**
     * Generate AI insights for the forecast
     */
    private function generateAIInsights($forecast, $summary)
    {
        $insights = [];
        $startingBalance = $forecast->starting_cash_balance;
        $minBalance = $startingBalance;
        $minBalanceDate = null;
        $maxOutflowDay = null;
        $maxOutflowAmount = 0;
        $maxInflowDay = null;
        $maxInflowAmount = 0;
        
        foreach ($summary as $date => $data) {
            // Find minimum balance
            if ($data['closing_balance'] < $minBalance) {
                $minBalance = $data['closing_balance'];
                $minBalanceDate = $date;
            }
            
            // Find maximum outflow day
            if ($data['outflows'] > $maxOutflowAmount) {
                $maxOutflowAmount = $data['outflows'];
                $maxOutflowDay = $date;
            }
            
            // Find maximum inflow day
            if ($data['inflows'] > $maxInflowAmount) {
                $maxInflowAmount = $data['inflows'];
                $maxInflowDay = $date;
            }
        }
        
        // Insight 1: Cash position warning
        if ($minBalance < 0) {
            $insights[] = [
                'type' => 'danger',
                'icon' => 'bx-error-circle',
                'title' => 'Cash Deficit Warning',
                'message' => "Cash position expected to fall below zero on " . \Carbon\Carbon::parse($minBalanceDate)->format('d M Y') . " (TZS " . number_format(abs($minBalance), 2) . " deficit).",
            ];
        } elseif ($minBalance < ($startingBalance * 0.2)) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'bx-error-alt',
                'title' => 'Low Cash Buffer',
                'message' => "Cash position expected to drop to TZS " . number_format($minBalance, 2) . " on " . \Carbon\Carbon::parse($minBalanceDate)->format('d M Y') . ". Consider arranging additional funding.",
            ];
        }
        
        // Insight 2: Major inflow
        if ($maxInflowAmount > 0) {
            $insights[] = [
                'type' => 'success',
                'icon' => 'bx-trending-up',
                'title' => 'Major Inflow Expected',
                'message' => "Large inflow of TZS " . number_format($maxInflowAmount, 2) . " expected on " . \Carbon\Carbon::parse($maxInflowDay)->format('d M Y') . ".",
            ];
        }
        
        // Insight 3: Payment clustering
        $summaryCount = max(count($summary), 1); // Prevent division by zero
        if ($summaryCount > 0 && $maxOutflowAmount > ($forecast->getTotalOutflows() / $summaryCount * 2)) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'bx-info-circle',
                'title' => 'Payment Clustering',
                'message' => "Heavy payment day on " . \Carbon\Carbon::parse($maxOutflowDay)->format('d M Y') . " (TZS " . number_format($maxOutflowAmount, 2) . "). Consider deferring some payments if possible.",
            ];
        }
        
        // Insight 4: Ending balance
        $endingBalance = $forecast->starting_cash_balance + $forecast->getNetCashflow();
        if ($endingBalance < $startingBalance) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'bx-trending-down',
                'title' => 'Negative Net Cashflow',
                'message' => "Forecast shows net cash outflow of TZS " . number_format(abs($forecast->getNetCashflow()), 2) . " over the period. Ending balance will be TZS " . number_format($endingBalance, 2) . ".",
            ];
        }
        
        return $insights;
    }

    /**
     * Detect anomalies in the forecast
     */
    private function detectAnomalies($forecast, $summary)
    {
        $anomalies = [];
        $avgDailyOutflow = $forecast->getTotalOutflows() / max(count($summary), 1);
        $avgDailyInflow = $forecast->getTotalInflows() / max(count($summary), 1);
        
        foreach ($summary as $date => $data) {
            // Detect unusually high expense days
            if ($data['outflows'] > ($avgDailyOutflow * 2.5)) {
                $anomalies[] = [
                    'date' => $date,
                    'type' => 'high_expense',
                    'message' => "Unusually high expenses on " . \Carbon\Carbon::parse($date)->format('d M Y') . " (TZS " . number_format($data['outflows'], 2) . ")",
                    'amount' => $data['outflows'],
                ];
            }
            
            // Detect missing inflows
            if ($data['inflows'] == 0 && $data['outflows'] > 0 && $data['closing_balance'] < 0) {
                $anomalies[] = [
                    'date' => $date,
                    'type' => 'missing_inflow',
                    'message' => "No expected inflows on " . \Carbon\Carbon::parse($date)->format('d M Y') . " despite significant outflows.",
                    'amount' => $data['outflows'],
                ];
            }
        }
        
        return $anomalies;
    }

    /**
     * Prepare chart data for visualization
     */
    private function prepareChartData($summary, $forecast)
    {
        $dates = [];
        $balances = [];
        $inflows = [];
        $outflows = [];
        
        foreach ($summary as $date => $data) {
            $dates[] = \Carbon\Carbon::parse($date)->format('d M');
            $balances[] = (float) $data['closing_balance'];
            $inflows[] = (float) $data['inflows'];
            $outflows[] = (float) $data['outflows'];
        }
        
        return [
            'dates' => $dates,
            'balances' => $balances,
            'inflows' => $inflows,
            'outflows' => $outflows,
            'starting_balance' => (float) $forecast->starting_cash_balance,
        ];
    }

    /**
     * Get upcoming large payments
     */
    private function getUpcomingLargePayments($forecast)
    {
        $largePayments = $forecast->items()
            ->where('type', 'outflow')
            ->where('forecast_date', '>=', now())
            ->orderBy('amount', 'desc')
            ->orderBy('forecast_date')
            ->limit(10)
            ->get();
        
        return $largePayments;
    }

    /**
     * Get overdue receivables with AI predicted delay
     */
    private function getOverdueReceivables($forecast)
    {
        $overdue = $forecast->items()
            ->where('type', 'inflow')
            ->where('source_type', 'accounts_receivable')
            ->where('forecast_date', '<', now())
            ->orderBy('forecast_date')
            ->get();
        
        return $overdue;
    }

    /**
     * Generate AP/AR Cash Impact Report
     */
    public function apArCashImpact($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $forecast = CashflowForecast::with(['items'])->findOrFail($id);
        
        // Group by source type
        $arItems = $forecast->items()->where('source_type', 'accounts_receivable')->get();
        $apItems = $forecast->items()->where('source_type', 'accounts_payable')->get();
        
        // Calculate totals and aging
        $arSummary = [
            'total' => $arItems->sum('amount'),
            'by_probability' => [
                'high' => $arItems->where('probability', '>=', 80)->sum('amount'),
                'medium' => $arItems->where('probability', '>=', 50)->where('probability', '<', 80)->sum('amount'),
                'low' => $arItems->where('probability', '<', 50)->sum('amount'),
            ],
            'by_date_range' => [
                'current_week' => $arItems->where('forecast_date', '>=', now())->where('forecast_date', '<=', now()->addWeek())->sum('amount'),
                'next_week' => $arItems->where('forecast_date', '>', now()->addWeek())->where('forecast_date', '<=', now()->addWeeks(2))->sum('amount'),
                'next_month' => $arItems->where('forecast_date', '>', now()->addWeeks(2))->where('forecast_date', '<=', now()->addMonth())->sum('amount'),
            ],
        ];
        
        $apSummary = [
            'total' => $apItems->sum('amount'),
            'by_date_range' => [
                'current_week' => $apItems->where('forecast_date', '>=', now())->where('forecast_date', '<=', now()->addWeek())->sum('amount'),
                'next_week' => $apItems->where('forecast_date', '>', now()->addWeek())->where('forecast_date', '<=', now()->addWeeks(2))->sum('amount'),
                'next_month' => $apItems->where('forecast_date', '>', now()->addWeeks(2))->where('forecast_date', '<=', now()->addMonth())->sum('amount'),
            ],
        ];
        
        return view('accounting.cashflow-forecasts.ap-ar-impact', compact('forecast', 'arSummary', 'apSummary', 'arItems', 'apItems'));
    }

    /**
     * Compare Best-Case vs Worst-Case scenarios
     */
    public function scenarioComparison($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $baseForecast = CashflowForecast::findOrFail($id);
        
        // Get or create best-case and worst-case forecasts for comparison
        $bestCase = CashflowForecast::where('company_id', $baseForecast->company_id)
            ->where('branch_id', $baseForecast->branch_id)
            ->where('start_date', $baseForecast->start_date)
            ->where('end_date', $baseForecast->end_date)
            ->where('scenario', 'best_case')
            ->where('is_active', true)
            ->first();
        
        $worstCase = CashflowForecast::where('company_id', $baseForecast->company_id)
            ->where('branch_id', $baseForecast->branch_id)
            ->where('start_date', $baseForecast->start_date)
            ->where('end_date', $baseForecast->end_date)
            ->where('scenario', 'worst_case')
            ->where('is_active', true)
            ->first();
        
        $comparison = [
            'base_case' => [
                'net_cashflow' => $baseForecast->getNetCashflow(),
                'ending_balance' => $baseForecast->starting_cash_balance + $baseForecast->getNetCashflow(),
                'total_inflows' => $baseForecast->getTotalInflows(),
                'total_outflows' => $baseForecast->getTotalOutflows(),
            ],
            'best_case' => $bestCase ? [
                'net_cashflow' => $bestCase->getNetCashflow(),
                'ending_balance' => $bestCase->starting_cash_balance + $bestCase->getNetCashflow(),
                'total_inflows' => $bestCase->getTotalInflows(),
                'total_outflows' => $bestCase->getTotalOutflows(),
            ] : null,
            'worst_case' => $worstCase ? [
                'net_cashflow' => $worstCase->getNetCashflow(),
                'ending_balance' => $worstCase->starting_cash_balance + $worstCase->getNetCashflow(),
                'total_inflows' => $worstCase->getTotalInflows(),
                'total_outflows' => $worstCase->getTotalOutflows(),
            ] : null,
        ];
        
        return view('accounting.cashflow-forecasts.scenario-comparison', compact('baseForecast', 'bestCase', 'worstCase', 'comparison'));
    }

    /**
     * Regenerate forecast items
     */
    public function regenerate($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $forecast = CashflowForecast::findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            // Delete existing items
            $forecast->items()->delete();
            
            // Regenerate
            $this->forecastService->generateForecastItems($forecast);
            
            DB::commit();
            
            return back()->with('success', 'Forecast regenerated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to regenerate forecast: ' . $e->getMessage());
        }
    }

    /**
     * Export forecast to PDF
     */
    public function exportPdf($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $forecast = CashflowForecast::with(['items' => function($q) {
            $q->orderBy('forecast_date');
        }, 'branch', 'createdBy'])->findOrFail($id);
        
        // Group items by date for display
        $groupedItems = $forecast->items->groupBy('forecast_date');
        
        // Calculate running balance
        $runningBalance = $forecast->starting_cash_balance;
        $summary = [];
        
        foreach ($groupedItems->sortKeys() as $date => $items) {
            $inflows = $items->where('type', 'inflow')->sum('amount');
            $outflows = $items->where('type', 'outflow')->sum('amount');
            $net = $inflows - $outflows;
            $runningBalance += $net;
            
            $summary[$date] = [
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $net,
                'balance' => $runningBalance,
                'items' => $items,
            ];
        }
        
        $company = Auth::user()->company;
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting.cashflow-forecasts.exports.pdf', compact(
            'forecast', 'summary', 'company'
        ))->setPaper('a4', 'landscape');
        
        return $pdf->download('cashflow-forecast-' . $forecast->forecast_name . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export forecast to Excel
     */
    public function exportExcel($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $forecast = CashflowForecast::with(['items' => function($q) {
            $q->orderBy('forecast_date');
        }, 'branch', 'createdBy'])->findOrFail($id);
        
        // Group items by date for display
        $groupedItems = $forecast->items->groupBy('forecast_date');
        
        // Calculate running balance
        $runningBalance = $forecast->starting_cash_balance;
        $summary = [];
        
        foreach ($groupedItems->sortKeys() as $date => $items) {
            $inflows = $items->where('type', 'inflow')->sum('amount');
            $outflows = $items->where('type', 'outflow')->sum('amount');
            $net = $inflows - $outflows;
            $runningBalance += $net;
            
            $summary[$date] = [
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $net,
                'balance' => $runningBalance,
                'items' => $items,
            ];
        }
        
        $company = Auth::user()->company;
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CashflowForecastExport($forecast, $summary, $company),
            'cashflow-forecast-' . $forecast->forecast_name . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}

