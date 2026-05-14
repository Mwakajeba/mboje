<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Movement;
use App\Models\Inventory\Item;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Yajra\DataTables\Facades\DataTables;
use App\Services\InventoryCostService;
use App\Services\InventoryStockService;

class MovementController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view inventory movements') && !auth()->user()->hasPermissionTo('view inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }
        
        // Auto-set session location if not set
        if (!session('location_id')) {
            $user = Auth::user();
            \Log::info('MovementController: Auto-setting session location for user: ' . $user->name);
            
            $defaultLocation = $user->defaultLocation()->first();
            
            if ($defaultLocation) {
                session(['location_id' => $defaultLocation->id, 'branch_id' => $defaultLocation->branch_id]);
                \Log::info('MovementController: Set default location: ' . $defaultLocation->name . ' (ID: ' . $defaultLocation->id . ')');
            } else {
                $firstLocation = $user->locations()->first();
                if ($firstLocation) {
                    session(['location_id' => $firstLocation->id, 'branch_id' => $firstLocation->branch_id]);
                    \Log::info('MovementController: Set fallback location: ' . $firstLocation->name . ' (ID: ' . $firstLocation->id . ')');
                }
            }
        } else {
            \Log::info('MovementController: Session location already set: ' . session('location_id'));
        }
        
        if ($request->ajax()) {
            $loginLocationId = session('location_id');
            $movements = Movement::with(['item', 'user', 'location'])
                ->whereHas('item', function($query) {
                    $query->where('company_id', Auth::user()->company_id);
                });
            
            // Filter by location if a specific location is selected
            if ($loginLocationId) {
                $movements->where('location_id', $loginLocationId);
            }
            
            // Filter by movement type if provided
            if ($request->has('movement_type') && $request->movement_type) {
                $movements->where('movement_type', $request->movement_type);
            }
            
            $movements->select('inventory_movements.*');

            return DataTables::of($movements)
                ->addColumn('item_name', function ($movement) {
                    return '<div>
                                <span class="fw-bold">' . $movement->item->name . '</span><br>
                                <small class="text-muted">' . $movement->item->code . '</small>
                            </div>';
                })
                ->addColumn('movement_type_badge', function ($movement) {
                    $typeClasses = [
                        'opening_balance' => 'bg-primary',
                        'transfer_in' => 'bg-success',
                        'transfer_out' => 'bg-info',
                        'sold' => 'bg-danger',
                        'purchased' => 'bg-success',
                        'adjustment_in' => 'bg-warning',
                        'adjustment_out' => 'bg-secondary',
                        'write_off' => 'bg-dark'
                    ];
                    $typeLabels = [
                        'opening_balance' => 'Opening Balance',
                        'transfer_in' => 'Transfer In',
                        'transfer_out' => 'Transfer Out',
                        'sold' => 'Sold',
                        'purchased' => 'Purchased',
                        'adjustment_in' => 'Adjustment In',
                        'adjustment_out' => 'Adjustment Out',
                        'write_off' => 'Write Off'
                    ];
                    
                    $badgeClass = $typeClasses[$movement->movement_type] ?? 'bg-secondary';
                    $badgeText = $typeLabels[$movement->movement_type] ?? ucfirst($movement->movement_type);
                    
                    return '<span class="badge ' . $badgeClass . '">' . $badgeText . '</span>';
                })
                ->addColumn('quantity_formatted', function ($movement) {
                    return '<span class="fw-bold">' . number_format($movement->quantity, 2) . '</span><br>
                            <small class="text-muted">' . $movement->item->unit_of_measure . '</small>';
                })
                ->addColumn('unit_cost_formatted', function ($movement) {
                    return number_format($movement->unit_cost, 2);
                })
                ->addColumn('total_cost_formatted', function ($movement) {
                    return '<span class="fw-bold">' . number_format($movement->total_cost, 2) . '</span>';
                })
                ->addColumn('balance_after_formatted', function ($movement) {
                    return '<span class="fw-bold">' . number_format($movement->balance_after, 2) . '</span>';
                })
                ->addColumn('user_name', function ($movement) {
                    return $movement->user->name ?? 'N/A';
                })
                ->addColumn('location_name', function ($movement) {
                    if ($movement->location) {
                        return '<div>
                                    <span class="fw-bold">' . $movement->location->name . '</span><br>
                                    <small class="text-muted">' . ($movement->location->branch->name ?? 'N/A') . '</small>
                                </div>';
                    }
                    return '<span class="text-muted">N/A</span>';
                })
                ->addColumn('actions', function ($movement) {
                    $actions = '<div class="d-flex gap-1">';
                    
                    // Check permissions directly
                    if (auth()->user()->hasPermissionTo('view inventory movements')) {
                        // View button
                        $actions .= '<a href="' . route('inventory.movements.show', $movement->hash_id) . '" class="btn btn-sm btn-outline-info" title="View">
                                    <i class="bx bx-show"></i>
                                    </a>';
                    }
                    
                // Only show edit/delete buttons for movements that are not 'sold', 'purchased', or 'opening_balance'
                if (auth()->user()->hasPermissionTo('manage inventory movements') && !in_array($movement->movement_type, ['sold', 'purchased', 'opening_balance'])) {
                        // Edit button
                        $actions .= '<a href="' . route('inventory.movements.edit', $movement->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bx bx-edit"></i>
                                    </a>';
                        
                        // Delete button
                        $actions .= '<form method="POST" action="' . route('inventory.movements.destroy', $movement->hash_id) . '" class="d-inline">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-sm btn-outline-danger delete-movement" data-reference="' . ($movement->reference ?? 'REF-' . $movement->id) . '" title="Delete">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                    </form>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->editColumn('movement_date', function ($movement) {
                    return format_date($movement->movement_date, 'M d, Y');
                })
                ->editColumn('reference', function ($movement) {
                    return $movement->reference 
                        ? '<span class="badge bg-light text-dark">' . $movement->reference . '</span>'
                        : '<span class="text-muted">N/A</span>';
                })
                ->rawColumns(['item_name', 'movement_type_badge', 'quantity_formatted', 'total_cost_formatted', 'balance_after_formatted', 'location_name', 'actions', 'reference'])
                ->make(true);
        }

        // Get movement statistics for the dashboard (scoped to login location when available)
        $loginLocationId = session('location_id');
        $baseMovementsQuery = Movement::whereHas('item', function($query) {
            $query->where('company_id', Auth::user()->company_id);
        });
        
        // Filter by location if a specific location is selected
        if ($loginLocationId) {
            $baseMovementsQuery->where('location_id', $loginLocationId);
        }

        $statistics = [
            'total_movements' => (clone $baseMovementsQuery)->count(),
            'stock_in' => (clone $baseMovementsQuery)->whereIn('movement_type', ['opening_balance', 'transfer_in', 'purchased', 'adjustment_in'])->count(),
            'stock_out' => (clone $baseMovementsQuery)->whereIn('movement_type', ['transfer_out', 'sold', 'adjustment_out'])->count(),
            'adjustments' => (clone $baseMovementsQuery)->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])->count(),
        ];

        return view('inventory.movements.index', compact('statistics'));
    }

    public function create(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('create inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }
        
        $loginLocationId = session('location_id');

        $itemsQuery = Item::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->where('track_stock', true); // Only show items that track stock

        $locationStocks = [];
        if ($loginLocationId) {
            // Use InventoryStockService to get stock information for all items
            $stockService = new InventoryStockService();
            
            // Get all active items that track stock
            $allItems = Item::where('company_id', Auth::user()->company_id)
                ->where('is_active', true)
                ->where('track_stock', true)
                ->get();
            
            // Calculate stock for all items (including those with 0 stock)
            foreach ($allItems as $item) {
                $stock = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                $locationStocks[$item->id] = $stock;
            }
            
            // Show all items, not just those with stock > 0
            // This allows adjustments for items that have been sold out
        }

        $items = $itemsQuery->orderBy('name')->get();
        // Attach resolved prices (location → branch → default) for display
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);

        // Get the movement type from query parameter (for quick actions)
        $defaultMovementType = $request->get('defaultMovementType', '');
        $isOpening = (bool) $request->get('opening', false);

        return view('inventory.movements.create', compact('items', 'defaultMovementType', 'isOpening', 'locationStocks'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('create inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }
        
        $request->validate([
            'movement_type' => 'required|in:adjustment_in,adjustment_out',
            'reference' => 'nullable|string|max:255',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'movement_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'opening' => 'nullable|boolean',
        ]);

        // Debug: Log the reason field
        \Log::info('MovementController::store - Reason field received: ' . $request->reason);

        DB::transaction(function () use ($request) {
            $costService = new InventoryCostService();
            $movements = [];
            
            foreach ($request->items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);
                
                // Verify item belongs to user's company
                if ($item->company_id !== Auth::user()->company_id) {
                    abort(403, 'Unauthorized access to item: ' . $item->name);
                }

                // Calculate total cost
                $totalCost = $itemData['quantity'] * $itemData['unit_cost'];

                // Get current stock using the new stock service
                $stockService = new InventoryStockService();
                $loginLocationId = session('location_id');
                
                if ($loginLocationId) {
                    $currentStockForCalc = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                } else {
                    $currentStockForCalc = $stockService->getItemTotalStock($item->id);
                }

                // Start new stock from current stock
                $newStock = $currentStockForCalc;
                
                switch ($request->movement_type) {
                    case 'adjustment_in':
                        $newStock += $itemData['quantity']; // Add adjustment quantity
                        // Add to cost layers
                        $costService->addInventory(
                            $item->id,
                            $itemData['quantity'],
                            $itemData['unit_cost'],
                            'adjustment_in',
                            $request->reference,
                            $request->movement_date
                        );
                        break;
                    case 'adjustment_out':
                        $newStock -= $itemData['quantity']; // Subtract adjustment quantity
                        // Remove from cost layers and get actual cost
                        $costInfo = $costService->removeInventory(
                            $item->id,
                            $itemData['quantity'],
                            'adjustment_out',
                            $request->reference,
                            $request->movement_date
                        );
                        // Use actual cost from FIFO/Weighted Average for GL transactions
                        $totalCost = $costInfo['total_cost'];
                        break;
                }

                // Ensure stock doesn't go negative
                if ($newStock < 0) {
                    throw new \Exception('Insufficient stock for ' . $item->name . '. Available: ' . number_format($currentStockForCalc, 2));
                }

                // Resolve branch_id with fallback to session
                $branchId = session('branch_id') ?? Auth::user()->branch_id;
                
                // Create movement record with balance_after already calculated
                $movement = Movement::create([
                    'branch_id' => $branchId,
                    'location_id' => $loginLocationId,
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'movement_type' => $request->movement_type,
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $totalCost / $itemData['quantity'], // Use calculated cost
                    'total_cost' => $totalCost,
                    'reference' => $request->reference ?: ($request->boolean('opening') ? 'Opening Balance' : null),
                    'reason' => $request->reason,
                    'notes' => $request->notes,
                    'movement_date' => $request->movement_date,
                    'balance_before' => $currentStockForCalc,
                    'balance_after' => $newStock,
                ]);

                // Debug: Log what was saved
                \Log::info('MovementController::store - Movement created with reason: ' . $movement->reason);

                // Stock is now tracked via movements, no need to update item directly

                // Create GL transactions
                if ($request->boolean('opening') && $request->movement_type === 'adjustment_in') {
                    $inventoryAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185;
                    $openingBalanceAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value') ?? 41;

                    if ($inventoryAccountId && $openingBalanceAccountId) {
                        // DR Inventory (asset)
                        \App\Models\GlTransaction::create([
                            'chart_account_id' => $inventoryAccountId,
                            'amount' => $totalCost,
                            'nature' => 'debit',
                            'transaction_id' => $movement->id,
                            'transaction_type' => 'opening_balance',
                            'date' => $request->movement_date,
                            'description' => 'Opening balance - ' . ($item->name ?? 'Item'),
                            'branch_id' =>  $branchId,
                            'user_id' => Auth::id(),
                        ]);
                        // CR Opening Balance (equity)
                        \App\Models\GlTransaction::create([
                            'chart_account_id' => $openingBalanceAccountId,
                            'amount' => $totalCost,
                            'nature' => 'credit',
                            'transaction_id' => $movement->id,
                            'transaction_type' => 'opening_balance',
                            'date' => $request->movement_date,
                            'description' => 'Opening balance - ' . ($item->name ?? 'Item'),
                            'branch_id' =>  $branchId,
                            'user_id' => Auth::id(),
                        ]);
                    }
                } else {
                    // Regular adjustments
                    $this->createAdjustmentTransactions($movement, $item);
                }
                
                // Persist independent opening balance record if flagged
                if ($request->boolean('opening')) {
                    $loginLocationId = session('location_id');
                    \App\Models\Inventory\OpeningBalance::create([
                        'company_id' => Auth::user()->company_id,
                        'branch_id' =>  $branchId,
                        'inventory_location_id' => $loginLocationId,
                        'item_id' => $item->id,
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $totalCost / $itemData['quantity'],
                        'total_cost' => $totalCost,
                        'reference' => $request->reference ?: 'Opening Balance',
                        'notes' => $request->notes,
                        'opened_at' => $request->movement_date,
                        'user_id' => Auth::id(),
                    ]);
                }
                
                $movements[] = $movement;
            }
        });

        return redirect()->route('inventory.movements.index')
            ->with('success', count($request->items) . ' stock movement(s) recorded successfully.');
    }

    public function show(Movement $movement)
    {
        if ((!auth()->user()->hasPermissionTo('view inventory movements') && !auth()->user()->hasPermissionTo('view inventory adjustments')) || 
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }
        
        $movement->load(['item', 'user']);

        return view('inventory.movements.show', compact('movement'));
    }

        public function edit(Movement $movement)
    {
        // Disallow editing opening balance movements
        if ($movement->movement_type === 'opening_balance') {
            abort(403, 'Opening balance movements cannot be edited.');
        }
        if ((!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) ||
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }
        
        $items = Item::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->where('has_opening_balance', true)
            ->orderBy('name')
            ->get();
        // Attach resolved prices (location → branch → default) for display
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);

        return view('inventory.movements.edit', compact('movement', 'items'));
    }

        public function update(Request $request, Movement $movement)
    {
        // Disallow updating opening balance movements
        if ($movement->movement_type === 'opening_balance') {
            abort(403, 'Opening balance movements cannot be updated.');
        }
        if ((!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) ||
            $movement->item->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized access.');
        }
        
        $request->validate([
            'reference' => 'nullable|string|max:255',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'movement_date' => 'required|date',
            'movement_type' => 'required|in:adjustment_in,adjustment_out',
            // When editing an adjustment, allow quantity/unit_cost update
            'items.0.quantity' => 'nullable|numeric|min:0.01',
            'items.0.unit_cost' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        // Debug: Log the reason field
        \Log::info('MovementController::update - Reason field received: ' . $request->reason);

        // Support updating quantity and unit cost for adjustments
        $newQuantity = $request->input('items.0.quantity', $request->input('quantity'));
        $newUnitCost = $request->input('items.0.unit_cost', $request->input('unit_cost'));

        $updatePayload = [
            'reference' => $request->reference,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'movement_date' => $request->movement_date,
        ];

        // Allow switching between adjustment_in and adjustment_out
        $newMovementType = $request->movement_type;

        if (in_array($movement->movement_type, ['adjustment_in', 'adjustment_out'])) {
            if ($newQuantity !== null && $newUnitCost !== null) {
                $newQuantity = (float) $newQuantity;
                $newUnitCost = (float) $newUnitCost;
                if ($newQuantity > 0 && $newUnitCost >= 0) {
                    $updatePayload['quantity'] = $newQuantity;
                    $updatePayload['unit_cost'] = $newUnitCost;
                    $updatePayload['total_cost'] = $newQuantity * $newUnitCost;

                    // Recalculate balance_after using existing balance_before (fallback to stock service if missing)
                    $balanceBefore = $movement->balance_before;
                    if ($balanceBefore === null) {
                        $stockService = new InventoryStockService();
                        $loginLocationId = session('location_id');
                        if ($loginLocationId) {
                            $balanceBefore = $stockService->getItemStockAtLocation($movement->item_id, $loginLocationId);
                        } else {
                            $balanceBefore = $stockService->getItemTotalStock($movement->item_id);
                        }
                    }
                    $updatePayload['balance_before'] = $balanceBefore;
                    // Use the requested movement type to derive sign
                    $effectiveType = $newMovementType ?: $movement->movement_type;
                    $updatePayload['movement_type'] = $effectiveType;
                    $updatePayload['balance_after'] = $effectiveType === 'adjustment_in'
                        ? $balanceBefore + $newQuantity
                        : $balanceBefore - $newQuantity;
                }
            }
        }

        $movement->update($updatePayload);

        // Recreate GL transactions to reflect any value change
        GlTransaction::where('transaction_type', 'inventory_adjustment')
            ->where('transaction_id', $movement->id)
            ->delete();
        $this->createAdjustmentTransactions($movement->fresh(), $movement->item);

        // Debug: Log what was updated
        \Log::info('MovementController::update - Movement updated with reason: ' . $movement->fresh()->reason);

        return redirect()->route('inventory.movements.index')
            ->with('success', 'Movement updated successfully.');
    }

        public function destroy(Movement $movement)
    {
        // Disallow deleting opening balance movements
        if ($movement->movement_type === 'opening_balance') {
            abort(403, 'Opening balance movements cannot be deleted.');
        }
        if ((!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('delete inventory adjustments')) ||
            $movement->item->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized access.');
        }
        
        DB::transaction(function () use ($movement) {
            $item = $movement->item;
            
            // Reverse the stock movement
            $newStock = $item->current_stock;
            
            switch ($movement->movement_type) {
                case 'in':
                    $newStock -= $movement->quantity;
                    break;
                case 'out':
                    $newStock += $movement->quantity;
                    break;
                case 'adjustment':
                    // For adjustments, revert to balance before
                    $newStock = $movement->balance_before;
                    break;
            }

            // Ensure stock doesn't go negative
            if ($newStock < 0) {
                throw new \Exception('Cannot delete movement. Would result in negative stock.');
            }

            $item->update(['current_stock' => $newStock]);
            $movement->delete();
        });

        return redirect()->route('inventory.movements.index')
            ->with('success', 'Movement deleted successfully.');
    }

    /**
     * Create double entry transactions for inventory adjustments
     */
    private function createAdjustmentTransactions($movement, $item)
    {
        // Get default accounts from system settings with fallbacks
        $costAccountId = SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185;
        $openingBalanceAccountId = SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value') ?? 41;

        // Debug logging
        \Log::info('Inventory Adjustment Debug', [
            'cost_account_id' => $costAccountId,
            'opening_balance_account_id' => $openingBalanceAccountId,
            'cost_account_type' => gettype($costAccountId),
            'opening_balance_type' => gettype($openingBalanceAccountId),
            'cost_account_empty' => empty($costAccountId),
            'opening_balance_empty' => empty($openingBalanceAccountId)
        ]);

        if (!$costAccountId || !$openingBalanceAccountId) {
            throw new \Exception('Default cost account or opening balance account not configured in inventory settings.');
        }

        // Use movement ID as the numeric transaction id to match schema
        $transactionId = $movement->id;

        // Calculate total value
        $totalValue = $movement->quantity * $movement->unit_cost;

         $branchId = session('branch_id') ?? Auth::user()->branch_id;

        if ($movement->movement_type === 'adjustment_in') {
            // Adjustment IN: Debit Inventory, Credit Opening Balance
            // Debit: Inventory Account (Asset increases)
            GlTransaction::create([
                'chart_account_id' => $costAccountId,
                'amount' => $totalValue,
                'nature' => 'debit',
                'transaction_id' => $transactionId,
                'transaction_type' => 'inventory_adjustment',
                'date' => $movement->movement_date,
                'description' => "Inventory adjustment IN: {$item->name} - {$movement->reason}",
                'branch_id' =>  $branchId,
                'user_id' => Auth::id(),
            ]);

            // Credit: Opening Balance Account (Equity decreases)
            GlTransaction::create([
                'chart_account_id' => $openingBalanceAccountId,
                'amount' => $totalValue,
                'nature' => 'credit',
                'transaction_id' => $transactionId,
                'transaction_type' => 'inventory_adjustment',
                'date' => $movement->movement_date,
                'description' => "Inventory adjustment IN: {$item->name} - {$movement->reason}",
                'branch_id' =>  $branchId,
                'user_id' => Auth::id(),
            ]);

        } elseif ($movement->movement_type === 'adjustment_out') {
            // Adjustment OUT: Debit Opening Balance, Credit Inventory
            // Debit: Opening Balance Account (Equity increases)
            GlTransaction::create([
                'chart_account_id' => $openingBalanceAccountId,
                'amount' => $totalValue,
                'nature' => 'debit',
                'transaction_id' => $transactionId,
                'transaction_type' => 'inventory_adjustment',
                'date' => $movement->movement_date,
                'description' => "Inventory adjustment OUT: {$item->name} - {$movement->reason}",
                'branch_id' =>  $branchId,
                'user_id' => Auth::id(),
            ]);

            // Credit: Inventory Account (Asset decreases)
            GlTransaction::create([
                'chart_account_id' => $costAccountId,
                'amount' => $totalValue,
                'nature' => 'credit',
                'transaction_id' => $transactionId,
                'transaction_type' => 'inventory_adjustment',
                'date' => $movement->movement_date,
                'description' => "Inventory adjustment OUT: {$item->name} - {$movement->reason}",
                'branch_id' =>  $branchId,
                'user_id' => Auth::id(),
            ]);
        }
    }
}
