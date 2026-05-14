<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory\Movement;
use App\Models\Inventory\Item;
use Illuminate\Support\Facades\DB;

class RecalculateInventoryMovements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:recalculate-movements 
                            {--item_id= : Specific item ID to recalculate}
                            {--location_id= : Specific location ID to recalculate}
                            {--company_id= : Company ID to recalculate (processes all items for that company)}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate inventory movement balances chronologically to fix incorrect balances from backdated transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $itemId = $this->option('item_id');
        $locationId = $this->option('location_id');
        $companyId = $this->option('company_id');
        $dryRun = $this->option('dry-run');

        $this->info('Starting inventory movement recalculation...');
        $this->newLine();

        // Build query to get movements
        $query = Movement::query();

        if ($itemId) {
            $query->where('item_id', $itemId);
            $this->info("Filtering by Item ID: {$itemId}");
        }

        if ($locationId) {
            $query->where('location_id', $locationId);
            $this->info("Filtering by Location ID: {$locationId}");
        }

        if ($companyId) {
            $query->whereHas('item', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
            $this->info("Filtering by Company ID: {$companyId}");
        }

        // Group by item_id and location_id to process each combination separately
        $groupedMovements = $query->get()
            ->groupBy(function($movement) {
                return $movement->item_id . '_' . $movement->location_id;
            });

        $totalProcessed = 0;
        $totalUpdated = 0;

        foreach ($groupedMovements as $groupKey => $movements) {
            [$itemId, $locId] = explode('_', $groupKey);
            
            $item = Item::find($itemId);
            if (!$item) {
                $this->warn("Item ID {$itemId} not found, skipping...");
                continue;
            }

            $this->info("Processing Item: {$item->name} (ID: {$itemId}) at Location ID: {$locId}");
            
            // Sort movements chronologically: by movement_date, then created_at, then id
            $sortedMovements = $movements->sortBy(function($movement) {
                return [
                    $movement->movement_date,
                    $movement->created_at ? $movement->created_at->timestamp : 0,
                    $movement->id
                ];
            })->values();

            $currentBalance = 0;
            $updatedCount = 0;

            foreach ($sortedMovements as $index => $movement) {
                $totalProcessed++;
                
                $oldBalanceBefore = $movement->balance_before;
                $oldBalanceAfter = $movement->balance_after;
                
                // Calculate new balance_before (current balance before this movement)
                $newBalanceBefore = $currentBalance;
                
                // Calculate new balance_after based on movement type
                $newBalanceAfter = $currentBalance;
                if (in_array($movement->movement_type, ['opening_balance', 'transfer_in', 'purchased', 'adjustment_in'])) {
                    $newBalanceAfter = $currentBalance + $movement->quantity;
                } elseif (in_array($movement->movement_type, ['transfer_out', 'sold', 'adjustment_out', 'write_off'])) {
                    $newBalanceAfter = $currentBalance - $movement->quantity;
                }
                
                // Update current balance for next iteration
                $currentBalance = $newBalanceAfter;
                
                // Check if update is needed
                $needsUpdate = abs($oldBalanceBefore - $newBalanceBefore) > 0.01 || 
                              abs($oldBalanceAfter - $newBalanceAfter) > 0.01;
                
                if ($needsUpdate) {
                    $updatedCount++;
                    $totalUpdated++;
                    
                    if ($dryRun) {
                        $this->line("  [DRY RUN] Movement ID {$movement->id} ({$movement->movement_type}):");
                        $this->line("    Balance Before: {$oldBalanceBefore} → {$newBalanceBefore}");
                        $this->line("    Balance After: {$oldBalanceAfter} → {$newBalanceAfter}");
                    } else {
                        $movement->update([
                            'balance_before' => $newBalanceBefore,
                            'balance_after' => $newBalanceAfter,
                        ]);
                        
                        if (($updatedCount % 10) == 0) {
                            $this->line("    Updated {$updatedCount} movements...");
                        }
                    }
                }
            }
            
            if ($updatedCount > 0) {
                $this->info("  ✓ Updated {$updatedCount} movements for this item/location");
            } else {
                $this->line("  - No updates needed");
            }
            $this->newLine();
        }

        $this->newLine();
        $this->info("Recalculation complete!");
        $this->info("Total movements processed: {$totalProcessed}");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE: No changes were made. Run without --dry-run to apply changes.");
            $this->info("Total movements that would be updated: {$totalUpdated}");
        } else {
            $this->info("Total movements updated: {$totalUpdated}");
        }
        
        return 0;
    }
}
