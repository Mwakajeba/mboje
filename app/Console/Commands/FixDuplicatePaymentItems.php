<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDuplicatePaymentItems extends Command
{
    protected $signature = 'petty-cash:fix-duplicate-payment-items {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix duplicate payment items for petty cash transactions';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }
        
        // Find payments with duplicate items (same payment_id, chart_account_id, and description)
        $duplicates = PaymentItem::select('payment_id', 'chart_account_id', 'description', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_id', 'chart_account_id', 'description')
            ->having('count', '>', 1)
            ->get();
        
        if ($duplicates->isEmpty()) {
            $this->info('No duplicate payment items found.');
            return 0;
        }
        
        $this->info("Found {$duplicates->count()} sets of duplicate payment items.");
        
        $totalRemoved = 0;
        $totalAmountFixed = 0;
        
        foreach ($duplicates as $duplicate) {
            $payment = Payment::find($duplicate->payment_id);
            if (!$payment) {
                continue;
            }
            
            // Get all duplicate items
            $items = PaymentItem::where('payment_id', $duplicate->payment_id)
                ->where('chart_account_id', $duplicate->chart_account_id)
                ->where('description', $duplicate->description)
                ->orderBy('id')
                ->get();
            
            if ($items->count() <= 1) {
                continue;
            }
            
            // Keep the first one, sum amounts, and delete the rest
            $firstItem = $items->first();
            $totalAmount = $items->sum('amount');
            $itemsToDelete = $items->skip(1);
            
            $this->line("Payment #{$payment->reference}: Found {$items->count()} duplicate items for account {$duplicate->chart_account_id}");
            $this->line("  - Keeping first item (ID: {$firstItem->id})");
            $this->line("  - Combining amounts: {$firstItem->amount} + " . $itemsToDelete->sum('amount') . " = {$totalAmount}");
            
            if (!$dryRun) {
                // Update first item with combined amount
                $firstItem->update([
                    'amount' => $totalAmount,
                    'base_amount' => $totalAmount,
                    'net_payable' => $totalAmount,
                    'total_cost' => $totalAmount,
                ]);
                
                // Delete duplicate items
                $deletedCount = $itemsToDelete->count();
                PaymentItem::whereIn('id', $itemsToDelete->pluck('id'))->delete();
                
                $totalRemoved += $deletedCount;
                $totalAmountFixed += $itemsToDelete->sum('amount');
                
                $this->info("  ✓ Fixed: Removed {$deletedCount} duplicate(s), updated amount to {$totalAmount}");
            } else {
                $this->line("  [DRY RUN] Would remove {$itemsToDelete->count()} duplicate(s) and update amount to {$totalAmount}");
            }
        }
        
        if (!$dryRun) {
            $this->info("\n✓ Fixed {$totalRemoved} duplicate payment items");
            $this->info("✓ Total amount consolidated: " . number_format($totalAmountFixed, 2));
        } else {
            $this->info("\n[DRY RUN] Would fix {$totalRemoved} duplicate payment items");
        }
        
        return 0;
    }
}

