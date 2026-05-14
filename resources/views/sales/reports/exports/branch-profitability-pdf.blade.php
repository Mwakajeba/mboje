<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Profitability Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            background: #fff;
        }
        
        .header {
            margin-bottom: 20px;
            border-bottom: 3px solid #17a2b8;
            padding-bottom: 15px;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        
        .logo-section {
            flex-shrink: 0;
        }
        
        .company-logo {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }
        
        .title-section {
            text-align: center;
            flex-grow: 1;
        }
        
        .header h1 {
            color: #17a2b8;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .company-name {
            color: #333;
            margin: 5px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .header .subtitle {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        
        .report-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #17a2b8;
        }
        
        .report-info h3 {
            margin: 0 0 10px 0;
            color: #17a2b8;
            font-size: 16px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 15px 5px 0;
            width: 120px;
            color: #555;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
        }
        
        .summary-stats {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 12px 8px;
            border-right: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #17a2b8;
            margin: 0;
        }
        
        .stat-label {
            font-size: 9px;
            color: #666;
            margin: 3px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed;
        }
        
        .data-table thead {
            background: #17a2b8;
            color: white;
        }
        
        .data-table th {
            padding: 8px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            word-wrap: break-word;
        }
        
        .data-table th:nth-child(1) { width: 15%; }  /* Branch */
        .data-table th:nth-child(2) { width: 12%; }  /* Total Revenue */
        .data-table th:nth-child(3) { width: 12%; }  /* Cost of Sales */
        .data-table th:nth-child(4) { width: 12%; }  /* Gross Profit */
        .data-table th:nth-child(5) { width: 12%; }  /* Operating Expenses */
        .data-table th:nth-child(6) { width: 12%; }  /* Net Profit */
        .data-table th:nth-child(7) { width: 10%; }  /* Profit Margin % */
        .data-table th:nth-child(8) { width: 8%; }   /* Staff Count */
        .data-table th:nth-child(9) { width: 7%; }  /* Profit per Staff */
        
        .data-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #dee2e6;
            font-size: 8px;
            word-wrap: break-word;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier', 'Courier New', monospace;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @if($company && $company->logo)
                <div class="logo-section">
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $company->name }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Branch Profitability Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Date From:</div>
                <div class="info-value">{{ $dateFrom->format('F d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date To:</div>
                <div class="info-value">{{ $dateTo->format('F d, Y') }}</div>
            </div>
        </div>
    </div>

    @if($branchData->count() > 0)
        @php
            $totalRevenue = $branchData->sum('total_revenue');
            $totalCostOfSales = $branchData->sum('cost_of_sales');
            $totalGrossProfit = $branchData->sum('gross_profit');
            $totalExpenses = $branchData->sum('operating_expenses');
            $totalProfit = $branchData->sum('net_profit');
            $averageMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalRevenue, 2) }}</div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalGrossProfit, 2) }}</div>
                <div class="stat-label">Gross Profit</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalProfit, 2) }}</div>
                <div class="stat-label">Net Profit</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($averageMargin, 1) }}%</div>
                <div class="stat-label">Avg Margin</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th class="number">Total Revenue (TZS)</th>
                    <th class="number">Cost of Sales (TZS)</th>
                    <th class="number">Gross Profit (TZS)</th>
                    <th class="number">Operating Expenses (TZS)</th>
                    <th class="number">Net Profit (TZS)</th>
                    <th class="number">Profit Margin %</th>
                    <th class="number">Staff Count</th>
                    <th class="number">Profit per Staff (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($branchData as $branch)
                    <tr>
                        <td>
                            <div style="font-weight: bold;">{{ $branch->branch->name ?? 'Unknown Branch' }}</div>
                            <small style="color: #666; font-size: 7px;">{{ $branch->branch->address ?? 'N/A' }}</small>
                        </td>
                        <td class="number">{{ number_format($branch->total_revenue, 2) }}</td>
                        <td class="number">{{ number_format($branch->cost_of_sales, 2) }}</td>
                        <td class="number" style="color: {{ $branch->gross_profit >= 0 ? '#198754' : '#dc3545' }}; font-weight: bold;">
                            {{ number_format($branch->gross_profit, 2) }}
                        </td>
                        <td class="number">{{ number_format($branch->operating_expenses, 2) }}</td>
                        <td class="number" style="color: {{ $branch->net_profit >= 0 ? '#198754' : '#dc3545' }}; font-weight: bold;">
                            {{ number_format($branch->net_profit, 2) }}
                        </td>
                        <td class="number">{{ number_format($branch->profit_margin_percentage, 0) }}%</td>
                        <td class="number">{{ $branch->staff_count }}</td>
                        <td class="number">{{ number_format($branch->profit_per_staff, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No profitability data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
