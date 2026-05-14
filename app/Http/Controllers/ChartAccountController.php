<?php

namespace App\Http\Controllers;

use App\Models\AccountClass;
use App\Models\AccountClassGroup;
use App\Models\ChartAccount;
use App\Models\CashFlowCategory;
use App\Models\EquityCategory;
use App\Models\GlTransaction;
use App\Exports\ChartAccountTemplateExport;
use App\Imports\ChartAccountImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Vinkla\Hashids\Facades\Hashids;

class ChartAccountController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            // Handle stats request
            if ($request->has('stats')) {
                $stats = [
                    'total' => ChartAccount::count(),
                    'cash_flow' => ChartAccount::where('has_cash_flow', true)->count(),
                    'equity' => ChartAccount::where('has_equity', true)->count(),
                    'active' => ChartAccount::whereDoesntHave('glTransactions')->count(),
                ];
                return response()->json($stats);
            }

            $chartAccounts = ChartAccount::with(['accountClassGroup.accountClass', 'cashFlowCategory', 'equityCategory', 'parent'])
                ->select(['id', 'account_class_group_id', 'account_code', 'account_name', 'account_type', 'parent_id', 'has_cash_flow', 'has_equity', 'cash_flow_category_id', 'equity_category_id', 'created_at']);

            return datatables($chartAccounts)
                ->addColumn('account_class', function ($chartAccount) {
                    return $chartAccount->accountClassGroup->accountClass->name ?? 'N/A';
                })
                ->addColumn('account_group', function ($chartAccount) {
                    return $chartAccount->accountClassGroup->name ?? 'N/A';
                })
                ->editColumn('account_name', function ($chartAccount) {
                    $html = '<div>';
                    $html .= '<strong>' . $chartAccount->account_name . '</strong>';
                    $html .= '<br><small>';
                    
                    // Show account type badge
                    if ($chartAccount->account_type === 'parent') {
                        $html .= '<span class="badge bg-primary-subtle text-primary">Parent Account</span>';
                    } else {
                        $html .= '<span class="badge bg-info-subtle text-info">Child Account</span>';
                        
                        // Show parent account if it's a child
                        if ($chartAccount->parent) {
                            $html .= ' <span class="text-muted">of</span> <strong>' . $chartAccount->parent->account_code . ' - ' . $chartAccount->parent->account_name . '</strong>';
                        }
                    }
                    
                    $html .= '</small></div>';
                    return $html;
                })
                ->addColumn('cash_flow_badge', function ($chartAccount) {
                    if ($chartAccount->has_cash_flow) {
                        return '<span class="badge bg-success">Yes</span>';
                    } else {
                        return '<span class="badge bg-secondary">No</span>';
                    }
                })
                ->addColumn('cash_flow_category', function ($chartAccount) {
                    if ($chartAccount->has_cash_flow && $chartAccount->cashFlowCategory) {
                        return '<span class="badge bg-info" title="' . ($chartAccount->cashFlowCategory->description ?? '') . '">
                                    <i class="bx bx-money-withdraw me-1"></i>' . $chartAccount->cashFlowCategory->name . '
                                </span>';
                    } else {
                        return '<span class="text-muted">-</span>';
                    }
                })
                ->addColumn('equity_badge', function ($chartAccount) {
                    if ($chartAccount->has_equity) {
                        return '<span class="badge bg-success">Yes</span>';
                    } else {
                        return '<span class="badge bg-secondary">No</span>';
                    }
                })
                ->addColumn('equity_category', function ($chartAccount) {
                    if ($chartAccount->has_equity && $chartAccount->equityCategory) {
                        return '<span class="badge bg-warning" title="' . ($chartAccount->equityCategory->description ?? '') . '">
                                    <i class="bx bx-pie-chart-alt me-1"></i>' . $chartAccount->equityCategory->name . '
                                </span>';
                    } else {
                        return '<span class="text-muted">-</span>';
                    }
                })
                ->addColumn('created_at_formatted', function ($chartAccount) {
                    return $chartAccount->created_at->format('M d, Y');
                })
                ->addColumn('actions', function ($chartAccount) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('accounting.chart-accounts.show', $chartAccount->encoded_id) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . route('accounting.chart-accounts.edit', $chartAccount->encoded_id) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                    
                    // Check if account is locked (used in GL Transactions)
                    $isLocked = $chartAccount->glTransactions()->exists();
                    if ($isLocked) {
                        $actions .= '<button class="btn btn-sm btn-secondary" title="Account is used in GL Transactions and cannot be deleted" disabled><i class="bx bx-lock"></i></button>';
                    } else {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteChartAccount(\'' . $chartAccount->encoded_id . '\', \'' . $chartAccount->account_name . '\')" title="Delete"><i class="bx bx-trash"></i></button>';
                    }
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['account_name', 'cash_flow_badge', 'cash_flow_category', 'equity_badge', 'equity_category', 'actions'])
                ->make(true);
        }

        return view('chart-accounts.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accountClassGroups = AccountClassGroup::with('accountClass')->get();
        $accountClasses = AccountClass::all();
        $cashFlowCategories = CashFlowCategory::all();
        $equityCategories = EquityCategory::all();
        
        // Get all parent accounts for the dropdown
        $parentAccounts = ChartAccount::where('account_type', 'parent')
            ->with('accountClassGroup.accountClass')
            ->orderBy('account_code')
            ->get();
        
        // Build class ranges mapping
        $classRanges = [];
        foreach ($accountClasses as $class) {
            $classRanges[$class->id] = [
                'from' => $class->range_from,
                'to' => $class->range_to
            ];
        }
        
        return view('chart-accounts.create', compact('accountClassGroups', 'accountClasses', 'cashFlowCategories', 'equityCategories', 'classRanges', 'parentAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'account_class_group_id' => 'required|exists:account_class_groups,id',
            'account_code' => 'required|string|max:255|unique:chart_accounts,account_code',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:parent,child',
            'parent_id' => 'nullable|exists:chart_accounts,id|required_if:account_type,child',
            'has_cash_flow' => 'boolean',
            'has_equity' => 'boolean',
            'cash_flow_category_id' => 'nullable|exists:cash_flow_categories,id',
            'equity_category_id' => 'nullable|exists:equity_categories,id',
        ]);

        // Range validation
        $group = AccountClassGroup::with('accountClass')->find($request->account_class_group_id);
        $class = $group ? $group->accountClass : null;
        $rangeFrom = $class ? $class->range_from : null;
        $rangeTo = $class ? $class->range_to : null;
        $accountCode = (int) $request->account_code;
        if ($rangeFrom !== null && $rangeTo !== null && ($accountCode < $rangeFrom || $accountCode > $rangeTo)) {
            return back()->withErrors(['account_code' => "Account code must be between $rangeFrom and $rangeTo for the selected class."])->withInput();
        }

        // Handle boolean fields properly for unchecked checkboxes
        $data = $request->all();
        $data['has_cash_flow'] = $request->has('has_cash_flow');
        $data['has_equity'] = $request->has('has_equity');

        // Set category IDs to null if checkboxes are unchecked
        if (!$data['has_cash_flow']) {
            $data['cash_flow_category_id'] = null;
        }
        if (!$data['has_equity']) {
            $data['equity_category_id'] = null;
        }

        // Handle parent_id
        $data['parent_id'] = $request->account_type === 'child' ? $request->parent_id : null;

        ChartAccount::create($data);

        return redirect()->route('accounting.chart-accounts.index')
            ->with('success', 'Chart Account created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.chart-accounts.index')->withErrors(['Chart Account not found.']);
        }

        $chartAccount = ChartAccount::findOrFail($decoded[0]);
        $chartAccount->load(['accountClassGroup.accountClass', 'cashFlowCategory', 'equityCategory', 'parent', 'children']);
        return view('chart-accounts.show', compact('chartAccount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.chart-accounts.index')->withErrors(['Chart Account not found.']);
        }

        $chartAccount = ChartAccount::findOrFail($decoded[0]);
        $chartAccount->load(['parent', 'children']);
        
        $accountClassGroups = AccountClassGroup::with('accountClass')->get();
        $accountClasses = AccountClass::all();
        $cashFlowCategories = CashFlowCategory::all();
        $equityCategories = EquityCategory::all();
        
        // Get all parent accounts (excluding the current account to prevent circular reference)
        $parentAccounts = ChartAccount::where('account_type', 'parent')
            ->where('id', '!=', $chartAccount->id)
            ->with('accountClassGroup.accountClass')
            ->orderBy('account_code')
            ->get();
        
        // Build class ranges mapping
        $classRanges = [];
        foreach ($accountClasses as $class) {
            $classRanges[$class->id] = [
                'from' => $class->range_from,
                'to' => $class->range_to
            ];
        }
        
        return view('chart-accounts.edit', compact('chartAccount', 'accountClassGroups', 'accountClasses', 'cashFlowCategories', 'equityCategories', 'classRanges', 'parentAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        // Decode chart account ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.chart-accounts.index')->withErrors(['Chart Account not found.']);
        }

        $chartAccount = ChartAccount::findOrFail($decoded[0]);

        $request->validate([
            'account_class_group_id' => 'required|exists:account_class_groups,id',
            'account_code' => 'required|string|max:255|unique:chart_accounts,account_code,' . $chartAccount->id,
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:parent,child',
            'parent_id' => 'nullable|exists:chart_accounts,id|required_if:account_type,child',
            'has_cash_flow' => 'boolean',
            'has_equity' => 'boolean',
            'cash_flow_category_id' => 'nullable|exists:cash_flow_categories,id',
            'equity_category_id' => 'nullable|exists:equity_categories,id',
        ]);

        // Range validation
        $group = AccountClassGroup::with('accountClass')->find($request->account_class_group_id);
        $class = $group ? $group->accountClass : null;
        $rangeFrom = $class ? $class->range_from : null;
        $rangeTo = $class ? $class->range_to : null;
        $accountCode = (int) $request->account_code;
        if ($rangeFrom !== null && $rangeTo !== null && ($accountCode < $rangeFrom || $accountCode > $rangeTo)) {
            return back()->withErrors(['account_code' => "Account code must be between $rangeFrom and $rangeTo for the selected class."])->withInput();
        }

        // Handle boolean fields properly for unchecked checkboxes
        $data = $request->all();
        $data['has_cash_flow'] = $request->has('has_cash_flow');
        $data['has_equity'] = $request->has('has_equity');

        // Set category IDs to null if checkboxes are unchecked
        if (!$data['has_cash_flow']) {
            $data['cash_flow_category_id'] = null;
        }
        if (!$data['has_equity']) {
            $data['equity_category_id'] = null;
        }

        // Handle parent_id
        $data['parent_id'] = $request->account_type === 'child' ? $request->parent_id : null;

        $chartAccount->update($data);

        return redirect()->route('accounting.chart-accounts.index')
            ->with('success', 'Chart Account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $encodedId): RedirectResponse|JsonResponse
    {
        // ... (existing code)
        return redirect()->route('accounting.chart-accounts.index')
            ->with('success', 'Chart Account deleted successfully.');
    }

    /**
     * Download Chart of Accounts Excel Template.
     */
    public function downloadTemplate()
    {
        return Excel::download(new ChartAccountTemplateExport, 'chart_of_accounts_template.xlsx');
    }

    /**
     * Import Chart of Accounts from Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            Excel::import(new ChartAccountImport, $request->file('file'));
            return redirect()->back()->with('success', 'Chart of Accounts imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
