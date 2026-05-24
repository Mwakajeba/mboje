<?php

namespace App\Http\Controllers\Purchase;

use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\ChartAccount;
use App\Models\Purchase\SupplierAdvance;
use App\Models\Purchase\SupplierAdvanceDeduction;
use App\Models\Purchase\SupplierAdvanceManunuziEntry;
use App\Models\Purchase\SupplierAdvanceStockLine;
use App\Models\Purchase\SupplierAdvanceStockRecord;
use App\Models\Supplier;
use App\Services\BankReconciliationService;
use App\Services\Purchase\SupplierAdvanceAllocationService;
use App\Services\Purchase\SupplierAdvanceJournalService;
use App\Services\Purchase\SupplierAdvanceExpenseService;
use App\Services\Purchase\SupplierAdvanceRefundService;
use App\Services\InventoryValueService;
use App\Services\Purchase\SupplierAdvanceStatementDeletionService;
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
        abort_unless(user_can_view_wamachinga_purchases(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        if ($request->ajax()) {
            if ($request->input('table') === 'balances') {
                return $this->balancesDataTable($request, $companyId, $branchId);
            }

            return $this->advancesDataTable($request, $companyId, $branchId);
        }

        return view('purchases.supplier-advances.index');
    }

    public function create()
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

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
        abort_unless(user_can_record_wamachinga_purchases(), 403);

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
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $this->mergeNormalizedAmount($request);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'advance_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'debit_chart_account_id' => 'required|exists:chart_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:2000',
            'reference' => 'nullable|string|max:64',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'sms_message_type' => 'required|in:malipo,mauzo',
        ], [
            'supplier_id.required' => 'Chagua msambazaji.',
            'supplier_id.exists' => 'Msambazaji aliyechaguliwa haipo.',
            'advance_date.required' => 'Tarehe inahitajika.',
            'advance_date.date' => 'Tarehe si sahihi.',
            'bank_account_id.required' => 'Chagua akaunti ya benki / fedha.',
            'bank_account_id.exists' => 'Akaunti ya benki iliyochaguliwa haipo.',
            'debit_chart_account_id.required' => 'Chagua akaunti ya chati ya malipo ya awali.',
            'debit_chart_account_id.exists' => 'Akaunti ya chati iliyochaguliwa haipo.',
            'amount.required' => 'Kiasi kinahitajika.',
            'amount.numeric' => 'Kiasi lazima kiwe nambari.',
            'amount.min' => 'Kiasi lazima kiwe zaidi ya sifuri.',
            'description.required' => 'Maelezo yanahitajika.',
            'attachment.mimes' => 'Kiambatisho lazima kiwe PDF au picha.',
            'attachment.max' => 'Kiambatisho hakiwezi kuzidi 5 MB.',
            'sms_message_type.required' => 'Chagua aina ya ujumbe wa SMS (Malipo au Mauzo).',
            'sms_message_type.in' => 'Aina ya ujumbe wa SMS si sahihi.',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $resolvedBranchId = $user->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        if (! $resolvedBranchId) {
            return back()->withInput()->with('error', 'Tawi halijachaguliwa. Chagua tawi kisha jaribu tena.');
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
                (string) $user->name,
                $validated['sms_message_type']
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Malipo mapya yamehifadhiwa na yameandikwa kwenye jarida kuu.');
    }

    public function storeOpening(Request $request)
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $this->mergeNormalizedAmount($request);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'advance_date' => 'required|date',
            'debit_chart_account_id' => 'required|exists:chart_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:2000',
            'reference' => 'nullable|string|max:64',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'supplier_id.required' => 'Chagua msambazaji.',
            'supplier_id.exists' => 'Msambazaji aliyechaguliwa haipo.',
            'advance_date.required' => 'Tarehe inahitajika.',
            'advance_date.date' => 'Tarehe si sahihi.',
            'debit_chart_account_id.required' => 'Chagua akaunti ya chati ya malipo ya awali.',
            'debit_chart_account_id.exists' => 'Akaunti ya chati iliyochaguliwa haipo.',
            'amount.required' => 'Kiasi kinahitajika.',
            'amount.numeric' => 'Kiasi lazima kiwe nambari.',
            'amount.min' => 'Kiasi lazima kiwe zaidi ya sifuri.',
            'description.required' => 'Maelezo yanahitajika.',
            'attachment.mimes' => 'Kiambatisho lazima kiwe PDF au picha.',
            'attachment.max' => 'Kiambatisho hakiwezi kuzidi 5 MB.',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $resolvedBranchId = $user->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        if (! $resolvedBranchId) {
            return back()->withInput()->with('error', 'Tawi halijachaguliwa. Chagua tawi kisha jaribu tena.');
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
            ->with('success', 'Malipo ya nyuma yamehifadhiwa na yameandikwa kupitia jarida na jarida kuu.');
    }

    public function edit(string $encodedId)
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

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
        abort_unless(user_can_record_wamachinga_purchases(), 403);

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
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $advance = $this->advanceForEncodedId($encodedId);

        if ($advance->advanceDeductions()->exists()) {
            return redirect()
                ->route('purchases.supplier-advances.index')
                ->with('error', 'Haiwezi kufuta: salio la malipo hili limetumika tayari (manunuzi, matumizi, au malipo).');
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
        abort_unless(user_can_view_wamachinga_purchases(), 403);

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

        $malipoLines = collect($statement['malipo_lines'] ?? [])->map(function (array $line) {
            $line['paid'] = (float) ($line['paid'] ?? 0);
            $line['deducted'] = (float) ($line['deducted'] ?? 0);

            return $line;
        });
        $manunuziEntryQuery = SupplierAdvanceManunuziEntry::query()
            ->where('company_id', $companyId)
            ->where('supplier_id', $supplier->id)
            ->when($branchId, fn ($q) => $q->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            }))
            ->with('user:id,name');

        if ($fromDate && $toDate) {
            $manunuziEntryQuery->whereBetween('entry_date', [$fromDate, $toDate]);
        }

        $manunuziEntries = $manunuziEntryQuery
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $matumiziLines = $manunuziEntries->map(fn (SupplierAdvanceManunuziEntry $entry) => [
            'date' => $entry->entry_date,
            'description' => $entry->maelezo,
            'deducted' => (float) $entry->kiasi,
            'performed_by' => $entry->user?->name ?? '—',
            'can_delete' => true,
            'entry_id' => $entry->id,
        ])->values();

        $manunuziLines = collect();

        $totals = $statement['totals'];
        $period = $statement['period'] ?? null;
        $openingBalance = $statement['opening_balance'] ?? ($totals['opening_balance'] ?? null);
        $openingRow = $statement['opening_row'] ?? null;
        $closingRow = $statement['closing_row'] ?? null;

        $malipoTotal = round((float) $malipoLines->sum('paid'), 2);
        $matumiziTotal = round((float) $matumiziLines->sum('deducted'), 2);
        $manunuziTotal = round((float) $manunuziLines->sum('deducted'), 2);

        $canDeleteStatementItems = user_can_record_wamachinga_purchases();

        return view('purchases.supplier-advances.statement', compact(
            'supplier',
            'totals',
            'period',
            'openingBalance',
            'openingRow',
            'malipoLines',
            'matumiziLines',
            'manunuziLines',
            'closingRow',
            'malipoTotal',
            'matumiziTotal',
            'manunuziTotal',
            'canDeleteStatementItems',
            'encodedSupplierId'
        ));
    }

    public function destroyStatementExpense(string $encodedSupplierId, string $encodedJournalId)
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $companyId = (int) Auth::user()->company_id;
        $branchId = session('branch_id') ?? Auth::user()->branch_id;
        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $branchId);
        $journalId = $this->decodeId($encodedJournalId);

        app(SupplierAdvanceStatementDeletionService::class)->deleteExpenseJournal(
            $supplier,
            $journalId,
            $companyId,
            $branchId ? (int) $branchId : null
        );

        return redirect()
            ->route('purchases.supplier-advances.statement', ['encodedSupplierId' => $encodedSupplierId])
            ->with('success', 'Matumizi yamefutwa.');
    }

    public function destroyStatementStock(string $encodedSupplierId, string $encodedStockRecordId)
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $companyId = (int) Auth::user()->company_id;
        $branchId = session('branch_id') ?? Auth::user()->branch_id;
        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $branchId);
        $recordId = $this->decodeId($encodedStockRecordId);

        app(SupplierAdvanceStatementDeletionService::class)->deleteStockRecord(
            $supplier,
            $recordId,
            $companyId,
            $branchId ? (int) $branchId : null
        );

        return redirect()
            ->route('purchases.supplier-advances.statement', ['encodedSupplierId' => $encodedSupplierId])
            ->with('success', 'Stoo imefutwa.');
    }

    public function storeManunuzi(Request $request, string $encodedSupplierId)
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $this->mergeNormalizedKiasi($request);

        $validated = $request->validate([
            'maelezo' => ['required', 'string', 'max:2000'],
            'kiasi' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
        ], [
            'maelezo.required' => 'Maelezo yanahitajika.',
            'kiasi.required' => 'Kiasi kinahitajika.',
            'kiasi.numeric' => 'Kiasi lazima kiwe nambari.',
            'kiasi.min' => 'Kiasi lazima kiwe zaidi ya sifuri.',
            'entry_date.required' => 'Tarehe inahitajika.',
            'entry_date.date' => 'Tarehe si sahihi.',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $branchId);

        $entryDate = $validated['entry_date'] ?? now()->toDateString();
        $kiasi = round((float) $validated['kiasi'], 2);

        DB::transaction(function () use ($validated, $companyId, $branchId, $supplier, $user, $entryDate, $kiasi) {
            $entry = SupplierAdvanceManunuziEntry::create([
                'company_id' => $companyId,
                'branch_id' => $branchId ? (int) $branchId : null,
                'supplier_id' => $supplier->id,
                'entry_date' => $entryDate,
                'maelezo' => $validated['maelezo'],
                'kiasi' => $kiasi,
                'user_id' => $user->id,
            ]);

            SupplierAdvanceDeduction::create([
                'company_id' => $companyId,
                'branch_id' => $branchId ? (int) $branchId : null,
                'supplier_id' => $supplier->id,
                'amount' => $kiasi,
                'deduction_date' => $entryDate,
                'source_type' => 'supplier_advance_manunuzi',
                'source_id' => $entry->id,
                'description' => $validated['maelezo'],
                'user_id' => $user->id,
            ]);
        });

        $this->sendSupplierAdvanceManunuziSms(
            $supplier,
            $validated['maelezo'],
            $kiasi,
            $companyId,
            $branchId ? (int) $branchId : null,
            $entryDate,
            (string) ($user->name ?? '—')
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Manunuzi yamehifadhiwa.',
            ]);
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Manunuzi yamehifadhiwa kwa '.$supplier->name.'.');
    }

    public function destroyStatementManunuzi(string $encodedSupplierId, string $encodedEntryId)
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $companyId = (int) Auth::user()->company_id;
        $branchId = session('branch_id') ?? Auth::user()->branch_id;
        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $branchId);
        $entryId = $this->decodeId($encodedEntryId);

        $entry = SupplierAdvanceManunuziEntry::query()
            ->where('company_id', $companyId)
            ->where('supplier_id', $supplier->id)
            ->when($branchId, fn ($q) => $q->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            }))
            ->findOrFail($entryId);

        DB::transaction(function () use ($entry, $companyId, $supplier) {
            SupplierAdvanceDeduction::query()
                ->where('company_id', $companyId)
                ->where('supplier_id', $supplier->id)
                ->where('source_type', 'supplier_advance_manunuzi')
                ->where('source_id', $entry->id)
                ->delete();

            $entry->delete();
        });

        return redirect()
            ->route('purchases.supplier-advances.statement', ['encodedSupplierId' => $encodedSupplierId])
            ->with('success', 'Manunuzi yamefutwa.');
    }

    private function decodeId(string $encoded): int
    {
        $decoded = Hashids::decode($encoded);
        if (empty($decoded[0])) {
            abort(404);
        }

        return (int) $decoded[0];
    }

    public function storeStock(Request $request, string $encodedSupplierId)
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $decoded = Hashids::decode($encodedSupplierId);
        if (empty($decoded[0])) {
            abort(404);
        }

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $supplier = Supplier::where('company_id', $companyId)->findOrFail((int) $decoded[0]);

        $lineRules = [];
        foreach (SupplierAdvanceStockLine::orderedTypes() as $type) {
            $lineRules['lines.'.$type.'.idadi'] = ['required', 'string', 'max:255'];
            $lineRules['lines.'.$type.'.thamani'] = ['required', 'numeric', 'min:0'];
        }

        $validated = $request->validate(array_merge([
            'bidhaa' => ['required', 'string', 'max:255'],
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array'],
        ], $lineRules), [
            'bidhaa.required' => 'Bidhaa inahitajika.',
            'entry_date.required' => 'Tarehe inahitajika.',
            'entry_date.date' => 'Tarehe si sahihi.',
            'lines.required' => 'Weka idadi na thamani kwa kila aina ya muamala.',
        ]);

        $record = DB::transaction(function () use ($validated, $companyId, $branchId, $supplier, $user) {
            $record = SupplierAdvanceStockRecord::create([
                'company_id' => $companyId,
                'branch_id' => $branchId ? (int) $branchId : null,
                'supplier_id' => $supplier->id,
                'bidhaa' => $validated['bidhaa'],
                'entry_date' => $validated['entry_date'],
                'description' => $validated['description'] ?? null,
                'user_id' => $user->id,
            ]);

            foreach (SupplierAdvanceStockLine::orderedTypes() as $type) {
                $line = $validated['lines'][$type] ?? [];
                SupplierAdvanceStockLine::create([
                    'stock_record_id' => $record->id,
                    'transaction_type' => $type,
                    'idadi' => $line['idadi'] ?? '',
                    'thamani' => $line['thamani'] ?? 0,
                ]);
            }

            return $record->load('lines');
        });

        $this->sendSupplierAdvanceStockSms($supplier, $record, $companyId);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Stoo imehifadhiwa.',
            ]);
        }

        return redirect()
            ->route('purchases.supplier-advances.index')
            ->with('success', 'Stoo imehifadhiwa kwa '.$supplier->name.'.');
    }

    public function pay(string $encodedSupplierId)
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

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
                ->with('error', 'Msambazaji huyu hana salio la malipo ya awali la kulipia.');
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
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $this->mergeNormalizedAmount($request);

        $validated = $request->validate([
            'date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:2000',
            'reference' => 'nullable|string|max:64',
        ], [
            'date.required' => 'Tarehe inahitajika.',
            'date.date' => 'Tarehe si sahihi.',
            'bank_account_id.required' => 'Chagua akaunti ya benki / fedha.',
            'bank_account_id.exists' => 'Akaunti ya benki iliyochaguliwa haipo.',
            'amount.required' => 'Kiasi kinahitajika.',
            'amount.numeric' => 'Kiasi lazima kiwe nambari.',
            'amount.min' => 'Kiasi lazima kiwe zaidi ya sifuri.',
            'description.required' => 'Maelezo yanahitajika.',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $resolvedBranchId = $user->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        if (! $resolvedBranchId) {
            return back()->withInput()->with('error', 'Tawi halijachaguliwa. Chagua tawi kisha jaribu tena.');
        }

        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $resolvedBranchId);

        $bankErr = $this->validateBankForBranch($companyId, (int) $resolvedBranchId, (int) $validated['bank_account_id']);
        if ($bankErr instanceof RedirectResponse) {
            return $bankErr;
        }

        $balance = $this->allocationService->balanceForSupplier($supplier->id, $companyId, (int) $resolvedBranchId);
        if ((float) $validated['amount'] > $balance + 0.05) {
            return back()->withInput()->withErrors([
                'amount' => 'Kiasi hakiwezi kuzidi salio la malipo ya awali ('.number_format($balance, 2).').',
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
            ->with('success', 'Malipo yamehifadhiwa. Benki imeongezwa na salio la malipo ya awali limepungua.');
    }

    public function expense(string $encodedSupplierId)
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

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
                ->with('error', 'Msambazaji huyu hana salio la malipo ya awali la kutumia kwa matumizi.');
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
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        $this->mergeNormalizedLineItemAmounts($request);

        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:2000',
            'reference' => 'nullable|string|max:64',
            'line_items' => 'required|array|min:1',
            'line_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'line_items.*.amount' => 'required|numeric|min:0.01',
            'line_items.*.description' => 'nullable|string|max:500',
        ], [
            'date.required' => 'Tarehe inahitajika.',
            'date.date' => 'Tarehe si sahihi.',
            'description.required' => 'Maelezo yanahitajika.',
            'line_items.required' => 'Ongeza angalau mstari mmoja wa matumizi.',
            'line_items.min' => 'Ongeza angalau mstari mmoja wa matumizi.',
            'line_items.*.chart_account_id.required' => 'Chagua akaunti ya matumizi kwa kila mstari.',
            'line_items.*.chart_account_id.exists' => 'Akaunti ya matumizi iliyochaguliwa haipo.',
            'line_items.*.amount.required' => 'Kiasi kinahitajika kwa kila mstari.',
            'line_items.*.amount.numeric' => 'Kiasi lazima kiwe nambari.',
            'line_items.*.amount.min' => 'Kiasi lazima kiwe zaidi ya sifuri.',
        ]);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $resolvedBranchId = $user->branch_id
            ?? (session('branch_id') ?: null)
            ?? (function_exists('current_branch_id') ? current_branch_id() : null);
        if (! $resolvedBranchId) {
            return back()->withInput()->with('error', 'Tawi halijachaguliwa. Chagua tawi kisha jaribu tena.');
        }

        $supplier = $this->supplierForEncodedId($encodedSupplierId, $companyId, $resolvedBranchId);

        $lineItems = [];
        foreach ($validated['line_items'] as $row) {
            $chartId = (int) $row['chart_account_id'];
            if (! $this->expenseChartAccountIsAllowed($companyId, $chartId)) {
                return back()->withInput()->withErrors([
                    'line_items' => 'Akaunti moja au zaidi haziruhusiwi kama akaunti za matumizi kwa kampuni hii.',
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
                'line_items' => 'Jumla haiwezi kuzidi salio la malipo ya awali ('.number_format($balance, 2).').',
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
            ->with('success', 'Matumizi yamewekwa. Jarida limeandikwa na salio la malipo ya awali limepungua.');
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
                'debit_chart_account_id' => 'Akaunti lazima iwe na msimbo unaanza na '.self::SUPPLIER_ADVANCE_DEBIT_ACCOUNT_CODE_PREFIX.'.',
            ]);
        }

        $retainedId = $this->advanceJournalService->resolveRetainedEarningsAccountId($companyId);
        if (! $retainedId) {
            return back()->withInput()->withErrors([
                'error' => 'Akaunti ya mapato yaliyohifadhiwa haijasanidiwa. Weka retained_earnings_account_id kwenye mipangilio.',
            ]);
        }

        if ($debitChartAccountId === $retainedId) {
            return back()->withInput()->withErrors([
                'debit_chart_account_id' => 'Akaunti ya malipo ya awali haiwezi kuwa sawa na akaunti ya mapato yaliyohifadhiwa.',
            ]);
        }

        return null;
    }

    private function updateOpeningAdvance(Request $request, SupplierAdvance $advance): RedirectResponse
    {
        abort_unless(user_can_record_wamachinga_purchases(), 403);

        if ($advance->hasCashPurchaseDeductions()) {
            return back()->withInput()->with('error', 'Haiwezi kuhariri: malipo haya yametumika kwenye ununuzi wa cash.');
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
            return back()->withInput()->withErrors(['bank_account_id' => 'Akaunti ya benki si sahihi kwa tawi au kampuni hii.']);
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
            return back()->withInput()->withErrors(['bank_account_id' => 'Akaunti ya benki si sahihi kwa tawi au kampuni hii.']);
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
            return back()->withInput()->withErrors(['debit_chart_account_id' => 'Akaunti lazima iwe na msimbo unaanza na '.self::SUPPLIER_ADVANCE_DEBIT_ACCOUNT_CODE_PREFIX.'.']);
        }

        $bankAccount = BankAccount::with('chartAccount')->find($validated['bank_account_id']);
        if ($bankAccount && (int) $bankAccount->chart_account_id === (int) $validated['debit_chart_account_id']) {
            return back()->withInput()->withErrors(['debit_chart_account_id' => 'Akaunti ya malipo ya awali haiwezi kuwa sawa na akaunti ya benki iliyochaguliwa.']);
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

    private function mergeNormalizedKiasi(Request $request): void
    {
        if (! $request->has('kiasi')) {
            return;
        }
        $raw = (string) $request->input('kiasi', '');
        $clean = preg_replace('/[^\d.]/', '', str_replace(',', '', $raw));
        if ($clean === '') {
            $request->merge(['kiasi' => null]);

            return;
        }
        $request->merge(['kiasi' => $clean]);
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
        $canRecord = user_can_record_wamachinga_purchases($user);
        $canView = user_can_view_wamachinga_purchases($user);

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
        $canRecord = user_can_record_wamachinga_purchases($user);
        $canView = user_can_view_wamachinga_purchases($user);
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
                if ($canRecord) {
                    // Weka stoo — disabled; use Hesabu za Kila Siku (Wafanyakazi) instead
                    // $html .= '<button type="button" class="btn btn-outline-success btn-weka-stoo" title="Weka stoo"'
                    //     .' data-supplier-id="'.e((string) $s->id).'"'
                    //     .' data-encoded-supplier-id="'.e($encSup).'"'
                    //     .' data-supplier-name="'.e($s->name).'"'
                    //     .'><i class="bx bx-package"></i> Weka stoo</button>';
                    if ($bal > 0.005) {
                        $html .= '<a href="'.route('purchases.supplier-advances.pay', ['encodedSupplierId' => $encSup]).'" class="btn btn-outline-primary" title="Rekodi fedha zilirudishwa na msambazaji"><i class="bx bx-money"></i> Lipa</a>';
                    }
                    $html .= '<button type="button" class="btn btn-outline-warning btn-ingiza-manunuzi" title="Ingiza manunuzi"'
                        .' data-encoded-supplier-id="'.e($encSup).'"'
                        .' data-supplier-name="'.e($s->name).'"'
                        .'><i class="bx bx-cart"></i> Ingiza Manunuzi</button>';
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

    private function sendSupplierAdvanceStockSms(
        Supplier $supplier,
        SupplierAdvanceStockRecord $record,
        int $companyId
    ): void {
        try {
            $company = Company::query()->find($companyId);
            $phone = trim((string) ($company?->phone ?? ''));
            if ($phone === '') {
                Log::warning('Supplier advance stock SMS skipped: company phone not set.', [
                    'company_id' => $companyId,
                    'supplier_id' => $supplier->id,
                ]);

                return;
            }

            if (! SmsHelper::isConfigured()) {
                Log::warning('Supplier advance stock SMS skipped: SMS gateway not configured.');

                return;
            }

            $linesByType = $record->lines->keyBy('transaction_type');
            $fmtQty = fn ($type) => (string) ($linesByType->get($type)?->idadi ?? '');
            $fmtVal = fn ($type) => number_format((float) ($linesByType->get($type)?->thamani ?? 0), 2);

            $dateFormatted = $record->entry_date->format('d/m/Y');

            $message = sprintf(
                'Taarifa ya %s ya %s tarehe %s, Zilizouzwa %s za thamani ya %s, Zizonunuliwa %s za thamani ya %s na Baki %s za thamani ya %s',
                $record->bidhaa,
                $supplier->name,
                $dateFormatted,
                $fmtQty(SupplierAdvanceStockLine::TYPE_ZILIZOUZWA),
                $fmtVal(SupplierAdvanceStockLine::TYPE_ZILIZOUZWA),
                $fmtQty(SupplierAdvanceStockLine::TYPE_ZIZONUNULIWA),
                $fmtVal(SupplierAdvanceStockLine::TYPE_ZIZONUNULIWA),
                $fmtQty(SupplierAdvanceStockLine::TYPE_BAKI),
                $fmtVal(SupplierAdvanceStockLine::TYPE_BAKI)
            );

            $result = SmsHelper::send($phone, $message);
            if (! ($result['success'] ?? false)) {
                Log::warning('Supplier advance stock SMS failed.', [
                    'phone' => $phone,
                    'supplier_id' => $supplier->id,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Supplier advance stock SMS exception: '.$e->getMessage(), [
                'supplier_id' => $supplier->id,
            ]);
        }
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
        string $recordedByName,
        string $smsMessageType = 'malipo'
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
            $amountFormatted = number_format($amount, 2);
            $balanceFormatted = number_format($balance, 2);

            if ($smsMessageType === 'mauzo') {
                $message = sprintf(
                    'Mauzo ya %s kiasi cha Tsh %s ya tarehe %s, salio jipya ni %s kabla ya kutoa matumizi. Imeingizwa na %s',
                    $supplier->name,
                    $amountFormatted,
                    $dateFormatted,
                    $balanceFormatted,
                    $recordedByName
                );
            } else {
                $message = sprintf(
                    'Nimempa %s kiasi cha Tsh %s tarehe %s, Baki yake ni %s, Imeingizwa na %s',
                    $supplier->name,
                    $amountFormatted,
                    $dateFormatted,
                    $balanceFormatted,
                    $recordedByName
                );
            }

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

    /**
     * Notify company phone when matumizi/manunuzi is recorded against a supplier advance.
     */
    private function sendSupplierAdvanceManunuziSms(
        Supplier $supplier,
        string $maelezo,
        float $kiasi,
        int $companyId,
        ?int $branchId,
        string $entryDate,
        string $recordedByName
    ): void {
        try {
            $company = Company::query()->find($companyId);
            $phone = trim((string) ($company?->phone ?? ''));
            if ($phone === '') {
                Log::warning('Supplier advance manunuzi SMS skipped: company phone not set in company settings.', [
                    'company_id' => $companyId,
                    'supplier_id' => $supplier->id,
                ]);

                return;
            }

            if (! SmsHelper::isConfigured()) {
                Log::warning('Supplier advance manunuzi SMS skipped: SMS gateway not configured.');

                return;
            }

            $balance = $this->allocationService->balanceForSupplier(
                $supplier->id,
                $companyId,
                $branchId
            );

            $maelezoLower = mb_strtolower(trim($maelezo));
            $dateFormatted = \Carbon\Carbon::parse($entryDate)->format('d/m/Y');
            $amountFormatted = number_format($kiasi, 2);
            $balanceFormatted = number_format($balance, 2);

            $message = sprintf(
                'Matumizi ya %s: %s kiasi cha Tsh %s tarehe %s, baki ni %s. Imeingizwa na %s',
                $supplier->name,
                $maelezoLower,
                $amountFormatted,
                $dateFormatted,
                $balanceFormatted,
                $recordedByName
            );

            $result = SmsHelper::send($phone, $message);
            if (! ($result['success'] ?? false)) {
                Log::warning('Supplier advance manunuzi SMS failed.', [
                    'phone' => $phone,
                    'supplier_id' => $supplier->id,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Supplier advance manunuzi SMS exception: '.$e->getMessage(), [
                'supplier_id' => $supplier->id,
            ]);
        }
    }
}
