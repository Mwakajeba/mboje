<?php

namespace App\Services;

use App\Models\Sales\SalesInvoice;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\BankAccount;
use App\Models\SystemSetting;
use App\Models\Company;
use App\Services\FxTransactionRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FxYearEndService
{
    protected $fxTransactionRateService;

    public function __construct(FxTransactionRateService $fxTransactionRateService)
    {
        $this->fxTransactionRateService = $fxTransactionRateService;
    }

    /**
     * Calculate unrealized FX gain/loss at year-end
     * Uses exchange rate from fx_rates table at year-end date
     * Compares with historical exchange rate (rate used when transaction was created)
     * 
     * @param int $companyId
     * @param int|null $branchId
     * @param string $yearEndDate
     * @return array
     */
    public function calculateYearEndUnrealizedGainLoss($companyId, $branchId = null, $yearEndDate)
    {
        $functionalCurrency = $this->getFunctionalCurrency($companyId);
        
        $items = [
            'AR' => [],
            'AP' => [],
            'BANK' => [],
        ];

        // 1. Accounts Receivable (AR) - Open sales invoices in FCY
        $arQuery = SalesInvoice::where('company_id', $companyId)
            ->where('currency', '!=', $functionalCurrency)
            ->where('balance_due', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $yearEndDate);

        if ($branchId) {
            $arQuery->where('branch_id', $branchId);
        }

        $salesInvoices = $arQuery->get();
        foreach ($salesInvoices as $invoice) {
            // Get historical rate (rate used when invoice was created)
            $historicalRate = $invoice->fx_rate_used ?? $invoice->exchange_rate ?? 1.000000;
            
            // Get year-end rate from fx_rates table
            $yearEndRate = $this->fxTransactionRateService->getYearEndRate(
                $invoice->currency,
                $functionalCurrency,
                $yearEndDate,
                $companyId
            );

            if ($yearEndRate === null) {
                Log::warning("Year-end rate not found for AR invoice", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'currency' => $invoice->currency,
                    'year_end_date' => $yearEndDate,
                ]);
                continue;
            }

            // Calculate unrealized gain/loss
            $fcyAmount = $invoice->balance_due;
            $gainLoss = ($yearEndRate - $historicalRate) * $fcyAmount;

            $items['AR'][] = [
                'id' => $invoice->id,
                'ref' => $invoice->invoice_number,
                'date' => $invoice->invoice_date,
                'currency' => $invoice->currency,
                'fcy_amount' => $fcyAmount,
                'historical_rate' => $historicalRate,
                'year_end_rate' => $yearEndRate,
                'original_lcy_amount' => $fcyAmount * $historicalRate,
                'year_end_lcy_amount' => $fcyAmount * $yearEndRate,
                'unrealized_gain_loss' => $gainLoss,
                'customer_id' => $invoice->customer_id,
                'branch_id' => $invoice->branch_id,
            ];
        }

        // 2. Accounts Payable (AP) - Open purchase invoices in FCY
        $apQuery = PurchaseInvoice::where('company_id', $companyId)
            ->where('currency', '!=', $functionalCurrency)
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $yearEndDate);

        if ($branchId) {
            $apQuery->where('branch_id', $branchId);
        }

        $purchaseInvoices = $apQuery->get();
        foreach ($purchaseInvoices as $invoice) {
            $balanceDue = $invoice->total_amount - ($invoice->total_paid ?? 0);
            if ($balanceDue <= 0) {
                continue;
            }

            // Get historical rate
            $historicalRate = $invoice->fx_rate_used ?? $invoice->exchange_rate ?? 1.000000;
            
            // Get year-end rate
            $yearEndRate = $this->fxTransactionRateService->getYearEndRate(
                $invoice->currency,
                $functionalCurrency,
                $yearEndDate,
                $companyId
            );

            if ($yearEndRate === null) {
                Log::warning("Year-end rate not found for AP invoice", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'currency' => $invoice->currency,
                    'year_end_date' => $yearEndDate,
                ]);
                continue;
            }

            // Calculate unrealized gain/loss
            $fcyAmount = $balanceDue;
            $gainLoss = ($yearEndRate - $historicalRate) * $fcyAmount;

            $items['AP'][] = [
                'id' => $invoice->id,
                'ref' => $invoice->invoice_number,
                'date' => $invoice->invoice_date,
                'currency' => $invoice->currency,
                'fcy_amount' => $fcyAmount,
                'historical_rate' => $historicalRate,
                'year_end_rate' => $yearEndRate,
                'original_lcy_amount' => $fcyAmount * $historicalRate,
                'year_end_lcy_amount' => $fcyAmount * $yearEndRate,
                'unrealized_gain_loss' => $gainLoss,
                'supplier_id' => $invoice->supplier_id,
                'branch_id' => $invoice->branch_id,
            ];
        }

        // 3. Bank Accounts - FCY bank accounts with balances
        $bankAccounts = BankAccount::where('currency', '!=', $functionalCurrency)
            ->whereNotNull('currency')
            ->get();

        foreach ($bankAccounts as $bank) {
            $balance = $bank->balance ?? 0;
            if (abs($balance) <= 0.01) {
                continue;
            }

            // Get historical rate (from account creation or first transaction)
            $historicalRate = 1.0;
            if ($bank->created_at) {
                $historicalRate = $this->fxTransactionRateService->getTransactionRate(
                    $bank->currency,
                    $functionalCurrency,
                    $bank->created_at->toDateString(),
                    $companyId
                )['rate'] ?? 1.0;
            }

            // Get year-end rate
            $yearEndRate = $this->fxTransactionRateService->getYearEndRate(
                $bank->currency,
                $functionalCurrency,
                $yearEndDate,
                $companyId
            );

            if ($yearEndRate === null) {
                continue;
            }

            // Calculate unrealized gain/loss
            $fcyAmount = $balance;
            $gainLoss = ($yearEndRate - $historicalRate) * $fcyAmount;

            $items['BANK'][] = [
                'id' => $bank->id,
                'ref' => $bank->account_number,
                'date' => $yearEndDate,
                'currency' => $bank->currency,
                'fcy_amount' => $fcyAmount,
                'historical_rate' => $historicalRate,
                'year_end_rate' => $yearEndRate,
                'original_lcy_amount' => $fcyAmount * $historicalRate,
                'year_end_lcy_amount' => $fcyAmount * $yearEndRate,
                'unrealized_gain_loss' => $gainLoss,
                'chart_account_id' => $bank->chart_account_id,
                'branch_id' => null,
            ];
        }

        // Calculate summary
        $summary = [
            'total_items' => 0,
            'total_gain' => 0,
            'total_loss' => 0,
            'net_gain_loss' => 0,
            'by_type' => [
                'AR' => ['count' => 0, 'gain' => 0, 'loss' => 0],
                'AP' => ['count' => 0, 'gain' => 0, 'loss' => 0],
                'BANK' => ['count' => 0, 'gain' => 0, 'loss' => 0],
            ],
        ];

        foreach ($items as $itemType => $itemList) {
            foreach ($itemList as $item) {
                $summary['total_items']++;
                $gainLoss = $item['unrealized_gain_loss'];
                
                $summary['by_type'][$itemType]['count']++;
                if ($gainLoss > 0) {
                    $summary['total_gain'] += $gainLoss;
                    $summary['by_type'][$itemType]['gain'] += $gainLoss;
                } else {
                    $summary['total_loss'] += abs($gainLoss);
                    $summary['by_type'][$itemType]['loss'] += abs($gainLoss);
                }
                $summary['net_gain_loss'] += $gainLoss;
            }
        }

        // Round amounts
        $summary['total_gain'] = round($summary['total_gain'], 2);
        $summary['total_loss'] = round($summary['total_loss'], 2);
        $summary['net_gain_loss'] = round($summary['net_gain_loss'], 2);
        foreach ($summary['by_type'] as $type => &$typeSummary) {
            $typeSummary['gain'] = round($typeSummary['gain'], 2);
            $typeSummary['loss'] = round($typeSummary['loss'], 2);
        }

        return [
            'year_end_date' => $yearEndDate,
            'functional_currency' => $functionalCurrency,
            'items' => $items,
            'summary' => $summary,
        ];
    }

    /**
     * Get functional currency
     */
    protected function getFunctionalCurrency($companyId)
    {
        $functionalCurrency = SystemSetting::getValue('functional_currency');
        
        if ($functionalCurrency) {
            return $functionalCurrency;
        }
        
        $company = Company::find($companyId);
        return $company->functional_currency ?? 'TZS';
    }
}

