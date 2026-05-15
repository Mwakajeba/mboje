<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\PosSale;
use App\Models\Sales\PosSaleItem;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\BankAccount;
use App\Models\Inventory\Movement;
use App\Models\Inventory\Item;
use App\Models\SystemSetting;
use App\Services\FxTransactionRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class PosSaleController extends Controller
{
    /**
     * Display POS interface
     */
    public function index()
    {
        // Get items with stock > 0 at current location using InventoryStockService
        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        
        // If no location is set, try to set default location
        if (!$loginLocationId) {
            $user = auth()->user();
            $defaultLocation = $user->defaultLocation()->first();
            
            if ($defaultLocation) {
                session(['location_id' => $defaultLocation->id, 'branch_id' => $defaultLocation->branch_id]);
                $loginLocationId = $defaultLocation->id;
            } else {
                $firstLocation = $user->locations()->first();
                if ($firstLocation) {
                    session(['location_id' => $firstLocation->id, 'branch_id' => $firstLocation->branch_id]);
                    $loginLocationId = $firstLocation->id;
                }
            }
        }
        
        $inventoryItems = $stockService->getAvailableItemsForSales(auth()->user()->company_id, $loginLocationId);
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        // Create a virtual walk-in customer object (not saved to database)
        $walkInCustomer = (object) [
            'id' => 0, // Special ID for walk-in customer
            'name' => 'Walk-in Customer',
            'description' => 'Walk-in Sales Customer',
            'phone1' => 'N/A',
            'workAddress' => 'Walk-in Sales',
            'category' => 'retail',
        ];

        // Fetch bank accounts for payment method selection, respecting branch scope
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

        // Get all categories for the filter dropdown
        $categories = \App\Models\Inventory\Category::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // POS default: start with No VAT unless user chooses otherwise
        $defaultVatRate = 0.00;
        $defaultVatType = 'no_vat';

        return view('sales.pos.index', compact('inventoryItems', 'walkInCustomer', 'bankAccounts', 'categories', 'defaultVatRate', 'defaultVatType'));
    }

    /**
     * Get inventory item details for POS
     */
    public function getItemDetails(Request $request)
    {
        $itemId = $request->input('item_id');
        $item = InventoryItem::queryVisibleForSession()->find($itemId);

        if (! $item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        // For service items, don't check stock - return 0 or null
        $currentStock = 0;
        if ($item->item_type !== 'service' && $item->track_stock) {
            $stockService = new \App\Services\InventoryStockService();
            $currentStock = $stockService->getItemStockAtLocation($item->id, session('location_id'));
        }

        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        $locationId = session('location_id');

        Item::withResolvedPricesForContext(collect([$item]), $branchId, $locationId);

        return response()->json([
            'id' => $item->id,
            'name' => $item->name,
            'code' => $item->code,
            'description' => $item->description,
            'unit_of_measure' => $item->unit_of_measure,
            'unit_price' => $item->getUnitPriceForBranchOrLocation($branchId, $locationId),
            'has_wholesale' => (bool) $item->has_wholesale,
            'wholesale_unit_price' => $item->has_wholesale
                ? (float) ($item->resolved_wholesale_unit_price ?? $item->getWholesaleUnitPriceForBranchOrLocation($branchId, $locationId))
                : null,
            'current_stock' => $currentStock,
            'cost_price' => $item->getCostPriceForBranchOrLocation($branchId, $locationId),
            'vat_rate' => $item->vat_rate ?? 18.00,
            'vat_type' => $item->vat_type ?? 'inclusive',
        ]);
    }

    /**
     * Store a new POS sale
     */
    public function store(Request $request)
    {
        // Check for branch ID first
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a branch before creating POS sale.'
            ], 400);
        }

        $request->validate([
            'customer_id' => 'required|integer',
            'payment_method' => 'required|in:bank',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'bank_amount' => 'required|numeric|min:0.01',
            'sale_date' => 'required|date',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'discount_type' => 'nullable|in:none,percentage,fixed',
            'discount_rate' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:inclusive,exclusive,no_vat',
            'items.*.vat_rate' => 'required|numeric|min:0',
            'items.*.price_tier' => 'nullable|in:retail,wholesale',
            'withholding_tax_type' => 'nullable|in:percentage,fixed',
            'withholding_tax_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $tierErrors = InventoryItem::priceTierValidationErrors($request);
        if ($tierErrors !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $tierErrors,
            ], 422);
        }
        
        // Additional validation: if discount_type is percentage or fixed, discount_rate must be greater than 0
        if (in_array($request->discount_type, ['percentage', 'fixed']) && ($request->discount_rate == 0 || $request->discount_rate == null)) {
            return response()->json([
                'success' => false,
                'message' => 'Discount rate is required when discount type is ' . $request->discount_type
            ], 422);
        }

        DB::beginTransaction();

        try {
            $stockService = new \App\Services\InventoryStockService();
            
            // Handle walk-in customer (customer_id = 0) - no database insertion
            $customerId = $request->customer_id;
            $customerName = null;
            
            if ($customerId == 0) {
                // For walk-in customers, set customer_id to null and use customer_name
                $customerId = null;
                $customerName = 'Walk-in Customer';
            }

            // Get functional currency
            $functionalCurrency = SystemSetting::getValue('functional_currency', Auth::user()->company->functional_currency ?? 'TZS');
            $saleCurrency = $request->currency ?? $functionalCurrency;
            $companyId = Auth::user()->company_id;

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
            
            // Create POS sale
            $posSale = PosSale::create([
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'sale_date' => $request->sale_date,
                'terminal_id' => $request->terminal_id ?? 'POS-' . Auth::id(),
                'operator_id' => Auth::id(),
                'payment_method' => 'bank',
                'cash_amount' => 0,
                'card_amount' => 0,
                'mobile_money_amount' => 0,
                'bank_amount' => $request->bank_amount,
                'bank_account_id' => $request->bank_account_id,
                'currency' => $saleCurrency,
                'exchange_rate' => $exchangeRate,
                'fx_rate_used' => $fxRateUsed,
                'vat_rate' => $request->vat_rate ?? 18.00,
                'vat_type' => $request->vat_type ?? 'inclusive',
                'discount_type' => $request->discount_type ?? 'none',
                'discount_rate' => $request->discount_rate ?? 0,
                'withholding_tax_type' => $request->withholding_tax_type ?? 'percentage',
                'withholding_tax_rate' => $request->withholding_tax_rate ?? 0,
                'notes' => $request->notes,
                'branch_id' => $branchId,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
            ]);

            // Create POS sale items
            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::queryVisibleForSession()->find($itemData['inventory_item_id']);
                $requestedQuantity = $itemData['quantity'];
                
                // Skip stock validation for service items or items that don't track stock
                if ($inventoryItem &&
                    $inventoryItem->item_type !== 'service' &&
                    $inventoryItem->track_stock) {
                    $availableStock = $stockService->getItemStockAtLocation($inventoryItem->id, session('location_id'));

                    // Check stock availability
                    if ($availableStock < $requestedQuantity) {
                        throw new \Exception("Insufficient stock for {$inventoryItem->name}. Available: {$availableStock}, Requested: {$requestedQuantity}");
                    }
                }

                // Create POS sale item
                // Note: expiry_date and batch_number will be set in updateInventory() method
                // after stock is consumed using FEFO
                $posSaleItem = PosSaleItem::create([
                    'pos_sale_id' => $posSale->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'item_name' => $inventoryItem->name,
                    'quantity' => $requestedQuantity,
                    'unit_price' => $itemData['unit_price'],
                    'price_tier' => InventoryItem::normalizedPriceTier($itemData['price_tier'] ?? null),
                    'vat_type' => $itemData['vat_type'],
                    'vat_rate' => $itemData['vat_rate'],
                    'vat_amount' => 0, // Will be calculated
                    'discount_type' => 'none', // No item-level discount
                    'discount_rate' => 0,
                    'discount_amount' => 0,
                    'line_total' => 0, // Will be calculated
                ]);

                $posSaleItem->calculateLineTotal();
                $posSaleItem->save();
            }

            // Refresh the model to ensure items relationship is loaded
            $posSale->refresh();
            $posSale->load('items');
            
            $posSale->updateTotals();
            // Create inventory movements for creation flow
            $posSale->updateInventory();
            $posSale->processPayment();
            $posSale->createDoubleEntryTransactions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'POS sale completed successfully!',
                'pos_sale_id' => $posSale->id,
                'pos_number' => $posSale->pos_number,
                'redirect_url' => route('sales.pos.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete POS sale: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display POS sale details
     */
    public function show($encodedId)
    {
        $posSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$posSaleId) {
            return redirect()->route('sales.pos.index')
                ->with('error', 'Invalid POS sale ID');
        }

        $posSale = PosSale::with([
            'customer', 
            'items.inventoryItem', 
            'branch', 
            'company', 
            'createdBy', 
            'updatedBy',
            'operator',
            'glTransactions.chartAccount',
            'bankAccount'
        ])->findOrFail($posSaleId);

        return view('sales.pos.show', compact('posSale'));
    }

    /**
     * Edit POS sale
     */
    public function edit($encodedId)
    {
        $posSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$posSaleId) {
            return redirect()->route('sales.pos.index')
                ->with('error', 'Invalid POS sale ID');
        }

        $posSale = PosSale::with(['items.inventoryItem', 'customer', 'operator', 'branch'])
            ->findOrFail($posSaleId);

        // Check if sale can be edited (within time limit)
        // Admins and users with 'edit all pos sales' permission can bypass this restriction
        $canBypassRestriction = auth()->user()->hasRole('admin') || 
                               auth()->user()->can('edit all pos sales') ||
                               auth()->user()->can('bypass pos time restrictions');
        
        if (!$canBypassRestriction) {
            // Get configurable time limit from system settings (default 24 hours)
            // Set to 0 to disable the restriction entirely
            $editTimeLimitHours = SystemSetting::getValue('pos_edit_time_limit_hours', 24);
            
            if ($editTimeLimitHours > 0) {
                $saleDate = $posSale->sale_date ?? $posSale->created_at;
                if ($saleDate->diffInHours(now()) > $editTimeLimitHours) {
            return redirect()->route('sales.pos.show', $encodedId)
                        ->with('error', "POS sales cannot be edited after {$editTimeLimitHours} hours from the sale date. Please contact an administrator if you need to edit this sale.");
                }
            }
        }

        // Get items with stock > 0 at current location using InventoryStockService
        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        $inventoryItems = $stockService->getAvailableItemsForSales(auth()->user()->company_id, $loginLocationId);
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        // Create a map of original quantities by item_id for frontend validation
        $originalQuantities = [];
        foreach ($posSale->items as $item) {
            $itemId = $item->inventory_item_id;
            if (!isset($originalQuantities[$itemId])) {
                $originalQuantities[$itemId] = 0;
            }
            $originalQuantities[$itemId] += $item->quantity;
        }

        // Fetch bank accounts for payment method selection, respecting branch scope
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

        return view('sales.pos.edit', compact('posSale', 'inventoryItems', 'bankAccounts', 'originalQuantities'));
    }

    /**
     * Update POS sale
     */
    public function update(Request $request, $encodedId)
    {
        $posSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$posSaleId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid POS sale ID'
            ], 404);
        }

        $posSale = PosSale::findOrFail($posSaleId);

        // Check if sale can be edited (within time limit)
        // Admins and users with 'edit all pos sales' permission can bypass this restriction
        $canBypassRestriction = auth()->user()->hasRole('admin') || 
                               auth()->user()->can('edit all pos sales') ||
                               auth()->user()->can('bypass pos time restrictions');
        
        if (!$canBypassRestriction) {
            // Get configurable time limit from system settings (default 24 hours)
            // Set to 0 to disable the restriction entirely
            $editTimeLimitHours = SystemSetting::getValue('pos_edit_time_limit_hours', 24);
            
            if ($editTimeLimitHours > 0) {
                $saleDate = $posSale->sale_date ?? $posSale->created_at;
                if ($saleDate->diffInHours(now()) > $editTimeLimitHours) {
            return response()->json([
                'success' => false,
                        'message' => "POS sales cannot be edited after {$editTimeLimitHours} hours from the sale date. Please contact an administrator if you need to edit this sale."
            ], 422);
                }
            }
        }

        $request->validate([
            'sale_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'bank_amount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:none,percentage,fixed',
            'discount_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0',
            'items.*.vat_type' => 'required|in:inclusive,exclusive,no_vat',
            'items.*.price_tier' => 'nullable|in:retail,wholesale',
        ]);

        $tierErrors = InventoryItem::priceTierValidationErrors($request);
        if ($tierErrors !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $tierErrors,
            ], 422);
        }
        
        // Additional validation: if discount_type is percentage or fixed, discount_rate must be greater than 0
        if (in_array($request->discount_type, ['percentage', 'fixed']) && ($request->discount_rate == 0 || $request->discount_rate == null)) {
            return response()->json([
                'success' => false,
                'message' => 'Discount rate is required when discount type is ' . $request->discount_type
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update POS sale
            $posSale->update([
                'sale_date' => $request->sale_date,
                'customer_id' => $request->customer_id,
                'customer_name' => $request->customer_name,
                'payment_method' => 'bank', // Always bank for POS sales
                'bank_account_id' => $request->bank_account_id,
                'bank_amount' => $request->bank_amount,
                'discount_type' => $request->discount_type,
                'discount_rate' => $request->discount_rate ?? 0,
                'notes' => $request->notes,
            ]);

            // Stock is now tracked via movements, no need to restore directly

            // Get old items and create a map of original quantities by item_id
            // This is needed to add back original quantities when checking stock availability
            $oldItems = $posSale->items()->with('inventoryItem')->get();
            $originalQuantities = [];
            foreach ($oldItems as $oldItem) {
                $itemId = $oldItem->inventory_item_id;
                if (!isset($originalQuantities[$itemId])) {
                    $originalQuantities[$itemId] = 0;
                }
                $originalQuantities[$itemId] += $oldItem->quantity;
            }

            // Restore expiry tracking for old items before deleting them
            foreach ($oldItems as $oldItem) {
                if ($oldItem->inventoryItem && 
                    $oldItem->inventoryItem->track_expiry && 
                    $oldItem->expiry_date && 
                    $oldItem->batch_number && 
                    session('location_id')) {
                    
                    // Restore expiry tracking by adding back the consumed quantity
                    // We'll restore to layers matching the batch number and expiry date
                    $expiryService = new \App\Services\ExpiryStockService();
                    
                    // Find matching expiry tracking layers (may be empty if layers were fully consumed)
                    $matchingLayers = \App\Models\Inventory\ExpiryTracking::forItem($oldItem->inventory_item_id)
                        ->forLocation(session('location_id'))
                        ->where('batch_number', $oldItem->batch_number)
                        ->whereDate('expiry_date', $oldItem->expiry_date)
                        ->get();
                    
                    // Restore quantity to matching layers if they exist
                    $quantityToRestore = $oldItem->quantity;
                    if ($matchingLayers->isNotEmpty()) {
                        // Restore to the first matching layer
                        $firstLayer = $matchingLayers->first();
                        $firstLayer->add($quantityToRestore, $firstLayer->unit_cost);
                    } else {
                        // No matching layers found, create a new layer with the restored stock
                        // Use resolved cost price (location → branch → default)
                        $restoreCost = $oldItem->inventoryItem 
                            ? $oldItem->inventoryItem->getCostPriceForBranchOrLocation($posSale->branch_id, session('location_id')) 
                            : 0;
                        $expiryService->addStock(
                            $oldItem->inventory_item_id,
                            session('location_id'),
                            $quantityToRestore,
                            $restoreCost,
                            $oldItem->expiry_date,
                            'pos_sale_restore',
                            $posSale->id,
                            $oldItem->batch_number,
                            $posSale->pos_number
                        );
                    }
                }
            }

            // Delete existing inventory movements for this sale
            // This restores the stock that was deducted when the sale was created
            Movement::where('reference_type', 'pos_sale')
                ->where('reference_id', $posSale->id)
                ->delete();

            // Delete existing items
            $posSale->items()->delete();

            // Add new items
            $stockService = new \App\Services\InventoryStockService();
            foreach ($request->items as $itemData) {
                // Get inventory item details
                $inventoryItem = InventoryItem::queryVisibleForSession()->find($itemData['item_id']);
                
                if (!$inventoryItem) {
                    throw new \Exception("Inventory item not found: " . $itemData['item_id']);
                }
                
                // Skip stock validation for service items or items that don't track stock
                if ($inventoryItem &&
                    $inventoryItem->item_type !== 'service' &&
                    $inventoryItem->track_stock) {
                    $availableStock = $stockService->getItemStockAtLocation($inventoryItem->id, session('location_id'));
                    $requestedQuantity = $itemData['quantity'];
                    
                    // Add back the original quantity for this item if it existed in the old sale
                    // This accounts for the fact that the original sale already deducted stock
                    $originalQuantity = $originalQuantities[$inventoryItem->id] ?? 0;
                    $adjustedAvailableStock = $availableStock + $originalQuantity;
                    
                    if ($adjustedAvailableStock < $requestedQuantity) {
                        throw new \Exception("Insufficient stock for {$inventoryItem->name}. Available: {$adjustedAvailableStock}, Requested: {$requestedQuantity}");
                    }
                }

                // Calculate line total before creating
                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $vatRate = $itemData['vat_rate'] ?? 0;
                // Normalize VAT type to handle case variations
                $vatType = strtolower(trim($itemData['vat_type'] ?? 'no_vat'));
                
                $baseAmount = $quantity * $unitPrice;
                
                // Calculate VAT
                $vatAmount = 0;
                if ($vatType === 'inclusive') {
                    $vatAmount = $baseAmount * ($vatRate / (100 + $vatRate));
                } elseif ($vatType === 'exclusive') {
                    $vatAmount = $baseAmount * ($vatRate / 100);
                }
                
                // Calculate line total
                $lineTotal = 0;
                if ($vatType === 'inclusive') {
                    $lineTotal = $baseAmount; // For inclusive, line total is baseAmount (includes VAT)
                } else {
                    $lineTotal = $baseAmount + $vatAmount; // For exclusive, add VAT to base
                }

                // Create POS sale item
                $posSaleItem = $posSale->items()->create([
                    'inventory_item_id' => $itemData['item_id'],
                    'item_name' => $inventoryItem->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'price_tier' => InventoryItem::normalizedPriceTier($itemData['price_tier'] ?? null),
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'vat_type' => $vatType,
                    'discount_type' => 'none',
                    'discount_rate' => 0,
                    'discount_amount' => 0,
                    'line_total' => $lineTotal,
                ]);
            }

            // Update inventory movements using model method
            $posSale->updateInventory();

            // Refresh the model to ensure items relationship is loaded
            $posSale->refresh();
            $posSale->load('items');
            
            // Update totals
            $posSale->updateTotals();

            // Delete existing GL transactions
            $posSale->glTransactions()->delete();

            // Create new GL transactions
            $posSale->createDoubleEntryTransactions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'POS sale updated successfully',
                'redirect_url' => route('sales.pos.show', $encodedId)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update POS sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print POS receipt
     */
    public function printReceipt($encodedId)
    {
        $posSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$posSaleId) {
            return redirect()->route('sales.pos.index')
                ->with('error', 'Invalid POS sale ID');
        }

        $posSale = PosSale::with([
            'customer', 
            'items.inventoryItem', 
            'branch', 
            'company', 
            'operator'
        ])->findOrFail($posSaleId);

        // Mark as printed
        $posSale->update(['receipt_printed' => true]);

        return view('sales.pos.receipt', compact('posSale'));
    }

    /**
     * Get POS sales list
     */
    public function list(Request $request)
    {
        // Check if this is a DataTable request
        if ($request->ajax()) {
            $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
            $query = PosSale::with(['customer', 'operator', 'branch', 'items'])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('sale_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('sale_date', '<=', $request->end_date);
            }

            // Filter by operator
            if ($request->filled('operator_id')) {
                $query->where('operator_id', $request->operator_id);
            }

            // Filter by payment method
            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Handle search
            if ($request->filled('search.value')) {
                $searchValue = $request->input('search.value');
                $query->where(function($q) use ($searchValue) {
                    $q->where('pos_number', 'like', "%{$searchValue}%")
                      ->orWhereHas('customer', function($customerQuery) use ($searchValue) {
                          $customerQuery->where('name', 'like', "%{$searchValue}%");
                      })
                      ->orWhere('customer_name', 'like', "%{$searchValue}%");
                });
            }

            // Handle ordering
            $orderColumn = $request->input('order.0.column', 2); // Default to date column
            $orderDir = $request->input('order.0.dir', 'desc');
            
            $columns = ['id', 'pos_number', 'sale_date', 'customer_name', 'items_count', 'total_amount', 'payment_method', 'operator_name', 'expiry_date', 'status'];
            $orderBy = $columns[$orderColumn] ?? 'sale_date';
            
            $query->orderBy($orderBy, $orderDir);

            // Get total count before pagination
            $totalRecords = $query->count();
            $filteredRecords = $totalRecords;

            // Pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            
            $posSales = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($posSales as $index => $posSale) {
                // Collect expiry dates from items
                $expiryDates = [];
                foreach ($posSale->items as $item) {
                    if ($item->expiry_date) {
                        $expiryDates[] = $item->expiry_date;
                    }
                }
                
                // Format expiry dates display
                $expiryDateDisplay = '-';
                if (!empty($expiryDates)) {
                    if (count($expiryDates) == 1) {
                        $expiryDateDisplay = '<span class="badge bg-info"><i class="bx bx-calendar me-1"></i>' . $expiryDates[0]->format('M d, Y') . '</span>';
                    } else {
                        // Show count and earliest date
                        $earliestDate = collect($expiryDates)->min();
                        $expiryDateDisplay = '<span class="badge bg-info" title="' . count($expiryDates) . ' items with expiry dates"><i class="bx bx-calendar me-1"></i>' . $earliestDate->format('M d, Y') . ' (' . count($expiryDates) . ')</span>';
                    }
                }
                
                $data[] = [
                    'sale_number' => '<a href="' . route('sales.pos.show', $posSale->encoded_id) . '" class="text-primary fw-bold">' . $posSale->pos_number . '</a>',
                    'customer_name' => $posSale->customer ? $posSale->customer->name : ($posSale->customer_name ?: 'Walk-in Customer'),
                    'sale_date_formatted' => format_datetime($posSale->sale_date, 'M d, Y h:i A'),
                    'items_count' => $posSale->items->count() . ' items',
                    'total_amount_formatted' => 'TZS ' . number_format($posSale->total_amount, 2),
                    'payment_method_text' => '<span class="badge bg-' . ($posSale->payment_method == 'cash' ? 'success' : ($posSale->payment_method == 'card' ? 'info' : ($posSale->payment_method == 'bank' ? 'primary' : 'warning'))) . '">' . ucfirst(str_replace('_', ' ', $posSale->payment_method)) . '</span>',
                    'operator_name' => $posSale->operator ? $posSale->operator->name : 'N/A',
                    'expiry_date' => $expiryDateDisplay,
                    'actions' => '<div class="d-flex gap-1">' .
                        '<a href="' . route('sales.pos.show', $posSale->encoded_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>' .
                        '<a href="' . route('sales.pos.receipt', $posSale->encoded_id) . '" class="btn btn-sm btn-outline-secondary" title="Print Receipt" target="_blank"><i class="bx bx-printer"></i></a>' .
                        '<a href="' . route('sales.pos.edit', $posSale->encoded_id) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bx bx-edit"></i></a>' .
                        '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePosSale(\'' . $posSale->encoded_id . '\')" title="Delete"><i class="bx bx-trash"></i></button>' .
                        '</div>'
                ];
            }

            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }

        // Regular view request
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        
        // Calculate statistics
        $baseQuery = PosSale::where('company_id', Auth::user()->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));
        
        $totalSales = (clone $baseQuery)->count();
        $totalAmount = (clone $baseQuery)->sum('total_amount');
        $todaySales = (clone $baseQuery)->whereDate('sale_date', today())->count();
        $todayAmount = (clone $baseQuery)->whereDate('sale_date', today())->sum('total_amount');
        $thisMonthSales = (clone $baseQuery)->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)->count();
        $thisMonthAmount = (clone $baseQuery)->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)->sum('total_amount');
        $totalDiscount = (clone $baseQuery)->sum('discount_amount');
        $totalVat = (clone $baseQuery)->sum('vat_amount');

        $query = PosSale::with(['customer', 'operator', 'branch'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }

        // Filter by operator
        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->operator_id);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $posSales = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('sales.pos.list', compact(
            'posSales',
            'totalSales',
            'totalAmount',
            'todaySales',
            'todayAmount',
            'thisMonthSales',
            'thisMonthAmount',
            'totalDiscount',
            'totalVat'
        ));
    }

    /**
     * Get POS sales statistics
     */
    public function statistics(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->get('date', today());

        $stats = [
            'total_sales' => PosSale::where('branch_id', $branchId)
                ->whereDate('sale_date', $date)
                ->count(),
            'total_amount' => PosSale::where('branch_id', $branchId)
                ->whereDate('sale_date', $date)
                ->sum('total_amount'),
            'cash_amount' => PosSale::where('branch_id', $branchId)
                ->whereDate('sale_date', $date)
                ->sum('cash_amount'),
            'card_amount' => PosSale::where('branch_id', $branchId)
                ->whereDate('sale_date', $date)
                ->sum('card_amount'),
            'mobile_money_amount' => PosSale::where('branch_id', $branchId)
                ->whereDate('sale_date', $date)
                ->sum('mobile_money_amount'),
            'vat_amount' => PosSale::where('branch_id', $branchId)
                ->whereDate('sale_date', $date)
                ->sum('vat_amount'),
            'discount_amount' => PosSale::where('branch_id', $branchId)
                ->whereDate('sale_date', $date)
                ->sum('discount_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Void/Cancel POS sale
     */
    public function void($encodedId)
    {
        $posSaleId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$posSaleId) {
            return response()->json(['error' => 'Invalid POS sale ID'], 404);
        }

        $posSale = PosSale::findOrFail($posSaleId);

        // Check if sale can be voided (within time limit, not already voided, etc.)
        // Admins and users with 'edit all pos sales' permission can bypass this restriction
        $canBypassRestriction = auth()->user()->hasRole('admin') || 
                               auth()->user()->can('edit all pos sales') ||
                               auth()->user()->can('bypass pos time restrictions');
        
        if (!$canBypassRestriction) {
            // Get configurable time limit from system settings (default 24 hours)
            // Set to 0 to disable the restriction entirely
            $editTimeLimitHours = SystemSetting::getValue('pos_edit_time_limit_hours', 24);
            
            if ($editTimeLimitHours > 0) {
                $saleDate = $posSale->sale_date ?? $posSale->created_at;
                if ($saleDate->diffInHours(now()) > $editTimeLimitHours) {
                    return response()->json([
                        'error' => "Sale cannot be voided after {$editTimeLimitHours} hours from the sale date. Please contact an administrator if you need to void this sale."
                    ], 422);
                }
            }
        }

        DB::beginTransaction();

        try {
            // Restore cost layers before deleting movements (reverse the cost consumption)
            $costService = new \App\Services\InventoryCostService();
            $oldMovements = Movement::where('reference_type', 'pos_sale')
                ->where('reference_id', $posSale->id)
                ->get();
            
            foreach ($oldMovements as $oldMovement) {
                // Restore the cost layers that were consumed for this movement
                $costService->restoreInventoryCostLayers(
                    $oldMovement->item_id,
                    $oldMovement->quantity,
                    $oldMovement->unit_cost,
                    'POS Sale Void: ' . $posSale->pos_number
                );
            }

            // Delete inventory movements for this POS sale (movement-based stock)
            // Since stock is calculated from movements, deleting the "sold" movements automatically restores stock
            // No need to create reversal movements - just delete the original movements like cash sales do
            Movement::where('reference_type', 'pos_sale')
                ->where('reference_id', $posSale->id)
                ->delete();

            // Delete GL transactions
            $posSale->glTransactions()->delete();

            // Delete the POS sale
            $posSale->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'POS sale voided successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to void POS sale: ' . $e->getMessage()
            ], 422);
        }
    }
}
