<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Effectiveness Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #fff;
        }
        
        .header {
            margin-bottom: 30px;
            border-bottom: 3px solid #fd7e14;
            padding-bottom: 20px;
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
            color: #fd7e14;
            margin: 0;
            font-size: 28px;
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
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #fd7e14;
        }
        
        .report-info h3 {
            margin: 0 0 10px 0;
            color: #fd7e14;
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
            margin-bottom: 25px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 20px 15px;
            border-right: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #fd7e14;
            margin: 0;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin: 5px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .data-table thead {
            background: #fd7e14;
            color: white;
        }
        
        .data-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 13px;
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
                <h1>Discount Effectiveness Report</h1>
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
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($discountData->count() > 0)
        @php
            $totalGrossSales = $discountData->sum('gross_sales');
            $totalDiscountAmount = $discountData->sum('discount_amount');
            $totalNetSales = $discountData->sum('net_sales');
            $avgDiscountPercentage = $discountData->avg('discount_percentage');
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalGrossSales, 2) }}</div>
                <div class="stat-label">Total Gross Sales</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalDiscountAmount, 2) }}</div>
                <div class="stat-label">Total Discounts</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalNetSales, 2) }}</div>
                <div class="stat-label">Net Sales</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($avgDiscountPercentage, 2) }}%</div>
                <div class="stat-label">Avg Discount %</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Customer Name</th>
                    <th>Invoice Date</th>
                    <th class="number">Gross Sales</th>
                    <th class="number">Discount Amount</th>
                    <th class="number">Net Sales</th>
                    <th class="number">Discount %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($discountData as $item)
                    <tr>
                        <td>{{ $item['invoice_number'] }}</td>
                        <td>{{ $item['customer_name'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($item['invoice_date'])->format('Y-m-d') }}</td>
                        <td class="number">{{ number_format($item['gross_sales'], 2) }}</td>
                        <td class="number">{{ number_format($item['discount_amount'], 2) }}</td>
                        <td class="number">{{ number_format($item['net_sales'], 2) }}</td>
                        <td class="number">{{ number_format($item['discount_percentage'], 2) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No discount data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Page 1 of 1</p>
    </div>
</body>
</html>
