<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Sales\PosSale;
use App\Models\Sales\CashSale;
use App\Models\Inventory\Movement;
use Illuminate\Support\Facades\DB;

class UpdateTransactionsToFixMovements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:update-transactions 
                            {--item_id= : Specific item ID to fix}
                            {--location_id= : Specific location ID to fix}
                            {--date_from= : Start date (Y-m-d) - only update transactions from this date onwards}
                            {--date_to= : End date (Y-m-d) - only update transactions up to this date}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update transactions (purchase invoices, POS sales, cash sales) to recalculate inventory movements with correct balances';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $itemId = $this->option('item_id');
        $locationId = $this->option('location_id');
        $dateFrom = $this->option('date_from');
        $dateTo = $this->option('date_to');
        $dryRun = $this->option('dry-run');

        $this->info('Updating transactions to fix inventory movements...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No changes will be made');
            $this->newLine();
        }

        $updatedCount = 0;
        $errorCount = 0;

        // Update Purchase Invoices
        $this->info('Processing Purchase Invoices...');
        $purchaseQuery = PurchaseInvoice::query();
        
        if ($dateFrom) {
            $purchaseQuery->whereDate('invoice_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $purchaseQuery->whereDate('invoice_date', '<=', $dateTo);
        }
        if ($itemId) {
            $purchaseQuery->whereHas('items', function($q) use ($itemId) {
                $q->where('inventory_item_id', $itemId);
            });
        }

        $purchaseInvoices = $purchaseQuery->get();
        $this->line("Found {$purchaseInvoices->count()} purchase invoices");

        foreach ($purchaseInvoices as $invoice) {
            try {
                if ($dryRun) {
                    $this->line("  [DRY RUN] Would update Purchase Invoice: {$invoice->invoice_number} (ID: {$invoice->id})");
                } else {
                    // This will delete old movements and recreate them with correct balances
                    $invoice->postInventoryMovements();
                    $this->line("  ✓ Updated Purchase Invoice: {$invoice->invoice_number}");
                }
                $updatedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  ✗ Error updating Purchase Invoice {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        $this->newLine();

        // Update POS Sales
        $this->info('Processing POS Sales...');
        $posQuery = PosSale::query();
        
        if ($dateFrom) {
            $posQuery->whereDate('sale_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $posQuery->whereDate('sale_date', '<=', $dateTo);
        }
        if ($itemId) {
            $posQuery->whereHas('items', function($q) use ($itemId) {
                $q->where('inventory_item_id', $itemId);
            });
        }

        $posSales = $posQuery->get();
        $this->line("Found {$posSales->count()} POS sales");

        foreach ($posSales as $sale) {
            try {
                if ($dryRun) {
                    $this->line("  [DRY RUN] Would update POS Sale: {$sale->pos_number} (ID: {$sale->id})");
                } else {
                    // This will delete old movements and recreate them with correct balances
                    $sale->updateInventory();
                    $this->line("  ✓ Updated POS Sale: {$sale->pos_number}");
                }
                $updatedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  ✗ Error updating POS Sale {$sale->pos_number}: " . $e->getMessage());
            }
        }

        $this->newLine();

        // Update Cash Sales
        $this->info('Processing Cash Sales...');
        $cashQuery = CashSale::query();
        
        if ($dateFrom) {
            $cashQuery->whereDate('sale_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $cashQuery->whereDate('sale_date', '<=', $dateTo);
        }
        if ($itemId) {
            $cashQuery->whereHas('items', function($q) use ($itemId) {
                $q->where('inventory_item_id', $itemId);
            });
        }

        $cashSales = $cashQuery->get();
        $this->line("Found {$cashSales->count()} cash sales");

        foreach ($cashSales as $sale) {
            try {
                if ($dryRun) {
                    $this->line("  [DRY RUN] Would update Cash Sale: {$sale->sale_number} (ID: {$sale->id})");
                } else {
                    // This will delete old movements and recreate them with correct balances
                    $sale->updateInventory();
                    $this->line("  ✓ Updated Cash Sale: {$sale->sale_number}");
                }
                $updatedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  ✗ Error updating Cash Sale {$sale->sale_number}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Update complete!');
        $this->info("Total transactions processed: {$updatedCount}");
        
        if ($errorCount > 0) {
            $this->error("Errors encountered: {$errorCount}");
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No changes were made. Run without --dry-run to apply changes.');
        }

        return 0;
    }
}
