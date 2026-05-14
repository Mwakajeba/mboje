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

class WriteOffController extends Controller
{
    use AuthorizesRequests;

    private const ALLOWED_TYPES = ['write_off', 'stock_out'];

    private function typeLabel(string $type): string
    {
        return $type === 'stock_out' ? 'Stock-out' : 'Write-off';
    }

    private function typeBadgeClass(string $type): string
    {
        return $type === 'stock_out' ? 'bg-warning text-dark' : 'bg-dark';
    }

    private function defaultReference(string $type): string
    {
        $prefix = $type === 'stock_out' ? 'SO' : 'WO';
        return $prefix . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Display a listing of write-offs and stock-outs.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view inventory movements') && !auth()->user()->hasPermissionTo('view inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        if ($request->ajax()) {
            $loginLocationId = session('location_id');
            $query = Movement::with(['item', 'user', 'location'])
                ->where('movement_type', 'write_off')
                ->whereHas('item', function ($q) {
                    $q->where('company_id', Auth::user()->company_id);
                });

            if ($loginLocationId) {
                $query->where('location_id', $loginLocationId);
            }

            // Filter by writeoff_type if requested
            $typeFilter = $request->input('writeoff_type');
            if ($typeFilter && in_array($typeFilter, self::ALLOWED_TYPES)) {
                $query->where('writeoff_type', $typeFilter);
            }

            $query->select('inventory_movements.*');

            return DataTables::of($query)
                ->addColumn('item_name', function ($movement) {
                    return '<div>
                                <span class="fw-bold">' . e($movement->item->name) . '</span><br>
                                <small class="text-muted">' . e($movement->item->code) . '</small>
                            </div>';
                })
                ->addColumn('movement_type_badge', function ($movement) {
                    $type = $movement->writeoff_type ?? 'write_off';
                    $label = $this->typeLabel($type);
                    $badgeClass = $this->typeBadgeClass($type);
                    return '<span class="badge ' . $badgeClass . '">' . $label . '</span>';
                })
                ->addColumn('quantity_formatted', function ($movement) {
                    return '<span class="fw-bold">' . number_format($movement->quantity, 2) . '</span><br>
                            <small class="text-muted">' . e($movement->item->unit_of_measure) . '</small>';
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
                                    <span class="fw-bold">' . e($movement->location->name) . '</span><br>
                                    <small class="text-muted">' . e($movement->location->branch->name ?? 'N/A') . '</small>
                                </div>';
                    }
                    return '<span class="text-muted">N/A</span>';
                })
                ->addColumn('actions', function ($movement) {
                    $actions = '<div class="d-flex gap-1">';

                    if (auth()->user()->hasPermissionTo('view inventory movements')) {
                        $actions .= '<a href="' . route('inventory.write-offs.show', $movement->hash_id) . '" class="btn btn-sm btn-outline-info" title="View">
                                    <i class="bx bx-show"></i>
                                    </a>';
                    }

                    if (auth()->user()->hasPermissionTo('manage inventory movements')) {
                        $actions .= '<a href="' . route('inventory.write-offs.edit', $movement->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bx bx-edit"></i>
                                    </a>';

                        $actions .= '<form method="POST" action="' . route('inventory.write-offs.destroy', $movement->hash_id) . '" class="d-inline">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-sm btn-outline-danger delete-record" data-reference="' . e($movement->reference ?? 'REF-' . $movement->id) . '" title="Delete">
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
                        ? '<span class="badge bg-light text-dark">' . e($movement->reference) . '</span>'
                        : '<span class="text-muted">N/A</span>';
                })
                ->rawColumns(['item_name', 'movement_type_badge', 'quantity_formatted', 'total_cost_formatted', 'balance_after_formatted', 'location_name', 'actions', 'reference'])
                ->make(true);
        }

        // Statistics
        $loginLocationId = session('location_id');
        $baseQuery = Movement::where('movement_type', 'write_off')
            ->whereHas('item', function ($q) {
                $q->where('company_id', Auth::user()->company_id);
            });

        if ($loginLocationId) {
            $baseQuery->where('location_id', $loginLocationId);
        }

        $statistics = [
            'total_write_offs'  => (clone $baseQuery)->where(function ($q) { $q->where('writeoff_type', 'write_off')->orWhereNull('writeoff_type'); })->count(),
            'total_stock_outs'  => (clone $baseQuery)->where('writeoff_type', 'stock_out')->count(),
            'total_value'       => (clone $baseQuery)->sum('total_cost'),
            'stock_out_value'   => (clone $baseQuery)->where('writeoff_type', 'stock_out')->sum('total_cost'),
        ];

        return view('inventory.write-offs.index', compact('statistics'));
    }

    /**
     * Show the form for creating a new write-off / stock-out.
     */
    public function create(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('create inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        $loginLocationId = session('location_id');

        if (!$loginLocationId) {
            return redirect()->route('inventory.index')
                ->with('error', 'Please select a location first.');
        }

        $stockService = new InventoryStockService();

        $items = $stockService->getItemsWithStockAtLocation(
            Auth::user()->company_id,
            $loginLocationId
        );

        $locationStocks = [];
        foreach ($items as $item) {
            $locationStocks[$item->id] = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
        }

        return view('inventory.write-offs.create', compact('items', 'locationStocks', 'loginLocationId'));
    }

    /**
     * Store a newly created write-off / stock-out.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('create inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'writeoff_type'  => 'required|in:write_off,stock_out',
            'reference'      => 'nullable|string|max:255',
            'reason'         => 'required|string|max:500',
            'notes'          => 'nullable|string',
            'movement_date'  => 'required|date',
            'items'          => 'required|array|min:1',
            'items.*.item_id'   => 'required|exists:inventory_items,id',
            'items.*.quantity'  => 'required|numeric|min:0.01',
        ]);

        $loginLocationId = session('location_id');

        if (!$loginLocationId) {
            return redirect()->route('inventory.write-offs.create')
                ->with('error', 'Please select a location first.');
        }

        $writeoffType = $request->writeoff_type;
        $typeLabel    = $this->typeLabel($writeoffType);

        $stockService = new InventoryStockService();
        $costService  = new InventoryCostService();

        $writeOffExpenseAccountId = (int) (SystemSetting::where('key', 'inventory_default_cost_account')->value('value') ?? 173);
        $inventoryAccountId       = (int) (SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185);

        if (!$inventoryAccountId) {
            return redirect()->route('inventory.write-offs.create')
                ->with('error', 'Inventory account not configured. Please configure it in inventory settings.');
        }

        $branchId           = session('branch_id') ?? Auth::user()->branch_id;
        $glTransactionType  = $writeoffType === 'stock_out' ? 'inventory_stock_out' : 'inventory_write_off';
        $movements          = [];
        $totalWriteOffValue = 0;

        DB::transaction(function () use ($request, $loginLocationId, $branchId, $writeoffType, $typeLabel, $glTransactionType, $stockService, $costService, $writeOffExpenseAccountId, $inventoryAccountId, &$movements, &$totalWriteOffValue) {
            foreach ($request->items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);

                if ($item->company_id !== Auth::user()->company_id) {
                    abort(403, 'Unauthorized access to item: ' . $item->name);
                }

                $currentStock     = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                $writeOffQuantity = (float) $itemData['quantity'];

                if ($writeOffQuantity > $currentStock) {
                    throw new \Exception('Insufficient stock for ' . $item->name . '. Available: ' . number_format($currentStock, 2) . ', Requested: ' . number_format($writeOffQuantity, 2));
                }

                if ($writeOffQuantity <= 0) {
                    continue;
                }

                $costInfo = $costService->removeInventory(
                    $item->id,
                    $writeOffQuantity,
                    'write_off',
                    $request->reference,
                    $request->movement_date
                );

                $totalCost       = $costInfo['total_cost'];
                $averageUnitCost = $writeOffQuantity > 0 ? $totalCost / $writeOffQuantity : 0;
                $totalWriteOffValue += $totalCost;
                $newStock = $currentStock - $writeOffQuantity;

                $movement = Movement::create([
                    'branch_id'      => $branchId,
                    'location_id'    => $loginLocationId,
                    'item_id'        => $item->id,
                    'user_id'        => Auth::id(),
                    'movement_type'  => 'write_off',
                    'writeoff_type'  => $writeoffType,
                    'quantity'       => $writeOffQuantity,
                    'unit_cost'      => $averageUnitCost,
                    'total_cost'     => $totalCost,
                    'reference'      => $request->reference,
                    'reason'         => $request->reason,
                    'notes'          => $request->notes,
                    'movement_date'  => $request->movement_date,
                    'balance_before' => $currentStock,
                    'balance_after'  => $newStock,
                ]);

                $movements[] = $movement;

                // GL: Debit expense account, Credit inventory account
                GlTransaction::create([
                    'chart_account_id' => $writeOffExpenseAccountId,
                    'amount'           => $totalCost,
                    'nature'           => 'debit',
                    'transaction_id'   => $movement->id,
                    'transaction_type' => $glTransactionType,
                    'date'             => $request->movement_date,
                    'description'      => "{$typeLabel}: {$item->name} - {$request->reason}",
                    'branch_id'        => $branchId,
                    'user_id'          => Auth::id(),
                ]);

                GlTransaction::create([
                    'chart_account_id' => $inventoryAccountId,
                    'amount'           => $totalCost,
                    'nature'           => 'credit',
                    'transaction_id'   => $movement->id,
                    'transaction_type' => $glTransactionType,
                    'date'             => $request->movement_date,
                    'description'      => "{$typeLabel}: {$item->name} - {$request->reason}",
                    'branch_id'        => $branchId,
                    'user_id'          => Auth::id(),
                ]);
            }
        });

        $itemsCount = count($movements);

        return redirect()->route('inventory.write-offs.index')
            ->with('success', "Successfully recorded {$itemsCount} {$typeLabel} item(s) with total value of " . number_format($totalWriteOffValue, 2) . ".");
    }

    /**
     * Display the specified record.
     */
    public function show(Movement $movement)
    {
        if ($movement->movement_type !== 'write_off') {
            abort(404, 'Record not found.');
        }

        if ((!auth()->user()->hasPermissionTo('view inventory movements') && !auth()->user()->hasPermissionTo('view inventory adjustments')) ||
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $movement->load(['item', 'user', 'location']);

        return view('inventory.write-offs.show', compact('movement'));
    }

    /**
     * Show the form for editing the specified record.
     */
    public function edit(Movement $movement)
    {
        if ($movement->movement_type !== 'write_off') {
            abort(404, 'Record not found.');
        }

        if ((!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) ||
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        return view('inventory.write-offs.edit', compact('movement'));
    }

    /**
     * Update the specified record.
     */
    public function update(Request $request, Movement $movement)
    {
        if ($movement->movement_type !== 'write_off') {
            abort(404, 'Record not found.');
        }

        if ((!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) ||
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'reference'     => 'nullable|string|max:255',
            'reason'        => 'required|string|max:500',
            'notes'         => 'nullable|string',
            'movement_date' => 'required|date',
        ]);

        $writeoffType      = $movement->writeoff_type ?? 'write_off';
        $typeLabel         = $this->typeLabel($writeoffType);
        $glTransactionType = $writeoffType === 'stock_out' ? 'inventory_stock_out' : 'inventory_write_off';

        DB::transaction(function () use ($request, $movement, $typeLabel, $glTransactionType) {
            $movement->update([
                'reference'     => $request->reference,
                'reason'        => $request->reason,
                'notes'         => $request->notes,
                'movement_date' => $request->movement_date,
            ]);

            GlTransaction::where('transaction_type', $glTransactionType)
                ->where('transaction_id', $movement->id)
                ->update([
                    'description' => "{$typeLabel}: {$movement->item->name} - {$request->reason}",
                    'date'        => $request->movement_date,
                ]);
        });

        return redirect()->route('inventory.write-offs.index')
            ->with('success', $typeLabel . ' updated successfully.');
    }

    /**
     * Remove the specified record.
     */
    public function destroy(Movement $movement)
    {
        if ($movement->movement_type !== 'write_off') {
            abort(404, 'Record not found.');
        }

        if ((!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('delete inventory adjustments')) ||
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $writeoffType      = $movement->writeoff_type ?? 'write_off';
        $typeLabel         = $this->typeLabel($writeoffType);
        $glTransactionType = $writeoffType === 'stock_out' ? 'inventory_stock_out' : 'inventory_write_off';

        DB::transaction(function () use ($movement, $glTransactionType) {
            GlTransaction::where('transaction_type', $glTransactionType)
                ->where('transaction_id', $movement->id)
                ->delete();

            $movement->delete();
        });

        return redirect()->route('inventory.write-offs.index')
            ->with('success', $typeLabel . ' deleted successfully.');
    }
}
