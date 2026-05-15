<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Sales\SalesOrder;
use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Models\SystemSetting;
use App\Models\Payment;
use App\Models\Journal;
use App\Models\GlTransaction;
use App\Services\InventoryCostService;
use App\Services\FxTransactionRateService;
use App\Mail\SalesInvoiceMail;
use App\Traits\GetsCurrenciesFromFxRates;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;
use App\Services\Printing\EscposUsbPrinter;
use Mike42\Escpos\Printer;


class SalesInvoiceController extends Controller
{
    use GetsCurrenciesFromFxRates;
    
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view sales invoices', ['only' => ['index', 'show']]);
        $this->middleware('permission:create sales invoices', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit sales invoices', ['only' => ['edit', 'update', 'deleteCashDepositJournalPayment']]);
        $this->middleware('permission:delete sales invoices', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('view sales invoices')) {
            abort(403, 'Unauthorized action.');
        }

        // Debug: Log user info
        // \Log::info('User accessing invoices:', [
        //     'user_id' => auth()->id(),
        //     'user_name' => auth()->user()->name,
        //     'branch_id' => auth()->user()->branch_id,
        //     'permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray()
        // ]);
        if ($request->ajax()) {
            $invoices = SalesInvoice::with(['customer', 'branch', 'createdBy'])
                ->where('company_id', auth()->user()->company_id)
                ->when(auth()->user()->branch_id, function($query) {
                    return $query->where('branch_id', auth()->user()->branch_id);
                })
                ->select(['id', 'invoice_number', 'reference_no', 'customer_id', 'invoice_date', 'due_date', 'status', 'total_amount', 'paid_amount', 'balance_due', 'branch_id', 'created_by', 'created_at']);

            return datatables($invoices)
                ->filter(function ($query) use ($request) {
                    if ($request->filled('search.value')) {
                        $searchValue = $request->input('search.value');
                        $query->where(function($q) use ($searchValue) {
                            $q->where('invoice_number', 'like', "%{$searchValue}%")
                              ->orWhere('reference_no', 'like', "%{$searchValue}%")
                              ->orWhereHas('customer', function($customerQuery) use ($searchValue) {
                                  $customerQuery->where('name', 'like', "%{$searchValue}%");
                              })
                              ->orWhere('status', 'like', "%{$searchValue}%")
                              ->orWhere('total_amount', 'like', "%{$searchValue}%")
                              ->orWhere('balance_due', 'like', "%{$searchValue}%");
                        });
                    }
                })
                ->addColumn('reference_no', function ($invoice) {
                    return $invoice->reference_no ?? '—';
                })
                ->addColumn('customer_name', function ($invoice) {
                    return $invoice->customer->name;
                })
                ->addColumn('branch_name', function ($invoice) {
                    return $invoice->branch->name;
                })
                ->addColumn('created_by_name', function ($invoice) {
                    return $invoice->createdBy->name;
                })
                ->addColumn('status_badge', function ($invoice) {
                    return $invoice->status_badge;
                })
                ->addColumn('formatted_total', function ($invoice) {
                    return number_format($invoice->total_amount, 2);
                })
                ->addColumn('formatted_balance', function ($invoice) {
                    return number_format($invoice->balance_due, 2);
                })
                ->addColumn('formatted_date', function ($invoice) {
                    return format_date($invoice->invoice_date);
                })
                ->addColumn('formatted_due_date', function ($invoice) {
                    return format_date($invoice->due_date);
                })
                ->addColumn('actions', function ($invoice) {
                    $encodedId = Hashids::encode($invoice->id);
                    $isPaid = (string) $invoice->status === 'paid';
                    $disabledClass = $isPaid ? ' disabled' : '';
                    $disabledAttr = $isPaid ? ' aria-disabled=true tabindex=-1' : '';

                    $actions = '<div class="btn-group" role="group">';

                    // View button - always show
                    $actions .= '<a href="' . route('sales.invoices.show', $encodedId) . '" class="btn btn-sm btn-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>';

                    // Edit button - check permission
                    if (auth()->user()->can('edit sales invoices')) {
                        $actions .= '<a href="' . ($isPaid ? '#' : route('sales.invoices.edit', $encodedId)) . '" class="btn btn-sm btn-primary' . $disabledClass . '" title="Edit"' . $disabledAttr . '>
                            <i class="bx bx-edit"></i>
                        </a>';
                    }

                    // Delete button - check permission
                    if (auth()->user()->can('delete sales invoices')) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger' . $disabledClass . '" ' . ($isPaid ? 'disabled' : 'onclick="deleteInvoice(\'' . $encodedId . '\')"') . ' title="Delete">
                            <i class="bx bx-trash"></i>
                        </button>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })
                ->editColumn('created_at', function ($invoice) {
                    return format_date($invoice->created_at);
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $totalInvoices = SalesInvoice::where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })->count();
        $totalAmount = SalesInvoice::where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })->sum('total_amount');
        $totalPaid = SalesInvoice::where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })->sum('paid_amount');
        $totalOutstanding = SalesInvoice::where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })->sum('balance_due');

        return view('sales.invoices.index', compact('totalInvoices', 'totalAmount', 'totalPaid', 'totalOutstanding'));
    }

    /**
     * Get customer credit information via AJAX
     */
    public function getCustomerCreditInfo(Request $request)
    {
        // Log that the method was called - use file_put_contents for immediate visibility
        file_put_contents(
            storage_path('logs/credit_info_debug.log'),
            date('Y-m-d H:i:s') . " - getCustomerCreditInfo called\n" .
            "Customer ID: " . $request->get('customer_id') . "\n" .
            "All params: " . json_encode($request->all()) . "\n" .
            "Method: " . $request->method() . "\n" .
            "URL: " . $request->fullUrl() . "\n" .
            "User ID: " . auth()->id() . "\n\n",
            FILE_APPEND
        );
        
        // Log that the method was called
        \Log::info('getCustomerCreditInfo called', [
            'customer_id' => $request->get('customer_id'),
            'all_params' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => auth()->id()
        ]);
        
        try {
            $customerId = $request->get('customer_id');
            if (!$customerId) {
                \Log::warning('Customer ID is missing in request');
                return response()->json([
                    'success' => false,
                    'message' => 'Customer ID is required'
                ], 422);
            }

            $customer = Customer::find($customerId);
            if (!$customer) {
                \Log::warning('Customer not found', ['customer_id' => $customerId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            $creditLimit = (float) ($customer->credit_limit ?? 0);
            
            // Calculate current balance with error handling
            try {
                $currentBalance = (float) $customer->getCurrentBalance();
            } catch (\Exception $e) {
                \Log::error('Error calculating current balance: ' . $e->getMessage());
                $currentBalance = 0;
            }
            
            // Calculate available credit with error handling
            try {
                $availableCredit = (float) $customer->getAvailableCredit();
            } catch (\Exception $e) {
                \Log::error('Error calculating available credit: ' . $e->getMessage());
                $availableCredit = $creditLimit > 0 ? ($creditLimit - $currentBalance) : 0;
            }

            \Log::info('Credit info calculated', [
                'customer_id' => $customerId,
                'credit_limit' => $creditLimit,
                'current_balance' => $currentBalance,
                'available_credit' => $availableCredit
            ]);

            return response()->json([
                'success' => true,
                'credit_limit' => $creditLimit,
                'current_balance' => $currentBalance,
                'available_credit' => $availableCredit,
                'has_credit_limit' => $creditLimit > 0
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching customer credit info: ' . $e->getMessage(), [
                'customer_id' => $request->get('customer_id'),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching credit information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if (!auth()->user()->can('create sales invoices')) {
            abort(403, 'Unauthorized action.');
        }
        $customers = Customer::forBranch(auth()->user()->branch_id)->active()->get();

        // Get items with stock > 0 at current location
        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        $inventoryItems = $stockService->getAvailableItemsForSales(auth()->user()->company_id, $loginLocationId);
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        // Get only unconverted sales orders (those without existing invoices and not already converted)
        $salesOrders = SalesOrder::forBranch(auth()->user()->branch_id)
            ->approved()
            ->whereDoesntHave('invoice')
            ->where('status', '!=', 'converted_to_invoice')
            ->with(['customer', 'items.inventoryItem'])
            ->orderBy('order_date', 'desc')
            ->get();

        // Pre-select customer if provided
        $selectedCustomer = null;
        if ($request->has('customer_id')) {
            $customerId = Hashids::decode($request->customer_id)[0] ?? null;
            if ($customerId) {
                $selectedCustomer = Customer::find($customerId);
            }
        }

        // Copy from invoice: load source invoice and pass prefilled data
        $copyFromInvoice = null;
        if ($request->filled('copy_from_invoice')) {
            $sourceId = Hashids::decode($request->copy_from_invoice)[0] ?? null;
            if ($sourceId) {
                $source = SalesInvoice::with(['customer', 'items'])->where('company_id', auth()->user()->company_id)->find($sourceId);
                if ($source) {
                    $copyFromInvoice = [
                        'customer' => ['id' => $source->customer_id, 'name' => $source->customer->name ?? ''],
                        'invoice_date' => $source->invoice_date ? $source->invoice_date->format('Y-m-d') : now()->format('Y-m-d'),
                        'due_date' => $source->due_date ? $source->due_date->format('Y-m-d') : now()->addDays(30)->format('Y-m-d'),
                        'payment_terms' => $source->payment_terms ?? 'net_30',
                        'payment_days' => (int) ($source->payment_days ?? 30),
                        'discount_amount' => (float) ($source->discount_amount ?? 0),
                        'notes' => $source->notes ?? '',
                        'terms_conditions' => $source->terms_conditions ?? '',
                        'items' => $source->items->map(function ($item) {
                            return [
                                'inventory_item_id' => $item->inventory_item_id,
                                'item_name' => $item->item_name ?? '',
                                'item_code' => $item->item_code ?? '',
                                'quantity' => (float) $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'vat_type' => $item->vat_type ?? 'inclusive',
                                'vat_rate' => (float) ($item->vat_rate ?? 0),
                                'vat_amount' => (float) ($item->vat_amount ?? 0),
                                'discount_type' => $item->discount_type ?? 'percentage',
                                'discount_rate' => (float) ($item->discount_rate ?? 0),
                                'discount_amount' => (float) ($item->discount_amount ?? 0),
                                'line_total' => (float) $item->line_total,
                                'notes' => $item->notes ?? '',
                            ];
                        })->values()->all(),
                    ];
                }
            }
        }

        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        return view('sales.invoices.create', compact('customers', 'inventoryItems', 'salesOrders', 'selectedCustomer', 'currencies', 'copyFromInvoice'))->with('items', $inventoryItems);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create sales invoices')) {
            abort(403, 'Unauthorized action.');
        }
        // Debug: Log the request data
        \Log::info('Sales Invoice Store Request Data:', $request->all());

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_45,net_60,custom',
            'payment_days' => 'required|integer|min:0',
            'reference_no' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'withholding_tax_enabled' => 'nullable|boolean',
            'withholding_tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->withholding_tax_type === 'percentage' && $value > 100) {
                        $fail('The withholding tax rate must not be greater than 100 when type is percentage.');
                    }
                },
            ],
            'withholding_tax_type' => 'nullable|in:percentage,fixed',
            'early_payment_discount_enabled' => 'nullable|boolean',
            'early_payment_discount_type' => 'nullable|in:percentage,fixed',
            'early_payment_discount_rate' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->early_payment_discount_type === 'percentage' && $value > 100) {
                        $fail('The early payment discount rate must not be greater than 100 when type is percentage.');
                    }
                },
            ],
            'early_payment_days' => 'nullable|integer|min:0',
            'late_payment_fees_enabled' => 'nullable|boolean',
            'late_payment_fees_type' => 'nullable|in:monthly,one_time',
            'late_payment_fees_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:inclusive,exclusive,no_vat',
            'items.*.vat_rate' => 'required|numeric|min:0|max:100',
            'items.*.discount_type' => 'nullable|in:percentage,fixed',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.price_tier' => 'nullable|in:retail,wholesale',
        ]);

        $tierErrors = InventoryItem::priceTierValidationErrors($request);
        if ($tierErrors !== []) {
            throw ValidationException::withMessages($tierErrors);
        }

        // Resolve branch
        $branchId = session('branch_id') ?? (auth()->user()->branch_id ?? null);
        if (empty($branchId)) {
            \Log::error('Sales Invoice Store: Missing branch_id');
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a branch before creating a sales invoice.'
                ], 422);
            }
            return redirect()->back()->withInput()->withErrors(['error' => 'Please select a branch before creating a sales invoice.']);
        }

        // Resolve location (required for inventory movements)
        $locationId = session('location_id');
        if (empty($locationId)) {
            \Log::error('Sales Invoice Store: Missing location_id');
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a location before creating a sales invoice.'
                ], 422);
            }
            return redirect()->back()->withInput()->withErrors(['error' => 'Please select a location before creating a sales invoice.']);
        }

        // Custom validation for stock availability
        $validator = \Validator::make($request->all(), []);

        foreach ($request->items as $index => $itemData) {
            $inventoryItem = InventoryItem::queryVisibleForSession()->find($itemData['inventory_item_id']);
            // Skip stock validation for service items or items that don't track stock
            if ($inventoryItem && 
                $inventoryItem->item_type !== 'service' && 
                $inventoryItem->track_stock && 
                $itemData['quantity'] > $inventoryItem->current_stock) {
                $validator->errors()->add("items.{$index}.quantity",
                    "Insufficient stock for {$inventoryItem->name}. Available: {$inventoryItem->current_stock}, Requested: {$itemData['quantity']}");
            }
        }

        if ($validator->errors()->count() > 0) {
            // Get the first validation error message (typically the stock error)
            $firstErrorMessage = $validator->errors()->first();

            // If this is an AJAX request, return JSON (e.g. for API or JS clients)
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    // Return the specific stock error so the frontend (SweetAlert) can show it directly
                    'message' => $firstErrorMessage ?: 'Stock validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // For normal form POST (non-AJAX), redirect back with errors and flash message
            // so the UI can show it (e.g. via SweetAlert using session('error')).
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator)
                ->with('error', $firstErrorMessage ?: 'Stock validation failed');
        }

        $customer = Customer::find($request->customer_id);
        $creditCheckContext = null;
        if ($customer && (float) $customer->credit_limit > 0) {
            $currentBalanceBeforeInvoice = (float) $customer->getCurrentBalance();
            $availableCreditBeforeInvoice = (float) $customer->getAvailableCredit();
            $creditCheckContext = [
                'current_balance_before' => $currentBalanceBeforeInvoice,
                'available_credit_before' => $availableCreditBeforeInvoice,
            ];
        }
        DB::beginTransaction();

            // Get functional currency
            $functionalCurrency = SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
            $invoiceCurrency = $request->currency ?? $functionalCurrency;
            $companyId = auth()->user()->company_id;

            // Get exchange rate using FxTransactionRateService
            $fxTransactionRateService = app(FxTransactionRateService::class);
            $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
            $rateResult = $fxTransactionRateService->getTransactionRate(
                $invoiceCurrency,
                $functionalCurrency,
                $request->invoice_date,
                $companyId,
                $userProvidedRate
            );
            $exchangeRate = $rateResult['rate'];
            $fxRateUsed = $exchangeRate; // Store the rate used for fx_rate_used field

            // Create invoice
            // Handle optional attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('sales-invoice-attachments', $fileName, 'public');
            }

            $invoice = SalesInvoice::create([
                'customer_id' => $request->customer_id,
                'sales_order_id' => $request->sales_order_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'status' => 'draft',
                'payment_terms' => $request->payment_terms,
                'payment_days' => $request->payment_days,
                'reference_no' => $request->reference_no,
                'currency' => $invoiceCurrency,
                'exchange_rate' => $exchangeRate,
                'fx_rate_used' => $fxRateUsed,
                // WHT is NOT set at invoice creation - it's only applied at payment/receipt time
                'withholding_tax_rate' => 0,
                'withholding_tax_type' => 'percentage',
                'early_payment_discount_enabled' => $request->early_payment_discount_enabled ?? false,
                'early_payment_discount_type' => $request->early_payment_discount_type ?? 'percentage',
                'early_payment_discount_rate' => $request->early_payment_discount_rate ?? 0,
                'early_payment_days' => $request->early_payment_days ?? 0,
                'late_payment_fees_enabled' => $request->late_payment_fees_enabled ?? false,
                'late_payment_fees_type' => $request->late_payment_fees_type ?? 'monthly',
                'late_payment_fees_rate' => $request->late_payment_fees_rate ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'attachment' => $attachmentPath,
                'branch_id' => $branchId,
                'company_id' => auth()->user()->company_id,
                'created_by' => auth()->id(),
            ]);

            $itemsCount = count($request->items);

            // Process items synchronously (job removed)
            \Log::info('Sales Invoice Store: Processing items synchronously', [
                'items_count' => $itemsCount
            ]);

                // Consolidate lines that share the same item and price tier (retail vs wholesale)
                $consolidatedItems = [];
                foreach ($request->items as $itemData) {
                    $inventoryItemId = $itemData['inventory_item_id'];
                    $tier = InventoryItem::normalizedPriceTier($itemData['price_tier'] ?? null);
                    $itemData['price_tier'] = $tier;
                    $key = $inventoryItemId.'|'.$tier;

                    if (isset($consolidatedItems[$key])) {
                        $consolidatedItems[$key]['quantity'] += $itemData['quantity'];
                    } else {
                        $consolidatedItems[$key] = $itemData;
                    }
                }

                // Create invoice items from consolidated data
                foreach ($consolidatedItems as $itemData) {
                $inventoryItem = InventoryItem::queryVisibleForSession()->find($itemData['inventory_item_id']);

                // Calculate line total before creating the item
                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $vatType = $itemData['vat_type'];
                $vatRate = $itemData['vat_rate'];

                // Calculate subtotal (no item-level discounts)
                $subtotal = $quantity * $unitPrice;

                // Calculate VAT and line total
                $vatAmount = 0;
                $lineTotal = 0;

                if ($vatType === 'no_vat') {
                    $lineTotal = $subtotal;
                } elseif ($vatType === 'exclusive') {
                    $vatAmount = $subtotal * ($vatRate / 100);
                    $lineTotal = $subtotal + $vatAmount;
                } else {
                    // VAT inclusive
                    $vatAmount = $subtotal * ($vatRate / (100 + $vatRate));
                    $lineTotal = $subtotal;
                }

                $invoiceItem = SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'description' => $inventoryItem->description,
                    'unit_of_measure' => $inventoryItem->unit_of_measure,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'price_tier' => InventoryItem::normalizedPriceTier($itemData['price_tier'] ?? null),
                    'line_total' => $lineTotal,
                    'vat_type' => $vatType,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'discount_type' => null,
                    'discount_rate' => 0,
                    'discount_amount' => 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // Check stock availability
                $invoiceItem->checkStockAvailability();
                $invoiceItem->save();

                // Create inventory movement for stock out (only for products)
                \Log::info('Sales Invoice Store: Checking item for movement creation', [
                    'invoice_id' => $invoice->id,
                    'item_id' => $inventoryItem->id,
                    'item_name' => $inventoryItem->name,
                    'track_stock' => $inventoryItem->track_stock,
                    'item_type' => $inventoryItem->item_type,
                    'will_create_movement' => ($inventoryItem->track_stock && $inventoryItem->item_type === 'product'),
                    'location_id' => $locationId
                ]);

                if ($inventoryItem->track_stock && $inventoryItem->item_type === 'product') {
                    try {
                        $costService = new InventoryCostService();
                        $stockService = new \App\Services\InventoryStockService();
                        $balanceBefore = $stockService->getItemStockAtLocation($inventoryItem->id, $locationId);
                        $balanceAfter = $balanceBefore - $quantity;

                        // Get actual cost using FIFO/Weighted Average
                        // Pass branch/location for fallback cost resolution (location → branch → default)
                        $costInfo = $costService->removeInventory(
                            $inventoryItem->id,
                            $quantity,
                            'sale',
                            'Sales Invoice: ' . $invoice->invoice_number,
                            $invoice->invoice_date,
                            $branchId,
                            $locationId
                        );

                        InventoryMovement::create([
                            'item_id' => $inventoryItem->id,
                            'user_id' => auth()->id(),
                            'branch_id' => $branchId,
                            'location_id' => $locationId,
                            'movement_type' => 'sold',
                            'quantity' => $quantity,
                            'unit_price' => $costInfo['average_unit_cost'],
                            'unit_cost' => $costInfo['average_unit_cost'],
                            'total_cost' => $costInfo['total_cost'],
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter,
                            'reference' => 'Sales Invoice: ' . $invoice->invoice_number,
                            'reference_type' => 'sales_invoice',
                            'reference_id' => $invoice->id,
                            'notes' => 'Stock sold via sales invoice',
                            'movement_date' => $invoice->invoice_date,
                        ]);

                        \Log::info('Sales Invoice: Inventory movement created', [
                            'invoice_id' => $invoice->id,
                            'item_id' => $inventoryItem->id,
                            'item_name' => $inventoryItem->name,
                            'quantity' => $quantity,
                            'location_id' => $locationId
                        ]);

                        // Consume stock using FEFO if item tracks expiry
                        $consumedLayers = [];
                        $earliestExpiryDate = null;
                        $batchNumbers = [];
                        
                        if ($inventoryItem->track_expiry) {
                            $expiryService = new \App\Services\ExpiryStockService();
                            $consumedLayers = $expiryService->consumeStock(
                                $inventoryItem->id,
                                $locationId,
                                $quantity,
                                'FEFO'
                            );
                            
                            // Extract expiry information for display
                            if (!empty($consumedLayers)) {
                                $earliestExpiryDate = $consumedLayers[0]['expiry_date'];
                                $batchNumbers = array_column($consumedLayers, 'batch_number');
                            }
                            
                            // Log consumed layers for audit trail
                            foreach ($consumedLayers as $layer) {
                                \Log::info('Sales Invoice: Consumed stock with expiry', [
                                    'invoice_id' => $invoice->id,
                                    'item_id' => $inventoryItem->id,
                                    'batch_number' => $layer['batch_number'],
                                    'expiry_date' => $layer['expiry_date'],
                                    'quantity' => $layer['quantity'],
                                    'unit_cost' => $layer['unit_cost']
                                ]);
                            }
                        }

                        // Update invoice item with expiry information
                        $invoiceItem->update([
                            'batch_number' => !empty($batchNumbers) ? implode(', ', $batchNumbers) : null,
                            'expiry_date' => $earliestExpiryDate,
                            'expiry_consumption_details' => $consumedLayers,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Sales Invoice: Failed to create inventory movement', [
                            'invoice_id' => $invoice->id,
                            'item_id' => $inventoryItem->id,
                            'item_name' => $inventoryItem->name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Continue processing other items even if one fails
                    }

                    // Stock is now tracked via movements, no need to update item directly
                } else {
                    \Log::info('Sales Invoice: Skipping movement creation', [
                        'invoice_id' => $invoice->id,
                        'item_id' => $inventoryItem->id,
                        'item_name' => $inventoryItem->name,
                        'reason' => !$inventoryItem->track_stock ? 'Item does not track stock' : 'Item type is not product (type: ' . $inventoryItem->item_type . ')'
                    ]);
                }
            }

            // Update invoice totals
            $invoice->updateTotals();
            
            // Refresh invoice to get updated total_amount
            $invoice->refresh();

            // Check credit limit against balance BEFORE this new invoice was created.
            if ($customer && $creditCheckContext !== null) {
                $currentBalance = (float) $creditCheckContext['current_balance_before'];
                $availableCredit = (float) $creditCheckContext['available_credit_before'];
                $invoiceTotal = (float) $invoice->total_amount;

                // Check if invoice total exceeds available credit
                if ($invoiceTotal > $availableCredit) {
                    $excessAmount = $invoiceTotal - $availableCredit;
                    $message = "Invoice amount (TZS " . number_format($invoiceTotal, 2) . ") exceeds available credit. " .
                               "Credit Limit: TZS " . number_format($customer->credit_limit, 2) . ", " .
                               "Current Balance: TZS " . number_format($currentBalance, 2) . ", " .
                               "Available Credit: TZS " . number_format($availableCredit, 2) . ". " .
                               "Excess: TZS " . number_format($excessAmount, 2);
                    
                    // Prevent invoice creation when credit limit is exceeded
                    \Log::warning("Credit limit exceeded for customer {$customer->id}: {$message}");
                    
                    DB::rollBack();
                    
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => $message
                        ], 422);
                    }
                    
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['error' => $message]);
                }
            }

            // Create double-entry transactions
            $invoice->createDoubleEntryTransactions();

            // Update sales order status if invoice was created from an order
            if ($request->sales_order_id) {
                $salesOrder = SalesOrder::find($request->sales_order_id);
                if ($salesOrder) {
                    $salesOrder->update([
                        'status' => 'converted_to_invoice',
                        'updated_by' => auth()->id()
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sales invoice created successfully!',
                    'redirect_url' => route('sales.invoices.show', $invoice->encoded_id)
                ]);
            }

            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('success', 'Sales invoice created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        if (!auth()->user()->can('view sales invoices')) {
            abort(403, 'Unauthorized action.');
        }
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = SalesInvoice::with([
            'customer',
            'items.inventoryItem',
            'branch',
            'company',
            'createdBy',
            'updatedBy',
            'glTransactions.chartAccount',
            'creditNotes',
            'payments.user',
            'payments.bankAccount',
            'payments.cashDeposit.type',
            'cashDepositPaymentJournals.user',
            'cashDepositPaymentJournals.items',
        ])->findOrFail($invoiceId);
        
        // Load receipts separately since receipts() is now a query method, not a relationship
        // Receipts will be loaded in the view using receipts() method

        //select the unpaid invoices for the customer which is not the current invoice
        $unpaidInvoices = SalesInvoice::where('customer_id', $invoice->customer_id)
            ->whereColumn('total_amount', '>', 'paid_amount')
            ->where('id', '!=', $invoice->id)
            ->get();

        // Calculate total unpaid amount in functional currency (TZS)
        $functionalCurrency = SystemSetting::getValue('functional_currency', $invoice->company->functional_currency ?? 'TZS');
        $totalUnpaidAmountInTZS = $unpaidInvoices->sum(function($unpaidInvoice) use ($functionalCurrency) {
            $invoiceCurrency = $unpaidInvoice->currency ?? $functionalCurrency;
            $exchangeRate = $unpaidInvoice->exchange_rate ?? 1.000000;
            
            // If invoice is in foreign currency, convert to functional currency
            if ($invoiceCurrency !== $functionalCurrency && $exchangeRate != 1.000000) {
                return $unpaidInvoice->balance_due * $exchangeRate;
            }
            
            // Already in functional currency, use as is
            return $unpaidInvoice->balance_due;
        });
        
        // Calculate current invoice balance in functional currency (TZS)
        $invoiceCurrency = $invoice->currency ?? $functionalCurrency;
        $invoiceExchangeRate = $invoice->exchange_rate ?? 1.000000;
        $currentInvoiceBalanceInTZS = $invoice->balance_due;
        if ($invoiceCurrency !== $functionalCurrency && $invoiceExchangeRate != 1.000000) {
            $currentInvoiceBalanceInTZS = $invoice->balance_due * $invoiceExchangeRate;
        }
        
        // Total customer balance in functional currency
        $totalCustomerBalanceInTZS = $currentInvoiceBalanceInTZS + $totalUnpaidAmountInTZS;

        // Sum of credit notes applied to this invoice (for summary display)
        $creditNotesApplied = \App\Models\Sales\CreditNoteApplication::where('sales_invoice_id', $invoice->id)
            ->where('application_type', 'invoice')
            ->sum('amount_applied');

        return view('sales.invoices.show', compact('invoice', 'unpaidInvoices', 'creditNotesApplied', 'totalUnpaidAmountInTZS', 'currentInvoiceBalanceInTZS', 'totalCustomerBalanceInTZS', 'functionalCurrency'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        if (!auth()->user()->can('edit sales invoices')) {
            abort(403, 'Unauthorized action.');
        }

        // Debug: Log user info
        \Log::info('User editing invoice:', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'branch_id' => auth()->user()->branch_id,
            'encoded_id' => $encodedId,
            'permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray()
        ]);
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        // Load invoice
        $invoice = SalesInvoice::findOrFail($invoiceId);
        
        // Load items including soft-deleted ones using the relationship
        $invoice->load(['items' => function ($query) {
            $query->withTrashed()->orderBy('id');
        }]);
        
        // Force reload to ensure items collection is fresh
        $items = $invoice->items()->withTrashed()->orderBy('id')->get();
        $invoice->setRelation('items', $items);
        
        // Load inventory items for all items
        $invoice->load('items.inventoryItem');
        
        // Debug: Log items count
        \Log::info('Sales Invoice Edit - Items loaded', [
            'invoice_id' => $invoice->id,
            'items_count' => $invoice->items->count(),
            'items' => $invoice->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'deleted_at' => $item->deleted_at
                ];
            })
        ]);
        $customers = Customer::forBranch(auth()->user()->branch_id)->active()->get();

        // Get items with stock > 0 at current location
        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        $inventoryItems = $stockService->getAvailableItemsForSales(auth()->user()->company_id, $loginLocationId);
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        // Create a map of original quantities by item_id for frontend validation
        $originalQuantities = [];
        foreach ($invoice->items as $item) {
            $itemId = $item->inventory_item_id;
            if (!isset($originalQuantities[$itemId])) {
                $originalQuantities[$itemId] = 0;
            }
            $originalQuantities[$itemId] += $item->quantity;
        }

        $salesOrders = SalesOrder::forBranch(auth()->user()->branch_id)->approved()->get();

        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        return view('sales.invoices.edit', compact('invoice', 'customers', 'inventoryItems', 'salesOrders', 'currencies', 'originalQuantities'))->with('items', $inventoryItems);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        if (!auth()->user()->can('edit sales invoices')) {
            abort(403, 'Unauthorized action.');
        }
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = SalesInvoice::findOrFail($invoiceId);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_45,net_60,custom',
            'payment_days' => 'required|integer|min:0',
            'reference_no' => 'nullable|string|max:255',
            'withholding_tax_enabled' => 'nullable|boolean',
            'withholding_tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->withholding_tax_type === 'percentage' && $value > 100) {
                        $fail('The withholding tax rate must not be greater than 100 when type is percentage.');
                    }
                },
            ],
            'withholding_tax_type' => 'nullable|in:percentage,fixed',
            'early_payment_discount_enabled' => 'nullable|boolean',
            'early_payment_discount_type' => 'nullable|in:percentage,fixed',
            'early_payment_discount_rate' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->early_payment_discount_type === 'percentage' && $value > 100) {
                        $fail('The early payment discount rate must not be greater than 100 when type is percentage.');
                    }
                },
            ],
            'early_payment_days' => 'nullable|integer|min:0',
            'late_payment_fees_enabled' => 'nullable|boolean',
            'late_payment_fees_type' => 'nullable|in:monthly,one_time',
            'late_payment_fees_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:inclusive,exclusive,no_vat',
            'items.*.vat_rate' => 'required|numeric|min:0|max:100',
            'items.*.discount_type' => 'nullable|in:percentage,fixed',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.price_tier' => 'nullable|in:retail,wholesale',
        ]);

        $tierErrors = InventoryItem::priceTierValidationErrors($request);
        if ($tierErrors !== []) {
            throw ValidationException::withMessages($tierErrors);
        }

        // Custom validation for stock availability
        $validator = \Validator::make($request->all(), []);

        // Resolve location (required for inventory movements and stock checks)
        $locationId = session('location_id');

        // Get existing invoice items to calculate stock that will be returned
        $existingItems = $invoice->items()->get();
        $originalQuantities = [];
        
        foreach ($existingItems as $existingItem) {
            $itemId = $existingItem->inventory_item_id;
            if (!isset($originalQuantities[$itemId])) {
                $originalQuantities[$itemId] = 0;
            }
            $originalQuantities[$itemId] += $existingItem->quantity;
        }

        $stockService = new \App\Services\InventoryStockService();
        foreach ($request->items as $index => $itemData) {
            $inventoryItem = InventoryItem::queryVisibleForSession()->find($itemData['inventory_item_id']);
            // Skip stock validation for service items or items that don't track stock
            if ($inventoryItem && 
                $inventoryItem->item_type !== 'service' && 
                $inventoryItem->track_stock && 
                $locationId) {
                // Use location-based stock service instead of current_stock
                $availableStock = $stockService->getItemStockAtLocation($inventoryItem->id, $locationId);
                
                // Add back the original quantity for this item if it existed in the old invoice
                // This accounts for the fact that the original invoice already deducted stock
                $originalQuantity = $originalQuantities[$inventoryItem->id] ?? 0;
                $adjustedAvailableStock = $availableStock + $originalQuantity;
                
                if ($itemData['quantity'] > $adjustedAvailableStock) {
                    $validator->errors()->add("items.{$index}.quantity",
                        "Insufficient stock for {$inventoryItem->name}. Available: {$adjustedAvailableStock}, Requested: {$itemData['quantity']}");
                }
            }
        }

        if ($validator->errors()->count() > 0) {
            // Get the first validation error message (typically the stock error)
            $firstErrorMessage = $validator->errors()->first();
            
            // If this is an AJAX request, return JSON (e.g. for API or JS clients)
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    // Return the specific stock error so the frontend (SweetAlert) can show it directly
                    'message' => $firstErrorMessage,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // For regular form submissions, redirect back with errors
            return redirect()->back()
                ->withInput()
                ->withErrors($validator->errors())
                ->with('error', $firstErrorMessage);
        }

        try {
            DB::beginTransaction();

            // Handle optional attachment upload (replace old file if new one uploaded)
            $attachmentPath = $invoice->attachment;
            if ($request->hasFile('attachment')) {
                if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                    Storage::disk('public')->delete($attachmentPath);
                }
                $file = $request->file('attachment');
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('sales-invoice-attachments', $fileName, 'public');
            }

            // Update invoice
            $invoice->update([
                'customer_id' => $request->customer_id,
                'sales_order_id' => $request->sales_order_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'payment_terms' => $request->payment_terms,
                'payment_days' => $request->payment_days,
                'reference_no' => $request->reference_no,
                // WHT is NOT set at invoice creation - it's only applied at payment/receipt time
                'withholding_tax_rate' => 0,
                'withholding_tax_type' => 'percentage',
                'early_payment_discount_enabled' => $request->early_payment_discount_enabled ?? false,
                'early_payment_discount_type' => $request->early_payment_discount_type ?? 'percentage',
                'early_payment_discount_rate' => $request->early_payment_discount_rate ?? 0,
                'early_payment_days' => $request->early_payment_days ?? 0,
                'late_payment_fees_enabled' => $request->late_payment_fees_enabled ?? false,
                'late_payment_fees_type' => $request->late_payment_fees_type ?? 'monthly',
                'late_payment_fees_rate' => $request->late_payment_fees_rate ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'attachment' => $attachmentPath,
                'updated_by' => auth()->id(),
            ]);

            // Restore cost layers before deleting movements (reverse the cost consumption)
            $costService = new InventoryCostService();
            $oldMovements = InventoryMovement::where('reference_type', 'sales_invoice')
                ->where('reference_id', $invoice->id)
                ->get();
            
            foreach ($oldMovements as $oldMovement) {
                // Restore the cost layers that were consumed for this movement
                $costService->restoreInventoryCostLayers(
                    $oldMovement->item_id,
                    $oldMovement->quantity,
                    $oldMovement->unit_cost,
                    'Sales Invoice: ' . $invoice->invoice_number
                );
            }

            // Delete existing inventory movements for this invoice (idempotency on edit)
            InventoryMovement::where('reference_type', 'sales_invoice')
                ->where('reference_id', $invoice->id)
                ->delete();

            $itemsCount = count($request->items);
            
            // Use job for large batches to avoid timeout and max_input_vars issues
            // Threshold can be adjusted: lower = more items processed synchronously, higher = more use async jobs
            $jobThreshold = config('queue.sales_invoice_job_threshold', 50);
            $useJob = $itemsCount >= $jobThreshold;

            if ($useJob) {
                \Log::info('Sales Invoice Update: Using job for large batch', [
                    'invoice_id' => $invoice->id,
                    'items_count' => $itemsCount
                ]);

                // Restore cost layers before deleting movements (reverse the cost consumption)
                $costService = new InventoryCostService();
                $oldMovements = InventoryMovement::where('reference_type', 'sales_invoice')
                    ->where('reference_id', $invoice->id)
                    ->get();
                
                foreach ($oldMovements as $oldMovement) {
                    // Restore the cost layers that were consumed for this movement
                    $costService->restoreInventoryCostLayers(
                        $oldMovement->item_id,
                        $oldMovement->quantity,
                        $oldMovement->unit_cost,
                        'Sales Invoice: ' . $invoice->invoice_number
                    );
                }

                // Delete existing inventory movements for this invoice
                InventoryMovement::where('reference_type', 'sales_invoice')
                    ->where('reference_id', $invoice->id)
                    ->delete();

                // Delete existing items (they will be recreated synchronously below)
                $invoice->items()->delete();

            } else {
                // Process items synchronously for smaller batches
                \Log::info('Sales Invoice Update: Processing items synchronously', [
                    'items_count' => $itemsCount
                ]);

                // Restore cost layers before deleting movements (reverse the cost consumption)
                $costService = new InventoryCostService();
                $oldMovements = InventoryMovement::where('reference_type', 'sales_invoice')
                    ->where('reference_id', $invoice->id)
                    ->get();
                
                foreach ($oldMovements as $oldMovement) {
                    // Restore the cost layers that were consumed for this movement
                    $costService->restoreInventoryCostLayers(
                        $oldMovement->item_id,
                        $oldMovement->quantity,
                        $oldMovement->unit_cost,
                        'Sales Invoice: ' . $invoice->invoice_number
                    );
                }

                // Delete existing inventory movements for this invoice (idempotency on edit)
                InventoryMovement::where('reference_type', 'sales_invoice')
                    ->where('reference_id', $invoice->id)
                    ->delete();

                // Delete existing items
                $invoice->items()->delete();

                // Consolidate lines that share the same item and price tier
                $consolidatedItems = [];
                foreach ($request->items as $itemData) {
                    $inventoryItemId = $itemData['inventory_item_id'];
                    $tier = InventoryItem::normalizedPriceTier($itemData['price_tier'] ?? null);
                    $itemData['price_tier'] = $tier;
                    $key = $inventoryItemId.'|'.$tier;

                    if (isset($consolidatedItems[$key])) {
                        $consolidatedItems[$key]['quantity'] += $itemData['quantity'];
                    } else {
                        $consolidatedItems[$key] = $itemData;
                    }
                }

                // Create new invoice items from consolidated data
            foreach ($consolidatedItems as $itemData) {
                $inventoryItem = InventoryItem::queryVisibleForSession()->find($itemData['inventory_item_id']);

                // Calculate line total before creating the item
                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $vatType = $itemData['vat_type'];
                $vatRate = $itemData['vat_rate'];
                // Calculate subtotal (no item-level discounts)
                $subtotal = $quantity * $unitPrice;

                // Calculate VAT and line total
                $vatAmount = 0;
                $lineTotal = 0;

                if ($vatType === 'no_vat') {
                    $lineTotal = $subtotal;
                } elseif ($vatType === 'exclusive') {
                    $vatAmount = $subtotal * ($vatRate / 100);
                    $lineTotal = $subtotal + $vatAmount;
                } else {
                    // VAT inclusive
                    $vatAmount = $subtotal * ($vatRate / (100 + $vatRate));
                    $lineTotal = $subtotal;
                }

                $invoiceItem = SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'description' => $inventoryItem->description,
                    'unit_of_measure' => $inventoryItem->unit_of_measure,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'price_tier' => InventoryItem::normalizedPriceTier($itemData['price_tier'] ?? null),
                    'line_total' => $lineTotal,
                    'vat_type' => $vatType,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'discount_type' => null,
                    'discount_rate' => 0,
                    'discount_amount' => 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // Check stock availability
                $invoiceItem->checkStockAvailability();
                $invoiceItem->save();

                // Create inventory movement for stock out (only for products)
                if ($inventoryItem->track_stock && $inventoryItem->item_type === 'product') {
                    $costService = new InventoryCostService();
                    $stockService = new \App\Services\InventoryStockService();
                    $balanceBefore = $stockService->getItemStockAtLocation($inventoryItem->id, session('location_id'));
                    $balanceAfter = $balanceBefore - $quantity;

                    // Get actual cost using FIFO/Weighted Average (same as in store method)
                    // Pass branch/location for fallback cost resolution (location → branch → default)
                    $costInfo = $costService->removeInventory(
                        $inventoryItem->id,
                        $quantity,
                        'sale',
                        'Sales Invoice: ' . $invoice->invoice_number,
                        $invoice->invoice_date,
                        $invoice->branch_id,
                        session('location_id')
                    );

                    InventoryMovement::create([
                        'item_id' => $inventoryItem->id,
                        'user_id' => auth()->id(),
                        'branch_id' => $invoice->branch_id,
                        'location_id' => session('location_id'),
                        'movement_type' => 'sold',
                        'quantity' => $quantity,
                        'unit_price' => $costInfo['average_unit_cost'],
                        'unit_cost' => $costInfo['average_unit_cost'],
                        'total_cost' => $costInfo['total_cost'],
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'reference' => 'Sales Invoice: ' . $invoice->invoice_number,
                        'reference_type' => 'sales_invoice',
                        'reference_id' => $invoice->id,
                        'notes' => 'Stock sold via sales invoice update',
                        'movement_date' => $invoice->invoice_date,
                    ]);

                    // Consume stock using FEFO if item tracks expiry
                    $consumedLayers = [];
                    $earliestExpiryDate = null;
                    $batchNumbers = [];
                    
                    if ($inventoryItem->track_expiry) {
                        $expiryService = new \App\Services\ExpiryStockService();
                        $consumedLayers = $expiryService->consumeStock(
                            $inventoryItem->id,
                            session('location_id'),
                            $quantity,
                            'FEFO'
                        );
                        
                        // Extract expiry information for display
                        if (!empty($consumedLayers)) {
                            $earliestExpiryDate = $consumedLayers[0]['expiry_date'];
                            $batchNumbers = array_column($consumedLayers, 'batch_number');
                        }
                        
                        // Log consumed layers for audit trail
                        foreach ($consumedLayers as $layer) {
                            \Log::info('Sales Invoice Update: Consumed stock with expiry', [
                                'invoice_id' => $invoice->id,
                                'item_id' => $inventoryItem->id,
                                'batch_number' => $layer['batch_number'],
                                'expiry_date' => $layer['expiry_date'],
                                'quantity' => $layer['quantity'],
                                'unit_cost' => $layer['unit_cost']
                            ]);
                        }
                    }

                    // Update invoice item with expiry information
                    $invoiceItem->update([
                        'batch_number' => !empty($batchNumbers) ? implode(', ', $batchNumbers) : null,
                        'expiry_date' => $earliestExpiryDate,
                        'expiry_consumption_details' => $consumedLayers,
                    ]);

                    // Stock is now tracked via movements, no need to update item directly
                }
            }
            }

            // Update invoice totals
            $invoice->updateTotals();
            
            // Refresh invoice to ensure all relationships are loaded
            $invoice->refresh();
            
            // Ensure movements are committed/visible before calculating COGS
            // Force a database query to ensure movements are visible in the transaction
            $movementCount = InventoryMovement::where('reference_type', 'sales_invoice')
                ->where('reference_id', $invoice->id)
                ->where('movement_type', 'sold')
                ->count();
            
            \Log::info('Sales Invoice Update: Movements before COGS calculation', [
                'invoice_id' => $invoice->id,
                'movement_count' => $movementCount
            ]);

            // Update double-entry transactions
            $invoice->createDoubleEntryTransactions();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sales invoice updated successfully!',
                    'redirect_url' => route('sales.invoices.show', $invoice->encoded_id)
                ]);
            }

            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('success', 'Sales invoice updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Sales Invoice Update Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update sales invoice: ' . $e->getMessage()
                ], 422);
            }

            return redirect()->back()
                ->with('error', 'Failed to update sales invoice: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encodedId)
    {
        if (!auth()->user()->can('delete sales invoices')) {
            abort(403, 'Unauthorized action.');
        }
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return response()->json(['success' => false, 'message' => 'Invalid invoice ID']);
        }

        try {
            DB::beginTransaction();

            $invoice = SalesInvoice::with(['receipts'])->findOrFail($invoiceId);

            // Check if invoice has paid status
            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a paid invoice. Please reverse all payments first before deleting the invoice. You can reverse payments from the invoice details page.'
                ], 422);
            }

            // Check if invoice has any payments (even if status is not 'paid')
            if ($invoice->receipts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete an invoice that has payments. Please reverse all payments first before deleting the invoice. You can reverse payments from the invoice details page.'
                ], 422);
            }

            // Delete inventory movements for this sales invoice (this handles stock reversal)
            InventoryMovement::where('reference_type', 'sales_invoice')
                ->where('reference_id', $invoice->id)
                ->delete();

            // Delete GL transactions
            $invoice->glTransactions()->delete();

            // Delete invoice items
            $invoice->items()->delete();

            // Delete invoice
            $invoice->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Sales invoice deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete sales invoice: ' . $e->getMessage()]);
        }
    }

    /**
     * Convert sales order to invoice
     */
    public function convertFromOrder($orderId)
    {
        try {
            $order = SalesOrder::with(['items.inventoryItem', 'customer'])->findOrFail($orderId);

            if ($order->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved orders can be converted to invoices'
                ]);
            }

            DB::beginTransaction();

            // Create invoice
            $invoice = SalesInvoice::create([
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($order->payment_days)->toDateString(),
                'status' => 'draft',
                'payment_terms' => $order->payment_terms,
                'payment_days' => $order->payment_days,
                'vat_rate' => $order->vat_rate,
                'vat_type' => $order->vat_type,
                'notes' => $order->notes,
                'terms_conditions' => $order->terms_conditions,
                'branch_id' => $order->branch_id,
                'company_id' => $order->company_id,
                'created_by' => auth()->id(),
            ]);

            // Create invoice items from order items
            foreach ($order->items as $orderItem) {
                // Calculate line total before creating the item
                $quantity = $orderItem->quantity;
                $unitPrice = $orderItem->unit_price;
                $vatType = $orderItem->vat_type;
                $vatRate = $orderItem->vat_rate;
                $discountType = $orderItem->discount_type;
                $discountRate = $orderItem->discount_rate ?? 0;

                // Calculate subtotal
                $subtotal = $quantity * $unitPrice;

                // Apply discount
                $discountAmount = 0;
                if ($discountType === 'percentage' && $discountRate > 0) {
                    $discountAmount = $subtotal * ($discountRate / 100);
                } elseif ($discountType === 'fixed') {
                    $discountAmount = $discountRate;
                }

                $discountedSubtotal = $subtotal - $discountAmount;

                // Calculate VAT and line total
                $vatAmount = 0;
                $lineTotal = 0;

                if ($vatType === 'no_vat') {
                    $lineTotal = $discountedSubtotal;
                } elseif ($vatType === 'exclusive') {
                    $vatAmount = $discountedSubtotal * ($vatRate / 100);
                    $lineTotal = $discountedSubtotal + $vatAmount;
                } else {
                    // VAT inclusive
                    $vatAmount = $discountedSubtotal * ($vatRate / (100 + $vatRate));
                    $lineTotal = $discountedSubtotal;
                }

                $invoiceItem = SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'inventory_item_id' => $orderItem->inventory_item_id,
                    'item_name' => $orderItem->item_name,
                    'item_code' => $orderItem->item_code,
                    'description' => $orderItem->description,
                    'unit_of_measure' => $orderItem->unit_of_measure,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'price_tier' => 'retail',
                    'line_total' => $lineTotal,
                    'vat_type' => $vatType,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'discount_type' => null,
                    'discount_rate' => 0,
                    'discount_amount' => 0,
                    'notes' => $orderItem->notes,
                ]);

                // Check stock availability
                $invoiceItem->checkStockAvailability();
                $invoiceItem->save();

                // Create inventory movement for stock out (only for products)
                $inventoryItem = InventoryItem::queryVisibleForSession()->find($orderItem->inventory_item_id);
                if ($inventoryItem && $inventoryItem->track_stock && $inventoryItem->item_type === 'product') {
                    // Get stock as of invoice date (for backdated invoices, this ensures correct balance calculation)
                    // Use the invoice's created_at timestamp to exclude same-day transactions that happened after this invoice
                    $stockService = new \App\Services\InventoryStockService();
                    $asOfTimestamp = $invoice->created_at ? $invoice->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
                    $balanceBefore = $stockService->getItemStockAtLocationAsOfDate(
                        $inventoryItem->id, 
                        session('location_id'), 
                        $invoice->invoice_date,
                        null,
                        $asOfTimestamp
                    );
                    $balanceAfter = $balanceBefore - $quantity;

                    // Use resolved cost (location → branch → default) so COGS matches branch/location pricing
                    $locationId = session('location_id');
                    $unitCost = $inventoryItem->getCostPriceForBranchOrLocation($invoice->branch_id, $locationId);
                    $totalCost = $quantity * $unitCost;

                    InventoryMovement::create([
                        'item_id' => $inventoryItem->id,
                        'user_id' => auth()->id(),
                        'branch_id' => $invoice->branch_id,
                        'location_id' => session('location_id'),
                        'movement_type' => 'sold',
                        'quantity' => $quantity,
                        'unit_price' => $unitCost,
                        'unit_cost' => $unitCost,
                        'total_cost' => $totalCost,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'reference' => 'Sales Invoice: ' . $invoice->invoice_number,
                        'reference_type' => 'sales_invoice',
                        'reference_id' => $invoice->id,
                        'notes' => 'Stock out due to order conversion to invoice',
                        'movement_date' => $invoice->invoice_date,
                    ]);

                    // Consume stock using FEFO if item tracks expiry
                    $consumedLayers = [];
                    $earliestExpiryDate = null;
                    $batchNumbers = [];
                    
                    if ($inventoryItem->track_expiry) {
                        $expiryService = new \App\Services\ExpiryStockService();
                        $consumedLayers = $expiryService->consumeStock(
                            $inventoryItem->id,
                            session('location_id'),
                            $quantity,
                            'FEFO'
                        );
                        
                        // Extract expiry information for display
                        if (!empty($consumedLayers)) {
                            $earliestExpiryDate = $consumedLayers[0]['expiry_date'];
                            $batchNumbers = array_column($consumedLayers, 'batch_number');
                        }
                        
                        // Log consumed layers for audit trail
                        foreach ($consumedLayers as $layer) {
                            \Log::info('Sales Invoice from Order: Consumed stock with expiry', [
                                'invoice_id' => $invoice->id,
                                'item_id' => $inventoryItem->id,
                                'batch_number' => $layer['batch_number'],
                                'expiry_date' => $layer['expiry_date'],
                                'quantity' => $layer['quantity'],
                                'unit_cost' => $layer['unit_cost']
                            ]);
                        }
                    }

                    // Update invoice item with expiry information
                    $invoiceItem->update([
                        'batch_number' => !empty($batchNumbers) ? implode(', ', $batchNumbers) : null,
                        'expiry_date' => $earliestExpiryDate,
                        'expiry_consumption_details' => $consumedLayers,
                    ]);

                    // Stock is now tracked via movements, no need to update item directly
                }
            }

            // Update invoice totals
            $invoice->updateTotals();

            // Create double-entry transactions
            $invoice->createDoubleEntryTransactions();

            // Update sales order status to converted_to_invoice
            $order->update([
                'status' => 'converted_to_invoice',
                'updated_by' => auth()->id(),
            ]);
            
            // Log conversion on order
            $customerName = $order->customer ? $order->customer->name : 'N/A';
            $order->logActivity('update', "Converted Sales Order {$order->order_number} to Invoice {$invoice->invoice_number}", [
                'Order Number' => $order->order_number,
                'Invoice Number' => $invoice->invoice_number,
                'Customer' => $customerName,
                'Order Total' => number_format($order->total_amount ?? 0, 2),
                'Invoice Total' => number_format($invoice->total_amount ?? 0, 2),
                'Invoice Date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'N/A',
                'Converted By' => auth()->user()->name,
                'Converted At' => now()->format('Y-m-d H:i:s')
            ]);
            
            // Also log on invoice
            if (method_exists($invoice, 'logActivity')) {
                $invoice->logActivity('create', "Created Sales Invoice {$invoice->invoice_number} from Sales Order {$order->order_number}", [
                    'Invoice Number' => $invoice->invoice_number,
                    'Sales Order Number' => $order->order_number,
                    'Customer' => $customerName,
                    'Invoice Date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : 'N/A',
                    'Due Date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A',
                    'Total Amount' => number_format($invoice->total_amount ?? 0, 2),
                    'Created By' => auth()->user()->name
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order converted to invoice successfully',
                'invoice_id' => $invoice->encoded_id,
                'redirect_url' => route('sales.invoices.show', $invoice->encoded_id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to convert order to invoice: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get inventory item details for AJAX
     */
    public function getInventoryItem($itemId)
    {
        $item = InventoryItem::queryVisibleForSession()->findOrFail($itemId);
        $branchId = session('branch_id') ?? (auth()->user()->branch_id ?? null);
        $locationId = session('location_id');

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'description' => $item->description,
                'unit_of_measure' => $item->unit_of_measure,
                'unit_price' => $item->getUnitPriceForBranchOrLocation($branchId, $locationId),
                'cost_price' => $item->getCostPriceForBranchOrLocation($branchId, $locationId),
                'available_stock' => $item->available_stock,
                'vat_rate' => $item->vat_rate ?? 18.00,
                'vat_type' => $item->vat_type ?? 'inclusive',
            ]
        ]);
    }

    /**
     * Record payment for invoice
     */
    public function recordPayment(Request $request, $encodedId)
    {
        if (!auth()->user()->can('edit sales invoices')) {
            abort(403, 'Unauthorized action.');
        }
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = SalesInvoice::findOrFail($invoiceId);

        // Check if invoice has credit notes applied
        $creditNoteApplications = \App\Models\Sales\CreditNoteApplication::where('sales_invoice_id', $invoice->id)->sum('amount_applied');
        $effectiveBalance = $invoice->balance_due - $creditNoteApplications;

        // Add warning if credit notes are applied
        if ($creditNoteApplications > 0) {
            $request->session()->flash('warning', "This invoice has credit notes applied totaling {$creditNoteApplications}. Effective balance available for payment: {$effectiveBalance}");
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $effectiveBalance,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank,cash_deposit',
            // bank field(s) are validated manually below so we can accept either chart_account_id or bank_account_id
            'cash_deposit_id' => 'nullable|in:customer_balance',
            'payment_exchange_rate' => 'nullable|numeric|min:0.000001',
            'description' => 'nullable|string|max:500',
            'chart_account_id' => 'nullable|integer',
            'bank_account_id' => 'nullable|integer',
            'wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Resolve bank account when payment method is bank
        if ($request->payment_method === 'bank') {
            $resolvedBankAccountId = null;

            if ($request->filled('chart_account_id')) {
                $bankAccount = BankAccount::where('chart_account_id', $request->chart_account_id)->first();
                if (!$bankAccount) {
                    return redirect()->back()
                        ->with('error', 'Invalid bank account selected (by chart account).')
                        ->withInput();
                }
                $resolvedBankAccountId = $bankAccount->id;
            } elseif ($request->filled('bank_account_id')) {
                $bankAccount = BankAccount::find($request->bank_account_id);
                if (!$bankAccount) {
                    return redirect()->back()
                        ->with('error', 'Invalid bank account selected.')
                        ->withInput();
                }
                $resolvedBankAccountId = $bankAccount->id;
            } else {
                return redirect()->back()
                    ->with('error', 'Please select a bank account.')
                    ->withInput();
            }

            // Ensure downstream code uses bank_account_id
            $request->merge(['bank_account_id' => $resolvedBankAccountId]);
        }

        try {
            DB::beginTransaction();

            // Check if early payment discount should be applied
            $paymentAmount = $request->amount;
            $earlyPaymentDiscount = 0;
            
            if ($invoice->early_payment_discount_enabled && $invoice->isEarlyPaymentDiscountValid()) {
                $calculatedDiscount = $invoice->calculateEarlyPaymentDiscount();
                if ($calculatedDiscount > 0) {
                    // Check if the payment amount matches the suggested early payment amount
                    $suggestedAmount = $invoice->getAmountDueWithEarlyDiscount();
                    if (abs($request->amount - $suggestedAmount) < 0.01) {
                        // Payment matches early payment discount amount, apply the discount
                        $earlyPaymentDiscount = $calculatedDiscount;
                        $paymentAmount = $request->amount + $earlyPaymentDiscount; // Add discount to payment amount
                    }
                }
            }

            if ($request->payment_method === 'cash_deposit') {
                // Validate customer has sufficient cash deposit balance (only actual cash deposits)
                $customer = $invoice->customer;
                $availableCashDeposits = $customer->cash_deposit_balance;
                
                if ($availableCashDeposits < $paymentAmount) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', "Insufficient cash deposit balance. Available cash deposits: " . number_format($availableCashDeposits, 2) . " TSh, Required: " . number_format($paymentAmount, 2) . " TSh. Note: Only actual cash deposits can be used for cash deposit payments.")
                        ->withInput();
                }
                // Cash Deposit Payment - Use Journal system
                // Pass a flag to indicate cash deposit payment instead of nullifying
                $cashDepositId = ($request->cash_deposit_id === 'customer_balance') ? 'customer_balance' : $request->cash_deposit_id;

                $payment = $invoice->recordPayment(
                    $paymentAmount, // Use adjusted payment amount
                    $request->payment_date,
                    null,
                    $request->description,
                    $cashDepositId,
                    $request->payment_exchange_rate
                );

                $result = $payment;
            } else {
                // Bank Payment - Save in Receipt table (with WHT support)
                // Use VAT mode and rate from invoice (set when invoice was created)
                $invoiceVatMode = $invoice->getVatMode();
                $invoiceVatRate = $invoice->getVatRate();
                
                $receipt = $invoice->recordPayment(
                    $paymentAmount, // Use adjusted payment amount
                    $request->payment_date,
                    $request->bank_account_id,
                    $request->description,
                    null,
                    $request->payment_exchange_rate,
                    $request->wht_treatment ?? 'EXCLUSIVE',
                    (float) ($request->wht_rate ?? 0),
                    $invoiceVatMode,
                    $invoiceVatRate
                );

                $result = $receipt;
            }

            DB::commit();

            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('success', 'Payment recorded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to record payment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show payment form
     */
    public function showPaymentForm($encodedId)
    {
        if (!auth()->user()->can('edit sales invoices')) {
            abort(403, 'Unauthorized action.');
        }
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = SalesInvoice::with(['customer.collaterals.type'])->findOrFail($invoiceId);

        // Limit bank accounts by branch scope (all branches or current branch)
        $user = auth()->user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $bankAccounts = \App\Models\BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();
        
        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        return view('sales.invoices.payment', compact('invoice', 'bankAccounts', 'currencies'));
    }

    /**
     * Get customer cash deposits for payment
     */
    public function getCustomerCashDeposits($customerId)
    {
        try {
            $decodedCustomerId = Hashids::decode($customerId)[0] ?? null;

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
     * Apply late payment fees to a specific invoice
     */
    public function applyLatePaymentFees($encodedId)
    {
        if (!auth()->user()->can('edit sales invoices')) {
            abort(403, 'Unauthorized action.');
        }

        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = SalesInvoice::findOrFail($invoiceId);

        if (!$invoice->late_payment_fees_enabled) {
            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('error', 'Late payment fees are not enabled for this invoice');
        }

        if (!$invoice->isOverdue()) {
            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('error', 'This invoice is not overdue');
        }

        try {
            DB::beginTransaction();

            $applied = $invoice->applyLatePaymentFees();

            if ($applied) {
                $feeAmount = $invoice->calculateLatePaymentFees();
                DB::commit();

                return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                    ->with('success', "Late payment fees of TZS " . number_format($feeAmount, 2) . " have been applied successfully!");
            } else {
                DB::rollBack();
                return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                    ->with('error', 'Late payment fees have already been applied for this period or no fees are due');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('error', 'Failed to apply late payment fees: ' . $e->getMessage());
        }
    }

    /**
     * Show payment edit form
     */
    public function editPayment($paymentId)
    {
        $paymentId = Hashids::decode($paymentId)[0] ?? null;

        if (!$paymentId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid payment ID');
        }

        $payment = Payment::with(['customer', 'bankAccount', 'cashDeposit.type'])->findOrFail($paymentId);

        // Check if this is an invoice payment
        if ($payment->reference_type !== 'sales_invoice') {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid payment type');
        }

        $invoice = SalesInvoice::where('invoice_number', $payment->reference)->first();
        if (!$invoice) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invoice not found');
        }

        $bankAccounts = \App\Models\BankAccount::orderBy('name')->get();

        return view('sales.invoices.edit-payment', compact('payment', 'invoice', 'bankAccounts'));
    }

    /**
     * Update payment
     */
    public function updatePayment(Request $request, $paymentId)
    {
        $paymentId = Hashids::decode($paymentId)[0] ?? null;

        if (!$paymentId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid payment ID');
        }

        $payment = Payment::findOrFail($paymentId);

        // Check if this is an invoice payment
        if ($payment->reference_type !== 'sales_invoice') {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid payment type');
        }

        $invoice = SalesInvoice::where('invoice_number', $payment->reference)->first();
        if (!$invoice) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invoice not found');
        }

        // Calculate the maximum allowed payment amount
        // This should be the current balance due plus the existing payment amount
        $currentBalanceDue = $invoice->balance_due;
        $existingPaymentAmount = $payment->amount;
        $maxAllowedAmount = $currentBalanceDue + $existingPaymentAmount;

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $maxAllowedAmount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank,cash_deposit',
            // accept either chart_account_id or bank_account_id when payment_method is bank
            'cash_deposit_id' => 'nullable|in:customer_balance',
            'description' => 'nullable|string|max:500',
            'chart_account_id' => 'nullable|integer',
            'bank_account_id' => 'nullable|integer',
            'payment_exchange_rate' => 'nullable|numeric|min:0.000001',
        ]);

        // Resolve bank account when payment method is bank
        if ($request->payment_method === 'bank') {
            $resolvedBankAccountId = null;

            if ($request->filled('chart_account_id')) {
                $bankAccount = \App\Models\BankAccount::where('chart_account_id', $request->chart_account_id)->first();
                if (!$bankAccount) {
                    return redirect()->back()
                        ->with('error', 'Invalid bank account selected (by chart account).')
                        ->withInput();
                }
                $resolvedBankAccountId = $bankAccount->id;
            } elseif ($request->filled('bank_account_id')) {
                $bankAccount = \App\Models\BankAccount::find($request->bank_account_id);
                if (!$bankAccount) {
                    return redirect()->back()
                        ->with('error', 'Invalid bank account selected.')
                        ->withInput();
                }
                $resolvedBankAccountId = $bankAccount->id;
            } else {
                return redirect()->back()
                    ->with('error', 'Please select a bank account.')
                    ->withInput();
            }

            // Ensure downstream code uses bank_account_id
            $request->merge(['bank_account_id' => $resolvedBankAccountId]);
        }

        try {
            DB::beginTransaction();

            // Calculate the difference in amount
            $amountDifference = $request->amount - $payment->amount;

            // Handle cash_deposit_id for customer_balance case
            $cashDepositId = ($request->cash_deposit_id === 'customer_balance') ? null : $request->cash_deposit_id;

            // Update payment record
            $payment->update([
                'amount' => $request->amount,
                'date' => $request->payment_date,
                'bank_account_id' => $request->bank_account_id,
                'cash_deposit_id' => $cashDepositId,
                'description' => $request->description,
            ]);

            // Update invoice paid amount
            $invoice->increment('paid_amount', $amountDifference);
            $invoice->balance_due = $invoice->total_amount - $invoice->paid_amount;

            // Update status if fully paid
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'sent';
            }
            $invoice->save();

            // Update GL transactions (delete old ones and create new ones)
            $payment->glTransactions()->delete();

            // Check if early payment discount should be applied to the updated payment
            $paymentAmount = $request->amount;
            $earlyPaymentDiscount = 0;
            
            if ($invoice->early_payment_discount_enabled && $invoice->isEarlyPaymentDiscountValid()) {
                $calculatedDiscount = $invoice->calculateEarlyPaymentDiscount();
                if ($calculatedDiscount > 0) {
                    // Check if the payment amount matches the suggested early payment amount
                    $suggestedAmount = $invoice->getAmountDueWithEarlyDiscount();
                    if (abs($request->amount - $suggestedAmount) < 0.01) {
                        // Payment matches early payment discount amount, apply the discount
                        $earlyPaymentDiscount = $calculatedDiscount;
                        $paymentAmount = $request->amount + $earlyPaymentDiscount; // Add discount to payment amount
                    }
                }
            }

            if ($request->payment_method === 'cash_deposit') {
                $invoice->createCashDepositPaymentGlTransactions($payment, $paymentAmount, $request->payment_date, $request->description);
                // Reduce specific cash deposit balance if selected
                if (!empty($request->cash_deposit_id) && $request->cash_deposit_id !== 'customer_balance') {
                    $deposit = \App\Models\CashDeposit::find($request->cash_deposit_id);
                    if ($deposit) {
                        $deposit->decrement('amount', $request->amount);
                    }
                }
            } else {
                $invoice->createBankPaymentGlTransactionsForPayment($payment, $paymentAmount, $request->payment_date, $request->description);
            }

            // Create early payment discount GL transactions if applicable
            $invoice->createEarlyPaymentDiscountTransactions($paymentAmount, $request->payment_date);

            DB::commit();

            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('success', 'Payment updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update payment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Reverse payment
     */
    public function reversePayment(Request $request, $encodedId)
    {
        if (!auth()->user()->can('edit sales invoices')) {
            abort(403, 'Unauthorized action.');
        }
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = SalesInvoice::findOrFail($invoiceId);

        $request->validate([
            'receipt_id' => 'required|exists:receipts,id',
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $invoice->reversePayment($request->receipt_id, $request->reason);

            DB::commit();

            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('success', 'Payment reversed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to reverse payment: ' . $e->getMessage());
        }
    }

    /**
     * Delete individual payment (receipt)
     */
    public function deletePayment(Request $request, $encodedId)
    {
        if (!auth()->user()->can('edit sales invoices')) {
            abort(403, 'Unauthorized action.');
        }
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return response()->json(['success' => false, 'message' => 'Invalid invoice ID']);
        }

        $invoice = SalesInvoice::findOrFail($invoiceId);

        $request->validate([
            'receipt_id' => 'required|exists:receipts,id',
        ]);

        try {
            DB::beginTransaction();

            $receipt = \App\Models\Receipt::findOrFail($request->receipt_id);

            // Verify the receipt belongs to this invoice (match by ID or invoice number)
            if ($receipt->reference_type != 'sales_invoice' || ($receipt->reference != $invoice->id && $receipt->reference_number != $invoice->invoice_number)) {
                throw new \Exception('Receipt does not belong to this invoice');
            }

            // Hard delete: remove GL entries and receipt, adjust invoice totals without creating reversal entries
            $amount = $receipt->amount;
            // Delete GL transactions tied to this receipt
            $receipt->glTransactions()->delete();

            // Adjust invoice paid and balance
            $invoice->decrement('paid_amount', $amount);
            $invoice->balance_due = $invoice->total_amount - $invoice->paid_amount;
            $invoice->status = ($invoice->paid_amount >= $invoice->total_amount) ? 'paid' : 'sent';
            $invoice->save();

            // If this receipt originated from a cash deposit payment, restore deposit (not typical for receipts, but safe check)
            if (!empty($receipt->cash_deposit_id)) {
                $deposit = \App\Models\CashDeposit::find($receipt->cash_deposit_id);
                if ($deposit) {
                    $deposit->increment('amount', $amount);
                }
            }

            // Delete the receipt record
            $receipt->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete invoice payment
     */
    public function deleteInvoicePayment(Request $request, $encodedId)
    {
        $paymentId = Hashids::decode($encodedId)[0] ?? null;

        if (!$paymentId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment ID'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment = Payment::findOrFail($paymentId);

            // Verify this is an invoice payment
            if ($payment->reference_type !== 'sales_invoice') {
                throw new \Exception('Invalid payment type');
            }

            // Get the invoice
            $invoice = SalesInvoice::where('invoice_number', $payment->reference)->first();
            if (!$invoice) {
                throw new \Exception('Invoice not found');
            }

            // Delete GL transactions for this payment
            $payment->glTransactions()->delete();

            // Update invoice paid amount
            $invoice->decrement('paid_amount', $payment->amount);
            $invoice->balance_due = $invoice->total_amount - $invoice->paid_amount;

            // Update status if needed
            if ($invoice->paid_amount <= 0) {
                $invoice->status = 'sent';
            } else {
                $invoice->status = 'sent'; // Partial payment
            }
            $invoice->save();

            // If this payment used a specific cash deposit, restore that deposit balance
            if (!empty($payment->cash_deposit_id)) {
                $deposit = \App\Models\CashDeposit::find($payment->cash_deposit_id);
                if ($deposit) {
                    $deposit->increment('amount', $payment->amount);
                }
            }

            // Delete the payment
            $payment->delete();

            $invoice->syncLinkedOpeningBalance();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove a cash-deposit journal payment from this invoice (reverses paid_amount and restores deposit in GL).
     */
    public function deleteCashDepositJournalPayment(Request $request, string $encodedInvoiceId, Journal $journal)
    {
        if (!auth()->user()->can('edit sales invoices')) {
            abort(403, 'Unauthorized action.');
        }

        $invoiceId = Hashids::decode($encodedInvoiceId)[0] ?? null;
        if (!$invoiceId) {
            return response()->json(['success' => false, 'message' => 'Invalid invoice ID'], 422);
        }

        $invoice = SalesInvoice::where('company_id', auth()->user()->company_id)->findOrFail($invoiceId);

        if (
            $journal->reference_type !== 'sales_invoice_payment'
            || (string) $journal->reference !== (string) $invoice->invoice_number
            || (int) $journal->customer_id !== (int) $invoice->customer_id
        ) {
            return response()->json(['success' => false, 'message' => 'This journal entry is not a cash deposit payment for this invoice.'], 422);
        }

        try {
            DB::transaction(function () use ($journal, $invoice) {
                $invoice->reverseCashDepositJournalPayment($journal);

                GlTransaction::where('transaction_id', $journal->id)
                    ->where('transaction_type', 'journal')
                    ->delete();

                $journal->items()->delete();
                $journal->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Cash deposit payment removed. Invoice totals and customer deposit balance have been updated.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove payment: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send invoice email to customer
     */
    public function sendEmail(Request $request, $encodedId): JsonResponse
    {
        try {
            $invoiceId = Hashids::decode($encodedId);
            if (empty($invoiceId)) {
                return response()->json(['success' => false, 'message' => 'Invalid invoice ID'], 400);
            }

            $invoice = SalesInvoice::with(['customer', 'items'])->find($invoiceId[0]);
            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'subject' => 'nullable|string|max:255',
                'message' => 'nullable|string',
                'email' => 'nullable|email',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            // Use provided email or customer email
            $email = $request->email ?? $invoice->customer->email;
            if (!$email) {
                return response()->json(['success' => false, 'message' => 'No email address available for customer'], 400);
            }

            // Send email
            Mail::to($email)->send(new SalesInvoiceMail(
                $invoice,
                $request->subject,
                $request->message
            ));

            // Update invoice status to sent if it was draft
            if ($invoice->status === 'draft') {
                $invoice->update(['status' => 'sent']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice email sent successfully to ' . $email
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending invoice email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing a receipt
     */
    public function editReceipt($encodedId)
    {
        $receiptId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$receiptId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid receipt ID');
        }
        
        $receipt = \App\Models\Receipt::with(['user', 'bankAccount'])->findOrFail($receiptId);

        // Check if this is an invoice receipt
        if ($receipt->reference_type !== 'sales_invoice') {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid receipt type');
        }

        $invoice = SalesInvoice::where('id', $receipt->reference)->first();
        if (!$invoice) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invoice not found');
        }

        // Limit bank accounts by branch scope (all branches or current branch)
        $user = auth()->user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $bankAccounts = \App\Models\BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();

        return view('sales.invoices.edit-receipt', compact('receipt', 'invoice', 'bankAccounts'));
    }

    /**
     * Update receipt
     */
    public function updateReceipt(Request $request, $encodedId)
    {
        $receiptId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$receiptId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid receipt ID');
        }
        
        $receipt = \App\Models\Receipt::findOrFail($receiptId);

        // Check if this is an invoice receipt
        if ($receipt->reference_type !== 'sales_invoice') {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid receipt type');
        }

        $invoice = SalesInvoice::where('id', $receipt->reference)->first();
        if (!$invoice) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invoice not found');
        }

        // Calculate the maximum allowed payment amount
        // This should be the current balance due plus the existing receipt amount
        $currentBalanceDue = $invoice->balance_due;
        $existingReceiptAmount = $receipt->amount;
        $maxAllowedAmount = $currentBalanceDue + $existingReceiptAmount;

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $maxAllowedAmount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank,cash_deposit',
            'bank_account_id' => 'required_if:payment_method,bank|nullable|exists:bank_accounts,id',
            'cash_deposit_id' => 'nullable',
            'description' => 'nullable|string|max:500',
            'wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'payment_exchange_rate' => 'nullable|numeric|min:0.000001',
        ]);

        try {
            DB::beginTransaction();

            // Calculate the difference in amount
            $amountDifference = $request->amount - $receipt->amount;

            // Get VAT mode and rate from invoice (set when invoice was created)
            $invoiceVatMode = $invoice->getVatMode();
            $invoiceVatRate = $invoice->getVatRate();

            // Calculate WHT if applicable (only for bank payments)
            $whtService = new \App\Services\WithholdingTaxService();
            $whtTreatment = $request->payment_method === 'bank' ? ($request->wht_treatment ?? 'EXCLUSIVE') : 'EXCLUSIVE';
            $whtRate = $request->payment_method === 'bank' ? ((float) ($request->wht_rate ?? 0)) : 0;
            
            $whtAmount = 0;
            $whtResult = null;
            $netReceivable = $request->amount;
            $vatAmount = 0;
            $baseAmount = $request->amount;
            
            if ($request->payment_method === 'bank' && $whtTreatment !== 'NONE' && $whtRate > 0) {
                $whtResult = $whtService->calculateWHTForAR(
                    (float) $request->amount,
                    $whtRate,
                    $whtTreatment,
                    $invoiceVatMode,
                    (float) $invoiceVatRate
                );
                $whtAmount = $whtResult['wht_amount'];
                $netReceivable = $whtResult['net_receivable'];
                $vatAmount = $whtResult['vat_amount'] ?? 0;
                $baseAmount = $whtResult['base_amount'] ?? $request->amount;
            } elseif ($request->payment_method === 'bank') {
                // Calculate VAT amount even if no WHT
                if ($invoiceVatMode === 'INCLUSIVE' && $invoiceVatRate > 0) {
                    $baseAmount = round($request->amount / (1 + ($invoiceVatRate / 100)), 2);
                    $vatAmount = round($request->amount - $baseAmount, 2);
                    $netReceivable = $request->amount; // Net receivable is the full amount when VAT is inclusive
                } elseif ($invoiceVatMode === 'EXCLUSIVE' && $invoiceVatRate > 0) {
                    $baseAmount = $request->amount;
                    $vatAmount = round($request->amount * ($invoiceVatRate / 100), 2);
                    $netReceivable = $request->amount; // Net receivable is the full amount
                } else {
                    $netReceivable = $request->amount;
                }
            }

            // Get functional currency and invoice currency
            $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $invoice->company->functional_currency ?? 'TZS');
            $invoiceCurrency = $invoice->currency ?? $functionalCurrency;
            
            // Get payment exchange rate using FxTransactionRateService (use payment date, not invoice date)
            $fxTransactionRateService = app(FxTransactionRateService::class);
            $userProvidedRate = $request->filled('payment_exchange_rate') ? (float) $request->payment_exchange_rate : null;
            $rateResult = $fxTransactionRateService->getTransactionRate(
                $invoiceCurrency,
                $functionalCurrency,
                $request->payment_date, // Use payment date, not invoice date
                $invoice->company_id,
                $userProvidedRate
            );
            $paymentExchangeRate = $rateResult['rate'];
            
            $invoiceExchangeRate = $invoice->exchange_rate ?? 1.000000;
            $needsConversion = ($invoiceCurrency !== $functionalCurrency && $invoiceExchangeRate != 1.000000);
            
            $convertToLCY = function($fcyAmount) use ($needsConversion, $paymentExchangeRate) {
                return $needsConversion ? round($fcyAmount * $paymentExchangeRate, 2) : $fcyAmount;
            };

            // Update receipt record (bank keeps bank_account_id; cash_deposit clears it)
            $receipt->update([
                'amount' => $request->amount,
                'date' => $request->payment_date,
                'bank_account_id' => $request->payment_method === 'bank' ? $request->bank_account_id : null,
                'description' => $request->description,
                'wht_treatment' => $request->payment_method === 'bank' ? $whtTreatment : null,
                'wht_rate' => $request->payment_method === 'bank' ? $whtRate : 0,
                'wht_amount' => $request->payment_method === 'bank' ? $whtAmount : 0,
                'net_receivable' => $request->payment_method === 'bank' ? $netReceivable : $request->amount,
                'vat_mode' => $request->payment_method === 'bank' ? $invoiceVatMode : null,
                'vat_amount' => $request->payment_method === 'bank' ? $vatAmount : 0,
                'base_amount' => $request->payment_method === 'bank' ? $baseAmount : $request->amount,
                'currency' => $invoiceCurrency,
                'exchange_rate' => $invoiceExchangeRate,
                'amount_fcy' => $needsConversion ? $request->amount : null,
                'amount_lcy' => $convertToLCY($request->amount),
            ]);

            // Update invoice paid amount
            $invoice->increment('paid_amount', $amountDifference);
            $invoice->balance_due = $invoice->total_amount - $invoice->paid_amount;

            // Update status if fully paid
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'sent';
            }
            $invoice->save();

            // Check if early payment discount should be applied to the updated receipt
            $paymentAmount = $request->amount;
            $earlyPaymentDiscount = 0;
            
            if ($invoice->early_payment_discount_enabled && $invoice->isEarlyPaymentDiscountValid()) {
                $calculatedDiscount = $invoice->calculateEarlyPaymentDiscount();
                if ($calculatedDiscount > 0) {
                    // Check if the payment amount matches the suggested early payment amount
                    $suggestedAmount = $invoice->getAmountDueWithEarlyDiscount();
                    if (abs($request->amount - $suggestedAmount) < 0.01) {
                        // Payment matches early payment discount amount, apply the discount
                        $earlyPaymentDiscount = $calculatedDiscount;
                        $paymentAmount = $request->amount + $earlyPaymentDiscount; // Add discount to payment amount
                    }
                }
            }

            // Update GL transactions (delete old ones and create new ones)
            $receipt->glTransactions()->delete();
            if ($request->payment_method === 'bank') {
                // Use SalesInvoice's createBankPaymentGlTransactions which correctly credits Trade Receivables
                // Pass full amount (before WHT) so GL function can properly calculate bank debit and receivable credit
                // Pass payment exchange rate for FX gain/loss calculation
                $invoice->createBankPaymentGlTransactions($receipt, $request->amount, $request->payment_date, $request->description ?: "Receipt for Invoice #{$invoice->invoice_number}", $whtAmount, $whtTreatment, $paymentExchangeRate);
            } else {
                // Create a Payment record to represent cash deposit payment for GL
                $user = auth()->user();
                $payment = \App\Models\Payment::create([
                    'customer_id' => $invoice->customer_id,
                    'amount' => $request->amount,
                    'date' => $request->payment_date,
                    'reference' => $invoice->invoice_number,
                    'reference_type' => 'sales_invoice',
                    'description' => $request->description,
                    'branch_id' => $invoice->branch_id,
                    'user_id' => $user ? $user->id : ($invoice->created_by ?? 1),
                    'bank_account_id' => null,
                    'cash_deposit_id' => $request->cash_deposit_id === 'customer_balance' ? null : $request->cash_deposit_id,
                ]);
                $invoice->createCashDepositPaymentGlTransactions($payment, $paymentAmount, $request->payment_date, $request->description);
            }

            // Create early payment discount GL transactions if applicable
            $invoice->createEarlyPaymentDiscountTransactions($paymentAmount, $request->payment_date);

            DB::commit();

            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('success', 'Receipt updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update receipt: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get sales order details for invoice creation
     */
    public function getSalesOrderDetails($orderId)
    {
        try {
            $order = SalesOrder::with(['customer', 'items.inventoryItem'])
                ->whereDoesntHave('invoice')
                ->where('status', '!=', 'converted_to_invoice')
                ->findOrFail($orderId);

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer' => [
                        'id' => $order->customer->id,
                        'name' => $order->customer->name,
                        'email' => $order->customer->email,
                        'phone' => $order->customer->phone,
                    ],
                    'payment_terms' => $order->payment_terms,
                    'payment_days' => $order->payment_days,
                    'notes' => $order->notes,
                    'terms_conditions' => $order->terms_conditions,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'inventory_item_id' => $item->inventory_item_id,
                            'item_name' => $item->item_name,
                            'item_code' => $item->item_code,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'vat_type' => $item->vat_type,
                            'vat_rate' => $item->vat_rate,
                            'vat_amount' => $item->vat_amount,
                            'discount_type' => $item->discount_type,
                            'discount_rate' => $item->discount_rate,
                            'discount_amount' => $item->discount_amount,
                            'line_total' => $item->line_total,
                            'notes' => $item->notes,
                        ];
                    }),
                    'subtotal' => $order->subtotal,
                    'vat_amount' => $order->vat_amount,
                    'tax_amount' => $order->tax_amount,
                    'discount_amount' => $order->discount_amount,
                    'total_amount' => $order->total_amount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching sales order details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export invoice as PDF
     */
    public function exportPdf($encodedId)
    {
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = SalesInvoice::with([
            'customer',
            'items.inventoryItem',
            'branch',
            'company',
            'createdBy',
            'updatedBy',
            'glTransactions.chartAccount',
            'salesOrder.proforma'
        ])->findOrFail($invoiceId);

        // Load receipts separately since receipts() is a query method, not a relationship
        $receipts = $invoice->receipts()->with(['bankAccount', 'user'])->orderBy('date', 'asc')->orderBy('created_at', 'asc')->get();

        $unpaidInvoices = SalesInvoice::where('customer_id', $invoice->customer_id)
            ->whereColumn('total_amount', '>', 'paid_amount')
            ->where('id', '!=', $invoice->id)
            ->get();

        // Get bank accounts for mobile money details
        $bankAccounts = \App\Models\BankAccount::all();

        // Apply paper size/orientation from settings
        $pageSize = strtoupper((string) (\App\Models\SystemSetting::getValue('document_page_size', 'A5')));
        $orientation = strtolower((string) (\App\Models\SystemSetting::getValue('document_orientation', 'portrait')));
        
        // Get margins from settings and convert cm to mm for dompdf
        $marginTopStr = \App\Models\SystemSetting::getValue('document_margin_top', '2.54cm');
        $marginRightStr = \App\Models\SystemSetting::getValue('document_margin_right', '2.54cm');
        $marginBottomStr = \App\Models\SystemSetting::getValue('document_margin_bottom', '2.54cm');
        $marginLeftStr = \App\Models\SystemSetting::getValue('document_margin_left', '2.54cm');
        
        // Convert cm to mm (dompdf expects mm)
        $convertToMm = function($value) {
            if (is_numeric($value)) {
                return (float) $value; // Assume already in mm
            }
            // Remove 'cm' and convert to mm
            $numeric = (float) str_replace(['cm', 'mm', 'pt', 'px', 'in'], '', $value);
            if (strpos($value, 'cm') !== false) {
                return $numeric * 10; // Convert cm to mm
            }
            return $numeric; // Already in mm or other unit
        };
        
        $marginTop = $convertToMm($marginTopStr);
        $marginRight = $convertToMm($marginRightStr);
        $marginBottom = $convertToMm($marginBottomStr);
        $marginLeft = $convertToMm($marginLeftStr);

        try {
            $pdf = \PDF::loadView('sales.invoices.pdf', compact('invoice', 'unpaidInvoices', 'bankAccounts', 'receipts'));
            $pdf->setPaper($pageSize, $orientation);
            
            // Set margins programmatically using setOptions (dompdf expects numeric values in mm)
            $pdf->setOptions([
                'margin-top' => $marginTop,
                'margin-right' => $marginRight,
                'margin-bottom' => $marginBottom,
                'margin-left' => $marginLeft,
            ]);
            
            // Always force download (match purchase invoice behavior)
            // Generate filename with customer name
            $customerName = $invoice->customer ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice->customer->name) : 'Unknown';
            $filename = 'SalesInvoice_for_' . $customerName . '_' . date('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('PDF generation error: ' . $e->getMessage());
            return redirect()->route('sales.invoices.show', $invoice->encoded_id)
                ->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Print a POS-style receipt for a specific invoice receipt entry
     */
    public function printReceipt($encodedId)
    {
        $receiptId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$receiptId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid receipt ID');
        }
        
        $receipt = \App\Models\Receipt::with(['bankAccount', 'user'])->findOrFail($receiptId);
        $invoice = SalesInvoice::with(['customer', 'branch', 'company'])
            ->where('id', $receipt->reference)
            ->firstOrFail();

        return view('sales.invoices.receipt', compact('invoice', 'receipt'));
    }

    /**
     * Print invoice in A5 format
     */
    public function printInvoice(Request $request, $encodedId)
    {
        $invoiceId = Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = SalesInvoice::with([
            'customer',
            'items.inventoryItem',
            'branch',
            'company',
            'createdBy'
        ])->findOrFail($invoiceId);

        // Get bank accounts for mobile money details
        $bankAccounts = \App\Models\BankAccount::all();

        // A4 or A5 from query (default A4 if not specified)
        $printSize = strtolower($request->query('size', 'a4'));
        if (!in_array($printSize, ['a4', 'a5'])) {
            $printSize = 'a4';
        }

        return view('sales.invoices.print', compact('invoice', 'bankAccounts', 'printSize'));
    }

    /**
     * Print current sales invoice to ESC/POS (USB) as a POS receipt.
     */
    public function posPrint(Request $request, string $encodedId): RedirectResponse
    {
        if (!auth()->user()->can('view sales invoices')) {
            abort(403, 'Unauthorized action.');
        }

        $invoiceId = Hashids::decode($encodedId)[0] ?? null;
        if (!$invoiceId) {
            return redirect()->route('sales.invoices.index')->with('error', 'Invalid invoice ID');
        }

        $invoice = SalesInvoice::with([
            'customer',
            'items',
            'branch',
            'company',
            'createdBy',
        ])->findOrFail($invoiceId);

        $device = (string) config('escpos.device', '/dev/usb/lp1');
        $currency = strtoupper(trim((string) ($invoice->currency ?: 'TZS')));

        try {
            $usb = new EscposUsbPrinter($device);
            $usb->run(function (Printer $p) use ($invoice, $currency): void {
                $width = 42;
                $line = str_repeat('-', $width) . "\n";

                $company = $invoice->company?->name ?: config('app.name', 'SMARTACCOUNTING');
                $branch = $invoice->branch?->name;

                $p->setJustification(Printer::JUSTIFY_CENTER);
                $p->setEmphasis(true);
                $p->text(self::posTrim($company, $width) . "\n");
                $p->setEmphasis(false);
                if ($branch) {
                    $p->text(self::posTrim($branch, $width) . "\n");
                }
                $p->text($line);

                $p->setJustification(Printer::JUSTIFY_LEFT);
                $p->text("Invoice: {$invoice->invoice_number}\n");
                $p->text("Date: " . ($invoice->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d')) . "\n");
                $p->text("Customer: " . self::posTrim((string) $invoice->customer?->name, $width - 10) . "\n");
                $p->text($line);

                // Items
                foreach ($invoice->items as $item) {
                    $name = (string) ($item->item_name ?: $item->item_code ?: 'Item');
                    $qty = (float) $item->quantity;
                    $unit = (float) $item->unit_price;
                    $total = (float) $item->line_total;

                    $p->text(self::posTrim($name, $width) . "\n");

                    $detailLeft = rtrim(number_format($qty, 2)) . " x " . number_format($unit, 2);
                    $detailRight = number_format($total, 2);
                    $p->text(self::posTwoCol($detailLeft, $detailRight, $width));
                }

                $p->text($line);

                $p->setEmphasis(true);
                $p->text(self::posTwoCol("TOTAL ({$currency})", number_format((float) $invoice->total_amount, 2), $width));
                $p->setEmphasis(false);
                $p->text(self::posTwoCol("PAID ({$currency})", number_format((float) $invoice->paid_amount, 2), $width));
                $p->text(self::posTwoCol("BALANCE ({$currency})", number_format((float) $invoice->balance_due, 2), $width));

                $p->feed();
                $p->setJustification(Printer::JUSTIFY_CENTER);
                $p->text("Thank you\n");
                $p->feed(3);
                $p->cut();
            });
        } catch (\Throwable $e) {
            Log::error('ESC/POS invoice print failed', [
                'invoice_id' => $invoiceId,
                'device' => $device,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('sales.invoices.show', $encodedId)
                ->with('error', "POS print failed: {$e->getMessage()}");
        }

        return redirect()
            ->route('sales.invoices.show', $encodedId)
            ->with('success', "POS print sent to {$device}");
    }

    private static function posTrim(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return '';
        }
        return mb_strimwidth($text, 0, $max, '', 'UTF-8');
    }

    private static function posTwoCol(string $left, string $right, int $width): string
    {
        $left = self::posTrim($left, $width);
        $right = self::posTrim($right, $width);
        $space = max(1, $width - mb_strlen($left, 'UTF-8') - mb_strlen($right, 'UTF-8'));
        return $left . str_repeat(' ', $space) . $right . "\n";
    }

    /**
     * Show the import form for sales invoice items
     */
    public function showImportForm(Request $request)
    {
        $user = Auth::user();
        $customers = Customer::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();
        
        $invoice = null;
        $isEditMode = false;
        
        // Check if this is edit mode (invoice_id provided)
        if ($request->has('invoice_id')) {
            $encodedId = $request->invoice_id;
            $id = Hashids::decode($encodedId)[0] ?? null;
            if ($id) {
                $invoice = SalesInvoice::find($id);
                if ($invoice && $invoice->company_id == $user->company_id) {
                    $isEditMode = true;
                }
            }
        }
        
        // Use invoice number from request or existing invoice or generate new one
        $suggestedInvoiceNumber = $request->invoice_number 
            ?? ($invoice ? $invoice->invoice_number : SalesInvoice::generateInvoiceNumber());
        
        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();
        
        // Pre-fill form data from query parameters or existing invoice
        if ($isEditMode && $invoice) {
            $prefillData = [
                'invoice_id' => $encodedId,
                'customer_id' => $invoice->customer_id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'payment_terms' => $invoice->payment_terms,
                'payment_days' => $invoice->payment_days,
                'currency' => $invoice->currency,
                'exchange_rate' => number_format($invoice->exchange_rate, 6, '.', ''),
                'notes' => $invoice->notes,
                'terms_conditions' => $invoice->terms_conditions,
            ];
        } else {
            $prefillData = [
                'customer_id' => $request->customer_id,
                'invoice_number' => $suggestedInvoiceNumber,
                'invoice_date' => $request->invoice_date ?? now()->toDateString(),
                'due_date' => $request->due_date ?? now()->addMonth()->toDateString(),
                'payment_terms' => $request->payment_terms ?? 'net_30',
                'payment_days' => $request->payment_days ?? '30',
                'currency' => $request->currency,
                'exchange_rate' => $request->exchange_rate ?? '1.000000',
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
            ];
        }
        
        return view('sales.invoices.import', compact('customers', 'suggestedInvoiceNumber', 'currencies', 'prefillData', 'isEditMode', 'invoice'));
    }

    /**
     * Import sales invoice items from CSV file
     */
    public function importFromCsv(Request $request): JsonResponse
    {
        \Log::info('Sales Invoice Import From CSV: Starting', [
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id
        ]);

        // If we are updating an existing invoice, allow the same invoice_number
        $invoiceIdForUnique = null;
        if ($request->has('invoice_id')) {
            $decoded = Hashids::decode($request->invoice_id)[0] ?? null;
            if ($decoded) {
                $invoiceIdForUnique = $decoded;
            }
        }

        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_45,net_60,custom',
            'payment_days' => 'required|integer|min:0',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ];

        // Note: Sales invoices auto-generate invoice_number, so we don't validate it here
        // But if invoice_number is provided, validate it with proper unique rule
        if ($request->filled('invoice_number')) {
            $rules['invoice_number'] = 'required|string|max:100|unique:sales_invoices,invoice_number';
            if ($invoiceIdForUnique) {
                $rules['invoice_number'] = 'required|string|max:100|unique:sales_invoices,invoice_number,' . $invoiceIdForUnique;
            }
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a branch before creating a sales invoice.'
                ], 422);
            }

            // Check if this is edit mode (invoice_id provided)
            $invoice = null;
            $isEditMode = false;
            if ($request->has('invoice_id')) {
                $encodedId = $request->invoice_id;
                $id = Hashids::decode($encodedId)[0] ?? null;
                if ($id) {
                    $invoice = SalesInvoice::find($id);
                    if ($invoice && $invoice->company_id == Auth::user()->company_id) {
                        $isEditMode = true;
                        // Check if invoice has payments (cannot edit if payments exist)
                        $hasPayments = Payment::where('reference_type', 'sales_invoice')
                            ->where('reference_number', $invoice->invoice_number)
                            ->where('customer_id', $invoice->customer_id)
                            ->exists();
                        if ($hasPayments) {
                            return response()->json([
                                'success' => false,
                                'message' => 'This invoice has payments and cannot be edited.'
                            ], 422);
                        }
                        // Delete existing items before importing new ones
                        $invoice->items()->delete();
                    }
                }
            }

            // Get functional currency
            $functionalCurrency = SystemSetting::getValue('functional_currency', Auth::user()->company->functional_currency ?? 'TZS');
            $invoiceCurrency = $request->currency ?? $functionalCurrency;
            $companyId = Auth::user()->company_id;

            // Get exchange rate
            $fxTransactionRateService = app(FxTransactionRateService::class);
            $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
            $rateResult = $fxTransactionRateService->getTransactionRate(
                $invoiceCurrency,
                $functionalCurrency,
                $request->invoice_date,
                $companyId,
                $userProvidedRate
            );
            $exchangeRate = $rateResult['rate'];

            // Handle attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('sales-invoice-attachments', $fileName, 'public');
                
                // Delete old attachment if updating
                if ($isEditMode && $invoice && $invoice->attachment) {
                    $oldPath = storage_path('app/public/' . $invoice->attachment);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
            } elseif ($isEditMode && $invoice) {
                // Keep existing attachment if no new file uploaded
                $attachmentPath = $invoice->attachment;
            }

            if ($isEditMode && $invoice) {
                // Update existing invoice
                $invoice->update([
                    'customer_id' => $request->customer_id,
                    'invoice_date' => $request->invoice_date,
                    'due_date' => $request->due_date,
                    'payment_terms' => $request->payment_terms,
                    'payment_days' => $request->payment_days,
                    'currency' => $invoiceCurrency,
                    'exchange_rate' => $exchangeRate,
                    'notes' => $request->notes,
                    'terms_conditions' => $request->terms_conditions,
                    'attachment' => $attachmentPath,
                    'updated_by' => Auth::id(),
                ]);
            } else {
                // Create invoice with draft status (will be updated by job)
                $invoice = SalesInvoice::create([
                    'customer_id' => $request->customer_id,
                    'invoice_number' => $request->invoice_number ?? SalesInvoice::generateInvoiceNumber(),
                    'invoice_date' => $request->invoice_date,
                    'due_date' => $request->due_date,
                    'payment_terms' => $request->payment_terms,
                    'payment_days' => $request->payment_days,
                    'currency' => $invoiceCurrency,
                    'exchange_rate' => $exchangeRate,
                    'notes' => $request->notes,
                    'terms_conditions' => $request->terms_conditions,
                    'attachment' => $attachmentPath,
                    'status' => 'draft',
                    'branch_id' => $branchId,
                    'company_id' => $companyId,
                    'created_by' => Auth::id(),
                ]);
            }

            // Process CSV file
            $file = $request->file('csv_file');
            $filePath = $file->store('imports/sales-invoice-items', 'local');
            $fullPath = storage_path('app/' . $filePath);

            // Resolve location for inventory movements
            $locationId = session('location_id');

            // Dispatch job to process CSV (synchronously like purchase invoices)
            $job = new \App\Jobs\ImportSalesInvoiceItemsJob(
                $fullPath,
                $invoice->id,
                $companyId,
                $branchId,
                $locationId
            );
            $job->handle();

            DB::commit();

            \Log::info('Sales Invoice Import From CSV: Success', [
                'invoice_id' => $invoice->id,
                'encoded_id' => $invoice->encoded_id
            ]);

            return response()->json([
                'success' => true,
                'message' => $isEditMode 
                    ? 'Sales invoice updated and items imported successfully.'
                    : 'Sales invoice created and items imported successfully.',
                'redirect_url' => route('sales.invoices.show', $invoice->encoded_id)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sales Invoice Import From CSV: Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to import: ' . $e->getMessage()
            ], 500);
        }
    }
}
