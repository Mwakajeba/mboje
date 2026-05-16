<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\CashPurchase;
use App\Models\Purchase\CashPurchaseItem;
use App\Models\Purchase\SupplierAdvanceDeduction;
use App\Models\Supplier;
use App\Models\Inventory\Item as InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\SystemSetting;
use App\Services\FxTransactionRateService;
use App\Services\Purchase\SupplierAdvanceAllocationService;
use App\Models\GlTransaction;
use App\Models\Inventory\Movement as InventoryMovement;
use Barryvdh\DomPDF\Facade\Pdf;

class CashPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $baseQuery = CashPurchase::where('company_id', $user->company_id)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });

        $totalCashPurchases = (clone $baseQuery)->count();
        $totalAmount = (clone $baseQuery)->sum('total_amount');
        $todayPurchases = (clone $baseQuery)->whereDate('purchase_date', today())->count();
        $monthPurchases = (clone $baseQuery)
            ->whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->count();

        if ($request->ajax()) {
            $rows = CashPurchase::with(['supplier'])
                ->where('company_id', $user->company_id)
                ->when($branchId, function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->select(['id', 'purchase_date', 'supplier_id', 'total_amount', 'supplier_advance_applied_amount']);

            return datatables($rows)
                ->addColumn('supplier_name', fn ($p) => $p->supplier->name ?? 'N/A')
                ->addColumn('purchase_date_formatted', fn ($p) => format_date($p->purchase_date, 'Y-m-d'))
                ->addColumn('total_amount_formatted', fn ($p) => 'TZS '.number_format((float) $p->total_amount, 2))
                ->addColumn('settlement_method', fn () => 'Supplier advance')
                ->addColumn('actions', function ($p) {
                    $id = Hashids::encode($p->id);

                    return '<div class="btn-group">'
                        .'<a href="'.route('purchases.cash-purchases.show', $id).'" class="btn btn-sm btn-info"><i class="bx bx-show"></i></a> '
                        .'<a href="'.route('purchases.cash-purchases.edit', $id).'" class="btn btn-sm btn-primary"><i class="bx bx-edit"></i></a> '
                        .'<button type="button" class="btn btn-sm btn-danger" onclick="deleteCashPurchase(\''.$id.'\')"><i class="bx bx-trash"></i></button>'
                        .'</div>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('purchases.cash-purchases.index', compact(
            'totalCashPurchases',
            'totalAmount',
            'todayPurchases',
            'monthPurchases'
        ));
    }

    public function create()
    {
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $items = InventoryItem::queryVisibleForSession()->orderBy('name')->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);

        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        $allocationService = app(SupplierAdvanceAllocationService::class);
        $supplierAdvanceBalances = [];
        foreach ($suppliers as $s) {
            $supplierAdvanceBalances[$s->id] = round($allocationService->balanceForSupplier((int) $s->id, (int) $user->company_id, $branchId), 2);
        }

        return view('purchases.cash-purchases.create', compact('suppliers', 'items', 'supplierAdvanceBalances'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'terms_conditions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:no_vat,inclusive,exclusive',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $resolvedBranchId = Auth::user()->branch_id
                ?? (session('branch_id') ?: null)
                ?? (function_exists('current_branch_id') ? current_branch_id() : null);
            if (! $resolvedBranchId) {
                throw new \RuntimeException('Active branch is not set. Please select a branch and try again.');
            }

            $functionalCurrency = SystemSetting::getValue('functional_currency', Auth::user()->company->functional_currency ?? 'TZS');
            $purchaseCurrency = $request->currency ?? $functionalCurrency;
            $companyId = Auth::user()->company_id;

            $fxTransactionRateService = app(FxTransactionRateService::class);
            $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
            $rateResult = $fxTransactionRateService->getTransactionRate(
                $purchaseCurrency,
                $functionalCurrency,
                $request->purchase_date,
                $companyId,
                $userProvidedRate
            );
            $exchangeRate = $rateResult['rate'];

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time().'_'.\Illuminate\Support\Str::random(10).'.'.$file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('cash-purchase-attachments', $fileName, 'public');
            }

            $purchase = CashPurchase::create([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'payment_method' => 'cash',
                'bank_account_id' => null,
                'currency' => $purchaseCurrency,
                'exchange_rate' => $exchangeRate,
                'fx_rate_used' => $exchangeRate,
                'discount_amount' => $request->discount_amount ?? 0,
                'supplier_advance_applied_amount' => 0,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'attachment' => $attachmentPath,
                'branch_id' => $resolvedBranchId,
                'company_id' => $companyId,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $line) {
                $inventoryItemId = $line['inventory_item_id'] ?? null;
                if (! $inventoryItemId) {
                    throw new \RuntimeException('Inventory item is required.');
                }
                $item = InventoryItem::queryVisibleForSession()->findOrFail($inventoryItemId);
                $row = new CashPurchaseItem([
                    'inventory_item_id' => $inventoryItemId,
                    'description' => $item->description,
                    'unit_of_measure' => $item->unit_of_measure,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'vat_type' => $line['vat_type'],
                    'vat_rate' => $line['vat_rate'] ?? 0,
                ]);
                $row->calculateLine();
                $purchase->items()->save($row);
            }

            $purchase->updateTotals();
            $purchase->supplier_advance_applied_amount = (float) $purchase->total_amount;
            $purchase->save();

            $this->assertSupplierAdvanceCoversPurchase($purchase, $purchaseCurrency, $functionalCurrency, $exchangeRate, $companyId, $resolvedBranchId);

            $purchase->updateInventory();
            $purchase->createDoubleEntryTransactions();

            DB::commit();

            return redirect()->route('purchases.cash-purchases.show', $purchase->encoded_id)
                ->with('success', 'Cash purchase recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Failed to save: '.$e->getMessage()]);
        }
    }

    public function show(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        $purchase = CashPurchase::with(['supplier', 'items.inventoryItem', 'journal.items.chartAccount'])->findOrFail($id);

        return view('purchases.cash-purchases.show', compact('purchase'));
    }

    public function edit(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        $purchase = CashPurchase::with(['items.inventoryItem'])->findOrFail($id);
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $items = InventoryItem::queryVisibleForSession()->orderBy('name')->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);

        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        $allocationService = app(SupplierAdvanceAllocationService::class);
        $functionalCurrency = SystemSetting::getValue('functional_currency', $user->company->functional_currency ?? 'TZS');
        $supplierAdvanceBalances = [];
        foreach ($suppliers as $s) {
            $b = round($allocationService->balanceForSupplier((int) $s->id, (int) $user->company_id, $branchId), 2);
            if ((int) $s->id === (int) $purchase->supplier_id) {
                $pc = $purchase->currency ?? $functionalCurrency;
                $ex = (float) ($purchase->exchange_rate ?? 1.0);
                $applyFcy = (float) ($purchase->supplier_advance_applied_amount ?? 0);
                $applyLcy = ($pc !== $functionalCurrency && $ex != 1.0)
                    ? round($applyFcy * $ex, 2)
                    : $applyFcy;
                $b = round($b + $applyLcy, 2);
            }
            $supplierAdvanceBalances[$s->id] = $b;
        }

        return view('purchases.cash-purchases.edit', compact('purchase', 'suppliers', 'items', 'supplierAdvanceBalances'));
    }

    public function update(Request $request, string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        $purchase = CashPurchase::findOrFail($id);

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'terms_conditions' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:no_vat,inclusive,exclusive',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.batch_number' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $functionalCurrency = SystemSetting::getValue('functional_currency', Auth::user()->company->functional_currency ?? 'TZS');
            $priorSupplierId = (int) $purchase->supplier_id;
            $previousApplyFcy = (float) ($purchase->supplier_advance_applied_amount ?? 0);
            $pcBefore = $purchase->currency ?? $functionalCurrency;
            $exBefore = (float) ($purchase->exchange_rate ?? 1.0);
            $previousApplyLcy = ($pcBefore !== $functionalCurrency && $exBefore != 1.0)
                ? round($previousApplyFcy * $exBefore, 2)
                : $previousApplyFcy;

            $updateData = [
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'payment_method' => 'cash',
                'bank_account_id' => null,
                'discount_amount' => $request->discount_amount ?? 0,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'updated_by' => Auth::id(),
            ];

            if ($request->hasFile('attachment')) {
                if ($purchase->attachment && \Storage::disk('public')->exists($purchase->attachment)) {
                    \Storage::disk('public')->delete($purchase->attachment);
                }
                $file = $request->file('attachment');
                $fileName = time().'_'.\Illuminate\Support\Str::random(10).'.'.$file->getClientOriginalExtension();
                $updateData['attachment'] = $file->storeAs('cash-purchase-attachments', $fileName, 'public');
            }

            $purchase->update($updateData);
            $purchase->items()->delete();

            foreach ($request->items as $line) {
                $inventoryItemId = $line['inventory_item_id'] ?? null;
                if (! $inventoryItemId) {
                    throw new \RuntimeException('Inventory item is required.');
                }
                $item = InventoryItem::queryVisibleForSession()->findOrFail($inventoryItemId);
                $row = new CashPurchaseItem([
                    'inventory_item_id' => $inventoryItemId,
                    'description' => $item->description,
                    'unit_of_measure' => $item->unit_of_measure,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'vat_type' => $line['vat_type'],
                    'vat_rate' => $line['vat_rate'] ?? 0,
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'batch_number' => $line['batch_number'] ?? null,
                ]);
                $row->calculateLine();
                $purchase->items()->save($row);
            }

            $purchase->updateTotals();
            $purchase->supplier_advance_applied_amount = (float) $purchase->total_amount;
            $purchase->save();

            $purchaseCurrency = $purchase->currency ?? $functionalCurrency;
            $exchangeRate = (float) ($purchase->exchange_rate ?? 1.0);
            $applyLcy = ($purchaseCurrency !== $functionalCurrency && $exchangeRate != 1.0)
                ? round((float) $purchase->total_amount * $exchangeRate, 2)
                : (float) $purchase->total_amount;

            $allocationService = app(SupplierAdvanceAllocationService::class);
            $advBal = $allocationService->balanceForSupplier((int) $purchase->supplier_id, (int) $purchase->company_id, (int) $purchase->branch_id);
            $releaseLcy = ((int) $purchase->supplier_id === $priorSupplierId) ? $previousApplyLcy : 0.0;
            $maxApplyLcy = $advBal + $releaseLcy;
            if ($applyLcy - $maxApplyLcy > 0.05) {
                throw new \RuntimeException('Supplier advance balance is insufficient ('.number_format($maxApplyLcy, 2).' available in functional currency).');
            }

            $purchase->updateInventory();
            $purchase->createDoubleEntryTransactions();

            DB::commit();

            return redirect()->route('purchases.cash-purchases.show', $purchase->encoded_id)
                ->with('success', 'Cash purchase updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Failed to update: '.$e->getMessage()]);
        }
    }

    public function destroy(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        DB::beginTransaction();
        try {
            $purchase = CashPurchase::with('items.inventoryItem')->findOrFail($id);

            InventoryMovement::where('reference_type', 'cash_purchase')
                ->where('reference_id', $purchase->id)
                ->delete();

            SupplierAdvanceDeduction::where('source_type', 'cash_purchase')
                ->where('source_id', $purchase->id)
                ->delete();

            $purchase->removeJournalAndGl();

            $payments = Payment::where('reference_type', 'cash_purchase')
                ->where('reference', (string) $purchase->id)
                ->get();
            foreach ($payments as $p) {
                GlTransaction::where('transaction_type', 'payment')->where('transaction_id', $p->id)->delete();
                PaymentItem::where('payment_id', $p->id)->delete();
                $p->delete();
            }

            $purchase->items()->delete();
            $purchase->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Cash purchase deleted']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportPdf(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        $purchase = CashPurchase::with(['supplier', 'items.inventoryItem', 'company', 'branch', 'createdBy', 'journal'])->findOrFail($id);

        $company = $purchase->company ?? $purchase->branch->company ?? auth()->user()->company ?? null;

        $html = view('purchases.cash-purchases.print', compact('purchase', 'company'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');

        $supplierName = $purchase->supplier ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $purchase->supplier->name) : 'Unknown';
        $filename = 'CashPurchase_for_'.$supplierName.'_'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    private function assertSupplierAdvanceCoversPurchase(
        CashPurchase $purchase,
        string $purchaseCurrency,
        string $functionalCurrency,
        float $exchangeRate,
        int $companyId,
        int $branchId
    ): void {
        $applyFcy = (float) $purchase->total_amount;
        $applyLcy = ($purchaseCurrency !== $functionalCurrency && $exchangeRate != 1.0)
            ? round($applyFcy * $exchangeRate, 2)
            : $applyFcy;

        $allocationService = app(SupplierAdvanceAllocationService::class);
        $advBal = $allocationService->balanceForSupplier((int) $purchase->supplier_id, $companyId, $branchId);
        if ($applyLcy - $advBal > 0.05) {
            throw new \RuntimeException('Supplier advance balance is insufficient. Available: '.number_format($advBal, 2).' '.$functionalCurrency.'. Purchase total requires: '.number_format($applyLcy, 2).'.');
        }
    }
}
