<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ChartAccount;
use App\Models\Purchase\SupplierAdvance;
use App\Models\Purchase\SupplierAdvanceDeduction;
use App\Models\Supplier;
use App\Services\BankReconciliationService;
use App\Services\Purchase\SupplierAdvanceStatementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class SupplierAdvanceController extends Controller
{
    /** Debit GL line: only chart accounts whose trimmed code starts with this (e.g. 1100, 11001). */
    private const SUPPLIER_ADVANCE_DEBIT_ACCOUNT_CODE_PREFIX = '1100';

    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('view purchases'), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $advances = SupplierAdvance::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['supplier', 'debitChartAccount', 'bankAccount'])
            ->orderByDesc('advance_date')
            ->orderByDesc('id')
            ->get();

        $suppliers = Supplier::where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->withSum(['supplierAdvances as advances_total' => function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)
                    ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
            }], 'amount')
            ->withSum(['supplierAdvanceDeductions as applied_total' => function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)
                    ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
            }], 'amount')
            ->orderBy('name')
            ->get();

        if (! $request->boolean('all')) {
            $suppliers = $suppliers->filter(function ($s) {
                return ((float) ($s->advances_total ?? 0)) > 0 || ((float) ($s->applied_total ?? 0)) > 0;
            })->values();
        }

        return view('purchases.supplier-advances.index', compact('advances', 'suppliers', 'branchId'));
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
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Supplier advance recorded and posted to the general ledger.');
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

        return view('purchases.supplier-advances.edit', compact('advance', 'bankAccounts', 'suppliers', 'chartAccounts'));
    }

    public function update(Request $request, string $encodedId)
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $advance = $this->advanceForEncodedId($encodedId);

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

        try {
            DB::beginTransaction();
            $this->assertAdvanceGlCanBeReversed($advance);

            $advance->loadMissing(['bankAccount.chartAccount', 'debitChartAccount']);
            $this->assertChartsNotInCompletedReconciliation($advance->bankAccount?->chart_account_id, $advance->advance_date);
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

    public function statement(string $encodedSupplierId)
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

        $statement = app(SupplierAdvanceStatementService::class)->buildForSupplier(
            $supplier->id,
            $companyId,
            $branchId ? (int) $branchId : null
        );

        $lines = $statement['lines']->map(function (array $line) {
            $line['debit'] = $line['paid'];
            $line['credit'] = $line['deducted'];

            return $line;
        });
        $totals = $statement['totals'];

        return view('purchases.supplier-advances.statement', compact('supplier', 'lines', 'totals'));
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

    private function assertChartsNotInCompletedReconciliation(?int $chartAccountId, $transactionDate): void
    {
        if (! $chartAccountId) {
            return;
        }
        if (BankReconciliationService::isChartAccountInCompletedReconciliation($chartAccountId, $transactionDate)) {
            throw new \RuntimeException('Cannot change or delete: a chart account used on this voucher is in a completed bank reconciliation for this date.');
        }
    }
}
