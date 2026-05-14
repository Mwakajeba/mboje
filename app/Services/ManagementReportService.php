<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ManagementReportService
{
    public function compute(array $options): array
    {
        $companyId = $options['company_id'];
        $branchIds = $options['branch_ids'] ?? [];
        $branchId = $options['branch_id'] ?? null;
        $startDate = $options['start_date'];
        $endDate = $options['end_date'];
        // previous period range with same length, right before current
        $days = (new \DateTime($startDate))->diff(new \DateTime($endDate))->days + 1;
        $prevEnd = (new \DateTime($startDate))->modify('-1 day')->format('Y-m-d');
        $prevStart = (new \DateTime($prevEnd))->modify('-' . ($days - 1) . ' day')->format('Y-m-d');

        $base = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(gt.date)'), [$startDate, $endDate]);

        if (!empty($branchIds) && !$branchId) {
            $base->whereIn('gt.branch_id', $branchIds);
        }
        if ($branchId) {
            $base->where('gt.branch_id', $branchId);
        }

        $revRow = (clone $base)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['income', 'revenue'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits, COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits')
            ->first();
        $revenue = (float)($revRow->credits ?? 0) - (float)($revRow->debits ?? 0);

        $expRow = (clone $base)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['expense', 'expenses'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();
        $expenses = (float)($expRow->debits ?? 0) - (float)($expRow->credits ?? 0);

        $netProfit = $revenue - $expenses;

        // Previous period aggregates
        $prevBase = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(gt.date)'), [$prevStart, $prevEnd]);
        if (!empty($branchIds) && !$branchId) {
            $prevBase->whereIn('gt.branch_id', $branchIds);
        }
        if ($branchId) {
            $prevBase->where('gt.branch_id', $branchId);
        }
        $prevRevRow = (clone $prevBase)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['income', 'revenue'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits, COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits')
            ->first();
        $prevRevenue = (float)($prevRevRow->credits ?? 0) - (float)($prevRevRow->debits ?? 0);
        $prevExpRow = (clone $prevBase)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['expense', 'expenses'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();
        $prevExpenses = (float)($prevExpRow->debits ?? 0) - (float)($prevExpRow->credits ?? 0);
        $prevNetProfit = $prevRevenue - $prevExpenses;

        // COGS for period and previous (approx by GL on COGS accounts)
        $cogsRow = (clone $base)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['expense', 'expenses'])
            ->where(function($q){
                $q->whereRaw('LOWER(ca.account_name) LIKE ?', ['cost of goods sold%'])
                  ->orWhereRaw('LOWER(ca.account_name) LIKE ?', ['cogs%']);
            })
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();
        $cogs = (float)($cogsRow->debits ?? 0) - (float)($cogsRow->credits ?? 0);
        $prevCogsRow = (clone $prevBase)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['expense', 'expenses'])
            ->where(function($q){
                $q->whereRaw('LOWER(ca.account_name) LIKE ?', ['cost of goods sold%'])
                  ->orWhereRaw('LOWER(ca.account_name) LIKE ?', ['cogs%']);
            })
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();
        $prevCogs = (float)($prevCogsRow->debits ?? 0) - (float)($prevCogsRow->credits ?? 0);

        // Cash flow (receipts - payments) for period and previous period
        $cashIn = DB::table('receipts as r')
            ->when(!empty($branchIds) && !$branchId, fn($q) => $q->whereIn('r.branch_id', $branchIds))
            ->when($branchId, fn($q) => $q->where('r.branch_id', $branchId))
            ->whereBetween(DB::raw('DATE(r.date)'), [$startDate, $endDate])
            ->sum('amount');
        $cashOut = DB::table('payments as p')
            ->when(!empty($branchIds) && !$branchId, fn($q) => $q->whereIn('p.branch_id', $branchIds))
            ->when($branchId, fn($q) => $q->where('p.branch_id', $branchId))
            ->whereBetween(DB::raw('DATE(p.date)'), [$startDate, $endDate])
            ->sum('amount');
        $cashFlow = (float)$cashIn - (float)$cashOut;

        $prevCashIn = DB::table('receipts as r')
            ->when(!empty($branchIds) && !$branchId, fn($q) => $q->whereIn('r.branch_id', $branchIds))
            ->when($branchId, fn($q) => $q->where('r.branch_id', $branchId))
            ->whereBetween(DB::raw('DATE(r.date)'), [$prevStart, $prevEnd])
            ->sum('amount');
        $prevCashOut = DB::table('payments as p')
            ->when(!empty($branchIds) && !$branchId, fn($q) => $q->whereIn('p.branch_id', $branchIds))
            ->when($branchId, fn($q) => $q->where('p.branch_id', $branchId))
            ->whereBetween(DB::raw('DATE(p.date)'), [$prevStart, $prevEnd])
            ->sum('amount');
        $prevCashFlow = (float)$prevCashIn - (float)$prevCashOut;

        // Receivables balance (at present)
        $receivables = DB::table('sales_invoices')
            ->when(!empty($branchIds) && !$branchId, fn($q) => $q->whereIn('branch_id', $branchIds))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['cancelled'])
            ->sum('balance_due');

        // DSO: receivables / (revenue per day) for the period
        $revenuePerDay = $revenue > 0 && $days > 0 ? ($revenue / $days) : 0;
        $dso = $revenuePerDay > 0 ? ($receivables / $revenuePerDay) : 0;

        // Ratios
        $netProfitMargin = $revenue != 0 ? ($netProfit / abs($revenue)) * 100 : 0;
        $prevNetProfitMargin = $prevRevenue != 0 ? (($prevNetProfit) / abs($prevRevenue)) * 100 : 0;
        $expenseRatio = $revenue != 0 ? ($expenses / abs($revenue)) * 100 : 0;
        $prevExpenseRatio = $prevRevenue != 0 ? ($prevExpenses / abs($prevRevenue)) * 100 : 0;
        $grossProfit = $revenue - $cogs;
        $prevGrossProfit = $prevRevenue - $prevCogs;
        $grossProfitMargin = $revenue != 0 ? ($grossProfit / abs($revenue)) * 100 : 0;
        $prevGrossProfitMargin = $prevRevenue != 0 ? ($prevGrossProfit / abs($prevRevenue)) * 100 : 0;

        // Inventory holding period (DIO) approx using inventory assets balance and COGS per day
        $inventoryAssetsRow = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereRaw('LOWER(ac.name) = ?', ['assets'])
            ->whereRaw('LOWER(ca.account_name) LIKE ?', ['%inventory%'])
            ->when(!empty($branchIds) && !$branchId, fn($q) => $q->whereIn('gt.branch_id', $branchIds))
            ->when($branchId, fn($q) => $q->where('gt.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();
        $inventoryAssets = (float)($inventoryAssetsRow->debits ?? 0) - (float)($inventoryAssetsRow->credits ?? 0);
        $cogsPerDay = $cogs > 0 && $days > 0 ? ($cogs / $days) : 0;
        $dio = $cogsPerDay > 0 ? ($inventoryAssets / $cogsPerDay) : 0;

        // Creditors Payment Period (DPO) using Trade Payables from GL and COGS per day
        $payablesRow = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereRaw('LOWER(ac.name) = ?', ['liabilities'])
            ->whereRaw('(LOWER(ca.account_name) LIKE ? OR LOWER(ca.account_name) LIKE ? OR LOWER(ca.account_name) LIKE ?)', ['%trade payable%', '%account payable%', '%payable%'])
            ->whereDate('gt.date', '<=', $endDate)
            ->when(!empty($branchIds) && !$branchId, fn($q) => $q->whereIn('gt.branch_id', $branchIds))
            ->when($branchId, fn($q) => $q->where('gt.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits, COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits')
            ->first();
        $payables = (float)($payablesRow->credits ?? 0) - (float)($payablesRow->debits ?? 0);
        
        // Debug: Log payables calculation
        \Log::info('DPO Debug', [
            'payables' => $payables,
            'payables_credits' => $payablesRow->credits ?? 0,
            'payables_debits' => $payablesRow->debits ?? 0,
            'cogs' => $cogs,
            'cogsPerDay' => $cogsPerDay,
            'days' => $days,
            'endDate' => $endDate,
            'companyId' => $companyId,
            'branchIds' => $branchIds,
            'branchId' => $branchId,
        ]);
        
        // Use COGS per day for DPO calculation (standard formula)
        $dpo = $cogsPerDay > 0 ? ($payables / $cogsPerDay) : 0;

        // Get balance sheet data for liquidity and profitability ratios
        $balanceSheet = $this->getBalanceSheetSnapshot($companyId, $branchIds, $branchId, $endDate);
        $prevBalanceSheet = $this->getBalanceSheetSnapshot($companyId, $branchIds, $branchId, $prevEnd);
        
        $totalAssets = $balanceSheet['assets'] ?? 0;
        $totalLiabilities = $balanceSheet['liabilities'] ?? 0;
        $totalEquity = ($balanceSheet['equity'] ?? 0) + ($balanceSheet['net_profit'] ?? 0);
        $prevTotalAssets = $prevBalanceSheet['assets'] ?? 0;
        $prevTotalLiabilities = $prevBalanceSheet['liabilities'] ?? 0;
        $prevTotalEquity = ($prevBalanceSheet['equity'] ?? 0) + ($prevBalanceSheet['net_profit'] ?? 0);
        
        // Approximate current assets and current liabilities (using total as approximation if not available)
        // Note: Ideally these should be filtered by account groups, but using total as approximation
        $currentAssets = $totalAssets; // Approximation - ideally should filter current vs non-current
        $currentLiabilities = $totalLiabilities; // Approximation
        $prevCurrentAssets = $prevTotalAssets;
        $prevCurrentLiabilities = $prevTotalLiabilities;
        
        // Cash balance (from GL cash accounts)
        $cashBalance = $this->getCashBalance($companyId, $branchIds, $branchId, $endDate);
        $prevCashBalance = $this->getCashBalance($companyId, $branchIds, $branchId, $prevEnd);
        
        // Average inventory (using current as approximation)
        $avgInventory = $inventoryAssets;
        $prevAvgInventory = $this->getInventoryBalance($companyId, $branchIds, $branchId, $prevEnd);
        
        // Average receivables (using current as approximation)
        $avgReceivables = $receivables;
        $prevAvgReceivables = DB::table('sales_invoices')
            ->when(!empty($branchIds) && !$branchId, fn($q) => $q->whereIn('branch_id', $branchIds))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['cancelled'])
            ->whereDate('invoice_date', '<=', $prevEnd)
            ->sum('balance_due');
        
        // Average payables (using beginning and ending balances)
        $prevPayablesRow = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereRaw('LOWER(ac.name) = ?', ['liabilities'])
            ->whereRaw('(LOWER(ca.account_name) LIKE ? OR LOWER(ca.account_name) LIKE ? OR LOWER(ca.account_name) LIKE ?)', ['%trade payable%', '%account payable%', '%payable%'])
            ->whereDate('gt.date', '<=', $prevEnd)
            ->when(!empty($branchIds) && !$branchId, fn($q) => $q->whereIn('gt.branch_id', $branchIds))
            ->when($branchId, fn($q) => $q->where('gt.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits, COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits')
            ->first();
        $prevPayables = (float)($prevPayablesRow->credits ?? 0) - (float)($prevPayablesRow->debits ?? 0);
        $avgPayables = ($payables + $prevPayables) / 2;
        $prevAvgPayables = $prevPayables; // For previous period, use beginning balance from period before that

        // 🔹 1. Liquidity & Solvency KPIs
        $currentRatio = $currentLiabilities != 0 ? ($currentAssets / $currentLiabilities) : 0;
        $prevCurrentRatio = $prevCurrentLiabilities != 0 ? ($prevCurrentAssets / $prevCurrentLiabilities) : 0;
        
        $quickRatio = $currentLiabilities != 0 ? (($currentAssets - $inventoryAssets) / $currentLiabilities) : 0;
        $prevQuickRatio = $prevCurrentLiabilities != 0 ? (($prevCurrentAssets - $prevAvgInventory) / $prevCurrentLiabilities) : 0;
        
        $cashRatio = $currentLiabilities != 0 ? ($cashBalance / $currentLiabilities) : 0;
        $prevCashRatio = $prevCurrentLiabilities != 0 ? ($prevCashBalance / $prevCurrentLiabilities) : 0;
        
        $debtToEquity = $totalEquity != 0 ? ($totalLiabilities / $totalEquity) : 0;
        $prevDebtToEquity = $prevTotalEquity != 0 ? ($prevTotalLiabilities / $prevTotalEquity) : 0;

        // 🔹 2. Efficiency / Activity KPIs
        $assetTurnover = $totalAssets != 0 ? ($revenue / $totalAssets) : 0;
        $prevAssetTurnover = $prevTotalAssets != 0 ? ($prevRevenue / $prevTotalAssets) : 0;
        
        $inventoryTurnover = $avgInventory != 0 ? ($cogs / $avgInventory) : 0;
        $prevInventoryTurnover = $prevAvgInventory != 0 ? ($prevCogs / $prevAvgInventory) : 0;
        
        $receivablesTurnover = $avgReceivables != 0 ? ($revenue / $avgReceivables) : 0;
        $prevReceivablesTurnover = $prevAvgReceivables != 0 ? ($prevRevenue / $prevAvgReceivables) : 0;
        
        $payablesTurnover = $avgPayables != 0 ? ($cogs / $avgPayables) : 0;
        $prevPayablesTurnover = $prevAvgPayables != 0 ? ($prevCogs / $prevAvgPayables) : 0;

        // 🔹 3. Profitability & Return KPIs
        $roa = $totalAssets != 0 ? ($netProfit / $totalAssets) * 100 : 0;
        $prevRoa = $prevTotalAssets != 0 ? ($prevNetProfit / $prevTotalAssets) * 100 : 0;
        
        $roe = $totalEquity != 0 ? ($netProfit / $totalEquity) * 100 : 0;
        $prevRoe = $prevTotalEquity != 0 ? ($prevNetProfit / $prevTotalEquity) * 100 : 0;
        
        // Operating Profit = Revenue - Operating Expenses (excluding COGS)
        $operatingExpenses = $expenses - $cogs;
        $prevOperatingExpenses = $prevExpenses - $prevCogs;
        $operatingProfit = $revenue - $operatingExpenses;
        $prevOperatingProfit = $prevRevenue - $prevOperatingExpenses;
        $operatingProfitMargin = $revenue != 0 ? ($operatingProfit / abs($revenue)) * 100 : 0;
        $prevOperatingProfitMargin = $prevRevenue != 0 ? ($prevOperatingProfit / abs($prevRevenue)) * 100 : 0;
        
        // EBITDA approximation (Operating Profit + Depreciation/Amortization - we'll approximate as Operating Profit)
        // Note: Depreciation/Amortization would need to be extracted from GL if available
        $ebitda = $operatingProfit; // Approximation
        $prevEbitda = $prevOperatingProfit; // Approximation
        $ebitdaMargin = $revenue != 0 ? ($ebitda / abs($revenue)) * 100 : 0;
        $prevEbitdaMargin = $prevRevenue != 0 ? ($prevEbitda / abs($prevRevenue)) * 100 : 0;

        // 🔹 4. Growth KPIs
        $revenueGrowthRate = $prevRevenue != 0 ? (($revenue - $prevRevenue) / abs($prevRevenue)) * 100 : ($revenue != 0 ? 100.0 : 0.0);
        $netProfitGrowthRate = $prevNetProfit != 0 ? (($netProfit - $prevNetProfit) / abs($prevNetProfit)) * 100 : ($netProfit != 0 ? 100.0 : 0.0);
        $expenseGrowthRate = $prevExpenses != 0 ? (($expenses - $prevExpenses) / abs($prevExpenses)) * 100 : ($expenses != 0 ? 100.0 : 0.0);

        // 🔹 5. Cash Flow Health KPIs
        // Operating Cash Flow = Cash Flow from operations (receipts - payments)
        $operatingCashFlow = $cashFlow;
        $prevOperatingCashFlow = $prevCashFlow;
        $operatingCashFlowRatio = $currentLiabilities != 0 ? ($operatingCashFlow / $currentLiabilities) : 0;
        $prevOperatingCashFlowRatio = $prevCurrentLiabilities != 0 ? ($prevOperatingCashFlow / $prevCurrentLiabilities) : 0;
        
        // Free Cash Flow = Operating Cash Flow - Capital Expenditure
        // Capital Expenditure would need to be tracked separately - approximating as 0 for now
        $capitalExpenditure = 0; // Would need GL tracking for capital assets
        $freeCashFlow = $operatingCashFlow - $capitalExpenditure;
        $prevCapitalExpenditure = 0;
        $prevFreeCashFlow = $prevOperatingCashFlow - $prevCapitalExpenditure;
        
        // Cash Conversion Cycle = DIO + DSO - DPO
        $cashConversionCycle = $dio + $dso - $dpo;
        $prevDio = $prevAvgInventory > 0 && $prevCogs > 0 && $days > 0 ? ($prevAvgInventory / ($prevCogs / $days)) : 0;
        $prevDso = $prevRevenue > 0 && $days > 0 ? ($prevAvgReceivables / ($prevRevenue / $days)) : 0;
        $prevCogsPerDay = $prevCogs > 0 && $days > 0 ? ($prevCogs / $days) : 0;
        $prevDpoCalculated = $prevCogsPerDay > 0 ? ($prevAvgPayables / $prevCogsPerDay) : 0;
        $prevCashConversionCycle = $prevDio + $prevDso - $prevDpoCalculated;

        $allKpis = [
            // Existing KPIs
            'revenue' => $this->decorateKpi('revenue', 'Revenue', $revenue, $prevRevenue),
            'expenses' => $this->decorateKpi('expenses', 'Expenses', $expenses, $prevExpenses),
            'net_profit' => $this->decorateKpi('net_profit', 'Net Profit', $netProfit, $prevNetProfit),
            'cash_flow' => $this->decorateKpi('cash_flow', 'Cash Flow', $cashFlow, $prevCashFlow),
            'net_profit_margin' => $this->decorateKpi('net_profit_margin', 'Net Profit Margin (%)', $netProfitMargin, $prevNetProfitMargin),
            'expense_ratio' => $this->decorateKpi('expense_ratio', 'Expense Ratio (%)', $expenseRatio, $prevExpenseRatio),
            'receivables' => $this->decorateKpi('receivables', 'Outstanding Receivables', $receivables, null),
            'dso' => $this->decorateKpi('dso', 'Debtors Collection Period (Days)', $dso, null),
            'gross_profit_margin' => $this->decorateKpi('gross_profit_margin', 'Gross Profit Margin (%)', $grossProfitMargin, $prevGrossProfitMargin),
            'dio' => $this->decorateKpi('dio', 'Inventory Holding Period (Days)', $dio, null),
            'dpo' => $this->decorateKpi('dpo', 'Creditors Payment Period (Days)', $dpo, $prevDpoCalculated),
            
            // 🔹 1. Liquidity & Solvency KPIs
            'current_ratio' => $this->decorateKpi('current_ratio', 'Current Ratio', $currentRatio, $prevCurrentRatio),
            'quick_ratio' => $this->decorateKpi('quick_ratio', 'Quick Ratio (Acid Test)', $quickRatio, $prevQuickRatio),
            'cash_ratio' => $this->decorateKpi('cash_ratio', 'Cash Ratio', $cashRatio, $prevCashRatio),
            'debt_to_equity' => $this->decorateKpi('debt_to_equity', 'Debt-to-Equity Ratio', $debtToEquity, $prevDebtToEquity),
            
            // 🔹 2. Efficiency / Activity KPIs
            'asset_turnover' => $this->decorateKpi('asset_turnover', 'Asset Turnover Ratio', $assetTurnover, $prevAssetTurnover),
            'inventory_turnover' => $this->decorateKpi('inventory_turnover', 'Inventory Turnover Ratio', $inventoryTurnover, $prevInventoryTurnover),
            'receivables_turnover' => $this->decorateKpi('receivables_turnover', 'Receivables Turnover Ratio', $receivablesTurnover, $prevReceivablesTurnover),
            'payables_turnover' => $this->decorateKpi('payables_turnover', 'Payables Turnover Ratio', $payablesTurnover, $prevPayablesTurnover),
            
            // 🔹 3. Profitability & Return KPIs
            'roa' => $this->decorateKpi('roa', 'Return on Assets (ROA) (%)', $roa, $prevRoa),
            'roe' => $this->decorateKpi('roe', 'Return on Equity (ROE) (%)', $roe, $prevRoe),
            'operating_profit_margin' => $this->decorateKpi('operating_profit_margin', 'Operating Profit Margin (%)', $operatingProfitMargin, $prevOperatingProfitMargin),
            'ebitda_margin' => $this->decorateKpi('ebitda_margin', 'EBITDA Margin (%)', $ebitdaMargin, $prevEbitdaMargin),
            
            // 🔹 4. Growth KPIs
            'revenue_growth_rate' => $this->decorateKpi('revenue_growth_rate', 'Revenue Growth Rate (%)', $revenueGrowthRate, null),
            'net_profit_growth_rate' => $this->decorateKpi('net_profit_growth_rate', 'Net Profit Growth Rate (%)', $netProfitGrowthRate, null),
            'expense_growth_rate' => $this->decorateKpi('expense_growth_rate', 'Expense Growth Rate (%)', $expenseGrowthRate, null),
            
            // 🔹 5. Cash Flow Health KPIs
            'operating_cash_flow_ratio' => $this->decorateKpi('operating_cash_flow_ratio', 'Operating Cash Flow Ratio', $operatingCashFlowRatio, $prevOperatingCashFlowRatio),
            'free_cash_flow' => $this->decorateKpi('free_cash_flow', 'Free Cash Flow (FCF)', $freeCashFlow, $prevFreeCashFlow),
            'cash_conversion_cycle' => $this->decorateKpi('cash_conversion_cycle', 'Cash Conversion Cycle (Days)', $cashConversionCycle, $prevCashConversionCycle),
        ];

        // Read enabled KPI keys from settings; default to all
        $enabled = \App\Models\SystemSetting::getValue('cmr_enabled_kpis', null);
        $enabledKeys = [];
        if ($enabled) {
            $decoded = is_array($enabled) ? $enabled : json_decode((string)$enabled, true);
            if (is_array($decoded)) $enabledKeys = array_values(array_intersect(array_keys($allKpis), $decoded));
        }
        if (empty($enabledKeys)) {
            $enabledKeys = array_keys($allKpis);
        }

        $kpis = [];
        foreach ($enabledKeys as $key) {
            $kpis[] = $allKpis[$key];
        }

        return [
            'kpis' => $kpis,
            'summary' => $this->generateNarration($revenue, $expenses, $netProfit),
            'period' => [ 'start' => $startDate, 'end' => $endDate, 'prev_start' => $prevStart, 'prev_end' => $prevEnd ],
        ];
    }

    private function generateNarration(float $revenue, float $expenses, float $netProfit): string
    {
        $trend = $netProfit >= 0 ? 'profitable' : 'loss-making';
        return sprintf(
            'The period was %s with revenue of TZS %s, expenses of TZS %s, and net %s of TZS %s.',
            $trend,
            number_format($revenue, 2),
            number_format($expenses, 2),
            $netProfit >= 0 ? 'profit' : 'loss',
            number_format(abs($netProfit), 2)
        );
    }

    private function decorateKpi(string $key, string $label, float $current, ?float $previous = null): array
    {
        $hasPrev = $previous !== null;
        $change = $hasPrev
            ? ($previous != 0 ? (($current - $previous) / abs($previous)) * 100 : ($current != 0 ? 100.0 : 0.0))
            : 0.0;
        $trend = $hasPrev
            ? ($current == $previous ? 'flat' : ($current > $previous ? 'up' : 'down'))
            : 'flat';
        
        // For expenses and expense_ratio, invert the trend (increase = bad, decrease = good)
        // Also for expense_growth_rate (lower growth is better)
        if (in_array($key, ['expenses', 'expense_ratio', 'expense_growth_rate'])) {
            if ($trend === 'up') {
                $trend = 'down';
            } elseif ($trend === 'down') {
                $trend = 'up';
            }
        }
        
        return [
            'key' => $key,
            'label' => $label,
            'value' => $current,
            'previous' => $previous,
            'change_percent' => $change,
            'trend' => $trend,
        ];
    }

    private function getBalanceSheetSnapshot(int $companyId, array $branchIds, ?int $branchId, string $asOfDate): array
    {
        $base = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereDate('gt.date', '<=', $asOfDate);
        if (!empty($branchIds) && !$branchId) {
            $base->whereIn('gt.branch_id', $branchIds);
        }
        if ($branchId) {
            $base->where('gt.branch_id', $branchId);
        }

        $rows = (clone $base)
            ->select('ac.name as class_name')
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="debit" THEN gt.amount ELSE 0 END),0) as debit_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="credit" THEN gt.amount ELSE 0 END),0) as credit_total')
            ->groupBy('ac.name')
            ->get();

        $assets = 0;
        $liabilities = 0;
        $equity = 0;
        foreach ($rows as $r) {
            $cls = strtolower($r->class_name);
            if ($cls === 'assets') {
                $assets = (float)$r->debit_total - (float)$r->credit_total;
            }
            if ($cls === 'liabilities') {
                $liabilities = (float)$r->credit_total - (float)$r->debit_total;
            }
            if ($cls === 'equity') {
                $equity = (float)$r->credit_total - (float)$r->debit_total;
            }
        }

        // Calculate YTD net profit for the balance sheet
        $yearStart = date('Y-01-01', strtotime($asOfDate));
        $ytdBase = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereBetween(DB::raw('DATE(gt.date)'), [$yearStart, $asOfDate]);
        if (!empty($branchIds) && !$branchId) {
            $ytdBase->whereIn('gt.branch_id', $branchIds);
        }
        if ($branchId) {
            $ytdBase->where('gt.branch_id', $branchId);
        }

        $ytdRevRow = (clone $ytdBase)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['income', 'revenue'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits, COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits')
            ->first();
        $ytdRevenue = (float)($ytdRevRow->credits ?? 0) - (float)($ytdRevRow->debits ?? 0);

        $ytdExpRow = (clone $ytdBase)
            ->whereIn(DB::raw('LOWER(ac.name)'), ['expense', 'expenses'])
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature = "debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature = "credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();
        $ytdExpenses = (float)($ytdExpRow->debits ?? 0) - (float)($ytdExpRow->credits ?? 0);
        $ytdNetProfit = $ytdRevenue - $ytdExpenses;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'net_profit' => $ytdNetProfit,
            'equity_including_profit' => $equity + $ytdNetProfit,
        ];
    }

    private function getCashBalance(int $companyId, array $branchIds, ?int $branchId, string $asOfDate): float
    {
        $base = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereRaw('LOWER(ac.name) = ?', ['assets'])
            ->where(function($q) {
                $q->whereRaw('LOWER(ca.account_name) LIKE ?', ['%cash%'])
                  ->orWhereRaw('LOWER(ca.account_name) LIKE ?', ['%bank%']);
            })
            ->whereDate('gt.date', '<=', $asOfDate);
        if (!empty($branchIds) && !$branchId) {
            $base->whereIn('gt.branch_id', $branchIds);
        }
        if ($branchId) {
            $base->where('gt.branch_id', $branchId);
        }

        $cashRow = (clone $base)
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature="credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();

        return (float)($cashRow->debits ?? 0) - (float)($cashRow->credits ?? 0);
    }

    private function getInventoryBalance(int $companyId, array $branchIds, ?int $branchId, string $asOfDate): float
    {
        $base = DB::table('gl_transactions as gt')
            ->join('chart_accounts as ca', 'gt.chart_account_id', '=', 'ca.id')
            ->join('account_class_groups as acg', 'ca.account_class_group_id', '=', 'acg.id')
            ->join('account_class as ac', 'acg.class_id', '=', 'ac.id')
            ->where('acg.company_id', $companyId)
            ->whereRaw('LOWER(ac.name) = ?', ['assets'])
            ->whereRaw('LOWER(ca.account_name) LIKE ?', ['%inventory%'])
            ->whereDate('gt.date', '<=', $asOfDate);
        if (!empty($branchIds) && !$branchId) {
            $base->whereIn('gt.branch_id', $branchIds);
        }
        if ($branchId) {
            $base->where('gt.branch_id', $branchId);
        }

        $invRow = (clone $base)
            ->selectRaw('COALESCE(SUM(CASE WHEN gt.nature="debit" THEN gt.amount ELSE 0 END),0) as debits, COALESCE(SUM(CASE WHEN gt.nature="credit" THEN gt.amount ELSE 0 END),0) as credits')
            ->first();

        return (float)($invRow->debits ?? 0) - (float)($invRow->credits ?? 0);
    }
}


