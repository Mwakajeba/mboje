<?php

namespace App\Http\Controllers\Api\Machinga;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Purchase\SupplierAdvance;
use App\Models\Purchase\SupplierAdvanceDeduction;
use App\Models\Purchase\SupplierAdvanceManunuziEntry;
use App\Models\Supplier;
use App\Services\Purchase\SupplierAdvanceAllocationService;
use App\Services\Purchase\SupplierAdvanceRefundService;
use App\Services\Purchase\SupplierAdvanceStatementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class MachingaSupplierAdvanceController extends Controller
{
    public function __construct(
        private readonly SupplierAdvanceAllocationService $allocationService,
        private readonly SupplierAdvanceStatementService $statementService,
        private readonly SupplierAdvanceRefundService $refundService
    ) {}

    public function balances(Request $request): JsonResponse
    {
        abort_unless(user_can_view_wamachinga_purchases($request->user()), 403);

        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branchId = $this->branchId($request, $user);

        $query = Supplier::query()
            ->where('company_id', $companyId)
            ->visibleInBranch($branchId)
            ->withSum(['supplierAdvances as advances_total' => function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)
                    ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
            }], 'amount')
            ->withSum(['supplierAdvanceDeductions as applied_total' => function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)
                    ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
            }], 'amount')
            ->where(function ($q) use ($companyId, $branchId) {
                $q->whereHas('supplierAdvances', function ($a) use ($companyId, $branchId) {
                    $a->where('company_id', $companyId)
                        ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
                })->orWhereHas('supplierAdvanceDeductions', function ($d) use ($companyId, $branchId) {
                    $d->where('company_id', $companyId)
                        ->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId));
                });
            })
            ->orderBy('name');

        $canRecord = user_can_record_wamachinga_purchases($user);

        $rows = $query->get()->map(function (Supplier $s) use ($canRecord) {
            $adv = (float) ($s->advances_total ?? 0);
            $app = (float) ($s->applied_total ?? 0);
            $balance = round($adv - $app, 2);

            return [
                'id' => $s->id,
                'encoded_id' => Hashids::encode($s->id),
                'name' => $s->name,
                'advances_total' => round($adv, 2),
                'applied_total' => round($app, 2),
                'balance' => $balance,
                'can_lipa' => $canRecord && $balance > 0.005,
                'can_manunuzi' => $canRecord,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => ['can_record' => $canRecord],
        ]);
    }

    public function suppliers(Request $request): JsonResponse
    {
        abort_unless(user_can_view_wamachinga_purchases($request->user()), 403);

        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branchId = $this->branchId($request, $user);

        $suppliers = Supplier::query()
            ->where('company_id', $companyId)
            ->visibleInBranch($branchId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Supplier $s) => [
                'id' => $s->id,
                'encoded_id' => Hashids::encode($s->id),
                'name' => $s->name,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    public function statement(Request $request, int $supplierId): JsonResponse
    {
        abort_unless(user_can_view_wamachinga_purchases($request->user()), 403);

        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branchId = $this->branchId($request, $user);

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($supplierId);

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        if ($fromDate && $toDate) {
            $statement = $this->statementService->buildForSupplierPeriod(
                $supplier->id,
                $companyId,
                $branchId,
                $fromDate,
                $toDate
            );
        } else {
            $statement = $this->statementService->buildForSupplier($supplier->id, $companyId, $branchId);
        }

        $manunuziEntries = SupplierAdvanceManunuziEntry::query()
            ->where('company_id', $companyId)
            ->where('supplier_id', $supplier->id)
            ->when($branchId, fn ($q) => $q->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            }))
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('entry_date', [$fromDate, $toDate]))
            ->with('user:id,name')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $matumiziLines = $manunuziEntries->map(fn ($e) => [
            'date' => $e->entry_date->format('Y-m-d'),
            'description' => $e->maelezo,
            'amount' => (float) $e->kiasi,
            'performed_by' => $e->user?->name ?? '—',
            'entry_id' => $e->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => ['id' => $supplier->id, 'name' => $supplier->name],
                'totals' => $statement['totals'] ?? [],
                'period' => $statement['period'] ?? null,
                'malipo_lines' => collect($statement['malipo_lines'] ?? [])->map(fn ($l) => [
                    'date' => $l['date']->format('Y-m-d'),
                    'description' => $l['description'],
                    'amount' => (float) ($l['paid'] ?? 0),
                    'performed_by' => $l['performed_by'] ?? '—',
                ])->values(),
                'matumizi_lines' => $matumiziLines,
                'opening_row' => ! empty($statement['opening_row']) ? [
                    'date' => $statement['opening_row']['date']->format('Y-m-d'),
                    'description' => $statement['opening_row']['description'],
                    'balance' => (float) $statement['opening_row']['balance'],
                ] : null,
                'closing_row' => ! empty($statement['closing_row']) ? [
                    'date' => $statement['closing_row']['date']->format('Y-m-d'),
                    'description' => $statement['closing_row']['description'],
                    'balance' => (float) $statement['closing_row']['balance'],
                ] : null,
            ],
        ]);
    }

    public function storeManunuzi(Request $request, int $supplierId): JsonResponse
    {
        abort_unless(user_can_record_wamachinga_purchases($request->user()), 403);

        $validated = $request->validate([
            'maelezo' => ['required', 'string', 'max:2000'],
            'kiasi' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
        ]);

        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branchId = $this->branchId($request, $user);

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($supplierId);
        $kiasi = round((float) $validated['kiasi'], 2);

        DB::transaction(function () use ($validated, $companyId, $branchId, $supplier, $user, $kiasi) {
            $entry = SupplierAdvanceManunuziEntry::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'supplier_id' => $supplier->id,
                'entry_date' => $validated['entry_date'],
                'maelezo' => $validated['maelezo'],
                'kiasi' => $kiasi,
                'user_id' => $user->id,
            ]);

            SupplierAdvanceDeduction::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'supplier_id' => $supplier->id,
                'amount' => $kiasi,
                'deduction_date' => $validated['entry_date'],
                'source_type' => 'supplier_advance_manunuzi',
                'source_id' => $entry->id,
                'description' => $validated['maelezo'],
                'user_id' => $user->id,
            ]);
        });

        $balance = $this->allocationService->balanceForSupplier($supplier->id, $companyId, $branchId);

        return response()->json([
            'success' => true,
            'message' => 'Manunuzi yamehifadhiwa.',
            'data' => ['balance' => round($balance, 2)],
        ]);
    }

    public function payForm(Request $request, int $supplierId): JsonResponse
    {
        abort_unless(user_can_record_wamachinga_purchases($request->user()), 403);

        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branchId = $this->branchId($request, $user);

        $supplier = $this->findSupplier($supplierId, $companyId, $branchId);
        $balance = $this->allocationService->balanceForSupplier($supplier->id, $companyId, $branchId);

        if ($balance <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Mkusanyaji huyu hana salio la malipo ya awali la kulipia.',
            ], 422);
        }

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', fn ($q) => $q->where('company_id', $companyId))
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->where('is_all_branches', true)->orWhere('branch_id', $branchId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (BankAccount $b) => ['id' => $b->id, 'name' => $b->name])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => ['id' => $supplier->id, 'name' => $supplier->name],
                'balance' => round($balance, 2),
                'bank_accounts' => $bankAccounts,
            ],
        ]);
    }

    public function storePay(Request $request, int $supplierId): JsonResponse
    {
        abort_unless(user_can_record_wamachinga_purchases($request->user()), 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:64'],
        ], [
            'date.required' => 'Tarehe inahitajika.',
            'bank_account_id.required' => 'Chagua akaunti ya benki.',
            'amount.required' => 'Kiasi kinahitajika.',
            'description.required' => 'Maelezo yanahitajika.',
        ]);

        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branchId = $this->branchId($request, $user);

        if (! $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Tawi halijachaguliwa.',
            ], 422);
        }

        if (! $this->bankValidForBranch($companyId, $branchId, (int) $validated['bank_account_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akaunti ya benki si sahihi kwa tawi au kampuni hii.',
                'errors' => ['bank_account_id' => ['Akaunti ya benki si sahihi.']],
            ], 422);
        }

        $supplier = $this->findSupplier($supplierId, $companyId, $branchId);
        $balance = $this->allocationService->balanceForSupplier($supplier->id, $companyId, $branchId);
        $amount = round((float) $validated['amount'], 2);

        if ($amount > $balance + 0.05) {
            return response()->json([
                'success' => false,
                'message' => 'Kiasi hakiwezi kuzidi salio la malipo ya awali ('.number_format($balance, 2).').',
                'errors' => ['amount' => ['Kiasi ni kikubwa kuliko salio.']],
            ], 422);
        }

        try {
            $this->refundService->processRefund(
                $supplier,
                $companyId,
                $branchId,
                (int) $validated['bank_account_id'],
                $amount,
                $validated['date'],
                (int) $user->id,
                $validated['description'],
                $validated['reference'] ?? null
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $newBalance = $this->allocationService->balanceForSupplier($supplier->id, $companyId, $branchId);

        return response()->json([
            'success' => true,
            'message' => 'Malipo yamehifadhiwa. Benki imeongezwa na salio limepungua.',
            'data' => ['balance' => round($newBalance, 2)],
        ]);
    }

    private function findSupplier(int $supplierId, int $companyId, ?int $branchId): Supplier
    {
        return Supplier::query()
            ->where('company_id', $companyId)
            ->visibleInBranch($branchId)
            ->findOrFail($supplierId);
    }

    private function bankValidForBranch(int $companyId, int $branchId, int $bankAccountId): bool
    {
        return BankAccount::whereKey($bankAccountId)
            ->whereHas('chartAccount.accountClassGroup', fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true)
                    ->orWhere('branch_id', $branchId);
            })
            ->exists();
    }

    private function branchId(Request $request, $user): ?int
    {
        $id = $request->header('X-Branch-Id') ?? session('branch_id') ?? $user->branch_id;

        return $id ? (int) $id : null;
    }
}
