<?php

namespace App\Http\Controllers\Accounting\PettyCash;

use App\Http\Controllers\Controller;
use App\Models\PettyCash\PettyCashUnit;
use App\Models\PettyCash\PettyCashTransaction;
use App\Models\PettyCash\PettyCashExpenseCategory;
use App\Models\PettyCash\PettyCashTransactionItem;
use App\Services\PettyCashService;
use App\Services\PettyCashImprestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class PettyCashTransactionController extends Controller
{
    protected $pettyCashService;

    public function __construct(PettyCashService $pettyCashService)
    {
        $this->pettyCashService = $pettyCashService;
    }

    /**
     * Display a listing of petty cash transactions
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // If AJAX request, return DataTables data
        if ($request->ajax()) {
            return $this->getTransactionsData($request);
        }
        
        $units = PettyCashUnit::forCompany($companyId)->active()->orderBy('name')->get();
        
        return view('accounting.petty-cash.transactions.index', compact('units'));
    }

    /**
     * Get transactions data for DataTables (AJAX)
     */
    public function getTransactionsData(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $query = PettyCashTransaction::with(['pettyCashUnit', 'expenseCategory', 'items.chartAccount', 'createdBy', 'approvedBy'])
            ->whereHas('pettyCashUnit', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        
        // Apply filters
        if ($request->filled('petty_cash_unit_id')) {
            $query->where('petty_cash_unit_id', $request->petty_cash_unit_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        return datatables($query)
            ->addIndexColumn()
            ->addColumn('transaction_number_link', function ($transaction) {
                return '<a href="' . route('accounting.petty-cash.transactions.show', $transaction->encoded_id) . '" class="text-primary fw-bold">' . $transaction->transaction_number . '</a>';
            })
            ->addColumn('formatted_date', function ($transaction) {
                return $transaction->transaction_date->format('M d, Y');
            })
            ->addColumn('unit_name', function ($transaction) {
                return '<a href="' . route('accounting.petty-cash.units.show', $transaction->pettyCashUnit->encoded_id) . '" class="text-info">' . $transaction->pettyCashUnit->name . '</a>';
            })
            ->addColumn('category_name', function ($transaction) {
                // If transaction has line items, show account names from items
                if ($transaction->items && $transaction->items->count() > 0) {
                    $accountNames = $transaction->items->map(function($item) {
                        return $item->chartAccount->account_name ?? 'N/A';
                    })->unique()->take(2);
                    
                    $count = $transaction->items->count();
                    if ($count > 2) {
                        return $accountNames->implode(', ') . ' (+' . ($count - 2) . ' more)';
                    }
                    return $accountNames->implode(', ');
                }
                
                // Fallback to expense category if no line items
                return $transaction->expenseCategory->name ?? 'N/A';
            })
            ->addColumn('description_with_payee', function ($transaction) {
                $html = '<div class="fw-bold">' . \Str::limit($transaction->description, 40) . '</div>';
                if ($transaction->payee) {
                    $html .= '<small class="text-muted">Payee: ' . $transaction->payee . '</small>';
                }
                return $html;
            })
            ->addColumn('formatted_amount', function ($transaction) {
                return '<span class="fw-bold text-danger">-TZS ' . number_format($transaction->amount, 2) . '</span>';
            })
            ->addColumn('status_badge', function ($transaction) {
                $statusColors = [
                    'draft' => 'secondary',
                    'submitted' => 'info',
                    'approved' => 'success',
                    'pending_receipt' => 'warning',
                    'posted' => 'primary',
                    'rejected' => 'danger'
                ];
                $color = $statusColors[$transaction->status] ?? 'secondary';
                $statusText = ucfirst(str_replace('_', ' ', $transaction->status));
                
                $badge = '<span class="badge bg-' . $color . '">' . $statusText . '</span>';
                
                // Add receipt status if available
                if ($transaction->receipt_status) {
                    $receiptColors = [
                        'pending' => 'warning',
                        'uploaded' => 'info',
                        'verified' => 'success',
                        'rejected' => 'danger'
                    ];
                    $receiptColor = $receiptColors[$transaction->receipt_status] ?? 'secondary';
                    $badge .= ' <span class="badge bg-' . $receiptColor . '" title="Receipt Status"><i class="bx bx-receipt"></i> ' . ucfirst($transaction->receipt_status) . '</span>';
                }
                
                return $badge;
            })
            ->addColumn('formatted_balance_after', function ($transaction) {
                return '<span class="fw-bold">TZS ' . number_format($transaction->balance_after ?? 0, 2) . '</span>';
            })
            ->addColumn('actions', function ($transaction) {
                $encodedId = $transaction->encoded_id;
                $buttons = '<div class="d-flex order-actions gap-1 justify-content-center">';
                
                // View button
                $buttons .= '<a href="' . route('accounting.petty-cash.transactions.show', $encodedId) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                
                // Edit button (only if can be edited)
                if ($transaction->canBeEdited()) {
                    $buttons .= '<a href="' . route('accounting.petty-cash.transactions.edit', $encodedId) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                // Approve button (if can be approved - status is submitted)
                if ($transaction->canBeApproved()) {
                    $buttons .= '<a href="javascript:void(0);" onclick="approveTransaction(\'' . $encodedId . '\')" class="btn btn-sm btn-success" title="Approve"><i class="bx bx-check"></i></a>';
                }
                
                // Reject button (if can be rejected - status is submitted)
                if ($transaction->canBeRejected()) {
                    $buttons .= '<a href="javascript:void(0);" onclick="rejectTransaction(\'' . $encodedId . '\')" class="btn btn-sm btn-danger" title="Reject"><i class="bx bx-x"></i></a>';
                }
                
                // Post to GL button (if approved but not posted)
                if ($transaction->canBePosted() || ($transaction->status === 'approved' && !$transaction->payment_id)) {
                    $buttons .= '<a href="javascript:void(0);" onclick="postTransactionToGL(\'' . $encodedId . '\')" class="btn btn-sm btn-success" title="Post to GL"><i class="bx bx-check-circle"></i></a>';
                }
                
                // Delete button (only if can be deleted - not posted to GL)
                if ($transaction->canBeDeleted()) {
                    $buttons .= '<a href="javascript:void(0);" onclick="deleteTransaction(\'' . $encodedId . '\')" class="btn btn-sm btn-danger" title="Delete"><i class="bx bx-trash"></i></a>';
                }
                
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['transaction_number_link', 'unit_name', 'description_with_payee', 'formatted_amount', 'status_badge', 'formatted_balance_after', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new transaction
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $units = PettyCashUnit::forCompany($companyId)->active()->orderBy('name')->get();
        $categories = PettyCashExpenseCategory::forCompany($companyId)->active()->orderBy('name')->get();
        
        $selectedUnitId = $request->get('petty_cash_unit_id');
        
        return view('accounting.petty-cash.transactions.create', compact('units', 'categories', 'selectedUnitId'));
    }

    /**
     * Get expense categories for AJAX requests
     */
    public function getCategories(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $categories = PettyCashExpenseCategory::forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        
        return response()->json($categories);
    }

    /**
     * Get expense accounts (chart accounts) for AJAX requests
     */
    public function getExpenseAccounts(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        try {
            // Get expense accounts using join for better performance and reliability
            $accounts = \App\Models\ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
                ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
                ->where('account_class_groups.company_id', $companyId)
                ->whereRaw('LOWER(account_class.name) LIKE ?', ['%expense%'])
                ->select('chart_accounts.id', 'chart_accounts.account_name', 'chart_accounts.account_code')
                ->orderBy('chart_accounts.account_code')
                ->get()
                ->map(function($account) {
                    return [
                        'id' => $account->id,
                        'account_name' => $account->account_name,
                        'account_code' => $account->account_code,
                        'display' => $account->account_code . ' - ' . $account->account_name
                    ];
                });
            
            \Log::info('Expense accounts loaded', ['count' => $accounts->count(), 'company_id' => $companyId]);
            
            return response()->json($accounts);
        } catch (\Exception $e) {
            \Log::error('Error loading expense accounts', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to load expense accounts'], 500);
        }
    }

    /**
     * Store a newly created transaction
     */
    public function store(Request $request)
    {
        // Prevent duplicate submissions using a unique request identifier
        $requestId = $request->header('X-Request-ID') ?: $request->input('_request_id');
        if ($requestId) {
            $cacheKey = 'petty_cash_transaction_request_' . $requestId;
            if (cache()->has($cacheKey)) {
                \Log::warning('Duplicate transaction creation request detected', [
                    'request_id' => $requestId,
                    'ip' => $request->ip(),
                    'user_id' => Auth::id()
                ]);
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This request has already been processed. Please refresh the page.'
                    ], 409);
                }
                return back()->with('error', 'This request has already been processed. Please refresh the page.');
            }
            // Cache the request ID for 30 seconds to prevent duplicates
            cache()->put($cacheKey, true, 30);
        }
        
        try {
            // Get line items from request
            $lineItems = $request->has('line_items') ? $request->line_items : [];

            // Validate based on whether line items are provided
            if (!empty($lineItems)) {
                $validated = $request->validate([
                    'petty_cash_unit_id' => 'required|exists:petty_cash_units,id',
                    'transaction_date' => 'required|date',
                    'payee_type' => 'required|in:customer,supplier,employee,other',
                    'customer_id' => 'required_if:payee_type,customer|exists:customers,id',
                    'supplier_id' => 'required_if:payee_type,supplier|exists:suppliers,id',
                    'employee_id' => 'nullable',
                    'payee_name' => 'required_if:payee_type,other|nullable|string|max:255',
                    'description' => 'required|string',
                    'notes' => 'nullable|string',
                    'receipt_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,application/x-pdf,image/jpeg,image/jpg,image/png|max:5120',
                    'line_items' => 'required|array|min:1',
                    'line_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
                    'line_items.*.amount' => 'required|numeric|min:0.01',
                    'line_items.*.description' => 'nullable|string',
                ]);
                $validated['line_items'] = $lineItems;
            } else {
                // Legacy validation for single expense category
                $validated = $request->validate([
                    'petty_cash_unit_id' => 'required|exists:petty_cash_units,id',
                    'transaction_date' => 'required|date',
                    'expense_category_id' => 'required|exists:petty_cash_expense_categories,id',
                    'amount' => 'required|numeric|min:0.01',
                    'payee' => 'nullable|string|max:255',
                    'description' => 'required|string',
                    'notes' => 'nullable|string',
                    'receipt_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,application/x-pdf,image/jpeg,image/jpg,image/png|max:5120',
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }
        
        try {
            DB::beginTransaction();
            
            $unit = PettyCashUnit::findOrFail($validated['petty_cash_unit_id']);
            
            // Calculate total amount from line items or single amount
            $totalAmount = 0;
            if (!empty($lineItems)) {
                foreach ($lineItems as $item) {
                    $totalAmount += (float) $item['amount'];
                }
            } else {
                $totalAmount = (float) $validated['amount'];
            }
            
            // Check balance
            if (!$unit->canSpend($totalAmount)) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient petty cash balance.'
                    ], 400);
                }
                return back()->withInput()->with('error', 'Insufficient petty cash balance.');
            }
            
            // Check for duplicate transaction created within the last 5 seconds
            // (same unit, same amount, same description, same user)
            $recentDuplicate = PettyCashTransaction::where('petty_cash_unit_id', $validated['petty_cash_unit_id'])
                ->where('amount', $totalAmount)
                ->where('description', $validated['description'])
                ->where('created_by', Auth::id())
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();
            
            if ($recentDuplicate) {
                \Log::warning('Duplicate transaction creation attempt detected', [
                    'existing_transaction_id' => $recentDuplicate->id,
                    'existing_transaction_number' => $recentDuplicate->transaction_number,
                    'new_attempt_user_id' => Auth::id(),
                    'unit_id' => $validated['petty_cash_unit_id'],
                    'amount' => $totalAmount
                ]);
                
                DB::rollBack();
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A similar transaction was just created. Please check if it already exists.',
                        'duplicate_transaction_id' => $recentDuplicate->encoded_id
                    ], 409);
                }
                return back()->withInput()->with('error', 'A similar transaction was just created. Please check if it already exists.');
            }
            
            // Generate transaction number
            $transactionNumber = $this->generateTransactionNumber($unit);
            
            // Handle file upload
            $receiptPath = null;
            if ($request->hasFile('receipt_attachment')) {
                $receiptPath = $request->file('receipt_attachment')->store('petty-cash/receipts', 'public');
            }
            
            // Determine payee information
            $payee = null;
            $payeeType = null;
            $customerId = null;
            $supplierId = null;
            $employeeId = null;
            
            if (!empty($lineItems)) {
                if ($request->payee_type === 'customer' && $request->customer_id) {
                    $customer = \App\Models\Customer::find($request->customer_id);
                    $payee = $customer ? $customer->name : null;
                    $payeeType = 'customer';
                    $customerId = $request->customer_id;
                } elseif ($request->payee_type === 'supplier' && $request->supplier_id) {
                    $supplier = \App\Models\Supplier::find($request->supplier_id);
                    $payee = $supplier ? $supplier->name : null;
                    $payeeType = 'supplier';
                    $supplierId = $request->supplier_id;
                } elseif ($request->payee_type === 'employee') {
                    $payee = $request->payee_name ?? null;
                    $payeeType = 'employee';
                    $employeeId = null;
                } elseif ($request->payee_type === 'other' && $request->payee_name) {
                    $payee = $request->payee_name;
                    $payeeType = 'other';
                }
            } else {
                $payee = $validated['payee'] ?? null;
            }
            
            // Get expense category from first line item or use provided category
            $expenseCategoryId = null;
            if (!empty($lineItems)) {
                // Try to find an expense category that uses the first chart account
                $firstAccountId = $lineItems[0]['chart_account_id'];
                $expenseCategory = PettyCashExpenseCategory::where('expense_account_id', $firstAccountId)->first();
                
                if ($expenseCategory) {
                    $expenseCategoryId = $expenseCategory->id;
                }
                // If no category found, leave it null - GL posting will use line items directly
            } else {
                $expenseCategoryId = $validated['expense_category_id'] ?? null;
            }
            
            // Determine if transaction requires approval
            $requiresApproval = $unit->requiresApproval($totalAmount);
            
            $transactionData = [
                'petty_cash_unit_id' => $validated['petty_cash_unit_id'],
                'transaction_number' => $transactionNumber,
                'transaction_date' => $validated['transaction_date'],
                'expense_category_id' => $expenseCategoryId,
                'amount' => $totalAmount,
                'payee' => $payee,
                'payee_type' => !empty($lineItems) ? $payeeType : null,
                'customer_id' => !empty($lineItems) ? $customerId : null,
                'supplier_id' => !empty($lineItems) ? $supplierId : null,
                'employee_id' => !empty($lineItems) ? $employeeId : null,
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'receipt_attachment' => $receiptPath,
                'created_by' => Auth::id(),
                'status' => $requiresApproval ? 'submitted' : 'approved',
                'balance_after' => $requiresApproval ? null : ($unit->current_balance - $totalAmount), // Only set if auto-approved
            ];
            
            $transaction = PettyCashTransaction::create($transactionData);
            
            // Create transaction line items
            if (!empty($lineItems)) {
                foreach ($lineItems as $item) {
                    \App\Models\PettyCash\PettyCashTransactionItem::create([
                        'petty_cash_transaction_id' => $transaction->id,
                        'chart_account_id' => $item['chart_account_id'],
                        'amount' => $item['amount'],
                        'description' => $item['description'] ?? null,
                    ]);
                }
            }
            
            // Reload transaction with items to ensure they're available
            $transaction->refresh();
            $transaction->load('items');
            
            // In Sub-Imprest mode, create imprest request when transaction is submitted
            // This creates the PCV automatically (User → Supervisor → Custodian workflow)
            $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($unit->company_id);
            if ($settings->isSubImprestMode() && $transaction->status === 'submitted') {
                try {
                    $imprestRequest = \App\Services\PettyCashImprestService::createImprestRequestFromTransaction($transaction);
                    if ($imprestRequest) {
                        \Log::info('Imprest request created from submitted petty cash transaction (Sub-Imprest Mode)', [
                            'transaction_id' => $transaction->id,
                            'imprest_request_id' => $imprestRequest->id,
                            'imprest_request_number' => $imprestRequest->request_number
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to create imprest request from transaction', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                    // Don't fail the transaction creation if imprest creation fails
                }
            }
            
            // Only update balance and post to GL if transaction is auto-approved
            // (Transactions requiring approval should wait for approval before posting)
            if ($transaction->status === 'approved') {
                // Update balance
                $unit->decrement('current_balance', $totalAmount);
                $transaction->update([
                    'balance_after' => $unit->current_balance,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);
                
                // Post to GL automatically for auto-approved transactions
                try {
                    $this->pettyCashService->postTransactionToGL($transaction);
                    \Log::info('Petty cash transaction auto-approved and posted to GL', [
                        'transaction_id' => $transaction->id,
                        'transaction_number' => $transaction->transaction_number,
                        'amount' => $totalAmount,
                        'new_balance' => $unit->current_balance,
                        'items_count' => $transaction->items->count()
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to post auto-approved transaction to GL', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Rollback balance change if GL posting fails
                    $unit->increment('current_balance', $totalAmount);
                    throw $e; // Re-throw to show error to user
                }
            } else {
                // Transaction requires approval - don't post to GL yet, don't update balance
                \Log::info('Petty cash transaction created with status: ' . $transaction->status . ' - awaiting approval', [
                    'transaction_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'amount' => $totalAmount,
                    'requires_approval' => $unit->requiresApproval($totalAmount),
                    'approval_threshold' => $unit->approval_threshold
                ]);
            }
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Petty cash transaction created successfully.',
                    'transaction' => $transaction->load(['expenseCategory', 'items'])
                ]);
            }
            
            return redirect()->route('accounting.petty-cash.transactions.index')
                ->with('success', 'Petty cash transaction created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create transaction: ' . $e->getMessage()
                ], 500);
            }
            return back()->withInput()->with('error', 'Failed to create transaction: ' . $e->getMessage());
        }
    }

    /**
     * Approve a transaction
     */
    public function approve($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
            }
            abort(404);
        }
        
        $transaction = PettyCashTransaction::findOrFail($id);
        
        if (!$transaction->canBeApproved()) {
            $message = 'Transaction cannot be approved.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        try {
            DB::beginTransaction();
            
            $transaction->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
            
            $unit = $transaction->pettyCashUnit;
            
            // Check if already posted to GL (if posted during creation)
            if (!$transaction->payment_id) {
                // Not posted yet - update balance and post to GL
                $unit->decrement('current_balance', $transaction->amount);
                $transaction->update(['balance_after' => $unit->current_balance]);
                
                // Reload transaction with items before posting to GL
                $transaction->refresh();
                $transaction->load('items');
                
                // Post to GL
                $this->pettyCashService->postTransactionToGL($transaction);
                
            // Reload to get payment_id
            $transaction->refresh();
            } else {
                // Already posted during creation - just update status and balance_after
                $transaction->update(['balance_after' => $unit->current_balance]);
            }
            
            // In Sub-Imprest mode, workflow is different:
            // 1. User submits → creates imprest request (already done in store method)
            // 2. Supervisor approves → transaction approved, but NOT disbursed yet
            // 3. Custodian disburses → status becomes 'pending_receipt', balance reduced
            // 4. Receipt uploaded → receipt_status becomes 'uploaded'
            // 5. Custodian verifies → receipt_status becomes 'verified'
            // 6. Accountant posts → status becomes 'posted', GL entry created
            
            $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($unit->company_id);
            if ($settings->isSubImprestMode()) {
                // In Sub-Imprest mode, don't reduce balance or post to GL on approval
                // Wait for custodian to disburse, then mark as pending_receipt
                // The imprest request should already be created when transaction was submitted
                \Log::info('Transaction approved in Sub-Imprest mode - awaiting custodian disbursement', [
                    'transaction_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number
                ]);
            } else {
                // In standalone mode, create imprest request if needed (shouldn't happen, but keep for safety)
                if (PettyCashImprestService::shouldCreateImprestRequest($transaction)) {
                    try {
                        $imprestRequest = PettyCashImprestService::createImprestRequestFromTransaction($transaction);
                        if ($imprestRequest) {
                            \Log::info('Imprest request created from approved petty cash transaction', [
                                'transaction_id' => $transaction->id,
                                'imprest_request_id' => $imprestRequest->id,
                                'imprest_request_number' => $imprestRequest->request_number
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to create imprest request from transaction', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage()
                        ]);
                        // Don't fail the approval if imprest creation fails
                    }
                }
            }
            
            DB::commit();
            
            $message = 'Transaction approved' . ($transaction->payment_id ? ' and posted to GL.' : ' (already posted to GL).');
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = 'Failed to approve transaction: ' . $e->getMessage();
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            
            return back()->with('error', $message);
        }
    }

    /**
     * Reject a transaction
     */
    public function reject($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
            }
            abort(404);
        }
        
        $transaction = PettyCashTransaction::findOrFail($id);
        
        if (!$transaction->canBeRejected()) {
            $message = 'Transaction cannot be rejected.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);
        
        try {
            DB::beginTransaction();
            
            $transaction->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
            ]);
            
            DB::commit();
            
            $message = 'Transaction rejected successfully.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = 'Failed to reject transaction: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Display the specified transaction
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $transaction = PettyCashTransaction::with([
            'pettyCashUnit',
            'expenseCategory',
            'items.chartAccount',
            'payment.paymentItems.chartAccount',
            'createdBy',
            'approvedBy',
            'customer',
            'supplier'
        ])->findOrFail($id);
        
        // Get linked imprest request if in Sub-Imprest mode
        $imprestRequest = null;
        $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($transaction->pettyCashUnit->company_id);
        if ($settings->isSubImprestMode()) {
            $imprestRequest = \App\Services\PettyCashImprestService::getLinkedImprestRequest($transaction);
        }
        
        // Load additional relationships for receipt verification
        $transaction->load(['receiptVerifiedBy', 'disbursedBy', 'employee']);
        
        return view('accounting.petty-cash.transactions.show', compact('transaction', 'imprestRequest', 'settings'));
    }

    /**
     * Show the form for editing the specified transaction
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $transaction = PettyCashTransaction::with(['items', 'pettyCashUnit'])->findOrFail($id);
        
        if (!$transaction->canBeEdited()) {
            return back()->with('error', 'Transaction cannot be edited in its current status.');
        }
        
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $units = PettyCashUnit::forCompany($companyId)->active()->orderBy('name')->get();
        $categories = PettyCashExpenseCategory::forCompany($companyId)->active()->orderBy('name')->get();
        
        $employees = collect();
        
        // Get expense accounts (chart accounts) for line items using join for better performance and reliability
        $expenseAccounts = \App\Models\ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $companyId)
            ->whereRaw('LOWER(account_class.name) LIKE ?', ['%expense%'])
            ->select('chart_accounts.id', 'chart_accounts.account_name', 'chart_accounts.account_code')
            ->orderBy('chart_accounts.account_code')
            ->get();
        
        return view('accounting.petty-cash.transactions.edit', compact('transaction', 'units', 'categories', 'expenseAccounts', 'employees'));
    }

    /**
     * Update the specified transaction
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $transaction = PettyCashTransaction::findOrFail($id);
        
        if (!$transaction->canBeEdited()) {
            return back()->with('error', 'Transaction cannot be edited in its current status.');
        }
        
        // Similar validation as store method
        $lineItems = $request->has('line_items') ? $request->line_items : [];
        
        if (!empty($lineItems)) {
            $validated = $request->validate([
                'petty_cash_unit_id' => 'required|exists:petty_cash_units,id',
                'transaction_date' => 'required|date',
                'payee_type' => 'required|in:customer,supplier,employee,other',
                'customer_id' => 'required_if:payee_type,customer|exists:customers,id',
                'supplier_id' => 'required_if:payee_type,supplier|exists:suppliers,id',
                'employee_id' => 'nullable',
                'payee_name' => 'required_if:payee_type,other|nullable|string|max:255',
                'description' => 'required|string',
                'notes' => 'nullable|string',
                'receipt_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,application/x-pdf,image/jpeg,image/jpg,image/png|max:5120',
                'line_items' => 'required|array|min:1',
                'line_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
                'line_items.*.amount' => 'required|numeric|min:0.01',
                'line_items.*.description' => 'nullable|string',
            ]);
        } else {
            $validated = $request->validate([
                'petty_cash_unit_id' => 'required|exists:petty_cash_units,id',
                'transaction_date' => 'required|date',
                'expense_category_id' => 'required|exists:petty_cash_expense_categories,id',
                'amount' => 'required|numeric|min:0.01',
                'payee' => 'nullable|string|max:255',
                'description' => 'required|string',
                'notes' => 'nullable|string',
                'receipt_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,application/x-pdf,image/jpeg,image/jpg,image/png|max:5120',
            ]);
        }
        
        try {
            DB::beginTransaction();
            
            $unit = PettyCashUnit::findOrFail($validated['petty_cash_unit_id']);
            
            // Calculate total amount
            $totalAmount = 0;
            if (!empty($lineItems)) {
                foreach ($lineItems as $item) {
                    $totalAmount += (float) $item['amount'];
                }
            } else {
                $totalAmount = (float) $validated['amount'];
            }
            
            // Handle file upload
            $receiptPath = $transaction->receipt_attachment;
            if ($request->hasFile('receipt_attachment')) {
                // Delete old file if exists
                if ($receiptPath) {
                    Storage::disk('public')->delete($receiptPath);
                }
                $receiptPath = $request->file('receipt_attachment')->store('petty-cash/receipts', 'public');
            }
            
            // Determine payee information
            $payee = null;
            $payeeType = null;
            $payeeId = null;
            $customerId = null;
            $supplierId = null;
            $employeeId = null;
            
            if (!empty($lineItems)) {
                if ($request->payee_type === 'customer' && $request->customer_id) {
                    $customer = \App\Models\Customer::find($request->customer_id);
                    $payee = $customer ? $customer->name : null;
                    $payeeType = 'customer';
                    $payeeId = $request->customer_id;
                    $customerId = $request->customer_id;
                } elseif ($request->payee_type === 'supplier' && $request->supplier_id) {
                    $supplier = \App\Models\Supplier::find($request->supplier_id);
                    $payee = $supplier ? $supplier->name : null;
                    $payeeType = 'supplier';
                    $payeeId = $request->supplier_id;
                    $supplierId = $request->supplier_id;
                } elseif ($request->payee_type === 'employee') {
                    $payee = $request->payee_name ?? null;
                    $payeeType = 'employee';
                    $payeeId = null;
                    $employeeId = null;
                } elseif ($request->payee_type === 'other' && $request->payee_name) {
                    $payee = $request->payee_name;
                    $payeeType = 'other';
                }
            } else {
                $payee = $validated['payee'] ?? null;
            }
            
            // Check if transaction already has a payment_id (already posted to GL)
            if ($transaction->payment_id) {
                return back()->withInput()->with('error', 'This transaction has already been posted to GL. Cannot edit posted transactions.');
            }
            
            // Store old values for balance reversal
            $oldUnit = $transaction->pettyCashUnit;
            $oldAmount = $transaction->amount;
            $oldStatus = $transaction->status;
            $wasApproved = ($oldStatus === 'approved' && !$transaction->payment_id);
            
            // If transaction was previously approved, reverse the balance change
            if ($wasApproved && $oldUnit->id == $unit->id) {
                $oldUnit->increment('current_balance', $oldAmount);
            } elseif ($wasApproved && $oldUnit->id != $unit->id) {
                // If unit changed, reverse on old unit
                $oldUnit->increment('current_balance', $oldAmount);
            }
            
            // Check if new amount requires approval
            $requiresApproval = $unit->requiresApproval($totalAmount);
            
            // If transaction was previously approved (or was in draft/rejected and can be edited),
            // and user is explicitly editing it, keep it approved and post it
            // This allows editing approved transactions without requiring re-approval
            if ($oldStatus === 'approved' || $oldStatus === 'draft' || $oldStatus === 'rejected') {
                // User is editing - keep it approved so it can be posted
                $newStatus = 'approved';
            } else {
                // For other statuses (submitted), check if approval is required
                $newStatus = $requiresApproval ? 'submitted' : 'approved';
            }
            
            // Check balance for new amount
            if (!$unit->canSpend($totalAmount)) {
                // Restore balance if we reversed it
                if ($wasApproved && $oldUnit->id == $unit->id) {
                    $oldUnit->decrement('current_balance', $oldAmount);
                } elseif ($wasApproved && $oldUnit->id != $unit->id) {
                    $oldUnit->decrement('current_balance', $oldAmount);
                }
                return back()->withInput()->with('error', 'Insufficient petty cash balance.');
            }
            
            // Update transaction
            $updateData = [
                'petty_cash_unit_id' => $validated['petty_cash_unit_id'],
                'transaction_date' => $validated['transaction_date'],
                'expense_category_id' => $validated['expense_category_id'] ?? null,
                'amount' => $totalAmount,
                'payee' => $payee,
                'payee_type' => $payeeType,
                'customer_id' => $customerId,
                'supplier_id' => $supplierId,
                'employee_id' => $employeeId,
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'receipt_attachment' => $receiptPath,
                'status' => $newStatus,
            ];
            
            // If auto-approved, set approved_by and approved_at, and update balance
            if ($newStatus === 'approved') {
                $updateData['approved_by'] = Auth::id();
                $updateData['approved_at'] = now();
                $unit->decrement('current_balance', $totalAmount);
                $updateData['balance_after'] = $unit->current_balance;
            } else {
                $updateData['balance_after'] = null;
            }
            
            $transaction->update($updateData);
            
            // Reload transaction with items
            $transaction->refresh();
            
            // Update line items if provided
            if (!empty($lineItems)) {
                // Delete existing items
                $transaction->items()->delete();
                
                // Create new items
                foreach ($lineItems as $item) {
                    PettyCashTransactionItem::create([
                        'petty_cash_transaction_id' => $transaction->id,
                        'chart_account_id' => $item['chart_account_id'],
                        'amount' => $item['amount'],
                        'description' => $item['description'] ?? null,
                    ]);
                }
            }
            
            // Reload transaction with items to ensure they're available
            $transaction->refresh();
            $transaction->load('items');
            
            // Auto-post to GL if transaction is approved and not already posted
            if ($newStatus === 'approved' && !$transaction->payment_id) {
                try {
                    \Log::info('Attempting to post updated transaction to GL', [
                        'transaction_id' => $transaction->id,
                        'transaction_number' => $transaction->transaction_number,
                        'status' => $transaction->status,
                        'payment_id' => $transaction->payment_id,
                        'amount' => $totalAmount,
                    ]);
                    
                    $this->pettyCashService->postTransactionToGL($transaction);
                    
                    // Reload transaction to get updated payment_id
                    $transaction->refresh();
                    
                    \Log::info('Petty cash transaction updated and posted to GL successfully', [
                        'transaction_id' => $transaction->id,
                        'transaction_number' => $transaction->transaction_number,
                        'payment_id' => $transaction->payment_id,
                        'amount' => $totalAmount,
                        'new_balance' => $unit->current_balance,
                    ]);
                } catch (\Exception $e) {
                    // Rollback balance change if GL posting fails
                    $unit->increment('current_balance', $totalAmount);
                    DB::rollBack();
                    \Log::error('Failed to post updated transaction to GL', [
                        'transaction_id' => $transaction->id,
                        'transaction_number' => $transaction->transaction_number,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return back()->withInput()->with('error', 'Transaction updated but failed to post to GL: ' . $e->getMessage());
                }
            } else {
                \Log::info('Transaction not posted to GL after update', [
                    'transaction_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'new_status' => $newStatus,
                    'payment_id' => $transaction->payment_id,
                    'reason' => $newStatus !== 'approved' ? 'Status is not approved' : 'Already has payment_id',
                ]);
            }
            
            DB::commit();
            
            $message = $newStatus === 'approved' 
                ? 'Transaction updated and posted to GL successfully.' 
                : 'Transaction updated successfully. Awaiting approval.';
            
            return redirect()->route('accounting.petty-cash.units.show', $transaction->pettyCashUnit->encoded_id)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update transaction: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified transaction
     */
    public function destroy($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
            }
            abort(404);
        }
        
        $transaction = PettyCashTransaction::findOrFail($id);
        
        // Check if transaction can be deleted
        if (!$transaction->canBeDeleted()) {
            $message = 'Transaction cannot be deleted. It may have been posted to GL or is in a status that prevents deletion.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        try {
            DB::beginTransaction();
            
            $unitId = $transaction->petty_cash_unit_id;
            $unit = $transaction->pettyCashUnit;
            $paymentId = $transaction->payment_id;
            
            // If transaction was posted to GL (has payment_id), reverse the balance and delete GL entries
            if ($paymentId) {
                // Reverse the balance change (balance was decremented when posted)
                $unit->increment('current_balance', $transaction->amount);
                
                // Get the payment to delete related records
                $payment = \App\Models\Payment::find($paymentId);
                if ($payment) {
                    // Delete GL transactions linked to this payment
                    \App\Models\GlTransaction::where('transaction_id', $paymentId)
                        ->where('transaction_type', 'payment')
                        ->delete();
                    
                    // Delete payment items
                    $payment->paymentItems()->delete();
                    
                    // Delete the payment
                    $payment->delete();
                    
                    \Log::info('Deleted payment and related GL transactions for petty cash transaction', [
                        'transaction_id' => $transaction->id,
                        'payment_id' => $paymentId
                    ]);
                }
            } elseif ($transaction->status === 'approved' && !$paymentId) {
                // If transaction was approved but not posted to GL, reverse the balance change
                $unit->increment('current_balance', $transaction->amount);
            }
            
            // Delete register entries related to this transaction
            \App\Models\PettyCash\PettyCashRegister::where('petty_cash_transaction_id', $transaction->id)->delete();
            
            // Delete receipt file if exists
            if ($transaction->receipt_attachment) {
                Storage::disk('public')->delete($transaction->receipt_attachment);
            }
            
            // Delete transaction items (hard delete - items don't use soft deletes)
            $transaction->items()->delete();
            
            // Force delete transaction (hard delete, not soft delete)
            $transaction->forceDelete();
            
            DB::commit();
            
            $message = 'Transaction deleted successfully.';
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->route('accounting.petty-cash.units.show', $unit->encoded_id)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = 'Failed to delete transaction: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * Generate transaction number
     */
    /**
     * Manually post transaction to GL
     */
    public function postToGL($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
            }
            abort(404);
        }
        
        $transaction = PettyCashTransaction::findOrFail($id);
        
        // Check if already posted
        if ($transaction->payment_id) {
            $message = 'Transaction has already been posted to GL.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        // Check if can be posted
        if (!$transaction->canBePosted() && $transaction->status !== 'approved') {
            $message = 'Transaction must be approved before it can be posted to GL.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        try {
            DB::beginTransaction();
            
            // If not approved yet, approve it first
            if ($transaction->status !== 'approved') {
                $transaction->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);
                
                // Update unit balance
                $unit = $transaction->pettyCashUnit;
                $unit->decrement('current_balance', $transaction->amount);
                $transaction->update(['balance_after' => $unit->current_balance]);
            }
            
            // Reload transaction with items
            $transaction->refresh();
            $transaction->load('items');
            
            // Post to GL
            $this->pettyCashService->postTransactionToGL($transaction);
            
            DB::commit();
            
            $message = 'Transaction posted to GL successfully.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = 'Failed to post transaction to GL: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Generate transaction number
     */
    private function generateTransactionNumber($unit): string
    {
        $prefix = 'PCT-' . $unit->code . '-';
        $year = date('Y');
        $month = date('m');
        
        // Only check non-deleted transactions (since we're using soft deletes)
        $lastTransaction = PettyCashTransaction::where('transaction_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('transaction_number', 'desc')
            ->first();
        
        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Disburse cash (Custodian action in Sub-Imprest Mode)
     */
    public function disburse($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
            }
            abort(404);
        }
        
        $transaction = PettyCashTransaction::findOrFail($id);
        $unit = $transaction->pettyCashUnit;
        $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($unit->company_id);
        
        // Only allow disbursement in Sub-Imprest mode and if transaction is approved
        if (!$settings->isSubImprestMode()) {
            $message = 'Disbursement is only available in Sub-Imprest mode.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        if ($transaction->status !== 'approved') {
            $message = 'Transaction must be approved before disbursement.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        // Check balance
        if (!$unit->canSpend($transaction->amount)) {
            $message = 'Insufficient petty cash balance.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        try {
            DB::beginTransaction();
            
            // Reduce balance
            $unit->decrement('current_balance', $transaction->amount);
            
            // Update transaction status to pending_receipt
            $transaction->update([
                'status' => 'pending_receipt',
                'receipt_status' => 'pending',
                'disbursed_by' => Auth::id(),
                'disbursed_at' => now(),
                'balance_after' => $unit->current_balance,
            ]);
            
            // Create register entry
            \App\Services\PettyCashModeService::createRegisterEntry($transaction);
            
            DB::commit();
            
            $message = 'Cash disbursed successfully. Waiting for receipt upload.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = 'Failed to disburse cash: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Upload receipt
     */
    public function uploadReceipt($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
            }
            abort(404);
        }
        
        $transaction = PettyCashTransaction::findOrFail($id);
        
        if ($transaction->status !== 'pending_receipt') {
            $message = 'Transaction is not in pending receipt status.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        $validated = $request->validate([
            'receipt_attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,application/x-pdf,image/jpeg,image/jpg,image/png|max:5120',
        ]);
        
        try {
            // Delete old receipt if exists
            if ($transaction->receipt_attachment) {
                \Storage::disk('public')->delete($transaction->receipt_attachment);
            }
            
            // Store new receipt
            $receiptPath = $request->file('receipt_attachment')->store('petty-cash/receipts', 'public');
            
            $transaction->update([
                'receipt_attachment' => $receiptPath,
                'receipt_status' => 'uploaded',
            ]);
            
            $message = 'Receipt uploaded successfully. Waiting for verification.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            $errorMessage = 'Failed to upload receipt: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Verify receipt (Custodian verifies → Accountant posts)
     */
    public function verifyReceipt($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
            }
            abort(404);
        }
        
        $transaction = PettyCashTransaction::findOrFail($id);
        
        if ($transaction->receipt_status !== 'uploaded') {
            $message = 'Receipt must be uploaded before verification.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        $validated = $request->validate([
            'receipt_verification_notes' => 'nullable|string',
            'verify' => 'required|boolean', // true to verify, false to reject
        ]);
        
        try {
            DB::beginTransaction();
            
            if ($validated['verify']) {
                // Verify receipt
                $transaction->update([
                    'receipt_status' => 'verified',
                    'receipt_verified_by' => Auth::id(),
                    'receipt_verified_at' => now(),
                    'receipt_verification_notes' => $validated['receipt_verification_notes'] ?? null,
                ]);
                
                // Reload transaction with items before posting to GL
                $transaction->refresh();
                $transaction->load('items');
                
                // Post to GL (Accountant posts expense)
                $this->pettyCashService->postTransactionToGL($transaction);
                
                // Update status to posted
                $transaction->update(['status' => 'posted']);
                
                $message = 'Receipt verified and expense posted to GL.';
            } else {
                // Reject receipt
                $transaction->update([
                    'receipt_status' => 'rejected',
                    'receipt_verified_by' => Auth::id(),
                    'receipt_verified_at' => now(),
                    'receipt_verification_notes' => $validated['receipt_verification_notes'] ?? 'Receipt rejected',
                ]);
                
                $message = 'Receipt rejected.';
            }
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = 'Failed to verify receipt: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }
}

