<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccrualSchedule;
use App\Models\AccrualJournal;
use App\Models\AccrualApproval;
use App\Models\ApprovalLevel;
use App\Models\ApprovalLevelAssignment;
use App\Models\ChartAccount;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\AccrualScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notification as BaseNotification;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AccrualsPrepaymentsController extends Controller
{
    protected $scheduleService;
    private const APPROVAL_MODULE = 'accruals_prepayments';

    public function __construct(AccrualScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Display a listing of accrual schedules.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getSchedulesData($request);
        }
        
        // Calculate statistics
        $totalSchedules = AccrualSchedule::forCompany($companyId)->count();
        $activeSchedules = AccrualSchedule::forCompany($companyId)->where('status', 'active')->count();
        $totalAmount = AccrualSchedule::forCompany($companyId)->sum('total_amount');
        $remainingAmount = AccrualSchedule::forCompany($companyId)->sum('remaining_amount');
        $amortisedAmount = AccrualSchedule::forCompany($companyId)->sum('amortised_amount');
        
        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        
        return view('accounting.accruals-prepayments.index', compact(
            'totalSchedules', 
            'activeSchedules', 
            'totalAmount', 
            'remainingAmount', 
            'amortisedAmount',
            'branches'
        ));
    }

    /**
     * Get schedules data for DataTables
     */
    private function getSchedulesData(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $query = AccrualSchedule::forCompany($companyId)
            ->with(['branch', 'expenseIncomeAccount', 'balanceSheetAccount', 'preparedBy', 'approvedBy']);
        
        // Apply filters
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('schedule_type')) {
            $query->where('schedule_type', $request->schedule_type);
        }
        
        if ($request->filled('nature')) {
            $query->where('nature', $request->nature);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('schedule_number_link', function ($schedule) {
                return '<a href="' . route('accounting.accruals-prepayments.show', $schedule->encoded_id) . '" class="text-primary fw-bold">' . $schedule->schedule_number . '</a>';
            })
            ->addColumn('category_name', function ($schedule) {
                return $schedule->category_name;
            })
            ->addColumn('formatted_start_date', function ($schedule) {
                return $schedule->start_date->format('M d, Y');
            })
            ->addColumn('formatted_end_date', function ($schedule) {
                return $schedule->end_date->format('M d, Y');
            })
            ->addColumn('formatted_total_amount', function ($schedule) {
                return number_format($schedule->total_amount, 2) . ' ' . $schedule->currency_code;
            })
            ->addColumn('formatted_remaining_amount', function ($schedule) {
                return number_format($schedule->remaining_amount, 2) . ' ' . $schedule->currency_code;
            })
            ->addColumn('status_badge', function ($schedule) {
                $badgeClass = match($schedule->status) {
                    'draft' => 'bg-secondary',
                    'submitted' => 'bg-info',
                    'approved' => 'bg-primary',
                    'active' => 'bg-success',
                    'completed' => 'bg-dark',
                    'cancelled' => 'bg-danger',
                    default => 'bg-secondary',
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($schedule->status) . '</span>';
            })
            ->addColumn('actions', function ($schedule) {
                $actions = '<div class="d-flex gap-1">';
                $actions .= '<a href="' . route('accounting.accruals-prepayments.show', $schedule->encoded_id) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                
                if ($schedule->canBeEdited()) {
                    $actions .= '<a href="' . route('accounting.accruals-prepayments.edit', $schedule->encoded_id) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                if ($schedule->canBeCancelled()) {
                    $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteSchedule(\'' . $schedule->encoded_id . '\')" title="Delete"><i class="bx bx-trash"></i></button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['schedule_number_link', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new schedule.
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $branches = Branch::where('company_id', $companyId)->get();
        $suppliers = Supplier::where('company_id', $companyId)->get();
        $customers = Customer::where('company_id', $companyId)->get();
        
        // Get P&L accounts (expense/income accounts) using joins
        $expenseIncomeAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['expense', 'expenses', 'income', 'revenue'])
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();
        
        // Get balance sheet accounts (asset/liability accounts) using joins
        $balanceSheetAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['assets', 'liabilities'])
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();
        
        // Get bank accounts for prepayment payment method
        // Filter by company through chart account relationship (same pattern as other controllers)
        // Also include bank accounts with direct company_id if they exist
        $bankAccounts = \App\Models\BankAccount::where(function($query) use ($companyId) {
                $query->whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    })
                    ->orWhere('company_id', $companyId);
            })
            ->orderBy('name')
            ->get();
        
        return view('accounting.accruals-prepayments.create', compact(
            'branches', 'suppliers', 'customers', 'expenseIncomeAccounts', 'balanceSheetAccounts', 'bankAccounts'
        ));
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_type' => 'required|in:prepayment,accrual',
            'nature' => 'required|in:expense,income',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'total_amount' => 'required|numeric|min:0.01',
            'expense_income_account_id' => 'required|exists:chart_accounts,id',
            'balance_sheet_account_id' => 'required|exists:chart_accounts,id',
            'frequency' => 'required|in:monthly,quarterly,custom',
            'custom_periods' => 'nullable|integer|min:1',
            'vendor_id' => 'nullable|exists:suppliers,id',
            'customer_id' => 'nullable|exists:customers,id',
            'currency_code' => 'required|string|size:3',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
            // Payment fields (required for prepayments)
            'payment_method' => 'nullable|required_if:schedule_type,prepayment|in:bank,cash',
            'bank_account_id' => 'nullable|required_if:payment_method,bank|exists:bank_accounts,id',
            'payment_date' => 'nullable|date',
        ]);
        
        $user = Auth::user();
        $companyId = $user->company_id;
        
        DB::beginTransaction();
        try {
            // Get FX rate at creation
            $fxRate = $this->scheduleService->getFxRate($validated['currency_code'], Carbon::parse($validated['start_date']));
            $homeCurrencyAmount = $validated['total_amount'] * $fxRate;
            
            $schedule = new AccrualSchedule();
            $schedule->schedule_number = $this->scheduleService->generateScheduleNumber($companyId);
            $schedule->schedule_type = $validated['schedule_type'];
            $schedule->nature = $validated['nature'];
            $schedule->start_date = $validated['start_date'];
            $schedule->end_date = $validated['end_date'];
            $schedule->total_amount = $validated['total_amount'];
            $schedule->remaining_amount = $validated['total_amount'];
            $schedule->amortised_amount = 0;
            $schedule->expense_income_account_id = $validated['expense_income_account_id'];
            $schedule->balance_sheet_account_id = $validated['balance_sheet_account_id'];
            $schedule->frequency = $validated['frequency'];
            $schedule->custom_periods = $validated['custom_periods'] ?? null;
            $schedule->vendor_id = $validated['vendor_id'] ?? null;
            $schedule->customer_id = $validated['customer_id'] ?? null;
            $schedule->currency_code = $validated['currency_code'];
            $schedule->payment_method = $validated['payment_method'] ?? null;
            $schedule->bank_account_id = $validated['bank_account_id'] ?? null;
            $schedule->payment_date = $validated['payment_date'] ?? $validated['start_date'];
            $schedule->fx_rate_at_creation = $fxRate;
            $schedule->home_currency_amount = $homeCurrencyAmount;
            $schedule->description = $validated['description'];
            $schedule->notes = $validated['notes'] ?? null;
            $schedule->prepared_by = $user->id;
            $schedule->company_id = $companyId;
            $schedule->branch_id = $validated['branch_id'] ?? null;
            $schedule->created_by = $user->id;
            $schedule->status = 'draft';
            $schedule->save();
            
            // Generate amortisation schedule and journals
            $this->scheduleService->generateJournals($schedule);
            
            DB::commit();
            
            return redirect()->route('accounting.accruals-prepayments.show', $schedule->encoded_id)
                ->with('success', 'Accrual schedule created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create schedule: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified schedule.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::with([
            'branch', 'vendor', 'customer', 'expenseIncomeAccount', 'balanceSheetAccount',
            'preparedBy', 'approvedBy', 'createdBy',
            'journals.journal', 'approvals.approver'
        ])->findOrFail($id);
        
        if ($schedule->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Approval context (multi-level, configured in Settings -> Approval Levels)
        $companyId = Auth::user()->company_id;
        $currentApprovalLevel = $this->getCurrentApprovalLevelForSchedule($schedule, $companyId);
        $approvalLevelsConfigured = $this->hasApprovalLevelsConfigured($companyId);
        $canApprove = $approvalLevelsConfigured
            ? ($currentApprovalLevel ? $this->canUserApproveAtLevel($schedule, $currentApprovalLevel, Auth::id()) : false)
            : true; // Backward-compatible: if no levels configured, any user can approve like before
        $pendingApprovers = $approvalLevelsConfigured && $currentApprovalLevel
            ? $this->getApproversForLevel($schedule, $currentApprovalLevel)->pluck('name')->values()
            : collect();

        // Approval history (all rounds)
        $approvalHistory = AccrualApproval::where('accrual_schedule_id', $schedule->id)
            ->with('approver')
            ->orderBy('approval_round', 'desc')
            ->orderBy('approval_level', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get amortisation schedule preview
        $amortisationSchedule = $this->scheduleService->calculateAmortisationSchedule($schedule);
        
        return view('accounting.accruals-prepayments.show', compact(
            'schedule',
            'amortisationSchedule',
            'approvalLevelsConfigured',
            'currentApprovalLevel',
            'canApprove',
            'pendingApprovers',
            'approvalHistory'
        ));
    }

    /**
     * Show the form for editing the specified schedule.
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::findOrFail($id);
        
        if ($schedule->company_id != Auth::user()->company_id) {
            abort(403);
        }
        
        if (!$schedule->canBeEdited()) {
            return redirect()->route('accounting.accruals-prepayments.show', $schedule->encoded_id)
                ->with('error', 'This schedule cannot be edited.');
        }
        
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $branches = Branch::where('company_id', $companyId)->get();
        $suppliers = Supplier::where('company_id', $companyId)->get();
        $customers = Customer::where('company_id', $companyId)->get();
        
        // Get P&L accounts (expense/income accounts) using joins
        $expenseIncomeAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['expense', 'expenses', 'income', 'revenue'])
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();
        
        // Get balance sheet accounts (asset/liability accounts) using joins
        $balanceSheetAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['assets', 'liabilities'])
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();
        
        return view('accounting.accruals-prepayments.edit', compact(
            'schedule', 'branches', 'suppliers', 'customers', 'expenseIncomeAccounts', 'balanceSheetAccounts'
        ));
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::findOrFail($id);
        
        if ($schedule->company_id != Auth::user()->company_id) {
            abort(403);
        }
        
        if (!$schedule->canBeEdited()) {
            return redirect()->route('accounting.accruals-prepayments.show', $schedule->encoded_id)
                ->with('error', 'This schedule cannot be edited.');
        }
        
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'total_amount' => 'required|numeric|min:0.01',
            'expense_income_account_id' => 'required|exists:chart_accounts,id',
            'balance_sheet_account_id' => 'required|exists:chart_accounts,id',
            'frequency' => 'required|in:monthly,quarterly,custom',
            'custom_periods' => 'nullable|integer|min:1',
            'vendor_id' => 'nullable|exists:suppliers,id',
            'customer_id' => 'nullable|exists:customers,id',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        
        DB::beginTransaction();
        try {
            $schedule->start_date = $validated['start_date'];
            $schedule->end_date = $validated['end_date'];
            $schedule->total_amount = $validated['total_amount'];
            $schedule->expense_income_account_id = $validated['expense_income_account_id'];
            $schedule->balance_sheet_account_id = $validated['balance_sheet_account_id'];
            $schedule->frequency = $validated['frequency'];
            $schedule->custom_periods = $validated['custom_periods'] ?? null;
            $schedule->vendor_id = $validated['vendor_id'] ?? null;
            $schedule->customer_id = $validated['customer_id'] ?? null;
            $schedule->description = $validated['description'];
            $schedule->notes = $validated['notes'] ?? null;
            $schedule->branch_id = $validated['branch_id'] ?? null;
            $schedule->updated_by = Auth::id();
            $schedule->save();
            
            // Recalculate schedule
            $this->scheduleService->recalculateSchedule($schedule);
            
            DB::commit();
            
            return redirect()->route('accounting.accruals-prepayments.show', $schedule->encoded_id)
                ->with('success', 'Schedule updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update schedule: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::findOrFail($id);
        
        if ($schedule->company_id != Auth::user()->company_id) {
            abort(403);
        }
        
        // Check if schedule can be deleted (only draft and submitted, not approved/active)
        if (!$schedule->canBeCancelled()) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This schedule cannot be deleted. Only draft and submitted schedules can be deleted.'
                ], 403);
            }
            return back()->with('error', 'This schedule cannot be deleted. Only draft and submitted schedules can be deleted.');
        }
        
        // Only allow deletion if no journals are posted
        $postedJournals = AccrualJournal::where('accrual_schedule_id', $schedule->id)
            ->where('status', 'posted')
            ->count();
        
        if ($postedJournals > 0) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete schedule with posted journals. Cancel it instead.'
                ], 403);
            }
            return back()->with('error', 'Cannot delete schedule with posted journals. Cancel it instead.');
        }
        
        // Check if initial journal exists (for prepayments) - means it's been approved
        if ($schedule->initial_journal_id) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete schedule with initial payment journal. This schedule has been approved and activated.'
                ], 403);
            }
            return back()->with('error', 'Cannot delete schedule with initial payment journal. This schedule has been approved and activated.');
        }
        
        try {
            DB::beginTransaction();
            
            // Delete all pending journals first
            AccrualJournal::where('accrual_schedule_id', $schedule->id)
                ->where('status', 'pending')
                ->delete();
            
            // Delete the schedule
            $schedule->delete();
            
            DB::commit();
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Schedule deleted successfully.'
                ]);
            }
        
        return redirect()->route('accounting.accruals-prepayments.index')
            ->with('success', 'Schedule deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete schedule: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete schedule: ' . $e->getMessage());
        }
    }

    /**
     * Submit schedule for approval
     */
    public function submit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::findOrFail($id);
        
        if ($schedule->status !== 'draft') {
            return back()->with('error', 'Only draft schedules can be submitted.');
        }

        DB::beginTransaction();
        try {
            // If there is any approval history in the current round, increment to a new round on resubmission
            $currentRound = (int) ($schedule->approval_round ?? 1);
            $hasHistoryThisRound = AccrualApproval::where('accrual_schedule_id', $schedule->id)
                ->where('approval_round', $currentRound)
                ->exists();
            if ($hasHistoryThisRound) {
                $schedule->approval_round = $currentRound + 1;
            } elseif (!$schedule->approval_round) {
                $schedule->approval_round = 1;
            }

            $schedule->status = 'submitted';
            $schedule->approved_by = null;
            $schedule->approved_at = null;
            $schedule->save();

            DB::commit();

            // Notify current level approvers (database notification)
            $companyId = Auth::user()->company_id;
            if ($this->hasApprovalLevelsConfigured($companyId)) {
                $level = $this->getCurrentApprovalLevelForSchedule($schedule, $companyId);
                if ($level) {
                    $approvers = $this->getApproversForLevel($schedule, $level);
                    if ($approvers->isNotEmpty()) {
                        $this->notifyUsers(
                            $approvers,
                            'Accrual Schedule Approval Required',
                            "Accrual schedule {$schedule->schedule_number} is pending approval at Level {$level->level} ({$level->level_name}).",
                            route('accounting.accruals-prepayments.show', $schedule->encoded_id)
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
        
        return back()->with('success', 'Schedule submitted for approval.');
    }

    /**
     * Approve schedule
     */
    public function approve(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::findOrFail($id);
        
        if (!$schedule->canBeApproved()) {
            return back()->with('error', 'Schedule cannot be approved.');
        }

        $comments = trim((string) $request->input('comments', ''));

        $companyId = Auth::user()->company_id;
        $approvalLevelsConfigured = $this->hasApprovalLevelsConfigured($companyId);

        DB::beginTransaction();
        try {
            // Backward-compatible: if no approval levels configured, allow direct approval like before
            if (!$approvalLevelsConfigured) {
                // Record approval for history (single-step)
                AccrualApproval::create([
                    'accrual_schedule_id' => $schedule->id,
                    'approval_level' => 1,
                    'approver_id' => Auth::id(),
                    'approval_round' => (int) ($schedule->approval_round ?? 1),
                    'status' => 'approved',
                    'comments' => $comments ?: null,
                    'approved_at' => now(),
                ]);
                $this->finalizeScheduleApproval($schedule, Auth::id());
                DB::commit();
                return back()->with('success', 'Schedule approved and activated.');
            }

            $currentLevel = $this->getCurrentApprovalLevelForSchedule($schedule, $companyId);

            // If all required levels are effectively complete (e.g., levels exist but no approvers assigned), finalize
            if (!$currentLevel) {
                $this->finalizeScheduleApproval($schedule, Auth::id());
                DB::commit();
                return back()->with('success', 'Schedule fully approved and activated.');
            }

            if (!$this->canUserApproveAtLevel($schedule, $currentLevel, Auth::id())) {
                DB::rollBack();
                return back()->with('error', 'You are not allowed to approve this schedule at the current approval level.');
            }

            // Record approval for this approver at this level (idempotent)
            AccrualApproval::updateOrCreate(
                [
                    'accrual_schedule_id' => $schedule->id,
                    'approval_level' => $currentLevel->level,
                    'approver_id' => Auth::id(),
                    'approval_round' => (int) ($schedule->approval_round ?? 1),
                ],
                [
                    'status' => 'approved',
                    'comments' => $comments ?: null,
                    'rejection_reason' => null,
                    'approved_at' => now(),
                    'rejected_at' => null,
                ]
            );

            // Re-evaluate if all levels are now approved; if yes -> finalize, else just stay submitted
            $nextPending = $this->getCurrentApprovalLevelForSchedule($schedule, $companyId);
            if (!$nextPending) {
                $this->finalizeScheduleApproval($schedule, Auth::id());
                DB::commit();

                // Notify submitter/preparer
                $submitter = $schedule->preparedBy ?? $schedule->createdBy ?? null;
                if ($submitter) {
                    $this->notifyUsers(
                        collect([$submitter]),
                        'Accrual Schedule Fully Approved',
                        "Accrual schedule {$schedule->schedule_number} has been fully approved and activated.",
                        route('accounting.accruals-prepayments.show', $schedule->encoded_id)
                    );
                }

                return back()->with('success', 'Schedule fully approved and activated.');
            }

            DB::commit();

            // Notify next level approvers
            $approvers = $this->getApproversForLevel($schedule, $nextPending);
            if ($approvers->isNotEmpty()) {
                $this->notifyUsers(
                    $approvers,
                    'Accrual Schedule Approval Required',
                    "Accrual schedule {$schedule->schedule_number} is pending approval at Level {$nextPending->level} ({$nextPending->level_name}).",
                    route('accounting.accruals-prepayments.show', $schedule->encoded_id)
                );
            }

            return back()->with('success', "Approval recorded for Level {$currentLevel->level}. Awaiting further approvals (next: Level {$nextPending->level}).");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve schedule: ' . $e->getMessage());
        }
    }

    /**
     * Reject schedule
     */
    public function reject($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::findOrFail($id);
        
        if ($schedule->status !== 'submitted') {
            return back()->with('error', 'Only submitted schedules can be rejected.');
        }

        $reason = (string) $request->input('reason', '');
        $companyId = Auth::user()->company_id;
        $approvalLevelsConfigured = $this->hasApprovalLevelsConfigured($companyId);

        DB::beginTransaction();
        try {
            if ($approvalLevelsConfigured) {
                $currentLevel = $this->getCurrentApprovalLevelForSchedule($schedule, $companyId);
                if (!$currentLevel) {
                    // Nothing pending (already complete), don't allow reject
                    DB::rollBack();
                    return back()->with('error', 'This schedule has no pending approval level to reject.');
                }
                if (!$this->canUserApproveAtLevel($schedule, $currentLevel, Auth::id())) {
                    DB::rollBack();
                    return back()->with('error', 'You are not allowed to reject this schedule at the current approval level.');
                }
            }

            // Record rejection (keeps audit trail even after status resets to draft)
            AccrualApproval::create([
                'accrual_schedule_id' => $schedule->id,
                'approval_level' => $approvalLevelsConfigured
                    ? ($this->getCurrentApprovalLevelForSchedule($schedule, $companyId)->level ?? 1)
                    : 1,
                'approver_id' => Auth::id(),
                'approval_round' => (int) ($schedule->approval_round ?? 1),
                'status' => 'rejected',
                'rejection_reason' => $reason ?: null,
                'rejected_at' => now(),
            ]);

            // Reset schedule back to draft
            $schedule->status = 'draft';
            $schedule->approved_by = null;
            $schedule->approved_at = null;
            $schedule->save();

            DB::commit();

            // Notify submitter/preparer
            $submitter = $schedule->preparedBy ?? $schedule->createdBy ?? null;
            if ($submitter) {
                $msg = "Accrual schedule {$schedule->schedule_number} was rejected.";
                if ($reason) {
                    $msg .= " Reason: {$reason}";
                }
                $this->notifyUsers(
                    collect([$submitter]),
                    'Accrual Schedule Rejected',
                    $msg,
                    route('accounting.accruals-prepayments.show', $schedule->encoded_id)
                );
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject schedule: ' . $e->getMessage());
        }
        
        return back()->with('success', 'Schedule rejected and returned to draft.');
    }

    private function hasApprovalLevelsConfigured(int $companyId): bool
    {
        return ApprovalLevel::where('module', self::APPROVAL_MODULE)
            ->where('company_id', $companyId)
            ->where('is_required', true)
            ->exists();
    }

    private function getRequiredApprovalLevels(int $companyId)
    {
        return ApprovalLevel::where('module', self::APPROVAL_MODULE)
            ->where('company_id', $companyId)
            ->where('is_required', true)
            ->ordered()
            ->get();
    }

    private function getApproversForLevel(AccrualSchedule $schedule, ApprovalLevel $level)
    {
        $branchId = $schedule->branch_id;

        $assignments = ApprovalLevelAssignment::where('approval_level_id', $level->id)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            })
            ->get();

        $approvers = collect();

        foreach ($assignments as $assignment) {
            if ($assignment->user_id) {
                $user = User::find($assignment->user_id);
                if ($user) {
                    $approvers->push($user);
                }
                continue;
            }

            if ($assignment->role_id) {
                $role = Role::find($assignment->role_id);
                if ($role) {
                    $roleUsers = User::role($role->name)->get();
                    $approvers = $approvers->merge($roleUsers);
                }
            }
        }

        return $approvers->unique('id')->filter();
    }

    /**
     * Determine the current (next pending) approval level for a schedule.
     *
     * Rule: a level is complete only when ALL assigned approvers at that level have approved.
     * If a level has no approvers assigned, it is treated as auto-complete and skipped.
     */
    private function getCurrentApprovalLevelForSchedule(AccrualSchedule $schedule, int $companyId): ?ApprovalLevel
    {
        $levels = $this->getRequiredApprovalLevels($companyId);
        if ($levels->isEmpty()) {
            return null;
        }

        foreach ($levels as $level) {
            $approvers = $this->getApproversForLevel($schedule, $level);

            // No approvers assigned => auto-complete this level
            if ($approvers->isEmpty()) {
                continue;
            }

            $approvedApprovers = AccrualApproval::where('accrual_schedule_id', $schedule->id)
                ->where('approval_round', (int) ($schedule->approval_round ?? 1))
                ->where('approval_level', $level->level)
                ->where('status', 'approved')
                ->pluck('approver_id')
                ->unique();

            $allApproved = $approvers->every(fn ($u) => $approvedApprovers->contains($u->id));
            if (!$allApproved) {
                return $level;
            }
        }

        return null; // all required levels complete
    }

    private function canUserApproveAtLevel(AccrualSchedule $schedule, ApprovalLevel $level, int $userId): bool
    {
        $approvers = $this->getApproversForLevel($schedule, $level);
        return $approvers->contains('id', $userId);
    }

    private function finalizeScheduleApproval(AccrualSchedule $schedule, int $approverId): void
    {
        $schedule->status = 'approved';
        $schedule->approved_by = $approverId;
        $schedule->approved_at = now();
        $schedule->save();

        // Create initial payment/receipt journal entry for prepayments (only once)
        if ($schedule->schedule_type === 'prepayment' && !$schedule->initial_journal_id) {
            $this->scheduleService->createInitialPaymentJournal($schedule);
            $schedule->refresh();
        }

        // Activate schedule
        $schedule->status = 'active';
        $schedule->save();
    }

    private function notifyUsers($users, string $title, string $message, string $url): void
    {
        try {
            $notification = new class($title, $message, $url) extends BaseNotification {
                public function __construct(
                    private readonly string $title,
                    private readonly string $message,
                    private readonly string $url
                ) {}

                public function via($notifiable): array
                {
                    return ['database'];
                }

                public function toArray($notifiable): array
                {
                    return [
                        'title' => $this->title,
                        'message' => $this->message,
                        'url' => $this->url,
                    ];
                }
            };

            Notification::send($users, $notification);
        } catch (\Exception $e) {
            // Never block core workflow on notification failures
            \Log::warning('Accruals & Prepayments notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Post a journal to GL
     */
    public function postJournal($encodedId, $journalId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::findOrFail($id);
        $journal = AccrualJournal::findOrFail($journalId);
        
        if ($journal->accrual_schedule_id != $schedule->id) {
            abort(404);
        }
        
        if ($journal->status === 'posted') {
            return back()->with('error', 'Journal already posted.');
        }
        
        try {
            $this->scheduleService->postJournal($journal);
            return back()->with('success', 'Journal posted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to post journal: ' . $e->getMessage());
        }
    }

    /**
     * Post all pending journals
     */
    public function postAllPending($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::findOrFail($id);
        
        $pendingJournals = AccrualJournal::where('accrual_schedule_id', $schedule->id)
            ->where('status', 'pending')
            ->get();
        
        $posted = 0;
        $errors = [];
        
        foreach ($pendingJournals as $journal) {
            try {
                $this->scheduleService->postJournal($journal);
                $posted++;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        if ($posted > 0) {
            return back()->with('success', "Posted {$posted} journal(s) successfully.");
        } else {
            return back()->with('error', 'Failed to post journals: ' . implode(', ', $errors));
        }
    }

    /**
     * Get amortisation schedule preview
     */
    public function amortisationSchedule($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::findOrFail($id);
        
        $amortisationSchedule = $this->scheduleService->calculateAmortisationSchedule($schedule);
        
        return response()->json($amortisationSchedule);
    }

    /**
     * Export schedule to PDF
     */
    public function exportPdf($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::with([
            'branch', 'vendor', 'customer', 'expenseIncomeAccount', 'balanceSheetAccount',
            'preparedBy', 'approvedBy', 'journals.journal'
        ])->findOrFail($id);
        
        if ($schedule->company_id != Auth::user()->company_id) {
            abort(403);
        }
        
        $amortisationSchedule = $this->scheduleService->calculateAmortisationSchedule($schedule);
        
        $company = $schedule->company;
        $branch = $schedule->branch;
        $user = Auth::user();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting.accruals-prepayments.exports.pdf', compact(
            'schedule', 'amortisationSchedule', 'company', 'branch', 'user'
        ))->setPaper('a4', 'portrait');
        
        return $pdf->download('accrual-schedule-' . $schedule->schedule_number . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export schedule to Excel
     */
    public function exportExcel($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $schedule = AccrualSchedule::with([
            'branch', 'vendor', 'customer', 'expenseIncomeAccount', 'balanceSheetAccount',
            'preparedBy', 'approvedBy', 'journals.journal'
        ])->findOrFail($id);
        
        if ($schedule->company_id != Auth::user()->company_id) {
            abort(403);
        }
        
        $amortisationSchedule = $this->scheduleService->calculateAmortisationSchedule($schedule);
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AccrualScheduleExport($schedule, $amortisationSchedule),
            'accrual-schedule-' . $schedule->schedule_number . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
