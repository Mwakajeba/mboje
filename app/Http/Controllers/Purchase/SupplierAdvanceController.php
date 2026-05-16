<?php

namespace App\Http\Controllers\Purchase;

use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\ChartAccount;
use App\Models\Purchase\SupplierAdvance;
use App\Models\Purchase\SupplierAdvanceDeduction;
use App\Models\Supplier;
use App\Services\BankReconciliationService;
use App\Services\Purchase\SupplierAdvanceAllocationService;
use App\Services\Purchase\SupplierAdvanceJournalService;
use App\Services\Purchase\SupplierAdvanceExpenseService;
use App\Services\Purchase\SupplierAdvanceRefundService;
use App\Services\Purchase\SupplierAdvanceStatementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class SupplierAdvanceController extends Controller
{
    /** Debit GL line: only chart accounts whose trimmed code starts with this (e.g. 1100, 11001). */
    private const SUPPLIER_ADVANCE_DEBIT_ACCOUNT_CODE_PREFIX = '1100';

    public function __construct(
        private readonly SupplierAdvanceJournalService $advanceJournalService,
        private readonly SupplierAdvanceAllocationService $allocationService,
        private readonly SupplierAdvanceRefundService $refundService,
        private readonly SupplierAdvanceExpenseService $expenseService
    ) {}

    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('view purchases'), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        if ($request->ajax()) {
            if ($request->input('table') === 'balances') {
                return $this->balancesDataTable($request, $companyId, $branchId);
            }

            return $this->advancesDataTable($request, $companyId, $branchId);
        }

        $showAll = $request->boolean('all');

        return view('purchases.supplier-advances.index', compact('showAll'));
    }

    public function create()
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true);
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('company_id', $user->company_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->withSum(['supplierAdvances as advances_total' => function ($q) use ($user, $branchId) {
                $q->where('company_id', $user->company_id)
                    ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
            }], 'amount')
            ->orderBy('name')
            ->get();

        $chartAccounts = $this->supplierAdvanceDebitChartAccounts($user);

        return view('purchases.supplier-advances.create', compact('bankAccounts', 'suppliers', 'chartAccounts'));
    }

    public function createOpening()
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        $suppliers = Supplier::where('company_id', $user->company_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->withSum(['supplierAdvances as advances_total' => function ($q) use ($user, $branchId) {
                $q->where('company_id', $user->company_id)
                    ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
            }], 'amount')
            ->orderBy('name')
            ->get();

        $chartAccounts = $this->supplierAdvanceDebitChartAccounts($user);
        $retainedEarningsId = $this->advanceJournalService->resolveRetainedEarningsAccountId((int) $user->company_id);
        $retainedEarningsAccount = $retainedEarningsId ? ChartAccount::find($retainedEarningsId) : null;

        return view('purchases.supplier-advances.create-opening', compact('suppliers', 'chartAccounts', 'retainedEarningsAccount'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $this->mergeNormalizedAmount($request);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'advance_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'debit_chart_account_id' => 'required|exists:chart_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:2000',
            'reference' => 'nullable|string|max:64',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $resolvedBranchId = $user->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        if (! $resolvedBranchId) {
            return back()->withInput()->with('error', 'Active branch is not set. Please select a branch and try again.');
        }

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($validated['supplier_id']);

        $err = $this->validateBankAndChartForStore($companyId, $resolvedBranchId, $validated, null);
        if ($err instanceof RedirectResponse) {
            return $err;
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time().'_'.\Illuminate\Support\Str::random(10).'.'.$file->getClientOriginalExtension();
            $attachmentPath = $file->storeAs('supplier-advance-attachments', $fileName, 'public');
        }

        try {
            DB::beginTransaction();
            $advance = SupplierAdvance::create([
                'company_id' => $companyId,
                'branch_id' => $resolvedBranchId,
                'supplier_id' => $supplier->id,
                'advance_date' => $validated['advance_date'],
                'reference' => $validated['reference'] ?: null,
                'debit_chart_account_id' => $validated['debit_chart_account_id'],
                'bank_account_id' => $validated['bank_account_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'attachment_path' => $attachmentPath,
                'user_id' => $user->id,
            ]);
            if (empty($advance->reference)) {
                $advance->update(['reference' => 'SADV-'.$advance->id]);
            }
            $advance->postGlTransactions();
            DB::commit();

            $this->sendSupplierAdvancePaymentSms(
                $supplier,
                (float) $validated['amount'],
                $companyId,
                (int) $resolvedBranchId,
                $validated['advance_date'],
                (string) $user->name
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Supplier advance recorded and posted to the general ledger.');
    }

    public function storeOpening(Request $request)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $this->mergeNormalizedAmount($request);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'advance_date' => 'required|date',
            'debit_chart_account_id' => 'required|exists:chart_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:2000',
            'reference' => 'nullable|string|max:64',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $resolvedBranchId = $user->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        if (! $resolvedBranchId) {
            return back()->withInput()->with('error', 'Active branch is not set. Please select a branch and try again.');
        }

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($validated['supplier_id']);

        $err = $this->validateOpeningAdvanceChart($companyId, (int) $validated['debit_chart_account_id']);
        if ($err instanceof RedirectResponse) {
            return $err;
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time().'_'.\Illuminate\Support\Str::random(10).'.'.$file->getClientOriginalExtension();
            $attachmentPath = $file->storeAs('supplier-advance-attachments', $fileName, 'public');
        }

        try {
            DB::beginTransaction();
            $advance = SupplierAdvance::create([
                'company_id' => $companyId,
                'branch_id' => $resolvedBranchId,
                'supplier_id' => $supplier->id,
                'advance_date' => $validated['advance_date'],
                'reference' => $validated['reference'] ?: null,
                'debit_chart_account_id' => $validated['debit_chart_account_id'],
                'bank_account_id' => null,
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'attachment_path' => $attachmentPath,
                'user_id' => $user->id,
            ]);
            if (empty($advance->reference)) {
                $advance->update(['reference' => 'SADV-'.$advance->id]);
            }
            $this->advanceJournalService->postOpeningAdvance($advance->fresh(['supplier']), (int) $user->id);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Opening balance advance payment posted via journal and general ledger.');
    }

    public function edit(string $encodedId)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $advance = $this->advanceForEncodedId($encodedId);
        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true);
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('company_id', $user->company_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->withSum(['supplierAdvances as advances_total' => function ($q) use ($user, $branchId) {
                $q->where('company_id', $user->company_id)
                    ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
            }], 'amount')
            ->orderBy('name')
            ->get();

        $chartAccounts = $this->supplierAdvanceDebitChartAccounts($user);
        $advance->loadMissing('debitChartAccount');
        if ($advance->debitChartAccount && ! $chartAccounts->pluck('id')->contains($advance->debit_chart_account_id)) {
            $chartAccounts = $chartAccounts
                ->prepend($advance->debitChartAccount)
                ->sortBy(fn ($c) => $c->account_code)
                ->values();
        }

        $retainedEarningsAccount = null;
        if ($advance->isOpeningJournalAdvance()) {
            $reId = $this->advanceJournalService->resolveRetainedEarningsAccountId((int) $user->company_id);
            $retainedEarningsAccount = $reId ? ChartAccount::find($reId) : null;

            return view('purchases.supplier-advances.edit-opening', compact(
                'advance',
                'suppliers',
                'chartAccounts',
                'retainedEarningsAccount'
            ));
        }

        return view('purchases.supplier-advances.edit', compact('advance', 'bankAccounts', 'suppliers', 'chartAccounts'));
    }

    public function update(Request $request, string $encodedId)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $advance = $this->advanceForEncodedId($encodedId);

        if ($advance->isOpeningJournalAdvance()) {
            return $this->updateOpeningAdvance($request, $advance);
        }

        $this->mergeNormalizedAmount($request);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'advance_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'debit_chart_account_id' => 'required|exists:chart_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:2000',
            'reference' => 'nullable|string|max:64',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $resolvedBranchId = (int) $advance->branch_id;

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($validated['supplier_id']);

        $err = $this->validateBankAndChartForStore($companyId, $resolvedBranchId, $validated, (int) $advance->debit_chart_account_id);
        if ($err instanceof RedirectResponse) {
            return $err;
        }

        try {
            DB::beginTransaction();
            $this->assertAdvanceGlCanBeReversed($advance);

            $advance->loadMissing(['bankAccount.chartAccount', 'debitChartAccount']);
            $oldBankChartId = $advance->bankAccount?->chart_account_id;
            $oldDebitChartId = $advance->debit_chart_account_id;
            $oldDate = $advance->advance_date;

            $this->assertChartsNotInCompletedReconciliation($oldBankChartId, $oldDate);
            $this->assertChartsNotInCompletedReconciliation($oldDebitChartId, $oldDate);

            $advance->removeGlTransactions();

            $attachmentPath = $advance->attachment_path;
            if ($request->hasFile('attachment')) {
                if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                    Storage::disk('public')->delete($attachmentPath);
                }
                $file = $request->file('attachment');
                $fileName = time().'_'.\Illuminate\Support\Str::random(10).'.'.$file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('supplier-advance-attachments', $fileName, 'public');
            }

            $advance->update([
                'supplier_id' => $supplier->id,
                'advance_date' => $validated['advance_date'],
                'reference' => $validated['reference'] ?: ('SADV-'.$advance->id),
                'debit_chart_account_id' => $validated['debit_chart_account_id'],
                'bank_account_id' => $validated['bank_account_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'attachment_path' => $attachmentPath,
            ]);

            $advance->refresh();
            $advance->postGlTransactions();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Supplier advance updated and general ledger entries refreshed.');
    }

    public function destroy(string $encodedId)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $advance = $this->advanceForEncodedId($encodedId);

        if ($advance->advanceDeductions()->exists()) {
            return redirect()
                ->route('purchases.supplier-advances.index')
                ->with('error', 'Cannot delete: this advance has been applied to purchases.');
        }

        try {
            DB::beginTransaction();
            $this->assertAdvanceGlCanBeReversed($advance);

            $advance->loadMissing(['bankAccount.chartAccount', 'debitChartAccount']);
            if ($advance->isOpeningJournalAdvance()) {
                $retainedId = $this->advanceJournalService->resolveRetainedEarningsAccountId((int) $advance->company_id);
                $this->assertChartsNotInCompletedReconciliation($retainedId, $advance->advance_date);
            } else {
                $this->assertChartsNotInCompletedReconciliation($advance->bankAccount?->chart_account_id, $advance->advance_date);
            }
            $this->assertChartsNotInCompletedReconciliation($advance->debit_chart_account_id, $advance->advance_date);

            $advance->removeGlTransactions();

            if ($advance->attachment_path && Storage::disk('public')->exists($advance->attachment_path)) {
                Storage::disk('public')->delete($advance->attachment_path);
            }

            $advance->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()
                ->route('purchases.supplier-advances.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Supplier advance deleted and GL entries removed.');
    }

    public function statement(Request $request, string $encodedSupplierId)
    {
        abort_unless(Auth::user()->can('view purchases'), 403);

        $decoded = Hashids::decode($encodedSupplierId);
        if (empty($decoded[0])) {
            abort(404);
        }
        $supplierId = (int) $decoded[0];

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($supplierId);

        $service = app(SupplierAdvanceStatementService::class);
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        if ($fromDate && $toDate) {
            try {
                $statement = $service->buildForSupplierPeriod(
                    $supplier->id,
                    $companyId,
                    $branchId ? (int) $branchId : null,
                    $fromDate,
                    $toDate
                );
            } catch (\InvalidArgumentException $e) {
                return redirect()
                    ->route('purchases.index')
                    ->with('error', $e->getMessage());
            }
        } else {
            $statement = $service->buildForSupplier(
                $supplier->id,
                $companyId,
                $branchId ? (int) $branchId : null
            );
        }

        $lines = $statement['lines']->map(function (array $line) {
            $line['debit'] = $line['paid'];
            $line['credit'] = $line['deducted'];

            return $line;
        });
        $totals = $statement['totals'];
        $period = $statement['period'] ?? null;
        $openingBalance = $statement['opening_balance'] ?? ($totals['opening_balance'] ?? null);

        return view('purchases.supplier-advances.statement', compact('supplier', 'lines', 'totals', 'period', 'openingBalance'));
    }

    public function pay(string $encodedSupplierId)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $branchId);

        $balance = $this->allocationService->balanceForSupplier(
            $supplier->id,
            $companyId,
            $branchId ? (int) $branchId : null
        );

        if ($balance <= 0) {
            return redirect()
                ->route('purchases.supplier-advances.index')
                ->with('error', 'This supplier has no advance balance to refund.');
        }

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true);
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->orderBy('name')
            ->get();

        return view('purchases.supplier-advances.pay', compact(
            'supplier',
            'balance',
            'bankAccounts',
            'encodedSupplierId'
        ));
    }

    public function payStore(Request $request, string $encodedSupplierId)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $this->mergeNormalizedAmount($request);

        $validated = $request->validate([
            'date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:2000',
            'reference' => 'nullable|string|max:64',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $resolvedBranchId = $user->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        if (! $resolvedBranchId) {
            return back()->withInput()->with('error', 'Active branch is not set. Please select a branch and try again.');
        }

        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $resolvedBranchId);

        $bankErr = $this->validateBankForBranch($companyId, (int) $resolvedBranchId, (int) $validated['bank_account_id']);
        if ($bankErr instanceof RedirectResponse) {
            return $bankErr;
        }

        $balance = $this->allocationService->balanceForSupplier($supplier->id, $companyId, (int) $resolvedBranchId);
        if ((float) $validated['amount'] > $balance + 0.05) {
            return back()->withInput()->withErrors([
                'amount' => 'Amount cannot exceed supplier advance balance ('.number_format($balance, 2).').',
            ]);
        }

        try {
            $this->refundService->processRefund(
                $supplier,
                $companyId,
                (int) $resolvedBranchId,
                (int) $validated['bank_account_id'],
                (float) $validated['amount'],
                $validated['date'],
                (int) $user->id,
                $validated['description'] ?? null,
                $validated['reference'] ?? null
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Supplier advance refund recorded. Bank debited and advance balance reduced.');
    }

    public function expense(string $encodedSupplierId)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $branchId);

        $balance = $this->allocationService->balanceForSupplier(
            $supplier->id,
            $companyId,
            $branchId ? (int) $branchId : null
        );

        if ($balance <= 0) {
            return redirect()
                ->route('purchases.supplier-advances.index')
                ->with('error', 'This supplier has no advance balance to apply to expenses.');
        }

        $expenseAccounts = $this->expenseChartAccounts($companyId);

        return view('purchases.supplier-advances.expense', compact(
            'supplier',
            'balance',
            'expenseAccounts',
            'encodedSupplierId'
        ));
    }

    public function expenseStore(Request $request, string $encodedSupplierId)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $this->mergeNormalizedLineItemAmounts($request);

        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string|max:2000',
            'reference' => 'nullable|string|max:64',
            'line_items' => 'required|array|min:1',
            'line_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.description' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $resolvedBranchId = $user->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        if (! $resolvedBranchId) {
            return back()->withInput()->with('error', 'Active branch is not set. Please select a branch and try again.');
        }

        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $resolvedBranchId);

        $lineItems = [];
        foreach ($validated['line_items'] as $row) {
            $chartId = (int) $row['chart_account_id'];
            if (! $this->expenseChartAccountIsAllowed($companyId, $chartId)) {
                return back()->withInput()->withErrors([
                    'line_items' => 'One or more selected accounts are not valid expense accounts for this company.',
                ]);
            }
            $lineItems[] = [
                'chart_account_id' => $chartId,
                'amount' => (float) $row['amount'],
                'description' => $row['description'] ?? null,
            ];
        }

        $total = round(collect($lineItems)->sum('amount'), 2);
        $balance = $this->allocationService->balanceForSupplier($supplier->id, $companyId, (int) $resolvedBranchId);
        if ($total > $balance + 0.05) {
            return back()->withInput()->withErrors([
                'line_items' => 'Total cannot exceed supplier advance balance ('.number_format($balance, 2).').',
            ]);
        }

        try {
            $this->expenseService->processExpense(
                $supplier,
                $companyId,
                (int) $resolvedBranchId,
                $lineItems,
                $validated['date'],
                (int) $user->id,
                $validated['description'] ?? null,
                $validated['reference'] ?? null
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Supplier advance expense journal posted. Expense debited and advance balance reduced.');
    }

    private function decodeAdvanceId(string $encodedId): int
    {
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded[0])) {
            abort(404);
        }

        return (int) $decoded[0];
    }

    private function advanceForEncodedId(string $encodedId): SupplierAdvance
    {
        $id = $this->decodeAdvanceId($encodedId);
        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $q = SupplierAdvance::where('company_id', $companyId)->whereKey($id);
        if ($branchId) {
            $q->where('branch_id', $branchId);
        }

        return $q->firstOrFail();
    }

    /**
     * @return RedirectResponse|null
     */
    private function validateOpeningAdvanceChart(int $companyId, int $debitChartAccountId): ?RedirectResponse
    {
        if (! $this->debitChartAccountIsAllowedForSupplierAdvance($companyId, $debitChartAccountId)) {
            return back()->withInput()->withErrors([
                'debit_chart_account_id' => 'Selected account must be a chart account with code starting '.self::SUPPLIER_ADVANCE_DEBIT_ACCOUNT_CODE_PREFIX.'.',
            ]);
        }

        $retainedId = $this->advanceJournalService->resolveRetainedEarningsAccountId($companyId);
        if (! $retainedId) {
            return back()->withInput()->withErrors([
                'error' => 'Retained earnings account is not configured. Set retained_earnings_account_id in Settings.',
            ]);
        }

        if ($debitChartAccountId === $retainedId) {
            return back()->withInput()->withErrors([
                'debit_chart_account_id' => 'Advance account cannot be the same as the retained earnings account.',
            ]);
        }

        return null;
    }

    private function updateOpeningAdvance(Request $request, SupplierAdvance $advance): RedirectResponse
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        if ($advance->advanceDeductions()->exists()) {
            return back()->withInput()->with('error', 'Cannot edit: this advance has been applied to purchases.');
        }

        $this->mergeNormalizedAmount($request);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'advance_date' => 'required|date',
            'debit_chart_account_id' => 'required|exists:chart_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:2000',
            'reference' => 'nullable|string|max:64',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $supplier = Supplier::where('company_id', $companyId)->findOrFail($validated['supplier_id']);

        $err = $this->validateOpeningAdvanceChart($companyId, (int) $validated['debit_chart_account_id']);
        if ($err) {
            return $err;
        }

        try {
            DB::beginTransaction();
            $this->assertAdvanceGlCanBeReversed($advance);

            $attachmentPath = $advance->attachment_path;
            if ($request->hasFile('attachment')) {
                if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                    Storage::disk('public')->delete($attachmentPath);
                }
                $file = $request->file('attachment');
                $fileName = time().'_'.\Illuminate\Support\Str::random(10).'.'.$file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('supplier-advance-attachments', $fileName, 'public');
            }

            $advance->update([
                'supplier_id' => $supplier->id,
                'advance_date' => $validated['advance_date'],
                'reference' => $validated['reference'] ?: ('SADV-'.$advance->id),
                'debit_chart_account_id' => $validated['debit_chart_account_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'attachment_path' => $attachmentPath,
            ]);

            $this->advanceJournalService->postOpeningAdvance($advance->fresh(['supplier']), (int) $user->id);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Opening supplier advance updated and journal reposted.');
    }

    private function supplierForEncodedId(string $encodedSupplierId, int $companyId, ?int $branchId): Supplier
    {
        $decoded = Hashids::decode($encodedSupplierId);
        if (empty($decoded[0])) {
            abort(404);
        }

        return Supplier::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->findOrFail((int) $decoded[0]);
    }

    private function validateBankForBranch(int $companyId, int $branchId, int $bankAccountId): ?RedirectResponse
    {
        $bankOk = BankAccount::whereKey($bankAccountId)
            ->whereHas('chartAccount.accountClassGroup', fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true)
                    ->orWhere('branch_id', $branchId);
            })
            ->exists();

        if (! $bankOk) {
            return back()->withInput()->withErrors(['bank_account_id' => 'Invalid bank account for this branch or company.']);
        }

        return null;
    }

    private function validateBankAndChartForStore(int $companyId, int $resolvedBranchId, array $validated, ?int $preserveDebitChartAccountId = null): ?RedirectResponse
    {
        $bankOk = BankAccount::whereKey($validated['bank_account_id'])
            ->whereHas('chartAccount.accountClassGroup', fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($query) use ($resolvedBranchId) {
                $query->where('is_all_branches', true)
                    ->orWhere('branch_id', $resolvedBranchId);
            })
            ->exists();
        if (! $bankOk) {
            return back()->withInput()->withErrors(['bank_account_id' => 'Invalid bank account for this branch or company.']);
        }

        $debitId = (int) $validated['debit_chart_account_id'];
        if ($preserveDebitChartAccountId !== null && $debitId === $preserveDebitChartAccountId) {
            $chartOk = ChartAccount::whereKey($debitId)
                ->whereHas('accountClassGroup', fn ($q) => $q->where('company_id', $companyId))
                ->exists();
        } else {
            $chartOk = $this->debitChartAccountIsAllowedForSupplierAdvance($companyId, $debitId);
        }
        if (! $chartOk) {
            return back()->withInput()->withErrors(['debit_chart_account_id' => 'Selected account must be a chart account with code starting '.self::SUPPLIER_ADVANCE_DEBIT_ACCOUNT_CODE_PREFIX.'.']);
        }

        $bankAccount = BankAccount::with('chartAccount')->find($validated['bank_account_id']);
        if ($bankAccount && (int) $bankAccount->chart_account_id === (int) $validated['debit_chart_account_id']) {
            return back()->withInput()->withErrors(['debit_chart_account_id' => 'Advance account cannot be the same as the selected bank ledger account.']);
        }

        return null;
    }

    private function assertAdvanceGlCanBeReversed(SupplierAdvance $advance): void
    {
        $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
        $periodLockService->validateTransactionDate($advance->advance_date, (int) $advance->company_id, 'transaction');
    }

    private function supplierAdvanceDebitChartAccounts($user)
    {
        $prefix = self::SUPPLIER_ADVANCE_DEBIT_ACCOUNT_CODE_PREFIX.'%';

        return ChartAccount::whereHas('accountClassGroup', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        })
            ->whereRaw('TRIM(account_code) LIKE ?', [$prefix])
            ->orderBy('account_code')
            ->orderBy('account_name')
            ->get();
    }

    private function debitChartAccountIsAllowedForSupplierAdvance(int $companyId, int $chartAccountId): bool
    {
        $prefix = self::SUPPLIER_ADVANCE_DEBIT_ACCOUNT_CODE_PREFIX.'%';

        return ChartAccount::whereKey($chartAccountId)
            ->whereHas('accountClassGroup', fn ($q) => $q->where('company_id', $companyId))
            ->whereRaw('TRIM(account_code) LIKE ?', [$prefix])
            ->exists();
    }

    private function mergeNormalizedAmount(Request $request): void
    {
        if (! $request->has('amount')) {
            return;
        }
        $raw = (string) $request->input('amount', '');
        $clean = preg_replace('/[^\d.]/', '', str_replace(',', '', $raw));
        if ($clean === '') {
            $request->merge(['amount' => null]);

            return;
        }
        $request->merge(['amount' => $clean]);
    }

    private function mergeNormalizedLineItemAmounts(Request $request): void
    {
        if (! $request->has('line_items') || ! is_array($request->line_items)) {
            return;
        }
        $items = $request->line_items;
        foreach ($items as $i => $row) {
            if (! isset($row['amount'])) {
                continue;
            }
            $raw = (string) $row['amount'];
            $clean = preg_replace('/[^\d.]/', '', str_replace(',', '', $raw));
            $items[$i]['amount'] = $clean === '' ? null : $clean;
        }
        $request->merge(['line_items' => $items]);
    }

    private function expenseChartAccounts(int $companyId)
    {
        return ChartAccount::whereHas('accountClassGroup', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->whereHas('accountClass', function ($q2) {
                    $q2->where('name', 'LIKE', '%expense%');
                });
        })
            ->orderBy('account_code')
            ->orderBy('account_name')
            ->get();
    }

    private function expenseChartAccountIsAllowed(int $companyId, int $chartAccountId): bool
    {
        return ChartAccount::whereKey($chartAccountId)
            ->whereHas('accountClassGroup', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->whereHas('accountClass', function ($q2) {
                        $q2->where('name', 'LIKE', '%expense%');
                    });
            })
            ->exists();
    }

    private function assertChartsNotInCompletedReconciliation(?int $chartAccountId, $transactionDate): void
    {
        if (! $chartAccountId) {
            return;
        }
        if (BankReconciliationService::isChartAccountInCompletedReconciliation($chartAccountId, $transactionDate)) {
            throw new \RuntimeException('Cannot change or delete: a chart account used on this voucher is in a completed bank reconciliation for this date.');
        }
    }

    private function advancesDataTable(Request $request, int $companyId, ?int $branchId)
    {
        $user = Auth::user();
        $canRecord = $user->can('record purchase payment');
        $canView = $user->can('view purchases');

        $query = SupplierAdvance::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['supplier', 'debitChartAccount', 'bankAccount', 'journal'])
            ->select('supplier_advances.*');

        return datatables($query)
            ->filter(function ($query) {
                $keyword = trim((string) request()->input('search.value', ''));
                if ($keyword === '') {
                    return;
                }
                $like = '%'.addcslashes($keyword, '%_\\').'%';
                $query->whereHas('supplier', fn ($q) => $q->where('name', 'like', $like));
            }, true)
            ->addColumn('advance_date_formatted', fn (SupplierAdvance $a) => $a->advance_date?->format('Y-m-d') ?? '—')
            ->addColumn('supplier_name', fn (SupplierAdvance $a) => $a->supplier->name ?? '—')
            ->addColumn('debit_account', function (SupplierAdvance $a) {
                if (! $a->debitChartAccount) {
                    return '—';
                }

                return e($a->debitChartAccount->account_code.' — '.$a->debitChartAccount->account_name);
            })
            ->addColumn('credit_account', function (SupplierAdvance $a) {
                if ($a->isOpeningJournalAdvance()) {
                    return '<span class="text-muted">Retained earnings (journal #'.$a->journal_id.')</span>';
                }

                return e($a->bankAccount->name ?? '—');
            })
            ->addColumn('amount_formatted', fn (SupplierAdvance $a) => format_currency((float) $a->amount))
            ->addColumn('actions', function (SupplierAdvance $a) use ($canRecord, $canView) {
                $enc = Hashids::encode($a->id);
                $encSup = Hashids::encode($a->supplier_id);
                $html = '<div class="btn-group btn-group-sm" role="group">';
                if ($canRecord) {
                    $html .= '<a href="'.route('purchases.supplier-advances.edit', $enc).'" class="btn btn-outline-primary" title="Edit"><i class="bx bx-edit-alt"></i></a>';
                    $html .= '<form action="'.route('purchases.supplier-advances.destroy', $enc).'" method="post" class="d-inline" onsubmit="return confirm(\'Delete this advance? Posted GL entries for this voucher will be removed.\');">';
                    $html .= csrf_field().method_field('DELETE');
                    $html .= '<button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bx bx-trash"></i></button></form>';
                }
                if ($canView) {
                    $html .= '<a href="'.route('purchases.supplier-advances.statement', ['encodedSupplierId' => $encSup]).'" class="btn btn-outline-secondary" title="Statement" target="_blank" rel="noopener"><i class="bx bx-file"></i></a>';
                }
                $html .= '</div>';

                return $html;
            })
            ->orderColumn('supplier_name', function ($query, $order) {
                $query->orderBy(
                    Supplier::select('name')
                        ->whereColumn('suppliers.id', 'supplier_advances.supplier_id')
                        ->limit(1),
                    $order
                );
            })
            ->rawColumns(['credit_account', 'actions'])
            ->make(true);
    }

    private function balancesDataTable(Request $request, int $companyId, ?int $branchId)
    {
        $user = Auth::user();
        $canRecord = $user->can('record purchase payment');
        $canView = $user->can('view purchases');
        $showAll = $request->boolean('show_all');

        $query = Supplier::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->withSum(['supplierAdvances as advances_total' => function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)
                    ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
            }], 'amount')
            ->withSum(['supplierAdvanceDeductions as applied_total' => function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)
                    ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
            }], 'amount');

        if (! $showAll) {
            $query->where(function ($q) use ($companyId, $branchId) {
                $q->whereHas('supplierAdvances', function ($a) use ($companyId, $branchId) {
                    $a->where('company_id', $companyId)
                        ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
                })->orWhereHas('supplierAdvanceDeductions', function ($d) use ($companyId, $branchId) {
                    $d->where('company_id', $companyId)
                        ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
                });
            });
        }

        return datatables($query)
            ->filter(function ($query) {
                $keyword = trim((string) request()->input('search.value', ''));
                if ($keyword === '') {
                    return;
                }
                $like = '%'.addcslashes($keyword, '%_\\').'%';
                $query->where('suppliers.name', 'like', $like);
            }, true)
            ->addColumn('advances_formatted', fn (Supplier $s) => format_currency((float) ($s->advances_total ?? 0)))
            ->addColumn('applied_formatted', fn (Supplier $s) => format_currency((float) ($s->applied_total ?? 0)))
            ->addColumn('balance_formatted', function (Supplier $s) {
                $bal = (float) ($s->advances_total ?? 0) - (float) ($s->applied_total ?? 0);

                return '<span class="fw-semibold">'.format_currency($bal).'</span>';
            })
            ->addColumn('balance_raw', function (Supplier $s) {
                return (float) ($s->advances_total ?? 0) - (float) ($s->applied_total ?? 0);
            })
            ->addColumn('actions', function (Supplier $s) use ($canRecord, $canView) {
                $adv = (float) ($s->advances_total ?? 0);
                $app = (float) ($s->applied_total ?? 0);
                $bal = $adv - $app;
                $encSup = Hashids::encode($s->id);
                $html = '<div class="btn-group btn-group-sm" role="group">';
                if ($canRecord && $bal > 0.005) {
                    $html .= '<a href="'.route('purchases.supplier-advances.pay', ['encodedSupplierId' => $encSup]).'" class="btn btn-outline-primary" title="Record cash returned by supplier"><i class="bx bx-money"></i> Pay</a>';
                    $html .= '<a href="'.route('purchases.supplier-advances.expense', ['encodedSupplierId' => $encSup]).'" class="btn btn-outline-warning" title="Apply advance to expense accounts"><i class="bx bx-receipt"></i> Expense</a>';
                }
                if ($canView) {
                    $html .= '<a href="'.route('purchases.supplier-advances.statement', ['encodedSupplierId' => $encSup]).'" class="btn btn-outline-secondary" title="Statement" target="_blank" rel="noopener"><i class="bx bx-file"></i> Statement</a>';
                }
                $html .= '</div>';

                return $html;
            })
            ->orderColumn('advances_formatted', function ($query, $order) {
                $query->orderBy('advances_total', $order);
            })
            ->orderColumn('applied_formatted', function ($query, $order) {
                $query->orderBy('applied_total', $order);
            })
            ->orderColumn('balance_formatted', function ($query, $order) {
                $query->orderByRaw('(COALESCE(advances_total, 0) - COALESCE(applied_total, 0)) '.$order);
            })
            ->rawColumns(['balance_formatted', 'actions'])
            ->make(true);
    }

    /**
     * Notify company phone (Settings > Company) when a supplier advance payment is recorded.
     */
    private function sendSupplierAdvancePaymentSms(
        Supplier $supplier,
        float $amount,
        int $companyId,
        int $branchId,
        string $paymentDate,
        string $recordedByName
    ): void {
        try {
            $company = Company::query()->find($companyId);
            $phone = trim((string) ($company?->phone ?? ''));
            if ($phone === '') {
                Log::warning('Supplier advance SMS skipped: company phone not set in company settings.', [
                    'company_id' => $companyId,
                    'supplier_id' => $supplier->id,
                ]);

                return;
            }

            if (! SmsHelper::isConfigured()) {
                Log::warning('Supplier advance SMS skipped: SMS gateway not configured.');

                return;
            }

            $balance = $this->allocationService->balanceForSupplier(
                $supplier->id,
                $companyId,
                $branchId
            );

            $dateFormatted = \Carbon\Carbon::parse($paymentDate)->format('d/m/Y');

            $message = sprintf(
                'Nimempa %s kiasi cha Tsh %s tarehe %s, Baki yake ni %s, Imeingizwa na %s',
                $supplier->name,
                number_format($amount, 2),
                $dateFormatted,
                number_format($balance, 2),
                $recordedByName
            );

            $result = SmsHelper::send($phone, $message);
            if (! ($result['success'] ?? false)) {
                Log::warning('Supplier advance SMS failed.', [
                    'phone' => $phone,
                    'supplier_id' => $supplier->id,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Supplier advance SMS exception: '.$e->getMessage(), [
                'supplier_id' => $supplier->id,
            ]);
        }
    }
}
