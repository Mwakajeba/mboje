<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Purchase\PurchaseQuotation;
use App\Services\Purchase\PurchaseRequisitionService;
use App\Services\Purchase\ProcurementReportingService;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class PurchaseRequisitionController extends Controller
{
    public function __construct(
        protected PurchaseRequisitionService $service
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->company_id;
        
        // Get selected month from request (format: YYYY-MM), default to current month
        $selectedMonth = $request->get('month', now()->format('Y-m'));
        $monthDate = \Carbon\Carbon::parse($selectedMonth . '-01');
        $startDate = $monthDate->copy()->startOfMonth()->toDateString();
        $endDate = $monthDate->copy()->endOfMonth()->toDateString();
        
        $baseQuery = PurchaseRequisition::query()
            ->where('company_id', $companyId);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'in_approval' => (clone $baseQuery)
                ->whereIn('status', ['submitted', 'pending_approval', 'in_review'])
                ->count(),
            'approved' => (clone $baseQuery)
                ->whereIn('status', ['approved', 'po_created'])
                ->count(),
        ];

        // Get dashboard data from reporting service
        $reportingService = app(ProcurementReportingService::class);
        
        // Pending approvals summary
        $pendingApprovals = $reportingService->getPendingApprovalsSummary($companyId);
        
        // PR to PO cycle metrics (selected month)
        $cycleMetrics = $reportingService->getPrToPoCycleMetrics(
            $companyId,
            $startDate,
            $endDate
        );
        
        // Budget utilization
        $budgetUtilization = $reportingService->getBudgetUtilization($companyId);
        
        // Procurement KPIs (selected month)
        $kpis = $reportingService->getProcurementKPIs(
            $companyId,
            $startDate,
            $endDate
        );

        return view('purchases.requisitions.index', compact(
            'stats',
            'pendingApprovals',
            'cycleMetrics',
            'budgetUtilization',
            'kpis',
            'selectedMonth'
        ));
    }

    public function create()
    {
        $user = auth()->user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Active inventory items for selection
        $items = \App\Models\Inventory\Item::forCompany($user->company_id)
            ->active()
            ->orderBy('name')
            ->get();
        // Attach resolved prices (location → branch → default) for display
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);

        // Departments (cost centers)
        $departments = \App\Models\Department::where('company_id', $user->company_id)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        // Budgets for selection (use latest approved/active budget, not strictly current year)
        $budgets = \App\Models\Budget::where('company_id', $user->company_id)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->whereIn('status', ['active', 'approved'])
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'year', 'branch_id']);

        // Get default budget (latest approved/active budget)
        $defaultBudget = $budgets->first();

        // Chart accounts for GL account selection (expense and asset accounts)
        // Filter by account class group for the company, and by account class type
        $chartAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
                // Filter by company_id on account class group
                $q->where('company_id', $user->company_id)
                  // Filter by account class name (expenses or assets)
                  ->whereHas('accountClass', function($q2) {
                      $q2->where(function($q3) {
                          $q3->where('name', 'LIKE', '%Expense%')
                             ->orWhere('name', 'LIKE', '%Asset%')
                             ->orWhere('name', 'LIKE', '%Cost%');
                      });
                  });
            })
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        // Tax groups for selection (optional - TaxGroup model may not exist)
        // Since tax_group_id is optional in the migration, we'll make this optional too
        $taxGroups = collect([]);
        try {
            // Check if TaxGroup model exists by trying to use it
            if (\Illuminate\Support\Facades\Schema::hasTable('tax_groups')) {
                // Use DB query directly to avoid class loading issues
                $taxGroupsData = \Illuminate\Support\Facades\DB::table('tax_groups')
                    ->where('company_id', $user->company_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'vat_type', 'vat_rate']);
                $taxGroups = $taxGroupsData;
            }
        } catch (\Exception $e) {
            // TaxGroup table doesn't exist, use empty collection
            $taxGroups = collect([]);
        }

        return view('purchases.requisitions.create', compact(
            'items', 
            'departments',
            'budgets',
            'defaultBudget',
            'chartAccounts',
            'taxGroups'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $user = auth()->user();
        $data['company_id'] = $user->company_id;
        // Use resolved branch from session fallback, as branch_id is NOT NULL
        $data['branch_id'] = session('branch_id') ?? $user->branch_id;
        $data['requestor_id'] = $user->id;
        $data['status'] = 'draft';

        // Validate that each line item has a budget
        $lines = $data['lines'] ?? [];
        $budgetId = $data['budget_id'] ?? null;
        
        if (!$budgetId) {
            // Try to find active budget for company/branch
            $budget = \App\Models\Budget::where('company_id', $user->company_id)
                ->where(function($q) use ($data) {
                    $q->where('branch_id', $data['branch_id'])
                      ->orWhereNull('branch_id');
                })
                ->where('year', date('Y'))
                ->whereIn('status', ['active', 'approved'])
                ->orderByDesc('created_at')
                ->first();
            
            if ($budget) {
                $budgetId = $budget->id;
                $data['budget_id'] = $budgetId;
            }
        }

        // Check if budget checking is enabled
        $budgetCheckEnabled = \App\Models\SystemSetting::getValue('budget_check_enabled', false);
        
        if ($budgetCheckEnabled && $budgetId) {
            $errors = [];
            
            foreach ($lines as $index => $line) {
                $glAccountId = $line['gl_account_id'] ?? null;
                
                if (!$glAccountId) {
                    continue; // Skip validation if no GL account (will be caught by other validation)
                }
                
                // Check if budget line exists for this GL account
                $budgetLine = \App\Models\BudgetLine::where('budget_id', $budgetId)
                    ->where('account_id', $glAccountId)
                    ->first();
                
                if (!$budgetLine) {
                    $glAccount = \App\Models\ChartAccount::find($glAccountId);
                    $accountName = $glAccount ? $glAccount->account_name : 'Unknown';
                    $errors[] = "Line " . ($index + 1) . " (GL Account: {$accountName}) has no budget allocated.";
                }
            }
            
            if (!empty($errors)) {
                return back()
                    ->withInput()
                    ->withErrors(['budget' => $errors])
                    ->with('error', 'Cannot save purchase requisition: One or more line items have no budget allocated.');
            }
        }

        $requisition = $this->service->saveDraft($data);

        return redirect()
            ->route('purchases.requisitions.show', $requisition->hash_id)
            ->with('success', 'Purchase Requisition drafted successfully.');
    }

    public function show(PurchaseRequisition $requisition)
    {
        // Build eager load array conditionally
        $eagerLoad = [
            'lines.glAccount',
            'lines.inventoryItem',
            'department',
            'requestor',
            'supplier',
            'budget',
            'purchaseOrder',
            'branch',
            'company'
        ];
        
        // Only eager load taxGroup if the model exists
        if (class_exists(\App\Models\TaxGroup::class)) {
            $eagerLoad[] = 'lines.taxGroup';
        }
        
        $requisition->load($eagerLoad);

        // Related RFQs / quotations for supplier selection (only if column exists)
        if (\Illuminate\Support\Facades\Schema::hasColumn('purchase_quotation', 'purchase_requisition_id')) {
            $quotations = PurchaseQuotation::with(['supplier'])
                ->where('purchase_requisition_id', $requisition->id)
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            $quotations = collect();
        }

        // Approval context for UI (approve / reject buttons)
        $approvalService = app(ApprovalService::class);
        $user = auth()->user();
        $canApprove = $approvalService->canUserApprove($requisition, $user->id);
        $currentLevel = $approvalService->getCurrentApprovalLevel($requisition);
        
        // Get approval history
        $approvalHistory = $approvalService->getApprovalHistory($requisition);
        
        // Get approval status summary
        $approvalSummary = $approvalService->getApprovalStatusSummary($requisition);

        return view('purchases.requisitions.show', compact(
            'requisition', 
            'quotations', 
            'canApprove', 
            'currentLevel',
            'approvalHistory',
            'approvalSummary'
        ));
    }

    public function submit(PurchaseRequisition $requisition)
    {
        $this->service->submitForApproval($requisition, auth()->id());

        return redirect()
            ->route('purchases.requisitions.show', $requisition->hash_id)
            ->with('success', 'Purchase Requisition submitted for approval.');
    }

    public function approve(Request $request, PurchaseRequisition $requisition)
    {
        $request->validate([
            'approval_level_id' => 'required|exists:approval_levels,id',
            'comments' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $approvalService = app(ApprovalService::class);

        try {
            if (!$approvalService->canUserApprove($requisition, $user->id)) {
                return back()->with('error', 'You are not allowed to approve this requisition at the current level.');
            }

            $approvalService->approve(
                $requisition,
                (int) $request->approval_level_id,
                $user->id,
                $request->input('comments')
            );

            $fresh = $requisition->fresh();
            $message = 'Purchase Requisition approved successfully.';
            if ($fresh->status === 'approved') {
                $message = 'Purchase Requisition fully approved.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, PurchaseRequisition $requisition)
    {
        $request->validate([
            'approval_level_id' => 'required|exists:approval_levels,id',
            'reason' => 'required|string|max:1000',
        ]);

        $user = auth()->user();
        $approvalService = app(ApprovalService::class);

        try {
            if (!$approvalService->canUserApprove($requisition, $user->id)) {
                return back()->with('error', 'You are not allowed to reject this requisition at the current level.');
            }

            $approvalService->reject(
                $requisition,
                (int) $request->approval_level_id,
                $user->id,
                $request->input('reason')
            );

            return back()->with('success', 'Purchase Requisition rejected successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a draft requisition.
     */
    public function destroy(PurchaseRequisition $requisition)
    {
        if ($requisition->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft requisitions can be deleted.',
            ], 422);
        }

        $requisition->delete();

        return response()->json([
            'message' => 'Draft requisition deleted successfully.',
        ]);
    }

    /**
     * Simple action to set preferred supplier and create PO.
     */
    public function chooseSupplierAndCreatePo(Request $request, PurchaseRequisition $requisition)
    {
        $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
        ]);

        // Update preferred supplier on requisition
        $requisition->update([
            'preferred_supplier_id' => $request->input('supplier_id'),
        ]);

        // Create PO from requisition
        $po = $this->service->createPurchaseOrderFromRequisition(
            $requisition->fresh(['lines']),
            (int) $request->input('supplier_id'),
            auth()->id()
        );

        return redirect()
            ->route('purchases.orders.show', $po->encoded_id)
            ->with('success', 'Purchase Order created from requisition.');
    }

    /**
     * Set preferred supplier from a selected quotation and (optionally) use its prices for PO.
     */
    public function setPreferredSupplierFromQuotation(Request $request, PurchaseRequisition $requisition)
    {
        $request->validate([
            'quotation_id' => ['required', 'exists:purchase_quotation,id'],
        ]);

        $quotation = \App\Models\Purchase\PurchaseQuotation::with('supplier', 'quotationItems.item')
            ->where('id', $request->quotation_id)
            ->where('purchase_requisition_id', $requisition->id)
            ->firstOrFail();

        // Set preferred supplier on requisition
        $requisition->update([
            'preferred_supplier_id' => $quotation->supplier_id,
        ]);

        // For now, we only mark the preferred supplier and keep PR estimated prices.
        // Extension point: in future we can map quotation item prices back to PR lines.

        return back()->with('success', 'Preferred supplier set from quotation: ' . ($quotation->supplier->name ?? 'Supplier'));
    }

    /**
     * Datatable AJAX source for requisitions list.
     */
    public function data(Request $request)
    {
        $requisitions = PurchaseRequisition::with(['department', 'requestor'])
            ->where('company_id', auth()->user()->company_id)
            ->orderByDesc('created_at')
            ->get();

        $data = $requisitions->map(function (PurchaseRequisition $pr) {
            return [
                'id' => $pr->id,
                'hash_id' => $pr->hash_id,
                'pr_no' => $pr->pr_no,
                'department' => $pr->department->name ?? 'N/A',
                'requestor' => $pr->requestor->name ?? 'N/A',
                'required_date' => optional($pr->required_date)->format('Y-m-d'),
                'status' => $pr->status,
                'status_label' => ucfirst(str_replace('_', ' ', $pr->status)),
                'total_amount' => number_format($pr->total_amount, 2),
                'show_url' => route('purchases.requisitions.show', $pr->hash_id),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Check budget availability for a line item (AJAX)
     */
    public function checkBudget(Request $request)
    {
        $request->validate([
            'budget_id' => 'required|exists:budgets,id',
            'gl_account_id' => 'required|exists:chart_accounts,id',
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $budget = \App\Models\Budget::findOrFail($request->budget_id);
            $budgetLine = \App\Models\BudgetLine::where('budget_id', $budget->id)
                ->where('account_id', $request->gl_account_id)
                ->first();

            if (!$budgetLine) {
                return response()->json([
                    'success' => false,
                    'status' => 'no_budget',
                    'message' => 'No budget line found for this GL account',
                ]);
            }

            // Calculate used amount
            $usedAmount = (float) \App\Models\GlTransaction::where('chart_account_id', $request->gl_account_id)
                ->where('branch_id', auth()->user()->branch_id)
                ->where('date', '>=', $budget->year . '-01-01')
                ->where('date', '<=', $budget->year . '-12-31')
                ->where('nature', 'debit')
                ->where('transaction_type', '!=', 'purchase_requisition')
                ->sum('amount');

            $requestedAmount = (float) $request->amount;
            $remainingBudget = (float) $budgetLine->amount - $usedAmount;

            // Get over-budget tolerance
            $overBudgetPercentage = \App\Models\SystemSetting::getValue('budget_over_budget_percentage', 10);
            $allowedAmount = (float) $budgetLine->amount * (1 + ($overBudgetPercentage / 100));
            $totalAfterTransaction = $usedAmount + $requestedAmount;

            if ($totalAfterTransaction > $allowedAmount) {
                return response()->json([
                    'success' => true,
                    'status' => 'over_budget',
                    'message' => 'Exceeds budget limit',
                    'budgeted' => $budgetLine->amount,
                    'used' => $usedAmount,
                    'remaining' => $remainingBudget,
                    'requested' => $requestedAmount,
                ]);
            } elseif ($totalAfterTransaction > (float) $budgetLine->amount) {
                return response()->json([
                    'success' => true,
                    'status' => 'over_budget_warning',
                    'message' => 'Exceeds allocated budget but within tolerance',
                    'budgeted' => $budgetLine->amount,
                    'used' => $usedAmount,
                    'remaining' => $remainingBudget,
                    'requested' => $requestedAmount,
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'status' => 'ok',
                    'message' => 'Within budget',
                    'budgeted' => $budgetLine->amount,
                    'used' => $usedAmount,
                    'remaining' => $remainingBudget,
                    'requested' => $requestedAmount,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking budget: ' . $e->getMessage(),
            ], 500);
        }
    }
}


