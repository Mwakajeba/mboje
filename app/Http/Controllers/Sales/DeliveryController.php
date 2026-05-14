<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Delivery;
use App\Models\Sales\DeliveryItem;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesInvoice;
use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Vinkla\Hashids\Facades\Hashids;

class DeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {
            $deliveries = Delivery::with(['customer', 'salesOrder', 'createdBy'])
                ->whereHas('customer', function ($query) use ($user) {
                    $query->whereHas('branch', function ($q) use ($user) {
                        $q->where('company_id', $user->company_id);
                    });
                })
                ->when($user->branch_id, function ($query) use ($user) {
                    return $query->where('branch_id', $user->branch_id);
                });

            return DataTables::of($deliveries)
                ->addColumn('actions', function ($delivery) {
                    $actions = '<div class="d-flex gap-2">';
                    $actions .= '<a href="' . route('sales.deliveries.show', Hashids::encode($delivery->id)) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                    
                    if ($delivery->status === 'draft') {
                        $actions .= '<a href="' . route('sales.deliveries.edit', Hashids::encode($delivery->id)) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bx bx-edit"></i></a>';
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDelivery(\'' . Hashids::encode($delivery->id) . '\', \'' . addslashes($delivery->delivery_number) . '\')" title="Delete"><i class="bx bx-trash"></i></button>';
                    }
                    
                    if ($delivery->status === 'draft') {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-info" onclick="startPicking(\'' . Hashids::encode($delivery->id) . '\')" title="Start Picking"><i class="bx bx-package"></i></button>';
                    }
                    
                    if ($delivery->status === 'picking') {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-warning" onclick="completePicking(\'' . Hashids::encode($delivery->id) . '\')" title="Complete Picking"><i class="bx bx-check"></i></button>';
                    }
                    
                    if ($delivery->status === 'packed') {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-primary" onclick="startDelivery(\'' . Hashids::encode($delivery->id) . '\')" title="Start Delivery"><i class="bx bx-truck"></i></button>';
                    }
                    
                    if ($delivery->status === 'in_transit') {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-success" onclick="completeDelivery(\'' . Hashids::encode($delivery->id) . '\')" title="Complete Delivery"><i class="bx bx-check-circle"></i></button>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->addColumn('status_badge', function ($delivery) {
                    return $delivery->status_badge;
                })
                ->addColumn('formatted_date', function ($delivery) {
                    return format_date($delivery->delivery_date, 'M d, Y');
                })
                ->addColumn('customer_name', function ($delivery) {
                    return $delivery->customer->name ?? 'N/A';
                })
                ->addColumn('order_number', function ($delivery) {
                    return $delivery->salesOrder ? $delivery->salesOrder->order_number : 'N/A';
                })
                ->addColumn('delivery_type_text', function ($delivery) {
                    return $delivery->delivery_type_text;
                })
                ->addColumn('progress', function ($delivery) {
                    return '<div class="progress" style="height: 20px;">
                                <div class="progress-bar" role="progressbar" style="width: ' . $delivery->progress_percentage . '%" 
                                     aria-valuenow="' . $delivery->progress_percentage . '" aria-valuemin="0" aria-valuemax="100">
                                    ' . $delivery->progress_percentage . '%
                                </div>
                            </div>';
                })
                ->rawColumns(['actions', 'status_badge', 'progress'])
                ->make(true);
        }

        // Get stats for the dashboard
        $deliveries = Delivery::with(['customer', 'createdBy'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            });

        $stats = [
            'total' => $deliveries->count(),
            'draft' => $deliveries->clone()->where('status', 'draft')->count(),
            'picking' => $deliveries->clone()->where('status', 'picking')->count(),
            'packed' => $deliveries->clone()->where('status', 'packed')->count(),
            'in_transit' => $deliveries->clone()->where('status', 'in_transit')->count(),
            'delivered' => $deliveries->clone()->where('status', 'delivered')->count(),
            'cancelled' => $deliveries->clone()->where('status', 'cancelled')->count(),
        ];

        return view('sales.deliveries.index', compact('stats'));
    }

    /**
     * Show the form for creating a new delivery from a sales invoice (Copy To).
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

        $order = null;
        return view('sales.deliveries.create', compact('order', 'invoice', 'customers', 'inventoryItems'))->with('items', $inventoryItems);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // Check if creating from sales order
        $orderId = $request->get('order_id');
        $order = null;
        $invoice = null;
        
        if ($orderId) {
            $decodedOrderId = Hashids::decode($orderId)[0] ?? null;
            if (!$decodedOrderId) {
                abort(404);
            }
            
            $order = SalesOrder::with(['customer', 'items.inventoryItem'])
                ->whereHas('customer', function ($query) use ($user) {
                    $query->whereHas('branch', function ($q) use ($user) {
                        $q->where('company_id', $user->company_id);
                    });
                })
                ->when($user->branch_id, function ($query) use ($user) {
                    return $query->where('branch_id', $user->branch_id);
                })
                ->where('status', 'approved')
                ->findOrFail($decodedOrderId);
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

        return view('sales.deliveries.create', compact('order', 'invoice', 'customers', 'inventoryItems'))->with('items', $inventoryItems);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'delivery_date' => 'required|date',
            'delivery_time' => 'nullable|date_format:H:i',
            'delivery_type' => 'required|in:pickup,delivery,shipping',
            'delivery_address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'vehicle_number' => 'nullable|string|max:20',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            'delivery_instructions' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_weight' => 'nullable|numeric|min:0',
            'items.*.location' => 'nullable|string|max:100',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
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

            $branchId = session('branch_id') ?? $user->branch_id ?? null;
            // Create delivery
            $delivery = Delivery::create([
                'customer_id' => $request->customer_id,
                'sales_order_id' => $request->sales_order_id,
                'delivery_date' => $request->delivery_date,
                'delivery_time' => $request->delivery_time,
                'delivery_type' => $request->delivery_type,
                'delivery_address' => $request->delivery_address,
                'contact_person' => $request->contact_person,
                'contact_phone' => $request->contact_phone,
                'vehicle_number' => $request->vehicle_number,
                'driver_name' => $request->driver_name,
                'driver_phone' => $request->driver_phone,
                'delivery_instructions' => $request->delivery_instructions,
                'notes' => $request->notes,
                'has_transport_cost' => $request->boolean('has_transport_cost'),
                'transport_cost' => $request->transport_cost ?? 0,
                'branch_id' => $branchId,
                'company_id' => $user->company_id,
                'created_by' => $user->id,
            ]);

            // Create delivery items
            $totalQuantity = 0;
            $totalWeight = 0;

            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::find($itemData['inventory_item_id']);
                
                $deliveryItem = DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'sales_order_item_id' => $itemData['sales_order_item_id'] ?? null,
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'quantity' => $itemData['quantity'],
                    'unit_of_measure' => $inventoryItem->unit_of_measure,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'vat_type' => $itemData['vat_type'] ?? 'inclusive',
                    'vat_rate' => $itemData['vat_rate'] ?? 0,
                    'vat_amount' => $itemData['vat_amount'] ?? 0,
                    'line_total' => $itemData['line_total'] ?? 0,
                    'unit_weight' => $itemData['unit_weight'] ?? 0,
                    'location' => $itemData['location'] ?? null,
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);

                $totalQuantity += $itemData['quantity'];
                $totalWeight += ($itemData['quantity'] * ($itemData['unit_weight'] ?? 0));
            }

            // Update delivery totals
            $delivery->update([
                'total_quantity' => $totalQuantity,
                'total_weight' => $totalWeight,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery created successfully',
                'redirect_url' => route('sales.deliveries.show', Hashids::encode($delivery->id))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating delivery: ' . $e->getMessage()
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
        
        $delivery = Delivery::with(['customer', 'items.inventoryItem', 'createdBy', 'pickedBy', 'packedBy', 'deliveredBy', 'receivedBy', 'salesOrder'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        return view('sales.deliveries.show', compact('delivery'));
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
        
        $delivery = Delivery::with(['customer', 'items.inventoryItem', 'createdBy'])
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
        if ($delivery->status !== 'draft') {
            return redirect()->route('sales.deliveries.show', Hashids::encode($delivery->id))
                ->with('error', 'Only draft deliveries can be edited.');
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

        return view('sales.deliveries.edit', compact('delivery', 'customers', 'inventoryItems'))->with('items', $inventoryItems);
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
        
        $delivery = Delivery::whereHas('customer', function ($query) use ($user) {
            $query->whereHas('branch', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        })
        ->when($user->branch_id, function ($query) use ($user) {
            return $query->where('branch_id', $user->branch_id);
        })
        ->findOrFail($decodedId);

        // Only allow updating if status is draft
        if ($delivery->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft deliveries can be updated.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'delivery_date' => 'required|date',
            'delivery_time' => 'nullable|date_format:H:i',
            'delivery_type' => 'required|in:pickup,delivery,shipping',
            'delivery_address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'vehicle_number' => 'nullable|string|max:20',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:20',
            'delivery_instructions' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_weight' => 'nullable|numeric|min:0',
            'items.*.location' => 'nullable|string|max:100',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
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

            // Update delivery
            $delivery->update([
                'customer_id' => $request->customer_id,
                'delivery_date' => $request->delivery_date,
                'delivery_time' => $request->delivery_time,
                'delivery_type' => $request->delivery_type,
                'delivery_address' => $request->delivery_address,
                'contact_person' => $request->contact_person,
                'contact_phone' => $request->contact_phone,
                'vehicle_number' => $request->vehicle_number,
                'driver_name' => $request->driver_name,
                'driver_phone' => $request->driver_phone,
                'delivery_instructions' => $request->delivery_instructions,
                'notes' => $request->notes,
                'has_transport_cost' => $request->boolean('has_transport_cost'),
                'transport_cost' => $request->transport_cost ?? 0,
                'updated_by' => $user->id,
            ]);

            // Delete existing items
            $delivery->items()->delete();

            // Create new delivery items
            $totalQuantity = 0;
            $totalWeight = 0;

            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::find($itemData['inventory_item_id']);
                
                $deliveryItem = DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'sales_order_item_id' => $itemData['sales_order_item_id'] ?? null,
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'quantity' => $itemData['quantity'],
                    'unit_of_measure' => $inventoryItem->unit_of_measure,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'vat_type' => $itemData['vat_type'] ?? 'inclusive',
                    'vat_rate' => $itemData['vat_rate'] ?? 0,
                    'vat_amount' => $itemData['vat_amount'] ?? 0,
                    'line_total' => $itemData['line_total'] ?? 0,
                    'unit_weight' => $itemData['unit_weight'] ?? 0,
                    'location' => $itemData['location'] ?? null,
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);

                $totalQuantity += $itemData['quantity'];
                $totalWeight += ($itemData['quantity'] * ($itemData['unit_weight'] ?? 0));
            }

            // Update delivery totals
            $delivery->update([
                'total_quantity' => $totalQuantity,
                'total_weight' => $totalWeight,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery updated successfully',
                'redirect_url' => route('sales.deliveries.show', Hashids::encode($delivery->id))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating delivery: ' . $e->getMessage()
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
        
        $delivery = Delivery::whereHas('customer', function ($query) use ($user) {
            $query->whereHas('branch', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            });
        })
        ->when($user->branch_id, function ($query) use ($user) {
            return $query->where('branch_id', $user->branch_id);
        })
        ->findOrFail($decodedId);

        // Only allow deletion if status is draft
        if ($delivery->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft deliveries can be deleted.'
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            // Delete inventory movements for this delivery (this handles stock reversal)
            \App\Models\Inventory\Movement::where('reference_type', 'delivery')
                ->where('reference_id', $delivery->id)
                ->delete();
            
            // Delete delivery items first
            $delivery->items()->delete();
            
            // Delete delivery
            $delivery->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting delivery: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start picking process
     */
    public function startPicking($id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $delivery = Delivery::with(['customer', 'items.inventoryItem', 'createdBy', 'pickedBy', 'packedBy', 'deliveredBy', 'receivedBy', 'salesOrder'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        if (!$delivery->canStartPicking()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot start picking for this delivery.'
            ], 422);
        }

        if ($delivery->startPicking($user->id)) {
            return response()->json([
                'success' => true,
                'message' => 'Picking started successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start picking.'
            ], 422);
        }
    }

    /**
     * Complete picking process
     */
    public function completePicking($id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $delivery = Delivery::with(['customer', 'items.inventoryItem', 'createdBy', 'pickedBy', 'packedBy', 'deliveredBy', 'receivedBy', 'salesOrder'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        if (!$delivery->canCompletePicking()) {
            $unpickedCount = $delivery->items()->where('picked', false)->count();
            $message = 'Cannot complete picking for this delivery.';
            
            if ($delivery->status !== 'picking') {
                $message = 'Delivery is not in picking status.';
            } elseif ($unpickedCount > 0) {
                $message = "Please pick all items first. {$unpickedCount} item(s) still need to be picked.";
            }
            
            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }

        if ($delivery->completePicking($user->id)) {
            return response()->json([
                'success' => true,
                'message' => 'Picking completed successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete picking.'
            ], 422);
        }
    }

    /**
     * Pick all remaining items in a delivery
     */
    public function pickAllItems($id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $delivery = Delivery::with(['items'])
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        if ($delivery->status !== 'picking') {
            return response()->json([
                'success' => false,
                'message' => 'Delivery is not in picking status.'
            ], 422);
        }

        $unpicked = $delivery->items()->where('picked', false)->get();
        foreach ($unpicked as $item) {
            $item->markAsPicked($user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'All items marked as picked.'
        ]);
    }

    /**
     * Pick a single delivery item
     */
    public function pickItem($itemId)
    {
        $user = Auth::user();

        $decodedItemId = Hashids::decode($itemId)[0] ?? null;
        if (!$decodedItemId) {
            abort(404);
        }

        $item = DeliveryItem::with('delivery')
            ->findOrFail($decodedItemId);

        $delivery = $item->delivery;
        if ($user->branch_id && $delivery->branch_id !== $user->branch_id) {
            abort(403);
        }

        if (!$item->markAsPicked($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot pick this item in current status.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item marked as picked.'
        ]);
    }

    /**
     * Start delivery process
     */
    public function startDelivery($id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $delivery = Delivery::with(['customer', 'items.inventoryItem', 'createdBy', 'pickedBy', 'packedBy', 'deliveredBy', 'receivedBy', 'salesOrder'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        if (!$delivery->canStartDelivery()) {
            $message = 'Cannot start delivery for this delivery.';
            if ($delivery->status !== 'packed') {
                $message = 'Delivery is not packed yet.';
            } else {
                $unpacked = $delivery->items()->where('packed', false)->count();
                if ($unpacked > 0) {
                    $message = "Please pack all items first. {$unpacked} item(s) pending.";
                }
            }
            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }

        if ($delivery->startDelivery($user->id)) {
            return response()->json([
                'success' => true,
                'message' => 'Delivery started successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start delivery.'
            ], 422);
        }
    }

    /**
     * Pack all remaining items
     */
    public function packAllItems($id)
    {
        $user = Auth::user();

        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }

        $delivery = Delivery::with(['items'])
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        if ($delivery->status !== 'packed') {
            // Move to packed if all items are picked, else error
            $unpicked = $delivery->items()->where('picked', false)->count();
            if ($unpicked > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot pack. {$unpicked} item(s) not picked."
                ], 422);
            }
            $delivery->update(['status' => 'packed', 'packed_by' => $user->id, 'packed_at' => now()]);
        }

        $pending = $delivery->items()->where('packed', false)->get();
        foreach ($pending as $item) {
            $item->markAsPacked($user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'All items marked as packed.'
        ]);
    }

    /**
     * Pack a single item
     */
    public function packItem($itemId)
    {
        $user = Auth::user();
        $decodedItemId = Hashids::decode($itemId)[0] ?? null;
        if (!$decodedItemId) {
            abort(404);
        }

        $item = DeliveryItem::with('delivery')->findOrFail($decodedItemId);
        $delivery = $item->delivery;
        if ($user->branch_id && $delivery->branch_id !== $user->branch_id) {
            abort(403);
        }

        if ($delivery->status !== 'packed') {
            return response()->json([
                'success' => false,
                'message' => 'Delivery is not in packed status.'
            ], 422);
        }

        if (!$item->markAsPacked($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot pack this item in current status.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item marked as packed.'
        ]);
    }

    /**
     * Complete delivery process
     */
    public function completeDelivery(Request $request, $id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $delivery = Delivery::with(['customer', 'items.inventoryItem', 'createdBy', 'pickedBy', 'packedBy', 'deliveredBy', 'receivedBy', 'salesOrder'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        if (!$delivery->canCompleteDelivery()) {
            $message = 'Cannot complete delivery for this delivery.';
            if (!in_array($delivery->status, ['in_transit', 'packed'])) {
                $message = 'Delivery is not in transit or packed status.';
            } else {
                $undelivered = $delivery->items()->where('delivered', false)->count();
                if ($undelivered > 0) {
                    $message = "Please deliver all items first. {$undelivered} item(s) pending.";
                }
            }
            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }

        $receivedByName = $request->input('received_by_name');

        if ($delivery->completeDelivery($user->id, $receivedByName)) {
            // Automatically create invoice when delivery is completed
            try {
                $invoice = $this->createInvoiceFromDelivery($delivery);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Delivery completed successfully and invoice created',
                    'invoice_id' => $invoice->encoded_id,
                    'invoice_number' => $invoice->invoice_number
                ]);
            } catch (\Exception $e) {
                // Rollback delivery completion if invoice creation fails
                $delivery->update([
                    'status' => 'in_transit', // Revert to previous status
                    'delivered_by' => null,
                    'delivered_at' => null,
                    'received_by_name' => null,
                    'received_by' => null,
                    'received_at' => null,
                ]);
                
                // Also revert item delivery status
                $delivery->items()->update(['delivered' => false]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery completion failed due to invoice creation error: ' . $e->getMessage()
                ], 422);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete delivery.'
            ], 422);
        }
    }

    /**
     * Create invoice from completed delivery
     */
    private function createInvoiceFromDelivery($delivery)
    {
        // Check if invoice already exists for this delivery
        $existingInvoice = \App\Models\Sales\SalesInvoice::where('delivery_id', $delivery->id)->first();
        if ($existingInvoice) {
            return $existingInvoice;
        }

        DB::beginTransaction();

        try {
            $defaultInvoiceDueDays = (int) SystemSetting::getValue('inventory_default_invoice_due_days', 30);
            $paymentTerms = match ($defaultInvoiceDueDays) {
                0 => 'immediate',
                15 => 'net_15',
                30 => 'net_30',
                45 => 'net_45',
                60 => 'net_60',
                default => 'custom',
            };

            $invoice = \App\Models\Sales\SalesInvoice::create([
                'delivery_id' => $delivery->id,
                'customer_id' => $delivery->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($defaultInvoiceDueDays)->toDateString(),
                'status' => 'draft',
                'payment_terms' => $paymentTerms,
                'payment_days' => $defaultInvoiceDueDays,
                'vat_rate' => 18.00, // Default VAT rate
                'vat_type' => 'inclusive',
                'notes' => $delivery->notes,
                'terms_conditions' => '',
                'branch_id' => $delivery->branch_id,
                'company_id' => $delivery->company_id,
                'created_by' => auth()->id(),
            ]);

            // Create invoice items from delivery items
            foreach ($delivery->items as $deliveryItem) {
                \App\Models\Sales\SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'delivery_item_id' => $deliveryItem->id,
                    'inventory_item_id' => $deliveryItem->inventory_item_id,
                    'item_name' => $deliveryItem->item_name,
                    'item_code' => $deliveryItem->item_code,
                    'quantity' => $deliveryItem->quantity,
                    'unit_of_measure' => $deliveryItem->unit_of_measure,
                    'unit_price' => $deliveryItem->unit_price,
                    'vat_rate' => $deliveryItem->vat_rate,
                    'vat_type' => $deliveryItem->vat_type,
                    'vat_amount' => $deliveryItem->vat_amount,
                    'line_total' => $deliveryItem->line_total,
                ]);
            }

            // Add transport cost as separate line item if applicable
            if ($delivery->has_transport_cost && $delivery->transport_cost > 0) {
                $transportRevenueAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_transport_revenue_account')->value('value');
                
                if ($transportRevenueAccountId) {
                    \App\Models\Sales\SalesInvoiceItem::create([
                        'sales_invoice_id' => $invoice->id,
                        'inventory_item_id' => null,
                        'item_name' => 'Transport/Delivery Service',
                        'item_code' => 'TRANSPORT',
                        'quantity' => 1,
                        'unit_of_measure' => 'Service',
                        'unit_price' => $delivery->transport_cost,
                        'vat_rate' => 0,
                        'vat_type' => 'exclusive',
                        'vat_amount' => 0,
                        'line_total' => $delivery->transport_cost,
                    ]);
                }
            }

            // Update invoice totals
            $invoice->updateTotals();

            // Relink delivery inventory movements to this invoice so COGS is captured
            \App\Models\Inventory\Movement::where('reference_type', 'delivery')
                ->where('reference_id', $delivery->id)
                ->update([
                    'reference' => 'Sales Invoice: ' . $invoice->invoice_number,
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $invoice->id,
                    'movement_date' => $invoice->invoice_date,
                ]);

            // Create GL transactions (includes AR, Revenue, VAT, COGS, Inventory)
            $invoice->createDoubleEntryTransactions();

            // Update delivery status (keep as delivered since invoice is created automatically)
            $delivery->update([
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Deliver all remaining items
     */
    public function deliverAllItems($id)
    {
        $user = Auth::user();

        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) { abort(404); }

        $delivery = Delivery::with(['items'])
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        if (!in_array($delivery->status, ['in_transit', 'packed'])) {
            return response()->json(['success' => false, 'message' => 'Delivery is not in transit or packed status.'], 422);
        }

        // If still packed, move to in_transit to allow delivering
        if ($delivery->status === 'packed') {
            $delivery->update(['status' => 'in_transit']);
        }

        $pending = $delivery->items()->where('delivered', false)->get();
        foreach ($pending as $item) {
            $item->markAsDelivered($user->id);
        }

        return response()->json(['success' => true, 'message' => 'All items marked as delivered.']);
    }

    /**
     * Deliver a single item
     */
    public function deliverItem($itemId)
    {
        $user = Auth::user();
        $decodedItemId = Hashids::decode($itemId)[0] ?? null;
        if (!$decodedItemId) { abort(404); }

        $item = DeliveryItem::with('delivery')->findOrFail($decodedItemId);
        $delivery = $item->delivery;
        if ($user->branch_id && $delivery->branch_id !== $user->branch_id) { abort(403); }

        if (!in_array($delivery->status, ['in_transit', 'packed'])) {
            return response()->json(['success' => false, 'message' => 'Delivery is not in transit or packed status.'], 422);
        }

        // If still packed, move to in_transit to allow delivering
        if ($delivery->status === 'packed') {
            $delivery->update(['status' => 'in_transit']);
        }

        if (!$item->markAsDelivered($user->id)) {
            return response()->json(['success' => false, 'message' => 'Cannot deliver this item in current status.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Item marked as delivered.']);
    }

    /**
     * Generate delivery note
     */
    public function generateDeliveryNote($id)
    {
        $user = Auth::user();
        
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }
        
        $delivery = Delivery::with(['customer', 'items.inventoryItem', 'createdBy', 'pickedBy', 'packedBy', 'deliveredBy', 'receivedBy', 'salesOrder'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        $deliveryNote = $delivery->generateDeliveryNote();

        return response()->json([
            'success' => true,
            'delivery_note' => $deliveryNote
        ]);
    }

    /**
     * Show printable delivery note (HTML)
     */
    public function showDeliveryNote($id)
    {
        $user = Auth::user();
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) { abort(404); }

        $delivery = Delivery::with(['customer', 'items.inventoryItem', 'company'])
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        $company = $delivery->company ?? optional($user->branch)->company ?? null;
        
        // Get bank accounts for payment methods
        $bankAccounts = \App\Models\BankAccount::orderBy('name')->get();

        return view('sales.deliveries.note', compact('delivery', 'company', 'bankAccounts'));
    }


    /**
     * Download delivery note as PDF
     */
    public function downloadDeliveryNotePdf($id)
    {
        $user = Auth::user();
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) { abort(404); }

        $delivery = Delivery::with(['customer', 'items.inventoryItem', 'company', 'branch', 'createdBy'])
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->findOrFail($decodedId);

        $company = $delivery->company ?? optional($user->branch)->company ?? null;
        
        // Get bank accounts for payment methods
        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })->orderBy('name')->get();

        $html = view('sales.deliveries.note', compact('delivery', 'company', 'bankAccounts'))->render();

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper('A4', 'portrait');
        $fileName = 'Delivery_Note_' . $delivery->delivery_number . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Convert from sales order
     */
    public function convertFromOrder($orderId)
    {
        $user = Auth::user();
        
        $decodedOrderId = Hashids::decode($orderId)[0] ?? null;
        if (!$decodedOrderId) {
            abort(404);
        }
        
        $order = SalesOrder::with(['customer', 'items.inventoryItem'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->where('status', 'approved')
            ->findOrFail($decodedOrderId);

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer->name,
                'order_date' => format_date($order->order_date, 'Y-m-d'),
                'expected_delivery_date' => format_date($order->expected_delivery_date, 'Y-m-d'),
                'items' => $order->items->map(function ($item) {
                    return [
                        'inventory_item_id' => $item->inventory_item_id,
                        'sales_order_item_id' => $item->id,
                        'item_name' => $item->item_name,
                        'item_code' => $item->item_code,
                        'quantity' => $item->quantity,
                        'unit_of_measure' => $item->inventoryItem->unit_of_measure,
                        'unit_weight' => $item->inventoryItem->unit_weight ?? 0,
                    ];
                })
            ]
        ]);
    }

    /**
     * Convert delivery to sales invoice
     */
    public function convertToInvoice(Request $request, $id)
    {
        try {
            $decodedId = Hashids::decode($id)[0] ?? null;
            if (!$decodedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid delivery ID'
                ], 404);
            }

            $delivery = Delivery::with(['customer', 'items.inventoryItem'])->findOrFail($decodedId);
            
            // Check if delivery is delivered
            if ($delivery->status !== 'delivered') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only delivered deliveries can be converted to invoices'
                ], 422);
            }

            // Check if delivery has already been converted to an invoice
            $existingInvoice = \App\Models\Sales\SalesInvoice::where('delivery_id', $delivery->id)->first();
            if ($existingInvoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'This delivery has already been converted to an invoice. Invoice: ' . $existingInvoice->invoice_number
                ], 422);
            }

            DB::beginTransaction();

            // Create invoice
            $invoice = \App\Models\Sales\SalesInvoice::create([
                'delivery_id' => $delivery->id,
                'customer_id' => $delivery->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(), // Default 30 days
                'status' => 'draft',
                'payment_terms' => 'Net 30',
                'payment_days' => 30,
                'vat_rate' => 18.00, // Default VAT rate
                'vat_type' => 'inclusive',
                'notes' => $delivery->notes,
                'terms_conditions' => '',
                'branch_id' => $delivery->branch_id,
                'company_id' => $delivery->company_id,
                'created_by' => auth()->id(),
            ]);

            // Create invoice items from delivery items
            foreach ($delivery->items as $deliveryItem) {
                \App\Models\Sales\SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'delivery_item_id' => $deliveryItem->id,
                    'inventory_item_id' => $deliveryItem->inventory_item_id,
                    'item_name' => $deliveryItem->item_name,
                    'item_code' => $deliveryItem->item_code,
                    'quantity' => $deliveryItem->quantity,
                    'unit_of_measure' => $deliveryItem->unit_of_measure,
                    'unit_price' => $deliveryItem->unit_price,
                    'vat_rate' => $deliveryItem->vat_rate,
                    'vat_type' => $deliveryItem->vat_type,
                    'vat_amount' => $deliveryItem->vat_amount,
                    'line_total' => $deliveryItem->line_total,
                ]);
            }

            // Add transport cost as a separate line item if applicable
            if ($delivery->has_transport_cost && $delivery->transport_cost > 0) {
                \App\Models\Sales\SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'inventory_item_id' => null, // No inventory item for transport
                    'item_name' => 'Transport/Delivery Service',
                    'item_code' => 'TRANSPORT',
                    'quantity' => 1,
                    'unit_of_measure' => 'Service',
                    'unit_price' => $delivery->transport_cost,
                    'vat_rate' => 0, // Transport might be VAT exempt
                    'vat_type' => 'exclusive',
                    'vat_amount' => 0,
                    'line_total' => $delivery->transport_cost,
                ]);
            }

            // Update invoice totals
            $invoice->updateTotals();

            // Relink delivery inventory movements to this invoice so COGS is captured
            \App\Models\Inventory\Movement::where('reference_type', 'delivery')
                ->where('reference_id', $delivery->id)
                ->update([
                    'reference' => 'Sales Invoice: ' . $invoice->invoice_number,
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $invoice->id,
                    'movement_date' => $invoice->invoice_date,
                ]);

            // Create double-entry transactions (AR, Revenue, VAT, COGS, Inventory)
            $invoice->createDoubleEntryTransactions();

            // Update delivery status (keep as delivered since invoice is created automatically)
            $delivery->update([
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery converted to invoice successfully',
                'invoice_id' => $invoice->encoded_id,
                'redirect_url' => route('sales.invoices.show', $invoice->encoded_id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error converting delivery to invoice: ' . $e->getMessage()
            ], 500);
        }
    }
}
