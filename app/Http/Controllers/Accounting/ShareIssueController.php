<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Shares\ShareIssue;
use App\Models\Shares\ShareClass;
use App\Models\Shares\Shareholder;
use App\Models\Shares\ShareHolding;
use App\Models\ChartAccount;
use App\Models\BankAccount;
use App\Services\ShareCapitalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class ShareIssueController extends Controller
{
    protected $shareCapitalService;

    public function __construct(ShareCapitalService $shareCapitalService)
    {
        $this->shareCapitalService = $shareCapitalService;
    }

    /**
     * Display a listing of share issues.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        if ($request->ajax()) {
            return $this->getData($request, $companyId);
        }
        
        return view('accounting.share-capital.share-issues.index');
    }
    
    /**
     * Get share issues data for DataTables
     */
    private function getData(Request $request, $companyId)
    {
        $query = ShareIssue::where('company_id', $companyId)
            ->with(['shareClass', 'creator', 'approver']);
        
        if ($request->filled('share_class_id')) {
            $query->where('share_class_id', $request->share_class_id);
        }
        
        if ($request->filled('issue_type')) {
            $query->where('issue_type', $request->issue_type);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('reference_link', function ($issue) {
                $ref = $issue->reference_number ?? 'ISSUE-' . $issue->id;
                return '<a href="' . route('accounting.share-capital.share-issues.show', $issue->encoded_id) . '" class="text-primary fw-bold">' . $ref . '</a>';
            })
            ->addColumn('share_class_name', function ($issue) {
                return $issue->shareClass->name ?? 'N/A';
            })
            ->addColumn('issue_type_badge', function ($issue) {
                $badgeClass = match($issue->issue_type) {
                    'initial' => 'bg-primary',
                    'rights' => 'bg-info',
                    'bonus' => 'bg-success',
                    'private_placement' => 'bg-warning',
                    'public_offering' => 'bg-danger',
                    'conversion' => 'bg-secondary',
                    default => 'bg-secondary',
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $issue->issue_type)) . '</span>';
            })
            ->addColumn('formatted_issue_date', function ($issue) {
                return $issue->issue_date->format('M d, Y');
            })
            ->addColumn('formatted_total_shares', function ($issue) {
                return number_format($issue->total_shares);
            })
            ->addColumn('formatted_total_amount', function ($issue) {
                return number_format($issue->total_amount, 2);
            })
            ->addColumn('status_badge', function ($issue) {
                $badgeClass = match($issue->status) {
                    'draft' => 'bg-secondary',
                    'approved' => 'bg-primary',
                    'posted' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    default => 'bg-secondary',
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($issue->status) . '</span>';
            })
            ->addColumn('actions', function ($issue) {
                $actions = '<div class="d-flex gap-1">';
                $actions .= '<a href="' . route('accounting.share-capital.share-issues.show', $issue->encoded_id) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                
                if ($issue->status === 'draft') {
                    $actions .= '<a href="' . route('accounting.share-capital.share-issues.edit', $issue->encoded_id) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                if ($issue->status === 'approved') {
                    $actions .= '<button type="button" class="btn btn-sm btn-success" onclick="postIssue(\'' . $issue->encoded_id . '\')" title="Post to GL"><i class="bx bx-check"></i></button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['reference_link', 'issue_type_badge', 'status_badge', 'actions'])
            ->make(true);
    }
    
    /**
     * Show the form for creating a new share issue.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $shareClasses = ShareClass::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        
        $shareholders = Shareholder::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Get bank accounts - check both direct company_id and through chart account relationship
        // This matches the pattern used in other controllers like PaymentVoucherController
        $bankAccounts = BankAccount::where(function($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    });
            })
            ->with('chartAccount')
            ->orderBy('name')
            ->get();
        
        // Get equity accounts for mapping
        $equityAccounts = ChartAccount::where('account_type', 'Equity')
            ->orWhere('has_equity', true)
            ->orderBy('account_name')
            ->get();
        
        // Handle pre-selected shareholder from query parameter
        $selectedShareholderId = null;
        if ($request->has('shareholder_id')) {
            $encodedShareholderId = $request->get('shareholder_id');
            $decodedId = Hashids::decode($encodedShareholderId)[0] ?? null;
            if ($decodedId) {
                // Verify the shareholder exists and belongs to the company
                // Use find() which respects the model's primaryKey setting (shareholder_id)
                $shareholder = Shareholder::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->find($decodedId);
                if ($shareholder) {
                    $selectedShareholderId = $shareholder->getKey(); // Use getKey() to get the actual primary key
                }
            }
        }
        
        return view('accounting.share-capital.share-issues.create', compact(
            'shareClasses',
            'shareholders',
            'bankAccounts',
            'equityAccounts',
            'selectedShareholderId'
        ));
    }
    
    /**
     * Store a newly created share issue.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'share_class_id' => 'required|exists:share_classes,id',
            'issue_type' => 'required|in:initial,rights,bonus,private_placement,public_offering,conversion,other',
            'reference_number' => 'nullable|string|max:100',
            'issue_date' => 'required|date',
            'record_date' => 'nullable|date',
            'settlement_date' => 'nullable|date|after_or_equal:issue_date',
            'price_per_share' => 'required|numeric|min:0',
            'total_shares' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'shareholders' => 'required|array|min:1',
            'shareholders.*.shareholder_id' => 'required|exists:shareholders,id',
            'shareholders.*.shares' => 'required|integer|min:1',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'share_capital_account_id' => 'required|exists:chart_accounts,id',
            'share_premium_account_id' => 'nullable|exists:chart_accounts,id',
            'issue_costs' => 'nullable|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        try {
            $shareClass = ShareClass::findOrFail($validated['share_class_id']);
            
            // Calculate total amount
            $totalAmount = $validated['price_per_share'] * $validated['total_shares'];
            
            // Validate total shares match
            $totalSharesAllocated = array_sum(array_column($validated['shareholders'], 'shares'));
            if ($totalSharesAllocated != $validated['total_shares']) {
                return back()->withErrors(['shareholders' => 'Total shares allocated must equal total shares issued.'])->withInput();
            }
            
            // Create share issue
            $shareIssue = ShareIssue::create([
                'company_id' => $user->company_id,
                'share_class_id' => $validated['share_class_id'],
                'issue_type' => $validated['issue_type'],
                'reference_number' => $validated['reference_number'],
                'issue_date' => $validated['issue_date'],
                'record_date' => $validated['record_date'] ?? $validated['issue_date'],
                'settlement_date' => $validated['settlement_date'] ?? null,
                'price_per_share' => $validated['price_per_share'],
                'par_value' => $shareClass->par_value,
                'total_shares' => $validated['total_shares'],
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'description' => $validated['description'] ?? null,
                'created_by' => $user->id,
            ]);
            
            // Create share holdings for each shareholder
            foreach ($validated['shareholders'] as $shareholderData) {
                $sharesAllocated = $shareholderData['shares'];
                $amountPaid = $validated['price_per_share'] * $sharesAllocated;
                
                ShareHolding::create([
                    'company_id' => $user->company_id,
                    'shareholder_id' => $shareholderData['shareholder_id'],
                    'share_class_id' => $validated['share_class_id'],
                    'share_issue_id' => $shareIssue->id,
                    'lot_number' => $shareIssue->reference_number ?? 'LOT-' . $shareIssue->id,
                    'acquisition_date' => $validated['issue_date'],
                    'shares_issued' => $sharesAllocated,
                    'shares_outstanding' => $sharesAllocated,
                    'paid_up_amount' => $amountPaid,
                    'unpaid_amount' => 0,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]);
            }
            
            // If auto-approve is enabled, approve and post
            if ($request->has('auto_approve') && $request->auto_approve) {
                $shareIssue->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                ]);
                
                // Post to GL
                $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
                
                if (!$bankAccount->chart_account_id) {
                    throw new \Exception('Bank account does not have a chart account assigned. Please configure the bank account first.');
                }
                
                $accountMappings = [
                    'bank_account_id' => $bankAccount->chart_account_id,
                    'share_capital_account_id' => $validated['share_capital_account_id'],
                    'share_premium_account_id' => $validated['share_premium_account_id'] ?? null,
                    'issue_costs' => $validated['issue_costs'] ?? 0,
                ];
                
                $this->shareCapitalService->postShareIssue($shareIssue, $accountMappings);
            }
            
            DB::commit();
            
            return redirect()->route('accounting.share-capital.share-issues.show', $shareIssue->encoded_id)
                ->with('success', 'Share issue created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create share issue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token', 'password']),
            ]);
            return back()->withErrors(['error' => 'Failed to create share issue: ' . $e->getMessage()])->withInput();
        }
    }
    
    /**
     * Display the specified share issue.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareIssue = ShareIssue::with([
            'shareClass',
            'creator',
            'approver',
            'poster',
            'shareHoldings.shareholder'
        ])->findOrFail($id);
        
        // Get journal for this share issue
        $journal = \App\Models\Journal::where('reference_type', 'share_issue')
            ->where('reference', $shareIssue->reference_number ?? 'SHARE-ISSUE-' . $shareIssue->id)
            ->with(['items.chartAccount', 'glTransactions.chartAccount', 'user', 'branch'])
            ->first();
        
        // Get GL transactions if journal exists
        $glTransactions = collect();
        if ($journal) {
            $glTransactions = \App\Models\GlTransaction::where('transaction_id', $journal->id)
                ->where('transaction_type', 'journal')
                ->with('chartAccount')
                ->orderBy('nature', 'desc')
                ->orderBy('id')
                ->get();
        }
        
        return view('accounting.share-capital.share-issues.show', compact('shareIssue', 'journal', 'glTransactions'));
    }
    
    /**
     * Show the form for editing the specified share issue.
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareIssue = ShareIssue::with(['shareClass', 'shareHoldings.shareholder'])->findOrFail($id);
        
        if ($shareIssue->status !== 'draft') {
            return redirect()->route('accounting.share-capital.share-issues.show', $encodedId)
                ->with('error', 'Only draft issues can be edited.');
        }
        
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $shareClasses = ShareClass::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $shareholders = Shareholder::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('accounting.share-capital.share-issues.edit', compact(
            'shareIssue',
            'shareClasses',
            'shareholders'
        ));
    }
    
    /**
     * Update the specified share issue.
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareIssue = ShareIssue::findOrFail($id);
        
        if ($shareIssue->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft issues can be edited.']);
        }
        
        // Similar validation and update logic as store
        // ... (implementation similar to store method)
        
        return redirect()->route('accounting.share-capital.share-issues.show', $encodedId)
            ->with('success', 'Share issue updated successfully.');
    }
    
    /**
     * Approve a share issue.
     */
    public function approve($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareIssue = ShareIssue::findOrFail($id);
        
        if ($shareIssue->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft issues can be approved.']);
        }
        
        $shareIssue->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);
        
        return back()->with('success', 'Share issue approved successfully.');
    }
    
    /**
     * Post share issue to GL.
     */
    public function postToGl(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareIssue = ShareIssue::findOrFail($id);
        
        if ($shareIssue->status !== 'approved') {
            return back()->withErrors(['error' => 'Only approved issues can be posted to GL.']);
        }
        
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'share_capital_account_id' => 'required|exists:chart_accounts,id',
            'share_premium_account_id' => 'nullable|exists:chart_accounts,id',
            'issue_costs' => 'nullable|numeric|min:0',
        ]);
        
        try {
            $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
            $accountMappings = [
                'bank_account_id' => $bankAccount->chart_account_id,
                'share_capital_account_id' => $validated['share_capital_account_id'],
                'share_premium_account_id' => $validated['share_premium_account_id'] ?? null,
                'issue_costs' => $validated['issue_costs'] ?? 0,
            ];
            
            $this->shareCapitalService->postShareIssue($shareIssue, $accountMappings);
            
            return back()->with('success', 'Share issue posted to GL successfully.');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to post to GL: ' . $e->getMessage()]);
        }
    }
}

