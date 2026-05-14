<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashDepositAccount;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Company;


use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class CustomerController extends Controller
{
    // Display all customers
    // Search customers for POS
    public function search(Request $request)
    {
        $term = $request->get('term', '');
        $branchId = session('branch_id') ?? auth()->user()->branch_id;
        
        $customers = Customer::where('branch_id', $branchId)
            ->where(function($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%')
                      ->orWhere('phone', 'like', '%' . $term . '%')
                      ->orWhere('email', 'like', '%' . $term . '%');
            })
            ->limit(10)
            ->get(['id', 'name', 'phone', 'email']);
            
        return response()->json($customers);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $branchId = session('branch_id') ?? auth()->user()->branch_id;
            $customers = Customer::with(['branch', 'company'])
                ->where('branch_id', $branchId)
                ->latest();

            return datatables()->of($customers)
                ->addColumn('actions', function ($customer) {
                    $actions = '';
                    
                    if (auth()->user()->can('view customer profile')) {
                        $actions .= '<a href="' . route('customers.show', Hashids::encode($customer->id)) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    }
                    
                    if (auth()->user()->can('edit customer')) {
                        $actions .= '<a href="' . route('customers.edit', Hashids::encode($customer->id)) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    }
                    
                    if (auth()->user()->can('delete customer')) {
                        $actions .= '<form action="' . route('customers.destroy', Hashids::encode($customer->id)) . '" method="POST" class="d-inline-block delete-form">';
                        $actions .= csrf_field() . method_field('DELETE');
                        $actions .= '<button class="btn btn-sm btn-outline-danger" data-name="' . $customer->name . '"><i class="bx bx-trash"></i></button>';
                        $actions .= '</form>';
                    }
                    
                    return $actions;
                })
                ->addColumn('customer_avatar', function ($customer) {
                    return '<div class="d-flex align-items-center">
                        <div class="avatar avatar-sm bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center shadow" style="width:36px; height:36px;">
                            <span class="avatar-title text-white fw-bold" style="font-size:1.25rem;">' . strtoupper(substr($customer->name, 0, 1)) . '</span>
                        </div>
                        <div class="fw-bold">' . $customer->name . '</div>
                    </div>';
                })
                ->addColumn('formatted_credit_limit', function ($customer) {
                    return $customer->credit_limit ? number_format($customer->credit_limit, 2) : 'N/A';
                })
                ->addColumn('status_badge', function ($customer) {
                    $badgeClass = match($customer->status) {
                        'active' => 'bg-success',
                        'inactive' => 'bg-secondary',
                        'suspended' => 'bg-warning',
                        default => 'bg-secondary'
                    };
                    return '<span class="badge ' . $badgeClass . '">' . ucfirst($customer->status) . '</span>';
                })
                ->addColumn('formatted_phone', function ($customer) {
                    return $customer->phone ? '<a href="tel:' . $customer->phone . '">' . $customer->phone . '</a>' : 'N/A';
                })
                ->editColumn('email', function ($customer) {
                    return $customer->email ? '<a href="mailto:' . $customer->email . '">' . $customer->email . '</a>' : 'N/A';
                })
                ->editColumn('branch.name', function ($customer) {
                    return $customer->branch ? $customer->branch->name : 'N/A';
                })
                ->editColumn('created_at', function ($customer) {
                    return format_date($customer->created_at, 'Y-m-d');
                })
                ->rawColumns(['actions', 'customer_avatar', 'status_badge', 'formatted_phone', 'email'])
                ->make(true);
        }

        $branchId = session('branch_id') ?? auth()->user()->branch_id;
        $companyId = auth()->user()->company_id;
        
        // Base query for customers in this branch
        $baseQuery = Customer::where('company_id', $companyId)
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            });
        
        // 1. Total Registered Customers (active + inactive)
        $totalRegisteredCustomers = (clone $baseQuery)->count();
        
        // 2. Active Customers - customers with at least one transaction, invoice, or engagement
        $activeCustomerIds = DB::table('customers')
            ->where('company_id', $companyId)
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where(function($query) {
                // Has sales invoices
                $query->whereExists(function($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('sales_invoices')
                        ->whereColumn('sales_invoices.customer_id', 'customers.id')
                        ->where('sales_invoices.status', '!=', 'cancelled');
                })
                // Or has sales orders
                ->orWhereExists(function($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('sales_orders')
                        ->whereColumn('sales_orders.customer_id', 'customers.id');
                })
                // Or has payments
                ->orWhereExists(function($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('payments')
                        ->whereColumn('payments.customer_id', 'customers.id');
                })
                // Or has receipts
                ->orWhereExists(function($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('receipts')
                        ->whereColumn('receipts.payee_id', 'customers.id')
                        ->where('receipts.payee_type', 'customer');
                })
                // Or has GL transactions
                ->orWhereExists(function($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('gl_transactions')
                        ->whereColumn('gl_transactions.customer_id', 'customers.id');
                });
            })
            ->pluck('id');
        
        $activeCustomers = count($activeCustomerIds);
        
        // 3. Dormant Customers - no activity in last 3-6 months (regardless of past activity)
        $sixMonthsAgo = now()->subMonths(6);
        $threeMonthsAgo = now()->subMonths(3);
        
        $dormantCustomerIds = DB::table('customers')
            ->where('company_id', $companyId)
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where(function($query) use ($sixMonthsAgo) {
                // No invoices in last 6 months
                $query->whereNotExists(function($subQuery) use ($sixMonthsAgo) {
                    $subQuery->select(DB::raw(1))
                        ->from('sales_invoices')
                        ->whereColumn('sales_invoices.customer_id', 'customers.id')
                        ->where('sales_invoices.status', '!=', 'cancelled')
                        ->where('sales_invoices.created_at', '>=', $sixMonthsAgo);
                })
                // No orders in last 6 months
                ->whereNotExists(function($subQuery) use ($sixMonthsAgo) {
                    $subQuery->select(DB::raw(1))
                        ->from('sales_orders')
                        ->whereColumn('sales_orders.customer_id', 'customers.id')
                        ->where('sales_orders.created_at', '>=', $sixMonthsAgo);
                })
                // No payments in last 6 months
                ->whereNotExists(function($subQuery) use ($sixMonthsAgo) {
                    $subQuery->select(DB::raw(1))
                        ->from('payments')
                        ->whereColumn('payments.customer_id', 'customers.id')
                        ->where('payments.date', '>=', $sixMonthsAgo);
                })
                // No receipts in last 6 months
                ->whereNotExists(function($subQuery) use ($sixMonthsAgo) {
                    $subQuery->select(DB::raw(1))
                        ->from('receipts')
                        ->whereColumn('receipts.payee_id', 'customers.id')
                        ->where('receipts.payee_type', 'customer')
                        ->where('receipts.date', '>=', $sixMonthsAgo);
                })
                // No GL transactions in last 6 months
                ->whereNotExists(function($subQuery) use ($sixMonthsAgo) {
                    $subQuery->select(DB::raw(1))
                        ->from('gl_transactions')
                        ->whereColumn('gl_transactions.customer_id', 'customers.id')
                        ->where('gl_transactions.date', '>=', $sixMonthsAgo);
                });
            })
            ->pluck('id');
        
        $dormantCustomers = count($dormantCustomerIds);
        
        // 4. New Customers This Month
        $startOfMonth = now()->startOfMonth();
        $newCustomersThisMonth = (clone $baseQuery)
            ->where('created_at', '>=', $startOfMonth)
            ->count();
        
        // Get previous month count for comparison
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();
        $newCustomersLastMonth = (clone $baseQuery)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();
        
        $newCustomersIncrease = $newCustomersLastMonth > 0 
            ? (($newCustomersThisMonth - $newCustomersLastMonth) / $newCustomersLastMonth) * 100 
            : ($newCustomersThisMonth > 0 ? 100 : 0);
        
        return view('customers.index', compact(
            'totalRegisteredCustomers',
            'activeCustomers',
            'dormantCustomers',
            'newCustomersThisMonth',
            'newCustomersIncrease'
        ));
    }





    // Show form to create a new customer
    public function create()
    {
        $branches = Branch::all();
        $companies = Company::all();
        $registrars = User::all();
        $collateralTypes = CashDepositAccount::where('is_active', 1)->get();

        return view('customers.create', compact('branches', 'companies', 'registrars', 'collateralTypes'));
    }

    // Store a new customer
    public function store(Request $request)
    {
        // Normalize phone to 255XXXXXXXXX if helper available
        if ($request->filled('phone') && function_exists('normalize_phone_number')) {
            $request->merge(['phone' => normalize_phone_number($request->input('phone'))]);
        }
        
        // Resolve company and branch for validation
        $companyId = auth()->user()->company_id;
        $resolvedBranchId = auth()->user()->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        
        // Basic validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => [
                'required',
                'string',
                'size:12',
                'regex:/^[0-9]+$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('customers', 'email')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })
            ],
            'status' => 'required|in:active,inactive,suspended',
            'credit_limit' => 'nullable|numeric|min:0',
            'company_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:100',
            'tin_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'send_welcome_sms' => 'nullable|boolean',
        ];

        $validated = $request->validate($rules);

        // Prepare customer data
        $data = $request->except(['customerNo']);
        $password = 12345;
        $date = now()->toDateString();

        $data['customerNo'] = 100000 + (\App\Models\Customer::max('id') ?? 0) + 1;
        $data['password'] = Hash::make($password);
        // Resolve branch reliably (user -> session -> helper)
        $resolvedBranchId = auth()->user()->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        if (!$resolvedBranchId) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Active branch is not set. Please select a branch and try again.'], 422);
            }
            return back()->withInput()->withErrors(['error' => 'Active branch is not set. Please select a branch and try again.']);
        }
        $data['branch_id'] = $resolvedBranchId;
        $data['company_id'] = auth()->user()->company_id;
        
        // Ensure branch_id is not null
        if (!$data['branch_id']) {
            return back()->withInput()->with('error', 'No branch selected. Please select a branch first.');
        }
        
        $data['status'] = $request->status ?? 'active'; // Use status from request or default to active



        DB::beginTransaction();
        try {
            $customer = \App\Models\Customer::create($data);

 
            // Send welcome SMS if requested
            if ($request->has('send_welcome_sms') && $request->send_welcome_sms) {
                try {
                    $this->sendWelcomeSMS($customer);
                } catch (\Exception $e) {
                    // Log the SMS error but don't fail the customer creation
                    \Log::error('Failed to send welcome SMS: ' . $e->getMessage());
                }
            }

            DB::commit();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Customer created successfully.',
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                    ],
                ], 201);
            }
            return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            // Handle duplicate entry errors
            if ($e->getCode() == 23000) {
                $errorMessage = $e->getMessage();
                
                // Check for duplicate email
                if (strpos($errorMessage, 'customers_email_unique') !== false || (strpos($errorMessage, 'Duplicate entry') !== false && strpos($errorMessage, 'email') !== false)) {
                    $email = $request->input('email');
                    $friendlyMessage = "A customer with the email address '{$email}' already exists. Please use a different email address or leave it blank.";
                    
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $friendlyMessage,
                            'errors' => ['email' => [$friendlyMessage]]
                        ], 422);
                    }
                    return back()->withInput()->withErrors(['email' => $friendlyMessage]);
                }
                
                // Generic duplicate entry message
                $friendlyMessage = "This customer already exists in the system. Please check the email and try again.";
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $friendlyMessage], 422);
                }
                return back()->withInput()->with('error', $friendlyMessage);
            }
            
            // Other database errors
            \Log::error('Customer creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to create customer. Please try again or contact support if the problem persists.'], 500);
            }
            return back()->withInput()->with('error', 'Failed to create customer. Please try again or contact support if the problem persists.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Customer creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to create customer. Please try again or contact support if the problem persists.'], 500);
            }
            return back()->withInput()->with('error', 'Failed to create customer. Please try again or contact support if the problem persists.');
        }
    }


    // Display one customer
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $customer = Customer::with([
            'collaterals.type', 
            'salesOrders',
            'salesProformas',
            'salesInvoices',
            'salesDeliveries',
            'payments',
            'receipts',
            'journals',
            'glTransactions'
        ])->findOrFail($id);

        // Calculate correct cash deposit balance using Receipt-based system (same as DataTable)
        $correctCashDepositBalance = 0;
        
        // Find cash collaterals (not cash deposits) that have transactions
        $cashCollaterals = \App\Models\CashCollateral::where('customer_id', $customer->id)->get();
        
        foreach ($cashCollaterals as $collateral) {
            // Get deposit transactions (receipts)
            $deposits = \App\Models\Receipt::where('reference', $collateral->id)
                ->where('reference_type', 'Deposit')
                ->sum('amount');
            
            // Get withdrawal transactions (payments)
            $withdrawals = \App\Models\Payment::where('reference', $collateral->id)
                ->where('reference_type', 'Withdrawal')
                ->sum('amount');
            
            // Get journal-based cash deposit payments (new system) - includes both invoice and cash sale payments
            $journalWithdrawals = \App\Models\Journal::where('customer_id', $customer->id)
                ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
                ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
                ->where('journal_items.chart_account_id', 28) // Cash Deposits account
                ->where('journal_items.nature', 'debit')
                ->sum('journal_items.amount');
            
            // Calculate balance for this collateral and add to total
            $collateralBalance = $deposits - ($withdrawals + $journalWithdrawals);
            $correctCashDepositBalance += $collateralBalance;
        }

        // If it's an AJAX request, return JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'customer' => $customer
            ]);
        }

        return view('customers.show', compact('customer', 'correctCashDepositBalance'));
    }

    // DataTable for customer cash deposits
    public function cashDepositsDataTable($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $customer = Customer::findOrFail($id);
        $deposits = $customer->cashCollaterals()->with('type');

        return datatables()->of($deposits)
            ->addColumn('type_name', function ($deposit) {
                return $deposit->type->name ?? 'N/A';
            })
            ->addColumn('formatted_amount', function ($deposit) {
                // Calculate actual current balance instead of using static amount field
                $transactions = collect();
                
                // Get deposit transactions (receipts)
                $deposits = \App\Models\Receipt::where('reference', $deposit->id)
                    ->where('reference_type', 'Deposit')
                    ->sum('amount');
                
                // Get withdrawal transactions (payments)
                $withdrawals = \App\Models\Payment::where('reference', $deposit->id)
                    ->where('reference_type', 'Withdrawal')
                    ->sum('amount');
                
                // Get journal-based cash deposit payments (new system)
                $journalWithdrawals = \App\Models\Journal::where('customer_id', $deposit->customer_id)
                    ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
                    ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
                    ->where('journal_items.chart_account_id', 28) // Cash Deposits account
                    ->where('journal_items.nature', 'debit')
                    ->sum('journal_items.amount');
                
                // Calculate current balance
                $currentBalance = $deposits - ($withdrawals + $journalWithdrawals);
                
                return number_format($currentBalance, 2);
            })
            ->addColumn('formatted_date', function ($deposit) {
                return format_date($deposit->created_at, 'M d, Y');
            })
            ->addColumn('actions', function ($deposit) {
                $actions = '<div class="btn-group" role="group">';
                
                // View button
                if (auth()->user()->can('view cash deposits')) {
                    $actions .= '<a href="' . route('cash_collaterals.show', Hashids::encode($deposit->id)) . '" class="btn btn-sm btn-outline-info" title="View Details">
                        <i class="bx bx-show"></i>
                    </a>';
                }
                
                // Deposit button  
                if (auth()->user()->can('deposit cash deposit')) {
                    $actions .= '<a href="' . route('cash_collaterals.deposit', Hashids::encode($deposit->id)) . '" class="btn btn-sm btn-outline-success" title="Make Deposit">
                        <i class="bx bx-plus-circle"></i>
                    </a>';
                }
                
                // Withdraw button
                if (auth()->user()->can('withdraw cash deposit')) {
                    $actions .= '<a href="' . route('cash_collaterals.withdraw', Hashids::encode($deposit->id)) . '" class="btn btn-sm btn-outline-warning" title="Make Withdrawal">
                        <i class="bx bx-minus-circle"></i>
                    </a>';
                }
                
                // Print statement button
                $actions .= '<a href="' . route('cash_collaterals.statement-pdf', Hashids::encode($deposit->id)) . '" class="btn btn-sm btn-outline-primary" title="Print Statement" target="_blank">
                    <i class="bx bx-printer"></i>
                </a>';
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    // API endpoint to get customer's cash deposits
    public function getCashDeposits($id)
    {
        $customer = Customer::with('cashDeposits.type')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'cash_deposits' => $customer->cashDeposits
        ]);
    }

    // DataTable for customer unpaid invoices
    public function unpaidInvoicesDataTable($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $customer = Customer::findOrFail($id);
        $invoices = $customer->salesInvoices()->where('status', '!=', 'paid');

        return datatables()->of($invoices)
            ->addIndexColumn()
            ->addColumn('invoice_number', function ($invoice) {
                return $invoice->invoice_number;
            })
            ->addColumn('formatted_date', function ($invoice) {
                return format_date($invoice->invoice_date, 'M d, Y');
            })
            ->addColumn('formatted_total', function ($invoice) {
                return number_format($invoice->total_amount, 2);
            })
            ->addColumn('formatted_balance', function ($invoice) {
                return number_format($invoice->balance_due, 2);
            })
            ->addColumn('status_badge', function ($invoice) {
                $statusClass = $invoice->status == 'paid' ? 'success' : ($invoice->status == 'partial' ? 'warning' : 'danger');
                return '<span class="badge bg-' . $statusClass . '">' . ucfirst($invoice->status) . '</span>';
            })
            ->addColumn('actions', function ($invoice) {
                $actions = '<a href="' . route('sales.invoices.show', Hashids::encode($invoice->id)) . '" class="btn btn-sm btn-info me-1">View</a>';
                
                if ($invoice->balance_due > 0) {
                    $actions .= '<a href="' . route('sales.invoices.payment-form', Hashids::encode($invoice->id)) . '" class="btn btn-sm btn-success me-1">Record Payment</a>';
                }
                
                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    // Show form to edit a customer
    public function edit($encodedId)
    {
        $id = \Vinkla\Hashids\Facades\Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        $customer = Customer::findOrFail($id);
        $branchId = session('branch_id') ?? auth()->user()->branch_id;
        $collateralTypes = CashDepositAccount::where('is_active', 1)->get();
        $branches = Branch::all();
        $companies = Company::all();
        $registrars = User::all();
        return view('customers.edit', compact('branches', 'companies', 'registrars', 'collateralTypes', 'customer'));
    }

    // Update customer data
    public function update(Request $request, $encodedId)
    {
        $id = \Vinkla\Hashids\Facades\Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        $customer = Customer::findOrFail($id);
        
        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => [
                'required',
                'string',
                'max:20',
                \Illuminate\Validation\Rule::unique('customers', 'phone')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })->ignore($id)
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('customers', 'email')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })->ignore($id)
            ],
            'status' => 'required|in:active,inactive,suspended',
            'has_cash_deposit' => 'nullable|boolean',
            'deposit_account_id' => 'nullable|exists:cash_deposit_accounts,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'company_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:100',
            'tin_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',

        ]);

        $data = $request->except(['customerNo', 'deposit_account_id']);

        // Set these from logged-in user
        $data['branch_id'] = session('branch_id') ?? auth()->user()->branch_id;
        $data['company_id'] = auth()->user()->company_id;
        $data['registrar'] = auth()->id();
        $data['has_cash_deposit'] = $request->has('has_cash_deposit') ? true : false; // Set boolean value



        DB::beginTransaction();
        try {
            $customer->update($data);

            // Handle cash collateral
            if ($request->has('has_cash_deposit') && $request->has('deposit_account_id') && $request->deposit_account_id) {
                // Check if collateral already exists
                $existingDeposit = \App\Models\CashDeposit::where('customer_id', $customer->id)->first();

                if ($existingDeposit) {
                    $existingDeposit->update([
                        'type_id' => $request->input('deposit_account_id'),
                    ]);
                } else {
                    \App\Models\CashDeposit::create([
                        'customer_id' => $customer->id,
                        'type_id' => $request->input('deposit_account_id'),
                        'amount' => 0,
                        'branch_id' => session('branch_id') ?? auth()->user()->branch_id,
                        'company_id' => auth()->user()->company_id,
                    ]);
                }
            } else {
                // If not checked, remove existing collateral
                \App\Models\CashDeposit::where('customer_id', $customer->id)->delete();
            }


            DB::commit();
            return redirect()->route('customers.show', $encodedId)->with('success', 'Customer updated successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            // Handle duplicate entry errors
            if ($e->getCode() == 23000) {
                $errorMessage = $e->getMessage();
                
                // Check for duplicate email
                if (strpos($errorMessage, 'customers_email_unique') !== false || (strpos($errorMessage, 'Duplicate entry') !== false && strpos($errorMessage, 'email') !== false)) {
                    $email = $request->input('email');
                    $friendlyMessage = "A customer with the email address '{$email}' already exists. Please use a different email address or leave it blank.";
                    
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $friendlyMessage,
                            'errors' => ['email' => [$friendlyMessage]]
                        ], 422);
                    }
                    return back()->withInput()->withErrors(['email' => $friendlyMessage]);
                }
                
                // Check for duplicate phone (if phone is unique)
                if (strpos($errorMessage, 'customers_phone_unique') !== false || (strpos($errorMessage, 'Duplicate entry') !== false && strpos($errorMessage, 'phone') !== false)) {
                    $phone = $request->input('phone');
                    $friendlyMessage = "A customer with the phone number '{$phone}' already exists. Please use a different phone number.";
                    
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $friendlyMessage,
                            'errors' => ['phone' => [$friendlyMessage]]
                        ], 422);
                    }
                    return back()->withInput()->withErrors(['phone' => $friendlyMessage]);
                }
                
                // Generic duplicate entry message
                $friendlyMessage = "This customer information conflicts with an existing customer. Please check the email or phone number and try again.";
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $friendlyMessage], 422);
                }
                return back()->withInput()->with('error', $friendlyMessage);
            }
            
            // Other database errors
            \Log::error('Customer update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update customer. Please try again or contact support if the problem persists.'], 500);
            }
            return back()->withInput()->with('error', 'Failed to update customer. Please try again or contact support if the problem persists.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Customer update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update customer. Please try again or contact support if the problem persists.'], 500);
            }
            return back()->withInput()->with('error', 'Failed to update customer. Please try again or contact support if the problem persists.');
        }
    }

    // Delete customer
    public function destroy($encodedId)
    {
        $decoded = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$decoded) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Invalid customer ID'], 422);
            }
            return redirect()->route('customers.index')->with('error', 'Invalid customer ID');
        }

        try {
            $customer = Customer::findOrFail($decoded);
            
            // Check if customer has any transactions that would prevent deletion
            $hasTransactions = false;
            $blockingReasons = [];

            if ($customer->salesInvoices()->exists()) {
                $hasTransactions = true;
                $blockingReasons[] = 'sales invoices';
            }
            if ($customer->salesOrders()->exists()) {
                $hasTransactions = true;
                $blockingReasons[] = 'sales orders';
            }
            if ($customer->salesProformas()->exists()) {
                $hasTransactions = true;
                $blockingReasons[] = 'sales proformas';
            }
            if ($customer->salesDeliveries()->exists()) {
                $hasTransactions = true;
                $blockingReasons[] = 'deliveries';
            }
            if ($customer->payments()->exists()) {
                $hasTransactions = true;
                $blockingReasons[] = 'payments';
            }
            if ($customer->receipts()->exists()) {
                $hasTransactions = true;
                $blockingReasons[] = 'receipts';
            }
            if ($customer->journals()->exists()) {
                $hasTransactions = true;
                $blockingReasons[] = 'journal entries';
            }
            if ($customer->glTransactions()->exists()) {
                $hasTransactions = true;
                $blockingReasons[] = 'general ledger transactions';
            }
            if ($customer->cashDeposits()->exists()) {
                $hasTransactions = true;
                $blockingReasons[] = 'cash deposits';
            }

            if ($hasTransactions) {
                $reasonText = implode(', ', $blockingReasons);
                $message = "Cannot delete customer '{$customer->name}' because they have {$reasonText}. Please deactivate the customer instead.";
                
                if (request()->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return back()->with('error', $message);
            }

            $customer->delete();
            
            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Customer deleted successfully.']);
            }
            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
            
        } catch (\Exception $e) {
            $message = 'Failed to delete customer: ' . $e->getMessage();
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    // Show bulk upload form
    public function bulkUpload()
    {
        $collateralTypes = CashDepositAccount::where('is_active', 1)->get();
        return view('customers.bulk-upload', compact('collateralTypes'));
    }

    // Process bulk upload
    public function bulkUploadStore(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
            'has_cash_deposit' => 'nullable|boolean',
            'deposit_account_id' => 'nullable|exists:cash_deposit_accounts,id',
        ]);

        if ($request->has('has_cash_deposit') && !$request->deposit_account_id) {
            return back()->withErrors(['deposit_account_id' => 'Please select a collateral type when applying cash collateral.']);
        }

        try {
            $file = $request->file('csv_file');
            $path = $file->getRealPath();

            // Read CSV file with proper UTF-8 encoding handling
            $csvContent = file_get_contents($path);
            // Remove BOM if present
            $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);
            // Convert to UTF-8 if not already
            if (!mb_check_encoding($csvContent, 'UTF-8')) {
                $csvContent = mb_convert_encoding($csvContent, 'UTF-8', 'auto');
            }
            
            // Helper function to clean text fields (remove non-breaking spaces, BOM, etc.)
            $cleanText = function ($text) {
                if (empty($text)) {
                    return '';
                }
                // Remove BOM and other invisible characters
                $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
                // Replace non-breaking spaces (\xA0) with regular spaces
                $text = str_replace(["\xC2\xA0", "\xA0"], ' ', $text);
                // Remove other problematic characters
                $text = str_replace(['﻿', ' ', '　'], ' ', $text);
                // Trim and normalize whitespace
                $text = trim($text);
                $text = preg_replace('/\s+/', ' ', $text);
                return $text;
            };
            
            // Parse CSV content with proper handling
            $lines = preg_split('/\r\n|\r|\n/', $csvContent);
            $data = [];
            foreach ($lines as $line) {
                // Skip empty lines
                if (trim($line) === '') {
                    continue;
                }
                $parsed = str_getcsv($line);
                if (!empty($parsed)) {
                    $data[] = $parsed;
                }
            }
            
            if (empty($data)) {
                return back()->withErrors(['csv_file' => 'CSV file appears to be empty or invalid.']);
            }
            
            $header = array_shift($data); // Remove header row
            $header = array_map(function($h) use ($cleanText) {
                return mb_strtolower($cleanText($h ?? ''));
            }, $header);
            
            // Remove empty header columns
            $header = array_filter($header, function($h) {
                return !empty($h);
            });
            $header = array_values($header); // Re-index array

            // Validate CSV structure
            $requiredColumns = ['name', 'phone'];
            $missingColumns = array_diff($requiredColumns, $header);

            if (!empty($missingColumns)) {
                return back()->withErrors(['csv_file' => 'Missing required columns: ' . implode(', ', $missingColumns)]);
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $warnings = [];

            DB::beginTransaction();

            foreach ($data as $rowIndex => $row) {
                try {
                    // Skip completely empty rows (all cells are empty or whitespace)
                    $hasData = false;
                    foreach ($row as $cell) {
                        if (!empty(trim($cell ?? ''))) {
                            $hasData = true;
                            break;
                        }
                    }
                    if (!$hasData) {
                        continue; // Skip empty rows
                    }

                    // Ensure row has same number of columns as header
                    while (count($row) < count($header)) {
                        $row[] = '';
                    }
                    $row = array_slice($row, 0, count($header));

                    $rowData = array_combine($header, $row);

                    // Clean and validate required fields
                    $name = $cleanText($rowData['name'] ?? '');
                    $phone = $cleanText($rowData['phone'] ?? '');

                    // Validate required fields after cleaning with specific error messages
                    $missingFields = [];
                    if (empty($name)) {
                        $missingFields[] = 'name';
                    }
                    if (empty($phone)) {
                        $missingFields[] = 'phone';
                    }

                    if (!empty($missingFields)) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required field(s): " . implode(', ', $missingFields) . 
                                   (empty($name) && empty($phone) ? " (row appears to be empty)" : "");
                        $errorCount++;
                        continue;
                    }

                    // Create customer data
                    $customerData = [
                        'name' => $name,
                        'phone' => $phone,
                        'description' => $cleanText($rowData['description'] ?? ''),
                        'customerNo' => 100000 + (Customer::max('id') ?? 0) + 1,
                        'branch_id' => session('branch_id') ?? auth()->user()->branch_id,
                        'company_id' => auth()->user()->company_id,
                        'registrar' => auth()->id(),
                        'has_cash_deposit' => $request->has('has_cash_deposit'),
                        'status' => 'active', // Set default status
                    ];

                    // Optional fields
                    if (isset($rowData['credit_limit']) && is_numeric($rowData['credit_limit'])) {
                        $customerData['credit_limit'] = (float) $rowData['credit_limit'];
                    }

                    $customer = Customer::create($customerData);

                    // Add cash collateral if selected
                    if ($request->has('has_cash_deposit') && $request->deposit_account_id) {
                        \App\Models\CashDeposit::create([
                            'customer_id' => $customer->id,
                            'type_id' => $request->deposit_account_id,
                            'amount' => 0,
                            'branch_id' => session('branch_id') ?? auth()->user()->branch_id,
                            'company_id' => auth()->user()->company_id,
                        ]);
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    // Check if it's a character encoding error
                    if (strpos($errorMessage, 'Incorrect string value') !== false || strpos($errorMessage, '1366') !== false) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Character encoding error in name field. Please ensure the CSV file is saved as UTF-8.";
                        $warnings[] = "Row " . ($rowIndex + 2) . ": " . $errorMessage;
                    } else {
                        $errors[] = "Row " . ($rowIndex + 2) . ": " . $errorMessage;
                    }
                    $errorCount++;
                }
            }

            if ($errorCount > 0) {
                DB::rollBack();
                $errorMessages = ['csv_file' => 'Upload completed with errors. ' . $errorCount . ' rows failed.'];
                if (!empty($warnings)) {
                    $errorMessages['warnings'] = $warnings;
                }
                return back()->withErrors($errorMessages)->with('upload_errors', $errors);
            }

            DB::commit();

            $message = "Successfully uploaded {$successCount} customers.";
            if ($request->has('has_cash_deposit')) {
                $message .= " Cash collateral applied to all customers.";
            }

            return redirect()->route('customers.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['csv_file' => 'Failed to process CSV file: ' . $e->getMessage()]);
        }
    }

    // Download sample CSV
    public function downloadSample()
    {
        $filename = 'customer_bulk_upload_sample.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'name',
                'phone',
                'description',
                'credit_limit'
            ]);

            // Add sample data
            fputcsv($file, [
                'John Doe',
                '0712345678',
                'Sample customer',
                '500000.00'
            ]);

            fputcsv($file, [
                'Jane Smith',
                '0723456789',
                'Another sample',
                '0'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Format phone number for SMS sending
     * If starts with 0, remove 0 and add 255
     * If starts with +255, remove +
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any spaces, dashes, or other characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If starts with 0, remove 0 and add 255
        if (preg_match('/^0/', $phone)) {
            $phone = '255' . substr($phone, 1);
        }
        
        // If starts with +255, remove +
        if (preg_match('/^\+255/', $phone)) {
            $phone = substr($phone, 1);
        }
        
        // If starts with 255, keep as is
        if (preg_match('/^255/', $phone)) {
            // Already in correct format
        } else {
            // If it doesn't match any pattern, assume it needs 255 prefix
            $phone = '255' . $phone;
        }
        
        return $phone;
    }

    /**
     * Send welcome SMS to customer using Beem API
     */
    private function sendWelcomeSMS($customer)
    {
        $apiKey = config('services.beem.api_key');
        $secretKey = config('services.beem.secret_key');
        $senderId = config('services.beem.sender_id', 'SAFCO');

        if (!$apiKey || !$secretKey) {
            throw new \Exception('Beem SMS configuration not found');
        }

        // Format the phone number
        $formattedPhone = $this->formatPhoneNumber($customer->phone);
        
        \Log::info('Sending SMS to formatted number: ' . $formattedPhone . ' (original: ' . $customer->phone . ')');

        $message = "Karibu {$customer->name}! Umesajiliwa kwenye mfumo wetu. Nambari yako ya mteja ni: {$customer->customerNo}. Asante!";
        
        $url = 'https://apisms.beem.africa/v1/send';
        
        $data = [
            'source_addr' => $senderId,
            'schedule_time' => '',
            'encoding' => 0,
            'message' => $message,
            'recipients' => [
                [
                    'recipient_id' => 1,
                    'dest_addr' => $formattedPhone
                ]
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ':' . $secretKey)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('SMS sending failed. HTTP Code: ' . $httpCode . ', Response: ' . $response);
        }

        $result = json_decode($response, true);
        
        if (isset($result['successful']) && $result['successful'] === false) {
            throw new \Exception('SMS sending failed: ' . ($result['message'] ?? 'Unknown error'));
        }

        \Log::info('Welcome SMS sent successfully to customer: ' . $customer->name . ' (' . $formattedPhone . ')');
    }
}
