<?php

namespace App\Http\Controllers;

use App\Helpers\SmsHelper;
use App\Models\BankAccount;
use App\Models\CashCollateral;
use App\Models\CashDepositAccount;
use App\Models\Customer;
use App\Models\Inventory\CustomerStorageBalance;
use App\Models\Inventory\CustomerStorageSale;
use App\Models\Inventory\Item;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Receipt;
use App\Services\CustomerBalanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;


use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            $companyId = auth()->user()->company_id;
            $customers = Customer::with(['branch', 'company'])
                ->where('company_id', $companyId)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
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
                    return $customer->credit_limit ? number_format($customer->credit_limit, 2) : '—';
                })
                ->addColumn('status_badge', function ($customer) {
                    $badgeClass = match($customer->status) {
                        'active' => 'bg-success',
                        'inactive' => 'bg-secondary',
                        'suspended' => 'bg-warning',
                        default => 'bg-secondary'
                    };
                    $label = match($customer->status) {
                        'active' => 'Hai',
                        'inactive' => 'Haifanyi kazi',
                        'suspended' => 'Imesimamishwa',
                        default => ucfirst($customer->status),
                    };
                    return '<span class="badge ' . $badgeClass . '">' . $label . '</span>';
                })
                ->addColumn('formatted_phone', function ($customer) {
                    return $customer->phone ? '<a href="tel:' . $customer->phone . '">' . $customer->phone . '</a>' : '—';
                })
                ->editColumn('email', function ($customer) {
                    return $customer->email ? '<a href="mailto:' . $customer->email . '">' . $customer->email . '</a>' : '—';
                })
                ->editColumn('branch.name', function ($customer) {
                    return $customer->branch ? $customer->branch->name : '—';
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
        } else {
            $request->merge(['phone' => $request->input('phone') ?: null]);
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
                'nullable',
                'string',
                Rule::when($request->filled('phone'), ['size:12', 'regex:/^[0-9]+$/']),
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
            'id_type' => ['nullable', Rule::in(array_keys(Customer::idTypeOptions()))],
            'id_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
            'company_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:100',
            'tin_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'send_welcome_sms' => 'nullable|boolean',
        ];

        $validated = $request->validate($rules);

        // Prepare customer data
        $data = $request->only([
            'name',
            'description',
            'id_type',
            'id_number',
            'status',
            'credit_limit',
            'bank_name',
            'bank_account_number',
            'account_name',
            'company_name',
            'company_registration_number',
            'tin_number',
            'vat_number',
        ]);
        $data['phone'] = $request->input('phone') ?: null;
        $data['email'] = $request->filled('email') ? $request->input('email') : null;
        $data['customerNo'] = Customer::nextCustomerNo();
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
        if (! empty($data['id_type'])) {
            $data['id_type'] = strtolower((string) $data['id_type']);
        }

        DB::beginTransaction();
        try {
            $customer = \App\Models\Customer::create($data);

            $customer->ensureLoanAccount();

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
            return redirect()->route('customers.index')->with('success', 'Mteja amesajiliwa kikamilifu.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            \Log::error('Customer creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if ($e->getCode() == 23000) {
                $errorMessage = $e->getMessage();
                $friendlyMessage = $this->friendlyDuplicateCustomerMessage($errorMessage, $request);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $friendlyMessage], 422);
                }
                return back()->withInput()->with('error', $friendlyMessage);
            }
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Imeshindwa kusajili mteja. Jaribu tena au wasiliana na msaada.'], 500);
            }
            return back()->withInput()->with('error', 'Imeshindwa kusajili mteja. Jaribu tena au wasiliana na msaada.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Customer creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Imeshindwa kusajili mteja. Jaribu tena au wasiliana na msaada.'], 500);
            }
            return back()->withInput()->with('error', 'Imeshindwa kusajili mteja. Jaribu tena au wasiliana na msaada.');
        }
    }

    private function friendlyDuplicateCustomerMessage(string $errorMessage, Request $request): string
    {
        if (str_contains($errorMessage, 'cannot be null') && str_contains($errorMessage, 'phone')) {
            return 'Namba ya simu haiwezi kuwa tupu kwenye hifadhidata. Tafadhali weka namba ya simu au endesha migration ya kufanya simu isiwe lazima.';
        }

        if (str_contains($errorMessage, 'customerNo') || str_contains($errorMessage, 'customers_customerno_unique')) {
            return 'Nambari ya mteja tayari ipo kwenye mfumo. Jaribu tena.';
        }

        if (str_contains($errorMessage, 'customers_email_unique') || (str_contains($errorMessage, 'Duplicate entry') && str_contains($errorMessage, 'email'))) {
            $email = $request->input('email');
            return $email
                ? "Mteja mwenye barua pepe '{$email}' tayari yupo. Tumia barua pepe nyingine au acha tupu."
                : 'Barua pepe hii tayari imetumika na mteja mwingine.';
        }

        if (str_contains($errorMessage, 'customers_phone_unique') || (str_contains($errorMessage, 'Duplicate entry') && str_contains($errorMessage, 'phone'))) {
            $phone = $request->input('phone');
            return $phone
                ? "Mteja mwenye namba ya simu '{$phone}' tayari yupo. Tumia namba nyingine."
                : 'Namba hii ya simu tayari imetumika na mteja mwingine.';
        }

        return 'Taarifa za mteja zinakinzana na rekodi iliyopo. Angalia nambari ya simu, barua pepe, au nambari ya mteja.';
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
        $correctCashDepositBalance = $this->calculateMikopoTotal($customer);

        // If it's an AJAX request, return JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'customer' => $customer
            ]);
        }

        $cropSalesDashboard = $this->buildCropSalesDashboard($customer);
        $totalCropSales = $this->calculateTotalCropSales($customer);
        $mikopoTotal = (float) $correctCashDepositBalance;
        $customerNetBalance = $totalCropSales - $mikopoTotal;
        $customerCashCollateral = $customer->cashCollaterals()->first();

        return view('customers.show', compact(
            'customer',
            'correctCashDepositBalance',
            'cropSalesDashboard',
            'totalCropSales',
            'mikopoTotal',
            'customerNetBalance',
            'customerCashCollateral'
        ));
    }

    public function balancePdf(string $encodedId, CustomerBalanceReportService $balanceReportService)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;

        if (! $id) {
            abort(404);
        }

        $customer = Customer::with(['branch', 'company'])->findOrFail($id);
        $company = current_company();
        $report = $balanceReportService->build($customer);

        $pdf = Pdf::loadView('customers.balance-pdf', array_merge($report, [
            'customer' => $customer,
            'company' => $company,
        ]))->setPaper('a4', 'portrait');

        $filename = 'salio-' . Str::slug($customer->name) . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    private function calculateTotalCropSales(Customer $customer): float
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('customer_storage_sales')) {
            return 0.0;
        }

        $companyId = auth()->user()->company_id;
        $branchId = session('branch_id') ?: auth()->user()->branch_id;

        return (float) CustomerStorageSale::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('total');
    }

    private function calculateMikopoTotal(Customer $customer): float
    {
        $total = 0.0;
        $cashCollaterals = \App\Models\CashCollateral::where('customer_id', $customer->id)->get();

        foreach ($cashCollaterals as $collateral) {
            $deposits = \App\Models\Receipt::where('reference', $collateral->id)
                ->where('reference_type', 'Deposit')
                ->sum('amount');

            $withdrawals = \App\Models\Payment::where('reference', $collateral->id)
                ->where('reference_type', 'Withdrawal')
                ->sum('amount');

            $journalWithdrawals = \App\Models\Journal::where('customer_id', $customer->id)
                ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
                ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
                ->where('journal_items.chart_account_id', 28)
                ->where('journal_items.nature', 'debit')
                ->sum('journal_items.amount');

            $total += (float) $deposits - ((float) $withdrawals + (float) $journalWithdrawals);
        }

        return $total;
    }

    public function sendSms(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;

        if (! $id) {
            return response()->json(['success' => false, 'message' => 'Mteja hajapatikana.'], 422);
        }

        $rules = [
            'message_title' => 'required|string|max:100',
            'bulk_message_content' => 'nullable|string|max:500',
        ];

        if ($request->input('message_title') === 'Custom') {
            $rules['bulk_message_content'] = 'required|string|max:500';
        }

        $validator = \Validator::make($request->all(), $rules, [
            'message_title.required' => 'Chagua kichwa cha ujumbe.',
            'bulk_message_content.required' => 'Andika maudhui ya ujumbe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Tafadhali rekebisha makosa.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::findOrFail($id);
        $title = $request->message_title;

        $rawPhone = $customer->phone;
        $phone = function_exists('normalize_phone_number')
            ? normalize_phone_number($rawPhone)
            : preg_replace('/[^0-9+]/', '', (string) $rawPhone);

        if (empty($phone) || strlen($phone) < 9) {
            return response()->json(['success' => false, 'message' => 'Namba ya simu ya mteja si sahihi.'], 422);
        }

        if ($title === 'Customer Account Info') {
            $mauzo = $this->calculateTotalCropSales($customer);
            $mikopo = $this->calculateMikopoTotal($customer);
            $salio = $mauzo - $mikopo;

            $fullMessage = 'Mpendwa ' . $customer->name
                . ', Taarifa za akaunti yako: Jumla ya mauzo TZS ' . number_format($mauzo, 2)
                . '. Mikopo TZS ' . number_format($mikopo, 2)
                . '. Salio lililobaki TZS ' . number_format($salio, 2)
                . '. Asante.';
        } elseif ($title === 'Payment Reminder') {
            $totalDue = (float) \App\Models\Sales\SalesInvoice::where('customer_id', $customer->id)
                ->where('balance_due', '>', 0)
                ->whereNotIn('status', ['cancelled'])
                ->sum('balance_due');

            if ($totalDue <= 0) {
                return response()->json(['success' => false, 'message' => 'Mteja hana deni lililobaki.'], 422);
            }

            $fullMessage = 'Mpendwa ' . $customer->name
                . ', tunakukumbusha kulipa deni lako la TZS ' . number_format($totalDue, 2)
                . '. Tafadhali fanya malipo mapema iwezekanavyo. Asante.';
        } else {
            $fullMessage = (string) $request->bulk_message_content;
        }

        if (! SmsHelper::isConfigured()) {
            return response()->json(['success' => false, 'message' => 'SMS haijasanidiwa.'], 422);
        }

        try {
            $response = SmsHelper::send($phone, $fullMessage);

            if (! ($response['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $response['error'] ?? 'Imeshindwa kutuma SMS.',
                ], 500);
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('sms_logs')) {
                DB::table('sms_logs')->insert([
                    'customer_id' => $customer->id,
                    'phone_number' => $phone,
                    'message' => $fullMessage,
                    'response' => json_encode($response),
                    'sent_by' => auth()->id(),
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'SMS imetumwa kikamilifu.']);
        } catch (\Throwable $e) {
            \Log::error('Failed to send customer SMS: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Imeshindwa kutuma SMS.'], 500);
        }
    }

    private function buildCropSalesDashboard(Customer $customer): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('customer_storage_sales')) {
            return [];
        }

        $companyId = auth()->user()->company_id;
        $branchId = session('branch_id') ?: auth()->user()->branch_id;

        $storedItemIds = CustomerStorageBalance::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->pluck('inventory_item_id');

        $soldItemIds = CustomerStorageSale::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->pluck('inventory_item_id');

        $itemIds = $storedItemIds->merge($soldItemIds)->unique()->values();

        if ($itemIds->isEmpty()) {
            return [];
        }

        $salesTotals = CustomerStorageSale::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereIn('inventory_item_id', $itemIds)
            ->selectRaw('inventory_item_id, SUM(quantity) as total_quantity, SUM(total) as total_sales')
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('inventory_item_id');

        return Item::query()
            ->whereIn('id', $itemIds)
            ->orderBy('name')
            ->get()
            ->map(function ($item) use ($salesTotals) {
                $row = $salesTotals->get($item->id);

                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_code' => $item->code,
                    'unit' => $item->unit_of_measure,
                    'total_quantity_sold' => (float) ($row->total_quantity ?? 0),
                    'total_sales' => (float) ($row->total_sales ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    // DataTable for customer loans (individual mkopo transactions)
    public function cashDepositsDataTable($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $customer = Customer::findOrFail($id);
        $collateralIds = $customer->cashCollaterals()->pluck('id');
        $loanTypeOptions = CashCollateral::loanTypeOptions();

        $loans = Receipt::query()
            ->with('user')
            ->whereIn('reference', $collateralIds)
            ->where('reference_type', 'Deposit');

        return datatables()->of($loans)
            ->addColumn('loan_type_label', function ($receipt) use ($loanTypeOptions) {
                if ($receipt->loan_type && isset($loanTypeOptions[$receipt->loan_type])) {
                    return $loanTypeOptions[$receipt->loan_type];
                }

                return $receipt->loan_type ?: '—';
            })
            ->addColumn('formatted_amount', function ($receipt) {
                return number_format($receipt->amount, 2);
            })
            ->addColumn('formatted_date', function ($receipt) {
                return format_date($receipt->date, 'M d, Y');
            })
            ->addColumn('entered_by_name', function ($receipt) {
                return $receipt->user->name ?? '—';
            })
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

    // DataTable for customer stored crops (uhifadhi wa wateja)
    public function customerStorageDataTable($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;

        if (! $id) {
            abort(404);
        }

        $user = auth()->user();
        $branchId = session('branch_id') ?: $user->branch_id;

        $query = CustomerStorageBalance::query()
            ->with(['item'])
            ->where('company_id', $user->company_id)
            ->where('customer_id', $id)
            ->where('quantity_on_hand', '>', 0)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        return datatables()->of($query)
            ->addColumn('item_name', fn ($row) => $row->item->name ?? '—')
            ->addColumn('item_code', fn ($row) => $row->item->code ?? '—')
            ->addColumn('quantity_display', fn ($row) => $this->formatStorageQuantity((float) $row->quantity_on_hand, $row->item))
            ->addColumn('package_display', fn ($row) => $this->formatStoragePackage((float) $row->quantity_on_hand, $row->item))
            ->addColumn('history_link', function ($row) use ($id) {
                $url = route('inventory.customer-storage.history', ['customer_id' => $id]);

                return '<a href="' . e($url) . '" class="btn btn-sm btn-outline-info" title="Historia ya uletaji">'
                    . '<i class="bx bx-history me-1"></i> Historia</a>';
            })
            ->filterColumn('item_name', function ($query, $keyword) {
                $query->whereHas('item', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'));
            })
            ->rawColumns(['history_link'])
            ->make(true);
    }

    private function formatStorageQuantity(float $quantity, ?Item $item): string
    {
        $unit = $item?->unit_of_measure;
        $formattedQty = $this->formatStorageNumber($quantity);

        return $unit ? $formattedQty . ' ' . $unit : $formattedQty;
    }

    private function formatStoragePackage(float $quantity, ?Item $item): string
    {
        if (! $item) {
            return '—';
        }

        $packageQuantity = (float) ($item->package_quantity ?? 0);
        $packageName = trim((string) ($item->package_name ?? ''));

        if ($packageQuantity <= 0 || $packageName === '') {
            return '—';
        }

        $count = $quantity / $packageQuantity;

        return $this->formatStorageNumber($count) . ' ' . $packageName;
    }

    private function formatStorageNumber(float $value): string
    {
        if (fmod($value, 1.0) === 0.0) {
            return number_format($value, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
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

        if ($request->filled('phone') && function_exists('normalize_phone_number')) {
            $request->merge(['phone' => normalize_phone_number($request->input('phone'))]);
        } else {
            $request->merge(['phone' => $request->input('phone') ?: null]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => [
                'nullable',
                'string',
                Rule::when($request->filled('phone'), [
                    'size:12',
                    'regex:/^[0-9]+$/',
                    Rule::unique('customers', 'phone')->where(function ($query) use ($companyId) {
                        return $query->where('company_id', $companyId);
                    })->ignore($id),
                ]),
            ],
            'status' => 'required|in:active,inactive,suspended',
            'id_type' => ['nullable', Rule::in(array_keys(Customer::idTypeOptions()))],
            'id_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:255',
        ]);

        $data = $request->only([
            'name',
            'description',
            'phone',
            'id_type',
            'id_number',
            'status',
            'bank_name',
            'bank_account_number',
            'account_name',
        ]);

        // Set these from logged-in user
        $data['branch_id'] = session('branch_id') ?? auth()->user()->branch_id;
        $data['company_id'] = auth()->user()->company_id;
        if (! empty($data['id_type'])) {
            $data['id_type'] = strtolower((string) $data['id_type']);
        }

        DB::beginTransaction();
        try {
            $customer->update($data);

            DB::commit();
            return redirect()->route('customers.index')->with('success', 'Mteja amesasishwa kikamilifu.');
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
                        'customerNo' => Customer::nextCustomerNo(),
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

                    $customer->ensureLoanAccount();

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
        if (! SmsHelper::isConfigured()) {
            throw new \Exception('SMS haijasanidiwa.');
        }

        $formattedPhone = function_exists('normalize_phone_number')
            ? normalize_phone_number($customer->phone)
            : $this->formatPhoneNumber($customer->phone);

        $message = "Karibu {$customer->name} katika Gala letu ya MBOJI MILLIS";

        $result = SmsHelper::send($formattedPhone, $message);

        if (! ($result['success'] ?? false)) {
            throw new \Exception($result['error'] ?? 'Imeshindikana kutuma SMS ya kukaribisha.');
        }

        \Log::info('Welcome SMS sent successfully to customer: '.$customer->name.' ('.$formattedPhone.')');
    }
}
