<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Performance Report</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
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
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table th:nth-child(1) { width: 15%; }
        .data-table th:nth-child(2) { width: 15%; }
        .data-table th:nth-child(3) { width: 15%; }
        .data-table th:nth-child(4) { width: 15%; }
        .data-table th:nth-child(5) { width: 12%; }
        .data-table th:nth-child(6) { width: 10%; }
        .data-table th:nth-child(7) { width: 18%; }
        
        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
            word-wrap: break-word;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tfoot {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .data-table tfoot td {
            border-top: 2px solid #17a2b8;
            padding: 10px 6px;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier New', monospace;
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
                <h1>Category Performance Report</h1>
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
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $dateFromCarbon->format('M d, Y') }} - {{ $dateToCarbon->format('M d, Y') }}</div>
            </div>
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($reportData->count() > 0)
        @php
            $totalSales = $reportData->sum('total_sales');
            $totalCost = $reportData->sum('cost_of_sales');
            $totalProfit = $reportData->sum('gross_profit');
            $totalUnits = $reportData->sum('units_sold');
            $totalMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
        @endphp

        <table class="data-table">
            <thead>
                <tr>
                    <th>Category / Dept</th>
                    <th class="number">Total Sales (TZS)</th>
                    <th class="number">Cost of Sales (TZS)</th>
                    <th class="number">Gross Profit</th>
                    <th class="number">Gross Margin %</th>
                    <th class="number">Units Sold</th>
                    <th>Top Selling Item</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $data)
                <tr>
                    <td>{{ $data['category']->name }}</td>
                    <td class="number">{{ number_format($data['total_sales'], 2) }}</td>
                    <td class="number">{{ number_format($data['cost_of_sales'], 2) }}</td>
                    <td class="number">{{ number_format($data['gross_profit'], 2) }}</td>
                    <td class="number">{{ number_format($data['gross_margin'], 2) }}%</td>
                    <td class="number">{{ number_format($data['units_sold'], 2) }}</td>
                    <td>{{ $data['top_selling_item']->name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="number">{{ number_format($totalSales, 2) }}</td>
                    <td class="number">{{ number_format($totalCost, 2) }}</td>
                    <td class="number">{{ number_format($totalProfit, 2) }}</td>
                    <td class="number">{{ number_format($totalMargin, 2) }}%</td>
                    <td class="number">{{ number_format($totalUnits, 2) }}</td>
                    <td>-</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No category performance data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} </p>
    </div>
</body>
</html>

