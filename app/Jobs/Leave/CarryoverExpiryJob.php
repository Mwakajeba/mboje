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

class CarryoverExpiryJob implements ShouldQueue
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
        $expiryDate = Carbon::now();

        if ($this->companyId) {
            $company = Company::find($this->companyId);
            if ($company) {
                Log::info('Processing expired carryovers for company', ['company_id' => $company->id]);
                $expired = $accrualService->processExpiredCarryovers($company, $expiryDate);
                Log::info('Expired carryovers processed', ['count' => $expired]);
            }
        } else {
            // Run for all companies
            $companies = Company::all();
            foreach ($companies as $company) {
                Log::info('Processing expired carryovers for company', ['company_id' => $company->id]);
                $expired = $accrualService->processExpiredCarryovers($company, $expiryDate);
                Log::info('Expired carryovers processed', ['count' => $expired]);
            }
        }
    }
}

