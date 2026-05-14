<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AccrualScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoPostAccruals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accruals:auto-post {--date= : Date to process postings (YYYY-MM-DD), defaults to today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-post pending accrual journals at month-end (IFRS compliant)';

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
        $this->info('Starting automatic accrual posting...');

        // Get processing date
        $asOfDate = $this->option('date') 
            ? Carbon::parse($this->option('date')) 
            : Carbon::now();

        $this->info("Processing postings as of: {$asOfDate->format('Y-m-d')}");

        try {
            $result = $this->scheduleService->autoPostPendingAccruals($asOfDate);

            if ($result['processed'] > 0) {
                $this->info("✓ Successfully posted {$result['processed']} journal(s)");
            } else {
                $this->info("ℹ No journals posted: {$result['message']}");
            }

            if (!empty($result['errors'])) {
                $this->warn("⚠ Encountered " . count($result['errors']) . " error(s):");
                foreach ($result['errors'] as $error) {
                    $this->error("  - Schedule: {$error['schedule_number']}, Error: {$error['error']}");
                }
                return 1;
            }

            $this->info('Auto-posting completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to process auto-postings: ' . $e->getMessage());
            Log::error('Auto-post command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
