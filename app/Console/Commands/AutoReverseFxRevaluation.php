<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FxRevaluationService;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class AutoReverseFxRevaluation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fx:auto-reverse {--company-id= : Specific company ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-reverse previous month\'s FX revaluation entries on the 1st of each month';

    protected $revaluationService;

    /**
     * Create a new command instance.
     */
    public function __construct(FxRevaluationService $revaluationService)
    {
        parent::__construct();
        $this->revaluationService = $revaluationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting FX Revaluation Auto-Reversal...');

        $companyId = $this->option('company-id');
        $newPeriodStartDate = now()->startOfMonth()->toDateString();

        if ($companyId) {
            // Process specific company
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company with ID {$companyId} not found.");
                return 1;
            }
            $this->processCompany($company, $newPeriodStartDate);
        } else {
            // Process all active companies
            $companies = Company::where('status', 'active')->get();
            $this->info("Processing {$companies->count()} companies...");

            foreach ($companies as $company) {
                $this->processCompany($company, $newPeriodStartDate);
            }
        }

        $this->info('FX Revaluation Auto-Reversal completed.');
        return 0;
    }

    /**
     * Process revaluation reversal for a company.
     */
    protected function processCompany(Company $company, string $newPeriodStartDate)
    {
        $this->info("Processing company: {$company->name} (ID: {$company->id})");

        try {
            $result = $this->revaluationService->reversePreviousRevaluation(
                $company->id,
                null, // All branches
                $newPeriodStartDate
            );

            if ($result['success']) {
                $this->info("  ✓ Reversed {$result['reversed_count']} revaluation entries for {$company->name}");
                Log::info("FX Auto-Reversal: Reversed {$result['reversed_count']} entries for company {$company->id}");
            } else {
                $this->warn("  ⚠ No entries to reverse for {$company->name}");
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Error processing {$company->name}: " . $e->getMessage());
            Log::error("FX Auto-Reversal failed for company {$company->id}: " . $e->getMessage());
        }
    }
}

