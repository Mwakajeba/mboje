<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\CreditNote;
use App\Models\Sales\CreditNoteItem;
use App\Models\Sales\CreditNoteApplication;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryLocation;
use App\Models\BankAccount;
use App\Services\CreditNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;


class CreditNoteController extends Controller
{
    protected $creditNoteService;

    public function __construct(CreditNoteService $creditNoteService)
    {
        $this->creditNoteService = $creditNoteService;
    }
    /**
     * Display a listing of credit notes
     */
    public function index(Request $request)
    {
        $this->authorize('view credit notes');

        $user = Auth::user();
        $branchId = $user->branch_id;
        $companyId = $user->company_id;

        if ($request->ajax()) {
            $creditNotes = CreditNote::with(['customer', 'salesInvoice', 'createdBy', 'approvedBy'])
                ->whereHas('customer', function ($query) use ($user) {
                    $query->whereHas('branch', function ($q) use ($user) {
                        $q->where('company_id', $user->company_id);
                    });
                })
                ->when($user->branch_id, function ($query) use ($user) {
                    return $query->where('branch_id', $user->branch_id);
                });

            return \DataTables::of($creditNotes)
                ->addColumn('actions', function ($creditNote) {
                    $encodedId = \Vinkla\Hashids\Facades\Hashids::encode($creditNote->id);
                    $actions = '<div class="d-flex gap-2">';
                    $actions .= '<a href="' . route('sales.credit-notes.show', $encodedId) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                    
                    if ($creditNote->status === 'draft') {
                        $actions .= '<a href="' . route('sales.credit-notes.edit', $encodedId) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bx bx-edit"></i></a>';
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-success" onclick="approveCreditNote(\'' . $encodedId . '\', \'' . e($creditNote->credit_note_number) . '\')" title="Approve"><i class="bx bx-check"></i></button>';
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCreditNote(\'' . $encodedId . '\', \'' . e($creditNote->credit_note_number) . '\')" title="Delete"><i class="bx bx-trash"></i></button>';
                    } elseif ($creditNote->status === 'issued') {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-info" onclick="applyCreditNote(\'' . $encodedId . '\', \'' . e($creditNote->credit_note_number) . '\', \'' . $creditNote->remaining_amount . '\')" title="Apply"><i class="bx bx-transfer"></i></button>';
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="cancelCreditNote(\'' . $encodedId . '\', \'' . e($creditNote->credit_note_number) . '\')" title="Cancel"><i class="bx bx-x"></i></button>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->addColumn('status_badge', function ($creditNote) {
                    $colors = [
                        'draft' => 'secondary',
                        'issued' => 'primary',
                        'applied' => 'success',
                        'cancelled' => 'danger'
                    ];
                    $color = $colors[$creditNote->status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . strtoupper($creditNote->status) . '</span>';
                })
                ->addColumn('type_badge', function ($creditNote) {
                    $colors = [
                        'return' => 'warning',
                        'discount' => 'info',
                        'correction' => 'secondary',
                        'other' => 'dark'
                    ];
                    $color = $colors[$creditNote->type] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . strtoupper($creditNote->type) . '</span>';
                })
                ->addColumn('formatted_date', function ($creditNote) {
                    return format_date($creditNote->credit_note_date, 'M d, Y');
                })
                ->addColumn('formatted_total', function ($creditNote) {
                    return 'TZS ' . number_format($creditNote->total_amount, 2);
                })
                ->addColumn('formatted_applied', function ($creditNote) {
                    return 'TZS ' . number_format($creditNote->applied_amount, 2);
                })
                ->addColumn('formatted_remaining', function ($creditNote) {
                    return 'TZS ' . number_format($creditNote->remaining_amount, 2);
                })
                ->addColumn('customer_name', function ($creditNote) {
                    return $creditNote->customer->name ?? 'N/A';
                })
                ->addColumn('invoice_number', function ($creditNote) {
                    return $creditNote->salesInvoice->invoice_number ?? 'N/A';
                })
                ->addColumn('encoded_id', function ($creditNote) {
                    return \Vinkla\Hashids\Facades\Hashids::encode($creditNote->id);
                })
                ->rawColumns(['actions', 'status_badge', 'type_badge'])
                ->make(true);
        }

        // Get stats for the dashboard
        $creditNotes = CreditNote::with(['customer', 'salesInvoice', 'createdBy', 'approvedBy'])
            ->whereHas('customer', function ($query) use ($user) {
                $query->whereHas('branch', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                });
            })
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            });

        $stats = [
            'total_credit_notes' => $creditNotes->count(),
            'draft' => $creditNotes->where('status', 'draft')->count(),
            'issued' => $creditNotes->where('status', 'issued')->count(),
            'applied' => $creditNotes->where('status', 'applied')->count(),
            'cancelled' => $creditNotes->where('status', 'cancelled')->count(),
        ];

        return view('sales.credit-notes.index', compact('stats'));
    }

    /**
     * Show the form for creating a new credit note from a sales invoice (Copy To).
     */
    public function createFromInvoice($invoiceEncodedId)
    {
        $this->authorize('create credit notes');
        $invoiceId = Hashids::decode($invoiceEncodedId)[0] ?? null;
        if (!$invoiceId) {
            abort(404);
        }
        $user = Auth::user();
        $sourceInvoice = SalesInvoice::with(['customer', 'items.inventoryItem'])
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->findOrFail($invoiceId);

        $branchId = $user->branch_id;
        $companyId = $user->company_id;
        $customers = Customer::forBranch($branchId)->forCompany($companyId)->get();
        $invoices = SalesInvoice::where('customer_id', $sourceInvoice->customer_id)
            ->whereIn('status', ['sent', 'paid'])
            ->get();

        $selectedLocationId = session('location_id');
        if (!empty($selectedLocationId)) {
            $warehouses = InventoryLocation::query()
                ->where('id', $selectedLocationId)
                ->when(Schema::hasColumn('inventory_locations', 'is_active'), function ($q) {
                    $q->where('is_active', true);
                })
                ->get();
        } else {
            $warehouses = InventoryLocation::query()
                ->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->when(Schema::hasColumn('inventory_locations', 'is_active'), function ($q) {
                    $q->where('is_active', true);
                })
                ->orderBy('name')
                ->get();
            if ($warehouses->isEmpty() && $branchId) {
                $warehouses = InventoryLocation::query()
                    ->where('branch_id', $branchId)
                    ->when(Schema::hasColumn('inventory_locations', 'is_active'), function ($q) {
                        $q->where('is_active', true);
                    })
                    ->orderBy('name')
                    ->get();
            }
            if ($warehouses->isEmpty()) {
                $defaultId = InventoryLocation::query()
                    ->where('company_id', $companyId)
                    ->when(Schema::hasColumn('inventory_locations', 'is_active'), function ($q) {
                        $q->where('is_active', true);
                    })
                    ->orderBy('name')
                    ->value('id');
                $warehouses = $defaultId ? InventoryLocation::where('id', $defaultId)->get() : collect();
            }
            $warehouses = $warehouses->unique('id')->values();
        }
        $bankAccounts = BankAccount::orderBy('name')->get();
        $inventoryItems = \App\Models\Inventory\Item::where('company_id', $user->company_id)->orderBy('name')->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);
        $items = $inventoryItems;
        $creditNoteTypes = [
            'return' => 'Goods Return',
            'exchange' => 'Item Exchange/Replacement',
            'discount' => 'Price/Quantity Discount',
            'correction' => 'Billing Correction',
            'overbilling' => 'Overbilling',
            'service_adjustment' => 'Service Adjustment',
            'post_invoice_discount' => 'Post Invoice Discount',
            'refund' => 'Refund',
            'restocking_fee' => 'Restocking Fee',
            'scrap_writeoff' => 'Scrap/Write-off',
            'advance_refund' => 'Advance Refund',
            'fx_adjustment' => 'FX Adjustment',
            'other' => 'Other',
        ];
        $reasonCodes = [
            'return' => ['damaged' => 'Damaged in Transit', 'defective' => 'Defective', 'wrong_item' => 'Wrong Item', 'excess' => 'Excess Delivery', 'other' => 'Other'],
            'discount' => ['volume' => 'Volume Discount', 'promotional' => 'Promotional', 'price_match' => 'Price Match', 'other' => 'Other'],
            'correction' => ['pricing' => 'Pricing Error', 'quantity' => 'Quantity Error', 'duplicate' => 'Duplicate Invoice', 'other' => 'Other'],
            'other' => ['other' => 'Other'],
        ];
        foreach (array_keys($creditNoteTypes) as $type) {
            if (!isset($reasonCodes[$type])) {
                $reasonCodes[$type] = ['other' => 'Other'];
            }
        }

        return view('sales.credit-notes.create', compact(
            'customers',
            'invoices',
            'warehouses',
            'bankAccounts',
            'inventoryItems',
            'items',
            'creditNoteTypes',
            'reasonCodes',
            'sourceInvoice'
        ));
    }

    /**
     * Show the form for creating a new credit note
     */
    public function create(Request $request)
    {
        $this->authorize('create credit notes');

        $user = Auth::user();
        $branchId = $user->branch_id;
        $companyId = $user->company_id;

        $customers = Customer::forBranch($branchId)->forCompany($companyId)->get();
        $invoices = collect();
        $sourceInvoice = null;

        if ($request->filled('customer_id')) {
            $invoices = SalesInvoice::where('customer_id', $request->customer_id)
                ->whereIn('status', ['sent', 'paid'])
                ->get();
        }

        // Determine a specific default location if present
        $selectedLocationId = session('location_id');

        if (!empty($selectedLocationId)) {
            // Only show the current session location to avoid clutter
            $warehouses = InventoryLocation::query()
                ->where('id', $selectedLocationId)
                ->when(Schema::hasColumn('inventory_locations', 'is_active'), function ($q) {
                    $q->where('is_active', true);
                })
                ->get();
        } else {
            // Load warehouses in priority: user's assigned locations -> branch locations -> single company-wide default
            $warehouses = InventoryLocation::query()
                ->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->when(Schema::hasColumn('inventory_locations', 'is_active'), function ($q) {
                    $q->where('is_active', true);
                })
                ->orderBy('name')
                ->get();

            if ($warehouses->isEmpty() && $branchId) {
                $warehouses = InventoryLocation::query()
                    ->where('branch_id', $branchId)
                    ->when(Schema::hasColumn('inventory_locations', 'is_active'), function ($q) {
                        $q->where('is_active', true);
                    })
                    ->orderBy('name')
                    ->get();
            }

            if ($warehouses->isEmpty()) {
                // Final fallback: only pick ONE sensible default to avoid long duplicates list
                $defaultId = InventoryLocation::query()
                    ->where('company_id', $companyId)
                    ->when(Schema::hasColumn('inventory_locations', 'is_active'), function ($q) {
                        $q->where('is_active', true);
                    })
                    ->orderBy('name')
                    ->value('id');

                $warehouses = $defaultId
                    ? InventoryLocation::where('id', $defaultId)->get()
                    : collect();
            }

            $warehouses = $warehouses->unique('id')->values();
        }
        $bankAccounts = BankAccount::orderBy('name')->get();
        $inventoryItems = \App\Models\Inventory\Item::where('company_id', $companyId)->orderBy('name')->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);
        $items = $inventoryItems;

        // Credit note types and reason codes
        $creditNoteTypes = [
            'return' => 'Goods Return',
            'exchange' => 'Item Exchange/Replacement',
            'discount' => 'Price/Quantity Discount',
            'correction' => 'Price/Quantity Correction',
            'overbilling' => 'Overbilling/Duplicate Invoice',
            'service_adjustment' => 'Service Adjustment',
            'post_invoice_discount' => 'Post-Invoice Discount',
            'refund' => 'Refund',
            'restocking_fee' => 'Restocking Fee',
            'scrap_writeoff' => 'Scrap Writeoff',
            'advance_refund' => 'Advance Refund',
            'fx_adjustment' => 'FX Adjustment',
            'other' => 'Other',
        ];

        $reasonCodes = [
            'quality_issue' => 'Quality Issue',
            'price_adjustment' => 'Price Adjustment',
            'duplicate_billing' => 'Duplicate Billing',
            'order_cancellation' => 'Order Cancellation',
            'service_quality' => 'Service Quality',
            'overbilling' => 'Overbilling',
            'promotional_discount' => 'Promotional Discount',
            'volume_discount' => 'Volume Discount',
            'damaged_goods' => 'Damaged Goods',
            'wrong_item' => 'Wrong Item',
            'late_delivery' => 'Late Delivery',
            'sla_penalty' => 'SLA Penalty',
            'commercial_goodwill' => 'Commercial Goodwill',
            'tax_correction' => 'Tax Correction',
            'fx_adjustment' => 'FX Adjustment',
            'other' => 'Other',
        ];

        // Debug: Log the data being passed to view
        \Log::info('Credit Note Create Method Debug:', [
            'customers_count' => $customers->count(),
            'invoices_count' => $invoices->count(),
            'warehouses_count' => $warehouses->count(),
            'bank_accounts_count' => $bankAccounts->count(),
            'user_branch_id' => $branchId,
            'user_company_id' => $companyId
        ]);

        return view('sales.credit-notes.create', compact(
            'customers', 
            'invoices', 
            'warehouses', 
            'bankAccounts',
            'inventoryItems',
            'items',
            'creditNoteTypes',
            'reasonCodes',
            'sourceInvoice'
        ));
    }

    /**
     * Store a newly created credit note
     */
    public function store(Request $request)
    {
        $this->authorize('create credit notes');

        // Debug: Log the request data
        \Log::info('Credit Note Store Request Data:', $request->all());

        $request->validate([
            'sales_invoice_id' => 'nullable|exists:sales_invoices,id',
            'reference_invoice_id' => 'nullable|exists:sales_invoices,id',
            'customer_id' => 'required|exists:customers,id',
            'credit_note_date' => 'required|date',
            'type' => 'required|in:return,exchange,discount,correction,overbilling,service_adjustment,post_invoice_discount,refund,restocking_fee,scrap_writeoff,advance_refund,fx_adjustment,other',
            'reason_code' => 'nullable|string',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'refund_now' => 'nullable|in:0,1,true,false',
            'return_to_stock' => 'nullable|in:0,1,true,false',
            'restocking_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'currency' => 'required|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_document' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:inventory_locations,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'vat_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.linked_invoice_line_id' => 'nullable|exists:sales_invoice_items,id',
            'items.*.warehouse_id' => 'nullable|exists:inventory_locations,id',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_code' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.unit_of_measure' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.return_to_stock' => 'nullable|in:0,1,true,false',
            'items.*.return_condition' => 'nullable|in:resellable,damaged,scrap,refurbish',
            'items.*.vat_type' => 'required|in:inclusive,exclusive,no_vat',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_type' => 'nullable|in:percentage,fixed,none',
            'items.*.discount_rate' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.item_condition_notes' => 'nullable|string',
            
            // Replacement items validation (for exchanges)
            'replacement_items' => 'nullable|array',
            'replacement_items.*.inventory_item_id' => 'required_with:replacement_items|exists:inventory_items,id',
            'replacement_items.*.item_name' => 'required_with:replacement_items|string|max:255',
            'replacement_items.*.item_code' => 'nullable|string|max:100',
            'replacement_items.*.unit_of_measure' => 'nullable|string|max:50',
            'replacement_items.*.quantity' => 'required_with:replacement_items|numeric|min:0.01',
            'replacement_items.*.unit_price' => 'required_with:replacement_items|numeric|min:0',
            'replacement_items.*.vat_type' => 'required_with:replacement_items|in:inclusive,exclusive,no_vat',
            'replacement_items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'replacement_items.*.notes' => 'nullable|string',
            'is_exchange' => 'nullable|in:0,1,true,false',
        ]);

        // Additional validation based on credit note type
        $type = $request->input('type');
        if (in_array($type, ['return', 'overbilling', 'refund']) && empty($request->input('sales_invoice_id')) && empty($request->input('reference_invoice_id'))) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A reference invoice is required for ' . $type . ' credit notes.',
                    'errors' => ['reference_invoice_id' => ['A reference invoice is required for ' . $type . ' credit notes.']],
                ], 422);
            }
            return back()->withInput()->withErrors(['reference_invoice_id' => 'A reference invoice is required for ' . $type . ' credit notes.']);
        }

        // Additional validation for exchange type
        if ($type === 'exchange') {
            if (empty($request->input('items'))) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'At least one returned item is required for exchanges.',
                        'errors' => ['items' => ['At least one returned item is required for exchanges.']],
                    ], 422);
                }
                return back()->withInput()->withErrors(['items' => 'At least one returned item is required for exchanges.']);
            }
            if (empty($request->input('replacement_items'))) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'At least one replacement item is required for exchanges.',
                        'errors' => ['replacement_items' => ['At least one replacement item is required for exchanges.']],
                    ], 422);
                }
                return back()->withInput()->withErrors(['replacement_items' => 'At least one replacement item is required for exchanges.']);
            }
        }

        try {
            // Use the service to create the credit note
            $creditNote = $this->creditNoteService->createCreditNote($request->all());

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Credit note created successfully.',
                    'redirect_url' => route('sales.credit-notes.show', $creditNote->encoded_id),
                ]);
            }

            return redirect()->route('sales.credit-notes.show', $creditNote->encoded_id)
                ->with('success', 'Credit note created successfully.');

        } catch (\Exception $e) {
            // Debug: Log the full error
            \Log::error('Credit Note Creation Error: ' . $e->getMessage());
            \Log::error('Error File: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Request data: ', $request->all());
            \Log::error('User info: ', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user() ? auth()->user()->name : 'No user',
                'branch_id' => auth()->user() ? auth()->user()->branch_id : 'No branch',
                'company_id' => auth()->user() ? auth()->user()->company_id : 'No company'
            ]);

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create credit note: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->withErrors(['error' => 'Failed to create credit note: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified credit note
     */
    public function show($encodedId)
    {
        $this->authorize('view credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            abort(404);
        }

        $creditNote = CreditNote::with([
            'customer', 
            'salesInvoice', 
            'referenceInvoice',
            'items.inventoryItem', 
            'createdBy', 
            'approvedBy',
            'bankAccount',
            'glTransactions.chartAccount'
        ])->findOrFail($creditNoteId);

        return view('sales.credit-notes.show', compact('creditNote'));
    }

    /**
     * Export the specified credit note as PDF
     */
    public function exportPdf($encodedId)
    {
        $this->authorize('view credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            abort(404);
        }

        $creditNote = CreditNote::with([
            'customer',
            'salesInvoice',
            'referenceInvoice',
            'items.inventoryItem',
            'createdBy',
            'approvedBy',
            'bankAccount',
            'branch',
            'company',
            'glTransactions.chartAccount'
        ])->findOrFail($creditNoteId);

        $company = $creditNote->company ?? auth()->user()->company ?? null;
        
        // Get bank accounts for payment methods
        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($creditNote) {
            $q->where('company_id', $creditNote->company_id ?? auth()->user()->company_id);
        })->orderBy('name')->get();

        $pdf = Pdf::loadView('sales.credit-notes.pdf', compact('creditNote', 'company', 'bankAccounts'));
        $pdf->setPaper('A4', 'portrait');

        $fileName = 'Credit_Note_' . $creditNote->credit_note_number . '.pdf';
        return $pdf->stream($fileName);
    }

    /**
     * Show the form for editing the specified credit note
     */
    public function edit($encodedId)
    {
        $this->authorize('edit credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            abort(404);
        }

        $creditNote = CreditNote::with(['items', 'customer', 'salesInvoice'])->findOrFail($creditNoteId);

        if ($creditNote->status !== 'draft') {
            return redirect()->route('sales.credit-notes.show', $creditNote->encoded_id)
                ->with('error', 'Only draft credit notes can be edited.');
        }

        $user = Auth::user();
        $customers = Customer::forBranch($user->branch_id)->forCompany($user->company_id)->get();
        $bankAccounts = BankAccount::orderBy('name')->get();
        $inventoryItems = InventoryItem::where('company_id', $user->company_id)->where('is_active', true)->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($inventoryItems);

        return view('sales.credit-notes.edit', compact('creditNote', 'customers', 'bankAccounts', 'inventoryItems'));
    }

    /**
     * Update the specified credit note
     */
    public function update(Request $request, $encodedId)
    {
        $this->authorize('edit credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            abort(404);
        }

        $creditNote = CreditNote::findOrFail($creditNoteId);

        if ($creditNote->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Only draft credit notes can be edited.'], 422);
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'credit_note_date' => 'required|date',
            'type' => 'required|in:return,exchange,discount,correction,other',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'refund_now' => 'boolean',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'return_to_stock' => 'boolean',
            'is_exchange' => 'boolean',
            'replacement_items' => 'nullable|array',
            'replacement_items.*.inventory_item_id' => 'required_with:replacement_items|exists:inventory_items,id',
            'replacement_items.*.quantity' => 'required_with:replacement_items|numeric|min:0.01',
            'replacement_items.*.unit_price' => 'required_with:replacement_items|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Update credit note basic information
            $updateData = [
                'customer_id' => $request->customer_id,
                'credit_note_date' => $request->credit_note_date,
                'type' => $request->type,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'refund_now' => $request->boolean('refund_now'),
                'bank_account_id' => $request->bank_account_id,
                'return_to_stock' => $request->boolean('return_to_stock'),
                'updated_by' => Auth::id(),
            ];

            $creditNote->update($updateData);

            // Handle replacement items for exchanges
            if ($request->type === 'exchange' && $request->has('replacement_items')) {
                // Delete existing replacement items
                $creditNote->items()->where('is_replacement', true)->delete();
                
                // Create new replacement items
                foreach ($request->replacement_items as $itemData) {
                    $itemData['credit_note_id'] = $creditNote->id;
                    $itemData['is_replacement'] = true;
                    $itemData['line_total'] = $itemData['quantity'] * $itemData['unit_price'];
                    
                    // Calculate VAT
                    $vatAmount = 0;
                    if ($itemData['vat_type'] === 'exclusive') {
                        $vatAmount = $itemData['line_total'] * ($itemData['vat_rate'] / 100);
                    } elseif ($itemData['vat_type'] === 'inclusive') {
                        $vatAmount = $itemData['line_total'] * ($itemData['vat_rate'] / (100 + $itemData['vat_rate']));
                    }
                    $itemData['vat_amount'] = $vatAmount;
                    
                    CreditNoteItem::create($itemData);
                }
            }

            DB::commit();

            return redirect()->route('sales.credit-notes.show', $creditNote->encoded_id)
                ->with('success', 'Credit note updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Debug: Log the full error
            \Log::error('Credit Note Update Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return back()->withInput()->withErrors(['error' => 'Failed to update credit note: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified credit note
     */
    public function destroy($encodedId)
    {
        $this->authorize('delete credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            return response()->json(['success' => false, 'message' => 'Invalid credit note ID.'], 404);
        }

        try {
            DB::beginTransaction();

            $creditNote = CreditNote::findOrFail($creditNoteId);

            if ($creditNote->status !== 'draft') {
                return response()->json(['success' => false, 'message' => 'Only draft credit notes can be deleted.'], 422);
            }

            // Delete inventory movements for this credit note (this handles stock reversal)
            \App\Models\Inventory\Movement::where('reference_type', 'credit_note')
                ->where('reference_id', $creditNote->id)
                ->delete();

            // Delete GL transactions
            $creditNote->glTransactions()->delete();

            // Delete items
            $creditNote->items()->delete();

            // Delete credit note
            $creditNote->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Credit note deleted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete credit note: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Approve credit note
     */
    public function approve($encodedId)
    {
        $this->authorize('approve credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            return response()->json(['success' => false, 'message' => 'Invalid credit note ID.'], 404);
        }

        try {
            DB::beginTransaction();

            $creditNote = CreditNote::findOrFail($creditNoteId);

            if ($creditNote->status !== 'draft') {
                return response()->json(['success' => false, 'message' => 'Only draft credit notes can be approved.'], 422);
            }

            // Approve credit note (this will create GL transactions and process inventory returns)
            $creditNote->approve();
            
            // Log activity
            $customerName = $creditNote->customer ? $creditNote->customer->name : 'N/A';
            $creditNote->logActivity('approve', "Approved Credit Note {$creditNote->credit_note_number} for Customer: {$customerName}", [
                'Credit Note Number' => $creditNote->credit_note_number,
                'Customer' => $customerName,
                'Total Amount' => number_format($creditNote->total_amount, 2),
                'Credit Note Date' => $creditNote->credit_note_date ? $creditNote->credit_note_date->format('Y-m-d') : 'N/A',
                'Approved By' => Auth::user()->name,
                'Approved At' => now()->format('Y-m-d H:i:s')
            ]);

            // If the credit note references a specific invoice, auto-apply it to reduce that invoice balance
            // This ensures the payment form shows the correct remaining amount immediately
            $linkedInvoiceId = $creditNote->sales_invoice_id ?: $creditNote->reference_invoice_id;
            if ($linkedInvoiceId && $creditNote->remaining_amount > 0) {
                try {
                    $invoice = \App\Models\Sales\SalesInvoice::find($linkedInvoiceId);
                    if ($invoice) {
                        // Apply up to the lesser of remaining credit and invoice balance
                        $this->creditNoteService->applyToInvoice($creditNote, $invoice);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Auto-apply of credit note on approval failed', [
                        'credit_note_id' => $creditNote->id,
                        'invoice_id' => $linkedInvoiceId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Credit note approved successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to approve credit note: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel credit note
     */
    public function cancel(Request $request, $encodedId)
    {
        $this->authorize('cancel credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            return response()->json(['success' => false, 'message' => 'Invalid credit note ID.'], 404);
        }

        try {
            DB::beginTransaction();

            $creditNote = CreditNote::findOrFail($creditNoteId);

            if ($creditNote->status === 'applied') {
                return response()->json(['success' => false, 'message' => 'Cannot cancel an applied credit note.'], 422);
            }

            // Cancel credit note
            $creditNote->cancel($request->reason);
            
            // Log activity
            $customerName = $creditNote->customer ? $creditNote->customer->name : 'N/A';
            $creditNote->logActivity('cancel', "Cancelled Credit Note {$creditNote->credit_note_number} for Customer: {$customerName}", [
                'Credit Note Number' => $creditNote->credit_note_number,
                'Customer' => $customerName,
                'Total Amount' => number_format($creditNote->total_amount, 2),
                'Credit Note Date' => $creditNote->credit_note_date ? $creditNote->credit_note_date->format('Y-m-d') : 'N/A',
                'Cancellation Reason' => $request->reason ?? 'No reason provided',
                'Cancelled By' => Auth::user()->name,
                'Cancelled At' => now()->format('Y-m-d H:i:s')
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Credit note cancelled successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to cancel credit note: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Apply credit note
     */
    public function apply(Request $request, $encodedId)
    {
        $this->authorize('apply credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            return response()->json(['success' => false, 'message' => 'Invalid credit note ID.'], 404);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $creditNote = CreditNote::findOrFail($creditNoteId);

            if ($creditNote->status !== 'issued') {
                return response()->json(['success' => false, 'message' => 'Only issued credit notes can be applied.'], 422);
            }

            if ($request->amount > $creditNote->remaining_amount) {
                return response()->json(['success' => false, 'message' => 'Cannot apply more than remaining amount.'], 422);
            }

            // Apply credit note
            $creditNote->applyCreditNote($request->amount, $request->description);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Credit note applied successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to apply credit note: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get invoices for a customer
     */
    public function getCustomerInvoices($customerId)
    {
        $this->authorize('create credit notes');

        $user = Auth::user();
        
        $invoices = SalesInvoice::where('customer_id', $customerId)
            ->where('company_id', $user->company_id)
            ->where('status', '!=', 'cancelled')
            ->select('id', 'invoice_number', 'invoice_date', 'total_amount', 'status', 'branch_id')
            ->with('branch')
            ->orderBy('invoice_date', 'desc')
            ->get();
        
        return response()->json($invoices);
    }

    /**
     * Get invoice details including default warehouse
     */
    public function getInvoiceDetails($invoiceId)
    {
        $this->authorize('create credit notes');

        $invoice = SalesInvoice::with('branch')->findOrFail($invoiceId);
        
        // Get the default warehouse for this invoice's branch
        $defaultWarehouse = null;
        if ($invoice->branch) {
            // Since there's no is_default column, we'll use the first active warehouse for this branch
            // You can modify this logic based on your business rules (e.g., use a specific warehouse name)
            $defaultWarehouse = \App\Models\InventoryLocation::where('branch_id', $invoice->branch_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->first();
        }
        
        return response()->json([
            'invoice' => $invoice,
            'default_warehouse' => $defaultWarehouse,
            'discount_amount' => $invoice->discount_amount ?? 0,
            'subtotal' => $invoice->subtotal ?? 0,
            'vat_amount' => $invoice->vat_amount ?? 0,
            'total_amount' => $invoice->total_amount ?? 0,
            'vat_type' => $invoice->vat_type ?? 'inclusive',
            'vat_rate' => $invoice->vat_rate ?? 18.00
        ]);
    }

    /**
     * Get invoice items for credit note creation
     */
    public function getInvoiceItems($invoiceId)
    {
        $this->authorize('create credit notes');

        $invoice = SalesInvoice::with(['items.inventoryItem'])->findOrFail($invoiceId);
        
        return response()->json($invoice->items->map(function ($item) {
            return [
                'id' => $item->id,
                'inventory_item_id' => $item->inventory_item_id,
                'item_name' => $item->item_name,
                'item_code' => $item->item_code,
                'description' => $item->description,
                'unit_of_measure' => $item->unit_of_measure,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
                'vat_type' => $item->vat_type,
                'vat_rate' => $item->vat_rate,
                'vat_amount' => $item->vat_amount,
                'discount_type' => $item->discount_type,
                'discount_rate' => $item->discount_rate,
                'discount_amount' => $item->discount_amount,
                'available_stock' => $item->inventoryItem ? (new \App\Services\InventoryStockService())->getItemStockAtLocation($item->inventoryItem->id, session('location_id')) : 0,
            ];
        }));
    }

    /**
     * Get inventory item details
     */
    public function getInventoryItem($id)
    {
        $this->authorize('create credit notes');

        $item = InventoryItem::findOrFail($id);
        $branchId = session('branch_id') ?? (auth()->user()->branch_id ?? null);
        $locationId = session('location_id');

        return response()->json([
            'id' => $item->id,
            'name' => $item->name,
            'code' => $item->code,
            'description' => $item->description,
            'unit_of_measure' => $item->unit_of_measure,
            'current_stock' => (new \App\Services\InventoryStockService())->getItemStockAtLocation($item->id, session('location_id')),
            'average_cost' => $item->average_cost ?? $item->getCostPriceForBranchOrLocation($branchId, $locationId),
            'sales_price' => $item->getUnitPriceForBranchOrLocation($branchId, $locationId),
            'unit_price' => $item->getUnitPriceForBranchOrLocation($branchId, $locationId),
        ]);
    }

    /**
     * Test debug method
     */
    public function testDebug(Request $request)
    {
        \Log::info('Test Debug Method Called');
        \Log::info('Request Data:', $request->all());
        
        // Test if we can create a simple credit note
        try {
            $user = Auth::user();
            
            $creditNote = CreditNote::create([
                'sales_invoice_id' => 1,
                'customer_id' => 1,
                'credit_note_date' => now(),
                'type' => 'return',
                'reason' => 'Test credit note',
                'vat_rate' => 0,
                'vat_type' => 'no_vat',
                'branch_id' => $user->branch_id,
                'company_id' => $user->company_id,
                'created_by' => $user->id,
                'status' => 'draft',
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Test credit note created successfully',
                'credit_note_id' => $creditNote->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply credit note to invoice
     */
    public function applyToInvoice(Request $request, $encodedId)
    {
        $this->authorize('apply credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            abort(404);
        }

        $request->validate([
            'invoice_id' => 'required|exists:sales_invoices,id',
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        try {
            $creditNote = CreditNote::findOrFail($creditNoteId);
            $invoice = SalesInvoice::findOrFail($request->invoice_id);

            $application = $this->creditNoteService->applyToInvoice(
                $creditNote, 
                $invoice, 
                $request->amount
            );

            return response()->json([
                'success' => true, 
                'message' => 'Credit note applied successfully.',
                'application' => $application
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to apply credit note: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process refund for credit note
     */
    public function processRefund(Request $request, $encodedId)
    {
        $this->authorize('refund credit notes');

        $creditNoteId = Hashids::decode($encodedId)[0] ?? null;
        if (!$creditNoteId) {
            abort(404);
        }

        $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payment_method' => 'nullable|string',
            'reference_number' => 'nullable|string',
        ]);

        try {
            $creditNote = CreditNote::findOrFail($creditNoteId);

            $application = $this->creditNoteService->processRefund($creditNote, $request->all());

            return response()->json([
                'success' => true, 
                'message' => 'Refund processed successfully.',
                'application' => $application
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to process refund: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available invoices for credit note application
     */
    public function getAvailableInvoices($customerId)
    {
        $this->authorize('view credit notes');

        try {
            $customer = Customer::findOrFail($customerId);
            $invoices = $this->creditNoteService->getAvailableInvoices($customer);

            return response()->json([
                'success' => true,
                'invoices' => $invoices
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get credit note statistics
     */
    public function getStatistics()
    {
        $this->authorize('view credit notes');

        try {
            $user = Auth::user();
            $stats = $this->creditNoteService->getStatistics($user->company_id, $user->branch_id);

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
