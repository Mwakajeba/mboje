<?php

namespace App\Console\Commands;

use App\Models\Provision;
use App\Services\ProvisionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProvisionPeriodicReview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'provisions:periodic-review 
                            {--date= : Review date (Y-m-d format, defaults to today)}
                            {--company= : Company ID (optional, reviews all companies if not specified)}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform periodic review of provisions: flag remeasurement items and calculate discount unwinding';

    protected ProvisionService $provisionService;

    public function __construct(ProvisionService $provisionService)
    {
        parent::__construct();
        $this->provisionService = $provisionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reviewDate = $this->option('date') 
            ? Carbon::parse($this->option('date')) 
            : now();
        
        $companyId = $this->option('company');
        $dryRun = $this->option('dry-run');

        $this->info("Provision Periodic Review - Date: {$reviewDate->format('Y-m-d')}");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }
        $this->newLine();

        // Get provisions to review
        $query = Provision::where('status', 'active')
            ->where('is_discounted', true)
            ->whereNotNull('expected_settlement_date')
            ->whereNotNull('discount_rate');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $provisions = $query->get();

        $this->info("Found {$provisions->count()} discounted provisions to review");
        $this->newLine();

        $reviewItems = [];
        $unwindingItems = [];

        foreach ($provisions as $provision) {
            // Check if remeasurement is needed
            $needsRemeasurement = $this->checkRemeasurementNeeded($provision, $reviewDate);
            
            if ($needsRemeasurement) {
                $reviewItems[] = [
                    'provision' => $provision,
                    'reason' => $needsRemeasurement,
                ];
            }

            // Calculate discount unwinding for the period
            $unwindingAmount = $this->calculateUnwinding($provision, $reviewDate);
            
            if ($unwindingAmount > 0) {
                $unwindingItems[] = [
                    'provision' => $provision,
                    'amount' => $unwindingAmount,
                ];
            }
        }

        // Display review items
        if (!empty($reviewItems)) {
            $this->info("=== Provisions Requiring Remeasurement ===");
            $this->table(
                ['Provision Number', 'Title', 'Type', 'Current Balance', 'Reason'],
                array_map(function ($item) {
                    return [
                        $item['provision']->provision_number,
                        $item['provision']->title,
                        ucfirst(str_replace('_', ' ', $item['provision']->provision_type)),
                        number_format($item['provision']->current_balance, 2),
                        $item['reason'],
                    ];
                }, $reviewItems)
            );
        } else {
            $this->info("No provisions require remeasurement at this time.");
        }

        $this->newLine();

        // Display unwinding items
        if (!empty($unwindingItems)) {
            $this->info("=== Discount Unwinding Calculations ===");
            $this->table(
                ['Provision Number', 'Title', 'Discount Rate', 'Unwinding Amount', 'Settlement Date'],
                array_map(function ($item) {
                    $provision = $item['provision'];
                    return [
                        $provision->provision_number,
                        $provision->title,
                        number_format($provision->discount_rate, 2) . '%',
                        number_format($item['amount'], 2),
                        $provision->expected_settlement_date?->format('Y-m-d') ?? 'N/A',
                    ];
                }, $unwindingItems)
            );

            if (!$dryRun) {
                if ($this->confirm('Post discount unwinding for all provisions?', true)) {
                    $this->postUnwinding($unwindingItems, $reviewDate);
                }
            } else {
                $this->warn("DRY RUN: Would post unwinding for " . count($unwindingItems) . " provisions");
            }
        } else {
            $this->info("No discount unwinding required at this time.");
        }

        // Flag inactive/expired provisions
        $this->newLine();
        $this->flagInactiveProvisions($companyId, $reviewDate, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * Check if provision needs remeasurement
     */
    private function checkRemeasurementNeeded(Provision $provision, Carbon $reviewDate): ?string
    {
        // Check if settlement date has passed
        if ($provision->expected_settlement_date && $provision->expected_settlement_date < $reviewDate) {
            return "Settlement date has passed ({$provision->expected_settlement_date->format('Y-m-d')})";
        }

        // Check if last remeasurement was more than 12 months ago
        $lastRemeasurement = $provision->movements()
            ->whereIn('movement_type', ['remeasure_increase', 'remeasure_decrease'])
            ->orderBy('movement_date', 'desc')
            ->first();

        if ($lastRemeasurement) {
            $monthsSinceRemeasurement = $reviewDate->diffInMonths($lastRemeasurement->movement_date);
            if ($monthsSinceRemeasurement >= 12) {
                return "Last remeasurement was {$monthsSinceRemeasurement} months ago (should be reviewed annually)";
            }
        } else {
            // Never remeasured, check if created more than 12 months ago
            $monthsSinceCreation = $reviewDate->diffInMonths($provision->created_at);
            if ($monthsSinceCreation >= 12) {
                return "Provision created {$monthsSinceCreation} months ago and never remeasured";
            }
        }

        return null;
    }

    /**
     * Calculate discount unwinding amount for a provision
     */
    private function calculateUnwinding(Provision $provision, Carbon $reviewDate): float
    {
        if (!$provision->is_discounted || !$provision->discount_rate || !$provision->expected_settlement_date) {
            return 0;
        }

        // Get the last unwinding date
        $lastUnwinding = $provision->movements()
            ->where('movement_type', 'unwinding')
            ->orderBy('movement_date', 'desc')
            ->first();

        $startDate = $lastUnwinding 
            ? Carbon::parse($lastUnwinding->movement_date)->addDay() 
            : $provision->created_at;

        // Calculate days in period
        $daysInPeriod = $startDate->diffInDays($reviewDate);
        if ($daysInPeriod <= 0) {
            return 0;
        }

        // Get opening balance at start of period
        $openingBalance = $lastUnwinding 
            ? (float) $lastUnwinding->balance_after_movement 
            : (float) $provision->original_estimate;

        // Annual unwinding = Opening Balance × Discount Rate
        // Daily unwinding = Annual / 365
        $annualUnwinding = $openingBalance * ($provision->discount_rate / 100);
        $dailyUnwinding = $annualUnwinding / 365;
        $periodUnwinding = $dailyUnwinding * $daysInPeriod;

        return round($periodUnwinding, 2);
    }

    /**
     * Post unwinding for provisions
     */
    private function postUnwinding(array $unwindingItems, Carbon $reviewDate): void
    {
        $this->info("Posting discount unwinding...");

        foreach ($unwindingItems as $item) {
            try {
                $this->provisionService->unwindDiscount(
                    $item['provision'],
                    $item['amount'],
                    "Periodic review - Discount unwinding for period ending {$reviewDate->format('Y-m-d')}",
                    $reviewDate->format('Y-m-d')
                );

                $this->line("✓ Posted unwinding for {$item['provision']->provision_number}: " . number_format($item['amount'], 2));
            } catch (\Exception $e) {
                $this->error("✗ Failed to post unwinding for {$item['provision']->provision_number}: " . $e->getMessage());
                Log::error("Provision unwinding failed", [
                    'provision_id' => $item['provision']->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Unwinding posting completed.");
    }

    /**
     * Flag inactive or expired provisions
     */
    private function flagInactiveProvisions(?int $companyId, Carbon $reviewDate, bool $dryRun): void
    {
        $query = Provision::where('status', 'active');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // Provisions with zero balance
        $zeroBalance = (clone $query)->where('current_balance', '<=', 0.01)->get();

        // Provisions with settlement date passed
        $expired = (clone $query)
            ->whereNotNull('expected_settlement_date')
            ->where('expected_settlement_date', '<', $reviewDate)
            ->get();

        if ($zeroBalance->count() > 0 || $expired->count() > 0) {
            $this->warn("=== Provisions Requiring Attention ===");
            
            if ($zeroBalance->count() > 0) {
                $this->warn("Found {$zeroBalance->count()} provision(s) with zero or near-zero balance:");
                foreach ($zeroBalance as $provision) {
                    $this->line("  - {$provision->provision_number}: {$provision->title} (Balance: " . number_format($provision->current_balance, 2) . ")");
                }
            }

            if ($expired->count() > 0) {
                $this->warn("Found {$expired->count()} provision(s) with passed settlement date:");
                foreach ($expired as $provision) {
                    $this->line("  - {$provision->provision_number}: {$provision->title} (Settlement: {$provision->expected_settlement_date->format('Y-m-d')})");
                }
            }

            $this->warn("Consider closing or settling these provisions.");
        } else {
            $this->info("No inactive or expired provisions found.");
        }
    }
}

