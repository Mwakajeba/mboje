<?php

namespace App\Services;

use App\Models\Provision;
use App\Models\ProvisionMovement;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\DiscountRate;
use App\Services\ProvisionComputation\ProvisionComputationFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProvisionService
{
    /**
     * Generate a unique provision number per company and year.
     */
    public function generateProvisionNumber(int $companyId): string
    {
        $prefix = 'PRV';
        $year = date('Y');

        $last = Provision::withTrashed()
            ->where('company_id', $companyId)
            ->where('provision_number', 'like', $prefix . '-' . $year . '-%')
            ->orderBy('provision_number', 'desc')
            ->first();

        $nextNumber = 1;

        if ($last) {
            $parts = explode('-', $last->provision_number);
            if (count($parts) === 3 && is_numeric($parts[2])) {
                $nextNumber = (int) $parts[2] + 1;
            }
        }

        $number = sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);

        $attempts = 0;
        while (Provision::withTrashed()->where('provision_number', $number)->exists() && $attempts < 100) {
            $nextNumber++;
            $number = sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
            $attempts++;
        }

        if ($attempts >= 100) {
            throw new \RuntimeException('Unable to generate unique provision number.');
        }

        return $number;
    }

    /**
     * Create and recognise a provision, including initial GL posting and movement.
     *
     * Applies IAS 37 gatekeeper logic:
     * - Present obligation must exist
     * - Probability must be 'probable' or 'virtually_certain'
     * - Reliable estimate (amount > 0) must be available
     */
    public function createProvisionWithInitialRecognition(array $data): Provision
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            $companyId = $user->company_id;
            // Branch resolution: explicit form value → session branch → user branch
            $branchId = $data['branch_id']
                ?? (session('branch_id') ?: ($user->branch_id ?? null));

            if (!$branchId) {
                throw new \InvalidArgumentException('Branch is required to create a provision. Please select a branch in the form or ensure a current branch is set in your session.');
            }

            // Handle computation if computation_assumptions are provided
            $computationAssumptions = $data['computation_assumptions'] ?? null;
            $computedAmount = null;
            $undiscountedAmount = null;
            $provisionType = $data['provision_type'] ?? 'other';

            if ($computationAssumptions && is_array($computationAssumptions)) {
                if (ProvisionComputationFactory::hasComputation($provisionType)) {
                    $computationService = ProvisionComputationFactory::getService($provisionType);
                    $computationResult = $computationService->calculate($computationAssumptions);
                    
                    if (!empty($computationResult['errors'])) {
                        throw new \InvalidArgumentException(
                            'Computation errors: ' . implode(', ', $computationResult['errors'])
                        );
                    }
                    
                    $computedAmount = $computationResult['amount'];
                    $undiscountedAmount = $computationResult['undiscounted_amount'] ?? null;
                    $computationAssumptions = $computationResult['assumptions'];
                    
                    // Use computed amount if no manual amount override provided
                    if (!isset($data['amount']) || $data['amount'] == 0) {
                        $data['amount'] = $computedAmount;
                    }
                }
            }

            // Restructuring validation: Block excluded costs
            if ($provisionType === 'restructuring') {
                $excludedCosts = [
                    'training_costs' => $data['training_costs'] ?? 0,
                    'marketing_costs' => $data['marketing_costs'] ?? 0,
                    'future_operating_losses' => $data['future_operating_losses'] ?? 0,
                ];
                
                foreach ($excludedCosts as $costType => $value) {
                    if ((float) $value > 0) {
                        throw new \InvalidArgumentException(
                            ucfirst(str_replace('_', ' ', $costType)) . 
                            ' cannot be included in restructuring provisions per IAS 37. ' .
                            'Only termination benefits, contract termination penalties, and direct restructuring costs are allowed.'
                        );
                    }
                }
            }

            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Provision amount must be greater than zero.');
            }

            $hasPresentObligation = (bool) ($data['has_present_obligation'] ?? false);
            $probability = $data['probability'] ?? 'possible';

            // IAS 37 recognition gatekeeper (hard enforcement):
            // 1) Present obligation (legal OR constructive) must exist
            // 2) Outflow must be at least probable (> 50%)
            // 3) Reliable estimate is implied by validated positive amount
            $presentObligationValid = $hasPresentObligation && !empty($data['present_obligation_type']);
            $probableOutflow = in_array($probability, ['probable', 'virtually_certain'], true);
            $reliableEstimate = $amount > 0;

            if (!($presentObligationValid && $probableOutflow && $reliableEstimate)) {
                throw new \InvalidArgumentException(
                    'IAS 37 gatekeeper: this case does not meet the three recognition conditions for a provision. ' .
                    'Please record it as a contingent liability/asset in the Contingent Items (IAS 37) module (disclosure only).'
                );
            }

            $currency = $data['currency_code'] ?? 'TZS';
            $fxRate = (float) ($data['fx_rate_at_creation'] ?? 1);
            $homeAmount = $amount * $fxRate;

            $provision = new Provision();
            $provision->provision_number = $this->generateProvisionNumber($companyId);
            $provision->provision_type = $data['provision_type'] ?? 'other';
            $provision->title = $data['title'];
            $provision->description = $data['description'];
            $provision->present_obligation_type = $data['present_obligation_type'] ?? null;
            $provision->has_present_obligation = $hasPresentObligation;
            $provision->probability = $probability;
            $provision->probability_percent = $data['probability_percent'] ?? null;
            $provision->is_recognised = true;
            $provision->estimate_method = $data['estimate_method'] ?? 'best_estimate';
            $provision->currency_code = $currency;
            $provision->fx_rate_at_creation = $fxRate;
            $provision->original_estimate = $homeAmount;
            $provision->current_balance = $homeAmount;
            $provision->utilised_amount = 0;
            $provision->reversed_amount = 0;
            $provision->is_discounted = (bool) ($data['is_discounted'] ?? false);
            $provision->discount_rate = $data['discount_rate'] ?? null;
            $provision->discount_rate_id = $data['discount_rate_id'] ?? null;
            $provision->expected_settlement_date = $data['expected_settlement_date'] ?? null;
            $provision->undiscounted_amount = $undiscountedAmount ?? ($data['undiscounted_amount'] ?? null);
            $provision->computation_assumptions = $computationAssumptions;
            
            // Asset linkage fields (for Environmental provisions)
            $provision->related_asset_id = $data['related_asset_id'] ?? null;
            $provision->asset_category = $data['asset_category'] ?? null;
            $provision->is_capitalised = (bool) ($data['is_capitalised'] ?? false);
            $provision->depreciation_start_date = isset($data['depreciation_start_date']) 
                ? Carbon::parse($data['depreciation_start_date'])->toDateString() 
                : null;
            
            $provision->expense_account_id = $data['expense_account_id'];
            $provision->provision_account_id = $data['provision_account_id'];
            $provision->unwinding_account_id = $data['unwinding_account_id'] ?? null;
            $provision->status = 'active';
            
            // Auto-populate discount rate from central table if discounted and rate not provided
            if ($provision->is_discounted && !$provision->discount_rate_id && !$provision->discount_rate) {
                $activeRate = DiscountRate::getActiveRate($companyId, 'provision', $currency);
                if ($activeRate) {
                    $provision->discount_rate_id = $activeRate->id;
                    $provision->discount_rate = $activeRate->rate_percent;
                }
            }
            $provision->company_id = $companyId;
            $provision->branch_id = $branchId;
            $provision->created_by = $user->id;
            $provision->updated_by = $user->id;
            $provision->save();

            // Determine debit account based on provision type and capitalisation flag
            // Environmental provisions with asset linkage: Dr Asset / Cr Provision
            // All others: Dr Expense / Cr Provision
            $debitAccountId = $provision->expense_account_id;
            if ($provision->provision_type === 'environmental' && $provision->is_capitalised && $provision->related_asset_id) {
                // For Environmental provisions capitalised into PPE, use the expense account as the asset account
                // (The expense_account_id field is repurposed to hold the asset account ID in this case)
                $debitAccountId = $provision->expense_account_id;
                $provision->is_capitalised = true;
            }
            
            // Create initial journal: Dr Expense/Asset, Cr Provision (liability)
            $journal = $this->createJournal(
                companyId: $companyId,
                branchId: $branchId,
                date: Carbon::parse($data['movement_date'] ?? now())->toDateString(),
                description: $data['journal_description']
                    ?? "Initial recognition of provision {$provision->provision_number} – {$provision->title}",
                debitAccountId: $debitAccountId,
                creditAccountId: $provision->provision_account_id,
                amount: $homeAmount
            );

            // Create movement record
            $movement = new ProvisionMovement();
            $movement->provision_id = $provision->id;
            $movement->movement_date = $journal->date;
            $movement->movement_type = 'initial_recognition';
            $movement->description = $journal->description;
            $movement->currency_code = $currency;
            $movement->fx_rate = $fxRate;
            $movement->foreign_amount = $amount;
            $movement->home_amount = $homeAmount;
            $movement->balance_after_movement = $provision->current_balance;
            $movement->journal_id = $journal->id;
            $movement->created_by = $user->id;
            $movement->updated_by = $user->id;
            $movement->save();

            return $provision->fresh(['movements']);
        });
    }

    /**
     * Remeasure provision (increase or decrease).
     * - Increase: Dr Expense, Cr Provision
     * - Decrease (reversal): Dr Provision, Cr Expense
     */
    public function remeasureProvision(Provision $provision, float $newHomeEstimate, string $description, ?string $movementDate = null): Provision
    {
        return DB::transaction(function () use ($provision, $newHomeEstimate, $description, $movementDate) {
            $user = Auth::user();

            $current = (float) $provision->current_balance;
            $delta = $newHomeEstimate - $current;

            if (abs($delta) < 0.01) {
                return $provision;
            }

            $date = $movementDate ? Carbon::parse($movementDate)->toDateString() : now()->toDateString();

            if ($delta > 0) {
                // Increase provision
                $journal = $this->createJournal(
                    companyId: $provision->company_id,
                    branchId: $provision->branch_id,
                    date: $date,
                    description: $description,
                    debitAccountId: $provision->expense_account_id,
                    creditAccountId: $provision->provision_account_id,
                    amount: $delta
                );

                $movementType = 'remeasure_increase';
            } else {
                // Decrease / reversal
                $amount = abs($delta);
                $journal = $this->createJournal(
                    companyId: $provision->company_id,
                    branchId: $provision->branch_id,
                    date: $date,
                    description: $description,
                    debitAccountId: $provision->provision_account_id,
                    creditAccountId: $provision->expense_account_id,
                    amount: $amount
                );

                $movementType = 'remeasure_decrease';
                $provision->reversed_amount += $amount;
            }

            $provision->current_balance = $newHomeEstimate;
            $provision->updated_by = $user?->id;
            $provision->save();

            $movement = new ProvisionMovement();
            $movement->provision_id = $provision->id;
            $movement->movement_date = $date;
            $movement->movement_type = $movementType;
            $movement->description = $description;
            $movement->currency_code = $provision->currency_code;
            $movement->fx_rate = $provision->fx_rate_at_creation;
            $movement->foreign_amount = $delta; // can be negative, store as signed
            $movement->home_amount = $delta;
            $movement->balance_after_movement = $provision->current_balance;
            $movement->journal_id = $journal->id;
            $movement->created_by = $user?->id;
            $movement->updated_by = $user?->id;
            $movement->save();

            return $provision->fresh(['movements']);
        });
    }

    /**
     * Record utilisation of a provision when actual cash/settlement occurs.
     *
     * IAS 37: Dr Provision, Cr Cash/Payables/Inventory (no P&L impact at this stage).
     */
    public function utiliseProvision(
        Provision $provision,
        float $homeAmount,
        int $offsetAccountId,
        string $description,
        ?string $movementDate = null
    ): Provision {
        return DB::transaction(function () use ($provision, $homeAmount, $offsetAccountId, $description, $movementDate) {
            $user = Auth::user();

            if ($homeAmount <= 0) {
                throw new \InvalidArgumentException('Utilisation amount must be greater than zero.');
            }

            if ($homeAmount > $provision->current_balance + 0.01) {
                throw new \InvalidArgumentException('Utilisation cannot exceed current provision balance.');
            }

            $date = $movementDate ? Carbon::parse($movementDate)->toDateString() : now()->toDateString();

            $journal = $this->createJournal(
                companyId: $provision->company_id,
                branchId: $provision->branch_id,
                date: $date,
                description: $description,
                debitAccountId: $provision->provision_account_id,
                creditAccountId: $offsetAccountId,
                amount: $homeAmount
            );

            $provision->current_balance -= $homeAmount;
            $provision->utilised_amount += $homeAmount;
            if ($provision->current_balance <= 0.01) {
                $provision->status = 'settled';
            }
            $provision->updated_by = $user?->id;
            $provision->save();

            $movement = new ProvisionMovement();
            $movement->provision_id = $provision->id;
            $movement->movement_date = $date;
            $movement->movement_type = 'utilisation';
            $movement->description = $description;
            $movement->currency_code = $provision->currency_code;
            $movement->fx_rate = $provision->fx_rate_at_creation;
            $movement->foreign_amount = $homeAmount / $provision->fx_rate_at_creation;
            $movement->home_amount = $homeAmount;
            $movement->balance_after_movement = $provision->current_balance;
            $movement->journal_id = $journal->id;
            $movement->created_by = $user?->id;
            $movement->updated_by = $user?->id;
            $movement->save();

            return $provision->fresh(['movements']);
        });
    }

    /**
     * Record unwinding of discount: Dr Finance Cost, Cr Provision.
     */
    public function unwindDiscount(
        Provision $provision,
        float $homeAmount,
        string $description,
        ?string $movementDate = null
    ): Provision {
        return DB::transaction(function () use ($provision, $homeAmount, $description, $movementDate) {
            $user = Auth::user();

            if (!$provision->unwinding_account_id) {
                throw new \InvalidArgumentException('Unwinding account is not configured for this provision.');
            }

            if ($homeAmount <= 0) {
                throw new \InvalidArgumentException('Unwinding amount must be greater than zero.');
            }

            $date = $movementDate ? Carbon::parse($movementDate)->toDateString() : now()->toDateString();

            $journal = $this->createJournal(
                companyId: $provision->company_id,
                branchId: $provision->branch_id,
                date: $date,
                description: $description,
                debitAccountId: $provision->unwinding_account_id,
                creditAccountId: $provision->provision_account_id,
                amount: $homeAmount
            );

            $provision->current_balance += $homeAmount;
            $provision->updated_by = $user?->id;
            $provision->save();

            $movement = new ProvisionMovement();
            $movement->provision_id = $provision->id;
            $movement->movement_date = $date;
            $movement->movement_type = 'unwinding';
            $movement->description = $description;
            $movement->currency_code = $provision->currency_code;
            $movement->fx_rate = $provision->fx_rate_at_creation;
            $movement->foreign_amount = $homeAmount / $provision->fx_rate_at_creation;
            $movement->home_amount = $homeAmount;
            $movement->balance_after_movement = $provision->current_balance;
            $movement->journal_id = $journal->id;
            $movement->created_by = $user?->id;
            $movement->updated_by = $user?->id;
            $movement->save();

            return $provision->fresh(['movements']);
        });
    }

    /**
     * Helper to create a simple journal (Dr / Cr, immediately posted).
     */
    protected function createJournal(
        int $companyId,
        int $branchId,
        string $date,
        string $description,
        int $debitAccountId,
        int $creditAccountId,
        float $amount
    ): Journal {
        return DB::transaction(function () use ($companyId, $branchId, $date, $description, $debitAccountId, $creditAccountId, $amount) {
            $userId = Auth::id();

            $journal = Journal::create([
                'reference' => $this->generateJournalNumber($companyId),
                'reference_type' => 'Provision',
                'description' => $description,
                'date' => $date,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'approved' => true,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            // Debit line
            $this->createJournalItem($journal, $debitAccountId, 'debit', $amount);
            // Credit line
            $this->createJournalItem($journal, $creditAccountId, 'credit', $amount);

            // Load items then create GL transactions using existing Journal logic
            $journal->refresh();
            $journal->load('items');

            if (method_exists($journal, 'createGlTransactions')) {
                $journal->createGlTransactions();
            }

            return $journal;
        });
    }

    protected function createJournalItem(Journal $journal, int $accountId, string $nature, float $amount): void
    {
        $item = new JournalItem();
        $item->journal_id = $journal->id;
        $item->chart_account_id = $accountId;
        $item->nature = $nature;
        $item->amount = $amount;
        $item->description = $journal->description;
        $item->save();
    }

    protected function generateJournalNumber(int $companyId): string
    {
        $prefix = 'JNL';
        $year = date('Y');

        $count = Journal::whereHas('branch', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $count);
    }
}


