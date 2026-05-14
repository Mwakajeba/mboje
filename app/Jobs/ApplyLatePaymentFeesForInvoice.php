<?php

namespace App\Jobs;

use App\Models\Sales\SalesInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyLatePaymentFeesForInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    private $invoiceId;

    public function __construct(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    public function handle(): void
    {
        $invoice = SalesInvoice::find($this->invoiceId);
        if (!$invoice) {
            return;
        }

        if (!$invoice->late_payment_fees_enabled || !$invoice->isOverdue() || $invoice->balance_due <= 0) {
            return;
        }

        $feeAmount = $invoice->calculateLatePaymentFees();
        if ($feeAmount <= 0) {
            return;
        }

        // Prevent duplicate fees for the current period (monthly guard)
        $alreadyApplied = $invoice->glTransactions()
            ->where('transaction_type', 'late_payment_fees')
            ->where('date', '>=', now()->startOfMonth())
            ->exists();
        if ($alreadyApplied && $invoice->late_payment_fees_type === 'monthly') {
            return;
        }

        try {
            $applied = $invoice->applyLatePaymentFees();
            if ($applied) {
                Log::info('Late payment fees applied via job', [
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $feeAmount,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to apply late payment fees via job', [
                'invoice_number' => $invoice->invoice_number,
                'error' => $e->getMessage(),
            ]);
            throw $e; // let the job retry based on queue settings
        }
    }
}


