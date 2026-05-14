<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountTransfer;
use App\Models\BankAccount;
use App\Models\CashDepositAccount;
use App\Models\PettyCash\PettyCashUnit;
use App\Services\AccountTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\JsonResponse;

class AccountTransferController extends Controller
{
    protected $transferService;

    public function __construct(AccountTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Display a listing of account transfers
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            $query = AccountTransfer::forCompany($companyId)
                ->with(['currency', 'createdBy', 'approvedBy', 'branch']);
            
            // Apply filters
            if ($request->filled('branch_id')) {
                $query->forBranch($request->branch_id);
            }
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            return datatables($query)
                ->filter(function ($query) use ($request) {
                    if ($request->filled('search.value')) {
                        $searchValue = $request->input('search.value');
                        $query->where(function($q) use ($searchValue) {
                            $q->where('transfer_number', 'like', "%{$searchValue}%")
                              ->orWhere('description', 'like', "%{$searchValue}%")
                              ->orWhere('reference_number', 'like', "%{$searchValue}%");
                        });
                    }
                })
                ->addColumn('transfer_date_formatted', function ($transfer) {
                    return $transfer->transfer_date->format('d M Y');
                })
                ->addColumn('from_account_name', function ($transfer) {
                    $account = null;
                    $type = ucfirst(str_replace('_', ' ', $transfer->from_account_type));
                    
                    switch ($transfer->from_account_type) {
                        case 'bank':
                            $account = \App\Models\BankAccount::find($transfer->from_account_id);
                            break;
                        case 'cash':
                            $account = \App\Models\CashDepositAccount::find($transfer->from_account_id);
                            break;
                        case 'petty_cash':
                            $account = \App\Models\PettyCash\PettyCashUnit::find($transfer->from_account_id);
                            break;
                    }
                    
                    if (!$account) return $type . ': N/A';
                    return $type . ': ' . ($account->name ?? 'N/A');
                })
                ->addColumn('to_account_name', function ($transfer) {
                    $account = null;
                    $type = ucfirst(str_replace('_', ' ', $transfer->to_account_type));
                    
                    switch ($transfer->to_account_type) {
                        case 'bank':
                            $account = \App\Models\BankAccount::find($transfer->to_account_id);
                            break;
                        case 'cash':
                            $account = \App\Models\CashDepositAccount::find($transfer->to_account_id);
                            break;
                        case 'petty_cash':
                            $account = \App\Models\PettyCash\PettyCashUnit::find($transfer->to_account_id);
                            break;
                    }
                    
                    if (!$account) return $type . ': N/A';
                    return $type . ': ' . ($account->name ?? 'N/A');
                })
                ->addColumn('amount_formatted', function ($transfer) {
                    $currency = $transfer->currency ? $transfer->currency->currency_code : 'TZS';
                    return number_format($transfer->amount, 2) . ' ' . $currency;
                })
                ->addColumn('status_badge', function ($transfer) {
                    $statusColors = [
                        'draft' => 'secondary',
                        'submitted' => 'info',
                        'approved' => 'success',
                        'posted' => 'primary',
                        'rejected' => 'danger'
                    ];
                    $color = $statusColors[$transfer->status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($transfer->status) . '</span>';
                })
                ->addColumn('branch_name', function ($transfer) {
                    return $transfer->branch->name ?? 'N/A';
                })
                ->addColumn('actions', function ($transfer) {
                    $encodedId = $transfer->encoded_id;
                    $actions = '<div class="btn-group" role="group">';
                    
                    $actions .= '<a href="' . route('accounting.account-transfers.show', $encodedId) . '" class="btn btn-sm btn-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>';
                    
                    if ($transfer->canBeEdited()) {
                        $actions .= '<a href="' . route('accounting.account-transfers.edit', $encodedId) . '" class="btn btn-sm btn-warning" title="Edit">
                            <i class="bx bx-edit"></i>
                        </a>';
                    }
                    
                    if ($transfer->canBeDeleted()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteTransfer(\'' . $encodedId . '\')" title="Delete">
                            <i class="bx bx-trash"></i>
                        </button>';
                    }
                    
                    if ($transfer->canBeApproved()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-success" onclick="approveTransfer(\'' . $encodedId . '\')" title="Approve">
                            <i class="bx bx-check"></i>
                        </button>';
                    }
                    
                    if ($transfer->canBeRejected()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="rejectTransfer(\'' . $encodedId . '\')" title="Reject">
                            <i class="bx bx-x"></i>
                        </button>';
                    }
                    
                    if ($transfer->canBePosted()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-primary" onclick="postTransferToGL(\'' . $encodedId . '\')" title="Post to GL">
                            <i class="bx bx-book"></i>
                        </button>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }
        
        // Calculate stats
        $totalTransfers = AccountTransfer::forCompany($companyId)->count();
        $pendingTransfers = AccountTransfer::forCompany($companyId)->pendingApproval()->count();
        $approvedTransfers = AccountTransfer::forCompany($companyId)->where('status', 'approved')->count();
        $totalAmount = AccountTransfer::forCompany($companyId)->sum('amount');
        
        $branches = \App\Models\Branch::where('company_id', $companyId)->orderBy('name')->get();
        
        return view('accounting.account-transfers.index', compact('totalTransfers', 'pendingTransfers', 'approvedTransfers', 'totalAmount', 'branches'));
    }

    /**
     * Show the form for creating a new transfer
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $bankAccounts = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->orderBy('name')->get();
        
        $cashAccounts = CashDepositAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->orderBy('name')->get();
        
        $pettyCashUnits = PettyCashUnit::forCompany($companyId)->active()->orderBy('name')->get();
        
        // Get expense accounts for charges
        $chargesAccounts = \App\Models\ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereRaw('LOWER(account_class.name) LIKE ?', ['%expense%'])
            ->select('chart_accounts.id', 'chart_accounts.account_name', 'chart_accounts.account_code')
            ->orderBy('chart_accounts.account_code')
            ->get();
        
        return view('accounting.account-transfers.create', compact('bankAccounts', 'cashAccounts', 'pettyCashUnits', 'chargesAccounts'));
    }

    /**
     * Store a newly created transfer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'from_account_type' => 'required|in:bank',
            'from_account_id' => 'required|integer|exists:bank_accounts,id',
            'to_account_type' => 'required|in:bank',
            'to_account_id' => 'required|integer|exists:bank_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'charges' => 'nullable|numeric|min:0',
            'charges_account_id' => 'nullable|exists:chart_accounts,id',
            'description' => 'required|string',
            'reference_number' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        
        // Additional validation: ensure accounts are different
        if ($validated['from_account_id'] == $validated['to_account_id']) {
            return back()->withInput()->with('error', 'From account and To account cannot be the same.');
        }
        
        // Check if transfer amount exceeds from account balance
        $fromAccount = BankAccount::findOrFail($validated['from_account_id']);
        $balance = $fromAccount->balance;
        
        if ($validated['amount'] > $balance) {
            return back()->withInput()->with('error', 'Transfer amount (TZS ' . number_format($validated['amount'], 2) . ') exceeds available balance (TZS ' . number_format($balance, 2) . ').');
        }
        
        try {
            DB::beginTransaction();
            
            // Generate transfer number
            $transferNumber = $this->generateTransferNumber();
            
            // Handle file upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('account-transfers', 'public');
            }
            
            $validated['transfer_number'] = $transferNumber;
            $validated['attachment'] = $attachmentPath;
            $validated['company_id'] = Auth::user()->company_id;
            $validated['branch_id'] = session('branch_id') ?? Auth::user()->branch_id;
            $validated['created_by'] = Auth::id();
            
            // Set status based on action button clicked
            $action = $request->input('action', 'draft');
            $validated['status'] = $action === 'submit' ? 'submitted' : 'draft';
            
            $transfer = AccountTransfer::create($validated);
            
            // Initialize approval workflow if submitted
            if ($action === 'submit') {
                $transfer->initializeApprovalWorkflow();
            }
            
            DB::commit();
            
            $message = $action === 'submit' 
                ? 'Account transfer submitted for approval successfully.' 
                : 'Account transfer saved as draft successfully.';
            
            return redirect()->route('accounting.account-transfers.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create transfer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified transfer
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $transfer = AccountTransfer::with([
            'currency', 
            'createdBy', 
            'approvedBy', 
            'branch', 
            'chargesAccount',
            'journal.items.chartAccount'
        ])->findOrFail($id);
        
        // Get approval settings and approvers
        $approvalSettings = \App\Models\AccountTransferApprovalSetting::where('company_id', $transfer->company_id)->first();
        $approvers = [];
        
        if ($approvalSettings && $approvalSettings->require_approval_for_all) {
            for ($level = 1; $level <= $approvalSettings->approval_levels; $level++) {
                $approvalType = $approvalSettings->{"level{$level}_approval_type"};
                $approverIds = $approvalSettings->{"level{$level}_approvers"} ?? [];
                
                if ($approvalType === 'role') {
                    $roles = \Spatie\Permission\Models\Role::whereIn('name', $approverIds)->get();
                    $users = \App\Models\User::role($approverIds)->get();
                    $approvers[$level] = [
                        'type' => 'role',
                        'roles' => $roles,
                        'users' => $users
                    ];
                } elseif ($approvalType === 'user') {
                    $users = \App\Models\User::whereIn('id', $approverIds)->get();
                    $approvers[$level] = [
                        'type' => 'user',
                        'users' => $users
                    ];
                }
            }
        }
        
        return view('accounting.account-transfers.show', compact('transfer', 'approvalSettings', 'approvers'));
    }

    /**
     * Show the form for editing the specified transfer
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $transfer = AccountTransfer::findOrFail($id);
        
        if (!$transfer->canBeEdited()) {
            return redirect()->route('accounting.account-transfers.index')
                ->with('error', 'This transfer cannot be edited.');
        }
        
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $bankAccounts = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->orderBy('name')->get();
        
        // Get expense accounts for charges
        $chargesAccounts = \App\Models\ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereRaw('LOWER(account_class.name) LIKE ?', ['%expense%'])
            ->select('chart_accounts.id', 'chart_accounts.account_name', 'chart_accounts.account_code')
            ->orderBy('chart_accounts.account_code')
            ->get();
        
        return view('accounting.account-transfers.edit', compact('transfer', 'bankAccounts', 'chargesAccounts'));
    }

    /**
     * Update the specified transfer
     */
    public function update($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $transfer = AccountTransfer::findOrFail($id);
        
        if (!$transfer->canBeEdited()) {
            return redirect()->route('accounting.account-transfers.index')
                ->with('error', 'This transfer cannot be edited.');
        }
        
        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'from_account_type' => 'required|in:bank',
            'from_account_id' => 'required|integer|exists:bank_accounts,id',
            'to_account_type' => 'required|in:bank',
            'to_account_id' => 'required|integer|exists:bank_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'charges' => 'nullable|numeric|min:0',
            'charges_account_id' => 'nullable|exists:chart_accounts,id',
            'description' => 'required|string',
            'reference_number' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        
        // Additional validation: ensure accounts are different
        if ($validated['from_account_id'] == $validated['to_account_id']) {
            return back()->withInput()->with('error', 'From account and To account cannot be the same.');
        }
        
        try {
            DB::beginTransaction();
            
            // Handle file upload
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($transfer->attachment) {
                    Storage::disk('public')->delete($transfer->attachment);
                }
                $validated['attachment'] = $request->file('attachment')->store('account-transfers', 'public');
            } else {
                unset($validated['attachment']);
            }
            
            // Update status if action is provided and transfer is draft
            if ($transfer->status === 'draft' && $request->has('action')) {
                $action = $request->input('action');
                $validated['status'] = $action === 'submit' ? 'submitted' : 'draft';
                
                // Initialize approval workflow if submitting
                if ($action === 'submit') {
                    $transfer->update($validated);
                    $transfer->initializeApprovalWorkflow();
                } else {
                    $transfer->update($validated);
                }
            } else {
                $transfer->update($validated);
            }
            
            DB::commit();
            
            $message = ($request->input('action') === 'submit' && isset($validated['status']) && $validated['status'] === 'submitted')
                ? 'Account transfer updated and submitted for approval successfully.'
                : 'Account transfer updated successfully.';
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return redirect()->route('accounting.account-transfers.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update transfer: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified transfer
     */
    public function destroy($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transfer not found'], 404);
            }
            abort(404);
        }
        
        $transfer = AccountTransfer::findOrFail($id);
        
        if (!$transfer->canBeDeleted()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'This transfer cannot be deleted.'], 400);
            }
            return redirect()->route('accounting.account-transfers.index')
                ->with('error', 'This transfer cannot be deleted.');
        }
        
        try {
            DB::beginTransaction();
            
            // Delete attachment if exists
            if ($transfer->attachment) {
                Storage::disk('public')->delete($transfer->attachment);
            }
            
            $transfer->delete();
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Transfer deleted successfully']);
            }
            
            return redirect()->route('accounting.account-transfers.index')
                ->with('success', 'Transfer deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete transfer: ' . $e->getMessage()], 500);
            }
            
            return back()->with('error', 'Failed to delete transfer: ' . $e->getMessage());
        }
    }

    /**
     * Approve transfer
     */
    public function approve($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transfer not found'], 404);
            }
            abort(404);
        }
        
        $transfer = AccountTransfer::findOrFail($id);
        
        if (!$transfer->canBeApproved()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transfer cannot be approved'], 400);
            }
            return back()->with('error', 'Transfer cannot be approved.');
        }
        
        // Check if approval settings exist and user has permission
        $settings = \App\Models\AccountTransferApprovalSetting::where('company_id', Auth::user()->company_id)->first();
        
        if ($settings && $settings->require_approval_for_all) {
            // Get current approval level
            $currentLevel = $transfer->current_approval_level ?? 1;
            
            // Check if user can approve at current level
            if (!$settings->canUserApproveAtLevel(Auth::user(), $currentLevel)) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'You do not have permission to approve this transfer at level ' . $currentLevel], 403);
                }
                return back()->with('error', 'You do not have permission to approve this transfer at level ' . $currentLevel);
            }
            
            // Get current pending approval for this user
            $currentApproval = $transfer->getCurrentApproval();
            if (!$currentApproval) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'No pending approval found for you at this level'], 400);
                }
                return back()->with('error', 'No pending approval found for you at this level.');
            }
        } else {
            $currentApproval = null;
        }
        
        try {
            DB::beginTransaction();
            
            if ($settings && $settings->require_approval_for_all && $currentApproval) {
                // Approve current level
                $currentApproval->approve($request->approval_notes ?? null);
                
                // Check if all approvals at current level are complete
                $currentLevel = $transfer->current_approval_level ?? 1;
                $levelApprovals = $transfer->approvals()->where('approval_level', $currentLevel)->get();
                $allApprovedAtLevel = $levelApprovals->every(function($approval) {
                    return $approval->status === 'approved';
                });
                
                if ($allApprovedAtLevel) {
                    // All approvals at current level are complete - move to next level
                    $nextLevel = $currentLevel + 1;
                    $requiredLevel = $settings->approval_levels;
                    
                    if ($nextLevel <= $requiredLevel) {
                        // Move to next level
                        $transfer->update([
                            'current_approval_level' => $nextLevel,
                            'status' => 'submitted', // Keep as submitted until all levels approved
                        ]);
                        
                        $message = "Level {$currentLevel} approved. Waiting for Level {$nextLevel} approval.";
                    } else {
                        // All levels approved - mark as fully approved
                        $transfer->update([
                            'status' => 'approved',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                            'approval_notes' => $request->approval_notes ?? '',
                        ]);
                        
                        $message = 'Transfer fully approved. Ready to post to GL.';
                    }
                } else {
                    // Waiting for other approvers at this level
                    $message = "Your approval recorded. Waiting for other approvers at Level {$currentLevel}.";
                }
            } else {
                // No approval workflow - direct approval
                $transfer->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'approval_notes' => $request->approval_notes ?? '',
                ]);
                
                $message = 'Transfer approved successfully.';
            }
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to approve transfer: ' . $e->getMessage()], 500);
            }
            
            return back()->with('error', 'Failed to approve transfer: ' . $e->getMessage());
        }
    }

    /**
     * Reject transfer
     */
    public function reject($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transfer not found'], 404);
            }
            abort(404);
        }
        
        $transfer = AccountTransfer::findOrFail($id);
        
        if (!$transfer->canBeRejected()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transfer cannot be rejected'], 400);
            }
            return back()->with('error', 'Transfer cannot be rejected.');
        }
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);
        
        try {
            DB::beginTransaction();
            
            $transfer->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Transfer rejected successfully']);
            }
            
            return back()->with('success', 'Transfer rejected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to reject transfer: ' . $e->getMessage()], 500);
            }
            
            return back()->with('error', 'Failed to reject transfer: ' . $e->getMessage());
        }
    }

    /**
     * Post transfer to GL
     */
    public function postToGL($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transfer not found'], 404);
            }
            abort(404);
        }
        
        $transfer = AccountTransfer::findOrFail($id);
        
        if (!$transfer->canBePosted()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transfer cannot be posted to GL'], 400);
            }
            return back()->with('error', 'Transfer cannot be posted to GL.');
        }
        
        try {
            DB::beginTransaction();
            
            // Post to GL
            $this->transferService->postTransferToGL($transfer);
            
            $transfer->update([
                'status' => 'posted'
            ]);
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Transfer posted to GL successfully']);
            }
            
            return back()->with('success', 'Transfer posted to GL successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to post transfer to GL: ' . $e->getMessage()], 500);
            }
            
            return back()->with('error', 'Failed to post transfer to GL: ' . $e->getMessage());
        }
    }

    /**
     * Generate transfer number
     */
    private function generateTransferNumber(): string
    {
        $prefix = 'ATF-';
        $year = date('Y');
        $month = date('m');
        
        $lastTransfer = AccountTransfer::where('transfer_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('transfer_number', 'desc')
            ->first();
        
        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Export transfer to PDF
     */
    public function exportPdf($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404, 'Invalid transfer ID');
        }
        
        try {
            $transfer = AccountTransfer::with([
                'currency', 
                'createdBy', 
                'approvedBy', 
                'branch', 
                'chargesAccount',
                'journal.items.chartAccount',
                'company'
            ])->findOrFail($id);
            
            // Check if user has access to this transfer
            $user = Auth::user();
            if ($transfer->company_id !== $user->company_id) {
                abort(403, 'Unauthorized access to this transfer.');
            }
            
            // Generate PDF using DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting.account-transfers.pdf', compact('transfer'));
            
            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');
            
            // Generate filename
            $filename = 'Account_Transfer_' . $transfer->transfer_number . '_' . date('Y-m-d') . '.pdf';
            
            // Return PDF for download
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            \Log::error('Failed to export transfer PDF', [
                'transfer_id' => $id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to export PDF: ' . $e->getMessage()]);
        }
    }

    /**
     * Get bank account balance via API
     */
    public function getBankAccountBalance($id): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyId = $user->company_id;
            
            // Find bank account and check if user has access
            // Check both direct company_id and through chart account relationship
            $bankAccount = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->find($id);
            
            if (!$bankAccount) {
                // Also check direct company_id in case it's set
                $bankAccount = BankAccount::where('id', $id)
                    ->where('company_id', $companyId)
                    ->first();
            }
            
            if (!$bankAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank account not found or unauthorized access'
                ], 403);
            }
            
            $balance = $bankAccount->balance;
            
            return response()->json([
                'success' => true,
                'balance' => $balance,
                'formatted_balance' => number_format($balance, 2)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching bank account balance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balance: ' . $e->getMessage()
            ], 500);
        }
    }
}

