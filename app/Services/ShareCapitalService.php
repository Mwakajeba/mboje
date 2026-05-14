<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use App\Models\Shares\ShareIssue;
use App\Models\Shares\ShareDividend;
use App\Models\Shares\ShareCorporateAction;
use App\Models\Shares\ShareClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ShareCapitalService
{
    /**
     * Post share issue to GL
     * 
     * @param ShareIssue $shareIssue
     * @param array $accountMappings - ['share_capital_account_id', 'share_premium_account_id', 'bank_account_id', 'issue_costs_account_id']
     * @return Journal
     * @throws Exception
     */
    public function postShareIssue(ShareIssue $shareIssue, array $accountMappings): Journal
    {
        // Use nested transaction only if not already in a transaction
        $needsTransaction = DB::transactionLevel() === 0;
        if ($needsTransaction) {
            DB::beginTransaction();
        }
        
        try {
            // Load relationships
            if (!$shareIssue->relationLoaded('shareClass')) {
                $shareIssue->load('shareClass');
            }
            $shareClass = $shareIssue->shareClass;
            
            if (!$shareClass) {
                throw new Exception('Share class not found for share issue.');
            }
            
            // Calculate amounts
            $parValue = $shareClass->par_value ?? $shareIssue->par_value ?? 0;
            $pricePerShare = $shareIssue->price_per_share ?? $parValue;
            $totalShares = $shareIssue->total_shares;
            $totalProceeds = $shareIssue->total_amount;
            
            $shareCapitalAmount = $parValue * $totalShares;
            $sharePremiumAmount = $totalProceeds - $shareCapitalAmount;
            
            // Create journal
            // Convert date to Carbon instance if it's a string
            $issueDate = $shareIssue->issue_date;
            if (is_string($issueDate)) {
                $issueDate = \Carbon\Carbon::parse($issueDate);
            }
            
            // Get branch_id from session, user, or default to first branch
            $branchId = session('branch_id') 
                ?? (auth()->user()->branch_id ?? null)
                ?? (\App\Models\Branch::where('company_id', $shareIssue->company_id)->value('id'));
            
            if (!$branchId) {
                throw new Exception('Branch ID is required for journal creation. Please select a branch.');
            }
            
            $journal = Journal::create([
                'branch_id' => $branchId,
                'date' => $issueDate,
                'reference' => $shareIssue->reference_number ?? 'SHARE-ISSUE-' . $shareIssue->id,
                'reference_type' => 'share_issue',
                'description' => "Share Issue: {$shareClass->name} - {$totalShares} shares",
                'user_id' => auth()->id() ?? $shareIssue->created_by,
            ]);
            
            // Debit: Bank Account (or asset account for non-cash)
            if (!isset($accountMappings['bank_account_id'])) {
                throw new Exception('Bank account mapping is required for share issue.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['bank_account_id'],
                'nature' => 'debit',
                'amount' => $totalProceeds,
                'description' => "Proceeds from share issue",
            ]);
            
            // Credit: Share Capital
            if (!isset($accountMappings['share_capital_account_id'])) {
                throw new Exception('Share capital account mapping is required.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['share_capital_account_id'],
                'nature' => 'credit',
                'amount' => $shareCapitalAmount,
                'description' => "Share Capital - {$shareClass->name}",
            ]);
            
            // Credit: Share Premium (if any)
            if ($sharePremiumAmount > 0) {
                if (!isset($accountMappings['share_premium_account_id']) || empty($accountMappings['share_premium_account_id'])) {
                    // If no premium account specified, post premium to share capital account
                    $premiumAccountId = $accountMappings['share_capital_account_id'];
                } else {
                    $premiumAccountId = $accountMappings['share_premium_account_id'];
                }
                
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $premiumAccountId,
                    'nature' => 'credit',
                    'amount' => $sharePremiumAmount,
                    'description' => "Share Premium - {$shareClass->name}",
                ]);
            }
            
            // Issue costs (if any) - reduce equity
            if (isset($accountMappings['issue_costs']) && $accountMappings['issue_costs'] > 0) {
                $costAccountId = $accountMappings['share_premium_account_id'] ?? $accountMappings['share_capital_account_id'];
                
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $costAccountId,
                    'nature' => 'debit',
                    'amount' => $accountMappings['issue_costs'],
                    'description' => "Share issue costs",
                ]);
                
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $accountMappings['bank_account_id'],
                    'nature' => 'credit',
                    'amount' => $accountMappings['issue_costs'],
                    'description' => "Payment of share issue costs",
                ]);
            }
            
            // Auto-approve and post to GL
            $journal->approved = true;
            $journal->approved_by = auth()->id() ?? $shareIssue->created_by;
            $journal->approved_at = now();
            $journal->save();
            
            // Refresh journal and load items relationship
            $journal->refresh();
            $journal->load('items');
            
            // Create GL transactions using Journal's method (which has proper validation)
            $journal->createGlTransactions();
            
            // Update share issue status
            $shareIssue->update([
                'status' => 'posted',
                'posted_by' => auth()->id() ?? $shareIssue->created_by,
            ]);
            
            if ($needsTransaction) {
                DB::commit();
            }
            return $journal->fresh();
            
        } catch (Exception $e) {
            if ($needsTransaction) {
                DB::rollBack();
            }
            Log::error('Failed to post share issue to GL', [
                'share_issue_id' => $shareIssue->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Post cash dividend declaration to GL
     * 
     * @param ShareDividend $dividend
     * @param array $accountMappings - ['retained_earnings_account_id', 'dividend_payable_account_id', 'withholding_tax_account_id']
     * @return Journal
     * @throws Exception
     */
    public function postDividendDeclaration(ShareDividend $dividend, array $accountMappings): Journal
    {
        if ($dividend->dividend_type !== 'cash') {
            throw new Exception('Only cash dividends can be declared via this method.');
        }
        
        DB::beginTransaction();
        try {
            // Total declared amount from dividend record
            $totalGrossAmount = $dividend->total_amount ?? 0;

            // Withholding tax should match the sum of per‑share WHT calculated on payments
            $withholdingTaxAmount = $dividend->dividendPayments()
                ->sum('withholding_tax_amount') ?? 0;

            // Net dividend is what will actually be paid to shareholders
            $netDividendAmount = $totalGrossAmount - $withholdingTaxAmount;
            
            // Resolve branch_id (required for journals)
            $branchId = session('branch_id')
                ?? (auth()->user()->branch_id ?? null);
            
            if (!$branchId) {
                throw new Exception('Branch ID is required for dividend declaration. Please select a branch.');
            }
            
            // Create journal
            $journal = Journal::create([
                'branch_id' => $branchId,
                'date' => $dividend->declaration_date,
                'reference' => 'DIV-' . $dividend->id,
                'reference_type' => 'dividend_declaration',
                'description' => "Dividend Declaration: " . ($dividend->shareClass->name ?? 'All Classes'),
                'user_id' => auth()->id() ?? $dividend->created_by,
            ]);
            
            // Debit: Retained Earnings
            if (!isset($accountMappings['retained_earnings_account_id'])) {
                throw new Exception('Retained earnings account mapping is required.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['retained_earnings_account_id'],
                'nature' => 'debit',
                'amount' => $totalGrossAmount,
                'description' => "Dividend declared",
            ]);
            
            // Credit: Dividend Payable
            if (!isset($accountMappings['dividend_payable_account_id'])) {
                throw new Exception('Dividend payable account mapping is required.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['dividend_payable_account_id'],
                'nature' => 'credit',
                'amount' => $netDividendAmount,
                'description' => "Dividend payable to shareholders",
            ]);
            
            // Credit: Withholding Tax Payable (if applicable)
            if ($withholdingTaxAmount > 0) {
                if (!isset($accountMappings['withholding_tax_account_id'])) {
                    throw new Exception('Withholding tax account mapping is required when tax is applicable.');
                }
                
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $accountMappings['withholding_tax_account_id'],
                    'nature' => 'credit',
                    'amount' => $withholdingTaxAmount,
                    'description' => "Withholding tax on dividends",
                ]);
            }
            
            // Auto-approve and post to GL
            $journal->approved = true;
            $journal->approved_by = auth()->id() ?? $dividend->created_by;
            $journal->approved_at = now();
            $journal->save();
            
            $this->createGlTransactions($journal);
            
            // Update dividend status
            $dividend->update([
                'status' => 'declared',
            ]);
            
            DB::commit();
            return $journal->fresh();
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to post dividend declaration to GL', [
                'dividend_id' => $dividend->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Post dividend payment to GL
     * 
     * @param ShareDividend $dividend
     * @param array $accountMappings - ['dividend_payable_account_id', 'bank_account_id']
     * @return Journal
     * @throws Exception
     */
    public function postDividendPayment(ShareDividend $dividend, array $accountMappings): Journal
    {
        DB::beginTransaction();
        try {
            // Use all pending payments (this call is what actually pays them)
            $totalPaid = $dividend->dividendPayments()
                ->where('status', 'pending')
                ->sum('net_amount') ?? 0;
            
            if ($totalPaid <= 0) {
                throw new Exception('No paid dividend payments found.');
            }
            
            // Resolve branch_id (required for journals)
            $branchId = session('branch_id')
                ?? (auth()->user()->branch_id ?? null);
            
            if (!$branchId) {
                throw new Exception('Branch ID is required for dividend payment. Please select a branch.');
            }
            
            // Create journal
            $journal = Journal::create([
                'branch_id' => $branchId,
                'date' => $dividend->payment_date ?? now(),
                'reference' => 'DIV-PAY-' . $dividend->id,
                'reference_type' => 'dividend_payment',
                'description' => "Dividend Payment: " . ($dividend->shareClass->name ?? 'All Classes'),
                'user_id' => auth()->id() ?? $dividend->created_by,
            ]);
            
            // Debit: Dividend Payable
            if (!isset($accountMappings['dividend_payable_account_id'])) {
                throw new Exception('Dividend payable account mapping is required.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['dividend_payable_account_id'],
                'nature' => 'debit',
                'amount' => $totalPaid,
                'description' => "Dividend payment to shareholders",
            ]);
            
            // Credit: Bank Account
            if (!isset($accountMappings['bank_account_id'])) {
                throw new Exception('Bank account mapping is required for dividend payment.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['bank_account_id'],
                'nature' => 'credit',
                'amount' => $totalPaid,
                'description' => "Payment of dividends",
            ]);
            
            // Auto-approve and post to GL
            $journal->approved = true;
            $journal->approved_by = auth()->id() ?? $dividend->created_by;
            $journal->approved_at = now();
            $journal->save();
            
            $this->createGlTransactions($journal);
            
            // Update dividend status
            $dividend->update([
                'status' => 'paid',
            ]);
            
            DB::commit();
            return $journal->fresh();
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to post dividend payment to GL', [
                'dividend_id' => $dividend->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Post bonus/share dividend (capitalization of reserves)
     * 
     * @param ShareDividend $dividend
     * @param array $accountMappings - ['retained_earnings_account_id', 'share_capital_account_id']
     * @return Journal
     * @throws Exception
     */
    public function postBonusDividend(ShareDividend $dividend, array $accountMappings): Journal
    {
        if ($dividend->dividend_type !== 'bonus') {
            throw new Exception('Only bonus dividends can be processed via this method.');
        }
        
        DB::beginTransaction();
        try {
            $totalAmount = $dividend->total_amount ?? 0;
            
            if ($totalAmount <= 0) {
                throw new Exception('Bonus dividend amount must be greater than zero.');
            }
            
            // Resolve branch_id (required for journals)
            $branchId = session('branch_id')
                ?? (auth()->user()->branch_id ?? null);
            
            if (!$branchId) {
                throw new Exception('Branch ID is required for bonus dividend. Please select a branch.');
            }
            
            // Create journal
            $journal = Journal::create([
                'branch_id' => $branchId,
                'date' => $dividend->declaration_date,
                'reference' => 'BONUS-DIV-' . $dividend->id,
                'reference_type' => 'bonus_dividend',
                'description' => "Bonus Dividend: " . ($dividend->shareClass->name ?? 'All Classes'),
                'user_id' => auth()->id() ?? $dividend->created_by,
            ]);
            
            // Debit: Retained Earnings or Share Premium
            if (!isset($accountMappings['source_account_id'])) {
                throw new Exception('Source account (retained earnings or share premium) mapping is required.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['source_account_id'],
                'nature' => 'debit',
                'amount' => $totalAmount,
                'description' => "Capitalization of reserves for bonus shares",
            ]);
            
            // Credit: Share Capital
            if (!isset($accountMappings['share_capital_account_id'])) {
                throw new Exception('Share capital account mapping is required.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['share_capital_account_id'],
                'nature' => 'credit',
                'amount' => $totalAmount,
                'description' => "Share Capital - Bonus Issue",
            ]);
            
            // Auto-approve and post to GL
            $journal->approved = true;
            $journal->approved_by = auth()->id() ?? $dividend->created_by;
            $journal->approved_at = now();
            $journal->save();
            
            $this->createGlTransactions($journal);
            
            // Update dividend status
            $dividend->update([
                'status' => 'declared',
            ]);
            
            DB::commit();
            return $journal->fresh();
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to post bonus dividend to GL', [
                'dividend_id' => $dividend->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Post share buyback to GL (treasury shares)
     * 
     * @param ShareCorporateAction $action
     * @param int $totalShares
     * @param float $totalCost
     * @param array $accountMappings - ['bank_account_id', 'treasury_shares_account_id']
     * @return Journal
     * @throws Exception
     */
    public function postShareBuyback(ShareCorporateAction $action, int $totalShares, float $totalCost, array $accountMappings): Journal
    {
        if ($action->action_type !== 'buyback') {
            throw new Exception('Only buyback actions can be processed via this method.');
        }
        
        DB::beginTransaction();
        try {
            // Create journal
            $journal = Journal::create([
                'branch_id' => null,
                'date' => $action->effective_date ?? now(),
                'reference' => 'BUYBACK-' . $action->id,
                'reference_type' => 'share_buyback',
                'description' => "Share Buyback: " . ($action->shareClass->name ?? 'N/A') . " - {$totalShares} shares",
                'user_id' => auth()->id() ?? $action->created_by,
            ]);
            
            // Debit: Treasury Shares (contra-equity)
            if (!isset($accountMappings['treasury_shares_account_id'])) {
                throw new Exception('Treasury shares account mapping is required.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['treasury_shares_account_id'],
                'nature' => 'debit',
                'amount' => $totalCost,
                'description' => "Treasury shares acquired",
            ]);
            
            // Credit: Bank Account
            if (!isset($accountMappings['bank_account_id'])) {
                throw new Exception('Bank account mapping is required for buyback.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accountMappings['bank_account_id'],
                'nature' => 'credit',
                'amount' => $totalCost,
                'description' => "Payment for share buyback",
            ]);
            
            // Auto-approve and post to GL
            $journal->approved = true;
            $journal->approved_by = auth()->id() ?? $action->created_by;
            $journal->approved_at = now();
            $journal->save();
            
            $this->createGlTransactions($journal);
            
            // Update action status
            $action->update([
                'status' => 'executed',
                'executed_by' => auth()->id() ?? $action->created_by,
            ]);
            
            DB::commit();
            return $journal->fresh();
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to post share buyback to GL', [
                'action_id' => $action->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Create GL transactions from journal items
     * 
     * @param Journal $journal
     * @return void
     */
    protected function createGlTransactions(Journal $journal): void
    {
        foreach ($journal->items as $item) {
            GlTransaction::create([
                'chart_account_id' => $item->chart_account_id,
                'amount' => $item->amount,
                'nature' => $item->nature,
                'transaction_id' => $journal->id,
                'transaction_type' => 'journal',
                'date' => $journal->date,
                'description' => $item->description ?? $journal->description,
                'branch_id' => $journal->branch_id,
                'user_id' => $journal->user_id,
            ]);
        }
    }
}

