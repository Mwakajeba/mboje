<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory\ExpiryTracking;
use App\Models\Inventory\Item;
use App\Services\ExpiryStockService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckExpiryAlerts extends Command
{
    protected $signature = 'inventory:check-expiry-alerts {--days=30 : Number of days to check for expiring items}';

    protected $description = 'Check for items expiring soon and send alerts';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        
        // Use global setting if no days specified
        if ($days === 30) { // Default value
            $globalDays = \App\Models\SystemSetting::where('key', 'inventory_global_expiry_warning_days')->value('value');
            if ($globalDays) {
                $days = (int) $globalDays;
            }
        }
        
        $this->info("Checking for items expiring within {$days} days...");

        $expiryService = new ExpiryStockService();
        $expiringItems = $expiryService->getExpiringStock($days);
        $expiredItems = $expiryService->getExpiredStock();

        $expiringCount = count($expiringItems);
        $expiredCount = count($expiredItems);

        $this->info("Found {$expiringCount} items expiring soon and {$expiredCount} expired items.");

        if ($expiringCount > 0) {
            $this->displayExpiringItems($expiringItems);
        }

        if ($expiredCount > 0) {
            $this->displayExpiredItems($expiredItems);
        }

        // Log the results
        Log::info('Expiry alerts check completed', [
            'expiring_count' => $expiringCount,
            'expired_count' => $expiredCount,
            'days_checked' => $days
        ]);

        // TODO: Send email alerts if configured
        // $this->sendEmailAlerts($expiringItems, $expiredItems);

        return Command::SUCCESS;
    }

    private function displayExpiringItems(array $expiringItems): void
    {
        $this->warn("\n=== ITEMS EXPIRING SOON ===");
        
        $headers = ['Item Code', 'Item Name', 'Total Qty', 'Total Value', 'Earliest Expiry', 'Days Left'];
        $rows = [];

        foreach ($expiringItems as $item) {
            $rows[] = [
                $item['item_code'],
                $item['item_name'],
                number_format($item['total_quantity'], 2),
                'TZS ' . number_format($item['total_value'], 2),
                $item['earliest_expiry'],
                $this->getDaysUntilExpiry($item['earliest_expiry'])
            ];
        }

        $this->table($headers, $rows);
    }

    private function displayExpiredItems(array $expiredItems): void
    {
        $this->error("\n=== EXPIRED ITEMS ===");
        
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

    private function getDaysUntilExpiry(string $expiryDate): int
    {
        $expiry = \Carbon\Carbon::parse($expiryDate);
        $today = \Carbon\Carbon::today();
        
        return $today->diffInDays($expiry, false);
    }

    private function sendEmailAlerts(array $expiringItems, array $expiredItems): void
    {
        // TODO: Implement email alerts
        // This would send emails to relevant users about expiring/expired items
        $this->info('Email alerts would be sent here (not implemented yet).');
    }
}
