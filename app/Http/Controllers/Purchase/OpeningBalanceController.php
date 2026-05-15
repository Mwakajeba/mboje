<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\Purchase\OpeningBalance;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Services\Purchase\SupplierOpeningBalanceJournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

class OpeningBalanceController extends Controller
{
    public function __construct(
        private readonly SupplierOpeningBalanceJournalService $journalService
    ) {}

    public function index()
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        $balances = OpeningBalance::with(['supplier', 'journal', 'payableChartAccount'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('opening_date')
            ->paginate(25);

        return view('purchases.opening-balances.index', compact('balances'));
    }

    public function create()
    {
        return $this->renderCreateForm(false);
    }

    public function createFromAdvances()
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        return $this->renderCreateForm(true);
    }

    private function renderCreateForm(bool $fromAdvances)
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        if (! $branchId) {
            return redirect()->back()->withErrors(['error' => 'Please select a branch before creating opening balance.']);
        }

        $user = Auth::user();
        $suppliers = Supplier::where('company_id', $user->company_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        $chartAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })
            ->orderBy('account_code')
            ->orderBy('account_name')
            ->get();

        $retainedEarningsId = $this->journalService->resolveRetainedEarningsAccountId((int) $user->company_id);
        $retainedEarningsAccount = $retainedEarningsId
            ? ChartAccount::find($retainedEarningsId)
            : null;

        $defaultPayableId = (int) (SystemSetting::where('key', 'inventory_default_purchase_payable_account')->value('value') ?? 0);

        $view = $fromAdvances
            ? 'purchases.supplier-advances.opening-balance-create'
            : 'purchases.opening-balances.create';

        return view($view, compact(
            'suppliers',
            'chartAccounts',
            'retainedEarningsAccount',
            'defaultPayableId',
            'fromAdvances'
        ));
    }

    public function store(Request $request)
    {
        return $this->persistOpeningBalance($request, false);
    }

    public function storeFromAdvances(Request $request)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        return $this->persistOpeningBalance($request, true);
    }

    private function persistOpeningBalance(Request $request, bool $fromAdvances): RedirectResponse
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        Log::info('purchase.opening_balance.store.start', [
            'user_id' => Auth::id(),
            'branch_id' => $branchId,
            'from_advances' => $fromAdvances,
        ]);

        if (! $branchId) {
            return back()->withInput()->withErrors(['error' => 'Please select a branch before creating opening balance.']);
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'opening_date' => 'required|date',
            'payable_chart_account_id' => 'required|exists:chart_accounts,id',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;

        $chartOk = ChartAccount::whereKey($request->payable_chart_account_id)
            ->whereHas('accountClassGroup', fn ($q) => $q->where('company_id', $companyId))
            ->exists();
        if (! $chartOk) {
            return back()->withInput()->withErrors(['payable_chart_account_id' => 'Invalid chart account for this company.']);
        }

        DB::beginTransaction();
        try {
            $userId = Auth::id();
            $functionalCurrency = SystemSetting::getValue('functional_currency', $user->company->functional_currency ?? 'TZS');
            $currency = $request->currency ?? $functionalCurrency;

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
            $amount = (float) $request->amount;

            $opening = OpeningBalance::create([
                'supplier_id' => $request->supplier_id,
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'opening_date' => $request->opening_date,
                'currency' => $currency,
                'exchange_rate' => $rate,
                'amount' => $amount,
                'paid_amount' => 0,
                'balance_due' => $amount,
                'status' => 'posted',
                'reference' => $request->reference,
                'notes' => $request->notes,
                'payable_chart_account_id' => $request->payable_chart_account_id,
                'created_by' => $userId,
            ]);

            if (empty($opening->reference)) {
                $opening->update(['reference' => 'SOB-'.$opening->id]);
            }

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
                'notes' => 'Supplier Opening Balance',
                'terms_conditions' => null,
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'created_by' => $userId,
            ]);

            $opening->update(['purchase_invoice_id' => $invoice->id]);

            $this->journalService->post(
                $opening->fresh(['supplier']),
                (int) $request->payable_chart_account_id,
                (int) $userId
            );

            DB::commit();

            $encodedId = Hashids::encode($opening->id);
            $success = 'Supplier opening balance posted via journal (retained earnings and selected payable account).';

            if ($fromAdvances) {
                return redirect()
                    ->route('purchases.supplier-advances.index')
                    ->with('success', $success);
            }

            return redirect()
                ->route('purchases.opening-balances.show', $encodedId)
                ->with('success', $success);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('purchase.opening_balance.store.failed', [
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Failed to create opening balance: '.$e->getMessage()]);
        }
    }

    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (! $id) {
            return redirect()->route('purchases.opening-balances.index')
                ->with('error', 'Invalid opening balance ID');
        }
        $balance = OpeningBalance::with(['supplier', 'invoice', 'journal', 'payableChartAccount'])->findOrFail($id);

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
        if ($last && preg_match('/^(PINV-\d{8})-(\d{4})$/', (string) $last->invoice_number, $m) && $m[1] === ($prefix.$datePart)) {
            $seq = (int) $m[2] + 1;
        }

        return $prefix.$datePart.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
