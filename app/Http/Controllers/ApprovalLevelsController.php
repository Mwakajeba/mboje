<?php

namespace App\Http\Controllers;

use App\Models\ApprovalLevel;
use App\Models\ApprovalLevelAssignment;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalLevelsController extends Controller
{
    private const MODULE_ACCRUALS_PREPAYMENTS = 'accruals_prepayments';

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the approval levels management page
     */
    public function index()
    {
        // Check permissions
        if (!auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to manage approval levels.');
        }

        $companyId = auth()->user()->company_id;
        
        // Get approval levels for Budget and Bank Reconciliation modules
        $budgetLevels = ApprovalLevel::where('module', 'budget')
            ->where('company_id', $companyId)
            ->with(['assignments.user', 'assignments.role', 'assignments.branch'])
            ->ordered()
            ->get();

        $bankReconciliationLevels = ApprovalLevel::where('module', 'bank_reconciliation')
            ->where('company_id', $companyId)
            ->with(['assignments.user', 'assignments.role', 'assignments.branch'])
            ->ordered()
            ->get();

        // Get approval levels for Asset Management modules
        $revaluationLevels = ApprovalLevel::where('module', 'asset_revaluation')
            ->where('company_id', $companyId)
            ->with(['assignments.user', 'assignments.role', 'assignments.branch'])
            ->ordered()
            ->get();

        $impairmentLevels = ApprovalLevel::where('module', 'asset_impairment')
            ->where('company_id', $companyId)
            ->with(['assignments.user', 'assignments.role', 'assignments.branch'])
            ->ordered()
            ->get();

        $disposalLevels = ApprovalLevel::where('module', 'asset_disposal')
            ->where('company_id', $companyId)
            ->with(['assignments.user', 'assignments.role', 'assignments.branch'])
            ->ordered()
            ->get();

        $hfsLevels = ApprovalLevel::where('module', 'hfs_request')
            ->where('company_id', $companyId)
            ->with(['assignments.user', 'assignments.role', 'assignments.branch'])
            ->ordered()
            ->get();

        // Purchase Requisition approval levels
        $purchaseRequisitionLevels = ApprovalLevel::where('module', 'purchase_requisition')
            ->where('company_id', $companyId)
            ->with(['assignments.user', 'assignments.role', 'assignments.branch'])
            ->ordered()
            ->get();

        // Purchase Order approval levels
        $purchaseOrderLevels = ApprovalLevel::where('module', 'purchase_order')
            ->where('company_id', $companyId)
            ->with(['assignments.user', 'assignments.role', 'assignments.branch'])
            ->ordered()
            ->get();

        // Accruals & Prepayments approval levels
        $accrualsPrepaymentsLevels = ApprovalLevel::where('module', self::MODULE_ACCRUALS_PREPAYMENTS)
            ->where('company_id', $companyId)
            ->with(['assignments.user', 'assignments.role', 'assignments.branch'])
            ->ordered()
            ->get();

        // Get users, roles, and branches for dropdowns
        $users = User::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $roles = Role::all(['id', 'name']);

        $branches = Branch::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('settings.approval-levels.index', compact(
            'budgetLevels',
            'bankReconciliationLevels',
            'revaluationLevels',
            'impairmentLevels',
            'disposalLevels',
            'hfsLevels',
            'purchaseRequisitionLevels',
            'purchaseOrderLevels',
            'accrualsPrepaymentsLevels',
            'users',
            'roles',
            'branches'
        ));
    }

    /**
     * Store a new approval level
     */
    public function store(Request $request)
    {
        // Check permissions
        if (!auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to manage approval levels.');
        }

        $request->validate([
            'module' => 'required|in:budget,bank_reconciliation,asset_revaluation,asset_impairment,asset_disposal,hfs_request,purchase_requisition,purchase_order,' . self::MODULE_ACCRUALS_PREPAYMENTS,
            'level' => 'required|integer|min:1|max:10',
            'level_name' => 'required|string|max:255',
            'is_required' => 'nullable|boolean',
        ]);

        $companyId = auth()->user()->company_id;

        // Check if level already exists for this module and company
        $existingLevel = ApprovalLevel::where('module', $request->module)
            ->where('company_id', $companyId)
            ->where('level', $request->level)
            ->first();

        if ($existingLevel) {
            return back()->withErrors(['level' => 'This approval level already exists for this module.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Get the maximum approval_order for this module
            $maxOrder = ApprovalLevel::where('module', $request->module)
                ->where('company_id', $companyId)
                ->max('approval_order') ?? 0;

            $approvalLevel = ApprovalLevel::create([
                'module' => $request->module,
                'level' => $request->level,
                'level_name' => $request->level_name,
                'is_required' => $request->has('is_required') ? $request->boolean('is_required') : true,
                'approval_order' => $maxOrder + 1,
                'company_id' => $companyId,
            ]);

            DB::commit();

            return redirect()->route('settings.approval-levels.index')
                ->with('success', 'Approval level created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Approval Level Creation Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create approval level. Please try again.'])->withInput();
        }
    }

    /**
     * Update an approval level
     */
    public function update(Request $request, ApprovalLevel $approvalLevel)
    {
        // Check permissions
        if (!auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to manage approval levels.');
        }

        // Check company scope
        if ($approvalLevel->company_id !== auth()->user()->company_id) {
            abort(403, 'You do not have permission to edit this approval level.');
        }

        $request->validate([
            'level_name' => 'required|string|max:255',
            'is_required' => 'nullable|boolean',
        ]);

        try {
            $approvalLevel->update([
                'level_name' => $request->level_name,
                'is_required' => $request->has('is_required') ? $request->boolean('is_required') : true,
            ]);

            return redirect()->route('settings.approval-levels.index')
                ->with('success', 'Approval level updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Approval Level Update Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update approval level. Please try again.'])->withInput();
        }
    }

    /**
     * Delete an approval level
     */
    public function destroy(ApprovalLevel $approvalLevel)
    {
        // Check permissions
        if (!auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to manage approval levels.');
        }

        // Check company scope
        if ($approvalLevel->company_id !== auth()->user()->company_id) {
            abort(403, 'You do not have permission to delete this approval level.');
        }

        try {
            DB::beginTransaction();

            // Delete all assignments for this level
            ApprovalLevelAssignment::where('approval_level_id', $approvalLevel->id)->delete();

            // Delete the level
            $approvalLevel->delete();

            DB::commit();

            return redirect()->route('settings.approval-levels.index')
                ->with('success', 'Approval level deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Approval Level Deletion Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete approval level. Please try again.']);
        }
    }

    /**
     * Store an assignment for an approval level
     */
    public function storeAssignment(Request $request)
    {
        // Check permissions
        if (!auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to manage approval level assignments.');
        }

        $request->validate([
            'approval_level_id' => 'required|exists:approval_levels,id',
            'user_id' => 'nullable|exists:users,id',
            'role_id' => 'nullable|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
        ], [
            'user_id.required_without' => 'Either user or role must be selected.',
            'role_id.required_without' => 'Either user or role must be selected.',
        ]);

        // Validate that either user_id or role_id is provided
        if (!$request->user_id && !$request->role_id) {
            return back()->withErrors(['user_id' => 'Either user or role must be selected.'])->withInput();
        }

        // Validate that both user_id and role_id are not provided
        if ($request->user_id && $request->role_id) {
            return back()->withErrors(['user_id' => 'Please select either user or role, not both.'])->withInput();
        }

        $approvalLevel = ApprovalLevel::findOrFail($request->approval_level_id);

        // Check company scope
        if ($approvalLevel->company_id !== auth()->user()->company_id) {
            abort(403, 'You do not have permission to create this assignment.');
        }

        // If user_id is provided, check company scope
        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
            if ($user->company_id !== auth()->user()->company_id) {
                abort(403, 'You do not have permission to assign this user.');
            }
        }

        // If branch_id is provided, check company scope
        if ($request->branch_id) {
            $branch = Branch::findOrFail($request->branch_id);
            if ($branch->company_id !== auth()->user()->company_id) {
                abort(403, 'You do not have permission to assign this branch.');
            }
        }

        try {
            ApprovalLevelAssignment::create([
                'approval_level_id' => $request->approval_level_id,
                'user_id' => $request->user_id,
                'role_id' => $request->role_id,
                'branch_id' => $request->branch_id,
            ]);

            return redirect()->route('settings.approval-levels.index')
                ->with('success', 'Approver assigned successfully.');

        } catch (\Exception $e) {
            \Log::error('Approval Level Assignment Creation Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to assign approver. Please try again.'])->withInput();
        }
    }

    /**
     * Delete an assignment
     */
    public function destroyAssignment(ApprovalLevelAssignment $assignment)
    {
        // Check permissions
        if (!auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to manage approval level assignments.');
        }

        $approvalLevel = $assignment->approvalLevel;

        // Check company scope
        if ($approvalLevel->company_id !== auth()->user()->company_id) {
            abort(403, 'You do not have permission to delete this assignment.');
        }

        try {
            $assignment->delete();

            return redirect()->route('settings.approval-levels.index')
                ->with('success', 'Assignment deleted successfully.');

        } catch (\Exception $e) {
            \Log::error('Approval Level Assignment Deletion Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete assignment. Please try again.']);
        }
    }

    /**
     * Reorder approval levels
     */
    public function reorder(Request $request)
    {
        // Check permissions
        if (!auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to reorder approval levels.');
        }

        $request->validate([
            'module' => 'required|in:budget,bank_reconciliation,asset_revaluation,asset_impairment,asset_disposal,hfs_request,purchase_requisition,purchase_order,' . self::MODULE_ACCRUALS_PREPAYMENTS,
            'level_ids' => 'required|array',
            'level_ids.*' => 'exists:approval_levels,id',
        ]);

        $companyId = auth()->user()->company_id;

        try {
            DB::beginTransaction();

            foreach ($request->level_ids as $order => $levelId) {
                $level = ApprovalLevel::findOrFail($levelId);
                
                // Check company scope
                if ($level->company_id !== $companyId) {
                    continue;
                }

                // Check module match
                if ($level->module !== $request->module) {
                    continue;
                }

                $level->update(['approval_order' => $order + 1]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Approval levels reordered successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Approval Level Reorder Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder approval levels.'
            ], 500);
        }
    }
}

