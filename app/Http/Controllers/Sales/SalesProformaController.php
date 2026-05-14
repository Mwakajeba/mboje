<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesProforma;
use App\Models\Sales\SalesProformaItem;
use App\Models\Sales\SalesInvoice;
use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\SystemSetting;
use App\Services\FxTransactionRateService;
use App\Traits\GetsCurrenciesFromFxRates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Vinkla\Hashids\Facades\Hashids;

class SalesProformaController extends Controller
{
    use GetsCurrenciesFromFxRates;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if ($request->ajax()) {
            $proformas = SalesProforma::with(['customer', 'createdBy'])
                ->whereHas('customer', function ($query) use ($user) {
                    $query->whereHas('branch', function ($q) use ($user) {
                        $q->where('company_id', $user->company_id);
                    });
                })
                ->when($user->branch_id, function ($query) use ($user) {
                    return $query->where('branch_id', $user->branch_id);
                });

            return DataTables::of($proformas)
                ->addColumn('actions', function ($proforma) {
                    $encodedId = \Vinkla\Hashids\Facades\Hashids::encode($proforma->id);
                    $actions = '<div class="d-flex gap-2">';
                    $actions .= '<a href="' . route('sales.proformas.show', $encodedId) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                    
                    if ($proforma->status === 'draft') {
                        $actions .= '<a href="' . route('sales.proformas.edit', $encodedId) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bx bx-edit"></i></a>';
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-info" onclick="convertProforma(\'' . $encodedId . '\', \'' . e($proforma->proforma_number) . '\')" title="Convert"><i class="bx bx-transfer"></i></button>';
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteProforma(\'' . $encodedId . '\', \'' . e($proforma->proforma_number) . '\')" title="Delete"><i class="bx bx-trash"></i></button>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->addColumn('status_badge', function ($proforma) {
                    $colors = [
                        'draft' => 'secondary',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'warning'
                    ];
                    $color = $colors[$proforma->status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . strtoupper($proforma->status) . '</span>';
                })
                ->addColumn('formatted_date', function ($proforma) {
                    return format_date($proforma->proforma_date, 'M d, Y');
                })
                ->addColumn('formatted_valid_until', function ($proforma) {
                    return format_date($proforma->valid_until, 'M d, Y');
                })
                ->addColumn('formatted_total', function ($proforma) {
                    return 'TZS ' . number_format($proforma->total_amount, 2);
                })
                ->addColumn('customer_name', function ($proforma) {
                    return $proforma->customer->name ?? 'N/A';
                })
                ->rawColumns(['actions', 'status_badge'])
                ->make(true);
        }

        // Get stats for the dashboard
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        
        // Base query for all stats
        $baseQuery = SalesProforma::whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            });

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'sent' => (clone $baseQuery)->where('status', 'sent')->count(),
            'accepted' => (clone $baseQuery)->where('status', 'accepted')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'expired' => (clone $baseQuery)->where('status', 'expired')->count(),
        ];

        // Load bank accounts for cash sale conversion in index modal
        $bankAccounts = \App\Models\BankAccount::orderBy('name')->get(['id','name']);

        return view('sales.proformas.index', compact('stats', 'bankAccounts'));
    }

    /**
     * Show the form for creating a new proforma (Sales Quote) from a sales invoice (Copy To).
     */
    public function createFromInvoice($invoiceEncodedId)
    {
        $invoiceId = Hashids::decode($invoiceEncodedId)[0] ?? null;
        if (!$invoiceId) {
            abort(404);
        }
        $user = Auth::user();
        $invoice = SalesInvoice::with(['customer', 'items.inventoryItem'])
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->findOrFail($invoiceId);

        $customers = Customer::whereHas('branch', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->orderBy('name')
            ->get();

        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        $inventoryItems = $stockService->getAvailableItemsForSales($user->company_id, $loginLocationId);
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        $copyFromInvoice = [
            'customer' => ['id' => $invoice->customer_id],
            'proforma_date' => $invoice->invoice_date?->format('Y-m-d') ?? date('Y-m-d'),
            'valid_until' => date('Y-m-d', strtotime('+30 days')),
            'currency' => $invoice->currency,
            'exchange_rate' => (float) ($invoice->exchange_rate ?? 1.000000),
            'notes' => $invoice->notes ?? '',
            'terms_conditions' => $invoice->terms_conditions ?? '',
            'items' => $invoice->items->map(function ($line) {
                return [
                    'inventory_item_id' => $line->inventory_item_id,
                    'item_name' => $line->item_name,
                    'item_code' => $line->item_code ?? '',
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                    'vat_type' => $line->vat_type ?? 'no_vat',
                    'vat_rate' => (float) ($line->vat_rate ?? 0),
                    'vat_amount' => (float) ($line->vat_amount ?? 0),
                    'line_total' => (float) ($line->line_total ?? 0),
                ];
            })->values()->all(),
        ];

        $currencies = $this->getCurrenciesFromFxRates($user->company_id);

        return view('sales.proformas.create', compact('customers', 'inventoryItems', 'invoice', 'copyFromInvoice', 'currencies'))->with('items', $inventoryItems);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        $customers = Customer::whereHas('branch', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })
        ->when($user->branch_id, function ($query) use ($user) {
            return $query->where('branch_id', $user->branch_id);
        })
        ->orderBy('name')
        ->get();

        // Get items with stock > 0 at current location
        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        $inventoryItems = $stockService->getAvailableItemsForSales($user->company_id, $loginLocationId);
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);
        $functionalCurrency = SystemSetting::getValue('functional_currency', $user->company->functional_currency ?? 'TZS');
        $currencies = $this->getCurrenciesFromFxRates($user->company_id);

        $copyFromInvoice = null;
        if ($request->filled('copy_from_invoice')) {
            $sourceId = Hashids::decode($request->copy_from_invoice)[0] ?? null;
            if ($sourceId) {
                $source = \App\Models\Sales\SalesInvoice::with(['customer', 'items'])->where('company_id', $user->company_id)->find($sourceId);
                if ($source) {
                    $vatType = fn($v) => $v === 'exclusive' ? 'vat_exclusive' : ($v === 'no_vat' ? 'no_vat' : 'vat_inclusive');
                    $copyFromInvoice = [
                        'customer' => ['id' => $source->customer_id],
                        'proforma_date' => $source->invoice_date ? $source->invoice_date->format('Y-m-d') : now()->format('Y-m-d'),
                        'valid_until' => $source->due_date ? $source->due_date->format('Y-m-d') : now()->addDays(30)->format('Y-m-d'),
                        'currency' => $source->currency ?? $functionalCurrency,
                        'exchange_rate' => (float) ($source->exchange_rate ?? 1.000000),
                        'notes' => $source->notes ?? '',
                        'terms_conditions' => $source->terms_conditions ?? '',
                        'items' => $source->items->map(function ($item) use ($vatType) {
                            return [
                                'inventory_item_id' => $item->inventory_item_id,
                                'item_name' => $item->item_name ?? '',
                                'item_code' => $item->item_code ?? '',
                                'quantity' => (float) $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'vat_type' => $vatType($item->vat_type ?? 'inclusive'),
                                'vat_rate' => (float) ($item->vat_rate ?? 0),
                                'vat_amount' => (float) ($item->vat_amount ?? 0),
                                'line_total' => (float) $item->line_total,
                            ];
                        })->values()->all(),
                    ];
                }
            }
        }

        return view('sales.proformas.create', compact('customers', 'inventoryItems', 'copyFromInvoice', 'currencies'))->with('items', $inventoryItems);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'proforma_date' => 'required|date',
            'valid_until' => 'required|date|after:proforma_date',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'vat_amount' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'terms_conditions' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $branchId = session('branch_id') ?? $user->branch_id ?? null;
            $functionalCurrency = SystemSetting::getValue('functional_currency', $user->company->functional_currency ?? 'TZS');
            $proformaCurrency = $request->currency ?? $functionalCurrency;
            $fxTransactionRateService = app(FxTransactionRateService::class);
            $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
            $rateResult = $fxTransactionRateService->getTransactionRate(
                $proformaCurrency,
                $functionalCurrency,
                $request->proforma_date,
                $user->company_id,
                $userProvidedRate
            );
            $exchangeRate = $rateResult['rate'];

            // Calculate VAT totals from items
            $totalVatAmount = 0;
            $itemVatRates = [];
            foreach ($request->items as $itemData) {
                $totalVatAmount += $itemData['vat_amount'] ?? 0;
                if (($itemData['vat_rate'] ?? 0) > 0) {
                    $itemVatRates[] = $itemData['vat_rate'];
                }
            }
            
            // Determine VAT rate display
            $uniqueVatRates = array_unique($itemVatRates);
            $vatRateDisplay = count($uniqueVatRates) > 1 ? 0 : (count($uniqueVatRates) == 1 ? $uniqueVatRates[0] : 0);

            // Handle attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('sales-proforma-attachments', $fileName, 'public');
            }

            $proformaData = [
                'customer_id' => $request->customer_id,
                'proforma_date' => $request->proforma_date,
                'valid_until' => $request->valid_until,
                'user_id' => $user->id,
                'proforma_number' => 'PF-' . date('Ymd') . str_pad(SalesProforma::whereDate('created_at', now()->toDateString())->count() + 1, 4, '0', STR_PAD_LEFT),
                'status' => 'draft',
                'subtotal' => $request->subtotal,
                'vat_type' => $request->vat_type ?? 'no_vat',
                'vat_rate' => $vatRateDisplay,
                'vat_amount' => $totalVatAmount,
                'tax_amount' => $request->tax_amount ?? 0, // Additional tax (separate from VAT)
                'discount_type' => $request->discount_type ?? 'percentage',
                'discount_rate' => $request->discount_rate ?? 0,
                'discount_amount' => $request->discount_amount,
                'total_amount' => $request->total_amount,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'attachment' => $attachmentPath,
                'branch_id' => $branchId,
                'company_id' => $user->company_id,
                'created_by' => $user->id,
            ];

            // Backward compatibility for databases where this migration is not yet applied.
            if (Schema::hasColumn('sales_proformas', 'currency')) {
                $proformaData['currency'] = $proformaCurrency;
            }
            if (Schema::hasColumn('sales_proformas', 'exchange_rate')) {
                $proformaData['exchange_rate'] = $exchangeRate;
            }

            $proforma = SalesProforma::create($proformaData);

            // Create proforma items
            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::find($itemData['inventory_item_id']);
                
                SalesProformaItem::create([
                    'sales_proforma_id' => $proforma->id,
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'vat_type' => $itemData['vat_type'] ?? 'no_vat',
                    'vat_rate' => $itemData['vat_rate'] ?? 0,
                    'vat_amount' => $itemData['vat_amount'] ?? 0,
                    'discount_type' => $itemData['discount_type'] ?? 'percentage',
                    'discount_rate' => $itemData['discount_rate'] ?? 0,
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'line_total' => $itemData['line_total'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proforma created successfully',
                'proforma_id' => $proforma->id,
                'redirect_url' => route('sales.proformas.show', \Vinkla\Hashids\Facades\Hashids::encode($proforma->id))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating proforma: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $proforma = SalesProforma::with(['customer', 'items.inventoryItem', 'createdBy'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        // Load bank accounts for cash sale conversion
        $bankAccounts = \App\Models\BankAccount::orderBy('name')->get(['id','name']);

        return view('sales.proformas.show', compact('proforma', 'bankAccounts'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $proforma = SalesProforma::with(['customer', 'items.inventoryItem', 'createdBy'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        // Only allow editing if status is draft
        if ($proforma->status !== 'draft') {
            return redirect()->route('sales.proformas.show', $proforma->id)
                ->with('error', 'Only draft proformas can be edited.');
        }

        $customers = Customer::whereHas('branch', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })
        ->when($user->branch_id, function ($query) use ($user) {
            return $query->where('branch_id', $user->branch_id);
        })
        ->orderBy('name')
        ->get();

        // Get items with stock > 0 at current location
        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        $inventoryItems = $stockService->getAvailableItemsForSales($user->company_id, $loginLocationId);
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);
        $currencies = $this->getCurrenciesFromFxRates($user->company_id);

        return view('sales.proformas.edit', compact('proforma', 'customers', 'inventoryItems', 'currencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $proforma = SalesProforma::whereHas('customer', function ($query) use ($user) {
            $query->whereHas('branch', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        })
        ->when($user->branch_id, function ($query) use ($user) {
            return $query->where('branch_id', $user->branch_id);
        })
        ->findOrFail($decodedId);

        // Only allow updating if status is draft
        if ($proforma->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft proformas can be updated.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'proforma_date' => 'required|date',
            'valid_until' => 'required|date|after:proforma_date',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'vat_amount' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'terms_conditions' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $functionalCurrency = SystemSetting::getValue('functional_currency', $user->company->functional_currency ?? 'TZS');
            $proformaCurrency = $request->currency ?? $functionalCurrency;
            $fxTransactionRateService = app(FxTransactionRateService::class);
            $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
            $rateResult = $fxTransactionRateService->getTransactionRate(
                $proformaCurrency,
                $functionalCurrency,
                $request->proforma_date,
                $user->company_id,
                $userProvidedRate
            );
            $exchangeRate = $rateResult['rate'];

            // Calculate VAT totals from items
            $totalVatAmount = 0;
            $itemVatRates = [];
            foreach ($request->items as $itemData) {
                $totalVatAmount += $itemData['vat_amount'] ?? 0;
                if (($itemData['vat_rate'] ?? 0) > 0) {
                    $itemVatRates[] = $itemData['vat_rate'];
                }
            }
            
            // Determine VAT rate display
            $uniqueVatRates = array_unique($itemVatRates);
            $vatRateDisplay = count($uniqueVatRates) > 1 ? 0 : (count($uniqueVatRates) == 1 ? $uniqueVatRates[0] : 0);

            // Update proforma
            $updateData = [
                'customer_id' => $request->customer_id,
                'proforma_date' => $request->proforma_date,
                'valid_until' => $request->valid_until,
                'subtotal' => $request->subtotal,
                'vat_type' => $request->vat_type ?? 'no_vat',
                'vat_rate' => $vatRateDisplay,
                'vat_amount' => $totalVatAmount,
                'tax_amount' => $request->tax_amount ?? 0, // Additional tax (separate from VAT)
                'discount_type' => $request->discount_type ?? 'percentage',
                'discount_rate' => $request->discount_rate ?? 0,
                'discount_amount' => $request->discount_amount,
                'total_amount' => $request->total_amount,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
            ];

            // Backward compatibility for databases where this migration is not yet applied.
            if (Schema::hasColumn('sales_proformas', 'currency')) {
                $updateData['currency'] = $proformaCurrency;
            }
            if (Schema::hasColumn('sales_proformas', 'exchange_rate')) {
                $updateData['exchange_rate'] = $exchangeRate;
            }

            // Handle attachment upload
            if ($request->hasFile('attachment')) {
                if ($proforma->attachment && \Storage::disk('public')->exists($proforma->attachment)) {
                    \Storage::disk('public')->delete($proforma->attachment);
                }
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $updateData['attachment'] = $file->storeAs('sales-proforma-attachments', $fileName, 'public');
            }

            $proforma->update($updateData);

            // Delete existing items
            $proforma->items()->delete();

            // Create new proforma items
            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::find($itemData['inventory_item_id']);
                
                SalesProformaItem::create([
                    'sales_proforma_id' => $proforma->id,
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'vat_type' => $itemData['vat_type'] ?? 'no_vat',
                    'vat_rate' => $itemData['vat_rate'] ?? 0,
                    'vat_amount' => $itemData['vat_amount'] ?? 0,
                    'discount_type' => $itemData['discount_type'] ?? 'percentage',
                    'discount_rate' => $itemData['discount_rate'] ?? 0,
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'line_total' => $itemData['line_total'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proforma updated successfully',
                'redirect_url' => route('sales.proformas.show', \Vinkla\Hashids\Facades\Hashids::encode($proforma->id))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating proforma: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $proforma = SalesProforma::whereHas('customer', function ($query) use ($user) {
            $query->whereHas('branch', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        })
        ->when($user->branch_id, function ($query) use ($user) {
            return $query->where('branch_id', $user->branch_id);
        })
        ->findOrFail($decodedId);

        // Only allow deletion if status is draft
        if ($proforma->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft proformas can be deleted.'
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            // Permanently delete proforma items first (if any)
            try {
                foreach ($proforma->items as $item) {
                    $item->forceDelete();
                }
            } catch (\Throwable $e) {
                // Fallback to bulk delete if forceDelete not supported on items
                $proforma->items()->delete();
            }

            // Permanently delete the proforma (no soft delete)
            $proforma->forceDelete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proforma deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting proforma: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get item details for AJAX request
     */
    public function getItemDetails($id)
    {
        $user = Auth::user();
        
        $item = InventoryItem::where('company_id', $user->company_id)
            ->findOrFail($id);

        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $locationId = session('location_id');

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'unit_price' => $item->getUnitPriceForBranchOrLocation($branchId, $locationId),
                'unit_of_measure' => $item->unit_of_measure,
                'current_stock' => $item->current_stock,
                'description' => $item->description,
            ]
        ]);
    }

    /**
     * Update proforma status
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $proforma = SalesProforma::whereHas('customer', function ($query) use ($user) {
            $query->whereHas('branch', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        })
        ->when($user->branch_id, function ($query) use ($user) {
            return $query->where('branch_id', $user->branch_id);
        })
        ->findOrFail($decodedId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,sent,accepted,rejected,expired'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status'
            ], 422);
        }

        $proforma->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Proforma status updated successfully'
        ]);
    }

    /**
     * Convert proforma to different document types
     */
    public function convertToDocument(Request $request, $id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $proforma = SalesProforma::with(['customer', 'items'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:order,invoice,cash_sale'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid document type'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update proforma status to accepted since conversion implies acceptance
            $proforma->update(['status' => 'accepted']);

            $documentType = $request->document_type;
            $redirectUrl = '';

            switch ($documentType) {
                case 'order':
                    // Create sales order
                    $order = \App\Models\Sales\SalesOrder::create([
                        'customer_id' => $proforma->customer_id,
                        'order_date' => now(),
                        'expected_delivery_date' => $proforma->valid_until,
                        'status' => 'draft',
                        'subtotal' => $proforma->subtotal,
                        'vat_type' => $proforma->vat_type,
                        'vat_rate' => $proforma->vat_rate,
                        'vat_amount' => $proforma->vat_amount,
                        'tax_amount' => $proforma->tax_amount,
                        'discount_type' => $proforma->discount_type,
                        'discount_rate' => $proforma->discount_rate,
                        'discount_amount' => $proforma->discount_amount,
                        'total_amount' => $proforma->total_amount,
                        'notes' => $proforma->notes,
                        'terms_conditions' => $proforma->terms_conditions,
                        'branch_id' => $proforma->branch_id,
                        'company_id' => $proforma->company_id,
                        'created_by' => $user->id,
                        'proforma_id' => $proforma->id
                    ]);

                    // Create order items
                    foreach ($proforma->items as $item) {
                        // Get the inventory item to get additional details
                        $inventoryItem = \App\Models\Inventory\Item::find($item->inventory_item_id);
                        
                        \App\Models\Sales\SalesOrderItem::create([
                            'sales_order_id' => $order->id,
                            'inventory_item_id' => $item->inventory_item_id,
                            'item_name' => $item->item_name,
                            'item_code' => $item->item_code,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'unit_of_measure' => $inventoryItem ? $inventoryItem->unit_of_measure : 'each',
                            'available_stock' => $inventoryItem ? $inventoryItem->current_stock : 0,
                            'reserved_stock' => 0,
                            'stock_available' => $inventoryItem ? ($inventoryItem->current_stock >= $item->quantity) : false,
                            'vat_type' => $item->vat_type,
                            'vat_rate' => $item->vat_rate,
                            'vat_amount' => $item->vat_amount,
                            'discount_type' => $item->discount_type,
                            'discount_rate' => $item->discount_rate,
                            'discount_amount' => $item->discount_amount,
                            'line_total' => $item->line_total,
                            'notes' => null
                        ]);
                    }

                    $redirectUrl = route('sales.orders.show', Hashids::encode($order->id));
                    break;

                case 'invoice':
                    // Create sales invoice from proforma
                    $invoice = \App\Models\Sales\SalesInvoice::create([
                        'invoice_number' => $this->generateInvoiceNumber(),
                        'sales_order_id' => null,
                        'customer_id' => $proforma->customer_id,
                        'invoice_date' => now()->toDateString(),
                        'due_date' => $proforma->valid_until,
                        'status' => 'draft',
                        // Provide sensible defaults to satisfy NOT NULL constraints
                        'payment_terms' => 'net_30',
                        'payment_days' => 30,
                        'subtotal' => $proforma->subtotal,
                        'vat_amount' => $proforma->vat_amount,
                        'discount_amount' => $proforma->discount_amount,
                        'total_amount' => $proforma->total_amount,
                        'paid_amount' => 0,
                        'balance_due' => $proforma->total_amount,
                        'currency' => $proforma->currency ?? 'TZS',
                        'exchange_rate' => $proforma->exchange_rate ?? 1.000000,
                        'withholding_tax_amount' => 0,
                        'withholding_tax_rate' => 0,
                        'withholding_tax_type' => 'percentage',
                        'notes' => $proforma->notes,
                        'terms_conditions' => $proforma->terms_conditions,
                        'branch_id' => $proforma->branch_id,
                        'company_id' => $proforma->company_id,
                        'created_by' => $user->id,
                    ]);

                    // Create invoice items
                    foreach ($proforma->items as $item) {
                        $inventoryItem = \App\Models\Inventory\Item::find($item->inventory_item_id);
                        \App\Models\Sales\SalesInvoiceItem::create([
                            'sales_invoice_id' => $invoice->id,
                            'inventory_item_id' => $item->inventory_item_id,
                            'item_name' => $item->item_name,
                            'item_code' => $item->item_code,
                            'description' => $inventoryItem->description ?? null,
                            'unit_of_measure' => $inventoryItem->unit_of_measure ?? 'each',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'vat_type' => $item->vat_type ?? 'no_vat',
                            'vat_rate' => $item->vat_rate ?? 0,
                            'vat_amount' => $item->vat_amount ?? 0,
                            'discount_type' => $item->discount_type ?? 'percentage',
                            'discount_rate' => $item->discount_rate ?? 0,
                            'discount_amount' => $item->discount_amount ?? 0,
                            'line_total' => $item->line_total,
                            'available_stock' => $inventoryItem->current_stock ?? 0,
                            'reserved_stock' => 0,
                            'stock_available' => isset($inventoryItem->current_stock) ? ($inventoryItem->current_stock >= $item->quantity) : true,
                            'notes' => null,
                        ]);
                    }

                    $redirectUrl = route('sales.invoices.show', Hashids::encode($invoice->id));
                    break;

                case 'cash_sale':
                    // Create cash sale from proforma
                    // Resolve a bank account: system default -> any active bank account -> null (fail)
                    $requestedBankId = $request->input('bank_account_id');
                    $defaultBankAccountId = $requestedBankId ?: \App\Models\SystemSetting::where('key', 'default_bank_account_id')->value('value');
                    if (!$defaultBankAccountId) {
                        $defaultBankAccountId = \App\Models\BankAccount::orderBy('name')->value('id');
                    }
                    if (!$defaultBankAccountId) {
                        throw new \Exception('No bank account available for cash sale. Please configure a default bank account.');
                    }

                    $cashSale = \App\Models\Sales\CashSale::create([
                        'sale_number' => \App\Models\Sales\CashSale::generateSaleNumber(),
                        'customer_id' => $proforma->customer_id,
                        'sale_date' => now()->toDateString(),
                        'payment_method' => 'bank',
                        'bank_account_id' => $defaultBankAccountId,
                        'cash_deposit_id' => null,
                        'subtotal' => $proforma->subtotal,
                        'vat_amount' => $proforma->vat_amount,
                        'discount_amount' => $proforma->discount_amount,
                        'total_amount' => $proforma->total_amount,
                        'paid_amount' => $proforma->total_amount,
                        'currency' => $proforma->currency ?? 'TZS',
                        'exchange_rate' => $proforma->exchange_rate ?? 1.000000,
                        'vat_rate' => $proforma->vat_rate ?? 0,
                        'withholding_tax_amount' => 0,
                        'withholding_tax_rate' => 0,
                        'withholding_tax_type' => 'percentage',
                        'notes' => $proforma->notes,
                        'terms_conditions' => $proforma->terms_conditions,
                        'branch_id' => $proforma->branch_id,
                        'company_id' => $proforma->company_id,
                        'created_by' => $user->id,
                    ]);

                    // Optionally create cash sale items if model exists
                    if (class_exists(\App\Models\Sales\CashSaleItem::class)) {
                        foreach ($proforma->items as $item) {
                            $inventoryItem = \App\Models\Inventory\Item::find($item->inventory_item_id);
                            \App\Models\Sales\CashSaleItem::create([
                                'cash_sale_id' => $cashSale->id,
                                'inventory_item_id' => $item->inventory_item_id,
                                'item_name' => $item->item_name,
                                'item_code' => $item->item_code,
                                'unit_of_measure' => $inventoryItem->unit_of_measure ?? 'each',
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'vat_type' => $item->vat_type ?? 'no_vat',
                                'vat_rate' => $item->vat_rate ?? 0,
                                'vat_amount' => $item->vat_amount ?? 0,
                                'discount_type' => $item->discount_type ?? 'percentage',
                                'discount_rate' => $item->discount_rate ?? 0,
                                'discount_amount' => $item->discount_amount ?? 0,
                                'line_total' => $item->line_total,
                            ]);
                        }
                    }

                    // Create GL transactions for the cash sale
                    $cashSale->load('items');
                    $cashSale->updateTotals();
                    $cashSale->createDoubleEntryTransactions();

                    $redirectUrl = route('sales.cash-sales.show', Hashids::encode($cashSale->id));
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proforma converted to ' . str_replace('_', ' ', $documentType) . ' successfully',
                'redirect_url' => $redirectUrl
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error converting proforma: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber()
    {
        // TODO: Implement when SalesInvoice model is created
        return 'INV-' . date('Ymd') . '000001';
    }

    /**
     * Generate cash sale number
     */
    private function generateCashSaleNumber()
    {
        // TODO: Implement when CashSale model is created
        return 'CS-' . date('Ymd') . '000001';
    }

    /**
     * Export Proforma to PDF
     */
    public function exportPdf(string $id)
    {
        $user = Auth::user();
        $decodedId = \Vinkla\Hashids\Facades\Hashids::decode($id)[0] ?? null;
        if (!$decodedId) abort(404);

        $proforma = SalesProforma::with(['customer', 'items', 'createdBy'])
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn($q)=>$q->where('branch_id', $user->branch_id))
            ->findOrFail($decodedId);

        $company = \App\Models\Company::find($user->company_id);
        $branch = $user->branch_id ? \App\Models\Branch::find($user->branch_id) : null;

        $pdf = \PDF::loadView('sales.proformas.export-pdf', compact('proforma', 'company', 'branch'));
        $filename = $proforma->proforma_number . '.pdf';
        return $pdf->download($filename);
    }
}
