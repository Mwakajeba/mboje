<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ChartAccount;
use App\Models\Provision;
use App\Services\ProvisionService;
use App\Services\ApprovalService;
use App\Services\ProvisionComputation\ProvisionComputationFactory;
use App\Models\DiscountRate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class ProvisionController extends Controller
{
    protected ProvisionService $provisionService;
    protected ApprovalService $approvalService;

    public function __construct(ProvisionService $provisionService, ApprovalService $approvalService)
    {
        $this->provisionService = $provisionService;
        $this->approvalService = $approvalService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {
            $query = Provision::forCompany($user->company_id)
                ->with(['branch'])
                ->select([
                    'id',
                    'provision_number',
                    'provision_type',
                    'title',
                    'status',
                    'probability',
                    'current_balance',
                    'utilised_amount',
                    'reversed_amount',
                    'currency_code',
                    'expected_settlement_date',
                    'branch_id',
                    'created_at',
                ]);

            return datatables($query)
                ->addColumn('provision_type_label', function (Provision $provision) {
                    return ucfirst(str_replace('_', ' ', $provision->provision_type));
                })
                ->addColumn('status_badge', function (Provision $provision) {
                    $badgeClass = match ($provision->status) {
                        'draft' => 'bg-secondary',
                        'pending_approval' => 'bg-info',
                        'approved' => 'bg-primary',
                        'active' => 'bg-success',
                        'settled' => 'bg-dark',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary',
                    };
                    return '<span class="badge ' . $badgeClass . '">' . ucfirst($provision->status) . '</span>';
                })
                ->addColumn('formatted_current_balance', function (Provision $provision) {
                    return number_format($provision->current_balance, 2) . ' ' . $provision->currency_code;
                })
                ->addColumn('formatted_utilised', function (Provision $provision) {
                    return number_format($provision->utilised_amount, 2) . ' ' . $provision->currency_code;
                })
                ->addColumn('formatted_reversed', function (Provision $provision) {
                    return number_format($provision->reversed_amount, 2) . ' ' . $provision->currency_code;
                })
                ->addColumn('formatted_expected_settlement', function (Provision $provision) {
                    return $provision->expected_settlement_date
                        ? $provision->expected_settlement_date->format('Y-m-d')
                        : '-';
                })
                ->addColumn('branch_name', function (Provision $provision) {
                    return $provision->branch?->name ?? '-';
                })
                ->addColumn('actions', function (Provision $provision) {
                    $encodedId = $provision->encoded_id;
                    $showUrl = route('accounting.provisions.show', $encodedId);
                    $editUrl = route('accounting.provisions.edit', $encodedId);
                    
                    $actions = '<div class="d-flex gap-1">';
                    $actions .= '<a href="' . $showUrl . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                    
                    // Only show edit button if provision can be edited
                    if ($provision->canBeEdited()) {
                        $actions .= '<a href="' . $editUrl . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->editColumn('created_at', function (Provision $provision) {
                    return $provision->created_at?->format('Y-m-d H:i');
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        // Dashboard-style stats for cards
        $baseQuery = Provision::forCompany($user->company_id);

        $totalProvisions = (clone $baseQuery)->count();
        $totalCurrentBalance = (clone $baseQuery)->sum('current_balance');
        $totalUtilised = (clone $baseQuery)->sum('utilised_amount');
        $totalReversed = (clone $baseQuery)->sum('reversed_amount');

        return view('accounting.provisions.index', compact(
            'totalProvisions',
            'totalCurrentBalance',
            'totalUtilised',
            'totalReversed'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();

        // Get accounts - will be filtered by template when provision type is selected
        // For initial load, show all relevant accounts
        $expenseAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['expense', 'expenses', 'assets'])
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();

        $provisionAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['liabilities'])
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();

        $financeCostAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['expense', 'expenses'])
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();

        $provisionTemplates = config('ias37_provision_templates', []);
        
        // Get active discount rates for auto-population
        $activeDiscountRates = DiscountRate::forCompany($user->company_id)
            ->active()
            ->forContext('provision')
            ->get();
        
        // Prepare computation services info for each template
        $computationServices = [];
        foreach ($provisionTemplates as $type => $template) {
            if (ProvisionComputationFactory::hasComputation($type)) {
                $service = ProvisionComputationFactory::getService($type);
                $computationServices[$type] = [
                    'input_fields' => $service->getInputFields(),
                    'enabled' => true,
                ];
            } else {
                $computationServices[$type] = ['enabled' => false];
            }
        }

        return view('accounting.provisions.create', compact(
            'branches',
            'expenseAccounts',
            'provisionAccounts',
            'financeCostAccounts',
            'provisionTemplates',
            'activeDiscountRates',
            'computationServices'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provision_type' => 'required|in:legal_claim,warranty,onerous_contract,environmental,restructuring,employee_benefit,other',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'present_obligation_type' => 'nullable|in:legal,constructive',
            'has_present_obligation' => 'required|boolean',
            'probability' => 'required|in:remote,possible,probable,virtually_certain',
            'probability_percent' => 'nullable|numeric|min:0|max:100',
            'estimate_method' => 'required|in:best_estimate,expected_value,most_likely_outcome',
            'amount' => 'nullable|numeric|min:0.01', // Can be computed from computation_assumptions
            'computation_assumptions' => 'nullable|array', // JSON data from computation panel
            'currency_code' => 'required|string|size:3',
            'fx_rate_at_creation' => 'nullable|numeric|min:0.000001',
            'is_discounted' => 'nullable|boolean',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'discount_rate_id' => 'nullable|exists:discount_rates,id',
            'expected_settlement_date' => 'nullable|date',
            'undiscounted_amount' => 'nullable|numeric|min:0',
            // Asset linkage fields (for Environmental)
            'related_asset_id' => 'nullable|integer',
            'asset_category' => 'nullable|string|max:100',
            'is_capitalised' => 'nullable|boolean',
            'depreciation_start_date' => 'nullable|date',
            'expense_account_id' => 'required|exists:chart_accounts,id',
            'provision_account_id' => 'required|exists:chart_accounts,id',
            'unwinding_account_id' => 'nullable|exists:chart_accounts,id',
            'branch_id' => 'nullable|exists:branches,id',
            'movement_date' => 'nullable|date',
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $data = $validated;
            $data['fx_rate_at_creation'] = $data['fx_rate_at_creation'] ?? 1;

            $provision = $this->provisionService->createProvisionWithInitialRecognition($data);

            DB::commit();

            return redirect()
                ->route('accounting.provisions.show', $provision->encoded_id)
                ->with('success', 'Provision created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create provision: ' . $e->getMessage());
        }
    }

    /**
     * Compute provision amount using computation service (AJAX endpoint)
     */
    public function compute(Request $request)
    {
        $validated = $request->validate([
            'provision_type' => 'required|in:legal_claim,warranty,onerous_contract,environmental,restructuring,employee_benefit,other',
            'inputs' => 'required|array',
        ]);

        try {
            $provisionType = $validated['provision_type'];
            $inputs = $validated['inputs'];

            if (!ProvisionComputationFactory::hasComputation($provisionType)) {
                return response()->json([
                    'errors' => ['Computation not available for this provision type'],
                ], 400);
            }

            $service = ProvisionComputationFactory::getService($provisionType);
            $result = $service->calculate($inputs);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function edit(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $provision = Provision::with(['company', 'branch', 'expenseAccount', 'provisionAccount', 'unwindingAccount', 'discountRate'])
            ->findOrFail($id);

        if ($provision->company_id !== $user->company_id) {
            abort(403);
        }

        // Only allow editing if in draft, rejected, or pending_approval status
        if (!in_array($provision->status, ['draft', 'rejected', 'pending_approval'])) {
            return redirect()
                ->route('accounting.provisions.show', $encodedId)
                ->with('error', 'Only draft, rejected, or pending approval provisions can be edited.');
        }

        $companyId = $user->company_id;

        // Load branches
        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();

        // Get accounts with template-based filtering based on current provision type
        $provisionType = $provision->provision_type;
        $template = config('ias37_provision_templates.' . $provisionType, []);
        $accountRestrictions = $template['account_restrictions'] ?? [];

        // Expense accounts - filter by template restrictions if available
        $expenseAccountsQuery = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['expense', 'expenses', 'assets']);

        if (!empty($accountRestrictions['expense_accounts'])) {
            $expenseAccountsQuery = $this->filterAccountsByTemplate($expenseAccountsQuery, $accountRestrictions['expense_accounts']);
        }

        $expenseAccounts = $expenseAccountsQuery->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();

        // Provision liability accounts
        $provisionAccountsQuery = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['liabilities']);

        if (!empty($accountRestrictions['provision_accounts'])) {
            $provisionAccountsQuery = $this->filterAccountsByTemplate($provisionAccountsQuery, $accountRestrictions['provision_accounts']);
        }

        $provisionAccounts = $provisionAccountsQuery->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();

        // Finance cost accounts for unwinding
        $financeCostAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereIn('account_class.name', ['expense', 'expenses'])
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_code')
            ->get();

        $provisionTemplates = config('ias37_provision_templates', []);
        
        // Get active discount rates
        $activeDiscountRates = DiscountRate::forCompany($user->company_id)
            ->active()
            ->forContext('provision')
            ->get();
        
        // Prepare computation services info
        $computationServices = [];
        foreach ($provisionTemplates as $type => $template) {
            if (ProvisionComputationFactory::hasComputation($type)) {
                $service = ProvisionComputationFactory::getService($type);
                $computationServices[$type] = [
                    'input_fields' => $service->getInputFields(),
                    'enabled' => true,
                ];
            } else {
                $computationServices[$type] = ['enabled' => false];
            }
        }

        return view('accounting.provisions.edit', compact(
            'provision',
            'branches',
            'expenseAccounts',
            'provisionAccounts',
            'financeCostAccounts',
            'provisionTemplates',
            'activeDiscountRates',
            'computationServices'
        ));
    }

    public function update(Request $request, string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $provision = Provision::findOrFail($id);
        if ($provision->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        // Only allow updating if in draft, rejected, or pending_approval status
        if (!in_array($provision->status, ['draft', 'rejected', 'pending_approval'])) {
            return redirect()
                ->route('accounting.provisions.show', $encodedId)
                ->with('error', 'Only draft, rejected, or pending approval provisions can be updated.');
        }

        $validated = $request->validate([
            'provision_type' => 'required|in:legal_claim,warranty,onerous_contract,environmental,restructuring,employee_benefit,other',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'present_obligation_type' => 'nullable|in:legal,constructive',
            'has_present_obligation' => 'required|boolean',
            'probability' => 'required|in:remote,possible,probable,virtually_certain',
            'probability_percent' => 'nullable|numeric|min:0|max:100',
            'estimate_method' => 'required|in:best_estimate,expected_value,most_likely_outcome',
            'computation_assumptions' => 'nullable|array',
            'currency_code' => 'required|string|size:3',
            'fx_rate_at_creation' => 'nullable|numeric|min:0.000001',
            'is_discounted' => 'nullable|boolean',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'discount_rate_id' => 'nullable|exists:discount_rates,id',
            'expected_settlement_date' => 'nullable|date',
            'undiscounted_amount' => 'nullable|numeric|min:0',
            'related_asset_id' => 'nullable|integer',
            'asset_category' => 'nullable|string|max:100',
            'is_capitalised' => 'nullable|boolean',
            'depreciation_start_date' => 'nullable|date',
            'expense_account_id' => 'required|exists:chart_accounts,id',
            'provision_account_id' => 'required|exists:chart_accounts,id',
            'unwinding_account_id' => 'nullable|exists:chart_accounts,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();

            // Update provision fields (but don't change amounts if already active - use remeasurement instead)
            $provision->provision_type = $validated['provision_type'];
            $provision->title = $validated['title'];
            $provision->description = $validated['description'];
            $provision->present_obligation_type = $validated['present_obligation_type'] ?? null;
            $provision->has_present_obligation = (bool) $validated['has_present_obligation'];
            $provision->probability = $validated['probability'];
            $provision->probability_percent = $validated['probability_percent'] ?? null;
            $provision->estimate_method = $validated['estimate_method'];
            $provision->currency_code = $validated['currency_code'];
            $provision->fx_rate_at_creation = $validated['fx_rate_at_creation'] ?? $provision->fx_rate_at_creation;
            $provision->is_discounted = (bool) ($validated['is_discounted'] ?? false);
            $provision->discount_rate = $validated['discount_rate'] ?? null;
            $provision->discount_rate_id = $validated['discount_rate_id'] ?? null;
            $provision->expected_settlement_date = $validated['expected_settlement_date'] ? Carbon::parse($validated['expected_settlement_date'])->toDateString() : null;
            $provision->undiscounted_amount = $validated['undiscounted_amount'] ?? null;
            $provision->related_asset_id = $validated['related_asset_id'] ?? null;
            $provision->asset_category = $validated['asset_category'] ?? null;
            $provision->is_capitalised = (bool) ($validated['is_capitalised'] ?? false);
            $provision->depreciation_start_date = $validated['depreciation_start_date'] ? Carbon::parse($validated['depreciation_start_date'])->toDateString() : null;
            $provision->expense_account_id = $validated['expense_account_id'];
            $provision->provision_account_id = $validated['provision_account_id'];
            $provision->unwinding_account_id = $validated['unwinding_account_id'] ?? null;
            $provision->branch_id = $validated['branch_id'] ?? $provision->branch_id;
            $provision->computation_assumptions = $validated['computation_assumptions'] ?? $provision->computation_assumptions;
            $provision->updated_by = $user->id;
            $provision->save();

            DB::commit();

            return redirect()
                ->route('accounting.provisions.show', $encodedId)
                ->with('success', 'Provision updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update provision: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $provision = Provision::with(['company', 'branch', 'expenseAccount', 'provisionAccount', 'unwindingAccount', 'movements'])
            ->findOrFail($id);

        if ($provision->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        // Approval summary (levels, history, etc.)
        $approvalSummary = $this->approvalService->getApprovalStatusSummary($provision);

        return view('accounting.provisions.show', compact('provision', 'approvalSummary'));
    }

    /**
     * Filter accounts based on template restrictions
     * Uses account name pattern matching since we don't have explicit account type tags
     */
    private function filterAccountsByTemplate($query, array $restrictions): \Illuminate\Database\Eloquent\Builder
    {
        // Map restriction keywords to account name patterns
        $patternMap = [
            'legal_expense' => ['legal', 'lawsuit', 'litigation', 'claim'],
            'warranty_expense' => ['warranty'],
            'onerous_expense' => ['onerous', 'contract'],
            'environmental_expense' => ['environmental', 'restoration', 'decommission'],
            'restructuring_expense' => ['restructuring'],
            'employee_benefit_expense' => ['employee benefit', 'staff cost', 'personnel'],
            'legal_provision' => ['legal', 'lawsuit', 'litigation', 'claim'],
            'warranty_provision' => ['warranty'],
            'onerous_provision' => ['onerous', 'contract'],
            'environmental_provision' => ['environmental', 'restoration', 'decommission'],
            'restructuring_provision' => ['restructuring'],
            'employee_benefit_provision' => ['employee benefit'],
            'other_provision' => ['provision', 'contingent'],
            'ppe' => ['asset', 'ppe', 'property', 'plant', 'equipment'],
            'finance_cost' => ['finance', 'interest', 'unwinding', 'discount'],
        ];

        $query->where(function ($q) use ($restrictions, $patternMap) {
            foreach ($restrictions as $restriction) {
                if (isset($patternMap[$restriction])) {
                    $patterns = $patternMap[$restriction];
                    $q->orWhere(function ($subQ) use ($patterns) {
                        foreach ($patterns as $pattern) {
                            $subQ->orWhere('chart_accounts.account_name', 'like', '%' . $pattern . '%')
                                ->orWhere('chart_accounts.account_code', 'like', '%' . strtoupper($pattern) . '%');
                        }
                    });
                }
            }
        });

        return $query;
    }

    /**
     * Submit provision for approval using generic ApprovalService.
     */
    public function submitForApproval(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $provision = Provision::findOrFail($id);
        if ($provision->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        if (!$this->approvalService->canUserSubmit($provision, Auth::id())) {
            return back()->with('error', 'You are not allowed to submit this provision for approval.');
        }

        try {
            $this->approvalService->submitForApproval($provision, Auth::id());
            return back()->with('success', 'Provision submitted for approval.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
    }

    /**
     * Approve provision at current level.
     */
    public function approve(string $encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $provision = Provision::findOrFail($id);
        if ($provision->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $currentLevel = $this->approvalService->getCurrentApprovalLevel($provision);
        if (!$currentLevel) {
            return back()->with('error', 'No active approval level configured for this provision.');
        }

        try {
            $this->approvalService->approve($provision, $currentLevel->id, Auth::id(), $request->input('comments'));
            return back()->with('success', 'Provision approved successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to approve provision: ' . $e->getMessage());
        }
    }

    /**
     * Reject provision at current level.
     */
    public function reject(string $encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $provision = Provision::findOrFail($id);
        if ($provision->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $currentLevel = $this->approvalService->getCurrentApprovalLevel($provision);
        if (!$currentLevel) {
            return back()->with('error', 'No active approval level configured for this provision.');
        }

        $data = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->approvalService->reject($provision, $currentLevel->id, Auth::id(), $data['reason']);
            return back()->with('success', 'Provision rejected.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to reject provision: ' . $e->getMessage());
        }
    }

    /**
     * Manual remeasurement of a provision (increase/decrease to new best estimate).
     */
    public function remeasure(string $encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $provision = Provision::findOrFail($id);
        if ($provision->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $data = $request->validate([
            'new_home_estimate' => 'required|numeric|min:0',
            'movement_date' => 'nullable|date',
            'description' => 'required|string|max:500',
        ]);

        try {
            $this->provisionService->remeasureProvision(
                $provision,
                (float) $data['new_home_estimate'],
                $data['description'],
                $data['movement_date'] ?? null
            );

            return back()->with('success', 'Provision remeasured successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to remeasure provision: ' . $e->getMessage());
        }
    }

    /**
     * Manual posting of discount unwinding for a discounted provision.
     */
    public function unwind(string $encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $provision = Provision::findOrFail($id);
        if ($provision->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $data = $request->validate([
            'unwind_amount' => 'required|numeric|min:0.01',
            'movement_date' => 'nullable|date',
            'description' => 'required|string|max:500',
        ]);

        try {
            $this->provisionService->unwindDiscount(
                $provision,
                (float) $data['unwind_amount'],
                $data['description'],
                $data['movement_date'] ?? null
            );

            return back()->with('success', 'Discount unwinding posted successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to post discount unwinding: ' . $e->getMessage());
        }
    }
}


