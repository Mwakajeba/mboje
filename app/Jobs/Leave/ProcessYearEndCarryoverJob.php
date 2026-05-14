<?php

namespace App\Jobs\Leave;

use App\Models\Company;
use App\Services\Leave\AccrualService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessYearEndCarryoverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $companyId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AccrualService $accrualService): void
    {
        $effectiveDate = Carbon::now()->startOfYear();

        if ($this->companyId) {
            $company = Company::find($this->companyId);
            if ($company) {
                Log::info('Processing year-end carryover for company', ['company_id' => $company->id]);
                $processed = $accrualService->processCarryover($company, $effectiveDate);
                Log::info('Carryover processed', ['count' => $processed]);
            }
        } else {
            // Run for all companies
            $companies = Company::all();
            foreach ($companies as $company) {
                Log::info('Processing year-end carryover for company', ['company_id' => $company->id]);
                $processed = $accrualService->processCarryover($company, $effectiveDate);
                Log::info('Carryover processed', ['count' => $processed]);
            }
        }
    }
}

