<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Efficiency Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 10px;
            color: #333;
            background: #fff;
            font-size: 11px;
        }
        
        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #dc3545;
            padding-bottom: 10px;
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
            color: #dc3545;
            margin: 0;
            font-size: 20px;
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
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 3px solid #dc3545;
        }
        
        .report-info h3 {
            margin: 0 0 10px 0;
            color: #dc3545;
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
            border-radius: 6px;
            overflow: hidden;
        }
        
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 10px 8px;
            border-right: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #dc3545;
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
            background: #dc3545;
            color: white;
        }
        
        .data-table th {
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .data-table td {
            padding: 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
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
                <h1>Collection Efficiency Report</h1>
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

    @if($collectionData->count() > 0)
        @php
            $totalAmount = $collectionData->sum('total_amount');
            $totalPaid = $collectionData->sum('paid_amount');
            $totalOutstanding = $collectionData->sum('outstanding_amount');
            $averageCollectionRate = $collectionData->avg('collection_rate');
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalAmount, 2) }}</div>
                <div class="stat-label">Total Amount</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalPaid, 2) }}</div>
                <div class="stat-label">Total Collected</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalOutstanding, 2) }}</div>
                <div class="stat-label">Outstanding</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($averageCollectionRate, 2) }}%</div>
                <div class="stat-label">Avg Collection Rate</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Invoice #</th>
                    <th style="width: 20%;">Customer</th>
                    <th style="width: 9%;">Date</th>
                    <th style="width: 10%;" class="number">Total</th>
                    <th style="width: 10%;" class="number">Paid</th>
                    <th style="width: 10%;" class="number">Outstanding</th>
                    <th style="width: 10%;" class="number">Rate %</th>
                    <th style="width: 9%;" class="number">Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach($collectionData as $item)
                    <tr>
                        <td style="font-size: 9px;">{{ $item['invoice_number'] }}</td>
                        <td style="font-size: 9px;">{{ Str::limit($item['customer_name'], 15) }}</td>
                        <td style="font-size: 9px;">{{ \Carbon\Carbon::parse($item['invoice_date'])->format('m/d/Y') }}</td>
                        <td class="number" style="font-size: 9px;">{{ number_format($item['total_amount'], 0) }}</td>
                        <td class="number" style="font-size: 9px;">{{ number_format($item['paid_amount'], 0) }}</td>
                        <td class="number" style="font-size: 9px;">{{ number_format($item['outstanding_amount'], 0) }}</td>
                        <td class="number" style="font-size: 9px;">{{ number_format($item['collection_rate'], 1) }}%</td>
                        <td class="number" style="font-size: 9px;">{{ $item['days_outstanding'] >= 0 ? round($item['days_outstanding']) : 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No collection data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Page 1 of 1</p>
    </div>
</body>
</html>
