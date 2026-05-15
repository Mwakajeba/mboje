<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\DebitNote;
use App\Models\Purchase\DebitNoteItem;
use App\Models\Purchase\DebitNoteApplication;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryLocation;
use App\Models\BankAccount;
use App\Services\DebitNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DebitNoteController extends Controller
{
    protected $debitNoteService;

    public function __construct(DebitNoteService $debitNoteService)
    {
        $this->debitNoteService = $debitNoteService;
        $this->middleware('auth');
        $this->middleware('permission:view debit notes', ['only' => ['index', 'show']]);
        $this->middleware('permission:create debit notes', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit debit notes', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete debit notes', ['only' => ['destroy']]);
        $this->middleware('permission:approve debit notes', ['only' => ['approve']]);
        $this->middleware('permission:apply debit notes', ['only' => ['apply']]);
        $this->middleware('permission:cancel debit notes', ['only' => ['cancel']]);
    }

    /**
     * Display a listing of debit notes
     */
    public function index(Request $request)
    {
        $this->authorize('view debit notes');

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;
        $companyId = $user->company_id;

        // Get stats
        $stats = [
            'total_debit_notes' => DebitNote::where('branch_id', $branchId)->count(),
            'draft' => DebitNote::where('branch_id', $branchId)->where('status', 'draft')->count(),
            'issued' => DebitNote::where('branch_id', $branchId)->where('status', 'issued')->count(),
            'approved' => DebitNote::where('branch_id', $branchId)->where('status', 'approved')->count(),
            'applied' => DebitNote::where('branch_id', $branchId)->where('status', 'applied')->count(),
            'cancelled' => DebitNote::where('branch_id', $branchId)->where('status', 'cancelled')->count(),
        ];

        if ($request->ajax()) {
            $debitNotes = DebitNote::with(['supplier', 'purchaseInvoice'])
                ->where('branch_id', $branchId)
                ->select(['id', 'debit_note_number', 'supplier_id', 'purchase_invoice_id', 'debit_note_date', 'status', 'type', 'total_amount', 'applied_amount', 'remaining_amount', 'created_at']);

            return DataTables::of($debitNotes)
                ->addColumn('supplier_name', function ($debitNote) {
                    return $debitNote->supplier ? $debitNote->supplier->name : 'N/A';
                })
                ->addColumn('reference_invoice', function ($debitNote) {
                    return $debitNote->purchaseInvoice ? $debitNote->purchaseInvoice->invoice_number : 'N/A';
                })
                ->addColumn('debit_note_date_formatted', function ($debitNote) {
                    return format_date($debitNote->debit_note_date, 'd/m/Y');
                })
                ->addColumn('status_badge', function ($debitNote) {
                    return '<span class="badge ' . $debitNote->status_badge_class . '">' . $debitNote->status_text . '</span>';
                })
                ->addColumn('type_badge', function ($debitNote) {
                    return '<span class="badge bg-info">' . $debitNote->type_text . '</span>';
                })
                ->addColumn('total_amount_formatted', function ($debitNote) {
                    return 'TZS ' . number_format($debitNote->total_amount, 2);
                })
                ->addColumn('applied_amount_formatted', function ($debitNote) {
                    return 'TZS ' . number_format($debitNote->applied_amount, 2);
                })
                ->addColumn('remaining_amount_formatted', function ($debitNote) {
                    return 'TZS ' . number_format($debitNote->remaining_amount, 2);
                })
                ->addColumn('actions', function ($debitNote) {
                    $encodedId = Hashids::encode($debitNote->id);
                    $actions = '<div class="btn-group" role="group">';
                    
                    // View button - always show
                    $actions .= '<a href="' . route('purchases.debit-notes.show', $encodedId) . '" class="btn btn-sm btn-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>';
                    
                    // Edit button - check permission and status
                    if (auth()->user()->can('edit debit notes') && $debitNote->canEdit()) {
                        $actions .= '<a href="' . route('purchases.debit-notes.edit', $encodedId) . '" class="btn btn-sm btn-primary" title="Edit">
                            <i class="bx bx-edit"></i>
                        </a>';
                    }
                    
                    // Approve button
                    if (auth()->user()->can('approve debit notes') && $debitNote->canApprove()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-success" onclick="approveDebitNote(\'' . $encodedId . '\')" title="Approve">
                            <i class="bx bx-check"></i>
                        </button>';
                    }
                    
                    // Apply button
                    if (auth()->user()->can('apply debit notes') && $debitNote->canApply()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-warning" onclick="applyDebitNote(\'' . $encodedId . '\')" title="Apply">
                            <i class="bx bx-credit-card"></i>
                        </button>';
                    }
                    
                    // Cancel button
                    if (auth()->user()->can('cancel debit notes') && $debitNote->canCancel()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-secondary" onclick="cancelDebitNote(\'' . $encodedId . '\')" title="Cancel">
                            <i class="bx bx-x"></i>
                        </button>';
                    }
                    
                    // Delete button - check permission and status
                    if (auth()->user()->can('delete debit notes') && $debitNote->canDelete()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteDebitNote(\'' . $encodedId . '\', \'' . $debitNote->debit_note_number . '\')" title="Delete">
                            <i class="bx bx-trash"></i>
                        </button>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status_badge', 'type_badge', 'actions'])
                ->make(true);
        }

        return view('purchases.debit-notes.index', compact('stats'));
    }

    /**
     * Show the form for creating a new debit note from a purchase invoice (Copy To).
     */
    public function createFromInvoice($invoiceEncodedId)
    {
        $this->authorize('create debit notes');
        $invoiceId = Hashids::decode($invoiceEncodedId)[0] ?? null;
        if (!$invoiceId) {
            abort(404);
        }
        $user = Auth::user();
        $branchId = $user->branch_id;
        $companyId = $user->company_id;
        $sourceInvoice = PurchaseInvoice::with(['supplier', 'items.inventoryItem'])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->findOrFail($invoiceId);

        $suppliers = Supplier::where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();
        $invoices = PurchaseInvoice::with('supplier')
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date', 'desc')
            ->limit(100)
            ->get();
        $warehouses = InventoryLocation::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('name')->get();
        $inventoryItems = InventoryItem::queryVisibleForSession($companyId)->with(['category'])->orderBy('name')->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);
        $debitNoteTypes = [
            'return' => 'Return', 'discount' => 'Discount', 'correction' => 'Correction', 'overbilling' => 'Overbilling',
            'service_adjustment' => 'Service Adjustment', 'post_invoice_discount' => 'Post Invoice Discount', 'refund' => 'Refund',
            'restocking_fee' => 'Restocking Fee', 'scrap_writeoff' => 'Scrap Write-off', 'advance_refund' => 'Advance Refund',
            'fx_adjustment' => 'FX Adjustment', 'other' => 'Other',
        ];
        $reasonCodes = [
            'defective_goods' => 'Defective Goods', 'wrong_item' => 'Wrong Item', 'overcharged' => 'Overcharged',
            'duplicate_billing' => 'Duplicate Billing', 'service_not_provided' => 'Service Not Provided', 'quality_issue' => 'Quality Issue',
            'late_delivery' => 'Late Delivery', 'damaged_goods' => 'Damaged Goods', 'quantity_discrepancy' => 'Quantity Discrepancy',
            'price_discrepancy' => 'Price Discrepancy', 'other' => 'Other',
        ];

        return view('purchases.debit-notes.create', compact(
            'suppliers', 'invoices', 'warehouses', 'bankAccounts', 'inventoryItems', 'debitNoteTypes', 'reasonCodes', 'sourceInvoice'
        ));
    }

    /**
     * Show the form for creating a new debit note
     */
    public function create(Request $request)
    {
        $this->authorize('create debit notes');

        $user = Auth::user();
        $branchId = $user->branch_id;
        $companyId = $user->company_id;

        // Get suppliers (company + branch scoped)
        $suppliers = Supplier::where('company_id', $companyId)
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        // Get purchase invoices for reference
        $invoices = PurchaseInvoice::with('supplier')
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date', 'desc')
            ->limit(100)
            ->get();

        // Get warehouses (inventory locations) for the active branch
        $warehouses = InventoryLocation::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        // Get bank accounts (no branch/status columns on model)
        $bankAccounts = BankAccount::orderBy('name')
            ->get();

        // Get inventory items
        $inventoryItems = InventoryItem::queryVisibleForSession($companyId)->with(['category'])
            ->orderBy('name')
            ->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        // Debit note types
        $debitNoteTypes = [
            'return' => 'Return',
            'discount' => 'Discount',
            'correction' => 'Correction',
            'overbilling' => 'Overbilling',
            'service_adjustment' => 'Service Adjustment',
            'post_invoice_discount' => 'Post Invoice Discount',
            'refund' => 'Refund',
            'restocking_fee' => 'Restocking Fee',
            'scrap_writeoff' => 'Scrap Write-off',
            'advance_refund' => 'Advance Refund',
            'fx_adjustment' => 'FX Adjustment',
            'other' => 'Other',
        ];

        // Reason codes
        $reasonCodes = [
            'defective_goods' => 'Defective Goods',
            'wrong_item' => 'Wrong Item',
            'overcharged' => 'Overcharged',
            'duplicate_billing' => 'Duplicate Billing',
            'service_not_provided' => 'Service Not Provided',
            'quality_issue' => 'Quality Issue',
            'late_delivery' => 'Late Delivery',
            'damaged_goods' => 'Damaged Goods',
            'quantity_discrepancy' => 'Quantity Discrepancy',
            'price_discrepancy' => 'Price Discrepancy',
            'other' => 'Other',
        ];

        $sourceInvoice = null;
        return view('purchases.debit-notes.create', compact(
            'suppliers', 
            'invoices', 
            'warehouses', 
            'bankAccounts',
            'inventoryItems',
            'debitNoteTypes',
            'reasonCodes',
            'sourceInvoice'
        ));
    }

    /**
     * Get items for a purchase invoice (for autofill in create form)
     */
    public function invoiceItemsJson(PurchaseInvoice $invoice)
    {
        $this->authorize('create debit notes');

        $items = $invoice->items()->with('inventoryItem')
            ->get()
            ->map(function($it) {
                return [
                    'id' => $it->id,
                    'inventory_item_id' => $it->inventory_item_id,
                    'item_name' => $it->inventoryItem->name ?? ($it->description ?? ''),
                    'item_code' => $it->inventoryItem->code ?? '',
                    'unit_of_measure' => $it->inventoryItem->unit_of_measure ?? '',
                    'quantity' => (float) $it->quantity,
                    'unit_cost' => (float) $it->unit_cost,
                    'vat_type' => $it->vat_type ?? 'inclusive',
                    'vat_rate' => (float) ($it->vat_rate ?? 18),
                ];
            });

        return response()->json([
            'supplier_id' => $invoice->supplier_id,
            'items' => $items,
        ]);
    }

    /**
     * Store a newly created debit note
     */
    public function store(Request $request)
    {
        $this->authorize('create debit notes');

        $request->validate([
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'reference_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'debit_note_date' => 'required|date',
            'type' => 'required|in:return,discount,correction,overbilling,service_adjustment,post_invoice_discount,refund,restocking_fee,scrap_writeoff,advance_refund,fx_adjustment,other',
            'reason_code' => 'nullable|string',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'refund_now' => 'boolean',
            'return_to_stock' => 'boolean',
            'restocking_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'currency' => 'string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_document' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:inventory_locations,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.linked_invoice_line_id' => 'nullable|exists:purchase_invoice_items,id',
            'items.*.warehouse_id' => 'nullable|exists:inventory_locations,id',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_code' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.unit_of_measure' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:inclusive,exclusive,no_vat',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_type' => 'nullable|in:none,percentage,fixed',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
            'items.*.return_to_stock' => 'boolean',
            'items.*.return_condition' => 'nullable|in:resellable,damaged,scrap,refurbish',
        ]);

        try {
            // Handle attachment upload (store path in data passed to service)
            $data = $request->all();
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $data['attachment'] = $file->storeAs('debit-note-attachments', $fileName, 'public');
            }

            $debitNote = $this->debitNoteService->createDebitNote($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Debit note created successfully.',
                'redirect' => route('purchases.debit-notes.show', $debitNote->encoded_id)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating debit note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified debit note
     */
    public function show(DebitNote $debitNote)
    {
        $this->authorize('view debit notes');

        $debitNote->load([
            'supplier',
            'purchaseInvoice',
            'referenceInvoice',
            'items.inventoryItem',
            'items.warehouse',
            'applications.purchaseInvoice',
            'applications.bankAccount',
            'createdBy',
            'approvedBy',
            'warehouse'
        ]);

        return view('purchases.debit-notes.show', compact('debitNote'));
    }

    /**
     * Show the form for editing the specified debit note
     */
    public function edit(DebitNote $debitNote)
    {
        $this->authorize('edit debit notes');

        if (!$debitNote->canEdit()) {
            abort(403, 'This debit note cannot be edited.');
        }

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        // Get suppliers (company + branch scoped)
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        // Get purchase invoices for reference
        $invoices = PurchaseInvoice::with('supplier')
            ->where('branch_id', $branchId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date', 'desc')
            ->limit(100)
            ->get();

        // Get warehouses (inventory locations) for the active branch
        $warehouses = InventoryLocation::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        // Get bank accounts
        $bankAccounts = BankAccount::orderBy('name')->get();

        // Get inventory items
        $inventoryItems = InventoryItem::queryVisibleForSession($companyId)->with(['category'])
            ->orderBy('name')
            ->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        // Debit note types
        $debitNoteTypes = [
            'return' => 'Return',
            'discount' => 'Discount',
            'correction' => 'Correction',
            'overbilling' => 'Overbilling',
            'service_adjustment' => 'Service Adjustment',
            'post_invoice_discount' => 'Post Invoice Discount',
            'refund' => 'Refund',
            'restocking_fee' => 'Restocking Fee',
            'scrap_writeoff' => 'Scrap Write-off',
            'advance_refund' => 'Advance Refund',
            'fx_adjustment' => 'FX Adjustment',
            'other' => 'Other',
        ];

        // Reason codes
        $reasonCodes = [
            'defective_goods' => 'Defective Goods',
            'wrong_item' => 'Wrong Item',
            'overcharged' => 'Overcharged',
            'duplicate_billing' => 'Duplicate Billing',
            'service_not_provided' => 'Service Not Provided',
            'quality_issue' => 'Quality Issue',
            'late_delivery' => 'Late Delivery',
            'damaged_goods' => 'Damaged Goods',
            'quantity_discrepancy' => 'Quantity Discrepancy',
            'price_discrepancy' => 'Price Discrepancy',
            'other' => 'Other',
        ];

        $debitNote->load(['items.inventoryItem', 'items.warehouse']);

        return view('purchases.debit-notes.edit', compact(
            'debitNote',
            'suppliers', 
            'invoices', 
            'warehouses', 
            'bankAccounts',
            'inventoryItems',
            'debitNoteTypes',
            'reasonCodes'
        ));
    }

    /**
     * Update the specified debit note
     */
    public function update(Request $request, DebitNote $debitNote)
    {
        $this->authorize('edit debit notes');

        if (!$debitNote->canEdit()) {
            abort(403, 'This debit note cannot be edited.');
        }

        $request->validate([
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'reference_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'debit_note_date' => 'required|date',
            'type' => 'required|in:return,discount,correction,overbilling,service_adjustment,post_invoice_discount,refund,restocking_fee,scrap_writeoff,advance_refund,fx_adjustment,other',
            'reason_code' => 'nullable|string',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'refund_now' => 'boolean',
            'return_to_stock' => 'boolean',
            'restocking_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'currency' => 'string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_document' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:inventory_locations,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.linked_invoice_line_id' => 'nullable|exists:purchase_invoice_items,id',
            'items.*.warehouse_id' => 'nullable|exists:inventory_locations,id',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_code' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.unit_of_measure' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:inclusive,exclusive,no_vat',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_type' => 'nullable|in:none,percentage,fixed',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
            'items.*.return_to_stock' => 'boolean',
            'items.*.return_condition' => 'nullable|in:resellable,damaged,scrap,refurbish',
        ]);

        try {
            $data = $request->all();

            // Handle attachment upload/update
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($debitNote->attachment && \Storage::disk('public')->exists($debitNote->attachment)) {
                    \Storage::disk('public')->delete($debitNote->attachment);
                }
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $data['attachment'] = $file->storeAs('debit-note-attachments', $fileName, 'public');
            } else {
                // Preserve existing attachment if no new file uploaded
                $data['attachment'] = $debitNote->attachment;
            }

            $debitNote = $this->debitNoteService->updateDebitNote($debitNote, $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Debit note updated successfully.',
                'redirect' => route('purchases.debit-notes.show', $debitNote->encoded_id)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating debit note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified debit note
     */
    public function destroy(DebitNote $debitNote)
    {
        $this->authorize('delete debit notes');

        if (!$debitNote->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'This debit note cannot be deleted.'
            ], 403);
        }

        try {
            $this->debitNoteService->deleteDebitNote($debitNote);
            
            return response()->json([
                'success' => true,
                'message' => 'Debit note deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting debit note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a debit note
     */
    public function approve(DebitNote $debitNote)
    {
        $this->authorize('approve debit notes');

        if (!$debitNote->canApprove()) {
            return response()->json([
                'success' => false,
                'message' => 'This debit note cannot be approved.'
            ], 403);
        }

        try {
            $this->debitNoteService->approveDebitNote($debitNote);
            
            return response()->json([
                'success' => true,
                'message' => 'Debit note approved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error approving debit note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply a debit note
     */
    public function apply(Request $request, DebitNote $debitNote)
    {
        $this->authorize('apply debit notes');

        if (!$debitNote->canApply()) {
            return response()->json([
                'success' => false,
                'message' => 'This debit note cannot be applied.'
            ], 403);
        }

        $request->validate([
            'application_type' => 'required|in:invoice,refund,debit_balance',
            'purchase_invoice_id' => 'required_if:application_type,invoice|exists:purchase_invoices,id',
            'bank_account_id' => 'required_if:application_type,refund|exists:bank_accounts,id',
            'amount_applied' => 'required|numeric|min:0.01|max:' . $debitNote->remaining_amount,
            'application_date' => 'required|date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->debitNoteService->applyDebitNote($debitNote, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Debit note applied successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error applying debit note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a debit note
     */
    public function cancel(DebitNote $debitNote)
    {
        $this->authorize('cancel debit notes');

        if (!$debitNote->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'This debit note cannot be cancelled.'
            ], 403);
        }

        try {
            $this->debitNoteService->cancelDebitNote($debitNote);
            
            return response()->json([
                'success' => true,
                'message' => 'Debit note cancelled successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling debit note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get inventory item details for AJAX
     */
    public function getInventoryItem(Request $request)
    {
        $itemId = $request->get('item_id');
        $item = InventoryItem::queryVisibleForSession()->with(['category', 'location'])->find($itemId);
        
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found']);
        }

        $branchId = session('branch_id') ?? (auth()->user()->branch_id ?? null);
        $locationId = session('location_id');

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->item_code ?? $item->code,
                'description' => $item->description,
                'unit_of_measure' => $item->unit_of_measure,
                'current_stock' => $item->current_stock,
                'cost_price' => $item->getCostPriceForBranchOrLocation($branchId, $locationId),
                'vat_rate' => $item->vat_rate ?? 18,
                'vat_type' => $item->vat_type ?? 'inclusive',
            ]
        ]);
    }
}