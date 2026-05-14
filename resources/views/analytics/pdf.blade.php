<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Analytics Dashboard - {{ $company->name ?? 'Company' }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 landscape;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18pt;
            color: #333;
        }
        
        .header .company-info {
            font-size: 10pt;
            color: #666;
            margin-top: 5px;
        }
        
        .header .period-info {
            font-size: 9pt;
            color: #666;
            margin-top: 5px;
        }
        
        .kpi-section {
            margin-bottom: 20px;
        }
        
        .kpi-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .kpi-row {
            display: table-row;
        }
        
        .kpi-card {
            display: table-cell;
            width: 12.5%;
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
            vertical-align: top;
            background: #f9f9f9;
        }
        
        .kpi-label {
            font-size: 7pt;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .kpi-value {
            font-size: 14pt;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .kpi-change {
            font-size: 8pt;
            margin-top: 5px;
        }
        
        .kpi-change.positive {
            color: #28a745;
        }
        
        .kpi-change.negative {
            color: #dc3545;
        }
        
        .chart-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .chart-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            padding-bottom: 5px;
            border-bottom: 2px solid #333;
        }
        
        .chart-container {
            border: 1px solid #ddd;
            padding: 15px;
            background: #ffffff;
        }
        
        .bar-chart {
            margin: 15px 0;
        }
        
        .bar-item {
            margin-bottom: 12px;
        }
        
        .bar-label {
            font-size: 8pt;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
        }
        
        .bar-wrapper {
            height: 20px;
            background: #e9ecef;
            border-radius: 3px;
            position: relative;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            border-radius: 3px;
            display: flex;
            align-items: center;
            padding: 0 5px;
            color: white;
            font-size: 7pt;
            font-weight: bold;
            min-width: 30px;
        }
        
        .bar-fill.revenue { background: #007bff; }
        .bar-fill.expenses { background: #dc3545; }
        .bar-fill.profit { background: #28a745; }
        .bar-fill.cash { background: #17a2b8; }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8pt;
        }
        
        .data-table th,
        .data-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .data-table th {
            background-color: #333;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        
        .data-table td {
            background-color: #fff;
        }
        
        .data-table tr:nth-child(even) td {
            background-color: #f9f9f9;
        }
        
        .data-table td.text-right {
            text-align: right;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
        
        .two-column {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .two-column .column {
            display: table-cell;
            width: 50%;
            padding: 0 10px;
            vertical-align: top;
        }
        
        .trend-arrow {
            font-weight: bold;
            font-size: 10pt;
        }
        
        .trend-arrow.up {
            color: #28a745;
        }
        
        .trend-arrow.down {
            color: #dc3545;
        }
        
        .summary-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .summary-box .label {
            font-size: 8pt;
            color: #666;
            margin-bottom: 3px;
        }
        
        .summary-box .value {
            font-size: 12pt;
            font-weight: bold;
            color: #333;
        }
        
        .revenue-trend-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        
        .revenue-trend-table th,
        .revenue-trend-table td {
            padding: 6px;
            text-align: right;
            border: 1px solid #ddd;
        }
        
        .revenue-trend-table th {
            background: #333;
            color: white;
            text-align: center;
        }
        
        .revenue-trend-table td:first-child {
            text-align: left;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Analytics Dashboard</h1>
        <div class="company-info">
            <strong>{{ $company->name ?? 'Company' }}</strong>
            @if($branch)
                | Branch: {{ $branch->name }}
            @endif
        </div>
        <div class="period-info">
            Period: {{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}
            @if($prev_start_date && $prev_end_date)
                | Previous: {{ \Carbon\Carbon::parse($prev_start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($prev_end_date)->format('M d, Y') }}
            @endif
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-section">
        <h2 style="font-size: 14pt; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 5px;">Key Performance Indicators</h2>
        <div class="kpi-grid">
            <div class="kpi-row">
                @php
                    $kpiList = [
                        'revenue' => 'Revenue',
                        'expenses' => 'Expenses',
                        'net_profit' => 'Net Profit',
                        'cash' => 'Cash',
                        'gross_profit_margin' => 'Gross Profit Margin (%)',
                        'net_profit_margin' => 'Net Profit Margin (%)',
                        'expense_ratio' => 'Expense Ratio (%)',
                        'current_ratio' => 'Current Ratio',
                    ];
                    $kpiCount = 0;
                    $maxRevenue = 0;
                    foreach($kpiList as $key => $label) {
                        if(isset($kpis[$key]) && ($key === 'revenue' || $key === 'expenses' || $key === 'net_profit')) {
                            $maxRevenue = max($maxRevenue, abs($kpis[$key]['current'] ?? 0));
                        }
                    }
                @endphp
                @foreach($kpiList as $key => $label)
                    @if(isset($kpis[$key]))
                        @php
                            $kpi = $kpis[$key];
                            $current = $kpi['current'] ?? 0;
                            $previous = $kpi['previous'] ?? null;
                            $change = $kpi['change_percent'] ?? 0;
                            $trend = $kpi['trend'] ?? 'flat';
                            
                            // Format value based on type
                            if (strpos($label, '%') !== false || strpos($key, 'ratio') !== false || strpos($key, 'margin') !== false) {
                                $value = number_format($current, 2) . '%';
                            } else {
                                $value = 'TZS ' . number_format($current, 2);
                            }
                            
                            // Format previous if exists
                            $prevValue = '';
                            if ($previous !== null) {
                                if (strpos($label, '%') !== false || strpos($key, 'ratio') !== false || strpos($key, 'margin') !== false) {
                                    $prevValue = number_format($previous, 2) . '%';
                                } else {
                                    $prevValue = 'TZS ' . number_format($previous, 2);
                                }
                            }
                            
                            $changeClass = $trend === 'up' ? 'positive' : ($trend === 'down' ? 'negative' : '');
                            $changeIcon = $trend === 'up' ? '▲' : ($trend === 'down' ? '▼' : '→');
                            $arrowColor = $trend === 'up' ? '#28a745' : ($trend === 'down' ? '#dc3545' : '#666');
                            $kpiCount++;
                        @endphp
                        <div class="kpi-card">
                            <div class="kpi-label">{{ $label }}</div>
                            <div class="kpi-value">{{ $value }}</div>
                            @if($previous !== null)
                                <div class="kpi-change {{ $changeClass }}">
                                    <span style="color: {{ $arrowColor }}; font-weight: bold;">{{ $changeIcon }}</span>
                                    <span>{{ number_format(abs($change), 1) }}%</span>
                                </div>
                                <div style="font-size: 7pt; color: #999; margin-top: 3px;">
                                    Prev: {{ $prevValue }}
                                </div>
                            @endif
                        </div>
                        @if($kpiCount % 4 === 0)
                            </div><div class="kpi-row">
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Revenue Trend Chart -->
    <div class="chart-section">
        <div class="chart-title">Revenue vs Expenses vs Profit</div>
        <div class="chart-container">
            @php
                $revenueTrend = $charts['revenue_trend'] ?? [];
                $totalRevenue = array_sum(array_column($revenueTrend, 'revenue'));
                $totalExpenses = array_sum(array_column($revenueTrend, 'expenses'));
                $totalProfit = array_sum(array_column($revenueTrend, 'profit'));
                $maxValue = max(abs($totalRevenue), abs($totalExpenses), abs($totalProfit));
            @endphp
            
            @if($maxValue > 0)
                <div class="bar-chart">
                    <div class="bar-item">
                        <div class="bar-label">
                            <span><strong>Revenue</strong></span>
                            <span>TZS {{ number_format($totalRevenue, 2) }}</span>
                        </div>
                        <div class="bar-wrapper">
                            <div class="bar-fill revenue" style="width: {{ ($totalRevenue / $maxValue) * 100 }}%;">
                                @if(($totalRevenue / $maxValue) * 100 > 15)
                                    TZS {{ number_format($totalRevenue, 0) }}
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="bar-item">
                        <div class="bar-label">
                            <span><strong>Expenses</strong></span>
                            <span>TZS {{ number_format($totalExpenses, 2) }}</span>
                        </div>
                        <div class="bar-wrapper">
                            <div class="bar-fill expenses" style="width: {{ ($totalExpenses / $maxValue) * 100 }}%;">
                                @if(($totalExpenses / $maxValue) * 100 > 15)
                                    TZS {{ number_format($totalExpenses, 0) }}
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="bar-item">
                        <div class="bar-label">
                            <span><strong>Net Profit</strong></span>
                            <span>TZS {{ number_format($totalProfit, 2) }}</span>
                        </div>
                        <div class="bar-wrapper">
                            <div class="bar-fill profit" style="width: {{ ($totalProfit / $maxValue) * 100 }}%;">
                                @if(($totalProfit / $maxValue) * 100 > 15)
                                    TZS {{ number_format($totalProfit, 0) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            @if(!empty($revenueTrend))
                <table class="revenue-trend-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Revenue</th>
                            <th>Expenses</th>
                            <th>Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($revenueTrend, 0, 10) as $day)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($day['date'] ?? '')->format('M d') }}</td>
                                <td>TZS {{ number_format($day['revenue'] ?? 0, 2) }}</td>
                                <td>TZS {{ number_format($day['expenses'] ?? 0, 2) }}</td>
                                <td>TZS {{ number_format($day['profit'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- Expense Composition & Cash Flow -->
    <div class="two-column">
        <div class="column">
            <div class="chart-section">
                <div class="chart-title">Expense Composition</div>
                <div class="chart-container">
                    @if(!empty($charts['expense_composition']))
                        @php
                            $expenses = array_slice($charts['expense_composition'], 0, 5);
                            $maxExpense = max(array_column($expenses, 'amount'));
                        @endphp
                        <div class="bar-chart">
                            @foreach($expenses as $expense)
                                @php
                                    $amount = $expense['amount'] ?? 0;
                                    $name = $expense['name'] ?? 'Unknown';
                                    $percentage = $maxExpense > 0 ? ($amount / $maxExpense) * 100 : 0;
                                @endphp
                                <div class="bar-item">
                                    <div class="bar-label">
                                        <span>{{ $name }}</span>
                                        <span>TZS {{ number_format($amount, 2) }}</span>
                                    </div>
                                    <div class="bar-wrapper">
                                        <div class="bar-fill expenses" style="width: {{ $percentage }}%;">
                                            @if($percentage > 15)
                                                TZS {{ number_format($amount, 0) }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align: center; padding: 40px; color: #999;">
                            No expense data available
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="column">
            <div class="chart-section">
                <div class="chart-title">Cash Flow Movement</div>
                <div class="chart-container">
                    @if(!empty($charts['cash_flow_movement']))
                        @php
                            $cashFlow = $charts['cash_flow_movement'];
                            $beginning = $cashFlow['beginning'] ?? 0;
                            $inflows = $cashFlow['inflows'] ?? 0;
                            $outflows = $cashFlow['outflows'] ?? 0;
                            $ending = $cashFlow['ending'] ?? 0;
                            $maxCash = max(abs($beginning), abs($inflows), abs($outflows), abs($ending));
                        @endphp
                        <div class="bar-chart">
                            <div class="bar-item">
                                <div class="bar-label">
                                    <span><strong>Beginning Balance</strong></span>
                                    <span>TZS {{ number_format($beginning, 2) }}</span>
                                </div>
                                <div class="bar-wrapper">
                                    <div class="bar-fill cash" style="width: {{ $maxCash > 0 ? (abs($beginning) / $maxCash) * 100 : 0 }}%;">
                                        @if($maxCash > 0 && (abs($beginning) / $maxCash) * 100 > 15)
                                            TZS {{ number_format($beginning, 0) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bar-item">
                                <div class="bar-label">
                                    <span><strong>Inflows</strong></span>
                                    <span>TZS {{ number_format($inflows, 2) }}</span>
                                </div>
                                <div class="bar-wrapper">
                                    <div class="bar-fill profit" style="width: {{ $maxCash > 0 ? (abs($inflows) / $maxCash) * 100 : 0 }}%;">
                                        @if($maxCash > 0 && (abs($inflows) / $maxCash) * 100 > 15)
                                            TZS {{ number_format($inflows, 0) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bar-item">
                                <div class="bar-label">
                                    <span><strong>Outflows</strong></span>
                                    <span>TZS {{ number_format($outflows, 2) }}</span>
                                </div>
                                <div class="bar-wrapper">
                                    <div class="bar-fill expenses" style="width: {{ $maxCash > 0 ? (abs($outflows) / $maxCash) * 100 : 0 }}%;">
                                        @if($maxCash > 0 && (abs($outflows) / $maxCash) * 100 > 15)
                                            TZS {{ number_format($outflows, 0) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bar-item">
                                <div class="bar-label">
                                    <span><strong>Ending Balance</strong></span>
                                    <span>TZS {{ number_format($ending, 2) }}</span>
                                </div>
                                <div class="bar-wrapper">
                                    <div class="bar-fill cash" style="width: {{ $maxCash > 0 ? (abs($ending) / $maxCash) * 100 : 0 }}%;">
                                        @if($maxCash > 0 && (abs($ending) / $maxCash) * 100 > 15)
                                            TZS {{ number_format($ending, 0) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div style="text-align: center; padding: 40px; color: #999;">
                            No cash flow data available
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Top Customers & Products -->
    <div class="two-column">
        <div class="column">
            <div class="chart-section">
                <div class="chart-title">Top 5 Customers</div>
                <div class="chart-container">
                    @if(!empty($charts['top_customers']))
                        @php
                            $customers = array_slice($charts['top_customers'], 0, 5);
                            $maxCustomerRevenue = max(array_column($customers, 'revenue'));
                        @endphp
                        <div class="bar-chart">
                            @foreach($customers as $customer)
                                @php
                                    $revenue = $customer['revenue'] ?? 0;
                                    $name = $customer['name'] ?? 'Unknown';
                                    $percentage = $maxCustomerRevenue > 0 ? ($revenue / $maxCustomerRevenue) * 100 : 0;
                                @endphp
                                <div class="bar-item">
                                    <div class="bar-label">
                                        <span>{{ $name }}</span>
                                        <span>TZS {{ number_format($revenue, 2) }}</span>
                                    </div>
                                    <div class="bar-wrapper">
                                        <div class="bar-fill revenue" style="width: {{ $percentage }}%;">
                                            @if($percentage > 15)
                                                TZS {{ number_format($revenue, 0) }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align: center; padding: 40px; color: #999;">
                            No customer data available
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="column">
            <div class="chart-section">
                <div class="chart-title">Top 5 Products</div>
                <div class="chart-container">
                    @if(!empty($charts['top_products']))
                        @php
                            $products = array_slice($charts['top_products'], 0, 5);
                            $maxProductRevenue = max(array_column($products, 'revenue'));
                        @endphp
                        <div class="bar-chart">
                            @foreach($products as $product)
                                @php
                                    $revenue = $product['revenue'] ?? 0;
                                    $name = $product['name'] ?? 'Unknown';
                                    $percentage = $maxProductRevenue > 0 ? ($revenue / $maxProductRevenue) * 100 : 0;
                                @endphp
                                <div class="bar-item">
                                    <div class="bar-label">
                                        <span>{{ $name }}</span>
                                        <span>TZS {{ number_format($revenue, 2) }}</span>
                                    </div>
                                    <div class="bar-wrapper">
                                        <div class="bar-fill revenue" style="width: {{ $percentage }}%;">
                                            @if($percentage > 15)
                                                TZS {{ number_format($revenue, 0) }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align: center; padding: 40px; color: #999;">
                            No product data available
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Table -->
    <div class="chart-section">
        <div class="chart-title">Key Metrics Summary</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="text-right">Current Period</th>
                    @if($prev_start_date && $prev_end_date)
                        <th class="text-right">Previous Period</th>
                        <th class="text-right">Change %</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($kpiList as $key => $label)
                    @if(isset($kpis[$key]))
                        @php
                            $kpi = $kpis[$key];
                            $current = $kpi['current'] ?? 0;
                            $previous = $kpi['previous'] ?? null;
                            $change = $kpi['change_percent'] ?? 0;
                            $trend = $kpi['trend'] ?? 'flat';
                            
                            if (strpos($label, '%') !== false || strpos($key, 'ratio') !== false || strpos($key, 'margin') !== false) {
                                $currentFmt = number_format($current, 2) . '%';
                                $prevFmt = $previous !== null ? number_format($previous, 2) . '%' : '-';
                            } else {
                                $currentFmt = 'TZS ' . number_format($current, 2);
                                $prevFmt = $previous !== null ? 'TZS ' . number_format($previous, 2) : '-';
                            }
                            
                            $changeClass = $trend === 'up' ? 'positive' : ($trend === 'down' ? 'negative' : '');
                            $changeIcon = $trend === 'up' ? '▲' : ($trend === 'down' ? '▼' : '→');
                            $arrowColor = $trend === 'up' ? '#28a745' : ($trend === 'down' ? '#dc3545' : '#666');
                        @endphp
                        <tr>
                            <td><strong>{{ $label }}</strong></td>
                            <td class="text-right">{{ $currentFmt }}</td>
                            @if($prev_start_date && $prev_end_date)
                                <td class="text-right">{{ $prevFmt }}</td>
                                <td class="text-right" style="color: {{ $arrowColor }};">
                                    <span style="font-weight: bold;">{{ $changeIcon }}</span>
                                    {{ $change > 0 ? '+' : '' }}{{ number_format($change, 1) }}%
                                </td>
                            @endif
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generated by {{ $generatedBy }} on {{ $generatedOn->format('Y-m-d H:i:s') }} using SMARTACCOUNTING
    </div>
</body>
</html>
