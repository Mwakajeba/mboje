<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase\PurchaseQuotation;
use App\Models\Purchase\PurchaseQuotationItem;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Inventory\Item;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\PurchaseQuotationMail;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    /**
     * Display a listing of purchase quotations
     */
    public function index()
    {
        if (!auth()->user()->hasPermissionTo('view purchase quotations')) {
            abort(403, 'Unauthorized access.');
        }

        return view('purchases.quotations.index');
    }

    /**
     * Datatable AJAX source for quotations list.
     */
    public function data(Request $request)
    {
        $quotations = PurchaseQuotation::with(['supplier', 'user', 'branch'])
            ->whereHas('branch', function($query) {
                $query->where('company_id', Auth::user()->company_id);
            })
            ->orderByDesc('created_at')
            ->get();

        $data = $quotations->map(function (PurchaseQuotation $q) {
            return [
                'id' => $q->id,
                'hash_id' => $q->hash_id,
                'reference' => $q->reference ?? ('QTN-' . str_pad($q->id, 6, '0', STR_PAD_LEFT)),
                'supplier' => $q->supplier->name ?? 'N/A',
                'type' => $q->is_request_for_quotation ? 'rfq' : 'quotation',
                'start_date' => optional($q->start_date)->format('Y-m-d'),
                'due_date' => optional($q->due_date)->format('Y-m-d'),
                'status' => $q->status,
                'total_amount' => $q->is_request_for_quotation ? null : number_format($q->total_amount, 2),
                'created_by' => $q->user->name ?? 'N/A',
                'show_url' => route('purchases.quotations.show', $q->hash_id),
                'edit_url' => route('purchases.quotations.edit', $q->hash_id),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Show the form for creating a new purchase quotation from a purchase invoice (Copy To).
     */
    public function createFromInvoice($invoiceEncodedId)
    {
        if (!auth()->user()->hasPermissionTo('create purchase quotations')) {
            abort(403, 'Unauthorized access.');
        }
        $invoiceId = Hashids::decode($invoiceEncodedId)[0] ?? null;
        if (!$invoiceId) {
            abort(404);
        }
        $user = Auth::user();
        $sourceInvoice = PurchaseInvoice::with(['supplier', 'items.inventoryItem'])
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->findOrFail($invoiceId);

        $suppliers = Supplier::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $items = Item::where('company_id', $user->company_id)->orderBy('name')->get();
        Item::withResolvedPricesForContext($items);
        $requisition = null;
        $requisitionItems = [];

        return view('purchases.quotations.create', compact('suppliers', 'items', 'requisition', 'requisitionItems', 'sourceInvoice'));
    }

    /**
     * Show the form for creating a new purchase quotation
     */
    public function create(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create purchase quotations')) {
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

        // Optional linkage to purchase requisition (RFQ from PR)
        $requisition = null;
        $requisitionItems = [];
        if ($request->filled('requisition')) {
            try {
                $requisition = \App\Models\Purchase\PurchaseRequisition::with(['lines.inventoryItem'])
                    ->where('company_id', Auth::user()->company_id)
                    ->where(function ($q) use ($request) {
                        // Support both hash_id and numeric id
                        $hash = $request->input('requisition');
                        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hash);
                        if (!empty($decoded)) {
                            $q->where('id', $decoded[0]);
                        } else {
                            $q->where('id', $hash);
                        }
                    })
                    ->first();

                if ($requisition) {
                    foreach ($requisition->lines as $line) {
                        if ((float) $line->quantity <= 0) {
                            continue;
                        }

                        $entry = [
                            'item_type' => $line->item_type ?? 'inventory',
                            'item_id' => null,
                            'asset_id' => null,
                            'fixed_asset_category_id' => $line->fixed_asset_category_id,
                            'intangible_asset_category_id' => $line->intangible_asset_category_id,
                            'description' => $line->description,
                            'unit' => $line->uom ?? 'units',
                            'quantity' => (float) $line->quantity,
                            'item_name' => null,
                            'item_code' => null,
                        ];

                        if ($line->item_type === 'fixed_asset' && $line->asset) {
                            $entry['asset_id'] = $line->asset_id;
                            $entry['item_name'] = $line->asset->name;
                            $entry['item_code'] = $line->asset->code ?? $line->asset->id;
                        } elseif ($line->item_type === 'intangible' && $line->intangibleAssetCategory) {
                            $entry['item_name'] = $line->intangibleAssetCategory->name;
                            $entry['item_code'] = $line->intangibleAssetCategory->code ?? $line->intangibleAssetCategory->id;
                        } elseif ($line->inventoryItem) {
                            $entry['item_id'] = $line->inventory_item_id;
                            $entry['item_name'] = $line->inventoryItem->name;
                            $entry['item_code'] = $line->inventoryItem->code;
                            $entry['unit'] = $line->inventoryItem->unit_of_measure ?? ($line->uom ?? 'units');
                        }

                        // Fallbacks
                        if (!$entry['item_name']) {
                            $entry['item_name'] = $line->description ?: 'PR Line #' . $line->id;
                        }
                        if (!$entry['item_code']) {
                            $entry['item_code'] = strtoupper($entry['item_type'] ?? 'line') . '-' . $line->id;
                        }

                        $requisitionItems[] = $entry;
                    }
                }
            } catch (\Throwable $e) {
                $requisition = null;
                $requisitionItems = [];
            }
        }

        $sourceInvoice = null;
        return view('purchases.quotations.create', compact('suppliers', 'items', 'requisition', 'requisitionItems', 'sourceInvoice'));
    }

    /**
     * Store a newly created purchase quotation
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create purchase quotations')) {
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
                'message' => 'Please select a branch before creating quotation.'
            ], 400);
        }
        
        $isRFQ = $request->boolean('is_request_for_quotation');
        
        $validationRules = [
            'supplier_id' => 'required_without:supplier_ids|nullable|exists:suppliers,id',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after:start_date',
            'is_request_for_quotation' => 'required|boolean',
            'items' => 'required|array|min:1',
            // Item-level validation supports inventory + asset/intangible
            'items.*.item_type' => 'nullable|in:inventory,fixed_asset,intangible',
            'items.*.item_id' => 'nullable|exists:inventory_items,id',
            'items.*.asset_id' => 'nullable|exists:assets,id',
            'items.*.fixed_asset_category_id' => 'nullable|exists:asset_categories,id',
            'items.*.intangible_asset_category_id' => 'nullable|exists:intangible_asset_categories,id',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.unit_of_measure' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0',
            'items.*.vat_amount' => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'purchase_requisition_id' => 'nullable|integer|exists:purchase_requisitions,id',
            'terms_conditions' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
        
        // Only require vat_type if not RFQ
        if (!$isRFQ) {
            $validationRules['items.*.vat_type'] = 'required|in:no_vat,inclusive,exclusive';
        } else {
            $validationRules['items.*.vat_type'] = 'nullable|in:no_vat,inclusive,exclusive';
        }
        
        $request->validate($validationRules);

        try {
            DB::beginTransaction();

            $isRFQ = $request->boolean('is_request_for_quotation');
            
            // Determine target suppliers: single supplier_id or multiple supplier_ids (for PR-linked RFQ)
            $supplierIds = [];
            if ($request->filled('supplier_ids')) {
                $supplierIds = collect($request->supplier_ids)
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            }
            if (empty($supplierIds) && $request->filled('supplier_id')) {
                $supplierIds = [(int) $request->supplier_id];
            }

            if (empty($supplierIds)) {
                throw new \Exception('Please select at least one supplier.');
            }

            // Calculate totals only if not RFQ (same totals will be used for each supplier)
            $subtotal = 0;
            $vatTotal = 0;
            $totalAmount = 0;

            if (!$isRFQ) {
                foreach ($request->items as $item) {
                    $vat = (float)($item['vat_amount'] ?? 0);
                    $total = (float)($item['total_amount'] ?? 0);
                    $subtotal += max(0, $total - $vat); // net
                    $vatTotal += $vat;
                    $totalAmount += $total;
                }
            }

            // Handle attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('purchase-quotation-attachments', $fileName, 'public');
            }

            // Create one quotation per supplier (bulk RFQ from PR)
            foreach ($supplierIds as $supplierId) {
                $quotation = PurchaseQuotation::create([
                    'purchase_requisition_id' => $request->purchase_requisition_id,
                    'supplier_id' => $supplierId,
                    'start_date' => $request->start_date,
                    'due_date' => $request->due_date,
                    'is_request_for_quotation' => $isRFQ,
                    'status' => 'draft',
                    'reference' => $request->reference ?? 'QTN-' . str_pad(PurchaseQuotation::count() + 1, 6, '0', STR_PAD_LEFT),
                    'discount_type' => 'percentage',
                    'discount_amount' => 0,
                    'total_amount' => $totalAmount,
                    'attachment' => $attachmentPath,
                    'branch_id' => $branchId,
                    'createdby' => Auth::id(),
                ]);

                // Create quotation items
                foreach ($request->items as $itemData) {
                    PurchaseQuotationItem::create([
                        'purchase_id' => $quotation->id,
                        'item_id' => $itemData['item_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $isRFQ ? 0 : ($itemData['unit_price'] ?? 0),
                        'tax_calculation_type' => 'percentage',
                        'vat_type' => $isRFQ ? 'no_vat' : ($itemData['vat_type'] ?? 'no_vat'),
                        'vat_rate' => $isRFQ ? 0 : ($itemData['vat_rate'] ?? 0),
                        'tax_amount' => $isRFQ ? 0 : ($itemData['vat_amount'] ?? 0),
                        'total_amount' => $isRFQ ? 0 : ($itemData['total_amount'] ?? 0),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase quotation created successfully!',
                'redirect_url' => route('purchases.quotations.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating purchase quotation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified purchase quotation
     */
    public function show(PurchaseQuotation $quotation)
    {
        if (!auth()->user()->hasPermissionTo('view purchase quotation details')) {
            abort(403, 'Unauthorized access.');
        }

        $quotation->load(['supplier', 'user', 'branch', 'quotationItems.item'])
                  ->loadCount('orders');

        // Fallback defensive count to ensure accuracy in UI
        $ordersCount = \App\Models\Purchase\PurchaseOrder::where('quotation_id', $quotation->id)->count();

        // Debug: log items info for troubleshooting missing items
        try {
            $itemCount = $quotation->quotationItems->count();
            $itemIds = $quotation->quotationItems->pluck('id');
            Log::info('Quotation items debug', [
                'quotation_id' => $quotation->id,
                'hash_id' => method_exists($quotation, 'getHashIdAttribute') ? $quotation->hash_id : null,
                'items_count' => $itemCount,
                'item_ids' => $itemIds,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Quotation items debug failed', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage(),
            ]);
        }
        
        return view('purchases.quotations.show', compact('quotation', 'ordersCount'));
    }

    /**
     * Show the form for editing the specified purchase quotation
     */
    public function edit(PurchaseQuotation $quotation)
    {
        if (!auth()->user()->hasPermissionTo('edit purchase quotations')) {
            abort(403, 'Unauthorized access.');
        }

        if ($quotation->status !== 'draft') {
            return redirect()->route('purchases.quotations.show', $quotation->id)
                ->with('error', 'Only draft quotations can be edited.');
        }

        $quotation->load(['quotationItems.item']);
        
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $items = Item::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();
        Item::withResolvedPricesForContext($items);

        return view('purchases.quotations.edit', compact('quotation', 'suppliers', 'items'));
    }

    /**
     * Update the specified purchase quotation
     */
    public function update(Request $request, PurchaseQuotation $quotation)
    {
        if (!auth()->user()->hasPermissionTo('edit purchase quotations')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        if ($quotation->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft quotations can be updated.'
            ], 400);
        }

        $isRFQ = $request->boolean('is_request_for_quotation');
        
        $validationRules = [
            'supplier_id' => 'required|exists:suppliers,id',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after:start_date',
            'is_request_for_quotation' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.vat_rate' => 'nullable|numeric|min:0',
            'items.*.vat_amount' => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'terms_conditions' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
        
        // Only require vat_type if not RFQ
        if (!$isRFQ) {
            $validationRules['items.*.vat_type'] = 'required|in:no_vat,inclusive,exclusive';
        } else {
            $validationRules['items.*.vat_type'] = 'nullable|in:no_vat,inclusive,exclusive';
        }
        
        $request->validate($validationRules);

        try {
            DB::beginTransaction();

            $isRFQ = $request->boolean('is_request_for_quotation');
            
            // Calculate totals only if not RFQ
            $subtotal = 0;
            $vatTotal = 0;
            $totalAmount = 0;

            if (!$isRFQ) {
                foreach ($request->items as $item) {
                    $vat = (float)($item['vat_amount'] ?? 0);
                    $total = (float)($item['total_amount'] ?? 0);
                    $subtotal += max(0, $total - $vat); // net
                    $vatTotal += $vat;
                    $totalAmount += $total;
                }
            }

            $updateData = [
                'supplier_id' => $request->supplier_id,
                'start_date' => $request->start_date,
                'due_date' => $request->due_date,
                'is_request_for_quotation' => $isRFQ,
                'total_amount' => $totalAmount,
            ];

            // Handle attachment upload
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($quotation->attachment && \Storage::disk('public')->exists($quotation->attachment)) {
                    \Storage::disk('public')->delete($quotation->attachment);
                }
                // Store new attachment
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $updateData['attachment'] = $file->storeAs('purchase-quotation-attachments', $fileName, 'public');
            }

            $quotation->update($updateData);

            // Delete existing items and recreate
            $quotation->quotationItems()->delete();

            // Create quotation items
            foreach ($request->items as $itemData) {
                PurchaseQuotationItem::create([
                    'purchase_id' => $quotation->id,
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $isRFQ ? 0 : ($itemData['unit_price'] ?? 0),
                    'tax_calculation_type' => 'percentage',
                    'vat_type' => $isRFQ ? 'no_vat' : ($itemData['vat_type'] ?? 'no_vat'),
                    'vat_rate' => $isRFQ ? 0 : ($itemData['vat_rate'] ?? 0),
                    'tax_amount' => $isRFQ ? 0 : ($itemData['vat_amount'] ?? 0),
                    'total_amount' => $isRFQ ? 0 : ($itemData['total_amount'] ?? 0),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase quotation updated successfully!',
                'redirect_url' => route('purchases.quotations.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating purchase quotation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update quotation status
     */
    public function updateStatus(Request $request, PurchaseQuotation $quotation)
    {
        if (!auth()->user()->hasPermissionTo('manage purchase quotations')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:draft,sent,approved,rejected,expired'
        ]);

        $quotation->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Quotation status updated successfully!'
        ]);
    }

    /**
     * Send quotation email to supplier
     */
    public function sendEmail(Request $request, PurchaseQuotation $quotation)
    {
        if (!auth()->user()->hasPermissionTo('send purchase quotations')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'subject' => 'nullable|string|max:255',
                'message' => 'nullable|string',
                'email' => 'nullable|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Validation failed', 
                    'errors' => $validator->errors()
                ], 422);
            }

            // Use provided email or supplier email
            $email = $request->email ?? $quotation->supplier->email;
            if (!$email) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No email address available for supplier'
                ], 400);
            }

            // Send email
            Mail::to($email)->send(new PurchaseQuotationMail(
                $quotation,
                $request->subject,
                $request->message
            ));

            // Update quotation status to sent if it was draft
            if ($quotation->status === 'draft') {
                $quotation->update(['status' => 'sent']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Quotation email sent successfully to ' . $email
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print/Export quotation as PDF
     */
    public function print(PurchaseQuotation $quotation)
    {
        if (!auth()->user()->hasPermissionTo('view purchase quotation details')) {
            abort(403, 'Unauthorized access.');
        }

        $quotation->load([
            'supplier', 
            'user', 
            'branch.company', 
            'quotationItems.item'
        ]);

        $company = $quotation->branch->company ?? auth()->user()->company ?? null;
        
        // Get bank accounts for payment methods
        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($quotation) {
            $companyId = $quotation->branch->company_id ?? auth()->user()->company_id;
            $q->where('company_id', $companyId);
        })->orderBy('name')->get();

        $pdf = Pdf::loadView('purchases.quotations.print', compact('quotation', 'company', 'bankAccounts'));
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'Quotation_' . $quotation->reference . '_' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Remove the specified purchase quotation
     */
    public function destroy(PurchaseQuotation $quotation)
    {
        if (!auth()->user()->hasPermissionTo('delete purchase quotations')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        if ($quotation->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft quotations can be deleted.'
            ], 400);
        }

        try {
            DB::beginTransaction();
            
            // Delete quotation items first
            $quotation->quotationItems()->delete();
            
            // Delete the quotation
            $quotation->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase quotation deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting purchase quotation: ' . $e->getMessage()
            ], 500);
        }
    }
}
