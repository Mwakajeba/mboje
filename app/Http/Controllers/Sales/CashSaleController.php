<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\CashSale;
use App\Models\Sales\CashSaleItem;
use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\BankAccount;
use App\Models\CashDeposit;
use App\Models\CashDepositAccount;
use App\Models\SystemSetting;
use App\Services\FxTransactionRateService;
use App\Traits\GetsCurrenciesFromFxRates;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Inventory\Movement as InventoryMovement;

class CashSaleController extends Controller
{
    use GetsCurrenciesFromFxRates;
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $cashSales = CashSale::with(['customer', 'branch', 'createdBy'])
                ->forBranch(auth()->user()->branch_id)
                ->select(['id', 'sale_number', 'customer_id', 'sale_date', 'payment_method', 'total_amount', 'branch_id', 'created_by', 'created_at']);

            return datatables($cashSales)
                ->addColumn('customer_name', function ($cashSale) {
                    return $cashSale->customer->name ?? 'N/A';
                })
                ->addColumn('sale_date_formatted', function ($cashSale) {
                    return format_date($cashSale->sale_date, 'M d, Y');
                })
                ->addColumn('payment_method_text', function ($cashSale) {
                    return $cashSale->payment_method_text;
                })
                ->addColumn('total_amount_formatted', function ($cashSale) {
                    return 'TZS ' . number_format($cashSale->total_amount, 2);
                })
                ->addColumn('created_by_name', function ($cashSale) {
                    return $cashSale->createdBy->name ?? 'N/A';
                })
                ->addColumn('actions', function ($cashSale) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('sales.cash-sales.show', $cashSale->encoded_id) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                                          $actions .= '<a href="' . route('sales.cash-sales.edit', $cashSale->encoded_id) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                      $actions .= '<a href="' . route('sales.cash-sales.print', $cashSale->encoded_id) . '" class="btn btn-sm btn-secondary" title="Print" target="_blank"><i class="bx bx-printer"></i></a>';
                    $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteCashSale(\'' . $cashSale->encoded_id . '\')" title="Delete"><i class="bx bx-trash"></i></button>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('sales.cash-sales.index');
    }

    public function create(Request $request)
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        if (!$branchId) {
            return redirect()->back()->withErrors(['error' => 'Please select a branch before creating a cash sale.']);
        }

        $customers = Customer::forBranch($branchId)->active()->get();
        
        // Get items with stock > 0 at current location
        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        $inventoryItems = $stockService->getAvailableItemsForSales(auth()->user()->company_id, $loginLocationId);
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        // Limit bank accounts by branch scope (all branches or current branch)
        $user = auth()->user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $bankAccounts = BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();
        $cashDepositAccounts = CashDepositAccount::where('is_active', true)->get();
        
        $selectedCustomer = null;
        if ($request->has('customer_id')) {
            $customerId = Hashids::decode($request->customer_id)[0] ?? null;
            if ($customerId) {
                $selectedCustomer = Customer::find($customerId);
            }
        }
        
        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        return view('sales.cash-sales.create', compact('customers', 'inventoryItems', 'bankAccounts', 'cashDepositAccounts', 'selectedCustomer', 'currencies'))->with('items', $inventoryItems);
    }

    public function store(Request $request)
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        if (!$branchId) {
            return redirect()->back()->withInput()->withErrors(['error' => 'Please select a branch before creating a cash sale.']);
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,cash_deposit',
            'bank_account_id' => 'nullable|required_if:payment_method,bank|exists:bank_accounts,id',
            'cash_deposit_id' => 'nullable|in:customer_balance',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'discount_amount' => 'nullable|numeric|min:0',
            'withholding_tax_enabled' => 'nullable|boolean',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'withholding_tax_type' => 'nullable|in:percentage,fixed',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
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

        try {
            DB::beginTransaction();

            if ($request->payment_method === 'cash_deposit' && $request->cash_deposit_id) {
                // Handle special case for customer balance
                if ($request->cash_deposit_id === 'customer_balance') {
                    // For customer balance, we don't need to validate against CashDeposit table
                    // Just ensure the customer exists
                    $customer = Customer::findOrFail($request->customer_id);
                } else {
                    // For actual cash deposits, validate as before
                    $cashDeposit = CashDeposit::findOrFail($request->cash_deposit_id);
                    if ($cashDeposit->customer_id != $request->customer_id) {
                        throw new \Exception('Cash deposit does not belong to the selected customer.');
                    }
                }
            }

            // Handle cash_deposit_id for customer_balance case
            $cashDepositId = ($request->cash_deposit_id === 'customer_balance') ? null : $request->cash_deposit_id;
            
            // Get functional currency
            $functionalCurrency = SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
            $saleCurrency = $request->currency ?? $functionalCurrency;
            $companyId = auth()->user()->company_id;

            // Get exchange rate using FxTransactionRateService
            $fxTransactionRateService = app(FxTransactionRateService::class);
            $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
            $rateResult = $fxTransactionRateService->getTransactionRate(
                $saleCurrency,
                $functionalCurrency,
                $request->sale_date,
                $companyId,
                $userProvidedRate
            );
            $exchangeRate = $rateResult['rate'];
            $fxRateUsed = $exchangeRate; // Store the rate used for fx_rate_used field

            // Handle attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('cash-sale-attachments', $fileName, 'public');
            }
            
            $cashSale = CashSale::create([
                'customer_id' => $request->customer_id,
                'sale_date' => $request->sale_date,
                'payment_method' => $request->payment_method,
                'bank_account_id' => $request->bank_account_id,
                'cash_deposit_id' => $cashDepositId,
                'currency' => $saleCurrency,
                'exchange_rate' => $exchangeRate,
                'fx_rate_used' => $fxRateUsed,
                'discount_amount' => $request->discount_amount ?? 0,
                'withholding_tax_rate' => $request->withholding_tax_enabled ? ($request->withholding_tax_rate ?? 5) : 0,
                'withholding_tax_type' => $request->withholding_tax_enabled ? ($request->withholding_tax_type ?? 'percentage') : 'percentage',
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'attachment' => $attachmentPath,
                'branch_id' => $branchId,
                'company_id' => auth()->user()->company_id,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::queryVisibleForSession()->findOrFail($itemData['inventory_item_id']);
                $requestedQuantity = $itemData['quantity'];
                $availableStock = 0;

                // Skip stock validation for service items or items that don't track stock
                if ($inventoryItem &&
                    $inventoryItem->item_type !== 'service' &&
                    $inventoryItem->track_stock) {
                    $stockService = new \App\Services\InventoryStockService();
                    $availableStock = $stockService->getItemStockAtLocation($inventoryItem->id, session('location_id'));

                    if ($availableStock < $requestedQuantity) {
                        throw new \Exception("Insufficient stock for item {$inventoryItem->name}. Available: {$availableStock}, Requested: {$requestedQuantity}");
                    }
                }

                // Create cash sale item with only required fields
                $cashSaleItem = CashSaleItem::create([
                    'cash_sale_id' => $cashSale->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'description' => $inventoryItem->description ?? '',
                    'unit_of_measure' => $inventoryItem->unit_of_measure ?? '',
                    'quantity' => $requestedQuantity,
                    'unit_price' => $itemData['unit_price'],
                    'price_tier' => InventoryItem::normalizedPriceTier($itemData['price_tier'] ?? null),
                    'line_total' => 0, // Will be calculated by calculateLineTotal()
                    'vat_type' => $itemData['vat_type'],
                    'vat_rate' => $itemData['vat_rate'],
                    'vat_amount' => 0, // Will be calculated by calculateLineTotal()
                    'discount_type' => $itemData['discount_type'] ?? null,
                    'discount_rate' => $itemData['discount_rate'] ?? 0,
                    'discount_amount' => 0, // Will be calculated by calculateLineTotal()
                    'available_stock' => $availableStock,
                    'reserved_stock' => 0, // Default value since this field doesn't exist in InventoryItem
                    'stock_available' => $availableStock >= $requestedQuantity,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $cashSaleItem->calculateLineTotal();
                $cashSaleItem->save();
            }

            $cashSale->updateTotals();
            $cashSale->processPayment();
            $cashSale->createDoubleEntryTransactions();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cash sale created successfully!',
                    'redirect_url' => route('sales.cash-sales.show', $cashSale->encoded_id)
                ]);
            }

            return redirect()->route('sales.cash-sales.show', $cashSale->encoded_id)
                ->with('success', 'Cash sale created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create cash sale: ' . $e->getMessage()
                ], 422);
            }

            return redirect()->back()
                ->with('error', 'Failed to create cash sale: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($encodedId)
    {
        $cashSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$cashSaleId) {
            return redirect()->route('cash-sales.index')
                ->with('error', 'Invalid cash sale ID');
        }

        $cashSale = CashSale::with([
            'customer', 
            'items.inventoryItem', 
            'branch', 
            'company', 
            'createdBy', 
            'updatedBy',
            'bankAccount',
            'cashDeposit.type',
            'glTransactions.chartAccount'
        ])->findOrFail($cashSaleId);

        return view('sales.cash-sales.show', compact('cashSale'));
    }

    public function edit($encodedId)
    {
        $cashSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$cashSaleId) {
            return redirect()->route('cash-sales.index')
                ->with('error', 'Invalid cash sale ID');
        }

        $cashSale = CashSale::with(['items.inventoryItem', 'customer'])->findOrFail($cashSaleId);
        $customers = Customer::forBranch(auth()->user()->branch_id)->active()->get();
        
        // Get items with stock > 0 at current location
        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        $inventoryItems = $stockService->getAvailableItemsForSales(auth()->user()->company_id, $loginLocationId);
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        // Create a map of original quantities by item_id for frontend validation
        $originalQuantities = [];
        foreach ($cashSale->items as $item) {
            $itemId = $item->inventory_item_id;
            if (!isset($originalQuantities[$itemId])) {
                $originalQuantities[$itemId] = 0;
            }
            $originalQuantities[$itemId] += $item->quantity;
        }
        
        // Limit bank accounts by branch scope (all branches or current branch)
        $user = auth()->user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $bankAccounts = BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();
        $cashDepositAccounts = CashDepositAccount::where('is_active', true)->get();
        
        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        return view('sales.cash-sales.edit', compact('cashSale', 'customers', 'inventoryItems', 'bankAccounts', 'cashDepositAccounts', 'currencies', 'originalQuantities'))->with('items', $inventoryItems);
    }

    public function update(Request $request, $encodedId)
    {
        $cashSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$cashSaleId) {
            return redirect()->route('cash-sales.index')
                ->with('error', 'Invalid cash sale ID');
        }

        $cashSale = CashSale::findOrFail($cashSaleId);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,cash_deposit',
            'bank_account_id' => 'nullable|required_if:payment_method,bank|exists:bank_accounts,id',
            'cash_deposit_id' => 'nullable|in:customer_balance',
            'discount_amount' => 'nullable|numeric|min:0',
            'withholding_tax_enabled' => 'nullable|boolean',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'withholding_tax_type' => 'nullable|in:percentage,fixed',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
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

        try {
            DB::beginTransaction();

            if ($request->payment_method === 'cash_deposit' && $request->cash_deposit_id) {
                // Handle special case for customer balance
                if ($request->cash_deposit_id === 'customer_balance') {
                    // For customer balance, we don't need to validate against CashDeposit table
                    // Just ensure the customer exists
                    $customer = Customer::findOrFail($request->customer_id);
                } else {
                    // For actual cash deposits, validate as before
                    $cashDeposit = CashDeposit::findOrFail($request->cash_deposit_id);
                    if ($cashDeposit->customer_id != $request->customer_id) {
                        throw new \Exception('Cash deposit does not belong to the selected customer.');
                    }
                }
            }

            // Handle cash_deposit_id for customer_balance case
            $cashDepositId = ($request->cash_deposit_id === 'customer_balance') ? null : $request->cash_deposit_id;
            
            $updateData = [
                'customer_id' => $request->customer_id,
                'sale_date' => $request->sale_date,
                'payment_method' => $request->payment_method,
                'bank_account_id' => $request->bank_account_id,
                'cash_deposit_id' => $cashDepositId,
                'discount_amount' => $request->discount_amount ?? 0,
                'withholding_tax_rate' => $request->withholding_tax_enabled ? ($request->withholding_tax_rate ?? 5) : 0,
                'withholding_tax_type' => $request->withholding_tax_enabled ? ($request->withholding_tax_type ?? 'percentage') : 'percentage',
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'updated_by' => auth()->id(),
            ];

            // Handle attachment upload
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($cashSale->attachment && \Storage::disk('public')->exists($cashSale->attachment)) {
                    \Storage::disk('public')->delete($cashSale->attachment);
                }
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $updateData['attachment'] = $file->storeAs('cash-sale-attachments', $fileName, 'public');
            }

            $cashSale->update($updateData);

            // Get old items and create a map of original quantities by item_id
            // This is needed to add back original quantities when checking stock availability
            $oldItems = $cashSale->items()->get();
            $originalQuantities = [];
            foreach ($oldItems as $oldItem) {
                $itemId = $oldItem->inventory_item_id;
                if (!isset($originalQuantities[$itemId])) {
                    $originalQuantities[$itemId] = 0;
                }
                $originalQuantities[$itemId] += $oldItem->quantity;
            }

            // Delete existing inventory movements for this sale
            // This restores the stock that was deducted when the sale was created
            \App\Models\Inventory\Movement::where('reference_type', 'cash_sale')
                ->where('reference_id', $cashSale->id)
                ->delete();

            // Delete existing items
            $cashSale->items()->delete();

            // Validate stock BEFORE creating items (like POS sales)
            // IMPORTANT: Stock validation must happen AFTER movements are deleted so stock is restored
            $stockService = new \App\Services\InventoryStockService();
            $locationId = session('location_id');
            
            // Validate all items first to prevent partial updates
            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::queryVisibleForSession()->findOrFail($itemData['inventory_item_id']);
                
                // Skip stock validation for service items or items that don't track stock
                if ($inventoryItem &&
                    $inventoryItem->item_type !== 'service' &&
                    $inventoryItem->track_stock) {
                    // Get current stock at location
                    // After deleting movements above, stock is already restored in the database
                    // The stock service reads from movements using SQL SUM, so after deleting movements,
                    // the stock calculation should reflect the restored stock
                    $availableStock = $stockService->getItemStockAtLocation($inventoryItem->id, $locationId);
                    $requestedQuantity = $itemData['quantity'];
                    
                    // Add back the original quantity for this item if it existed in the old sale
                    // This accounts for the fact that when we check stock, the stock service might
                    // not immediately reflect the deleted movements (transaction isolation), so we
                    // manually add back what we know was restored
                    $originalQuantity = $originalQuantities[$inventoryItem->id] ?? 0;
                    $adjustedAvailableStock = $availableStock + $originalQuantity;
                    
                    // Debug logging to help diagnose issues
                    \Log::info('Cash Sale Stock Validation', [
                        'item_id' => $inventoryItem->id,
                        'item_name' => $inventoryItem->name,
                        'location_id' => $locationId,
                        'available_stock' => $availableStock,
                        'original_quantity' => $originalQuantity,
                        'adjusted_available_stock' => $adjustedAvailableStock,
                        'requested_quantity' => $requestedQuantity,
                    ]);
                    
                    // Validate that we have enough stock (same validation as POS sales)
                    // This prevents negative stock balances - we must have enough stock to cover the requested quantity
                    if ($adjustedAvailableStock < $requestedQuantity) {
                        DB::rollBack();
                        $errorMessage = "Insufficient stock for {$inventoryItem->name}. Available: {$adjustedAvailableStock}, Requested: {$requestedQuantity}";
                        
                        if ($request->ajax() || $request->expectsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $errorMessage
                            ], 422);
                        }
                        
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['error' => $errorMessage]);
                    }
                }
            }
            
            // Now create items after validation passes
            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::queryVisibleForSession()->findOrFail($itemData['inventory_item_id']);
                
                // Get stock info for item record (for display purposes)
                $availableStock = 0;
                $requestedQuantity = $itemData['quantity'];
                if ($inventoryItem->item_type !== 'service' && $inventoryItem->track_stock) {
                    $availableStock = $stockService->getItemStockAtLocation($inventoryItem->id, session('location_id'));
                }

                // Create cash sale item with only required fields
                $cashSaleItem = CashSaleItem::create([
                    'cash_sale_id' => $cashSale->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'description' => $inventoryItem->description ?? '',
                    'unit_of_measure' => $inventoryItem->unit_of_measure ?? '',
                    'quantity' => $requestedQuantity,
                    'unit_price' => $itemData['unit_price'],
                    'price_tier' => InventoryItem::normalizedPriceTier($itemData['price_tier'] ?? null),
                    'line_total' => 0, // Will be calculated by calculateLineTotal()
                    'vat_type' => $itemData['vat_type'],
                    'vat_rate' => $itemData['vat_rate'],
                    'vat_amount' => 0, // Will be calculated by calculateLineTotal()
                    'discount_type' => $itemData['discount_type'] ?? null,
                    'discount_rate' => $itemData['discount_rate'] ?? 0,
                    'discount_amount' => 0, // Will be calculated by calculateLineTotal()
                    'available_stock' => $availableStock,
                    'reserved_stock' => 0, // Default value since this field doesn't exist in InventoryItem
                    'stock_available' => $availableStock >= $requestedQuantity,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $cashSaleItem->calculateLineTotal();
                $cashSaleItem->save();
            }

            // Update inventory movements using model method
            $cashSale->updateInventory();
            
            // Final validation: Check stock after inventory update to ensure no negative balances
            // This is a safety check to catch any edge cases
            $cashSale->refresh();
            $cashSale->load('items');
            foreach ($cashSale->items as $item) {
                if ($item->inventoryItem && 
                    $item->inventoryItem->item_type !== 'service' && 
                    $item->inventoryItem->track_stock) {
                    $finalStock = $stockService->getItemStockAtLocation($item->inventory_item_id, $locationId);
                    if ($finalStock < 0) {
                        DB::rollBack();
                        $errorMessage = "Stock validation failed: {$item->inventoryItem->name} would have negative balance ({$finalStock}). This should not happen.";
                        
                        if ($request->ajax() || $request->expectsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $errorMessage
                            ], 422);
                        }
                        
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['error' => $errorMessage]);
                    }
                }
            }

            // Update totals
            $cashSale->updateTotals();
            
            // Validate payment balance to prevent negative balances
            // For cash_deposit payment method, ensure sufficient balance
            if ($cashSale->payment_method === 'cash_deposit') {
                if ($cashSale->cash_deposit_id) {
                    // Check specific cash deposit balance
                    $cashDeposit = \App\Models\CashDeposit::find($cashSale->cash_deposit_id);
                    if ($cashDeposit && $cashDeposit->amount < $cashSale->total_amount) {
                        DB::rollBack();
                        $errorMessage = "Insufficient cash deposit balance. Available: " . number_format($cashDeposit->amount, 2) . ", Required: " . number_format($cashSale->total_amount, 2);
                        
                        if ($request->ajax() || $request->expectsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $errorMessage
                            ], 422);
                        }
                        
                        return redirect()->back()
                            ->withInput()
                            ->withErrors(['error' => $errorMessage]);
                    }
                } else {
                    // Check customer's total cash deposit balance
                    $customer = Customer::find($cashSale->customer_id);
                    if ($customer) {
                        $totalDepositBalance = $customer->cashDeposits()->sum('amount');
                        if ($totalDepositBalance < $cashSale->total_amount) {
                            DB::rollBack();
                            $errorMessage = "Insufficient customer balance. Available: " . number_format($totalDepositBalance, 2) . ", Required: " . number_format($cashSale->total_amount, 2);
                            
                            if ($request->ajax() || $request->expectsJson()) {
                                return response()->json([
                                    'success' => false,
                                    'message' => $errorMessage
                                ], 422);
                            }
                            
                            return redirect()->back()
                                ->withInput()
                                ->withErrors(['error' => $errorMessage]);
                        }
                    }
                }
            }

            // Delete existing GL transactions
            $cashSale->glTransactions()->delete();

            // Create new GL transactions
            $cashSale->createDoubleEntryTransactions();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cash sale updated successfully!',
                    'redirect_url' => route('sales.cash-sales.show', $cashSale->encoded_id)
                ]);
            }

            return redirect()->route('sales.cash-sales.show', $cashSale->encoded_id)
                ->with('success', 'Cash sale updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update cash sale: ' . $e->getMessage()
                ], 422);
            }

            return redirect()->back()
                ->with('error', 'Failed to update cash sale: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($encodedId)
    {
        $cashSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$cashSaleId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid cash sale ID'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $cashSale = CashSale::findOrFail($cashSaleId);

            // Delete inventory movements for this cash sale (movement-based stock)
            InventoryMovement::where('reference_type', 'cash_sale')
                ->where('reference_id', $cashSale->id)
                ->delete();

            $cashSale->glTransactions()->delete();
            $cashSale->items()->delete();
            $cashSale->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cash sale deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cash sale: ' . $e->getMessage()
            ], 422);
        }
    }

    public function getInventoryItem($id)
    {
        $itemId = Hashids::decode($id)[0] ?? null;
        
        if (!$itemId) {
            return response()->json(['error' => 'Invalid item ID'], 422);
        }

        $item = InventoryItem::queryVisibleForSession()->findOrFail($itemId);
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        $locationId = session('location_id');

        return response()->json([
            'id' => $item->encoded_id,
            'name' => $item->name,
            'item_code' => $item->code,
            'description' => $item->description,
            'unit_of_measure' => $item->unit_of_measure,
            'unit_price' => $item->getUnitPriceForBranchOrLocation($branchId, $locationId),
            'available_stock' => $item->available_stock,
            'vat_rate' => $item->vat_rate ?? 18.00,
            'vat_type' => $item->vat_type ?? 'inclusive',
        ]);
    }

    public function print($encodedId)
    {
        $cashSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$cashSaleId) {
            return redirect()->route('cash-sales.index')
                ->with('error', 'Invalid cash sale ID');
        }

        $cashSale = CashSale::with([
            'customer', 
            'items.inventoryItem', 
            'branch', 
            'company', 
            'createdBy',
            'bankAccount',
            'cashDeposit.type'
        ])->findOrFail($cashSaleId);

        return view('sales.cash-sales.print', compact('cashSale'));
    }

    public function getCustomerCashDeposits($customerId)
    {
        $customerId = Hashids::decode($customerId)[0] ?? null;
        
        if (!$customerId) {
            return response()->json(['error' => 'Invalid customer ID'], 422);
        }

        $customer = Customer::findOrFail($customerId);
        
        // Check if customer has any actual cash deposit records
        $hasCashDeposits = $customer->cashDeposits()->exists() || $customer->cashCollaterals()->exists();
        
        // Get customer's cash deposit balance (only actual cash deposits available for spending)
        $availableCashDeposits = $customer->cash_deposit_balance ?? 0;
        
        // Only return account option if customer has actual deposit records OR has a positive balance
        // If customer has never had any deposits, return empty array
        $data = [];
        if ($hasCashDeposits || $availableCashDeposits > 0) {
        $data = [
            [
                'id' => 'customer_balance',
                'balance_text' => "Available Cash Deposits: " . number_format($availableCashDeposits, 2) . " TSh (Customer: {$customer->name})"
            ]
        ];
        }
        
        return response()->json(['data' => $data]);
    }
} 