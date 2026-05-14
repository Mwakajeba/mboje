<?php

namespace App\Http\Controllers\PeriodClosing;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Models\AccountingPeriod;
use App\Models\CloseBatch;
use App\Models\CloseAdjustment;
use App\Models\ChartAccount;
use App\Services\PeriodClosing\PeriodCloseService;
use App\Services\PeriodClosing\PeriodLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class PeriodClosingController extends Controller
{
    protected $periodCloseService;

    public function __construct(PeriodCloseService $periodCloseService)
    {
        $this->periodCloseService = $periodCloseService;
        $this->middleware('auth');
    }

    /**
     * AJAX endpoint to check if a given date is in a locked period
     */
    public function checkDateLock(Request $request, PeriodLockService $periodLockService)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $companyId = Auth::user()->company_id;
        $date = $validated['date'];

        $lockedPeriod = $periodLockService->getLockedPeriodForDate($date, $companyId);

        if ($lockedPeriod) {
            $message = sprintf(
                'The period %s is locked. Transactions dated between %s and %s are not allowed.',
                $lockedPeriod->period_label,
                $lockedPeriod->start_date->format('M d, Y'),
                $lockedPeriod->end_date->format('M d, Y')
            );

            return response()->json([
                'locked' => true,
                'message' => $message,
                'period_label' => $lockedPeriod->period_label,
                'start_date' => $lockedPeriod->start_date->toDateString(),
                'end_date' => $lockedPeriod->end_date->toDateString(),
            ]);
        }

        return response()->json([
            'locked' => false,
        ]);
    }

    /**
     * Display period closing dashboard
     */
    public function index()
    {
        $companyId = Auth::user()->company_id;
        
        $fiscalYears = FiscalYear::where('company_id', $companyId)
            ->with('periods')
            ->orderBy('start_date', 'desc')
            ->get();

        $currentPeriod = AccountingPeriod::whereHas('fiscalYear', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->where('status', 'OPEN')
        ->orderBy('end_date', 'desc')
        ->first();

        $pendingBatches = CloseBatch::where('company_id', $companyId)
            ->whereIn('status', ['DRAFT', 'REVIEW'])
            ->with(['period', 'preparedBy'])
            ->get();

        return view('settings.period-closing.index', compact(
            'fiscalYears',
            'currentPeriod',
            'pendingBatches'
        ));
    }

    /**
     * Show fiscal year management
     */
    public function fiscalYears()
    {
        return view('settings.period-closing.fiscal-years');
    }

    /**
     * Get fiscal years data for DataTables
     */
    public function fiscalYearsData(Request $request)
    {
        $companyId = Auth::user()->company_id;

        // Base query
        $baseQuery = FiscalYear::where('company_id', $companyId);

        // Get total records count
        $totalRecords = (clone $baseQuery)->count();

        // Apply search
        $searchQuery = clone $baseQuery;
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $searchQuery->where(function($q) use ($searchValue) {
                $q->where('fy_label', 'like', "%{$searchValue}%")
                  ->orWhereHas('createdBy', function($q2) use ($searchValue) {
                      $q2->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        // Check if we need to order by periods count (need withCount before counting)
        $needsPeriodCount = false;
        if ($request->has('order') && count($request->order) > 0) {
            $orderColumnIndex = (int) ($request->order[0]['column'] ?? 2);
            if ($orderColumnIndex === 5) {
                $needsPeriodCount = true;
                $searchQuery->withCount('periods');
            }
        }

        // Get filtered count
        $filteredRecords = $searchQuery->count();

        // Apply ordering
        $orderQuery = clone $searchQuery;
        if ($request->has('order') && count($request->order) > 0) {
            $orderColumnIndex = (int) ($request->order[0]['column'] ?? 2);
            $orderDirection = $request->order[0]['dir'] ?? 'desc';
            
            // Column mapping: index(0), fy_label(1), start_date(2), end_date(3), duration(4), periods(5), status(6), created_by(7), actions(8)
            switch ($orderColumnIndex) {
                case 1:
                    $orderQuery->orderBy('fy_label', $orderDirection);
                    break;
                case 2:
                    $orderQuery->orderBy('start_date', $orderDirection);
                    break;
                case 3:
                    $orderQuery->orderBy('end_date', $orderDirection);
                    break;
                case 5:
                    if (!$needsPeriodCount) {
                        $orderQuery->withCount('periods');
                    }
                    $orderQuery->orderBy('periods_count', $orderDirection);
                    break;
                case 6:
                    $orderQuery->orderBy('status', $orderDirection);
                    break;
                case 7:
                    // Order by created_by name using a subquery
                    $orderQuery->orderByRaw("(SELECT name FROM users WHERE users.id = fiscal_years.created_by) {$orderDirection}");
                    break;
                default:
                    $orderQuery->orderBy('start_date', 'desc');
                    break;
            }
        } else {
            $orderQuery->orderBy('start_date', 'desc');
        }

        // Apply pagination
        $start = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 25);
        $fiscalYears = $orderQuery->with(['periods', 'createdBy'])->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        foreach ($fiscalYears as $index => $fy) {
            // Calculate duration in days
            $days = $fy->start_date->diffInDays($fy->end_date) + 1; // +1 to include both start and end dates
            $months = $fy->start_date->diffInMonths($fy->end_date);
            $years = floor($days / 365);
            $remainingDays = $days % 365;
            $remainingMonths = floor($remainingDays / 30);
            $finalDays = $remainingDays % 30;
            
            // Format duration: show years, months, and days
            $durationParts = [];
            if ($years > 0) {
                $durationParts[] = $years . ' ' . ($years == 1 ? 'year' : 'years');
            }
            if ($remainingMonths > 0) {
                $durationParts[] = $remainingMonths . ' ' . ($remainingMonths == 1 ? 'month' : 'months');
            }
            if ($finalDays > 0 || count($durationParts) == 0) {
                $durationParts[] = $finalDays . ' ' . ($finalDays == 1 ? 'day' : 'days');
            }
            
            $duration = implode(', ', $durationParts) . ' (' . number_format($days) . ' days)';
            
            $data[] = [
                'index' => $start + $index + 1,
                'fy_label' => '<strong>' . e($fy->fy_label) . '</strong>',
                'start_date' => $fy->start_date->format('M d, Y'),
                'end_date' => $fy->end_date->format('M d, Y'),
                'duration' => $duration,
                'periods' => '<span class="badge bg-info">' . $fy->periods->count() . ' periods</span>',
                'status' => '<span class="badge bg-' . ($fy->status === 'OPEN' ? 'success' : 'secondary') . '">' . e($fy->status) . '</span>',
                'created_by' => e($fy->createdBy->name ?? 'N/A'),
                'actions' => '<div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-primary" 
                            onclick="viewPeriods(' . $fy->fy_id . ')" 
                            title="View Periods">
                        <i class="bx bx-list-ul me-1"></i> View Periods
                    </button>
                    <a href="' . route('settings.period-closing.fiscal-years.year-end-wizard', $fy->fy_id) . '" 
                       class="btn btn-sm btn-success" 
                       title="Year-End Closing Wizard">
                        <i class="bx bx-wizard me-1"></i> Year-End
                    </a>
                </div>',
            ];
        }

        return response()->json([
            'draw' => intval($request->draw ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Show periods management
     */
    public function periods(Request $request)
    {
        // If AJAX request, return DataTables data
        if ($request->ajax()) {
            return $this->periodsData($request);
        }

        return view('settings.period-closing.periods');
    }

    /**
     * Get periods data for DataTables
     */
    public function periodsData(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        $query = AccountingPeriod::whereHas('fiscalYear', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->with(['fiscalYear', 'lockedBy', 'closeBatches']);

        // Filter by fiscal year if provided
        if ($request->has('fy_id') && $request->fy_id) {
            $query->where('fy_id', $request->fy_id);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('period_label_formatted', function ($period) {
                return '<strong>' . e($period->period_label) . '</strong>';
            })
            ->addColumn('fiscal_year', function ($period) {
                return e($period->fiscalYear->fy_label ?? 'N/A');
            })
            ->addColumn('start_date_formatted', function ($period) {
                return $period->start_date->format('M d, Y');
            })
            ->addColumn('end_date_formatted', function ($period) {
                return $period->end_date->format('M d, Y');
            })
            ->addColumn('period_type_badge', function ($period) {
                return '<span class="badge bg-info">' . e($period->period_type) . '</span>';
            })
            ->addColumn('status_badge', function ($period) {
                $badgeClass = match($period->status) {
                    'OPEN' => 'success',
                    'LOCKED' => 'warning',
                    'CLOSED' => 'danger',
                    default => 'secondary'
                };
                return '<span class="badge bg-' . $badgeClass . '">' . e($period->status) . '</span>';
            })
            ->addColumn('locked_by_info', function ($period) {
                if ($period->lockedBy) {
                    $lockedBy = e($period->lockedBy->name);
                    $lockedAt = $period->locked_at ? '<br><small class="text-muted">' . $period->locked_at->format('M d, Y') . '</small>' : '';
                    return $lockedBy . $lockedAt;
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('actions', function ($period) {
                $actions = '<div class="btn-group" role="group">';
                
                if ($period->isLocked() || $period->isClosed()) {
                    if (auth()->user()->can('manage system settings')) {
                        $actions .= '<button type="button" class="btn btn-sm btn-warning reopen-btn" 
                                data-period-id="' . $period->period_id . '"
                                data-period-label="' . e($period->period_label) . '"
                                data-start-date="' . $period->start_date->format('M d, Y') . '"
                                data-end-date="' . $period->end_date->format('M d, Y') . '"
                                title="Reopen Period">
                            <i class="bx bx-lock-open"></i> Reopen
                        </button>';
                    }
                }
                
                if ($period->isOpen()) {
                    $actions .= '<a href="' . route('settings.period-closing.close-batch.create', $period) . '" 
                           class="btn btn-sm btn-primary" 
                           title="Create Close Batch">
                        <i class="bx bx-file"></i> Close
                    </a>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->filter(function ($query) use ($request) {
                // Global search
                if ($request->has('search.value') && !empty($request->search['value'])) {
                    $searchValue = $request->search['value'];
                    $query->where(function($q) use ($searchValue) {
                        $q->where('period_label', 'like', "%{$searchValue}%")
                          ->orWhere('period_type', 'like', "%{$searchValue}%")
                          ->orWhere('status', 'like', "%{$searchValue}%")
                          ->orWhereHas('fiscalYear', function($fyQuery) use ($searchValue) {
                              $fyQuery->where('fy_label', 'like', "%{$searchValue}%");
                          })
                          ->orWhereHas('lockedBy', function($userQuery) use ($searchValue) {
                              $userQuery->where('name', 'like', "%{$searchValue}%");
                          });
                    });
                }

                // Filter by fiscal year if provided
                if ($request->has('fy_id') && $request->fy_id) {
                    $query->where('fy_id', $request->fy_id);
                }
            })
            ->orderColumn('period_label', 'period_label $1')
            ->orderColumn('start_date', 'start_date $1')
            ->orderColumn('end_date', 'end_date $1')
            ->orderColumn('fiscal_year', function($query, $order) {
                $query->join('fiscal_years', 'accounting_periods.fy_id', '=', 'fiscal_years.fy_id')
                      ->orderBy('fiscal_years.fy_label', $order)
                      ->select('accounting_periods.*');
            })
            ->rawColumns(['period_label_formatted', 'period_type_badge', 'status_badge', 'locked_by_info', 'actions'])
            ->make(true);
    }

    /**
     * Get periods for a fiscal year (AJAX)
     */
    public function getPeriodsForFiscalYear(FiscalYear $fiscalYear)
    {
        // Verify fiscal year belongs to user's company
        if ($fiscalYear->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $periods = $fiscalYear->periods()
            ->with(['lockedBy', 'closeBatches'])
            ->orderBy('start_date', 'asc')
            ->get();

        $data = [];
        foreach ($periods as $period) {
            $data[] = [
                'period_label' => $period->period_label,
                'start_date' => $period->start_date->format('M d, Y'),
                'end_date' => $period->end_date->format('M d, Y'),
                'period_type' => $period->period_type,
                'status' => $period->status,
                'status_badge' => '<span class="badge bg-' . 
                    ($period->status === 'OPEN' ? 'success' : ($period->status === 'LOCKED' ? 'warning' : ($period->status === 'CLOSED' ? 'danger' : 'secondary'))) . 
                    '">' . e($period->status) . '</span>',
                'locked_by' => $period->lockedBy ? $period->lockedBy->name : '-',
                'locked_at' => $period->locked_at ? $period->locked_at->format('M d, Y H:i') : '-',
                'close_batches' => $period->closeBatches->count(),
                'actions' => '<div class="btn-group" role="group">
                    ' . ($period->isOpen() ? 
                        '<a href="' . route('settings.period-closing.close-batch.create', $period) . '" class="btn btn-sm btn-primary" title="Create Close Batch">
                            <i class="bx bx-file me-1"></i> Create Batch
                        </a>' : '') . '
                    ' . (($period->isLocked() || $period->isClosed()) ? 
                        '<a href="' . route('settings.period-closing.periods') . '?fy_id=' . $fiscalYear->fy_id . '" class="btn btn-sm btn-info" title="View Details">
                            <i class="bx bx-show me-1"></i> View
                        </a>' : '') . '
                </div>',
            ];
        }

        return response()->json([
            'success' => true,
            'fiscal_year' => [
                'fy_label' => $fiscalYear->fy_label,
                'start_date' => $fiscalYear->start_date->format('M d, Y'),
                'end_date' => $fiscalYear->end_date->format('M d, Y'),
            ],
            'periods' => $data,
            'total_periods' => $periods->count(),
        ]);
    }

    /**
     * Store new fiscal year
     */
    public function storeFiscalYear(Request $request)
    {
        $validated = $request->validate([
            'fy_label' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        DB::beginTransaction();
        try {
            $fiscalYear = FiscalYear::create([
                'company_id' => Auth::user()->company_id,
                'fy_label' => $validated['fy_label'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'OPEN',
                'created_by' => Auth::id(),
            ]);

            // Auto-generate periods (monthly by default)
            $this->generatePeriods($fiscalYear);

            DB::commit();

            return redirect()->route('settings.period-closing.fiscal-years')
                ->with('success', 'Fiscal year created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create fiscal year', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to create fiscal year: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Create close batch
     */
    public function createCloseBatch(AccountingPeriod $period)
    {
        $companyId = Auth::user()->company_id;

        // Verify period belongs to user's company
        if ($period->fiscalYear->company_id !== $companyId) {
            abort(403, 'Unauthorized');
        }

        // Check if previous periods are closed (sequential validation)
        $sequentialCheck = $this->periodCloseService->canClosePeriod($period);
        if (!$sequentialCheck['can_close']) {
            return redirect()->route('settings.period-closing.periods', ['fy_id' => $period->fy_id])
                ->with('error', $sequentialCheck['message']);
        }

        // Run pre-close checks
        $preCloseChecks = $this->periodCloseService->runPreCloseChecks($companyId, $period);

        $chartAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('account_code')
            ->get();

        return view('settings.period-closing.create-close-batch', compact(
            'period',
            'preCloseChecks',
            'chartAccounts'
        ));
    }

    /**
     * Store close batch
     */
    public function storeCloseBatch(Request $request, AccountingPeriod $period)
    {
        $validated = $request->validate([
            'batch_label' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id;

        // Verify period belongs to user's company
        if ($period->fiscalYear->company_id !== $companyId) {
            abort(403, 'Unauthorized');
        }

        // Check if previous periods are closed (sequential validation)
        $sequentialCheck = $this->periodCloseService->canClosePeriod($period);
        if (!$sequentialCheck['can_close']) {
            return redirect()->route('settings.period-closing.periods', ['fy_id' => $period->fy_id])
                ->with('error', $sequentialCheck['message']);
        }

        // Run pre-close checks and block if unposted journals exist
        $preCloseChecks = $this->periodCloseService->runPreCloseChecks($companyId, $period);
        
        if (!$preCloseChecks['all_passed']) {
            $failedChecks = collect($preCloseChecks['checks'])->filter(fn($check) => !$check['passed']);
            $errorMessages = $failedChecks->pluck('message')->implode(', ');
            
            return redirect()->route('settings.period-closing.close-batch.create', $period)
                ->with('error', 'Cannot create close batch. Please resolve the following issues: ' . $errorMessages)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $closeBatch = CloseBatch::create([
                'company_id' => $companyId,
                'period_id' => $period->period_id,
                'batch_label' => $validated['batch_label'],
                'status' => 'DRAFT',
                'prepared_by' => Auth::id(),
                'prepared_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('settings.period-closing.close-batch.show', $closeBatch)
                ->with('success', 'Close batch created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create close batch', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to create close batch: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show close batch details
     */
    public function showCloseBatch(CloseBatch $closeBatch)
    {
        $closeBatch->load([
            'period.fiscalYear',
            'adjustments.debitAccount',
            'adjustments.creditAccount',
            'snapshots.account',
            'preparedBy',
            'reviewedBy',
            'approvedBy'
        ]);

        $chartAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($query) {
                $query->where('company_id', Auth::user()->company_id);
            })
            ->orderBy('account_code')
            ->get();

        return view('settings.period-closing.close-batch', compact('closeBatch', 'chartAccounts'));
    }

    /**
     * Get snapshots data for DataTables (AJAX)
     */
    public function snapshotsData(Request $request, CloseBatch $closeBatch)
    {
        // Verify close batch belongs to user's company
        if ($closeBatch->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $snapshots = $closeBatch->snapshots()
            ->with('account')
            ->orderBy('account_id', 'asc');

        // Filter zero-balance accounts if requested
        if ($request->has('hide_zero_balance') && $request->hide_zero_balance == 'true') {
            $snapshots->where(function($query) {
                $query->where('opening_balance', '!=', 0)
                      ->orWhere('period_debits', '!=', 0)
                      ->orWhere('period_credits', '!=', 0)
                      ->orWhere('closing_balance', '!=', 0);
            });
        }

        // Apply search
        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $snapshots->whereHas('account', function($query) use ($search) {
                $query->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        $totalRecords = $closeBatch->snapshots()->count();
        $filteredRecords = $snapshots->count();

        // Apply pagination
        $start = intval($request->start ?? 0);
        $length = intval($request->length ?? 25);
        $snapshots = $snapshots->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = [];
        foreach ($snapshots as $snapshot) {
            $account = $snapshot->account;
            $data[] = [
                'account_code' => $account ? $account->account_code : 'N/A',
                'account_name' => $account ? $account->account_name : 'N/A',
                'account' => $account ? 
                    '<strong>' . e($account->account_code) . '</strong><br><small class="text-muted">' . e($account->account_name) . '</small>' : 
                    'N/A',
                'opening_balance' => number_format($snapshot->opening_balance, 2),
                'period_debits' => '<span class="text-success">' . number_format($snapshot->period_debits, 2) . '</span>',
                'period_credits' => '<span class="text-danger">' . number_format($snapshot->period_credits, 2) . '</span>',
                'closing_balance' => '<strong>' . number_format($snapshot->closing_balance, 2) . '</strong>',
            ];
        }

        return response()->json([
            'draw' => intval($request->draw ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Add adjustment to close batch
     */
    public function addAdjustment(Request $request, CloseBatch $closeBatch)
    {
        $validated = $request->validate([
            'adj_date' => 'required|date',
            'gl_debit_account' => 'required|exists:chart_accounts,id',
            'gl_credit_account' => 'required|exists:chart_accounts,id|different:gl_debit_account',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'source_document' => 'nullable|string|max:200',
        ]);

        DB::beginTransaction();
        try {
            CloseAdjustment::create([
                'close_id' => $closeBatch->close_id,
                'adj_date' => $validated['adj_date'],
                'gl_debit_account' => $validated['gl_debit_account'],
                'gl_credit_account' => $validated['gl_credit_account'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'source_document' => $validated['source_document'] ?? null,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Adjustment added successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add adjustment', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to add adjustment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete adjustment from close batch
     */
    public function deleteAdjustment(CloseBatch $closeBatch, CloseAdjustment $closeAdjustment)
    {
        // Verify adjustment belongs to this close batch
        if ($closeAdjustment->close_id !== $closeBatch->close_id) {
            abort(403, 'Unauthorized');
        }

        // Only allow deletion if batch is in draft status
        if (!$closeBatch->isDraft()) {
            return redirect()->back()
                ->with('error', 'Adjustments can only be deleted from draft batches');
        }

        // Check if adjustment has been posted
        if ($closeAdjustment->posted_journal_id) {
            return redirect()->back()
                ->with('error', 'Cannot delete adjustment that has already been posted to GL');
        }

        DB::beginTransaction();
        try {
            $closeAdjustment->delete();

            DB::commit();

            return redirect()->back()
                ->with('success', 'Adjustment deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete adjustment', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Submit for review
     */
    public function submitForReview(CloseBatch $closeBatch)
    {
        if (!$closeBatch->isDraft()) {
            return redirect()->back()
                ->with('error', 'Only draft batches can be submitted for review');
        }

        DB::beginTransaction();
        try {
            // Generate snapshot
            $this->periodCloseService->generateSnapshot($closeBatch);

            $closeBatch->update([
                'status' => 'REVIEW',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Close batch submitted for review');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit for review', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to submit for review: ' . $e->getMessage());
        }
    }

    /**
     * Approve and lock period
     */
    public function approve(CloseBatch $closeBatch)
    {
        // Only system configuration managers / admins can approve and lock periods
        if (!auth()->user()->can('manage system settings') &&
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to approve and lock accounting periods.');
        }

        if (!$closeBatch->isInReview()) {
            return redirect()->back()
                ->with('error', 'Only batches in review can be approved');
        }

        DB::beginTransaction();
        try {
            $userId = Auth::id();
            
            // Post adjustments as journal entries (if any)
            // Pass approved_by (userId) to avoid using null value
            if ($closeBatch->adjustments()->count() > 0) {
                $this->periodCloseService->postAdjustments($closeBatch, $userId);
            }

            // Lock the period (sets status to LOCKED - blocks new transactions)
            $this->periodCloseService->lockPeriod($closeBatch->period, $userId);

            // Update batch status to LOCKED after successful operations
            $closeBatch->status = 'LOCKED';
            $closeBatch->approved_by = $userId;
            $closeBatch->approved_at = now();
            $closeBatch->save();

            // Refresh the model to ensure status is updated
            $closeBatch->refresh();

            // Note: Period remains LOCKED (not CLOSED) to allow for potential reopening if needed
            // CLOSED status can be set manually later for year-end periods or when fully finalized

            DB::commit();

            return redirect()->route('settings.period-closing.index')
                ->with('success', 'Period locked successfully. No new transactions can be posted to this period.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve close batch', [
                'close_batch_id' => $closeBatch->close_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

    /**
     * Generate periods for fiscal year
     */
    private function generatePeriods(FiscalYear $fiscalYear)
    {
        $start = Carbon::parse($fiscalYear->start_date);
        $end = Carbon::parse($fiscalYear->end_date);
        $current = $start->copy();

        $periodNumber = 1;
        while ($current->lte($end)) {
            $periodEnd = $current->copy()->endOfMonth();
            if ($periodEnd->gt($end)) {
                $periodEnd = $end->copy();
            }

            AccountingPeriod::create([
                'fy_id' => $fiscalYear->fy_id,
                'period_label' => $current->format('Y-m'),
                'start_date' => $current->copy()->startOfMonth(),
                'end_date' => $periodEnd,
                'period_type' => 'MONTH',
                'status' => 'OPEN',
            ]);

            $current->addMonth()->startOfMonth();
            $periodNumber++;
        }
    }

    /**
     * Roll P&L to retained earnings (year-end closing)
     */
    public function rollRetainedEarnings(CloseBatch $closeBatch)
    {
        // Only system configuration managers / admins can roll retained earnings
        if (!auth()->user()->can('manage system settings') &&
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to roll retained earnings.');
        }

        if (!$closeBatch->isLocked()) {
            return redirect()->back()
                ->with('error', 'Only locked close batches can have retained earnings rolled.');
        }

        // Check if this is the last period of the fiscal year
        if (!$this->periodCloseService->isLastPeriodOfFiscalYear($closeBatch->period)) {
            return redirect()->back()
                ->with('error', 'Retained earnings roll can only be performed on the last period of a fiscal year.');
        }

        DB::beginTransaction();
        try {
            $journal = $this->periodCloseService->rollToRetainedEarnings(
                $closeBatch->period->fiscalYear,
                Auth::id()
            );

            DB::commit();

            return redirect()->back()
                ->with('success', "Retained earnings rolled successfully. Journal Entry: {$journal->reference}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to roll retained earnings', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to roll retained earnings: ' . $e->getMessage());
        }
    }

    /**
     * Reopen a locked/closed period
     */
    public function reopenPeriod(AccountingPeriod $period)
    {
        // Only system configuration managers / admins can reopen periods
        if (!auth()->user()->can('manage system settings') &&
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to reopen accounting periods.');
        }

        $companyId = Auth::user()->company_id;

        // Verify period belongs to user's company
        if ($period->fiscalYear->company_id !== $companyId) {
            abort(403, 'Unauthorized');
        }

        $validated = request()->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $this->periodCloseService->reopenPeriod(
                $period,
                Auth::id(),
                $validated['reason'] ?? null
            );

            DB::commit();

            return redirect()->route('settings.period-closing.index')
                ->with('success', "Period {$period->period_label} has been reopened successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reopen period', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to reopen period: ' . $e->getMessage());
        }
    }

    /**
     * Show year-end closing wizard
     */
    public function yearEndWizard(FiscalYear $fiscalYear)
    {
        $companyId = Auth::user()->company_id;

        // Verify fiscal year belongs to user's company
        if ($fiscalYear->company_id !== $companyId) {
            abort(403, 'Unauthorized');
        }

        // Get all open periods with their closing status
        $openPeriods = $this->periodCloseService->getOpenPeriodsForFiscalYear($fiscalYear);

        // Get closed periods count
        $closedCount = $fiscalYear->periods()
            ->whereIn('status', ['CLOSED', 'LOCKED'])
            ->count();

        $totalPeriods = $fiscalYear->periods()->count();
        $progress = $totalPeriods > 0 ? round(($closedCount / $totalPeriods) * 100, 1) : 0;

        return view('settings.period-closing.year-end-wizard', compact(
            'fiscalYear',
            'openPeriods',
            'closedCount',
            'totalPeriods',
            'progress'
        ));
    }

    /**
     * Get period closing status (AJAX for wizard)
     */
    public function getPeriodClosingStatus(FiscalYear $fiscalYear)
    {
        $companyId = Auth::user()->company_id;

        if ($fiscalYear->company_id !== $companyId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $openPeriods = $this->periodCloseService->getOpenPeriodsForFiscalYear($fiscalYear);
        $closedCount = $fiscalYear->periods()
            ->whereIn('status', ['CLOSED', 'LOCKED'])
            ->count();
        $totalPeriods = $fiscalYear->periods()->count();
        $progress = $totalPeriods > 0 ? round(($closedCount / $totalPeriods) * 100, 1) : 0;

        return response()->json([
            'open_periods' => array_map(function($item) {
                return [
                    'period_id' => $item['period']->period_id,
                    'period_label' => $item['period']->period_label,
                    'start_date' => $item['period']->start_date->format('M d, Y'),
                    'end_date' => $item['period']->end_date->format('M d, Y'),
                    'can_close' => $item['can_close'],
                    'unclosed_periods' => $item['unclosed_periods'],
                    'has_close_batch' => $item['has_close_batch'],
                ];
            }, $openPeriods),
            'closed_count' => $closedCount,
            'total_periods' => $totalPeriods,
            'progress' => $progress,
        ]);
    }

    /**
     * Download Period-End Closing User Guide
     */
    public function downloadGuide()
    {
        $filePath = base_path('PERIOD_END_CLOSING_GUIDE.pdf');
        
        if (!file_exists($filePath)) {
            return redirect()->back()
                ->with('error', 'User guide file not found.');
        }

        return response()->download($filePath, 'PERIOD_END_CLOSING_GUIDE.pdf', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
