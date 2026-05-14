<?php

namespace App\Services;

use App\Models\CashflowForecast;
use App\Models\CashflowForecastItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashflowForecastService
{
    /**
     * Calculate opening cash balance from bank accounts only (excludes petty cash)
     */
    public function calculateOpeningCashBalance($companyId, $branchId = null, $asOfDate = null)
    {
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : now();
        // Format date with time to match database format (end of day to include all transactions on that date)
        $asOfDateWithTime = $asOfDate->format('Y-m-d') . ' 23:59:59';
        $totalBalance = 0;
        
        // Bank Account Balances only
        // Filter by company through chart_account -> account_class_group relationship
        // (since bank_accounts.company_id may be NULL, we use the chart account relationship)
        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();
        
        // Debug: Log bank accounts found
        \Log::info('Cashflow Forecast - Bank Accounts Found', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'as_of_date' => $asOfDateWithTime,
            'bank_accounts_count' => $bankAccounts->count(),
            'bank_accounts' => $bankAccounts->map(function($ba) {
                return [
                    'id' => $ba->id,
                    'name' => $ba->name,
                    'chart_account_id' => $ba->chart_account_id,
                ];
            })->toArray(),
        ]);
        
        foreach ($bankAccounts as $bankAccount) {
            if (!$bankAccount->chart_account_id) {
                \Log::warning('Cashflow Forecast - Bank account without chart_account_id', [
                    'bank_account_id' => $bankAccount->id,
                    'bank_account_name' => $bankAccount->name,
                ]);
                continue; // Skip if no chart account is linked
            }
            
            // Calculate balance from GL transactions up to asOfDate
            // Use DB::table() for consistency with other reports and proper date/time handling
            // Try multiple approaches to ensure we get the data
            
            // Approach 1: Using date string with time
            $balanceQuery1 = DB::table('gl_transactions')
                ->where('chart_account_id', $bankAccount->chart_account_id)
                ->where('date', '<=', $asOfDateWithTime);
            
            // Approach 2: Using date only (for comparison)
            $balanceQuery2 = DB::table('gl_transactions')
                ->where('chart_account_id', $bankAccount->chart_account_id)
                ->whereDate('date', '<=', $asOfDate->format('Y-m-d'));
            
            // Apply branch filter if specified
            if ($branchId) {
                $balanceQuery1->where('branch_id', $branchId);
                $balanceQuery2->where('branch_id', $branchId);
            }
            
            // Debug: Check transaction count with both approaches
            $transactionCount1 = (clone $balanceQuery1)->count();
            $transactionCount2 = (clone $balanceQuery2)->count();
            
            // Also check total transactions without date filter
            $totalTransactions = DB::table('gl_transactions')
                ->where('chart_account_id', $bankAccount->chart_account_id)
                ->count();
            
            \Log::info('Cashflow Forecast - GL Transactions Check', [
                'bank_account_id' => $bankAccount->id,
                'bank_account_name' => $bankAccount->name,
                'chart_account_id' => $bankAccount->chart_account_id,
                'total_transactions' => $totalTransactions,
                'transaction_count_with_datetime' => $transactionCount1,
                'transaction_count_with_date' => $transactionCount2,
                'date_filter_datetime' => $asOfDateWithTime,
                'date_filter_date' => $asOfDate->format('Y-m-d'),
            ]);
            
            // Use the query that finds transactions (prefer datetime approach)
            $balanceQuery = $transactionCount1 > 0 ? $balanceQuery1 : $balanceQuery2;
            
            // Calculate balance using SQL aggregation (more efficient)
            $balance = $balanceQuery->selectRaw('
                SUM(CASE WHEN nature = "debit" THEN amount ELSE 0 END) -
                SUM(CASE WHEN nature = "credit" THEN amount ELSE 0 END) as balance
            ')->value('balance') ?? 0;
            
            \Log::info('Cashflow Forecast - Balance Calculated', [
                'bank_account_id' => $bankAccount->id,
                'bank_account_name' => $bankAccount->name,
                'balance' => $balance,
            ]);
            
            $totalBalance += $balance;
        }
        
        \Log::info('Cashflow Forecast - Total Balance', [
            'total_balance' => $totalBalance,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'as_of_date' => $asOfDateWithTime,
        ]);
        
        // Note: Petty cash is excluded from opening balance calculation
        // Mobile Money (if applicable - add when mobile money module is available)
        // TODO: Add mobile money balance calculation when module is implemented
        
        return $totalBalance;
    }

    /**
     * Generate forecast items for a cashflow forecast
     */
    public function generateForecastItems(CashflowForecast $forecast)
    {
        $startDate = Carbon::parse($forecast->start_date);
        $endDate = Carbon::parse($forecast->end_date);
        $companyId = $forecast->company_id;
        $branchId = $forecast->branch_id;
        $scenario = $forecast->scenario;
        
        // Clear existing items
        $forecast->items()->delete();
        
        // Auto-calculate opening balance if not set or if user wants to recalculate
        if (!$forecast->starting_cash_balance || $forecast->starting_cash_balance == 0) {
            $calculatedBalance = $this->calculateOpeningCashBalance($companyId, $branchId, $startDate->copy()->subDay());
            $forecast->update(['starting_cash_balance' => $calculatedBalance]);
        }
        
        // Generate items from different sources
        $this->generateFromAccountsReceivable($forecast, $startDate, $endDate, $companyId, $branchId, $scenario);
        $this->generateFromAccountsPayable($forecast, $startDate, $endDate, $companyId, $branchId, $scenario);
        $this->generateFromSalesOrders($forecast, $startDate, $endDate, $companyId, $branchId, $scenario);
        $this->generateFromPurchaseOrders($forecast, $startDate, $endDate, $companyId, $branchId, $scenario);
        $this->generateFromTaxes($forecast, $startDate, $endDate, $companyId, $branchId, $scenario);
        $this->generateFromPaymentVouchers($forecast, $startDate, $endDate, $companyId, $branchId, $scenario);
        $this->generateFromRecurringExpenses($forecast, $startDate, $endDate, $companyId, $branchId, $scenario);
    }

    /**
     * Generate forecast items from Accounts Receivable
     */
    private function generateFromAccountsReceivable($forecast, $startDate, $endDate, $companyId, $branchId, $scenario)
    {
        $query = \App\Models\Sales\SalesInvoice::where('company_id', $companyId)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('due_date', [$startDate, $endDate]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $invoices = $query->get();
        
        foreach ($invoices as $invoice) {
            $forecastDate = $this->applyScenarioToDate($invoice->due_date, $scenario, 'receivable');
            
            if ($forecastDate >= $startDate && $forecastDate <= $endDate) {
                CashflowForecastItem::create([
                    'cashflow_forecast_id' => $forecast->id,
                    'forecast_date' => $forecastDate,
                    'type' => 'inflow',
                    'source_type' => 'accounts_receivable',
                    'source_reference' => $invoice->invoice_number,
                    'source_id' => $invoice->id,
                    'amount' => $invoice->balance_due,
                    'probability' => $this->getReceivableProbability($invoice, $scenario),
                    'description' => 'Invoice Payment: ' . $invoice->invoice_number,
                ]);
            }
        }
    }

    /**
     * Generate forecast items from Accounts Payable
     */
    private function generateFromAccountsPayable($forecast, $startDate, $endDate, $companyId, $branchId, $scenario)
    {
        $query = \App\Models\Purchase\PurchaseInvoice::where('company_id', $companyId)
            ->whereNotIn('status', ['cancelled', 'paid'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$startDate, $endDate]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $invoices = $query->get();
        
        foreach ($invoices as $invoice) {
            // Use outstanding_amount accessor (calculates total_amount - total_paid)
            $outstandingAmount = $invoice->outstanding_amount;
            
            // Only include invoices with outstanding balance
            if ($outstandingAmount <= 0) {
                continue;
            }
            
            $forecastDate = $this->applyScenarioToDate($invoice->due_date, $scenario, 'payable');
            
            if ($forecastDate >= $startDate && $forecastDate <= $endDate) {
                CashflowForecastItem::create([
                    'cashflow_forecast_id' => $forecast->id,
                    'forecast_date' => $forecastDate,
                    'type' => 'outflow',
                    'source_type' => 'accounts_payable',
                    'source_reference' => $invoice->invoice_number,
                    'source_id' => $invoice->id,
                    'amount' => $outstandingAmount,
                    'probability' => $this->getPayableProbability($invoice, $scenario),
                    'description' => 'Supplier Invoice Payment: ' . $invoice->invoice_number,
                ]);
            }
        }
    }

    /**
     * Generate forecast items from Sales Orders
     */
    private function generateFromSalesOrders($forecast, $startDate, $endDate, $companyId, $branchId, $scenario)
    {
        $query = \App\Models\Sales\SalesOrder::where('company_id', $companyId)
            ->where('status', 'confirmed')
            ->whereBetween('expected_delivery_date', [$startDate, $endDate]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $orders = $query->get();
        
        foreach ($orders as $order) {
            $forecastDate = $this->applyScenarioToDate($order->expected_delivery_date, $scenario, 'order');
            
            if ($forecastDate >= $startDate && $forecastDate <= $endDate) {
                CashflowForecastItem::create([
                    'cashflow_forecast_id' => $forecast->id,
                    'forecast_date' => $forecastDate,
                    'type' => 'inflow',
                    'source_type' => 'sales_order',
                    'source_reference' => $order->order_number,
                    'source_id' => $order->id,
                    'amount' => $order->total_amount,
                    'probability' => 70.00, // Sales orders have lower probability
                    'description' => 'Sales Order Payment: ' . $order->order_number,
                ]);
            }
        }
    }

    /**
     * Generate forecast items from Purchase Orders (future cash commitments)
     */
    private function generateFromPurchaseOrders($forecast, $startDate, $endDate, $companyId, $branchId, $scenario)
    {
        $query = \App\Models\Purchase\PurchaseOrder::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'in_production', 'ready_for_delivery'])
            ->whereBetween('expected_delivery_date', [$startDate, $endDate]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $orders = $query->get();
        
        foreach ($orders as $order) {
            // Calculate payment date based on delivery date + payment terms
            $paymentDate = $this->calculatePaymentDateFromTerms(
                $order->expected_delivery_date,
                $order->payment_terms,
                $order->payment_days
            );
            
            $forecastDate = $this->applyScenarioToDate($paymentDate, $scenario, 'payable');
            
            if ($forecastDate >= $startDate && $forecastDate <= $endDate) {
                CashflowForecastItem::create([
                    'cashflow_forecast_id' => $forecast->id,
                    'forecast_date' => $forecastDate,
                    'type' => 'outflow',
                    'source_type' => 'purchase_order',
                    'source_reference' => $order->order_number,
                    'source_id' => $order->id,
                    'amount' => $order->total_amount,
                    'probability' => $this->getPurchaseOrderProbability($order, $scenario),
                    'description' => 'Purchase Order Payment: ' . $order->order_number . ' (Expected: ' . $order->expected_delivery_date->format('d M Y') . ')',
                ]);
            }
        }
    }

    /**
     * Calculate payment date from payment terms
     */
    private function calculatePaymentDateFromTerms($deliveryDate, $paymentTerms, $paymentDays = 0)
    {
        $date = Carbon::parse($deliveryDate);
        
        switch ($paymentTerms) {
            case 'immediate':
                return $date;
            case 'net_15':
                return $date->copy()->addDays(15);
            case 'net_30':
                return $date->copy()->addDays(30);
            case 'net_45':
                return $date->copy()->addDays(45);
            case 'net_60':
                return $date->copy()->addDays(60);
            case 'custom':
                return $date->copy()->addDays($paymentDays ?: 30);
            default:
                return $date->copy()->addDays(30);
        }
    }

    /**
     * Get purchase order probability
     */
    private function getPurchaseOrderProbability($order, $scenario)
    {
        // Approved orders have higher probability
        if ($order->status === 'approved') {
            return $scenario === 'best_case' ? 85.00 : ($scenario === 'worst_case' ? 95.00 : 90.00);
        } elseif ($order->status === 'in_production') {
            return $scenario === 'best_case' ? 90.00 : ($scenario === 'worst_case' ? 95.00 : 92.00);
        } elseif ($order->status === 'ready_for_delivery') {
            return 95.00; // Very likely to be paid soon
        }
        
        return 70.00;
    }

    /**
     * Generate forecast items from Taxes
     */
    private function generateFromTaxes($forecast, $startDate, $endDate, $companyId, $branchId, $scenario)
    {
        // VAT payments (monthly on 20th of next month)
        $vatDates = $this->getTaxPaymentDates($startDate, $endDate, 'vat', 20);
        foreach ($vatDates as $date) {
            if ($date >= $startDate && $date <= $endDate) {
                $estimatedVAT = $this->estimateTaxAmount($companyId, $branchId, 'vat', $date);
                if ($estimatedVAT > 0) {
                    CashflowForecastItem::create([
                        'cashflow_forecast_id' => $forecast->id,
                        'forecast_date' => $date,
                        'type' => 'outflow',
                        'source_type' => 'tax_vat',
                        'source_reference' => 'VAT Payment',
                        'source_id' => null,
                        'amount' => $estimatedVAT,
                        'probability' => 100.00,
                        'description' => 'VAT Payment (Due: ' . $date->format('d M Y') . ')',
                    ]);
                }
            }
        }
        
        // PAYE payments (monthly on 7th)
        $payeDates = $this->getTaxPaymentDates($startDate, $endDate, 'paye', 7);
        foreach ($payeDates as $date) {
            if ($date >= $startDate && $date <= $endDate) {
                $estimatedPAYE = $this->estimateTaxAmount($companyId, $branchId, 'paye', $date);
                if ($estimatedPAYE > 0) {
                    CashflowForecastItem::create([
                        'cashflow_forecast_id' => $forecast->id,
                        'forecast_date' => $date,
                        'type' => 'outflow',
                        'source_type' => 'tax_paye',
                        'source_reference' => 'PAYE Payment',
                        'source_id' => null,
                        'amount' => $estimatedPAYE,
                        'probability' => 100.00,
                        'description' => 'PAYE Payment (Due: ' . $date->format('d M Y') . ')',
                    ]);
                }
            }
        }
        
        // SDL payments (monthly on 7th)
        $sdlDates = $this->getTaxPaymentDates($startDate, $endDate, 'sdl', 7);
        foreach ($sdlDates as $date) {
            if ($date >= $startDate && $date <= $endDate) {
                $estimatedSDL = $this->estimateTaxAmount($companyId, $branchId, 'sdl', $date);
                if ($estimatedSDL > 0) {
                    CashflowForecastItem::create([
                        'cashflow_forecast_id' => $forecast->id,
                        'forecast_date' => $date,
                        'type' => 'outflow',
                        'source_type' => 'tax_sdl',
                        'source_reference' => 'SDL Payment',
                        'source_id' => null,
                        'amount' => $estimatedSDL,
                        'probability' => 100.00,
                        'description' => 'SDL Payment (Due: ' . $date->format('d M Y') . ')',
                    ]);
                }
            }
        }
        
        // WHT payments (monthly on 7th)
        $whtDates = $this->getTaxPaymentDates($startDate, $endDate, 'wht', 7);
        foreach ($whtDates as $date) {
            if ($date >= $startDate && $date <= $endDate) {
                $estimatedWHT = $this->estimateTaxAmount($companyId, $branchId, 'wht', $date);
                if ($estimatedWHT > 0) {
                    CashflowForecastItem::create([
                        'cashflow_forecast_id' => $forecast->id,
                        'forecast_date' => $date,
                        'type' => 'outflow',
                        'source_type' => 'tax_wht',
                        'source_reference' => 'WHT Payment',
                        'source_id' => null,
                        'amount' => $estimatedWHT,
                        'probability' => 100.00,
                        'description' => 'WHT Payment (Due: ' . $date->format('d M Y') . ')',
                    ]);
                }
            }
        }
        
        // Corporate Tax (quarterly - end of quarter)
        $corporateTaxDates = $this->getCorporateTaxDates($startDate, $endDate);
        foreach ($corporateTaxDates as $date) {
            if ($date >= $startDate && $date <= $endDate) {
                $estimatedCorpTax = $this->estimateTaxAmount($companyId, $branchId, 'corporate_tax', $date);
                if ($estimatedCorpTax > 0) {
                    CashflowForecastItem::create([
                        'cashflow_forecast_id' => $forecast->id,
                        'forecast_date' => $date,
                        'type' => 'outflow',
                        'source_type' => 'tax_corporate',
                        'source_reference' => 'Corporate Tax Payment',
                        'source_id' => null,
                        'amount' => $estimatedCorpTax,
                        'probability' => 100.00,
                        'description' => 'Corporate Tax Payment (Quarter End: ' . $date->format('d M Y') . ')',
                    ]);
                }
            }
        }
    }

    /**
     * Generate forecast items from Payment Vouchers
     */
    private function generateFromPaymentVouchers($forecast, $startDate, $endDate, $companyId, $branchId, $scenario)
    {
        $query = \App\Models\Payment::whereHas('branch', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->where('approved', true)
        ->whereNotNull('date')
        ->whereBetween('date', [$startDate, $endDate]);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $payments = $query->get();
        
        foreach ($payments as $payment) {
            CashflowForecastItem::create([
                'cashflow_forecast_id' => $forecast->id,
                'forecast_date' => $payment->date,
                'type' => 'outflow',
                'source_type' => 'accounts_payable', // Or create a new type
                'source_reference' => $payment->reference,
                'source_id' => $payment->id,
                'amount' => $payment->amount,
                'probability' => 90.00,
                'description' => 'Approved Payment Voucher: ' . $payment->reference,
            ]);
        }
    }

    /**
     * Apply scenario to date
     */
    private function applyScenarioToDate($originalDate, $scenario, $type)
    {
        $date = Carbon::parse($originalDate);
        
        switch ($scenario) {
            case 'best_case':
                if ($type === 'receivable') {
                    return $date->copy()->subDays(3); // Early collection
                } elseif ($type === 'payable') {
                    return $date->copy()->addDays(5); // Delayed payment
                }
                return $date;
                
            case 'worst_case':
                if ($type === 'receivable') {
                    return $date->copy()->addDays(10); // Delayed collection
                } elseif ($type === 'payable') {
                    return $date->copy()->subDays(3); // Early payment
                }
                return $date;
                
            default: // base_case
                return $date;
        }
    }

    /**
     * Get receivable probability based on scenario and customer payment history (AI-enhanced)
     */
    private function getReceivableProbability($invoice, $scenario)
    {
        $baseProbability = $this->getBaseReceivableProbability($invoice, $scenario);
        
        // Enhance with customer payment history if available
        if ($invoice->customer_id) {
            $customerHistory = $this->getCustomerPaymentHistory($invoice->customer_id);
            $historyAdjustment = $this->calculateHistoryAdjustment($customerHistory, $invoice);
            
            // Adjust base probability based on customer history
            $adjustedProbability = $baseProbability + $historyAdjustment;
            
            // Clamp between 0 and 100
            return max(0, min(100, $adjustedProbability));
        }
        
        return $baseProbability;
    }

    /**
     * Get base receivable probability
     */
    private function getBaseReceivableProbability($invoice, $scenario)
    {
        $age = Carbon::parse($invoice->due_date)->diffInDays(now());
        
        if ($age < 0) {
            // Overdue
            return $scenario === 'best_case' ? 60.00 : ($scenario === 'worst_case' ? 30.00 : 45.00);
        } elseif ($age <= 30) {
            return $scenario === 'best_case' ? 95.00 : ($scenario === 'worst_case' ? 70.00 : 85.00);
        } else {
            return $scenario === 'best_case' ? 80.00 : ($scenario === 'worst_case' ? 50.00 : 65.00);
        }
    }

    /**
     * Get customer payment history for AI probability calculation
     */
    private function getCustomerPaymentHistory($customerId)
    {
        $invoices = \App\Models\Sales\SalesInvoice::where('customer_id', $customerId)
            ->where('status', 'paid')
            ->with(['receipts' => function($query) {
                $query->orderBy('date', 'desc');
            }])
            ->orderBy('due_date', 'desc')
            ->limit(20)
            ->get();
        
        $history = [
            'total_invoices' => $invoices->count(),
            'on_time_payments' => 0,
            'late_payments' => 0,
            'average_delay_days' => 0,
            'payment_rate' => 0,
        ];
        
        if ($invoices->count() > 0) {
            $totalDelay = 0;
            foreach ($invoices as $inv) {
                // Get the latest receipt date as the payment date
                $latestReceipt = $inv->receipts->first();
                $paidDate = $latestReceipt ? $latestReceipt->date : null;
                
                if ($paidDate && $inv->due_date) {
                    $delay = Carbon::parse($paidDate)->diffInDays(Carbon::parse($inv->due_date));
                    if ($delay <= 0) {
                        $history['on_time_payments']++;
                    } else {
                        $history['late_payments']++;
                        $totalDelay += $delay;
                    }
                }
            }
            
            $history['average_delay_days'] = $history['late_payments'] > 0 
                ? ($totalDelay / $history['late_payments']) 
                : 0;
            $history['payment_rate'] = $history['total_invoices'] > 0 
                ? (($history['on_time_payments'] / $history['total_invoices']) * 100) 
                : 0;
        }
        
        return $history;
    }

    /**
     * Calculate probability adjustment based on customer history
     */
    private function calculateHistoryAdjustment($history, $invoice)
    {
        if ($history['total_invoices'] == 0) {
            return 0; // No history, no adjustment
        }
        
        $adjustment = 0;
        
        // Adjust based on payment rate
        if ($history['payment_rate'] >= 90) {
            $adjustment += 5; // Excellent payer
        } elseif ($history['payment_rate'] >= 70) {
            $adjustment += 2; // Good payer
        } elseif ($history['payment_rate'] < 50) {
            $adjustment -= 10; // Poor payer
        }
        
        // Adjust based on average delay
        if ($history['average_delay_days'] > 30) {
            $adjustment -= 5; // Frequently late
        } elseif ($history['average_delay_days'] < 5) {
            $adjustment += 3; // Usually on time
        }
        
        // Adjust based on invoice amount (larger invoices may have different payment patterns)
        if ($invoice->total_amount > 1000000) {
            $adjustment -= 2; // Large invoices might be delayed
        }
        
        return $adjustment;
    }

    /**
     * Get payable probability
     */
    private function getPayableProbability($invoice, $scenario)
    {
        return $scenario === 'best_case' ? 70.00 : ($scenario === 'worst_case' ? 95.00 : 85.00);
    }

    /**
     * Get tax payment dates
     */
    private function getTaxPaymentDates($startDate, $endDate, $taxType, $dayOfMonth = 20)
    {
        $dates = [];
        $current = $startDate->copy();
        
        while ($current <= $endDate) {
            $taxDate = $current->copy()->day($dayOfMonth);
            // If the day doesn't exist in the month (e.g., Feb 30), use last day of month
            if ($taxDate->format('Y-m') !== $current->format('Y-m')) {
                $taxDate = $current->copy()->endOfMonth();
            }
            
            if ($taxDate >= $startDate && $taxDate <= $endDate) {
                $dates[] = $taxDate;
            }
            $current->addMonth();
        }
        
        return $dates;
    }

    /**
     * Get corporate tax payment dates (quarterly - end of quarter)
     */
    private function getCorporateTaxDates($startDate, $endDate)
    {
        $dates = [];
        $current = $startDate->copy();
        
        while ($current <= $endDate) {
            // Corporate tax is paid at the end of each quarter
            $quarter = ceil($current->month / 3);
            $quarterEndMonth = $quarter * 3;
            $taxDate = $current->copy()->month($quarterEndMonth)->endOfMonth();
            
            if ($taxDate >= $startDate && $taxDate <= $endDate && !in_array($taxDate->format('Y-m-d'), array_map(function($d) {
                return $d->format('Y-m-d');
            }, $dates))) {
                $dates[] = $taxDate;
            }
            $current->addMonth();
        }
        
        return $dates;
    }

    /**
     * Estimate tax amount (simplified)
     */
    private function estimateTaxAmount($companyId, $branchId, $taxType, $date)
    {
        // Get average tax payments from last 3 months
        $avgTax = \App\Models\Payment::whereHas('branch', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->where('description', 'like', '%' . strtoupper($taxType) . '%')
        ->where('date', '>=', $date->copy()->subMonths(3))
        ->avg('amount');
        
        return $avgTax ?? 0;
    }

    /**
     * Generate forecast items from Recurring Expenses (Standing Orders)
     * Identifies recurring patterns from historical payments
     */
    private function generateFromRecurringExpenses($forecast, $startDate, $endDate, $companyId, $branchId, $scenario)
    {
        // Get historical payments to identify recurring patterns
        $query = \App\Models\Payment::whereHas('branch', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->where('date', '>=', $startDate->copy()->subMonths(6)) // Look back 6 months
        ->where('date', '<', $startDate);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $historicalPayments = $query->get();
        
        // Group by description to find recurring patterns
        $groupedPayments = $historicalPayments->groupBy(function($payment) {
            // Normalize description for grouping (remove dates, amounts, etc.)
            return strtolower(trim(preg_replace('/\d+/', '', $payment->description)));
        });
        
        foreach ($groupedPayments as $normalizedDesc => $payments) {
            // Only consider expenses that appear at least 3 times (likely recurring)
            if ($payments->count() < 3) {
                continue;
            }
            
            // Calculate average amount
            $avgAmount = $payments->avg('amount');
            
            // Determine frequency (monthly, weekly, etc.) based on payment dates
            $dates = $payments->pluck('date')->sort();
            $intervals = [];
            for ($i = 1; $i < $dates->count(); $i++) {
                $interval = $dates[$i]->diffInDays($dates[$i-1]);
                $intervals[] = $interval;
            }
            
            if (empty($intervals)) {
                continue;
            }
            
            $avgInterval = array_sum($intervals) / count($intervals);
            
            // Determine if monthly (25-35 days), weekly (6-8 days), or quarterly (85-95 days)
            $frequency = 'monthly';
            if ($avgInterval >= 6 && $avgInterval <= 8) {
                $frequency = 'weekly';
            } elseif ($avgInterval >= 85 && $avgInterval <= 95) {
                $frequency = 'quarterly';
            }
            
            // Generate forecast items based on frequency
            $currentDate = $startDate->copy();
            $lastPaymentDate = $dates->last();
            
            // Start from next expected payment date
            if ($frequency === 'monthly') {
                $currentDate = $lastPaymentDate->copy()->addMonth();
            } elseif ($frequency === 'weekly') {
                $currentDate = $lastPaymentDate->copy()->addWeek();
            } elseif ($frequency === 'quarterly') {
                $currentDate = $lastPaymentDate->copy()->addMonths(3);
            }
            
            while ($currentDate <= $endDate) {
                if ($currentDate >= $startDate) {
                    $forecastDate = $this->applyScenarioToDate($currentDate, $scenario, 'payable');
                    
                    if ($forecastDate >= $startDate && $forecastDate <= $endDate) {
                        CashflowForecastItem::create([
                            'cashflow_forecast_id' => $forecast->id,
                            'forecast_date' => $forecastDate,
                            'type' => 'outflow',
                            'source_type' => 'recurring_expense',
                            'source_reference' => $payments->first()->description,
                            'source_id' => null,
                            'amount' => $avgAmount,
                            'probability' => 90.00, // Recurring expenses are highly probable
                            'description' => 'Recurring Expense: ' . $payments->first()->description,
                        ]);
                    }
                }
                
                // Move to next occurrence
                if ($frequency === 'monthly') {
                    $currentDate->addMonth();
                } elseif ($frequency === 'weekly') {
                    $currentDate->addWeek();
                } elseif ($frequency === 'quarterly') {
                    $currentDate->addMonths(3);
                }
            }
        }
        
        // Also check for subscriptions (if subscription module exists)
        if (class_exists('\App\Models\Subscription')) {
            $subscriptions = \App\Models\Subscription::where('company_id', $companyId)
                ->where('status', 'active')
                ->where('auto_renew', true)
                ->get();
            
            foreach ($subscriptions as $subscription) {
                $currentDate = $startDate->copy();
                
                // Calculate next payment dates based on billing cycle
                while ($currentDate <= $endDate) {
                    if ($currentDate >= $startDate) {
                        CashflowForecastItem::create([
                            'cashflow_forecast_id' => $forecast->id,
                            'forecast_date' => $currentDate,
                            'type' => 'outflow',
                            'source_type' => 'subscription',
                            'source_reference' => $subscription->plan_name,
                            'source_id' => $subscription->id,
                            'amount' => $subscription->amount,
                            'probability' => 95.00,
                            'description' => 'Subscription: ' . $subscription->plan_name,
                        ]);
                    }
                    
                    // Move to next billing cycle
                    switch ($subscription->billing_cycle) {
                        case 'monthly':
                            $currentDate->addMonth();
                            break;
                        case 'quarterly':
                            $currentDate->addMonths(3);
                            break;
                        case 'half-yearly':
                            $currentDate->addMonths(6);
                            break;
                        case 'yearly':
                            $currentDate->addYear();
                            break;
                    }
                }
            }
        }
    }
}

