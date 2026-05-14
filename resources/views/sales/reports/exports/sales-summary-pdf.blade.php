<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Summary Report</title>
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
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table th:nth-child(1) { width: 12%; }
        .data-table th:nth-child(2) { width: 10%; }
        .data-table th:nth-child(3) { width: 10%; }
        .data-table th:nth-child(4) { width: 14%; }
        .data-table th:nth-child(5) { width: 12%; }
        .data-table th:nth-child(6) { width: 14%; }
        .data-table th:nth-child(7) { width: 13%; }
        .data-table th:nth-child(8) { width: 15%; }
        
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
                <h1>Sales Summary Report</h1>
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
                <div class="info-value">{{ $dateFrom->format('M d, Y') }} - {{ $dateTo->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Group By:</div>
                <div class="info-value">{{ ucfirst($groupBy) }}</div>
            </div>
            @if(isset($branchId))
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branchId === 'all' ? 'All My Branches' : ($branch ? $branch->name : 'N/A') }}</div>
            </div>
            @elseif($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
            @if($customer)
            <div class="info-row">
                <div class="info-label">Customer:</div>
                <div class="info-value">{{ $customer->name }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($summaryData->count() > 0)
        @php
            $totalSales = $summaryData->sum('total_sales');
            $totalDiscounts = $summaryData->sum('total_discounts');
            $totalNetSales = $summaryData->sum('net_sales');
            $totalQuantity = $summaryData->sum('total_quantity');
            $totalInvoices = $summaryData->sum('invoice_count');
            $averageSales = $summaryData->avg('average_daily_sales');
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalSales, 2) }}</div>
                <div class="stat-label">Total Sales (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalDiscounts, 2) }}</div>
                <div class="stat-label">Total Discounts (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalNetSales, 2) }}</div>
                <div class="stat-label">Net Sales (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalQuantity) }}</div>
                <div class="stat-label">Total Quantity</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalInvoices) }}</div>
                <div class="stat-label">Total Invoices</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($averageSales, 2) }}</div>
                <div class="stat-label">Avg Daily Sales (TZS)</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ ucfirst($groupBy) }}</th>
                    <th class="number">No. of Invoices</th>
                    <th class="number">Quantity Sold</th>
                    <th class="number">Total Sales (TZS)</th>
                    <th class="number">Total Discounts</th>
                    <th class="number">Net Sales</th>
                    <th class="number">% Growth vs. Prev.</th>
                    <th class="number">Avg Daily Sales (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summaryData as $data)
                <tr>
                    <td>{{ $data['period_label'] }}</td>
                    <td class="number">{{ number_format($data['invoice_count']) }}</td>
                    <td class="number">{{ number_format($data['total_quantity']) }}</td>
                    <td class="number">{{ number_format($data['total_sales'], 2) }}</td>
                    <td class="number">{{ number_format($data['total_discounts'], 2) }}</td>
                    <td class="number">{{ number_format($data['net_sales'], 2) }}</td>
                    <td class="number">
                        @if(!is_null($data['growth_vs_prev']))
                            {{ ($data['growth_vs_prev'] >= 0 ? '+' : '') . number_format($data['growth_vs_prev'], 1) }}%
                        @else
                            —
                        @endif
                    </td>
                    <td class="number">{{ number_format($data['average_daily_sales'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No sales data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} </p>
    </div>
</body>
</html>
