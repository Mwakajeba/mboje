<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\GlTransaction;
use App\Models\ApprovalHistory;
use App\Services\ApprovalService;
use App\Services\UnclearedItemsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class BankReconciliationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $reconciliations = BankReconciliation::with(['bankAccount', 'user'])
            ->whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('reconciliation_date', 'desc')
            ->paginate(15);

        // Calculate statistics
        $stats = [
            'total' => $reconciliations->total(),
            'completed' => BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->where('status', 'completed')->count(),
            'in_progress' => BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->where('status', 'in_progress')->count(),
            'draft' => BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->where('status', 'draft')->count(),
        ];

        return view('accounting.bank-reconciliation.index', compact('reconciliations', 'stats'));
    }

    /**
     * Get bank reconciliations data for DataTables.
     */
    public function data(Request $request)
    {
        $user = Auth::user();

        $reconciliations = BankReconciliation::with(['bankAccount', 'user'])
            ->whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->select('bank_reconciliations.*');

        return DataTables::eloquent($reconciliations)
            ->addColumn('bank_account', function ($reconciliation) {
                return [
                    'name' => $reconciliation->bankAccount->name ?? 'N/A',
                    'account_number' => $reconciliation->bankAccount->account_number ?? 'N/A',
                ];
            })
            ->addColumn('reconciliation_date', function ($reconciliation) {
                return $reconciliation->reconciliation_date 
                    ? $reconciliation->reconciliation_date->format('M d, Y') 
                    : 'N/A';
            })
            ->addColumn('period', function ($reconciliation) {
                return [
                    'start' => $reconciliation->start_date 
                        ? $reconciliation->start_date->format('M d, Y') 
                        : 'N/A',
                    'end' => $reconciliation->end_date 
                        ? $reconciliation->end_date->format('M d, Y') 
                        : 'N/A',
                ];
            })
            ->addColumn('bank_statement_balance', function ($reconciliation) {
                return number_format($reconciliation->bank_statement_balance, 2);
            })
            ->addColumn('book_balance', function ($reconciliation) {
                return number_format($reconciliation->book_balance, 2);
            })
            ->addColumn('difference', function ($reconciliation) {
                return [
                    'is_balanced' => $reconciliation->isBalanced(),
                    'formatted' => number_format($reconciliation->difference, 2),
                ];
            })
            ->addColumn('status_badge', function ($reconciliation) {
                return $reconciliation->status_badge;
            })
            ->addColumn('created_by', function ($reconciliation) {
                return [
                    'name' => $reconciliation->user->name ?? 'N/A',
                    'date' => $reconciliation->created_at 
                        ? $reconciliation->created_at->format('M d, Y') 
                        : 'N/A',
                ];
            })
            ->addColumn('show_url', function ($reconciliation) {
                return route('accounting.bank-reconciliation.show', $reconciliation->getRouteKey());
            })
            ->addColumn('export_url', function ($reconciliation) {
                return route('accounting.bank-reconciliation.export-statement', $reconciliation->getRouteKey());
            })
            ->addColumn('edit_url', function ($reconciliation) {
                return route('accounting.bank-reconciliation.edit', $reconciliation->getRouteKey());
            })
            ->addColumn('hash_id', function ($reconciliation) {
                return $reconciliation->getRouteKey();
            })
            ->addColumn('status', function ($reconciliation) {
                return $reconciliation->status;
            })
            ->rawColumns(['status_badge'])
            ->filterColumn('bank_account', function ($query, $keyword) {
                $query->whereHas('bankAccount', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('account_number', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('created_by', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('reconciliation_date', 'reconciliation_date $1')
            ->orderColumn('bank_statement_balance', 'bank_statement_balance $1')
            ->orderColumn('book_balance', 'book_balance $1')
            ->orderColumn('difference', 'difference $1')
            ->orderColumn('status', 'status $1')
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();

        // Get bank accounts for the current company, limited by branch scope
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->orderBy('name')
            ->get();

        return view('accounting.bank-reconciliation.create', compact('bankAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reconciliation_date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'bank_statement_balance' => 'required|numeric',
            'notes' => 'nullable|string',
            'bank_statement_document' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $branchId = session('branch_id') ?? ($user->branch_id ?? null);
            
            if (!$branchId) {
                throw new \Exception('Branch ID is required but not found. Please ensure you are assigned to a branch.');
            }

            // Handle document upload
            $documentPath = null;
            if ($request->hasFile('bank_statement_document')) {
                $document = $request->file('bank_statement_document');
                $documentName = time() . '_' . $document->getClientOriginalName();
                $documentPath = $document->storeAs('bank_statements', $documentName, 'public');
            }

            // Create bank reconciliation
            $reconciliation = BankReconciliation::create([
                'bank_account_id' => $request->bank_account_id,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'reconciliation_date' => $request->reconciliation_date,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'bank_statement_balance' => $request->bank_statement_balance,
                'notes' => $request->notes,
                'bank_statement_document' => $documentPath,
                'status' => 'draft',
            ]);

            // Calculate book balance from GL transactions
            $reconciliation->calculateBookBalance();

            // Set initial adjusted balances
            $reconciliation->update([
                'adjusted_bank_balance' => $reconciliation->bank_statement_balance,
                'adjusted_book_balance' => $reconciliation->book_balance,
            ]);

            // Calculate difference
            $reconciliation->calculateDifference();

            // Import GL transactions as book entries
            $this->importBookEntries($reconciliation);

            // Process uncleared items (identify, carry forward, auto-match)
            $unclearedItemsService = app(UnclearedItemsService::class);
            $unclearedItemsService->processReconciliationItems($reconciliation);

            DB::commit();

            return redirect()->route('accounting.bank-reconciliation.show', $reconciliation)
                ->with('success', 'Bank reconciliation created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create bank reconciliation: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return redirect()->back()->withErrors(['error' => 'Invalid reconciliation id.']);
        }

        $bankReconciliation = BankReconciliation::findOrFail($id);
        $bankReconciliation->load([
            'bankAccount.chartAccount',
            'user',
            'branch',
            'reconciliationItems.glTransaction.chartAccount',
            'reconciliationItems.matchedWithItem',
            'reconciliationItems.reconciledBy',
            'submittedBy',
            'approvedBy',
            'rejectedBy',
            'approvalHistories.approvalLevel',
            'approvalHistories.approver'
        ]);

        // Get unreconciled items (not in balance)
        $unreconciledBankItems = $bankReconciliation->reconciliationItems()
            ->where('is_bank_statement_item', true)
            ->where('is_reconciled', false)
            ->orderBy('transaction_date', 'asc')
            ->get();

        $unreconciledBookItems = $bankReconciliation->reconciliationItems()
            ->where('is_book_entry', true)
            ->where('is_reconciled', false)
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Get uncleared items summary (DNC, UPC, Brought Forward)
        $unclearedItemsService = app(UnclearedItemsService::class);
        $unclearedItemsSummary = $unclearedItemsService->getUnclearedItemsSummary($bankReconciliation);

        // Get brought forward items (highlighted)
        $broughtForwardItems = $bankReconciliation->reconciliationItems()
            ->where('is_brought_forward', true)
            ->where('uncleared_status', 'UNCLEARED')
            ->with(['originReconciliation', 'broughtForwardFromItem'])
            ->orderBy('origin_date', 'asc')
            ->get();

        // Get reconciled pairs, show only the book entry side for clarity
        $reconciledItems = $bankReconciliation->reconciliationItems()
            ->where('is_reconciled', true)
            ->where('is_book_entry', true)
            ->with(['matchedWithItem', 'reconciledBy'])
            ->orderBy('reconciled_at', 'desc')
            ->limit(10)
            ->get();

        // Compute total reconciled PAIRS (count only book entries)
        $totalReconciledCount = $bankReconciliation->reconciliationItems()
            ->where('is_reconciled', true)
            ->where('is_book_entry', true)
            ->count();

        // Get approval service data
        $user = Auth::user();
        $approvalService = app(ApprovalService::class);
        $canSubmit = $approvalService->canUserSubmit($bankReconciliation, $user->id);
        $canApprove = $approvalService->canUserApprove($bankReconciliation, $user->id);
        $currentApprovers = $approvalService->getCurrentApprovers($bankReconciliation);
        $currentLevel = $approvalService->getCurrentApprovalLevel($bankReconciliation);
        $approvalSummary = $approvalService->getApprovalStatusSummary($bankReconciliation);
        
        // Get customers, suppliers and chart accounts for receipt/payment voucher modals
        $customers = \App\Models\Customer::where('company_id', $user->company_id)
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->orderBy('name')
            ->get();
        
        $suppliers = \App\Models\Supplier::where('company_id', $user->company_id)
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->orderBy('name')
            ->get();
        
        $chartAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })
            ->orderBy('account_name')
            ->get();
        
        return view('accounting.bank-reconciliation.show', compact(
            'bankReconciliation',
            'unreconciledBankItems',
            'unreconciledBookItems',
            'reconciledItems',
            'totalReconciledCount',
            'canSubmit',
            'canApprove',
            'currentApprovers',
            'currentLevel',
            'approvalSummary',
            'customers',
            'suppliers',
            'chartAccounts',
            'unclearedItemsSummary',
            'broughtForwardItems'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return redirect()->back()->withErrors(['error' => 'Invalid reconciliation id.']);
        }
        $bankReconciliation = BankReconciliation::findOrFail($id);
        $user = Auth::user();

        // Get bank accounts for the current company, limited by branch scope
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->orderBy('name')
            ->get();

        return view('accounting.bank-reconciliation.edit', compact('bankReconciliation', 'bankAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BankReconciliation $bankReconciliation)
    {
        $validator = Validator::make($request->all(), [
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reconciliation_date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'bank_statement_balance' => 'required|numeric',
            'adjusted_bank_balance' => 'required|numeric',
            'adjusted_book_balance' => 'required|numeric',
            'notes' => 'nullable|string',
            'bank_statement_document' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Handle document upload
            if ($request->hasFile('bank_statement_document')) {
                // Delete old document if exists
                if ($bankReconciliation->bank_statement_document) {
                    Storage::disk('public')->delete($bankReconciliation->bank_statement_document);
                }
                
                $document = $request->file('bank_statement_document');
                $documentName = time() . '_' . $document->getClientOriginalName();
                $documentPath = $document->storeAs('bank_statements', $documentName, 'public');
                
                $bankReconciliation->bank_statement_document = $documentPath;
            }

            // Update bank reconciliation
            $bankReconciliation->update([
                'bank_account_id' => $request->bank_account_id,
                'reconciliation_date' => $request->reconciliation_date,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'bank_statement_balance' => $request->bank_statement_balance,
                'adjusted_bank_balance' => $request->adjusted_bank_balance,
                'adjusted_book_balance' => $request->adjusted_book_balance,
                'notes' => $request->notes,
            ]);

            // Calculate difference
            $bankReconciliation->calculateDifference();

            DB::commit();

            return redirect()->route('accounting.bank-reconciliation.show', $bankReconciliation)
                ->with('success', 'Bank reconciliation updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update bank reconciliation: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankReconciliation $bankReconciliation)
    {
        try {
            DB::beginTransaction();

            // Delete reconciliation items
            $bankReconciliation->reconciliationItems()->delete();

            // Delete reconciliation
            $bankReconciliation->delete();

            DB::commit();

            return redirect()->route('accounting.bank-reconciliation.index')
                ->with('success', 'Bank reconciliation deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete bank reconciliation: ' . $e->getMessage()]);
        }
    }

    /**
     * Add bank statement item.
     */
    public function addBankStatementItem(Request $request, BankReconciliation $bankReconciliation)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'nullable|string|max:255',
            'description' => 'required|string',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'nature' => 'required|in:debit,credit',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $bankItem = BankReconciliationItem::create([
                'bank_reconciliation_id' => $bankReconciliation->id,
                'transaction_type' => 'bank_statement',
                'reference' => $request->reference,
                'description' => $request->description,
                'transaction_date' => $request->transaction_date,
                'amount' => $request->amount,
                'nature' => $request->nature,
                'is_bank_statement_item' => true,
                'is_book_entry' => false,
                'notes' => $request->notes,
            ]);

            // Try auto-matching with uncleared items
            $unclearedItemsService = app(UnclearedItemsService::class);
            $unclearedItemsService->autoMatchUnclearedItems($bankReconciliation);

            // Recalculate adjusted bank balance
            $this->recalculateAdjustedBalances($bankReconciliation);

            return redirect()->back()
                ->with('success', 'Bank statement item added successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to add bank statement item: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Match items.
     */
    public function matchItems(Request $request, $hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return redirect()->back()->withErrors(['error' => 'Invalid reconciliation id.']);
        }
        $bankReconciliation = BankReconciliation::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'bank_item_id' => 'required|exists:bank_reconciliation_items,id',
            'book_item_id' => 'required|exists:bank_reconciliation_items,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            DB::beginTransaction();
            
            $bankItem = BankReconciliationItem::find($request->bank_item_id);
            $bookItem = BankReconciliationItem::find($request->book_item_id);

            // Match the items
            $bankItem->matchWith($bookItem->id);
            $bookItem->matchWith($bankItem->id);
            
            // If book item is uncleared (DNC/UPC), mark it as cleared
            if ($bookItem->uncleared_status === 'UNCLEARED' && $bookItem->is_book_entry) {
                $bookItem->markAsCleared(
                    $bankItem->transaction_date,
                    $bankItem->reference,
                    auth()->id()
                );
            }

            // Recalculate adjusted balances after matching
            $this->recalculateAdjustedBalances($bankReconciliation);
            
            DB::commit();

            return redirect()->back()
                ->with('success', 'Items matched successfully. Balances recalculated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to match items: ' . $e->getMessage()]);
        }
    }

    /**
     * Unmatch items.
     */
    public function unmatchItems(Request $request, $hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return redirect()->back()->withErrors(['error' => 'Invalid reconciliation id.']);
        }
        $bankReconciliation = BankReconciliation::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:bank_reconciliation_items,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            $item = BankReconciliationItem::find($request->item_id);
            $matchedItem = $item->getMatchedItem();

            // Smart reverse rules:
            // - If either side is a placeholder bank statement (created during confirmation), delete that placeholder
            //   and only move the real system item back to unreconciled.
            // - Otherwise (real bank + real book), unmatch both.
            $isItemPlaceholderBank = $item->is_bank_statement_item
                && is_null($item->gl_transaction_id)
                && ((is_string($item->description) && str_starts_with($item->description, 'Statement confirmed'))
                    || (is_string($item->notes) && str_contains($item->notes, 'Confirmed from physical statement')));

            $isMatchPlaceholderBank = $matchedItem && $matchedItem->is_bank_statement_item
                && is_null($matchedItem->gl_transaction_id)
                && ((is_string($matchedItem->description) && str_starts_with($matchedItem->description, 'Statement confirmed'))
                    || (is_string($matchedItem->notes) && str_contains($matchedItem->notes, 'Confirmed from physical statement')));

            DB::beginTransaction();
            
            if ($isItemPlaceholderBank && $matchedItem) {
                // Delete placeholder (clicked item), unmatch and set real item to unreconciled
                $matchedItem->markAsUnreconciled();
                // If it was cleared, mark as uncleared again
                if ($matchedItem->uncleared_status === 'CLEARED' && $matchedItem->is_book_entry) {
                    $matchedItem->update(['uncleared_status' => 'UNCLEARED']);
                }
                $item->delete();
            } elseif ($isMatchPlaceholderBank) {
                // Delete placeholder (paired item), unmatch and set clicked real item to unreconciled
                $item->markAsUnreconciled();
                // If it was cleared, mark as uncleared again
                if ($item->uncleared_status === 'CLEARED' && $item->is_book_entry) {
                    $item->update(['uncleared_status' => 'UNCLEARED']);
                }
                $matchedItem->delete();
            } else {
                // Fallback: unmatch both
                $item->markAsUnreconciled();
                // If it was cleared, mark as uncleared again
                if ($item->uncleared_status === 'CLEARED' && $item->is_book_entry) {
                    $item->update(['uncleared_status' => 'UNCLEARED']);
                }
                if ($matchedItem) {
                    $matchedItem->markAsUnreconciled();
                    // If it was cleared, mark as uncleared again
                    if ($matchedItem->uncleared_status === 'CLEARED' && $matchedItem->is_book_entry) {
                        $matchedItem->update(['uncleared_status' => 'UNCLEARED']);
                    }
                }
            }
            
            // Recalculate adjusted balances after unmatching
            $this->recalculateAdjustedBalances($bankReconciliation);
            
            DB::commit();

            return redirect()->back()
                ->with('success', 'Items unmatched successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to unmatch items: ' . $e->getMessage()]);
        }
    }

    /**
     * Confirm a book entry against the physical bank statement by creating a matching
     * placeholder bank statement item and marking both as reconciled.
     */
    /**
     * Mark a previous month's item as reconciled with date and bank reference
     * This is used when clicking on a brought forward item that has been reconciled
     */
    public function markPreviousMonthItemReconciled(Request $request, $hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reconciliation id.'
            ], 422);
        }
        $bankReconciliation = BankReconciliation::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:bank_reconciliation_items,id',
            'reconciled_date' => 'required|date',
            'bank_reference' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $item = BankReconciliationItem::find($request->item_id);
            
            // Check if item belongs to this reconciliation
            if ($item->bank_reconciliation_id != $bankReconciliation->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item does not belong to this reconciliation.',
                ], 422);
            }

            // Mark as reconciled with date and reference
            $reconciledDate = \Carbon\Carbon::parse($request->reconciled_date);
            $item->markAsReconciled(auth()->id(), $reconciledDate, $request->bank_reference);

            // Recalculate adjusted balances
            $this->recalculateAdjustedBalances($bankReconciliation);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item marked as reconciled successfully.',
                'item' => [
                    'id' => $item->id,
                    'reconciled_at' => $item->reconciled_at ? $item->reconciled_at->format('Y-m-d H:i:s') : null,
                    'clearing_reference' => $item->clearing_reference,
                ],
                'adjusted_bank_balance' => $bankReconciliation->fresh()->adjusted_bank_balance,
                'adjusted_book_balance' => $bankReconciliation->fresh()->adjusted_book_balance,
                'difference' => $bankReconciliation->fresh()->difference,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark item as reconciled: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function confirmBookItem(Request $request, $hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reconciliation id.'
            ], 422);
        }
        $bankReconciliation = BankReconciliation::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'book_item_id' => 'required|exists:bank_reconciliation_items,id',
            'clearing_date' => 'nullable|date',
            'clearing_reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $bookItem = BankReconciliationItem::find($request->book_item_id);

            if ($bookItem->is_reconciled) {
                return response()->json([
                    'success' => true,
                    'already_reconciled' => true,
                ]);
            }

            // Get clearing date and reference from request
            $clearingDate = $request->clearing_date ? \Carbon\Carbon::parse($request->clearing_date) : now();
            $clearingReference = $request->clearing_reference ?? $bookItem->reference;

            // Create a placeholder bank statement item based on the book entry details
            $bankItem = BankReconciliationItem::create([
                'bank_reconciliation_id' => $bankReconciliation->id,
                'transaction_type' => 'bank_statement',
                'reference' => $clearingReference,
                'description' => 'Statement confirmed: ' . $bookItem->description,
                'transaction_date' => $clearingDate,
                'amount' => $bookItem->amount,
                'nature' => $bookItem->nature,
                'is_bank_statement_item' => true,
                'is_book_entry' => false,
                'notes' => 'Confirmed from physical statement',
            ]);

            // Match the items both ways
            $bankItem->matchWith($bookItem->id);
            $bookItem->matchWith($bankItem->id);

            // If this is a brought forward item or uncleared item, mark it as cleared
            if ($bookItem->uncleared_status === 'UNCLEARED' && $bookItem->is_book_entry) {
                $bookItem->markAsCleared($clearingDate, $clearingReference, auth()->id());
            }

            // Recalculate adjusted balances after confirmation
            $this->recalculateAdjustedBalances($bankReconciliation);

            return response()->json([
                'success' => true,
                'bank_item_id' => $bankItem->id,
                'adjusted_bank_balance' => $bankReconciliation->fresh()->adjusted_bank_balance,
                'adjusted_book_balance' => $bankReconciliation->fresh()->adjusted_book_balance,
                'difference' => $bankReconciliation->fresh()->difference,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete reconciliation.
     */
    public function completeReconciliation($hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return redirect()->back()->withErrors(['error' => 'Invalid reconciliation id.']);
        }
        $bankReconciliation = BankReconciliation::findOrFail($id);
        
        try {
            // Only allow completion if reconciliation is approved
            if ($bankReconciliation->status !== 'approved') {
                return redirect()->back()
                    ->withErrors(['error' => 'Reconciliation must be approved before it can be marked as completed.']);
            }

            // Ensure reconciliation is still balanced
            if (!$bankReconciliation->isBalanced()) {
                return redirect()->back()
                    ->withErrors(['error' => 'Reconciliation must be balanced before completion.']);
            }

            // Auto-post adjustments to GL if not already posted
            if (!$bankReconciliation->adjustments_posted_at) {
                $reconciliationService = app(\App\Services\BankReconciliationService::class);
                $postResult = $reconciliationService->postAdjustmentsToGL($bankReconciliation, $user->id);
                
                if (!$postResult['success']) {
                    \Log::warning('Failed to auto-post adjustments during completion', [
                        'reconciliation_id' => $bankReconciliation->id,
                        'result' => $postResult
                    ]);
                }
            }

            $bankReconciliation->update(['status' => 'completed']);

            return redirect()->route('accounting.bank-reconciliation.show', $bankReconciliation)
                ->with('success', 'Bank reconciliation completed successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to complete reconciliation: ' . $e->getMessage()]);
        }
    }

    /**
     * Import book entries from GL transactions.
     */
    private function importBookEntries(BankReconciliation $reconciliation)
    {
        $glTransactions = GlTransaction::where('chart_account_id', $reconciliation->bankAccount->chart_account_id)
            ->whereBetween('date', [$reconciliation->start_date, $reconciliation->end_date])
            ->get();

        // Get existing reconciliation items to avoid duplicates
        $existingGlTransactionIds = $reconciliation->reconciliationItems()
            ->whereNotNull('gl_transaction_id')
            ->pluck('gl_transaction_id')
            ->toArray();

        // Also check for items without gl_transaction_id but with matching reference, date, and amount
        $existingItems = $reconciliation->reconciliationItems()
            ->where('is_book_entry', true)
            ->get();

        foreach ($glTransactions as $glTransaction) {
            // Skip if already exists by gl_transaction_id (primary check)
            if (in_array($glTransaction->id, $existingGlTransactionIds)) {
                continue;
            }

            // Also check if an item exists with same gl_transaction_id, date, and amount
            // This catches duplicates even if reference format differs
            $duplicateExists = $existingItems->contains(function ($item) use ($glTransaction) {
                // Check by gl_transaction_id first (most reliable)
                if ($item->gl_transaction_id && $item->gl_transaction_id == $glTransaction->id) {
                    return true;
                }
                
                // Also check by reference, date, and amount (for items without gl_transaction_id)
                return ($item->reference == $glTransaction->transaction_id || 
                        $item->reference == $glTransaction->description ||
                        $item->description == $glTransaction->description)
                    && $item->transaction_date->format('Y-m-d') == $glTransaction->date->format('Y-m-d')
                    && abs($item->amount - $glTransaction->amount) < 0.01
                    && $item->nature == $glTransaction->nature;
            });

            if ($duplicateExists) {
                continue;
            }

            // Determine item type (DNC or UPC)
            // DNC = Deposits Not Credited = Receipts = Debit (money coming in)
            // UPC = Unpresented Cheques = Payments = Credit (money going out)
            $itemType = $glTransaction->nature === 'debit' ? 'DNC' : 'UPC';

            // Create book entry item
            BankReconciliationItem::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'gl_transaction_id' => $glTransaction->id,
                'transaction_type' => 'book_entry',
                'item_type' => $itemType,
                'reference' => $glTransaction->transaction_id ?? $glTransaction->description,
                'description' => $glTransaction->description,
                'transaction_date' => $glTransaction->date,
                'amount' => $glTransaction->amount,
                'nature' => $glTransaction->nature,
                'is_reconciled' => false,
                'is_bank_statement_item' => false,
                'is_book_entry' => true,
                'uncleared_status' => 'UNCLEARED',
                'origin_date' => $glTransaction->date,
                'origin_month' => $glTransaction->date->copy()->startOfMonth(),
                'origin_reconciliation_id' => $reconciliation->id,
                'notes' => 'Imported from GL transaction',
            ]);
        }
    }

    /**
     * Recalculate adjusted balances.
     */
    private function recalculateAdjustedBalances(BankReconciliation $reconciliation)
    {
        // Calculate adjusted bank balance
        // Formula: Bank statement closing + Deposits in transit (DNC) - Outstanding checks (UPC)
        $dncItems = $reconciliation->reconciliationItems()
            ->where('item_type', 'DNC')
            ->where('uncleared_status', 'UNCLEARED')
            ->get();
        
        $upcItems = $reconciliation->reconciliationItems()
            ->where('item_type', 'UPC')
            ->where('uncleared_status', 'UNCLEARED')
            ->get();
        
        $totalDNC = $dncItems->sum('amount');
        $totalUPC = $upcItems->sum('amount');
        
        $adjustedBankBalance = $reconciliation->bank_statement_balance + $totalDNC - $totalUPC;

        // Calculate adjusted book balance
        // Formula: Book (cash-book) closing + Book reconciling adjustments
        // Book reconciling adjustments = items that appear in bank statement but not yet in books
        // (like bank fees, interest income, errors, etc.)
        $bankItemsWithoutBookMatch = $reconciliation->reconciliationItems()
            ->where('is_bank_statement_item', true)
            ->where('is_reconciled', false)
            ->where(function($query) {
                $query->whereNull('matched_with_item_id')
                      ->orWhereDoesntHave('matchedWithItem', function($q) {
                          $q->where('is_book_entry', true);
                      });
            })
            ->get();

        // For bank accounts: debits increase balance, credits decrease balance
        // Bank fees (credit) decrease book balance, interest income (debit) increases book balance
        $bankOnlyDebits = $bankItemsWithoutBookMatch->where('nature', 'debit')->sum('amount');
        $bankOnlyCredits = $bankItemsWithoutBookMatch->where('nature', 'credit')->sum('amount');
        
        $adjustedBookBalance = $reconciliation->book_balance + $bankOnlyDebits - $bankOnlyCredits;

        $reconciliation->update([
            'adjusted_bank_balance' => $adjustedBankBalance,
            'adjusted_book_balance' => $adjustedBookBalance,
        ]);

        $reconciliation->calculateDifference();
    }

    /**
     * Generate Bank Reconciliation Statement
     */
    public function generateStatement($hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return redirect()->back()->withErrors(['error' => 'Invalid reconciliation id.']);
        }

        $bankReconciliation = BankReconciliation::findOrFail($id);
        $user = Auth::user();

        // Check access
        if ($bankReconciliation->bankAccount->chartAccount->accountClassGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this reconciliation.');
        }

        $bankReconciliation->load([
            'bankAccount.chartAccount',
            'user',
            'branch',
            'reconciliationItems' => function($query) {
                $query->orderBy('transaction_date', 'asc');
            },
            'reconciliationItems.glTransaction.chartAccount',
            'reconciliationItems.matchedWithItem',
            'reconciliationItems.reconciledBy',
            'reconciliationItems.clearedBy',
            'reconciliationItems.originReconciliation',
            'approvedBy',
            'submittedBy',
        ]);

        // Get uncleared items summary
        $unclearedItemsService = app(UnclearedItemsService::class);
        $unclearedItemsSummary = $unclearedItemsService->getUnclearedItemsSummary($bankReconciliation);

        // Get DNC items (Deposits Not Credited)
        $dncItems = $bankReconciliation->reconciliationItems()
            ->where('item_type', 'DNC')
            ->where('uncleared_status', 'UNCLEARED')
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Get UPC items (Unpresented Cheques)
        $upcItems = $bankReconciliation->reconciliationItems()
            ->where('item_type', 'UPC')
            ->where('uncleared_status', 'UNCLEARED')
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Get Bank Errors (items marked as BANK_ERROR)
        $bankErrors = $bankReconciliation->reconciliationItems()
            ->where('item_type', 'BANK_ERROR')
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Calculate totals
        $totalDNC = $dncItems->sum('amount');
        $totalUPC = $upcItems->sum('amount');
        $totalBankErrors = $bankErrors->sum('amount');

        // Calculate adjusted bank balance
        // Formula: Bank statement closing + Deposits in transit (DNC) - Outstanding checks (UPC)
        $adjustedBankBalance = $bankReconciliation->bank_statement_balance 
            + $totalDNC 
            - $totalUPC;

        // Recalculate adjusted balances to ensure consistency
        $this->recalculateAdjustedBalances($bankReconciliation);
        $bankReconciliation->refresh();

        $company = $user->company;
        $branch = $user->branch;

        return view('accounting.bank-reconciliation.statement', compact(
            'bankReconciliation',
            'dncItems',
            'upcItems',
            'bankErrors',
            'totalDNC',
            'totalUPC',
            'totalBankErrors',
            'adjustedBankBalance',
            'unclearedItemsSummary',
            'company',
            'branch',
            'user'
        ));
    }

    /**
     * Export Bank Reconciliation Statement to PDF
     */
    public function exportStatement($hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return redirect()->back()->withErrors(['error' => 'Invalid reconciliation id.']);
        }

        $bankReconciliation = BankReconciliation::findOrFail($id);
        $user = Auth::user();

        // Check access
        if ($bankReconciliation->bankAccount->chartAccount->accountClassGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this reconciliation.');
        }

        $bankReconciliation->load([
            'bankAccount.chartAccount',
            'user',
            'branch',
            'reconciliationItems' => function($query) {
                $query->orderBy('transaction_date', 'asc');
            },
            'reconciliationItems.glTransaction.chartAccount',
            'reconciliationItems.matchedWithItem',
            'reconciliationItems.reconciledBy',
            'reconciliationItems.clearedBy',
            'reconciliationItems.originReconciliation',
            'approvedBy',
            'submittedBy',
        ]);

        // Get uncleared items summary
        $unclearedItemsService = app(UnclearedItemsService::class);
        $unclearedItemsSummary = $unclearedItemsService->getUnclearedItemsSummary($bankReconciliation);

        // Get DNC items
        $dncItems = $bankReconciliation->reconciliationItems()
            ->where('item_type', 'DNC')
            ->where('uncleared_status', 'UNCLEARED')
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Get UPC items
        $upcItems = $bankReconciliation->reconciliationItems()
            ->where('item_type', 'UPC')
            ->where('uncleared_status', 'UNCLEARED')
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Get Bank Errors
        $bankErrors = $bankReconciliation->reconciliationItems()
            ->where('item_type', 'BANK_ERROR')
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Calculate totals
        $totalDNC = $dncItems->sum('amount');
        $totalUPC = $upcItems->sum('amount');
        $totalBankErrors = $bankErrors->sum('amount');

        // Calculate adjusted bank balance
        // Formula: Bank statement closing + Deposits in transit (DNC) - Outstanding checks (UPC)
        $adjustedBankBalance = $bankReconciliation->bank_statement_balance 
            + $totalDNC 
            - $totalUPC;
        
        // Recalculate adjusted balances to ensure consistency
        $this->recalculateAdjustedBalances($bankReconciliation);
        $bankReconciliation->refresh();

        $company = $user->company;
        $branch = $user->branch;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting.bank-reconciliation.statement-pdf', compact(
            'bankReconciliation',
            'dncItems',
            'upcItems',
            'bankErrors',
            'totalDNC',
            'totalUPC',
            'totalBankErrors',
            'adjustedBankBalance',
            'unclearedItemsSummary',
            'company',
            'branch',
            'user'
        ));

        $pdf->setPaper('A4', 'landscape');
        
        $filename = 'Bank_Reconciliation_Statement_' . $bankReconciliation->bankAccount->name . '_' . $bankReconciliation->end_date->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Update book balance when new transactions are added to the reconciliation period.
     */
    public function updateBookBalance($hash)
    {
        $id = \App\Helpers\HashIdHelper::decode($hash);
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reconciliation id.'
            ], 400);
        }
        $bankReconciliation = BankReconciliation::findOrFail($id);
        try {
            DB::beginTransaction();

            // Check if reconciliation is completed
            if ($bankReconciliation->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update completed reconciliation.'
                ], 400);
            }

            // Recalculate book balance from GL transactions
            $bankReconciliation->calculateBookBalance();

            // Recalculate adjusted balances
            $this->recalculateAdjustedBalances($bankReconciliation);

            // Import only missing GL transactions as book entries
            $service = app(\App\Services\BankReconciliationService::class);
            $service->importMissingTransactions($bankReconciliation);

            DB::commit();

            // Refresh the model to get the latest calculated values
            $bankReconciliation->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Book balance updated successfully.',
                'book_balance' => $bankReconciliation->formatted_book_balance,
                'adjusted_book_balance' => $bankReconciliation->formatted_adjusted_book_balance,
                'adjusted_bank_balance' => $bankReconciliation->formatted_adjusted_bank_balance,
                'difference' => $bankReconciliation->formatted_difference
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update book balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh all active reconciliations
     */
    public function refreshAllReconciliations()
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $service = app(\App\Services\BankReconciliationService::class);

            // Get all active reconciliations for the current company
            $activeReconciliations = BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->whereIn('status', ['draft', 'in_progress'])
            ->get();

            $updatedCount = 0;
            $errors = [];

            foreach ($activeReconciliations as $reconciliation) {
                try {
                    // Recalculate book balance
                    $reconciliation->calculateBookBalance();
                    
                    // Recalculate adjusted balances
                    $this->recalculateAdjustedBalances($reconciliation);
                    
                    // Import missing transactions
                    $service->importMissingTransactions($reconciliation);
                    
                    $updatedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Reconciliation ID {$reconciliation->id}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Successfully refreshed {$updatedCount} reconciliations.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'updated_count' => $updatedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh reconciliations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit bank reconciliation for approval.
     */
    public function submitForApproval(BankReconciliation $bankReconciliation, Request $request)
    {
        $user = Auth::user();
        
        // Check permission
        if (!$user->can('submit bank reconciliation for approval')) {
            abort(403, 'You do not have permission to submit bank reconciliations for approval.');
        }

        // Check if reconciliation can be submitted
        if (!in_array($bankReconciliation->status, ['draft', 'rejected'])) {
            return redirect()->back()->with('error', 'Reconciliation can only be submitted from draft or rejected status.');
        }

        try {
            DB::beginTransaction();
            
            // Load relationships needed for company_id access
            $bankReconciliation->load('bankAccount.chartAccount.accountClassGroup');
            
            // Recalculate balances before checking if balanced
            $this->recalculateAdjustedBalances($bankReconciliation);
            $bankReconciliation->refresh();

            // Validate reconciliation is balanced before submission
            if (!$bankReconciliation->isBalanced()) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Reconciliation must be balanced (difference = 0) before submitting for approval. Current difference: ' . number_format($bankReconciliation->difference, 2) . '. Please ensure all items are reconciled.');
            }

            $approvalService = app(ApprovalService::class);

            if (!$approvalService->canUserSubmit($bankReconciliation, $user->id)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'You do not have permission to submit this reconciliation.');
            }

            $approvalService->submitForApproval($bankReconciliation, $user->id);
            
            DB::commit();

            return redirect()->back()->with('success', 'Bank reconciliation submitted for approval successfully. Notifications have been sent to approvers.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bank Reconciliation Submission Error', [
                'reconciliation_id' => $bankReconciliation->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to submit reconciliation: ' . $e->getMessage());
        }
    }

    /**
     * Approve bank reconciliation at current level.
     */
    public function approve(BankReconciliation $bankReconciliation, Request $request)
    {
        $user = Auth::user();
        
        // Check permission
        if (!$user->can('approve bank reconciliation')) {
            abort(403, 'You do not have permission to approve bank reconciliations.');
        }

        $request->validate([
            'approval_level_id' => 'required|exists:approval_levels,id',
            'comments' => 'nullable|string|max:1000',
        ]);

        $approvalService = app(ApprovalService::class);

        try {
            $approvalService->approve(
                $bankReconciliation,
                $request->approval_level_id,
                $user->id,
                $request->comments
            );

            // Refresh to get updated status
            $bankReconciliation->refresh();

            $message = 'Bank reconciliation approved successfully.';
            if ($bankReconciliation->status === 'approved') {
                // Auto-post adjustments to GL when fully approved
                $reconciliationService = app(\App\Services\BankReconciliationService::class);
                $postResult = $reconciliationService->postAdjustmentsToGL($bankReconciliation, $user->id);
                
                if ($postResult['success'] && $postResult['posted_count'] > 0) {
                    $message .= ' ' . $postResult['message'];
                } elseif (!$postResult['success']) {
                    \Log::warning('Failed to auto-post adjustments during approval', [
                        'reconciliation_id' => $bankReconciliation->id,
                        'result' => $postResult
                    ]);
                }
                
                // Auto-complete after full approval
                if ($bankReconciliation->isBalanced()) {
                    $bankReconciliation->update(['status' => 'completed']);
                    $message = 'Bank reconciliation fully approved and automatically marked as completed.';
                    if ($postResult['success'] && $postResult['posted_count'] > 0) {
                        $message .= ' ' . $postResult['message'];
                    }
                } else {
                    $message = 'Bank reconciliation fully approved. Please ensure it is balanced before completion.';
                }
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject bank reconciliation at current level.
     */
    public function reject(BankReconciliation $bankReconciliation, Request $request)
    {
        $user = Auth::user();
        
        // Check permission
        if (!$user->can('reject bank reconciliation')) {
            abort(403, 'You do not have permission to reject bank reconciliations.');
        }

        $request->validate([
            'approval_level_id' => 'required|exists:approval_levels,id',
            'reason' => 'required|string|max:1000',
        ]);

        $approvalService = app(ApprovalService::class);

        try {
            $approvalService->reject(
                $bankReconciliation,
                $request->approval_level_id,
                $user->id,
                $request->reason
            );

            return redirect()->back()->with('success', 'Bank reconciliation rejected.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reassign bank reconciliation approval to another approver.
     */
    public function reassign(BankReconciliation $bankReconciliation, Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'approval_level_id' => 'required|exists:approval_levels,id',
            'new_approver_id' => 'required|exists:users,id',
            'comments' => 'nullable|string|max:1000',
        ]);

        $approvalService = app(ApprovalService::class);

        try {
            $approvalService->reassign(
                $bankReconciliation,
                $request->approval_level_id,
                $user->id,
                $request->new_approver_id,
                $request->comments
            );

            return redirect()->back()->with('success', 'Bank reconciliation approval reassigned successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get approval history for a bank reconciliation.
     */
    public function approvalHistory(BankReconciliation $bankReconciliation)
    {
        $approvalService = app(ApprovalService::class);
        $history = $approvalService->getApprovalHistory($bankReconciliation);
        $summary = $approvalService->getApprovalStatusSummary($bankReconciliation);
        $currentApprovers = $approvalService->getCurrentApprovers($bankReconciliation);

        return view('accounting.bank-reconciliation.approval-history', compact('bankReconciliation', 'history', 'summary', 'currentApprovers'));
    }
}
