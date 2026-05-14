<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FxRevaluationService;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class ProcessMonthEndFxRevaluation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fx:process-month-end-revaluation 
                            {--company= : Process for specific company ID}
                            {--branch= : Process for specific branch ID}
                            {--date= : Specific month-end date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process month-end FX revaluation for all companies (reverses previous month and creates new revaluation)';

    protected $fxRevaluationService;

    /**
     * Create a new command instance.
     */
    public function __construct(FxRevaluationService $fxRevaluationService)
    {
        parent::__construct();
        $this->fxRevaluationService = $fxRevaluationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting month-end FX revaluation processing...');

        $companyId = $this->option('company');
        $branchId = $this->option('branch');
        $date = $this->option('date');

        if ($companyId) {
            // Process for specific company
            $companies = Company::where('id', $companyId)->get();
        } else {
            // Process for all active companies
            $companies = Company::where('is_active', true)->get();
        }

        if ($companies->isEmpty()) {
            $this->warn('No companies found to process.');
            return 0;
        }

        $totalProcessed = 0;
        $totalErrors = 0;

        foreach ($companies as $company) {
            try {
                $this->info("Processing company: {$company->name} (ID: {$company->id})");

                $result = $this->fxRevaluationService->processMonthEndRevaluation(
                    $company->id,
                    $branchId,
                    $date,
                    null // System user
                );

                if ($result['success']) {
                    $this->info("✓ Processed: {$result['items_revalued']} items, Net Gain/Loss: " . number_format($result['net_gain_loss'] ?? 0, 2));
                    if ($result['reversal_performed'] ?? false) {
                        $this->info("  → Previous month's revaluation reversed");
                    }
                    $totalProcessed++;
                } else {
                    $this->error("✗ Failed to process company: {$company->name}");
                    $totalErrors++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Error processing company {$company->name}: " . $e->getMessage());
                Log::error("Month-end FX revaluation error for company {$company->id}: " . $e->getMessage(), [
                    'company_id' => $company->id,
                    'branch_id' => $branchId,
                    'date' => $date,
                    'exception' => $e,
                ]);
                $totalErrors++;
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Companies processed: {$totalProcessed}");
        $this->info("Errors: {$totalErrors}");

        return $totalErrors === 0 ? 0 : 1;
    }
}

