<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Supplier;
use App\Models\ChartAccount;
use App\Models\GlTransaction;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Company;
use App\Helpers\HashIdHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BillPurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;

        if ($companyId) {
            $bills = Bill::with(['supplier', 'creditAccount', 'user', 'branch'])
                ->where('company_id', $companyId)
                ->orderBy('date', 'desc')
                ->get();
        } else {
            $bills = Bill::with(['supplier', 'creditAccount', 'user', 'branch'])
                ->orderBy('date', 'desc')
                ->get();
        }

        $stats = [
            'total' => $bills->count(),
            'paid' => $bills->where('status', 'paid')->count(),
            'pending' => $bills->where('status', 'pending')->count(),
            'overdue' => $bills->where('status', 'overdue')->count(),
        ];

        return view('accounting.bill-purchases.index', compact('bills', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;

        $suppliers = Supplier::where('status', 'active')
            ->where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q)=>$q->where('branch_id', Auth::user()->branch_id))
            ->orderBy('name')->get();
        $chartAccounts = ChartAccount::orderBy('account_name')->get();

        return view('accounting.bill-purchases.create', compact('suppliers', 'chartAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'supplier_id' => 'required|exists:suppliers,id',
            'note' => 'nullable|string|max:1000',
            'credit_account' => 'required|exists:chart_accounts,id',
            'line_items' => 'required|array|min:1',
            'line_items.*.debit_account' => 'required|exists:chart_accounts,id',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $companyId = $user->company_id ?? Company::first()->id ?? 1;
            $branchId = session('branch_id') ?? ($user->branch_id ?? null);
            
            if (!$branchId) {
                throw new \Exception('Branch ID is required but not found. Please ensure you are assigned to a branch.');
            }

            // Calculate total amount
            $totalAmount = collect($request->line_items)->sum('amount');

            // Generate reference
            $reference = 'BILL-' . date('Ymd') . '-' . str_pad(Bill::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Get VAT mode and rate
            $vatMode = $request->vat_mode ?? 'NONE';
            $vatRate = (float) ($request->vat_rate ?? get_default_vat_rate());

            // Create bill
            $bill = Bill::create([
                'reference' => $reference,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'supplier_id' => $request->supplier_id,
                'note' => $request->note,
                'credit_account' => $request->credit_account,
                'total_amount' => $totalAmount,
                'vat_mode' => $vatMode,
                'vat_rate' => $vatRate,
                'paid' => 0,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'company_id' => $companyId,
            ]);

            // Create bill items
            $billItems = [];
            foreach ($request->line_items as $lineItem) {
                $billItems[] = [
                    'bill_id' => $bill->id,
                    'debit_account' => $lineItem['debit_account'],
                    'amount' => $lineItem['amount'],
                    'description' => $lineItem['description'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            BillItem::insert($billItems);

            // Create GL transactions with VAT handling
            // Get VAT Input account from system settings
            $vatInputAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36);
            if (!$vatInputAccountId) {
                // Fallback: try to find VAT Input account by name
                $vatAccount = \App\Models\ChartAccount::where('account_name', 'like', '%VAT%Account%')
                    ->orWhere('account_name', 'like', '%VAT Account%')
                    ->orWhere('account_name', 'like', '%VAT Input%')
                    ->first();
                $vatInputAccountId = $vatAccount ? $vatAccount->id : 0;
            }

            // Calculate VAT amounts based on VAT mode
            $totalVAT = 0;
            $totalBase = 0;
            $lineItemBases = [];

            if ($vatMode !== 'NONE' && $vatRate > 0) {
                foreach ($request->line_items as $index => $lineItem) {
                    $itemAmount = (float) $lineItem['amount'];
                    
                    if ($vatMode === 'INCLUSIVE') {
                        // VAT is included in the amount
                        $itemBase = round($itemAmount / (1 + ($vatRate / 100)), 2);
                        $itemVAT = round($itemAmount - $itemBase, 2);
                    } else {
                        // EXCLUSIVE: VAT is separate
                        $itemBase = $itemAmount;
                        $itemVAT = round($itemAmount * ($vatRate / 100), 2);
                    }
                    
                    $lineItemBases[$index] = $itemBase;
                    $totalBase += $itemBase;
                    $totalVAT += $itemVAT;
                }
            } else {
                // No VAT: amounts are base amounts
                foreach ($request->line_items as $index => $lineItem) {
                    $lineItemBases[$index] = (float) $lineItem['amount'];
                    $totalBase += (float) $lineItem['amount'];
                }
            }

            // Check if credit account (accounts payable) is a bank account in a completed reconciliation period
            $isInCompletedReconciliation = \App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation(
                $request->credit_account,
                $request->date
            );
            
            if ($isInCompletedReconciliation) {
                \Log::warning('BillPurchaseController - Cannot post: Credit account is in a completed reconciliation period', [
                    'bill_id' => $bill->id,
                    'bill_reference' => $bill->reference,
                    'chart_account_id' => $request->credit_account,
                    'transaction_date' => $request->date
                ]);
                throw new \Exception("Cannot post bill: Credit account is in a completed reconciliation period for date {$request->date}.");
            }

            // Debit each expense account (base amount only)
            foreach ($request->line_items as $index => $lineItem) {
                $baseAmount = $lineItemBases[$index] ?? (float) $lineItem['amount'];
                GlTransaction::create([
                    'chart_account_id' => $lineItem['debit_account'],
                    'supplier_id' => $request->supplier_id,
                    'amount' => $baseAmount,
                    'nature' => 'debit',
                    'transaction_id' => $bill->id,
                    'transaction_type' => 'bill',
                    'date' => $request->date,
                    'description' => $lineItem['description'] ?: "Bill purchase {$bill->reference}",
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);
            }

            // Debit VAT Input account (if VAT exists)
            if ($vatInputAccountId > 0 && $totalVAT > 0 && $vatMode !== 'NONE') {
                GlTransaction::create([
                    'chart_account_id' => $vatInputAccountId,
                    'supplier_id' => $request->supplier_id,
                    'amount' => round($totalVAT, 2),
                    'nature' => 'debit',
                    'transaction_id' => $bill->id,
                    'transaction_type' => 'bill',
                    'date' => $request->date,
                    'description' => "VAT Input - Bill purchase {$bill->reference}",
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);
            }

            // Credit the accounts payable (credit_account) - total amount (base + VAT)
            GlTransaction::create([
                'chart_account_id' => $request->credit_account,
                'supplier_id' => $request->supplier_id,
                'amount' => $totalAmount,
                'nature' => 'credit',
                'transaction_id' => $bill->id,
                'transaction_type' => 'bill',
                'date' => $request->date,
                'description' => "Bill purchase {$bill->reference}",
                'branch_id' => $branchId,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return redirect()->route('accounting.bill-purchases.show', $bill)
                ->with('success', 'Bill purchase created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create bill purchase: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Bill $billPurchase)
    {
        // Ensure paid amount is up-to-date before displaying
        // This recalculates based on actual payments in the database
        $billPurchase->updatePaidAmount();
        
        // Reload the bill with all relationships after updating paid amount
        $billPurchase->refresh();
        $billPurchase->load([
            'supplier', 
            'creditAccount', 
            'user', 
            'branch', 
            'company',
            'billItems.debitAccount',
            'glTransactions.chartAccount'
        ]);
        
        // Manually load payments since the relationship doesn't work well with eager loading
        // due to the where clause using $this->reference
        $payments = Payment::where('supplier_id', $billPurchase->supplier_id)
            ->where('reference_type', 'Bill')
            ->where('reference_number', $billPurchase->reference)
            ->with(['bankAccount', 'user'])
            ->get();
        
        // Set payments manually on the model so the view can access them
        $billPurchase->setRelation('payments', $payments);

        return view('accounting.bill-purchases.show', compact('billPurchase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bill $billPurchase)
    {
        $billPurchase->load(['billItems']);

        $suppliers = Supplier::where('status', 'active')
            ->where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q)=>$q->where('branch_id', Auth::user()->branch_id))
            ->orderBy('name')->get();
        $chartAccounts = ChartAccount::orderBy('account_name')->get();

        return view('accounting.bill-purchases.edit', compact('billPurchase', 'suppliers', 'chartAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bill $billPurchase)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'supplier_id' => 'required|exists:suppliers,id',
            'note' => 'nullable|string|max:1000',
            'credit_account' => 'required|exists:chart_accounts,id',
            'vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'line_items' => 'required|array|min:1',
            'line_items.*.debit_account' => 'required|exists:chart_accounts,id',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $branchId = session('branch_id') ?? ($user->branch_id ?? null);
            
            if (!$branchId) {
                throw new \Exception('Branch ID is required but not found. Please ensure you are assigned to a branch.');
            }

            // Delete existing GL transactions
            $billPurchase->glTransactions()->delete();

            // Delete existing bill items
            $billPurchase->billItems()->delete();

            // Calculate total amount
            $totalAmount = collect($request->line_items)->sum('amount');

            // Get VAT mode and rate
            $vatMode = $request->vat_mode ?? 'NONE';
            $vatRate = (float) ($request->vat_rate ?? get_default_vat_rate());

            // Update bill
            $billPurchase->update([
                'date' => $request->date,
                'due_date' => $request->due_date,
                'supplier_id' => $request->supplier_id,
                'note' => $request->note,
                'credit_account' => $request->credit_account,
                'total_amount' => $totalAmount,
                'vat_mode' => $vatMode,
                'vat_rate' => $vatRate,
                'branch_id' => $branchId,
            ]);

            // Create new bill items
            $billItems = [];
            foreach ($request->line_items as $lineItem) {
                $billItems[] = [
                    'bill_id' => $billPurchase->id,
                    'debit_account' => $lineItem['debit_account'],
                    'amount' => $lineItem['amount'],
                    'description' => $lineItem['description'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            BillItem::insert($billItems);

            // Check if credit account (accounts payable) is a bank account in a completed reconciliation period
            $isInCompletedReconciliation = \App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation(
                $request->credit_account,
                $request->date
            );
            
            if ($isInCompletedReconciliation) {
                \Log::warning('BillPurchaseController::update - Cannot post: Credit account is in a completed reconciliation period', [
                    'bill_id' => $billPurchase->id,
                    'bill_reference' => $billPurchase->reference,
                    'chart_account_id' => $request->credit_account,
                    'transaction_date' => $request->date
                ]);
                throw new \Exception("Cannot post bill: Credit account is in a completed reconciliation period for date {$request->date}.");
            }

            // Create new GL transactions with VAT handling
            // Get VAT Input account from system settings
            $vatInputAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36);
            if (!$vatInputAccountId) {
                // Fallback: try to find VAT Input account by name
                $vatAccount = \App\Models\ChartAccount::where('account_name', 'like', '%VAT%Account%')
                    ->orWhere('account_name', 'like', '%VAT Account%')
                    ->orWhere('account_name', 'like', '%VAT Input%')
                    ->first();
                $vatInputAccountId = $vatAccount ? $vatAccount->id : 0;
            }

            // Calculate VAT amounts based on VAT mode
            $totalVAT = 0;
            $totalBase = 0;
            $lineItemBases = [];

            if ($vatMode !== 'NONE' && $vatRate > 0) {
                foreach ($request->line_items as $index => $lineItem) {
                    $itemAmount = (float) $lineItem['amount'];
                    
                    if ($vatMode === 'INCLUSIVE') {
                        // VAT is included in the amount
                        $itemBase = round($itemAmount / (1 + ($vatRate / 100)), 2);
                        $itemVAT = round($itemAmount - $itemBase, 2);
                    } else {
                        // EXCLUSIVE: VAT is separate
                        $itemBase = $itemAmount;
                        $itemVAT = round($itemAmount * ($vatRate / 100), 2);
                    }
                    
                    $lineItemBases[$index] = $itemBase;
                    $totalBase += $itemBase;
                    $totalVAT += $itemVAT;
                }
            } else {
                // No VAT: amounts are base amounts
                foreach ($request->line_items as $index => $lineItem) {
                    $lineItemBases[$index] = (float) $lineItem['amount'];
                    $totalBase += (float) $lineItem['amount'];
                }
            }

            // Debit each expense account (base amount only)
            foreach ($request->line_items as $index => $lineItem) {
                $baseAmount = $lineItemBases[$index] ?? (float) $lineItem['amount'];
                GlTransaction::create([
                    'chart_account_id' => $lineItem['debit_account'],
                    'supplier_id' => $request->supplier_id,
                    'amount' => $baseAmount,
                    'nature' => 'debit',
                    'transaction_id' => $billPurchase->id,
                    'transaction_type' => 'bill',
                    'date' => $request->date,
                    'description' => $lineItem['description'] ?: "Bill purchase {$billPurchase->reference}",
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                ]);
            }

            // Debit VAT Input account (if VAT exists)
            if ($vatInputAccountId > 0 && $totalVAT > 0 && $vatMode !== 'NONE') {
                GlTransaction::create([
                    'chart_account_id' => $vatInputAccountId,
                    'supplier_id' => $request->supplier_id,
                    'amount' => round($totalVAT, 2),
                    'nature' => 'debit',
                    'transaction_id' => $billPurchase->id,
                    'transaction_type' => 'bill',
                    'date' => $request->date,
                    'description' => "VAT Input - Bill purchase {$billPurchase->reference}",
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                ]);
            }

            // Credit the accounts payable (credit_account) - total amount (base + VAT)
            GlTransaction::create([
                'chart_account_id' => $request->credit_account,
                'supplier_id' => $request->supplier_id,
                'amount' => $totalAmount,
                'nature' => 'credit',
                'transaction_id' => $billPurchase->id,
                'transaction_type' => 'bill',
                'date' => $request->date,
                'description' => "Bill purchase {$billPurchase->reference}",
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('accounting.bill-purchases.show', $billPurchase)
                ->with('success', 'Bill purchase updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update bill purchase: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bill $billPurchase)
    {
        try {
            DB::beginTransaction();

            // Delete GL transactions
            $billPurchase->glTransactions()->delete();

            // Delete bill items
            $billPurchase->billItems()->delete();

            // Delete the bill
            $billPurchase->delete();

            DB::commit();

            return redirect()->route('accounting.bill-purchases')
                ->with('success', 'Bill purchase deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete bill purchase: ' . $e->getMessage()]);
        }
    }

    /**
     * Show payment form for bill
     */
    public function showPaymentForm(Bill $billPurchase)
    {
        $billPurchase->load(['supplier', 'creditAccount']);

        // Limit bank accounts by branch scope (all branches or current branch)
        $user = auth()->user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        $bankAccounts = BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();
        $chartAccounts = ChartAccount::orderBy('account_name')->get();

        return view('accounting.bill-purchases.payment', compact('billPurchase', 'bankAccounts', 'chartAccounts'));
    }

    /**
     * Process payment for bill
     */
    public function processPayment(Request $request, Bill $billPurchase)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:' . $billPurchase->balance,
            'date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'description' => 'nullable|string|max:1000',
            'wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,GROSS_UP,NONE',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $branchId = session('branch_id') ?? ($user->branch_id ?? null);
            
            if (!$branchId) {
                throw new \Exception('Branch ID is required but not found. Please ensure you are assigned to a branch.');
            }
            
            $totalAmount = (float) $request->amount;
            
            // Calculate WHT if provided (with VAT integration)
            $whtService = new \App\Services\WithholdingTaxService();
            $whtTreatment = $request->wht_treatment ?? 'EXCLUSIVE';
            $whtRate = (float) ($request->wht_rate ?? 0);
            
            // Get VAT mode and rate from bill (if set) or from request, default to NONE
            $vatMode = $request->vat_mode ?? ($billPurchase->vat_mode ?? 'NONE');
            $vatRate = (float) ($request->vat_rate ?? ($billPurchase->vat_rate ?? get_default_vat_rate()));
            
            // If bill has no VAT, don't apply VAT calculations
            if ($vatMode === 'NONE' || $vatRate <= 0) {
                $vatMode = 'NONE';
                $vatRate = 0;
            }
            
            // Check if supplier has allow_gross_up flag
            $supplier = $billPurchase->supplier;
            if ($supplier && $supplier->allow_gross_up && $whtTreatment === 'EXCLUSIVE' && $whtRate > 0) {
                $whtTreatment = 'GROSS_UP';
            }
            
            $paymentWHT = 0;
            $paymentNetPayable = $totalAmount;
            $paymentTotalCost = $totalAmount;
            $paymentBaseAmount = $totalAmount;
            $paymentVatAmount = 0;
            
            if ($whtRate > 0 && $whtTreatment !== 'NONE') {
                $whtCalc = $whtService->calculateWHT($totalAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                $paymentWHT = $whtCalc['wht_amount'];
                $paymentNetPayable = $whtCalc['net_payable'];
                $paymentTotalCost = $whtCalc['total_cost'];
                $paymentBaseAmount = $whtCalc['base_amount'];
                $paymentVatAmount = $whtCalc['vat_amount'];
            } elseif ($vatMode !== 'NONE' && $vatRate > 0) {
                // Calculate VAT even if no WHT
                if ($vatMode === 'INCLUSIVE') {
                    $paymentBaseAmount = round($totalAmount / (1 + ($vatRate / 100)), 2);
                    $paymentVatAmount = round($totalAmount - $paymentBaseAmount, 2);
                } else {
                    // EXCLUSIVE
                    $paymentBaseAmount = round($totalAmount / (1 + ($vatRate / 100)), 2);
                    $paymentVatAmount = round($totalAmount - $paymentBaseAmount, 2);
                }
            }

            // Generate payment reference
            $reference = 'PAY-' . date('Ymd') . '-' . str_pad(Payment::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Create payment
            $payment = Payment::create([
                'reference' => $reference,
                'reference_type' => 'Bill',
                'reference_number' => $billPurchase->reference, // Link payment to bill using bill's reference
                'amount' => $totalAmount, // Total amount (may include VAT)
                'wht_treatment' => $whtTreatment,
                'wht_rate' => $whtRate,
                'wht_amount' => $paymentWHT,
                'net_payable' => $paymentNetPayable,
                'total_cost' => $paymentTotalCost,
                'vat_mode' => $vatMode,
                'vat_amount' => $paymentVatAmount,
                'base_amount' => $paymentBaseAmount,
                'date' => $request->date,
                'description' => $request->description ?: "Payment for bill {$billPurchase->reference}",
                'bank_account_id' => $request->bank_account_id,
                'supplier_id' => $billPurchase->supplier_id,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'approved' => true,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // Create payment item for the bill payment
            PaymentItem::create([
                'payment_id' => $payment->id,
                'chart_account_id' => $billPurchase->credit_account,
                'amount' => $totalAmount,
                'wht_treatment' => $whtTreatment,
                'wht_rate' => $whtRate,
                'wht_amount' => $paymentWHT,
                'base_amount' => $paymentBaseAmount,
                'net_payable' => $paymentNetPayable,
                'total_cost' => $paymentTotalCost,
                'vat_mode' => $vatMode,
                'vat_amount' => $paymentVatAmount,
                'description' => $request->description ?: "Payment for bill {$billPurchase->reference}",
            ]);

            // Use Payment model's createGlTransactions method which handles WHT correctly
            $payment->createGlTransactions();

            // Update bill paid amount
            $billPurchase->updatePaidAmount();

            DB::commit();

            return redirect()->route('accounting.bill-purchases.show', $billPurchase)
                ->with('success', 'Payment processed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to process payment: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show payment details
     */
    public function showPayment(Payment $payment)
    {
        $payment->load(['bankAccount', 'supplier', 'user', 'branch', 'glTransactions.chartAccount']);
        
        // Find the bill linked to this payment
        $bill = null;
        if ($payment->reference_type === 'Bill' && $payment->reference_number) {
            $bill = Bill::where('reference', $payment->reference_number)->first();
        }
        
        return view('accounting.bill-purchases.payment-show', compact('payment', 'bill'));
    }

    /**
     * Show form to edit payment
     */
    public function editPayment(Payment $payment)
    {
        $payment->load(['bankAccount', 'supplier']);

        // Limit bank accounts by branch scope (all branches or current branch)
        $user = auth()->user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        $bankAccounts = BankAccount::orderBy('name')
            ->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true);
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->get();
        $suppliers = \App\Models\Supplier::where('status', 'active')->orderBy('name')->get();

        return view('accounting.bill-purchases.payment-edit', compact('payment', 'bankAccounts', 'suppliers'));
    }

    /**
     * Update payment
     */
    public function updatePayment(Request $request, Payment $payment)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable|string|max:1000',
            'wht_treatment' => 'nullable|in:EXCLUSIVE,INCLUSIVE,GROSS_UP,NONE',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'vat_mode' => 'nullable|in:EXCLUSIVE,INCLUSIVE,NONE',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $branchId = session('branch_id') ?? ($user->branch_id ?? null);
            
            if (!$branchId) {
                throw new \Exception('Branch ID is required but not found. Please ensure you are assigned to a branch.');
            }
            
            $oldAmount = $payment->amount;
            $newAmount = $request->amount;

            // Get WHT and VAT values
            $whtTreatment = $request->wht_treatment ?? ($payment->wht_treatment ?? 'EXCLUSIVE');
            $whtRate = (float) ($request->wht_rate ?? ($payment->wht_rate ?? 0));
            $vatMode = $request->vat_mode ?? ($payment->vat_mode ?? 'NONE');
            $vatRate = (float) ($request->vat_rate ?? ($payment->vat_rate ?? get_default_vat_rate()));

            // Calculate WHT and VAT amounts (similar to processPayment)
            $whtService = new \App\Services\WithholdingTaxService();
            $paymentWHT = 0;
            $paymentNetPayable = $newAmount;
            $paymentTotalCost = $newAmount;
            $paymentBaseAmount = $newAmount;
            $paymentVatAmount = 0;

            // If bill has no VAT, don't apply VAT calculations
            if ($vatMode === 'NONE' || $vatRate <= 0) {
                $vatMode = 'NONE';
                $vatRate = 0;
            }

            if ($whtRate > 0 && $whtTreatment !== 'NONE') {
                $whtCalc = $whtService->calculateWHT($newAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
                $paymentWHT = $whtCalc['wht_amount'];
                $paymentNetPayable = $whtCalc['net_payable'];
                $paymentTotalCost = $whtCalc['total_cost'];
                $paymentBaseAmount = $whtCalc['base_amount'];
                $paymentVatAmount = $whtCalc['vat_amount'];
            } elseif ($vatMode !== 'NONE' && $vatRate > 0) {
                // Calculate VAT even if no WHT
                if ($vatMode === 'INCLUSIVE') {
                    $paymentBaseAmount = round($newAmount / (1 + ($vatRate / 100)), 2);
                    $paymentVatAmount = round($newAmount - $paymentBaseAmount, 2);
                } else {
                    // EXCLUSIVE
                    $paymentBaseAmount = round($newAmount / (1 + ($vatRate / 100)), 2);
                    $paymentVatAmount = round($newAmount - $paymentBaseAmount, 2);
                }
            }

            // Update payment with calculated values
            $payment->update([
                'amount' => $newAmount,
                'date' => $request->date,
                'description' => $request->description,
                'bank_account_id' => $request->bank_account_id,
                'supplier_id' => $request->supplier_id,
                'wht_treatment' => $whtTreatment,
                'wht_rate' => $whtRate,
                'wht_amount' => $paymentWHT,
                'net_payable' => $paymentNetPayable,
                'total_cost' => $paymentTotalCost,
                'vat_mode' => $vatMode,
                'vat_rate' => $vatRate,
                'vat_amount' => $paymentVatAmount,
                'base_amount' => $paymentBaseAmount,
            ]);

            // Update payment items with new calculated values
            $bill = Bill::where('reference', $payment->reference_number)->first();
            foreach ($payment->paymentItems as $item) {
                $item->update([
                    'amount' => $newAmount,
                    'wht_treatment' => $whtTreatment,
                    'wht_rate' => $whtRate,
                    'wht_amount' => $paymentWHT,
                    'base_amount' => $paymentBaseAmount,
                    'net_payable' => $paymentNetPayable,
                    'total_cost' => $paymentTotalCost,
                    'vat_mode' => $vatMode,
                    'vat_amount' => $paymentVatAmount,
                ]);
            }

            // Delete existing GL transactions
            $payment->glTransactions()->delete();

            // Use Payment model's createGlTransactions method which handles VAT correctly
            $payment->refresh(); // Refresh to ensure payment items are loaded
            $payment->createGlTransactions();

            // Update bill paid amount
            if ($bill) {
                $bill->updatePaidAmount();
            }

            DB::commit();

            return redirect()->route('accounting.bill-purchases.payment.show', $payment)
                ->with('success', 'Payment updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update payment: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Delete payment
     */
    public function deletePayment(Payment $payment)
    {
        try {
            DB::beginTransaction();

            // Verify payment exists and get bill reference before deletion
            if (!$payment->exists) {
                throw new \Exception('Payment not found');
            }

            $bill = Bill::where('reference', $payment->reference_number)->first();
            $billReference = $bill ? $bill->reference : null;

            // Delete GL transactions first
            $glTransactionsDeleted = $payment->glTransactions()->delete();
            
            // Delete payment items if they exist
            if (method_exists($payment, 'paymentItems')) {
                $payment->paymentItems()->delete();
            }

            // Delete payment
            $paymentDeleted = $payment->delete();
            
            if (!$paymentDeleted) {
                throw new \Exception('Failed to delete payment record');
            }

            // Update bill paid amount if bill exists
            if ($bill) {
                // Refresh the bill model to ensure we have the latest data
                $bill->refresh();
                $bill->updatePaidAmount();
                // Refresh again after update to ensure we have the latest paid amount
                $bill->refresh();
            }

            DB::commit();

            // Redirect to bill show page if bill exists, otherwise back
            if ($billReference) {
                return redirect()->route('accounting.bill-purchases.show', $billReference)
                    ->with('success', 'Payment deleted successfully!');
            }

            return redirect()->back()
                ->with('success', 'Payment deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete payment: ' . $e->getMessage(), [
                'payment_id' => $payment->id ?? null,
                'reference' => $payment->reference ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Export bill to PDF
     */
    public function exportPdf(Bill $billPurchase)
    {
        // Load relationships
        $billPurchase->load(['supplier', 'creditAccount', 'user', 'branch', 'company', 'billItems.debitAccount']);

        $pdf = \PDF::loadView('accounting.bill-purchases.pdf', compact('billPurchase'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('bill-' . $billPurchase->reference . '.pdf');
    }

    /**
     * Export bill payment to PDF
     */
    public function exportPaymentPdf(Payment $payment)
    {
        // Load relationships
        $payment->load(['supplier', 'bankAccount', 'user', 'branch', 'paymentItems']);

        $pdf = \PDF::loadView('accounting.bill-purchases.payment-pdf', compact('payment'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('payment-' . $payment->reference . '.pdf');
    }
}
