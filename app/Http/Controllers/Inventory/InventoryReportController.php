<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\InventoryLocation;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Models\Inventory\Category as InventoryCategory;
use App\Models\Purchase\PurchaseInvoiceItem;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Sales\SalesOrderItem;
use App\Models\Purchase\DebitNoteItem;
use App\Models\Sales\CreditNoteItem;
use App\Models\Purchase\GoodsReceiptItem;
use App\Models\Sales\DeliveryItem;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Services\InventoryStockService;
use App\Services\InventoryCostService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view inventory reports');
    }

    /**
     * Display the inventory reports index page
     */
    public function index()
    {
        return view('inventory.reports.index');
    }

    /**
     * Stock on Hand Report
     */
    public function stockOnHand(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->orderBy('name')->get();
        $stockService = new InventoryStockService();
        $costService = new InventoryCostService();
        
        // Get costing method from system settings
        $systemCostMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';

        // Get all locations for the company
        $allLocations = InventoryLocation::where('company_id', auth()->user()->company_id)->get();

        // Calculate item values with location breakdown
        $itemsWithStock = $items->map(function ($item) use ($stockService, $costService, $systemCostMethod, $allLocations, $request) {
            $itemData = [
                'item' => $item,
                'total_stock' => 0,
                'total_value' => 0,
                'locations' => [],
                'unit_cost' => 0
            ];

            // Calculate unit cost based on system method
            if ($systemCostMethod === 'fifo') {
                $inventoryValue = $costService->getInventoryValue($item->id);
                // If no cost layers exist, fall back to item's cost_price
                $itemData['unit_cost'] = $inventoryValue['average_cost'] > 0 ? $inventoryValue['average_cost'] : ($item->cost_price ?? 0);
            } else {
                $itemData['unit_cost'] = $item->cost_price ?? 0;
            }

            // Calculate stock and value for each location
            foreach ($allLocations as $location) {
                $stock = $stockService->getItemStockAtLocation($item->id, $location->id);
                if ($stock > 0) {
                    $value = $stock * $itemData['unit_cost'];
                    $itemData['locations'][] = [
                        'location' => $location,
                        'stock' => $stock,
                        'value' => $value
                    ];
                    $itemData['total_stock'] += $stock;
                    $itemData['total_value'] += $value;
                }
            }

            return $itemData;
        })->filter(function ($itemData) {
            return $itemData['total_stock'] > 0; // Only items with stock
        });

        // Apply location filter if specified
        if ($request->filled('location_id')) {
            $filteredLocationId = $request->location_id;
            $itemsWithStock = $itemsWithStock->map(function ($itemData) use ($filteredLocationId) {
                $itemData['locations'] = collect($itemData['locations'])->filter(function ($locationData) use ($filteredLocationId) {
                    return $locationData['location']->id == $filteredLocationId;
                })->values()->toArray();
                
                // Recalculate totals for filtered location
                $itemData['total_stock'] = collect($itemData['locations'])->sum('stock');
                $itemData['total_value'] = collect($itemData['locations'])->sum('value');
                
                return $itemData;
            })->filter(function ($itemData) {
                return $itemData['total_stock'] > 0;
            });
        }


        // Calculate overall totals
        $totalQuantity = $itemsWithStock->sum('total_stock');
        $totalValue = $itemsWithStock->sum('total_value');

        $locations = $allLocations;
        $categories = InventoryCategory::where('company_id', auth()->user()->company_id)->get();

        return view('inventory.reports.stock-on-hand', compact(
            'itemsWithStock', 'locations', 'categories', 'totalQuantity', 'totalValue', 'systemCostMethod'
        ));
    }

    /**
     * Export Stock on Hand Report to Excel
     */
    public function stockOnHandExportExcel(Request $request)
    {
        // Get the same data as the main report
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->orderBy('name')->get();
        $stockService = new InventoryStockService();
        $costService = new InventoryCostService();
        
        // Get costing method from system settings
        $systemCostMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';

        // Get all locations for the company (with branch for export aggregation by branch)
        $allLocations = InventoryLocation::with('branch')
            ->where('company_id', auth()->user()->company_id)
            ->get();

        // Calculate item values with location breakdown
        $itemsWithStock = $items->map(function ($item) use ($stockService, $costService, $systemCostMethod, $allLocations, $request) {
            $itemData = [
                'item' => $item,
                'total_stock' => 0,
                'total_value' => 0,
                'locations' => [],
                'unit_cost' => 0
            ];

            // Calculate unit cost based on system method
            if ($systemCostMethod === 'fifo') {
                $inventoryValue = $costService->getInventoryValue($item->id);
                // If no cost layers exist, fall back to item's cost_price
                $itemData['unit_cost'] = $inventoryValue['average_cost'] > 0 ? $inventoryValue['average_cost'] : ($item->cost_price ?? 0);
            } else {
                $itemData['unit_cost'] = $item->cost_price ?? 0;
            }

            // Calculate stock and value for each location
            foreach ($allLocations as $location) {
                $stock = $stockService->getItemStockAtLocation($item->id, $location->id);
                if ($stock > 0) {
                    $value = $stock * $itemData['unit_cost'];
                    $itemData['locations'][] = [
                        'location' => $location,
                        'stock' => $stock,
                        'value' => $value
                    ];
                    $itemData['total_stock'] += $stock;
                    $itemData['total_value'] += $value;
                }
            }

            return $itemData;
        })->filter(function ($itemData) {
            return $itemData['total_stock'] > 0; // Only items with stock
        });

        // Apply location filter if specified
        if ($request->filled('location_id')) {
            $filteredLocationId = $request->location_id;
            $itemsWithStock = $itemsWithStock->map(function ($itemData) use ($filteredLocationId) {
                $itemData['locations'] = collect($itemData['locations'])->filter(function ($locationData) use ($filteredLocationId) {
                    return $locationData['location']->id == $filteredLocationId;
                })->values()->toArray();
                
                // Recalculate totals for filtered location
                $itemData['total_stock'] = collect($itemData['locations'])->sum('stock');
                $itemData['total_value'] = collect($itemData['locations'])->sum('value');
                
                return $itemData;
            })->filter(function ($itemData) {
                return $itemData['total_stock'] > 0;
            });
        }

        // Calculate overall totals
        $totalQuantity = $itemsWithStock->sum('total_stock');
        $totalValue = $itemsWithStock->sum('total_value');

        // Effective locations for export; branches = unique branches to show (aggregate locations per branch)
        $exportLocations = $request->filled('location_id')
            ? $allLocations->where('id', (int) $request->location_id)->values()
            : $allLocations;

        $branchIds = $exportLocations->pluck('branch_id')->unique()->filter()->values()->all();
        $exportBranches = Branch::whereIn('id', $branchIds)->orderBy('name')->get();

        $company = auth()->user()->company;
        $filename = 'Stock_On_Hand_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StockOnHandExport($itemsWithStock, $totalQuantity, $totalValue, $systemCostMethod, $company, $exportBranches),
            $filename
        );
    }

    /**
     * Export Stock on Hand Report to PDF
     */
    public function stockOnHandExportPdf(Request $request)
    {
        // Increase memory limit and execution time for large datasets
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 minutes
        
        // Get the same data as the main report
        $query = InventoryItem::where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $stockService = new InventoryStockService();
        $costService = new InventoryCostService();
        
        // Get costing method from system settings
        $systemCostMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';

        // Get all locations for the company
        $allLocations = InventoryLocation::where('company_id', auth()->user()->company_id)->get();

        // Process items in chunks to avoid memory issues
        $itemsWithStock = collect();
        $query->orderBy('name')->chunkById(100, function ($items) use (&$itemsWithStock, $stockService, $costService, $systemCostMethod, $allLocations, $request) {
            // Load categories for this chunk
            $items->load('category');
            
            $chunkData = $items->map(function ($item) use ($stockService, $costService, $systemCostMethod, $allLocations, $request) {
                $itemData = [
                    'item' => $item,
                    'total_stock' => 0,
                    'total_value' => 0,
                    'locations' => [],
                    'unit_cost' => 0
                ];

                // Calculate unit cost based on system method
                if ($systemCostMethod === 'fifo') {
                    $inventoryValue = $costService->getInventoryValue($item->id);
                    // If no cost layers exist, fall back to item's cost_price
                    $itemData['unit_cost'] = $inventoryValue['average_cost'] > 0 ? $inventoryValue['average_cost'] : ($item->cost_price ?? 0);
                } else {
                    $itemData['unit_cost'] = $item->cost_price ?? 0;
                }

                // Calculate stock and value for each location
                foreach ($allLocations as $location) {
                    $stock = $stockService->getItemStockAtLocation($item->id, $location->id);
                    if ($stock > 0) {
                        $value = $stock * $itemData['unit_cost'];
                        $itemData['locations'][] = [
                            'location' => $location,
                            'stock' => $stock,
                            'value' => $value
                        ];
                        $itemData['total_stock'] += $stock;
                        $itemData['total_value'] += $value;
                    }
                }

                return $itemData;
            })->filter(function ($itemData) {
                return $itemData['total_stock'] > 0; // Only items with stock
            });
            
            // Merge chunk data
            $itemsWithStock = $itemsWithStock->merge($chunkData);
        });

        // Apply location filter if specified
        if ($request->filled('location_id')) {
            $filteredLocationId = $request->location_id;
            $itemsWithStock = $itemsWithStock->map(function ($itemData) use ($filteredLocationId) {
                $itemData['locations'] = collect($itemData['locations'])->filter(function ($locationData) use ($filteredLocationId) {
                    return $locationData['location']->id == $filteredLocationId;
                })->values()->toArray();
                
                // Recalculate totals for filtered location
                $itemData['total_stock'] = collect($itemData['locations'])->sum('stock');
                $itemData['total_value'] = collect($itemData['locations'])->sum('value');
                
                return $itemData;
            })->filter(function ($itemData) {
                return $itemData['total_stock'] > 0;
            });
        }

        // Calculate overall totals
        $totalQuantity = $itemsWithStock->sum('total_stock');
        $totalValue = $itemsWithStock->sum('total_value');

        $company = auth()->user()->company;
        $branch = $request->filled('branch_id') ? Branch::find($request->branch_id) : null;
        $location = $request->filled('location_id') ? InventoryLocation::find($request->location_id) : null;
        $category = $request->filled('category_id') ? InventoryCategory::find($request->category_id) : null;
        
        $filename = 'Stock_On_Hand_Report_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.reports.stock-on-hand-pdf', [
            'itemsWithStock' => $itemsWithStock,
            'totalQuantity' => $totalQuantity,
            'totalValue' => $totalValue,
            'systemCostMethod' => $systemCostMethod,
            'company' => $company,
            'branch' => $branch,
            'location' => $location,
            'category' => $category,
            'generatedAt' => now()
        ]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download($filename);
    }

    /**
     * Stock Valuation Report
     */
    public function stockValuation(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($request->filled('location_id')) {
            $query->whereHas('movements', function ($q) use ($request) {
                $q->where('location_id', $request->location_id);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->orderBy('name')->get();
        $stockService = new InventoryStockService();
        $costService = new InventoryCostService();
        
        // Get costing method from request or system settings
        $systemCostMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';
        $selectedCostingMethod = $request->get('costing_method', $systemCostMethod);

        // Calculate item values based on costing method
        $itemsWithValues = $items->map(function ($item) use ($stockService, $costService, $selectedCostingMethod, $request) {
            $stock = $stockService->getItemTotalStock($item->id);
            
            // Get location-specific stock if location filter is applied
            if ($request->filled('location_id')) {
                $stock = $stockService->getItemStockAtLocation($item->id, $request->location_id);
            }
            
            $unitCost = 0;
            $totalValue = 0;
            $costingMethod = 'N/A';
            
            if ($stock > 0) {
                if ($selectedCostingMethod === 'fifo') {
                    $inventoryValue = $costService->getInventoryValue($item->id);
                    $unitCost = $inventoryValue['average_cost'];
                    $totalValue = $stock * $unitCost;
                    $costingMethod = 'FIFO';
                } else {
                    // Weighted Average or default
                    $unitCost = $item->cost_price ?? 0;
                    $totalValue = $stock * $unitCost;
                    $costingMethod = 'Weighted Average';
                }
            }
            
            return [
                'item' => $item,
                'stock' => $stock,
                'unit_cost' => $unitCost,
                'total_value' => $totalValue,
                'costing_method' => $costingMethod
            ];
        })->filter(function ($itemData) {
            return $itemData['stock'] > 0; // Only show items with stock
        });

        // Group by category for subtotals
        $categoryTotals = $itemsWithValues->groupBy('item.category.name')->map(function ($categoryItems) {
            $quantity = $categoryItems->sum('stock');
            $value = $categoryItems->sum('total_value');
            return [
                'quantity' => $quantity,
                'value' => $value
            ];
        });

        // Group by location for subtotals
        $locationTotals = collect();
        if ($request->filled('location_id')) {
            // If specific location is selected, show only that location
            $location = InventoryLocation::find($request->location_id);
            if ($location) {
                $quantity = $itemsWithValues->sum('stock');
                $value = $itemsWithValues->sum('total_value');
                $locationTotals->put($location->name, [
                    'quantity' => $quantity,
                    'value' => $value
                ]);
            }
        } else {
            // Show all locations
            $locations = InventoryLocation::where('company_id', auth()->user()->company_id)->get();
            foreach ($locations as $location) {
                $quantity = 0;
                $value = 0;
                foreach ($itemsWithValues as $itemData) {
                    $stock = $stockService->getItemStockAtLocation($itemData['item']->id, $location->id);
                    if ($stock > 0) {
                        $quantity += $stock;
                        if ($selectedCostingMethod === 'fifo') {
                            $inventoryValue = $costService->getInventoryValue($itemData['item']->id);
                            $unitCost = $inventoryValue['average_cost'];
                        } else {
                            $unitCost = $itemData['item']->cost_price ?? 0;
                        }
                        $value += $stock * $unitCost;
                    }
                }
                if ($quantity > 0) {
                    $locationTotals->put($location->name, [
                        'quantity' => $quantity,
                        'value' => $value
                    ]);
                }
            }
        }

        $totalValue = $itemsWithValues->sum('total_value');

        $locations = InventoryLocation::where('company_id', auth()->user()->company_id)->get();
        $categories = InventoryCategory::where('company_id', auth()->user()->company_id)->get();

        return view('inventory.reports.stock-valuation', compact(
            'itemsWithValues', 'locations', 'categories', 'categoryTotals', 'locationTotals', 'totalValue', 'selectedCostingMethod'
        ));
    }

    /**
     * Movement Register Report
     */
    public function movementRegister(Request $request)
    {
        $query = InventoryMovement::with(['item', 'user', 'location'])
            ->whereHas('item', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('movement_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('movement_date', '<=', $request->date_to);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        // Note: location_id filter removed as inventory_items table doesn't have location_id column

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $movements = $query->orderBy('movement_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate running balance for each movement
        $runningBalance = 0;
        $movementsWithBalance = $movements->map(function ($movement) use (&$runningBalance) {
            // Determine if this is an in or out movement
            $isIn = in_array($movement->movement_type, ['opening_balance', 'transfer_in', 'purchased', 'adjustment_in', 'production']);
            $isOut = in_array($movement->movement_type, ['transfer_out', 'sold', 'adjustment_out', 'write_off']);
            
            if ($isIn) {
                $runningBalance += $movement->quantity;
            } elseif ($isOut) {
                $runningBalance -= $movement->quantity;
            }
            
            $movement->in_qty = $isIn ? $movement->quantity : 0;
            $movement->out_qty = $isOut ? $movement->quantity : 0;
            $movement->balance_qty = $runningBalance;
            
            return $movement;
        });

        // Calculate summary statistics
        $totalMovements = $movementsWithBalance->count();
        $totalInQuantity = $movementsWithBalance->sum('in_qty');
        $totalOutQuantity = $movementsWithBalance->sum('out_qty');
        $totalInValue = $movementsWithBalance->whereIn('movement_type', ['opening_balance', 'transfer_in', 'purchased', 'adjustment_in'])->sum('total_cost');
        $totalOutValue = $movementsWithBalance->whereIn('movement_type', ['transfer_out', 'sold', 'adjustment_out'])->sum('total_cost');

        // Get filter options
        $items = InventoryItem::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $locations = InventoryLocation::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $users = \App\Models\User::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

        // Movement type options
        $movementTypes = [
            'opening_balance' => 'Opening Balance',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'sold' => 'Sold',
            'purchased' => 'Purchased',
            'adjustment_in' => 'Adjustment In',
            'adjustment_out' => 'Adjustment Out',
            'write_off' => 'Write Off'
        ];

        return view('inventory.reports.movement-register', compact(
            'movements', 'movementsWithBalance', 'items', 'locations', 'users', 'movementTypes',
            'totalMovements', 'totalInQuantity', 'totalOutQuantity', 'totalInValue', 'totalOutValue'
        ));
    }

    /**
     * Export Movement Register Report to Excel
     */
    public function movementRegisterExportExcel(Request $request)
    {
        // Get the same data as the main report
        $query = InventoryMovement::with(['item', 'user', 'location'])
            ->whereHas('item', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('movement_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('movement_date', '<=', $request->date_to);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        // Note: location_id filter removed as inventory_items table doesn't have location_id column

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $movements = $query->orderBy('movement_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate running balance for each movement
        $runningBalance = 0;
        $movementsWithBalance = $movements->map(function ($movement) use (&$runningBalance) {
            $isIn = in_array($movement->movement_type, ['opening_balance', 'transfer_in', 'purchased', 'adjustment_in', 'production']);
            $isOut = in_array($movement->movement_type, ['transfer_out', 'sold', 'adjustment_out', 'write_off']);
            
            if ($isIn) {
                $runningBalance += $movement->quantity;
            } elseif ($isOut) {
                $runningBalance -= $movement->quantity;
            }
            
            $movement->in_qty = $isIn ? $movement->quantity : 0;
            $movement->out_qty = $isOut ? $movement->quantity : 0;
            $movement->balance_qty = $runningBalance;
            
            return $movement;
        });

        // Movement type options
        $movementTypes = [
            'opening_balance' => 'Opening Balance',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'sold' => 'Sold',
            'purchased' => 'Purchased',
            'adjustment_in' => 'Adjustment In',
            'adjustment_out' => 'Adjustment Out',
            'write_off' => 'Write Off'
        ];

        $company = auth()->user()->company;
        $filename = 'Movement_Register_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MovementRegisterExport($movementsWithBalance, $movementTypes, $company),
            $filename
        );
    }

    /**
     * Export Movement Register Report to PDF
     */
    public function movementRegisterExportPdf(Request $request)
    {
        // Increase memory limit and execution time for large datasets
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 minutes
        
        // Get the same data as the main report
        $query = InventoryMovement::whereHas('item', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('movement_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('movement_date', '<=', $request->date_to);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        // Note: location_id filter removed as inventory_items table doesn't have location_id column

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Process movements in chunks to avoid memory issues
        // Note: Using regular chunk() instead of chunkById() to maintain date order for running balance
        $movementsWithBalance = collect();
        $runningBalance = 0;
        
        $query->orderBy('movement_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->chunk(100, function ($movements) use (&$movementsWithBalance, &$runningBalance) {
                // Load relationships for this chunk
                $movements->load(['item', 'user', 'location']);
                
                $chunkData = $movements->map(function ($movement) use (&$runningBalance) {
                    $isIn = in_array($movement->movement_type, ['opening_balance', 'transfer_in', 'purchased', 'adjustment_in', 'production']);
                    $isOut = in_array($movement->movement_type, ['transfer_out', 'sold', 'adjustment_out', 'write_off']);
                    
                    if ($isIn) {
                        $runningBalance += $movement->quantity;
                    } elseif ($isOut) {
                        $runningBalance -= $movement->quantity;
                    }
                    
                    $movement->in_qty = $isIn ? $movement->quantity : 0;
                    $movement->out_qty = $isOut ? $movement->quantity : 0;
                    $movement->balance_qty = $runningBalance;
                    
                    return $movement;
                });
                
                // Merge chunk data
                $movementsWithBalance = $movementsWithBalance->merge($chunkData);
            });

        // Calculate summary statistics
        $totalMovements = $movementsWithBalance->count();
        $totalInQuantity = $movementsWithBalance->sum('in_qty');
        $totalOutQuantity = $movementsWithBalance->sum('out_qty');
        $totalInValue = $movementsWithBalance->whereIn('movement_type', ['opening_balance', 'transfer_in', 'purchased', 'adjustment_in'])->sum('total_cost');
        $totalOutValue = $movementsWithBalance->whereIn('movement_type', ['transfer_out', 'sold', 'adjustment_out'])->sum('total_cost');

        // Movement type options
        $movementTypes = [
            'opening_balance' => 'Opening Balance',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'sold' => 'Sold',
            'purchased' => 'Purchased',
            'adjustment_in' => 'Adjustment In',
            'adjustment_out' => 'Adjustment Out',
            'write_off' => 'Write Off'
        ];

        $company = auth()->user()->company;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $item = $request->filled('item_id') ? InventoryItem::find($request->item_id) : null;
        $location = $request->filled('location_id') ? InventoryLocation::find($request->location_id) : null;
        $user = $request->filled('user_id') ? \App\Models\User::find($request->user_id) : null;
        $selectedMovementType = $request->filled('movement_type') ? $request->movement_type : null;
        
        $filename = 'Movement_Register_Report_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.reports.movement-register-pdf', [
            'movements' => $movementsWithBalance,
            'movementTypes' => $movementTypes,
            'totalMovements' => $totalMovements,
            'totalInQuantity' => $totalInQuantity,
            'totalOutQuantity' => $totalOutQuantity,
            'totalInValue' => $totalInValue,
            'totalOutValue' => $totalOutValue,
            'company' => $company,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'item' => $item,
            'location' => $location,
            'user' => $user,
            'selectedMovementType' => $selectedMovementType,
            'generatedAt' => now()
        ]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download($filename);
    }

    /**
     * Aging Stock Report
     */
    public function agingStock(Request $request)
    {
        $thresholdDays = $request->get('threshold_days', 90);
        $dateThreshold = Carbon::now()->subDays($thresholdDays);

        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id)
            ->where('current_stock', '>', 0);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();
        // Attach resolved prices (location → branch → default) for display
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);

        // Get last movement date for each item
        $itemsWithLastMovement = $items->map(function ($item) {
            $lastMovement = InventoryMovement::where('item_id', $item->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $daysInactive = $lastMovement
                ? Carbon::parse($lastMovement->created_at)->diffInDays(Carbon::now())
                : 999; // Very high number if no movements

            $status = 'active';
            if ($daysInactive > 180) {
                $status = 'obsolete';
            } elseif ($daysInactive > 90) {
                $status = 'slow';
            }

            // Use resolved cost price for valuation
            $resolvedCost = $item->resolved_cost_price ?? $item->cost_price;

            return [
                'item' => $item,
                'last_movement_date' => $lastMovement ? $lastMovement->created_at : null,
                'days_inactive' => $daysInactive,
                'status' => $status,
                'value' => $item->current_stock * $resolvedCost
            ];
        });

        $categories = InventoryCategory::where('company_id', auth()->user()->company_id)->get();

        return view('inventory.reports.aging-stock', compact(
            'itemsWithLastMovement', 'categories', 'thresholdDays'
        ));
    }

    /**
     * Reorder Report
     */
    public function reorderReport(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        // Note: location_id filter removed as inventory_items table doesn't have location_id column

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();
        // Attach resolved prices (location → branch → default) for display
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);
        $stockService = new InventoryStockService();

        // Filter items that need reordering
        $reorderItems = $items->filter(function ($item) use ($stockService) {
            $currentStock = $stockService->getItemTotalStock($item->id);
            return $currentStock <= $item->reorder_level;
        })->map(function ($item) use ($stockService) {
            $currentStock = $stockService->getItemTotalStock($item->id);
            $suggestedQty = max(
                $item->reorder_level - $currentStock + 10, // Add buffer
                $item->reorder_level // Use reorder_level as suggested quantity
            );

            return [
                'item' => $item,
                'available' => $currentStock,
                'suggested_qty' => $suggestedQty
            ];
        });

        $locations = InventoryLocation::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })
            ->get();
        $categories = InventoryCategory::where('company_id', auth()->user()->company_id)->get();

        return view('inventory.reports.reorder', compact(
            'reorderItems', 'locations', 'categories'
        ));
    }

    /**
     * Export Reorder Report to Excel
     */
    public function reorderReportExportExcel(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();
        // Attach resolved prices (location → branch → default) for display
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);
        $stockService = new InventoryStockService();

        $reorderItems = $items->filter(function ($item) use ($stockService) {
            $currentStock = $stockService->getItemTotalStock($item->id);
            return $currentStock <= $item->reorder_level;
        })->map(function ($item) use ($stockService) {
            $currentStock = $stockService->getItemTotalStock($item->id);
            $suggestedQty = max(
                $item->reorder_level - $currentStock + 10,
                $item->reorder_level
            );

            return [
                'item_code' => $item->code,
                'item_name' => $item->name,
                'category' => $item->category->name ?? 'N/A',
                'current_stock' => $currentStock,
                'reorder_level' => $item->reorder_level,
                'suggested_qty' => $suggestedQty,
                'unit_of_measure' => $item->unit_of_measure,
                'cost_price' => $item->resolved_cost_price ?? $item->cost_price,
                'unit_price' => $item->resolved_unit_price ?? $item->unit_price,
            ];
        });

        return \Excel::download(new \App\Exports\ReorderReportExport($reorderItems), 'reorder-report-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Reorder Report to PDF
     */
    public function reorderReportExportPdf(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();
        // Attach resolved prices (location → branch → default) for display
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);
        $stockService = new InventoryStockService();

        $reorderItems = $items->filter(function ($item) use ($stockService) {
            $currentStock = $stockService->getItemTotalStock($item->id);
            return $currentStock <= $item->reorder_level;
        })->map(function ($item) use ($stockService) {
            $currentStock = $stockService->getItemTotalStock($item->id);
            $suggestedQty = max(
                $item->reorder_level - $currentStock + 10,
                $item->reorder_level
            );

            return [
                'item' => $item,
                'available' => $currentStock,
                'suggested_qty' => $suggestedQty
            ];
        });

        $company = auth()->user()->company;
        $category = $request->filled('category_id') ? InventoryCategory::find($request->category_id) : null;
        
        // Calculate totals
        $totalItems = $reorderItems->count();
        $totalSuggestedQty = $reorderItems->sum('suggested_qty');
        $totalSuggestedValue = $reorderItems->sum(function($item) {
            return $item['suggested_qty'] * ($item['item']->resolved_cost_price ?? $item['item']->cost_price ?? 0);
        });
        
        $filename = 'Reorder_Report_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.reports.reorder-pdf', [
            'reorderItems' => $reorderItems,
            'company' => $company,
            'category' => $category,
            'totalItems' => $totalItems,
            'totalSuggestedQty' => $totalSuggestedQty,
            'totalSuggestedValue' => $totalSuggestedValue,
            'generatedAt' => now()
        ]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download($filename);
    }

    /**
     * Over/Understock Report
     */
    public function overUnderstock(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        // Note: location_id filter removed as inventory_items table doesn't have location_id column

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();
        $stockService = new InventoryStockService();

        $stockAnalysis = $items->map(function ($item) use ($stockService) {
            $currentStock = $stockService->getItemTotalStock($item->id);
            $status = 'ok';
            $variance = 0;

            if ($currentStock <= 0) {
                $status = 'out_of_stock';
                $variance = $currentStock - ($item->minimum_stock ?? 0);
            } elseif ($currentStock < $item->minimum_stock) {
                $status = 'understock';
                $variance = $currentStock - $item->minimum_stock;
            } elseif ($item->maximum_stock > 0 && $currentStock > $item->maximum_stock) {
                $status = 'overstock';
                $variance = $currentStock - $item->maximum_stock;
            }

            return [
                'item' => $item,
                'status' => $status,
                'variance' => $variance,
                'value' => $currentStock * $item->cost_price
            ];
        });

        // Apply status filter if provided
        if ($request->filled('status')) {
            $allowedStatuses = ['ok', 'understock', 'overstock', 'out_of_stock'];
            $statusFilter = strtolower($request->get('status'));
            if (in_array($statusFilter, $allowedStatuses, true)) {
                $stockAnalysis = $stockAnalysis->filter(function ($row) use ($statusFilter) {
                    return ($row['status'] ?? null) === $statusFilter;
                })->values();
            }
        }

        $locations = InventoryLocation::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })
            ->get();
        $categories = InventoryCategory::where('company_id', auth()->user()->company_id)->get();
        $company = auth()->user()->company;

        return view('inventory.reports.over-understock', compact(
            'stockAnalysis', 'locations', 'categories', 'company'
        ));
    }

    /**
     * Export Over/Understock Report to Excel
     */
    public function overUnderstockExportExcel(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();
        $stockService = new InventoryStockService();

        $stockAnalysis = $items->map(function ($item) use ($stockService) {
            $currentStock = $stockService->getItemTotalStock($item->id);
            $status = 'ok';
            $variance = 0;

            if ($currentStock <= 0) {
                $status = 'out_of_stock';
                $variance = $currentStock - ($item->minimum_stock ?? 0);
            } elseif ($currentStock < $item->minimum_stock) {
                $status = 'understock';
                $variance = $currentStock - $item->minimum_stock;
            } elseif ($item->maximum_stock > 0 && $currentStock > $item->maximum_stock) {
                $status = 'overstock';
                $variance = $currentStock - $item->maximum_stock;
            }

            return [
                'item_code' => $item->code,
                'item_name' => $item->name,
                'category' => $item->category->name ?? 'N/A',
                'current_stock' => $currentStock,
                'minimum_stock' => $item->minimum_stock,
                'maximum_stock' => $item->maximum_stock,
                'status' => $status,
                'variance' => $variance,
                'value' => $currentStock * $item->cost_price,
                'unit_of_measure' => $item->unit_of_measure,
                'cost_price' => $item->cost_price,
            ];
        });

        // Apply status filter if provided
        if ($request->filled('status')) {
            $allowedStatuses = ['ok', 'understock', 'overstock', 'out_of_stock'];
            $statusFilter = strtolower($request->get('status'));
            if (in_array($statusFilter, $allowedStatuses, true)) {
                $stockAnalysis = $stockAnalysis->filter(function ($row) use ($statusFilter) {
                    return ($row['status'] ?? null) === $statusFilter;
                })->values();
            }
        }

        return \Excel::download(new \App\Exports\OverUnderstockReportExport($stockAnalysis), 'over-understock-report-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Over/Understock Report to PDF
     */
    public function overUnderstockExportPdf(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();
        $stockService = new InventoryStockService();

        $stockAnalysis = $items->map(function ($item) use ($stockService) {
            $currentStock = $stockService->getItemTotalStock($item->id);
            $status = 'ok';
            $variance = 0;

            if ($currentStock <= 0) {
                $status = 'out_of_stock';
                $variance = $currentStock - ($item->minimum_stock ?? 0);
            } elseif ($currentStock < $item->minimum_stock) {
                $status = 'understock';
                $variance = $currentStock - $item->minimum_stock;
            } elseif ($item->maximum_stock > 0 && $currentStock > $item->maximum_stock) {
                $status = 'overstock';
                $variance = $currentStock - $item->maximum_stock;
            }

            return [
                'item' => $item,
                'current_stock' => $currentStock,
                'status' => $status,
                'variance' => $variance,
                'value' => $currentStock * ($item->cost_price ?? 0)
            ];
        });

        // Apply status filter if provided
        if ($request->filled('status')) {
            $allowedStatuses = ['ok', 'understock', 'overstock', 'out_of_stock'];
            $statusFilter = strtolower($request->get('status'));
            if (in_array($statusFilter, $allowedStatuses, true)) {
                $stockAnalysis = $stockAnalysis->filter(function ($row) use ($statusFilter) {
                    return ($row['status'] ?? null) === $statusFilter;
                })->values();
            }
        }

        $company = auth()->user()->company;
        $category = $request->filled('category_id') ? InventoryCategory::find($request->category_id) : null;
        $selectedStatus = $request->filled('status') ? $request->status : null;
        
        // Calculate summary statistics
        $totalItems = $stockAnalysis->count();
        $okItems = $stockAnalysis->where('status', 'ok')->count();
        $understockItems = $stockAnalysis->where('status', 'understock')->count();
        $overstockItems = $stockAnalysis->where('status', 'overstock')->count();
        $outOfStockItems = $stockAnalysis->where('status', 'out_of_stock')->count();
        $totalValue = $stockAnalysis->sum('value');
        
        $filename = 'Over_Understock_Report_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.reports.over-understock-pdf', [
            'stockAnalysis' => $stockAnalysis,
            'company' => $company,
            'category' => $category,
            'selectedStatus' => $selectedStatus,
            'totalItems' => $totalItems,
            'okItems' => $okItems,
            'understockItems' => $understockItems,
            'overstockItems' => $overstockItems,
            'outOfStockItems' => $outOfStockItems,
            'totalValue' => $totalValue,
            'generatedAt' => now()
        ]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download($filename);
    }

    /**
     * Item Ledger (Kardex) Report
     */
    public function itemLedger(Request $request)
    {
        $itemId = $request->get('item_id');
        $locationId = $request->get('location_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$itemId) {
            return view('inventory.reports.item-ledger', [
                'items' => InventoryItem::where('company_id', auth()->user()->company_id)->get(),
                'locations' => InventoryLocation::where('company_id', auth()->user()->company_id)
                    ->where('is_active', true)
                    ->when(auth()->user()->branch_id, function($query) {
                        return $query->where('branch_id', auth()->user()->branch_id);
                    })
                    ->get(),
                'ledgerEntries' => [],
                'item' => null
            ]);
        }

        // Temporary debugging - remove after fixing
        \Log::info('=== ITEM LEDGER DEBUG ===');
        \Log::info('Requested item_id: ' . $itemId);
        \Log::info('All request data: ' . json_encode($request->all()));
        
        $item = InventoryItem::with(['category'])->findOrFail($itemId);
        
        \Log::info('Found item: ID=' . $item->id . ', Code=' . $item->code . ', Name=' . $item->name);

        $query = InventoryMovement::where('item_id', $itemId);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $movements = $query->orderBy('created_at')->get();

        // Calculate running balance
        $runningQty = 0;
        $runningValue = 0;
        $ledgerEntries = [];

        foreach ($movements as $movement) {
            // Determine if this is an "in" movement (increases stock)
            $isInMovement = in_array($movement->movement_type, [
                'in', 'adjustment_in', 'purchased', 'opening_balance', 'return_in', 'transfer_in'
            ]);
            
            // Determine if this is an "out" movement (decreases stock)
            $isOutMovement = in_array($movement->movement_type, [
                'out', 'adjustment_out', 'sold', 'return_out', 'transfer_out', 'waste', 'damage'
            ]);

            // Calculate weighted average cost BEFORE this movement (for out movements)
            $weightedAvgBefore = ($runningQty > 0) ? ($runningValue / $runningQty) : 0;
            
            // Determine display unit cost
            $displayUnitCost = $movement->unit_cost;
            
            if ($isInMovement) {
                $runningQty += $movement->quantity;
                $runningValue += $movement->total_cost;
                // For in movements, use the movement's unit_cost (purchase/adjustment cost)
                $displayUnitCost = $movement->unit_cost;
            } elseif ($isOutMovement) {
                // For out movements, use the weighted average cost BEFORE the movement
                // This ensures we show the correct average cost used at the time of sale
                $displayUnitCost = $weightedAvgBefore > 0 ? $weightedAvgBefore : $movement->unit_cost;
                
                // Recalculate running value using the correct unit cost
                $correctTotalCost = $movement->quantity * $displayUnitCost;
                $runningQty -= $movement->quantity;
                $runningValue -= $correctTotalCost;
            }

            // Calculate weighted average AFTER this movement
            $weightedAvgAfter = ($runningQty > 0) ? ($runningValue / $runningQty) : 0;

            $ledgerEntries[] = [
                'movement' => $movement,
                'running_qty' => $runningQty,
                'running_value' => $runningValue,
                'unit_cost' => $displayUnitCost, // Use calculated weighted average for sales
                'avg_unit_cost' => $weightedAvgAfter
            ];
        }

        $items = InventoryItem::where('company_id', auth()->user()->company_id)->get();
        $locations = InventoryLocation::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })
            ->get();

        return view('inventory.reports.item-ledger', compact(
            'item', 'ledgerEntries', 'items', 'locations'
        ));
    }

    /**
     * Export Item Ledger Report to Excel
     */
    public function itemLedgerExportExcel(Request $request)
    {
        $itemId = $request->get('item_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$itemId) {
            return redirect()->route('inventory.reports.item-ledger')
                ->with('error', 'Please select an item to export the ledger.');
        }

        $item = InventoryItem::with(['category'])->findOrFail($itemId);

        $query = InventoryMovement::where('item_id', $itemId);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $movements = $query->orderBy('created_at')->get();

        // Calculate running balance
        $runningQty = 0;
        $runningValue = 0;
        $ledgerEntries = [];

        foreach ($movements as $movement) {
            // Determine if this is an "in" movement (increases stock)
            $isInMovement = in_array($movement->movement_type, [
                'in', 'adjustment_in', 'purchased', 'opening_balance', 'return_in', 'transfer_in'
            ]);
            
            // Determine if this is an "out" movement (decreases stock)
            $isOutMovement = in_array($movement->movement_type, [
                'out', 'adjustment_out', 'sold', 'return_out', 'transfer_out', 'waste', 'damage'
            ]);

            // Calculate weighted average cost BEFORE this movement (for out movements)
            $weightedAvgBefore = ($runningQty > 0) ? ($runningValue / $runningQty) : 0;
            
            // Determine display unit cost
            $displayUnitCost = $movement->unit_cost;
            
            if ($isInMovement) {
                $runningQty += $movement->quantity;
                $runningValue += $movement->total_cost;
                // For in movements, use the movement's unit_cost (purchase/adjustment cost)
                $displayUnitCost = $movement->unit_cost;
            } elseif ($isOutMovement) {
                // For out movements, use the weighted average cost BEFORE the movement
                // This ensures we show the correct average cost used at the time of sale
                $displayUnitCost = $weightedAvgBefore > 0 ? $weightedAvgBefore : $movement->unit_cost;
                
                // Recalculate running value using the correct unit cost
                $correctTotalCost = $movement->quantity * $displayUnitCost;
                $runningQty -= $movement->quantity;
                $runningValue -= $correctTotalCost;
            }

            // Calculate weighted average AFTER this movement
            $weightedAvgAfter = ($runningQty > 0) ? ($runningValue / $runningQty) : 0;

            $ledgerEntries[] = [
                'date' => $movement->created_at->format('Y-m-d H:i'),
                'reference' => $movement->reference_type . ' #' . $movement->reference_id,
                'type' => ucfirst(str_replace('_', ' ', $movement->movement_type)),
                'in_qty' => $isInMovement ? $movement->quantity : 0,
                'out_qty' => $isOutMovement ? $movement->quantity : 0,
                'unit_cost' => $displayUnitCost, // Use calculated weighted average for sales
                'running_qty' => $runningQty,
                'running_value' => $runningValue,
                'avg_unit_cost' => $weightedAvgAfter
            ];
        }

        return \Excel::download(new \App\Exports\ItemLedgerReportExport($ledgerEntries, $item), 'item-ledger-report-' . $item->code . '-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Item Ledger Report to PDF
     */
    public function itemLedgerExportPdf(Request $request)
    {
        $itemId = $request->get('item_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$itemId) {
            return redirect()->route('inventory.reports.item-ledger')
                ->with('error', 'Please select an item to export the ledger.');
        }

        $item = InventoryItem::with(['category'])->findOrFail($itemId);

        $query = InventoryMovement::where('item_id', $itemId);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $movements = $query->orderBy('created_at')->get();

        // Calculate running balance
        $runningQty = 0;
        $runningValue = 0;
        $ledgerEntries = [];

        foreach ($movements as $movement) {
            // Determine if this is an "in" movement (increases stock)
            $isInMovement = in_array($movement->movement_type, [
                'in', 'adjustment_in', 'purchased', 'opening_balance', 'return_in', 'transfer_in'
            ]);
            
            // Determine if this is an "out" movement (decreases stock)
            $isOutMovement = in_array($movement->movement_type, [
                'out', 'adjustment_out', 'sold', 'return_out', 'transfer_out', 'waste', 'damage'
            ]);

            // Calculate weighted average cost BEFORE this movement (for out movements)
            $weightedAvgBefore = ($runningQty > 0) ? ($runningValue / $runningQty) : 0;
            
            // Determine display unit cost
            $displayUnitCost = $movement->unit_cost;
            
            if ($isInMovement) {
                $runningQty += $movement->quantity;
                $runningValue += $movement->total_cost;
                // For in movements, use the movement's unit_cost (purchase/adjustment cost)
                $displayUnitCost = $movement->unit_cost;
            } elseif ($isOutMovement) {
                // For out movements, use the weighted average cost BEFORE the movement
                // This ensures we show the correct average cost used at the time of sale
                $displayUnitCost = $weightedAvgBefore > 0 ? $weightedAvgBefore : $movement->unit_cost;
                
                // Recalculate running value using the correct unit cost
                $correctTotalCost = $movement->quantity * $displayUnitCost;
                $runningQty -= $movement->quantity;
                $runningValue -= $correctTotalCost;
            }

            // Calculate weighted average AFTER this movement
            $weightedAvgAfter = ($runningQty > 0) ? ($runningValue / $runningQty) : 0;

            $ledgerEntries[] = [
                'movement' => $movement,
                'running_qty' => $runningQty,
                'running_value' => $runningValue,
                'unit_cost' => $displayUnitCost, // Use calculated weighted average for sales
                'avg_unit_cost' => $weightedAvgAfter
            ];
        }

        $company = auth()->user()->company;
        
        // Calculate totals
        $totalInQty = collect($ledgerEntries)->sum(function($entry) {
            $movement = $entry['movement'];
            $isIn = in_array($movement->movement_type, [
                'in', 'adjustment_in', 'purchased', 'opening_balance', 'return_in', 'transfer_in'
            ]);
            return $isIn ? $movement->quantity : 0;
        });
        
        $totalOutQty = collect($ledgerEntries)->sum(function($entry) {
            $movement = $entry['movement'];
            $isOut = in_array($movement->movement_type, [
                'out', 'adjustment_out', 'sold', 'return_out', 'transfer_out', 'waste', 'damage'
            ]);
            return $isOut ? $movement->quantity : 0;
        });
        
        $totalEntries = count($ledgerEntries);
        $finalRunningQty = !empty($ledgerEntries) ? end($ledgerEntries)['running_qty'] : 0;
        $finalRunningValue = !empty($ledgerEntries) ? end($ledgerEntries)['running_value'] : 0;
        
        $dateFromCarbon = $dateFrom ? Carbon::parse($dateFrom) : null;
        $dateToCarbon = $dateTo ? Carbon::parse($dateTo) : null;
        
        $filename = 'Item_Ledger_Report_' . $item->code . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.reports.item-ledger-pdf', [
            'item' => $item,
            'ledgerEntries' => $ledgerEntries,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'dateFromCarbon' => $dateFromCarbon,
            'dateToCarbon' => $dateToCarbon,
            'company' => $company,
            'totalEntries' => $totalEntries,
            'totalInQty' => $totalInQty,
            'totalOutQty' => $totalOutQty,
            'finalRunningQty' => $finalRunningQty,
            'finalRunningValue' => $finalRunningValue,
            'generatedAt' => now()
        ]);

        $pdf->setPaper('A4', 'landscape');
        return $pdf->download($filename);
    }

    /**
     * Cost Changes Report
     */
    public function costChanges(Request $request)
    {
        // Get system costing method
        $systemCostMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';
        
        // Get cost changes from movements (for average cost method)
        $movementQuery = InventoryMovement::with(['item', 'user', 'location'])
            ->whereHas('item', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->whereIn('movement_type', ['adjustment_in', 'adjustment_out', 'opening_balance', 'purchased']);

        // Get FIFO layer changes
        $layerQuery = \App\Models\InventoryCostLayer::with(['item'])
            ->whereHas('item', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply filters to both queries
        if ($request->filled('item_id')) {
            $movementQuery->where('item_id', $request->item_id);
            $layerQuery->where('item_id', $request->item_id);
        }

        if ($request->filled('date_from')) {
            $movementQuery->whereDate('movement_date', '>=', $request->date_from);
            $layerQuery->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $movementQuery->whereDate('movement_date', '<=', $request->date_to);
            $layerQuery->whereDate('transaction_date', '<=', $request->date_to);
        }

        $movements = $movementQuery->orderBy('movement_date', 'desc')->get();
        $layers = $layerQuery->orderBy('transaction_date', 'desc')->get();

        // Combine and format the data
        $costChanges = collect();
        
        // Add movement-based cost changes
        foreach ($movements as $movement) {
            $costChanges->push([
                'type' => 'movement',
                'date' => $movement->movement_date ?: $movement->created_at,
                'item' => $movement->item,
                'location' => $movement->location,
                'user' => $movement->user,
                'movement_type' => $movement->movement_type,
                'quantity' => $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $movement->total_cost,
                'reference' => $movement->reference,
                'notes' => $movement->notes,
                'reason' => $this->getCostChangeReason($movement->movement_type, $movement->reference),
                'cost_method' => 'Average Cost'
            ]);
        }

        // Add FIFO layer changes
        foreach ($layers as $layer) {
            $costChanges->push([
                'type' => 'layer',
                'date' => $layer->transaction_date,
                'item' => $layer->item,
                'location' => null, // Layers don't have location
                'user' => null, // Layers don't have user
                'movement_type' => $layer->transaction_type,
                'quantity' => $layer->quantity,
                'unit_cost' => $layer->unit_cost,
                'total_cost' => $layer->total_cost,
                'reference' => $layer->reference,
                'notes' => $layer->is_consumed ? 'Layer Consumed' : 'Layer Created',
                'reason' => $this->getCostChangeReason($layer->transaction_type, $layer->reference),
                'cost_method' => 'FIFO',
                'remaining_quantity' => $layer->remaining_quantity,
                'is_consumed' => $layer->is_consumed
            ]);
        }

        // Sort by date descending
        $costChanges = $costChanges->sortByDesc('date')->values();

        // Calculate summary statistics
        $totalChanges = $costChanges->count();
        $totalValue = $costChanges->sum('total_cost');
        $averageCostChanges = $costChanges->where('cost_method', 'Average Cost')->count();
        $fifoLayerChanges = $costChanges->where('cost_method', 'FIFO')->count();

        // Get filter options
        $items = InventoryItem::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

        return view('inventory.reports.cost-changes', compact(
            'costChanges', 'items', 'systemCostMethod', 'totalChanges', 'totalValue', 
            'averageCostChanges', 'fifoLayerChanges'
        ));
    }

    /**
     * Export Cost Changes Report to Excel
     */
    public function costChangesExportExcel(Request $request)
    {
        // Get the same data as the main report
        $movementQuery = InventoryMovement::with(['item', 'user', 'location'])
            ->whereHas('item', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->whereIn('movement_type', ['adjustment_in', 'adjustment_out', 'opening_balance', 'purchased']);

        $layerQuery = \App\Models\InventoryCostLayer::with(['item'])
            ->whereHas('item', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply filters
        if ($request->filled('item_id')) {
            $movementQuery->where('item_id', $request->item_id);
            $layerQuery->where('item_id', $request->item_id);
        }

        if ($request->filled('date_from')) {
            $movementQuery->whereDate('movement_date', '>=', $request->date_from);
            $layerQuery->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $movementQuery->whereDate('movement_date', '<=', $request->date_to);
            $layerQuery->whereDate('transaction_date', '<=', $request->date_to);
        }

        $movements = $movementQuery->orderBy('movement_date', 'desc')->get();
        $layers = $layerQuery->orderBy('transaction_date', 'desc')->get();

        // Combine and format the data
        $costChanges = collect();
        
        foreach ($movements as $movement) {
            $costChanges->push([
                'type' => 'movement',
                'date' => $movement->movement_date ?: $movement->created_at,
                'item' => $movement->item,
                'location' => $movement->location,
                'user' => $movement->user,
                'movement_type' => $movement->movement_type,
                'quantity' => $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $movement->total_cost,
                'reference' => $movement->reference,
                'notes' => $movement->notes,
                'reason' => $this->getCostChangeReason($movement->movement_type, $movement->reference),
                'cost_method' => 'Average Cost'
            ]);
        }

        foreach ($layers as $layer) {
            $costChanges->push([
                'type' => 'layer',
                'date' => $layer->transaction_date,
                'item' => $layer->item,
                'location' => null,
                'user' => null,
                'movement_type' => $layer->transaction_type,
                'quantity' => $layer->quantity,
                'unit_cost' => $layer->unit_cost,
                'total_cost' => $layer->total_cost,
                'reference' => $layer->reference,
                'notes' => $layer->is_consumed ? 'Layer Consumed' : 'Layer Created',
                'reason' => $this->getCostChangeReason($layer->transaction_type, $layer->reference),
                'cost_method' => 'FIFO',
                'remaining_quantity' => $layer->remaining_quantity,
                'is_consumed' => $layer->is_consumed
            ]);
        }

        $costChanges = $costChanges->sortByDesc('date')->values();

        $company = auth()->user()->company;
        $filename = 'Cost_Changes_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CostChangesExport($costChanges, $company),
            $filename
        );
    }

    /**
     * Export Cost Changes Report to PDF
     */
    public function costChangesExportPdf(Request $request)
    {
        // Get the same data as the main report
        $movementQuery = InventoryMovement::with(['item', 'user', 'location'])
            ->whereHas('item', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->whereIn('movement_type', ['adjustment_in', 'adjustment_out', 'opening_balance', 'purchased']);

        $layerQuery = \App\Models\InventoryCostLayer::with(['item'])
            ->whereHas('item', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply filters
        if ($request->filled('item_id')) {
            $movementQuery->where('item_id', $request->item_id);
            $layerQuery->where('item_id', $request->item_id);
        }

        if ($request->filled('date_from')) {
            $movementQuery->whereDate('movement_date', '>=', $request->date_from);
            $layerQuery->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $movementQuery->whereDate('movement_date', '<=', $request->date_to);
            $layerQuery->whereDate('transaction_date', '<=', $request->date_to);
        }

        $movements = $movementQuery->orderBy('movement_date', 'desc')->get();
        $layers = $layerQuery->orderBy('transaction_date', 'desc')->get();

        // Combine and format the data
        $costChanges = collect();
        
        foreach ($movements as $movement) {
            $costChanges->push([
                'type' => 'movement',
                'date' => $movement->movement_date ?: $movement->created_at,
                'item' => $movement->item,
                'location' => $movement->location,
                'user' => $movement->user,
                'movement_type' => $movement->movement_type,
                'quantity' => $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $movement->total_cost,
                'reference' => $movement->reference,
                'notes' => $movement->notes,
                'reason' => $this->getCostChangeReason($movement->movement_type, $movement->reference),
                'cost_method' => 'Average Cost'
            ]);
        }

        foreach ($layers as $layer) {
            $costChanges->push([
                'type' => 'layer',
                'date' => $layer->transaction_date,
                'item' => $layer->item,
                'location' => null,
                'user' => null,
                'movement_type' => $layer->transaction_type,
                'quantity' => $layer->quantity,
                'unit_cost' => $layer->unit_cost,
                'total_cost' => $layer->total_cost,
                'reference' => $layer->reference,
                'notes' => $layer->is_consumed ? 'Layer Consumed' : 'Layer Created',
                'reason' => $this->getCostChangeReason($layer->transaction_type, $layer->reference),
                'cost_method' => 'FIFO',
                'remaining_quantity' => $layer->remaining_quantity,
                'is_consumed' => $layer->is_consumed
            ]);
        }

        $costChanges = $costChanges->sortByDesc('date')->values();

        // Calculate summary statistics
        $totalChanges = $costChanges->count();
        $totalValue = $costChanges->sum('total_cost');
        $averageCostChanges = $costChanges->where('cost_method', 'Average Cost')->count();
        $fifoLayerChanges = $costChanges->where('cost_method', 'FIFO')->count();

        $company = auth()->user()->company;
        $filename = 'Cost_Changes_Report_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('inventory.reports.cost-changes-pdf', [
            'costChanges' => $costChanges,
            'totalChanges' => $totalChanges,
            'totalValue' => $totalValue,
            'averageCostChanges' => $averageCostChanges,
            'fifoLayerChanges' => $fifoLayerChanges,
            'company' => $company,
            'generatedAt' => now()
        ]);

        return $pdf->download($filename);
    }

    private function getCostChangeReason($type, $reference)
    {
        return match($type) {
            'opening_balance' => 'Opening Balance Entry',
            'purchased' => 'Purchase Transaction',
            'adjustment_in' => 'Stock Adjustment (Increase)',
            'adjustment_out' => 'Stock Adjustment (Decrease)',
            'sale' => 'Sale Transaction (FIFO Consumption)',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            default => 'Cost Change - ' . ucfirst(str_replace('_', ' ', $type))
        };
    }

    /**
     * Stock Take Variance Report
     */
    public function stockTakeVariance(Request $request)
    {
        // TODO: Implement when StockTake model is available
        $stockTakes = collect();

        $locations = InventoryLocation::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })
            ->get();

        return view('inventory.reports.stock-take-variance', compact(
            'stockTakes', 'locations'
        ));
    }

    /**
     * Location Bin Report
     */
    public function locationBin(Request $request)
    {
        $locationId = $request->get('location_id');

        if (!$locationId) {
            $locations = InventoryLocation::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })
            ->get();
            return view('inventory.reports.location-bin', [
                'locations' => $locations,
                'binReport' => collect(),
                'location' => null
            ]);
        }

        $location = InventoryLocation::findOrFail($locationId);
        
        $items = InventoryItem::whereHas('movements', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            })
            ->where('company_id', auth()->user()->company_id)
            ->get();

        $binReport = $items->map(function ($item) {
            $utilization = $item->maximum_stock > 0 
                ? ($item->current_stock / $item->maximum_stock) * 100 
                : 0;

            $status = 'normal';
            if ($item->current_stock == 0) {
                $status = 'empty';
            } elseif ($utilization > 90) {
                $status = 'overfull';
            }

            return [
                'item' => $item,
                'utilization' => $utilization,
                'status' => $status
            ];
        });

        $locations = InventoryLocation::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })
            ->get();

        return view('inventory.reports.location-bin', compact(
            'location', 'binReport', 'locations'
        ));
    }

    /**
     * Category Brand Mix Report
     */
    public function categoryBrandMix(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();

        // Group by category
        $categoryMix = $items->groupBy('category.name')->map(function ($categoryItems) {
            $totalQty = $categoryItems->sum('current_stock');
            $totalValue = $categoryItems->sum(function ($item) {
                return $item->current_stock * $item->cost_price;
            });

            return [
                'items_count' => $categoryItems->count(),
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
                'items' => $categoryItems
            ];
        });

        $grandTotalQty = $items->sum('current_stock');
        $grandTotalValue = $items->sum(function ($item) {
            return $item->current_stock * $item->cost_price;
        });

        // Calculate percentages
        $categoryMix = $categoryMix->map(function ($category) use ($grandTotalQty, $grandTotalValue) {
            $category['qty_percentage'] = $grandTotalQty > 0 ? ($category['total_qty'] / $grandTotalQty) * 100 : 0;
            $category['value_percentage'] = $grandTotalValue > 0 ? ($category['total_value'] / $grandTotalValue) * 100 : 0;
            return $category;
        });

        $categories = InventoryCategory::where('company_id', auth()->user()->company_id)->get();

        return view('inventory.reports.category-brand-mix', compact(
            'categoryMix', 'categories', 'grandTotalQty', 'grandTotalValue'
        ));
    }

    /**
     * Export Category Brand Mix Report to Excel
     */
    public function categoryBrandMixExportExcel(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();

        $categoryMix = $items->groupBy('category.name')->map(function ($categoryItems) {
            $totalQty = $categoryItems->sum('current_stock');
            $totalValue = $categoryItems->sum(function ($item) {
                return $item->current_stock * $item->cost_price;
            });

            return [
                'category_name' => $categoryItems->first()->category->name ?? 'N/A',
                'items_count' => $categoryItems->count(),
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
                'items' => $categoryItems->map(function ($item) {
                    return [
                        'item_code' => $item->code,
                        'item_name' => $item->name,
                        'current_stock' => $item->current_stock,
                        'cost_price' => $item->cost_price,
                        'unit_of_measure' => $item->unit_of_measure,
                        'value' => $item->current_stock * $item->cost_price,
                    ];
                })
            ];
        });

        $grandTotalQty = $items->sum('current_stock');
        $grandTotalValue = $items->sum(function ($item) {
            return $item->current_stock * $item->cost_price;
        });

        $categoryMix = $categoryMix->map(function ($category) use ($grandTotalQty, $grandTotalValue) {
            $category['qty_percentage'] = $grandTotalQty > 0 ? ($category['total_qty'] / $grandTotalQty) * 100 : 0;
            $category['value_percentage'] = $grandTotalValue > 0 ? ($category['total_value'] / $grandTotalValue) * 100 : 0;
            return $category;
        });

        return \Excel::download(new \App\Exports\CategoryBrandMixReportExport($categoryMix, $grandTotalQty, $grandTotalValue), 'category-brand-mix-report-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Category Brand Mix Report to PDF
     */
    public function categoryBrandMixExportPdf(Request $request)
    {
        $query = InventoryItem::with(['category'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->get();

        $categoryMix = $items->groupBy('category.name')->map(function ($categoryItems) {
            $totalQty = $categoryItems->sum('current_stock');
            $totalValue = $categoryItems->sum(function ($item) {
                return $item->current_stock * $item->cost_price;
            });

            return [
                'items_count' => $categoryItems->count(),
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
                'items' => $categoryItems
            ];
        });

        $grandTotalQty = $items->sum('current_stock');
        $grandTotalValue = $items->sum(function ($item) {
            return $item->current_stock * $item->cost_price;
        });

        $categoryMix = $categoryMix->map(function ($category) use ($grandTotalQty, $grandTotalValue) {
            $category['qty_percentage'] = $grandTotalQty > 0 ? ($category['total_qty'] / $grandTotalQty) * 100 : 0;
            $category['value_percentage'] = $grandTotalValue > 0 ? ($category['total_value'] / $grandTotalValue) * 100 : 0;
            return $category;
        });

        $pdf = \PDF::loadView('inventory.reports.category-brand-mix-pdf', compact('categoryMix', 'grandTotalQty', 'grandTotalValue'));
        return $pdf->stream('category-brand-mix-report-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Profit Margin Report
     */
    public function profitMargin(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

        // Get sales data
        $salesQuery = SalesInvoiceItem::with(['salesInvoice', 'inventoryItem'])
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo) {
                $q->where('company_id', auth()->user()->company_id)
                  ->when(auth()->user()->branch_id, function($query) {
                      return $query->where('branch_id', auth()->user()->branch_id);
                  })
                  ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
            });

        if ($request->filled('item_id')) {
            $salesQuery->where('inventory_item_id', $request->item_id);
        }

        if ($request->filled('customer_id')) {
            $salesQuery->whereHas('salesInvoice', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        $salesItems = $salesQuery->get();

        // Get branch/location context for cost resolution
        $branchId = session('branch_id') ?? auth()->user()->branch_id;
        $locationId = session('location_id');

        // Group by item and calculate margins using resolved cost prices
        $profitData = $salesItems->groupBy('inventory_item_id')->map(function ($itemSales) use ($branchId, $locationId) {
            $item = $itemSales->first()->inventoryItem;
            $soldQty = $itemSales->sum('quantity');
            $salesRevenue = $itemSales->sum('line_total');
            $costOfGoods = $itemSales->sum(function ($sale) use ($branchId, $locationId) {
                // Use resolved cost price (location → branch → default)
                $costPrice = $sale->inventoryItem ? $sale->inventoryItem->getCostPriceForBranchOrLocation($branchId, $locationId) : 0;
                return $sale->quantity * $costPrice;
            });
            $grossMargin = $salesRevenue - $costOfGoods;
            $grossMarginPercent = $salesRevenue > 0 ? ($grossMargin / $salesRevenue) * 100 : 0;

            return [
                'item' => $item,
                'sold_qty' => $soldQty,
                'sales_revenue' => $salesRevenue,
                'cost_of_goods' => $costOfGoods,
                'gross_margin' => $grossMargin,
                'gross_margin_percent' => $grossMarginPercent
            ];
        });

        $items = InventoryItem::where('company_id', auth()->user()->company_id)->get();
        $customers = Customer::where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, function($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })
            ->get();

        return view('inventory.reports.profit-margin', compact(
            'profitData', 'items', 'customers', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Export Profit Margin Report to Excel
     */
    public function profitMarginExportExcel(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

        $salesQuery = SalesInvoiceItem::with(['salesInvoice', 'inventoryItem'])
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo) {
                $q->where('company_id', auth()->user()->company_id)
                  ->when(auth()->user()->branch_id, function($query) {
                      return $query->where('branch_id', auth()->user()->branch_id);
                  })
                  ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
            });

        if ($request->filled('item_id')) {
            $salesQuery->where('inventory_item_id', $request->item_id);
        }

        if ($request->filled('customer_id')) {
            $salesQuery->whereHas('salesInvoice', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        $salesItems = $salesQuery->get();

        // Get branch/location context for cost resolution
        $branchId = session('branch_id') ?? auth()->user()->branch_id;
        $locationId = session('location_id');

        $profitData = $salesItems->groupBy('inventory_item_id')->map(function ($itemSales) use ($branchId, $locationId) {
            $item = $itemSales->first()->inventoryItem;
            $soldQty = $itemSales->sum('quantity');
            $salesRevenue = $itemSales->sum('line_total');
            $costOfGoods = $itemSales->sum(function ($sale) use ($branchId, $locationId) {
                // Use resolved cost price (location → branch → default)
                $costPrice = $sale->inventoryItem ? $sale->inventoryItem->getCostPriceForBranchOrLocation($branchId, $locationId) : 0;
                return $sale->quantity * $costPrice;
            });
            $grossMargin = $salesRevenue - $costOfGoods;
            $grossMarginPercent = $salesRevenue > 0 ? ($grossMargin / $salesRevenue) * 100 : 0;

            // Use resolved prices for the export
            $resolvedCostPrice = $item ? $item->getCostPriceForBranchOrLocation($branchId, $locationId) : 0;
            $resolvedUnitPrice = $item ? $item->getUnitPriceForBranchOrLocation($branchId, $locationId) : 0;

            return [
                'item_code' => $item->code,
                'item_name' => $item->name,
                'sold_qty' => $soldQty,
                'sales_revenue' => $salesRevenue,
                'cost_of_goods' => $costOfGoods,
                'gross_margin' => $grossMargin,
                'gross_margin_percent' => $grossMarginPercent,
                'unit_of_measure' => $item->unit_of_measure,
                'cost_price' => $resolvedCostPrice,
                'unit_price' => $resolvedUnitPrice,
            ];
        });

        return \Excel::download(new \App\Exports\ProfitMarginReportExport($profitData, $dateFrom, $dateTo), 'profit-margin-report-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Profit Margin Report to PDF
     */
    public function profitMarginExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

        $salesQuery = SalesInvoiceItem::with(['salesInvoice', 'inventoryItem'])
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo) {
                $q->where('company_id', auth()->user()->company_id)
                  ->when(auth()->user()->branch_id, function($query) {
                      return $query->where('branch_id', auth()->user()->branch_id);
                  })
                  ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
            });

        if ($request->filled('item_id')) {
            $salesQuery->where('inventory_item_id', $request->item_id);
        }

        if ($request->filled('customer_id')) {
            $salesQuery->whereHas('salesInvoice', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        $salesItems = $salesQuery->get();

        // Get branch/location context for cost resolution
        $branchId = session('branch_id') ?? auth()->user()->branch_id;
        $locationId = session('location_id');

        $profitData = $salesItems->groupBy('inventory_item_id')->map(function ($itemSales) use ($branchId, $locationId) {
            $item = $itemSales->first()->inventoryItem;
            $soldQty = $itemSales->sum('quantity');
            $salesRevenue = $itemSales->sum('line_total');
            $costOfGoods = $itemSales->sum(function ($sale) use ($branchId, $locationId) {
                // Use resolved cost price (location → branch → default)
                $costPrice = $sale->inventoryItem ? $sale->inventoryItem->getCostPriceForBranchOrLocation($branchId, $locationId) : 0;
                return $sale->quantity * $costPrice;
            });
            $grossMargin = $salesRevenue - $costOfGoods;
            $grossMarginPercent = $salesRevenue > 0 ? ($grossMargin / $salesRevenue) * 100 : 0;

            return [
                'item' => $item,
                'sold_qty' => $soldQty,
                'sales_revenue' => $salesRevenue,
                'cost_of_goods' => $costOfGoods,
                'gross_margin' => $grossMargin,
                'gross_margin_percent' => $grossMarginPercent
            ];
        });

        $company = \App\Models\Company::find(auth()->user()->company_id);
        $pdf = \PDF::loadView('inventory.reports.profit-margin-pdf', compact('profitData', 'dateFrom', 'dateTo', 'company'));
        return $pdf->stream('profit-margin-report-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Inventory Value Summary Report
     */
    public function inventoryValueSummary(Request $request)
    {
        // Handle AJAX request for locations by branch
        if ($request->ajax() && $request->has('get_locations')) {
            $branchId = $request->get('branch_id');
            $user = auth()->user();
            $userBranches = $user->branches()->pluck('branches.id')->toArray();
            
            $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $locationsQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $locationsQuery->whereIn('branch_id', $userBranches);
            } elseif (count($userBranches) > 0) {
                $locationsQuery->whereIn('branch_id', $userBranches);
            } elseif ($user->branch_id) {
                $locationsQuery->where('branch_id', $user->branch_id);
            }
            
            $locations = $locationsQuery->orderBy('name')->get();
            
            return response()->json([
                'locations' => $locations->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name
                    ];
                })
            ]);
        }

        // Use date range approach
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        // Default branch: if user has multiple assigned branches, use 'all_my_branches', otherwise use session branch or first assigned branch
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();
        
        // Get branches - show user's assigned branches, or all if no assigned branches
        if (count($userBranches) > 0) {
            $branches = \App\Models\Branch::whereIn('id', $userBranches)->orderBy('name')->get();
        } else {
            $branches = \App\Models\Branch::where('company_id', $user->company_id)->orderBy('name')->get();
        }
        
        // Get locations - filter by branch if selected
        $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
        if ($branchId && $branchId !== 'all_my_branches') {
            $locationsQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif (count($userBranches) > 0 && !$branchId) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif ($user->branch_id) {
            $locationsQuery->where('branch_id', $user->branch_id);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
        $dateToCarbon = Carbon::parse($dateTo)->endOfDay();

        $reportData = $items->map(function ($item) use ($dateFromCarbon, $dateToCarbon, $locationId, $branchId, $userBranches) {
            // Opening balance = stock value before date_from (all movements before the date range)
            $openingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<', $dateFromCarbon);
            
            if ($locationId) {
                $openingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $openingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $openingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $openingBalance = $openingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN total_cost 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -total_cost 
                        ELSE 0 
                    END) as opening_value
                ')
                ->value('opening_value') ?? 0;

            // Purchases within date range
            $purchasesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'purchased')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $purchasesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $purchasesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $purchasesQuery->whereIn('branch_id', $userBranches);
            }

            $purchases = $purchasesQuery->sum('total_cost') ?? 0;

            // Production Orders within date range
            $productionQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'production')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $productionQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $productionQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $productionQuery->whereIn('branch_id', $userBranches);
            }

            $productionOrders = $productionQuery->sum('total_cost') ?? 0;

            // Cost of Sales within date range
            $costOfSalesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $costOfSalesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costOfSalesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costOfSalesQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costOfSalesQuery->sum('total_cost') ?? 0;

            // Adjustments within date range - net
            $adjustmentsQuery = InventoryMovement::where('item_id', $item->id)
                ->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $adjustmentsQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $adjustmentsQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $adjustmentsQuery->whereIn('branch_id', $userBranches);
            }

            $adjustments = $adjustmentsQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type = "adjustment_in" 
                        THEN total_cost 
                        WHEN movement_type = "adjustment_out" 
                        THEN -total_cost 
                        ELSE 0 
                    END) as adjustment_value
                ')
                ->value('adjustment_value') ?? 0;

            // Closing Balance = stock value at the end of date_to (all movements up to and including date_to)
            $closingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<=', $dateToCarbon);
            
            if ($locationId) {
                $closingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $closingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $closingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $closingBalance = $closingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN total_cost 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -total_cost 
                        ELSE 0 
                    END) as closing_value
                ')
                ->value('closing_value') ?? 0;

            return [
                'item' => $item,
                'opening_balance' => (float) $openingBalance,
                'purchases' => (float) $purchases,
                'production_orders' => (float) $productionOrders,
                'cost_of_sales' => (float) $costOfSales,
                'adjustments' => (float) $adjustments,
                'closing_balance' => (float) $closingBalance,
            ];
        })->filter(function ($data) {
            return $data['opening_balance'] != 0 || 
                   $data['purchases'] != 0 || 
                   $data['production_orders'] != 0 || 
                   $data['cost_of_sales'] != 0 || 
                   $data['adjustments'] != 0 ||
                   $data['closing_balance'] != 0;
        });

        return view('inventory.reports.inventory-value-summary', compact(
            'reportData',
            'dateFrom',
            'dateTo',
            'categoryId',
            'locationId',
            'branchId',
            'categories',
            'locations',
            'branches',
            'userBranches',
            'hasMultipleBranches'
        ));
    }

    /**
     * Export Inventory Value Summary Report to PDF
     */
    public function inventoryValueSummaryExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();

        $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
        $dateToCarbon = Carbon::parse($dateTo)->endOfDay();

        $reportData = $items->map(function ($item) use ($dateFromCarbon, $dateToCarbon, $locationId, $branchId, $userBranches) {
            // Opening balance = stock value before date_from (all movements before the date range)
            $openingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<', $dateFromCarbon);
            
            if ($locationId) {
                $openingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $openingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $openingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $openingBalance = $openingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN total_cost 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -total_cost 
                        ELSE 0 
                    END) as opening_value
                ')
                ->value('opening_value') ?? 0;

            // Purchases within date range
            $purchasesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'purchased')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $purchasesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $purchasesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $purchasesQuery->whereIn('branch_id', $userBranches);
            }

            $purchases = $purchasesQuery->sum('total_cost') ?? 0;

            // Production Orders within date range
            $productionQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'production')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $productionQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $productionQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $productionQuery->whereIn('branch_id', $userBranches);
            }

            $productionOrders = $productionQuery->sum('total_cost') ?? 0;

            // Cost of Sales within date range
            $costOfSalesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $costOfSalesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costOfSalesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costOfSalesQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costOfSalesQuery->sum('total_cost') ?? 0;

            // Adjustments within date range - net
            $adjustmentsQuery = InventoryMovement::where('item_id', $item->id)
                ->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $adjustmentsQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $adjustmentsQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $adjustmentsQuery->whereIn('branch_id', $userBranches);
            }

            $adjustments = $adjustmentsQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type = "adjustment_in" 
                        THEN total_cost 
                        WHEN movement_type = "adjustment_out" 
                        THEN -total_cost 
                        ELSE 0 
                    END) as adjustment_value
                ')
                ->value('adjustment_value') ?? 0;

            // Closing Balance = stock value at the end of date_to (all movements up to and including date_to)
            $closingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<=', $dateToCarbon);
            
            if ($locationId) {
                $closingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $closingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $closingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $closingBalance = $closingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN total_cost 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -total_cost 
                        ELSE 0 
                    END) as closing_value
                ')
                ->value('closing_value') ?? 0;

            return [
                'item' => $item,
                'opening_balance' => (float) $openingBalance,
                'purchases' => (float) $purchases,
                'production_orders' => (float) $productionOrders,
                'cost_of_sales' => (float) $costOfSales,
                'adjustments' => (float) $adjustments,
                'closing_balance' => (float) $closingBalance,
            ];
        })->filter(function ($data) {
            return $data['opening_balance'] != 0 || 
                   $data['purchases'] != 0 || 
                   $data['production_orders'] != 0 || 
                   $data['cost_of_sales'] != 0 || 
                   $data['adjustments'] != 0 ||
                   $data['closing_balance'] != 0;
        });

        // Get filter details
        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $location = $locationId ? InventoryLocation::find($locationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        $pdf = Pdf::loadView('inventory.reports.exports.inventory-value-summary-pdf', compact(
            'reportData',
            'dateFromCarbon',
            'dateToCarbon',
            'category',
            'location',
            'branch',
            'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('inventory-value-summary-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Inventory Value Summary Report to Excel
     */
    public function inventoryValueSummaryExportExcel(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();

        $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
        $dateToCarbon = Carbon::parse($dateTo)->endOfDay();

        $reportData = $items->map(function ($item) use ($dateFromCarbon, $dateToCarbon, $locationId, $branchId, $userBranches) {
            // Opening balance = stock value before date_from (all movements before the date range)
            $openingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<', $dateFromCarbon);
            
            if ($locationId) {
                $openingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $openingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $openingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $openingBalance = $openingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN total_cost 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -total_cost 
                        ELSE 0 
                    END) as opening_value
                ')
                ->value('opening_value') ?? 0;

            // Purchases within date range
            $purchasesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'purchased')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $purchasesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $purchasesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $purchasesQuery->whereIn('branch_id', $userBranches);
            }

            $purchases = $purchasesQuery->sum('total_cost') ?? 0;

            // Production Orders within date range
            $productionQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'production')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $productionQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $productionQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $productionQuery->whereIn('branch_id', $userBranches);
            }

            $productionOrders = $productionQuery->sum('total_cost') ?? 0;

            // Cost of Sales within date range
            $costOfSalesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $costOfSalesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costOfSalesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costOfSalesQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costOfSalesQuery->sum('total_cost') ?? 0;

            // Adjustments within date range - net
            $adjustmentsQuery = InventoryMovement::where('item_id', $item->id)
                ->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $adjustmentsQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $adjustmentsQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $adjustmentsQuery->whereIn('branch_id', $userBranches);
            }

            $adjustments = $adjustmentsQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type = "adjustment_in" 
                        THEN total_cost 
                        WHEN movement_type = "adjustment_out" 
                        THEN -total_cost 
                        ELSE 0 
                    END) as adjustment_value
                ')
                ->value('adjustment_value') ?? 0;

            // Closing Balance = stock value at the end of date_to (all movements up to and including date_to)
            $closingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<=', $dateToCarbon);
            
            if ($locationId) {
                $closingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $closingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $closingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $closingBalance = $closingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN total_cost 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -total_cost 
                        ELSE 0 
                    END) as closing_value
                ')
                ->value('closing_value') ?? 0;

            return [
                'item_code' => $item->code,
                'item_name' => $item->name,
                'category' => $item->category->name ?? 'N/A',
                'opening_balance' => (float) $openingBalance,
                'purchases' => (float) $purchases,
                'production_orders' => (float) $productionOrders,
                'cost_of_sales' => (float) $costOfSales,
                'adjustments' => (float) $adjustments,
                'closing_balance' => (float) $closingBalance,
            ];
        })->filter(function ($data) {
            return $data['opening_balance'] != 0 || 
                   $data['purchases'] != 0 || 
                   $data['production_orders'] != 0 || 
                   $data['cost_of_sales'] != 0 || 
                   $data['adjustments'] != 0 ||
                   $data['closing_balance'] != 0;
        });

        // Calculate totals
        $totals = [
            'opening_balance' => $reportData->sum('opening_balance'),
            'purchases' => $reportData->sum('purchases'),
            'production_orders' => $reportData->sum('production_orders'),
            'cost_of_sales' => $reportData->sum('cost_of_sales'),
            'adjustments' => $reportData->sum('adjustments'),
            'closing_balance' => $reportData->sum('closing_balance'),
        ];

        // Get filter details
        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $location = $locationId ? InventoryLocation::find($locationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        // Create Excel export using inline approach
        return Excel::download(new class($reportData, $totals, $dateFromCarbon, $dateToCarbon, $category, $location, $branch, $company) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithTitle {
            protected $data;
            protected $totals;
            protected $dateFrom;
            protected $dateTo;
            protected $category;
            protected $location;
            protected $branch;
            protected $company;

            public function __construct($data, $totals, $dateFrom, $dateTo, $category, $location, $branch, $company)
            {
                $this->data = $data;
                $this->totals = $totals;
                $this->dateFrom = $dateFrom;
                $this->dateTo = $dateTo;
                $this->category = $category;
                $this->location = $location;
                $this->branch = $branch;
                $this->company = $company;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'Item Code',
                    'Item Name',
                    'Category',
                    'Opening Balance',
                    'Purchases',
                    'Production Orders',
                    'Cost of Sales',
                    'Adjustments',
                    'Closing Balance'
                ];
            }

            public function map($item): array
            {
                return [
                    $item['item_code'],
                    $item['item_name'],
                    $item['category'],
                    number_format($item['opening_balance'], 2),
                    number_format($item['purchases'], 2),
                    number_format($item['production_orders'], 2),
                    number_format($item['cost_of_sales'], 2),
                    number_format($item['adjustments'], 2),
                    number_format($item['closing_balance'], 2),
                ];
            }

            public function title(): string
            {
                return 'Inventory Value Summary';
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
                ];
            }
        }, 'inventory-value-summary-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ==================== INVENTORY QUANTITY SUMMARY ====================
    
    /**
     * Inventory Quantity Summary Report
     */
    public function inventoryQuantitySummary(Request $request)
    {
        // Use date range approach
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();
        
        if (count($userBranches) > 0) {
            $branches = \App\Models\Branch::whereIn('id', $userBranches)->orderBy('name')->get();
        } else {
            $branches = \App\Models\Branch::where('company_id', $user->company_id)->orderBy('name')->get();
        }
        
        $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
        if ($branchId && $branchId !== 'all_my_branches') {
            $locationsQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif (count($userBranches) > 0 && !$branchId) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif ($user->branch_id) {
            $locationsQuery->where('branch_id', $user->branch_id);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
        $dateToCarbon = Carbon::parse($dateTo)->endOfDay();

        $reportData = $items->map(function ($item) use ($dateFromCarbon, $dateToCarbon, $locationId, $branchId, $userBranches) {
            // Opening balance = stock before date_from (all movements before the date range)
            $openingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<', $dateFromCarbon);
            
            if ($locationId) {
                $openingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $openingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $openingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $openingBalance = $openingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN quantity 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -quantity 
                        ELSE 0 
                    END) as opening_qty
                ')
                ->value('opening_qty') ?? 0;

            // Purchases within date range
            $purchasesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'purchased')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $purchasesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $purchasesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $purchasesQuery->whereIn('branch_id', $userBranches);
            }

            $purchases = $purchasesQuery->sum('quantity') ?? 0;

            // Production Orders within date range
            $productionQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'production')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $productionQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $productionQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $productionQuery->whereIn('branch_id', $userBranches);
            }

            $productionOrders = $productionQuery->sum('quantity') ?? 0;

            // Cost of Sales / Sales within date range
            $costOfSalesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $costOfSalesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costOfSalesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costOfSalesQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costOfSalesQuery->sum('quantity') ?? 0;

            // Adjustments within date range - net
            $adjustmentsQuery = InventoryMovement::where('item_id', $item->id)
                ->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $adjustmentsQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $adjustmentsQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $adjustmentsQuery->whereIn('branch_id', $userBranches);
            }

            $adjustments = $adjustmentsQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type = "adjustment_in" 
                        THEN quantity 
                        WHEN movement_type = "adjustment_out" 
                        THEN -quantity 
                        ELSE 0 
                    END) as adjustment_qty
                ')
                ->value('adjustment_qty') ?? 0;

            // Closing Balance = stock at the end of date_to (all movements up to and including date_to)
            $closingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<=', $dateToCarbon);
            
            if ($locationId) {
                $closingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $closingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $closingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $closingBalance = $closingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN quantity 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -quantity 
                        ELSE 0 
                    END) as closing_qty
                ')
                ->value('closing_qty') ?? 0;

            return [
                'item' => $item,
                'opening_balance' => (float) $openingBalance,
                'purchases' => (float) $purchases,
                'production_orders' => (float) $productionOrders,
                'cost_of_sales' => (float) $costOfSales,
                'adjustments' => (float) $adjustments,
                'closing_balance' => (float) $closingBalance,
            ];
        })->filter(function ($data) {
            return $data['opening_balance'] != 0 || 
                   $data['purchases'] != 0 || 
                   $data['production_orders'] != 0 || 
                   $data['cost_of_sales'] != 0 || 
                   $data['adjustments'] != 0 ||
                   $data['closing_balance'] != 0;
        });

        return view('inventory.reports.inventory-quantity-summary', compact(
            'reportData',
            'dateFrom',
            'dateTo',
            'categoryId',
            'locationId',
            'branchId',
            'categories',
            'locations',
            'branches',
            'userBranches',
            'hasMultipleBranches'
        ));
    }

    public function inventoryQuantitySummaryExportPdf(Request $request)
    {
        // Increase memory limit and execution time for large datasets
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 minutes
        
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
        $dateToCarbon = Carbon::parse($dateTo)->endOfDay();

        // Process items in chunks to avoid memory issues
        $reportData = collect();
        $query->orderBy('name')->chunk(100, function ($items) use (&$reportData, $dateFromCarbon, $dateToCarbon, $locationId, $branchId, $userBranches) {
            $chunkData = $items->map(function ($item) use ($dateFromCarbon, $dateToCarbon, $locationId, $branchId, $userBranches) {
            // Opening balance = stock before date_from (all movements before the date range)
            $openingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<', $dateFromCarbon);
            
            if ($locationId) {
                $openingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $openingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $openingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $openingBalance = $openingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN quantity 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -quantity 
                        ELSE 0 
                    END) as opening_qty
                ')
                ->value('opening_qty') ?? 0;

            // Purchases within date range
            $purchasesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'purchased')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $purchasesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $purchasesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $purchasesQuery->whereIn('branch_id', $userBranches);
            }

            $purchases = $purchasesQuery->sum('quantity') ?? 0;

            // Production Orders within date range
            $productionQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'production')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $productionQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $productionQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $productionQuery->whereIn('branch_id', $userBranches);
            }

            $productionOrders = $productionQuery->sum('quantity') ?? 0;

            // Cost of Sales / Sales within date range
            $costOfSalesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $costOfSalesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costOfSalesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costOfSalesQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costOfSalesQuery->sum('quantity') ?? 0;

            // Adjustments within date range - net
            $adjustmentsQuery = InventoryMovement::where('item_id', $item->id)
                ->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $adjustmentsQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $adjustmentsQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $adjustmentsQuery->whereIn('branch_id', $userBranches);
            }

            $adjustments = $adjustmentsQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type = "adjustment_in" 
                        THEN quantity 
                        WHEN movement_type = "adjustment_out" 
                        THEN -quantity 
                        ELSE 0 
                    END) as adjustment_qty
                ')
                ->value('adjustment_qty') ?? 0;

            // Closing Balance = stock at the end of date_to (all movements up to and including date_to)
            $closingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<=', $dateToCarbon);
            
            if ($locationId) {
                $closingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $closingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $closingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $closingBalance = $closingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN quantity 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -quantity 
                        ELSE 0 
                    END) as closing_qty
                ')
                ->value('closing_qty') ?? 0;

                return [
                    'item' => $item,
                    'opening_balance' => (float) $openingBalance,
                    'purchases' => (float) $purchases,
                    'production_orders' => (float) $productionOrders,
                    'cost_of_sales' => (float) $costOfSales,
                    'adjustments' => (float) $adjustments,
                    'closing_balance' => (float) $closingBalance,
                ];
            });
            
            // Filter and merge chunk data
            $filteredChunk = $chunkData->filter(function ($data) {
                return $data['opening_balance'] != 0 || 
                       $data['purchases'] != 0 || 
                       $data['production_orders'] != 0 || 
                       $data['cost_of_sales'] != 0 || 
                       $data['adjustments'] != 0 ||
                       $data['closing_balance'] != 0;
            });
            
            $reportData = $reportData->merge($filteredChunk);
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $location = $locationId ? InventoryLocation::find($locationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        $pdf = Pdf::loadView('inventory.reports.exports.inventory-quantity-summary-pdf', compact(
            'reportData',
            'dateFromCarbon',
            'dateToCarbon',
            'category',
            'location',
            'branch',
            'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('inventory-quantity-summary-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function inventoryQuantitySummaryExportExcel(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();

        $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
        $dateToCarbon = Carbon::parse($dateTo)->endOfDay();

        $reportData = $items->map(function ($item) use ($dateFromCarbon, $dateToCarbon, $locationId, $branchId, $userBranches) {
            // Opening balance = stock before date_from (all movements before the date range)
            $openingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<', $dateFromCarbon);
            
            if ($locationId) {
                $openingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $openingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $openingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $openingBalance = $openingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN quantity 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -quantity 
                        ELSE 0 
                    END) as opening_qty
                ')
                ->value('opening_qty') ?? 0;

            // Purchases within date range
            $purchasesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'purchased')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $purchasesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $purchasesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $purchasesQuery->whereIn('branch_id', $userBranches);
            }

            $purchases = $purchasesQuery->sum('quantity') ?? 0;

            // Production Orders within date range
            $productionQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'production')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $productionQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $productionQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $productionQuery->whereIn('branch_id', $userBranches);
            }

            $productionOrders = $productionQuery->sum('quantity') ?? 0;

            // Cost of Sales / Sales within date range
            $costOfSalesQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $costOfSalesQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costOfSalesQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costOfSalesQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costOfSalesQuery->sum('quantity') ?? 0;

            // Adjustments within date range - net
            $adjustmentsQuery = InventoryMovement::where('item_id', $item->id)
                ->whereIn('movement_type', ['adjustment_in', 'adjustment_out'])
                ->whereBetween('movement_date', [$dateFromCarbon, $dateToCarbon]);
            
            if ($locationId) {
                $adjustmentsQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $adjustmentsQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $adjustmentsQuery->whereIn('branch_id', $userBranches);
            }

            $adjustments = $adjustmentsQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type = "adjustment_in" 
                        THEN quantity 
                        WHEN movement_type = "adjustment_out" 
                        THEN -quantity 
                        ELSE 0 
                    END) as adjustment_qty
                ')
                ->value('adjustment_qty') ?? 0;

            // Closing Balance = stock at the end of date_to (all movements up to and including date_to)
            $closingBalanceQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_date', '<=', $dateToCarbon);
            
            if ($locationId) {
                $closingBalanceQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $closingBalanceQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $closingBalanceQuery->whereIn('branch_id', $userBranches);
            }

            $closingBalance = $closingBalanceQuery
                ->selectRaw('
                    SUM(CASE 
                        WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                        THEN quantity 
                        WHEN movement_type IN ("transfer_out", "sold", "adjustment_out") 
                        THEN -quantity 
                        ELSE 0 
                    END) as closing_qty
                ')
                ->value('closing_qty') ?? 0;

            return [
                'item_code' => $item->code,
                'item_name' => $item->name,
                'category' => $item->category->name ?? 'N/A',
                'opening_balance' => (float) $openingBalance,
                'purchases' => (float) $purchases,
                'production_orders' => (float) $productionOrders,
                'cost_of_sales' => (float) $costOfSales,
                'adjustments' => (float) $adjustments,
                'closing_balance' => (float) $closingBalance,
            ];
        })->filter(function ($data) {
            return $data['opening_balance'] != 0 || 
                   $data['purchases'] != 0 || 
                   $data['production_orders'] != 0 || 
                   $data['cost_of_sales'] != 0 || 
                   $data['adjustments'] != 0 ||
                   $data['closing_balance'] != 0;
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $location = $locationId ? InventoryLocation::find($locationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        return Excel::download(new class($reportData, $dateFromCarbon, $dateToCarbon, $category, $location, $branch, $company) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithTitle {
            protected $data;
            protected $dateFrom;
            protected $dateTo;
            protected $category;
            protected $location;
            protected $branch;
            protected $company;

            public function __construct($data, $dateFrom, $dateTo, $category, $location, $branch, $company)
            {
                $this->data = $data;
                $this->dateFrom = $dateFrom;
                $this->dateTo = $dateTo;
                $this->category = $category;
                $this->location = $location;
                $this->branch = $branch;
                $this->company = $company;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'Item Code',
                    'Item Name',
                    'Category',
                    'Opening Balance',
                    'Purchases',
                    'Production Orders',
                    'Cost of Sales',
                    'Adjustments',
                    'Closing Balance'
                ];
            }

            public function map($item): array
            {
                return [
                    $item['item_code'],
                    $item['item_name'],
                    $item['category'],
                    number_format($item['opening_balance'], 2),
                    number_format($item['purchases'], 2),
                    number_format($item['production_orders'], 2),
                    number_format($item['cost_of_sales'], 2),
                    number_format($item['adjustments'], 2),
                    number_format($item['closing_balance'], 2),
                ];
            }

            public function title(): string
            {
                return 'Inventory Quantity Summary';
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
                ];
            }
        }, 'inventory-quantity-summary-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ==================== INVENTORY PROFIT MARGIN ====================
    
    /**
     * Inventory Profit Margin Report
     */
    public function inventoryProfitMargin(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();
        
        if (count($userBranches) > 0) {
            $branches = \App\Models\Branch::whereIn('id', $userBranches)->orderBy('name')->get();
        } else {
            $branches = \App\Models\Branch::where('company_id', $user->company_id)->orderBy('name')->get();
        }
        
        $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
        if ($branchId && $branchId !== 'all_my_branches') {
            $locationsQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif (count($userBranches) > 0 && !$branchId) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif ($user->branch_id) {
            $locationsQuery->where('branch_id', $user->branch_id);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $reportData = $items->map(function ($item) use ($dateFrom, $dateTo, $locationId, $branchId, $userBranches) {
            // Get sales data from sales invoice items
            $salesQuery = SalesInvoiceItem::where('inventory_item_id', $item->id)
                ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo, $branchId, $userBranches) {
                    $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                      ->where('status', '!=', 'cancelled');
                    
                    if ($branchId && $branchId !== 'all_my_branches') {
                        $q->where('branch_id', $branchId);
                    } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                        $q->whereIn('branch_id', $userBranches);
                    }
                });
            
            if ($locationId) {
                // Filter by location if needed - this might require joining with movements
                // For now, we'll skip location filter for sales as it's complex
            }

            $unitsSold = $salesQuery->sum('quantity') ?? 0;
            $salesValue = $salesQuery->sum(DB::raw('(quantity * unit_price) - COALESCE(discount_amount, 0)')) ?? 0;

            // Get cost of sales from inventory movements
            $costQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFrom, $dateTo]);
            
            if ($locationId) {
                $costQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costQuery->sum('total_cost') ?? 0;

            $grossProfit = $salesValue - $costOfSales;
            $grossMargin = $salesValue > 0 ? ($grossProfit / $salesValue) * 100 : 0;

            return [
                'item' => $item,
                'units_sold' => (float) $unitsSold,
                'sales_value' => (float) $salesValue,
                'cost_of_sales' => (float) $costOfSales,
                'gross_profit' => (float) $grossProfit,
                'gross_margin' => (float) $grossMargin,
            ];
        })->filter(function ($data) {
            return $data['units_sold'] > 0 || $data['sales_value'] > 0;
        });

        return view('inventory.reports.inventory-profit-margin', compact(
            'reportData',
            'dateFrom',
            'dateTo',
            'categoryId',
            'locationId',
            'branchId',
            'categories',
            'locations',
            'branches',
            'userBranches',
            'hasMultipleBranches'
        ));
    }

    public function inventoryProfitMarginExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();

        $reportData = $items->map(function ($item) use ($dateFrom, $dateTo, $locationId, $branchId, $userBranches) {
            $salesQuery = SalesInvoiceItem::where('inventory_item_id', $item->id)
                ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo, $branchId, $userBranches) {
                    $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                      ->where('status', '!=', 'cancelled');
                    
                    if ($branchId && $branchId !== 'all_my_branches') {
                        $q->where('branch_id', $branchId);
                    } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                        $q->whereIn('branch_id', $userBranches);
                    }
                });

            $unitsSold = $salesQuery->sum('quantity') ?? 0;
            $salesValue = $salesQuery->sum(DB::raw('(quantity * unit_price) - COALESCE(discount_amount, 0)')) ?? 0;

            $costQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFrom, $dateTo]);
            
            if ($locationId) {
                $costQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costQuery->sum('total_cost') ?? 0;

            $grossProfit = $salesValue - $costOfSales;
            $grossMargin = $salesValue > 0 ? ($grossProfit / $salesValue) * 100 : 0;

            return [
                'item' => $item,
                'units_sold' => (float) $unitsSold,
                'sales_value' => (float) $salesValue,
                'cost_of_sales' => (float) $costOfSales,
                'gross_profit' => (float) $grossProfit,
                'gross_margin' => (float) $grossMargin,
            ];
        })->filter(function ($data) {
            return $data['units_sold'] > 0 || $data['sales_value'] > 0;
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $location = $locationId ? InventoryLocation::find($locationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        $pdf = Pdf::loadView('inventory.reports.exports.inventory-profit-margin-pdf', compact(
            'reportData',
            'dateFromCarbon',
            'dateToCarbon',
            'category',
            'location',
            'branch',
            'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('inventory-profit-margin-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function inventoryProfitMarginExportExcel(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();

        $reportData = $items->map(function ($item) use ($dateFrom, $dateTo, $locationId, $branchId, $userBranches) {
            $salesQuery = SalesInvoiceItem::where('inventory_item_id', $item->id)
                ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo, $branchId, $userBranches) {
                    $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                      ->where('status', '!=', 'cancelled');
                    
                    if ($branchId && $branchId !== 'all_my_branches') {
                        $q->where('branch_id', $branchId);
                    } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                        $q->whereIn('branch_id', $userBranches);
                    }
                });

            $unitsSold = $salesQuery->sum('quantity') ?? 0;
            $salesValue = $salesQuery->sum(DB::raw('(quantity * unit_price) - COALESCE(discount_amount, 0)')) ?? 0;

            $costQuery = InventoryMovement::where('item_id', $item->id)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFrom, $dateTo]);
            
            if ($locationId) {
                $costQuery->where('location_id', $locationId);
            }
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costQuery->sum('total_cost') ?? 0;

            $grossProfit = $salesValue - $costOfSales;
            $grossMargin = $salesValue > 0 ? ($grossProfit / $salesValue) * 100 : 0;

            return [
                'item_code' => $item->code,
                'item_name' => $item->name,
                'category' => $item->category->name ?? 'N/A',
                'units_sold' => (float) $unitsSold,
                'sales_value' => (float) $salesValue,
                'cost_of_sales' => (float) $costOfSales,
                'gross_profit' => (float) $grossProfit,
                'gross_margin' => (float) $grossMargin,
            ];
        })->filter(function ($data) {
            return $data['units_sold'] > 0 || $data['sales_value'] > 0;
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $location = $locationId ? InventoryLocation::find($locationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        return Excel::download(new class($reportData, $dateFromCarbon, $dateToCarbon, $category, $location, $branch, $company) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithTitle {
            protected $data;
            protected $dateFrom;
            protected $dateTo;
            protected $category;
            protected $location;
            protected $branch;
            protected $company;

            public function __construct($data, $dateFrom, $dateTo, $category, $location, $branch, $company)
            {
                $this->data = $data;
                $this->dateFrom = $dateFrom;
                $this->dateTo = $dateTo;
                $this->category = $category;
                $this->location = $location;
                $this->branch = $branch;
                $this->company = $company;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'Item Code',
                    'Item Name',
                    'Category',
                    'Units Sold',
                    'Sales Value (TZS)',
                    'Cost of Sales (TZS)',
                    'Gross Profit (TZS)',
                    'Gross Margin %'
                ];
            }

            public function map($item): array
            {
                return [
                    $item['item_code'],
                    $item['item_name'],
                    $item['category'],
                    number_format($item['units_sold'], 2),
                    number_format($item['sales_value'], 2),
                    number_format($item['cost_of_sales'], 2),
                    number_format($item['gross_profit'], 2),
                    number_format($item['gross_margin'], 2) . '%',
                ];
            }

            public function title(): string
            {
                return 'Inventory Profit Margin';
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
                ];
            }
        }, 'inventory-profit-margin-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ==================== INVENTORY PRICE LIST ====================
    
    /**
     * Inventory Price List Report
     */
    public function inventoryPriceList(Request $request)
    {
        $categoryId = $request->get('category_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();

        $reportData = $items->map(function ($item) {
            $unitCost = $item->cost_price ?? 0;
            $sellingPrice = $item->unit_price ?? 0;
            $markup = $unitCost > 0 ? (($sellingPrice - $unitCost) / $unitCost) * 100 : 0;

            return [
                'item' => $item,
                'unit_cost' => (float) $unitCost,
                'selling_price' => (float) $sellingPrice,
                'markup' => (float) $markup,
            ];
        });

        return view('inventory.reports.inventory-price-list', compact(
            'reportData',
            'categoryId',
            'categories'
        ));
    }

    public function inventoryPriceListExportPdf(Request $request)
    {
        $categoryId = $request->get('category_id');
        
        $user = auth()->user();

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();

        $reportData = $items->map(function ($item) {
            $unitCost = $item->cost_price ?? 0;
            $sellingPrice = $item->unit_price ?? 0;
            $markup = $unitCost > 0 ? (($sellingPrice - $unitCost) / $unitCost) * 100 : 0;

            return [
                'item' => $item,
                'unit_cost' => (float) $unitCost,
                'selling_price' => (float) $sellingPrice,
                'markup' => (float) $markup,
            ];
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $company = \App\Models\Company::find($user->company_id);

        $pdf = Pdf::loadView('inventory.reports.exports.inventory-price-list-pdf', compact(
            'reportData',
            'category',
            'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('inventory-price-list-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function inventoryPriceListExportExcel(Request $request)
    {
        $categoryId = $request->get('category_id');
        
        $user = auth()->user();

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();

        $reportData = $items->map(function ($item) {
            $unitCost = $item->cost_price ?? 0;
            $sellingPrice = $item->unit_price ?? 0;
            $markup = $unitCost > 0 ? (($sellingPrice - $unitCost) / $unitCost) * 100 : 0;

            return [
                'item_code' => $item->code,
                'item_name' => $item->name,
                'category' => $item->category->name ?? 'N/A',
                'unit_cost' => (float) $unitCost,
                'selling_price' => (float) $sellingPrice,
                'markup' => (float) $markup,
            ];
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $company = \App\Models\Company::find($user->company_id);

        return Excel::download(new class($reportData, $category, $company) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithTitle {
            protected $data;
            protected $category;
            protected $company;

            public function __construct($data, $category, $company)
            {
                $this->data = $data;
                $this->category = $category;
                $this->company = $company;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'Item Code',
                    'Item Name',
                    'Category',
                    'Unit Cost (TZS)',
                    'Selling Price (TZS)',
                    'Markup %'
                ];
            }

            public function map($item): array
            {
                return [
                    $item['item_code'],
                    $item['item_name'],
                    $item['category'],
                    number_format($item['unit_cost'], 2),
                    number_format($item['selling_price'], 2),
                    number_format($item['markup'], 2) . '%',
                ];
            }

            public function title(): string
            {
                return 'Inventory Price List';
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
                ];
            }
        }, 'inventory-price-list-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ==================== INVENTORY COSTING CALCULATION WORKSHEET ====================
    
    /**
     * Inventory Costing Calculation Worksheet
     */
    public function inventoryCostingWorksheet(Request $request)
    {
        $costingMethod = $request->get('costing_method', 'weighted_average');
        $categoryId = $request->get('category_id');
        
        $user = auth()->user();
        $systemCostMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';
        
        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();

        $reportData = $items->map(function ($item) use ($costingMethod, $systemCostMethod) {
            $quantity = 0;
            $averageCost = 0;
            $totalCost = 0;

            if ($costingMethod === 'fifo') {
                // Calculate from cost layers (FIFO method)
                $costLayers = \App\Models\InventoryCostLayer::where('item_id', $item->id)
                    ->where('is_consumed', false)
                    ->get();
                
                $quantity = $costLayers->sum('remaining_quantity');
                $totalCost = $costLayers->sum(function ($layer) {
                    return $layer->remaining_quantity * $layer->unit_cost;
                });
                $averageCost = $quantity > 0 ? $totalCost / $quantity : 0;
            } else {
                // Weighted Average method
                $stockService = new InventoryStockService();
                $quantity = $stockService->getItemTotalStock($item->id);
                $averageCost = $item->cost_price ?? 0;
                $totalCost = $quantity * $averageCost;
            }

            return [
                'item' => $item,
                'quantity' => (float) $quantity,
                'average_cost' => (float) $averageCost,
                'total_cost' => (float) $totalCost,
            ];
        })->filter(function ($data) {
            return $data['quantity'] > 0;
        });

        return view('inventory.reports.inventory-costing-worksheet', compact(
            'reportData',
            'costingMethod',
            'categoryId',
            'categories',
            'systemCostMethod'
        ));
    }

    public function inventoryCostingWorksheetExportPdf(Request $request)
    {
        $costingMethod = $request->get('costing_method', 'weighted_average');
        $categoryId = $request->get('category_id');
        
        $user = auth()->user();
        $systemCostMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();

        $reportData = $items->map(function ($item) use ($costingMethod, $systemCostMethod) {
            $quantity = 0;
            $averageCost = 0;
            $totalCost = 0;

            if ($costingMethod === 'fifo') {
                // Calculate from cost layers (FIFO method)
                $costLayers = \App\Models\InventoryCostLayer::where('item_id', $item->id)
                    ->where('is_consumed', false)
                    ->get();
                
                $quantity = $costLayers->sum('remaining_quantity');
                $totalCost = $costLayers->sum(function ($layer) {
                    return $layer->remaining_quantity * $layer->unit_cost;
                });
                $averageCost = $quantity > 0 ? $totalCost / $quantity : 0;
            } else {
                // Weighted Average method
                $stockService = new InventoryStockService();
                $quantity = $stockService->getItemTotalStock($item->id);
                $averageCost = $item->cost_price ?? 0;
                $totalCost = $quantity * $averageCost;
            }

            return [
                'item' => $item,
                'quantity' => (float) $quantity,
                'average_cost' => (float) $averageCost,
                'total_cost' => (float) $totalCost,
            ];
        })->filter(function ($data) {
            return $data['quantity'] > 0;
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $company = \App\Models\Company::find($user->company_id);

        $pdf = Pdf::loadView('inventory.reports.exports.inventory-costing-worksheet-pdf', compact(
            'reportData',
            'costingMethod',
            'category',
            'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('inventory-costing-worksheet-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function inventoryCostingWorksheetExportExcel(Request $request)
    {
        $costingMethod = $request->get('costing_method', 'weighted_average');
        $categoryId = $request->get('category_id');
        
        $user = auth()->user();
        $systemCostMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();

        $reportData = $items->map(function ($item) use ($costingMethod, $systemCostMethod) {
            $quantity = 0;
            $averageCost = 0;
            $totalCost = 0;

            if ($costingMethod === 'fifo') {
                // Calculate from cost layers (FIFO method)
                $costLayers = \App\Models\InventoryCostLayer::where('item_id', $item->id)
                    ->where('is_consumed', false)
                    ->get();
                
                $quantity = $costLayers->sum('remaining_quantity');
                $totalCost = $costLayers->sum(function ($layer) {
                    return $layer->remaining_quantity * $layer->unit_cost;
                });
                $averageCost = $quantity > 0 ? $totalCost / $quantity : 0;
            } else {
                // Weighted Average method
                $stockService = new InventoryStockService();
                $quantity = $stockService->getItemTotalStock($item->id);
                $averageCost = $item->cost_price ?? 0;
                $totalCost = $quantity * $averageCost;
            }

            return [
                'item_code' => $item->code,
                'item_name' => $item->name,
                'category' => $item->category->name ?? 'N/A',
                'quantity' => (float) $quantity,
                'average_cost' => (float) $averageCost,
                'total_cost' => (float) $totalCost,
            ];
        })->filter(function ($data) {
            return $data['quantity'] > 0;
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $company = \App\Models\Company::find($user->company_id);

        return Excel::download(new class($reportData, $costingMethod, $category, $company) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithTitle {
            protected $data;
            protected $costingMethod;
            protected $category;
            protected $company;

            public function __construct($data, $costingMethod, $category, $company)
            {
                $this->data = $data;
                $this->costingMethod = $costingMethod;
                $this->category = $category;
                $this->company = $company;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'Item Code',
                    'Item Name',
                    'Category',
                    'Qty',
                    'Average Cost',
                    'Total Cost'
                ];
            }

            public function map($item): array
            {
                return [
                    $item['item_code'],
                    $item['item_name'],
                    $item['category'],
                    number_format($item['quantity'], 2),
                    number_format($item['average_cost'], 4),
                    number_format($item['total_cost'], 2),
                ];
            }

            public function title(): string
            {
                return 'Costing Worksheet';
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
                ];
            }
        }, 'inventory-costing-worksheet-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ==================== INVENTORY QUANTITY BY LOCATION ====================
    
    /**
     * Inventory Quantity by Location Report
     */
    public function inventoryQuantityByLocation(Request $request)
    {
        $categoryId = $request->get('category_id');
        $branchId = $request->get('branch_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();
        
        if (count($userBranches) > 0) {
            $branches = \App\Models\Branch::whereIn('id', $userBranches)->orderBy('name')->get();
        } else {
            $branches = \App\Models\Branch::where('company_id', $user->company_id)->orderBy('name')->get();
        }
        
        // Get all locations
        $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
        if ($branchId && $branchId !== 'all_my_branches') {
            $locationsQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $stockService = new InventoryStockService();

        $reportData = $items->map(function ($item) use ($locations, $stockService) {
            $locationQuantities = [];
            $totalQuantity = 0;

            foreach ($locations as $location) {
                $qty = $stockService->getItemStockAtLocation($item->id, $location->id);
                $locationQuantities[$location->id] = (float) $qty;
                $totalQuantity += $qty;
            }

            return [
                'item' => $item,
                'location_quantities' => $locationQuantities,
                'total_quantity' => (float) $totalQuantity,
            ];
        })->filter(function ($data) {
            return $data['total_quantity'] > 0;
        });

        return view('inventory.reports.inventory-quantity-by-location', compact(
            'reportData',
            'locations',
            'categoryId',
            'branchId',
            'categories',
            'branches',
            'userBranches',
            'hasMultipleBranches'
        ));
    }

    public function inventoryQuantityByLocationExportPdf(Request $request)
    {
        $categoryId = $request->get('category_id');
        $branchId = $request->get('branch_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        
        $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
        if ($branchId && $branchId !== 'all_my_branches') {
            $locationsQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $stockService = new InventoryStockService();

        $reportData = $items->map(function ($item) use ($locations, $stockService) {
            $locationQuantities = [];
            $totalQuantity = 0;

            foreach ($locations as $location) {
                $qty = $stockService->getItemStockAtLocation($item->id, $location->id);
                $locationQuantities[$location->id] = (float) $qty;
                $totalQuantity += $qty;
            }

            return [
                'item' => $item,
                'location_quantities' => $locationQuantities,
                'total_quantity' => (float) $totalQuantity,
            ];
        })->filter(function ($data) {
            return $data['total_quantity'] > 0;
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $pdf = Pdf::loadView('inventory.reports.exports.inventory-quantity-by-location-pdf', compact(
            'reportData',
            'locations',
            'category',
            'branch',
            'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('inventory-quantity-by-location-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function inventoryQuantityByLocationExportExcel(Request $request)
    {
        $categoryId = $request->get('category_id');
        $branchId = $request->get('branch_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        
        $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
        if ($branchId && $branchId !== 'all_my_branches') {
            $locationsQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $stockService = new InventoryStockService();

        $reportData = $items->map(function ($item) use ($locations, $stockService) {
            $row = [
                'item_code' => $item->code,
                'item_name' => $item->name,
                'category' => $item->category->name ?? 'N/A',
            ];

            $totalQuantity = 0;
            foreach ($locations as $location) {
                $qty = $stockService->getItemStockAtLocation($item->id, $location->id);
                $row['location_' . $location->id] = (float) $qty;
                $totalQuantity += $qty;
            }
            $row['total_quantity'] = (float) $totalQuantity;

            return $row;
        })->filter(function ($data) {
            return $data['total_quantity'] > 0;
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $headings = ['Item Code', 'Item Name', 'Category'];
        foreach ($locations as $location) {
            $headings[] = $location->name;
        }
        $headings[] = 'Total Quantity';

        return Excel::download(new class($reportData, $headings, $category, $branch, $company) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithTitle {
            protected $data;
            protected $headings;
            protected $category;
            protected $branch;
            protected $company;

            public function __construct($data, $headings, $category, $branch, $company)
            {
                $this->data = $data;
                $this->headings = $headings;
                $this->category = $category;
                $this->branch = $branch;
                $this->company = $company;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headings;
            }

            public function map($item): array
            {
                $row = [
                    $item['item_code'],
                    $item['item_name'],
                    $item['category'],
                ];

                // Add location quantities
                foreach ($item as $key => $value) {
                    if (strpos($key, 'location_') === 0) {
                        $row[] = number_format($value, 2);
                    }
                }

                $row[] = number_format($item['total_quantity'], 2);
                return $row;
            }

            public function title(): string
            {
                return 'Quantity by Location';
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
                ];
            }
        }, 'inventory-quantity-by-location-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ==================== INVENTORY TRANSFER MOVEMENT REPORT ====================
    
    /**
     * Inventory Transfer Movement Report
     */
    public function inventoryTransferMovement(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $itemId = $request->get('item_id');
        $fromLocationId = $request->get('from_location_id');
        $toLocationId = $request->get('to_location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        // Get transfer_out movements (they contain the transfer information)
        $transfersQuery = InventoryMovement::with(['item', 'location'])
            ->whereIn('movement_type', ['transfer_out', 'transfer_in'])
            ->whereBetween('movement_date', [$dateFrom, $dateTo]);

        if ($itemId) {
            $transfersQuery->where('item_id', $itemId);
        }

        if ($fromLocationId) {
            $transfersQuery->where('location_id', $fromLocationId);
        }

        if ($branchId && $branchId !== 'all_my_branches') {
            $transfersQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $transfersQuery->whereIn('branch_id', $userBranches);
        }

        $transfers = $transfersQuery->orderBy('movement_date', 'desc')
            ->orderBy('reference')
            ->get();

        // Group transfers by reference to match transfer_out and transfer_in
        $reportData = [];
        $processedReferences = [];

        foreach ($transfers as $transfer) {
            if (in_array($transfer->reference, $processedReferences)) {
                continue;
            }

            if ($transfer->movement_type === 'transfer_out') {
                // Find matching transfer_in
                $transferIn = InventoryMovement::where('reference', $transfer->reference)
                    ->where('movement_type', 'transfer_in')
                    ->where('item_id', $transfer->item_id)
                    ->first();

                $toLocation = $transferIn ? $transferIn->location : null;
                $fromLocation = $transfer->location;

                // Extract location names from notes if needed
                if (!$toLocation && $transferIn) {
                    $toLocation = InventoryLocation::find($transferIn->location_id);
                }

                $reportData[] = [
                    'transfer_id' => $transfer->reference ?? 'N/A',
                    'date' => $transfer->movement_date,
                    'item' => $transfer->item,
                    'from_location' => $fromLocation,
                    'to_location' => $toLocation,
                    'quantity' => (float) $transfer->quantity,
                    'unit_cost' => (float) $transfer->unit_cost,
                    'total_value' => (float) $transfer->total_cost,
                ];

                $processedReferences[] = $transfer->reference;
            }
        }

        $items = InventoryItem::where('company_id', $user->company_id)->orderBy('name')->get();
        $locations = InventoryLocation::where('company_id', $user->company_id)->orderBy('name')->get();
        
        if (count($userBranches) > 0) {
            $branches = \App\Models\Branch::whereIn('id', $userBranches)->orderBy('name')->get();
        } else {
            $branches = \App\Models\Branch::where('company_id', $user->company_id)->orderBy('name')->get();
        }

        return view('inventory.reports.inventory-transfer-movement', compact(
            'reportData',
            'dateFrom',
            'dateTo',
            'itemId',
            'fromLocationId',
            'toLocationId',
            'branchId',
            'items',
            'locations',
            'branches',
            'userBranches',
            'hasMultipleBranches'
        ));
    }

    public function inventoryTransferMovementExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $itemId = $request->get('item_id');
        $fromLocationId = $request->get('from_location_id');
        $toLocationId = $request->get('to_location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $transfersQuery = InventoryMovement::with(['item', 'location'])
            ->whereIn('movement_type', ['transfer_out', 'transfer_in'])
            ->whereBetween('movement_date', [$dateFrom, $dateTo]);

        if ($itemId) {
            $transfersQuery->where('item_id', $itemId);
        }

        if ($fromLocationId) {
            $transfersQuery->where('location_id', $fromLocationId);
        }

        if ($branchId && $branchId !== 'all_my_branches') {
            $transfersQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $transfersQuery->whereIn('branch_id', $userBranches);
        }

        $transfers = $transfersQuery->orderBy('movement_date', 'desc')
            ->orderBy('reference')
            ->get();

        $reportData = [];
        $processedReferences = [];

        foreach ($transfers as $transfer) {
            if (in_array($transfer->reference, $processedReferences)) {
                continue;
            }

            if ($transfer->movement_type === 'transfer_out') {
                $transferIn = InventoryMovement::where('reference', $transfer->reference)
                    ->where('movement_type', 'transfer_in')
                    ->where('item_id', $transfer->item_id)
                    ->first();

                $toLocation = $transferIn ? $transferIn->location : null;
                $fromLocation = $transfer->location;

                if (!$toLocation && $transferIn) {
                    $toLocation = InventoryLocation::find($transferIn->location_id);
                }

                $reportData[] = [
                    'transfer_id' => $transfer->reference ?? 'N/A',
                    'date' => $transfer->movement_date,
                    'item' => $transfer->item,
                    'from_location' => $fromLocation,
                    'to_location' => $toLocation,
                    'quantity' => (float) $transfer->quantity,
                    'unit_cost' => (float) $transfer->unit_cost,
                    'total_value' => (float) $transfer->total_cost,
                ];

                $processedReferences[] = $transfer->reference;
            }
        }

        $item = $itemId ? InventoryItem::find($itemId) : null;
        $fromLocation = $fromLocationId ? InventoryLocation::find($fromLocationId) : null;
        $toLocation = $toLocationId ? InventoryLocation::find($toLocationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        $pdf = Pdf::loadView('inventory.reports.exports.inventory-transfer-movement-pdf', compact(
            'reportData',
            'dateFromCarbon',
            'dateToCarbon',
            'item',
            'fromLocation',
            'toLocation',
            'branch',
            'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('inventory-transfer-movement-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function inventoryTransferMovementExportExcel(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $itemId = $request->get('item_id');
        $fromLocationId = $request->get('from_location_id');
        $toLocationId = $request->get('to_location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $transfersQuery = InventoryMovement::with(['item', 'location'])
            ->whereIn('movement_type', ['transfer_out', 'transfer_in'])
            ->whereBetween('movement_date', [$dateFrom, $dateTo]);

        if ($itemId) {
            $transfersQuery->where('item_id', $itemId);
        }

        if ($fromLocationId) {
            $transfersQuery->where('location_id', $fromLocationId);
        }

        if ($branchId && $branchId !== 'all_my_branches') {
            $transfersQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $transfersQuery->whereIn('branch_id', $userBranches);
        }

        $transfers = $transfersQuery->orderBy('movement_date', 'desc')
            ->orderBy('reference')
            ->get();

        $reportData = [];
        $processedReferences = [];

        foreach ($transfers as $transfer) {
            if (in_array($transfer->reference, $processedReferences)) {
                continue;
            }

            if ($transfer->movement_type === 'transfer_out') {
                $transferIn = InventoryMovement::where('reference', $transfer->reference)
                    ->where('movement_type', 'transfer_in')
                    ->where('item_id', $transfer->item_id)
                    ->first();

                $toLocation = $transferIn ? $transferIn->location : null;
                $fromLocation = $transfer->location;

                if (!$toLocation && $transferIn) {
                    $toLocation = InventoryLocation::find($transferIn->location_id);
                }

                $reportData[] = [
                    'transfer_id' => $transfer->reference ?? 'N/A',
                    'date' => $transfer->movement_date->format('Y-m-d'),
                    'item_code' => $transfer->item->code ?? 'N/A',
                    'item_name' => $transfer->item->name ?? 'N/A',
                    'category' => $transfer->item->category->name ?? 'N/A',
                    'from_location' => $fromLocation ? $fromLocation->name : 'N/A',
                    'to_location' => $toLocation ? $toLocation->name : 'N/A',
                    'quantity' => (float) $transfer->quantity,
                    'unit_cost' => (float) $transfer->unit_cost,
                    'total_value' => (float) $transfer->total_cost,
                ];

                $processedReferences[] = $transfer->reference;
            }
        }

        $item = $itemId ? InventoryItem::find($itemId) : null;
        $fromLocation = $fromLocationId ? InventoryLocation::find($fromLocationId) : null;
        $toLocation = $toLocationId ? InventoryLocation::find($toLocationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        return Excel::download(new class($reportData, $dateFromCarbon, $dateToCarbon, $item, $fromLocation, $toLocation, $branch, $company) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithTitle {
            protected $data;
            protected $dateFrom;
            protected $dateTo;
            protected $item;
            protected $fromLocation;
            protected $toLocation;
            protected $branch;
            protected $company;

            public function __construct($data, $dateFrom, $dateTo, $item, $fromLocation, $toLocation, $branch, $company)
            {
                $this->data = collect($data);
                $this->dateFrom = $dateFrom;
                $this->dateTo = $dateTo;
                $this->item = $item;
                $this->fromLocation = $fromLocation;
                $this->toLocation = $toLocation;
                $this->branch = $branch;
                $this->company = $company;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'Transfer ID',
                    'Date',
                    'Item Code',
                    'Item Name',
                    'Category',
                    'From Location',
                    'To Location',
                    'Quantity',
                    'Unit Cost (TZS)',
                    'Total Value (TZS)'
                ];
            }

            public function map($item): array
            {
                return [
                    $item['transfer_id'],
                    $item['date'],
                    $item['item_code'],
                    $item['item_name'],
                    $item['category'],
                    $item['from_location'],
                    $item['to_location'],
                    number_format($item['quantity'], 2),
                    number_format($item['unit_cost'], 2),
                    number_format($item['total_value'], 2),
                ];
            }

            public function title(): string
            {
                return 'Transfer Movement';
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
                ];
            }
        }, 'inventory-transfer-movement-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ==================== INVENTORY AGING REPORT ====================
    
    /**
     * Inventory Aging Report
     */
    public function inventoryAging(Request $request)
    {
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();
        
        if (count($userBranches) > 0) {
            $branches = \App\Models\Branch::whereIn('id', $userBranches)->orderBy('name')->get();
        } else {
            $branches = \App\Models\Branch::where('company_id', $user->company_id)->orderBy('name')->get();
        }
        
        $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
        if ($branchId && $branchId !== 'all_my_branches') {
            $locationsQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif (count($userBranches) > 0 && !$branchId) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif ($user->branch_id) {
            $locationsQuery->where('branch_id', $user->branch_id);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $stockService = new InventoryStockService();
        $now = Carbon::now();

        $reportData = $items->map(function ($item) use ($locationId, $branchId, $userBranches, $locations, $stockService, $now) {
            $itemData = [];
            
            foreach ($locations as $location) {
                if ($locationId && $location->id != $locationId) {
                    continue;
                }

                $quantity = $stockService->getItemStockAtLocation($item->id, $location->id);
                
                if ($quantity <= 0) {
                    continue;
                }

                // Get last movement date for this item at this location
                $lastMovementQuery = InventoryMovement::where('item_id', $item->id)
                    ->where('location_id', $location->id);
                
                if ($branchId && $branchId !== 'all_my_branches') {
                    $lastMovementQuery->where('branch_id', $branchId);
                } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                    $lastMovementQuery->whereIn('branch_id', $userBranches);
                }

                $lastMovement = $lastMovementQuery->orderBy('movement_date', 'desc')->first();
                
                $lastMovementDate = $lastMovement ? $lastMovement->movement_date : null;
                $ageInDays = $lastMovementDate ? $now->diffInDays($lastMovementDate) : 0;

                // Categorize into age buckets
                $age0_30 = 0;
                $age31_60 = 0;
                $age61_90 = 0;
                $age91_180 = 0;
                $ageOver180 = 0;

                if ($ageInDays <= 30) {
                    $age0_30 = $quantity;
                } elseif ($ageInDays <= 60) {
                    $age31_60 = $quantity;
                } elseif ($ageInDays <= 90) {
                    $age61_90 = $quantity;
                } elseif ($ageInDays <= 180) {
                    $age91_180 = $quantity;
                } else {
                    $ageOver180 = $quantity;
                }

                $unitCost = $item->cost_price ?? 0;
                $value = $quantity * $unitCost;

                $itemData[] = [
                    'item' => $item,
                    'location' => $location,
                    'quantity' => (float) $quantity,
                    'unit_cost' => (float) $unitCost,
                    'value' => (float) $value,
                    'last_movement_date' => $lastMovementDate,
                    'age_0_30' => (float) $age0_30,
                    'age_31_60' => (float) $age31_60,
                    'age_61_90' => (float) $age61_90,
                    'age_91_180' => (float) $age91_180,
                    'age_over_180' => (float) $ageOver180,
                ];
            }

            return $itemData;
        })->flatten(1)->filter(function ($data) {
            return $data['quantity'] > 0;
        });

        return view('inventory.reports.inventory-aging', compact(
            'reportData',
            'categoryId',
            'locationId',
            'branchId',
            'categories',
            'locations',
            'branches',
            'userBranches',
            'hasMultipleBranches'
        ));
    }

    public function inventoryAgingExportPdf(Request $request)
    {
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        
        $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
        if ($branchId && $branchId !== 'all_my_branches') {
            $locationsQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif (count($userBranches) > 0 && !$branchId) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif ($user->branch_id) {
            $locationsQuery->where('branch_id', $user->branch_id);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $stockService = new InventoryStockService();
        $now = Carbon::now();

        $reportData = $items->map(function ($item) use ($locationId, $branchId, $userBranches, $locations, $stockService, $now) {
            $itemData = [];
            
            foreach ($locations as $location) {
                if ($locationId && $location->id != $locationId) {
                    continue;
                }

                $quantity = $stockService->getItemStockAtLocation($item->id, $location->id);
                
                if ($quantity <= 0) {
                    continue;
                }

                $lastMovementQuery = InventoryMovement::where('item_id', $item->id)
                    ->where('location_id', $location->id);
                
                if ($branchId && $branchId !== 'all_my_branches') {
                    $lastMovementQuery->where('branch_id', $branchId);
                } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                    $lastMovementQuery->whereIn('branch_id', $userBranches);
                }

                $lastMovement = $lastMovementQuery->orderBy('movement_date', 'desc')->first();
                
                $lastMovementDate = $lastMovement ? $lastMovement->movement_date : null;
                $ageInDays = $lastMovementDate ? $now->diffInDays($lastMovementDate) : 0;

                $age0_30 = 0;
                $age31_60 = 0;
                $age61_90 = 0;
                $age91_180 = 0;
                $ageOver180 = 0;

                if ($ageInDays <= 30) {
                    $age0_30 = $quantity;
                } elseif ($ageInDays <= 60) {
                    $age31_60 = $quantity;
                } elseif ($ageInDays <= 90) {
                    $age61_90 = $quantity;
                } elseif ($ageInDays <= 180) {
                    $age91_180 = $quantity;
                } else {
                    $ageOver180 = $quantity;
                }

                $unitCost = $item->cost_price ?? 0;
                $value = $quantity * $unitCost;

                $itemData[] = [
                    'item' => $item,
                    'location' => $location,
                    'quantity' => (float) $quantity,
                    'unit_cost' => (float) $unitCost,
                    'value' => (float) $value,
                    'last_movement_date' => $lastMovementDate,
                    'age_0_30' => (float) $age0_30,
                    'age_31_60' => (float) $age31_60,
                    'age_61_90' => (float) $age61_90,
                    'age_91_180' => (float) $age91_180,
                    'age_over_180' => (float) $ageOver180,
                ];
            }

            return $itemData;
        })->flatten(1)->filter(function ($data) {
            return $data['quantity'] > 0;
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $location = $locationId ? InventoryLocation::find($locationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $pdf = Pdf::loadView('inventory.reports.exports.inventory-aging-pdf', compact(
            'reportData',
            'category',
            'location',
            'branch',
            'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('inventory-aging-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function inventoryAgingExportExcel(Request $request)
    {
        $categoryId = $request->get('category_id');
        $locationId = $request->get('location_id');
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $query = InventoryItem::with(['category'])
            ->where('company_id', $user->company_id);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $items = $query->orderBy('name')->get();
        
        $locationsQuery = InventoryLocation::where('company_id', $user->company_id);
        if ($branchId && $branchId !== 'all_my_branches') {
            $locationsQuery->where('branch_id', $branchId);
        } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif (count($userBranches) > 0 && !$branchId) {
            $locationsQuery->whereIn('branch_id', $userBranches);
        } elseif ($user->branch_id) {
            $locationsQuery->where('branch_id', $user->branch_id);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $stockService = new InventoryStockService();
        $now = Carbon::now();

        $reportData = $items->map(function ($item) use ($locationId, $branchId, $userBranches, $locations, $stockService, $now) {
            $itemData = [];
            
            foreach ($locations as $location) {
                if ($locationId && $location->id != $locationId) {
                    continue;
                }

                $quantity = $stockService->getItemStockAtLocation($item->id, $location->id);
                
                if ($quantity <= 0) {
                    continue;
                }

                $lastMovementQuery = InventoryMovement::where('item_id', $item->id)
                    ->where('location_id', $location->id);
                
                if ($branchId && $branchId !== 'all_my_branches') {
                    $lastMovementQuery->where('branch_id', $branchId);
                } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                    $lastMovementQuery->whereIn('branch_id', $userBranches);
                }

                $lastMovement = $lastMovementQuery->orderBy('movement_date', 'desc')->first();
                
                $lastMovementDate = $lastMovement ? $lastMovement->movement_date : null;
                $ageInDays = $lastMovementDate ? $now->diffInDays($lastMovementDate) : 0;

                $age0_30 = 0;
                $age31_60 = 0;
                $age61_90 = 0;
                $age91_180 = 0;
                $ageOver180 = 0;

                if ($ageInDays <= 30) {
                    $age0_30 = $quantity;
                } elseif ($ageInDays <= 60) {
                    $age31_60 = $quantity;
                } elseif ($ageInDays <= 90) {
                    $age61_90 = $quantity;
                } elseif ($ageInDays <= 180) {
                    $age91_180 = $quantity;
                } else {
                    $ageOver180 = $quantity;
                }

                $unitCost = $item->cost_price ?? 0;
                $value = $quantity * $unitCost;

                $itemData[] = [
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'category' => $item->category->name ?? 'N/A',
                    'location' => $location->name,
                    'quantity' => (float) $quantity,
                    'unit_cost' => (float) $unitCost,
                    'value' => (float) $value,
                    'last_movement_date' => $lastMovementDate ? $lastMovementDate->format('Y-m-d') : 'N/A',
                    'age_0_30' => (float) $age0_30,
                    'age_31_60' => (float) $age31_60,
                    'age_61_90' => (float) $age61_90,
                    'age_91_180' => (float) $age91_180,
                    'age_over_180' => (float) $ageOver180,
                ];
            }

            return $itemData;
        })->flatten(1)->filter(function ($data) {
            return $data['quantity'] > 0;
        });

        $category = $categoryId ? InventoryCategory::find($categoryId) : null;
        $location = $locationId ? InventoryLocation::find($locationId) : null;
        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        return Excel::download(new class($reportData, $category, $location, $branch, $company) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithTitle {
            protected $data;
            protected $category;
            protected $location;
            protected $branch;
            protected $company;

            public function __construct($data, $category, $location, $branch, $company)
            {
                $this->data = collect($data);
                $this->category = $category;
                $this->location = $location;
                $this->branch = $branch;
                $this->company = $company;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'Item Code',
                    'Item Name',
                    'Category',
                    'Location',
                    'Quantity on Hand',
                    'Unit Cost',
                    'Value (TZS)',
                    'Last Movement Date',
                    '0-30 Days',
                    '31-60 Days',
                    '61-90 Days',
                    '91-180 Days',
                    '>180 Days'
                ];
            }

            public function map($item): array
            {
                return [
                    $item['item_code'],
                    $item['item_name'],
                    $item['category'],
                    $item['location'],
                    number_format($item['quantity'], 2),
                    number_format($item['unit_cost'], 2),
                    number_format($item['value'], 2),
                    $item['last_movement_date'],
                    number_format($item['age_0_30'], 2),
                    number_format($item['age_31_60'], 2),
                    number_format($item['age_61_90'], 2),
                    number_format($item['age_91_180'], 2),
                    number_format($item['age_over_180'], 2),
                ];
            }

            public function title(): string
            {
                return 'Inventory Aging';
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
                ];
            }
        }, 'inventory-aging-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ==================== CATEGORY PERFORMANCE REPORT ====================
    
    /**
     * Category Performance Report
     */
    public function categoryPerformance(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();

        $reportData = $categories->map(function ($category) use ($dateFrom, $dateTo, $branchId, $userBranches) {
            // Get sales data for items in this category
            $salesQuery = SalesInvoiceItem::whereHas('inventoryItem', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo, $branchId, $userBranches) {
                $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
                
                if ($branchId && $branchId !== 'all_my_branches') {
                    $q->where('branch_id', $branchId);
                } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                    $q->whereIn('branch_id', $userBranches);
                }
            });

            $totalSales = $salesQuery->sum(DB::raw('(quantity * unit_price) - COALESCE(discount_amount, 0)')) ?? 0;
            $unitsSold = $salesQuery->sum('quantity') ?? 0;

            // Get cost of sales from movements
            $itemIds = InventoryItem::where('category_id', $category->id)->pluck('id');
            
            $costQuery = InventoryMovement::whereIn('item_id', $itemIds)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFrom, $dateTo]);
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costQuery->sum('total_cost') ?? 0;

            $grossProfit = $totalSales - $costOfSales;
            $grossMargin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;

            // Get top selling item
            $topItem = SalesInvoiceItem::whereHas('inventoryItem', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo, $branchId, $userBranches) {
                $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
                
                if ($branchId && $branchId !== 'all_my_branches') {
                    $q->where('branch_id', $branchId);
                } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                    $q->whereIn('branch_id', $userBranches);
                }
            })
            ->select('inventory_item_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('inventory_item_id')
            ->orderBy('total_qty', 'desc')
            ->first();

            $topSellingItem = $topItem ? InventoryItem::find($topItem->inventory_item_id) : null;

            return [
                'category' => $category,
                'total_sales' => (float) $totalSales,
                'cost_of_sales' => (float) $costOfSales,
                'gross_profit' => (float) $grossProfit,
                'gross_margin' => (float) $grossMargin,
                'units_sold' => (float) $unitsSold,
                'top_selling_item' => $topSellingItem,
            ];
        })->filter(function ($data) {
            return $data['total_sales'] > 0 || $data['units_sold'] > 0;
        });

        if (count($userBranches) > 0) {
            $branches = \App\Models\Branch::whereIn('id', $userBranches)->orderBy('name')->get();
        } else {
            $branches = \App\Models\Branch::where('company_id', $user->company_id)->orderBy('name')->get();
        }

        return view('inventory.reports.category-performance', compact(
            'reportData',
            'dateFrom',
            'dateTo',
            'branchId',
            'branches',
            'userBranches',
            'hasMultipleBranches'
        ));
    }

    public function categoryPerformanceExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();

        $reportData = $categories->map(function ($category) use ($dateFrom, $dateTo, $branchId, $userBranches) {
            $salesQuery = SalesInvoiceItem::whereHas('inventoryItem', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo, $branchId, $userBranches) {
                $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
                
                if ($branchId && $branchId !== 'all_my_branches') {
                    $q->where('branch_id', $branchId);
                } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                    $q->whereIn('branch_id', $userBranches);
                }
            });

            $totalSales = $salesQuery->sum(DB::raw('(quantity * unit_price) - COALESCE(discount_amount, 0)')) ?? 0;
            $unitsSold = $salesQuery->sum('quantity') ?? 0;

            $itemIds = InventoryItem::where('category_id', $category->id)->pluck('id');
            
            $costQuery = InventoryMovement::whereIn('item_id', $itemIds)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFrom, $dateTo]);
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costQuery->sum('total_cost') ?? 0;

            $grossProfit = $totalSales - $costOfSales;
            $grossMargin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;

            $topItem = SalesInvoiceItem::whereHas('inventoryItem', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo, $branchId, $userBranches) {
                $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
                
                if ($branchId && $branchId !== 'all_my_branches') {
                    $q->where('branch_id', $branchId);
                } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                    $q->whereIn('branch_id', $userBranches);
                }
            })
            ->select('inventory_item_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('inventory_item_id')
            ->orderBy('total_qty', 'desc')
            ->first();

            $topSellingItem = $topItem ? InventoryItem::find($topItem->inventory_item_id) : null;

            return [
                'category' => $category,
                'total_sales' => (float) $totalSales,
                'cost_of_sales' => (float) $costOfSales,
                'gross_profit' => (float) $grossProfit,
                'gross_margin' => (float) $grossMargin,
                'units_sold' => (float) $unitsSold,
                'top_selling_item' => $topSellingItem,
            ];
        })->filter(function ($data) {
            return $data['total_sales'] > 0 || $data['units_sold'] > 0;
        });

        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        $pdf = Pdf::loadView('inventory.reports.exports.category-performance-pdf', compact(
            'reportData',
            'dateFromCarbon',
            'dateToCarbon',
            'branch',
            'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('category-performance-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function categoryPerformanceExportExcel(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        
        $user = auth()->user();
        $userBranches = $user->branches()->pluck('branches.id')->toArray();
        $hasMultipleBranches = count($userBranches) > 1;
        
        $defaultBranchId = null;
        if ($hasMultipleBranches) {
            $defaultBranchId = $request->get('branch_id', 'all_my_branches');
        } else {
            $defaultBranchId = $request->get('branch_id', session('branch_id') ?? ($userBranches[0] ?? null));
        }
        
        $branchId = $defaultBranchId;

        $categories = InventoryCategory::where('company_id', $user->company_id)->orderBy('name')->get();

        $reportData = $categories->map(function ($category) use ($dateFrom, $dateTo, $branchId, $userBranches) {
            $salesQuery = SalesInvoiceItem::whereHas('inventoryItem', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo, $branchId, $userBranches) {
                $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
                
                if ($branchId && $branchId !== 'all_my_branches') {
                    $q->where('branch_id', $branchId);
                } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                    $q->whereIn('branch_id', $userBranches);
                }
            });

            $totalSales = $salesQuery->sum(DB::raw('(quantity * unit_price) - COALESCE(discount_amount, 0)')) ?? 0;
            $unitsSold = $salesQuery->sum('quantity') ?? 0;

            $itemIds = InventoryItem::where('category_id', $category->id)->pluck('id');
            
            $costQuery = InventoryMovement::whereIn('item_id', $itemIds)
                ->where('movement_type', 'sold')
                ->whereBetween('movement_date', [$dateFrom, $dateTo]);
            
            if ($branchId && $branchId !== 'all_my_branches') {
                $costQuery->where('branch_id', $branchId);
            } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                $costQuery->whereIn('branch_id', $userBranches);
            }

            $costOfSales = $costQuery->sum('total_cost') ?? 0;

            $grossProfit = $totalSales - $costOfSales;
            $grossMargin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;

            $topItem = SalesInvoiceItem::whereHas('inventoryItem', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->whereHas('salesInvoice', function ($q) use ($dateFrom, $dateTo, $branchId, $userBranches) {
                $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
                
                if ($branchId && $branchId !== 'all_my_branches') {
                    $q->where('branch_id', $branchId);
                } elseif ($branchId === 'all_my_branches' && count($userBranches) > 0) {
                    $q->whereIn('branch_id', $userBranches);
                }
            })
            ->select('inventory_item_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('inventory_item_id')
            ->orderBy('total_qty', 'desc')
            ->first();

            $topSellingItem = $topItem ? InventoryItem::find($topItem->inventory_item_id) : null;

            return [
                'category' => $category->name,
                'total_sales' => (float) $totalSales,
                'cost_of_sales' => (float) $costOfSales,
                'gross_profit' => (float) $grossProfit,
                'gross_margin' => (float) $grossMargin,
                'units_sold' => (float) $unitsSold,
                'top_selling_item' => $topSellingItem ? $topSellingItem->name : 'N/A',
            ];
        })->filter(function ($data) {
            return $data['total_sales'] > 0 || $data['units_sold'] > 0;
        });

        $branch = null;
        if ($branchId && $branchId !== 'all_my_branches') {
            $branch = \App\Models\Branch::find($branchId);
        } elseif ($branchId === 'all_my_branches') {
            $branch = (object) ['name' => 'All My Branches'];
        }
        $company = \App\Models\Company::find($user->company_id);

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        return Excel::download(new class($reportData, $dateFromCarbon, $dateToCarbon, $branch, $company) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithTitle {
            protected $data;
            protected $dateFrom;
            protected $dateTo;
            protected $branch;
            protected $company;

            public function __construct($data, $dateFrom, $dateTo, $branch, $company)
            {
                $this->data = $data;
                $this->dateFrom = $dateFrom;
                $this->dateTo = $dateTo;
                $this->branch = $branch;
                $this->company = $company;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'Category / Dept',
                    'Total Sales (TZS)',
                    'Cost of Sales (TZS)',
                    'Gross Profit',
                    'Gross Margin %',
                    'Units Sold',
                    'Top Selling Item'
                ];
            }

            public function map($item): array
            {
                return [
                    $item['category'],
                    number_format($item['total_sales'], 2),
                    number_format($item['cost_of_sales'], 2),
                    number_format($item['gross_profit'], 2),
                    number_format($item['gross_margin'], 2) . '%',
                    number_format($item['units_sold'], 2),
                    $item['top_selling_item'],
                ];
            }

            public function title(): string
            {
                return 'Category Performance';
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
                ];
            }
        }, 'category-performance-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Full Inventory Count Report
     */
    public function fullInventoryCountReport(Request $request)
    {
        $companyId = auth()->user()->company_id;
        
        $query = \App\Models\Inventory\CountSession::with(['period', 'location', 'entries.item', 'entries.variance'])
            ->where('company_id', $companyId);

        if ($request->filled('period_id')) {
            $query->where('count_period_id', $request->period_id);
        }

        if ($request->filled('location_id')) {
            $query->where('inventory_location_id', $request->location_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sessions = $query->orderBy('created_at', 'desc')->get();

        $periods = \App\Models\Inventory\CountPeriod::where('company_id', $companyId)->get();
        $locations = InventoryLocation::where('company_id', $companyId)->get();

        return view('inventory.reports.full-inventory-count', compact('sessions', 'periods', 'locations'));
    }

    /**
     * Variance Summary Report
     */
    public function varianceSummaryReport(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = \App\Models\Inventory\CountVariance::with(['entry.session', 'entry.item', 'entry.location'])
            ->whereHas('entry.session', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });

        if ($request->filled('session_id')) {
            $query->whereHas('entry', function($q) use ($request) {
                $q->where('count_session_id', $request->session_id);
            });
        }

        if ($request->filled('variance_type')) {
            $query->where('variance_type', $request->variance_type);
        }

        if ($request->filled('is_high_value')) {
            $query->where('is_high_value', $request->is_high_value);
        }

        $variances = $query->get();

        // Summary statistics
        $summary = [
            'total_variances' => $variances->count(),
            'zero_variances' => $variances->where('variance_type', 'zero')->count(),
            'positive_variances' => $variances->where('variance_type', 'positive')->count(),
            'negative_variances' => $variances->where('variance_type', 'negative')->count(),
            'high_value_variances' => $variances->where('is_high_value', true)->count(),
            'total_variance_value' => $variances->sum('variance_value'),
            'total_positive_value' => $variances->where('variance_type', 'positive')->sum('variance_value'),
            'total_negative_value' => abs($variances->where('variance_type', 'negative')->sum('variance_value')),
        ];

        $sessions = \App\Models\Inventory\CountSession::where('company_id', $companyId)->get();

        return view('inventory.reports.variance-summary', compact('variances', 'summary', 'sessions'));
    }

    /**
     * Variance Value Report
     */
    public function varianceValueReport(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = \App\Models\Inventory\CountVariance::with(['entry.session', 'entry.item', 'entry.location'])
            ->whereHas('entry.session', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('variance_value', '>', 0);

        if ($request->filled('session_id')) {
            $query->whereHas('entry', function($q) use ($request) {
                $q->where('count_session_id', $request->session_id);
            });
        }

        if ($request->filled('min_value')) {
            $query->where('variance_value', '>=', $request->min_value);
        }

        if ($request->filled('max_value')) {
            $query->where('variance_value', '<=', $request->max_value);
        }

        $variances = $query->orderBy('variance_value', 'desc')->get();

        $sessions = \App\Models\Inventory\CountSession::where('company_id', $companyId)->get();

        return view('inventory.reports.variance-value', compact('variances', 'sessions'));
    }

    /**
     * High-Value Items Scorecard
     */
    public function highValueItemsScorecard(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = \App\Models\Inventory\CountVariance::with(['entry.session', 'entry.item', 'entry.location'])
            ->whereHas('entry.session', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('is_high_value', true);

        if ($request->filled('session_id')) {
            $query->whereHas('entry', function($q) use ($request) {
                $q->where('count_session_id', $request->session_id);
            });
        }

        $highValueVariances = $query->orderBy('variance_value', 'desc')->get();

        // Group by item
        $itemScorecard = $highValueVariances->groupBy('item_id')->map(function ($variances, $itemId) {
            $item = $variances->first()->item;
            return [
                'item' => $item,
                'variance_count' => $variances->count(),
                'total_variance_value' => $variances->sum('variance_value'),
                'avg_variance_percentage' => $variances->avg('variance_percentage'),
                'variances' => $variances,
            ];
        })->sortByDesc('total_variance_value');

        $sessions = \App\Models\Inventory\CountSession::where('company_id', $companyId)->get();

        return view('inventory.reports.high-value-scorecard', compact('itemScorecard', 'sessions'));
    }

    /**
     * Expiry & Damaged Stock Report
     */
    public function expiryDamagedStockReport(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = \App\Models\Inventory\CountEntry::with(['item', 'session', 'location'])
            ->whereHas('session', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where(function($q) {
                $q->whereIn('condition', ['damaged', 'expired', 'obsolete'])
                  ->orWhereNotNull('expiry_date');
            });

        if ($request->filled('session_id')) {
            $query->where('count_session_id', $request->session_id);
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->filled('expiry_from')) {
            $query->whereDate('expiry_date', '>=', $request->expiry_from);
        }

        if ($request->filled('expiry_to')) {
            $query->whereDate('expiry_date', '<=', $request->expiry_to);
        }

        $entries = $query->get();

        // Group by condition
        $byCondition = $entries->groupBy('condition')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_quantity' => $group->sum('physical_quantity'),
                'items' => $group,
            ];
        });

        // Group by expiry status
        $expired = $entries->filter(function($entry) {
            return $entry->expiry_date && $entry->expiry_date->isPast();
        });

        $expiringSoon = $entries->filter(function($entry) {
            return $entry->expiry_date && 
                   $entry->expiry_date->isFuture() && 
                   $entry->expiry_date->diffInDays(now()) <= 30;
        });

        $sessions = \App\Models\Inventory\CountSession::where('company_id', $companyId)->get();

        return view('inventory.reports.expiry-damaged-stock', compact('entries', 'byCondition', 'expired', 'expiringSoon', 'sessions'));
    }

    /**
     * Cycle Count Performance Report
     */
    public function cycleCountPerformanceReport(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = \App\Models\Inventory\CountPeriod::with(['sessions.entries.variance', 'location'])
            ->where('company_id', $companyId)
            ->where('count_type', 'cycle_count');

        if ($request->filled('date_from')) {
            $query->whereDate('count_start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('count_end_date', '<=', $request->date_to);
        }

        $periods = $query->get();

        $performance = $periods->map(function ($period) {
            $totalEntries = $period->sessions->sum(function($session) {
                return $session->entries->count();
            });

            $countedEntries = $period->sessions->sum(function($session) {
                return $session->entries->where('status', '!=', 'pending')->count();
            });

            $variances = $period->sessions->flatMap(function($session) {
                return $session->entries->pluck('variance')->filter();
            });

            $accuracy = $totalEntries > 0 ? (($totalEntries - $variances->where('variance_type', '!=', 'zero')->count()) / $totalEntries) * 100 : 0;

            return [
                'period' => $period,
                'total_entries' => $totalEntries,
                'counted_entries' => $countedEntries,
                'completion_rate' => $totalEntries > 0 ? ($countedEntries / $totalEntries) * 100 : 0,
                'variance_count' => $variances->count(),
                'zero_variance_count' => $variances->where('variance_type', 'zero')->count(),
                'accuracy_rate' => $accuracy,
                'total_variance_value' => $variances->sum('variance_value'),
            ];
        });

        return view('inventory.reports.cycle-count-performance', compact('performance'));
    }

    /**
     * Year-end Stock Valuation Report (IPSAS/IFRS compliant)
     */
    public function yearEndStockValuationReport(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $asOfDate = $request->get('as_of_date', $year . '-12-31');

        // Get all count sessions for the year
        $sessions = \App\Models\Inventory\CountSession::with(['entries.item', 'entries.variance', 'location'])
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->where('status', 'completed')
            ->get();

        // Get all items with their valuations
        $items = InventoryItem::where('company_id', $companyId)
            ->where('track_stock', true)
            ->with(['category'])
            ->get();

        $stockService = new InventoryStockService();
        $costService = new InventoryCostService();
        $systemCostMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';

        $valuation = $items->map(function ($item) use ($stockService, $costService, $systemCostMethod, $sessions, $asOfDate) {
            // Get stock at all locations
            $locations = InventoryLocation::where('company_id', $item->company_id)->get();
            
            $totalQty = 0;
            $totalValue = 0;
            $locationBreakdown = [];

            foreach ($locations as $location) {
                $qty = $stockService->getItemStockAtLocation($item->id, $location->id);
                
                if ($systemCostMethod === 'fifo') {
                    $inventoryValue = $costService->getInventoryValue($item->id);
                    $unitCost = $inventoryValue['average_cost'] > 0 ? $inventoryValue['average_cost'] : ($item->cost_price ?? 0);
                } else {
                    $unitCost = $item->cost_price ?? 0;
                }

                $value = $qty * $unitCost;
                $totalQty += $qty;
                $totalValue += $value;

                if ($qty > 0) {
                    $locationBreakdown[] = [
                        'location' => $location,
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                        'value' => $value,
                    ];
                }
            }

            // Get count variances for this item
            $variances = $sessions->flatMap(function($session) use ($item) {
                return $session->entries->where('item_id', $item->id)->pluck('variance')->filter();
            });

            return [
                'item' => $item,
                'total_quantity' => $totalQty,
                'unit_cost' => $totalQty > 0 ? ($totalValue / $totalQty) : 0,
                'total_value' => $totalValue,
                'location_breakdown' => $locationBreakdown,
                'variance_count' => $variances->count(),
                'variance_value' => $variances->sum('variance_value'),
            ];
        })->filter(function($item) {
            return $item['total_quantity'] > 0;
        })->sortByDesc('total_value');

        $summary = [
            'total_items' => $valuation->count(),
            'total_quantity' => $valuation->sum('total_quantity'),
            'total_value' => $valuation->sum('total_value'),
            'total_variances' => $valuation->sum('variance_count'),
            'total_variance_value' => $valuation->sum('variance_value'),
        ];

        return view('inventory.reports.year-end-stock-valuation', compact('valuation', 'summary', 'year', 'asOfDate'));
    }
}
