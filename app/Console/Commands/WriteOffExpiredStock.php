<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory\ExpiryTracking;
use App\Services\ExpiryStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WriteOffExpiredStock extends Command
{
    protected $signature = 'inventory:write-off-expired {--dry-run : Show what would be written off without actually doing it}';

    protected $description = 'Write off expired stock and create GL transactions';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('DRY RUN MODE - No actual changes will be made');
        }

        $expiryService = new ExpiryStockService();
        $expiredItems = $expiryService->getExpiredStock();

        if (empty($expiredItems)) {
            $this->info('No expired items found.');
            return Command::SUCCESS;
        }

        $totalValue = 0;
        $totalItems = 0;

        foreach ($expiredItems as $item) {
            $totalValue += $item['total_value'];
            $totalItems += $item['total_quantity'];
        }

        $this->info("Found expired stock worth TZS " . number_format($totalValue, 2) . " across " . count($expiredItems) . " items.");

        if ($isDryRun) {
            $this->displayExpiredItems($expiredItems);
            return Command::SUCCESS;
        }

        if (!$this->confirm('Do you want to write off this expired stock?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $writeOffDetails = [];
            
            foreach ($expiredItems as $item) {
                $itemDetails = $expiryService->writeOffExpiredStock($item['item_id'], null);
                $writeOffDetails = array_merge($writeOffDetails, $itemDetails);
            }

            // Create GL transactions for write-off
            $this->createWriteOffGlTransactions($writeOffDetails);

            DB::commit();
            
            $this->info('Successfully wrote off expired stock.');
            $this->info('Total value written off: TZS ' . number_format($totalValue, 2));
            
            Log::info('Expired stock written off', [
                'total_value' => $totalValue,
                'total_items' => $totalItems,
                'write_off_details' => $writeOffDetails
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to write off expired stock: ' . $e->getMessage());
            Log::error('Failed to write off expired stock', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function displayExpiredItems(array $expiredItems): void
    {
        $this->warn("\n=== EXPIRED ITEMS TO BE WRITTEN OFF ===");
        
        $headers = ['Item Code', 'Item Name', 'Total Qty', 'Total Value'];
        $rows = [];

        foreach ($expiredItems as $item) {
            $rows[] = [
                $item['item_code'],
                $item['item_name'],
                number_format($item['total_quantity'], 2),
                'TZS ' . number_format($item['total_value'], 2)
            ];
        }

        $this->table($headers, $rows);
    }

    private function createWriteOffGlTransactions(array $writeOffDetails): void
    {
        // Get write-off expense account from settings
        $writeOffAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_write_off_expense_account')->value('value') ?? 0);
        
        if (!$writeOffAccountId) {
            throw new \Exception('Write-off expense account not configured. Please set inventory_write_off_expense_account in system settings.');
        }

        $totalWriteOffValue = 0;
        $transactions = [];

        foreach ($writeOffDetails as $detail) {
            $totalWriteOffValue += $detail['total_cost'];
            
            // Debit: Write-off Expense
            $transactions[] = [
                'chart_account_id' => $writeOffAccountId,
                'amount' => $detail['total_cost'],
                'nature' => 'debit',
                'transaction_type' => 'inventory_write_off',
                'date' => now()->toDateString(),
                'description' => "Write-off expired stock - Batch: {$detail['batch_number']}, Expiry: {$detail['expiry_date']}",
                'branch_id' => session('branch_id') ?? 1,
                'user_id' => 1, // System user
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Credit: Inventory (reduce inventory value)
            $transactions[] = [
                'chart_account_id' => (int) (\App\Models\SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185),
                'amount' => $detail['total_cost'],
                'nature' => 'credit',
                'transaction_type' => 'inventory_write_off',
                'date' => now()->toDateString(),
                'description' => "Write-off expired stock - Batch: {$detail['batch_number']}, Expiry: {$detail['expiry_date']}",
                'branch_id' => session('branch_id') ?? 1,
                'user_id' => 1, // System user
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        \App\Models\GlTransaction::insert($transactions);
        
        $this->info("Created GL transactions for write-off worth TZS " . number_format($totalWriteOffValue, 2));
    }
}
