<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\OpeningBalance;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\GlTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

class OpeningBalanceController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        $balances = OpeningBalance::with('supplier')
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId))
            ->orderByDesc('opening_date')
            ->paginate(25);
        return view('purchases.opening-balances.index', compact('balances'));
    }

    public function create()
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        if (!$branchId) {
            return redirect()->back()->withErrors(['error' => 'Please select a branch before creating opening balance.']);
        }
        $suppliers = Supplier::forBranch($branchId)->get();
        return view('purchases.opening-balances.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        Log::info('purchase.opening_balance.store.start', [
            'user_id' => Auth::id(),
            'branch_id' => $branchId,
            'payload' => $request->only(['supplier_id','opening_date','currency','exchange_rate','amount','reference'])
        ]);
        if (!$branchId) {
            Log::warning('purchase.opening_balance.store.no_branch', [
                'user_id' => Auth::id()
            ]);
            return back()->withInput()->withErrors(['error' => 'Please select a branch before creating opening balance.']);
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'opening_date' => 'required|date',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        Log::info('purchase.opening_balance.store.validated');
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $userId = Auth::id();
            
            // Get functional currency
            $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', Auth::user()->company->functional_currency ?? 'TZS');
            $currency = $request->currency ?? $functionalCurrency;
            
            // Get exchange rate using FxTransactionRateService
            $fxTransactionRateService = app(\App\Services\FxTransactionRateService::class);
            $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
            $rateResult = $fxTransactionRateService->getTransactionRate(
                $currency,
                $functionalCurrency,
                $request->opening_date,
                $companyId,
                $userProvidedRate
            );
            $rate = $rateResult['rate'];
            $fxRateUsed = $rate; // Store the rate used for fx_rate_used field
            
            $amount = (float) $request->amount;

            $opening = OpeningBalance::create([
                'supplier_id' => $request->supplier_id,
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'opening_date' => $request->opening_date,
                'currency' => $currency,
                'exchange_rate' => $rate,
                'fx_rate_used' => $fxRateUsed,
                'amount' => $amount,
                'paid_amount' => 0,
                'balance_due' => $amount,
                'status' => 'posted',
                'reference' => $request->reference,
                'notes' => $request->notes,
                'created_by' => $userId,
            ]);
            Log::info('purchase.opening_balance.created', [
                'opening_balance_id' => $opening->id,
                'supplier_id' => $opening->supplier_id,
                'amount' => $opening->amount
            ]);
            
            // Create synthetic invoice
            $invoice = PurchaseInvoice::create([
                'supplier_id' => $request->supplier_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => $request->opening_date,
                'due_date' => $request->opening_date,
                'status' => 'sent',
                'payment_terms' => 'immediate',
                'payment_days' => 0,
                'subtotal' => $amount,
                'vat_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => 0,
                'balance_due' => $amount,
                'currency' => $currency,
                'exchange_rate' => $rate,
                'fx_rate_used' => $fxRateUsed,
                'notes' => 'Opening Balance',
                'terms_conditions' => null,
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'created_by' => $userId,
            ]);

            $opening->update(['purchase_invoice_id' => $invoice->id]);
            Log::info('purchase.opening_balance.invoice_created', [
                'opening_balance_id' => $opening->id,
                'invoice_id' => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'total_amount' => $invoice->total_amount
            ]);

            // Post GL: Dr AP, Cr Opening AP Equity
            $payableAccountId = (int) (\App\Models\SystemSetting::where('key','inventory_default_purchase_payable_account')->value('value') ?? 30);
            // Resolve Opening Balance Equity account with multiple fallbacks
            $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','inventory_default_opening_balance_account')->value('value') ?? 0);
            if (!$openingEquityAccountId) {
                $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','ap_opening_balance_account_id')->value('value') ?? 0);
            }
            if (!$openingEquityAccountId) {
                // Fallback to Retained Earnings or a safe equity account id if configured
                $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','retained_earnings_account_id')->value('value') ?? 0);
            }
            Log::info('purchase.opening_balance.gl_accounts_resolved', [
                'payable_account_id' => $payableAccountId,
                'opening_equity_account_id' => $openingEquityAccountId
            ]);
            if (!$openingEquityAccountId) {
                Log::warning('purchase.opening_balance.missing_equity_account', [
                    'opening_balance_id' => $opening->id
                ]);
                DB::rollBack();
                return back()->withInput()->withErrors(['error' => 'Opening Balance Equity account is not configured. Set inventory_default_opening_balance_account (preferred) or ap_opening_balance_account_id or retained_earnings_account_id in Settings.']);
            }

            GlTransaction::create([
                'chart_account_id' => $payableAccountId,
                'supplier_id' => $request->supplier_id,
                'amount' => $amount,
                'nature' => 'credit',
                'transaction_id' => $invoice->id,
                'transaction_type' => 'purchase_invoice',
                'date' => $request->opening_date,
                'description' => 'Opening Balance AP',
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);

            GlTransaction::create([
                'chart_account_id' => $openingEquityAccountId,
                'supplier_id' => $request->supplier_id,
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_id' => $invoice->id,
                'transaction_type' => 'purchase_invoice',
                'date' => $request->opening_date,
                'description' => 'Opening Balance Offset',
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);

            DB::commit();
            Log::info('purchase.opening_balance.store.success', [
                'opening_balance_id' => $opening->id,
                'invoice_id' => $invoice->id
            ]);
            $encodedId = \Vinkla\Hashids\Facades\Hashids::encode($opening->id);
            return redirect()->route('purchases.opening-balances.show', $encodedId)->with('success', 'Opening balance posted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('purchase.opening_balance.store.failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->withErrors(['error' => 'Failed to create opening balance: ' . $e->getMessage()]);
        }
    }

    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return redirect()->route('purchases.opening-balances.index')
                ->with('error', 'Invalid opening balance ID');
        }
        $balance = OpeningBalance::with(['supplier','invoice'])->findOrFail($id);
        return view('purchases.opening-balances.show', compact('balance'));
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'PINV-';
        $datePart = now()->format('Ymd');
        $last = PurchaseInvoice::whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->first();
        $seq = 1;
        if ($last && preg_match('/^(PINV-\d{8})-(\d{4})$/', (string) $last->invoice_number, $m) && $m[1] === ($prefix . $datePart)) {
            $seq = (int) $m[2] + 1;
        }
        return $prefix . $datePart . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
