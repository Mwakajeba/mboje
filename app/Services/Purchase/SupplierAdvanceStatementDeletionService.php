<?php

namespace App\Services\Purchase;

use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Purchase\SupplierAdvanceDeduction;
use App\Models\Purchase\SupplierAdvanceStockRecord;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierAdvanceStatementDeletionService
{
    public function deleteExpenseJournal(
        Supplier $supplier,
        int $journalId,
        int $companyId,
        ?int $branchId
    ): void {
        $journal = Journal::query()
            ->where('id', $journalId)
            ->where('supplier_id', $supplier->id)
            ->where('reference_type', 'supplier_advance_expense')
            ->firstOrFail();

        if ($branchId && $journal->branch_id && (int) $journal->branch_id !== (int) $branchId) {
            abort(403, 'Jarida hili halipo katika tawi lako.');
        }

        DB::transaction(function () use ($journal) {
            SupplierAdvanceDeduction::query()
                ->where('source_type', 'supplier_advance_expense')
                ->where('source_id', $journal->id)
                ->delete();

            GlTransaction::query()
                ->where('transaction_type', 'journal')
                ->where('transaction_id', $journal->id)
                ->delete();

            JournalItem::query()->where('journal_id', $journal->id)->delete();
            $journal->delete();
        });
    }

    public function deleteStockRecord(
        Supplier $supplier,
        int $recordId,
        int $companyId,
        ?int $branchId
    ): void {
        $record = SupplierAdvanceStockRecord::query()
            ->where('id', $recordId)
            ->where('supplier_id', $supplier->id)
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            }))
            ->firstOrFail();

        $record->delete();
    }
}
