<?php

namespace App\Services;

use App\Models\Sales\SalesInvoice;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\BankAccount;
use App\Models\GlRevaluationHistory;
use App\Models\Company;
use App\Models\Journal;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use App\Services\ExchangeRateService;
use App\Services\FxTransactionRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FxRevaluationService
{
    protected $exchangeRateService;
    protected $fxTransactionRateService;

    public function __construct(ExchangeRateService $exchangeRateService, FxTransactionRateService $fxTransactionRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->fxTransactionRateService = $fxTransactionRateService;
    }

    /**
     * Identify all monetary items to revalue
     * 
     * @param int $companyId
     * @param int|null $branchId
     * @param string $asOfDate
     * @return array
     */
    public function identifyMonetaryItems($companyId, $branchId = null, $asOfDate = null)
    {
        $asOfDate = $asOfDate ?? now()->toDateString();
        $functionalCurrency = $this->getFunctionalCurrency($companyId);

        $items = [
            'AR' => [],
            'AP' => [],
            'BANK' => [],
            'LOAN' => [],
        ];

        // 1. Accounts Receivable (AR) - Open sales invoices in FCY
        $arQuery = SalesInvoice::where('company_id', $companyId)
            ->where('currency', '!=', $functionalCurrency)
            ->where('balance_due', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $asOfDate);

        if ($branchId) {
            $arQuery->where('branch_id', $branchId);
        }

        $salesInvoices = $arQuery->get();
        foreach ($salesInvoices as $invoice) {
            $items['AR'][] = [
                'id' => $invoice->id,
                'ref' => $invoice->invoice_number,
                'date' => $invoice->invoice_date,
                'currency' => $invoice->currency,
                'fcy_amount' => $invoice->balance_due,
                'original_rate' => $invoice->exchange_rate,
                'lcy_amount' => $invoice->balance_due * $invoice->exchange_rate,
                'customer_id' => $invoice->customer_id,
                'branch_id' => $invoice->branch_id,
            ];
        }

        // 2. Accounts Payable (AP) - Open purchase invoices in FCY
        $apQuery = PurchaseInvoice::where('company_id', $companyId)
            ->where('currency', '!=', $functionalCurrency)
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $asOfDate);

        if ($branchId) {
            $apQuery->where('branch_id', $branchId);
        }

        $purchaseInvoices = $apQuery->get();
        foreach ($purchaseInvoices as $invoice) {
            $balanceDue = $invoice->total_amount - ($invoice->total_paid ?? 0);
            if ($balanceDue > 0) {
                $items['AP'][] = [
                    'id' => $invoice->id,
                    'ref' => $invoice->invoice_number,
                    'date' => $invoice->invoice_date,
                    'currency' => $invoice->currency,
                    'fcy_amount' => $balanceDue,
                    'original_rate' => $invoice->exchange_rate,
                    'lcy_amount' => $balanceDue * $invoice->exchange_rate,
                    'supplier_id' => $invoice->supplier_id,
                    'branch_id' => $invoice->branch_id,
                ];
            }
        }

        // 3. Bank Accounts - FCY bank accounts with balances
        // Only include bank accounts that:
        // - Have currency different from functional currency
        // - Have revaluation_required = true
        // - Have a balance (FCY amount)
        $bankAccounts = BankAccount::where('company_id', $companyId)
            ->where('currency', '!=', $functionalCurrency)
            ->whereNotNull('currency')
            ->where('revaluation_required', true)
            ->get();

        foreach ($bankAccounts as $bank) {
            // Get FCY balance from GL transactions
            // For bank accounts, we need to track FCY and LCY separately
            // The balance attribute gives us LCY balance, but we need FCY balance
            $balance = $bank->balance ?? 0;
            
            if (abs($balance) > 0.01) {
                // Calculate FCY balance from GL transactions
                // We need to sum up FCY amounts from transactions related to this bank account
                $fcyBalance = $this->calculateBankAccountFcyBalance($bank, $asOfDate);
                
                // Get the average rate from transactions to calculate original LCY value
                // This represents the weighted average rate of all transactions
                $originalRate = $this->getBankAccountAverageRate($bank, $asOfDate, $companyId);
                
                // If no transactions found, use creation date rate
                if ($originalRate == 1.0 && $bank->created_at) {
                    $originalRate = $this->fxTransactionRateService->getTransactionRate(
                        $bank->currency,
                        $functionalCurrency,
                        $bank->created_at->toDateString(),
                        $companyId
                    )['rate'] ?? 1.0;
                }

                $items['BANK'][] = [
                    'id' => $bank->id,
                    'ref' => $bank->account_number . ' - ' . $bank->name,
                    'date' => $asOfDate,
                    'currency' => $bank->currency,
                    'fcy_amount' => $fcyBalance,
                    'original_rate' => $originalRate,
                    'lcy_amount' => $fcyBalance * $originalRate, // Historical LCY value
                    'chart_account_id' => $bank->chart_account_id,
                    'branch_id' => null, // Bank accounts typically don't have branch_id
                    'item_type' => 'BANK',
                ];
            }
        }

        return $items;
    }

    /**
     * Calculate unrealized gain/loss
     * 
     * @param array $item
     * @param float $closingRate
     * @param float $originalRate
     * @param float $fcyAmount
     * @return float
     */
    public function calculateUnrealizedGainLoss($item, $closingRate, $originalRate, $fcyAmount)
    {
        // Determine item type from item array
        $itemType = is_array($item) && isset($item['item_type']) ? $item['item_type'] : null;
        
        if ($itemType === 'AP') {
            // For AP (Accounts Payable): When rate goes DOWN, you pay LESS local currency = GAIN
            // Formula: (Original Rate - Closing Rate) × FCY Amount
            // Example: Original 2566, Closing 2449, FCY 7.93
            //   (2566 - 2449) × 7.93 = 117 × 7.93 = 927.81 (GAIN)
            $gainLoss = ($originalRate - $closingRate) * $fcyAmount;
        } else {
            // For AR (Accounts Receivable), BANK, LOAN, etc.: When rate goes DOWN, you receive LESS = LOSS
            // Formula: (Closing Rate - Original Rate) × FCY Amount
            // Example: Original 2762, Closing 2449, FCY 1.47
            //   (2449 - 2762) × 1.47 = -313 × 1.47 = -459.83 (LOSS)
            $gainLoss = ($closingRate - $originalRate) * $fcyAmount;
        }
        
        return round($gainLoss, 2);
    }

    /**
     * Generate revaluation preview
     * 
     * @param int $companyId
     * @param int|null $branchId
     * @param string $revaluationDate
     * @return array
     */
    public function generateRevaluationPreview($companyId, $branchId = null, $revaluationDate = null)
    {
        $revaluationDate = $revaluationDate ?? now()->toDateString();
        $functionalCurrency = $this->getFunctionalCurrency($companyId);

        // Identify all monetary items
        $items = $this->identifyMonetaryItems($companyId, $branchId, $revaluationDate);

        $preview = [
            'revaluation_date' => $revaluationDate,
            'functional_currency' => $functionalCurrency,
            'items' => [],
            'summary' => [
                'total_items' => 0,
                'total_gain' => 0,
                'total_loss' => 0,
                'net_gain_loss' => 0,
            ],
        ];

        // Process each item type
        $year = Carbon::parse($revaluationDate)->year;
        $month = Carbon::parse($revaluationDate)->month;
        
        foreach ($items as $itemType => $itemList) {
            foreach ($itemList as $item) {
                // Add item_type to item array for gain/loss calculation
                $item['item_type'] = $itemType;
                
                // Get closing rate for the revaluation date (month-end closing rate)
                $closingRate = $this->fxTransactionRateService->getMonthEndClosingRate(
                    $item['currency'],
                    $functionalCurrency,
                    $year,
                    $month,
                    $companyId
                );

                // If month-end rate not available, use spot rate for the date
                if (!$closingRate) {
                    $closingRate = $this->exchangeRateService->getSpotRate(
                        $item['currency'],
                        $functionalCurrency,
                        $revaluationDate,
                        $companyId
                    ) ?? $item['original_rate'];
                }

                // Calculate gain/loss
                $gainLoss = $this->calculateUnrealizedGainLoss(
                    $item,
                    $closingRate,
                    $item['original_rate'],
                    $item['fcy_amount']
                );

                // Calculate new LCY amount at closing rate
                $newLcyAmount = $item['fcy_amount'] * $closingRate;

                $preview['items'][] = [
                    'item_type' => $itemType,
                    'item_id' => $item['id'],
                    'item_ref' => $item['ref'],
                    'item_date' => $item['date'],
                    'currency' => $item['currency'],
                    'fcy_amount' => $item['fcy_amount'],
                    'original_rate' => $item['original_rate'],
                    'closing_rate' => $closingRate,
                    'original_lcy_amount' => $item['lcy_amount'],
                    'new_lcy_amount' => $newLcyAmount,
                    'gain_loss' => $gainLoss,
                    'branch_id' => $item['branch_id'] ?? null,
                ];

                // Update summary
                $preview['summary']['total_items']++;
                if ($gainLoss > 0) {
                    $preview['summary']['total_gain'] += $gainLoss;
                } else {
                    $preview['summary']['total_loss'] += abs($gainLoss);
                }
                $preview['summary']['net_gain_loss'] += $gainLoss;
            }
        }

        // Round summary amounts
        $preview['summary']['total_gain'] = round($preview['summary']['total_gain'], 2);
        $preview['summary']['total_loss'] = round($preview['summary']['total_loss'], 2);
        $preview['summary']['net_gain_loss'] = round($preview['summary']['net_gain_loss'], 2);

        return $preview;
    }

    /**
     * Post revaluation journal entries
     * 
     * @param int $companyId
     * @param int|null $branchId
     * @param string $revaluationDate
     * @param array $previewData
     * @param int $userId
     * @return array
     */
    public function postRevaluation($companyId, $branchId = null, $revaluationDate, $previewData, $userId)
    {
        DB::beginTransaction();
        try {
            // Check if revaluation has already been posted for this period
            $revaluationDateCarbon = \Carbon\Carbon::parse($revaluationDate);
            $year = $revaluationDateCarbon->year;
            $month = $revaluationDateCarbon->month;
            
            $existingRevaluation = \App\Models\GlRevaluationHistory::where('company_id', $companyId)
                ->whereYear('revaluation_date', $year)
                ->whereMonth('revaluation_date', $month)
                ->where('is_reversed', false)
                ->where(function($query) use ($branchId) {
                    if ($branchId) {
                        // If branch is specified, check for revaluations for that specific branch
                        $query->where('branch_id', $branchId);
                    } else {
                        // If no branch is specified, check for company-level revaluations (branch_id is null)
                        $query->whereNull('branch_id');
                    }
                })
                ->exists();
            
            if ($existingRevaluation) {
                $periodName = $revaluationDateCarbon->format('F Y');
                throw new \Exception("A revaluation has already been posted for the period {$periodName}. Please reverse the existing revaluation before posting a new one for the same period.");
            }

            // Validate preview data structure
            if (!isset($previewData['items']) || !is_array($previewData['items'])) {
                throw new \Exception('Invalid preview data: items array is missing or invalid.');
            }

            $functionalCurrency = $this->getFunctionalCurrency($companyId);
            $journalEntries = [];
            $revaluationHistoryRecords = [];
            $itemsProcessed = 0;

            foreach ($previewData['items'] as $item) {
                if (abs($item['gain_loss']) < 0.01) {
                    continue; // Skip items with no gain/loss
                }
                
                $itemsProcessed++;

                // Add company_id to item array
                $item['company_id'] = $companyId;

                // Get FX gain/loss account IDs
                $fxGainAccountId = $this->getFxGainAccountId($companyId);
                $fxLossAccountId = $this->getFxLossAccountId($companyId);

                // Create journal entry based on item type
                try {
                    $journal = $this->createRevaluationJournalEntry(
                        $item,
                        $fxGainAccountId,
                        $fxLossAccountId,
                        $functionalCurrency,
                        $revaluationDate,
                        $userId
                    );

                    if ($journal) {
                        $journalEntries[] = $journal;

                        // Create revaluation history record
                        $historyRecord = GlRevaluationHistory::create([
                            'revaluation_date' => $revaluationDate,
                            'item_type' => $item['item_type'],
                            'item_ref' => $item['item_ref'],
                            'item_id' => $item['item_id'],
                            'original_rate' => $item['original_rate'],
                            'closing_rate' => $item['closing_rate'],
                            'base_amount' => $item['new_lcy_amount'],
                            'fcy_amount' => $item['fcy_amount'],
                            'gain_loss' => $item['gain_loss'],
                            'posted_journal_id' => $journal->id,
                            'is_reversed' => false,
                            'company_id' => $companyId,
                            'branch_id' => $item['branch_id'] ?? $branchId,
                            'created_by' => $userId,
                        ]);

                        // Log posting activity
                        $historyRecord->logActivity('post', "Posted FX Revaluation - {$item['item_type']} - {$item['item_ref']}", [
                            'journal_reference' => $journal->reference,
                            'gain_loss' => number_format($item['gain_loss'], 2)
                        ]);

                        $revaluationHistoryRecords[] = $historyRecord;
                    } else {
                        // Journal creation failed - log the reason
                        Log::error("Failed to create journal for FX revaluation item", [
                            'item_type' => $item['item_type'],
                            'item_ref' => $item['item_ref'],
                            'item_id' => $item['item_id'],
                            'gain_loss' => $item['gain_loss'],
                            'reason' => 'createRevaluationJournalEntry returned null - likely missing account ID'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Exception while creating journal for FX revaluation item", [
                        'item_type' => $item['item_type'],
                        'item_ref' => $item['item_ref'],
                        'item_id' => $item['item_id'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Re-throw to rollback transaction
                    throw $e;
                }
            }

            // Check if any items were processed
            if ($itemsProcessed === 0) {
                DB::rollBack();
                throw new \Exception('No items with gain/loss to process. All items have zero gain/loss.');
            }

            DB::commit();

            Log::info('FX Revaluation posted successfully', [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'revaluation_date' => $revaluationDate,
                'journals_created' => count($journalEntries),
                'history_records' => count($revaluationHistoryRecords),
            ]);

            return [
                'success' => true,
                'journals_created' => count($journalEntries),
                'history_records' => count($revaluationHistoryRecords),
                'journals' => $journalEntries,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FX Revaluation posting failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create revaluation journal entry
     * 
     * @param array $item
     * @param int $fxGainAccountId
     * @param int $fxLossAccountId
     * @param string $functionalCurrency
     * @param string $revaluationDate
     * @param int $userId
     * @return Journal|null
     */
    protected function createRevaluationJournalEntry($item, $fxGainAccountId, $fxLossAccountId, $functionalCurrency, $revaluationDate, $userId)
    {
        $gainLoss = $item['gain_loss'];
        $isGain = $gainLoss > 0;
        $amount = abs($gainLoss);

        // Determine account IDs based on item type
        $itemAccountId = $this->getItemAccountId($item);

        if (!$itemAccountId) {
            Log::warning("Could not determine account ID for item type: {$item['item_type']}");
            return null;
        }

        // Check if approval is required for FX revaluation
        $approvalRequired = SystemSetting::getValue('fx_revaluation_approval_required', false);

        // Generate reference number
        $reference = $this->generateJournalReference();

        // Create journal
        $journal = Journal::create([
            'date' => $revaluationDate,
            'reference' => $reference,
            'reference_type' => 'FX Revaluation',
            'description' => "FX Revaluation - {$item['item_type']} - {$item['item_ref']} - " . ($isGain ? 'Gain' : 'Loss'),
            'branch_id' => $item['branch_id'] ?? null,
            'user_id' => $userId,
        ]);
        
        // Load user relationship for approval workflow
        $journal->load('user');

        // Create journal items and GL transactions
        if ($item['item_type'] === 'AR') {
            // AR Revaluation
            if ($isGain) {
                // Dr AR, Cr FX Gain
                $journal->items()->create([
                    'chart_account_id' => $itemAccountId,
                    'amount' => $amount,
                    'nature' => 'debit',
                    'description' => "FX Gain - AR Revaluation - {$item['item_ref']}",
                ]);

                $journal->items()->create([
                    'chart_account_id' => $fxGainAccountId,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'description' => "FX Gain - AR Revaluation - {$item['item_ref']}",
                ]);

                // Create GL transactions only if approval is not required
                if (!$approvalRequired) {
                    GlTransaction::create([
                        'chart_account_id' => $itemAccountId,
                        'amount' => $amount,
                        'nature' => 'debit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Gain - AR Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);

                    GlTransaction::create([
                        'chart_account_id' => $fxGainAccountId,
                        'amount' => $amount,
                        'nature' => 'credit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Gain - AR Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);
                }
            } else {
                // Dr FX Loss, Cr AR
                $journal->items()->create([
                    'chart_account_id' => $fxLossAccountId,
                    'amount' => $amount,
                    'nature' => 'debit',
                    'description' => "FX Loss - AR Revaluation - {$item['item_ref']}",
                ]);

                $journal->items()->create([
                    'chart_account_id' => $itemAccountId,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'description' => "FX Loss - AR Revaluation - {$item['item_ref']}",
                ]);

                // Create GL transactions only if approval is not required
                if (!$approvalRequired) {
                    GlTransaction::create([
                        'chart_account_id' => $fxLossAccountId,
                        'amount' => $amount,
                        'nature' => 'debit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Loss - AR Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);

                    GlTransaction::create([
                        'chart_account_id' => $itemAccountId,
                        'amount' => $amount,
                        'nature' => 'credit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Loss - AR Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);
                }
            }
        } elseif ($item['item_type'] === 'AP') {
            // AP Revaluation
            if ($isGain) {
                // Dr AP, Cr FX Gain
                $journal->items()->create([
                    'chart_account_id' => $itemAccountId,
                    'amount' => $amount,
                    'nature' => 'debit',
                    'description' => "FX Gain - AP Revaluation - {$item['item_ref']}",
                ]);

                $journal->items()->create([
                    'chart_account_id' => $fxGainAccountId,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'description' => "FX Gain - AP Revaluation - {$item['item_ref']}",
                ]);

                // Create GL transactions only if approval is not required
                if (!$approvalRequired) {
                    GlTransaction::create([
                        'chart_account_id' => $itemAccountId,
                        'amount' => $amount,
                        'nature' => 'debit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Gain - AP Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);

                    GlTransaction::create([
                        'chart_account_id' => $fxGainAccountId,
                        'amount' => $amount,
                        'nature' => 'credit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Gain - AP Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);
                }
            } else {
                // Dr FX Loss, Cr AP
                $journal->items()->create([
                    'chart_account_id' => $fxLossAccountId,
                    'amount' => $amount,
                    'nature' => 'debit',
                    'description' => "FX Loss - AP Revaluation - {$item['item_ref']}",
                ]);

                $journal->items()->create([
                    'chart_account_id' => $itemAccountId,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'description' => "FX Loss - AP Revaluation - {$item['item_ref']}",
                ]);

                // Create GL transactions only if approval is not required
                if (!$approvalRequired) {
                    GlTransaction::create([
                        'chart_account_id' => $fxLossAccountId,
                        'amount' => $amount,
                        'nature' => 'debit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Loss - AP Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);

                    GlTransaction::create([
                        'chart_account_id' => $itemAccountId,
                        'amount' => $amount,
                        'nature' => 'credit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Loss - AP Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);
                }
            }
        } elseif ($item['item_type'] === 'BANK') {
            // Bank Account Revaluation
            if ($isGain) {
                // Dr Bank, Cr FX Gain
                $journal->items()->create([
                    'chart_account_id' => $itemAccountId,
                    'amount' => $amount,
                    'nature' => 'debit',
                    'description' => "FX Gain - Bank Revaluation - {$item['item_ref']}",
                ]);

                $journal->items()->create([
                    'chart_account_id' => $fxGainAccountId,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'description' => "FX Gain - Bank Revaluation - {$item['item_ref']}",
                ]);

                // Create GL transactions only if approval is not required
                if (!$approvalRequired) {
                    GlTransaction::create([
                        'chart_account_id' => $itemAccountId,
                        'amount' => $amount,
                        'nature' => 'debit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Gain - Bank Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);

                    GlTransaction::create([
                        'chart_account_id' => $fxGainAccountId,
                        'amount' => $amount,
                        'nature' => 'credit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Gain - Bank Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);
                }
            } else {
                // Dr FX Loss, Cr Bank
                $journal->items()->create([
                    'chart_account_id' => $fxLossAccountId,
                    'amount' => $amount,
                    'nature' => 'debit',
                    'description' => "FX Loss - Bank Revaluation - {$item['item_ref']}",
                ]);

                $journal->items()->create([
                    'chart_account_id' => $itemAccountId,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'description' => "FX Loss - Bank Revaluation - {$item['item_ref']}",
                ]);

                // Create GL transactions only if approval is not required
                if (!$approvalRequired) {
                    GlTransaction::create([
                        'chart_account_id' => $fxLossAccountId,
                        'amount' => $amount,
                        'nature' => 'debit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Loss - Bank Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);

                    GlTransaction::create([
                        'chart_account_id' => $itemAccountId,
                        'amount' => $amount,
                        'nature' => 'credit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Loss - Bank Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);
                }
            }
        } elseif ($item['item_type'] === 'LOAN') {
            // Loan Revaluation
            if ($isGain) {
                // Dr Loan Payable, Cr FX Gain
                $journal->items()->create([
                    'chart_account_id' => $itemAccountId,
                    'amount' => $amount,
                    'nature' => 'debit',
                    'description' => "FX Gain - Loan Revaluation - {$item['item_ref']}",
                ]);

                $journal->items()->create([
                    'chart_account_id' => $fxGainAccountId,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'description' => "FX Gain - Loan Revaluation - {$item['item_ref']}",
                ]);

                // Create GL transactions only if approval is not required
                if (!$approvalRequired) {
                    GlTransaction::create([
                        'chart_account_id' => $itemAccountId,
                        'amount' => $amount,
                        'nature' => 'debit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Gain - Loan Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);

                    GlTransaction::create([
                        'chart_account_id' => $fxGainAccountId,
                        'amount' => $amount,
                        'nature' => 'credit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Gain - Loan Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);
                }
            } else {
                // Dr FX Loss, Cr Loan Payable
                $journal->items()->create([
                    'chart_account_id' => $fxLossAccountId,
                    'amount' => $amount,
                    'nature' => 'debit',
                    'description' => "FX Loss - Loan Revaluation - {$item['item_ref']}",
                ]);

                $journal->items()->create([
                    'chart_account_id' => $itemAccountId,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'description' => "FX Loss - Loan Revaluation - {$item['item_ref']}",
                ]);

                // Create GL transactions only if approval is not required
                if (!$approvalRequired) {
                    GlTransaction::create([
                        'chart_account_id' => $fxLossAccountId,
                        'amount' => $amount,
                        'nature' => 'debit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Loss - Loan Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);

                    GlTransaction::create([
                        'chart_account_id' => $itemAccountId,
                        'amount' => $amount,
                        'nature' => 'credit',
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $revaluationDate,
                        'description' => "FX Loss - Loan Revaluation - {$item['item_ref']}",
                        'branch_id' => $item['branch_id'] ?? null,
                        'user_id' => $userId,
                    ]);
                }
            }
        }

        // Refresh journal to load relationships and calculate total
        $journal->refresh();
        $journal->load(['items', 'user']);

        // Handle approval workflow
        if ($approvalRequired) {
            // Initialize approval workflow - this will create approval records if needed
            // GL transactions will be created only after approval via createGlTransactions()
            try {
                // Ensure journal has items loaded for total calculation
                if (!$journal->relationLoaded('items')) {
                    $journal->load('items');
                }
                
                // Ensure journal has user loaded for company_id access
                if (!$journal->relationLoaded('user')) {
                    $journal->load('user');
                }
                
                $journal->initializeApprovalWorkflow();
                
                Log::info('FX Revaluation journal approval workflow initialized', [
                    'journal_id' => $journal->id,
                    'journal_reference' => $journal->reference,
                    'item_type' => $item['item_type'],
                    'item_ref' => $item['item_ref'],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to initialize approval workflow for FX revaluation journal', [
                    'journal_id' => $journal->id,
                    'journal_reference' => $journal->reference,
                    'item_type' => $item['item_type'] ?? 'unknown',
                    'item_ref' => $item['item_ref'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('Failed to initialize approval workflow: ' . $e->getMessage(), 0, $e);
            }
        } else {
            // No approval required - mark journal as approved
            $journal->update([
                'approved' => true,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
            
            Log::info('FX Revaluation journal auto-approved (no approval required)', [
                'journal_id' => $journal->id,
                'journal_reference' => $journal->reference,
                'item_type' => $item['item_type'],
                'item_ref' => $item['item_ref'],
            ]);
        }

        return $journal;
    }

    /**
     * Get account ID for item type
     * 
     * @param array $item
     * @return int|null
     */
    protected function getItemAccountId($item)
    {
        switch ($item['item_type']) {
            case 'AR':
                // Get customer receivable account using the proper method
                $invoice = SalesInvoice::find($item['item_id']);
                if ($invoice) {
                    return $invoice->getReceivableAccountId() ?? 18;
                }
                // Fallback to default receivable account if invoice not found
                $settingValue = SystemSetting::where('key', 'inventory_default_receivable_account')->value('value');
                if ($settingValue) {
                    return (int) $settingValue;
                }
                
                // Fallback: Try ID 18 first (Accounts Receivable for new installations), then ID 2 (existing databases)
                $account18 = \App\Models\ChartAccount::find(18);
                if ($account18 && $account18->account_name === 'Accounts Receivable') {
                    return 18;
                }
                
                $account2 = \App\Models\ChartAccount::find(2);
                if ($account2 && $account2->account_name === 'Accounts Receivable') {
                    return 2;
                }
                
                // Last resort: find by name
                $account = \App\Models\ChartAccount::where('account_name', 'like', '%Accounts Receivable%')->first();
                return $account ? $account->id : 18; // Default to 18 if nothing found
                
            case 'AP':
                // Get Accounts Payable account from system settings (same as PurchaseInvoice uses)
                // This is the account used when posting purchase invoices to GL
                $settingValue = SystemSetting::where('key', 'inventory_default_purchase_payable_account')->value('value');
                if ($settingValue) {
                    return (int) $settingValue;
                }
                
                // Fallback: Default to account ID 30 (Trade Payables) as used in PurchaseInvoice
                $account30 = \App\Models\ChartAccount::find(30);
                if ($account30 && (stripos($account30->account_name, 'Payable') !== false || 
                    stripos($account30->account_name, 'Trade') !== false ||
                    stripos($account30->account_name, 'Creditor') !== false)) {
                    return 30;
                }
                
                // Fallback: Try to find Accounts Payable account by name
                $account = \App\Models\ChartAccount::where('account_name', 'like', '%Accounts Payable%')
                    ->orWhere('account_name', 'like', '%Trade Payable%')
                    ->orWhere('account_name', 'like', '%Payable%')
                    ->first();
                if ($account) {
                    return $account->id;
                }
                
                // Last resort: Try common account IDs
                $account19 = \App\Models\ChartAccount::find(19);
                if ($account19 && (stripos($account19->account_name, 'Payable') !== false || stripos($account19->account_name, 'Creditor') !== false)) {
                    return 19;
                }
                
                $account3 = \App\Models\ChartAccount::find(3);
                if ($account3 && (stripos($account3->account_name, 'Payable') !== false || stripos($account3->account_name, 'Creditor') !== false)) {
                    return 3;
                }
                
                // If still nothing found, log warning and return null
                Log::warning("Could not find Accounts Payable account for AP revaluation", [
                    'item_id' => $item['item_id'] ?? null,
                    'item_ref' => $item['item_ref'] ?? null,
                    'system_setting_key' => 'inventory_default_purchase_payable_account',
                ]);
                return null;
                
            case 'BANK':
                return $item['chart_account_id'] ?? null;
                
            default:
                return null;
        }
    }

    /**
     * Reverse previous period revaluation
     * 
     * @param int $companyId
     * @param int|null $branchId
     * @param string $newPeriodStartDate
     * @return array
     */
    public function reversePreviousRevaluation($companyId, $branchId = null, $newPeriodStartDate)
    {
        $previousPeriodEnd = Carbon::parse($newPeriodStartDate)->subDay()->toDateString();
        
        $query = GlRevaluationHistory::where('company_id', $companyId)
            ->where('is_reversed', false)
            ->where('revaluation_date', '<=', $previousPeriodEnd);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $revaluations = $query->get();
        $reversedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($revaluations as $revaluation) {
                // Create reversal journal entry (opposite of original)
                $reversalJournal = $this->createReversalJournalEntry($revaluation, $newPeriodStartDate);
                
                if ($reversalJournal) {
                    $revaluation->markAsReversed($reversalJournal->id);
                    
                    // Log auto-reversal activity
                    $revaluation->logActivity('reverse', "Auto-reversed FX Revaluation - {$revaluation->item_type} - {$revaluation->item_ref}", [
                        'reversal_journal' => $reversalJournal->reference,
                        'reversal_date' => $newPeriodStartDate
                    ]);
                    
                    $reversedCount++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'reversed_count' => $reversedCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FX Revaluation reversal failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create reversal journal entry
     * 
     * @param GlRevaluationHistory $revaluation
     * @param string $reversalDate
     * @return Journal|null
     */
    protected function createReversalJournalEntry($revaluation, $reversalDate)
    {
        $originalJournal = $revaluation->postedJournal;
        if (!$originalJournal) {
            return null;
        }

        // Get original journal items
        $originalItems = $originalJournal->items;

        // Generate reference number
        $reference = $this->generateJournalReference();

        // Create reversal journal
        $reversalJournal = Journal::create([
            'date' => $reversalDate,
            'reference' => $reference,
            'reference_type' => 'FX Revaluation Reversal',
            'description' => "FX Revaluation Reversal - {$revaluation->item_type} - {$revaluation->item_ref}",
            'branch_id' => $revaluation->branch_id,
            'user_id' => $revaluation->created_by,
        ]);

        // Reverse each journal item (swap debit/credit)
        foreach ($originalItems as $originalItem) {
            $reversalJournal->items()->create([
                'chart_account_id' => $originalItem->chart_account_id,
                'amount' => $originalItem->amount,
                'nature' => $originalItem->nature === 'debit' ? 'credit' : 'debit',
                'description' => "Reversal: " . ($originalItem->description ?? ''),
            ]);

            // Create GL transaction
            GlTransaction::create([
                'chart_account_id' => $originalItem->chart_account_id,
                'amount' => $originalItem->amount,
                'nature' => $originalItem->nature === 'debit' ? 'credit' : 'debit',
                'transaction_id' => $reversalJournal->id,
                'transaction_type' => 'journal',
                'date' => $reversalDate,
                'description' => "Reversal: " . ($originalItem->description ?? ''),
                'branch_id' => $revaluation->branch_id,
                'user_id' => $revaluation->created_by,
            ]);
        }

        return $reversalJournal;
    }

    /**
     * Get functional currency
     * 
     * @param int $companyId
     * @return string
     */
    public function getFunctionalCurrency($companyId)
    {
        // Try to get from system settings first
        $functionalCurrency = SystemSetting::getValue('functional_currency');
        
        if ($functionalCurrency) {
            return $functionalCurrency;
        }
        
        // Fallback to company setting
        $company = Company::find($companyId);
        return $company->functional_currency ?? 'TZS';
    }

    /**
     * Get FX Unrealized Gain Account ID (for month-end revaluation)
     * 
     * @param int $companyId
     * @return int|null
     */
    protected function getFxGainAccountId($companyId)
    {
        // Get from system settings - use unrealized gain account for revaluation
        $accountId = SystemSetting::getValue('fx_unrealized_gain_account_id');
        
        if ($accountId) {
            return (int) $accountId;
        }
        
        // Fallback to realized gain account if unrealized not set
        $accountId = SystemSetting::getValue('fx_realized_gain_account_id');
        if ($accountId) {
            return (int) $accountId;
        }
        
        return null;
    }

    /**
     * Get FX Unrealized Loss Account ID (for month-end revaluation)
     * 
     * @param int $companyId
     * @return int|null
     */
    protected function getFxLossAccountId($companyId)
    {
        // Get from system settings - use unrealized loss account for revaluation
        $accountId = SystemSetting::getValue('fx_unrealized_loss_account_id');
        
        if ($accountId) {
            return (int) $accountId;
        }
        
        // Fallback to realized loss account if unrealized not set
        $accountId = SystemSetting::getValue('fx_realized_loss_account_id');
        if ($accountId) {
            return (int) $accountId;
        }
        
        return null;
    }

    /**
     * Process month-end FX revaluation automatically
     * This method:
     * 1. Reverses previous month's revaluation on the 1st of the month
     * 2. Performs new month-end revaluation using closing rates
     * 
     * @param int $companyId
     * @param int|null $branchId
     * @param string|null $monthEndDate If null, uses last day of previous month
     * @param int|null $userId
     * @return array
     */
    public function processMonthEndRevaluation($companyId, $branchId = null, $monthEndDate = null, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        $today = now();
        
        // If it's the 1st of the month, reverse previous month's revaluation first
        if ($today->day === 1) {
            $previousMonthEnd = $today->copy()->subDay()->toDateString();
            $reversalResult = $this->reversePreviousRevaluation($companyId, $branchId, $today->toDateString());
            
            Log::info("Month-end FX revaluation: Reversed previous month's revaluation", [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'reversed_count' => $reversalResult['reversed_count'] ?? 0,
            ]);
        }
        
        // Determine month-end date
        if ($monthEndDate === null) {
            // Use last day of previous month if today is 1st, otherwise use last day of current month
            if ($today->day === 1) {
                $monthEndDate = $today->copy()->subDay()->toDateString();
            } else {
                $monthEndDate = $today->copy()->endOfMonth()->toDateString();
            }
        }
        
        // Generate revaluation preview
        $preview = $this->generateRevaluationPreview($companyId, $branchId, $monthEndDate);
        
        // Post revaluation journals
        if (count($preview['items']) > 0) {
            $postResult = $this->postRevaluation($companyId, $branchId, $monthEndDate, $preview, $userId);
            
            return [
                'success' => true,
                'revaluation_date' => $monthEndDate,
                'items_revalued' => count($preview['items']),
                'net_gain_loss' => $preview['summary']['net_gain_loss'],
                'journals_created' => $postResult['journals_created'] ?? 0,
                'reversal_performed' => $today->day === 1,
            ];
        }
        
        return [
            'success' => true,
            'revaluation_date' => $monthEndDate,
            'items_revalued' => 0,
            'message' => 'No items to revalue',
            'reversal_performed' => $today->day === 1,
        ];
    }

    /**
     * Generate journal reference number
     * 
     * @return string
     */
    protected function generateJournalReference()
    {
        $prefix = 'FX-REVAL-';
        $date = now()->format('Ymd');
        $lastJournal = Journal::where('reference', 'like', $prefix . $date . '%')
            ->where('reference_type', 'FX Revaluation')
            ->orderBy('reference', 'desc')
            ->first();

        if ($lastJournal) {
            $lastNumber = (int) substr($lastJournal->reference, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate FCY balance for a bank account
     * 
     * @param BankAccount $bank
     * @param string $asOfDate
     * @return float
     */
    protected function calculateBankAccountFcyBalance($bank, $asOfDate)
    {
        // Get all payments and receipts that used this bank account
        // Sum up FCY amounts (debits increase, credits decrease)
        // Handle mixed cases: some transactions have amount_fcy, others don't
        $fcyBalance = 0;
        $functionalCurrency = $this->getFunctionalCurrency($bank->company_id);

        // Get payments (credits to bank account)
        $payments = \App\Models\Payment::whereHas('glTransactions', function($q) use ($bank) {
                $q->where('chart_account_id', $bank->chart_account_id)
                  ->where('nature', 'credit');
            })
            ->where('currency', $bank->currency)
            ->where('date', '<=', $asOfDate)
            ->get();

        foreach ($payments as $payment) {
            $fcyAmount = 0;
            
            if ($payment->amount_fcy) {
                // Use stored FCY amount (preferred method)
                $fcyAmount = $payment->amount_fcy;
            } elseif ($payment->amount_lcy && $payment->exchange_rate && $payment->exchange_rate > 0) {
                // Calculate FCY from LCY using transaction exchange rate
                $fcyAmount = $payment->amount_lcy / $payment->exchange_rate;
            } elseif ($payment->amount && $payment->exchange_rate && $payment->exchange_rate > 0 && $payment->currency !== $functionalCurrency) {
                // Fallback: use amount field and exchange rate (for old transactions)
                $fcyAmount = $payment->amount;
            }
            
            if ($fcyAmount > 0) {
                $fcyBalance -= $fcyAmount; // Credits decrease balance
            }
        }

        // Get receipts (debits to bank account)
        $receipts = \App\Models\Receipt::whereHas('glTransactions', function($q) use ($bank) {
                $q->where('chart_account_id', $bank->chart_account_id)
                  ->where('nature', 'debit');
            })
            ->where('currency', $bank->currency)
            ->where('date', '<=', $asOfDate)
            ->get();

        foreach ($receipts as $receipt) {
            $fcyAmount = 0;
            
            if ($receipt->amount_fcy) {
                // Use stored FCY amount (preferred method)
                $fcyAmount = $receipt->amount_fcy;
            } elseif ($receipt->amount_lcy && $receipt->exchange_rate && $receipt->exchange_rate > 0) {
                // Calculate FCY from LCY using transaction exchange rate
                $fcyAmount = $receipt->amount_lcy / $receipt->exchange_rate;
            } elseif ($receipt->amount && $receipt->exchange_rate && $receipt->exchange_rate > 0 && $receipt->currency !== $functionalCurrency) {
                // Fallback: use amount field and exchange rate (for old transactions)
                $fcyAmount = $receipt->amount;
            }
            
            if ($fcyAmount > 0) {
                $fcyBalance += $fcyAmount; // Debits increase balance
            }
        }

        // If still no FCY balance calculated, use fallback method
        if (abs($fcyBalance) < 0.01) {
            $lcyBalance = $bank->balance ?? 0;
            if (abs($lcyBalance) > 0.01) {
                $avgRate = $this->getBankAccountAverageRate($bank, $asOfDate, $bank->company_id);
                if ($avgRate > 0 && $avgRate != 1.0) {
                    // Calculate FCY from LCY using average rate
                    $fcyBalance = $lcyBalance / $avgRate;
                } else {
                    // Last resort: try to get rate from bank account creation date
                    if ($bank->created_at) {
                        $creationRate = $this->fxTransactionRateService->getTransactionRate(
                            $bank->currency,
                            $functionalCurrency,
                            $bank->created_at->toDateString(),
                            $bank->company_id
                        )['rate'] ?? 1.0;
                        
                        if ($creationRate > 0 && $creationRate != 1.0) {
                            $fcyBalance = $lcyBalance / $creationRate;
                        }
                    }
                }
            }
        }

        return round($fcyBalance, 2);
    }

    /**
     * Get weighted average exchange rate for a bank account
     * 
     * @param BankAccount $bank
     * @param string $asOfDate
     * @param int $companyId
     * @return float
     */
    protected function getBankAccountAverageRate($bank, $asOfDate, $companyId)
    {
        $totalFcy = 0;
        $totalLcy = 0;
        $functionalCurrency = $this->getFunctionalCurrency($companyId);

        // Get payments - include all payments, not just those with amount_fcy
        $payments = \App\Models\Payment::whereHas('glTransactions', function($q) use ($bank) {
                $q->where('chart_account_id', $bank->chart_account_id);
            })
            ->where('currency', $bank->currency)
            ->where('date', '<=', $asOfDate)
            ->get();

        foreach ($payments as $payment) {
            $fcyAmount = 0;
            $lcyAmount = 0;
            
            if ($payment->amount_fcy && $payment->amount_lcy) {
                // Preferred: use stored FCY and LCY amounts
                $fcyAmount = abs($payment->amount_fcy);
                $lcyAmount = abs($payment->amount_lcy);
            } elseif ($payment->amount_lcy && $payment->exchange_rate && $payment->exchange_rate > 0) {
                // Calculate FCY from LCY using transaction exchange rate
                $lcyAmount = abs($payment->amount_lcy);
                $fcyAmount = $lcyAmount / $payment->exchange_rate;
            } elseif ($payment->amount && $payment->exchange_rate && $payment->exchange_rate > 0 && $payment->currency !== $functionalCurrency) {
                // Fallback: use amount field as FCY, calculate LCY
                $fcyAmount = abs($payment->amount);
                $lcyAmount = $fcyAmount * $payment->exchange_rate;
            }
            
            if ($fcyAmount > 0 && $lcyAmount > 0) {
                $totalFcy += $fcyAmount;
                $totalLcy += $lcyAmount;
            }
        }

        // Get receipts - include all receipts, not just those with amount_fcy
        $receipts = \App\Models\Receipt::whereHas('glTransactions', function($q) use ($bank) {
                $q->where('chart_account_id', $bank->chart_account_id);
            })
            ->where('currency', $bank->currency)
            ->where('date', '<=', $asOfDate)
            ->get();

        foreach ($receipts as $receipt) {
            $fcyAmount = 0;
            $lcyAmount = 0;
            
            if ($receipt->amount_fcy && $receipt->amount_lcy) {
                // Preferred: use stored FCY and LCY amounts
                $fcyAmount = abs($receipt->amount_fcy);
                $lcyAmount = abs($receipt->amount_lcy);
            } elseif ($receipt->amount_lcy && $receipt->exchange_rate && $receipt->exchange_rate > 0) {
                // Calculate FCY from LCY using transaction exchange rate
                $lcyAmount = abs($receipt->amount_lcy);
                $fcyAmount = $lcyAmount / $receipt->exchange_rate;
            } elseif ($receipt->amount && $receipt->exchange_rate && $receipt->exchange_rate > 0 && $receipt->currency !== $functionalCurrency) {
                // Fallback: use amount field as FCY, calculate LCY
                $fcyAmount = abs($receipt->amount);
                $lcyAmount = $fcyAmount * $receipt->exchange_rate;
            }
            
            if ($fcyAmount > 0 && $lcyAmount > 0) {
                $totalFcy += $fcyAmount;
                $totalLcy += $lcyAmount;
            }
        }

        // Calculate weighted average rate
        if ($totalFcy > 0) {
            return round($totalLcy / $totalFcy, 6);
        }

        // Fallback: use creation date rate
        return 1.0;
    }
}

