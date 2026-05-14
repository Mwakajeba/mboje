<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales by Salesperson Report</title>
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
        
        .data-table th:nth-child(1) { width: 18%; }  /* Salesperson */
        .data-table th:nth-child(2) { width: 13%; }  /* Total Sales */
        .data-table th:nth-child(3) { width: 13%; }  /* Collected */
        .data-table th:nth-child(4) { width: 13%; }  /* Outstanding */
        .data-table th:nth-child(5) { width: 11%; }  /* Collection % */
        .data-table th:nth-child(6) { width: 9%; }   /* Invoices */
        .data-table th:nth-child(7) { width: 9%; }   /* Customers */
        .data-table th:nth-child(8) { width: 14%; }  /* Contribution % */
        
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
        
        .data-table tfoot {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .data-table tfoot td {
            font-size: 8px;
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
                <h1>Sales by Salesperson Report</h1>
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

    @if($salespersonSales->count() > 0)
        @php
            $totalSales = $salespersonSales->sum('total_sales');
            $totalCollected = $salespersonSales->sum('amount_collected');
            $totalOutstanding = $salespersonSales->sum('outstanding_amount');
            $totalInvoices = $salespersonSales->sum('invoice_count');
            $totalCustomers = $salespersonSales->sum('customers_served');
            $overallCollectionPercentage = $totalSales > 0 ? ($totalCollected / $totalSales) * 100 : 0;
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalSales, 2) }}</div>
                <div class="stat-label">Total Sales</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalCollected, 2) }}</div>
                <div class="stat-label">Amount Collected</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalOutstanding, 2) }}</div>
                <div class="stat-label">Outstanding</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($overallCollectionPercentage, 1) }}%</div>
                <div class="stat-label">Collection %</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $totalInvoices }}</div>
                <div class="stat-label">Total Invoices</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Salesperson</th>
                    <th class="number">Total Sales</th>
                    <th class="number">Collected</th>
                    <th class="number">Outstanding</th>
                    <th class="number">Collection %</th>
                    <th class="number">Invoices</th>
                    <th class="number">Customers</th>
                    <th class="number">Contribution %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salespersonSales as $salesperson)
                    @php
                        $salesPercentage = $totalSales > 0 ? ($salesperson->total_sales / $totalSales) * 100 : 0;
                    @endphp
                    <tr>
                        <td>{{ $salesperson->createdBy->name ?? 'Unknown' }}</td>
                        <td class="number">{{ number_format($salesperson->total_sales, 2) }}</td>
                        <td class="number">{{ number_format($salesperson->amount_collected ?? 0, 2) }}</td>
                        <td class="number">{{ number_format($salesperson->outstanding_amount ?? 0, 2) }}</td>
                        <td class="number">{{ number_format($salesperson->collection_percentage ?? 0, 1) }}%</td>
                        <td class="number">{{ $salesperson->invoice_count }}</td>
                        <td class="number">{{ $salesperson->customers_served }}</td>
                        <td class="number">{{ number_format($salesPercentage, 2) }}%</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="number">{{ number_format($totalSales, 2) }}</td>
                    <td class="number">{{ number_format($totalCollected, 2) }}</td>
                    <td class="number">{{ number_format($totalOutstanding, 2) }}</td>
                    <td class="number">{{ number_format($overallCollectionPercentage, 1) }}%</td>
                    <td class="number">{{ $totalInvoices }}</td>
                    <td class="number">{{ $totalCustomers }}</td>
                    <td class="number">100.0%</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No sales data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
