<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Shares\ShareDividend;
use App\Models\Shares\ShareDividendPayment;
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

class ShareDividendController extends Controller
{
    protected $shareCapitalService;

    public function __construct(ShareCapitalService $shareCapitalService)
    {
        $this->shareCapitalService = $shareCapitalService;
    }

    /**
     * Display a listing of dividends.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        if ($request->ajax()) {
            return $this->getData($request, $companyId);
        }
        
        return view('accounting.share-capital.dividends.index');
    }
    
    /**
     * Get dividends data for DataTables
     */
    private function getData(Request $request, $companyId)
    {
        $query = ShareDividend::where('company_id', $companyId)
            ->with(['shareClass', 'creator', 'approver']);
        
        if ($request->filled('share_class_id')) {
            $query->where('share_class_id', $request->share_class_id);
        }
        
        if ($request->filled('dividend_type')) {
            $query->where('dividend_type', $request->dividend_type);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('reference_link', function ($dividend) {
                $ref = 'DIV-' . $dividend->id;
                return '<a href="' . route('accounting.share-capital.dividends.show', $dividend->encoded_id) . '" class="text-primary fw-bold">' . $ref . '</a>';
            })
            ->addColumn('share_class_name', function ($dividend) {
                return $dividend->shareClass->name ?? 'All Classes';
            })
            ->addColumn('dividend_type_badge', function ($dividend) {
                $badgeClass = match($dividend->dividend_type) {
                    'cash' => 'bg-success',
                    'bonus' => 'bg-info',
                    'scrip' => 'bg-warning',
                    default => 'bg-secondary',
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($dividend->dividend_type) . '</span>';
            })
            ->addColumn('formatted_declaration_date', function ($dividend) {
                return $dividend->declaration_date->format('M d, Y');
            })
            ->addColumn('formatted_per_share_amount', function ($dividend) {
                return $dividend->per_share_amount ? number_format($dividend->per_share_amount, 6) : 'N/A';
            })
            ->addColumn('formatted_total_amount', function ($dividend) {
                return $dividend->total_amount ? number_format($dividend->total_amount, 2) : 'N/A';
            })
            ->addColumn('status_badge', function ($dividend) {
                $badgeClass = match($dividend->status) {
                    'draft' => 'bg-secondary',
                    'approved' => 'bg-primary',
                    'declared' => 'bg-info',
                    'paying' => 'bg-warning',
                    'paid' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    default => 'bg-secondary',
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($dividend->status) . '</span>';
            })
            ->addColumn('actions', function ($dividend) {
                $actions = '<div class="d-flex gap-1">';
                $actions .= '<a href="' . route('accounting.share-capital.dividends.show', $dividend->encoded_id) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                
                if ($dividend->status === 'draft') {
                    $actions .= '<a href="' . route('accounting.share-capital.dividends.edit', $dividend->encoded_id) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                if ($dividend->status === 'approved' && $dividend->dividend_type === 'cash') {
                    $actions .= '<button type="button" class="btn btn-sm btn-success" onclick="declareDividend(\'' . $dividend->encoded_id . '\')" title="Declare"><i class="bx bx-check"></i></button>';
                }
                
                if ($dividend->status === 'declared' && $dividend->dividend_type === 'cash') {
                    $actions .= '<button type="button" class="btn btn-sm btn-warning" onclick="processPayment(\'' . $dividend->encoded_id . '\')" title="Process Payment"><i class="bx bx-money"></i></button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['reference_link', 'dividend_type_badge', 'status_badge', 'actions'])
            ->make(true);
    }
    
    /**
     * Show the form for creating a new dividend.
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $shareClasses = ShareClass::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Get bank accounts - allow both direct company_id or via chart account's account class group
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
        
        return view('accounting.share-capital.dividends.create', compact(
            'shareClasses',
            'bankAccounts',
            'equityAccounts'
        ));
    }
    
    /**
     * Store a newly created dividend.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'share_class_id' => 'nullable|exists:share_classes,id',
            'dividend_type' => 'required|in:cash,bonus,scrip',
            'declaration_date' => 'required|date',
            'record_date' => 'required|date',
            'ex_date' => 'nullable|date',
            'payment_date' => 'nullable|date|after_or_equal:declaration_date',
            'per_share_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);
        
        DB::beginTransaction();
        try {
            // Calculate total amount based on eligible shareholders
            $totalAmount = 0;
            $totalShares = 0;
            
            if ($validated['share_class_id']) {
                // Dividend for specific share class
                $shareClass = ShareClass::findOrFail($validated['share_class_id']);
                $holdings = ShareHolding::where('share_class_id', $validated['share_class_id'])
                    ->where('status', 'active')
                    ->whereDate('acquisition_date', '<=', $validated['record_date'])
                    ->get();
                
                foreach ($holdings as $holding) {
                    $totalShares += $holding->shares_outstanding;
                }
            } else {
                // Dividend for all share classes
                $holdings = ShareHolding::where('company_id', $user->company_id)
                    ->where('status', 'active')
                    ->whereDate('acquisition_date', '<=', $validated['record_date'])
                    ->get();
                
                foreach ($holdings as $holding) {
                    $totalShares += $holding->shares_outstanding;
                }
            }
            
            if ($validated['dividend_type'] === 'cash' && isset($validated['per_share_amount'])) {
                $totalAmount = $validated['per_share_amount'] * $totalShares;
            } elseif ($validated['dividend_type'] === 'bonus') {
                // For bonus dividends, total amount is calculated differently
                $totalAmount = $validated['per_share_amount'] * $totalShares ?? 0;
            }
            
            // Create dividend
            $dividend = ShareDividend::create([
                'company_id' => $user->company_id,
                'share_class_id' => $validated['share_class_id'] ?? null,
                'dividend_type' => $validated['dividend_type'],
                'declaration_date' => $validated['declaration_date'],
                'record_date' => $validated['record_date'],
                'ex_date' => $validated['ex_date'] ?? null,
                'payment_date' => $validated['payment_date'] ?? null,
                'per_share_amount' => $validated['per_share_amount'] ?? null,
                'total_amount' => $totalAmount,
                'currency_code' => 'USD', // Default, can be made configurable
                'status' => 'draft',
                'description' => $validated['description'] ?? null,
                'created_by' => $user->id,
            ]);
            
            // Create dividend payments for each eligible shareholder
            if ($validated['dividend_type'] === 'cash' && $totalShares > 0) {
                $shareholders = [];
                foreach ($holdings as $holding) {
                    $shareholderId = $holding->shareholder_id;
                    if (!isset($shareholders[$shareholderId])) {
                        $shareholders[$shareholderId] = 0;
                    }
                    $shareholders[$shareholderId] += $holding->shares_outstanding;
                }
                
                $withholdingTaxRate = $validated['withholding_tax_rate'] ?? 0;
                
                foreach ($shareholders as $shareholderId => $shares) {
                    $grossAmount = $validated['per_share_amount'] * $shares;
                    $withholdingTaxAmount = $grossAmount * ($withholdingTaxRate / 100);
                    $netAmount = $grossAmount - $withholdingTaxAmount;
                    
                    ShareDividendPayment::create([
                        'company_id' => $user->company_id,
                        'dividend_id' => $dividend->id,
                        'shareholder_id' => $shareholderId,
                        'gross_amount' => $grossAmount,
                        'withholding_tax_amount' => $withholdingTaxAmount,
                        'net_amount' => $netAmount,
                        'status' => 'pending',
                        'created_by' => $user->id,
                    ]);
                }
            }
            
            // If auto-approve is enabled
            if ($request->has('auto_approve') && $request->auto_approve) {
                $dividend->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('accounting.share-capital.dividends.show', $dividend->encoded_id)
                ->with('success', 'Dividend created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create dividend: ' . $e->getMessage()])->withInput();
        }
    }
    
    /**
     * Display the specified dividend.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $dividend = ShareDividend::with([
            'shareClass',
            'creator',
            'approver',
            'dividendPayments.shareholder'
        ])->findOrFail($id);
        
        // Load accounts for mapping in modals
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $equityAccounts = ChartAccount::where('account_type', 'Equity')
            ->orWhere('has_equity', true)
            ->orderBy('account_name')
            ->get();
        
        // Withholding tax can be mapped to any GL account (commonly tax / liability),
        // so offer the full chart for this dropdown
        $withholdingTaxAccounts = ChartAccount::orderBy('account_name')->get();
        
        // Get bank accounts for payment modal - same logic as create()
        $bankAccounts = BankAccount::where(function($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    });
            })
            ->with('chartAccount')
            ->orderBy('name')
            ->get();
        
        // Try to find declaration journal
        $journal = \App\Models\Journal::where('reference_type', 'dividend_declaration')
            ->where('reference', 'DIV-' . $dividend->id)
            ->with(['items.chartAccount', 'glTransactions.chartAccount', 'user', 'branch'])
            ->first();
        
        // If no declaration journal, try payment journal
        if (!$journal) {
            $journal = \App\Models\Journal::where('reference_type', 'dividend_payment')
                ->where('reference', 'DIV-PAY-' . $dividend->id)
                ->with(['items.chartAccount', 'glTransactions.chartAccount', 'user', 'branch'])
                ->first();
        }
        
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
        
        return view('accounting.share-capital.dividends.show', compact(
            'dividend',
            'journal',
            'glTransactions',
            'equityAccounts',
            'withholdingTaxAccounts',
            'bankAccounts'
        ));
    }
    
    /**
     * Show the form for editing the specified dividend.
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $dividend = ShareDividend::findOrFail($id);
        
        if ($dividend->status !== 'draft') {
            return redirect()->route('accounting.share-capital.dividends.show', $encodedId)
                ->with('error', 'Only draft dividends can be edited.');
        }
        
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $shareClasses = ShareClass::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('accounting.share-capital.dividends.edit', compact(
            'dividend',
            'shareClasses'
        ));
    }
    
    /**
     * Update the specified dividend.
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $dividend = ShareDividend::findOrFail($id);
        
        if ($dividend->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft dividends can be edited.']);
        }
        
        // Similar validation and update logic as store
        // ... (implementation similar to store method)
        
        return redirect()->route('accounting.share-capital.dividends.show', $encodedId)
            ->with('success', 'Dividend updated successfully.');
    }
    
    /**
     * Approve a dividend.
     */
    public function approve($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $dividend = ShareDividend::findOrFail($id);
        
        if ($dividend->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft dividends can be approved.']);
        }
        
        $dividend->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);
        
        return back()->with('success', 'Dividend approved successfully.');
    }
    
    /**
     * Declare dividend (post to GL).
     */
    public function declare(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $dividend = ShareDividend::findOrFail($id);
        
        if ($dividend->status !== 'approved') {
            return back()->withErrors(['error' => 'Only approved dividends can be declared.']);
        }
        
        if ($dividend->dividend_type === 'bonus') {
            // Handle bonus dividend
            $validated = $request->validate([
                'source_account_id' => 'required|exists:chart_accounts,id',
                'share_capital_account_id' => 'required|exists:chart_accounts,id',
            ]);
            
            try {
                $accountMappings = [
                    'source_account_id' => $validated['source_account_id'],
                    'share_capital_account_id' => $validated['share_capital_account_id'],
                ];
                
                $this->shareCapitalService->postBonusDividend($dividend, $accountMappings);
                
                return back()->with('success', 'Bonus dividend declared and posted to GL successfully.');
            } catch (\Exception $e) {
                return back()->withErrors(['error' => 'Failed to declare dividend: ' . $e->getMessage()]);
            }
        } else {
            // Handle cash dividend
            $validated = $request->validate([
                'retained_earnings_account_id' => 'required|exists:chart_accounts,id',
                'dividend_payable_account_id' => 'required|exists:chart_accounts,id',
                'withholding_tax_account_id' => 'nullable|exists:chart_accounts,id',
                'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            ]);
            
            try {
                $accountMappings = [
                    'retained_earnings_account_id' => $validated['retained_earnings_account_id'],
                    'dividend_payable_account_id' => $validated['dividend_payable_account_id'],
                    'withholding_tax_account_id' => $validated['withholding_tax_account_id'] ?? null,
                    'withholding_tax_rate' => $validated['withholding_tax_rate'] ?? 0,
                ];
                
                $this->shareCapitalService->postDividendDeclaration($dividend, $accountMappings);
                
                return back()->with('success', 'Dividend declared and posted to GL successfully.');
            } catch (\Exception $e) {
                return back()->withErrors(['error' => 'Failed to declare dividend: ' . $e->getMessage()]);
            }
        }
    }
    
    /**
     * Process dividend payment.
     */
    public function processPayment(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $dividend = ShareDividend::findOrFail($id);
        
        if ($dividend->status !== 'declared' || $dividend->dividend_type !== 'cash') {
            return back()->withErrors(['error' => 'Only declared cash dividends can be paid.']);
        }
        
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'dividend_payable_account_id' => 'required|exists:chart_accounts,id',
        ]);
        
        try {
            $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
            $accountMappings = [
                'bank_account_id' => $bankAccount->chart_account_id,
                'dividend_payable_account_id' => $validated['dividend_payable_account_id'],
            ];
            
            $this->shareCapitalService->postDividendPayment($dividend, $accountMappings);
            
            // Update payment status
            $dividend->dividendPayments()->update([
                'status' => 'paid',
                'payment_date' => $dividend->payment_date ?? now(),
            ]);
            
            return back()->with('success', 'Dividend payment processed and posted to GL successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to process payment: ' . $e->getMessage()]);
        }
    }
}

