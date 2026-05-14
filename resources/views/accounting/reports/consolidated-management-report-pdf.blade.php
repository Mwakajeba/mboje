@php
$fontFamily = \App\Models\SystemSetting::getValue('document_font_family', 'DejaVu Sans');
$baseFontSize = (int) \App\Models\SystemSetting::getValue('document_base_font_size', 10);
$textColor = \App\Models\SystemSetting::getValue('document_text_color', '#333333');
$headerColor = \App\Models\SystemSetting::getValue('document_header_color', '#2c3e50');
$accentColor = \App\Models\SystemSetting::getValue('document_accent_color', '#3498db');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30mm 18mm 20mm 18mm; }
        body { font-family: '{{ $fontFamily }}', sans-serif; color: {{ $textColor }}; font-size: {{ $baseFontSize }}px; }
        h1,h2,h3 { color: {{ $headerColor }}; margin: 0 0 8px; }
        .cover { height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
        .cover .title { font-size: 26px; margin-bottom: 6px; color: {{ $headerColor }}; }
        .cover .subtitle { font-size: 16px; color: #666; }
        .cover .meta { margin-top: 14px; color: #888; }
        .logo { margin-bottom: 16px; }
        .page-break { page-break-before: always; }
        .section { margin: 0 0 14px; }
        .p { text-align: justify; line-height: 1.5; }
        .kpi-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .kpi-cell { width: 25%; border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        .kpi-label { display: inline-flex; align-items: center; gap: 6px; }
        .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
        .dot-up { background: #27ae60; }
        .dot-down { background: #e74c3c; }
        .dot-flat { background: #95a5a6; }
        .bar-chart { width: 100%; margin-top: 6px; }
        .bar-row { display: flex; align-items: center; margin: 6px 0; }
        .bar-label { width: 120px; font-weight: bold; color: {{ $headerColor }}; }
        .bar-container { flex: 1; background: #f1f3f5; height: 10px; border-radius: 6px; overflow: hidden; }
        .bar { height: 10px; border-radius: 6px; }
        .bar-revenue { background: #3498db; }
        .bar-expenses { background: #e74c3c; }
        .bar-profit { background: #27ae60; }
        .bar-loss { background: #e67e22; }
        .bar-value { width: 80px; text-align: right; font-size: {{ max(8, $baseFontSize-1) }}px; color: #555; }
        .small { color: #777; font-size: {{ max(8, $baseFontSize-2) }}px; }
        .hr { border-top: 1px solid #ddd; margin: 10px 0; }
        .list { margin: 0; padding-left: 16px; }
        .footer { position: static; margin-top: 12mm; text-align: center; color: #999; font-size: {{ max(8, $baseFontSize-2) }}px; }
        .footer-fixed { position: fixed; bottom: 0; left: 0; right: 0; width: 100%; text-align: center; color: #999; font-size: {{ max(8, $baseFontSize-2) }}px; }
        .pagenum:before { content: counter(page); }
        .totalpages:before { content: counter(pages); }
        .arrow-up { color: #27ae60; }
        .arrow-down { color: #e74c3c; }
        .arrow-flat { color: #95a5a6; }
    </style>
</head>
<body>
    <!-- Global fixed footer for page numbers on all pages -->
    <div class="footer-fixed">Page <span class="pagenum"></span></div>
    <!-- Cover Page -->
    <div class="cover">
        @php
            $companyModel = isset($company) ? $company : (function_exists('current_company') ? current_company() : null);
            $logoPath = null;
            if ($companyModel) {
                if (!empty($companyModel->logo)) {
                    $logoPath = public_path('storage/' . $companyModel->logo);
                } elseif (!empty($companyModel->logo_path)) {
                    $logoPath = public_path($companyModel->logo_path);
                }
            }
        @endphp
        @if($logoPath && file_exists($logoPath))
            <img class="logo" src="{{ $logoPath }}" alt="Company Logo" height="80">
        @endif
        <div class="title">Consolidated Management Report</div>
        <div class="subtitle">{{ $company->name ?? 'Company' }} @if(!empty($branch)) — {{ $branch->name }} @endif</div>
        @if(!empty($company))
            @if(!empty($company->address))
                <div class="meta">{{ $company->address }}</div>
            @endif
            @php
                $contactBits = [];
                if (!empty($company->phone)) { $contactBits[] = 'Tel: '.$company->phone; }
                if (!empty($company->email)) { $contactBits[] = 'Email: '.$company->email; }
                if (!empty($company->website)) { $contactBits[] = 'Web: '.$company->website; }
            @endphp
            @if(!empty($contactBits))
                <div class="meta">{{ implode(' | ', $contactBits) }}</div>
            @endif
            @php
                $regBits = [];
                if (!empty($company->tin)) { $regBits[] = 'TIN: '.$company->tin; }
                if (!empty($company->vat_number)) { $regBits[] = 'VAT: '.$company->vat_number; }
                if (!empty($company->registration_no)) { $regBits[] = 'Reg No: '.$company->registration_no; }
            @endphp
            @if(!empty($regBits))
                <div class="meta">{{ implode(' | ', $regBits) }}</div>
            @endif
        @endif
        <div class="meta">Period: {{ strtoupper($period) }} ({{ $startDate }} to {{ $endDate }})</div>
        <div class="meta">Generated on {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <div class="page-break"></div>

    <!-- What is Consolidated Section -->
    <div class="section">
        <h2>Table of Contents</h2>
        <ol class="list">
            <li>Executive Summary</li>
            <li>About This Consolidated Report</li>
            <li>Key Performance Indicators</li>
            <li>Analytical Highlights</li>
            <li>Balance Sheet Snapshot</li>
            <li>Profit & Loss</li>
            <li>Methodology</li>
            <li>Data Sources & Scope</li>
            <li>Definitions & Formulas</li>
            <li>Management Commentary</li>
            <li>Appendix</li>
        </ol>
    </div>

    <div class="section">
        <h2>Executive Summary</h2>
        <p class="p">{{ $summary ?? '—' }}</p>
    </div>
    <div class="section">
        <h2>About This Consolidated Report</h2>
        <p class="p">
            This consolidated report aggregates key metrics across Accounting and Operations to support timely decision making. It
            presents performance for the selected period alongside the previous comparable period to reveal direction and momentum.
            The KPIs included here reflect your configuration and data availability: Revenue, Expenses, Net Profit, Cash Flow,
            Outstanding Receivables and Debtors Collection Period (DSO), Creditors Payment Period (DPO), Inventory Holding Period (DIO),
            and Profitability ratios such as Gross Profit Margin and Net Profit Margin. Where applicable, values show prior period,
            percent change and trend arrows.
        </p>
    </div>

    <div class="section">
        <h2>Key Performance Indicators</h2>
        @php $kCount = count($kpis ?? []); @endphp
        <table class="kpi-table">
            @for($i = 0; $i < $kCount; $i += 4)
                <tr>
                    @for($j = 0; $j < 4; $j++)
                        @php $idx = $i + $j; @endphp
                        @if($idx < $kCount)
                            @php 
                                $kpi = $kpis[$idx]; 
                                $key = $kpi['key'] ?? ''; 
                                $isPercent = in_array($key, ['net_profit_margin','expense_ratio','gross_profit_margin','roa','roe','operating_profit_margin','ebitda_margin','revenue_growth_rate','net_profit_growth_rate','expense_growth_rate']); 
                                $isDays = in_array($key, ['dso','dpo','dio','cash_conversion_cycle']); 
                                $isRatio = in_array($key, ['current_ratio','quick_ratio','cash_ratio','debt_to_equity','asset_turnover','inventory_turnover','receivables_turnover','payables_turnover','operating_cash_flow_ratio']);
                            @endphp
                            <td class="kpi-cell">
                                <div class="kpi-label"><span class="dot {{ ($kpi['trend'] ?? '') === 'up' ? 'dot-up' : ((($kpi['trend'] ?? '') === 'down') ? 'dot-down' : 'dot-flat') }}"></span><span class="small">{{ $kpi['label'] }}</span></div>
                                <div style="font-weight:bold; color: {{ ($kpi['trend'] ?? '') === 'down' ? '#e74c3c' : $accentColor }};">
                                    @if($isPercent)
                                        {{ number_format((float)($kpi['value'] ?? 0), 1) }}%
                                    @elseif($isDays)
                                        {{ number_format((float)($kpi['value'] ?? 0), 0) }} days
                                    @elseif($isRatio)
                                        {{ number_format((float)($kpi['value'] ?? 0), 2) }}
                                    @else
                                        TZS {{ number_format((float)($kpi['value'] ?? 0), 2) }}
                                    @endif
                                </div>
                                @if($kpi['previous'] !== null)
                                <div class="small">Prev:
                                    @if($isPercent)
                                        {{ number_format((float)($kpi['previous'] ?? 0), 1) }}%
                                    @elseif($isDays)
                                        {{ number_format((float)($kpi['previous'] ?? 0), 0) }} days
                                    @elseif($isRatio)
                                        {{ number_format((float)($kpi['previous'] ?? 0), 2) }}
                                    @else
                                        TZS {{ number_format((float)($kpi['previous'] ?? 0), 2) }}
                                    @endif
                                     | {{ number_format((float)($kpi['change_percent'] ?? 0), 1) }}%
                                     @php $t = $kpi['trend'] ?? 'flat'; @endphp
                                     <span class="{{ $t === 'up' ? 'arrow-up' : ($t === 'down' ? 'arrow-down' : 'arrow-flat') }}">
                                        {{ $t === 'up' ? '↑' : ($t === 'down' ? '↓' : '—') }}
                                     </span>
                                </div>
                                @endif
                            </td>
                        @else
                            <td class="kpi-cell">&nbsp;</td>
                        @endif
                    @endfor
                </tr>
            @endfor
        </table>
    </div>

    @php
        // Simple inline bar chart for core KPIs
        $rev = collect($kpis ?? [])->firstWhere('key','revenue')['value'] ?? 0;
        $exp = collect($kpis ?? [])->firstWhere('key','expenses')['value'] ?? 0;
        $prf = collect($kpis ?? [])->firstWhere('key','net_profit')['value'] ?? ($rev - $exp);
        $maxVal = max(1, abs($rev), abs($exp), abs($prf));
        $revPct = round((abs($rev) / $maxVal) * 100);
        $expPct = round((abs($exp) / $maxVal) * 100);
        $prfPct = round((abs($prf) / $maxVal) * 100);
    @endphp
    <div class="section">
        <h2>KPI Chart</h2>
        <div class="bar-chart">
            <div class="bar-row">
                <div class="bar-label">Revenue</div>
                <div class="bar-container"><div class="bar bar-revenue" style="width: {{ $revPct }}%"></div></div>
                <div class="bar-value">TZS {{ number_format((float)$rev,2) }}</div>
            </div>
            <div class="bar-row">
                <div class="bar-label">Expenses</div>
                <div class="bar-container"><div class="bar bar-expenses" style="width: {{ $expPct }}%"></div></div>
                <div class="bar-value">TZS {{ number_format((float)$exp,2) }}</div>
            </div>
            <div class="bar-row">
                <div class="bar-label">Net Profit</div>
                <div class="bar-container"><div class="bar {{ ($prf ?? 0) >= 0 ? 'bar-profit' : 'bar-loss' }}" style="width: {{ $prfPct }}%"></div></div>
                <div class="bar-value">TZS {{ number_format((float)$prf,2) }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Analytical Highlights</h2>
        <ul class="list">
            @foreach(($kpis ?? []) as $k)
                @php
                    $key = $k['key'] ?? '';
                    $isPercent = in_array($key, ['net_profit_margin','expense_ratio','gross_profit_margin','roa','roe','operating_profit_margin','ebitda_margin','revenue_growth_rate','net_profit_growth_rate','expense_growth_rate']);
                    $isDays = in_array($key, ['dso','dpo','dio','cash_conversion_cycle']);
                    $isRatio = in_array($key, ['current_ratio','quick_ratio','cash_ratio','debt_to_equity','asset_turnover','inventory_turnover','receivables_turnover','payables_turnover','operating_cash_flow_ratio']);
                    $trend = $k['trend'] ?? 'flat';
                    $verb = $trend === 'up' ? 'increased' : ($trend === 'down' ? 'decreased' : 'remained unchanged');
                    $currFmt = $isPercent ? (number_format((float)($k['value'] ?? 0), 1).'%' ) : ($isDays ? (number_format((float)($k['value'] ?? 0), 0).' days') : ($isRatio ? number_format((float)($k['value'] ?? 0), 2) : ('TZS '.number_format((float)($k['value'] ?? 0), 2))));
                    if ($k['previous'] !== null) {
                        $prevFmt = $isPercent ? (number_format((float)($k['previous'] ?? 0), 1).'%' ) : ($isDays ? (number_format((float)($k['previous'] ?? 0), 0).' days') : ($isRatio ? number_format((float)($k['previous'] ?? 0), 2) : ('TZS '.number_format((float)($k['previous'] ?? 0), 2))));
                        $deltaRaw = (float)($k['value'] ?? 0) - (float)($k['previous'] ?? 0);
                        $deltaFmt = $isPercent ? (number_format($deltaRaw, 1).'%' ) : ($isDays ? (number_format($deltaRaw, 0).' days') : ($isRatio ? number_format($deltaRaw, 2) : ('TZS '.number_format($deltaRaw, 2))));
                        $pct = number_format((float)($k['change_percent'] ?? 0), 1);
                    } else {
                        $prevFmt = 'N/A';
                        $deltaFmt = 'N/A';
                        $pct = 'N/A';
                    }
                @endphp
                <li>
                    @if($k['previous'] === null)
                        {{ $k['label'] }}: {{ $currFmt }}.
                    @elseif($trend === 'flat')
                        {{ $k['label'] }} remained unchanged at {{ $currFmt }}.
                    @else
                        {{ $k['label'] }} {{ $verb }} by {{ $deltaFmt }} from {{ $prevFmt }} to {{ $currFmt }}, equivalent to {{ $pct }}%.
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="section">
        <h2>Profit Drivers & Recommendations</h2>
        @php
            $np = (float)($incomeStatement['net_profit'] ?? 0);
            $revKpi = collect($kpis ?? [])->firstWhere('key','revenue');
            $expKpi = collect($kpis ?? [])->firstWhere('key','expenses');
            $gpmKpi = collect($kpis ?? [])->firstWhere('key','gross_profit_margin');
            $npmKpi = collect($kpis ?? [])->firstWhere('key','net_profit_margin');
            $revTrend = $revKpi['trend'] ?? 'flat';
            $expTrend = $expKpi['trend'] ?? 'flat';
            $revChg = (float)($revKpi['change_percent'] ?? 0);
            $expChg = (float)($expKpi['change_percent'] ?? 0);
            $gpmVal = isset($gpmKpi['value']) ? number_format((float)$gpmKpi['value'], 1).'%' : null;
            $npmVal = isset($npmKpi['value']) ? number_format((float)$npmKpi['value'], 1).'%' : null;
        @endphp
        <p class="p">
            The period closed with @if($np >= 0) a net profit of TZS {{ number_format($np, 2) }} @else a net loss of TZS {{ number_format(abs($np), 2) }} @endif.
            Revenue {{ $revTrend === 'up' ? 'increased' : ($revTrend === 'down' ? 'decreased' : 'remained stable') }} by {{ number_format(abs($revChg), 1) }}%, while expenses
            {{ $expTrend === 'up' ? 'increased' : ($expTrend === 'down' ? 'decreased' : 'remained stable') }} by {{ number_format(abs($expChg), 1) }}%.
            @if($gpmVal) Gross Profit Margin stood at {{ $gpmVal }}.@endif
            @if($npmVal) Net Profit Margin was {{ $npmVal }}.@endif
        </p>
        @if($np >= 0)
            <p class="p">
                Positive performance was primarily driven by
                @if($revTrend === 'up') stronger revenue growth @endif
                @if($revTrend === 'up' && $expTrend === 'down') and @endif
                @if($expTrend === 'down') effective cost control @endif
                @if($revTrend === 'flat' && $expTrend === 'flat') stable revenue and expense management @endif
                .
            </p>
            <p class="p"><strong>Recommendations to sustain/improve:</strong> Focus on maintaining pricing discipline and customer mix, continue procurement and operating cost efficiencies, reinforce credit control to protect cash flow, and optimize inventory to limit carrying costs without risking stock-outs.</p>
        @else
            <p class="p">
                Negative performance likely reflects
                @if($revTrend !== 'up') softer revenue momentum @endif
                @if($revTrend !== 'up' && $expTrend !== 'down') and @endif
                @if($expTrend !== 'down') elevated operating costs @endif
                .
            </p>
            <p class="p"><strong>Recommendations to recover:</strong> Accelerate sales through targeted offers and top-customer retention, review pricing/margin by product, cut non-essential expenses, renegotiate supplier terms to reduce COGS, tighten collections (reduce DSO), and optimize stock levels (reduce DIO) to free working capital.</p>
        @endif
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2>Balance Sheet Snapshot (As at {{ $endDate }})</h2>
        <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse: collapse;">
            <tr style="background:#f5f5f5;">
                <td><strong>Item</strong></td>
                <td align="right"><strong>Current</strong></td>
                <td align="right"><strong>Prev{{ isset($prevEndDate) ? ' ('.$prevEndDate.')' : '' }}</strong></td>
            </tr>
            <tr>
                <td>Assets</td>
                <td align="right">TZS {{ number_format((float)($balanceSheet['assets'] ?? 0), 2) }}</td>
                <td align="right">TZS {{ number_format((float)($prevBalanceSheet['assets'] ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Liabilities</td>
                <td align="right">TZS {{ number_format((float)($balanceSheet['liabilities'] ?? 0), 2) }}</td>
                <td align="right">TZS {{ number_format((float)($prevBalanceSheet['liabilities'] ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Equity</td>
                <td align="right">TZS {{ number_format((float)($balanceSheet['equity'] ?? 0), 2) }}</td>
                <td align="right">TZS {{ number_format((float)($prevBalanceSheet['equity'] ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Year-to-Date Net Profit</td>
                <td align="right">TZS {{ number_format((float)($balanceSheet['net_profit'] ?? 0), 2) }}</td>
                <td align="right">TZS {{ number_format((float)($prevBalanceSheet['net_profit'] ?? 0), 2) }}</td>
            </tr>
            <tr style="background:#eef7ff;">
                <td><strong>Liabilities + Equity</strong></td>
                @php $rhs = (float)($balanceSheet['liabilities'] ?? 0) + (float)($balanceSheet['equity_including_profit'] ?? 0); $rhsPrev = (float)($prevBalanceSheet['liabilities'] ?? 0) + (float)($prevBalanceSheet['equity_including_profit'] ?? 0); @endphp
                <td align="right"><strong>TZS {{ number_format($rhs, 2) }}</strong></td>
                <td align="right"><strong>TZS {{ number_format($rhsPrev, 2) }}</strong></td>
            </tr>
            <tr style="background:#eef7ff;">
                <td><strong>Total Assets</strong></td>
                <td align="right"><strong>TZS {{ number_format((float)($balanceSheet['assets'] ?? 0), 2) }}</strong></td>
                <td align="right"><strong>TZS {{ number_format((float)($prevBalanceSheet['assets'] ?? 0), 2) }}</strong></td>
            </tr>
        </table>
        <p class="p" style="margin-top:8px;">
            The balance sheet shows the resources controlled (Assets) and the claims against them (Liabilities and Equity) as at the end of the period.
            Differences versus prior periods typically arise from operating results, financing activities, and working capital movements.
        </p>
        @if(!empty($balanceSheetDetailed))
        <h3>Detailed Balance Sheet</h3>
        <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse: collapse;">
            @php
                $assetsTotal = 0; $liabTotal = 0; $equityTotal = 0; $np = (float)($balanceSheet['net_profit'] ?? 0);
                $assetsTotalPrev = 0; $liabTotalPrev = 0; $equityTotalPrev = 0; $npPrev = (float)($prevBalanceSheet['net_profit'] ?? 0);
                // Helper function to find previous amount by account ID
                $findPrevBalanceAmount = function($category, $mainGroup, $fsli, $accountId, $prevData) {
                    if (!isset($prevData[$category]['main_groups'][$mainGroup]['fslis'][$fsli]['accounts'])) return 0;
                    foreach ($prevData[$category]['main_groups'][$mainGroup]['fslis'][$fsli]['accounts'] as $prevAcc) {
                        if ($prevAcc['account_id'] == $accountId) {
                            return (float)$prevAcc['amount'];
                        }
                    }
                    return 0;
                };
            @endphp
            @foreach(['Assets', 'Liabilities', 'Equity'] as $category)
                @php $categoryLower = strtolower($category); @endphp
                <tr style="background:#f9fafb;"><td colspan="3"><strong>{{ $category }}</strong></td></tr>
                @if(isset($balanceSheetDetailed[$category]['main_groups']))
                    @foreach($balanceSheetDetailed[$category]['main_groups'] as $mainGroupName => $mainGroup)
                        <tr style="background:#f5f5f5;"><td colspan="3"><strong>{{ $mainGroupName }}</strong></td></tr>
                        @if(isset($mainGroup['fslis']))
                            @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                <tr style="background:#fcfcfc;"><td colspan="3"><em>{{ $fsliName }}</em></td></tr>
                                @if(isset($fsli['accounts']))
                                    @foreach($fsli['accounts'] as $acc)
                                        @php 
                                            $amount = (float)$acc['amount'];
                                            if ($categoryLower === 'assets') {
                                                $assetsTotal += $amount;
                                                $prevAmt = $findPrevBalanceAmount('Assets', $mainGroupName, $fsliName, $acc['account_id'], $prevBalanceSheetDetailed ?? []);
                                                $assetsTotalPrev += $prevAmt;
                                            } elseif ($categoryLower === 'liabilities') {
                                                $liabTotal += $amount;
                                                $prevAmt = $findPrevBalanceAmount('Liabilities', $mainGroupName, $fsliName, $acc['account_id'], $prevBalanceSheetDetailed ?? []);
                                                $liabTotalPrev += $prevAmt;
                                            } elseif ($categoryLower === 'equity') {
                                                $equityTotal += $amount;
                                                $prevAmt = $findPrevBalanceAmount('Equity', $mainGroupName, $fsliName, $acc['account_id'], $prevBalanceSheetDetailed ?? []);
                                                $equityTotalPrev += $prevAmt;
                                            }
                                            $accountLabel = ($acc['account_code'] ?? '') ? ($acc['account_code'] . ' - ' . $acc['account_name']) : $acc['account_name'];
                                        @endphp
                                        <tr>
                                            <td style="padding-left: 20px;">{{ $accountLabel }}</td>
                                            <td align="right">TZS {{ number_format($amount, 2) }}</td>
                                            <td align="right">TZS {{ number_format($prevAmt ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                                @if(isset($fsli['total']))
                                    <tr style="background:#f9f9f9;">
                                        <td style="padding-left: 10px;"><em>Total {{ $fsliName }}</em></td>
                                        <td align="right"><em>TZS {{ number_format($fsli['total'], 2) }}</em></td>
                                        <td align="right"><em>TZS {{ number_format($prevBalanceSheetDetailed[$category]['main_groups'][$mainGroupName]['fslis'][$fsliName]['total'] ?? 0, 2) }}</em></td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                        @if(isset($mainGroup['total']))
                            <tr style="background:#f0f0f0;">
                                <td style="padding-left: 5px;"><strong>Total {{ $mainGroupName }}</strong></td>
                                <td align="right"><strong>TZS {{ number_format($mainGroup['total'], 2) }}</strong></td>
                                <td align="right"><strong>TZS {{ number_format($prevBalanceSheetDetailed[$category]['main_groups'][$mainGroupName]['total'] ?? 0, 2) }}</strong></td>
                            </tr>
                        @endif
                    @endforeach
                @endif
            @endforeach
            <tr>
                <td>Year-to-Date Net Profit</td>
                <td align="right">TZS {{ number_format($np, 2) }}</td>
                <td align="right">TZS {{ number_format($npPrev, 2) }}</td>
            </tr>
            @php $lhsTotal = $assetsTotal; $rhsTotal = $liabTotal + $equityTotal + $np; $lhsTotalPrev = $assetsTotalPrev; $rhsTotalPrev = $liabTotalPrev + $equityTotalPrev + $npPrev; @endphp
            <tr style="background:#eef7ff;">
                <td><strong>Total Assets</strong></td>
                <td align="right"><strong>TZS {{ number_format($assetsTotal, 2) }}</strong></td>
                <td align="right"><strong>TZS {{ number_format($assetsTotalPrev, 2) }}</strong></td>
            </tr>
            <tr style="background:#eef7ff;">
                <td><strong>Total Liabilities + Equity</strong></td>
                <td align="right"><strong>TZS {{ number_format($rhsTotal, 2) }}</strong></td>
                <td align="right"><strong>TZS {{ number_format($rhsTotalPrev, 2) }}</strong></td>
            </tr>
        </table>
        @endif
    </div>

    <div class="section">
        <h2>Profit & Loss ({{ $startDate }} to {{ $endDate }})</h2>
        <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse: collapse;">
            <tr style="background:#f5f5f5;">
                <td><strong>Item</strong></td>
                <td align="right"><strong>Current</strong></td>
                <td align="right"><strong>Prev{{ isset($prevStartDate) && isset($prevEndDate) ? ' ('.$prevStartDate.' to '.$prevEndDate.')' : '' }}</strong></td>
            </tr>
            <tr>
                <td>Revenue</td>
                <td align="right">TZS {{ number_format((float)($incomeStatement['revenue'] ?? 0), 2) }}</td>
                <td align="right">TZS {{ number_format((float)($prevIncomeStatement['revenue'] ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Cost of Goods Sold</td>
                <td align="right">TZS {{ number_format((float)($incomeStatement['cogs'] ?? 0), 2) }}</td>
                <td align="right">TZS {{ number_format((float)($prevIncomeStatement['cogs'] ?? 0), 2) }}</td>
            </tr>
            <tr style="background:#eef7ff;">
                <td><strong>Gross Profit</strong></td>
                <td align="right"><strong>TZS {{ number_format((float)($incomeStatement['gross_profit'] ?? 0), 2) }}</strong></td>
                <td align="right"><strong>TZS {{ number_format((float)($prevIncomeStatement['gross_profit'] ?? 0), 2) }}</strong></td>
            </tr>
            <tr>
                <td>Operating Expenses</td>
                <td align="right">TZS {{ number_format((float)($incomeStatement['expenses'] ?? 0), 2) }}</td>
                <td align="right">TZS {{ number_format((float)($prevIncomeStatement['expenses'] ?? 0), 2) }}</td>
            </tr>
            <tr style="background:#eafaf1;">
                <td><strong>Net Profit</strong></td>
                <td align="right"><strong>TZS {{ number_format((float)($incomeStatement['net_profit'] ?? 0), 2) }}</strong></td>
                <td align="right"><strong>TZS {{ number_format((float)($prevIncomeStatement['net_profit'] ?? 0), 2) }}</strong></td>
            </tr>
        </table>
        <p class="p" style="margin-top:8px;">
            The profit and loss statement explains performance over the period. Revenue reflects invoiced income; Cost of Goods Sold tracks the
            direct costs attributed to sales (based on COGS-designated accounts); Operating Expenses include administrative and selling costs.
            Net Profit summarizes overall profitability after operating costs. Variances are typically driven by volume and pricing on the revenue side,
            product mix and purchasing on COGS, and cost controls within operating expenses.
        </p>
        @if(!empty($incomeStatementDetailed))
        <h3>Detailed Profit & Loss</h3>
        <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse: collapse;">
            @php 
                $revTotalDtl = 0; $cogsTotalDtl = 0; $expTotalDtl = 0; 
                $revTotalDtlPrev = 0; $cogsTotalDtlPrev = 0; $expTotalDtlPrev = 0;
                
                // Helper function to find previous amount by account ID
                $findPrevIncomeAmount = function($category, $mainGroup, $fsli, $accountId, $prevData) {
                    if (!isset($prevData[$category]['main_groups'][$mainGroup]['fslis'][$fsli]['accounts'])) return 0;
                    foreach ($prevData[$category]['main_groups'][$mainGroup]['fslis'][$fsli]['accounts'] as $prevAcc) {
                        if ($prevAcc['account_id'] == $accountId) {
                            return (float)$prevAcc['amount'];
                        }
                    }
                    return 0;
                };
            @endphp
            <tr style="background:#f9fafb;"><td colspan="3"><strong>Revenue</strong></td></tr>
            @if(isset($incomeStatementDetailed['revenue']['main_groups']))
                @foreach($incomeStatementDetailed['revenue']['main_groups'] as $mainGroupName => $mainGroup)
                    <tr style="background:#f5f5f5;"><td colspan="3"><strong>{{ $mainGroupName }}</strong></td></tr>
                    @if(isset($mainGroup['fslis']))
                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                            <tr style="background:#fcfcfc;"><td colspan="3"><em>{{ $fsliName }}</em></td></tr>
                            @if(isset($fsli['accounts']))
                                @foreach($fsli['accounts'] as $acc)
                                    @php 
                                        $amount = (float)$acc['amount'];
                                        $revTotalDtl += $amount;
                                        $prevAmt = $findPrevIncomeAmount('revenue', $mainGroupName, $fsliName, $acc['account_id'], $prevIncomeStatementDetailed ?? []);
                                        $revTotalDtlPrev += $prevAmt;
                                        $accountLabel = ($acc['account_code'] ?? '') ? ($acc['account_code'] . ' - ' . $acc['account_name']) : $acc['account_name'];
                                    @endphp
                                    <tr>
                                        <td style="padding-left: 20px;">{{ $accountLabel }}</td>
                                        <td align="right">TZS {{ number_format($amount, 2) }}</td>
                                        <td align="right">TZS {{ number_format($prevAmt, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                            @if(isset($fsli['total']))
                                <tr style="background:#f9f9f9;">
                                    <td style="padding-left: 10px;"><em>Total {{ $fsliName }}</em></td>
                                    <td align="right"><em>TZS {{ number_format($fsli['total'], 2) }}</em></td>
                                    <td align="right"><em>TZS {{ number_format($prevIncomeStatementDetailed['revenue']['main_groups'][$mainGroupName]['fslis'][$fsliName]['total'] ?? 0, 2) }}</em></td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                    @if(isset($mainGroup['total']))
                        <tr style="background:#f0f0f0;">
                            <td style="padding-left: 5px;"><strong>Total {{ $mainGroupName }}</strong></td>
                            <td align="right"><strong>TZS {{ number_format($mainGroup['total'], 2) }}</strong></td>
                            <td align="right"><strong>TZS {{ number_format($prevIncomeStatementDetailed['revenue']['main_groups'][$mainGroupName]['total'] ?? 0, 2) }}</strong></td>
                        </tr>
                    @endif
                @endforeach
            @endif
            <tr style="background:#eef7ff;"><td><strong>Total Revenue</strong></td><td align="right"><strong>TZS {{ number_format($revTotalDtl, 2) }}</strong></td><td align="right"><strong>TZS {{ number_format($revTotalDtlPrev, 2) }}</strong></td></tr>
            <tr style="background:#f9fafb;"><td colspan="3"><strong>Cost of Goods Sold</strong></td></tr>
            @if(isset($incomeStatementDetailed['cogs']['main_groups']) && !empty($incomeStatementDetailed['cogs']['main_groups']))
                @foreach($incomeStatementDetailed['cogs']['main_groups'] as $mainGroupName => $mainGroup)
                    <tr style="background:#f5f5f5;"><td colspan="3"><strong>{{ $mainGroupName }}</strong></td></tr>
                    @if(isset($mainGroup['fslis']))
                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                            <tr style="background:#fcfcfc;"><td colspan="3"><em>{{ $fsliName }}</em></td></tr>
                            @if(isset($fsli['accounts']))
                                @foreach($fsli['accounts'] as $acc)
                                    @php 
                                        $amount = (float)$acc['amount'];
                                        $cogsTotalDtl += $amount;
                                        $prevAmt = $findPrevIncomeAmount('cogs', $mainGroupName, $fsliName, $acc['account_id'], $prevIncomeStatementDetailed ?? []);
                                        $cogsTotalDtlPrev += $prevAmt;
                                        $accountLabel = ($acc['account_code'] ?? '') ? ($acc['account_code'] . ' - ' . $acc['account_name']) : $acc['account_name'];
                                    @endphp
                                    <tr>
                                        <td style="padding-left: 20px;">{{ $accountLabel }}</td>
                                        <td align="right">TZS {{ number_format($amount, 2) }}</td>
                                        <td align="right">TZS {{ number_format($prevAmt, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                            @if(isset($fsli['total']))
                                <tr style="background:#f9f9f9;">
                                    <td style="padding-left: 10px;"><em>Total {{ $fsliName }}</em></td>
                                    <td align="right"><em>TZS {{ number_format($fsli['total'], 2) }}</em></td>
                                    <td align="right"><em>TZS {{ number_format($prevIncomeStatementDetailed['cogs']['main_groups'][$mainGroupName]['fslis'][$fsliName]['total'] ?? 0, 2) }}</em></td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                    @if(isset($mainGroup['total']))
                        <tr style="background:#f0f0f0;">
                            <td style="padding-left: 5px;"><strong>Total {{ $mainGroupName }}</strong></td>
                            <td align="right"><strong>TZS {{ number_format($mainGroup['total'], 2) }}</strong></td>
                            <td align="right"><strong>TZS {{ number_format($prevIncomeStatementDetailed['cogs']['main_groups'][$mainGroupName]['total'] ?? 0, 2) }}</strong></td>
                        </tr>
                    @endif
                @endforeach
            @else
                <tr><td colspan="3" style="text-align:center; color:#999; padding:12px;">No Cost of Goods Sold accounts found</td></tr>
            @endif
            <tr style="background:#eef7ff;"><td><strong>Total COGS</strong></td><td align="right"><strong>TZS {{ number_format($cogsTotalDtl, 2) }}</strong></td><td align="right"><strong>TZS {{ number_format($cogsTotalDtlPrev, 2) }}</strong></td></tr>
            @php $grossProfitDtl = $revTotalDtl - $cogsTotalDtl; $grossProfitDtlPrev = $revTotalDtlPrev - $cogsTotalDtlPrev; @endphp
            <tr style="background:#eef7ff;"><td><strong>Gross Profit</strong></td><td align="right"><strong>TZS {{ number_format($grossProfitDtl, 2) }}</strong></td><td align="right"><strong>TZS {{ number_format($grossProfitDtlPrev, 2) }}</strong></td></tr>
            <tr style="background:#f9fafb;"><td colspan="3"><strong>Operating Expenses</strong></td></tr>
            @if(isset($incomeStatementDetailed['expenses']['main_groups']))
                @foreach($incomeStatementDetailed['expenses']['main_groups'] as $mainGroupName => $mainGroup)
                    <tr style="background:#f5f5f5;"><td colspan="3"><strong>{{ $mainGroupName }}</strong></td></tr>
                    @if(isset($mainGroup['fslis']))
                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                            <tr style="background:#fcfcfc;"><td colspan="3"><em>{{ $fsliName }}</em></td></tr>
                            @if(isset($fsli['accounts']))
                                @foreach($fsli['accounts'] as $acc)
                                    @php 
                                        $amount = (float)$acc['amount'];
                                        $expTotalDtl += $amount;
                                        $prevAmt = $findPrevIncomeAmount('expenses', $mainGroupName, $fsliName, $acc['account_id'], $prevIncomeStatementDetailed ?? []);
                                        $expTotalDtlPrev += $prevAmt;
                                        $accountLabel = ($acc['account_code'] ?? '') ? ($acc['account_code'] . ' - ' . $acc['account_name']) : $acc['account_name'];
                                    @endphp
                                    <tr>
                                        <td style="padding-left: 20px;">{{ $accountLabel }}</td>
                                        <td align="right">TZS {{ number_format($amount, 2) }}</td>
                                        <td align="right">TZS {{ number_format($prevAmt, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                            @if(isset($fsli['total']))
                                <tr style="background:#f9f9f9;">
                                    <td style="padding-left: 10px;"><em>Total {{ $fsliName }}</em></td>
                                    <td align="right"><em>TZS {{ number_format($fsli['total'], 2) }}</em></td>
                                    <td align="right"><em>TZS {{ number_format($prevIncomeStatementDetailed['expenses']['main_groups'][$mainGroupName]['fslis'][$fsliName]['total'] ?? 0, 2) }}</em></td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                    @if(isset($mainGroup['total']))
                        <tr style="background:#f0f0f0;">
                            <td style="padding-left: 5px;"><strong>Total {{ $mainGroupName }}</strong></td>
                            <td align="right"><strong>TZS {{ number_format($mainGroup['total'], 2) }}</strong></td>
                            <td align="right"><strong>TZS {{ number_format($prevIncomeStatementDetailed['expenses']['main_groups'][$mainGroupName]['total'] ?? 0, 2) }}</strong></td>
                        </tr>
                    @endif
                @endforeach
            @endif
             <tr style="background:#eef7ff;"><td><strong>Total Operating Expenses</strong></td><td align="right"><strong>TZS {{ number_format($expTotalDtl, 2) }}</strong></td><td align="right"><strong>TZS {{ number_format($expTotalDtlPrev, 2) }}</strong></td></tr>
             @php $npDtl = $revTotalDtl - $cogsTotalDtl - $expTotalDtl; $npDtlPrev = $revTotalDtlPrev - $cogsTotalDtlPrev - $expTotalDtlPrev; $npBg = $npDtl >= 0 ? '#eafaf1' : '#fdecea'; @endphp
             <tr style="background: {{ $npBg }};"><td><strong>Net Profit/Loss</strong></td><td align="right"><strong>TZS {{ number_format($npDtl, 2) }}</strong></td><td align="right"><strong>TZS {{ number_format($npDtlPrev, 2) }}</strong></td></tr>

        </table>
        @endif
    </div>

    <div class="section">
        <h2>Methodology</h2>
        <p class="p">
            Metrics are computed from the General Ledger (GL) and operational ledgers within the reporting window ({{ $startDate }} to {{ $endDate }}),
            contrasted with an immediately preceding period of equal length. Revenue and expense figures are derived from GL class mappings;
            cash flow from receipts and payments; receivables and payables from outstanding customer invoices and supplier bills where available;
            inventory balances from asset-class GL accounts containing “inventory”. Ratios are based on period activity and balances as described
            in the Definitions section. All values are branch-scoped if a branch is selected, otherwise consolidated across permitted branches.
        </p>
    </div>

    <div class="section">
        <h2>Data Sources & Scope</h2>
        <ul class="list">
            <li>General Ledger transactions (class: Revenue, Expenses, Assets for inventory, and COGS-related accounts)</li>
            <li>Sales invoices and customer receivables (balance due)</li>
            <li>Supplier bills/payables (balance due), if maintained in the system</li>
            <li>Cash receipts and payments journals for operating cash flows</li>
            <li>Computed KPIs reflect configured branches and the selected period</li>
        </ul>
    </div>

    <div class="section">
        <h2>Definitions & Formulas</h2>
        <h3 style="font-size: {{ $baseFontSize + 1 }}px; margin-top: 8px; color: {{ $accentColor }};">Core Financial</h3>
        <ul class="list">
            <li><strong>Revenue</strong>: GL credits minus debits for income/revenue classes.</li>
            <li><strong>Expenses</strong>: GL debits minus credits for expense classes.</li>
            <li><strong>Cash Flow</strong>: Receipts minus payments for the period.</li>
            <li><strong>Net Profit</strong>: Revenue minus Expenses.</li>
            <li><strong>Gross Profit Margin</strong>: (Revenue − COGS) ÷ Revenue × 100.</li>
            <li><strong>Net Profit Margin</strong>: Net Profit ÷ Revenue × 100.</li>
            <li><strong>Expense Ratio</strong>: Expenses ÷ Revenue × 100.</li>
            <li><strong>DSO (Debtors Collection Period)</strong>: Receivables ÷ (Revenue per day).</li>
            <li><strong>DPO (Creditors Payment Period)</strong>: Accounts Payable ÷ (COGS per day).</li>
            <li><strong>DIO (Inventory Holding Period)</strong>: Inventory Balance ÷ (COGS per day).</li>
        </ul>
        <h3 style="font-size: {{ $baseFontSize + 1 }}px; margin-top: 12px; color: {{ $accentColor }};">Liquidity & Solvency</h3>
        <ul class="list">
            <li><strong>Current Ratio</strong>: Current Assets ÷ Current Liabilities.</li>
            <li><strong>Quick Ratio (Acid Test)</strong>: (Current Assets − Inventory) ÷ Current Liabilities.</li>
            <li><strong>Cash Ratio</strong>: Cash ÷ Current Liabilities.</li>
            <li><strong>Debt-to-Equity Ratio</strong>: Total Liabilities ÷ Shareholders' Equity.</li>
        </ul>
        <h3 style="font-size: {{ $baseFontSize + 1 }}px; margin-top: 12px; color: {{ $accentColor }};">Efficiency / Activity</h3>
        <ul class="list">
            <li><strong>Asset Turnover Ratio</strong>: Revenue ÷ Total Assets.</li>
            <li><strong>Inventory Turnover Ratio</strong>: Cost of Goods Sold ÷ Average Inventory.</li>
            <li><strong>Receivables Turnover Ratio</strong>: Revenue ÷ Average Receivables.</li>
            <li><strong>Payables Turnover Ratio</strong>: Cost of Goods Sold ÷ Average Payables.</li>
        </ul>
        <h3 style="font-size: {{ $baseFontSize + 1 }}px; margin-top: 12px; color: {{ $accentColor }};">Profitability & Return</h3>
        <ul class="list">
            <li><strong>Return on Assets (ROA)</strong>: Net Profit ÷ Total Assets × 100.</li>
            <li><strong>Return on Equity (ROE)</strong>: Net Profit ÷ Equity × 100.</li>
            <li><strong>Operating Profit Margin</strong>: (Operating Profit ÷ Revenue) × 100.</li>
            <li><strong>EBITDA Margin</strong>: (EBITDA ÷ Revenue) × 100.</li>
        </ul>
        <h3 style="font-size: {{ $baseFontSize + 1 }}px; margin-top: 12px; color: {{ $accentColor }};">Growth</h3>
        <ul class="list">
            <li><strong>Revenue Growth Rate</strong>: ((Current Revenue − Previous Revenue) ÷ Previous Revenue) × 100.</li>
            <li><strong>Net Profit Growth Rate</strong>: ((Current Net Profit − Previous Net Profit) ÷ Previous Net Profit) × 100.</li>
            <li><strong>Expense Growth Rate</strong>: ((Current Expenses − Previous Expenses) ÷ Previous Expenses) × 100.</li>
        </ul>
        <h3 style="font-size: {{ $baseFontSize + 1 }}px; margin-top: 12px; color: {{ $accentColor }};">Cash Flow Health</h3>
        <ul class="list">
            <li><strong>Operating Cash Flow Ratio</strong>: Operating Cash Flow ÷ Current Liabilities.</li>
            <li><strong>Free Cash Flow (FCF)</strong>: Operating Cash Flow − Capital Expenditure.</li>
            <li><strong>Cash Conversion Cycle</strong>: Inventory Days + Receivables Days − Payables Days.</li>
        </ul>
    </div>

    <div class="section">
        <h2>Management Commentary</h2>
        @php
            // Helper function to generate KPI-specific commentary
            $generateCommentary = function($k) {
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
                
                // For expense-related KPIs, invert the interpretation (increase is bad, decrease is good)
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
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "{$label} {$verb} by {$changeAbs}%, from {$prevFmt} to {$currFmt}";
                            if ($isDecrease) {
                                $commentary .= ", mainly reflecting reduced customer orders and possibly lower pricing during the period. The decline suggests weaker demand or seasonal sales fluctuation.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Management should assess key client accounts and explore new marketing initiatives or pricing adjustments to restore sales growth.";
                            } elseif ($isIncrease) {
                                $commentary .= ", reflecting strong sales performance and possibly improved market conditions or pricing strategies.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Sustain this growth momentum by maintaining customer relationships and exploring opportunities to expand market share.";
                            } else {
                                $commentary .= ", indicating stable revenue generation.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Focus on growth initiatives while maintaining current revenue levels.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>{$label} stands at {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish baseline metrics and monitor trends going forward.";
                        }
                        break;
                        
                    case 'expenses':
                        if ($hasPrev) {
                            $verb = $isDecrease ? 'reduced' : ($isIncrease ? 'increased' : 'remained stable');
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Total {$label} {$verb}";
                            if ($isDecrease) {
                                $commentary .= " by {$changeAbs}%, from {$prevFmt} to {$currFmt}, reflecting effective cost containment measures and operational streamlining.";
                                $commentary .= "<br/><strong>Recommendation:</strong> While this decline positively impacts profitability, management should ensure essential operational activities and service delivery are not compromised.";
                            } elseif ($isIncrease) {
                                $commentary .= " by {$changeAbs}%, from {$prevFmt} to {$currFmt}, indicating higher operational costs or expanded business activities.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review expense categories to identify areas for optimization and ensure cost increases are justified by corresponding revenue growth.";
                            } else {
                                $commentary .= " from {$prevFmt} to {$currFmt}, indicating stable cost management.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring expenses while seeking opportunities for efficiency improvements.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Total {$label} are {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish cost benchmarks and monitor spending patterns.";
                        }
                        break;
                        
                    case 'net_profit':
                        if ($hasPrev) {
                            $wasLoss = $prev < 0;
                            $isLoss = $curr < 0;
                            $commentary = "<strong>{$label}</strong><br/>";
                            if ($wasLoss && !$isLoss) {
                                $commentary .= "Net profit improved from a loss of {$prevFmt} to a profit of {$currFmt}, a remarkable {$changeAbs}% turnaround. This improvement is largely attributed to lower expenses and tighter financial discipline.";
                                $commentary .= "<br/><strong>Recommendation:</strong> The business should sustain cost efficiency measures while focusing on rebuilding top-line growth to maintain healthy profitability margins.";
                            } elseif ($isIncrease || $isDecrease) {
                                $verb = $isIncrease ? 'improved' : 'declined';
                                $commentary .= "Net profit {$verb} by {$changeAbs}%, from {$prevFmt} to {$currFmt}";
                                if ($isIncrease) {
                                    $commentary .= ", reflecting stronger operational performance and improved cost management.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Maintain profitability by balancing revenue growth with cost efficiency.";
                                } else {
                                    $commentary .= ", indicating challenges in maintaining profit margins.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Review revenue streams and cost structures to restore profitability.";
                                }
                            } else {
                                $commentary .= "Net profit changed from {$prevFmt} to {$currFmt} ({$changeAbs}% change).";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring profitability trends and adjust strategies as needed.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Net profit is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish profitability targets and track performance against goals.";
                        }
                        break;
                        
                    case 'cash_flow':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            if ($isDecrease) {
                                $commentary .= "Operating cash flow deteriorated from {$prevFmt} to {$currFmt}, indicating greater cash outflows. The shortfall may have been driven by delayed customer payments or higher inventory purchases.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Management should review credit collection practices and optimize working capital management to enhance liquidity.";
                            } elseif ($isIncrease) {
                                $commentary .= "Operating cash flow improved from {$prevFmt} to {$currFmt}, reflecting better cash management and timely collections.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue maintaining strong cash flow discipline and consider investing excess cash in productive assets.";
                            } else {
                                $commentary .= "Operating cash flow remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Monitor cash flow patterns and ensure adequate liquidity for operations.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Operating cash flow is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish cash flow targets and monitor liquidity regularly.";
                        }
                        break;
                        
                    case 'net_profit_margin':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Net Profit Margin ";
                            if ($isIncrease) {
                                $commentary .= "rose significantly from {$prevFmt} to {$currFmt}, reflecting a strong improvement in profitability. The business is now earning more per shilling of revenue generated.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Sustain this efficiency by maintaining cost controls and focusing on profitable products or services.";
                            } elseif ($isDecrease) {
                                $commentary .= "declined from {$prevFmt} to {$currFmt}, indicating reduced profitability relative to revenue.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review pricing strategies and cost structures to restore margin performance.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring margins and seek opportunities for improvement.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Net Profit Margin is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish margin targets and benchmark against industry standards.";
                        }
                        break;
                        
                    case 'expense_ratio':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            if ($isDecrease) {
                                $commentary .= "The expense ratio improved from {$prevFmt} to {$currFmt}, indicating that expenses now represent a smaller portion of revenue. This improvement demonstrates better cost discipline and resource utilization.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring expenditure levels to ensure sustained cost efficiency without underfunding critical functions.";
                            } elseif ($isIncrease) {
                                $commentary .= "The expense ratio increased from {$prevFmt} to {$currFmt}, suggesting that expenses are consuming a larger portion of revenue.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review expense categories and identify opportunities to optimize spending while maintaining operational effectiveness.";
                            } else {
                                $commentary .= "The expense ratio remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring the expense ratio and seek efficiency improvements.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>The expense ratio is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish expense ratio targets and monitor trends.";
                        }
                        break;
                        
                    case 'receivables':
                        if ($hasPrev && $prev != 0) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Outstanding receivables ";
                            if ($isIncrease) {
                                $commentary .= "increased from {$prevFmt} to {$currFmt}, suggesting significant credit sales and possible extension of payment terms.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Introduce robust credit control policies and ensure timely follow-up to prevent overdue debts from escalating.";
                            } elseif ($isDecrease) {
                                $commentary .= "decreased from {$prevFmt} to {$currFmt}, reflecting improved collection efficiency.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain effective collection practices and continue monitoring receivables aging.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring receivables and ensure timely collections.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Outstanding receivables stand at {$currFmt}";
                            if ($curr > 0) {
                                $commentary .= ", suggesting credit sales and possible extension of payment terms.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Introduce robust credit control policies and ensure timely follow-up to prevent overdue debts from escalating.";
                            } else {
                                $commentary .= ".<br/><strong>Recommendation:</strong> Monitor receivables as business grows and credit terms are extended.";
                            }
                        }
                        break;
                        
                    case 'dso':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "The collection period ";
                            if ($isIncrease) {
                                $commentary .= "increased to {$currFmt}, implying that it takes longer to convert sales into cash. This may indicate relaxed credit terms or slower customer payments.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Aim to reduce the collection period to below 45 days by strengthening customer credit assessments and enforcing payment timelines.";
                            } elseif ($isDecrease) {
                                $commentary .= "improved to {$currFmt}, indicating faster collection of receivables.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain efficient collection practices and continue monitoring payment trends.";
                            } else {
                                $commentary .= "averages {$currFmt} days.";
                                if ($curr > 45) {
                                    $commentary .= " While this reflects moderate credit terms, the cash flow strain is evident.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Aim to reduce the collection period to below 45 days by strengthening customer credit assessments.";
                                } else {
                                    $commentary .= "<br/><strong>Recommendation:</strong> Continue maintaining efficient collection practices.";
                                }
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>The collection period averages {$currFmt}";
                            if ($curr > 45) {
                                $commentary .= ", implying that it takes over a month to convert sales into cash. While this reflects moderate credit terms, the cash flow strain is evident.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Aim to reduce the collection period to below 45 days by strengthening customer credit assessments and enforcing payment timelines.";
                            } else {
                                $commentary .= " days.<br/><strong>Recommendation:</strong> Continue monitoring collection efficiency.";
                            }
                        }
                        break;
                        
                                       case 'gross_profit_margin':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            if ($isIncrease) {
                                $commentary .= "Gross profit margin improved from {$prevFmt} to {$currFmt}, showing significant progress in controlling production or procurement costs. This might also reflect better supplier negotiations or product mix optimization.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Management should ensure that the improvement is sustainable and not driven by temporary cost advantages.";
                            } elseif ($isDecrease) {
                                $commentary .= "Gross profit margin declined from {$prevFmt} to {$currFmt}, indicating higher costs of goods sold relative to revenue.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review supplier relationships and product pricing to restore margin performance.";
                            } else {
                                $commentary .= "Gross profit margin remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring margin trends and seek opportunities for improvement.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Gross profit margin is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish margin targets and monitor cost efficiency.";
                        }
                        break;
                        
                    case 'dio':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Inventory holding period ";
                            if ($isIncrease) {
                                $commentary .= "increased to {$currFmt}, suggesting overstocking or very slow-moving inventory. This poses a risk of obsolescence and ties up cash unnecessarily.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Conduct a stock aging analysis and implement an inventory reduction strategy to improve turnover and liquidity.";
                            } elseif ($isDecrease) {
                                $commentary .= "improved to {$currFmt}, indicating faster inventory turnover and better stock management.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain efficient inventory levels while ensuring adequate stock availability.";
                            } else {
                                $commentary .= "averages {$currFmt} days.";
                                if ($curr > 90) {
                                    $commentary .= " This suggests slow-moving inventory that may require attention.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Review inventory levels and consider strategies to improve turnover.";
                                } else {
                                    $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring inventory efficiency.";
                                }
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Inventory holding period is {$currFmt}";
                            if ($curr > 90) {
                                $commentary .= ", suggesting overstocking or very slow-moving inventory. This poses a risk of obsolescence and ties up cash unnecessarily.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Conduct a stock aging analysis and implement an inventory reduction strategy to improve turnover and liquidity.";
                            } else {
                                $commentary .= " days.<br/><strong>Recommendation:</strong> Monitor inventory levels and turnover efficiency.";
                            }
                        }
                        break;
                        
                    case 'dpo':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Creditors payment period ";
                            if ($isIncrease) {
                                $commentary .= "increased to {$currFmt}, indicating longer payment terms with suppliers. While this may provide cash flow flexibility, it could strain supplier relationships.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Balance payment terms to maintain supplier goodwill while optimizing cash flow.";
                            } elseif ($isDecrease) {
                                $commentary .= "decreased to {$currFmt}, suggesting faster payment to suppliers or shorter payment terms.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Consider negotiating longer payment terms where possible to improve cash flow management, while maintaining supplier relationships.";
                            } else {
                                $commentary .= "remains at {$currFmt}";
                                if ($curr == 0) {
                                    $commentary .= ", implying that suppliers are paid immediately upon purchase. While this avoids liabilities, it may limit short-term liquidity flexibility.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Negotiate longer payment terms with suppliers where possible to improve cash flow management.";
                                } else {
                                    $commentary .= " days.<br/><strong>Recommendation:</strong> Continue monitoring payment terms and supplier relationships.";
                                }
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Creditors payment period remains at {$currFmt}";
                            if ($curr == 0) {
                                $commentary .= ", implying that suppliers are paid immediately upon purchase. While this avoids liabilities, it may limit short-term liquidity flexibility.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Negotiate longer payment terms with suppliers where possible to improve cash flow management.";
                            } else {
                                $commentary .= " days.<br/><strong>Recommendation:</strong> Monitor payment terms and optimize supplier relationships.";
                            }
                        }
                        break;
                        
                    case 'current_ratio':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "The current ratio ";
                            if ($isDecrease) {
                                $commentary .= "declined from {$prevFmt} to {$currFmt}";
                                if ($curr >= 1) {
                                    $commentary .= ", indicating slightly reduced liquidity but still showing a strong ability to meet short-term obligations.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Maintain this level by managing working capital prudently and avoiding excessive cash holdings that could otherwise be invested.";
                                } else {
                                    $commentary .= ", indicating potential liquidity concerns. The ratio below 1 suggests current assets may not fully cover current liabilities.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Improve liquidity by accelerating collections, reducing inventory, or extending payment terms.";
                                }
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger liquidity position.";
                                $commentary .= "<br/><strong>Recommendation:</strong> While strong liquidity is positive, ensure excess assets are efficiently deployed for growth.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}";
                                if ($curr >= 1) {
                                    $commentary .= ", indicating adequate liquidity coverage.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Continue maintaining prudent working capital management.";
                                } else {
                                    $commentary .= ", but below the ideal threshold of 1.0.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Focus on improving liquidity to ensure ability to meet short-term obligations.";
                                }
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>The current ratio is {$currFmt}";
                            if ($curr >= 1) {
                                $commentary .= ", indicating a strong ability to meet short-term obligations.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain this level by managing working capital prudently.";
                            } else {
                                $commentary .= ", which is below the ideal threshold of 1.0. This suggests potential liquidity concerns.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Improve liquidity by accelerating collections and managing working capital more effectively.";
                            }
                        }
                        break;
                        
                    case 'quick_ratio':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "The quick ratio ";
                            if ($isDecrease) {
                                $commentary .= "fell from {$prevFmt} to {$currFmt}, reflecting reduced liquid assets relative to current liabilities. This could be linked to slower receivable collections.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Accelerate collections and monitor short-term asset liquidity to ensure coverage of immediate liabilities.";
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger immediate liquidity position.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain strong liquidity while ensuring efficient use of liquid assets.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}";
                                if ($curr >= 1) {
                                    $commentary .= ", indicating adequate immediate liquidity.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring quick assets and current liabilities.";
                                } else {
                                    $commentary .= ", but below 1.0, suggesting limited immediate liquidity.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Improve quick assets or reduce current liabilities to strengthen immediate liquidity.";
                                }
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>The quick ratio is {$currFmt}";
                            if ($curr >= 1) {
                                $commentary .= ", indicating strong immediate liquidity.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring quick assets and current liabilities.";
                            } else {
                                $commentary .= ", which is below 1.0, suggesting limited immediate liquidity without relying on inventory.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Improve quick assets or reduce current liabilities to strengthen immediate liquidity.";
                            }
                        }
                        break;
                        
                    case 'cash_ratio':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Cash ratio ";
                            if ($isDecrease) {
                                $commentary .= "declined from {$prevFmt} to {$currFmt}";
                                if ($curr < 0) {
                                    $commentary .= ", indicating that cash balances are insufficient to cover short-term obligations.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Strengthen cash flow planning and prioritize inflows before committing to new cash outlays.";
                                } else {
                                    $commentary .= ", indicating reduced cash coverage of current liabilities.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Improve cash reserves while maintaining efficient cash management.";
                                }
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger cash position.";
                                $commentary .= "<br/><strong>Recommendation:</strong> While strong cash reserves are positive, ensure excess cash is efficiently deployed.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}";
                                if ($curr < 0) {
                                    $commentary .= ". However, negative cash ratio indicates insufficient cash to cover current liabilities.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Strengthen cash flow planning and prioritize cash inflows.";
                                } else {
                                    $commentary .= ".<br/><strong>Recommendation:</strong> Continue monitoring cash reserves and liquidity.";
                                }
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Cash ratio is {$currFmt}";
                            if ($curr < 0) {
                                $commentary .= ", indicating that cash balances are insufficient to cover short-term obligations.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Strengthen cash flow planning and prioritize inflows before committing to new cash outlays.";
                            } else {
                                $commentary .= ".<br/><strong>Recommendation:</strong> Monitor cash reserves and ensure adequate liquidity.";
                            }
                        }
                        break;
                        
                    case 'debt_to_equity':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "The ratio ";
                            if ($isIncrease) {
                                $commentary .= "increased from {$prevFmt} to {$currFmt}, showing a rise in leverage. ";
                                if ($curr < 1) {
                                    $commentary .= "The business remains conservatively financed, but continued borrowing could affect solvency.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Any additional financing should be directed towards productive investments that generate positive returns.";
                                } else {
                                    $commentary .= "The business now has more debt than equity, indicating higher financial risk.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Monitor leverage levels and ensure debt is used for value-creating investments.";
                                }
                            } elseif ($isDecrease) {
                                $commentary .= "decreased from {$prevFmt} to {$currFmt}, indicating reduced leverage and stronger equity position.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain conservative leverage while ensuring adequate capital for growth opportunities.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}";
                                if ($curr < 1) {
                                    $commentary .= ", indicating conservative financing.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring leverage and consider strategic use of debt for growth.";
                                } else {
                                    $commentary .= ", but leverage remains high.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Focus on reducing debt or increasing equity to improve financial stability.";
                                }
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>The debt-to-equity ratio is {$currFmt}";
                            if ($curr < 1) {
                                $commentary .= ", indicating conservative financing.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Monitor leverage levels and consider strategic use of debt for growth.";
                            } else {
                                $commentary .= ", indicating higher leverage and financial risk.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Focus on reducing debt or increasing equity to improve financial stability.";
                            }
                        }
                        break;
                        
                    case 'asset_turnover':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "The asset turnover ratio ";
                            if ($isDecrease) {
                                $commentary .= "decreased from {$prevFmt} to {$currFmt}, suggesting that assets are generating less revenue per unit of investment.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review asset utilization efficiency and consider divesting underused assets to improve capital productivity.";
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating better utilization of assets to generate revenue.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain efficient asset utilization and continue monitoring asset productivity.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring asset efficiency and seek opportunities for improvement.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>The asset turnover ratio is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor asset utilization and ensure assets are generating adequate returns.";
                        }
                        break;
                        
                    case 'inventory_turnover':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Inventory turnover ";
                            if ($isDecrease) {
                                $commentary .= "dropped from {$prevFmt} to {$currFmt}, confirming slower stock movement consistent with the high inventory holding period.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Improve inventory planning, enhance demand forecasting, and consider promotional strategies to clear slow-moving goods.";
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating faster inventory movement and better stock management.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain efficient inventory levels while ensuring adequate stock availability.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring inventory turnover and seek opportunities for improvement.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Inventory turnover is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor inventory efficiency and ensure stock is moving at optimal rates.";
                        }
                        break;
                        
                    case 'receivables_turnover':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Receivables turnover ";
                            if ($isDecrease) {
                                $commentary .= "declined from {$prevFmt} to {$currFmt}, indicating slower collection of debts and higher reliance on credit sales.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Enforce stricter credit policies and consider early payment incentives to speed up collections.";
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating faster collection of receivables.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain effective collection practices and continue monitoring receivables efficiency.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring collection efficiency and seek improvement opportunities.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Receivables turnover is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor collection efficiency and ensure timely receivables conversion.";
                        }
                        break;
                        
                    case 'payables_turnover':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Payables turnover ";
                            if ($isDecrease) {
                                $commentary .= "declined from {$prevFmt} to {$currFmt}, suggesting slower payment of suppliers or fewer purchases relative to payables.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review payment terms and supplier relationships to optimize cash flow management.";
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating faster payment cycle or more frequent purchases.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Balance payment efficiency with cash flow optimization.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring payables management and supplier relationships.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Payables turnover is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor payables efficiency and optimize payment terms with suppliers.";
                        }
                        break;
                        
                    case 'roa':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "ROA ";
                            if ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating better profitability relative to total assets. This reflects more efficient use of resources.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue optimizing asset use and ensure investments deliver measurable returns.";
                            } elseif ($isDecrease) {
                                $commentary .= "declined from {$prevFmt} to {$currFmt}, indicating reduced profitability relative to asset base.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review asset utilization and profitability to improve returns on assets.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring asset returns and seek opportunities for improvement.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>ROA is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor asset returns and ensure efficient asset utilization.";
                        }
                        break;
                        
                    case 'roe':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "ROE ";
                            if ($isIncrease) {
                                $commentary .= "increased from {$prevFmt} to {$currFmt}, showing enhanced returns for shareholders after a prior loss. This indicates improved profitability and capital management.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain this trajectory by reinvesting profits in high-yielding initiatives.";
                            } elseif ($isDecrease) {
                                $commentary .= "declined from {$prevFmt} to {$currFmt}, indicating reduced returns for equity holders.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Focus on improving profitability and efficient capital deployment to enhance shareholder returns.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring equity returns and seek improvement opportunities.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>ROE is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor equity returns and ensure profitable use of shareholder capital.";
                        }
                        break;
                        
                    case 'operating_profit_margin':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Operating profit margin ";
                            if ($isDecrease) {
                                $commentary .= "decreased from {$prevFmt} to {$currFmt}, indicating slightly higher operating costs relative to revenue.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review administrative and overhead expenses to identify further efficiency opportunities.";
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating better operational efficiency and cost management.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain operational efficiency while continuing to optimize costs.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring operating margins and seek efficiency improvements.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Operating profit margin is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor operating efficiency and ensure sustainable margins.";
                        }
                        break;
                        
                    case 'ebitda_margin':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "EBITDA margin ";
                            if ($isDecrease) {
                                $commentary .= "also declined from {$prevFmt} to {$currFmt}, consistent with the operating profit margin trend. The business remains operationally profitable but faces slight margin compression.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Focus on maintaining core operating efficiency while managing indirect costs.";
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger operational profitability and cost efficiency.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Sustain operational efficiency and continue optimizing core business operations.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring EBITDA margins and operational efficiency.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>EBITDA margin is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor operational profitability and ensure sustainable EBITDA performance.";
                        }
                        break;
                        
                    case 'revenue_growth_rate':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Revenue growth rate of {$currFmt}";
                            if ($curr < 0) {
                                $commentary .= " highlights a contraction in business activity.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Management should revisit marketing strategies, strengthen client relationships, and diversify revenue sources.";
                            } elseif ($curr > 0) {
                                $commentary .= " indicates positive business growth and expansion.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Sustain growth momentum by maintaining customer relationships and exploring new market opportunities.";
                            } else {
                                $commentary .= " indicates stable revenue with no growth.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Focus on growth initiatives to expand business activity.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Revenue growth rate is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish growth targets and monitor revenue trends.";
                        }
                        break;
                        
                    case 'net_profit_growth_rate':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Net profit growth ";
                            if (abs($changePct) > 100) {
                                $commentary .= "surged to {$currFmt}, reflecting a strong recovery from previous losses or significant profitability improvement.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Focus on stabilizing this improvement through consistent sales performance and prudent cost management.";
                            } elseif ($curr > 0) {
                                $commentary .= "is {$currFmt}, indicating positive profit growth.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain profitability growth through balanced revenue and cost management.";
                            } else {
                                $commentary .= "is {$currFmt}, indicating declining profitability.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review revenue streams and cost structures to restore profit growth.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Net profit growth rate is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish profit growth targets and monitor trends.";
                        }
                        break;
                        
                    case 'expense_growth_rate':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Expenses ";
                            if ($isDecrease) {
                                $commentary .= "declined by {$changeAbs}%, indicating significant improvement in spending control.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain current cost efficiency measures and ensure savings are sustainable.";
                            } elseif ($isIncrease) {
                                $commentary .= "increased by {$changeAbs}%, suggesting higher operational costs or business expansion.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Review expense categories and ensure cost increases are justified by revenue growth.";
                            } else {
                                $commentary .= "remained stable, indicating controlled spending.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring expenses and seek efficiency opportunities.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Expense growth rate is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor expense trends and establish cost control measures.";
                        }
                        break;
                        
                    case 'operating_cash_flow_ratio':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Operating cash flow ratio ";
                            if ($isDecrease) {
                                $commentary .= "decreased from {$prevFmt} to {$currFmt}";
                                if ($curr < 0) {
                                    $commentary .= ", signaling weakened ability to cover current liabilities with operating cash.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Improve operational cash inflows by managing receivables and reducing unnecessary cash outflows.";
                                } else {
                                    $commentary .= ", indicating reduced cash coverage of current liabilities.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Strengthen operating cash flow and optimize working capital management.";
                                }
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating stronger ability to cover liabilities with operating cash.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain strong operating cash flow discipline.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring operating cash flow coverage.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Operating cash flow ratio is {$currFmt}";
                            if ($curr < 0) {
                                $commentary .= ", indicating insufficient operating cash to cover current liabilities.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Improve operational cash inflows and optimize working capital.";
                            } else {
                                $commentary .= " for this period.<br/><strong>Recommendation:</strong> Monitor operating cash flow coverage of liabilities.";
                            }
                        }
                        break;
                        
                    case 'free_cash_flow':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "Free cash flow ";
                            if ($isDecrease) {
                                $commentary .= "worsened from {$prevFmt} to {$currFmt}, suggesting heavy cash utilization for operational or capital purposes.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Reassess investment priorities and ensure cash reserves remain adequate for short-term needs.";
                            } elseif ($isIncrease) {
                                $commentary .= "improved from {$prevFmt} to {$currFmt}, indicating better cash generation after capital expenditures.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Consider deploying excess free cash flow in value-creating investments or debt reduction.";
                            } else {
                                $commentary .= "remained stable at {$currFmt}.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring free cash flow and optimize capital allocation.";
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>Free cash flow is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Monitor free cash flow generation and ensure adequate cash reserves.";
                        }
                        break;
                        
                    case 'cash_conversion_cycle':
                        if ($hasPrev) {
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "The cash conversion cycle ";
                            if ($isIncrease) {
                                $commentary .= "lengthened from {$prevFmt} to {$currFmt}, showing a major slowdown in converting resources into cash.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Streamline inventory management and accelerate customer collections to shorten the cycle and strengthen liquidity.";
                            } elseif ($isDecrease) {
                                $commentary .= "shortened from {$prevFmt} to {$currFmt}, indicating faster conversion of resources into cash.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Maintain efficient working capital management and continue optimizing the cash cycle.";
                            } else {
                                $commentary .= "remained stable at {$currFmt} days.";
                                if ($curr > 90) {
                                    $commentary .= " The extended cycle suggests opportunities for improvement.";
                                    $commentary .= "<br/><strong>Recommendation:</strong> Focus on reducing inventory days and receivables collection to shorten the cycle.";
                                } else {
                                    $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring the cash conversion cycle and maintain efficiency.";
                                }
                            }
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>The cash conversion cycle is {$currFmt}";
                            if ($curr > 90) {
                                $commentary .= ", showing a major slowdown in converting resources into cash.";
                                $commentary .= "<br/><strong>Recommendation:</strong> Streamline inventory management and accelerate customer collections to shorten the cycle and strengthen liquidity.";
                            } else {
                                $commentary .= " days.<br/><strong>Recommendation:</strong> Monitor the cash conversion cycle and optimize working capital management.";
                            }
                        }
                        break;
                        
                    default:
                        // Generic commentary for any KPI not specifically handled
                        if ($hasPrev) {
                            $verb = $isIncrease ? 'increased' : ($isDecrease ? 'decreased' : 'remained stable');
                            $commentary = "<strong>{$label}</strong><br/>";
                            $commentary .= "{$label} {$verb}";
                            if ($hasPrev && $prev != 0) {
                                $commentary .= " by {$changeAbs}%, from {$prevFmt} to {$currFmt}";
                            }
                            $commentary .= ". This movement reflects changes in underlying business operations.";
                            $commentary .= "<br/><strong>Recommendation:</strong> Continue monitoring this KPI and assess its impact on overall business performance.";
                        } else {
                            $commentary = "<strong>{$label}</strong><br/>{$label} is {$currFmt} for this period.<br/><strong>Recommendation:</strong> Establish baseline metrics and monitor trends going forward.";
                        }
                        break;
                }
                
                return $commentary;
            };
        @endphp
        @foreach(($kpis ?? []) as $k)
            <div class="p">
                {!! $generateCommentary($k) !!}
            </div>
        @endforeach
    </div>

     <div class="section">
        <h2>Overall Commentary Summary</h2>
        @php
            // Analyze overall business situation based on key KPIs
            $revenueKpi = collect($kpis ?? [])->firstWhere('key', 'revenue');
            $expensesKpi = collect($kpis ?? [])->firstWhere('key', 'expenses');
            $netProfitKpi = collect($kpis ?? [])->firstWhere('key', 'net_profit');
            $cashFlowKpi = collect($kpis ?? [])->firstWhere('key', 'cash_flow');
            $netProfitMarginKpi = collect($kpis ?? [])->firstWhere('key', 'net_profit_margin');
            $dsoKpi = collect($kpis ?? [])->firstWhere('key', 'dso');
            $dioKpi = collect($kpis ?? [])->firstWhere('key', 'dio');
            $currentRatioKpi = collect($kpis ?? [])->firstWhere('key', 'current_ratio');
            $revenueGrowthKpi = collect($kpis ?? [])->firstWhere('key', 'revenue_growth_rate');
            
            $revenueTrend = $revenueKpi['trend'] ?? 'flat';
            $expensesTrend = $expensesKpi['trend'] ?? 'flat';
            $netProfitTrend = $netProfitKpi['trend'] ?? 'flat';
            $cashFlowTrend = $cashFlowKpi['trend'] ?? 'flat';
            
            // For expenses, invert the trend (increase is bad, decrease is good)
            $expensesTrendAdjusted = $expensesTrend === 'up' ? 'down' : ($expensesTrend === 'down' ? 'up' : 'flat');
            
            $netProfit = (float)($netProfitKpi['value'] ?? 0);
            $netProfitMargin = (float)($netProfitMarginKpi['value'] ?? 0);
            $dso = (float)($dsoKpi['value'] ?? 0);
            $dio = (float)($dioKpi['value'] ?? 0);
            $currentRatio = (float)($currentRatioKpi['value'] ?? 0);
            $cashFlow = (float)($cashFlowKpi['value'] ?? 0);
            $revenueGrowth = $revenueGrowthKpi ? (float)($revenueGrowthKpi['value'] ?? 0) : null;
            
            // Determine overall situation
            $hasProfitabilityRecovery = $netProfitTrend === 'up' && $netProfit > 0;
            $hasStrongCostControl = $expensesTrendAdjusted === 'up'; // Decrease in expenses
            $hasCashFlowChallenge = $cashFlow < 0 || $cashFlowTrend === 'down';
            $hasReceivablesChallenge = $dso > 45 || ($dsoKpi['trend'] ?? 'flat') === 'up';
            $hasInventoryChallenge = $dio > 90 || ($dioKpi['trend'] ?? 'flat') === 'up';
            $hasLiquidityConcern = $currentRatio < 1;
            $hasRevenueGrowth = $revenueTrend === 'up' || ($revenueGrowth !== null && $revenueGrowth > 0);
            $hasRevenueDecline = $revenueTrend === 'down' || ($revenueGrowth !== null && $revenueGrowth < 0);
            
           // Build overall summary
            $summaryParts = [];
            $challenges = [];
            $strengths = [];
            $recommendations = [];
            
            // Analyze profitability
            if ($hasProfitabilityRecovery) {
                $strengths[] = "strong cost control and a major profitability recovery";
            } elseif ($netProfit > 0 && $netProfitTrend !== 'down') {
                $strengths[] = "profitable operations";
            } elseif ($netProfit < 0) {
                $challenges[] = "profitability challenges";
            }
            
            // Analyze cost control
            if ($hasStrongCostControl) {
                $strengths[] = "effective cost containment measures";
            } elseif ($expensesTrendAdjusted === 'down') {
                $challenges[] = "increasing expenses";
            }
            
            // Analyze cash flow
            if ($hasCashFlowChallenge) {
                $challenges[] = "cash flow management";
            } elseif ($cashFlow > 0 && $cashFlowTrend === 'up') {
                $strengths[] = "improving cash flow";
            }
            
            // Analyze receivables
            if ($hasReceivablesChallenge) {
                $challenges[] = "receivables collection";
            } elseif ($dso <= 45 && ($dsoKpi['trend'] ?? 'flat') === 'down') {
                $strengths[] = "efficient receivables management";
            }
            
            // Analyze inventory
            if ($hasInventoryChallenge) {
                $challenges[] = "inventory efficiency";
            } elseif ($dio <= 90 && ($dioKpi['trend'] ?? 'flat') === 'down') {
                $strengths[] = "effective inventory management";
            }
            
            // Analyze liquidity
            if ($hasLiquidityConcern) {
                $challenges[] = "liquidity discipline";
            } elseif ($currentRatio >= 1.5) {
                $strengths[] = "strong liquidity position";
            }
            
            // Analyze revenue performance
            if ($hasRevenueGrowth) {
                $strengths[] = "revenue growth momentum";
            } elseif ($hasRevenueDecline) {
                $challenges[] = "sales performance";
            }
            
            // Build recommendations based on challenges
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
            
            // Build the summary text
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
            
            // Add forward-looking statement
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
        @endphp
        <p class="p" style="text-align: justify;">
            {{ $summaryText }}
        </p>
    </div>

    <div class="section">
        <h2>Appendix</h2>
        <ul class="list">
            <li>All figures are rounded for readability and may include consolidations across multiple branches.</li>
            <li>Where prior period data is unavailable, trends default to stable and percent change to 0%.</li>
            <li>Inventory and COGS approximations rely on account naming conventions (e.g., accounts containing “Inventory”, “Cost of Goods Sold”).</li>
            <li>For audit-grade reporting, reconcile KPI outputs with detailed ledgers and trial balances.</li>
        </ul>
    </div>
    <!-- Attribution on last page only (placed at end of document content) -->
    <div class="section" style="text-align:center; color:#777; font-size: {{ max(8, $baseFontSize-2) }}px; margin-top: 12mm;">
        <span>This is a System Generated Report</span>
        — Generated by {{ $generatedBy ?? 'System' }}
        on {{ ($generatedOn ?? now())->format('Y-m-d H:i') }}
        using {{ $systemName ?? 'SmartAccounting' }}.
    </div>
    


</body>
</html>
