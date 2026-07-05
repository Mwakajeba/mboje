<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory\Category;
use App\Models\Inventory\Item;
use App\Models\Inventory\Movement;
use App\Models\Inventory\CustomerStorageBalance;
use App\Models\InventoryLocation;
use App\Services\Inventory\CustomerStorageReportService;
use App\Services\CustomerAccountSummaryService;
use App\Services\InventoryValueService;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller
{
    /**
     * Display the inventory management dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get summary statistics for cards
        $categoriesCount = Category::forCompany(auth()->user()->company_id)->count();
        // Inventory items count according to login location (company-scoped)
        $loginLocationId = session('location_id');
      
            // Count all items for the company
            $itemsCount = Item::where('company_id', auth()->user()->company_id)->count();
        
        // Prefer current session branch locations; fallback to user branch; fallback to company
        $currentBranchId = session('branch_id') ?: auth()->user()->branch_id;
        if ($currentBranchId) {
            $locationsCount = InventoryLocation::where('branch_id', $currentBranchId)->count();
        } else {
            $locationsCount = InventoryLocation::where('company_id', auth()->user()->company_id)->count();
        }
        $movementsCount = Movement::whereHas('item', function ($query) {
            $query->forCompany(auth()->user()->company_id);
        })->count();

        // Get adjustments count
        $adjustmentsCount = Movement::whereHas('item', function ($query) {
            $query->forCompany(auth()->user()->company_id);
        })->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])->count();

        // Get transfers count (transfer_in and transfer_out) filtered by location
        $loginLocationId = session('location_id');
        $transfersCount = Movement::whereHas('item', function ($query) {
            $query->forCompany(auth()->user()->company_id);
        })->whereIn('movement_type', ['transfer_in', 'transfer_out']);
        
        // Filter by location if set
        if ($loginLocationId) {
            $transfersCount->where('location_id', $loginLocationId);
        }
        
        $transfersCount = $transfersCount->count();

        // Get write-offs count filtered by location
        $writeOffsCount = Movement::whereHas('item', function ($query) {
            $query->forCompany(auth()->user()->company_id);
        })->where('movement_type', 'write_off');
        
        // Filter by location if set
        if ($loginLocationId) {
            $writeOffsCount->where('location_id', $loginLocationId);
        }
        
        $writeOffsCount = $writeOffsCount->count();

        // Get recent movements for quick overview (scoped to login location when available)
        $recentMovementsQuery = Movement::with(['item', 'user'])
            ->whereHas('item', function ($query) {
                $query->forCompany(auth()->user()->company_id);
            });

        if ($loginLocationId) {
            // Only show movements relevant to the current login location:
            // 1) Movements that occurred in this location
            // 2) Opening Balance movements that were actually posted for this location (match by item and date)
            $recentMovementsQuery->where(function ($q) use ($loginLocationId) {
                $q->where('location_id', $loginLocationId)
                ->orWhere(function ($oq) use ($loginLocationId) {
                    $oq->where('movement_type', 'opening_balance')
                        ->whereExists(function ($sub) use ($loginLocationId) {
                            $sub->from('inventory_opening_balances as ob')
                                ->whereColumn('ob.item_id', 'inventory_movements.item_id')
                                ->whereColumn('ob.opened_at', 'inventory_movements.movement_date')
                                ->where('ob.inventory_location_id', $loginLocationId)
                                ->where('ob.company_id', auth()->user()->company_id);
                        });
                });
            });
        }

        $recentMovements = $recentMovementsQuery->latest()->limit(5)->get();

        // Get count sessions count
        $countSessionsCount = \App\Models\Inventory\CountSession::forCompany(auth()->user()->company_id)
            ->whereIn('status', ['draft', 'frozen', 'counting', 'completed'])
            ->count();

        $customerStorageCount = CustomerStorageBalance::where('company_id', auth()->user()->company_id)
            ->when($currentBranchId, fn ($q) => $q->where('branch_id', $currentBranchId))
            ->where('quantity_on_hand', '>', 0)
            ->count();

        $storageReport = app(CustomerStorageReportService::class)->build(
            (int) auth()->user()->company_id,
            $currentBranchId ? (int) $currentBranchId : null
        );

        $inventoryValueAtLocation = null;
        $inventoryValueCurrency = null;
        if ($loginLocationId) {
            $valueService = app(InventoryValueService::class);
            $inventoryValueCurrency = $valueService->functionalCurrency();
            $inventoryValueAtLocation = $valueService->totalCostValueAtLocation(
                (int) $loginLocationId,
                (int) auth()->user()->company_id
            );
        }

        return view('inventory.index', compact(
            'categoriesCount',
            'itemsCount', 
            'locationsCount',
            'movementsCount',
            'adjustmentsCount',
            'transfersCount',
            'writeOffsCount',
            'recentMovements',
            'countSessionsCount',
            'customerStorageCount',
            'storageReport',
            'inventoryValueAtLocation',
            'inventoryValueCurrency'
        ));
    }

    public function storageReportDatatable(Request $request)
    {
        abort_unless(auth()->user()->can('manage inventory items'), 403);

        $user = auth()->user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?: $user->branch_id;
        $branchId = $branchId ? (int) $branchId : null;

        $reportService = app(CustomerStorageReportService::class);
        $summaryService = app(CustomerAccountSummaryService::class);
        $mikopoCache = [];

        return DataTables::of($reportService->balancesQuery($companyId, $branchId))
            ->addIndexColumn()
            ->addColumn('customer_name', fn ($row) => '<strong>'.e($row->customer->name ?? '—').'</strong>')
            ->addColumn('customer_phone', fn ($row) => e($row->customer->phone ?? '—'))
            ->addColumn('item_display', function ($row) {
                $name = e($row->item->name ?? '—');
                $code = trim((string) ($row->item->code ?? ''));

                if ($code !== '') {
                    return $name.'<br><small class="text-muted">'.e($code).'</small>';
                }

                return $name;
            })
            ->addColumn('quantity_display', fn ($row) => '<span class="fw-semibold">'
                .e($reportService->formatQuantity((float) $row->quantity_on_hand, $row->item))
                .'</span>')
            ->addColumn('mikopo_total', function ($row) use (&$mikopoCache, $summaryService) {
                $customerId = (int) $row->customer_id;

                if (! array_key_exists($customerId, $mikopoCache)) {
                    $mikopoCache[$customerId] = $row->customer
                        ? $summaryService->calculateMikopoTotal($row->customer)
                        : 0.0;
                }

                return '<span class="text-warning fw-semibold">'
                    .number_format($mikopoCache[$customerId], 2)
                    .'</span>';
            })
            ->filterColumn('customer_name', function ($query, $keyword) {
                $query->whereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$keyword.'%'));
            })
            ->filterColumn('item_display', function ($query, $keyword) {
                $query->whereHas('item', function ($q) use ($keyword) {
                    $q->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('code', 'like', '%'.$keyword.'%');
                });
            })
            ->orderColumn('quantity_display', fn ($query, $order) => $query->orderBy('quantity_on_hand', $order))
            ->rawColumns(['customer_name', 'item_display', 'quantity_display', 'mikopo_total'])
            ->make(true);
    }
}
