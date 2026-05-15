<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use App\Models\Sales\SalesProforma;
use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Yajra\DataTables\Facades\DataTables;
use Vinkla\Hashids\Facades\Hashids;

class SalesOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if ($request->ajax()) {
            $orders = SalesOrder::with(['customer', 'createdBy', 'proforma'])
                ->whereHas('customer', function ($query) use ($user) {
                    $query->whereHas('branch', function ($q) use ($user) {
                        $q->where('company_id', $user->company_id);
                    });
                })
                ->when($user->branch_id, function ($query) use ($user) {
                    return $query->where('branch_id', $user->branch_id);
                })
                ->orderBy('created_at', 'desc');

            return DataTables::of($orders)
                ->addColumn('actions', function ($order) {
                    $encodedId = \Vinkla\Hashids\Facades\Hashids::encode($order->id);
                    $actions = '<div class="d-flex gap-2">';
                    $actions .= '<a href="' . route('sales.orders.show', $encodedId) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                    
                    if ($order->status === 'draft') {
                        $actions .= '<a href="' . route('sales.orders.edit', $encodedId) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bx bx-edit"></i></a>';
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteOrder(\'' . $encodedId . '\', \'' . e($order->order_number) . '\')" title="Delete"><i class="bx bx-trash"></i></button>';
                    }
                    
                    if ($order->status === 'approved') {
                        // Check if order has already been converted to an invoice
                        $existingInvoice = \App\Models\Sales\SalesInvoice::where('sales_order_id', $order->id)->first();
                        if (!$existingInvoice) {
                            $actions .= '<button type="button" class="btn btn-sm btn-outline-info" onclick="convertToInvoice(\'' . $encodedId . '\')" title="Convert to Invoice"><i class="bx bx-receipt"></i></button>';
                        } else {
                            $actions .= '<a href="' . route('sales.invoices.show', $existingInvoice->encoded_id) . '" class="btn btn-sm btn-outline-info" title="View Invoice"><i class="bx bx-receipt"></i></a>';
                        }
                    } elseif ($order->status === 'converted_to_invoice') {
                        // Show View Invoice button for converted orders
                        $existingInvoice = \App\Models\Sales\SalesInvoice::where('sales_order_id', $order->id)->first();
                        if ($existingInvoice) {
                            $actions .= '<a href="' . route('sales.invoices.show', $existingInvoice->encoded_id) . '" class="btn btn-sm btn-outline-info" title="View Invoice"><i class="bx bx-receipt"></i></a>';
                        }
                    }
                    
                    // Check if order has already been converted to a delivery (for approved and converted_to_invoice statuses)
                    if (in_array($order->status, ['approved', 'converted_to_invoice'])) {
                        $existingDelivery = \App\Models\Sales\Delivery::where('sales_order_id', $order->id)->first();
                        if (!$existingDelivery) {
                            $actions .= '<button type="button" class="btn btn-sm btn-outline-success" onclick="convertToDelivery(\'' . $encodedId . '\')" title="Convert to Delivery"><i class="bx bx-package"></i></button>';
                        } else {
                            $actions .= '<a href="' . route('sales.deliveries.show', $existingDelivery->encoded_id) . '" class="btn btn-sm btn-outline-success" title="View Delivery"><i class="bx bx-package"></i></a>';
                        }
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->addColumn('status_badge', function ($order) {
                    $colors = [
                        'draft' => 'secondary',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'in_production' => 'info',
                        'ready_for_delivery' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'converted_to_invoice' => 'info'
                    ];
                    $color = $colors[$order->status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . strtoupper(str_replace('_', ' ', $order->status)) . '</span>';
                })
                ->addColumn('formatted_date', function ($order) {
                    return format_date($order->order_date, 'M d, Y');
                })
                ->addColumn('formatted_delivery_date', function ($order) {
                    return format_date($order->expected_delivery_date, 'M d, Y');
                })
                ->addColumn('formatted_total', function ($order) {
                    return 'TZS ' . number_format($order->total_amount, 2);
                })
                ->addColumn('customer_name', function ($order) {
                    return $order->customer->name ?? 'N/A';
                })
                ->addColumn('proforma_number', function ($order) {
                    return $order->proforma ? $order->proforma->proforma_number : 'N/A';
                })
                ->addColumn('id', function ($order) {
                    return Hashids::encode($order->id);
                })
                ->rawColumns(['actions', 'status_badge'])
                ->make(true);
        }

        // Get stats for the dashboard
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        
        // Base query for all stats
        $baseQuery = SalesOrder::whereHas('customer', function ($query) use ($user) {
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
            'pending_approval' => (clone $baseQuery)->where('status', 'pending_approval')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'in_production' => (clone $baseQuery)->where('status', 'in_production')->count(),
            'ready_for_delivery' => (clone $baseQuery)->where('status', 'ready_for_delivery')->count(),
            'converted_to_invoice' => (clone $baseQuery)->where('status', 'converted_to_invoice')->count(),
        ];

        return view('sales.orders.index', compact('stats'));
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

        $copyFromInvoice = null;
        if ($request->filled('copy_from_invoice')) {
            $sourceId = Hashids::decode($request->copy_from_invoice)[0] ?? null;
            if ($sourceId) {
                $source = \App\Models\Sales\SalesInvoice::with(['customer', 'items'])->where('company_id', $user->company_id)->find($sourceId);
                if ($source) {
                    $copyFromInvoice = [
                        'customer' => ['id' => $source->customer_id],
                        'order_date' => now()->format('Y-m-d'),
                        'expected_delivery_date' => now()->addDays(7)->format('Y-m-d'),
                        'payment_terms' => $source->payment_terms ?? 'net_30',
                        'payment_days' => (int) ($source->payment_days ?? 30),
                        'notes' => $source->notes ?? '',
                        'terms_conditions' => $source->terms_conditions ?? '',
                        'items' => $source->items->map(function ($item) {
                            $vatType = $item->vat_type ?? 'inclusive';
                            $orderVatType = $vatType === 'exclusive' ? 'vat_exclusive' : ($vatType === 'no_vat' ? 'no_vat' : 'vat_inclusive');
                            return [
                                'inventory_item_id' => $item->inventory_item_id,
                                'item_name' => $item->item_name ?? '',
                                'item_code' => $item->item_code ?? '',
                                'quantity' => (float) $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'vat_type' => $orderVatType,
                                'vat_rate' => (float) ($item->vat_rate ?? 0),
                                'vat_amount' => (float) ($item->vat_amount ?? 0),
                                'line_total' => (float) $item->line_total,
                            ];
                        })->values()->all(),
                    ];
                }
            }
        }

        return view('sales.orders.create', compact('customers', 'inventoryItems', 'copyFromInvoice'))->with('items', $inventoryItems);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proforma_id' => 'nullable|exists:sales_proformas,id',
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'required|date|after:order_date',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'subtotal' => 'required|numeric|min:0',
            'vat_amount' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_terms' => 'nullable|in:immediate,net_15,net_30,net_45,net_60,custom',
            'payment_days' => 'nullable|integer|min:0',
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
            $branchId = session('branch_id') ?? $user->branch_id;
            
            // Handle attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('sales-order-attachments', $fileName, 'public');
            }
            
            $orderPayload = [
                'proforma_id' => $request->proforma_id,
                'customer_id' => $request->customer_id,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'status' => 'draft',
                'payment_terms' => $request->payment_terms ?? 'immediate',
                'payment_days' => $request->payment_days ?? 0,
                'subtotal' => $request->subtotal,
                'vat_type' => $request->vat_type ?? 'no_vat',
                'vat_rate' => $request->vat_rate ?? 0,
                'vat_amount' => $request->vat_amount,
                'tax_amount' => $request->tax_amount ?? 0,
                'discount_type' => $request->discount_type ?? 'percentage',
                'discount_rate' => $request->discount_rate ?? 0,
                'discount_amount' => $request->discount_amount,
                'total_amount' => $request->total_amount,
                'customer_credit_limit' => 0,
                'customer_current_balance' => 0,
                'available_credit' => 0,
                'credit_check_passed' => false,
                'inventory_check_passed' => false,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'attachment' => $attachmentPath,
                'branch_id' => $branchId,
                'company_id' => $user->company_id,
                'created_by' => $user->id,
            ];

            $order = null;
            $maxAttempts = 3;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $order = SalesOrder::create($orderPayload);
                    break;
                } catch (QueryException $queryException) {
                    // Retry only when order_number unique key collides due concurrent inserts.
                    if ((int) ($queryException->errorInfo[1] ?? 0) !== 1062 || $attempt === $maxAttempts) {
                        throw $queryException;
                    }
                }
            }

            // Create order items
            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::queryVisibleForSession()->find($itemData['inventory_item_id']);
                
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit_of_measure' => $inventoryItem->unit_of_measure ?? 'pcs',
                    'available_stock' => $inventoryItem->current_stock ?? 0,
                    'vat_type' => $itemData['vat_type'] ?? 'no_vat',
                    'vat_rate' => $itemData['vat_rate'] ?? 0,
                    'vat_amount' => $itemData['vat_amount'] ?? 0,
                    'discount_type' => 'percentage', // Default since we removed item-level discounts
                    'discount_rate' => 0, // No item-level discount
                    'discount_amount' => 0, // No item-level discount
                    'line_total' => $itemData['line_total'],
                    'notes' => $itemData['description'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sales order created successfully',
                'order_id' => $order->id,
                'redirect_url' => route('sales.orders.show', Hashids::encode($order->id))
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating sales order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $order = SalesOrder::with(['customer', 'items.inventoryItem', 'createdBy', 'approvedBy', 'proforma'])
            ->findOrFail($id);

        return view('sales.orders.show', compact('order'));
    }

    /**
     * Export sales order as PDF
     */
    public function exportPdf($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        
        $order = SalesOrder::with([
            'customer',
            'items.inventoryItem',
            'company',
            'branch',
            'createdBy'
        ])->findOrFail($id);

        $html = view('sales.orders.pdf', compact('order'))->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4');
        $filename = 'SalesOrder_' . $order->order_number . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $order = SalesOrder::with(['items.inventoryItem'])->findOrFail($id);
        
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

        return view('sales.orders.edit', compact('order', 'customers', 'inventoryItems'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'required|date|after:order_date',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|max:255',
            'payment_days' => 'nullable|integer|min:0',
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
            $order = SalesOrder::findOrFail($id);

            // Only allow editing if status is draft
            if ($order->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit order that is not in draft status'
                ], 422);
            }

            // Handle attachment upload
            $updateData = [
                'customer_id' => $request->customer_id,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'payment_terms' => $request->payment_terms,
                'payment_days' => $request->payment_days,
                'subtotal' => $request->subtotal ?? 0,
                'vat_type' => $request->vat_type ?? 'no_vat',
                'vat_rate' => $request->vat_rate ?? 0,
                'vat_amount' => $request->vat_amount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'discount_type' => $request->discount_type ?? 'percentage',
                'discount_rate' => $request->discount_rate ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'total_amount' => $request->total_amount ?? 0,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'updated_by' => $user->id,
            ];

            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($order->attachment && \Storage::disk('public')->exists($order->attachment)) {
                    \Storage::disk('public')->delete($order->attachment);
                }
                // Store new attachment
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $updateData['attachment'] = $file->storeAs('sales-order-attachments', $fileName, 'public');
            }

            $order->update($updateData);

            // Delete existing items and create new ones
            $order->items()->delete();

            foreach ($request->items as $itemData) {
                $inventoryItem = InventoryItem::queryVisibleForSession()->find($itemData['inventory_item_id']);
                
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
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
                    'subtotal' => $itemData['subtotal'],
                    'total' => $itemData['total'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sales order updated successfully',
                'redirect_url' => route('sales.orders.show', Hashids::encode($order->id))
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error updating sales order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        try {
            $order = SalesOrder::findOrFail($id);
            
            // Only allow deletion if status is draft
            if ($order->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete order that is not in draft status'
                ], 422);
            }

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sales order deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting sales order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get item details for AJAX request
     */
    public function getItemDetails($id)
    {
        $item = InventoryItem::queryVisibleForSession()->findOrFail($id);
        $branchId = session('branch_id') ?? (auth()->user()->branch_id ?? null);
        $locationId = session('location_id');

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'unit_price' => $item->getUnitPriceForBranchOrLocation($branchId, $locationId),
                'available_quantity' => $item->available_quantity,
                'vat_rate' => $item->vat_rate ?? 0,
            ]
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,pending_approval,approved,in_production,ready_for_delivery,delivered,cancelled,on_hold,converted_to_invoice'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check permissions for approval-related status changes
        $user = Auth::user();
        if (in_array($request->status, ['pending_approval', 'approved', 'cancelled'])) {
            if (!$user->hasPermissionTo('approve sales order')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to approve sales orders'
                ], 403);
            }
        }

        try {
            $decodedId = Hashids::decode($id)[0] ?? null;
            if (!$decodedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID'
                ], 404);
            }

            $order = SalesOrder::findOrFail($decodedId);
            $oldStatus = $order->status;

            $order->update([
                'status' => $request->status,
                'updated_by' => $user->id,
            ]);

            // If status is approved, set approved_by and approved_at
            if ($request->status === 'approved') {
                $order->update([
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);
                
                // Log approval action
                $customerName = $order->customer ? $order->customer->name : 'N/A';
                $order->logActivity('approve', "Approved Sales Order {$order->order_number} for Customer: {$customerName}", [
                    'Order Number' => $order->order_number,
                    'Customer' => $customerName,
                    'Total Amount' => number_format($order->total_amount ?? 0, 2),
                    'Order Date' => $order->order_date ? $order->order_date->format('Y-m-d') : 'N/A',
                    'Approved By' => $user->name,
                    'Approved At' => now()->format('Y-m-d H:i:s')
                ]);
            } elseif ($request->status === 'pending_approval' && $oldStatus !== 'pending_approval') {
                // Log submission for approval
                $customerName = $order->customer ? $order->customer->name : 'N/A';
                $order->logActivity('update', "Submitted Sales Order {$order->order_number} for Approval", [
                    'Order Number' => $order->order_number,
                    'Customer' => $customerName,
                    'Total Amount' => number_format($order->total_amount ?? 0, 2),
                    'Previous Status' => ucfirst(str_replace('_', ' ', $oldStatus)),
                    'Submitted By' => $user->name
                ]);
            } elseif ($oldStatus !== $request->status) {
                // Log other status changes with custom description
                $customerName = $order->customer ? $order->customer->name : 'N/A';
                $order->logActivity('update', "Changed Sales Order {$order->order_number} Status from " . ucfirst(str_replace('_', ' ', $oldStatus)) . " to " . ucfirst(str_replace('_', ' ', $request->status)), [
                    'Order Number' => $order->order_number,
                    'Customer' => $customerName,
                    'From Status' => ucfirst(str_replace('_', ' ', $oldStatus)),
                    'To Status' => ucfirst(str_replace('_', ' ', $request->status)),
                    'Changed By' => $user->name
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new sales order from a sales invoice (Copy To).
     */
    public function createFromInvoice($invoiceEncodedId)
    {
        $invoiceId = Hashids::decode($invoiceEncodedId)[0] ?? null;
        if (!$invoiceId) {
            abort(404);
        }
        $user = Auth::user();
        $invoice = \App\Models\Sales\SalesInvoice::with(['customer', 'items.inventoryItem'])
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
            'order_date' => $invoice->invoice_date?->format('Y-m-d') ?? date('Y-m-d'),
            'expected_delivery_date' => date('Y-m-d', strtotime('+7 days')),
            'payment_terms' => $invoice->payment_terms ?? 'net_30',
            'payment_days' => (int) ($invoice->payment_days ?? 30),
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

        return view('sales.orders.create', compact('invoice', 'customers', 'inventoryItems', 'copyFromInvoice'));
    }

    /**
     * Convert from proforma
     */
    public function convertFromProforma($proformaId)
    {
        $proformaId = Hashids::decode($proformaId)[0] ?? null;
        if (!$proformaId) {
            abort(404);
        }

        $user = Auth::user();
        $proforma = SalesProforma::with(['customer', 'items.inventoryItem'])->findOrFail($proformaId);
        
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

        return view('sales.orders.create', compact('proforma', 'customers', 'inventoryItems'));
    }

    /**
     * Convert order to invoice
     */
    public function convertToInvoice(Request $request, $id)
    {
        try {
            $decodedId = Hashids::decode($id)[0] ?? null;
            if (!$decodedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID'
                ], 404);
            }

            $order = SalesOrder::with(['customer', 'items.inventoryItem'])->findOrFail($decodedId);
            
            // Check if order is approved
            if ($order->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved orders can be converted to invoice'
                ], 422);
            }

            // Check if order has already been converted to an invoice
            $existingInvoice = \App\Models\Sales\SalesInvoice::where('sales_order_id', $order->id)->first();
            if ($existingInvoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been converted to an invoice. Invoice: ' . $existingInvoice->invoice_number
                ], 422);
            }

            // Use the existing conversion logic from SalesInvoiceController
            $invoiceController = new \App\Http\Controllers\Sales\SalesInvoiceController();
            $response = $invoiceController->convertFromOrder($decodedId);
            
            // Log conversion if successful
            if (is_array($response) && isset($response['success']) && $response['success']) {
                $customerName = $order->customer ? $order->customer->name : 'N/A';
                $order->logActivity('update', "Converted Sales Order {$order->order_number} to Invoice", [
                    'Order Number' => $order->order_number,
                    'Customer' => $customerName,
                    'Order Total' => number_format($order->total_amount ?? 0, 2),
                    'Converted By' => Auth::user()->name,
                    'Converted At' => now()->format('Y-m-d H:i:s')
                ]);
            }
            
            return $response;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error converting order to invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert order to delivery
     */
    public function convertToDelivery(Request $request, $id)
    {
        try {
            $decodedId = Hashids::decode($id)[0] ?? null;
            if (!$decodedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID'
                ], 404);
            }

            $order = SalesOrder::with(['customer', 'items.inventoryItem'])->findOrFail($decodedId);
            
            // Check if order is approved
            if ($order->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved orders can be converted to delivery'
                ], 422);
            }

            // Check if order has already been converted to a delivery
            $existingDelivery = \App\Models\Sales\Delivery::where('sales_order_id', $order->id)->first();
            if ($existingDelivery) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been converted to a delivery. Delivery: ' . $existingDelivery->delivery_number
                ], 422);
            }

            // Create delivery from order
            DB::beginTransaction();

            $delivery = \App\Models\Sales\Delivery::create([
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'delivery_date' => now()->toDateString(),
                'expected_delivery_date' => $order->expected_delivery_date,
                'status' => 'draft',
                'notes' => $order->notes,
                'branch_id' => $order->branch_id,
                'company_id' => $order->company_id,
                'created_by' => auth()->id(),
            ]);

            // Create delivery items from order items
            foreach ($order->items as $orderItem) {
                \App\Models\Sales\DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'sales_order_item_id' => $orderItem->id,
                    'inventory_item_id' => $orderItem->inventory_item_id,
                    'item_name' => $orderItem->item_name,
                    'item_code' => $orderItem->item_code,
                    'quantity' => $orderItem->quantity,
                    'unit_of_measure' => $orderItem->inventoryItem->unit_of_measure ?? 'PCS',
                    'unit_weight' => $orderItem->inventoryItem->unit_weight ?? 0,
                ]);
            }

            DB::commit();
            
            // Log conversion
            $customerName = $order->customer ? $order->customer->name : 'N/A';
            $order->logActivity('update', "Converted Sales Order {$order->order_number} to Delivery {$delivery->delivery_number}", [
                'Order Number' => $order->order_number,
                'Delivery Number' => $delivery->delivery_number,
                'Customer' => $customerName,
                'Order Total' => number_format($order->total_amount ?? 0, 2),
                'Delivery Date' => $delivery->delivery_date ? $delivery->delivery_date->format('Y-m-d') : 'N/A',
                'Converted By' => Auth::user()->name,
                'Converted At' => now()->format('Y-m-d H:i:s')
            ]);
            
            // Also log on delivery
            if (method_exists($delivery, 'logActivity')) {
                $delivery->logActivity('create', "Created Delivery {$delivery->delivery_number} from Sales Order {$order->order_number}", [
                    'Delivery Number' => $delivery->delivery_number,
                    'Sales Order Number' => $order->order_number,
                    'Customer' => $customerName,
                    'Delivery Date' => $delivery->delivery_date ? $delivery->delivery_date->format('Y-m-d') : 'N/A',
                    'Created By' => Auth::user()->name
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order converted to delivery successfully',
                'redirect_url' => route('sales.deliveries.show', Hashids::encode($delivery->id))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error converting order to delivery: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert order to cash sale
     */
    public function convertToCash(Request $request, $id)
    {
        try {
            $decodedId = Hashids::decode($id)[0] ?? null;
            if (!$decodedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order ID'
                ], 404);
            }

            $order = SalesOrder::with(['customer', 'items.inventoryItem'])->findOrFail($decodedId);
            
            // Check if order is approved
            if ($order->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved orders can be converted to cash sale'
                ], 422);
            }

            // Check if order has already been converted to cash sale
            $existingCashSale = \App\Models\Sales\CashSale::where('notes', 'like', '%Converted from Sales Order: ' . $order->order_number . '%')->first();
            if ($existingCashSale) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been converted to cash sale'
                ], 422);
            }

            DB::beginTransaction();

            $user = Auth::user();
            $branchId = session('branch_id') ?? $user->branch_id;

            // Create cash sale from order
            $cashSale = \App\Models\Sales\CashSale::create([
                'customer_id' => $order->customer_id,
                'sale_date' => $order->order_date,
                'payment_method' => 'cash', // Default to cash for converted orders
                'subtotal' => $order->subtotal,
                'vat_amount' => $order->vat_amount,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total_amount,
                'paid_amount' => $order->total_amount, // Mark as fully paid
                'currency' => 'TZS',
                'exchange_rate' => 1.000000,
                'vat_rate' => $order->vat_rate,
                'withholding_tax_amount' => 0,
                'withholding_tax_rate' => 0,
                'withholding_tax_type' => 'percentage',
                'notes' => 'Converted from Sales Order: ' . $order->order_number,
                'terms_conditions' => $order->terms_conditions,
                'branch_id' => $branchId,
                'company_id' => $user->company_id,
                'created_by' => $user->id,
            ]);

            // Create cash sale items from order items
            foreach ($order->items as $orderItem) {
                \App\Models\Sales\CashSaleItem::create([
                    'cash_sale_id' => $cashSale->id,
                    'inventory_item_id' => $orderItem->inventory_item_id,
                    'item_name' => $orderItem->item_name,
                    'item_code' => $orderItem->item_code,
                    'description' => $orderItem->notes,
                    'unit_of_measure' => $orderItem->unit_of_measure,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'line_total' => $orderItem->line_total,
                    'vat_type' => $orderItem->vat_type,
                    'vat_rate' => $orderItem->vat_rate,
                    'vat_amount' => $orderItem->vat_amount,
                    'discount_type' => $orderItem->discount_type,
                    'discount_rate' => $orderItem->discount_rate,
                    'discount_amount' => $orderItem->discount_amount,
                    'available_stock' => $orderItem->available_stock,
                    'stock_available' => $orderItem->available_stock >= $orderItem->quantity,
                    'notes' => $orderItem->notes,
                ]);
            }

            // Update order status to delivered (since it's now a cash sale)
            $order->update([
                'status' => 'delivered',
                'updated_by' => $user->id,
            ]);

            // Create inventory movements for cash sale
            $cashSale->updateInventory();

            // Create GL transactions for cash sale
            $cashSale->createDoubleEntryTransactions();

            DB::commit();
            
            // Log conversion
            $customerName = $order->customer ? $order->customer->name : 'N/A';
            $order->logActivity('update', "Converted Sales Order {$order->order_number} to Cash Sale {$cashSale->sale_number}", [
                'Order Number' => $order->order_number,
                'Cash Sale Number' => $cashSale->sale_number,
                'Customer' => $customerName,
                'Order Total' => number_format($order->total_amount ?? 0, 2),
                'Cash Sale Total' => number_format($cashSale->total_amount ?? 0, 2),
                'Sale Date' => $cashSale->sale_date ? $cashSale->sale_date->format('Y-m-d') : 'N/A',
                'Converted By' => Auth::user()->name,
                'Converted At' => now()->format('Y-m-d H:i:s')
            ]);
            
            // Also log on cash sale
            if (method_exists($cashSale, 'logActivity')) {
                $cashSale->logActivity('create', "Created Cash Sale {$cashSale->sale_number} from Sales Order {$order->order_number}", [
                    'Cash Sale Number' => $cashSale->sale_number,
                    'Sales Order Number' => $order->order_number,
                    'Customer' => $customerName,
                    'Total Amount' => number_format($cashSale->total_amount ?? 0, 2),
                    'Sale Date' => $cashSale->sale_date ? $cashSale->sale_date->format('Y-m-d') : 'N/A',
                    'Created By' => Auth::user()->name
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sales order converted to cash sale successfully',
                'cash_sale_id' => $cashSale->id,
                'redirect_url' => route('sales.cash-sales.show', $cashSale->encoded_id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error converting order to cash sale: ' . $e->getMessage()
            ], 500);
        }
    }
} 