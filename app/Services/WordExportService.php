<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

class WordExportService
{
    /**
     * Get justified text style array
     */
    private function getJustifiedStyle($style = [])
    {
        return array_merge(['alignment' => 'both'], $style);
    }
    
    /**
     * Generate comprehensive Word document for Consolidated Management Report
     */
    public function generateConsolidatedManagementReport($data)
    {
        $phpWord = new PhpWord();
        
        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator($data['user']->name ?? 'System');
        $properties->setCompany($data['company']->name ?? '');
        $properties->setTitle('Consolidated Management Report');
        $properties->setDescription('Consolidated Management Report for ' . $data['period'] . ' ' . $data['year']);
        
        // Define styles
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 24, 'color' => '000000'], ['spaceAfter' => 240]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 18, 'color' => '000000'], ['spaceAfter' => 120]);
        $phpWord->addTitleStyle(3, ['bold' => true, 'size' => 14, 'color' => '000000'], ['spaceAfter' => 60]);
        
        $section = $phpWord->addSection(['marginTop' => 1440, 'marginBottom' => 1440, 'marginLeft' => 1440, 'marginRight' => 1440]);
        
        // Cover Page
        $this->addCoverPage($section, $data);
        $section->addPageBreak();
        
        // Table of Contents
        $this->addTableOfContents($section);
        $section->addPageBreak();
        
        // Executive Summary
        $this->addExecutiveSummary($section, $data);
        
        // About This Consolidated Report
        $this->addAboutSection($section, $data);
        
        // Key Performance Indicators
        $this->addKPIs($section, $data);
        
        // KPI Chart
        $this->addKPIChart($section, $data);
        
        // Analytical Highlights
        $this->addAnalyticalHighlights($section, $data);
        
        // Balance Sheet Snapshot
        $section->addPageBreak();
        $this->addBalanceSheetSnapshot($section, $data);
        
        // Detailed Balance Sheet
        $this->addDetailedBalanceSheet($section, $data);
        
        // Profit & Loss Summary
        $section->addPageBreak();
        $this->addProfitLossSummary($section, $data);
        
        // Detailed Profit & Loss
        $this->addDetailedProfitLoss($section, $data);
        
        // Methodology
        $section->addPageBreak();
        $this->addMethodology($section, $data);
        
        // Data Sources & Scope
        $this->addDataSources($section, $data);
        
        // Definitions & Formulas
        $this->addDefinitions($section, $data);
        
        // Management Commentary
        $section->addPageBreak();
        $this->addManagementCommentary($section, $data);
        
        // Overall Commentary Summary
        $this->addOverallCommentary($section, $data);
        
        // Appendix
        $section->addPageBreak();
        $this->addAppendix($section, $data);
        
        // Footer (last page only)
        $this->addFooter($section, $data);
        
        return $phpWord;
    }
    
    private function addCoverPage($section, $data)
    {
        $section->addTextBreak(2);
        $section->addTitle('CONSOLIDATED MANAGEMENT REPORT', 1);
        $section->addTextBreak(1);
        
        // Company info
        if ($data['company']) {
            $company = $data['company'];
            $section->addText($company->name ?? '', ['bold' => true, 'size' => 16]);
            if ($company->address) {
                $section->addText($company->address, ['size' => 12]);
            }
            $contactBits = [];
            if ($company->phone) $contactBits[] = 'Tel: ' . $company->phone;
            if ($company->email) $contactBits[] = 'Email: ' . $company->email;
            if ($company->website) $contactBits[] = 'Web: ' . $company->website;
            if (!empty($contactBits)) {
                $section->addText(implode(' | ', $contactBits), ['size' => 12]);
            }
            $regBits = [];
            if ($company->tin) $regBits[] = 'TIN: ' . $company->tin;
            if ($company->vat_number) $regBits[] = 'VAT: ' . $company->vat_number;
            if ($company->registration_no) $regBits[] = 'Reg No: ' . $company->registration_no;
            if (!empty($regBits)) {
                $section->addText(implode(' | ', $regBits), ['size' => 12]);
            }
        }
        
        $section->addTextBreak(2);
        
        // Period info
        $periodLabel = ucfirst($data['period']);
        if ($data['period'] === 'month') {
            $periodLabel .= ' - ' . date('F Y', mktime(0, 0, 0, $data['month'], 1, $data['year']));
        } elseif ($data['period'] === 'quarter') {
            $periodLabel .= ' ' . $data['quarter'] . ' - ' . $data['year'];
        } else {
            $periodLabel .= ' ' . $data['year'];
        }
        
        $section->addText('Period: ' . strtoupper($data['period']) . ' (' . $data['startDate'] . ' to ' . $data['endDate'] . ')', ['size' => 12]);
        if ($data['branch']) {
            $section->addText($data['company']->name ?? 'Company' . ' — ' . $data['branch']->name, ['size' => 12]);
        }
        $section->addText('Generated on ' . now()->format('Y-m-d H:i'), ['size' => 12]);
    }
    
    private function addTableOfContents($section)
    {
        $section->addTitle('Table of Contents', 2);
        $toc = [
            'Executive Summary',
            'About This Consolidated Report',
            'Key Performance Indicators',
            'Analytical Highlights',
            'Balance Sheet Snapshot',
            'Profit & Loss',
            'Methodology',
            'Data Sources & Scope',
            'Definitions & Formulas',
            'Management Commentary',
            'Overall Commentary Summary',
            'Appendix'
        ];
        foreach ($toc as $index => $item) {
            $section->addText(($index + 1) . '. ' . $item, ['size' => 12]);
        }
    }
    
    private function addExecutiveSummary($section, $data)
    {
        $section->addPageBreak();
        $section->addTitle('Executive Summary', 2);
        $section->addText($data['summary'] ?? 'No summary available.', $this->getJustifiedStyle(['size' => 11, 'spaceAfter' => 120]));
    }
    
    private function addAboutSection($section, $data)
    {
        $section->addTitle('About This Consolidated Report', 2);
        $text = "This consolidated report aggregates key metrics across Accounting and Operations to support timely decision making. It " .
                "presents performance for the selected period alongside the previous comparable period to reveal direction and momentum. " .
                "The KPIs included here reflect your configuration and data availability: Revenue, Expenses, Net Profit, Cash Flow, " .
                "Outstanding Receivables and Debtors Collection Period (DSO), Creditors Payment Period (DPO), Inventory Holding Period (DIO), " .
                "and Profitability ratios such as Gross Profit Margin and Net Profit Margin. Where applicable, values show prior period, " .
                "percent change and trend arrows.";
        $section->addText($text, $this->getJustifiedStyle(['size' => 11, 'spaceAfter' => 120]));
    }
    
    private function addKPIs($section, $data)
    {
        $section->addPageBreak();
        $section->addTitle('Key Performance Indicators', 2);
        
        if (empty($data['kpis'])) {
            $section->addText('No KPIs available.', ['size' => 11]);
            return;
        }
        
        // Create 4-column table like PDF
        $kpis = $data['kpis'];
        $kCount = count($kpis);
        
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
        
        // Add rows in groups of 4
        for ($i = 0; $i < $kCount; $i += 4) {
            $table->addRow();
            for ($j = 0; $j < 4; $j++) {
                $idx = $i + $j;
                $cell = $table->addCell(2000);
                if ($idx < $kCount) {
                    $kpi = $kpis[$idx];
                    $key = $kpi['key'] ?? '';
                    $isPercent = in_array($key, ['net_profit_margin','expense_ratio','gross_profit_margin','roa','roe','operating_profit_margin','ebitda_margin','revenue_growth_rate','net_profit_growth_rate','expense_growth_rate']);
                    $isDays = in_array($key, ['dso','dpo','dio','cash_conversion_cycle']);
                    $isRatio = in_array($key, ['current_ratio','quick_ratio','cash_ratio','debt_to_equity','asset_turnover','inventory_turnover','receivables_turnover','payables_turnover','operating_cash_flow_ratio']);
                    
                    $trend = $kpi['trend'] ?? 'flat';
                    $trendSymbol = $trend === 'up' ? '↑' : ($trend === 'down' ? '↓' : '—');
                    $trendColor = $trend === 'up' ? '27ae60' : ($trend === 'down' ? 'e74c3c' : '95a5a6');
                    
                    $cell->addText($kpi['label'] ?? '', ['size' => 9, 'color' => '666666']);
                    $cell->addText($trendSymbol . ' ', ['size' => 9, 'color' => $trendColor]);
                    
                    $currVal = $kpi['value'] ?? 0;
                    if ($isPercent) {
                        $cell->addText(number_format($currVal, 1) . '%', ['size' => 11, 'bold' => true]);
                    } elseif ($isDays) {
                        $cell->addText(number_format($currVal, 0) . ' days', ['size' => 11, 'bold' => true]);
                    } elseif ($isRatio) {
                        $cell->addText(number_format($currVal, 2), ['size' => 11, 'bold' => true]);
                    } else {
                        $cell->addText('TZS ' . number_format($currVal, 2), ['size' => 11, 'bold' => true]);
                    }
                    
                    if ($kpi['previous'] !== null) {
                        $prevVal = $kpi['previous'];
                        $change = $kpi['change_percent'] ?? 0;
                        $prevText = 'Prev: ';
                        if ($isPercent) {
                            $prevText .= number_format($prevVal, 1) . '%';
                        } elseif ($isDays) {
                            $prevText .= number_format($prevVal, 0) . ' days';
                        } elseif ($isRatio) {
                            $prevText .= number_format($prevVal, 2);
                        } else {
                            $prevText .= 'TZS ' . number_format($prevVal, 2);
                        }
                        $prevText .= ' | ' . number_format($change, 1) . '%';
                        $cell->addText($prevText, ['size' => 8, 'color' => '666666']);
                    }
                } else {
                    $cell->addText('', ['size' => 11]);
                }
            }
        }
    }
    
    private function addKPIChart($section, $data)
    {
        $section->addTitle('KPI Chart', 2);
        
        $kpis = $data['kpis'] ?? [];
        $rev = collect($kpis)->firstWhere('key', 'revenue')['value'] ?? 0;
        $exp = collect($kpis)->firstWhere('key', 'expenses')['value'] ?? 0;
        $prf = collect($kpis)->firstWhere('key', 'net_profit')['value'] ?? ($rev - $exp);
        
        $maxVal = max(1, abs($rev), abs($exp), abs($prf));
        
        $section->addText('Revenue: TZS ' . number_format($rev, 2), $this->getJustifiedStyle(['size' => 11]));
        $section->addText('Expenses: TZS ' . number_format($exp, 2), $this->getJustifiedStyle(['size' => 11]));
        $section->addText('Net Profit: TZS ' . number_format($prf, 2), $this->getJustifiedStyle(['size' => 11]));
    }
    
    private function addAnalyticalHighlights($section, $data)
    {
        $section->addTitle('Analytical Highlights', 2);
        
        if (empty($data['kpis'])) {
            return;
        }
        
        foreach ($data['kpis'] as $kpi) {
            $key = $kpi['key'] ?? '';
            $isPercent = in_array($key, ['net_profit_margin','expense_ratio','gross_profit_margin','roa','roe','operating_profit_margin','ebitda_margin','revenue_growth_rate','net_profit_growth_rate','expense_growth_rate']);
            $isDays = in_array($key, ['dso','dpo','dio','cash_conversion_cycle']);
            $isRatio = in_array($key, ['current_ratio','quick_ratio','cash_ratio','debt_to_equity','asset_turnover','inventory_turnover','receivables_turnover','payables_turnover','operating_cash_flow_ratio']);
            
            $trend = $kpi['trend'] ?? 'flat';
            $verb = $trend === 'up' ? 'increased' : ($trend === 'down' ? 'decreased' : 'remained unchanged');
            
            $currFmt = $isPercent ? (number_format((float)($kpi['value'] ?? 0), 1) . '%') : ($isDays ? (number_format((float)($kpi['value'] ?? 0), 0) . ' days') : ($isRatio ? number_format((float)($kpi['value'] ?? 0), 2) : ('TZS ' . number_format((float)($kpi['value'] ?? 0), 2))));
            
            if ($kpi['previous'] !== null) {
                $prevFmt = $isPercent ? (number_format((float)($kpi['previous'] ?? 0), 1) . '%') : ($isDays ? (number_format((float)($kpi['previous'] ?? 0), 0) . ' days') : ($isRatio ? number_format((float)($kpi['previous'] ?? 0), 2) : ('TZS ' . number_format((float)($kpi['previous'] ?? 0), 2))));
                $deltaRaw = (float)($kpi['value'] ?? 0) - (float)($kpi['previous'] ?? 0);
                $deltaFmt = $isPercent ? (number_format($deltaRaw, 1) . '%') : ($isDays ? (number_format($deltaRaw, 0) . ' days') : ($isRatio ? number_format($deltaRaw, 2) : ('TZS ' . number_format($deltaRaw, 2))));
                $pct = number_format((float)($kpi['change_percent'] ?? 0), 1);
                
                $text = $kpi['label'] . " {$verb} by {$pct}% ({$deltaFmt}), from {$prevFmt} to {$currFmt}.";
            } else {
                $text = $kpi['label'] . " is {$currFmt} for this period.";
            }
            
            $section->addText('• ' . $text, $this->getJustifiedStyle(['size' => 11]));
        }
    }
    
    private function addBalanceSheetSnapshot($section, $data)
    {
        $section->addTitle('Balance Sheet Snapshot (As at ' . $data['endDate'] . ')', 2);
        
        $bs = $data['balanceSheet'];
        $prevBs = $data['prevBalanceSheet'];
        
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
        $table->addRow();
        $table->addCell(4000)->addText('Item', ['bold' => true]);
        $table->addCell(3000)->addText('Current', ['bold' => true, 'alignment' => 'right']);
        $table->addCell(3000)->addText('Prev (' . $data['prevEndDate'] . ')', ['bold' => true, 'alignment' => 'right']);
        
        $items = [
            ['Assets', $bs['assets'] ?? 0, $prevBs['assets'] ?? 0],
            ['Liabilities', $bs['liabilities'] ?? 0, $prevBs['liabilities'] ?? 0],
            ['Equity', $bs['equity'] ?? 0, $prevBs['equity'] ?? 0],
            ['Year-to-Date Net Profit', $bs['net_profit'] ?? 0, $prevBs['net_profit'] ?? 0],
        ];
        
        foreach ($items as $item) {
            $table->addRow();
            $table->addCell(4000)->addText($item[0]);
            $table->addCell(3000)->addText('TZS ' . number_format($item[1], 2), ['alignment' => 'right']);
            $table->addCell(3000)->addText('TZS ' . number_format($item[2], 2), ['alignment' => 'right']);
        }
        
        $rhs = ($bs['liabilities'] ?? 0) + ($bs['equity_including_profit'] ?? ($bs['equity'] ?? 0) + ($bs['net_profit'] ?? 0));
        $rhsPrev = ($prevBs['liabilities'] ?? 0) + ($prevBs['equity_including_profit'] ?? ($prevBs['equity'] ?? 0) + ($prevBs['net_profit'] ?? 0));
        
        $table->addRow();
        $table->addCell(4000)->addText('Total Liabilities + Equity', ['bold' => true]);
        $table->addCell(3000)->addText('TZS ' . number_format($rhs, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(3000)->addText('TZS ' . number_format($rhsPrev, 2), ['bold' => true, 'alignment' => 'right']);
        
        $table->addRow();
        $table->addCell(4000)->addText('Total Assets', ['bold' => true]);
        $table->addCell(3000)->addText('TZS ' . number_format($bs['assets'] ?? 0, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(3000)->addText('TZS ' . number_format($prevBs['assets'] ?? 0, 2), ['bold' => true, 'alignment' => 'right']);
        
        $section->addText('The balance sheet shows the resources controlled (Assets) and the claims against them (Liabilities and Equity) as at the end of the period. Differences versus prior periods typically arise from operating results, financing activities, and working capital movements.', $this->getJustifiedStyle(['size' => 11]));
    }
    
    private function addDetailedBalanceSheet($section, $data)
    {
        if (empty($data['balanceSheetDetailed'])) {
            return;
        }
        
        $section->addTitle('Detailed Balance Sheet', 3);
        
        $bsDet = $data['balanceSheetDetailed'];
        $prevBsDet = $data['prevBalanceSheetDetailed'] ?? [];
        
        // Helper function to find previous amount by account ID
        $findPrev = function($category, $mainGroup, $fsli, $accountId) use ($prevBsDet) {
            if (!isset($prevBsDet[$category]['main_groups'][$mainGroup]['fslis'][$fsli]['accounts'])) return 0;
            foreach ($prevBsDet[$category]['main_groups'][$mainGroup]['fslis'][$fsli]['accounts'] as $prevAcc) {
                if ($prevAcc['account_id'] == $accountId) {
                    return (float)$prevAcc['amount'];
                }
            }
            return 0;
        };
        
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
        $table->addRow();
        $table->addCell(4000)->addText('Account', ['bold' => true]);
        $table->addCell(3000)->addText('Current', ['bold' => true, 'alignment' => 'right']);
        $table->addCell(3000)->addText('Previous', ['bold' => true, 'alignment' => 'right']);
        
        $assetsTotal = 0;
        $assetsTotalPrev = 0;
        $liabTotal = 0;
        $liabTotalPrev = 0;
        $equityTotal = 0;
        $equityTotalPrev = 0;
        
        // Process each category (Assets, Liabilities, Equity)
        foreach (['Assets', 'Liabilities', 'Equity'] as $category) {
            $categoryLower = strtolower($category);
            
            $table->addRow();
            $cell = $table->addCell(10000, ['gridSpan' => 3]);
            $cell->addText($category, ['bold' => true]);
            
            if (isset($bsDet[$category]['main_groups'])) {
                foreach ($bsDet[$category]['main_groups'] as $mainGroupName => $mainGroup) {
                    $table->addRow();
                    $cell = $table->addCell(10000, ['gridSpan' => 3]);
                    $cell->addText($mainGroupName, ['bold' => true, 'size' => 11]);
                    
                    if (isset($mainGroup['fslis'])) {
                        foreach ($mainGroup['fslis'] as $fsliName => $fsli) {
                            $table->addRow();
                            $cell = $table->addCell(10000, ['gridSpan' => 3]);
                            $cell->addText($fsliName, ['italic' => true]);
                            
                            if (isset($fsli['accounts'])) {
                                foreach ($fsli['accounts'] as $acc) {
                                    $amount = (float)$acc['amount'];
                                    $prevAmt = $findPrev($category, $mainGroupName, $fsliName, $acc['account_id']);
                                    
                                    if ($categoryLower === 'assets') {
                                        $assetsTotal += $amount;
                                        $assetsTotalPrev += $prevAmt;
                                    } elseif ($categoryLower === 'liabilities') {
                                        $liabTotal += $amount;
                                        $liabTotalPrev += $prevAmt;
                                    } elseif ($categoryLower === 'equity') {
                                        $equityTotal += $amount;
                                        $equityTotalPrev += $prevAmt;
                                    }
                                    
                                    $accountLabel = ($acc['account_code'] ?? '') ? ($acc['account_code'] . ' - ' . $acc['account_name']) : $acc['account_name'];
                                    
                                    $table->addRow();
                                    $table->addCell(4000)->addText('    ' . $accountLabel);
                                    $table->addCell(3000)->addText('TZS ' . number_format($amount, 2), ['alignment' => 'right']);
                                    $table->addCell(3000)->addText('TZS ' . number_format($prevAmt, 2), ['alignment' => 'right']);
                                }
                            }
                            
                            if (isset($fsli['total'])) {
                                $table->addRow();
                                $table->addCell(4000)->addText('  Total ' . $fsliName, ['italic' => true]);
                                $table->addCell(3000)->addText('TZS ' . number_format($fsli['total'], 2), ['italic' => true, 'alignment' => 'right']);
                                $table->addCell(3000)->addText('TZS ' . number_format($prevBsDet[$category]['main_groups'][$mainGroupName]['fslis'][$fsliName]['total'] ?? 0, 2), ['italic' => true, 'alignment' => 'right']);
                            }
                        }
                    }
                    
                    if (isset($mainGroup['total'])) {
                        $table->addRow();
                        $table->addCell(4000)->addText('Total ' . $mainGroupName, ['bold' => true]);
                        $table->addCell(3000)->addText('TZS ' . number_format($mainGroup['total'], 2), ['bold' => true, 'alignment' => 'right']);
                        $table->addCell(3000)->addText('TZS ' . number_format($prevBsDet[$category]['main_groups'][$mainGroupName]['total'] ?? 0, 2), ['bold' => true, 'alignment' => 'right']);
                    }
                }
            }
        }
        
        $table->addRow();
        $table->addCell(4000)->addText('Total Assets', ['bold' => true]);
        $table->addCell(3000)->addText('TZS ' . number_format($assetsTotal, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(3000)->addText('TZS ' . number_format($assetsTotalPrev, 2), ['bold' => true, 'alignment' => 'right']);
        
        $np = (float)($data['balanceSheet']['net_profit'] ?? 0);
        $npPrev = (float)($data['prevBalanceSheet']['net_profit'] ?? 0);
        
        $table->addRow();
        $table->addCell(4000)->addText('Year-to-Date Net Profit');
        $table->addCell(3000)->addText('TZS ' . number_format($np, 2), ['alignment' => 'right']);
        $table->addCell(3000)->addText('TZS ' . number_format($npPrev, 2), ['alignment' => 'right']);
        
        $rhsTotal = $liabTotal + $equityTotal + $np;
        $rhsTotalPrev = $liabTotalPrev + $equityTotalPrev + $npPrev;
        
        $table->addRow();
        $table->addCell(4000)->addText('Total Liabilities + Equity', ['bold' => true]);
        $table->addCell(3000)->addText('TZS ' . number_format($rhsTotal, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(3000)->addText('TZS ' . number_format($rhsTotalPrev, 2), ['bold' => true, 'alignment' => 'right']);
    }
    
    private function addProfitLossSummary($section, $data)
    {
        $section->addTitle('Profit & Loss (' . $data['startDate'] . ' to ' . $data['endDate'] . ')', 2);
        
        $is = $data['incomeStatement'];
        $prevIs = $data['prevIncomeStatement'];
        
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
        $table->addRow();
        $table->addCell(4000)->addText('Item', ['bold' => true]);
        $table->addCell(3000)->addText('Current', ['bold' => true, 'alignment' => 'right']);
        $table->addCell(3000)->addText('Prev (' . $data['prevStartDate'] . ' to ' . $data['prevEndDate'] . ')', ['bold' => true, 'alignment' => 'right']);
        
        $items = [
            ['Revenue', $is['revenue'] ?? 0, $prevIs['revenue'] ?? 0],
            ['Cost of Goods Sold', $is['cogs'] ?? 0, $prevIs['cogs'] ?? 0],
            ['Gross Profit', $is['gross_profit'] ?? 0, $prevIs['gross_profit'] ?? 0],
            ['Operating Expenses', $is['expenses'] ?? 0, $prevIs['expenses'] ?? 0],
            ['Net Profit', $is['net_profit'] ?? 0, $prevIs['net_profit'] ?? 0],
        ];
        
        foreach ($items as $item) {
            $table->addRow();
            $table->addCell(4000)->addText($item[0], $item[0] === 'Gross Profit' || $item[0] === 'Net Profit' ? ['bold' => true] : []);
            $table->addCell(3000)->addText('TZS ' . number_format($item[1], 2), ['alignment' => 'right', 'bold' => ($item[0] === 'Gross Profit' || $item[0] === 'Net Profit')]);
            $table->addCell(3000)->addText('TZS ' . number_format($item[2], 2), ['alignment' => 'right', 'bold' => ($item[0] === 'Gross Profit' || $item[0] === 'Net Profit')]);
        }
        
        $section->addText('The profit and loss statement explains performance over the period. Revenue reflects invoiced income; Cost of Goods Sold tracks the direct costs attributed to sales (based on COGS-designated accounts); Operating Expenses include administrative and selling costs. Net Profit summarizes overall profitability after operating costs.', $this->getJustifiedStyle(['size' => 11]));
    }
    
    private function addDetailedProfitLoss($section, $data)
    {
        if (empty($data['incomeStatementDetailed'])) {
            return;
        }
        
        $section->addTitle('Detailed Profit & Loss', 3);
        
        $isDet = $data['incomeStatementDetailed'];
        $prevIsDet = $data['prevIncomeStatementDetailed'] ?? [];
        
        // Helper to get merged accounts
        $getMerged = function($category, $currentData, $prevData) {
            $merged = [];
            $currentGroups = $currentData[$category] ?? [];
            $prevGroups = $prevData[$category] ?? [];
            $allGroups = array_unique(array_merge(array_keys($currentGroups), array_keys($prevGroups)));
            
            foreach ($allGroups as $group) {
                $currentAccounts = $currentGroups[$group] ?? [];
                $prevAccounts = $prevGroups[$group] ?? [];
                
                $currentMap = [];
                foreach ($currentAccounts as $acc) {
                    $currentMap[$acc['account']] = (float)$acc['amount'];
                }
                
                $prevMap = [];
                foreach ($prevAccounts as $acc) {
                    $prevMap[$acc['account']] = (float)$acc['amount'];
                }
                
                $allAccountNames = array_unique(array_merge(array_keys($currentMap), array_keys($prevMap)));
                
                $mergedAccounts = [];
                foreach ($allAccountNames as $accountName) {
                    $mergedAccounts[] = [
                        'account' => $accountName,
                        'current' => $currentMap[$accountName] ?? 0,
                        'previous' => $prevMap[$accountName] ?? 0,
                    ];
                }
                
                if (!empty($mergedAccounts)) {
                    $merged[$group] = $mergedAccounts;
                }
            }
            
            return $merged;
        };
        
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
        $table->addRow();
        $table->addCell(4000)->addText('Account', ['bold' => true]);
        $table->addCell(2000)->addText('Current Period', ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('Previous Period', ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('Change', ['bold' => true, 'alignment' => 'right']);
        
        $revTotal = 0;
        $revTotalPrev = 0;
        $cogsTotal = 0;
        $cogsTotalPrev = 0;
        $expTotal = 0;
        $expTotalPrev = 0;
        
        // Revenue
        $table->addRow();
        $cell = $table->addCell(10000, ['gridSpan' => 4]);
        $cell->addText('Revenue', ['bold' => true]);
        
        $revMerged = $getMerged('revenue', $isDet, $prevIsDet);
        foreach ($revMerged as $group => $accounts) {
            $table->addRow();
            $cell = $table->addCell(10000, ['gridSpan' => 4]);
            $cell->addText($group, ['italic' => true]);
            
            foreach ($accounts as $acc) {
                if ($acc['current'] == 0 && $acc['previous'] == 0) continue;
                
                $change = $acc['current'] - $acc['previous'];
                $revTotal += $acc['current'];
                $revTotalPrev += $acc['previous'];
                
                $table->addRow();
                $table->addCell(4000)->addText('  ' . $acc['account']);
                $table->addCell(2000)->addText('TZS ' . number_format($acc['current'], 2), ['alignment' => 'right']);
                $table->addCell(2000)->addText('TZS ' . number_format($acc['previous'], 2), ['alignment' => 'right']);
                $table->addCell(2000)->addText('TZS ' . number_format($change, 2), ['alignment' => 'right']);
            }
        }
        
        $table->addRow();
        $table->addCell(4000)->addText('Total Revenue', ['bold' => true]);
        $table->addCell(2000)->addText('TZS ' . number_format($revTotal, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('TZS ' . number_format($revTotalPrev, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('TZS ' . number_format($revTotal - $revTotalPrev, 2), ['bold' => true, 'alignment' => 'right']);
        
        // COGS
        $table->addRow();
        $cell = $table->addCell(10000, ['gridSpan' => 4]);
        $cell->addText('Cost of Goods Sold', ['bold' => true]);
        
        $cogsMerged = $getMerged('cogs', $isDet, $prevIsDet);
        foreach ($cogsMerged as $group => $accounts) {
            $table->addRow();
            $cell = $table->addCell(10000, ['gridSpan' => 4]);
            $cell->addText($group, ['italic' => true]);
            
            foreach ($accounts as $acc) {
                if ($acc['current'] == 0 && $acc['previous'] == 0) continue;
                
                $change = abs($acc['current']) - abs($acc['previous']);
                $cogsTotal += abs($acc['current']);
                $cogsTotalPrev += abs($acc['previous']);
                
                $table->addRow();
                $table->addCell(4000)->addText('  ' . $acc['account']);
                $table->addCell(2000)->addText('TZS ' . number_format(abs($acc['current']), 2), ['alignment' => 'right']);
                $table->addCell(2000)->addText('TZS ' . number_format(abs($acc['previous']), 2), ['alignment' => 'right']);
                $table->addCell(2000)->addText('TZS ' . number_format($change, 2), ['alignment' => 'right']);
            }
        }
        
        $table->addRow();
        $table->addCell(4000)->addText('Total Cost of Goods Sold', ['bold' => true]);
        $table->addCell(2000)->addText('TZS ' . number_format($cogsTotal, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('TZS ' . number_format($cogsTotalPrev, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('TZS ' . number_format($cogsTotal - $cogsTotalPrev, 2), ['bold' => true, 'alignment' => 'right']);
        
        // Operating Expenses
        $table->addRow();
        $cell = $table->addCell(10000, ['gridSpan' => 4]);
        $cell->addText('Operating Expenses', ['bold' => true]);
        
        $expMerged = $getMerged('expenses', $isDet, $prevIsDet);
        foreach ($expMerged as $group => $accounts) {
            $table->addRow();
            $cell = $table->addCell(10000, ['gridSpan' => 4]);
            $cell->addText($group, ['italic' => true]);
            
            foreach ($accounts as $acc) {
                if ($acc['current'] == 0 && $acc['previous'] == 0) continue;
                
                $change = abs($acc['current']) - abs($acc['previous']);
                $expTotal += abs($acc['current']);
                $expTotalPrev += abs($acc['previous']);
                
                $table->addRow();
                $table->addCell(4000)->addText('  ' . $acc['account']);
                $table->addCell(2000)->addText('TZS ' . number_format(abs($acc['current']), 2), ['alignment' => 'right']);
                $table->addCell(2000)->addText('TZS ' . number_format(abs($acc['previous']), 2), ['alignment' => 'right']);
                $table->addCell(2000)->addText('TZS ' . number_format($change, 2), ['alignment' => 'right']);
            }
        }
        
        $table->addRow();
        $table->addCell(4000)->addText('Total Operating Expenses', ['bold' => true]);
        $table->addCell(2000)->addText('TZS ' . number_format($expTotal, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('TZS ' . number_format($expTotalPrev, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('TZS ' . number_format($expTotal - $expTotalPrev, 2), ['bold' => true, 'alignment' => 'right']);
        
        $npDtl = $revTotal - $cogsTotal - $expTotal;
        $npDtlPrev = $revTotalPrev - $cogsTotalPrev - $expTotalPrev;
        
        $table->addRow();
        $table->addCell(4000)->addText('Net Profit/Loss', ['bold' => true]);
        $table->addCell(2000)->addText('TZS ' . number_format($npDtl, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('TZS ' . number_format($npDtlPrev, 2), ['bold' => true, 'alignment' => 'right']);
        $table->addCell(2000)->addText('TZS ' . number_format($npDtl - $npDtlPrev, 2), ['bold' => true, 'alignment' => 'right']);
    }
    
    private function addMethodology($section, $data)
    {
        $section->addTitle('Methodology', 2);
        $text = "Metrics are computed from the General Ledger (GL) and operational ledgers within the reporting window ({$data['startDate']} to {$data['endDate']}), " .
                "contrasted with an immediately preceding period of equal length. Revenue and expense figures are derived from GL class mappings; " .
                "cash flow from receipts and payments; receivables and payables from outstanding customer invoices and supplier bills where available; " .
                "inventory balances from asset-class GL accounts containing \"inventory\". Ratios are based on period activity and balances as described " .
                "in the Definitions section. All values are branch-scoped if a branch is selected, otherwise consolidated across permitted branches.";
        $section->addText($text, $this->getJustifiedStyle(['size' => 11, 'spaceAfter' => 120]));
    }
    
    private function addDataSources($section, $data)
    {
        $section->addTitle('Data Sources & Scope', 2);
        $sources = [
            'General Ledger transactions (class: Revenue, Expenses, Assets for inventory, and COGS-related accounts)',
            'Sales invoices and customer receivables (balance due)',
            'Supplier bills/payables (balance due), if maintained in the system',
            'Cash receipts and payments journals for operating cash flows',
            'Computed KPIs reflect configured branches and the selected period'
        ];
        foreach ($sources as $source) {
            $section->addText('• ' . $source, $this->getJustifiedStyle(['size' => 11]));
        }
    }
    
    private function addDefinitions($section, $data)
    {
        $section->addTitle('Definitions & Formulas', 2);
        
        // Core Financial
        $section->addTitle('Core Financial', 3);
        $coreDefs = [
            'Revenue: GL credits minus debits for income/revenue classes.',
            'Expenses: GL debits minus credits for expense classes.',
            'Cash Flow: Receipts minus payments for the period.',
            'Net Profit: Revenue minus Expenses.',
            'Gross Profit Margin: (Revenue − COGS) ÷ Revenue × 100.',
            'Net Profit Margin: Net Profit ÷ Revenue × 100.',
            'Expense Ratio: Expenses ÷ Revenue × 100.',
            'DSO (Debtors Collection Period): Receivables ÷ (Revenue per day).',
            'DPO (Creditors Payment Period): Accounts Payable ÷ (COGS per day).',
            'DIO (Inventory Holding Period): Inventory Balance ÷ (COGS per day).'
        ];
        foreach ($coreDefs as $def) {
            $section->addText('• ' . $def, $this->getJustifiedStyle(['size' => 11]));
        }
        
        // Liquidity & Solvency
        $section->addTitle('Liquidity & Solvency', 3);
        $liquidityDefs = [
            'Current Ratio: Current Assets ÷ Current Liabilities.',
            'Quick Ratio (Acid Test): (Current Assets − Inventory) ÷ Current Liabilities.',
            'Cash Ratio: Cash ÷ Current Liabilities.',
            'Debt-to-Equity Ratio: Total Liabilities ÷ Shareholders\' Equity.'
        ];
        foreach ($liquidityDefs as $def) {
            $section->addText('• ' . $def, $this->getJustifiedStyle(['size' => 11]));
        }
        
        // Efficiency / Activity
        $section->addTitle('Efficiency / Activity', 3);
        $efficiencyDefs = [
            'Asset Turnover Ratio: Revenue ÷ Total Assets.',
            'Inventory Turnover Ratio: Cost of Goods Sold ÷ Average Inventory.',
            'Receivables Turnover Ratio: Revenue ÷ Average Receivables.',
            'Payables Turnover Ratio: Cost of Goods Sold ÷ Average Payables.'
        ];
        foreach ($efficiencyDefs as $def) {
            $section->addText('• ' . $def, $this->getJustifiedStyle(['size' => 11]));
        }
        
        // Profitability & Return
        $section->addTitle('Profitability & Return', 3);
        $profitabilityDefs = [
            'Return on Assets (ROA): Net Profit ÷ Total Assets × 100.',
            'Return on Equity (ROE): Net Profit ÷ Equity × 100.',
            'Operating Profit Margin: (Operating Profit ÷ Revenue) × 100.',
            'EBITDA Margin: (EBITDA ÷ Revenue) × 100.'
        ];
        foreach ($profitabilityDefs as $def) {
            $section->addText('• ' . $def, $this->getJustifiedStyle(['size' => 11]));
        }
        
        // Growth
        $section->addTitle('Growth', 3);
        $growthDefs = [
            'Revenue Growth Rate: ((Current Revenue − Previous Revenue) ÷ Previous Revenue) × 100.',
            'Net Profit Growth Rate: ((Current Net Profit − Previous Net Profit) ÷ Previous Net Profit) × 100.',
            'Expense Growth Rate: ((Current Expenses − Previous Expenses) ÷ Previous Expenses) × 100.'
        ];
        foreach ($growthDefs as $def) {
            $section->addText('• ' . $def, $this->getJustifiedStyle(['size' => 11]));
        }
        
        // Cash Flow Health
        $section->addTitle('Cash Flow Health', 3);
        $cashFlowDefs = [
            'Operating Cash Flow Ratio: Operating Cash Flow ÷ Current Liabilities.',
            'Free Cash Flow (FCF): Operating Cash Flow − Capital Expenditure.',
            'Cash Conversion Cycle: Inventory Days + Receivables Days − Payables Days.'
        ];
        foreach ($cashFlowDefs as $def) {
            $section->addText('• ' . $def, $this->getJustifiedStyle(['size' => 11]));
        }
    }
    
    private function addManagementCommentary($section, $data)
    {
        $section->addTitle('Management Commentary', 2);
        
        if (empty($data['kpis'])) {
            return;
        }
        
        foreach ($data['kpis'] as $kpi) {
            $commentary = $this->generateKpiCommentary($kpi);
            
            // Parse commentary (uses \n for line breaks)
            $lines = explode("\n", $commentary);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if (strpos($line, 'Recommendation:') !== false) {
                    $section->addText($line, $this->getJustifiedStyle(['size' => 11, 'italic' => true]));
                } elseif (strpos($line, $kpi['label']) === 0) {
                    // First line with label is bold
                    $section->addText($line, $this->getJustifiedStyle(['size' => 11, 'bold' => true]));
                } else {
                    $section->addText($line, $this->getJustifiedStyle(['size' => 11]));
                }
            }
            $section->addTextBreak(1);
        }
    }
    
    private function generateKpiCommentary($k)
    {
        $key = $k['key'] ?? '';
        $label = $k['label'];
        $curr = (float)($k['value'] ?? 0);
        $prev = $k['previous'] !== null ? (float)$k['previous'] : null;
        $changePct = (float)($k['change_percent'] ?? 0);
        $trend = $k['trend'] ?? 'flat';
        $hasPrev = $prev !== null;
        
        $isPercent = in_array($key, ['net_profit_margin','expense_ratio','gross_profit_margin','roa','roe','operating_profit_margin','ebitda_margin','revenue_growth_rate','net_profit_growth_rate','expense_growth_rate']);
        $isDays = in_array($key, ['dso','dpo','dio','cash_conversion_cycle']);
        $isRatio = in_array($key, ['current_ratio','quick_ratio','cash_ratio','debt_to_equity','asset_turnover','inventory_turnover','receivables_turnover','payables_turnover','operating_cash_flow_ratio']);
        
        $currFmt = $isPercent ? (number_format($curr, 1).'%') : ($isDays ? (number_format($curr, 0).' days') : ($isRatio ? number_format($curr, 2) : ('TZS '.number_format($curr, 2))));
        $prevFmt = $hasPrev ? ($isPercent ? (number_format($prev, 1).'%') : ($isDays ? (number_format($prev, 0).' days') : ($isRatio ? number_format($prev, 2) : ('TZS '.number_format($prev, 2))))) : 'N/A';
        
        $changeAbs = number_format(abs($changePct), 2);
        $isIncrease = $trend === 'up';
        $isDecrease = $trend === 'down';
        
        // For expense-related KPIs, invert the interpretation
        $isExpenseKpi = in_array($key, ['expenses', 'expense_ratio', 'expense_growth_rate']);
        if ($isExpenseKpi) {
            $isIncrease = $trend === 'down';
            $isDecrease = $trend === 'up';
        }
        
        $commentary = '';
        
        switch($key) {
            case 'revenue':
                if ($hasPrev) {
                    $verb = $isIncrease ? 'increased' : ($isDecrease ? 'decreased' : 'remained stable');
                    $commentary = "{$label}\n{$label} {$verb} by {$changeAbs}%, from {$prevFmt} to {$currFmt}";
                    if ($isDecrease) {
                        $commentary .= ", mainly reflecting reduced customer orders and possibly lower pricing during the period. The decline suggests weaker demand or seasonal sales fluctuation.\nRecommendation: Management should assess key client accounts and explore new marketing initiatives or pricing adjustments to restore sales growth.";
                    } elseif ($isIncrease) {
                        $commentary .= ", reflecting strong sales performance and possibly improved market conditions or pricing strategies.\nRecommendation: Sustain this growth momentum by maintaining customer relationships and exploring opportunities to expand market share.";
                    } else {
                        $commentary .= ", indicating stable revenue generation.\nRecommendation: Focus on growth initiatives while maintaining current revenue levels.";
                    }
                } else {
                    $commentary = "{$label}\n{$label} stands at {$currFmt} for this period.\nRecommendation: Establish baseline metrics and monitor trends going forward.";
                }
                break;
                
            case 'expenses':
                if ($hasPrev) {
                    $verb = $isDecrease ? 'reduced' : ($isIncrease ? 'increased' : 'remained stable');
                    $commentary = "{$label}\nTotal {$label} {$verb}";
                    if ($isDecrease) {
                        $commentary .= " by {$changeAbs}%, from {$prevFmt} to {$currFmt}, reflecting effective cost containment measures and operational streamlining.\nRecommendation: While this decline positively impacts profitability, management should ensure essential operational activities and service delivery are not compromised.";
                    } elseif ($isIncrease) {
                        $commentary .= " by {$changeAbs}%, from {$prevFmt} to {$currFmt}, indicating higher operational costs or expanded business activities.\nRecommendation: Review expense categories to identify areas for optimization and ensure cost increases are justified by corresponding revenue growth.";
                    } else {
                        $commentary .= " from {$prevFmt} to {$currFmt}, indicating stable cost management.\nRecommendation: Continue monitoring expenses while seeking opportunities for efficiency improvements.";
                    }
                } else {
                    $commentary = "{$label}\nTotal {$label} are {$currFmt} for this period.\nRecommendation: Establish cost benchmarks and monitor spending patterns.";
                }
                break;
                
            case 'net_profit':
                if ($hasPrev) {
                    $wasLoss = $prev < 0;
                    $isLoss = $curr < 0;
                    $commentary = "{$label}\n";
                    if ($wasLoss && !$isLoss) {
                        $commentary .= "Net profit improved from a loss of {$prevFmt} to a profit of {$currFmt}, a remarkable {$changeAbs}% turnaround. This improvement is largely attributed to lower expenses and tighter financial discipline.\nRecommendation: The business should sustain cost efficiency measures while focusing on rebuilding top-line growth to maintain healthy profitability margins.";
                    } elseif ($isIncrease || $isDecrease) {
                        $verb = $isIncrease ? 'improved' : 'declined';
                        $commentary .= "Net profit {$verb} by {$changeAbs}%, from {$prevFmt} to {$currFmt}";
                        if ($isIncrease) {
                            $commentary .= ", reflecting stronger operational performance and improved cost management.\nRecommendation: Maintain profitability by balancing revenue growth with cost efficiency.";
                        } else {
                            $commentary .= ", indicating challenges in maintaining profit margins.\nRecommendation: Review revenue streams and cost structures to restore profitability.";
                        }
                    } else {
                        $commentary .= "Net profit changed from {$prevFmt} to {$currFmt} ({$changeAbs}% change).\nRecommendation: Continue monitoring profitability trends and adjust strategies as needed.";
                    }
                } else {
                    $commentary = "{$label}\nNet profit is {$currFmt} for this period.\nRecommendation: Establish profitability targets and track performance against goals.";
                }
                break;
                
            case 'cash_flow':
                if ($hasPrev) {
                    $commentary = "{$label}\n";
                    if ($isDecrease) {
                        $commentary .= "Operating cash flow deteriorated from {$prevFmt} to {$currFmt}, indicating greater cash outflows. The shortfall may have been driven by delayed customer payments or higher inventory purchases.\nRecommendation: Management should review credit collection practices and optimize working capital management to enhance liquidity.";
                    } elseif ($isIncrease) {
                        $commentary .= "Operating cash flow improved from {$prevFmt} to {$currFmt}, reflecting better cash management and timely collections.\nRecommendation: Continue maintaining strong cash flow discipline and consider investing excess cash in productive assets.";
                    } else {
                        $commentary .= "Operating cash flow remained stable at {$currFmt}.\nRecommendation: Monitor cash flow patterns and ensure adequate liquidity for operations.";
                    }
                } else {
                    $commentary = "{$label}\nOperating cash flow is {$currFmt} for this period.\nRecommendation: Establish cash flow targets and monitor liquidity regularly.";
                }
                break;
                
            case 'net_profit_margin':
                if ($hasPrev) {
                    $commentary = "{$label}\nNet Profit Margin ";
                    if ($isIncrease) {
                        $commentary .= "rose significantly from {$prevFmt} to {$currFmt}, reflecting a strong improvement in profitability. The business is now earning more per shilling of revenue generated.\nRecommendation: Sustain this efficiency by maintaining cost controls and focusing on profitable products or services.";
                    } elseif ($isDecrease) {
                        $commentary .= "declined from {$prevFmt} to {$currFmt}, indicating reduced profitability relative to revenue.\nRecommendation: Review pricing strategies and cost structures to restore margin performance.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring margins and seek opportunities for improvement.";
                    }
                } else {
                    $commentary = "{$label}\nNet Profit Margin is {$currFmt} for this period.\nRecommendation: Establish margin targets and benchmark against industry standards.";
                }
                break;
                
            case 'expense_ratio':
                if ($hasPrev) {
                    $commentary = "{$label}\n";
                    if ($isDecrease) {
                        $commentary .= "The expense ratio improved from {$prevFmt} to {$currFmt}, indicating that expenses now represent a smaller portion of revenue. This improvement demonstrates better cost discipline and resource utilization.\nRecommendation: Continue monitoring expenditure levels to ensure sustained cost efficiency without underfunding critical functions.";
                    } elseif ($isIncrease) {
                        $commentary .= "The expense ratio increased from {$prevFmt} to {$currFmt}, suggesting that expenses are consuming a larger portion of revenue.\nRecommendation: Review expense categories and identify opportunities to optimize spending while maintaining operational effectiveness.";
                    } else {
                        $commentary .= "The expense ratio remained stable at {$currFmt}.\nRecommendation: Continue monitoring the expense ratio and seek efficiency improvements.";
                    }
                } else {
                    $commentary = "{$label}\nThe expense ratio is {$currFmt} for this period.\nRecommendation: Establish expense ratio targets and monitor trends.";
                }
                break;
                
            case 'receivables':
                if ($hasPrev && $prev != 0) {
                    $commentary = "{$label}\nOutstanding receivables ";
                    if ($isIncrease) {
                        $commentary .= "increased from {$prevFmt} to {$currFmt}, suggesting significant credit sales and possible extension of payment terms.\nRecommendation: Introduce robust credit control policies and ensure timely follow-up to prevent overdue debts from escalating.";
                    } elseif ($isDecrease) {
                        $commentary .= "decreased from {$prevFmt} to {$currFmt}, reflecting improved collection efficiency.\nRecommendation: Maintain effective collection practices and continue monitoring receivables aging.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring receivables and ensure timely collections.";
                    }
                } else {
                    $commentary = "{$label}\nOutstanding receivables stand at {$currFmt}";
                    if ($curr > 0) {
                        $commentary .= ", suggesting credit sales and possible extension of payment terms.\nRecommendation: Introduce robust credit control policies and ensure timely follow-up to prevent overdue debts from escalating.";
                    } else {
                        $commentary .= ".\nRecommendation: Monitor receivables as business grows and credit terms are extended.";
                    }
                }
                break;
                
            case 'dso':
                if ($hasPrev) {
                    $commentary = "{$label}\nThe collection period ";
                    if ($isIncrease) {
                        $commentary .= "increased to {$currFmt}, implying that it takes longer to convert sales into cash. This may indicate relaxed credit terms or slower customer payments.\nRecommendation: Aim to reduce the collection period to below 45 days by strengthening customer credit assessments and enforcing payment timelines.";
                    } elseif ($isDecrease) {
                        $commentary .= "improved to {$currFmt}, indicating faster collection of receivables.\nRecommendation: Maintain efficient collection practices and continue monitoring payment trends.";
                    } else {
                        $commentary .= "averages {$currFmt} days.";
                        if ($curr > 45) {
                            $commentary .= " While this reflects moderate credit terms, the cash flow strain is evident.\nRecommendation: Aim to reduce the collection period to below 45 days by strengthening customer credit assessments.";
                        } else {
                            $commentary .= "\nRecommendation: Continue maintaining efficient collection practices.";
                        }
                    }
                } else {
                    $commentary = "{$label}\nThe collection period averages {$currFmt}";
                    if ($curr > 45) {
                        $commentary .= ", implying that it takes over a month to convert sales into cash. While this reflects moderate credit terms, the cash flow strain is evident.\nRecommendation: Aim to reduce the collection period to below 45 days by strengthening customer credit assessments and enforcing payment timelines.";
                    } else {
                        $commentary .= " days.\nRecommendation: Continue monitoring collection efficiency.";
                    }
                }
                break;
                
            case 'gross_profit_margin':
                if ($hasPrev) {
                    $commentary = "{$label}\n";
                    if ($isIncrease) {
                        $commentary .= "Gross profit margin improved from {$prevFmt} to {$currFmt}, showing significant progress in controlling production or procurement costs. This might also reflect better supplier negotiations or product mix optimization.\nRecommendation: Management should ensure that the improvement is sustainable and not driven by temporary cost advantages.";
                    } elseif ($isDecrease) {
                        $commentary .= "Gross profit margin declined from {$prevFmt} to {$currFmt}, indicating higher costs of goods sold relative to revenue.\nRecommendation: Review supplier relationships and product pricing to restore margin performance.";
                    } else {
                        $commentary .= "Gross profit margin remained stable at {$currFmt}.\nRecommendation: Continue monitoring margin trends and seek opportunities for improvement.";
                    }
                } else {
                    $commentary = "{$label}\nGross profit margin is {$currFmt} for this period.\nRecommendation: Establish margin targets and monitor cost efficiency.";
                }
                break;
                
            case 'dio':
                if ($hasPrev) {
                    $commentary = "{$label}\nInventory holding period ";
                    if ($isIncrease) {
                        $commentary .= "increased to {$currFmt}, suggesting overstocking or very slow-moving inventory. This poses a risk of obsolescence and ties up cash unnecessarily.\nRecommendation: Conduct a stock aging analysis and implement an inventory reduction strategy to improve turnover and liquidity.";
                    } elseif ($isDecrease) {
                        $commentary .= "improved to {$currFmt}, indicating faster inventory turnover and better stock management.\nRecommendation: Maintain efficient inventory levels while ensuring adequate stock availability.";
                    } else {
                        $commentary .= "averages {$currFmt} days.";
                        if ($curr > 90) {
                            $commentary .= " This suggests slow-moving inventory that may require attention.\nRecommendation: Review inventory levels and consider strategies to improve turnover.";
                        } else {
                            $commentary .= "\nRecommendation: Continue monitoring inventory efficiency.";
                        }
                    }
                } else {
                    $commentary = "{$label}\nInventory holding period is {$currFmt}";
                    if ($curr > 90) {
                        $commentary .= ", suggesting overstocking or very slow-moving inventory. This poses a risk of obsolescence and ties up cash unnecessarily.\nRecommendation: Conduct a stock aging analysis and implement an inventory reduction strategy to improve turnover and liquidity.";
                    } else {
                        $commentary .= " days.\nRecommendation: Monitor inventory levels and turnover efficiency.";
                    }
                }
                break;
                
            case 'dpo':
                if ($hasPrev) {
                    $commentary = "{$label}\nCreditors payment period ";
                    if ($isIncrease) {
                        $commentary .= "increased to {$currFmt}, indicating longer payment terms with suppliers. While this may provide cash flow flexibility, it could strain supplier relationships.\nRecommendation: Balance payment terms to maintain supplier goodwill while optimizing cash flow.";
                    } elseif ($isDecrease) {
                        $commentary .= "decreased to {$currFmt}, suggesting faster payment to suppliers or shorter payment terms.\nRecommendation: Consider negotiating longer payment terms where possible to improve cash flow management, while maintaining supplier relationships.";
                    } else {
                        $commentary .= "remains at {$currFmt}";
                        if ($curr == 0) {
                            $commentary .= ", implying that suppliers are paid immediately upon purchase. While this avoids liabilities, it may limit short-term liquidity flexibility.\nRecommendation: Negotiate longer payment terms with suppliers where possible to improve cash flow management.";
                        } else {
                            $commentary .= " days.\nRecommendation: Continue monitoring payment terms and supplier relationships.";
                        }
                    }
                } else {
                    $commentary = "{$label}\nCreditors payment period remains at {$currFmt}";
                    if ($curr == 0) {
                        $commentary .= ", implying that suppliers are paid immediately upon purchase. While this avoids liabilities, it may limit short-term liquidity flexibility.\nRecommendation: Negotiate longer payment terms with suppliers where possible to improve cash flow management.";
                    } else {
                        $commentary .= " days.\nRecommendation: Monitor payment terms and optimize supplier relationships.";
                    }
                }
                break;
                
            case 'current_ratio':
                if ($hasPrev) {
                    $commentary = "{$label}\nThe current ratio ";
                    if ($isDecrease) {
                        $commentary .= "declined from {$prevFmt} to {$currFmt}";
                        if ($curr >= 1) {
                            $commentary .= ", indicating slightly reduced liquidity but still showing a strong ability to meet short-term obligations.\nRecommendation: Maintain this level by managing working capital prudently and avoiding excessive cash holdings that could otherwise be invested.";
                        } else {
                            $commentary .= ", indicating potential liquidity concerns. The ratio below 1 suggests current assets may not fully cover current liabilities.\nRecommendation: Improve liquidity by accelerating collections, reducing inventory, or extending payment terms.";
                        }
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger liquidity position.\nRecommendation: While strong liquidity is positive, ensure excess assets are efficiently deployed for growth.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}";
                        if ($curr >= 1) {
                            $commentary .= ", indicating adequate liquidity coverage.\nRecommendation: Continue maintaining prudent working capital management.";
                        } else {
                            $commentary .= ", but below the ideal threshold of 1.0.\nRecommendation: Focus on improving liquidity to ensure ability to meet short-term obligations.";
                        }
                    }
                } else {
                    $commentary = "{$label}\nThe current ratio is {$currFmt}";
                    if ($curr >= 1) {
                        $commentary .= ", indicating a strong ability to meet short-term obligations.\nRecommendation: Maintain this level by managing working capital prudently.";
                    } else {
                        $commentary .= ", which is below the ideal threshold of 1.0. This suggests potential liquidity concerns.\nRecommendation: Improve liquidity by accelerating collections and managing working capital more effectively.";
                    }
                }
                break;
                
            case 'quick_ratio':
                if ($hasPrev) {
                    $commentary = "{$label}\nThe quick ratio ";
                    if ($isDecrease) {
                        $commentary .= "fell from {$prevFmt} to {$currFmt}, reflecting reduced liquid assets relative to current liabilities. This could be linked to slower receivable collections.\nRecommendation: Accelerate collections and monitor short-term asset liquidity to ensure coverage of immediate liabilities.";
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger immediate liquidity position.\nRecommendation: Maintain strong liquidity while ensuring efficient use of liquid assets.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}";
                        if ($curr >= 1) {
                            $commentary .= ", indicating adequate immediate liquidity.\nRecommendation: Continue monitoring quick assets and current liabilities.";
                        } else {
                            $commentary .= ", but below 1.0, suggesting limited immediate liquidity.\nRecommendation: Improve quick assets or reduce current liabilities to strengthen immediate liquidity.";
                        }
                    }
                } else {
                    $commentary = "{$label}\nThe quick ratio is {$currFmt}";
                    if ($curr >= 1) {
                        $commentary .= ", indicating strong immediate liquidity.\nRecommendation: Continue monitoring quick assets and current liabilities.";
                    } else {
                        $commentary .= ", which is below 1.0, suggesting limited immediate liquidity without relying on inventory.\nRecommendation: Improve quick assets or reduce current liabilities to strengthen immediate liquidity.";
                    }
                }
                break;
                
            case 'cash_ratio':
                if ($hasPrev) {
                    $commentary = "{$label}\nCash ratio ";
                    if ($isDecrease) {
                        $commentary .= "declined from {$prevFmt} to {$currFmt}";
                        if ($curr < 0) {
                            $commentary .= ", indicating that cash balances are insufficient to cover short-term obligations.\nRecommendation: Strengthen cash flow planning and prioritize inflows before committing to new cash outlays.";
                        } else {
                            $commentary .= ", indicating reduced cash coverage of current liabilities.\nRecommendation: Improve cash reserves while maintaining efficient cash management.";
                        }
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger cash position.\nRecommendation: While strong cash reserves are positive, ensure excess cash is efficiently deployed.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}";
                        if ($curr < 0) {
                            $commentary .= ". However, negative cash ratio indicates insufficient cash to cover current liabilities.\nRecommendation: Strengthen cash flow planning and prioritize cash inflows.";
                        } else {
                            $commentary .= ".\nRecommendation: Continue monitoring cash reserves and liquidity.";
                        }
                    }
                } else {
                    $commentary = "{$label}\nCash ratio is {$currFmt}";
                    if ($curr < 0) {
                        $commentary .= ", indicating that cash balances are insufficient to cover short-term obligations.\nRecommendation: Strengthen cash flow planning and prioritize inflows before committing to new cash outlays.";
                    } else {
                        $commentary .= ".\nRecommendation: Monitor cash reserves and ensure adequate liquidity.";
                    }
                }
                break;
                
            case 'debt_to_equity':
                if ($hasPrev) {
                    $commentary = "{$label}\nThe ratio ";
                    if ($isIncrease) {
                        $commentary .= "increased from {$prevFmt} to {$currFmt}, showing a rise in leverage. ";
                        if ($curr < 1) {
                            $commentary .= "The business remains conservatively financed, but continued borrowing could affect solvency.\nRecommendation: Any additional financing should be directed towards productive investments that generate positive returns.";
                        } else {
                            $commentary .= "The business now has more debt than equity, indicating higher financial risk.\nRecommendation: Monitor leverage levels and ensure debt is used for value-creating investments.";
                        }
                    } elseif ($isDecrease) {
                        $commentary .= "decreased from {$prevFmt} to {$currFmt}, indicating reduced leverage and stronger equity position.\nRecommendation: Maintain conservative leverage while ensuring adequate capital for growth opportunities.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}";
                        if ($curr < 1) {
                            $commentary .= ", indicating conservative financing.\nRecommendation: Continue monitoring leverage and consider strategic use of debt for growth.";
                        } else {
                            $commentary .= ", but leverage remains high.\nRecommendation: Focus on reducing debt or increasing equity to improve financial stability.";
                        }
                    }
                } else {
                    $commentary = "{$label}\nThe debt-to-equity ratio is {$currFmt}";
                    if ($curr < 1) {
                        $commentary .= ", indicating conservative financing.\nRecommendation: Monitor leverage levels and consider strategic use of debt for growth.";
                    } else {
                        $commentary .= ", indicating higher leverage and financial risk.\nRecommendation: Focus on reducing debt or increasing equity to improve financial stability.";
                    }
                }
                break;
                
            case 'asset_turnover':
                if ($hasPrev) {
                    $commentary = "{$label}\nThe asset turnover ratio ";
                    if ($isDecrease) {
                        $commentary .= "decreased from {$prevFmt} to {$currFmt}, suggesting that assets are generating less revenue per unit of investment.\nRecommendation: Review asset utilization efficiency and consider divesting underused assets to improve capital productivity.";
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating better utilization of assets to generate revenue.\nRecommendation: Maintain efficient asset utilization and continue monitoring asset productivity.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring asset efficiency and seek opportunities for improvement.";
                    }
                } else {
                    $commentary = "{$label}\nThe asset turnover ratio is {$currFmt} for this period.\nRecommendation: Monitor asset utilization and ensure assets are generating adequate returns.";
                }
                break;
                
            case 'inventory_turnover':
                if ($hasPrev) {
                    $commentary = "{$label}\nInventory turnover ";
                    if ($isDecrease) {
                        $commentary .= "dropped from {$prevFmt} to {$currFmt}, confirming slower stock movement consistent with the high inventory holding period.\nRecommendation: Improve inventory planning, enhance demand forecasting, and consider promotional strategies to clear slow-moving goods.";
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating faster inventory movement and better stock management.\nRecommendation: Maintain efficient inventory levels while ensuring adequate stock availability.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring inventory turnover and seek opportunities for improvement.";
                    }
                } else {
                    $commentary = "{$label}\nInventory turnover is {$currFmt} for this period.\nRecommendation: Monitor inventory efficiency and ensure stock is moving at optimal rates.";
                }
                break;
                
            case 'receivables_turnover':
                if ($hasPrev) {
                    $commentary = "{$label}\nReceivables turnover ";
                    if ($isDecrease) {
                        $commentary .= "declined from {$prevFmt} to {$currFmt}, indicating slower collection of debts and higher reliance on credit sales.\nRecommendation: Enforce stricter credit policies and consider early payment incentives to speed up collections.";
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating faster collection of receivables.\nRecommendation: Maintain effective collection practices and continue monitoring receivables efficiency.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring collection efficiency and seek improvement opportunities.";
                    }
                } else {
                    $commentary = "{$label}\nReceivables turnover is {$currFmt} for this period.\nRecommendation: Monitor collection efficiency and ensure timely receivables conversion.";
                }
                break;
                
            case 'payables_turnover':
                if ($hasPrev) {
                    $commentary = "{$label}\nPayables turnover ";
                    if ($isDecrease) {
                        $commentary .= "declined from {$prevFmt} to {$currFmt}, suggesting slower payment of suppliers or fewer purchases relative to payables.\nRecommendation: Review payment terms and supplier relationships to optimize cash flow management.";
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating faster payment cycle or more frequent purchases.\nRecommendation: Balance payment efficiency with cash flow optimization.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring payables management and supplier relationships.";
                    }
                } else {
                    $commentary = "{$label}\nPayables turnover is {$currFmt} for this period.\nRecommendation: Monitor payables efficiency and optimize payment terms with suppliers.";
                }
                break;
                
            case 'roa':
                if ($hasPrev) {
                    $commentary = "{$label}\nROA ";
                    if ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating better profitability relative to total assets. This reflects more efficient use of resources.\nRecommendation: Continue optimizing asset use and ensure investments deliver measurable returns.";
                    } elseif ($isDecrease) {
                        $commentary .= "declined from {$prevFmt} to {$currFmt}, indicating reduced profitability relative to asset base.\nRecommendation: Review asset utilization and profitability to improve returns on assets.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring asset returns and seek opportunities for improvement.";
                    }
                } else {
                    $commentary = "{$label}\nROA is {$currFmt} for this period.\nRecommendation: Monitor asset returns and ensure efficient asset utilization.";
                }
                break;
                
            case 'roe':
                if ($hasPrev) {
                    $commentary = "{$label}\nROE ";
                    if ($isIncrease) {
                        $commentary .= "increased from {$prevFmt} to {$currFmt}, showing enhanced returns for shareholders after a prior loss. This indicates improved profitability and capital management.\nRecommendation: Maintain this trajectory by reinvesting profits in high-yielding initiatives.";
                    } elseif ($isDecrease) {
                        $commentary .= "declined from {$prevFmt} to {$currFmt}, indicating reduced returns for equity holders.\nRecommendation: Focus on improving profitability and efficient capital deployment to enhance shareholder returns.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring equity returns and seek improvement opportunities.";
                    }
                } else {
                    $commentary = "{$label}\nROE is {$currFmt} for this period.\nRecommendation: Monitor equity returns and ensure profitable use of shareholder capital.";
                }
                break;
                
            case 'operating_profit_margin':
                if ($hasPrev) {
                    $commentary = "{$label}\nOperating profit margin ";
                    if ($isDecrease) {
                        $commentary .= "decreased from {$prevFmt} to {$currFmt}, indicating slightly higher operating costs relative to revenue.\nRecommendation: Review administrative and overhead expenses to identify further efficiency opportunities.";
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating better operational efficiency and cost management.\nRecommendation: Maintain operational efficiency while continuing to optimize costs.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring operating margins and seek efficiency improvements.";
                    }
                } else {
                    $commentary = "{$label}\nOperating profit margin is {$currFmt} for this period.\nRecommendation: Monitor operating efficiency and ensure sustainable margins.";
                }
                break;
                
            case 'ebitda_margin':
                if ($hasPrev) {
                    $commentary = "{$label}\nEBITDA margin ";
                    if ($isDecrease) {
                        $commentary .= "also declined from {$prevFmt} to {$currFmt}, consistent with the operating profit margin trend. The business remains operationally profitable but faces slight margin compression.\nRecommendation: Focus on maintaining core operating efficiency while managing indirect costs.";
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger operational profitability and cost efficiency.\nRecommendation: Sustain operational efficiency and continue optimizing core business operations.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring EBITDA margins and operational efficiency.";
                    }
                } else {
                    $commentary = "{$label}\nEBITDA margin is {$currFmt} for this period.\nRecommendation: Monitor operational profitability and ensure sustainable EBITDA performance.";
                }
                break;
                
            case 'revenue_growth_rate':
                if ($hasPrev) {
                    $commentary = "{$label}\nRevenue growth rate of {$currFmt}";
                    if ($curr < 0) {
                        $commentary .= " highlights a contraction in business activity.\nRecommendation: Management should revisit marketing strategies, strengthen client relationships, and diversify revenue sources.";
                    } elseif ($curr > 0) {
                        $commentary .= " indicates positive business growth and expansion.\nRecommendation: Sustain growth momentum by maintaining customer relationships and exploring new market opportunities.";
                    } else {
                        $commentary .= " indicates stable revenue with no growth.\nRecommendation: Focus on growth initiatives to expand business activity.";
                    }
                } else {
                    $commentary = "{$label}\nRevenue growth rate is {$currFmt} for this period.\nRecommendation: Establish growth targets and monitor revenue trends.";
                }
                break;
                
            case 'net_profit_growth_rate':
                if ($hasPrev) {
                    $commentary = "{$label}\nNet profit growth ";
                    if (abs($changePct) > 100) {
                        $commentary .= "surged to {$currFmt}, reflecting a strong recovery from previous losses or significant profitability improvement.\nRecommendation: Focus on stabilizing this improvement through consistent sales performance and prudent cost management.";
                    } elseif ($curr > 0) {
                        $commentary .= "is {$currFmt}, indicating positive profit growth.\nRecommendation: Maintain profitability growth through balanced revenue and cost management.";
                    } else {
                        $commentary .= "is {$currFmt}, indicating declining profitability.\nRecommendation: Review revenue streams and cost structures to restore profit growth.";
                    }
                } else {
                    $commentary = "{$label}\nNet profit growth rate is {$currFmt} for this period.\nRecommendation: Establish profit growth targets and monitor trends.";
                }
                break;
                
            case 'expense_growth_rate':
                if ($hasPrev) {
                    $commentary = "{$label}\nExpenses ";
                    if ($isDecrease) {
                        $commentary .= "declined by {$changeAbs}%, indicating significant improvement in spending control.\nRecommendation: Maintain current cost efficiency measures and ensure savings are sustainable.";
                    } elseif ($isIncrease) {
                        $commentary .= "increased by {$changeAbs}%, suggesting higher operational costs or business expansion.\nRecommendation: Review expense categories and ensure cost increases are justified by revenue growth.";
                    } else {
                        $commentary .= "remained stable, indicating controlled spending.\nRecommendation: Continue monitoring expenses and seek efficiency opportunities.";
                    }
                } else {
                    $commentary = "{$label}\nExpense growth rate is {$currFmt} for this period.\nRecommendation: Monitor expense trends and establish cost control measures.";
                }
                break;
                
            case 'operating_cash_flow_ratio':
                if ($hasPrev) {
                    $commentary = "{$label}\nOperating cash flow ratio ";
                    if ($isDecrease) {
                        $commentary .= "decreased from {$prevFmt} to {$currFmt}";
                        if ($curr < 0) {
                            $commentary .= ", signaling weakened ability to cover current liabilities with operating cash.\nRecommendation: Improve operational cash inflows by managing receivables and reducing unnecessary cash outflows.";
                        } else {
                            $commentary .= ", indicating reduced cash coverage of current liabilities.\nRecommendation: Strengthen operating cash flow and optimize working capital management.";
                        }
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger ability to cover liabilities with operating cash.\nRecommendation: Maintain strong operating cash flow discipline.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring operating cash flow coverage.";
                    }
                } else {
                    $commentary = "{$label}\nOperating cash flow ratio is {$currFmt}";
                    if ($curr < 0) {
                        $commentary .= ", indicating insufficient operating cash to cover current liabilities.\nRecommendation: Improve operational cash inflows and optimize working capital.";
                    } else {
                        $commentary .= " for this period.\nRecommendation: Monitor operating cash flow coverage of liabilities.";
                    }
                }
                break;
                
            case 'free_cash_flow':
                if ($hasPrev) {
                    $commentary = "{$label}\nFree cash flow ";
                    if ($isDecrease) {
                        $commentary .= "worsened from {$prevFmt} to {$currFmt}, suggesting heavy cash utilization for operational or capital purposes.\nRecommendation: Reassess investment priorities and ensure cash reserves remain adequate for short-term needs.";
                    } elseif ($isIncrease) {
                        $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating better cash generation after capital expenditures.\nRecommendation: Consider deploying excess free cash flow in value-creating investments or debt reduction.";
                    } else {
                        $commentary .= "remained stable at {$currFmt}.\nRecommendation: Continue monitoring free cash flow and optimize capital allocation.";
                    }
                } else {
                    $commentary = "{$label}\nFree cash flow is {$currFmt} for this period.\nRecommendation: Monitor free cash flow generation and ensure adequate cash reserves.";
                }
                break;
                
            case 'cash_conversion_cycle':
                if ($hasPrev) {
                    $commentary = "{$label}\nThe cash conversion cycle ";
                    if ($isIncrease) {
                        $commentary .= "lengthened from {$prevFmt} to {$currFmt}, showing a major slowdown in converting resources into cash.\nRecommendation: Streamline inventory management and accelerate customer collections to shorten the cycle and strengthen liquidity.";
                    } elseif ($isDecrease) {
                        $commentary .= "shortened from {$prevFmt} to {$currFmt}, indicating faster conversion of resources into cash.\nRecommendation: Maintain efficient working capital management and continue optimizing the cash cycle.";
                    } else {
                        $commentary .= "remained stable at {$currFmt} days.";
                        if ($curr > 90) {
                            $commentary .= " The extended cycle suggests opportunities for improvement.\nRecommendation: Focus on reducing inventory days and receivables collection to shorten the cycle.";
                        } else {
                            $commentary .= "\nRecommendation: Continue monitoring the cash conversion cycle and maintain efficiency.";
                        }
                    }
                } else {
                    $commentary = "{$label}\nThe cash conversion cycle is {$currFmt}";
                    if ($curr > 90) {
                        $commentary .= ", showing a major slowdown in converting resources into cash.\nRecommendation: Streamline inventory management and accelerate customer collections to shorten the cycle and strengthen liquidity.";
                    } else {
                        $commentary .= " days.\nRecommendation: Monitor the cash conversion cycle and optimize working capital management.";
                    }
                }
                break;
                
            default:
                // Generic commentary for other KPIs
                if ($hasPrev) {
                    $verb = $isIncrease ? 'increased' : ($isDecrease ? 'decreased' : 'remained stable');
                    $commentary = "{$label}\n{$label} {$verb}";
                    if ($hasPrev && $prev != 0) {
                        $commentary .= " by {$changeAbs}%, from {$prevFmt} to {$currFmt}";
                    }
                    $commentary .= ". This movement reflects changes in underlying business operations.\nRecommendation: Continue monitoring this KPI and assess its impact on overall business performance.";
                } else {
                    $commentary = "{$label}\n{$label} is {$currFmt} for this period.\nRecommendation: Establish baseline metrics and monitor trends going forward.";
                }
                break;
        }
        
        return $commentary;
    }
    
    private function addOverallCommentary($section, $data)
    {
        $section->addTitle('Overall Commentary Summary', 2);
        
        $kpis = $data['kpis'] ?? [];
        $revenueKpi = collect($kpis)->firstWhere('key', 'revenue');
        $expensesKpi = collect($kpis)->firstWhere('key', 'expenses');
        $netProfitKpi = collect($kpis)->firstWhere('key', 'net_profit');
        $cashFlowKpi = collect($kpis)->firstWhere('key', 'cash_flow');
        $netProfitMarginKpi = collect($kpis)->firstWhere('key', 'net_profit_margin');
        $dsoKpi = collect($kpis)->firstWhere('key', 'dso');
        $dioKpi = collect($kpis)->firstWhere('key', 'dio');
        $currentRatioKpi = collect($kpis)->firstWhere('key', 'current_ratio');
        $revenueGrowthKpi = collect($kpis)->firstWhere('key', 'revenue_growth_rate');
        
        $revenueTrend = $revenueKpi['trend'] ?? 'flat';
        $expensesTrend = $expensesKpi['trend'] ?? 'flat';
        $netProfitTrend = $netProfitKpi['trend'] ?? 'flat';
        $cashFlowTrend = $cashFlowKpi['trend'] ?? 'flat';
        
        $expensesTrendAdjusted = $expensesTrend === 'up' ? 'down' : ($expensesTrend === 'down' ? 'up' : 'flat');
        
        $netProfit = (float)($netProfitKpi['value'] ?? 0);
        $netProfitMargin = (float)($netProfitMarginKpi['value'] ?? 0);
        $dso = (float)($dsoKpi['value'] ?? 0);
        $dio = (float)($dioKpi['value'] ?? 0);
        $currentRatio = (float)($currentRatioKpi['value'] ?? 0);
        $cashFlow = (float)($cashFlowKpi['value'] ?? 0);
        $revenueGrowth = $revenueGrowthKpi ? (float)($revenueGrowthKpi['value'] ?? 0) : null;
        
        $hasProfitabilityRecovery = $netProfitTrend === 'up' && $netProfit > 0;
        $hasStrongCostControl = $expensesTrendAdjusted === 'up';
        $hasCashFlowChallenge = $cashFlow < 0 || $cashFlowTrend === 'down';
        $hasReceivablesChallenge = $dso > 45 || ($dsoKpi['trend'] ?? 'flat') === 'up';
        $hasInventoryChallenge = $dio > 90 || ($dioKpi['trend'] ?? 'flat') === 'up';
        $hasLiquidityConcern = $currentRatio < 1;
        $hasRevenueGrowth = $revenueTrend === 'up' || ($revenueGrowth !== null && $revenueGrowth > 0);
        $hasRevenueDecline = $revenueTrend === 'down' || ($revenueGrowth !== null && $revenueGrowth < 0);
        
        $strengths = [];
        $challenges = [];
        $recommendations = [];
        
        if ($hasProfitabilityRecovery) {
            $strengths[] = "strong cost control and a major profitability recovery";
        } elseif ($netProfit > 0 && $netProfitTrend !== 'down') {
            $strengths[] = "profitable operations";
        } elseif ($netProfit < 0) {
            $challenges[] = "profitability challenges";
        }
        
        if ($hasStrongCostControl) {
            $strengths[] = "effective cost containment measures";
        } elseif ($expensesTrendAdjusted === 'down') {
            $challenges[] = "increasing expenses";
        }
        
        if ($hasCashFlowChallenge) {
            $challenges[] = "cash flow management";
        } elseif ($cashFlow > 0 && $cashFlowTrend === 'up') {
            $strengths[] = "improving cash flow";
        }
        
        if ($hasReceivablesChallenge) {
            $challenges[] = "receivables collection";
        } elseif ($dso <= 45 && ($dsoKpi['trend'] ?? 'flat') === 'down') {
            $strengths[] = "efficient receivables management";
        }
        
        if ($hasInventoryChallenge) {
            $challenges[] = "inventory efficiency";
        } elseif ($dio <= 90 && ($dioKpi['trend'] ?? 'flat') === 'down') {
            $strengths[] = "effective inventory management";
        }
        
        if ($hasLiquidityConcern) {
            $challenges[] = "liquidity discipline";
        } elseif ($currentRatio >= 1.5) {
            $strengths[] = "strong liquidity position";
        }
        
        if ($hasRevenueGrowth) {
            $strengths[] = "revenue growth momentum";
        } elseif ($hasRevenueDecline) {
            $challenges[] = "sales performance";
        }
        
        if ($hasCashFlowChallenge) {
            $recommendations[] = "improving liquidity discipline";
        }
        if ($hasReceivablesChallenge) {
            $recommendations[] = "enhancing collection efficiency";
        }
        if ($hasInventoryChallenge) {
            $recommendations[] = "optimizing inventory management";
        }
        if ($hasRevenueDecline) {
            $recommendations[] = "enhancing sales performance";
        }
        if ($hasProfitabilityRecovery || ($netProfit > 0 && $netProfitMargin > 0)) {
            $recommendations[] = "sustaining profitability";
        }
        if ($hasCashFlowChallenge || $hasReceivablesChallenge) {
            $recommendations[] = "ensuring that operational gains are supported by timely cash inflows";
        }
        
        $summaryText = "The reporting period ";
        
        if (!empty($strengths)) {
            $summaryText .= "reflects " . implode(", ", array_slice($strengths, 0, 2));
            if (count($strengths) > 2) {
                $summaryText .= ", and " . $strengths[2];
            }
        } else {
            $summaryText .= "presents ";
        }
        
        if (!empty($challenges)) {
            if (!empty($strengths)) {
                $summaryText .= ", but challenges remain in " . implode(", ", array_slice($challenges, 0, 2));
                if (count($challenges) > 2) {
                    $summaryText .= ", and " . $challenges[2];
                }
            } else {
                $summaryText .= "challenges in " . implode(", ", array_slice($challenges, 0, 2));
                if (count($challenges) > 2) {
                    $summaryText .= ", and " . $challenges[2];
                }
            }
        }
        
        $summaryText .= ".";
        
        if (!empty($recommendations)) {
            if (count($recommendations) == 1) {
                $summaryText .= " " . ucfirst($recommendations[0]) . " will be critical for maintaining and improving business performance.";
            } elseif (count($recommendations) == 2) {
                $summaryText .= " " . ucfirst($recommendations[0]) . " and " . $recommendations[1] . " will be critical for maintaining and improving business performance.";
            } else {
                $lastRec = array_pop($recommendations);
                $summaryText .= " " . ucfirst(implode(", ", $recommendations)) . ", and " . $lastRec . " will be critical for maintaining and improving business performance.";
            }
        } else {
            $summaryText .= " Continued focus on operational efficiency and strategic growth will be essential for sustained success.";
        }
        
        $section->addText($summaryText, $this->getJustifiedStyle(['size' => 11]));
    }
    
    private function addAppendix($section, $data)
    {
        $section->addTitle('Appendix', 2);
        $items = [
            'All figures are rounded for readability and may include consolidations across multiple branches.',
            'Where prior period data is unavailable, trends default to stable and percent change to 0%.',
            'Inventory and COGS approximations rely on account naming conventions (e.g., accounts containing "Inventory", "Cost of Goods Sold").',
            'For audit-grade reporting, reconcile KPI outputs with detailed ledgers and trial balances.'
        ];
        foreach ($items as $item) {
            $section->addText('• ' . $item, $this->getJustifiedStyle(['size' => 11]));
        }
    }
    
    private function addFooter($section, $data)
    {
        $section->addTextBreak(2);
        $section->addText('This is a System Generated Report', ['size' => 10, 'italic' => true]);
        $section->addText('Generated by: ' . ($data['user']->name ?? 'System'), ['size' => 10]);
        $section->addText('Generated on: ' . now()->format('Y-m-d H:i'), ['size' => 10]);
        $section->addText('Using: ' . \App\Models\SystemSetting::getValue('application_name', config('app.name')), ['size' => 10]);
    }
}
