<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Shares\ShareCorporateAction;
use App\Models\Shares\ShareClass;
use App\Models\Shares\ShareHolding;
use App\Models\ChartAccount;
use App\Models\BankAccount;
use App\Services\ShareCapitalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class ShareCorporateActionController extends Controller
{
    protected $shareCapitalService;

    public function __construct(ShareCapitalService $shareCapitalService)
    {
        $this->shareCapitalService = $shareCapitalService;
    }

    /**
     * Display a listing of corporate actions.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        if ($request->ajax()) {
            return $this->getData($request, $companyId);
        }
        
        return view('accounting.share-capital.corporate-actions.index');
    }
    
    /**
     * Get corporate actions data for DataTables
     */
    private function getData(Request $request, $companyId)
    {
        $query = ShareCorporateAction::where('company_id', $companyId)
            ->with(['shareClass', 'creator', 'approver', 'executor']);
        
        if ($request->filled('share_class_id')) {
            $query->where('share_class_id', $request->share_class_id);
        }
        
        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('reference_link', function ($action) {
                $ref = $action->reference_number ?? 'CA-' . $action->id;
                return '<a href="' . route('accounting.share-capital.corporate-actions.show', $action->encoded_id) . '" class="text-primary fw-bold">' . $ref . '</a>';
            })
            ->addColumn('share_class_name', function ($action) {
                return $action->shareClass->name ?? 'All Classes';
            })
            ->addColumn('action_type_badge', function ($action) {
                $badgeClass = match($action->action_type) {
                    'split' => 'bg-info',
                    'reverse_split' => 'bg-warning',
                    'buyback' => 'bg-danger',
                    'conversion' => 'bg-primary',
                    'bonus' => 'bg-success',
                    'rights' => 'bg-secondary',
                    default => 'bg-secondary',
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $action->action_type)) . '</span>';
            })
            ->addColumn('formatted_effective_date', function ($action) {
                return $action->effective_date ? $action->effective_date->format('M d, Y') : 'N/A';
            })
            ->addColumn('ratio_display', function ($action) {
                if ($action->ratio_numerator && $action->ratio_denominator) {
                    return $action->ratio_numerator . ':' . $action->ratio_denominator;
                }
                return 'N/A';
            })
            ->addColumn('status_badge', function ($action) {
                $badgeClass = match($action->status) {
                    'draft' => 'bg-secondary',
                    'pending_approval' => 'bg-warning',
                    'approved' => 'bg-primary',
                    'executed' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    default => 'bg-secondary',
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $action->status)) . '</span>';
            })
            ->addColumn('actions', function ($action) {
                $actions = '<div class="d-flex gap-1">';
                $actions .= '<a href="' . route('accounting.share-capital.corporate-actions.show', $action->encoded_id) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                
                if ($action->status === 'draft') {
                    $actions .= '<a href="' . route('accounting.share-capital.corporate-actions.edit', $action->encoded_id) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                if ($action->status === 'approved') {
                    $actions .= '<button type="button" class="btn btn-sm btn-success" onclick="executeAction(\'' . $action->encoded_id . '\')" title="Execute"><i class="bx bx-check"></i></button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['reference_link', 'action_type_badge', 'status_badge', 'actions'])
            ->make(true);
    }
    
    /**
     * Show the form for creating a new corporate action.
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $shareClasses = ShareClass::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $bankAccounts = BankAccount::where('company_id', $companyId)
            ->with('chartAccount')
            ->get();
        
        // Get equity accounts for mapping
        $equityAccounts = ChartAccount::where('account_type', 'Equity')
            ->orWhere('has_equity', true)
            ->orderBy('account_name')
            ->get();
        
        return view('accounting.share-capital.corporate-actions.create', compact(
            'shareClasses',
            'bankAccounts',
            'equityAccounts'
        ));
    }
    
    /**
     * Store a newly created corporate action.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'share_class_id' => 'nullable|exists:share_classes,id',
            'action_type' => 'required|in:split,reverse_split,buyback,conversion,bonus,rights,forfeiture,call,other',
            'reference_number' => 'nullable|string|max:100',
            'record_date' => 'nullable|date',
            'ex_date' => 'nullable|date',
            'effective_date' => 'required|date',
            'ratio_numerator' => 'nullable|numeric|min:0',
            'ratio_denominator' => 'nullable|numeric|min:1',
            'price_per_share' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            // Buyback specific
            'total_shares' => 'nullable|integer|min:1',
            'total_cost' => 'nullable|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        try {
            $corporateAction = ShareCorporateAction::create([
                'company_id' => $user->company_id,
                'share_class_id' => $validated['share_class_id'] ?? null,
                'action_type' => $validated['action_type'],
                'reference_number' => $validated['reference_number'],
                'record_date' => $validated['record_date'] ?? null,
                'ex_date' => $validated['ex_date'] ?? null,
                'effective_date' => $validated['effective_date'],
                'ratio_numerator' => $validated['ratio_numerator'] ?? null,
                'ratio_denominator' => $validated['ratio_denominator'] ?? null,
                'price_per_share' => $validated['price_per_share'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);
            
            // If auto-approve is enabled
            if ($request->has('auto_approve') && $request->auto_approve) {
                $corporateAction->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('accounting.share-capital.corporate-actions.show', $corporateAction->encoded_id)
                ->with('success', 'Corporate action created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create corporate action: ' . $e->getMessage()])->withInput();
        }
    }
    
    /**
     * Display the specified corporate action.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $action = ShareCorporateAction::with([
            'shareClass',
            'creator',
            'approver',
            'executor'
        ])->findOrFail($id);
        
        return view('accounting.share-capital.corporate-actions.show', compact('action'));
    }
    
    /**
     * Show the form for editing the specified corporate action.
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $action = ShareCorporateAction::findOrFail($id);
        
        if ($action->status !== 'draft') {
            return redirect()->route('accounting.share-capital.corporate-actions.show', $encodedId)
                ->with('error', 'Only draft actions can be edited.');
        }
        
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $shareClasses = ShareClass::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('accounting.share-capital.corporate-actions.edit', compact(
            'action',
            'shareClasses'
        ));
    }
    
    /**
     * Update the specified corporate action.
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $action = ShareCorporateAction::findOrFail($id);
        
        if ($action->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft actions can be edited.']);
        }
        
        $validated = $request->validate([
            'share_class_id' => 'nullable|exists:share_classes,id',
            'action_type' => 'required|in:split,reverse_split,buyback,conversion,bonus,rights,forfeiture,call,other',
            'reference_number' => 'nullable|string|max:100',
            'record_date' => 'nullable|date',
            'ex_date' => 'nullable|date',
            'effective_date' => 'required|date',
            'ratio_numerator' => 'nullable|numeric|min:0',
            'ratio_denominator' => 'nullable|numeric|min:1',
            'price_per_share' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        $action->update($validated);
        
        return redirect()->route('accounting.share-capital.corporate-actions.show', $encodedId)
            ->with('success', 'Corporate action updated successfully.');
    }
    
    /**
     * Approve a corporate action.
     */
    public function approve($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $action = ShareCorporateAction::findOrFail($id);
        
        if (!in_array($action->status, ['draft', 'pending_approval'])) {
            return back()->withErrors(['error' => 'Only draft or pending approval actions can be approved.']);
        }
        
        $action->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);
        
        return back()->with('success', 'Corporate action approved successfully.');
    }
    
    /**
     * Execute a corporate action.
     */
    public function execute(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $action = ShareCorporateAction::findOrFail($id);
        
        if ($action->status !== 'approved') {
            return back()->withErrors(['error' => 'Only approved actions can be executed.']);
        }
        
        DB::beginTransaction();
        try {
            $shareClass = $action->shareClass;
            
            switch ($action->action_type) {
                case 'split':
                    $this->executeSplit($action, $shareClass);
                    break;
                    
                case 'reverse_split':
                    $this->executeReverseSplit($action, $shareClass);
                    break;
                    
                case 'buyback':
                    $validated = $request->validate([
                        'total_shares' => 'required|integer|min:1',
                        'total_cost' => 'required|numeric|min:0',
                        'bank_account_id' => 'required|exists:bank_accounts,id',
                        'treasury_shares_account_id' => 'required|exists:chart_accounts,id',
                    ]);
                    
                    $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
                    $accountMappings = [
                        'bank_account_id' => $bankAccount->chart_account_id,
                        'treasury_shares_account_id' => $validated['treasury_shares_account_id'],
                    ];
                    
                    $this->shareCapitalService->postShareBuyback(
                        $action,
                        $validated['total_shares'],
                        $validated['total_cost'],
                        $accountMappings
                    );
                    
                    $this->executeBuyback($action, $validated['total_shares']);
                    break;
                    
                case 'conversion':
                    $this->executeConversion($action, $shareClass);
                    break;
                    
                default:
                    // For other action types, just mark as executed
                    break;
            }
            
            $action->update([
                'status' => 'executed',
                'executed_by' => Auth::id(),
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Corporate action executed successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to execute action: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Execute share split.
     */
    private function executeSplit(ShareCorporateAction $action, $shareClass)
    {
        if (!$action->ratio_numerator || !$action->ratio_denominator) {
            throw new \Exception('Split ratio is required.');
        }
        
        $ratio = $action->ratio_numerator / $action->ratio_denominator;
        
        // Get all active holdings for this share class
        $holdings = ShareHolding::where('share_class_id', $action->share_class_id)
            ->where('status', 'active')
            ->get();
        
        foreach ($holdings as $holding) {
            $newShares = (int)($holding->shares_outstanding * $ratio);
            $holding->update([
                'shares_outstanding' => $newShares,
                'shares_issued' => $newShares,
            ]);
        }
        
        // Update share class par value (if applicable)
        if ($shareClass && $shareClass->has_par_value) {
            $newParValue = $shareClass->par_value / $ratio;
            $shareClass->update(['par_value' => $newParValue]);
        }
    }
    
    /**
     * Execute reverse split.
     */
    private function executeReverseSplit(ShareCorporateAction $action, $shareClass)
    {
        if (!$action->ratio_numerator || !$action->ratio_denominator) {
            throw new \Exception('Reverse split ratio is required.');
        }
        
        $ratio = $action->ratio_denominator / $action->ratio_numerator; // Inverse for reverse split
        
        // Get all active holdings for this share class
        $holdings = ShareHolding::where('share_class_id', $action->share_class_id)
            ->where('status', 'active')
            ->get();
        
        foreach ($holdings as $holding) {
            $newShares = (int)($holding->shares_outstanding * $ratio);
            $holding->update([
                'shares_outstanding' => $newShares,
                'shares_issued' => $newShares,
            ]);
        }
        
        // Update share class par value (if applicable)
        if ($shareClass && $shareClass->has_par_value) {
            $newParValue = $shareClass->par_value * ($action->ratio_numerator / $action->ratio_denominator);
            $shareClass->update(['par_value' => $newParValue]);
        }
    }
    
    /**
     * Execute buyback.
     */
    private function executeBuyback(ShareCorporateAction $action, int $totalShares)
    {
        // Mark shares as treasury (this would typically involve updating holdings)
        // For now, we'll just record the action
        // In a full implementation, you'd update share holdings to treasury status
    }
    
    /**
     * Execute conversion.
     */
    private function executeConversion(ShareCorporateAction $action, $shareClass)
    {
        // Conversion logic would depend on the specific conversion terms
        // This is a placeholder for the conversion execution
        // In a full implementation, you'd:
        // 1. Identify holdings to convert
        // 2. Create new holdings in the target share class
        // 3. Update or cancel old holdings
    }
}

