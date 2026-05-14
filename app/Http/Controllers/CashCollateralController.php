<?php

namespace App\Http\Controllers;

use App\Models\CashCollateral;
use App\Models\CashCollateralType;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ChartAccount;
use App\Models\BankAccount;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\GlTransaction;
use App\Helpers\SmsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;
use Vinkla\Hashids\Facades\Hashids;

class CashCollateralController extends Controller
{
    /**
     * Display a listing of cash collaterals
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataTable($request);
        }

        $totalCollaterals = CashCollateral::where('company_id', Auth::user()->company_id)->count();
        
        // Calculate total amount based on actual current balances (not static amounts)
        $collaterals = CashCollateral::where('company_id', Auth::user()->company_id)->get();
        $totalAmount = 0;
        
        foreach ($collaterals as $collateral) {
            // Calculate actual current balance for each collateral
            $deposits = \App\Models\Receipt::where('reference', $collateral->id)
                ->where('reference_type', 'Deposit')
                ->sum('amount');
            
            $withdrawals = \App\Models\Payment::where('reference', $collateral->id)
                ->where('reference_type', 'Withdrawal')
                ->sum('amount');
            
            // Include both invoice and cash sale payments from journal system
            $journalWithdrawals = \App\Models\Journal::where('customer_id', $collateral->customer_id)
                ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
                ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
                ->where('journal_items.chart_account_id', 28) // Cash Deposits account
                ->where('journal_items.nature', 'debit')
                ->sum('journal_items.amount');
            
            $currentBalance = $deposits - ($withdrawals + $journalWithdrawals);
            $totalAmount += $currentBalance;
        }
        
        $cashCollateralTypes = CashCollateralType::with('chartAccount')
                                                ->where('is_active', 1)
                                                ->get();
        $customers = Customer::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $branches = Auth::user()->company->branches ?? collect();
        $chartAccounts = ChartAccount::orderBy('account_code')->get();
        $occupiedCustomerIds = CashCollateral::where('company_id', Auth::user()->company_id)
            ->pluck('customer_id')
            ->unique()
            ->values()
            ->all();

        return view('cash_collaterals.index', compact(
            'totalCollaterals', 
            'totalAmount',
            'cashCollateralTypes',
            'customers',
            'branches',
            'chartAccounts',
            'occupiedCustomerIds'
        ));
    }

    /**
     * Get data for DataTables
     */
    public function getDataTable(Request $request)
    {
        $query = CashCollateral::with(['customer', 'type.chartAccount', 'branch'])
                              ->where('company_id', Auth::user()->company_id);

        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->whereHas('customer', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('type', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                })
                ->orWhere('amount', 'LIKE', "%{$search}%");
            });
        }

        $totalRecords = CashCollateral::where('company_id', Auth::user()->company_id)->count();
        $filteredRecords = $query->count();

        $order = $request->order[0] ?? null;
        if ($order) {
            $columns = ['id', 'customer', 'type', 'amount', 'created_at', 'actions'];
            $orderColumn = $columns[$order['column']] ?? 'id';
            $orderDirection = $order['dir'] ?? 'asc';
            
            if ($orderColumn === 'customer') {
                $query->join('customers', 'cash_collaterals.customer_id', '=', 'customers.id')
                      ->orderBy('customers.name', $orderDirection)
                      ->select('cash_collaterals.*');
            } elseif ($orderColumn === 'type') {
                $query->join('cash_collateral_types', 'cash_collaterals.type_id', '=', 'cash_collateral_types.id')
                      ->orderBy('cash_collateral_types.name', $orderDirection)
                      ->select('cash_collaterals.*');
            } else {
                $query->orderBy($orderColumn === 'actions' ? 'id' : $orderColumn, $orderDirection);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->has('start')) {
            $query->skip($request->start);
        }

        if ($request->has('length') && $request->length != -1) {
            $query->take($request->length);
        }

        $data = $query->get()->map(function($collateral) {
            // Calculate actual current balance (same logic as customer DataTable)
            $deposits = \App\Models\Receipt::where('reference', $collateral->id)
                ->where('reference_type', 'Deposit')
                ->sum('amount');
            
            $withdrawals = \App\Models\Payment::where('reference', $collateral->id)
                ->where('reference_type', 'Withdrawal')
                ->sum('amount');
            
            // Include both invoice and cash sale payments from journal system
            $journalWithdrawals = \App\Models\Journal::where('customer_id', $collateral->customer_id)
                ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
                ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
                ->where('journal_items.chart_account_id', 28) // Cash Deposits account
                ->where('journal_items.nature', 'debit')
                ->sum('journal_items.amount');
            
            $currentBalance = floatval($deposits) - (floatval($withdrawals) + floatval($journalWithdrawals));
            $encodedId = Hashids::encode($collateral->id);
            
            $actions = '<div class="btn-group" role="group">';
            
            // Show button
            $actions .= '<a href="' . route('cash_collaterals.show', $encodedId) . '" 
                            class="btn btn-sm btn-outline-info" 
                            title="View Details">
                            <i class="bx bx-show"></i>
                         </a>';
            
            // Deposit button (with permission check)
            if (auth()->user()->can('deposit cash collateral')) {
                $actions .= '<a href="' . route('cash_collaterals.deposit', $encodedId) . '" 
                                class="btn btn-sm btn-outline-success" 
                                title="Make Deposit">
                                <i class="bx bx-plus-circle"></i>
                             </a>';
            }
            
            // Withdraw button (only if balance > 0 and user has permission)
            if ($currentBalance > 0 && auth()->user()->can('withdraw cash collateral')) {
                $actions .= '<a href="' . route('cash_collaterals.withdraw', $encodedId) . '" 
                                class="btn btn-sm btn-outline-warning" 
                                title="Withdraw">
                                <i class="bx bx-minus-circle"></i>
                             </a>';
            }
            
            // Edit button  
            $actions .= '<button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                            data-id="' . $collateral->id . '"
                            data-customer-id="' . $collateral->customer_id . '"
                            data-type-id="' . $collateral->type_id . '"
                            title="Edit">
                            <i class="bx bx-edit"></i>
                         </button>';
            
            // Delete button
            $actions .= '<button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                            data-id="' . $collateral->id . '" 
                            title="Delete">
                            <i class="bx bx-trash"></i>
                         </button>';
            
            $actions .= '</div>';

            return [
                'DT_RowIndex' => $collateral->id,
                'customer_name' => $collateral->customer->name ?? 'N/A',
                'type_name' => $collateral->type->name ?? 'N/A',
                'chart_account' => ($collateral->type && $collateral->type->chartAccount) 
                    ? $collateral->type->chartAccount->account_code . ' - ' . $collateral->type->chartAccount->account_name
                    : 'No chart account',
                'formatted_amount' => number_format($currentBalance, 2), // Show calculated balance instead of static amount
                'branch_name' => $collateral->branch->name ?? 'N/A',
                'formatted_date' => $collateral->created_at->format('M d, Y'),
                'action' => $actions
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Show the form for creating a new cash collateral
     */
    public function create(Request $request)
    {
        $types = CashCollateralType::where('is_active', 1)->get();
        $customers = Customer::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $branches = Auth::user()->company->branches ?? collect();
        
        // Pre-select customer if passed in URL
        $selectedCustomerId = null;
        if ($request->has('customer_id')) {
            $encodedCustomerId = $request->get('customer_id');
            $decodedCustomerId = Hashids::decode($encodedCustomerId)[0] ?? null;
            if ($decodedCustomerId && $customers->contains('id', $decodedCustomerId)) {
                $selectedCustomerId = $decodedCustomerId;
            }
        }
        
        $occupiedCustomerIds = CashCollateral::where('company_id', Auth::user()->company_id)
            ->pluck('customer_id')
            ->unique()
            ->values()
            ->all();

        return view('cash_collaterals.create', compact('types', 'customers', 'branches', 'selectedCustomerId', 'occupiedCustomerIds'));
    }

    /**
     * Store a newly created cash collateral
     */
    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $request->validate([
            'customer_id' => [
                'required',
                'exists:customers,id',
                Rule::unique('cash_collaterals', 'customer_id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'type_id' => 'required|exists:cash_collateral_types,id',
        ], [
            'customer_id.unique' => 'This customer already has a cash deposit account. Use deposits on the existing account instead of creating another.',
        ]);

        // Get user's branch_id, fallback to first available branch if null
        $branchId = Auth::user()->branch_id;
        if (!$branchId) {
            $firstBranch = Auth::user()->company->branches()->first();
            $branchId = $firstBranch ? $firstBranch->id : null;
        }

        $cashCollateral = CashCollateral::create([
            'customer_id' => $request->customer_id,
            'type_id' => $request->type_id,
            'amount' => 0.00, // Default amount
            'company_id' => Auth::user()->company_id,
            'branch_id' => $branchId,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cash collateral created successfully.',
                'data' => $cashCollateral->load(['customer', 'type', 'branch'])
            ]);
        }

        return redirect()->route('cash_collaterals.index')
            ->with('success', 'Cash collateral created successfully.');
    }

    /**
     * Display the specified cash collateral
     */
    public function show($cashcollateral)
    {
        // Decode Hashids if needed
        $id = is_numeric($cashcollateral) ? $cashcollateral : (Hashids::decode($cashcollateral)[0] ?? null);
        
        if (!$id) {
            abort(404, 'Invalid Cash Collateral ID.');
        }
        
        $cashCollateral = CashCollateral::with(['customer', 'type', 'branch', 'company'])->findOrFail($id);
        
        // Get transaction history by combining deposits and withdrawals
        $transactions = collect();
        
        // Get deposit transactions (receipts)
        $deposits = Receipt::with(['user'])
            ->where('reference', $cashCollateral->id)
            ->where('reference_type', 'Deposit')
            ->get()
            ->map(function($receipt) {
                return [
                    'id' => $receipt->id,
                    'date' => $receipt->date,
                    'narration' => $receipt->description ?? 'Cash Deposit',
                    'created_by' => $receipt->user->name ?? 'System',
                    'type' => 'deposit',
                    'credit' => $receipt->amount, // Credit for deposits
                    'debit' => 0,
                    'created_at' => $receipt->created_at,
                    'deletable' => true, // Direct deposits can be deleted
                    'delete_type' => 'receipt',
                    'delete_id' => $receipt->id,
                ];
            });
        
        // Get withdrawal transactions (payments)
        $withdrawals = Payment::with(['user'])
            ->where('reference', $cashCollateral->id)
            ->where('reference_type', 'Withdrawal')
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'date' => $payment->date,
                    'narration' => $payment->description ?? 'Cash Withdrawal',
                    'created_by' => $payment->user->name ?? 'System',
                    'type' => 'withdrawal',
                    'credit' => 0,
                    'debit' => $payment->amount, // Debit for withdrawals
                    'created_at' => $payment->created_at,
                    'deletable' => true, // Direct withdrawals can be deleted
                    'delete_type' => 'payment',
                    'delete_id' => $payment->id,
                ];
            });

        // Get journal-based cash deposit payments (new system)
        $journalPayments = \App\Models\Journal::with(['items', 'user'])
            ->where('customer_id', $cashCollateral->customer_id)
            ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
            ->get()
            ->filter(function($journal) {
                // Only include journals that have cash deposit account entries (account 28)
                return $journal->items->where('chart_account_id', 28)->where('nature', 'debit')->count() > 0;
            })
            ->map(function($journal) {
                $cashDepositItem = $journal->items->where('chart_account_id', 28)->where('nature', 'debit')->first();
                
                // Set appropriate narration based on reference type
                $narration = $journal->description ?? 'Cash Deposit Payment';
                if ($journal->reference_type == 'cash_sale_payment') {
                    $narration = $journal->description ?? 'Cash Sale Payment (Cash Deposit)';
                } elseif ($journal->reference_type == 'sales_invoice_payment') {
                    $narration = $journal->description ?? 'Invoice Payment (Cash Deposit)';
                }
                
                return [
                    'id' => 'journal_' . $journal->id,
                    'date' => $journal->date,
                    'narration' => $narration,
                    'created_by' => $journal->user->name ?? 'System',
                    'type' => 'journal_payment',
                    'credit' => 0,
                    'debit' => $cashDepositItem->amount, // Debit for cash deposit usage
                    'created_at' => $journal->created_at,
                    'deletable' => false, // Journal entries from invoices/sales cannot be deleted directly
                    'delete_type' => null,
                    'delete_id' => null,
                ];
            });
        
        // Combine deposits, withdrawals, and journal payments, then sort by date (oldest first)
        $transactions = $deposits->merge($withdrawals)->merge($journalPayments)
            ->sortBy('created_at')
            ->values();
        
        // Calculate running balance for each transaction
        $runningBalance = 0;
        $transactions = $transactions->map(function($transaction, $index) use (&$runningBalance) {
            $runningBalance += ($transaction['credit'] - $transaction['debit']);
            $transaction['balance'] = $runningBalance;
            $transaction['row_number'] = $index + 1;
            return $transaction;
        });

        // Store the final calculated balance
        $calculatedBalance = $runningBalance;

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $cashCollateral,
                'transactions' => $transactions,
                'calculated_balance' => $calculatedBalance
            ]);
        }

        return view('cash_collaterals.show', compact('cashCollateral', 'transactions', 'calculatedBalance'));
    }

    /**
     * Export cash deposit statement as PDF
     */
    public function exportStatementPdf($cashcollateral)
    {
        try {
            // Decode Hashids if needed
            $id = is_numeric($cashcollateral) ? $cashcollateral : (Hashids::decode($cashcollateral)[0] ?? null);
            
            if (!$id) {
                abort(404, 'Invalid Cash Collateral ID.');
            }

            $cashCollateral = CashCollateral::with(['customer', 'type', 'branch', 'company'])->findOrFail($id);
            
            // Ensure customer relationship is loaded and exists
            if (!$cashCollateral->customer && $cashCollateral->customer_id) {
                $cashCollateral->load('customer');
            }
            
            // If customer still doesn't exist, log error but continue
            if (!$cashCollateral->customer) {
                \Log::warning('Cash Collateral missing customer', [
                    'cash_collateral_id' => $cashCollateral->id,
                    'customer_id' => $cashCollateral->customer_id
                ]);
            }

            // Use the same transaction logic from show method
            $transactions = collect();
            
            // Get deposit transactions (receipts)
            $deposits = Receipt::with(['user'])
                ->where('reference', $cashCollateral->id)
                ->where('reference_type', 'Deposit')
                ->get()
                ->map(function($receipt) {
                    return [
                        'id' => $receipt->id,
                        'date' => $receipt->date,
                        'narration' => $receipt->description ?? 'Cash Deposit',
                        'created_by' => $receipt->user->name ?? 'System',
                        'type' => 'deposit',
                        'credit' => $receipt->amount,
                        'debit' => 0,
                        'created_at' => $receipt->created_at,
                    ];
                });

            // Get withdrawal transactions (payments)
            $withdrawals = Payment::with(['user'])
                ->where('reference', $cashCollateral->id)
                ->where('reference_type', 'Withdrawal')
                ->get()
                ->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'date' => $payment->date,
                        'narration' => $payment->description ?? 'Cash Withdrawal',
                        'created_by' => $payment->user->name ?? 'System',
                        'type' => 'withdrawal',
                        'credit' => 0,
                        'debit' => $payment->amount,
                        'created_at' => $payment->created_at,
                    ];
                });

            // Get journal-based cash deposit payments (new system)
            $journalPayments = \App\Models\Journal::with(['items', 'user'])
                ->where('customer_id', $cashCollateral->customer_id)
                ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
                ->get()
                ->filter(function($journal) {
                    return $journal->items->where('chart_account_id', 28)->where('nature', 'debit')->count() > 0;
                })
                ->map(function($journal) {
                    $cashDepositItem = $journal->items->where('chart_account_id', 28)->where('nature', 'debit')->first();
                    
                    // Set appropriate narration based on reference type
                    $narration = $journal->description ?? 'Cash Deposit Payment';
                    if ($journal->reference_type == 'cash_sale_payment') {
                        $narration = $journal->description ?? 'Cash Sale Payment (Cash Deposit)';
                    } elseif ($journal->reference_type == 'sales_invoice_payment') {
                        $narration = $journal->description ?? 'Invoice Payment (Cash Deposit)';
                    }
                    
                    return [
                        'id' => 'journal_' . $journal->id,
                        'date' => $journal->date,
                        'narration' => $narration,
                        'created_by' => $journal->user->name ?? 'System',
                        'type' => 'journal_payment',
                        'credit' => 0,
                        'debit' => $cashDepositItem->amount,
                        'created_at' => $journal->created_at,
                    ];
                });

            // Combine deposits, withdrawals, and journal payments, then sort by date (oldest first)
            $transactions = $deposits->merge($withdrawals)->merge($journalPayments)
                ->sortBy('created_at')
                ->values();

            // Calculate running balance for each transaction
            $runningBalance = 0;
            $transactions = $transactions->map(function($transaction, $index) use (&$runningBalance) {
                $runningBalance += ($transaction['credit'] - $transaction['debit']);
                $transaction['balance'] = $runningBalance;
                $transaction['row_number'] = $index + 1;
                return $transaction;
            });

            // Store the final calculated balance
            $calculatedBalance = $runningBalance;

            // Generate PDF using DomPDF
            $pdf = \PDF::loadView('cash_collaterals.statement_pdf', compact('cashCollateral', 'transactions', 'calculatedBalance'));

            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');

            // Generate filename
            $customerNo = $cashCollateral->customer ? $cashCollateral->customer->customerNo : $cashCollateral->id;
            $filename = 'cash_deposit_statement_' . $customerNo . '_' . date('Y-m-d_H-i-s') . '.pdf';

            // Return PDF for download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to export PDF: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified cash collateral
     */
    public function edit($cashcollateral)
    {
        $cashCollateral = CashCollateral::findOrFail($cashcollateral);
        $collateral = $cashCollateral;
        $types = CashCollateralType::where('is_active', 1)->get();
        $customers = Customer::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $branches = Auth::user()->company->branches ?? collect();
        $occupiedCustomerIds = CashCollateral::where('company_id', Auth::user()->company_id)
            ->where('id', '!=', $cashCollateral->id)
            ->pluck('customer_id')
            ->unique()
            ->values()
            ->all();

        return view('cash_collaterals.edit', compact('cashCollateral', 'collateral', 'types', 'customers', 'branches', 'occupiedCustomerIds'));
    }

    /**
     * Update the specified cash collateral
     */
    public function update(Request $request, $cashcollateral)
    {
        $cashCollateral = CashCollateral::findOrFail($cashcollateral);

        $companyId = Auth::user()->company_id;
        $request->validate([
            'customer_id' => [
                'required',
                'exists:customers,id',
                Rule::unique('cash_collaterals', 'customer_id')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($cashCollateral->id),
            ],
            'type_id' => 'required|exists:cash_collateral_types,id',
        ], [
            'customer_id.unique' => 'This customer already has a cash deposit account.',
        ]);

        // Get user's branch_id, fallback to first available branch if null
        $branchId = Auth::user()->branch_id;
        if (!$branchId) {
            $firstBranch = Auth::user()->company->branches()->first();
            $branchId = $firstBranch ? $firstBranch->id : null;
        }

        $cashCollateral->update([
            'customer_id' => $request->customer_id,
            'type_id' => $request->type_id,
            'branch_id' => $branchId,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cash collateral updated successfully.',
                'data' => $cashCollateral->load(['customer', 'type', 'branch'])
            ]);
        }

        return redirect()->route('cash_collaterals.index')
            ->with('success', 'Cash collateral updated successfully.');
    }

    /**
     * Remove the specified cash collateral
     */
    public function destroy($cashcollateral)
    {
        try {
            $cashCollateral = CashCollateral::findOrFail($cashcollateral);
            
            // Check if cash collateral has any transactions
            $hasDeposits = \App\Models\Receipt::where('reference', $cashCollateral->id)
                ->where('reference_type', 'Deposit')
                ->exists();
            
            $hasWithdrawals = \App\Models\Payment::where('reference', $cashCollateral->id)
                ->where('reference_type', 'Withdrawal')
                ->exists();
            
            // Check if cash collateral has been used in journal entries (payments)
            $hasJournalTransactions = \App\Models\Journal::where('customer_id', $cashCollateral->customer_id)
                ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
                ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
                ->where('journal_items.chart_account_id', 28) // Cash Deposits account
                ->where('journal_items.nature', 'debit')
                ->exists();
            
            if ($hasDeposits || $hasWithdrawals || $hasJournalTransactions) {
                $errorMessage = 'Cannot delete cash deposit account. It has existing transactions.';
                
                if ($hasDeposits) {
                    $depositCount = \App\Models\Receipt::where('reference', $cashCollateral->id)
                        ->where('reference_type', 'Deposit')
                        ->count();
                    $errorMessage .= " ($depositCount deposit(s)";
                }
                
                if ($hasWithdrawals) {
                    $withdrawalCount = \App\Models\Payment::where('reference', $cashCollateral->id)
                        ->where('reference_type', 'Withdrawal')
                        ->count();
                    $errorMessage .= ($hasDeposits ? ', ' : ' (') . "$withdrawalCount withdrawal(s)";
                }
                
                if ($hasJournalTransactions) {
                    $errorMessage .= ($hasDeposits || $hasWithdrawals ? ', ' : ' (') . "used in payments";
                }
                
                $errorMessage .= ')';
                
                if (request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 422);
                }

                return redirect()->route('cash_collaterals.index')
                    ->with('error', $errorMessage);
            }
            
            // Safe to delete - no transactions exist
            $cashCollateral->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cash collateral deleted successfully.'
                ]);
            }

            return redirect()->route('cash_collaterals.index')
                ->with('success', 'Cash collateral deleted successfully.');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting cash collateral: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('cash_collaterals.index')
                ->with('error', 'Error deleting cash collateral: ' . $e->getMessage());
        }
    }

        //////////////DEPOSIT FOR CASH COLLATERAL OF CUSTOMER/////////
    /**
     * Show the deposit form for cash collateral
     */
    public function deposit(Request $request, $cashcollateral)
    {
        $id = Hashids::decode($cashcollateral)[0] ?? null;

        if (!$id) {
            abort(404, 'Invalid Cash Collateral ID.');
        }

        $collateral = CashCollateral::with('customer')->findOrFail($id);
        $customer = $collateral->customer;

        // Limit bank accounts by branch scope (all branches or current branch)
        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $bankAccounts = BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();

        return view('cash_collaterals.deposit', compact('bankAccounts', 'customer', 'collateral'));
    }

    protected function sendSms($phone, $message)
    {
        SmsHelper::send($phone, $message);
    }

    /**
     * PROCESS CASH COLLATERAL FOR DEPOSIT OF CUSTOMER
     */
    public function depositStore(Request $request)
    {
        $request->validate([
            'collateral_id' => 'required|string', // Still encoded, decode later
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'required|string|max:500',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Decode Hashid
                $collateralId = Hashids::decode($request->collateral_id)[0] ?? null;

                if (!$collateralId) {
                    throw new \Exception('Invalid collateral ID.');
                }

                $user = Auth::user();
                $collateral = CashCollateral::with(['customer', 'type'])->findOrFail($collateralId);
                $bankAccount = BankAccount::findOrFail($request->bank_account_id);
                $notes = $request->notes;

                // Check if required relationships exist
                if (!$collateral->type || !$collateral->type->chart_account_id) {
                    throw new \Exception('Cash collateral type must have a chart account assigned.');
                }

                if (!$bankAccount->chart_account_id) {
                    throw new \Exception('Bank account must have a chart account assigned.');
                }

                if (!$user->branch_id) {
                    // Get user's first assigned branch
                    $userBranch = $user->branches()->first();
                    if (!$userBranch) {
                        throw new \Exception('User must be assigned to a branch.');
                    }
                    $branchId = $userBranch->id;
                } else {
                    $branchId = $user->branch_id;
                }

                // Create receipt
                $receipt = Receipt::create([
                    'reference' => $collateralId,
                    'reference_type' => 'Deposit',
                    'reference_number' => null,
                    'amount' => $request->amount,
                    'date' => $request->deposit_date,
                    'description' => $notes,
                    'user_id' => $user->id,
                    'bank_account_id' => $bankAccount->id,
                    'payee_type' => 'Customer',
                    'payee_id' => $collateral->customer_id,
                    'payee_name' => $collateral->customer->name,
                    'branch_id' => $branchId,
                    'approved' => true,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                // Create receipt item
                ReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'chart_account_id' => $collateral->type->chart_account_id,
                    'amount' => $request->amount,
                    'description' => $notes,
                ]);

                // GL Transactions

                // Debit: Bank Account
                GlTransaction::create([
                    'chart_account_id' => $bankAccount->chart_account_id,
                    'customer_id' => $collateral->customer_id,
                    'amount' => $request->amount,
                    'nature' => 'debit',
                    'transaction_id' => $receipt->id,
                    'transaction_type' => 'receipt',
                    'date' => $request->deposit_date,
                    'description' => $notes,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);

                // Credit: Cash Collateral Account
                GlTransaction::create([
                    'chart_account_id' => $collateral->type->chart_account_id,
                    'customer_id' => $collateral->customer_id,
                    'amount' => $request->amount,
                    'nature' => 'credit',
                    'transaction_id' => $receipt->id,
                    'transaction_type' => 'receipt',
                    'date' => $request->deposit_date,
                    'description' => $notes,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);

                // Update collateral amount
                $collateral->increment('amount', $request->amount);

                // Send SMS to customer after successful deposit
                if ($collateral->customer && $collateral->customer->phone) {
                    $smsMessage = "Cash deposit processed successfully. Amount: TSHS" . number_format($request->amount, 2);
                    $this->sendSms($collateral->customer->phone, $smsMessage);
                }

                // Generate thermal receipt data
                $receiptData = [
                    'receipt_id' => $receipt->id,
                    'receipt_number' => $receipt->reference_number ?? 'DEP-' . str_pad($receipt->id, 6, '0', STR_PAD_LEFT),
                    'date' => $receipt->date,
                    'customer_name' => $collateral->customer->name,
                    'deposit_type' => $collateral->type->name,
                    'amount' => $request->amount,
                    'notes' => $notes,
                    'bank_account' => $bankAccount->name . ' - ' . $bankAccount->account_number,
                    'received_by' => $user->name,
                    'branch' => $user->branch->name ?? 'N/A',
                    'time' => now()->format('H:i:s'),
                ];

                return redirect()->route('cash_collaterals.index')
                    ->with('success', 'Cash deposit processed successfully. Amount: TSHS' . number_format($request->amount, 2))
                    ->with('print_receipt', true)
                    ->with('receipt_data', $receiptData);
            });
        } catch (\Throwable $th) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to process deposit: ' . $th->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show withdrawal form
     */
    public function withdraw(Request $request, $cashcollateral)
    {
        $id = Hashids::decode($cashcollateral)[0] ?? null;

        if (!$id) {
            abort(404, 'Invalid Cash Collateral ID.');
        }

        $collateral = CashCollateral::with('customer')->findOrFail($id);
        $customer = $collateral->customer;

        // Limit bank accounts by branch scope (all branches or current branch)
        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $bankAccounts = BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();

        return view('cash_collaterals.withdraw', compact('bankAccounts', 'customer', 'collateral'));
    }

    /**
     * PROCESS CASH COLLATERAL FOR WITHDRAWAL OF CUSTOMER
     */
    public function withdrawStore(Request $request)
    {
        $request->validate([
            'collateral_id' => 'required|string', // Still encoded, decode later
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'withdrawal_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'required|string|max:500',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Decode Hashid
                $collateralId = Hashids::decode($request->collateral_id)[0] ?? null;

                if (!$collateralId) {
                    throw new \Exception('Invalid collateral ID.');
                }

                $user = Auth::user();
                $collateral = CashCollateral::with(['customer', 'type'])->findOrFail($collateralId);
                $bankAccount = BankAccount::findOrFail($request->bank_account_id);
                $notes = $request->notes;

                // Check if sufficient balance
                if ($collateral->amount < $request->amount) {
                    throw new \Exception('Insufficient balance for withdrawal. Available: ' . number_format($collateral->amount, 2));
                }

                // Check if required relationships exist
                if (!$collateral->type || !$collateral->type->chart_account_id) {
                    throw new \Exception('Cash collateral type must have a chart account assigned.');
                }

                if (!$bankAccount->chart_account_id) {
                    throw new \Exception('Bank account must have a chart account assigned.');
                }

                // Handle branch assignment like the controller
                if (!$user->branch_id) {
                    // Get user's first assigned branch
                    $userBranch = $user->branches()->first();
                    if (!$userBranch) {
                        throw new \Exception('User must be assigned to a branch.');
                    }
                    $branchId = $userBranch->id;
                } else {
                    $branchId = $user->branch_id;
                }

                // Create payment
                $payment = Payment::create([
                    'reference' => $collateralId,
                    'reference_type' => 'Withdrawal',
                    'reference_number' => null,
                    'amount' => $request->amount,
                    'date' => $request->withdrawal_date,
                    'description' => $notes,
                    'user_id' => $user->id,
                    'bank_account_id' => $bankAccount->id,
                    'payee_type' => 'Customer',
                    'payee_id' => $collateral->customer_id,
                    'payee_name' => $collateral->customer->name,
                    'branch_id' => $branchId,
                    'approved' => true,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                // Create payment item
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'chart_account_id' => $collateral->type->chart_account_id,
                    'amount' => $request->amount,
                    'description' => $notes,
                ]);

                // GL Transactions (REVERSED from deposits)

                // Debit: Cash Collateral Account (reducing the liability)
                GlTransaction::create([
                    'chart_account_id' => $collateral->type->chart_account_id,
                    'customer_id' => $collateral->customer_id,
                    'amount' => $request->amount,
                    'nature' => 'debit',
                    'transaction_id' => $payment->id,
                    'transaction_type' => 'payment',
                    'date' => $request->withdrawal_date,
                    'description' => $notes,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);

                // Credit: Bank Account (money going out)
                GlTransaction::create([
                    'chart_account_id' => $bankAccount->chart_account_id,
                    'customer_id' => $collateral->customer_id,
                    'amount' => $request->amount,
                    'nature' => 'credit',
                    'transaction_id' => $payment->id,
                    'transaction_type' => 'payment',
                    'date' => $request->withdrawal_date,
                    'description' => $notes,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);

                // Update collateral amount
                $collateral->decrement('amount', $request->amount);

                // Send SMS to customer after successful withdrawal
                if ($collateral->customer && $collateral->customer->phone) {
                    $smsMessage = "Cash withdrawal processed successfully. Amount: TSHS" . number_format($request->amount, 2) . ". Remaining balance: TSHS" . number_format($collateral->fresh()->amount, 2);
                    $this->sendSms($collateral->customer->phone, $smsMessage);
                }

                // Generate thermal receipt data
                $receiptData = [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->reference_number ?? 'WTH-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                    'date' => $payment->date,
                    'customer_name' => $collateral->customer->name,
                    'withdrawal_type' => $collateral->type->name,
                    'amount' => $request->amount,
                    'remaining_balance' => $collateral->fresh()->amount,
                    'bank_account' => $bankAccount->account_name . ' - ' . $bankAccount->account_number,
                    'notes' => $notes,
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Withdrawal processed successfully.',
                    'payment_id' => $payment->id,
                    'receipt_data' => $receiptData,
                    'redirect' => route('cash_collaterals.index')
                ]);
            });

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print withdrawal receipt
     */
    public function printWithdrawalReceipt($id)
    {
        $payment = Payment::with(['bankAccount', 'paymentItems.chartAccount'])
            ->where('reference_type', 'Withdrawal')
            ->findOrFail($id);
        
        $collateral = CashCollateral::with(['customer', 'type'])
            ->findOrFail($payment->reference);
        
        $receiptData = [
            'payment' => $payment,
            'collateral' => $collateral,
            'customer' => $collateral->customer,
            'type' => $collateral->type,
            'bank_account' => $payment->bankAccount,
            'current_balance' => $collateral->amount,
        ];
        
        return view('cash_collaterals.withdrawal_receipt', compact('receiptData'));
    }

    /**
     * Delete a deposit transaction (receipt)
     */
    public function deleteDeposit($receiptId)
    {
        try {
            return DB::transaction(function () use ($receiptId) {
                $receipt = Receipt::findOrFail($receiptId);
                
                // Verify this is a deposit transaction
                if ($receipt->reference_type !== 'Deposit') {
                    throw new \Exception('This is not a deposit transaction.');
                }
                
                // Get the associated cash collateral
                $cashCollateral = CashCollateral::findOrFail($receipt->reference);
                
                // Delete associated GL transactions
                GlTransaction::where('transaction_type', 'receipt')
                    ->where('transaction_id', $receipt->id)
                    ->delete();
                
                // Delete receipt items
                $receipt->receiptItems()->delete();
                
                // Delete the receipt
                $receipt->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Deposit transaction deleted successfully.',
                    'amount' => $receipt->amount
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete deposit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a withdrawal transaction (payment)
     */
    public function deleteWithdrawal($paymentId)
    {
        try {
            return DB::transaction(function () use ($paymentId) {
                $payment = Payment::findOrFail($paymentId);
                
                // Verify this is a withdrawal transaction
                if ($payment->reference_type !== 'Withdrawal') {
                    throw new \Exception('This is not a withdrawal transaction.');
                }
                
                // Get the associated cash collateral
                $cashCollateral = CashCollateral::findOrFail($payment->reference);
                
                // Delete associated GL transactions
                GlTransaction::where('transaction_type', 'payment')
                    ->where('transaction_id', $payment->id)
                    ->delete();
                
                // Delete payment items
                $payment->paymentItems()->delete();
                
                // Delete the payment
                $payment->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Withdrawal transaction deleted successfully.',
                    'amount' => $payment->amount
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete withdrawal: ' . $e->getMessage()
            ], 500);
        }
    }
}