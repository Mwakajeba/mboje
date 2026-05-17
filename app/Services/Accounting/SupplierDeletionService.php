<?php

namespace App\Services\Accounting;

use App\Models\Bill;
use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\PettyCash\PettyCashTransaction;
use App\Models\Purchase\CashPurchase;
use App\Models\Purchase\DebitNote;
use App\Models\Purchase\OpeningBalance;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseQuotation;
use App\Models\Purchase\SupplierAdvance;
use App\Models\Purchase\SupplierAdvanceDeduction;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Supplier;
use App\Services\DebitNoteService;
use App\Services\Purchase\SupplierAdvanceJournalService;
use App\Services\Purchase\SupplierOpeningBalanceJournalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SupplierDeletionService
{
    public function __construct(
        private readonly SupplierAdvanceJournalService $advanceJournalService,
        private readonly SupplierOpeningBalanceJournalService $openingBalanceJournalService,
        private readonly DebitNoteService $debitNoteService,
    ) {}

    public function deleteAllRelatedData(Supplier $supplier): void
    {
        $supplierId = (int) $supplier->id;

        foreach (CashPurchase::where('supplier_id', $supplierId)->orderBy('id')->cursor() as $purchase) {
            $this->deleteCashPurchase($purchase);
        }

        SupplierAdvanceDeduction::where('supplier_id', $supplierId)->delete();

        $this->deleteSupplierReceipts($supplierId);

        $this->deleteSupplierExpenseJournals($supplierId);

        foreach (SupplierAdvance::where('supplier_id', $supplierId)->orderBy('id')->cursor() as $advance) {
            $this->deleteSupplierAdvance($advance);
        }

        foreach (Payment::where('supplier_id', $supplierId)->orderBy('id')->cursor() as $payment) {
            $this->deletePayment($payment);
        }

        foreach (PurchaseInvoice::where('supplier_id', $supplierId)->orderBy('id')->cursor() as $invoice) {
            $this->deletePurchaseInvoice($invoice);
        }

        foreach (DebitNote::withTrashed()->where('supplier_id', $supplierId)->orderBy('id')->cursor() as $debitNote) {
            if ($debitNote->trashed()) {
                $debitNote->forceDelete();
            } else {
                $this->debitNoteService->deleteDebitNote($debitNote);
            }
        }

        foreach (PurchaseOrder::where('supplier_id', $supplierId)->orderBy('id')->cursor() as $order) {
            $this->deletePurchaseOrder($order);
        }

        foreach (PurchaseQuotation::where('supplier_id', $supplierId)->orderBy('id')->cursor() as $quotation) {
            $quotation->quotationItems()->delete();
            $quotation->delete();
        }

        foreach (OpeningBalance::where('supplier_id', $supplierId)->orderBy('id')->cursor() as $opening) {
            $this->openingBalanceJournalService->removeJournalAndGl($opening);
            $opening->delete();
        }

        Bill::where('supplier_id', $supplierId)->delete();

        PettyCashTransaction::where('supplier_id', $supplierId)->delete();

        $this->deleteOrphanSupplierJournals($supplierId);

        GlTransaction::where('supplier_id', $supplierId)->delete();

        SupplierAdvanceDeduction::where('supplier_id', $supplierId)->delete();
    }

    private function deleteCashPurchase(CashPurchase $purchase): void
    {
        \App\Models\Inventory\Movement::where('reference_type', 'cash_purchase')
            ->where('reference_id', $purchase->id)
            ->delete();

        SupplierAdvanceDeduction::where('source_type', 'cash_purchase')
            ->where('source_id', $purchase->id)
            ->delete();

        $purchase->removeJournalAndGl();

        foreach (Payment::where('reference_type', 'cash_purchase')
            ->where('reference', (string) $purchase->id)
            ->get() as $payment) {
            $this->deletePayment($payment);
        }

        $purchase->items()->delete();
        $purchase->delete();
    }

    private function deleteSupplierAdvance(SupplierAdvance $advance): void
    {
        SupplierAdvanceDeduction::where('supplier_advance_id', $advance->id)->delete();

        $this->advanceJournalService->removeJournalAndGl($advance);

        GlTransaction::where('transaction_type', 'supplier_advance')
            ->where('transaction_id', $advance->id)
            ->delete();

        if ($advance->attachment_path && Storage::disk('public')->exists($advance->attachment_path)) {
            Storage::disk('public')->delete($advance->attachment_path);
        }

        $advance->delete();
    }

    private function deleteSupplierReceipts(int $supplierId): void
    {
        $receipts = Receipt::query()
            ->where(function ($q) use ($supplierId) {
                $q->where(function ($q2) use ($supplierId) {
                    $q2->where('payee_type', 'supplier')->where('payee_id', $supplierId);
                })->orWhere(function ($q2) use ($supplierId) {
                    $q2->where('reference_type', 'supplier_advance_refund')
                        ->where('reference_number', (string) $supplierId);
                });
            })
            ->orderBy('id')
            ->get();

        foreach ($receipts as $receipt) {
            SupplierAdvanceDeduction::where('source_type', 'supplier_advance_refund')
                ->where('source_id', $receipt->id)
                ->delete();

            GlTransaction::where('transaction_type', 'receipt')
                ->where('transaction_id', $receipt->id)
                ->delete();

            ReceiptItem::where('receipt_id', $receipt->id)->delete();
            $receipt->delete();
        }
    }

    private function deleteSupplierExpenseJournals(int $supplierId): void
    {
        $journals = Journal::where('supplier_id', $supplierId)
            ->where('reference_type', 'supplier_advance_expense')
            ->orderBy('id')
            ->get();

        foreach ($journals as $journal) {
            SupplierAdvanceDeduction::where('source_type', 'supplier_advance_expense')
                ->where('source_id', $journal->id)
                ->delete();

            GlTransaction::where('transaction_type', 'journal')
                ->where('transaction_id', $journal->id)
                ->delete();

            JournalItem::where('journal_id', $journal->id)->delete();
            $journal->delete();
        }
    }

    private function deleteOrphanSupplierJournals(int $supplierId): void
    {
        $journals = Journal::where('supplier_id', $supplierId)->orderBy('id')->get();

        foreach ($journals as $journal) {
            GlTransaction::where('transaction_type', 'journal')
                ->where('transaction_id', $journal->id)
                ->delete();

            JournalItem::where('journal_id', $journal->id)->delete();
            $journal->delete();
        }
    }

    private function deletePayment(Payment $payment): void
    {
        GlTransaction::where('transaction_type', 'payment')
            ->where('transaction_id', $payment->id)
            ->delete();

        PaymentItem::where('payment_id', $payment->id)->delete();
        $payment->delete();
    }

    private function deletePurchaseInvoice(PurchaseInvoice $invoice): void
    {
        foreach (Payment::where('reference_type', 'purchase_invoice')
            ->where('reference_number', $invoice->invoice_number)
            ->where('supplier_id', $invoice->supplier_id)
            ->get() as $payment) {
            $this->deletePayment($payment);
        }

        \App\Models\Inventory\Movement::where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->delete();

        GlTransaction::where('transaction_type', 'purchase_invoice')
            ->where('transaction_id', $invoice->id)
            ->delete();

        $invoice->items()->delete();
        $invoice->delete();
    }

    private function deletePurchaseOrder(PurchaseOrder $order): void
    {
        $order->items()->each(function ($item) {
            if (method_exists($item, 'forceDelete')) {
                $item->forceDelete();
            } else {
                $item->delete();
            }
        });

        if (method_exists($order, 'forceDelete')) {
            $order->forceDelete();
        } else {
            $order->delete();
        }
    }
}
