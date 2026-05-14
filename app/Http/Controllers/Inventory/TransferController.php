<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Item;
use App\Models\Inventory\Movement;
use App\Models\Branch;
use App\Models\InventoryLocation;
use App\Services\InventoryCostService;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view inventory movements') && !auth()->user()->hasPermissionTo('view inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }
        
        if ($request->ajax()) {
            $loginLocationId = session('location_id');
            $transfers = Movement::with(['item', 'user'])
                ->whereIn('movement_type', ['transfer_in', 'transfer_out'])
                ->whereHas('item', function($q) {
                    $q->where('company_id', Auth::user()->company_id);
                })
                ->orderBy('created_at', 'desc');
            
            // Filter by login location if set - show only transfers for this location
            if ($loginLocationId) {
                $transfers->where('location_id', $loginLocationId);
            }

            return DataTables::of($transfers)
                ->addColumn('checkbox', function ($transfer) {
                    // Only show checkbox for transfer_out records
                    if ($transfer->movement_type === 'transfer_out') {
                        return '<input type="checkbox" class="form-check-input transfer-checkbox" value="' . $transfer->hash_id . '">';
                    }
                    return '';
                })
                ->addColumn('item_name', function ($transfer) {
                    return '<div>
                                <span class="fw-bold">' . $transfer->item->name . '</span><br>
                                <small class="text-muted">' . $transfer->item->code . '</small>
                            </div>';
                })
                ->addColumn('transfer_type_badge', function ($transfer) {
                    $badgeClass = $transfer->movement_type === 'transfer_in' ? 'bg-success' : 'bg-info';
                    $badgeText = $transfer->movement_type === 'transfer_in' ? 'Transfer In' : 'Transfer Out';
                    
                    return '<span class="badge ' . $badgeClass . '">' . $badgeText . '</span>';
                })
                ->addColumn('quantity_formatted', function ($transfer) {
                    return '<span class="fw-bold">' . number_format($transfer->quantity, 2) . '</span><br>
                            <small class="text-muted">' . $transfer->item->unit_of_measure . '</small>';
                })
                ->addColumn('unit_cost_formatted', function ($transfer) {
                    return number_format($transfer->unit_cost, 2);
                })
                ->addColumn('total_cost_formatted', function ($transfer) {
                    return '<span class="fw-bold">' . number_format($transfer->total_cost, 2) . '</span>';
                })
                ->addColumn('balance_after_formatted', function ($transfer) {
                    return '<span class="fw-bold">' . number_format($transfer->balance_after, 2) . '</span>';
                })
                ->addColumn('user_name', function ($transfer) {
                    return $transfer->user->name ?? 'N/A';
                })
                ->addColumn('actions', function ($transfer) {
                    $actions = '<div class="d-flex gap-1">';
                    
                    if (auth()->user()->hasPermissionTo('view inventory movements') || auth()->user()->hasPermissionTo('view inventory adjustments')) {
                        $actions .= '<a href="' . route('inventory.transfers.show', $transfer->hash_id) . '" class="btn btn-sm btn-outline-info" title="View">
                                    <i class="bx bx-show"></i>
                                    </a>';
                    }
                    
                    if ((auth()->user()->hasPermissionTo('manage inventory movements') || auth()->user()->hasPermissionTo('edit inventory adjustments')) && $transfer->movement_type !== 'transfer_in') {
                        $actions .= '<a href="' . route('inventory.transfers.edit', $transfer->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bx bx-edit"></i>
                                    </a>';
                    }
                    
                    if ((auth()->user()->hasPermissionTo('manage inventory movements') || auth()->user()->hasPermissionTo('delete inventory adjustments')) && $transfer->movement_type !== 'transfer_in') {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger delete-transfer" 
                                    data-url="' . route('inventory.transfers.destroy', $transfer->hash_id) . '" 
                                    data-reference="' . ($transfer->reference ?? 'N/A') . '" 
                                    title="Delete">
                                        <i class="bx bx-trash"></i>
                                    </button>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->editColumn('movement_date', function ($transfer) {
                    return format_date($transfer->movement_date, 'M d, Y');
                })
                ->editColumn('reference', function ($transfer) {
                    return $transfer->reference 
                        ? '<span class="badge bg-light text-dark">' . $transfer->reference . '</span>'
                        : '<span class="text-muted">N/A</span>';
                })
                ->rawColumns(['checkbox', 'item_name', 'transfer_type_badge', 'quantity_formatted', 'total_cost_formatted', 'balance_after_formatted', 'actions', 'reference'])
                ->make(true);
        }

        // Get transfer statistics with simplified query
        $loginLocationId = session('location_id');
        
        // Create base query for company and location filtering
        $baseQuery = Movement::whereIn('movement_type', ['transfer_in', 'transfer_out'])
            ->whereHas('item', function($query) use ($loginLocationId) {
                $query->where('company_id', Auth::user()->company_id);
                if ($loginLocationId) {
                    $query->where('location_id', $loginLocationId);
                }
            });

        // Calculate statistics with proper cloning
        $statistics = [
            'total_transfers' => (clone $baseQuery)->count(),
            'transfers_in' => (clone $baseQuery)->where('movement_type', 'transfer_in')->count(),
            'transfers_out' => (clone $baseQuery)->where('movement_type', 'transfer_out')->count(),
        ];

        return view('inventory.transfers.index', compact('statistics'));
    }

    public function create(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('create inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }
        
        // Get items for current company with stock > 0 at login location
        $loginLocationId = session('location_id');
        $currentBranchId = session('branch_id') ?? $sourceBranchId;
        $currentBranch = $currentBranchId ? \App\Models\Branch::find($currentBranchId) : null;
        $currentLocation = $loginLocationId ? \App\Models\InventoryLocation::with('branch')->find($loginLocationId) : null;
        $stockService = new InventoryStockService();
        
        // Get all active items that track stock
        $allItems = Item::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->orderBy('name')
            ->get();

        $items = collect();
        $locationStocks = [];
        
        if ($loginLocationId) {
            // Filter items to only show those with stock > 0 at the login location
            foreach ($allItems as $item) {
                $stock = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                if ($stock > 0) {
                    $items->push($item);
                    $locationStocks[$item->id] = $stock;
                }
            }
        } else {
            // If no location is set, show all items but calculate total stock
            foreach ($allItems as $item) {
                $stock = $stockService->getItemTotalStock($item->id);
                if ($stock > 0) {
                    $items->push($item);
                    $locationStocks[$item->id] = $stock;
                }
            }
        }

        // Get all branches (including current user's branch for internal transfers)
        $branches = Branch::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('inventory.transfers.create', compact('items', 'branches', 'locationStocks', 'currentBranch', 'currentLocation'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('create inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }
        
        $request->validate([
            'destination_branch_id' => 'required|exists:branches,id',
            'destination_location_id' => 'required|exists:inventory_locations,id',
            'transfer_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        // Resolve source context (branch/location) and guard against nulls
        $loginLocationId = session('location_id');
        $sourceBranchId = session('branch_id') ?? ($sourceBranchId ?? null);
        if (empty($sourceBranchId)) {
            return back()->with('error', 'Your branch is not set. Please select a branch and try again.')->withInput();
        }
        if (empty($loginLocationId)) {
            return back()->with('error', 'Your location is not set. Please select a location and try again.')->withInput();
        }

        // Validate that destination location belongs to destination branch
        $destinationLocation = InventoryLocation::find($request->destination_location_id);
        if (!$destinationLocation || $destinationLocation->branch_id != $request->destination_branch_id) {
            return back()->withErrors(['destination_location_id' => 'Selected location does not belong to the selected branch.'])->withInput();
        }

        DB::transaction(function () use ($request, $loginLocationId, $sourceBranchId) {
            $costService = new InventoryCostService();
            
            foreach ($request->items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);
                
                // Verify item belongs to user's company
                if ($item->company_id !== Auth::user()->company_id) {
                    abort(403, 'Unauthorized access to item: ' . $item->name);
                }

                // Check if item has sufficient stock at the current login location using stock service
                $stockService = new InventoryStockService();
                
                // Prevent transfers to the same location
                if ($loginLocationId && $loginLocationId == $request->destination_location_id) {
                    return back()->with('error', 'Cannot transfer to the same location. Please select a different destination location.')->withInput();
                }
                
                if ($loginLocationId) {
                    $availableAtLocation = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                } else {
                    $availableAtLocation = $stockService->getItemTotalStock($item->id);
                }
                if ($availableAtLocation < $itemData['quantity']) {
                    return back()->with('error', 'Insufficient stock for ' . $item->name . '. Available: ' . number_format($availableAtLocation, 2))->withInput();
                }

                // Get actual cost using FIFO/Weighted Average
                $costInfo = $costService->removeInventory(
                    $item->id,
                    $itemData['quantity'],
                    'transfer_out',
                    $request->reference,
                    $request->transfer_date
                );

                // Stock is now calculated from movements, no need to calculate newStock

                // Create transfer out movement
                $destBranchModel = Branch::find($request->destination_branch_id);
                $destLocationModel = InventoryLocation::find($request->destination_location_id);
                $destBranchName = $destBranchModel?->name ?? 'Unknown Branch';
                $destLocationName = $destLocationModel?->name ?? 'Unknown Location';

                // Record source balances
                $sourceBalanceBefore = $availableAtLocation;
                $sourceBalanceAfter = $availableAtLocation - $itemData['quantity'];

                $createdOut = Movement::create([
                    'branch_id' => $sourceBranchId,
                    'location_id' => $loginLocationId,
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'movement_type' => 'transfer_out',
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $costInfo['average_unit_cost'],
                    'total_cost' => $costInfo['total_cost'],
                    'reference' => $request->reference,
                    'notes' => 'Transfer to ' . $destBranchName . ' (' . $destLocationName . ')' . 
                              ($request->destination_branch_id == $sourceBranchId ? ' (Internal Transfer)' : '') . ' - ' . ($request->notes ?? ''),
                    'movement_date' => $request->transfer_date,
                    'balance_before' => $sourceBalanceBefore,
                    'balance_after' => $sourceBalanceAfter,
                ]);

                // Recalculate and persist balance_after from live stock for accuracy
                $liveAfterSource = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                $createdOut->update(['balance_after' => $liveAfterSource]);

                // Update source item stock
                // Stock is now tracked via movements, no need to update current_stock

                // Check if destination branch and location has this item
                // In the new architecture, items are global and shared across locations
                // No need to find destination items since items are not location-specific
                $destinationItem = null;

                if ($destinationItem) {
                    // Item exists in destination branch - update stock
                    // Stock is now calculated from movements, no need to calculate destinationNewStock
                    
                    // Add to cost layers for destination item
                    $costService->addInventory(
                        $destinationItem->id,
                        $itemData['quantity'],
                        $costInfo['average_unit_cost'],
                        'transfer_in',
                        $request->reference,
                        $request->transfer_date
                    );

                    // Create transfer in movement
                    $sourceBranchName = Branch::find($sourceBranchId)?->name ?? 'Unknown Branch';
                    $sourceLocationName = \App\Models\InventoryLocation::find($loginLocationId)?->name ?? 'Unknown Location';

                    // Destination balances when destination item exists
                    $destBalanceBefore = $stockService->getItemStockAtLocation($destinationItem->id, $request->destination_location_id);
                    $destBalanceAfter = $destBalanceBefore + $itemData['quantity'];

                    $createdIn = Movement::create([
                        'branch_id' => $destinationItem->branch_id,
                        'location_id' => $request->destination_location_id,
                        'item_id' => $destinationItem->id,
                        'user_id' => Auth::id(),
                        'movement_type' => 'transfer_in',
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $costInfo['average_unit_cost'],
                        'total_cost' => $costInfo['total_cost'],
                        'reference' => $request->reference,
                        'notes' => 'Transfer from ' . $sourceBranchName . ' (' . $sourceLocationName . ')' . 
                                  ($request->destination_branch_id == $sourceBranchId ? ' (Internal Transfer)' : '') . ' - ' . ($request->notes ?? ''),
                        'movement_date' => $request->transfer_date,
                        'balance_before' => $destBalanceBefore,
                        'balance_after' => $destBalanceAfter,
                    ]);

                    // Ensure destination balance_after matches live stock snapshot
                    $liveAfterDest = $stockService->getItemStockAtLocation($destinationItem->id, $request->destination_location_id);
                    $createdIn->update(['balance_after' => $liveAfterDest]);

                    // Update destination item stock
                    // Stock is now tracked via movements, no need to update current_stock
                } else {
                    // Single item per company: do not create destination item; just record transfer_in for the same item
                    $sourceBranchName = Branch::find($sourceBranchId)?->name ?? 'Unknown Branch';
                    $sourceLocationName = \App\Models\InventoryLocation::find($loginLocationId)?->name ?? 'Unknown Location';

                    $costService->addInventory(
                        $item->id,
                        $itemData['quantity'],
                        $costInfo['average_unit_cost'],
                        'transfer_in',
                        $request->reference,
                        $request->transfer_date
                    );

                    // Destination balances when using same item
                    $destBalanceBefore2 = $stockService->getItemStockAtLocation($item->id, $request->destination_location_id);
                    $destBalanceAfter2 = $destBalanceBefore2 + $itemData['quantity'];

                    $createdIn2 = Movement::create([
                        'branch_id' => $request->destination_branch_id,
                        'location_id' => $request->destination_location_id,
                        'item_id' => $item->id,
                        'user_id' => Auth::id(),
                        'movement_type' => 'transfer_in',
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $costInfo['average_unit_cost'],
                        'total_cost' => $costInfo['total_cost'],
                        'reference' => $request->reference,
                        'notes' => 'Transfer from ' . $sourceBranchName . ' (' . $sourceLocationName . ')' . 
                                  ($request->destination_branch_id == $sourceBranchId ? ' (Internal Transfer)' : '') . ' - ' . ($request->notes ?? ''),
                        'movement_date' => $request->transfer_date,
                        'balance_before' => $destBalanceBefore2,
                        'balance_after' => $destBalanceAfter2,
                    ]);

                    // Ensure destination balance_after matches live stock snapshot
                    $liveAfterDest2 = $stockService->getItemStockAtLocation($item->id, $request->destination_location_id);
                    $createdIn2->update(['balance_after' => $liveAfterDest2]);
                }
            }
        });

        return redirect()->route('inventory.transfers.index')
            ->with('success', count($request->items) . ' item(s) transferred successfully.');
    }

    public function edit(Movement $transfer)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        // Check if user has access to this transfer based on company and location
        if ($transfer->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $loginLocationId = session('location_id');
        if ($loginLocationId && $transfer->location_id !== $loginLocationId) {
            // Check if this is a base item with opening balance at this location
            $hasOpeningBalance = \App\Models\Inventory\OpeningBalance::where('company_id', Auth::user()->company_id)
                ->where('inventory_location_id', $loginLocationId)
                ->where('item_id', $transfer->item->id)
                ->exists();
            
            if (!$hasOpeningBalance) {
                abort(403, 'Unauthorized access.');
            }
        }

        // Only edit transfer movements, but not transfer_in
        if (!in_array($transfer->movement_type, ['transfer_in', 'transfer_out'])) {
            abort(404, 'Transfer not found.');
        }
        
        // Prevent editing transfer_in movements
        if ($transfer->movement_type === 'transfer_in') {
            abort(403, 'Transfer In movements cannot be edited.');
        }

        // Get items from current company (show all), but compute available stock per login location
        $loginLocationId = session('location_id');
        $items = Item::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->orderBy('name')
            ->get();

        // Get all branches (including current user's branch for internal transfers)
        $branches = Branch::where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get transfer items - find all movements with same reference and date
        $transferItems = Movement::where('reference', $transfer->reference)
            ->where('movement_date', $transfer->movement_date)
            ->whereIn('movement_type', ['transfer_in', 'transfer_out'])
            ->whereHas('item', function($query) use ($loginLocationId) {
                $query->where('company_id', Auth::user()->company_id);
                if ($loginLocationId) {
                    $query->where('location_id', $loginLocationId);
                }
            })
            ->with('item')
            ->get();

        // Get destination info from the corresponding transfer
        $destinationBranch = null;
        $destinationLocation = null;
        
        if ($transfer->movement_type === 'transfer_out') {
            // This is a transfer out, find the corresponding transfer in
            $correspondingTransfer = Movement::where('reference', $transfer->reference)
                ->where('movement_type', 'transfer_in')
                ->where('movement_date', $transfer->movement_date)
                ->where('id', '!=', $transfer->id)
                ->with('item', 'location')
                ->first();
                
            if ($correspondingTransfer) {
                $destinationBranch = $correspondingTransfer->location ? $correspondingTransfer->location->branch : null;
                $destinationLocation = $correspondingTransfer->location;
            }
        } else {
            // This is a transfer in, the destination is the current transfer
            $destinationBranch = $transfer->location ? $transfer->location->branch : null;
            $destinationLocation = $transfer->location;
        }

        return view('inventory.transfers.edit', compact('transfer', 'items', 'branches', 'transferItems', 'destinationBranch', 'destinationLocation'));
    }

    public function update(Request $request, Movement $transfer)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        // Check if user has access to this transfer based on company and location
        if ($transfer->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $loginLocationId = session('location_id');
        if ($loginLocationId && $transfer->location_id !== $loginLocationId) {
            // Check if this is a base item with opening balance at this location
            $hasOpeningBalance = \App\Models\Inventory\OpeningBalance::where('company_id', Auth::user()->company_id)
                ->where('inventory_location_id', $loginLocationId)
                ->where('item_id', $transfer->item->id)
                ->exists();
            
            if (!$hasOpeningBalance) {
                abort(403, 'Unauthorized access.');
            }
        }

        // Only update transfer movements, but not transfer_in
        if (!in_array($transfer->movement_type, ['transfer_in', 'transfer_out'])) {
            abort(404, 'Transfer not found.');
        }
        
        // Prevent updating transfer_in movements
        if ($transfer->movement_type === 'transfer_in') {
            abort(403, 'Transfer In movements cannot be updated.');
        }

        $request->validate([
            'destination_branch_id' => 'required|exists:branches,id',
            'destination_location_id' => 'required|exists:inventory_locations,id',
            'transfer_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        // Validate that destination location belongs to destination branch
        $destinationLocation = InventoryLocation::find($request->destination_location_id);
        if (!$destinationLocation || $destinationLocation->branch_id != $request->destination_branch_id) {
            return back()->withErrors(['destination_location_id' => 'Selected location does not belong to the selected branch.'])->withInput();
        }

        // Resolve source context (branch/location) and guard against nulls
        $sourceBranchId = session('branch_id') ?? ($sourceBranchId ?? null);
        if (empty($sourceBranchId)) {
            return back()->with('error', 'Your branch is not set. Please select a branch and try again.')->withInput();
        }

        // Find the corresponding transfer movement (in/out pair)
        $correspondingTransfer = null;
        if ($transfer->movement_type === 'transfer_out') {
            // Find the corresponding transfer_in
            $correspondingTransfer = Movement::where('reference', $transfer->reference)
                ->where('movement_type', 'transfer_in')
                ->where('movement_date', $transfer->movement_date)
                ->where('id', '!=', $transfer->id)
                ->first();
        } else {
            // Find the corresponding transfer_out
            $correspondingTransfer = Movement::where('reference', $transfer->reference)
                ->where('movement_type', 'transfer_out')
                ->where('movement_date', $transfer->movement_date)
                ->where('id', '!=', $transfer->id)
                ->first();
        }

        DB::transaction(function () use ($request, $transfer, $correspondingTransfer, $sourceBranchId, $loginLocationId) {
            $costService = new InventoryCostService();
            $stockService = new InventoryStockService();
            
            // Delete the original transfer movement
            $transfer->delete();

            // Delete corresponding transfer if exists
            if ($correspondingTransfer) {
                $correspondingTransfer->delete();
            }

            // Now create the new transfer with updated details
            foreach ($request->items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);
                
                // Verify item belongs to user's company
                if ($item->company_id !== Auth::user()->company_id) {
                    abort(403, 'Unauthorized access to item: ' . $item->name);
                }

                // Check if item has sufficient stock at the current login location using stock service
                $loginLocationId = session('location_id');
                
                // Prevent transfers to the same location
                if ($loginLocationId && $loginLocationId == $request->destination_location_id) {
                    throw new \Exception('Cannot transfer to the same location. Please select a different destination location.');
                }
                
                if ($loginLocationId) {
                    $availableAtLocation = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                } else {
                    $availableAtLocation = $stockService->getItemTotalStock($item->id);
                }
                
                if ($availableAtLocation < $itemData['quantity']) {
                    throw new \Exception('Insufficient stock for ' . $item->name . '. Available: ' . number_format($availableAtLocation, 2));
                }

                // Get actual cost using FIFO/Weighted Average
                $costInfo = $costService->removeInventory(
                    $item->id,
                    $itemData['quantity'],
                    'transfer_out',
                    $request->reference,
                    $request->transfer_date
                );

                // Stock is now calculated from movements, no need to calculate newStock

                // Create transfer out movement
                $destBranchModel = Branch::find($request->destination_branch_id);
                $destLocationModel = InventoryLocation::find($request->destination_location_id);
                $destBranchName = $destBranchModel?->name ?? 'Unknown Branch';
                $destLocationName = $destLocationModel?->name ?? 'Unknown Location';

                Movement::create([
                    'branch_id' => $sourceBranchId,
                    'location_id' => $loginLocationId,
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'movement_type' => 'transfer_out',
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $costInfo['average_unit_cost'],
                    'total_cost' => $costInfo['total_cost'],
                    'reference' => $request->reference,
                    'notes' => 'Transfer to ' . $destBranchName . ' (' . $destLocationName . ')' . 
                              ($request->destination_branch_id == $sourceBranchId ? ' (Internal Transfer)' : '') . ' - ' . ($request->notes ?? ''),
                    'movement_date' => $request->transfer_date,
                ]);

                // Update source item stock
                // Stock is now tracked via movements, no need to update current_stock

                // Check if destination branch and location has this item
                // In the new architecture, items are global and shared across locations
                // No need to find destination items since items are not location-specific
                $destinationItem = null;

                if ($destinationItem) {
                    // Item exists in destination branch/location - update stock
                    // Stock is now calculated from movements, no need to calculate destinationNewStock
                    
                    // Add to cost layers for destination item
                    $costService->addInventory(
                        $destinationItem->id,
                        $itemData['quantity'],
                        $costInfo['average_unit_cost'],
                        'transfer_in',
                        $request->reference,
                        $request->transfer_date
                    );

                    // Create transfer in movement
                    $sourceBranch = Branch::find($sourceBranchId);
                    $sourceBranchName = $sourceBranch?->name ?? 'Unknown Branch';
                    $sourceLocationName = \App\Models\InventoryLocation::find($loginLocationId)?->name ?? 'Unknown Location';

                    Movement::create([
                        'branch_id' => $destinationItem->branch_id,
                        'location_id' => $request->destination_location_id,
                        'item_id' => $destinationItem->id,
                        'user_id' => Auth::id(),
                        'movement_type' => 'transfer_in',
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $costInfo['average_unit_cost'],
                        'total_cost' => $costInfo['total_cost'],
                        'reference' => $request->reference,
                        'notes' => 'Transfer from ' . $sourceBranchName . ' (' . $sourceLocationName . ')' . 
                                  ($request->destination_branch_id == $sourceBranchId ? ' (Internal Transfer)' : '') . ' - ' . ($request->notes ?? ''),
                        'movement_date' => $request->transfer_date,
                    ]);

                    // Update destination item stock
                    // Stock is now tracked via movements, no need to update current_stock
                } else {
                    // Item doesn't exist in destination branch/location - use the same item but create movement
                    $newItem = $item;

                    // Create transfer in movement
                    $sourceBranchName = Branch::find($sourceBranchId)?->name ?? 'Unknown Branch';
                    $sourceLocationName = \App\Models\InventoryLocation::find($loginLocationId)?->name ?? 'Unknown Location';

                    Movement::create([
                        'branch_id' => $sourceBranchId,
                        'location_id' => $request->destination_location_id,
                        'item_id' => $newItem->id,
                        'user_id' => Auth::id(),
                        'movement_type' => 'transfer_in',
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $costInfo['average_unit_cost'],
                        'total_cost' => $costInfo['total_cost'],
                        'reference' => $request->reference,
                        'notes' => 'Transfer from ' . $sourceBranchName . ' (' . $sourceLocationName . ')' . 
                                  ($request->destination_branch_id == $sourceBranchId ? ' (Internal Transfer)' : '') . ' - ' . ($request->notes ?? ''),
                        'movement_date' => $request->transfer_date,
                        'balance_before' => 0,
                        'balance_after' => $itemData['quantity'],
                    ]);
                }
            }
        });

        return redirect()->route('inventory.transfers.index')
            ->with('success', 'Transfer updated successfully.');
    }

    public function destroy(Movement $transfer)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('delete inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        // Check if user has access to this transfer based on company and location
        if ($transfer->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $loginLocationId = session('location_id');
        if ($loginLocationId && $transfer->location_id !== $loginLocationId) {
            // Check if this is a base item with opening balance at this location
            $hasOpeningBalance = \App\Models\Inventory\OpeningBalance::where('company_id', Auth::user()->company_id)
                ->where('inventory_location_id', $loginLocationId)
                ->where('item_id', $transfer->item->id)
                ->exists();
            
            if (!$hasOpeningBalance) {
                abort(403, 'Unauthorized access.');
            }
        }

        // Only delete transfer movements, but not transfer_in
        if (!in_array($transfer->movement_type, ['transfer_in', 'transfer_out'])) {
            abort(404, 'Transfer not found.');
        }
        
        // Prevent deleting transfer_in movements
        if ($transfer->movement_type === 'transfer_in') {
            abort(403, 'Transfer In movements cannot be deleted.');
        }

        // Find the corresponding transfer movement (in/out pair)
        $correspondingTransfer = null;
        if ($transfer->movement_type === 'transfer_out') {
            // Find the corresponding transfer_in
            $correspondingTransfer = Movement::where('reference', $transfer->reference)
                ->where('movement_type', 'transfer_in')
                ->where('movement_date', $transfer->movement_date)
                ->where('id', '!=', $transfer->id)
                ->first();
        } else {
            // Find the corresponding transfer_out
            $correspondingTransfer = Movement::where('reference', $transfer->reference)
                ->where('movement_type', 'transfer_out')
                ->where('movement_date', $transfer->movement_date)
                ->where('id', '!=', $transfer->id)
                ->first();
        }

        DB::transaction(function () use ($transfer, $correspondingTransfer) {
            // Reverse stock changes
            $item = $transfer->item;
            
            if ($transfer->movement_type === 'transfer_out') {
                // Add back the quantity to source item
                // Stock is now tracked via movements, no need to increment current_stock
            } else {
                // Remove the quantity from destination item
                // Stock is now tracked via movements, no need to decrement current_stock
            }

            // Delete the transfer movement
            $transfer->delete();

            // Delete corresponding transfer if exists
            if ($correspondingTransfer) {
                $correspondingItem = $correspondingTransfer->item;
                
                if ($correspondingTransfer->movement_type === 'transfer_out') {
                    // Stock is now tracked via movements, no need to increment current_stock
                } else {
                    // Stock is now tracked via movements, no need to decrement current_stock
                }
                
                $correspondingTransfer->delete();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Transfer deleted successfully.'
        ]);
    }

    public function show(Movement $transfer)
    {
        if (!auth()->user()->hasPermissionTo('view inventory movements') && !auth()->user()->hasPermissionTo('view inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        // Check if user has access to this transfer based on company and location
        if ($transfer->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $loginLocationId = session('location_id');
        if ($loginLocationId && $transfer->location_id !== $loginLocationId) {
            // Check if this is a base item with opening balance at this location
            $hasOpeningBalance = \App\Models\Inventory\OpeningBalance::where('company_id', Auth::user()->company_id)
                ->where('inventory_location_id', $loginLocationId)
                ->where('item_id', $transfer->item->id)
                ->exists();
            
            if (!$hasOpeningBalance) {
                abort(403, 'Unauthorized access.');
            }
        }

        // Only show transfer movements
        if (!in_array($transfer->movement_type, ['transfer_in', 'transfer_out'])) {
            abort(404, 'Transfer not found.');
        }

        // Load relationships
        $transfer->load(['item', 'user', 'location']);

        // Find the corresponding transfer movement (in/out pair)
        $correspondingTransfer = null;
        $sourceBranch = null;
        $sourceLocation = null;
        $destinationBranch = null;
        $destinationLocation = null;

        if ($transfer->movement_type === 'transfer_out') {
            // This is a transfer out, find the corresponding transfer in
            $correspondingTransfer = Movement::where('reference', $transfer->reference)
                ->where('movement_type', 'transfer_in')
                ->where('movement_date', $transfer->movement_date)
                ->where('id', '!=', $transfer->id)
                ->with('item', 'location')
                ->first();
                
            if ($correspondingTransfer) {
                $sourceBranch = $transfer->location ? $transfer->location->branch : null;
                $sourceLocation = $transfer->location;
                $destinationBranch = $correspondingTransfer->location ? $correspondingTransfer->location->branch : null;
                $destinationLocation = $correspondingTransfer->location;
            }
        } else {
            // This is a transfer in, find the corresponding transfer out
            $correspondingTransfer = Movement::where('reference', $transfer->reference)
                ->where('movement_type', 'transfer_out')
                ->where('movement_date', $transfer->movement_date)
                ->where('id', '!=', $transfer->id)
                ->with('item', 'location')
                ->first();
                
            if ($correspondingTransfer) {
                $sourceBranch = $correspondingTransfer->location ? $correspondingTransfer->location->branch : null;
                $sourceLocation = $correspondingTransfer->location;
                $destinationBranch = $transfer->location ? $transfer->location->branch : null;
                $destinationLocation = $transfer->location;
            }
        }

        return view('inventory.transfers.show', compact('transfer', 'sourceBranch', 'sourceLocation', 'destinationBranch', 'destinationLocation'));
    }

    public function bulkDelete(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('delete inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'transfer_ids' => 'required|array|min:1',
            'transfer_ids.*' => 'required|string'
        ]);

        $transferIds = $request->transfer_ids;
        $deletedCount = 0;
        $errors = [];

        DB::transaction(function () use ($transferIds, &$deletedCount, &$errors) {
            foreach ($transferIds as $transferId) {
                try {
                    $transfer = Movement::findByHashId($transferId);
                    
                    if (!$transfer) {
                        $errors[] = "Transfer not found: {$transferId}";
                        continue;
                    }

                    // Check if user has access to this transfer
                    if ($transfer->item->company_id !== Auth::user()->company_id) {
                        $errors[] = "Unauthorized access to transfer: {$transfer->reference}";
                        continue;
                    }

                    // Only allow deletion of transfer_out movements
                    if ($transfer->movement_type !== 'transfer_out') {
                        $errors[] = "Can only delete transfer out records: {$transfer->reference}";
                        continue;
                    }

                    // Find corresponding transfer_in
                    $correspondingTransfer = Movement::where('reference', $transfer->reference)
                        ->where('movement_type', 'transfer_in')
                        ->where('movement_date', $transfer->movement_date)
                        ->where('id', '!=', $transfer->id)
                        ->first();

                    // Delete the transfer movement
                    $transfer->delete();

                    // Delete corresponding transfer if exists
                    if ($correspondingTransfer) {
                        $correspondingTransfer->delete();
                    }

                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete transfer {$transferId}: " . $e->getMessage();
                }
            }
        });

        $message = "Successfully deleted {$deletedCount} transfer(s).";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        return response()->json([
            'success' => $deletedCount > 0,
            'message' => $message,
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ]);
    }

    public function bulkEdit(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        $transferIds = $request->get('ids');
        if (!$transferIds) {
            return redirect()->route('inventory.transfers.index')->with('error', 'No transfers selected for editing.');
        }

        $transferIds = explode(',', $transferIds);
        $transfers = Movement::whereIn('hash_id', $transferIds)
            ->where('movement_type', 'transfer_out')
            ->whereHas('item', function($q) {
                $q->where('company_id', Auth::user()->company_id);
            })
            ->with(['item', 'user'])
            ->get();

        if ($transfers->isEmpty()) {
            return redirect()->route('inventory.transfers.index')->with('error', 'No valid transfers found for editing.');
        }

        // Get items from current company
        $loginLocationId = session('location_id');
        $items = Item::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->orderBy('name')
            ->get();

        // Get all branches
        $branches = Branch::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('inventory.transfers.bulk-edit', compact('transfers', 'items', 'branches', 'transferIds'));
    }

    public function bulkUpdate(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'transfer_ids' => 'required|array|min:1',
            'transfer_ids.*' => 'required|string',
            'destination_branch_id' => 'required|exists:branches,id',
            'destination_location_id' => 'required|exists:inventory_locations,id',
            'transfer_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        // Validate that destination location belongs to destination branch
        $destinationLocation = InventoryLocation::find($request->destination_location_id);
        if ($destinationLocation->branch_id !== $request->destination_branch_id) {
            return back()->withErrors(['destination_location_id' => 'The selected location does not belong to the selected branch.']);
        }

        $transferIds = $request->transfer_ids;
        $updatedCount = 0;
        $errors = [];

        // Resolve source context (branch/location) and guard against nulls
        $loginLocationId = session('location_id');
        $sourceBranchId = session('branch_id') ?? ($sourceBranchId ?? null);
        if (empty($sourceBranchId)) {
            return back()->with('error', 'Your branch is not set. Please select a branch and try again.')->withInput();
        }

        DB::transaction(function () use ($request, $transferIds, $sourceBranchId, $loginLocationId, &$updatedCount, &$errors) {
            $costService = new InventoryCostService();
            $stockService = new InventoryStockService();

            foreach ($transferIds as $transferId) {
                try {
                    $transfer = Movement::findByHashId($transferId);
                    
                    if (!$transfer) {
                        $errors[] = "Transfer not found: {$transferId}";
                        continue;
                    }

                    // Check if user has access to this transfer
                    if ($transfer->item->company_id !== Auth::user()->company_id) {
                        $errors[] = "Unauthorized access to transfer: {$transfer->reference}";
                        continue;
                    }

                    // Only allow editing of transfer_out movements
                    if ($transfer->movement_type !== 'transfer_out') {
                        $errors[] = "Can only edit transfer out records: {$transfer->reference}";
                        continue;
                    }

                    // Find corresponding transfer_in
                    $correspondingTransfer = Movement::where('reference', $transfer->reference)
                        ->where('movement_type', 'transfer_in')
                        ->where('movement_date', $transfer->movement_date)
                        ->where('id', '!=', $transfer->id)
                        ->first();

                    // Delete the original transfer movement
                    $transfer->delete();

                    // Delete corresponding transfer if exists
                    if ($correspondingTransfer) {
                        $correspondingTransfer->delete();
                    }

                    // Create new transfers for each item
                    foreach ($request->items as $itemData) {
                        $item = Item::findOrFail($itemData['item_id']);
                        
                        // Verify item belongs to user's company
                        if ($item->company_id !== Auth::user()->company_id) {
                            $errors[] = "Unauthorized access to item: {$item->name}";
                            continue;
                        }

                        // Check if item has sufficient stock at the current login location
                        $loginLocationId = session('location_id');
                        
                        // Prevent transfers to the same location
                        if ($loginLocationId && $loginLocationId == $request->destination_location_id) {
                            $errors[] = "Cannot transfer to the same location for item: {$item->name}";
                            continue;
                        }
                        
                        if ($loginLocationId) {
                            $availableAtLocation = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                        } else {
                            $availableAtLocation = $stockService->getItemTotalStock($item->id);
                        }
                        
                        if ($availableAtLocation < $itemData['quantity']) {
                            $errors[] = "Insufficient stock for {$item->name}. Available: " . number_format($availableAtLocation, 2);
                            continue;
                        }

                        // Get actual cost using FIFO/Weighted Average
                        $costInfo = $costService->removeInventory(
                            $item->id,
                            $itemData['quantity'],
                            'transfer_out',
                            $request->reference,
                            $request->transfer_date
                        );

                        // Create transfer out movement
                        $destBranchModel = Branch::find($request->destination_branch_id);
                        $destLocationModel = InventoryLocation::find($request->destination_location_id);
                        $destBranchName = $destBranchModel?->name ?? 'Unknown Branch';
                        $destLocationName = $destLocationModel?->name ?? 'Unknown Location';

                        Movement::create([
                            'branch_id' => $sourceBranchId,
                            'location_id' => $loginLocationId,
                            'item_id' => $item->id,
                            'user_id' => Auth::id(),
                            'movement_type' => 'transfer_out',
                            'quantity' => $itemData['quantity'],
                            'unit_cost' => $costInfo['average_unit_cost'],
                            'total_cost' => $costInfo['total_cost'],
                            'reference' => $request->reference,
                            'notes' => 'Transfer to ' . $destBranchName . ' (' . $destLocationName . ')' . 
                                      ($request->destination_branch_id == $sourceBranchId ? ' (Internal Transfer)' : '') . ' - ' . ($request->notes ?? ''),
                            'movement_date' => $request->transfer_date,
                        ]);

                        // Create transfer in movement
                        $sourceBranch = Branch::find($sourceBranchId);
                        $sourceLocation = $loginLocationId ? InventoryLocation::find($loginLocationId) : null;
                        
                        Movement::create([
                            'branch_id' => $request->destination_branch_id,
                            'location_id' => $request->destination_location_id,
                            'item_id' => $item->id,
                            'user_id' => Auth::id(),
                            'movement_type' => 'transfer_in',
                            'quantity' => $itemData['quantity'],
                            'unit_cost' => $costInfo['average_unit_cost'],
                            'total_cost' => $costInfo['total_cost'],
                            'reference' => $request->reference,
                            'notes' => 'Transfer from ' . ($sourceBranch->name ?? 'Unknown Branch') . ' (' . ($sourceLocation->name ?? 'Main Location') . ')' . 
                                      ($request->destination_branch_id == $sourceBranchId ? ' (Internal Transfer)' : '') . ' - ' . ($request->notes ?? ''),
                            'movement_date' => $request->transfer_date,
                        ]);
                    }

                    $updatedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to update transfer {$transferId}: " . $e->getMessage();
                }
            }
        });

        $message = "Successfully updated {$updatedCount} transfer(s).";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        return redirect()->route('inventory.transfers.index')->with('success', $message);
    }
}
