<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Return Report</title>
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
            border-bottom: 2px solid #fd7e14;
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
            color: #fd7e14;
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
            border-left: 3px solid #fd7e14;
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
        
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-damaged {
            background: #dc3545;
            color: white;
        }
        
        .badge-defective {
            background: #ffc107;
            color: #000;
        }
        
        .badge-wrong-item {
            background: #6f42c1;
            color: white;
        }
        
        .badge-customer-request {
            background: #17a2b8;
            color: white;
        }
        
        .badge-other {
            background: #6c757d;
            color: white;
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
                <h1>Sales Return Report</h1>
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
            @if($reason && $reason !== 'all')
            <div class="info-row">
                <div class="info-label">Return Reason:</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $reason)) }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($returnData->count() > 0)
        @php
            $totalReturnValue = $returnData->sum('return_value');
            $totalReturns = $returnData->count();
            $grossSales = $grossSales ?? 0;
            $returnRate = $grossSales > 0 ? ($totalReturnValue / $grossSales) * 100 : 0;
            $netSales = $grossSales - $totalReturnValue;
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $totalReturns }}</div>
                <div class="stat-label">Total Returns</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalReturnValue, 0) }}</div>
                <div class="stat-label">Return Value</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($returnRate, 1) }}%</div>
                <div class="stat-label">Return Rate</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($netSales, 0) }}</div>
                <div class="stat-label">Net Sales</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Credit Note #</th>
                    <th style="width: 15%;">Original Invoice</th>
                    <th style="width: 18%;">Customer</th>
                    <th style="width: 9%;">Date</th>
                    <th style="width: 8%;">Reason</th>
                    <th style="width: 15%;">Item</th>
                    <th style="width: 8%;" class="number">Qty</th>
                    <th style="width: 10%;" class="number">Unit Price</th>
                    <th style="width: 15%;" class="number">Return Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($returnData as $return)
                    <tr>
                        <td style="font-size: 9px;">{{ $return->credit_note_number }}</td>
                        <td style="font-size: 9px;">{{ $return->original_invoice_number }}</td>
                        <td style="font-size: 9px;">{{ Str::limit($return->customer_name, 15) }}</td>
                        <td style="font-size: 9px;">{{ $return->return_date->format('m/d/Y') }}</td>
                        <td style="font-size: 9px;">
                            @if($return->reason === 'damaged')
                                <span class="badge badge-damaged">Damaged</span>
                            @elseif($return->reason === 'defective')
                                <span class="badge badge-defective">Defective</span>
                            @elseif($return->reason === 'wrong_item')
                                <span class="badge badge-wrong-item">Wrong Item</span>
                            @elseif($return->reason === 'customer_request')
                                <span class="badge badge-customer-request">Customer</span>
                            @else
                                <span class="badge badge-other">{{ ucfirst($return->reason) }}</span>
                            @endif
                        </td>
                        <td style="font-size: 9px;">{{ Str::limit($return->item_name, 15) }}</td>
                        <td class="number" style="font-size: 9px;">{{ number_format($return->quantity, 0) }}</td>
                        <td class="number" style="font-size: 9px;">{{ number_format($return->unit_price, 0) }}</td>
                        <td class="number" style="font-size: 9px;">{{ number_format($return->return_value, 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="8" style="text-align: right; padding: 8px;">TOTAL RETURN VALUE:</td>
                    <td class="number" style="padding: 8px;">{{ number_format($totalReturnValue, 0) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No sales return data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Page 1 of 1</p>
    </div>
</body>
</html>
