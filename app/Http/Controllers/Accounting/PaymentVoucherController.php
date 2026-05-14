<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\GlTransaction;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\SystemSetting;
use App\Services\FxTransactionRateService;
use App\Traits\GetsCurrenciesFromFxRates;
use App\Traits\TransactionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Helpers\HashIdHelper;
use Yajra\DataTables\Facades\DataTables;

class PaymentVoucherController extends Controller
{
    use TransactionHelper, GetsCurrenciesFromFxRates;

    /**
     * Check budget limits for payment voucher line items
     * 
     * @param array $lineItems Array of line items with chart_account_id and amount
     * @param int|null $branchId Branch ID for filtering budgets
     * @param int $companyId Company ID
     * @param string $date Payment date to determine year
     * @param int|null $excludePaymentId Payment ID to exclude from used amount calculation (for updates)
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    protected function checkBudgetLimits($lineItems, $branchId, $companyId, $date, $excludePaymentId = null)
    {
        // Check if budget checking is enabled
        $budgetCheckEnabled = SystemSetting::getValue('budget_check_enabled', false);
        if (!$budgetCheckEnabled) {
            return ['valid' => true, 'errors' => [], 'warnings' => []];
        }

        $overBudgetPercentage = SystemSetting::getValue('budget_over_budget_percentage', 10);
        $currentYear = date('Y', strtotime($date));
        $errors = [];
        $warnings = [];

        // Get active budget for the year and branch
        $budget = Budget::where('company_id', $companyId)
            ->where('year', $currentYear)
            ->where(function($query) use ($branchId) {
                if ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id'); // Include company-wide budgets
                } else {
                    $query->whereNull('branch_id'); // Only company-wide budgets
                }
            })
            ->orderByDesc('created_at')
            ->first();

        if (!$budget) {
            // No budget found - allow but warn
            $warnings[] = "No budget found for year {$currentYear}. Budget checking is disabled for this transaction.";
            return ['valid' => true, 'errors' => [], 'warnings' => $warnings];
        }

        // Check each line item
        foreach ($lineItems as $index => $item) {
            $chartAccountId = $item['chart_account_id'];
            $requestedAmount = (float) $item['amount'];

            // Get budget line for this account
            $budgetLine = BudgetLine::where('budget_id', $budget->id)
                ->where('account_id', $chartAccountId)
                ->first();

            if (!$budgetLine) {
                $chartAccount = ChartAccount::find($chartAccountId);
                $requireAllocation = SystemSetting::getValue('budget_require_allocation', false);
                
                if ($requireAllocation) {
                    // Strict mode: Block accounts not in budget
                    $errors[] = "Account '{$chartAccount->account_name}' ({$chartAccount->account_code}) is not included in the budget. Please add this account to your budget before creating payment vouchers for it.";
                } else {
                    // Lenient mode: Allow with warning
                    $warnings[] = "No budget allocation found for account: {$chartAccount->account_name} ({$chartAccount->account_code}). This expense will not be checked against budget.";
                }
                continue;
            }

            // Calculate used amount from GL transactions (debit transactions for expenses)
            // Exclude GL transactions from the payment being updated
            $usedAmountQuery = GlTransaction::where('chart_account_id', $chartAccountId)
                ->where(function($query) use ($branchId) {
                    if ($branchId) {
                        $query->where('branch_id', $branchId);
                    } else {
                        $query->whereNull('branch_id');
                    }
                })
                ->whereYear('date', $currentYear)
                ->where('nature', 'debit');

            // Exclude GL transactions from the payment being updated
            if ($excludePaymentId) {
                $usedAmountQuery->where(function($query) use ($excludePaymentId) {
                    $query->where('transaction_type', '!=', 'payment')
                          ->orWhere(function($q) use ($excludePaymentId) {
                              $q->where('transaction_type', 'payment')
                                ->where('transaction_id', '!=', $excludePaymentId);
                          });
                });
            }

            $usedAmount = $usedAmountQuery->sum('amount');

            $usedAmount = (float) $usedAmount;

            // Calculate remaining budget
            $remainingBudget = $budgetLine->amount - $usedAmount;

            // Calculate allowed amount (budget + over-budget percentage)
            $allowedAmount = $budgetLine->amount * (1 + ($overBudgetPercentage / 100));
            $allowedOverBudget = $allowedAmount - $budgetLine->amount;

            // Check if requested amount exceeds allowed amount
            $totalAfterTransaction = $usedAmount + $requestedAmount;
            
            if ($totalAfterTransaction > $allowedAmount) {
                $chartAccount = ChartAccount::find($chartAccountId);
                $exceededBy = $totalAfterTransaction - $allowedAmount;
                $errors[] = "Account '{$chartAccount->account_name}' ({$chartAccount->account_code}): Requested amount (TZS " . number_format($requestedAmount, 2) . ") exceeds budget limit. Budget: TZS " . number_format($budgetLine->amount, 2) . ", Used: TZS " . number_format($usedAmount, 2) . ", Allowed (with {$overBudgetPercentage}% tolerance): TZS " . number_format($allowedAmount, 2) . ". Exceeded by: TZS " . number_format($exceededBy, 2);
            } elseif ($totalAfterTransaction > $budgetLine->amount) {
                // Over budget but within tolerance - warn
                $chartAccount = ChartAccount::find($chartAccountId);
                $overBy = $totalAfterTransaction - $budgetLine->amount;
                $overPercentage = ($overBy / $budgetLine->amount) * 100;
                $warnings[] = "Account '{$chartAccount->account_name}' ({$chartAccount->account_code}): This expense exceeds budget by TZS " . number_format($overBy, 2) . " ({$overPercentage}%). Budget: TZS " . number_format($budgetLine->amount, 2) . ", Used: TZS " . number_format($usedAmount, 2) . ", Remaining: TZS " . number_format($remainingBudget, 2);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        // Calculate stats only, scoped to current branch (or all if no branch)
        $allPayments = Payment::with(['bankAccount.chartAccount.accountClassGroup'])
            ->whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->get();

        $stats = [
            'total' => $allPayments->count(),
            'this_month' => $allPayments->where('date', '>=', now()->startOfMonth())->count(),
            'total_amount' => $allPayments->sum('amount'),
            'this_month_amount' => $allPayments->where('date', '>=', now()->startOfMonth())->sum('amount'),
        ];

        return view('accounting.payment-vouchers.index', compact('stats'));
    }

    /**
     * DataTables AJAX endpoint for payment vouchers
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $payments = Payment::with(['bankAccount', 'customer', 'supplier', 'user', 'branch'])
            ->where(function ($query) use ($user) {
                // Include payments with bank accounts (existing filter)
                $query->whereHas('bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                })
                // OR include petty cash payments (filtered by branch company)
                ->orWhere(function ($q) use ($user) {
                    $q->where('reference_type', 'petty_cash')
                      ->whereHas('branch', function ($branchQuery) use ($user) {
                          $branchQuery->where('company_id', $user->company_id);
                      });
                });
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('payments.*');

        return DataTables::eloquent($payments)
            ->addColumn('formatted_date', function ($payment) {
                return $payment->date ? $payment->date->format('M d, Y') : 'N/A';
            })
            ->addColumn('reference_link', function ($payment) {
                return '<a href="' . route('accounting.payment-vouchers.show', $payment->hash_id) . '" class="text-primary fw-bold">' . 
                       ($payment->reference ?: 'N/A') . '</a>';
            })
            ->addColumn('reference_type_badge', function ($payment) {
                $badgeClass = match($payment->reference_type) {
                    'manual' => 'bg-primary',
                    'auto' => 'bg-success',
                    'petty_cash' => 'bg-info',
                    default => 'bg-secondary'
                };
                $label = match($payment->reference_type) {
                    'petty_cash' => 'Petty Cash',
                    default => ucfirst(str_replace('_', ' ', $payment->reference_type))
                };
                return '<span class="badge ' . $badgeClass . '">' . $label . '</span>';
            })
            ->addColumn('bank_account_name', function ($payment) {
                if ($payment->reference_type === 'petty_cash') {
                    return '<span class="badge bg-info">Petty Cash</span>';
                }
                return $payment->bankAccount ? $payment->bankAccount->name : 'N/A';
            })
            ->addColumn('payee_info', function ($payment) {
                if ($payment->payee_type === 'customer' && $payment->customer) {
                    return $payment->customer->name;
                } elseif ($payment->payee_type === 'supplier' && $payment->supplier) {
                    return $payment->supplier->name;
                } elseif ($payment->payee_type === 'employee' && $payment->employee) {
                    return $payment->employee->full_name;
                } elseif ($payment->payee_type === 'other') {
                    return $payment->payee_name ?: 'N/A';
                }
                return 'N/A';
            })
            ->addColumn('description_limited', function ($payment) {
                return $payment->description ? \Str::limit($payment->description, 50) : 'N/A';
            })
            ->addColumn('formatted_amount', function ($payment) {
                return 'TZS ' . number_format($payment->amount, 2);
            })
            ->addColumn('status_badge', function ($payment) {
                if ($payment->approved) {
                    return '<span class="badge bg-success">Approved</span>';
                } else {
                    return '<span class="badge bg-warning">Pending</span>';
                }
            })
            ->addColumn('actions', function ($payment) {
                $actions = '<div class="d-flex gap-2">';
                $actions .= '<a href="' . route('accounting.payment-vouchers.show', $payment->hash_id) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                $actions .= '<a href="' . route('accounting.payment-vouchers.edit', $payment->hash_id) . '" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bx bx-edit"></i></a>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['reference_link', 'reference_type_badge', 'bank_account_name', 'status_badge', 'actions'])
            ->make(true);
    }

    // Ajax endpoint for DataTables
    public function getPaymentVouchersData(Request $request)
    {
        $user = Auth::user();

        $payments = Payment::with(['bankAccount', 'customer', 'supplier', 'user', 'approvals', 'branch', 'paymentItems'])
            ->where(function ($query) use ($user) {
                // Include payments with bank accounts (existing filter)
                $query->whereHas('bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                })
                // OR include petty cash payments (filtered by branch company)
                ->orWhere(function ($q) use ($user) {
                    $q->where('reference_type', 'petty_cash')
                      ->whereHas('branch', function ($branchQuery) use ($user) {
                          $branchQuery->where('company_id', $user->company_id);
                      });
                });
            })
            ->select('payments.*');

        // Optional filter by payee_type (e.g., 'hotel' for Hotel Expenses)
        if ($request->filled('payee_type')) {
            $payments->where('payee_type', $request->get('payee_type'));
        }

        return DataTables::eloquent($payments)
                ->addColumn('formatted_date', function ($payment) {
                    return $payment->date ? $payment->date->format('M d, Y') : 'N/A';
                })
                ->addColumn('reference_link', function ($payment) {
                    // Use hotel expense routes for hotel expenses
                    if ($payment->payee_type === 'hotel' && $payment->reference_type === 'hotel_expense') {
                        return '<a href="' . route('hotel.expenses.show', $payment->hash_id) . '" 
                                    class="text-primary fw-bold">
                                    ' . e($payment->reference_number ?? $payment->reference) . '
                                </a>';
                    }
                    return '<a href="' . route('accounting.payment-vouchers.show', $payment->hash_id) . '" 
                                class="text-primary fw-bold">
                                ' . e($payment->reference) . '
                            </a>';
                })
                ->addColumn('bank_account_name', function ($payment) {
                    if ($payment->reference_type === 'petty_cash') {
                        return '<span class="badge bg-info">Petty Cash</span>';
                    }
                    return optional($payment->bankAccount)->name ?? 'N/A';
                })
                ->addColumn('payee_info', function ($payment) {
                    if ($payment->payee_type == 'hotel' && $payment->reference_type == 'hotel_expense') {
                        // Extract property/room info from payment items description
                        $scope = 'General';
                        if ($payment->paymentItems && $payment->paymentItems->count() > 0) {
                            $firstItem = $payment->paymentItems->first();
                            if ($firstItem && $firstItem->description) {
                                if (preg_match('/\(Property:\s*([^)]+)\)/', $firstItem->description, $matches)) {
                                    $scope = 'Property: ' . $matches[1];
                                } elseif (preg_match('/\(Room:\s*([^)]+)\)/', $firstItem->description, $matches)) {
                                    $scope = 'Room: ' . $matches[1];
                                }
                            }
                        }
                        return '<span class="badge bg-warning me-1">Hotel</span>' . e($scope);
                    } elseif ($payment->payee_type == 'customer' && $payment->customer) {
                        return '<span class="badge bg-primary me-1">Customer</span>' . e($payment->customer->name ?? 'N/A');
                    } elseif ($payment->payee_type == 'supplier' && $payment->supplier) {
                        return '<span class="badge bg-success me-1">Supplier</span>' . e($payment->supplier->name ?? 'N/A');
                    } elseif ($payment->payee_type == 'employee' && $payment->employee) {
                        return '<span class="badge bg-info me-1">Employee</span>' . e($payment->payee_name ?? 'N/A');
                    } elseif ($payment->payee_type == 'other') {
                        return '<span class="badge bg-warning me-1">Other</span>' . e($payment->payee_name ?? 'N/A');
                    } else {
                        return '<span class="text-muted">No payee</span>';
                    }
                })
                ->addColumn('description_limited', function ($payment) {
                    return $payment->description ? Str::limit($payment->description, 50) : 'No description';
                })
                ->addColumn('reference_type_badge', function ($payment) {
                    if ($payment->reference_type === 'manual') {
                        return '<span class="badge bg-primary">Manual Payment Voucher</span>';
                    } elseif ($payment->reference_type === 'petty_cash') {
                        return '<span class="badge bg-info">Petty Cash</span>';
                    } else {
                        return '<span class="badge bg-secondary">' . ucfirst(str_replace('_', ' ', $payment->reference_type)) . '</span>';
                    }
                })
                ->addColumn('formatted_amount', function ($payment) {
                    return '<span class="text-end fw-bold">' . number_format($payment->amount, 2) . '</span>';
                })
                ->addColumn('status_badge', function ($payment) {
                    return $payment->approval_status_badge;
                })
                ->addColumn('actions', function ($payment) {
                    $actions = '';
                    
                    // Check if this is a hotel expense
                    $isHotelExpense = $payment->payee_type === 'hotel' && $payment->reference_type === 'hotel_expense';
                    
                    // View action
                    if ($isHotelExpense) {
                        $actions .= '<a href="' . route('hotel.expenses.show', $payment->hash_id) . '" 
                                        class="btn btn-sm btn-outline-success me-1" 
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        title="View expense">
                                        <i class="bx bx-show"></i>
                                    </a>';
                        
                        // Edit/Delete for hotel expenses (if not approved)
                        if (!$payment->approved) {
                            $actions .= '<a href="' . route('hotel.expenses.edit', $payment->hash_id) . '" 
                                            class="btn btn-sm btn-outline-info me-1" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Edit expense">
                                            <i class="bx bx-edit"></i>
                                        </a>';
                            
                            $actions .= '<button type="button" 
                                            class="btn btn-sm btn-outline-danger delete-hotel-expense-btn"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Delete expense"
                                            data-expense-id="' . $payment->hash_id . '"
                                            data-expense-reference="' . e($payment->reference_number ?? $payment->reference) . '">
                                            <i class="bx bx-trash"></i>
                                        </button>';
                        }
                    } else {
                        // Regular payment voucher actions
                        if (auth()->user()->can('view payment voucher details')) {
                            $actions .= '<a href="' . route('accounting.payment-vouchers.show', $payment->hash_id) . '" 
                                            class="btn btn-sm btn-outline-success me-1" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="View payment voucher">
                                            <i class="bx bx-show"></i>
                                        </a>';
                        }
                        
                        if ($payment->reference_type === 'manual') {
                            // Always allow edit/delete for manual vouchers if user has permission
                            if (auth()->user()->can('edit payment voucher')) {
                                $actions .= '<a href="' . route('accounting.payment-vouchers.edit', $payment->hash_id) . '" 
                                                class="btn btn-sm btn-outline-info me-1" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Edit payment voucher">
                                                <i class="bx bx-edit"></i>
                                            </a>';
                            }
                            if (auth()->user()->can('delete payment voucher')) {
                                $actions .= '<button type="button" 
                                                class="btn btn-sm btn-outline-danger delete-payment-btn"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Delete payment voucher"
                                                data-payment-id="' . $payment->hash_id . '"
                                                data-payment-reference="' . e($payment->reference) . '">
                                                <i class="bx bx-trash"></i>
                                            </button>';
                            }
                        } else {
                            $actions .= '<button type="button" 
                                            class="btn btn-sm btn-outline-secondary" 
                                            title="Edit/Delete locked: Source is ' . ucfirst($payment->reference_type) . ' transaction" 
                                            disabled>
                                            <i class="bx bx-lock"></i>
                                        </button>';
                        }
                    }
                    
                    return '<div class="text-center">' . $actions . '</div>';
                })
                ->rawColumns(['reference_link', 'payee_info', 'description_limited', 'reference_type_badge', 'bank_account_name', 'formatted_amount', 'status_badge', 'actions'])
                ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        // Get bank accounts for the current company, respecting branch scope
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true);
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->orderBy('name')
            ->get();

        // Get customers for the current company/branch
        $customers = Customer::where('company_id', $user->company_id)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get();

        // Get suppliers for the current company
        $suppliers = Supplier::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        $employees = collect();

        // Get chart accounts for the current company - all account types
        $chartAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('account_name')
            ->get();

        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        return view('accounting.payment-vouchers.create', compact('bankAccounts', 'customers', 'suppliers', 'employees', 'chartAccounts', 'currencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:bank_transfer,cash_deposit,cheque',
            'bank_account_id' => 'required_if:payment_method,bank_transfer|required_if:payment_method,cheque|nullable|exists:bank_accounts,id',
            'cash_deposit_id' => 'required_if:payment_method,cash_deposit|nullable|in:customer_balance',
            // Cheque fields
            'cheque_number' => 'required_if:payment_method,cheque|nullable|string|max:50',
            'cheque_date' => 'required_if:payment_method,cheque|nullable|date',
            'payee_type' => 'required|in:customer,supplier,employee,other',
            'customer_id' => 'required_if:payee_type,customer|exists:customers,id',
            'supplier_id' => 'required_if:payee_type,supplier|exists:suppliers,id',
            'employee_id' => 'nullable',
            'payee_name' => 'required_if:payee_type,other|nullable|string|max:255',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf|max:2048',
            'wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,GROSS_UP,NONE',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'line_items' => 'required|array|min:1',
            'line_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.description' => 'nullable|string',
            'line_items.*.wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,GROSS_UP,NONE',
            'line_items.*.wht_rate' => 'nullable|numeric|min:0|max:100',
            'line_items.*.vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'line_items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            return $this->runTransaction(function () use ($request) {
                $user = Auth::user();
                $branchId = session('branch_id') ?? ($user->branch_id ?? null);
                
                // Check budget limits before proceeding
                $budgetCheck = $this->checkBudgetLimits(
                    $request->line_items,
                    $branchId,
                    $user->company_id,
                    $request->date
                );

                // If budget check fails, return with errors
                if (!$budgetCheck['valid']) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Budget check failed',
                            'errors' => ['budget' => $budgetCheck['errors']],
                            'warnings' => $budgetCheck['warnings']
                        ], 422);
                    }
                    return redirect()->back()
                        ->withErrors(['budget' => $budgetCheck['errors']])
                        ->withInput()
                        ->with('budget_warnings', $budgetCheck['warnings']);
                }

                // Store warnings in session to display after successful creation
                if (!empty($budgetCheck['warnings'])) {
                    session()->flash('budget_warnings', $budgetCheck['warnings']);
                }
                
                // Check if WHT is enabled (from form switch)
                $whtEnabled = $request->has('wht_enabled') && $request->wht_enabled == '1';
                
                $whtService = new \App\Services\WithholdingTaxService();
                
                // Calculate total amount (sum of line items - may include VAT)
                $totalAmount = collect($request->line_items)->sum('amount');
                
                // Get WHT treatment and rate (payment-level or item-level)
                // If WHT is disabled, set defaults to NONE/0
                if (!$whtEnabled) {
                    $whtTreatment = 'NONE';
                    $whtRate = 0;
                    $vatMode = 'NONE';
                    $vatRate = 0;
                } else {
                    $whtTreatment = $request->wht_treatment ?? 'EXCLUSIVE';
                    $whtRate = (float) ($request->wht_rate ?? 0);
                    
                    // Get VAT mode and rate (payment-level)
                    // Use system default VAT type if not provided
                    $defaultVatType = get_default_vat_type();
                    $defaultVatMode = 'EXCLUSIVE'; // Default fallback
                    if ($defaultVatType == 'inclusive') {
                        $defaultVatMode = 'INCLUSIVE';
                    } elseif ($defaultVatType == 'exclusive') {
                        $defaultVatMode = 'EXCLUSIVE';
                    } elseif ($defaultVatType == 'no_vat') {
                        $defaultVatMode = 'NONE';
                    }
                    $vatMode = $request->vat_mode ?? $defaultVatMode;
                    $vatRate = (float) ($request->vat_rate ?? get_default_vat_rate()); // Use system default VAT rate
                    
                    // If supplier is selected and has allow_gross_up, default to GROSS_UP
                    if ($request->payee_type === 'supplier' && $request->supplier_id) {
                        $supplier = \App\Models\Supplier::find($request->supplier_id);
                        if ($supplier && $supplier->allow_gross_up && $whtTreatment === 'EXCLUSIVE') {
                            $whtTreatment = 'GROSS_UP';
                        }
                    }
                }
                
                // Calculate WHT at payment level if rate is provided (with VAT integration)
                $paymentWHT = 0;
                $paymentNetPayable = $totalAmount;
                $paymentTotalCost = $totalAmount;
                $paymentBaseAmount = $totalAmount;
                $paymentVatAmount = 0;
                
                if ($whtEnabled && $whtRate > 0 && $whtTreatment !== 'NONE') {
                    $whtCalc = $whtService->calculateWHT($totalAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                    $paymentWHT = $whtCalc['wht_amount'];
                    $paymentNetPayable = $whtCalc['net_payable'];
                    $paymentTotalCost = $whtCalc['total_cost'];
                    $paymentBaseAmount = $whtCalc['base_amount'];
                    $paymentVatAmount = $whtCalc['vat_amount'];
                } elseif ($vatMode !== 'NONE' && $vatRate > 0) {
                    // Calculate VAT even if no WHT
                    if ($vatMode === 'INCLUSIVE') {
                        $paymentBaseAmount = round($totalAmount / (1 + ($vatRate / 100)), 2);
                        $paymentVatAmount = round($totalAmount - $paymentBaseAmount, 2);
                    } else {
                        // EXCLUSIVE - total amount IS the base amount, VAT is added on top
                        $paymentBaseAmount = $totalAmount;
                        $paymentVatAmount = round($totalAmount * ($vatRate / 100), 2);
                    }
                }

                // Handle file upload
                $attachmentPath = null;
                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $attachmentPath = $file->storeAs('payment-attachments', $fileName, 'public');
                }

                // Handle payee information
                $payeeType = $request->payee_type;
                $payeeId = null;
                $payeeName = null;

                if ($request->payee_type === 'customer') {
                    $payeeId = $request->customer_id;
                } elseif ($request->payee_type === 'supplier') {
                    $payeeId = $request->supplier_id;
                } elseif ($request->payee_type === 'employee') {
                    $payeeId = $request->employee_id;
                } elseif ($request->payee_type === 'other') {
                    $payeeName = $request->payee_name;
                }

                // Get functional currency
                $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $user->company->functional_currency ?? 'TZS');
                $currency = $request->currency ?? $functionalCurrency;
                
                // Get exchange rate using FxTransactionRateService
                $fxTransactionRateService = app(FxTransactionRateService::class);
                $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
                $rateResult = $fxTransactionRateService->getTransactionRate(
                    $currency,
                    $functionalCurrency,
                    $request->date,
                    $user->company_id,
                    $userProvidedRate
                );
                $exchangeRate = $rateResult['rate'];
                $fxRateUsed = $exchangeRate; // Store the rate used for fx_rate_used field
                
                // Calculate FCY and LCY amounts for revaluation
                $needsConversion = ($currency !== $functionalCurrency);
                $amountFcy = $needsConversion ? $totalAmount : null; // FCY amount (if foreign currency)
                $amountLcy = $needsConversion ? ($totalAmount * $exchangeRate) : $totalAmount; // LCY amount
                
                // Set default payment method if not provided
                $paymentMethod = $request->payment_method ?? 'bank_transfer';
                
                // Handle cheque creation if cheque payment method is selected
                $chequeId = null;
                if ($paymentMethod === 'cheque') {
                    $chequeService = new \App\Services\ChequeService();
                    
                    // Get payee name
                    $chequePayeeName = $payeeName;
                    if ($payeeType === 'customer' && $request->customer_id) {
                        $customer = \App\Models\Customer::find($request->customer_id);
                        $chequePayeeName = $customer->name ?? $payeeName;
                    } elseif ($payeeType === 'supplier' && $request->supplier_id) {
                        $supplier = \App\Models\Supplier::find($request->supplier_id);
                        $chequePayeeName = $supplier->name ?? $payeeName;
                    } elseif ($payeeType === 'employee') {
                        $chequePayeeName = $payeeName ?? 'N/A';
                    }
                    
                    // Get expense account from first line item (for journal entry)
                    // Find the first line item with a valid chart_account_id
                    $expenseAccountId = null;
                    if (!empty($request->line_items)) {
                        foreach ($request->line_items as $lineItem) {
                            if (!empty($lineItem['chart_account_id'])) {
                                $expenseAccountId = $lineItem['chart_account_id'];
                                break;
                            }
                        }
                    }
                    
                    // Validate expense account exists
                    if (!$expenseAccountId) {
                        throw new \Exception('Expense or Payable account is required for cheque issuance. Please ensure line items have chart accounts selected.');
                    }
                    
                    // Create cheque
                    $cheque = $chequeService->issueCheque([
                        'cheque_number' => $request->cheque_number,
                        'cheque_date' => $request->cheque_date ?? $request->date,
                        'bank_account_id' => $request->bank_account_id,
                        'payee_name' => $chequePayeeName,
                        'amount' => $totalAmount,
                        'payment_reference_type' => 'payment',
                        'payment_reference_id' => null, // Will be set after payment is created
                        'payment_reference_number' => $request->reference ?: 'PV-' . strtoupper(uniqid()),
                        'module_origin' => 'payment_voucher',
                        'payment_type' => $payeeType,
                        'description' => $request->description,
                        'company_id' => $user->company_id,
                        'branch_id' => $branchId,
                        'issued_by' => $user->id,
                        'expense_account_id' => $expenseAccountId,
                    ]);
                    
                    $chequeId = $cheque->id;
                }
                
                // Create payment
                $payment = Payment::create([
                    'reference' => $request->reference ?: 'PV-' . strtoupper(uniqid()),
                    'reference_type' => 'manual',
                    'reference_number' => $request->reference,
                    'amount' => $totalAmount, // Total amount (may include VAT)
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'amount_fcy' => $amountFcy, // Foreign currency amount for revaluation
                    'amount_lcy' => $amountLcy, // Local currency amount for revaluation
                    'fx_rate_used' => $fxRateUsed,
                    'wht_treatment' => $whtTreatment,
                    'wht_rate' => $whtRate,
                    'wht_amount' => $paymentWHT,
                    'net_payable' => $paymentNetPayable,
                    'total_cost' => $paymentTotalCost,
                    'vat_mode' => $vatMode,
                    'vat_amount' => $paymentVatAmount,
                    'base_amount' => $paymentBaseAmount,
                    'date' => $request->date,
                    'description' => $request->description,
                    'attachment' => $attachmentPath,
                    'user_id' => $user->id,
                    'payment_method' => $paymentMethod,
                    'bank_account_id' => ($paymentMethod === 'cheque' || $paymentMethod === 'bank_transfer') ? $request->bank_account_id : null,
                    'cash_deposit_id' => ($paymentMethod === 'cash_deposit' && $request->cash_deposit_id !== 'customer_balance') ? $request->cash_deposit_id : null,
                    'cheque_id' => $chequeId,
                    'payee_type' => $payeeType,
                    'payee_id' => $payeeId,
                    'payee_name' => $payeeName,
                    'customer_id' => $request->customer_id,
                    'supplier_id' => $request->supplier_id,
                    'employee_id' => $request->employee_id,
                    'branch_id' => $branchId,
                    'approved' => false, // Will be set by approval workflow
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
                
                // Update cheque with payment reference if cheque was created
                if ($chequeId) {
                    \App\Models\Cheque::where('id', $chequeId)->update([
                        'payment_reference_id' => $payment->id,
                    ]);
                }

                // Initialize approval workflow (may auto-approve depending on settings)
                $payment->initializeApprovalWorkflow();

                // Create payment items with WHT and VAT calculation
                $paymentItems = [];
                foreach ($request->line_items as $lineItem) {
                    $itemTotalAmount = (float) $lineItem['amount'];
                    // If WHT is disabled, ignore item-level WHT settings
                    if (!$whtEnabled) {
                        $itemWHTTreatment = 'NONE';
                        $itemWHTRate = 0;
                        $itemVatMode = 'NONE';
                        $itemVatRate = 0;
                    } else {
                        $itemWHTTreatment = $lineItem['wht_treatment'] ?? $whtTreatment;
                        $itemWHTRate = (float) ($lineItem['wht_rate'] ?? $whtRate);
                        $itemVatMode = $lineItem['vat_mode'] ?? $vatMode;
                        $itemVatRate = (float) ($lineItem['vat_rate'] ?? $vatRate);
                    }
                    
                    $itemWHT = 0;
                    $itemNetPayable = $itemTotalAmount;
                    $itemTotalCost = $itemTotalAmount;
                    $itemBaseAmount = $itemTotalAmount;
                    $itemVatAmount = 0;
                    
                    if ($whtEnabled && $itemWHTRate > 0 && $itemWHTTreatment !== 'NONE') {
                        $itemWHTCalc = $whtService->calculateWHT($itemTotalAmount, $itemWHTRate, $itemWHTTreatment, $itemVatMode, $itemVatRate);
                        $itemWHT = $itemWHTCalc['wht_amount'];
                        $itemNetPayable = $itemWHTCalc['net_payable'];
                        $itemTotalCost = $itemWHTCalc['total_cost'];
                        $itemBaseAmount = $itemWHTCalc['base_amount'];
                        $itemVatAmount = $itemWHTCalc['vat_amount'];
                    } elseif ($vatMode === 'INCLUSIVE' && $paymentBaseAmount > 0 && $totalAmount > 0) {
                        // When VAT is INCLUSIVE at payment level, allocate base and VAT proportionally
                        // This prevents double VAT extraction when item amount already includes VAT
                        $itemBaseAmount = round(($itemTotalAmount / $totalAmount) * $paymentBaseAmount, 2);
                        $itemVatAmount = round(($itemTotalAmount / $totalAmount) * $paymentVatAmount, 2);
                    } elseif ($vatMode === 'EXCLUSIVE' && $paymentBaseAmount > 0 && $totalAmount > 0) {
                        // When VAT is EXCLUSIVE at payment level, allocate base and VAT proportionally
                        // Item amount is part of the base, allocate proportionally
                        $itemBaseAmount = round(($itemTotalAmount / $totalAmount) * $paymentBaseAmount, 2);
                        $itemVatAmount = round(($itemTotalAmount / $totalAmount) * $paymentVatAmount, 2);
                    } elseif ($itemVatMode !== 'NONE' && $itemVatRate > 0) {
                        // Calculate VAT even if no WHT (for item-level VAT)
                        if ($itemVatMode === 'INCLUSIVE') {
                            // VAT is included in item amount - extract base
                            $itemBaseAmount = round($itemTotalAmount / (1 + ($itemVatRate / 100)), 2);
                            $itemVatAmount = round($itemTotalAmount - $itemBaseAmount, 2);
                        } else {
                            // EXCLUSIVE - item amount IS the base amount, VAT is added on top
                            $itemBaseAmount = $itemTotalAmount;
                            $itemVatAmount = round($itemTotalAmount * ($itemVatRate / 100), 2);
                        }
                    }
                    
                    $paymentItems[] = [
                        'payment_id' => $payment->id,
                        'chart_account_id' => $lineItem['chart_account_id'],
                        'amount' => $itemTotalAmount,
                        'wht_treatment' => $itemWHTTreatment,
                        'wht_rate' => $itemWHTRate,
                        'wht_amount' => $itemWHT,
                        'base_amount' => $itemBaseAmount,
                        'net_payable' => $itemNetPayable,
                        'total_cost' => $itemTotalCost,
                        'vat_mode' => $itemVatMode,
                        'vat_amount' => $itemVatAmount,
                        'description' => $lineItem['description'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                PaymentItem::insert($paymentItems);

                // Handle invoice payments - create linked payments for each invoice
                $invoicePayments = [];
                foreach ($request->line_items as $index => $lineItem) {
                    if (isset($lineItem['invoice_id']) && isset($lineItem['invoice_number'])) {
                        $invoice = \App\Models\Purchase\PurchaseInvoice::find($lineItem['invoice_id']);
                        if ($invoice && $invoice->invoice_number === $lineItem['invoice_number']) {
                            // Create a payment record linked to this invoice
                            $invoicePaymentAmount = (float) $lineItem['amount'];
                            
                            // Calculate WHT for this invoice payment
                            $invoiceWHT = 0;
                            $invoiceNetPayable = $invoicePaymentAmount;
                            $invoiceTotalCost = $invoicePaymentAmount;
                            $invoiceBaseAmount = $invoicePaymentAmount;
                            $invoiceVatAmount = 0;
                            
                            if ($whtEnabled && $whtRate > 0 && $whtTreatment !== 'NONE') {
                                $invoiceWHTCalc = $whtService->calculateWHT($invoicePaymentAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                                $invoiceWHT = $invoiceWHTCalc['wht_amount'];
                                $invoiceNetPayable = $invoiceWHTCalc['net_payable'];
                                $invoiceTotalCost = $invoiceWHTCalc['total_cost'];
                                $invoiceBaseAmount = $invoiceWHTCalc['base_amount'];
                                $invoiceVatAmount = $invoiceWHTCalc['vat_amount'];
                            } elseif ($vatMode === 'INCLUSIVE' && $paymentBaseAmount > 0 && $totalAmount > 0) {
                                $invoiceBaseAmount = round(($invoicePaymentAmount / $totalAmount) * $paymentBaseAmount, 2);
                                $invoiceVatAmount = round(($invoicePaymentAmount / $totalAmount) * $paymentVatAmount, 2);
                            } elseif ($vatMode === 'EXCLUSIVE' && $paymentBaseAmount > 0 && $totalAmount > 0) {
                                $invoiceBaseAmount = round(($invoicePaymentAmount / $totalAmount) * $paymentBaseAmount, 2);
                                $invoiceVatAmount = round(($invoicePaymentAmount / $totalAmount) * $paymentVatAmount, 2);
                            }
                            
                            // Calculate amounts in LCY
                            $invoiceAmountFcy = $invoicePaymentAmount;
                            $invoiceAmountLcy = $needsConversion ? round($invoicePaymentAmount * $exchangeRate, 2) : $invoicePaymentAmount;
                            
                            $invoicePayments[] = [
                                'reference' => $payment->reference . '-INV-' . $invoice->invoice_number,
                                'reference_type' => 'purchase_invoice',
                                'reference_number' => $invoice->invoice_number,
                                'amount' => $invoicePaymentAmount,
                                'currency' => $currency,
                                'exchange_rate' => $exchangeRate,
                                'amount_fcy' => $invoiceAmountFcy,
                                'amount_lcy' => $invoiceAmountLcy,
                                'fx_rate_used' => $fxRateUsed,
                                'wht_treatment' => $whtTreatment,
                                'wht_rate' => $whtRate,
                                'wht_amount' => $invoiceWHT,
                                'net_payable' => $invoiceNetPayable,
                                'total_cost' => $invoiceTotalCost,
                                'vat_mode' => $vatMode,
                                'vat_amount' => $invoiceVatAmount,
                                'base_amount' => $invoiceBaseAmount,
                                'date' => $request->date,
                                'description' => ($lineItem['description'] ?? null) ?: "Payment for Invoice {$invoice->invoice_number}",
                                'attachment' => $attachmentPath,
                                'user_id' => $user->id,
                                'payment_method' => $paymentMethod,
                                'bank_account_id' => ($paymentMethod === 'cheque' || $paymentMethod === 'bank_transfer') ? $request->bank_account_id : null,
                                'cash_deposit_id' => ($paymentMethod === 'cash_deposit' && $request->cash_deposit_id !== 'customer_balance') ? $request->cash_deposit_id : null,
                                'cheque_id' => $chequeId,
                                'payee_type' => 'supplier',
                                'payee_id' => $request->supplier_id,
                                'payee_name' => null,
                                'customer_id' => null,
                                'supplier_id' => $request->supplier_id,
                                'employee_id' => null,
                                'branch_id' => $branchId,
                                'approved' => $payment->approved, // Same approval status as main payment
                                'approved_by' => $payment->approved_by,
                                'approved_at' => $payment->approved_at,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
                
                // Insert invoice payments if any
                if (!empty($invoicePayments)) {
                    Payment::insert($invoicePayments);
                    
                    // Create payment items for invoice payments (link to Accounts Payable)
                    $apAccountId = (int) (SystemSetting::where('key', 'inventory_default_purchase_payable_account')->value('value') ?? 30);
                    $invoicePaymentItems = [];
                    
                    foreach ($invoicePayments as $idx => $invPayment) {
                        $invoicePayment = Payment::where('reference', $invPayment['reference'])->first();
                        if ($invoicePayment) {
                            $invoicePaymentItems[] = [
                                'payment_id' => $invoicePayment->id,
                                'chart_account_id' => $apAccountId,
                                'amount' => $invPayment['amount'],
                                'wht_treatment' => $invPayment['wht_treatment'],
                                'wht_rate' => $invPayment['wht_rate'],
                                'wht_amount' => $invPayment['wht_amount'],
                                'base_amount' => $invPayment['base_amount'],
                                'net_payable' => $invPayment['net_payable'],
                                'total_cost' => $invPayment['total_cost'],
                                'vat_mode' => $invPayment['vat_mode'],
                                'vat_amount' => $invPayment['vat_amount'],
                                'description' => $invPayment['description'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                    
                    if (!empty($invoicePaymentItems)) {
                        PaymentItem::insert($invoicePaymentItems);
                        
                        // Post GL transactions for invoice payments if approved
                        foreach ($invoicePayments as $invPayment) {
                            $invoicePayment = Payment::where('reference', $invPayment['reference'])->first();
                            if ($invoicePayment && $invoicePayment->approved) {
                                $invoicePayment->createGlTransactions();
                            }
                        }
                    }
                }

                // Post to GL only if payment has been approved (either approvals disabled or auto-approved)
                // Use Payment model's createGlTransactions method which handles WHT correctly
                $payment->refresh();
                if ($payment->approved) {
                    $payment->createGlTransactions();
                    $successMessage = 'Payment voucher created and posted to GL successfully.';
                } else {
                    $successMessage = 'Payment voucher created and is awaiting approval. GL posting will occur after final approval.';
                }

                // If AJAX request or reconciliation_id is present, return JSON or redirect to reconciliation
                if ($request->ajax() || $request->wantsJson()) {
                    $redirectUrl = route('accounting.payment-vouchers.show', $payment);
                    
                    // If reconciliation_id is present, redirect to bank reconciliation page
                    if ($request->has('reconciliation_id') && $request->reconciliation_id) {
                        $redirectUrl = route('accounting.bank-reconciliation.show', \App\Helpers\HashIdHelper::encode($request->reconciliation_id));
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => $successMessage,
                        'redirect_url' => $redirectUrl,
                        'payment_id' => $payment->id
                    ]);
                }
                
                // If reconciliation_id is present, redirect to bank reconciliation page
                if ($request->has('reconciliation_id') && $request->reconciliation_id) {
                    return redirect()->route('accounting.bank-reconciliation.show', \App\Helpers\HashIdHelper::encode($request->reconciliation_id))
                        ->with('success', $successMessage);
                }

                return redirect()->route('accounting.payment-vouchers.show', $payment)
                    ->with('success', $successMessage);
            });
        } catch (\Exception $e) {
            \Log::error('Payment Voucher Creation Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Request data: ' . json_encode($request->all()));
            
            $errorMessage = $e->getMessage();
            
            // Check if it's a reconciliation error
            if (str_contains($errorMessage, 'completed reconciliation period')) {
                $errorMessage = 'Cannot post: Payment is in a completed reconciliation period';
            } else {
                $errorMessage = 'Failed to create payment voucher: ' . $errorMessage;
            }
            
            // For AJAX requests, return JSON with SweetAlert-friendly format
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'swal' => true, // Flag to indicate this should be shown as SweetAlert
                    'icon' => 'error'
                ], 422);
            }
            
            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    /**
     * Helper method to resolve payment voucher from encoded ID or instance
     */
    protected function resolvePaymentVoucher($encodedIdOrInstance)
    {
        if ($encodedIdOrInstance instanceof Payment) {
            return $encodedIdOrInstance;
        }
        
        $decodedId = HashIdHelper::decode($encodedIdOrInstance);
        if ($decodedId === null) {
            return null;
        }
        
        return Payment::find($decodedId);
    }

    /**
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }
        $paymentVoucher->load(['bankAccount', 'customer', 'supplier', 'user', 'branch', 'paymentItems.chartAccount', 'glTransactions.chartAccount']);

        return view('accounting.payment-vouchers.show', compact('paymentVoucher'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }
        $user = Auth::user();

        // Editing approved vouchers is allowed

        // Get bank accounts for the current company, respecting branch scope
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true);
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->orderBy('name')
            ->get();

        // Get customers for the current company/branch
        $customers = Customer::where('company_id', $user->company_id)
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->orderBy('name')
            ->get();

        // Get suppliers for the current company
        $suppliers = Supplier::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        $employees = collect();

        // Get chart accounts for the current company - all account types
        $chartAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('account_name')
            ->get();

        $paymentVoucher->load(['paymentItems', 'cheque']);

        return view('accounting.payment-vouchers.edit', compact('paymentVoucher', 'bankAccounts', 'customers', 'suppliers', 'employees', 'chartAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }

        // Updating approved vouchers is allowed

        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
        
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:bank_transfer,cash_deposit,cheque',
            'bank_account_id' => 'required_if:payment_method,bank_transfer|required_if:payment_method,cheque|nullable|exists:bank_accounts,id',
            'cash_deposit_id' => 'required_if:payment_method,cash_deposit|nullable|in:customer_balance',
            'cheque_number' => 'required_if:payment_method,cheque|nullable|string|max:50',
            'cheque_date' => 'required_if:payment_method,cheque|nullable|date',
            'currency' => 'nullable|string|size:3',
            'exchange_rate' => [
                'nullable',
                'numeric',
                'min:0.000001',
                function ($attribute, $value, $fail) use ($request, $functionalCurrency) {
                    if ($request->currency && $request->currency !== $functionalCurrency && (!$value || $value == 1)) {
                        $fail('Exchange rate is required when currency is different from functional currency.');
                    }
                },
            ],
            'payee_type' => 'required|in:customer,supplier,employee,other',
            'customer_id' => 'required_if:payee_type,customer|exists:customers,id',
            'supplier_id' => 'required_if:payee_type,supplier|exists:suppliers,id',
            'employee_id' => 'nullable',
            'payee_name' => 'required_if:payee_type,other|nullable|string|max:255',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf|max:2048',
            'wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,GROSS_UP,NONE',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'line_items' => 'required|array|min:1',
            'line_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.description' => 'nullable|string',
            'line_items.*.wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,GROSS_UP,NONE',
            'line_items.*.wht_rate' => 'nullable|numeric|min:0|max:100',
            'line_items.*.vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'line_items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            return $this->runTransaction(function () use ($request, $paymentVoucher) {
                $user = Auth::user();
                $branchId = session('branch_id') ?? ($user->branch_id ?? null);
                
                // Check budget limits before proceeding
                // For updates, exclude current payment voucher's GL transactions
                $budgetCheck = $this->checkBudgetLimits(
                    $request->line_items,
                    $branchId,
                    $user->company_id,
                    $request->date,
                    $paymentVoucher->id // Exclude this payment from used amount calculation
                );

                // If budget check fails, return with errors
                if (!$budgetCheck['valid']) {
                    return redirect()->back()
                        ->withErrors(['budget' => $budgetCheck['errors']])
                        ->withInput()
                        ->with('budget_warnings', $budgetCheck['warnings']);
                }

                // Store warnings in session to display after successful update
                if (!empty($budgetCheck['warnings'])) {
                    session()->flash('budget_warnings', $budgetCheck['warnings']);
                }

                // Check if WHT is enabled.
                // - If the form has an explicit switch (create form), use it
                // - Otherwise (edit form), infer from the submitted WHT treatment/rate if present,
                //   falling back to the existing voucher settings when the user hasn't touched WHT.
                if ($request->has('wht_enabled')) {
                    $whtEnabled = $request->wht_enabled == '1';
                } else {
                    $requestWhtTreatment = $request->input('wht_treatment');
                    $requestWhtRate = $request->input('wht_rate');

                    if (!is_null($requestWhtTreatment) || !is_null($requestWhtRate)) {
                        // User has interacted with the WHT fields on the edit form:
                        // treat WHT as enabled only when treatment is not NONE and rate > 0.
                        $whtEnabled = $requestWhtTreatment
                            && $requestWhtTreatment !== 'NONE'
                            && (float) $requestWhtRate > 0;
                    } else {
                        // No new WHT values submitted – preserve existing WHT so it is not reset unintentionally.
                        $hasExistingWht = $paymentVoucher->wht_treatment
                            && $paymentVoucher->wht_treatment !== 'NONE'
                            && (float) ($paymentVoucher->wht_rate ?? 0) > 0;
                        $whtEnabled = $hasExistingWht;
                    }
                }

                $whtService = new \App\Services\WithholdingTaxService();

                // Calculate total amount (sum of line items - may include VAT)
                $totalAmount = collect($request->line_items)->sum('amount');

                // Get WHT treatment and rate (payment-level or item-level)
                if (!$whtEnabled) {
                    $whtTreatment = 'NONE';
                    $whtRate = 0;
                    $vatMode = 'NONE';
                    $vatRate = 0;
                } else {
                    $whtTreatment = $request->wht_treatment ?? $paymentVoucher->wht_treatment ?? 'EXCLUSIVE';
                    $whtRate = (float) ($request->wht_rate ?? $paymentVoucher->wht_rate ?? 0);
                    $defaultVatType = get_default_vat_type();
                    $defaultVatMode = 'EXCLUSIVE';
                    if ($defaultVatType == 'inclusive') {
                        $defaultVatMode = 'INCLUSIVE';
                    } elseif ($defaultVatType == 'exclusive') {
                        $defaultVatMode = 'EXCLUSIVE';
                    } elseif ($defaultVatType == 'no_vat') {
                        $defaultVatMode = 'NONE';
                    }
                    $vatMode = $request->vat_mode ?? $paymentVoucher->vat_mode ?? $defaultVatMode;
                    $vatRate = (float) ($request->vat_rate ?? $paymentVoucher->vat_rate ?? get_default_vat_rate());
                    if ($request->payee_type === 'supplier' && $request->supplier_id) {
                        $supplier = \App\Models\Supplier::find($request->supplier_id);
                        if ($supplier && $supplier->allow_gross_up && $whtTreatment === 'EXCLUSIVE') {
                            $whtTreatment = 'GROSS_UP';
                        }
                    }
                }

                // Calculate WHT at payment level if rate is provided (with VAT integration)
                $paymentWHT = 0;
                $paymentNetPayable = $totalAmount;
                $paymentTotalCost = $totalAmount;
                $paymentBaseAmount = $totalAmount;
                $paymentVatAmount = 0;

                if ($whtEnabled && $whtRate > 0 && $whtTreatment !== 'NONE') {
                    $whtCalc = $whtService->calculateWHT($totalAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                    $paymentWHT = $whtCalc['wht_amount'];
                    $paymentNetPayable = $whtCalc['net_payable'];
                    $paymentTotalCost = $whtCalc['total_cost'];
                    $paymentBaseAmount = $whtCalc['base_amount'];
                    $paymentVatAmount = $whtCalc['vat_amount'];
                } elseif ($whtEnabled && $vatMode !== 'NONE' && $vatRate > 0) {
                    // Calculate VAT even if no WHT
                    if ($vatMode === 'INCLUSIVE') {
                        // VAT is included in total amount - extract base
                        $paymentBaseAmount = round($totalAmount / (1 + ($vatRate / 100)), 2);
                        $paymentVatAmount = round($totalAmount - $paymentBaseAmount, 2);
                    } else {
                        // EXCLUSIVE - total amount IS the base amount, VAT is added on top
                        $paymentBaseAmount = $totalAmount;
                        $paymentVatAmount = round($totalAmount * ($vatRate / 100), 2);
                    }
                }

                // Handle file upload and attachment removal
                $attachmentPath = $paymentVoucher->attachment;
                
                // Check if user wants to remove attachment
                if ($request->has('remove_attachment') && $request->remove_attachment == '1') {
                    // Delete old attachment if exists
                    if ($paymentVoucher->attachment && Storage::disk('public')->exists($paymentVoucher->attachment)) {
                        Storage::disk('public')->delete($paymentVoucher->attachment);
                    }
                    $attachmentPath = null;
                } elseif ($request->hasFile('attachment')) {
                    // Delete old attachment if exists
                    if ($paymentVoucher->attachment && Storage::disk('public')->exists($paymentVoucher->attachment)) {
                        Storage::disk('public')->delete($paymentVoucher->attachment);
                    }

                    $file = $request->file('attachment');
                    $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $attachmentPath = $file->storeAs('payment-attachments', $fileName, 'public');
                }

                // Handle payee information
                $payeeType = $request->payee_type;
                $payeeId = null;
                $payeeName = null;

                if ($request->payee_type === 'customer') {
                    $payeeId = $request->customer_id;
                } elseif ($request->payee_type === 'supplier') {
                    $payeeId = $request->supplier_id;
                } elseif ($request->payee_type === 'employee') {
                    $payeeId = $request->employee_id;
                } elseif ($request->payee_type === 'other') {
                    $payeeName = $request->payee_name;
                }

                // Get functional currency
                $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $user->company->functional_currency ?? 'TZS');
                $currency = $request->currency ?? $functionalCurrency;
                
                // Get exchange rate using FxTransactionRateService
                $fxTransactionRateService = app(FxTransactionRateService::class);
                $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
                $rateResult = $fxTransactionRateService->getTransactionRate(
                    $currency,
                    $functionalCurrency,
                    $request->date,
                    $user->company_id,
                    $userProvidedRate
                );
                $exchangeRate = $rateResult['rate'];
                $fxRateUsed = $exchangeRate; // Store the rate used for fx_rate_used field
                
                // Calculate FCY and LCY amounts for revaluation
                $needsConversion = ($currency !== $functionalCurrency);
                $amountFcy = $needsConversion ? $totalAmount : null; // FCY amount (if foreign currency)
                $amountLcy = $needsConversion ? ($totalAmount * $exchangeRate) : $totalAmount; // LCY amount
                
                // Set default payment method if not provided
                $paymentMethod = $request->payment_method ?? $paymentVoucher->payment_method ?? 'bank_transfer';
                
                // Handle cheque creation/update if cheque payment method is selected
                $chequeId = $paymentVoucher->cheque_id;
                if ($paymentMethod === 'cheque') {
                    $chequeService = new \App\Services\ChequeService();
                    
                    // Get payee name
                    $chequePayeeName = $payeeName;
                    if ($payeeType === 'customer' && $request->customer_id) {
                        $customer = \App\Models\Customer::find($request->customer_id);
                        $chequePayeeName = $customer->name ?? $payeeName;
                    } elseif ($payeeType === 'supplier' && $request->supplier_id) {
                        $supplier = \App\Models\Supplier::find($request->supplier_id);
                        $chequePayeeName = $supplier->name ?? $payeeName;
                    } elseif ($payeeType === 'employee') {
                        $chequePayeeName = $payeeName ?? 'N/A';
                    }
                    
                    // Get expense account from first line item (for journal entry)
                    // Find the first line item with a valid chart_account_id
                    $expenseAccountId = null;
                    if (!empty($request->line_items)) {
                        foreach ($request->line_items as $lineItem) {
                            if (!empty($lineItem['chart_account_id'])) {
                                $expenseAccountId = $lineItem['chart_account_id'];
                                break;
                            }
                        }
                    }
                    
                    // Validate expense account exists
                    if (!$expenseAccountId) {
                        throw new \Exception('Expense or Payable account is required for cheque issuance. Please ensure line items have chart accounts selected.');
                    }
                    
                    if ($chequeId) {
                        // Update existing cheque
                        $cheque = \App\Models\Cheque::find($chequeId);
                        if ($cheque) {
                            $cheque->update([
                                'cheque_number' => $request->cheque_number,
                                'cheque_date' => $request->cheque_date ?? $request->date,
                                'bank_account_id' => $request->bank_account_id,
                                'payee_name' => $chequePayeeName,
                                'amount' => $totalAmount,
                                'description' => $request->description,
                            ]);
                        }
                    } else {
                        // Create new cheque
                        $cheque = $chequeService->issueCheque([
                            'cheque_number' => $request->cheque_number,
                            'cheque_date' => $request->cheque_date ?? $request->date,
                            'bank_account_id' => $request->bank_account_id,
                            'payee_name' => $chequePayeeName,
                            'amount' => $totalAmount,
                            'payment_reference_type' => 'payment',
                            'payment_reference_id' => $paymentVoucher->id,
                            'payment_reference_number' => $request->reference ?: $paymentVoucher->reference,
                            'module_origin' => 'payment_voucher',
                            'payment_type' => $payeeType,
                            'description' => $request->description,
                            'company_id' => $user->company_id,
                            'branch_id' => $branchId,
                            'issued_by' => $user->id,
                            'expense_account_id' => $expenseAccountId,
                        ]);
                        $chequeId = $cheque->id;
                    }
                } elseif ($chequeId) {
                    // If payment method changed from cheque, cancel the cheque
                    $cheque = \App\Models\Cheque::find($chequeId);
                    if ($cheque && $cheque->status === 'issued') {
                        $chequeService = new \App\Services\ChequeService();
                        try {
                            $chequeService->cancelCheque($chequeId, 'Payment method changed from cheque');
                        } catch (\Exception $e) {
                            \Log::warning('Failed to cancel cheque when payment method changed', [
                                'cheque_id' => $chequeId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    $chequeId = null;
                }
                
                // Update payment
                $updateData = [
                    'reference' => $request->reference ?: $paymentVoucher->reference,
                    'amount' => $totalAmount, // Total amount (may include VAT)
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'amount_fcy' => $amountFcy, // Foreign currency amount for revaluation
                    'amount_lcy' => $amountLcy, // Local currency amount for revaluation
                    'fx_rate_used' => $fxRateUsed,
                    'wht_treatment' => $whtTreatment,
                    'wht_rate' => $whtRate,
                    'wht_amount' => $paymentWHT,
                    'net_payable' => $paymentNetPayable,
                    'total_cost' => $paymentTotalCost,
                    'vat_mode' => $vatMode,
                    'vat_amount' => $paymentVatAmount,
                    'base_amount' => $paymentBaseAmount,
                    'date' => $request->date,
                    'description' => $request->description,
                    'attachment' => $attachmentPath,
                    'payment_method' => $paymentMethod,
                    'bank_account_id' => ($paymentMethod === 'cheque' || $paymentMethod === 'bank_transfer') ? $request->bank_account_id : null,
                    'cash_deposit_id' => ($paymentMethod === 'cash_deposit' && $request->cash_deposit_id !== 'customer_balance') ? $request->cash_deposit_id : null,
                    'cheque_id' => $chequeId,
                    'payee_type' => $payeeType,
                    'payee_id' => $payeeId,
                    'payee_name' => $payeeName,
                    'customer_id' => $request->customer_id,
                    'supplier_id' => $request->supplier_id,
                    'employee_id' => $request->employee_id,
                    'branch_id' => $branchId,
                ];

                $paymentVoucher->update($updateData);

                // Delete existing payment items and GL transactions
                $paymentVoucher->paymentItems()->delete();
                $paymentVoucher->glTransactions()->delete();

                // Create new payment items with WHT and VAT calculation
                $paymentItems = [];
                foreach ($request->line_items as $lineItem) {
                    $itemTotalAmount = (float) $lineItem['amount'];
                    // If WHT is disabled, ignore item-level WHT settings
                    if (!$whtEnabled) {
                        $itemWHTTreatment = 'NONE';
                        $itemWHTRate = 0;
                        $itemVatMode = 'NONE';
                        $itemVatRate = 0;
                    } else {
                        $itemWHTTreatment = $lineItem['wht_treatment'] ?? $whtTreatment;
                        $itemWHTRate = (float) ($lineItem['wht_rate'] ?? $whtRate);
                        $itemVatMode = $lineItem['vat_mode'] ?? $vatMode;
                        $itemVatRate = (float) ($lineItem['vat_rate'] ?? $vatRate);
                    }
                    
                    $itemWHT = 0;
                    $itemNetPayable = $itemTotalAmount;
                    $itemTotalCost = $itemTotalAmount;
                    $itemBaseAmount = $itemTotalAmount;
                    $itemVatAmount = 0;
                    
                    if ($whtEnabled && $itemWHTRate > 0 && $itemWHTTreatment !== 'NONE') {
                        $itemWHTCalc = $whtService->calculateWHT($itemTotalAmount, $itemWHTRate, $itemWHTTreatment, $itemVatMode, $itemVatRate);
                        $itemWHT = $itemWHTCalc['wht_amount'];
                        $itemNetPayable = $itemWHTCalc['net_payable'];
                        $itemTotalCost = $itemWHTCalc['total_cost'];
                        $itemBaseAmount = $itemWHTCalc['base_amount'];
                        $itemVatAmount = $itemWHTCalc['vat_amount'];
                    } elseif ($vatMode === 'INCLUSIVE' && $paymentBaseAmount > 0 && $totalAmount > 0) {
                        // When VAT is INCLUSIVE at payment level, allocate base and VAT proportionally
                        // This prevents double VAT extraction when item amount already includes VAT
                        $itemBaseAmount = round(($itemTotalAmount / $totalAmount) * $paymentBaseAmount, 2);
                        $itemVatAmount = round(($itemTotalAmount / $totalAmount) * $paymentVatAmount, 2);
                    } elseif ($vatMode === 'EXCLUSIVE' && $paymentBaseAmount > 0 && $totalAmount > 0) {
                        // When VAT is EXCLUSIVE at payment level, allocate base and VAT proportionally
                        // Item amount is part of the base, allocate proportionally
                        $itemBaseAmount = round(($itemTotalAmount / $totalAmount) * $paymentBaseAmount, 2);
                        $itemVatAmount = round(($itemTotalAmount / $totalAmount) * $paymentVatAmount, 2);
                    } elseif ($itemVatMode !== 'NONE' && $itemVatRate > 0) {
                        // Calculate VAT even if no WHT (for item-level VAT)
                        if ($itemVatMode === 'INCLUSIVE') {
                            // VAT is included in item amount - extract base
                            $itemBaseAmount = round($itemTotalAmount / (1 + ($itemVatRate / 100)), 2);
                            $itemVatAmount = round($itemTotalAmount - $itemBaseAmount, 2);
                        } else {
                            // EXCLUSIVE - item amount IS the base amount, VAT is added on top
                            $itemBaseAmount = $itemTotalAmount;
                            $itemVatAmount = round($itemTotalAmount * ($itemVatRate / 100), 2);
                        }
                    }
                    
                    $paymentItems[] = [
                        'payment_id' => $paymentVoucher->id,
                        'chart_account_id' => $lineItem['chart_account_id'],
                        'amount' => $itemTotalAmount,
                        'wht_treatment' => $itemWHTTreatment,
                        'wht_rate' => $itemWHTRate,
                        'wht_amount' => $itemWHT,
                        'base_amount' => $itemBaseAmount,
                        'net_payable' => $itemNetPayable,
                        'total_cost' => $itemTotalCost,
                        'vat_mode' => $itemVatMode,
                        'vat_amount' => $itemVatAmount,
                        'description' => $lineItem['description'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                PaymentItem::insert($paymentItems);

                // Handle invoice payments - delete old invoice payments and create new ones
                // Find existing invoice payments linked to this payment voucher
                $existingInvoicePayments = Payment::where('reference', 'like', $paymentVoucher->reference . '-INV-%')
                    ->orWhere(function($query) use ($paymentVoucher) {
                        $query->where('reference_type', 'purchase_invoice')
                              ->where('supplier_id', $paymentVoucher->supplier_id)
                              ->where('date', $paymentVoucher->date)
                              ->where('user_id', $paymentVoucher->user_id);
                    })
                    ->get();
                
                // Delete existing invoice payments and their items/GL transactions
                foreach ($existingInvoicePayments as $existingPayment) {
                    $existingPayment->paymentItems()->delete();
                    $existingPayment->glTransactions()->delete();
                    $existingPayment->delete();
                }
                
                // Create new invoice payments for each invoice line item
                $invoicePayments = [];
                foreach ($request->line_items as $index => $lineItem) {
                    if (isset($lineItem['invoice_id']) && isset($lineItem['invoice_number'])) {
                        $invoice = \App\Models\Purchase\PurchaseInvoice::find($lineItem['invoice_id']);
                        if ($invoice && $invoice->invoice_number === $lineItem['invoice_number']) {
                            // Create a payment record linked to this invoice
                            $invoicePaymentAmount = (float) $lineItem['amount'];
                            
                            // Calculate WHT for this invoice payment
                            $invoiceWHT = 0;
                            $invoiceNetPayable = $invoicePaymentAmount;
                            $invoiceTotalCost = $invoicePaymentAmount;
                            $invoiceBaseAmount = $invoicePaymentAmount;
                            $invoiceVatAmount = 0;
                            
                            if ($whtEnabled && $whtRate > 0 && $whtTreatment !== 'NONE') {
                                $invoiceWHTCalc = $whtService->calculateWHT($invoicePaymentAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                                $invoiceWHT = $invoiceWHTCalc['wht_amount'];
                                $invoiceNetPayable = $invoiceWHTCalc['net_payable'];
                                $invoiceTotalCost = $invoiceWHTCalc['total_cost'];
                                $invoiceBaseAmount = $invoiceWHTCalc['base_amount'];
                                $invoiceVatAmount = $invoiceWHTCalc['vat_amount'];
                            } elseif ($vatMode === 'INCLUSIVE' && $paymentBaseAmount > 0 && $totalAmount > 0) {
                                $invoiceBaseAmount = round(($invoicePaymentAmount / $totalAmount) * $paymentBaseAmount, 2);
                                $invoiceVatAmount = round(($invoicePaymentAmount / $totalAmount) * $paymentVatAmount, 2);
                            } elseif ($vatMode === 'EXCLUSIVE' && $paymentBaseAmount > 0 && $totalAmount > 0) {
                                $invoiceBaseAmount = round(($invoicePaymentAmount / $totalAmount) * $paymentBaseAmount, 2);
                                $invoiceVatAmount = round(($invoicePaymentAmount / $totalAmount) * $paymentVatAmount, 2);
                            }
                            
                            // Calculate amounts in LCY
                            $invoiceAmountFcy = $invoicePaymentAmount;
                            $invoiceAmountLcy = $needsConversion ? round($invoicePaymentAmount * $exchangeRate, 2) : $invoicePaymentAmount;
                            
                            $invoicePayments[] = [
                                'reference' => $paymentVoucher->reference . '-INV-' . $invoice->invoice_number,
                                'reference_type' => 'purchase_invoice',
                                'reference_number' => $invoice->invoice_number,
                                'amount' => $invoicePaymentAmount,
                                'currency' => $currency,
                                'exchange_rate' => $exchangeRate,
                                'amount_fcy' => $invoiceAmountFcy,
                                'amount_lcy' => $invoiceAmountLcy,
                                'fx_rate_used' => $fxRateUsed,
                                'wht_treatment' => $whtTreatment,
                                'wht_rate' => $whtRate,
                                'wht_amount' => $invoiceWHT,
                                'net_payable' => $invoiceNetPayable,
                                'total_cost' => $invoiceTotalCost,
                                'vat_mode' => $vatMode,
                                'vat_amount' => $invoiceVatAmount,
                                'base_amount' => $invoiceBaseAmount,
                                'date' => $request->date,
                                'description' => ($lineItem['description'] ?? null) ?: "Payment for Invoice {$invoice->invoice_number}",
                                'attachment' => $attachmentPath,
                                'user_id' => $user->id,
                                'payment_method' => $paymentMethod,
                                'bank_account_id' => ($paymentMethod === 'cheque' || $paymentMethod === 'bank_transfer') ? $request->bank_account_id : null,
                                'cash_deposit_id' => ($paymentMethod === 'cash_deposit' && $request->cash_deposit_id !== 'customer_balance') ? $request->cash_deposit_id : null,
                                'cheque_id' => $chequeId,
                                'payee_type' => 'supplier',
                                'payee_id' => $request->supplier_id,
                                'payee_name' => null,
                                'customer_id' => null,
                                'supplier_id' => $request->supplier_id,
                                'employee_id' => null,
                                'branch_id' => $branchId,
                                'approved' => $paymentVoucher->approved, // Same approval status as main payment
                                'approved_by' => $paymentVoucher->approved_by,
                                'approved_at' => $paymentVoucher->approved_at,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
                
                // Insert invoice payments if any
                if (!empty($invoicePayments)) {
                    Payment::insert($invoicePayments);
                    
                    // Create payment items for invoice payments (link to Accounts Payable)
                    $apAccountId = (int) (SystemSetting::where('key', 'inventory_default_purchase_payable_account')->value('value') ?? 30);
                    $invoicePaymentItems = [];
                    
                    foreach ($invoicePayments as $idx => $invPayment) {
                        $invoicePayment = Payment::where('reference', $invPayment['reference'])->first();
                        if ($invoicePayment) {
                            $invoicePaymentItems[] = [
                                'payment_id' => $invoicePayment->id,
                                'chart_account_id' => $apAccountId,
                                'amount' => $invPayment['amount'],
                                'wht_treatment' => $invPayment['wht_treatment'],
                                'wht_rate' => $invPayment['wht_rate'],
                                'wht_amount' => $invPayment['wht_amount'],
                                'base_amount' => $invPayment['base_amount'],
                                'net_payable' => $invPayment['net_payable'],
                                'total_cost' => $invPayment['total_cost'],
                                'vat_mode' => $invPayment['vat_mode'],
                                'vat_amount' => $invPayment['vat_amount'],
                                'description' => $invPayment['description'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                    
                    if (!empty($invoicePaymentItems)) {
                        PaymentItem::insert($invoicePaymentItems);
                        
                        // Post GL transactions for invoice payments if approved
                        foreach ($invoicePayments as $invPayment) {
                            $invoicePayment = Payment::where('reference', $invPayment['reference'])->first();
                            if ($invoicePayment && $invoicePayment->approved) {
                                $invoicePayment->createGlTransactions();
                            }
                        }
                    }
                }

                // Post to GL using Payment model's createGlTransactions method which handles WHT correctly
                $paymentVoucher->refresh();
                if ($paymentVoucher->approved) {
                    $paymentVoucher->createGlTransactions();
                }

                return redirect()->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                    ->with('success', 'Payment voucher updated successfully.');
            });
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Check if it's a reconciliation error
            if (str_contains($errorMessage, 'completed reconciliation period')) {
                $errorMessage = 'Cannot post: Payment is in a completed reconciliation period';
            } else {
                $errorMessage = 'Failed to update payment voucher: ' . $errorMessage;
            }
            
            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }

        // Deleting approved vouchers is allowed

        try {
            return $this->runTransaction(function () use ($paymentVoucher) {
                // Delete attachment if exists
                if ($paymentVoucher->attachment && Storage::disk('public')->exists($paymentVoucher->attachment)) {
                    Storage::disk('public')->delete($paymentVoucher->attachment);
                }

                // Delete related records
                $paymentVoucher->paymentItems()->delete();
                $paymentVoucher->glTransactions()->delete();
                $paymentVoucher->delete();

                return redirect()->route('accounting.payment-vouchers.index')
                    ->with('success', 'Payment voucher deleted successfully.');
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete payment voucher: ' . $e->getMessage()]);
        }
    }

    /**
     * Download attachment.
     */
    public function downloadAttachment($encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }
        
        if (!$paymentVoucher->attachment) {
            return redirect()->back()->withErrors(['error' => 'No attachment found.']);
        }

        if (!Storage::disk('public')->exists($paymentVoucher->attachment)) {
            return redirect()->back()->withErrors(['error' => 'Attachment file not found.']);
        }

        return Storage::disk('public')->download($paymentVoucher->attachment);
    }

    /**
     * Remove attachment.
     */
    public function removeAttachment($encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }
        
        // Check if payment voucher is approved - if so, prevent attachment removal
        if ($paymentVoucher->isFullyApproved()) {
            return redirect()->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                ->withErrors(['error' => 'Cannot modify an approved payment voucher.']);
        }

        try {
            // Delete attachment file if exists
            if ($paymentVoucher->attachment && Storage::disk('public')->exists($paymentVoucher->attachment)) {
                Storage::disk('public')->delete($paymentVoucher->attachment);
            }

            // Update payment to remove attachment reference
            $paymentVoucher->update(['attachment' => null]);

            return redirect()->back()->with('success', 'Attachment removed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to remove attachment: ' . $e->getMessage()]);
        }
    }

    /**
     * Export payment voucher to PDF
     */
    public function exportPdf($encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }
        
        try {
            // Check if user has access to this payment voucher
            $user = Auth::user();
            if ($paymentVoucher->bankAccount->chartAccount->accountClassGroup->company_id !== $user->company_id) {
                abort(403, 'Unauthorized access to this payment voucher.');
            }

            // Load relationships
            $paymentVoucher->load([
                'bankAccount.chartAccount',
                'customer',
                'supplier',
                'employee',
                'user.company',
                'approvedBy',
                'branch',
                'paymentItems.chartAccount'
            ]);

            // Generate PDF using DomPDF
            $pdf = \PDF::loadView('accounting.payment-vouchers.pdf', compact('paymentVoucher'));

            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');

            // Generate filename
            $filename = 'payment_voucher_' . $paymentVoucher->reference . '_' . date('Y-m-d_H-i-s') . '.pdf';

            // Return PDF for download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to export PDF: ' . $e->getMessage()]);
        }
    }

    /**
     * Mark cheque for this payment voucher as cleared.
     * This should be used when the bank confirms the cheque has cleared.
     *
     * Journal entry (already handled by ChequeService):
     *   Dr Cheque Issued Clearing
     *       Cr Bank Account
     */
    public function clearCheque(Request $request, $encodedId)
    {
        $this->authorize('edit payment voucher');
        
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return $request->wantsJson() 
                ? response()->json(['error' => 'Payment voucher not found.'], 404)
                : redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }

        if ($paymentVoucher->payment_method !== 'cheque' || !$paymentVoucher->cheque_id) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'This payment voucher is not linked to a cheque.'], 400)
                : redirect()->back()->withErrors(['error' => 'This payment voucher is not linked to a cheque.']);
        }

        try {
            $cheque = \App\Models\Cheque::findOrFail($paymentVoucher->cheque_id);

            // Check if cheque is already cleared before attempting to clear
            if ($cheque->status === 'cleared' && $cheque->clear_journal_id) {
                $message = 'This cheque has already been cleared. Journal Reference: CHQ-CLEAR-' . $cheque->cheque_number;
                
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 400);
                }
                
                return redirect()->back()->withErrors(['error' => $message]);
            }

            // Rate limiting to prevent duplicate requests
            $cacheKey = 'cheque_clear_' . $cheque->id;
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                $message = 'Please wait a moment before clearing this cheque again.';
                
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 429);
                }
                
                return redirect()->back()->withErrors(['error' => $message]);
            }
            
            // Set cache for 10 seconds to prevent duplicate requests
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, 10);

            $chequeService = new \App\Services\ChequeService();
            
            try {
                // Clear the cheque - this creates journal, updates cheque, and creates GL transactions
                $chequeService->clearCheque($cheque, Auth::id());
                
                // If we reach here, cheque was cleared successfully
                // Clear the cache after successful clearing
                \Illuminate\Support\Facades\Cache::forget($cacheKey);

                $message = 'Cheque marked as cleared successfully.';

                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $message]);
                }

                return redirect()
                    ->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                    ->with('success', $message);
                    
            } catch (\Exception $serviceException) {
                // Even if service throws exception, check if cheque was actually cleared
                // (this can happen if GL transactions fail after cheque is saved)
                // Clear cache on error
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                
                // Reload cheque from database to check actual status (don't rely on variable)
                try {
                    $chequeFromDb = \App\Models\Cheque::find($paymentVoucher->cheque_id);
                    
                    if ($chequeFromDb && $chequeFromDb->status === 'cleared' && $chequeFromDb->clear_journal_id) {
                        // Cheque was actually cleared despite exception - return success
                        $message = 'Cheque marked as cleared successfully.';
                        
                        \Log::warning('Cheque cleared successfully but exception occurred during process', [
                            'payment_id' => $paymentVoucher->id,
                            'cheque_id' => $chequeFromDb->id,
                            'cheque_number' => $chequeFromDb->cheque_number,
                            'original_error' => $serviceException->getMessage(),
                            'cheque_status' => $chequeFromDb->status,
                            'clear_journal_id' => $chequeFromDb->clear_journal_id,
                        ]);

                        if ($request->wantsJson()) {
                            return response()->json(['success' => true, 'message' => $message]);
                        }

                        return redirect()
                            ->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                            ->with('success', $message);
                    }
                } catch (\Exception $refreshException) {
                    // If we can't check cheque status, log it but continue with error handling
                    \Log::error('Failed to check cheque status after clear attempt', [
                        'payment_id' => $paymentVoucher->id,
                        'cheque_id' => $paymentVoucher->cheque_id,
                        'refresh_error' => $refreshException->getMessage(),
                        'original_error' => $serviceException->getMessage(),
                    ]);
                }
                
                // Cheque wasn't cleared, re-throw the exception to be caught by outer catch block
                throw $serviceException;
            }
        } catch (\Exception $e) {
            // Clear cache on error
            \Illuminate\Support\Facades\Cache::forget('cheque_clear_' . $paymentVoucher->cheque_id);
            
            // Always refresh cheque from database to check actual status
            // Even if an exception was thrown, the cheque might have been cleared before the exception
            try {
                // Reload cheque from database (don't rely on the variable, query fresh)
                $chequeFromDb = \App\Models\Cheque::find($paymentVoucher->cheque_id);
                
                if ($chequeFromDb && $chequeFromDb->status === 'cleared' && $chequeFromDb->clear_journal_id) {
                    // Cheque was actually cleared despite the exception - return success
                    $message = 'Cheque marked as cleared successfully.';
                    
                    \Log::warning('Cheque cleared successfully but exception occurred during process', [
                        'payment_id' => $paymentVoucher->id,
                        'cheque_id' => $paymentVoucher->cheque_id,
                        'cheque_number' => $chequeFromDb->cheque_number,
                        'original_error' => $e->getMessage(),
                        'cheque_status' => $chequeFromDb->status,
                        'clear_journal_id' => $chequeFromDb->clear_journal_id,
                    ]);

                    if ($request->wantsJson()) {
                        return response()->json(['success' => true, 'message' => $message]);
                    }

                    return redirect()
                        ->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                        ->with('success', $message);
                }
            } catch (\Exception $refreshException) {
                // If we can't refresh, log it but continue with error handling
                \Log::error('Failed to check cheque status after clear attempt', [
                    'payment_id' => $paymentVoucher->id,
                    'cheque_id' => $paymentVoucher->cheque_id,
                    'refresh_error' => $refreshException->getMessage(),
                    'original_error' => $e->getMessage(),
                ]);
            }
            
            \Log::error('Error clearing cheque for payment voucher', [
                'payment_id' => $paymentVoucher->id,
                'cheque_id' => $paymentVoucher->cheque_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = 'Failed to clear cheque: ' . $e->getMessage();

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return redirect()->back()->withErrors(['error' => $message]);
        }
    }

    /**
     * Fix duplicate GL transactions for a cleared cheque's clear journal
     */
    public function fixChequeDuplicateGlTransactions(Request $request, $encodedId)
    {
        $this->authorize('edit payment voucher');
        
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return $request->wantsJson() 
                ? response()->json(['error' => 'Payment voucher not found.'], 404)
                : redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }

        if ($paymentVoucher->payment_method !== 'cheque' || !$paymentVoucher->cheque_id) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'This payment voucher is not linked to a cheque.'], 400)
                : redirect()->back()->withErrors(['error' => 'This payment voucher is not linked to a cheque.']);
        }

        try {
            $cheque = \App\Models\Cheque::findOrFail($paymentVoucher->cheque_id);

            if ($cheque->status !== 'cleared' || !$cheque->clear_journal_id) {
                $message = 'This cheque has not been cleared yet. Only cleared cheques can have duplicate GL transactions fixed.';
                
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 400);
                }
                
                return redirect()->back()->withErrors(['error' => $message]);
            }

            $chequeService = new \App\Services\ChequeService();
            $result = $chequeService->fixDuplicateGlTransactions($cheque);

            $message = "Successfully fixed duplicate GL transactions. Deleted {$result['deleted_count']} duplicate(s). Remaining: {$result['remaining_count']} GL transaction(s).";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'deleted_count' => $result['deleted_count'],
                    'remaining_count' => $result['remaining_count'],
                ]);
            }

            return redirect()
                ->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                ->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error fixing duplicate GL transactions for cheque', [
                'payment_id' => $paymentVoucher->id,
                'cheque_id' => $paymentVoucher->cheque_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = 'Failed to fix duplicate GL transactions: ' . $e->getMessage();

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return redirect()->back()->withErrors(['error' => $message]);
        }
    }

    /**
     * Mark cheque as bounced and reverse original payment.
     *
     * Journal entry (handled by ChequeService):
     *   Dr Bank Account / Cheque Issued
     *       Cr Expense / Payable (reverse original payment)
     */
    public function bounceCheque(Request $request, $encodedId)
    {
        $this->authorize('edit payment voucher');
        
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return $request->wantsJson() 
                ? response()->json(['error' => 'Payment voucher not found.'], 404)
                : redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }

        if ($paymentVoucher->payment_method !== 'cheque' || !$paymentVoucher->cheque_id) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'This payment voucher is not linked to a cheque.'], 400)
                : redirect()->back()->withErrors(['error' => 'This payment voucher is not linked to a cheque.']);
        }

        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $cheque = \App\Models\Cheque::findOrFail($paymentVoucher->cheque_id);

            $chequeService = new \App\Services\ChequeService();
            $chequeService->bounceCheque($cheque, $data['reason'], Auth::id());

            $message = 'Cheque marked as bounced and original payment reversed.';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()
                ->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                ->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error bouncing cheque for payment voucher', [
                'payment_id' => $paymentVoucher->id,
                'cheque_id' => $paymentVoucher->cheque_id,
                'error' => $e->getMessage(),
            ]);

            $message = 'Failed to mark cheque as bounced: ' . $e->getMessage();

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return redirect()->back()->withErrors(['error' => $message]);
        }
    }

    /**
     * Cancel a cheque and reset the payment to pending (journal-level reversal).
     *
     * Journal entry (handled by ChequeService):
     *   Reverse all lines of issue journal.
     */
    public function cancelCheque(Request $request, $encodedId)
    {
        $this->authorize('edit payment voucher');
        
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return $request->wantsJson() 
                ? response()->json(['error' => 'Payment voucher not found.'], 404)
                : redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }

        if ($paymentVoucher->payment_method !== 'cheque' || !$paymentVoucher->cheque_id) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'This payment voucher is not linked to a cheque.'], 400)
                : redirect()->back()->withErrors(['error' => 'This payment voucher is not linked to a cheque.']);
        }

        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $cheque = \App\Models\Cheque::findOrFail($paymentVoucher->cheque_id);

            $chequeService = new \App\Services\ChequeService();
            $chequeService->cancelCheque($cheque, $data['reason'], Auth::id());

            $message = 'Cheque cancelled and original entry reversed.';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()
                ->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                ->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error cancelling cheque for payment voucher', [
                'payment_id' => $paymentVoucher->id,
                'cheque_id' => $paymentVoucher->cheque_id,
                'error' => $e->getMessage(),
            ]);

            $message = 'Failed to cancel cheque: ' . $e->getMessage();

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return redirect()->back()->withErrors(['error' => $message]);
        }
    }

    /**
     * Mark cheque as stale (e.g. after 6 months).
     * This locks the cheque from being reused and allows re-issue.
     */
    public function markChequeStale(Request $request, $encodedId)
    {
        $this->authorize('edit payment voucher');
        
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return $request->wantsJson() 
                ? response()->json(['error' => 'Payment voucher not found.'], 404)
                : redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }

        if ($paymentVoucher->payment_method !== 'cheque' || !$paymentVoucher->cheque_id) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'This payment voucher is not linked to a cheque.'], 400)
                : redirect()->back()->withErrors(['error' => 'This payment voucher is not linked to a cheque.']);
        }

        $days = (int) ($request->input('stale_days') ?? 180);

        try {
            $cheque = \App\Models\Cheque::findOrFail($paymentVoucher->cheque_id);

            $chequeService = new \App\Services\ChequeService();
            $marked = $chequeService->markStale($cheque, $days);

            if (!$marked) {
                $message = 'Cheque is not yet stale based on the configured days.';

                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 400);
                }

                return redirect()->back()->withErrors(['error' => $message]);
            }

            $message = 'Cheque marked as stale successfully.';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()
                ->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                ->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error marking cheque as stale for payment voucher', [
                'payment_id' => $paymentVoucher->id,
                'cheque_id' => $paymentVoucher->cheque_id,
                'error' => $e->getMessage(),
            ]);

            $message = 'Failed to mark cheque as stale: ' . $e->getMessage();

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return redirect()->back()->withErrors(['error' => $message]);
        }
    }

    /**
     * Show approval interface for payment voucher
     */
    public function showApproval($encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }
        
        $user = Auth::user();
        
        // Check if user can approve this payment
        $settings = \App\Models\PaymentVoucherApprovalSetting::where('company_id', $user->company_id)->first();
        
        if (!$settings) {
            return redirect()->back()->withErrors(['error' => 'No approval settings configured.']);
        }

        $currentApproval = $paymentVoucher->currentApproval();
        
        if (!$currentApproval) {
            return redirect()->back()->withErrors(['error' => 'No pending approval found for this payment voucher.']);
        }

        // Check if current user can approve at this level
        if (!$settings->canUserApproveAtLevel($user, $currentApproval->approval_level)) {
            return redirect()->back()->withErrors(['error' => 'You do not have permission to approve this payment voucher.']);
        }

        $paymentVoucher->load(['bankAccount', 'customer', 'supplier', 'user', 'branch', 'paymentItems.chartAccount', 'approvals.approver']);

        return view('accounting.payment-vouchers.approval', compact('paymentVoucher', 'currentApproval', 'settings'));
    }

    /**
     * Approve payment voucher
     */
    public function approve(Request $request, $encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return $request->wantsJson() 
                ? response()->json(['error' => 'Payment voucher not found.'], 404)
                : redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }
        
        $user = Auth::user();
        
        // Check if user can approve this payment
        $settings = \App\Models\PaymentVoucherApprovalSetting::where('company_id', $user->company_id)->first();
        
        if (!$settings) {
            return redirect()->back()->withErrors(['error' => 'No approval settings configured.']);
        }

        $currentApproval = $paymentVoucher->currentApproval();
        
        if (!$currentApproval) {
            return redirect()->back()->withErrors(['error' => 'No pending approval found for this payment voucher.']);
        }

        // Check if current user can approve at this level
        if (!$settings->canUserApproveAtLevel($user, $currentApproval->approval_level)) {
            return redirect()->back()->withErrors(['error' => 'You do not have permission to approve this payment voucher.']);
        }

        $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($paymentVoucher, $currentApproval, $user, $request) {
                // Approve current level
                $currentApproval->approve($request->comments);
                
                // Check if this was the final approval level
                if ($paymentVoucher->isFullyApproved()) {
                    $paymentVoucher->update([
                        'approved' => true,
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                    ]);
                    
                    // Log final approval
                    if (method_exists($paymentVoucher, 'logActivity')) {
                        $beneficiary = $paymentVoucher->supplier ? $paymentVoucher->supplier->name : ($paymentVoucher->customer ? $paymentVoucher->customer->name : 'N/A');
                        $paymentVoucher->logActivity('approve', "Fully Approved Payment Voucher {$paymentVoucher->reference} for {$beneficiary}", [
                            'Payment Reference' => $paymentVoucher->reference,
                            'Beneficiary' => $beneficiary,
                            'Amount' => number_format($paymentVoucher->amount ?? 0, 2),
                            'Payment Date' => $paymentVoucher->date ? $paymentVoucher->date->format('Y-m-d') : 'N/A',
                            'Payment Method' => $paymentVoucher->payment_method ? ucfirst($paymentVoucher->payment_method) : 'N/A',
                            'Approved By' => $user->name,
                            'Approved At' => now()->format('Y-m-d H:i:s')
                        ]);
                    }

                    // Post to GL if not already posted (use Payment model's method so WHT/VAT are correct)
                    $alreadyPosted = \App\Models\GlTransaction::where('transaction_type', 'payment')
                        ->where('transaction_id', $paymentVoucher->id)
                        ->exists();

                    if (!$alreadyPosted) {
                        $paymentVoucher->loadMissing(['bankAccount', 'paymentItems']);
                        $paymentVoucher->createGlTransactions();
                    }
                }
            });

            // Check if this is an AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment voucher approved successfully.',
                    'redirect' => route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                ]);
            }

            return redirect()->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                ->with('success', 'Payment voucher approved successfully.');
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Check if it's a reconciliation error
            if (str_contains($errorMessage, 'completed reconciliation period')) {
                $errorMessage = 'Cannot post: Payment is in a completed reconciliation period';
            } else {
                $errorMessage = 'Failed to approve payment voucher: ' . $errorMessage;
            }
            
            // Check if this is an AJAX request
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'swal' => true,
                    'icon' => 'error'
                ], 422);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Reject payment voucher
     */
    public function reject(Request $request, $encodedId)
    {
        $paymentVoucher = $this->resolvePaymentVoucher($encodedId);
        if (!$paymentVoucher) {
            return $request->wantsJson() 
                ? response()->json(['error' => 'Payment voucher not found.'], 404)
                : redirect()->route('accounting.payment-vouchers.index')->withErrors(['Payment voucher not found.']);
        }
        
        $user = Auth::user();
        
        // Check if user can approve this payment
        $settings = \App\Models\PaymentVoucherApprovalSetting::where('company_id', $user->company_id)->first();
        
        if (!$settings) {
            return redirect()->back()->withErrors(['error' => 'No approval settings configured.']);
        }

        $currentApproval = $paymentVoucher->currentApproval();
        
        if (!$currentApproval) {
            return redirect()->back()->withErrors(['error' => 'No pending approval found for this payment voucher.']);
        }

        // Check if current user can approve at this level
        if (!$settings->canUserApproveAtLevel($user, $currentApproval->approval_level)) {
            return redirect()->back()->withErrors(['error' => 'You do not have permission to reject this payment voucher.']);
        }

        $request->validate([
            'comments' => 'required|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($paymentVoucher, $currentApproval, $request, $user) {
                // Reject current level
                $currentApproval->reject($request->comments);
                
                // Log rejection
                if (method_exists($paymentVoucher, 'logActivity')) {
                    $beneficiary = $paymentVoucher->supplier ? $paymentVoucher->supplier->name : ($paymentVoucher->customer ? $paymentVoucher->customer->name : 'N/A');
                    $paymentVoucher->logActivity('reject', "Rejected Payment Voucher {$paymentVoucher->reference} for {$beneficiary} at Level {$currentApproval->approval_level}", [
                        'Payment Reference' => $paymentVoucher->reference,
                        'Beneficiary' => $beneficiary,
                        'Amount' => number_format($paymentVoucher->amount ?? 0, 2),
                        'Payment Date' => $paymentVoucher->date ? $paymentVoucher->date->format('Y-m-d') : 'N/A',
                        'Rejection Level' => $currentApproval->approval_level,
                        'Rejected By' => $user->name,
                        'Rejection Reason' => $request->comments ?? 'No reason provided',
                        'Rejected At' => now()->format('Y-m-d H:i:s')
                    ]);
                }
                
                // Reject all remaining pending approvals
                $paymentVoucher->pendingApprovals()->update([
                    'status' => 'rejected',
                    'comments' => 'Rejected by higher level approval',
                    'approved_at' => now(),
                ]);
            });

            // Check if this is an AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment voucher rejected successfully.',
                    'redirect' => route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                ]);
            }

            return redirect()->route('accounting.payment-vouchers.show', $paymentVoucher->hash_id)
                ->with('success', 'Payment voucher rejected successfully.');
        } catch (\Exception $e) {
            // Check if this is an AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject payment voucher: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withErrors(['error' => 'Failed to reject payment voucher: ' . $e->getMessage()]);
        }
    }

    /**
     * Show pending approvals for current user
     */
    public function pendingApprovals()
    {
        $user = Auth::user();
        
        $pendingApprovals = \App\Models\PaymentVoucherApproval::with(['payment.bankAccount', 'payment.user'])
            ->whereHas('payment.user', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->where(function ($query) use ($user) {
                $query->where('approver_id', $user->id)
                      ->orWhereIn('approver_name', $user->getRoleNames());
            })
            ->pending()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('accounting.payment-vouchers.pending-approvals', compact('pendingApprovals'));
    }

    /**
     * Get customer cash deposits for payment
     */
    public function getCustomerCashDeposits($customerId)
    {
        try {
            $decodedCustomerId = HashIdHelper::decode($customerId);

            if (!$decodedCustomerId) {
                \Log::warning('Invalid customer ID in getCustomerCashDeposits', ['encoded_id' => $customerId]);
                return response()->json(['error' => 'Invalid customer ID'], 422);
            }

            $customer = Customer::findOrFail($decodedCustomerId);

            // Check if customer has any actual cash deposit records
            $hasCashDeposits = $customer->cashDeposits()->exists() || $customer->cashCollaterals()->exists();

            // Get customer's cash deposit balance (only actual cash deposits)
            $cashDepositBalance = $customer->cash_deposit_balance ?? 0;

            // Only return account option if customer has actual deposit records OR has a positive balance
            // If customer has never had any deposits, return empty array
            $data = [];
            if ($hasCashDeposits || $cashDepositBalance > 0) {
                $data = [
                    [
                        'id' => 'customer_balance',
                        'balance_text' => "Cash Deposits: {$customer->name} (ID: {$customer->customerNo}) - Available: " . number_format($cashDepositBalance, 2) . " TSh"
                    ]
                ];
            }

            \Log::info('Customer cash deposits retrieved', [
                'customer_id' => $decodedCustomerId,
                'customer_name' => $customer->name,
                'has_cash_deposits' => $hasCashDeposits,
                'balance' => $cashDepositBalance,
                'data_count' => count($data)
            ]);

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            \Log::error('Error in getCustomerCashDeposits', [
                'customer_id' => $customerId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to load customer cash deposits: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get unpaid supplier invoices for payment
     */
    public function getSupplierInvoices($supplierId)
    {
        try {
            $supplier = Supplier::findOrFail($supplierId);

            // Get unpaid invoices (where total_amount > total_paid)
            $invoices = \App\Models\Purchase\PurchaseInvoice::where('supplier_id', $supplierId)
                ->where('company_id', Auth::user()->company_id)
                ->whereColumn('total_amount', '>', DB::raw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE reference_type = "purchase_invoice" AND reference_number = purchase_invoices.invoice_number AND supplier_id = purchase_invoices.supplier_id)'))
                ->orderBy('invoice_date', 'asc')
                ->orderBy('invoice_number', 'asc')
                ->get()
                ->map(function ($invoice) {
                    $totalPaid = (float) $invoice->total_paid;
                    $outstanding = (float) $invoice->outstanding_amount;
                    
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                        'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                        'total_amount' => (float) $invoice->total_amount,
                        'total_paid' => $totalPaid,
                        'outstanding_amount' => $outstanding,
                        'currency' => $invoice->currency ?? 'TZS',
                        'status' => $invoice->status,
                    ];
                });

            return response()->json(['data' => $invoices]);
        } catch (\Exception $e) {
            \Log::error('Error in getSupplierInvoices', [
                'supplier_id' => $supplierId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to load supplier invoices: ' . $e->getMessage()], 500);
        }
    }
}
