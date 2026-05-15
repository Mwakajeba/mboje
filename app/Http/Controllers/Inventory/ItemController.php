<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Vinkla\Hashids\Facades\Hashids;
use App\Jobs\ImportInventoryItems;

use App\Models\Inventory\Item;
use App\Models\Inventory\Category;
use App\Models\Inventory\Movement;
use App\Models\Inventory\ImportBatch;
use App\Services\InventoryStockService;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Sales\SalesOrderItem;
use App\Models\Sales\DeliveryItem;
use App\Models\Sales\PosSaleItem;
use App\Models\Sales\CashSaleItem;
use App\Models\Sales\CreditNoteItem;
use App\Models\Purchase\PurchaseInvoiceItem;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\PurchaseQuotationItem;
use App\Models\Purchase\GoodsReceiptItem;
use App\Models\Purchase\DebitNoteItem;
use App\Models\Production\ItemBatch;
use App\Models\InventoryLocation;
use App\Models\Branch;
use App\Models\Inventory\ItemBranchPrice;
use App\Models\Inventory\ItemLocationPrice;
use Illuminate\Validation\Rule;
use App\Models\ChartAccount;
use App\Models\GlTransaction;
use App\Models\AccountClass;
use App\Models\AccountClassGroup;
use App\Services\InventoryCostService;
use App\Models\InventoryCostLayer;
use App\Models\Inventory\OpeningBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Yajra\DataTables\Facades\DataTables;

class ItemController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Item::class);
        
        // Auto-set session location if not set
        if (!session('location_id')) {
            $user = Auth::user();
            $defaultLocation = $user->defaultLocation()->first();
            
            if ($defaultLocation) {
                session(['location_id' => $defaultLocation->id, 'branch_id' => $defaultLocation->branch_id]);
            } else {
                $firstLocation = $user->locations()->first();
                if ($firstLocation) {
                    session(['location_id' => $firstLocation->id, 'branch_id' => $firstLocation->branch_id]);
                }
            }
        }

        $sessionBranchId = session('branch_id') ? (int) session('branch_id') : null;
        
        if ($request->ajax()) {
            $items = Item::with(['category', 'stockLevels', 'branchPrices', 'locationPrices', 'visibilityBranches'])
                ->where('company_id', Auth::user()->company_id)
                ->visibleInSessionBranch($sessionBranchId)
                ->select('inventory_items.*');

            // Get current session branch/location for price resolution
            $sessionLocationId = session('location_id');

            return DataTables::of($items)
                ->addColumn('category_name', function ($item) {
                    return $item->category_name;
                })
                ->addColumn('expiry_tracking_badge', function ($item) {
                    if ($item->track_expiry) {
                        return '<span class="badge bg-success">Tracks Expiry</span>';
                    } else {
                        return '<span class="badge bg-secondary">No Expiry</span>';
                    }
                })
                ->addColumn('status_badge', function ($item) {
                    return $item->status_badge;
                })
                ->addColumn('branches_scope', function ($item) {
                    if ($item->visibilityBranches->isEmpty()) {
                        return '<span class="badge bg-info">All branches</span>';
                    }

                    return e($item->visibilityBranches->pluck('name')->implode(', '));
                })
                ->editColumn('cost_price', function ($item) use ($sessionBranchId, $sessionLocationId) {
                    // Resolve cost price: location → branch → default
                    $resolvedCost = $item->getCostPriceForBranchOrLocation($sessionBranchId, $sessionLocationId);
                    return number_format($resolvedCost, 2);
                })
                ->editColumn('unit_price', function ($item) use ($sessionBranchId, $sessionLocationId) {
                    // Resolve unit price: location → branch → default
                    $resolvedPrice = $item->getUnitPriceForBranchOrLocation($sessionBranchId, $sessionLocationId);
                    return number_format($resolvedPrice, 2);
                })
                ->editColumn('current_stock', function ($item) {
                    $stockService = new InventoryStockService();
                    $loginLocationId = session('location_id');
                    
                    if ($loginLocationId) {
                        // Get stock for specific location
                        $stock = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                    } else {
                        // Get total stock across all locations
                        $stock = $stockService->getItemTotalStock($item->id);
                    }
                    
                    return number_format($stock, 2);
                })
                ->addColumn('actions', function ($item) {
                    return $item->actions;
                })
                ->rawColumns(['expiry_tracking_badge', 'status_badge', 'branches_scope', 'actions'])
                ->make(true);
        }

        // Get categories and locations for import modal
        $categories = Category::where('company_id', Auth::user()->company_id)->get();
        $locations = InventoryLocation::where('company_id', Auth::user()->company_id)->get();

        // Summary cards for login location
        $loginLocationId = session('location_id');
        $loginLocationName = null;
        if ($loginLocationId) {
            $loginLocationName = \App\Models\InventoryLocation::where('id', $loginLocationId)->value('name');
        }
        $stockService = new InventoryStockService();
        
        if ($loginLocationId) {
            // Get stock summary for specific location
            $locationStock = $stockService->getLocationStockSummary($loginLocationId);
            $lowStockItems = $stockService->getLowStockItemsAtLocation($loginLocationId);
            $outOfStockItems = $stockService->getOutOfStockItemsAtLocation($loginLocationId);
            
            $totalItems = $locationStock->count();
            $inStock = $locationStock->where('quantity', '>', 0)->count();
            $lowStock = $lowStockItems->count();
            $outOfStock = $outOfStockItems->count();
        } else {
            // Company-level fallback - get all items and calculate totals
            $allItems = Item::where('company_id', Auth::user()->company_id)
                ->visibleInSessionBranch($sessionBranchId)
                ->get();
            $totalItems = $allItems->count();
            $inStock = 0;
            $lowStock = 0;
            $outOfStock = 0;
            
            foreach ($allItems as $item) {
                $totalStock = $stockService->getItemTotalStock($item->id);
                if ($totalStock > 0) {
                    $inStock++;
                    if ($totalStock <= ($item->reorder_level ?? 0)) {
                        $lowStock++;
                    }
                } else {
                    $outOfStock++;
                }
            }
        }

        return view('inventory.items.index', compact('categories', 'locations', 'totalItems', 'inStock', 'lowStock', 'outOfStock', 'loginLocationName'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Item::class);
        $categories = Category::where('company_id', Auth::user()->company_id)->get();
        $locations = InventoryLocation::where('company_id', Auth::user()->company_id)->get();
        
        // Get accounts for dropdowns based on account classes with company filtering
        $companyId = Auth::user()->company_id;
        
        $inventoryAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->where('name', 'Assets');
        })->get();

        $salesAccounts = ChartAccount::All();

        $costAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->where('name', 'Expenses');
        })->get();

        $vatAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Liabilities']);
        })->get();

        $withholdingTaxAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Liabilities']);
        })->get();

        $discountAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Expenses']);
        })->get();

        $withholdingTaxExpenseAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Expenses']);
        })->get();

        $purchasePayableAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Liabilities']);
        })->get();

        $discountIncomeAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Revenue', 'Income']);
        })->get();

        $prefillCategoryId = null;
        if ($request->filled('category_id')) {
            $decoded = Hashids::decode($request->input('category_id'));
            $prefillCategoryId = $decoded[0] ?? null;
        }

        $assignableBranches = Branch::where('company_id', Auth::user()->company_id)
            ->whereIn('id', Auth::user()->permittedBranchIds())
            ->active()
            ->orderBy('name')
            ->get();

        return view('inventory.items.create', compact('categories', 'locations', 'inventoryAccounts', 'salesAccounts', 'costAccounts', 'vatAccounts', 'withholdingTaxAccounts', 'withholdingTaxExpenseAccounts', 'purchasePayableAccounts', 'discountAccounts', 'discountIncomeAccounts', 'prefillCategoryId', 'assignableBranches'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Item::class);

        $allowedBranchIds = Auth::user()->permittedBranchIds();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:inventory_items,code',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'item_type' => 'required|in:product,service',
            'unit_of_measure' => 'nullable|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'has_wholesale' => 'nullable|boolean',
            'wholesale_unit_price' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () => $request->boolean('has_wholesale')),
            ],
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'track_expiry' => 'nullable|boolean',
            'has_different_sales_revenue_account' => 'nullable|boolean',
            'sales_revenue_account_id' => 'nullable|required_if:has_different_sales_revenue_account,1|exists:chart_accounts,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['integer', Rule::in($allowedBranchIds)],
        ]);

        // Opening balance via items form deprecated; handled via adjustments/opening balance section

        try {
            DB::beginTransaction();

            // Initial stock is zero; opening balances are handled in adjustments
            $initialStock = 0;

            // For service items, cost_price should always be 0
            // For product items, use provided cost_price or default to 0
            $costPrice = $request->cost_price ?? 0;
            if ($request->item_type === 'service') {
                $costPrice = 0; // Services don't have a cost price
            }

            // Calculate opening balance value automatically
            $openingBalanceValue = 0;
            if ($request->boolean('has_opening_balance') && $request->opening_balance_quantity > 0) {
                $openingBalanceValue = $request->opening_balance_quantity * $costPrice;
            }

            $hasWholesale = $request->boolean('has_wholesale');
            $wholesaleUnitPrice = $hasWholesale ? ($request->wholesale_unit_price ?? 0) : null;

            $item = Item::create([
                'company_id' => Auth::user()->company_id,
                'category_id' => $request->category_id,
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'item_type' => $request->item_type,
                'unit_of_measure' => $request->unit_of_measure,
                'cost_price' => $costPrice,
                'unit_price' => $request->unit_price,
                'has_wholesale' => $hasWholesale,
                'wholesale_unit_price' => $wholesaleUnitPrice,
                'minimum_stock' => $request->minimum_stock,
                'maximum_stock' => $request->maximum_stock,
                'reorder_level' => $request->reorder_level,
                'is_active' => $request->has('is_active'),
                'track_stock' => $request->has('track_stock'),
                'track_expiry' => $request->has('track_expiry'),
                'has_opening_balance' => false,
                'opening_balance_quantity' => 0,
                'opening_balance_value' => 0,
                'has_different_sales_revenue_account' => $request->has('has_different_sales_revenue_account'),
                'sales_revenue_account_id' => $request->has('has_different_sales_revenue_account') ? $request->sales_revenue_account_id : null,
            ]);

            if (Schema::hasTable('inventory_items_branches')) {
                $branchIds = collect($request->input('branch_ids', []))
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
                $item->visibilityBranches()->sync($branchIds);
            }

            // Opening balance transactions/movements will be created from the Opening Balance section

            DB::commit();

            return redirect()->route('inventory.items.index')
                ->with('success', 'Inventory item created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create inventory item: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $itemId = !empty($decoded) ? $decoded[0] : null;
        
        if (!$itemId) {
            return redirect()->route('inventory.items.index')
                ->with('error', 'Invalid item ID');
        }

        $item = Item::findOrFail($itemId);
        $this->authorize('view', $item);
        
        $item->load(['category', 'movements', 'stockLevels']);

        // Get cost layer information
        $costService = new InventoryCostService();
        $inventoryValue = $costService->getInventoryValue($item->id);
        
        // Get recent cost layers for display
        $costLayers = InventoryCostLayer::where('item_id', $item->id)
            ->where('remaining_quantity', '>', 0)
            ->where('is_consumed', false)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->limit(10)
            ->get();

        // Get current stock for QR code (needed for POS scanning)
        $stockService = new \App\Services\InventoryStockService();
        $currentStock = 0;
        if ($item->item_type !== 'service' && $item->track_stock) {
            $loginLocationId = session('location_id');
            if ($loginLocationId) {
                $currentStock = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
            } else {
                $currentStock = $stockService->getItemTotalStock($item->id);
            }
        }

        // Get VAT information (from item or defaults)
        $defaultVatRate = \App\Models\SystemSetting::where('key', 'inventory_default_vat_rate')->value('value') ?? 18.00;
        $defaultVatType = \App\Models\SystemSetting::where('key', 'inventory_default_vat_type')->value('value') ?? 'no_vat';
        
        $vatRate = $item->vat_rate ?? $defaultVatRate;
        $vatType = $item->vat_type ?? $defaultVatType;

        return view('inventory.items.show', compact('item', 'inventoryValue', 'costLayers', 'currentStock', 'vatRate', 'vatType'));
    }

    public function movements($encodedId, Request $request)
    {
        $decoded = Hashids::decode($encodedId);
        $itemId = !empty($decoded) ? $decoded[0] : null;
        
        if (!$itemId) {
            return response()->json(['error' => 'Invalid item ID'], 404);
        }

        $item = Item::findOrFail($itemId);
        $this->authorize('view', $item);

        if ($request->ajax()) {
            $loginLocationId = session('location_id');
            $movements = Movement::where('item_id', $item->id)
                ->when($loginLocationId, function ($q) use ($loginLocationId) {
                    $q->where('location_id', $loginLocationId);
                })
                ->with('user')
                ->orderBy('movement_date', 'desc')
                ->orderBy('id', 'desc');

            return DataTables::of($movements)
                ->addColumn('movement_date_formatted', function ($movement) {
                    return $movement->movement_date ? $movement->movement_date->format('M d, Y') : 'N/A';
                })
                ->addColumn('movement_type_badge', function ($movement) {
                    $typeClasses = [
                        'opening_balance' => 'bg-primary',
                        'transfer_in' => 'bg-success',
                        'transfer_out' => 'bg-info',
                        'sold' => 'bg-danger',
                        'purchased' => 'bg-success',
                        'adjustment_in' => 'bg-warning',
                        'adjustment_out' => 'bg-secondary'
                    ];
                    $typeLabels = [
                        'opening_balance' => 'Opening Balance',
                        'transfer_in' => 'Transfer In',
                        'transfer_out' => 'Transfer Out',
                        'sold' => 'Sold',
                        'purchased' => 'Purchased',
                        'adjustment_in' => 'Adjustment In',
                        'adjustment_out' => 'Adjustment Out'
                    ];
                    
                    $badgeClass = $typeClasses[$movement->movement_type] ?? 'bg-secondary';
                    $badgeText = $typeLabels[$movement->movement_type] ?? ucfirst($movement->movement_type);
                    
                    return '<span class="badge ' . $badgeClass . '">' . $badgeText . '</span>';
                })
                ->addColumn('location_name', function ($movement) {
                    return $movement->location ? $movement->location->name : 'N/A';
                })
                ->addColumn('quantity_formatted', function ($movement) {
                    return number_format($movement->quantity, 2);
                })
                ->addColumn('balance_after_formatted', function ($movement) {
                    return number_format($movement->balance_after, 2);
                })
                ->addColumn('reference_display', function ($movement) {
                    return $movement->reference ?: 'N/A';
                })
                ->addColumn('notes_display', function ($movement) {
                    return $movement->notes ?: 'N/A';
                })
                ->addColumn('user_name', function ($movement) {
                    return $movement->user->name ?? 'N/A';
                })
                ->rawColumns(['movement_type_badge'])
                ->make(true);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }

    public function edit($encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $itemId = !empty($decoded) ? $decoded[0] : null;
        
        if (!$itemId) {
            return redirect()->route('inventory.items.index')
                ->with('error', 'Invalid item ID');
        }

        $item = Item::findOrFail($itemId);
        $this->authorize('update', $item);
        
        $categories = Category::where('company_id', Auth::user()->company_id)->get();
        $locations = InventoryLocation::where('company_id', Auth::user()->company_id)->with('branch')->orderBy('name')->get();
        
        // Get accounts for dropdowns based on account classes with company filtering
        $companyId = Auth::user()->company_id;
        
        $inventoryAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->where('name', 'Assets');
        })->get();

        $salesAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Revenue', 'Income']);
        })->get();

        $costAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->where('name', 'Expenses');
        })->get();

        $vatAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Liabilities']);
        })->get();

        $withholdingTaxAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Liabilities']);
        })->get();

        $discountAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Expenses']);
        })->get();

        $withholdingTaxExpenseAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Expenses']);
        })->get();

        $purchasePayableAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Liabilities']);
        })->get();

        $discountIncomeAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereHas('accountClassGroup.accountClass', function ($query) {
            $query->whereIn('name', ['Revenue', 'Income']);
        })->get();

        $branches = Branch::where('company_id', $companyId)->active()->orderBy('name')->get();
        $assignableBranches = Branch::where('company_id', $companyId)
            ->whereIn('id', Auth::user()->permittedBranchIds())
            ->active()
            ->orderBy('name')
            ->get();
        $item->load('visibilityBranches');
        $branchPricesByBranch = $item->branchPrices()->get()->keyBy('branch_id');
        $locationPricesByLocation = $item->locationPrices()->get()->keyBy('location_id');

        return view('inventory.items.edit', compact('item', 'categories', 'locations', 'inventoryAccounts', 'salesAccounts', 'costAccounts', 'vatAccounts', 'withholdingTaxAccounts', 'withholdingTaxExpenseAccounts', 'purchasePayableAccounts', 'discountAccounts', 'discountIncomeAccounts', 'branches', 'assignableBranches', 'branchPricesByBranch', 'locationPricesByLocation'));
    }

    public function update(Request $request, $encodedId)
    {
        $itemId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$itemId) {
            return redirect()->route('inventory.items.index')
                ->with('error', 'Invalid item ID');
        }

        $item = Item::findOrFail($itemId);
        $this->authorize('update', $item);

        $allowedBranchIds = Auth::user()->permittedBranchIds();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:inventory_items,code,' . $item->id,
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'location_id' => 'nullable|exists:inventory_locations,id',
            'item_type' => 'required|in:product,service',
            'unit_of_measure' => 'nullable|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'has_wholesale' => 'nullable|boolean',
            'wholesale_unit_price' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () => $request->boolean('has_wholesale')),
            ],
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'track_expiry' => 'nullable|boolean',
            'has_opening_balance' => 'nullable|boolean',
            'opening_balance_quantity' => 'nullable|numeric|min:0.01',
            'has_different_sales_revenue_account' => 'nullable|boolean',
            'sales_revenue_account_id' => 'nullable|required_if:has_different_sales_revenue_account,1|exists:chart_accounts,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['integer', Rule::in($allowedBranchIds)],
        ]);

        // Detect if item already has an opening balance set or movement created
        $itemHasOpening = (bool) (
            ($item->has_opening_balance ?? false) ||
            ($item->opening_balance_quantity ?? 0) > 0 ||
            ($item->opening_balance_value ?? 0) > 0 ||
            \App\Models\Inventory\Movement::where('item_id', $item->id)
                ->where('reference', 'like', 'Opening Balance -%')
                ->exists()
        );

        // For update: only require opening balance fields when adding them for the first time
        if ($request->boolean('has_opening_balance') && !$itemHasOpening) {
            $request->validate([
                'opening_balance_quantity' => 'required|numeric|min:0.01',
                'cost_price' => 'required|numeric|min:0',
            ], [
                'opening_balance_quantity.required' => 'Opening balance quantity is required when opening balance is enabled.',
                'cost_price.required' => 'Cost price is required when opening balance is enabled.',
            ]);
        }

        try {
            DB::beginTransaction();

            // Calculate opening balance value automatically
            $openingBalanceValue = 0;
            if ($request->boolean('has_opening_balance') && $request->opening_balance_quantity > 0) {
                $openingBalanceValue = $request->opening_balance_quantity * $request->cost_price;
            }

            // Check if opening balance is being added for the first time
            $isAddingOpeningBalance = $request->boolean('has_opening_balance') && 
                                    !$item->has_opening_balance && 
                                    $request->opening_balance_quantity > 0;

            // If item already has opening balance, prevent editing its quantity/value via edit form
            $preserveOpeningQty = $itemHasOpening;

            // Normalize has_opening_balance: if any opening exists, force true and ignore unchecking
            $hasOpeningBalanceFinal = $itemHasOpening ? true : $request->has('has_opening_balance');

            // For service items, cost_price should default to 0 if not provided
            // For product items, use provided cost_price or default to 0
            $costPrice = $request->cost_price ?? 0;
            if ($request->item_type === 'service') {
                $costPrice = 0; // Services don't have a cost price
            }

            $hasWholesale = $request->boolean('has_wholesale');
            $wholesaleUnitPrice = $hasWholesale ? ($request->wholesale_unit_price ?? 0) : null;

            $item->update([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'location_id' => $request->location_id,
                'item_type' => $request->item_type,
                'unit_of_measure' => $request->unit_of_measure,
                'cost_price' => $costPrice,
                'unit_price' => $request->unit_price,
                'has_wholesale' => $hasWholesale,
                'wholesale_unit_price' => $wholesaleUnitPrice,
                'minimum_stock' => $request->minimum_stock,
                'maximum_stock' => $request->maximum_stock,
                'reorder_level' => $request->reorder_level,
                'is_active' => $request->has('is_active'),
                'track_stock' => $request->has('track_stock'),
                'track_expiry' => $request->has('track_expiry'),
                'has_opening_balance' => $hasOpeningBalanceFinal,
                'has_different_sales_revenue_account' => $request->has('has_different_sales_revenue_account'),
                'sales_revenue_account_id' => $request->has('has_different_sales_revenue_account') ? $request->sales_revenue_account_id : null,
            ]);

            // Sync branch-specific prices (override per branch; empty = use item default)
            if (Schema::hasTable('inventory_item_prices')) {
                $branchPricesInput = $request->input('branch_prices', []);
                $branchIdsWithInput = array_keys($branchPricesInput);
                foreach ($branchIdsWithInput as $branchId) {
                    $branchId = (int) $branchId;
                    if ($branchId <= 0) continue;
                    $prices = $branchPricesInput[$branchId] ?? [];
                    $cost = isset($prices['cost_price']) && $prices['cost_price'] !== '' && $prices['cost_price'] !== null
                        ? (float) $prices['cost_price'] : null;
                    $unit = isset($prices['unit_price']) && $prices['unit_price'] !== '' && $prices['unit_price'] !== null
                        ? (float) $prices['unit_price'] : null;
                    $wholesale = isset($prices['wholesale_unit_price']) && $prices['wholesale_unit_price'] !== '' && $prices['wholesale_unit_price'] !== null
                        ? (float) $prices['wholesale_unit_price'] : null;
                    if ($cost === null && $unit === null && $wholesale === null) {
                        ItemBranchPrice::where('item_id', $item->id)->where('branch_id', $branchId)->delete();
                        continue;
                    }
                    ItemBranchPrice::updateOrCreate(
                        ['item_id' => $item->id, 'branch_id' => $branchId],
                        [
                            'cost_price' => $cost ?? $item->cost_price,
                            'unit_price' => $unit ?? $item->unit_price,
                            'wholesale_unit_price' => $wholesale,
                        ]
                    );
                }
            }

            // Sync location-specific prices
            if (Schema::hasTable('inventory_item_location_prices')) {
                $locationPricesInput = $request->input('location_prices', []);
                foreach ($locationPricesInput as $locationId => $prices) {
                    $locationId = (int) $locationId;
                    if ($locationId <= 0) continue;
                    $cost = isset($prices['cost_price']) && $prices['cost_price'] !== '' && $prices['cost_price'] !== null
                        ? (float) $prices['cost_price'] : null;
                    $unit = isset($prices['unit_price']) && $prices['unit_price'] !== '' && $prices['unit_price'] !== null
                        ? (float) $prices['unit_price'] : null;
                    $wholesale = isset($prices['wholesale_unit_price']) && $prices['wholesale_unit_price'] !== '' && $prices['wholesale_unit_price'] !== null
                        ? (float) $prices['wholesale_unit_price'] : null;
                    if ($cost === null && $unit === null && $wholesale === null) {
                        ItemLocationPrice::where('item_id', $item->id)->where('location_id', $locationId)->delete();
                        continue;
                    }
                    ItemLocationPrice::updateOrCreate(
                        ['item_id' => $item->id, 'location_id' => $locationId],
                        [
                            'cost_price' => $cost ?? $item->cost_price,
                            'unit_price' => $unit ?? $item->unit_price,
                            'wholesale_unit_price' => $wholesale,
                        ]
                    );
                }
            }

            if (Schema::hasTable('inventory_items_branches')) {
                $branchIds = collect($request->input('branch_ids', []))
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
                $item->visibilityBranches()->sync($branchIds);
            }

            DB::commit();

            return redirect()->route('inventory.items.index')
                ->with('success', 'Inventory item updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update inventory item: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($encodedId)
    {
        $itemId = Hashids::decode($encodedId)[0] ?? null;
        
        if (!$itemId) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Invalid item ID'], 422);
            }
            return redirect()->route('inventory.items.index')
                ->with('error', 'Invalid item ID');
        }

        $item = Item::findOrFail($itemId);
        $this->authorize('delete', $item);
        
        // Block deletion if item is referenced anywhere
        $hasReferences = false;
        $blockingReasons = [];

        if ($item->movements()->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'inventory movements';
        }
        if (SalesInvoiceItem::where('inventory_item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'sales invoices';
        }
        if (SalesOrderItem::where('inventory_item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'sales orders';
        }
        if (DeliveryItem::where('inventory_item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'deliveries';
        }
        if (PosSaleItem::where('inventory_item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'POS sales';
        }
        if (class_exists(CashSaleItem::class) && CashSaleItem::where('inventory_item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'cash sales';
        }
        if (CreditNoteItem::where('inventory_item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'credit notes';
        }
        if (PurchaseInvoiceItem::where('inventory_item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'purchase invoices';
        }
        if (PurchaseOrderItem::where('item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'purchase orders';
        }
        if (PurchaseQuotationItem::where('item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'purchase quotations';
        }
        if (GoodsReceiptItem::where('inventory_item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'goods receipts';
        }
        if (class_exists(ItemBatch::class) && ItemBatch::where('item_id', $item->id)->exists()) {
            $hasReferences = true;
            $blockingReasons[] = 'production batches';
        }

        if ($hasReferences) {
            $message = 'Cannot delete item because it is referenced in: ' . implode(', ', $blockingReasons) . '.';
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        $item->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Inventory item deleted successfully.']);
        }

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item deleted successfully.');
    }

    public function import(Request $request)
    {
        $this->authorize('create', Item::class);
        
        $request->validate([
            'category_id' => 'required|exists:inventory_categories,id',
            'item_type' => 'required|in:product,service',
            'csv_file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        try {
            $file = $request->file('csv_file');
            
            // Store the file temporarily
            $filePath = $file->store('imports/inventory-items', 'local');
            $fullPath = storage_path('app/' . $filePath);
            
            \Log::info('Starting inventory import', [
                'file' => $fullPath,
                'company_id' => Auth::user()->company_id,
                'user_id' => Auth::id()
            ]);
            
            // Validate CSV header before queuing
            $csvData = array_map('str_getcsv', file($fullPath));
            $header = array_shift($csvData);

            // Validate CSV header
            $requiredColumns = ['name', 'code', 'unit_price'];
            $missingColumns = array_diff($requiredColumns, $header);
            
            if (!empty($missingColumns)) {
                unlink($fullPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required columns: ' . implode(', ', $missingColumns)
                ], 422);
            }

            // Count rows for feedback
            $rowCount = count($csvData);

            // Create import batch record
            $batch = ImportBatch::create([
                'company_id' => Auth::user()->company_id,
                'user_id' => Auth::id(),
                'file_name' => $file->getClientOriginalName(),
                'total_rows' => $rowCount,
                'status' => 'pending'
            ]);

            \Log::info('Import batch created', [
                'batch_id' => $batch->id,
                'total_rows' => $rowCount
            ]);

            // Execute the import job directly (synchronously) instead of queuing
            $job = new ImportInventoryItems(
                $fullPath,
                $request->category_id,
                $request->item_type,
                Auth::user()->company_id,
                Auth::id(),
                $batch->id
            );
            
            try {
                $job->handle();
            } catch (\Exception $jobException) {
                \Log::error('Job execution error: ' . $jobException->getMessage(), [
                    'trace' => $jobException->getTraceAsString()
                ]);
                throw $jobException;
            }
            
            // Refresh batch to get updated stats
            $batch->refresh();

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$batch->imported_rows} items.",
                'batchId' => $batch->id,
                'rowCount' => $rowCount,
                'imported' => $batch->imported_rows,
                'errors' => $batch->error_rows
            ]);

        } catch (\Exception $e) {
            \Log::error('Import failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate(Request $request)
    {
        $variant = $request->query('variant', 'basic');
        if (! in_array($variant, ['basic', 'wholesale'], true)) {
            $variant = 'basic';
        }

        $filename = $variant === 'wholesale'
            ? 'inventory_items_template_with_wholesale.csv'
            : 'inventory_items_template.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $columns = [
            'name',
            'code',
            'description',
            'unit_of_measure',
            'cost_price',
            'unit_price',
            'minimum_stock',
            'maximum_stock',
            'reorder_level',
            'track_expiry',
        ];

        // Sample beverage products data (Prices in TZS); unit_price is column index 5
        $baseSampleData = [
            ['Coca Cola 500ml', 'CC500', 'Coca Cola soft drink 500ml bottle', 'bottles', '800', '1200', '50', '500', '100', 'Yes'],
            ['Pepsi 500ml', 'PP500', 'Pepsi soft drink 500ml bottle', 'bottles', '750', '1150', '50', '500', '100', 'Yes'],
            ['Sprite 500ml', 'SP500', 'Sprite lemon-lime drink 500ml bottle', 'bottles', '750', '1150', '40', '400', '80', 'Yes'],
            ['Fanta Orange 500ml', 'FO500', 'Fanta orange flavored drink 500ml bottle', 'bottles', '750', '1150', '40', '400', '80', 'Yes'],
            ['Mountain Dew 500ml', 'MD500', 'Mountain Dew citrus drink 500ml bottle', 'bottles', '850', '1250', '30', '300', '60', 'Yes'],
            ['Red Bull Energy 250ml', 'RB250', 'Red Bull energy drink 250ml can', 'cans', '1500', '2500', '20', '200', '40', 'Yes'],
            ['Monster Energy 500ml', 'ME500', 'Monster energy drink 500ml can', 'cans', '1800', '2800', '15', '150', '30', 'Yes'],
            ['Aquafina Water 500ml', 'AQ500', 'Aquafina purified water 500ml bottle', 'bottles', '300', '600', '100', '1000', '200', 'No'],
            ['Dasani Water 500ml', 'DS500', 'Dasani purified water 500ml bottle', 'bottles', '300', '600', '100', '1000', '200', 'No'],
            ['7UP 500ml', 'SU500', '7UP lemon-lime drink 500ml bottle', 'bottles', '750', '1150', '40', '400', '80', 'Yes'],
            ['Dr Pepper 500ml', 'DP500', 'Dr Pepper soft drink 500ml bottle', 'bottles', '800', '1200', '30', '300', '60', 'Yes'],
            ['Gatorade Sports Drink 500ml', 'GT500', 'Gatorade sports drink 500ml bottle', 'bottles', '1000', '1800', '25', '250', '50', 'Yes'],
            ['Powerade Sports Drink 500ml', 'PW500', 'Powerade sports drink 500ml bottle', 'bottles', '950', '1750', '25', '250', '50', 'Yes'],
            ['Juice Orange 1L', 'JO1L', 'Fresh orange juice 1 liter carton', 'cartons', '2000', '3500', '20', '200', '40', 'Yes'],
            ['Juice Apple 1L', 'JA1L', 'Fresh apple juice 1 liter carton', 'cartons', '2200', '3700', '20', '200', '40', 'Yes'],
            ['Iced Tea Lemon 500ml', 'IT500', 'Lemon flavored iced tea 500ml bottle', 'bottles', '900', '1500', '35', '350', '70', 'Yes'],
            ['Coffee Frappuccino 500ml', 'CF500', 'Coffee frappuccino drink 500ml bottle', 'bottles', '1200', '2000', '20', '200', '40', 'Yes'],
            ['Green Tea 500ml', 'GT500B', 'Unsweetened green tea 500ml bottle', 'bottles', '1000', '1600', '25', '250', '50', 'Yes'],
            ['Coconut Water 500ml', 'CW500', 'Natural coconut water 500ml bottle', 'bottles', '1500', '2500', '15', '150', '30', 'Yes'],
            ['Energy Shot 60ml', 'ES60', 'High caffeine energy shot 60ml bottle', 'bottles', '800', '1500', '50', '500', '100', 'Yes'],
        ];

        $sampleData = [];
        $rowIndex = 0;
        foreach ($baseSampleData as $row) {
            if ($variant === 'wholesale') {
                $unitPrice = is_numeric($row[5] ?? null) ? (float) $row[5] : 0;
                // First rows: wholesale enabled with ~10% below retail; water rows: retail only
                if ($rowIndex < 12 && $unitPrice > 0) {
                    $wholesale = number_format(round($unitPrice * 0.9, 2), 2, '.', '');
                    $sampleData[] = array_merge($row, ['Yes', $wholesale]);
                } else {
                    $sampleData[] = array_merge($row, ['No', '']);
                }
            } else {
                $sampleData[] = $row;
            }
            $rowIndex++;
        }

        if ($variant === 'wholesale') {
            $columns[] = 'has_wholesale';
            $columns[] = 'wholesale_unit_price';
        }

        $callback = function () use ($columns, $sampleData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get stock quantities for a specific item across all locations
     */
    public function getItemStock($encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $itemId = !empty($decoded) ? $decoded[0] : null;
        
        if (!$itemId) {
            return response()->json(['error' => 'Invalid item ID'], 400);
        }

        $item = Item::findOrFail($itemId);
        $this->authorize('view', $item);

        $stockService = new InventoryStockService();
        
        return response()->json([
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'unit_of_measure' => $item->unit_of_measure,
            ],
            'total_stock' => $stockService->getItemTotalStock($itemId),
            'stock_by_location' => $stockService->getItemStockByLocation($itemId)
        ]);
    }

    /**
     * Get comprehensive stock report for all items
     */
    public function getStockReport()
    {
        $this->authorize('viewAny', Item::class);
        
        $stockService = new InventoryStockService();
        $companyId = auth()->user()->company_id;
        
        return response()->json([
            'stock_report' => $stockService->getComprehensiveStockReport($companyId)
        ]);
    }

    /**
     * Get stock summary for a specific location
     */
    public function getLocationStock($locationId)
    {
        $this->authorize('viewAny', Item::class);
        
        $stockService = new InventoryStockService();
        
        return response()->json([
            'location_id' => $locationId,
            'stock_summary' => $stockService->getLocationStockSummary($locationId),
            'low_stock_items' => $stockService->getLowStockItemsAtLocation($locationId),
            'out_of_stock_items' => $stockService->getOutOfStockItemsAtLocation($locationId)
        ]);
    }



    /**
     * Create opening balance inventory movement
     */
    private function createOpeningBalanceMovement($item, $quantity)
    {
        $costService = new InventoryCostService();
        
        // Add to cost layers
        $costService->addInventory(
            $item->id,
            $quantity,
            $item->cost_price ?? 0,
            'opening_balance',
            'Opening Balance - ' . $item->code,
            now()->toDateString()
        );

        $branch_id = session('branch_id') ?? Auth::user()->branch_id ?? 1;

        Movement::create([
            'branch_id' => $branch_id,
            'location_id' => session('location_id'),
            'item_id' => $item->id,
            'user_id' => auth()->id(),
            'movement_type' => 'opening_balance',
            'quantity' => $quantity,
            'unit_cost' => $item->cost_price ?? 0,
            'total_cost' => ($item->cost_price ?? 0) * $quantity,
            'balance_before' => 0,
            'balance_after' => $quantity,
            'reference' => 'Opening Balance - ' . $item->code,
            'notes' => 'Opening balance stock entry',
            'movement_date' => now()->toDateString(),
        ]);
    }

    public function importStatus($batchId)
    {
        $batch = ImportBatch::find($batchId);
        
        if (!$batch || $batch->company_id !== Auth::user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Import batch not found'
            ], 404);
        }

        $percentage = 0;
        if ($batch->total_rows > 0) {
            $percentage = round(($batch->imported_rows + $batch->error_rows) / $batch->total_rows * 100, 2);
        }

        return response()->json([
            'success' => true,
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'total_rows' => $batch->total_rows,
            'imported_rows' => $batch->imported_rows,
            'error_rows' => $batch->error_rows,
            'progress_percentage' => $percentage,
            'file_name' => $batch->file_name,
            'created_at' => $batch->created_at->format('M d, Y H:i'),
            'completed_at' => $batch->updated_at ? $batch->updated_at->format('M d, Y H:i') : null,
            'error_log' => $batch->error_log ? json_decode($batch->error_log, true) : [],
        ]);
    }

    /**
     * Export inventory items to CSV
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Item::class);

        $companyId = Auth::user()->company_id;
        $stockService = new InventoryStockService();

        $sessionBranchId = session('branch_id') ? (int) session('branch_id') : null;

        // Get all items for the company
        $items = Item::with('category')
            ->where('company_id', $companyId)
            ->visibleInSessionBranch($sessionBranchId)
            ->orderBy('name')
            ->get();

        $filename = 'inventory_items_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($items, $stockService) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8 to ensure Excel opens it correctly
            fwrite($file, "\xEF\xBB\xBF");

            // CSV Headers
            fputcsv($file, [
                'Name',
                'Code',
                'Category',
                'Cost Price',
                'Selling Price',
                'Min Stock',
                'Max Stock',
                'Reorder Level',
                'Current Stock',
                'Expiry Tracking'
            ]);

            // CSV Data
            foreach ($items as $item) {
                $currentStock = $stockService->getItemTotalStock($item->id);
                $expiryTracking = $item->track_expiry ? 'Yes' : 'No';

                fputcsv($file, [
                    $item->name ?? '',
                    $item->code ?? '',
                    $item->category ? $item->category->name : 'No Category',
                    number_format($item->cost_price ?? 0, 2),
                    number_format($item->unit_price ?? 0, 2),
                    $item->minimum_stock ?? 0,
                    $item->maximum_stock ?? 0,
                    $item->reorder_level ?? 0,
                    number_format($currentStock, 2),
                    $expiryTracking
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
