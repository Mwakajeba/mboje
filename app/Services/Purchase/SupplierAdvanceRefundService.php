<?php

namespace App\Services\Purchase;

use App\Models\BankAccount;
use App\Models\GlTransaction;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Supplier;
use App\Services\BankReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierAdvanceRefundService
{
    public function __construct(
        private readonly SupplierAdvanceAllocationService $allocationService
    ) {}

    /**
     * Record cash returned by supplier: Receipt + Receipt items + GL (Dr bank, Cr advance) + deductions.
     */
    public function processRefund(
        Supplier $supplier,
        int $companyId,
        int $branchId,
        int $bankAccountId,
        float $amount,
        string $date,
        int $userId,
        ?string $description = null,
        ?string $reference = null
    ): Receipt {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \RuntimeException('Refund amount must be greater than zero.');
        }

        $balance = $this->allocationService->balanceForSupplier($supplier->id, $companyId, $branchId);
        if ($amount > $balance + 0.05) {
            throw new \RuntimeException(
                'Refund amount exceeds supplier advance balance ('.number_format($balance, 2).').'
            );
        }

        $slices = $this->allocationService->allocateFifo($supplier->id, $companyId, $branchId, $amount);
        $allocated = round((float) $slices->sum('amount'), 2);
        if ($slices->isEmpty() || abs($allocated - $amount) > 0.05) {
            throw new \RuntimeException('Could not allocate refund across supplier advances (insufficient balance).');
        }

        $bankAccount = BankAccount::with('chartAccount')
            ->whereKey($bankAccountId)
            ->whereHas('chartAccount.accountClassGroup', fn ($q) => $q->where('company_id', $companyId))
            ->first();

        if (! $bankAccount?->chart_account_id) {
            throw new \RuntimeException('Selected bank account has no linked chart account.');
        }

        if (BankReconciliationService::isChartAccountInCompletedReconciliation(
            (int) $bankAccount->chart_account_id,
            $date
        )) {
            throw new \RuntimeException('Cannot post: bank account is in a completed reconciliation for this date.');
        }

        foreach ($slices->pluck('debit_chart_account_id')->unique() as $chartId) {
            if (BankReconciliationService::isChartAccountInCompletedReconciliation((int) $chartId, $date)) {
                throw new \RuntimeException('Cannot post: an advance account is in a completed reconciliation for this date.');
            }
        }

        $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
        $periodLockService->validateTransactionDate($date, $companyId, 'supplier advance refund');

        return DB::transaction(function () use (
            $supplier,
            $companyId,
            $branchId,
            $bankAccount,
            $amount,
            $date,
            $userId,
            $description,
            $reference,
            $slices
        ) {
            $ref = $reference ?: $this->generateReference();
            $desc = $description ?: ('Supplier advance refund — '.$supplier->name);

            $receipt = Receipt::create([
                'reference' => $ref,
                'reference_type' => 'supplier_advance_refund',
                'reference_number' => (string) $supplier->id,
                'amount' => $amount,
                'base_amount' => $amount,
                'vat_mode' => 'NONE',
                'vat_amount' => 0,
                'wht_treatment' => 'NONE',
                'wht_rate' => 0,
                'wht_amount' => 0,
                'net_receivable' => $amount,
                'date' => $date,
                'description' => $desc,
                'user_id' => $userId,
                'bank_account_id' => $bankAccount->id,
                'payment_method' => 'bank_transfer',
                'payee_type' => 'supplier',
                'payee_id' => $supplier->id,
                'payee_name' => $supplier->name,
                'branch_id' => $branchId,
                'approved' => true,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            foreach ($slices as $slice) {
                ReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'chart_account_id' => $slice['debit_chart_account_id'],
                    'amount' => $slice['amount'],
                    'base_amount' => $slice['amount'],
                    'vat_mode' => 'NONE',
                    'vat_amount' => 0,
                    'wht_treatment' => 'NONE',
                    'wht_rate' => 0,
                    'wht_amount' => 0,
                    'net_receivable' => $slice['amount'],
                    'description' => 'Advance cleared — cash returned',
                ]);
            }

            $receipt->load('receiptItems', 'bankAccount');
            $receipt->createGlTransactions();

            $this->allocationService->recordDeductionsForSource(
                $slices,
                $supplier->id,
                $companyId,
                $branchId,
                $date,
                'supplier_advance_refund',
                $receipt->id,
                $userId,
                $desc
            );

            return $receipt;
        });
    }

    private function generateReference(): string
    {
        do {
            $ref = 'SAR-'.date('Ymd').'-'.strtoupper(Str::random(6));
        } while (Receipt::where('reference', $ref)->exists());

        return $ref;
    }
}
