<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory\Item;
use App\Services\InventoryCostService;
use Carbon\Carbon;

class MigrateInventoryCostLayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:migrate-cost-layers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing inventory items to cost layers system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting inventory cost layers migration...');
        
        $costService = new InventoryCostService();
        $items = Item::where('current_stock', '>', 0)->get();
        
        $this->info('Found ' . $items->count() . ' items with current stock.');
        
        $bar = $this->output->createProgressBar($items->count());
        $bar->start();
        
        foreach ($items as $item) {
            // Create cost layer for existing stock
            if ($item->current_stock > 0 && $item->cost_price > 0) {
                $costService->addInventory(
                    $item->id,
                    $item->current_stock,
                    $item->cost_price,
                    'opening_balance',
                    'Initial Migration - ' . $item->code,
                    Carbon::now()->toDateString()
                );
                
                $this->newLine();
                $this->info("Migrated {$item->name} - Qty: {$item->current_stock}, Cost: {$item->cost_price}");
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Inventory cost layers migration completed successfully!');
        
        return 0;
    }
}
