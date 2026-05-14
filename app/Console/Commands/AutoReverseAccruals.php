<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AccrualScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoReverseAccruals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accruals:auto-reverse {--date= : Date to process reversals (YYYY-MM-DD), defaults to today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-reverse accrual journals on the 1st day of each month (IFRS compliant)';

    protected $scheduleService;

    /**
     * Create a new command instance.
     */
    public function __construct(AccrualScheduleService $scheduleService)
    {
        parent::__construct();
        $this->scheduleService = $scheduleService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automatic accrual reversal...');

        // Get reversal date
        $reversalDate = $this->option('date') 
            ? Carbon::parse($this->option('date')) 
            : Carbon::now();

        $this->info("Processing reversals for date: {$reversalDate->format('Y-m-d')}");

        try {
            $result = $this->scheduleService->autoReverseAccruals($reversalDate);

            if ($result['processed'] > 0) {
                $this->info("✓ Successfully processed {$result['processed']} reversal(s)");
            } else {
                $this->info("ℹ No reversals processed: {$result['message']}");
            }

            if (!empty($result['errors'])) {
                $this->warn("⚠ Encountered " . count($result['errors']) . " error(s):");
                foreach ($result['errors'] as $error) {
                    $this->error("  - Schedule: {$error['schedule_number']}, Error: {$error['error']}");
                }
                return 1;
            }

            $this->info('Auto-reversal completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to process auto-reversals: ' . $e->getMessage());
            Log::error('Auto-reversal command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
