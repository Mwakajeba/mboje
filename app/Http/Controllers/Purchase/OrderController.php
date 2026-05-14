<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\PurchaseQuotation;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Inventory\Item;
use App\Services\Purchase\PurchaseOrderService;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Purchase\GoodsReceipt;
use App\Models\Purchase\GoodsReceiptItem;
use App\Models\Inventory\Item as InventoryItem;
use App\Services\InventoryStockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $orderService,
        protected InventoryStockService $stockService
    ) {
    }
    /**
     * Display a listing of purchase orders
     */
    public function index()
    {
        if (!auth()->user()->hasPermissionTo('view purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $orders = PurchaseOrder::with(['supplier', 'createdBy', 'branch'])
            ->whereHas('branch', function($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            // Scope to current branch (session branch takes precedence)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->latest()
            ->get();

        $stats = [
            'total' => $orders->count(),
            'draft' => $orders->where('status', 'draft')->count(),
            'pending_approval' => $orders->where('status', 'pending_approval')->count(),
            'approved' => $orders->where('status', 'approved')->count(),
            'in_production' => $orders->where('status', 'in_production')->count(),
            'ready_for_delivery' => $orders->where('status', 'ready_for_delivery')->count(),
            'delivered' => $orders->where('status', 'delivered')->count(),
            'cancelled' => $orders->where('status', 'cancelled')->count(),
        ];

        // If it's an AJAX request, return JSON for DataTable
        if (request()->ajax()) {
            $formattedOrders = $orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'encoded_id' => $order->encoded_id,
                    'order_number' => $order->order_number,
                    'supplier_name' => $order->supplier->name,
                    'formatted_date' => format_date($order->order_date, 'M d, Y'),
                    'formatted_delivery_date' => format_date($order->expected_delivery_date, 'M d, Y'),
                    'status_badge' => '<span class="badge ' . $order->status_badge . '">' . $order->status_label . '</span>',
                    'formatted_total' => number_format($order->total_amount, 2),
                    'actions' => $this->getActionButtons($order)
                ];
            });

            return response()->json([
                'orders' => $formattedOrders,
                'stats' => $stats
            ]);
        }

        return view('purchases.orders.index', compact('orders', 'stats'));
    }


    private function getActionButtons($order)
    {
        $buttons = '';
        
        // View button
        $buttons .= '<a href="' . route('purchases.orders.show', $order->encoded_id) . '" class="btn btn-sm btn-outline-primary me-1" title="View"><i class="bx bx-show"></i></a>';
        
        // Print button (only for approved orders)
        if ($order->status === 'approved') {
            $buttons .= '<a href="' . route('purchases.orders.print', $order->encoded_id) . '" class="btn btn-sm btn-outline-success me-1" title="Print PDF" target="_blank"><i class="bx bx-printer"></i></a>';
        }
        
        // Edit button
        if (auth()->user()->can('edit purchase orders') && in_array($order->status, ['draft', 'pending_approval'])) {
            $buttons .= '<a href="' . route('purchases.orders.edit', $order->encoded_id) . '" class="btn btn-sm btn-outline-warning me-1" title="Edit"><i class="bx bx-edit"></i></a>';
        }
        
        // Delete button
        if (auth()->user()->can('delete purchase orders') && $order->status === 'draft') {
            $buttons .= '<button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteOrder(\'' . $order->encoded_id . '\', \'' . addslashes($order->order_number) . '\')"><i class="bx bx-trash"></i></button>';
        }
        
        return $buttons;
    }

    /**
     * Show the form for creating a new purchase order
     */
    public function create()
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $items = Item::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();
        Item::withResolvedPricesForContext($items);

        $quotations = PurchaseQuotation::where('status', 'approved')
            ->whereHas('branch', function($query) {
                $query->where('company_id', Auth::user()->company_id);
            })
            ->with('supplier')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchases.orders.create', compact('suppliers', 'items', 'quotations'));
    }

    /**
     * Store a newly created purchase order
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        // Check for branch ID first
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a branch before creating purchase order.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'required|date|after_or_equal:order_date',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_45,net_60,custom',
            'payment_days' => 'required_if:payment_terms,custom|nullable|integer|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'quotation_id' => 'nullable|exists:purchase_quotation,id',
            'hide_cost_price' => 'nullable|boolean',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_rate' => 'required_if:discount_type,percentage|nullable|numeric|min:0|max:100',
            'discount_amount' => 'required_if:discount_type,fixed|nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:no_vat,vat_inclusive,vat_exclusive',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Handle attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('purchase-order-attachments', $fileName, 'public');
            }

            $order = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'status' => 'draft',
                'payment_terms' => $request->payment_terms,
                'payment_days' => $request->payment_days ?? 0,
                'vat_type' => 'no_vat',
                'vat_rate' => 0,
                'discount_type' => $request->discount_type,
                'discount_rate' => $request->discount_type === 'percentage' ? ($request->discount_rate ?? 0) : 0,
                'discount_amount' => $request->discount_type === 'fixed' ? ($request->discount_amount ?? 0) : 0,
                'hide_cost_price' => $request->boolean('hide_cost_price'),
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'attachment' => $attachmentPath,
                'branch_id' => $branchId,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
                'quotation_id' => $request->quotation_id,
            ]);

            $subtotal = 0;
            $totalVatAmount = 0;

            foreach ($request->items as $itemData) {
                $mappedVatType = match($itemData['vat_type']) {
                    'vat_inclusive' => 'inclusive',
                    'vat_exclusive' => 'exclusive',
                    default => 'no_vat',
                };

                $item = new PurchaseOrderItem([
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'cost_price' => $itemData['cost_price'],
                    'tax_calculation_type' => 'percentage',
                    'vat_type' => $mappedVatType,
                    'vat_rate' => $itemData['vat_rate'] ?? 0,
                    'description' => $itemData['description'] ?? null,
                ]);

                $item->calculateTotals();
                $order->items()->save($item);

                $subtotal += $item->subtotal;
                $totalVatAmount += $item->vat_amount;
            }

            // Calculate order totals
            $vatAmount = $totalVatAmount; // sum of line VATs
            $taxAmount = (float) ($request->tax_amount ?? 0);

            $discountAmount = $request->discount_type === 'percentage'
                ? ($subtotal * (($request->discount_rate ?? 0) / 100))
                : ($request->discount_amount ?? 0);

            $totalAmount = $subtotal + $vatAmount + $taxAmount - $discountAmount;

            $order->update([
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully.',
                'redirect' => route('purchases.orders.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating purchase order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified purchase order
     */
    public function show($encodedId)
    {
        if (!auth()->user()->hasPermissionTo('view purchase order details')) {
            abort(403, 'Unauthorized access.');
        }

        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $order = PurchaseOrder::with(['supplier', 'items.item', 'createdBy', 'approvedBy', 'branch', 'quotation'])
            ->findOrFail($id);

        // Approval context for UI (approve / reject buttons)
        $approvalService = app(ApprovalService::class);
        $user = auth()->user();
        $canApprove = $approvalService->canUserApprove($order, $user->id);
        $currentLevel = $approvalService->getCurrentApprovalLevel($order);

        return view('purchases.orders.show', compact('order', 'canApprove', 'currentLevel'));
    }

    /**
     * Show the form for editing the specified purchase order
     */
    public function edit($encodedId)
    {
        if (!auth()->user()->hasPermissionTo('edit purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $order = PurchaseOrder::findOrFail($id);

        if (!in_array($order->status, ['draft', 'pending_approval'])) {
            return redirect()->route('purchases.orders.show', $order->encoded_id)
                ->with('error', 'Only draft and pending approval orders can be edited.');
        }

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $inventoryItems = Item::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();
        Item::withResolvedPricesForContext($inventoryItems);

        $order->load(['items.item', 'supplier']);

        return view('purchases.orders.edit', compact('order', 'suppliers', 'inventoryItems'));
    }

    /**
     * Update the specified purchase order
     */
    public function update(Request $request, $encodedId)
    {
        if (!auth()->user()->hasPermissionTo('edit purchase orders')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order ID.'
            ], 404);
        }

        $order = PurchaseOrder::findOrFail($id);

        if (!in_array($order->status, ['draft', 'pending_approval'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft and pending approval orders can be edited.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'required|date|after_or_equal:order_date',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_45,net_60,custom',
            'payment_days' => 'required_if:payment_terms,custom|nullable|integer|min:0',
            'vat_type' => 'required|in:no_vat,vat_inclusive,vat_exclusive',
            'vat_rate' => 'required_if:vat_type,vat_inclusive,vat_exclusive|nullable|numeric|min:0|max:100',
            'hide_cost_price' => 'nullable|boolean',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_rate' => 'required_if:discount_type,percentage|nullable|numeric|min:0|max:100',
            'discount_amount' => 'required_if:discount_type,fixed|nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:no_vat,vat_inclusive,vat_exclusive',
            'items.*.vat_rate' => 'required_if:items.*.vat_type,vat_inclusive,vat_exclusive|nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updateData = [
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'payment_terms' => $request->payment_terms,
                'payment_days' => $request->payment_days ?? 0,
                'vat_type' => $request->vat_type,
                'vat_rate' => $request->vat_rate ?? 0,
                'discount_type' => $request->discount_type,
                'discount_rate' => $request->discount_rate ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'hide_cost_price' => $request->boolean('hide_cost_price'),
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'updated_by' => Auth::id(),
            ];

            // Handle attachment upload
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($order->attachment && \Storage::disk('public')->exists($order->attachment)) {
                    \Storage::disk('public')->delete($order->attachment);
                }
                // Store new attachment
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $updateData['attachment'] = $file->storeAs('purchase-order-attachments', $fileName, 'public');
            }

            $order->update($updateData);

            // Delete existing items
            $order->items()->delete();

            $subtotal = 0;
            $totalVatAmount = 0;

            foreach ($request->items as $itemData) {
                $mappedVatType = match($itemData['vat_type']) {
                    'vat_inclusive' => 'inclusive',
                    'vat_exclusive' => 'exclusive',
                    default => 'no_vat',
                };

                $item = new PurchaseOrderItem([
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'cost_price' => $itemData['cost_price'],
                    'tax_calculation_type' => 'percentage',
                    'vat_type' => $mappedVatType,
                    'vat_rate' => $itemData['vat_rate'] ?? 0,
                ]);

                $item->calculateTotals();
                $order->items()->save($item);

                $subtotal += $item->subtotal;
                $totalVatAmount += $item->vat_amount;
            }

            // Calculate order totals (mirror store logic): sum line VATs and include optional additional tax
            $vatAmount = $totalVatAmount;
            $taxAmount = (float) ($request->tax_amount ?? 0);

            $discountAmount = $request->discount_type === 'percentage'
                ? ($subtotal * (($request->discount_rate ?? 0) / 100))
                : ($request->discount_amount ?? 0);

            $totalAmount = $subtotal + $vatAmount + $taxAmount - $discountAmount;

            $order->update([
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order updated successfully.',
                'redirect' => route('purchases.orders.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating purchase order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $encodedId)
    {
        if (!auth()->user()->hasPermissionTo('approve purchase orders')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,pending_approval,approved,rejected,in_production,ready_for_delivery,delivered,cancelled,on_hold',
            'rejection_reason' => 'nullable|string|min:5',
        ]);

        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order ID.'
            ], 404);
        }

        $order = PurchaseOrder::findOrFail($id);

        if ($validator->fails() || ($request->status === 'rejected' && !trim((string)$request->rejection_reason))) {
            if ($request->status === 'rejected' && !trim((string)$request->rejection_reason)) {
                $validator->errors()->add('rejection_reason', 'Rejection reason is required and must be at least 5 characters.');
            }
            return response()->json([
                'success' => false,
                'message' => 'Invalid status.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Handle submission for approval
            if ($request->status === 'pending_approval' && $order->status === 'draft') {
                $this->orderService->submitForApproval($order, Auth::id());
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase Order submitted for approval successfully.'
                ]);
            }

            // Handle approval via ApprovalService
            if ($request->status === 'approved' && $order->status === 'pending_approval') {
                $approvalService = app(ApprovalService::class);
                
                // Check if user can approve
                if (!$approvalService->canUserApprove($order, Auth::id())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not allowed to approve this order at the current level.'
                    ], 403);
                }

                // Get current approval level
                $currentLevel = $approvalService->getCurrentApprovalLevel($order);
                if (!$currentLevel) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No approval level found for this order.'
                    ], 422);
                }

                // Approve using ApprovalService
                $this->orderService->approve(
                    $order,
                    $currentLevel->id,
                    Auth::id(),
                    $request->input('comments')
                );

                $fresh = $order->fresh();
                $message = 'Purchase Order approved successfully.';
                if ($fresh->status === 'approved') {
                    $message = 'Purchase Order fully approved.';
                }

                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            // Handle rejection via ApprovalService
            if ($request->status === 'rejected' && $order->status === 'pending_approval') {
                $approvalService = app(ApprovalService::class);
                
                // Check if user can approve/reject
                if (!$approvalService->canUserApprove($order, Auth::id())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not allowed to reject this order at the current level.'
                    ], 403);
                }

                // Get current approval level
                $currentLevel = $approvalService->getCurrentApprovalLevel($order);
                if (!$currentLevel) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No approval level found for this order.'
                    ], 422);
                }

                // Reject using ApprovalService
                $this->orderService->reject(
                    $order,
                    $currentLevel->id,
                    Auth::id(),
                    $request->input('rejection_reason', 'No reason provided')
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Purchase Order rejected successfully.'
                ]);
            }

            // For other status changes, use direct update
            $updateData = ['status' => $request->status];

            if ($request->status === 'rejected' && $order->status !== 'pending_approval') {
                $updateData['rejected_by'] = Auth::id();
                $updateData['rejected_at'] = now();
                if ($request->filled('rejection_reason')) {
                    $updateData['rejection_reason'] = $request->input('rejection_reason');
                }
            }

            $order->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a draft Goods Receipt (GRN) from an approved purchase order
     */
    public function createGrn($encodedId)
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid order ID.'], 404);
        }

        $order = PurchaseOrder::with(['items', 'supplier'])->findOrFail($id);

        // Prevent GRN creation if PO is not fully approved
        if ($order->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Only approved orders can be converted to GRN. Current status: ' . $order->status], 422);
        }

        // Additional check: ensure PO has been approved (not just status change)
        if (!$order->approved_at || !$order->approved_by) {
            return response()->json(['success' => false, 'message' => 'Purchase Order must be fully approved before creating GRN.'], 422);
        }

        // Create draft GRN
        $defaultWarehouseId = session('location_id') ?? optional(auth()->user()->defaultLocation)->id;
        $grn = GoodsReceipt::create([
            'purchase_order_id' => $order->id,
            'receipt_date' => now()->toDateString(),
            'received_by' => auth()->id(),
            'total_quantity' => $order->items->sum('quantity'),
            'total_amount' => $order->total_amount,
            'notes' => 'Auto-generated from Purchase Order ' . $order->order_number,
            'status' => 'draft',
            'warehouse_id' => $defaultWarehouseId,
            'company_id' => auth()->user()->company_id,
            'branch_id' => auth()->user()->branch_id,
        ]);

        // Prefill GRN items from order items with zero received qty
        foreach ($order->items as $poItem) {
            $grn->items()->create([
                'purchase_order_item_id' => $poItem->id,
                'inventory_item_id' => $poItem->item_id,
                'quantity_ordered' => $poItem->quantity,
                'quantity_received' => 0,
                'unit_cost' => $poItem->cost_price,
                'total_cost' => 0,
                'vat_type' => $poItem->vat_type,
                'vat_rate' => $poItem->vat_rate,
                'vat_amount' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Goods Receipt Note created as draft.',
            'redirect' => route('purchases.orders.show', $order->encoded_id)
        ]);
    }

    /** GRN form - can be from order or standalone */
    public function createGrnForm($encodedId = null)
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $order = null;
        $prefillItems = collect();

        // If encodedId is provided, load the order
        if ($encodedId) {
            $id = Hashids::decode($encodedId)[0] ?? null;
            if ($id) {
                $order = PurchaseOrder::with(['items.item', 'supplier'])->findOrFail($id);
                if ($order->status !== 'approved') {
                    return redirect()->route('purchases.orders.show', $order->encoded_id)
                        ->with('error', 'Only approved orders can be converted to GRN.');
                }

                $prefillItems = $order->items->map(function($it){
                    return [
                        'purchase_order_item_id' => $it->id,
                        'inventory_item_id' => $it->item_id,
                        'item_name' => optional($it->item)->name,
                        'item_code' => optional($it->item)->code,
                        'quantity_ordered' => (float)$it->quantity,
                        'quantity_received' => 0,
                        'unit_cost' => (float)$it->cost_price,
                        'vat_type' => $it->vat_type,
                        'vat_rate' => (float)($it->vat_rate ?? 0),
                    ];
                })->values();
            }
        }

        $inventoryItems = InventoryItem::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('purchases.grn.create', compact('order', 'prefillItems', 'inventoryItems', 'suppliers'));
    }

    /** Store GRN */
    public function storeGrn(Request $request, $encodedId)
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid order ID.'], 404);
        }

        $order = PurchaseOrder::with('items')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'receipt_date' => 'required|date',
            'warehouse_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|integer',
            'items.*.inventory_item_id' => 'required|integer',
            'items.*.quantity_ordered' => 'required|numeric|min:0',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $grn = GoodsReceipt::create([
                'purchase_order_id' => $order->id,
                'receipt_date' => $request->receipt_date,
                'received_by' => auth()->id(),
                'total_quantity' => collect($request->items)->sum('quantity_received'),
                'total_amount' => 0,
                'notes' => $request->notes,
                'status' => 'draft',
                'warehouse_id' => $request->warehouse_id,
                'company_id' => $order->company_id ?? auth()->user()->company_id,
                'branch_id' => $order->branch_id ?? (session('branch_id') ?? auth()->user()->branch_id),
            ]);

            $totalAmount = 0;
            foreach ($request->items as $it) {
                $lineTotal = (float)$it['unit_cost'] * (float)$it['quantity_received'];
                $grn->items()->create([
                    'purchase_order_item_id' => $it['purchase_order_item_id'],
                    'inventory_item_id' => $it['inventory_item_id'],
                    'quantity_ordered' => $it['quantity_ordered'],
                    'quantity_received' => $it['quantity_received'],
                    'unit_cost' => $it['unit_cost'],
                    'total_cost' => $lineTotal,
                    'vat_type' => $it['vat_type'] ?? 'no_vat',
                    'vat_rate' => $it['vat_rate'] ?? 0,
                    'vat_amount' => 0,
                ]);
                $totalAmount += $lineTotal;
            }

            $grn->update(['total_amount' => $totalAmount]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'GRN saved successfully.', 'redirect' => route('purchases.orders.show', $order->encoded_id) . '?grn=' . $grn->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to save GRN: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display GRN index page
     */
    public function grnIndex(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $user = auth()->user();
        $sessionBranchId = session('branch_id');
        $userBranchId = $user->branch_id;
        $permittedBranchIds = collect($user->branches ?? [])->pluck('id')->all();

        $query = \App\Models\Purchase\GoodsReceipt::with(['purchaseOrder.supplier', 'receivedByUser', 'items'])
            ->where('company_id', $user->company_id)
            ->when($sessionBranchId, function($q) use ($sessionBranchId) {
                return $q->where('branch_id', $sessionBranchId);
            }, function($q) use ($userBranchId, $permittedBranchIds) {
                if (!empty($permittedBranchIds)) {
                    return $q->whereIn('branch_id', $permittedBranchIds);
                }
                if ($userBranchId) {
                    return $q->where('branch_id', $userBranchId);
                }
                return $q; // no branch filter fallback
            });

        // Calculate stats
        $totalGrns = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $drafts = (clone $query)->where('status', 'draft')->count();

        // If AJAX request, return DataTable JSON
        if ($request->ajax()) {
            return DataTables::of($query)
                ->addColumn('grn_number', function ($grn) {
                    return '<strong>GRN-' . $grn->hash_id . '</strong>';
                })
                ->addColumn('purchase_order', function ($grn) {
                    if ($grn->purchaseOrder) {
                        $orderRef = $grn->purchaseOrder->reference ?? ('PO-' . str_pad($grn->purchaseOrder->id, 6, '0', STR_PAD_LEFT));
                        return '<a href="' . route('purchases.orders.show', $grn->purchaseOrder->encoded_id) . '" class="text-decoration-none">' . e($orderRef) . '</a>';
                    }
                    return '<span class="text-muted">Standalone</span>';
                })
                ->addColumn('supplier_name', function ($grn) {
                    return e($grn->purchaseOrder->supplier->name ?? 'N/A');
                })
                ->addColumn('receipt_date_formatted', function ($grn) {
                    return $grn->receipt_date ? $grn->receipt_date->format('M j, Y') : 'N/A';
                })
                ->addColumn('received_by_name', function ($grn) {
                    return e($grn->receivedByUser->name ?? 'N/A');
                })
                ->addColumn('items_count', function ($grn) {
                    return '<span class="badge bg-info">' . $grn->items->count() . ' items</span>';
                })
                ->addColumn('total_amount_formatted', function ($grn) {
                    return '<strong>TZS ' . number_format($grn->total_amount, 2) . '</strong>';
                })
                ->addColumn('status_badge', function ($grn) {
                    $statusClasses = [
                        'draft' => 'bg-secondary',
                        'completed' => 'bg-success',
                        'pending' => 'bg-warning',
                        'cancelled' => 'bg-danger',
                    ];
                    $statusClass = $statusClasses[$grn->status] ?? 'bg-secondary';
                    return '<span class="badge ' . $statusClass . '">' . ucfirst($grn->status) . '</span>';
                })
                ->addColumn('actions', function ($grn) {
                    $actions = '<div class="btn-group" role="group">';
                    
                    if (auth()->user()->can('view purchase orders')) {
                        $actions .= '<a href="' . route('purchases.grn.show', $grn->hash_id) . '" class="btn btn-outline-primary btn-sm" title="View"><i class="bx bx-show"></i></a> ';
                    }
                    
                    if (auth()->user()->can('create purchase invoices')) {
                        $actions .= '<a href="' . route('purchases.purchase-invoices.create', ['grn_id' => $grn->id]) . '" class="btn btn-outline-success btn-sm" title="Create Invoice from GRN"><i class="bx bx-receipt"></i></a> ';
                    }
                    
                    if (auth()->user()->can('edit purchase orders')) {
                        $actions .= '<a href="' . route('purchases.grn.edit', $grn->hash_id) . '" class="btn btn-outline-secondary btn-sm" title="Edit"><i class="bx bx-edit"></i></a> ';
                    }
                    
                    if (auth()->user()->can('delete purchase orders')) {
                        $disabled = $grn->status !== 'draft' ? 'disabled' : '';
                        $actions .= '<button type="button" class="btn btn-outline-danger btn-sm delete-grn-btn" data-grn-id="' . $grn->hash_id . '" data-grn-number="GRN-' . $grn->hash_id . '" title="Delete" ' . $disabled . '><i class="bx bx-trash"></i></button>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['grn_number', 'purchase_order', 'items_count', 'total_amount_formatted', 'status_badge', 'actions'])
                ->make(true);
        }

        // Calculate stats for view
        $stats = [
            'total' => $totalGrns,
            'completed' => $completed,
            'pending' => $pending,
            'draft' => $drafts,
        ];

        return view('purchases.grn.index', compact('stats'));
    }

    /** Store standalone GRN */
    public function storeStandaloneGrn(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'receipt_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $grn = GoodsReceipt::create([
                'purchase_order_id' => null, // Standalone GRN
                'receipt_date' => $request->receipt_date,
                'received_by' => auth()->id(),
                'total_quantity' => collect($request->items)->sum('quantity_received'),
                'total_amount' => 0,
                'notes' => $request->notes,
                'status' => 'draft',
                'company_id' => auth()->user()->company_id,
                'branch_id' => session('branch_id') ?? auth()->user()->branch_id,
            ]);

            $totalAmount = 0;
            foreach ($request->items as $it) {
                $lineTotal = (float)$it['unit_cost'] * (float)$it['quantity_received'];
                $grn->items()->create([
                    'purchase_order_item_id' => null, // Standalone item
                    'inventory_item_id' => $it['inventory_item_id'],
                    'quantity_ordered' => $it['quantity_ordered'],
                    'quantity_received' => $it['quantity_received'],
                    'unit_cost' => $it['unit_cost'],
                    'total_cost' => $lineTotal,
                    'vat_type' => 'no_vat',
                    'vat_rate' => 0,
                    'vat_amount' => 0,
                ]);
                $totalAmount += $lineTotal;
            }

            $grn->update(['total_amount' => $totalAmount]);

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => 'Standalone GRN created successfully.',
                'redirect' => route('purchases.grn.index') . '?grn=' . $grn->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Failed to create standalone GRN: ' . $e->getMessage()
            ], 500);
        }
    }

    /** Show a GRN */
    public function grnShow(GoodsReceipt $grn)
    {
        if (!auth()->user()->hasPermissionTo('view purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $grn->load(['purchaseOrder.supplier', 'receivedByUser', 'items.inventoryItem', 'warehouse', 'branch']);
        
        // Check if GRN has already been converted to an invoice
        $grnItemIds = $grn->items->pluck('id')->toArray();
        $alreadyConverted = \App\Models\Purchase\PurchaseInvoiceItem::whereIn('grn_item_id', $grnItemIds)->exists();
        
        return view('purchases.grn.show', compact('grn', 'alreadyConverted'));
    }

    /** Print/Export GRN */
    public function grnPrint(GoodsReceipt $grn)
    {
        if (!auth()->user()->hasPermissionTo('view purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $grn->load(['purchaseOrder.supplier', 'receivedByUser', 'items.inventoryItem', 'company', 'branch', 'warehouse']);

        $company = $grn->company ?? $grn->branch->company ?? auth()->user()->company ?? null;
        
        // Get bank accounts for payment methods
        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($grn) {
            $companyId = $grn->company_id ?? $grn->branch->company_id ?? auth()->user()->company_id;
            $q->where('company_id', $companyId);
        })->orderBy('name')->get();

        $pdf = Pdf::loadView('purchases.grn.pdf', compact('grn', 'company', 'bankAccounts'));
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'GRN_' . ($grn->grn_number ?? ('GRN-' . str_pad($grn->id, 6, '0', STR_PAD_LEFT))) . '_' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /** Edit a GRN */
    public function grnEdit(GoodsReceipt $grn)
    {
        if (!auth()->user()->hasPermissionTo('edit purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $grn->load(['purchaseOrder.supplier', 'receivedByUser', 'items']);
        // If already converted, redirect to show with error
        $grnItemIds = $grn->items->pluck('id')->toArray();
        $alreadyConverted = \App\Models\Purchase\PurchaseInvoiceItem::whereIn('grn_item_id', $grnItemIds)->exists();
        if ($alreadyConverted) {
            return redirect()->route('purchases.grn.show', $grn->hash_id ?? $grn->id)
                ->with('error', 'This GRN has already been converted to an invoice and cannot be edited.');
        }
        $warehouses = \Illuminate\Support\Facades\Schema::hasTable('warehouses')
            ? \App\Models\Warehouse::where('branch_id', auth()->user()->branch_id)->orderBy('name')->get()
            : collect();
        $inventoryItems = InventoryItem::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        return view('purchases.grn.edit', compact('grn', 'warehouses', 'inventoryItems'));
    }

    /** Update a GRN */
    public function grnUpdate(Request $request, GoodsReceipt $grn)
    {
        if (!auth()->user()->hasPermissionTo('edit purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'receipt_date' => 'required|date',
            'notes' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'nullable|in:draft,completed,cancelled,pending',
            'items' => 'array',
            'items.*.accepted_quantity' => 'nullable|numeric|min:0',
            'items.*.item_qc_status' => 'nullable|in:pending,passed,failed',
        ]);

        // Check if GRN has already been converted to an invoice
        $grnItemIds = $grn->items->pluck('id')->toArray();
        $alreadyConverted = \App\Models\Purchase\PurchaseInvoiceItem::whereIn('grn_item_id', $grnItemIds)->exists();
        
        if ($alreadyConverted) {
            // If already converted, prevent status changes and only allow notes/warehouse updates
            $updateData = $request->only(['receipt_date', 'notes', 'warehouse_id']);
            if ($request->has('status') && $request->input('status') !== $grn->status) {
                return redirect()->back()->with('error', 'Cannot change GRN status. This GRN has already been converted to an invoice.');
            }
            $grn->update($updateData);
        } else {
            // Enforce: cannot complete GRN unless QC PASSED
            $newStatus = $request->input('status');
            if ($newStatus === 'completed' && (($grn->quality_check_status ?? 'pending') !== 'passed')) {
                return redirect()->back()->with('error', 'You can only mark GRN as COMPLETED after Quality Check is PASSED.');
            }
        $grn->update($request->only(['receipt_date','notes','warehouse_id','status']));
        }

        // Persist per-line QC if provided (only if not already converted)
        if (!$alreadyConverted && is_array($request->input('items'))) {
            foreach ($request->input('items') as $itemId => $payload) {
                $grnItem = $grn->items()->where('id', $itemId)->first();
                if (!$grnItem) { continue; }
                $accepted = isset($payload['accepted_quantity']) ? (float)$payload['accepted_quantity'] : null;
                $itemQc = $payload['item_qc_status'] ?? null;
                if ($accepted !== null) {
                    $accepted = max(0, min($accepted, (float)$grnItem->quantity_received));
                    $grnItem->accepted_quantity = $accepted;
                }
                if ($itemQc !== null) {
                    $grnItem->item_qc_status = $itemQc;
                }
                $grnItem->save();
            }
        }

        return redirect()->route('purchases.grn.show', $grn->hash_id ?? $grn->id)->with('success', 'GRN updated successfully.');
    }

    /** Save per-line QC decisions (accepted qty and item qc status) */
    public function grnUpdateLineQc(Request $request, GoodsReceipt $grn)
    {
        if (!auth()->user()->hasPermissionTo('edit purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        // Check if GRN has already been converted to an invoice
        $grnItemIds = $grn->items->pluck('id')->toArray();
        $alreadyConverted = \App\Models\Purchase\PurchaseInvoiceItem::whereIn('grn_item_id', $grnItemIds)->exists();
        if ($alreadyConverted) {
            return redirect()->route('purchases.grn.show', $grn->hash_id ?? $grn->id)
                ->with('error', 'Cannot change item QC decisions. This GRN has already been converted to an invoice.');
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.accepted_quantity' => 'nullable|numeric|min:0',
            'items.*.item_qc_status' => 'nullable|in:pending,passed,failed',
        ]);

        foreach ($request->input('items') as $itemId => $payload) {
            $grnItem = $grn->items()->where('id', $itemId)->first();
            if (!$grnItem) { continue; }
            $accepted = isset($payload['accepted_quantity']) ? (float)$payload['accepted_quantity'] : null;
            $itemQc = $payload['item_qc_status'] ?? null;
            if ($accepted !== null) {
                $accepted = max(0, min($accepted, (float)$grnItem->quantity_received));
                $grnItem->accepted_quantity = $accepted;
            }
            if ($itemQc !== null) {
                $grnItem->item_qc_status = $itemQc;
            }
            $grnItem->save();
        }

        return redirect()->route('purchases.grn.show', $grn->hash_id ?? $grn->id)->with('success', 'QC decisions saved.');
    }

    /** Update GRN Quality Check status */
    public function grnUpdateQc(Request $request, GoodsReceipt $grn)
    {
        if (!auth()->user()->hasPermissionTo('edit purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        // Check if GRN has already been converted to an invoice
        $grnItemIds = $grn->items->pluck('id')->toArray();
        $alreadyConverted = \App\Models\Purchase\PurchaseInvoiceItem::whereIn('grn_item_id', $grnItemIds)->exists();
        if ($alreadyConverted) {
            return redirect()->route('purchases.grn.show', $grn->hash_id ?? $grn->id)
                ->with('error', 'Cannot change Quality Check status. This GRN has already been converted to an invoice.');
        }

        $request->validate([
            'quality_check_status' => 'required|in:pending,passed,failed,partial',
        ]);

        $current = (string) ($grn->quality_check_status ?? 'pending');
        $target = (string) $request->quality_check_status;

        // Disallow changing after finalization (passed/failed are final)
        if (in_array($current, ['passed','failed'], true) && $target !== $current) {
            return redirect()->route('purchases.grn.show', $grn->hash_id ?? $grn->id)
                ->with('error', 'Quality Check is finalized (' . strtoupper($current) . '). You cannot change it.');
        }

        // No-op if same status
        if ($current === $target) {
            return redirect()->route('purchases.grn.show', $grn->hash_id ?? $grn->id)
                ->with('success', 'Quality Check already ' . strtoupper($current) . '.');
        }

        // Allowed transitions
        $allowed = [
            'pending' => ['passed','failed','partial'],
            'partial' => ['passed','failed'],
            'passed' => [],
            'failed' => [],
        ];

        if (!in_array($target, $allowed[$current] ?? [], true)) {
            return redirect()->route('purchases.grn.show', $grn->hash_id ?? $grn->id)
                ->with('error', 'Transition from ' . strtoupper($current) . ' to ' . strtoupper($target) . ' is not allowed.');
        }

        $grn->update([
            'quality_check_status' => $target,
            'quality_check_by' => auth()->id(),
            'quality_check_date' => now(),
        ]);

        return redirect()->route('purchases.grn.show', $grn->hash_id ?? $grn->id)
            ->with('success', 'Quality Check updated to ' . strtoupper($target) . '.');
    }

    /** Delete a GRN */
    public function grnDestroy(GoodsReceipt $grn)
    {
        if (!auth()->user()->hasPermissionTo('delete purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        // Only allow deleting draft GRNs
        if ($grn->status !== 'draft') {
            return back()->with('error', 'Only draft GRNs can be deleted.');
        }

        $grn->items()->delete();
        $grn->delete();

        return redirect()->route('purchases.grn.index')->with('success', 'GRN deleted successfully.');
    }

    /**
     * Convert from quotation
     */
    public function convertFromQuotation(PurchaseQuotation $quotation)
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        if ($quotation->status !== 'approved') {
            return redirect()->route('purchases.quotations.show', $quotation)
                ->with('error', 'Only approved quotations can be converted to purchase orders.');
        }

        // If already converted, suggest creating another
        $existingOrdersCount = $quotation->orders()->count();
        if ($existingOrdersCount > 0) {
            session()->flash('info', "This quotation already has {$existingOrdersCount} order(s). You can create another order if needed.");
        }

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $items = Item::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        $quotation->load(['items.item', 'supplier']);

        // Build prefill items payload for the view (JS-safe)
        $prefillItems = $quotation->quotationItems()->with('item')->get()->map(function($qi){
            return [
                'item_id' => $qi->item_id,
                'item_name' => optional($qi->item)->name,
                'item_code' => optional($qi->item)->code,
                'quantity' => (float) $qi->quantity,
                'unit_price' => (float) $qi->unit_price,
                'vat_type' => $qi->vat_type,
                'vat_rate' => (float) ($qi->vat_rate ?? 0),
                'tax_amount' => (float) ($qi->tax_amount ?? 0),
                'total_amount' => (float) ($qi->total_amount ?? 0),
            ];
        })->values();

        return view('purchases.orders.create', compact('quotation', 'suppliers', 'items', 'prefillItems'));
    }

    /**
     * Show the form for creating a new purchase order from a purchase invoice (Copy To).
     */
    public function createFromInvoice($invoiceEncodedId)
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            abort(403, 'Unauthorized access.');
        }
        $invoiceId = Hashids::decode($invoiceEncodedId)[0] ?? null;
        if (!$invoiceId) {
            abort(404);
        }
        $user = Auth::user();
        $invoice = PurchaseInvoice::with(['supplier', 'items.inventoryItem'])
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->findOrFail($invoiceId);

        $suppliers = Supplier::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $items = Item::where('company_id', $user->company_id)->orderBy('name')->get();

        $prefillItems = $invoice->items->map(function ($line) {
            $item = $line->inventoryItem;
            return [
                'item_id' => $line->inventory_item_id,
                'item_name' => $item ? $item->name : ($line->description ?: 'Item'),
                'item_code' => $item ? $item->code : '',
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) ($line->unit_cost ?? 0),
                'vat_type' => $line->vat_type ?? 'no_vat',
                'vat_rate' => (float) ($line->vat_rate ?? 0),
                'tax_amount' => (float) ($line->vat_amount ?? 0),
                'total_amount' => (float) ($line->line_total ?? 0),
            ];
        })->values();

        $quotation = null;
        return view('purchases.orders.create', compact('invoice', 'quotation', 'suppliers', 'items', 'prefillItems'));
    }

    /**
     * Show the form for creating a standalone GRN from a purchase invoice (Copy To).
     */
    public function createGrnFromInvoice($invoiceEncodedId)
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            abort(403, 'Unauthorized access.');
        }
        $invoiceId = Hashids::decode($invoiceEncodedId)[0] ?? null;
        if (!$invoiceId) {
            abort(404);
        }
        $user = Auth::user();
        $invoice = PurchaseInvoice::with(['supplier', 'items.inventoryItem'])
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->findOrFail($invoiceId);

        $order = null;
        $prefillItems = $invoice->items->map(function ($line) {
            $item = $line->inventoryItem;
            return [
                'purchase_order_item_id' => null,
                'inventory_item_id' => $line->inventory_item_id,
                'item_name' => $item ? $item->name : ($line->description ?: 'Item'),
                'item_code' => $item ? $item->code : '',
                'quantity_ordered' => (float) $line->quantity,
                'quantity_received' => (float) $line->quantity,
                'unit_cost' => (float) ($line->unit_cost ?? 0),
                'vat_type' => $line->vat_type ?? 'no_vat',
                'vat_rate' => (float) ($line->vat_rate ?? 0),
            ];
        })->values();

        $inventoryItems = InventoryItem::where('company_id', $user->company_id)->orderBy('name')->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);
        $suppliers = Supplier::where('company_id', $user->company_id)->where('status', 'active')->orderBy('name')->get();

        return view('purchases.grn.create', compact('order', 'prefillItems', 'inventoryItems', 'suppliers', 'invoice'));
    }

    /**
     * Create purchase order from low stock items
     */
    public function createFromStock()
    {
        if (!auth()->user()->hasPermissionTo('create purchase orders')) {
            abort(403, 'Unauthorized access.');
        }

        $user = Auth::user();
        $locationId = session('location_id') ?? $user->branch_id;

        if (!$locationId) {
            return redirect()->route('purchases.orders.index')
                ->with('error', 'Please select a branch/location first.');
        }

        $lowStockItems = $this->stockService->getLowStockItemsAtLocation($locationId);

        if ($lowStockItems->isEmpty()) {
            return redirect()->route('purchases.orders.index')
                ->with('info', 'No items are currently below reorder level.');
        }

        $suppliers = Supplier::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $items = Item::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();
        Item::withResolvedPricesForContext($items);

        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $locationId = session('location_id');

        // Build prefill items payload (cost = resolved: location → branch → default)
        $prefillItems = $lowStockItems->map(function($lsi) use ($branchId, $locationId) {
            $suggestedQty = max(0, $lsi['reorder_level'] - $lsi['current_stock']);
            if ($suggestedQty <= 0) {
                $suggestedQty = 1;
            }
            $item = Item::find($lsi['item_id'] ?? null);
            $costPrice = $item ? $item->getCostPriceForBranchOrLocation($branchId, $locationId) : ($lsi['cost_price'] ?? 0);

            return [
                'item_id' => $lsi['item_id'],
                'item_name' => $lsi['item_name'],
                'item_code' => $lsi['item_code'],
                'quantity' => $suggestedQty,
                'unit_price' => $costPrice,
                'vat_type' => 'no_vat',
                'vat_rate' => 0,
            ];
        })->values();

        return view('purchases.orders.create', compact('suppliers', 'items', 'prefillItems'));
    }

    /**
     * Print/Export purchase order to PDF
     */
    public function print($encodedId)
    {
        if (!auth()->user()->hasPermissionTo('view purchase order details')) {
            abort(403, 'Unauthorized access.');
        }

        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $order = PurchaseOrder::with(['supplier', 'items.item', 'createdBy', 'approvedBy', 'branch.company', 'quotation', 'company'])
            ->findOrFail($id);

        // Only allow printing approved orders
        if ($order->status !== 'approved') {
            abort(403, 'Only approved purchase orders can be printed.');
        }

        $company = $order->company ?? $order->branch->company ?? auth()->user()->company ?? null;
        
        // Get bank accounts for payment methods
        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($order) {
            $companyId = $order->company_id ?? $order->branch->company_id ?? auth()->user()->company_id;
            $q->where('company_id', $companyId);
        })->orderBy('name')->get();

        $pdf = Pdf::loadView('purchases.orders.print', compact('order', 'company', 'bankAccounts'));
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'Purchase_Order_' . $order->order_number . '_' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Remove the specified purchase order
     */
    public function destroy($encodedId)
    {
        if (!auth()->user()->hasPermissionTo('delete purchase orders')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $id = Hashids::decode($encodedId)[0] ?? null;

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order ID.'
            ], 404);
        }

        $order = PurchaseOrder::findOrFail($id);

        if (!in_array($order->status, ['draft'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft orders can be deleted.'
            ], 422);
        }

        try {
            // Permanently delete the order and its related data
            DB::transaction(function () use ($order) {
                // Delete related order items
                $order->items()->delete();
                
                // Delete the order permanently
                $order->forceDelete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Purchase order permanently deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting purchase order: ' . $e->getMessage()
            ], 500);
        }
    }

}
