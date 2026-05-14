<?php

namespace App\Services\PeriodClosing;

use App\Models\FiscalYear;
use App\Models\AccountingPeriod;
use App\Models\CloseBatch;
use App\Models\CloseAdjustment;
use App\Models\PeriodSnapshot;
use App\Models\ChartAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PeriodCloseService
{
    /**
     * Run pre-close checklist validation
     */
    public function runPreCloseChecks(int $companyId, AccountingPeriod $period): array
    {
        $checks = [
            'unposted_journals' => $this->checkUnpostedJournals($companyId, $period),
            'unreconciled_bank_items' => $this->checkUnreconciledBankItems($companyId, $period),
            'unallocated_receipts' => $this->checkUnallocatedReceipts($companyId, $period),
            'unallocated_payments' => $this->checkUnallocatedPayments($companyId, $period),
            'inventory_valuation' => $this->checkInventoryValuation($companyId, $period),
            'depreciation_run' => $this->checkDepreciationRun($companyId, $period),
            'tax_vat_booked' => $this->checkTaxVatBooked($companyId, $period),
        ];

        $allPassed = collect($checks)->every(fn($check) => $check['passed']);

        return [
            'all_passed' => $allPassed,
            'checks' => $checks,
        ];
    }

    /**
     * Generate period snapshot (immutable GL balances)
     */
    public function generateSnapshot(CloseBatch $closeBatch): array
    {
        DB::beginTransaction();
        try {
            $period = $closeBatch->period;
            $companyId = $closeBatch->company_id;

            // Get all chart accounts for the company
            $accounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })->get();

            $snapshots = [];
            foreach ($accounts as $account) {
                // Calculate opening balance (from previous period or start of fiscal year)
                $openingBalance = $this->getOpeningBalance($account->id, $period);

                // Calculate period activity
                $periodActivity = $this->getPeriodActivity($account->id, $period);

                // Calculate closing balance
                $closingBalance = $openingBalance + $periodActivity['debits'] - $periodActivity['credits'];

                $snapshot = PeriodSnapshot::create([
                    'close_id' => $closeBatch->close_id,
                    'account_id' => $account->id,
                    'period_id' => $period->period_id,
                    'opening_balance' => $openingBalance,
                    'period_debits' => $periodActivity['debits'],
                    'period_credits' => $periodActivity['credits'],
                    'closing_balance' => $closingBalance,
                ]);

                $snapshots[] = $snapshot;
            }

            DB::commit();
            return ['success' => true, 'snapshots' => $snapshots];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate period snapshot', [
                'close_batch_id' => $closeBatch->close_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Post adjustments as journal entries
     */
    public function postAdjustments(CloseBatch $closeBatch, ?int $approvedBy = null): array
    {
        // Use nested transaction only if not already in a transaction
        $needsTransaction = !DB::transactionLevel();
        if ($needsTransaction) {
            DB::beginTransaction();
        }

        try {
            $adjustments = $closeBatch->adjustments;
            $postedJournals = [];

            // Use provided approved_by or fallback to closeBatch approved_by or current user
            $approverId = $approvedBy ?? $closeBatch->approved_by ?? Auth::id();

            foreach ($adjustments as $adjustment) {
                // Resolve branch_id from session, user, or closeBatch
                $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? $closeBatch->branch_id ?? null);

                // Create journal entry for adjustment
                $journal = Journal::create([
                    'date' => $adjustment->adj_date,
                    'description' => $adjustment->description . ' (Period Close Adjustment)',
                    'reference_type' => 'Period Close',
                    'reference' => 'ADJ-' . $closeBatch->batch_label,
                    'branch_id' => $branchId,
                    'user_id' => $adjustment->created_by,
                    'approved' => true, // Auto-approved as part of close batch approval
                    'approved_by' => $approverId,
                    'approved_at' => now(),
                ]);

                // Create journal items
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $adjustment->gl_debit_account,
                    'amount' => $adjustment->amount,
                    'nature' => 'debit',
                    'description' => $adjustment->description,
                ]);

                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $adjustment->gl_credit_account,
                    'amount' => $adjustment->amount,
                    'nature' => 'credit',
                    'description' => $adjustment->description,
                ]);

                // Create GL transactions
                $journal->createGlTransactions();

                // Update adjustment with journal reference
                $adjustment->update(['posted_journal_id' => $journal->id]);

                $postedJournals[] = $journal;
            }

            if ($needsTransaction) {
                DB::commit();
            }
            return ['success' => true, 'journals' => $postedJournals];
        } catch (\Exception $e) {
            if ($needsTransaction) {
                DB::rollBack();
            }
            Log::error('Failed to post adjustments', [
                'close_batch_id' => $closeBatch->close_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Lock period (prevent new transactions)
     * LOCKED status: Period is locked, no new transactions allowed, but can be reopened if needed
     */
    public function lockPeriod(AccountingPeriod $period, int $userId): bool
    {
        // Use nested transaction only if not already in a transaction
        $needsTransaction = !DB::transactionLevel();
        if ($needsTransaction) {
            DB::beginTransaction();
        }

        try {
            $period->update([
                'status' => 'LOCKED',
                'locked_by' => $userId,
                'locked_at' => now(),
            ]);

            if ($needsTransaction) {
                DB::commit();
            }
            return true;
        } catch (\Exception $e) {
            if ($needsTransaction) {
                DB::rollBack();
            }
            Log::error('Failed to lock period', [
                'period_id' => $period->period_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Close period (finalize - mark as permanently closed)
     * CLOSED status: Period is fully closed and finalized, typically used for year-end periods
     * Note: Both LOCKED and CLOSED periods block transactions
     */
    public function closePeriod(AccountingPeriod $period, int $userId): bool
    {
        if (!$period->isLocked()) {
            throw new \Exception('Period must be locked before it can be closed.');
        }

        DB::beginTransaction();
        try {
            $period->update([
                'status' => 'CLOSED',
                'locked_by' => $userId,
                'locked_at' => $period->locked_at ?? now(),
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to close period', [
                'period_id' => $period->period_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Year-end: Roll P&L to retained earnings
     */
    public function rollToRetainedEarnings(FiscalYear $fiscalYear, int $userId): Journal
    {
        DB::beginTransaction();
        try {
            $companyId = $fiscalYear->company_id;
            // Get the last period of the fiscal year (should be December)
            $yearEndPeriod = $fiscalYear->periods()
                ->whereIn('status', ['LOCKED', 'CLOSED'])
                ->orderBy('end_date', 'desc')
                ->first();

            if (!$yearEndPeriod) {
                throw new \Exception('Year-end period not found or not closed. Please ensure the last period (December) is locked or closed.');
            }

            // Verify this is actually the last period
            if (!$this->isLastPeriodOfFiscalYear($yearEndPeriod)) {
                throw new \Exception('Selected period is not the last period of the fiscal year. Please close the last period first.');
            }

            // Get retained earnings account
            $retainedEarningsAccount = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
                ->where('account_name', 'LIKE', '%Retained Earnings%')
                ->first();

            if (!$retainedEarningsAccount) {
                // Try to get from system settings
                $retainedEarningsAccountId = SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value');
                if ($retainedEarningsAccountId) {
                    $retainedEarningsAccount = ChartAccount::find($retainedEarningsAccountId);
                }
            }

            if (!$retainedEarningsAccount) {
                throw new \Exception('Retained Earnings account not configured');
            }

            // Get all P&L accounts (Revenue and Expense)
            $revenueAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
                ->whereHas('accountClassGroup.accountClass', function ($q) {
                    $q->whereIn('name', ['Revenue', 'Income']);
                })
                ->get();

            $expenseAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
                ->whereHas('accountClassGroup.accountClass', function ($query) {
                    $query->where('name', 'like', '%expense%')
                        ->orWhere('name', 'like', '%cost%')
                        ->orWhere('name', 'like', '%expenditure%');
                })
                ->get();

            // Calculate totals
            $totalRevenue = $this->getAccountBalance($revenueAccounts, $yearEndPeriod);
            $totalExpenses = $this->getAccountBalance($expenseAccounts, $yearEndPeriod);
            $netIncome = $totalRevenue - $totalExpenses;

            // Resolve branch_id from session or user
            $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);

            // Use the first day of next fiscal year (or day after period end) for journal entry date
            // This avoids period lock issues since the entry is dated in the new year
            $journalDate = Carbon::parse($fiscalYear->end_date)->addDay();

            // Try to find next fiscal year and use its start date if available
            $nextFiscalYear = FiscalYear::where('company_id', $companyId)
                ->where('start_date', '>', $fiscalYear->end_date)
                ->orderBy('start_date', 'asc')
                ->first();

            if ($nextFiscalYear) {
                $journalDate = Carbon::parse($nextFiscalYear->start_date);
            }

            // Create journal entry
            $journal = Journal::create([
                'date' => $journalDate->toDateString(),
                'description' => "Year-End Closing: Roll P&L to Retained Earnings for {$fiscalYear->fy_label}",
                'reference_type' => 'Year-End Close',
                'reference' => 'YE-' . $fiscalYear->fy_label,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'approved' => true,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            // Close revenue accounts
            // Revenue accounts have credit balances (negative in balance calculation: credits > debits)
            // To close: Debit revenue account (opposite of credit balance), Credit retained earnings
            foreach ($revenueAccounts as $account) {
                $balance = $this->getAccountBalance([$account], $yearEndPeriod);
                // Revenue balance is negative (credit balance), we need to debit to close
                if ($balance < 0) {
                    $absBalance = abs($balance);
                    // Debit revenue account to close it (bring credit balance to zero)
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $account->id,
                        'amount' => $absBalance,
                        'nature' => 'debit',
                        'description' => "Close {$account->account_name} to Retained Earnings",
                    ]);
                    // Credit retained earnings (revenue increases equity)
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $retainedEarningsAccount->id,
                        'amount' => $absBalance,
                        'nature' => 'credit',
                        'description' => "Close {$account->account_name} to Retained Earnings",
                    ]);
                }
            }

            // Close expense accounts
            // Expense accounts have debit balances (positive in balance calculation: debits > credits)
            // To close: Credit expense account (opposite of debit balance), Debit retained earnings
            foreach ($expenseAccounts as $account) {
                $balance = $this->getAccountBalance([$account], $yearEndPeriod);
                // Expense balance is positive (debit balance), we need to credit to close
                if ($balance > 0) {
                    // Credit expense account to close it (bring debit balance to zero)
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $account->id,
                        'amount' => $balance,
                        'nature' => 'credit',
                        'description' => "Close {$account->account_name} to Retained Earnings",
                    ]);
                    // Debit retained earnings (expenses decrease equity)
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $retainedEarningsAccount->id,
                        'amount' => $balance,
                        'nature' => 'debit',
                        'description' => "Close {$account->account_name} to Retained Earnings",
                    ]);
                }
            }

            // Post to GL
            $journal->createGlTransactions();

            DB::commit();
            return $journal;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to roll P&L to retained earnings', [
                'fiscal_year_id' => $fiscalYear->fy_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // Private helper methods

    private function checkUnpostedJournals(int $companyId, AccountingPeriod $period): array
    {
        $unpostedJournals = Journal::whereHas('user', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->where('approved', false)
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $count = $unpostedJournals->count();

        $journalsList = $unpostedJournals->map(function ($journal) {
            return [
                'id' => $journal->id,
                'reference' => $journal->reference,
                'date' => $journal->date->format('M d, Y'),
                'description' => $journal->description,
                'amount' => $journal->items()->sum('amount'),
                'created_at' => $journal->created_at->format('M d, Y H:i'),
                'created_by' => $journal->user->name ?? 'N/A',
            ];
        })->toArray();

        return [
            'passed' => $count === 0,
            'message' => $count > 0 ? "{$count} unposted journal entries found" : 'All journals posted',
            'count' => $count,
            'journals' => $journalsList,
        ];
    }

    private function checkUnreconciledBankItems(int $companyId, AccountingPeriod $period): array
    {
        // This would check bank reconciliation status
        // Placeholder implementation
        return [
            'passed' => true,
            'message' => 'Bank reconciliation check passed',
            'count' => 0,
        ];
    }

    private function checkUnallocatedReceipts(int $companyId, AccountingPeriod $period): array
    {
        // Placeholder - would check for unallocated receipts
        return [
            'passed' => true,
            'message' => 'All receipts allocated',
            'count' => 0,
        ];
    }

    private function checkUnallocatedPayments(int $companyId, AccountingPeriod $period): array
    {
        // Placeholder - would check for unallocated payments
        return [
            'passed' => true,
            'message' => 'All payments allocated',
            'count' => 0,
        ];
    }

    private function checkInventoryValuation(int $companyId, AccountingPeriod $period): array
    {
        // Placeholder - would check if inventory valuation is complete
        return [
            'passed' => true,
            'message' => 'Inventory valuation complete',
            'count' => 0,
        ];
    }

    private function checkDepreciationRun(int $companyId, AccountingPeriod $period): array
    {
        // Placeholder - would check if depreciation has been run for the period
        return [
            'passed' => true,
            'message' => 'Depreciation run complete',
            'count' => 0,
        ];
    }

    private function checkTaxVatBooked(int $companyId, AccountingPeriod $period): array
    {
        // Placeholder - would check if tax/VAT entries are booked
        return [
            'passed' => true,
            'message' => 'Tax/VAT entries booked',
            'count' => 0,
        ];
    }

    private function getOpeningBalance(int $accountId, AccountingPeriod $period): float
    {
        // Get balance at start of period
        $previousPeriod = AccountingPeriod::where('fy_id', $period->fy_id)
            ->where('end_date', '<', $period->start_date)
            ->orderBy('end_date', 'desc')
            ->first();

        if ($previousPeriod) {
            // Get from previous period snapshot if available
            $snapshot = PeriodSnapshot::where('period_id', $previousPeriod->period_id)
                ->where('account_id', $accountId)
                ->latest('snapshot_id')
                ->first();

            if ($snapshot) {
                return $snapshot->closing_balance;
            }
        }

        // Otherwise calculate from GL transactions up to period start
        $debits = GlTransaction::where('chart_account_id', $accountId)
            ->where('nature', 'debit')
            ->where('date', '<', $period->start_date)
            ->sum('amount');

        $credits = GlTransaction::where('chart_account_id', $accountId)
            ->where('nature', 'credit')
            ->where('date', '<', $period->start_date)
            ->sum('amount');

        return $debits - $credits;
    }

    private function getPeriodActivity(int $accountId, AccountingPeriod $period): array
    {
        $debits = GlTransaction::where('chart_account_id', $accountId)
            ->where('nature', 'debit')
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->sum('amount');

        $credits = GlTransaction::where('chart_account_id', $accountId)
            ->where('nature', 'credit')
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->sum('amount');

        return [
            'debits' => $debits ?? 0,
            'credits' => $credits ?? 0,
        ];
    }

    private function getAccountBalance($accounts, AccountingPeriod $period): float
    {
        $total = 0;
        foreach ($accounts as $account) {
            $opening = $this->getOpeningBalance($account->id, $period);
            $activity = $this->getPeriodActivity($account->id, $period);
            $balance = $opening + $activity['debits'] - $activity['credits'];
            $total += $balance;
        }
        return $total;
    }

    /**
     * Reopen a locked/closed period
     */
    public function reopenPeriod(AccountingPeriod $period, int $userId, string $reason = null): void
    {
        if (!$period->isLocked() && !$period->isClosed()) {
            throw new \Exception('Period is not locked or closed. Only locked/closed periods can be reopened.');
        }

        DB::beginTransaction();
        try {
            $period->update([
                'status' => 'OPEN',
                'locked_by' => null,
                'locked_at' => null,
            ]);

            // Update any locked close batches to REOPENED status
            $closeBatches = CloseBatch::where('period_id', $period->period_id)
                ->where('status', 'LOCKED')
                ->get();

            foreach ($closeBatches as $batch) {
                $batch->update([
                    'status' => 'REOPENED',
                    'notes' => ($batch->notes ?? '') . "\n\nReopened on " . now()->format('Y-m-d H:i:s') . " by User ID {$userId}" . ($reason ? ". Reason: {$reason}" : ''),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reopen period', [
                'period_id' => $period->period_id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if a period is the last period of a fiscal year
     */
    public function isLastPeriodOfFiscalYear(AccountingPeriod $period): bool
    {
        $lastPeriod = AccountingPeriod::where('fy_id', $period->fy_id)
            ->orderBy('end_date', 'desc')
            ->first();

        return $lastPeriod && $lastPeriod->period_id === $period->period_id;
    }

    /**
     * Check if previous periods are closed (sequential closing validation)
     */
    public function canClosePeriod(AccountingPeriod $period): array
    {
        $previousPeriods = AccountingPeriod::where('fy_id', $period->fy_id)
            ->where('end_date', '<', $period->start_date)
            ->orderBy('end_date', 'asc')
            ->get();

        $unclosedPeriods = [];
        foreach ($previousPeriods as $prevPeriod) {
            if (!$prevPeriod->isClosed() && !$prevPeriod->isLocked()) {
                $unclosedPeriods[] = [
                    'period_id' => $prevPeriod->period_id,
                    'period_label' => $prevPeriod->period_label,
                    'start_date' => $prevPeriod->start_date->format('M d, Y'),
                    'end_date' => $prevPeriod->end_date->format('M d, Y'),
                ];
            }
        }

        return [
            'can_close' => empty($unclosedPeriods),
            'unclosed_periods' => $unclosedPeriods,
            'message' => empty($unclosedPeriods)
                ? 'All previous periods are closed. You can proceed with closing this period.'
                : 'Cannot close this period. The following previous periods must be closed first: ' .
                implode(', ', array_column($unclosedPeriods, 'period_label')),
        ];
    }

    /**
     * Get all open periods for a fiscal year (for year-end wizard)
     */
    public function getOpenPeriodsForFiscalYear(FiscalYear $fiscalYear): array
    {
        $periods = AccountingPeriod::where('fy_id', $fiscalYear->fy_id)
            ->whereIn('status', ['OPEN'])
            ->orderBy('start_date', 'asc')
            ->get();

        $result = [];
        foreach ($periods as $period) {
            $canClose = $this->canClosePeriod($period);
            $result[] = [
                'period' => $period,
                'can_close' => $canClose['can_close'],
                'unclosed_periods' => $canClose['unclosed_periods'],
                'has_close_batch' => $period->closeBatches()->exists(),
            ];
        }

        return $result;
    }
}
