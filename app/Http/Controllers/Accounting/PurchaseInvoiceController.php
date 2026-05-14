<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceItem;
use App\Models\Purchase\GoodsReceipt;
use App\Models\Supplier;
use App\Models\Inventory\Item as InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        $invoices = PurchaseInvoice::with(['supplier'])->orderByDesc('id')->paginate(20);
        return view('purchases.purchase-invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $items = InventoryItem::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $prefill = null;
        if ($request->filled('grn_id')) {
            $grn = GoodsReceipt::with(['items.inventoryItem', 'purchaseOrder.supplier'])->find($request->grn_id);
            $prefill = $grn;
        }
        return view('purchases.purchase-invoices.create', compact('suppliers','items','prefill'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:100|unique:purchase_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:no_vat,inclusive,exclusive',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $invoice = PurchaseInvoice::create([
                'supplier_id' => $request->supplier_id,
                'invoice_number' => $request->invoice_number,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'currency' => $request->currency ?? 'TZS',
                'exchange_rate' => $request->exchange_rate ?? 1.000000,
                'notes' => $request->notes,
                'company_id' => Auth::user()->company_id,
                'branch_id' => Auth::user()->branch_id,
                'created_by' => Auth::id(),
            ]);

            $subtotal = 0; $vatAmount = 0; $discountAmount = 0; $total = 0;
            foreach ($request->items as $line) {
                $qty = (float) $line['quantity'];
                $unit = (float) $line['unit_cost'];
                $base = $qty * $unit;
                $vat = 0;
                $vatType = $line['vat_type'];
                $rate = (float) ($line['vat_rate'] ?? 0);
                if ($vatType === 'inclusive' && $rate > 0) {
                    $vat = $base * ($rate / (100 + $rate));
                } elseif ($vatType === 'exclusive' && $rate > 0) {
                    $vat = $base * ($rate / 100);
                }
                $lineTotal = $vatType === 'exclusive' ? $base + $vat : $base;

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'inventory_item_id' => $line['inventory_item_id'] ?? null,
                    'grn_item_id' => $line['grn_item_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'quantity' => $qty,
                    'unit_cost' => $unit,
                    'vat_type' => $vatType,
                    'vat_rate' => $rate,
                    'vat_amount' => $vat,
                    'line_total' => $lineTotal,
                ]);

                // Totals
                $subtotal += $base;
                $vatAmount += $vat;
                $total += $lineTotal;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'discount_amount' => 0,
                'total_amount' => $total,
                'status' => 'open',
            ]);

            // Double entry
            $invoice->postGlTransactions();

            DB::commit();
            return redirect()->route('purchases.purchase-invoices.show', $invoice->id)->with('success', 'Purchase invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create invoice: ' . $e->getMessage()]);
        }
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['supplier','items.inventoryItem']);
        return view('purchases.purchase-invoices.show', ['invoice' => $purchaseInvoice]);
    }
}


