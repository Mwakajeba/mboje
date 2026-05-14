<?php

namespace App\Services;

use App\Models\PettyCash\PettyCashSettings;
use App\Models\PettyCash\PettyCashUnit;
use App\Models\PettyCash\PettyCashRegister;
use App\Models\PettyCash\PettyCashTransaction;
use App\Models\PettyCash\PettyCashReplenishment;
use App\Models\ImprestRequest;
use Illuminate\Support\Facades\DB;

class PettyCashModeService
{
    /**
     * Get petty cash settings for a company
     */
    public static function getSettings($companyId): PettyCashSettings
    {
        return PettyCashSettings::getForCompany($companyId);
    }

    /**
     * Check if sub-imprest mode is enabled
     */
    public static function isSubImprestMode($companyId): bool
    {
        $settings = self::getSettings($companyId);
        return $settings->isSubImprestMode();
    }

    /**
     * Check if standalone mode is enabled
     */
    public static function isStandaloneMode($companyId): bool
    {
        $settings = self::getSettings($companyId);
        return $settings->isStandaloneMode();
    }

    /**
     * Create register entry for a transaction
     */
    public static function createRegisterEntry(PettyCashTransaction $transaction): PettyCashRegister
    {
        $unit = $transaction->pettyCashUnit;
        $pcvNumber = PettyCashRegister::generatePcvNumber($unit->code);

        $registerEntry = PettyCashRegister::create([
            'petty_cash_unit_id' => $unit->id,
            'petty_cash_transaction_id' => $transaction->id,
            'register_date' => $transaction->transaction_date,
            'pcv_number' => $pcvNumber,
            'description' => $transaction->description,
            'amount' => $transaction->amount,
            'entry_type' => 'disbursement',
            'nature' => 'debit',
            'gl_account_id' => $transaction->pettyCashUnit->petty_cash_account_id,
            'requested_by' => $transaction->created_by,
            'approved_by' => $transaction->approved_by,
            'status' => $transaction->status === 'posted' ? 'posted' : ($transaction->status === 'approved' ? 'approved' : 'pending'),
            'balance_after' => $transaction->balance_after,
            'notes' => $transaction->notes,
        ]);

        return $registerEntry;
    }

    /**
     * Create register entry for a replenishment
     */
    public static function createReplenishmentRegisterEntry(PettyCashReplenishment $replenishment): PettyCashRegister
    {
        $unit = $replenishment->pettyCashUnit;

        $registerEntry = PettyCashRegister::create([
            'petty_cash_unit_id' => $unit->id,
            'petty_cash_replenishment_id' => $replenishment->id,
            'register_date' => $replenishment->request_date,
            'pcv_number' => 'REPL-' . $replenishment->replenishment_number,
            'description' => 'Replenishment: ' . $replenishment->reason,
            'amount' => $replenishment->approved_amount ?? $replenishment->requested_amount,
            'entry_type' => 'replenishment',
            'nature' => 'credit',
            'gl_account_id' => $unit->petty_cash_account_id,
            'requested_by' => $replenishment->requested_by,
            'approved_by' => $replenishment->approved_by,
            'status' => $replenishment->status === 'posted' ? 'posted' : ($replenishment->status === 'approved' ? 'approved' : 'pending'),
            'balance_after' => $unit->current_balance,
            'notes' => $replenishment->notes,
        ]);

        return $registerEntry;
    }

    /**
     * Create opening balance register entry
     */
    public static function createOpeningBalanceEntry(PettyCashUnit $unit): PettyCashRegister
    {
        return PettyCashRegister::create([
            'petty_cash_unit_id' => $unit->id,
            'register_date' => now()->toDateString(),
            'pcv_number' => 'OPEN-' . $unit->code . '-' . date('Ymd'),
            'description' => 'Opening Balance - ' . $unit->name,
            'amount' => $unit->float_amount,
            'entry_type' => 'opening_balance',
            'nature' => 'credit',
            'gl_account_id' => $unit->petty_cash_account_id,
            'status' => 'posted',
            'balance_after' => $unit->float_amount,
        ]);
    }

    /**
     * Link transaction to imprest request (for sub-imprest mode)
     */
    public static function linkToImprest(PettyCashTransaction $transaction, $imprestRequestId): void
    {
        $registerEntry = PettyCashRegister::where('petty_cash_transaction_id', $transaction->id)->first();
        
        if ($registerEntry) {
            $registerEntry->update(['imprest_request_id' => $imprestRequestId]);
        }
    }

    /**
     * Get register entries for a unit
     */
    public static function getRegisterEntries($unitId, $filters = [])
    {
        $query = PettyCashRegister::where('petty_cash_unit_id', $unitId)
            ->with(['transaction', 'replenishment', 'imprestRequest', 'glAccount', 'requestedBy', 'approvedBy'])
            ->orderBy('register_date', 'desc')
            ->orderBy('id', 'desc');

        if (isset($filters['date_from'])) {
            $query->where('register_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('register_date', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['entry_type'])) {
            $query->where('entry_type', $filters['entry_type']);
        }

        return $query;
    }

    /**
     * Calculate reconciliation summary
     */
    public static function getReconciliationSummary(PettyCashUnit $unit, $asOfDate = null)
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        $openingBalance = $unit->float_amount;
        
        $totalDisbursed = PettyCashRegister::where('petty_cash_unit_id', $unit->id)
            ->where('entry_type', 'disbursement')
            ->where('register_date', '<=', $asOfDate)
            ->where('status', 'posted')
            ->sum('amount');

        $totalReplenished = PettyCashRegister::where('petty_cash_unit_id', $unit->id)
            ->where('entry_type', 'replenishment')
            ->where('register_date', '<=', $asOfDate)
            ->where('status', 'posted')
            ->sum('amount');

        $closingCash = $openingBalance - $totalDisbursed + $totalReplenished;

        return [
            'opening_balance' => $openingBalance,
            'total_disbursed' => $totalDisbursed,
            'total_replenished' => $totalReplenished,
            'closing_cash' => $closingCash,
            'system_balance' => $unit->current_balance,
            'variance' => $closingCash - $unit->current_balance,
        ];
    }
}


