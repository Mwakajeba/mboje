<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\ChartAccount;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use App\Traits\TransactionHelper;
use App\Traits\GetsCurrenciesFromFxRates;
use App\Services\FxTransactionRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class ReceiptVoucherController extends Controller
{
    use TransactionHelper, GetsCurrenciesFromFxRates;

    /**
     * Debug method to test controller accessibility
     */
    public function debug()
    {
        return response()->json([
            'message' => 'ReceiptVoucherController is accessible',
            'user' => Auth::user()->name ?? 'No user',
            'timestamp' => now()
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        // Get receipts for the current company/branch
        $receipts = Receipt::with(['bankAccount', 'user', 'receiptItems'])
            ->whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->orderBy('date', 'desc')
            ->get();

        // Load customer relationships for receipts with payee_type = 'customer'
        $customerReceiptIds = $receipts->where('payee_type', 'customer')->pluck('payee_id')->filter();
        if ($customerReceiptIds->isNotEmpty()) {
            $receipts->load([
                'customer' => function ($query) use ($customerReceiptIds) {
                    $query->whereIn('id', $customerReceiptIds);
                }
            ]);
        }

        // Calculate stats
        $stats = [
            'total' => $receipts->count(),
            'this_month' => $receipts->where('date', '>=', now()->startOfMonth())->count(),
            'total_amount' => $receipts->sum('amount'),
            'this_month_amount' => $receipts->where('date', '>=', now()->startOfMonth())->sum('amount'),
        ];

        return view('accounting.receipt-vouchers.index', compact('receipts', 'stats'));
    }

    /**
     * Get receipt vouchers data for DataTables
     */
    public function data()
    {
        $user = Auth::user();

        $receipts = Receipt::with(['bankAccount', 'user', 'receiptItems', 'customer'])
            ->whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->orderBy('date', 'desc');

        return DataTables::of($receipts)
            ->addColumn('formatted_date', function ($receipt) {
                return $receipt->date->format('d M Y');
            })
            ->addColumn('reference_link', function ($receipt) {
                return '<a href="' . route('accounting.receipt-vouchers.show', $receipt->encoded_id) . '" class="text-primary">RCP-' . $receipt->id . '</a>';
            })
            ->addColumn('bank_account_name', function ($receipt) {
                return $receipt->bankAccount ? $receipt->bankAccount->name : 'Cash';
            })
            ->addColumn('payee_info', function ($receipt) {
                if ($receipt->payee_type === 'customer' && $receipt->customer) {
                    return $receipt->customer->name;
                } elseif ($receipt->payee_type === 'employee' && $receipt->employee) {
                    return $receipt->employee->full_name;
                }
                return $receipt->payee_name ?? 'N/A';
            })
            ->addColumn('description_limited', function ($receipt) {
                return \Str::limit($receipt->description ?? 'N/A', 50);
            })
            ->addColumn('formatted_amount', function ($receipt) {
                return 'TZS ' . number_format($receipt->amount, 2);
            })
            ->addColumn('user_name', function ($receipt) {
                return $receipt->user ? $receipt->user->name : 'System';
            })
            ->addColumn('actions', function ($receipt) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('accounting.receipt-vouchers.show', $receipt->encoded_id) . '" class="btn btn-sm btn-info me-1" title="View"><i class="bx bx-show"></i></a>';
                $actions .= '<a href="' . route('accounting.receipt-vouchers.edit', $receipt->encoded_id) . '" class="btn btn-sm btn-primary me-1" title="Edit"><i class="bx bx-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteReceipt(\'' . $receipt->encoded_id . '\')" title="Delete"><i class="bx bx-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['reference_link', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();

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

        // Get chart accounts for the current company
        $chartAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })
            ->orderBy('account_name')
            ->get();

        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        return view('accounting.receipt-vouchers.create', compact('bankAccounts', 'customers', 'chartAccounts', 'currencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Debug: Log the incoming request data
        \Log::info('Receipt voucher store request started');
        \Log::info('Request method:', ['method' => $request->method()]);
        \Log::info('Request URL:', ['url' => $request->url()]);
        \Log::info('Request headers:', $request->headers->all());
        \Log::info('Request all data:', $request->all());
        \Log::info('Request input:', $request->input());
        \Log::info('Request has file attachment:', ['has_file' => $request->hasFile('attachment')]);

        // Check if line_items are present
        \Log::info('Line items data:', ['line_items' => $request->input('line_items')]);

        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
        
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'bank_account_id' => 'required|exists:bank_accounts,id',
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
            'payee_type' => 'required|in:customer,employee,other',
            'customer_id' => 'required_if:payee_type,customer|nullable|exists:customers,id',
            'employee_id' => 'nullable',
            'payee_name' => 'required_if:payee_type,other|nullable|string|max:255',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf|max:2048',
            'wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'line_items' => 'required|array|min:1',
            'line_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.description' => 'nullable|string',
            'line_items.*.wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'line_items.*.wht_rate' => 'nullable|numeric|min:0|max:100',
            'line_items.*.vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'line_items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            \Log::error('Receipt voucher validation failed:', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        \Log::info('Validation passed, proceeding with creation');

        try {
            return $this->runTransaction(function () use ($request) {
                $user = Auth::user();
                $branchId = session('branch_id') ?? ($user->branch_id ?? null);
                
                if (!$branchId) {
                    \Log::error('Branch ID is null:', [
                        'user_id' => $user->id,
                        'user_branch_id' => $user->branch_id,
                        'session_branch_id' => session('branch_id')
                    ]);
                    throw new \Exception('Branch ID is required but not found. Please ensure you are assigned to a branch.');
                }
                
                $totalAmount = collect($request->line_items)->sum('amount');
                
                // Calculate WHT if enabled (for AR, only Exclusive/Inclusive, no Gross-Up)
                $whtService = new \App\Services\WithholdingTaxService();
                $whtEnabled = $request->has('wht_enabled') && $request->wht_enabled == '1';
                $whtTreatment = $whtEnabled ? ($request->wht_treatment ?? 'EXCLUSIVE') : 'NONE';
                $whtRate = $whtEnabled ? (float) ($request->wht_rate ?? 0) : 0;
                
                // Get VAT mode and rate (receipt-level) - only if WHT is enabled
                $vatMode = 'NONE';
                $vatRate = 0;
                if ($whtEnabled) {
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
                }
                
                // Validate AR treatment (no Gross-Up for receipts)
                if ($whtEnabled && !$whtService->isValidARTreatment($whtTreatment)) {
                    $whtTreatment = 'EXCLUSIVE';
                }
                
                $receiptWHT = 0;
                $receiptNetReceivable = $totalAmount;
                $receiptBaseAmount = $totalAmount;
                $receiptVatAmount = 0;
                
                if ($whtEnabled && $whtRate > 0 && $whtTreatment !== 'NONE') {
                    $whtCalc = $whtService->calculateWHTForAR($totalAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                    $receiptWHT = $whtCalc['wht_amount'];
                    $receiptNetReceivable = $whtCalc['net_receivable'];
                    $receiptBaseAmount = $whtCalc['base_amount'];
                    $receiptVatAmount = $whtCalc['vat_amount'];
                }

                \Log::info('Creating receipt voucher with total amount:', ['total' => $totalAmount, 'base' => $receiptBaseAmount, 'vat' => $receiptVatAmount, 'wht' => $receiptWHT]);

                // Handle file upload
                $attachmentPath = null;
                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $attachmentPath = $file->storeAs('receipt-attachments', $fileName, 'public');
                }

                // Set payee information
                if ($request->payee_type === 'customer') {
                    $payeeType = 'customer';
                    $payeeId = $request->customer_id;
                    $payeeName = null;
                } elseif ($request->payee_type === 'employee') {
                    $payeeType = 'employee';
                    $payeeId = $request->employee_id;
                    $payeeName = null;
                } else {
                    $payeeType = 'other';
                    $payeeId = null;
                    $payeeName = $request->payee_name;
                }

                \Log::info('Payee information:', [
                    'type' => $payeeType,
                    'id' => $payeeId,
                    'name' => $payeeName
                ]);

                // Validate period lock BEFORE creating receipt
                $companyId = $user->company_id;
                if ($companyId) {
                    $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
                    try {
                        $periodLockService->validateTransactionDate($request->date, $companyId, 'receipt voucher');
                    } catch (\Exception $e) {
                        \Log::warning('Receipt Voucher - Cannot create: Period is locked', [
                            'receipt_date' => $request->date,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }

                // Generate unique reference
                $reference = $request->reference;
                if (!$reference) {
                    do {
                        $reference = 'RV-' . date('Ymd') . '-' . strtoupper(uniqid());
                    } while (Receipt::where('reference', $reference)->exists());
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
                
                // Create receipt
                $receiptData = [
                    'reference' => $reference,
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
                    'wht_amount' => $receiptWHT,
                    'net_receivable' => $receiptNetReceivable,
                    'vat_mode' => $vatMode,
                    'vat_amount' => $receiptVatAmount,
                    'base_amount' => $receiptBaseAmount,
                    'date' => $request->date,
                    'description' => $request->description,
                    'attachment' => $attachmentPath,
                    'user_id' => $user->id,
                    'bank_account_id' => $request->bank_account_id,
                    'payment_method' => $request->payment_method ?? 'bank_transfer',
                    'cheque_id' => $request->cheque_id ?? null,
                    'payee_type' => $payeeType,
                    'payee_id' => $payeeId,
                    'payee_name' => $payeeName,
                    'branch_id' => $branchId,
                    'approved' => true, // Auto-approve for now
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ];

                \Log::info('Attempting to create receipt with data:', $receiptData);
                \Log::info('Branch ID resolved:', ['branch_id' => $branchId, 'user_branch_id' => $user->branch_id, 'session_branch_id' => session('branch_id')]);

                try {
                    $receipt = Receipt::create($receiptData);
                } catch (\Exception $e) {
                    \Log::error('Receipt creation failed:', [
                        'error' => $e->getMessage(),
                        'data' => $receiptData
                    ]);
                    throw $e;
                }

                \Log::info('Receipt created successfully:', ['receipt_id' => $receipt->id]);

                // Create receipt items with WHT and VAT calculation (only if WHT is enabled)
                $receiptItems = [];
                foreach ($request->line_items as $lineItem) {
                    $itemTotalAmount = (float) $lineItem['amount'];
                    $itemWHTTreatment = $whtEnabled ? ($lineItem['wht_treatment'] ?? $whtTreatment) : 'NONE';
                    $itemWHTRate = $whtEnabled ? (float) ($lineItem['wht_rate'] ?? $whtRate) : 0;
                    $itemVatMode = $whtEnabled ? ($lineItem['vat_mode'] ?? $vatMode) : 'NONE';
                    $itemVatRate = $whtEnabled ? (float) ($lineItem['vat_rate'] ?? $vatRate) : 0;
                    
                    // Validate AR treatment
                    if ($whtEnabled && !$whtService->isValidARTreatment($itemWHTTreatment)) {
                        $itemWHTTreatment = $whtTreatment;
                    }
                    
                    $itemWHT = 0;
                    $itemNetReceivable = $itemTotalAmount;
                    $itemBaseAmount = $itemTotalAmount;
                    $itemVatAmount = 0;
                    
                    if ($whtEnabled && $itemWHTRate > 0 && $itemWHTTreatment !== 'NONE') {
                        $itemWHTCalc = $whtService->calculateWHTForAR($itemTotalAmount, $itemWHTRate, $itemWHTTreatment, $itemVatMode, $itemVatRate);
                        $itemWHT = $itemWHTCalc['wht_amount'];
                        $itemNetReceivable = $itemWHTCalc['net_receivable'];
                        $itemBaseAmount = $itemWHTCalc['base_amount'];
                        $itemVatAmount = $itemWHTCalc['vat_amount'];
                    }
                    
                    $receiptItems[] = [
                        'receipt_id' => $receipt->id,
                        'chart_account_id' => $lineItem['chart_account_id'],
                        'amount' => $itemTotalAmount,
                        'wht_treatment' => $itemWHTTreatment,
                        'wht_rate' => $itemWHTRate,
                        'wht_amount' => $itemWHT,
                        'base_amount' => $itemBaseAmount,
                        'net_receivable' => $itemNetReceivable,
                        'vat_mode' => $itemVatMode,
                        'vat_amount' => $itemVatAmount,
                        'description' => $lineItem['description'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                ReceiptItem::insert($receiptItems);
                \Log::info('Receipt items created:', ['count' => count($receiptItems)]);

                // Handle invoice payments - create linked receipts for each invoice
                $invoiceReceipts = [];
                foreach ($request->line_items as $index => $lineItem) {
                    if (isset($lineItem['invoice_id']) && isset($lineItem['invoice_number'])) {
                        $invoice = \App\Models\Sales\SalesInvoice::find($lineItem['invoice_id']);
                        if ($invoice && $invoice->invoice_number === $lineItem['invoice_number']) {
                            // Create a receipt record linked to this invoice
                            $invoiceReceiptAmount = (float) $lineItem['amount'];
                            
                            // Calculate WHT for this invoice receipt
                            $invoiceWHT = 0;
                            $invoiceNetReceivable = $invoiceReceiptAmount;
                            $invoiceBaseAmount = $invoiceReceiptAmount;
                            $invoiceVatAmount = 0;
                            
                            if ($whtEnabled && $whtRate > 0 && $whtTreatment !== 'NONE') {
                                $invoiceWHTCalc = $whtService->calculateWHTForAR($invoiceReceiptAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                                $invoiceWHT = $invoiceWHTCalc['wht_amount'];
                                $invoiceNetReceivable = $invoiceWHTCalc['net_receivable'];
                                $invoiceBaseAmount = $invoiceWHTCalc['base_amount'];
                                $invoiceVatAmount = $invoiceWHTCalc['vat_amount'];
                            }
                            
                            // Calculate amounts in LCY
                            $invoiceAmountFcy = $needsConversion ? $invoiceReceiptAmount : null;
                            $invoiceAmountLcy = $needsConversion ? round($invoiceReceiptAmount * $exchangeRate, 2) : $invoiceReceiptAmount;
                            
                            // Generate unique reference for invoice receipt
                            $invoiceReference = $reference . '-INV-' . $invoice->invoice_number;
                            
                            $invoiceReceipts[] = [
                                'reference' => $invoiceReference,
                                'reference_type' => 'sales_invoice',
                                'reference_number' => $invoice->invoice_number,
                                'amount' => $invoiceReceiptAmount,
                                'currency' => $currency,
                                'exchange_rate' => $exchangeRate,
                                'amount_fcy' => $invoiceAmountFcy,
                                'amount_lcy' => $invoiceAmountLcy,
                                'fx_rate_used' => $fxRateUsed,
                                'wht_treatment' => $whtTreatment,
                                'wht_rate' => $whtRate,
                                'wht_amount' => $invoiceWHT,
                                'net_receivable' => $invoiceNetReceivable,
                                'vat_mode' => $vatMode,
                                'vat_amount' => $invoiceVatAmount,
                                'base_amount' => $invoiceBaseAmount,
                                'date' => $request->date,
                                'description' => ($lineItem['description'] ?? null) ?: "Receipt for Invoice {$invoice->invoice_number}",
                                'attachment' => $attachmentPath,
                                'user_id' => $user->id,
                                'bank_account_id' => $request->bank_account_id,
                                'payment_method' => $request->payment_method ?? 'bank_transfer',
                                'cheque_id' => $request->cheque_id ?? null,
                                'payee_type' => 'customer',
                                'payee_id' => $request->customer_id,
                                'payee_name' => null,
                                'branch_id' => $branchId,
                                'approved' => true,
                                'approved_by' => $user->id,
                                'approved_at' => now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
                
                // Insert invoice receipts if any
                if (!empty($invoiceReceipts)) {
                    Receipt::insert($invoiceReceipts);
                    
                    // Create receipt items for invoice receipts (link to Accounts Receivable)
                    // Use the same logic as SalesInvoice model
                    $arAccountId = null;
                    $settingValue = SystemSetting::where('key', 'inventory_default_receivable_account')->value('value');
                    if ($settingValue) {
                        $arAccountId = (int) $settingValue;
                    } else {
                        // Fallback: Try ID 18 first, then ID 2
                        $account18 = \App\Models\ChartAccount::find(18);
                        if ($account18 && stripos($account18->account_name, 'Accounts Receivable') !== false) {
                            $arAccountId = 18;
                        } else {
                            $account2 = \App\Models\ChartAccount::find(2);
                            if ($account2 && stripos($account2->account_name, 'Accounts Receivable') !== false) {
                                $arAccountId = 2;
                            } else {
                                // Last resort: find by name
                                $account = \App\Models\ChartAccount::where('account_name', 'like', '%Accounts Receivable%')->first();
                                $arAccountId = $account ? $account->id : 18; // Default to 18 if nothing found
                            }
                        }
                    }
                    if (!$arAccountId) {
                        $arAccountId = 18; // Final fallback
                    }
                    $invoiceReceiptItems = [];
                    
                    foreach ($invoiceReceipts as $idx => $invReceipt) {
                        $invoiceReceipt = Receipt::where('reference', $invReceipt['reference'])->first();
                        if ($invoiceReceipt) {
                            $invoiceReceiptItems[] = [
                                'receipt_id' => $invoiceReceipt->id,
                                'chart_account_id' => $arAccountId,
                                'amount' => $invReceipt['amount'],
                                'wht_treatment' => $invReceipt['wht_treatment'],
                                'wht_rate' => $invReceipt['wht_rate'],
                                'wht_amount' => $invReceipt['wht_amount'],
                                'base_amount' => $invReceipt['base_amount'],
                                'net_receivable' => $invReceipt['net_receivable'],
                                'vat_mode' => $invReceipt['vat_mode'],
                                'vat_amount' => $invReceipt['vat_amount'],
                                'description' => $invReceipt['description'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                    
                    if (!empty($invoiceReceiptItems)) {
                        ReceiptItem::insert($invoiceReceiptItems);
                        
                        // Post GL transactions for invoice receipts
                        foreach ($invoiceReceipts as $invReceipt) {
                            $invoiceReceipt = Receipt::where('reference', $invReceipt['reference'])->first();
                            if ($invoiceReceipt) {
                                $invoiceReceipt->createGlTransactions();
                                
                                // Update invoice paid amount
                                // Use the amount from the line item (invReceipt['amount']), not the invoice's total_amount
                                $invoice = \App\Models\Sales\SalesInvoice::where('invoice_number', $invReceipt['reference_number'])
                                    ->where('customer_id', $request->customer_id)
                                    ->first();
                                if ($invoice) {
                                    // Use the amount from the line item (invReceipt['amount']), not the invoice's total_amount
                                    $paymentAmount = (float) $invReceipt['amount'];
                                    
                                    // Increment paid_amount by the payment amount (allows overpayment)
                                    $invoice->increment('paid_amount', $paymentAmount);
                                    
                                    // Recalculate balance_due (can be negative if overpaid)
                                    $invoice->balance_due = $invoice->total_amount - $invoice->paid_amount;
                                    
                                    // Update status - if paid_amount >= total_amount, mark as paid
                                    // Allow overpayment (paid_amount > total_amount)
                                    if ($invoice->paid_amount >= $invoice->total_amount) {
                                        $invoice->status = 'paid';
                                    } else {
                                        $invoice->status = 'sent';
                                    }
                                    
                                    $invoice->save();
                                    
                                    // Sync linked Opening Balance amounts if applicable
                                    $invoice->syncLinkedOpeningBalance();
                                }
                            }
                        }
                    }
                }

                // Use Receipt model's createGlTransactions method which handles WHT correctly
                $receipt->createGlTransactions();

                \Log::info('GL transactions created successfully');

                // If AJAX request or reconciliation_id is present, return JSON or redirect to reconciliation
                if ($request->ajax() || $request->wantsJson()) {
                    $redirectUrl = route('accounting.receipt-vouchers.show', Hashids::encode($receipt->id));
                    
                    // If reconciliation_id is present, redirect to bank reconciliation page
                    if ($request->has('reconciliation_id') && $request->reconciliation_id) {
                        $redirectUrl = route('accounting.bank-reconciliation.show', \App\Helpers\HashIdHelper::encode($request->reconciliation_id));
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Receipt voucher created successfully.',
                        'redirect_url' => $redirectUrl,
                        'receipt_id' => $receipt->id
                    ]);
                }
                
                // If reconciliation_id is present, redirect to bank reconciliation page
                if ($request->has('reconciliation_id') && $request->reconciliation_id) {
                    return redirect()->route('accounting.bank-reconciliation.show', \App\Helpers\HashIdHelper::encode($request->reconciliation_id))
                        ->with('success', 'Receipt voucher created successfully.');
                }

                return redirect()->route('accounting.receipt-vouchers.show', Hashids::encode($receipt->id))
                    ->with('success', 'Receipt voucher created successfully.');
            });
        } catch (\Exception $e) {
            \Log::error('Receipt voucher creation failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = $e->getMessage();
            
            // Check if it's a reconciliation error
            if (str_contains($errorMessage, 'completed reconciliation period')) {
                $errorMessage = 'Cannot post: Receipt is in a completed reconciliation period';
            } else {
                $errorMessage = 'Failed to create receipt voucher: ' . $errorMessage;
            }
            
            // For AJAX requests, return JSON with SweetAlert-friendly format
            if (request()->ajax() || request()->wantsJson()) {
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
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.receipt-vouchers.index')->withErrors(['Receipt voucher not found.']);
        }

        $receiptVoucher = Receipt::findOrFail($decoded[0]);

        $receiptVoucher->load([
            'bankAccount',
            'customer.company',
            'customer.branch',
            'user',
            'receiptItems.chartAccount',
            'glTransactions.chartAccount',
            'branch'
        ]);

        return view('accounting.receipt-vouchers.show', compact('receiptVoucher'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.receipt-vouchers.index')->withErrors(['Receipt voucher not found.']);
        }

        $receiptVoucher = Receipt::findOrFail($decoded[0]);

        $user = Auth::user();

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

        // Get chart accounts for the current company
        $chartAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })
            ->orderBy('account_name')
            ->get();

        $receiptVoucher->load('receiptItems');

        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        return view('accounting.receipt-vouchers.edit', compact('receiptVoucher', 'bankAccounts', 'customers', 'chartAccounts', 'currencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        // Decode receipt voucher ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.receipt-vouchers.index')->withErrors(['Receipt voucher not found.']);
        }

        $receiptVoucher = Receipt::findOrFail($decoded[0]);

        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
        
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:bank_transfer,cash,cheque',
            'cheque_id' => 'nullable|exists:cheques,id',
            'bank_account_id' => 'required_if:payment_method,bank_transfer|required_if:payment_method,cheque|nullable|exists:bank_accounts,id',
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
            'payee_type' => 'required|in:customer,employee,other',
            'customer_id' => 'required_if:payee_type,customer|nullable|exists:customers,id',
            'employee_id' => 'nullable',
            'payee_name' => 'required_if:payee_type,other|nullable|string|max:255',
            'cheque_number' => 'required_if:payment_method,cheque|nullable|string|max:50',
            'cheque_date' => 'required_if:payment_method,cheque|nullable|date',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf|max:2048',
            'wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'line_items' => 'required|array|min:1',
            'line_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            return $this->runTransaction(function () use ($request, $receiptVoucher) {
                $user = Auth::user();
                $branchId = session('branch_id') ?? ($user->branch_id ?? null);
                $totalAmount = collect($request->line_items)->sum('amount');

                // Get WHT and VAT settings from request
                $whtEnabled = $request->has('wht_enabled') && $request->wht_enabled == '1';
                $whtTreatment = $whtEnabled ? ($request->wht_treatment ?? 'EXCLUSIVE') : 'NONE';
                $whtRate = $whtEnabled ? (float) ($request->wht_rate ?? 0) : 0;
                
                // Get VAT mode and rate (receipt-level) - only if WHT is enabled
                $vatMode = 'NONE';
                $vatRate = 0;
                if ($whtEnabled) {
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
                }

                // Calculate WHT if provided (for AR, only Exclusive/Inclusive, no Gross-Up)
                $whtService = new \App\Services\WithholdingTaxService();
                $receiptWHT = 0;
                $receiptNetReceivable = $totalAmount;
                $receiptBaseAmount = $totalAmount;
                $receiptVatAmount = 0;

                if ($whtEnabled && $whtRate > 0 && $whtTreatment !== 'NONE') {
                    $whtCalc = $whtService->calculateWHTForAR($totalAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                    $receiptWHT = $whtCalc['wht_amount'];
                    $receiptNetReceivable = $whtCalc['net_receivable'];
                    $receiptBaseAmount = $whtCalc['base_amount'];
                    $receiptVatAmount = $whtCalc['vat_amount'];
                }

                // Handle file upload and attachment removal
                $attachmentPath = $receiptVoucher->attachment;

                // Check if user wants to remove attachment
                if ($request->has('remove_attachment') && $request->remove_attachment == '1') {
                    // Delete old attachment if exists
                    if ($receiptVoucher->attachment && Storage::disk('public')->exists($receiptVoucher->attachment)) {
                        Storage::disk('public')->delete($receiptVoucher->attachment);
                    }
                    $attachmentPath = null;
                } elseif ($request->hasFile('attachment')) {
                    // Delete old attachment if exists
                    if ($receiptVoucher->attachment && Storage::disk('public')->exists($receiptVoucher->attachment)) {
                        Storage::disk('public')->delete($receiptVoucher->attachment);
                    }

                    $file = $request->file('attachment');
                    $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $attachmentPath = $file->storeAs('receipt-attachments', $fileName, 'public');
                }

                // Set payee information
                if ($request->payee_type === 'customer') {
                    $payeeType = 'customer';
                    $payeeId = $request->customer_id;
                    $payeeName = null;
                } elseif ($request->payee_type === 'employee') {
                    $payeeType = 'employee';
                    $payeeId = $request->employee_id;
                    $payeeName = null;
                } else {
                    $payeeType = 'other';
                    $payeeId = null;
                    $payeeName = $request->payee_name;
                }

                // Validate period lock BEFORE updating receipt
                $companyId = $user->company_id;
                if ($companyId) {
                    $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
                    try {
                        $periodLockService->validateTransactionDate($request->date, $companyId, 'receipt voucher');
                    } catch (\Exception $e) {
                        \Log::warning('Receipt Voucher - Cannot update: Period is locked', [
                            'receipt_id' => $receiptVoucher->id,
                            'receipt_date' => $request->date,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
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
                
                // Update receipt
                $receiptVoucher->update([
                    'reference' => $request->reference ?: $receiptVoucher->reference,
                    'reference_number' => $request->reference,
                    'amount' => $totalAmount, // Total amount (may include VAT)
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'amount_fcy' => $amountFcy, // Foreign currency amount for revaluation
                    'amount_lcy' => $amountLcy, // Local currency amount for revaluation
                    'fx_rate_used' => $fxRateUsed,
                    'payment_method' => $request->payment_method ?? $receiptVoucher->payment_method ?? 'bank_transfer',
                    'cheque_id' => $request->cheque_id ?? $receiptVoucher->cheque_id,
                    'wht_treatment' => $whtTreatment,
                    'wht_rate' => $whtRate,
                    'wht_amount' => $receiptWHT,
                    'net_receivable' => $receiptNetReceivable,
                    'vat_mode' => $vatMode,
                    'vat_amount' => $receiptVatAmount,
                    'base_amount' => $receiptBaseAmount,
                    'date' => $request->date,
                    'description' => $request->description,
                    'attachment' => $attachmentPath,
                    'bank_account_id' => $request->bank_account_id,
                    'payee_type' => $payeeType,
                    'payee_id' => $payeeId,
                    'payee_name' => $payeeName,
                ]);

                // Delete existing receipt items and GL transactions
                $receiptVoucher->receiptItems()->delete();
                $receiptVoucher->glTransactions()->delete();

                // Create new receipt items with WHT and VAT calculation (only if WHT is enabled)
                $receiptItems = [];
                foreach ($request->line_items as $lineItem) {
                    $itemTotalAmount = (float) $lineItem['amount'];
                    $itemWHTTreatment = $whtEnabled ? ($whtTreatment) : 'NONE'; // Use receipt-level WHT treatment only if enabled
                    $itemWHTRate = $whtEnabled ? ($whtRate) : 0; // Use receipt-level WHT rate only if enabled
                    $itemVatMode = $whtEnabled ? ($vatMode) : 'NONE'; // Use receipt-level VAT mode only if enabled
                    $itemVatRate = $whtEnabled ? ($vatRate) : 0; // Use receipt-level VAT rate only if enabled
                    
                    // Validate AR treatment
                    if ($whtEnabled && !$whtService->isValidARTreatment($itemWHTTreatment)) {
                        $itemWHTTreatment = 'EXCLUSIVE';
                    }
                    
                    $itemWHT = 0;
                    $itemNetReceivable = $itemTotalAmount;
                    $itemBaseAmount = $itemTotalAmount;
                    $itemVatAmount = 0;
                    
                    if ($whtEnabled && $itemWHTRate > 0 && $itemWHTTreatment !== 'NONE') {
                        $itemWHTCalc = $whtService->calculateWHTForAR($itemTotalAmount, $itemWHTRate, $itemWHTTreatment, $itemVatMode, $itemVatRate);
                        $itemWHT = $itemWHTCalc['wht_amount'];
                        $itemNetReceivable = $itemWHTCalc['net_receivable'];
                        $itemBaseAmount = $itemWHTCalc['base_amount'];
                        $itemVatAmount = $itemWHTCalc['vat_amount'];
                    }
                    
                    $receiptItems[] = [
                        'receipt_id' => $receiptVoucher->id,
                        'chart_account_id' => $lineItem['chart_account_id'],
                        'amount' => $itemTotalAmount,
                        'wht_treatment' => $itemWHTTreatment,
                        'wht_rate' => $itemWHTRate,
                        'wht_amount' => $itemWHT,
                        'base_amount' => $itemBaseAmount,
                        'net_receivable' => $itemNetReceivable,
                        'vat_mode' => $itemVatMode,
                        'vat_amount' => $itemVatAmount,
                        'description' => $lineItem['description'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                ReceiptItem::insert($receiptItems);

                // Handle invoice payments - delete old invoice receipts and create new ones
                // Find existing invoice receipts linked to this receipt voucher
                $existingInvoiceReceipts = Receipt::where('reference', 'like', $receiptVoucher->reference . '-INV-%')
                    ->orWhere(function($query) use ($receiptVoucher) {
                        $query->where('reference_type', 'sales_invoice')
                              ->where('payee_id', $receiptVoucher->payee_id)
                              ->where('date', $receiptVoucher->date)
                              ->where('user_id', $receiptVoucher->user_id);
                    })
                    ->get();
                
                // Delete existing invoice receipts and their items/GL transactions
                foreach ($existingInvoiceReceipts as $existingReceipt) {
                    // Reverse invoice paid amounts before deleting
                    $invoice = \App\Models\Sales\SalesInvoice::where('invoice_number', $existingReceipt->reference_number)
                        ->where('customer_id', $receiptVoucher->payee_id)
                        ->first();
                    if ($invoice) {
                        $invoice->decrement('paid_amount', $existingReceipt->amount);
                        $invoice->balance_due = $invoice->total_amount - $invoice->paid_amount;
                        if ($invoice->paid_amount <= 0) {
                            $invoice->status = 'sent';
                        }
                        $invoice->save();
                        $invoice->syncLinkedOpeningBalance();
                    }
                    
                    $existingReceipt->receiptItems()->delete();
                    $existingReceipt->glTransactions()->delete();
                    $existingReceipt->delete();
                }
                
                // Create new invoice receipts for each invoice line item
                $invoiceReceipts = [];
                foreach ($request->line_items as $index => $lineItem) {
                    if (isset($lineItem['invoice_id']) && isset($lineItem['invoice_number'])) {
                        $invoice = \App\Models\Sales\SalesInvoice::find($lineItem['invoice_id']);
                        if ($invoice && $invoice->invoice_number === $lineItem['invoice_number']) {
                            // Create a receipt record linked to this invoice
                            $invoiceReceiptAmount = (float) $lineItem['amount'];
                            
                            // Calculate WHT for this invoice receipt
                            $invoiceWHT = 0;
                            $invoiceNetReceivable = $invoiceReceiptAmount;
                            $invoiceBaseAmount = $invoiceReceiptAmount;
                            $invoiceVatAmount = 0;
                            
                            if ($whtEnabled && $whtRate > 0 && $whtTreatment !== 'NONE') {
                                $invoiceWHTCalc = $whtService->calculateWHTForAR($invoiceReceiptAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                                $invoiceWHT = $invoiceWHTCalc['wht_amount'];
                                $invoiceNetReceivable = $invoiceWHTCalc['net_receivable'];
                                $invoiceBaseAmount = $invoiceWHTCalc['base_amount'];
                                $invoiceVatAmount = $invoiceWHTCalc['vat_amount'];
                            }
                            
                            // Calculate amounts in LCY
                            $invoiceAmountFcy = $needsConversion ? $invoiceReceiptAmount : null;
                            $invoiceAmountLcy = $needsConversion ? round($invoiceReceiptAmount * $exchangeRate, 2) : $invoiceReceiptAmount;
                            
                            // Generate unique reference for invoice receipt
                            $invoiceReference = $receiptVoucher->reference . '-INV-' . $invoice->invoice_number;
                            
                            $invoiceReceipts[] = [
                                'reference' => $invoiceReference,
                                'reference_type' => 'sales_invoice',
                                'reference_number' => $invoice->invoice_number,
                                'amount' => $invoiceReceiptAmount,
                                'currency' => $currency,
                                'exchange_rate' => $exchangeRate,
                                'amount_fcy' => $invoiceAmountFcy,
                                'amount_lcy' => $invoiceAmountLcy,
                                'fx_rate_used' => $fxRateUsed,
                                'wht_treatment' => $whtTreatment,
                                'wht_rate' => $whtRate,
                                'wht_amount' => $invoiceWHT,
                                'net_receivable' => $invoiceNetReceivable,
                                'vat_mode' => $vatMode,
                                'vat_amount' => $invoiceVatAmount,
                                'base_amount' => $invoiceBaseAmount,
                                'date' => $request->date,
                                'description' => ($lineItem['description'] ?? null) ?: "Receipt for Invoice {$invoice->invoice_number}",
                                'attachment' => $attachmentPath,
                                'user_id' => $user->id,
                                'bank_account_id' => $request->bank_account_id,
                                'payment_method' => $request->payment_method ?? 'bank_transfer',
                                'cheque_id' => $request->cheque_id ?? null,
                                'payee_type' => 'customer',
                                'payee_id' => $request->customer_id,
                                'payee_name' => null,
                                'branch_id' => $branchId,
                                'approved' => true,
                                'approved_by' => $user->id,
                                'approved_at' => now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
                
                // Insert invoice receipts if any
                if (!empty($invoiceReceipts)) {
                    Receipt::insert($invoiceReceipts);
                    
                    // Create receipt items for invoice receipts (link to Accounts Receivable)
                    // Use the same logic as SalesInvoice model
                    $arAccountId = null;
                    $settingValue = SystemSetting::where('key', 'inventory_default_receivable_account')->value('value');
                    if ($settingValue) {
                        $arAccountId = (int) $settingValue;
                    } else {
                        // Fallback: Try ID 18 first, then ID 2
                        $account18 = \App\Models\ChartAccount::find(18);
                        if ($account18 && stripos($account18->account_name, 'Accounts Receivable') !== false) {
                            $arAccountId = 18;
                        } else {
                            $account2 = \App\Models\ChartAccount::find(2);
                            if ($account2 && stripos($account2->account_name, 'Accounts Receivable') !== false) {
                                $arAccountId = 2;
                            } else {
                                // Last resort: find by name
                                $account = \App\Models\ChartAccount::where('account_name', 'like', '%Accounts Receivable%')->first();
                                $arAccountId = $account ? $account->id : 18; // Default to 18 if nothing found
                            }
                        }
                    }
                    if (!$arAccountId) {
                        $arAccountId = 18; // Final fallback
                    }
                    $invoiceReceiptItems = [];
                    
                    foreach ($invoiceReceipts as $idx => $invReceipt) {
                        $invoiceReceipt = Receipt::where('reference', $invReceipt['reference'])->first();
                        if ($invoiceReceipt) {
                            $invoiceReceiptItems[] = [
                                'receipt_id' => $invoiceReceipt->id,
                                'chart_account_id' => $arAccountId,
                                'amount' => $invReceipt['amount'],
                                'wht_treatment' => $invReceipt['wht_treatment'],
                                'wht_rate' => $invReceipt['wht_rate'],
                                'wht_amount' => $invReceipt['wht_amount'],
                                'base_amount' => $invReceipt['base_amount'],
                                'net_receivable' => $invReceipt['net_receivable'],
                                'vat_mode' => $invReceipt['vat_mode'],
                                'vat_amount' => $invReceipt['vat_amount'],
                                'description' => $invReceipt['description'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                    
                    if (!empty($invoiceReceiptItems)) {
                        ReceiptItem::insert($invoiceReceiptItems);
                        
                        // Post GL transactions for invoice receipts
                        foreach ($invoiceReceipts as $invReceipt) {
                            $invoiceReceipt = Receipt::where('reference', $invReceipt['reference'])->first();
                            if ($invoiceReceipt) {
                                $invoiceReceipt->createGlTransactions();
                                
                                // Update invoice paid amount
                                // Use the amount from the line item (invReceipt['amount']), not the invoice's total_amount
                                $invoice = \App\Models\Sales\SalesInvoice::where('invoice_number', $invReceipt['reference_number'])
                                    ->where('customer_id', $request->customer_id)
                                    ->first();
                                if ($invoice) {
                                    // Use the amount from the line item (invReceipt['amount']), not the invoice's total_amount
                                    $paymentAmount = (float) $invReceipt['amount'];
                                    
                                    // Increment paid_amount by the payment amount (allows overpayment)
                                    $invoice->increment('paid_amount', $paymentAmount);
                                    
                                    // Recalculate balance_due (can be negative if overpaid)
                                    $invoice->balance_due = $invoice->total_amount - $invoice->paid_amount;
                                    
                                    // Update status - if paid_amount >= total_amount, mark as paid
                                    // Allow overpayment (paid_amount > total_amount)
                                    if ($invoice->paid_amount >= $invoice->total_amount) {
                                        $invoice->status = 'paid';
                                    } else {
                                        $invoice->status = 'sent';
                                    }
                                    
                                    $invoice->save();
                                    
                                    // Sync linked Opening Balance amounts if applicable
                                    $invoice->syncLinkedOpeningBalance();
                                }
                            }
                        }
                    }
                }

                // Use Receipt model's createGlTransactions method which handles WHT correctly
                $receiptVoucher->refresh(); // Refresh to get updated receipt data
                $receiptVoucher->createGlTransactions();

                return redirect()->route('accounting.receipt-vouchers.show', Hashids::encode($receiptVoucher->id))
                    ->with('success', 'Receipt voucher updated successfully.');
            });
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Check if it's a reconciliation error
            if (str_contains($errorMessage, 'completed reconciliation period')) {
                $errorMessage = 'Cannot post: Receipt is in a completed reconciliation period';
            } else {
                $errorMessage = 'Failed to update receipt voucher: ' . $errorMessage;
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
        // Decode the encoded ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.receipt-vouchers.index')->withErrors(['Receipt voucher not found.']);
        }

        $receiptVoucher = Receipt::findOrFail($decoded[0]);

        try {
            return $this->runTransaction(function () use ($receiptVoucher) {
                // Delete attachment if exists
                if ($receiptVoucher->attachment && Storage::disk('public')->exists($receiptVoucher->attachment)) {
                    Storage::disk('public')->delete($receiptVoucher->attachment);
                }

                // Delete GL transactions first
                $receiptVoucher->glTransactions()->delete();

                // Delete receipt items
                $receiptVoucher->receiptItems()->delete();

                // Delete receipt
                $receiptVoucher->delete();

                return redirect()->route('accounting.receipt-vouchers.index')
                    ->with('success', 'Receipt voucher deleted successfully.');
            });
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete receipt voucher: ' . $e->getMessage()]);
        }
    }

    /**
     * Download attachment.
     */
    public function downloadAttachment($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.receipt-vouchers.index')->withErrors(['Receipt voucher not found.']);
        }

        $receiptVoucher = Receipt::findOrFail($decoded[0]);

        if (!$receiptVoucher->attachment) {
            return redirect()->back()->withErrors(['error' => 'No attachment found.']);
        }

        if (!Storage::disk('public')->exists($receiptVoucher->attachment)) {
            return redirect()->back()->withErrors(['error' => 'Attachment file not found.']);
        }

        return Storage::disk('public')->download($receiptVoucher->attachment);
    }

    /**
     * Remove attachment.
     */
    public function removeAttachment($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.receipt-vouchers.index')->withErrors(['Receipt voucher not found.']);
        }

        $receiptVoucher = Receipt::findOrFail($decoded[0]);

        try {
            // Delete attachment file if exists
            if ($receiptVoucher->attachment && Storage::disk('public')->exists($receiptVoucher->attachment)) {
                Storage::disk('public')->delete($receiptVoucher->attachment);
            }

            // Update receipt to remove attachment reference
            $receiptVoucher->update(['attachment' => null]);

            return redirect()->back()->with('success', 'Attachment removed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to remove attachment: ' . $e->getMessage()]);
        }
    }

    /**
     * Export receipt voucher as PDF
     */
    public function exportPdf($encodedId)
    {
        try {
            // Decode the hash ID to get the actual receipt ID
            $receiptId = Hashids::decode($encodedId)[0] ?? null;
            if (!$receiptId) {
                abort(404, 'Invalid receipt voucher ID');
            }

            $receiptVoucher = Receipt::with([
                'bankAccount.chartAccount.accountClassGroup',
                'user.company',
                'approvedBy',
                'employee',
                'branch',
                'receiptItems.chartAccount',
                'customer'
            ])->findOrFail($receiptId);

            // Get the associated invoice only if this is a sales invoice receipt
            $invoice = null;
            if ($receiptVoucher->reference_type === 'sales_invoice') {
                // Try to get invoice by reference_number (invoice_number) first
                if ($receiptVoucher->reference_number) {
                    $invoice = \App\Models\Sales\SalesInvoice::with(['customer', 'branch', 'company'])
                        ->where('invoice_number', $receiptVoucher->reference_number)
                        ->first();
                }
                
                // Fallback: try by reference field if it's numeric (invoice ID)
                if (!$invoice && is_numeric($receiptVoucher->reference)) {
                    $invoice = \App\Models\Sales\SalesInvoice::with(['customer', 'branch', 'company'])
                        ->find($receiptVoucher->reference);
                }
            }

            // Generate PDF using DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting.receipt-vouchers.pdf', [
                'receiptVoucher' => $receiptVoucher,
                'invoice' => $invoice
            ]);

            $pdf->setPaper('A4', 'portrait');
            
            $filename = 'Receipt_Voucher_RCP-' . $receiptVoucher->id . '_' . now()->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to export PDF: ' . $e->getMessage()]);
        }
    }

    /**
     * Deposit a cheque (move from Cheques in Transit to Bank Account)
     */
    public function depositCheque(Request $request, $id)
    {
        try {
            $receiptId = Hashids::decode($id);
            if (empty($receiptId)) {
                return redirect()->back()->with('error', 'Invalid receipt ID.');
            }
            $receiptId = $receiptId[0];

            $receipt = Receipt::findOrFail($receiptId);

            // Validate that this is a cheque receipt
            if ($receipt->payment_method !== 'cheque') {
                return redirect()->back()->with('error', 'This receipt is not a cheque payment.');
            }

            if ($receipt->cheque_deposited) {
                return redirect()->back()->with('error', 'This cheque has already been deposited.');
            }

            $depositDate = $request->input('deposit_date', now()->format('Y-m-d'));

            // Deposit the cheque
            $receipt->depositCheque(auth()->id(), \Carbon\Carbon::parse($depositDate));

            return redirect()->route('accounting.receipt-vouchers.show', $id)
                ->with('success', 'Cheque deposited successfully.');
        } catch (\Exception $e) {
            \Log::error('Cheque deposit failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to deposit cheque: ' . $e->getMessage());
        }
    }

    /**
     * Get unpaid customer invoices for receipt
     */
    public function getCustomerInvoices($customerId)
    {
        try {
            $customer = Customer::findOrFail($customerId);
            $user = Auth::user();

            \Log::info('getCustomerInvoices called', [
                'customer_id' => $customerId,
                'customer_name' => $customer->name,
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id
            ]);

            // Get all invoices for this customer (excluding cancelled)
            // Don't filter by branch - show all unpaid invoices for the customer
            $allInvoices = \App\Models\Sales\SalesInvoice::where('customer_id', $customerId)
                ->where('company_id', $user->company_id)
                ->where('status', '!=', 'cancelled')
                ->orderBy('invoice_date', 'asc')
                ->orderBy('invoice_number', 'asc')
                ->get();

            \Log::info('Found invoices for customer', [
                'customer_id' => $customerId,
                'total_invoices' => $allInvoices->count(),
                'invoice_ids' => $allInvoices->pluck('id')->toArray()
            ]);

            // Filter and map invoices that have outstanding amounts
            $invoices = $allInvoices->map(function ($invoice) {
                    $totalAmount = (float) ($invoice->total_amount ?? 0);
                    $totalPaid = (float) ($invoice->paid_amount ?? 0);
                    
                    // Calculate outstanding - prefer balance_due if set, otherwise calculate
                    $balanceDue = $invoice->balance_due;
                    if ($balanceDue === null || $balanceDue === '') {
                        // Calculate outstanding if balance_due is not set
                        $outstanding = $totalAmount - $totalPaid;
                    } else {
                        $outstanding = (float) $balanceDue;
                    }
                    
                    \Log::debug('Invoice calculation', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => $totalAmount,
                        'paid_amount' => $totalPaid,
                        'balance_due' => $balanceDue,
                        'calculated_outstanding' => $outstanding
                    ]);
                    
                    // Only include invoices with outstanding amount > 0
                    if ($outstanding <= 0) {
                        return null;
                    }
                    
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'invoice_date' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : null,
                        'due_date' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : null,
                        'total_amount' => $totalAmount,
                        'total_paid' => $totalPaid,
                        'outstanding_amount' => $outstanding,
                        'currency' => $invoice->currency ?? 'TZS',
                        'status' => $invoice->status,
                    ];
                })
                ->filter(function($invoice) {
                    // Remove null entries (invoices with no outstanding)
                    return $invoice !== null && ($invoice['outstanding_amount'] ?? 0) > 0;
                })
                ->values(); // Re-index array after filtering

            \Log::info('Returning unpaid invoices', [
                'customer_id' => $customerId,
                'unpaid_count' => $invoices->count(),
                'invoice_numbers' => $invoices->pluck('invoice_number')->toArray()
            ]);

            return response()->json(['data' => $invoices]);
        } catch (\Exception $e) {
            \Log::error('Error in getCustomerInvoices', [
                'customer_id' => $customerId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to load customer invoices: ' . $e->getMessage()], 500);
        }
    }
}
