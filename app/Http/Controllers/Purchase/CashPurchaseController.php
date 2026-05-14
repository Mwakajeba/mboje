<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\CashPurchase;
use App\Models\Purchase\CashPurchaseItem;
use App\Models\Supplier;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\SystemSetting;
use App\Services\FxTransactionRateService;
use App\Models\GlTransaction;
use App\Models\Inventory\Movement as InventoryMovement;
use Barryvdh\DomPDF\Facade\Pdf;

class CashPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;
        
        // Base query for stats
        $baseQuery = CashPurchase::where('company_id', $user->company_id)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        
        // Calculate stats
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
                // Scope to current branch (session branch takes precedence over user branch)
                ->when($branchId, function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->select(['id','purchase_date','supplier_id','payment_method','total_amount']);

            return datatables($rows)
                ->addColumn('supplier_name', fn($p)=>$p->supplier->name ?? 'N/A')
                ->addColumn('purchase_date_formatted', fn($p)=>format_date($p->purchase_date, 'Y-m-d'))
                ->addColumn('total_amount_formatted', fn($p)=>'TZS ' . number_format((float)$p->total_amount,2))
                ->addColumn('actions', function($p){
                    $id = Hashids::encode($p->id);
                    return '<div class="btn-group">'
                        .'<a href="'.route('purchases.cash-purchases.show',$id).'" class="btn btn-sm btn-info"><i class="bx bx-show"></i></a> '
                        .'<a href="'.route('purchases.cash-purchases.edit',$id).'" class="btn btn-sm btn-primary"><i class="bx bx-edit"></i></a> '
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
        $items = InventoryItem::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);
        // Limit bank accounts by branch scope (all branches or current branch)
        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $bankAccounts = BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();
        return view('purchases.cash-purchases.create', compact('suppliers','items','bankAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'payment_method' => 'required|in:bank',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.vat_type' => 'required|in:no_vat,inclusive,exclusive',
            'items.*.vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Determine branch id reliably: prefer user->branch_id, then session, then helper
            $resolvedBranchId = Auth::user()->branch_id
                ?? (session('branch_id') ?: null)
                ?? (function_exists('current_branch_id') ? current_branch_id() : null);
            if (!$resolvedBranchId) {
                throw new \RuntimeException('Active branch is not set. Please select a branch and try again.');
            }
            
            // Get functional currency
            $functionalCurrency = SystemSetting::getValue('functional_currency', Auth::user()->company->functional_currency ?? 'TZS');
            $purchaseCurrency = $request->currency ?? $functionalCurrency;
            $companyId = Auth::user()->company_id;

            // Get exchange rate using FxTransactionRateService
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
            $fxRateUsed = $exchangeRate; // Store the rate used for fx_rate_used field

            // Handle attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('cash-purchase-attachments', $fileName, 'public');
            }
            
            $purchase = CashPurchase::create([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'payment_method' => $request->payment_method,
                'bank_account_id' => $request->bank_account_id,
                'currency' => $purchaseCurrency,
                'exchange_rate' => $exchangeRate,
                'fx_rate_used' => $fxRateUsed,
                'discount_amount' => $request->discount_amount ?? 0,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'attachment' => $attachmentPath,
                'branch_id' => $resolvedBranchId,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $line) {
                $inventoryItemId = $line['inventory_item_id'] ?? null;

                if (!$inventoryItemId) {
                    throw new \RuntimeException('Inventory item is required.');
                }

                $item = InventoryItem::findOrFail($inventoryItemId);

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
            $purchase->updateInventory();
            $purchase->createDoubleEntryTransactions();

            // Create payment record for this cash purchase
            $payment = Payment::create([
                'reference' => (string) $purchase->id,
                'reference_type' => 'cash_purchase',
                'amount' => (float) $purchase->total_amount,
                'date' => $purchase->purchase_date,
                'description' => 'Cash purchase payment',
                'bank_account_id' => $purchase->payment_method === 'bank' ? $purchase->bank_account_id : null,
                'payee_type' => 'supplier',
                'payee_id' => $purchase->supplier_id,
                'supplier_id' => $purchase->supplier_id,
                'branch_id' => $purchase->branch_id,
                'user_id' => Auth::id(),
                'approved' => true,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Create payment item with cash/bank account
            $paymentAccountId = $purchase->payment_method === 'bank' && $purchase->bankAccount
                ? (int) $purchase->bankAccount->chart_account_id
                : (int) (SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 1);
            PaymentItem::create([
                'payment_id' => $payment->id,
                'chart_account_id' => $paymentAccountId,
                'amount' => (float) $purchase->total_amount,
                'description' => 'Cash purchase payment',
            ]);

            DB::commit();
            return redirect()->route('purchases.cash-purchases.show', $purchase->encoded_id)
                ->with('success','Cash purchase recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to save: '.$e->getMessage()]);
        }
    }

    public function show(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        $purchase = CashPurchase::with(['supplier','items.inventoryItem','bankAccount'])->findOrFail($id);
        return view('purchases.cash-purchases.show', compact('purchase'));
    }

    public function edit(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        $purchase = CashPurchase::with(['items.inventoryItem'])->findOrFail($id);
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $items = InventoryItem::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        \App\Models\Inventory\Item::withResolvedPricesForContext($items);
        // Limit bank accounts by branch scope (all branches or current branch)
        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        $bankAccounts = BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();
        return view('purchases.cash-purchases.edit', compact('purchase','suppliers','items','bankAccounts'));
    }

    public function update(Request $request, string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        $purchase = CashPurchase::findOrFail($id);

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'payment_method' => 'required|in:bank',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
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
            $updateData = [
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'payment_method' => $request->payment_method,
                'bank_account_id' => $request->bank_account_id,
                'discount_amount' => $request->discount_amount ?? 0,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'updated_by' => Auth::id(),
            ];

            // Handle attachment upload
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($purchase->attachment && \Storage::disk('public')->exists($purchase->attachment)) {
                    \Storage::disk('public')->delete($purchase->attachment);
                }
                $file = $request->file('attachment');
                $fileName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $updateData['attachment'] = $file->storeAs('cash-purchase-attachments', $fileName, 'public');
            }

            $purchase->update($updateData);

            $purchase->items()->delete();
            foreach ($request->items as $line) {
                $inventoryItemId = $line['inventory_item_id'] ?? null;

                if (!$inventoryItemId) {
                    throw new \RuntimeException('Inventory item is required.');
                }

                $item = InventoryItem::findOrFail($inventoryItemId);

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
            $purchase->updateInventory();
            $purchase->createDoubleEntryTransactions();

            // Upsert payment record for this cash purchase
            $payment = Payment::updateOrCreate(
                [
                    'reference' => (string) $purchase->id,
                    'reference_type' => 'cash_purchase',
                ],
                [
                    'amount' => (float) $purchase->total_amount,
                    'date' => $purchase->purchase_date,
                    'description' => 'Cash purchase payment',
                    'bank_account_id' => $purchase->payment_method === 'bank' ? $purchase->bank_account_id : null,
                    'payee_type' => 'supplier',
                    'payee_id' => $purchase->supplier_id,
                    'supplier_id' => $purchase->supplier_id,
                    'branch_id' => $purchase->branch_id,
                    'user_id' => Auth::id(),
                    'approved' => true,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]
            );

            // Upsert payment item
            $paymentAccountId = $purchase->payment_method === 'bank' && $purchase->bankAccount
                ? (int) $purchase->bankAccount->chart_account_id
                : (int) (SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 1);
            PaymentItem::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'chart_account_id' => $paymentAccountId,
                    'amount' => (float) $purchase->total_amount,
                    'description' => 'Cash purchase payment',
                ]
            );

            DB::commit();
            return redirect()->route('purchases.cash-purchases.show', $purchase->encoded_id)
                ->with('success','Cash purchase updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error'=>'Failed to update: '.$e->getMessage()]);
        }
    }

    public function destroy(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        DB::beginTransaction();
        try {
            $purchase = CashPurchase::with('items.inventoryItem')->findOrFail($id);
            
            // delete inventory movements for this purchase (this handles stock reversal)
            InventoryMovement::where('reference_type', 'cash_purchase')
                ->where('reference_id', $purchase->id)
                ->delete();

            // delete GL transactions for this purchase
            GlTransaction::where('transaction_type', 'cash_purchase')
                ->where('transaction_id', $purchase->id)
                ->delete();

            // delete related payments and their items
            $payments = Payment::where('reference_type', 'cash_purchase')
                ->where('reference', (string) $purchase->id)
                ->get();
            foreach ($payments as $p) {
                PaymentItem::where('payment_id', $p->id)->delete();
                $p->delete();
            }

            $purchase->items()->delete();
            $purchase->delete();
            DB::commit();
            return response()->json(['success'=>true,'message'=>'Cash purchase deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    public function exportPdf(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_unless($id, 404);
        $purchase = CashPurchase::with(['supplier','items.inventoryItem','bankAccount','company','branch','createdBy'])->findOrFail($id);
        
        $company = $purchase->company ?? $purchase->branch->company ?? auth()->user()->company ?? null;
        
        // Get bank accounts for payment methods
        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($purchase) {
            $companyId = $purchase->company_id ?? $purchase->branch->company_id ?? auth()->user()->company_id;
            $q->where('company_id', $companyId);
        })->orderBy('name')->get();
        
        $html = view('purchases.cash-purchases.print', compact('purchase', 'company', 'bankAccounts'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        
        // Generate filename with supplier name
        $supplierName = $purchase->supplier ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $purchase->supplier->name) : 'Unknown';
        $filename = 'CashPurchase_for_' . $supplierName . '_' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
}
