<?php

namespace App\Console\Commands;

use App\Models\Sales\SalesInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApplyLatePaymentFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:apply-late-fees {--dry-run : Show what would be applied without actually applying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply late payment fees to overdue invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting late payment fees application...');

        // Get overdue invoices with late payment fees enabled
        $overdueInvoices = SalesInvoice::where('status', '!=', 'paid')
            ->where('late_payment_fees_enabled', true)
            ->where('due_date', '<', now()->toDateString())
            ->where('balance_due', '>', 0)
            ->get();

        $this->info("Found {$overdueInvoices->count()} overdue invoices with late payment fees enabled.");

        $appliedCount = 0;
        $totalFees = 0;

        foreach ($overdueInvoices as $invoice) {
            if (!$invoice->isOverdue()) { continue; }

            $feeAmount = $invoice->calculateLatePaymentFees();
            if ($feeAmount <= 0) { continue; }

            // Check if fees have already been applied for this period (guard monthly)
            $existingFees = $invoice->glTransactions()
                ->where('transaction_type', 'late_payment_fees')
                ->where('date', '>=', now()->startOfMonth())
                ->sum('amount');
            if ($existingFees > 0 && $invoice->late_payment_fees_type === 'monthly') {
                $this->line("Skipping invoice {$invoice->invoice_number} - monthly fees already applied for this period");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("Would apply TZS " . number_format($feeAmount, 2) . " late payment fees to invoice {$invoice->invoice_number} (Customer: {$invoice->customer->name})");
                $appliedCount++;
                $totalFees += $feeAmount;
            } else {
                // Apply fees directly (synchronously) to ensure immediate GL posting
                // This is important when called from login trigger
                try {
                    $applied = $invoice->applyLatePaymentFees();
                    if ($applied) {
                        $this->info("Applied TZS " . number_format($feeAmount, 2) . " late payment fees to invoice {$invoice->invoice_number}");
                        $appliedCount++;
                        $totalFees += $feeAmount;
                        
                        Log::info('Late payment fees applied via command', [
                            'invoice_number' => $invoice->invoice_number,
                            'amount' => $feeAmount,
                        ]);
                    } else {
                        $this->line("Skipped invoice {$invoice->invoice_number} - fees already applied or conditions not met");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to apply late payment fees to invoice {$invoice->invoice_number}: " . $e->getMessage());
                    Log::error('Failed to apply late payment fees via command', [
                        'invoice_number' => $invoice->invoice_number,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run completed. Would apply late payment fees to {$appliedCount} invoices totaling TZS " . number_format($totalFees, 2));
        } else {
            $this->info("Completed! Applied late payment fees to {$appliedCount} invoices totaling TZS " . number_format($totalFees, 2));
        }

        return 0;
    }
}
