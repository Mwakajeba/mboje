<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN vs Invoice Variance Report</title>
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
        
        .data-table th.number {
            text-align: right;
        }
        
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
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-success {
            background-color: #198754;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-info {
            background-color: #0dcaf0;
            color: #000;
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
                <h1>GRN vs Invoice Variance Report</h1>
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
            @if($dateFrom && $dateTo)
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $dateFrom->format('M d, Y') }} - {{ $dateTo->format('M d, Y') }}</div>
            </div>
            @endif
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
            @if($supplier)
            <div class="info-row">
                <div class="info-label">Supplier:</div>
                <div class="info-value">{{ $supplier->name }}</div>
            </div>
            @endif
            @if($status)
            <div class="info-row">
                <div class="info-label">GRN Status:</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $status)) }}</div>
            </div>
            @endif
            @if($varianceStatus && $varianceStatus !== 'all')
            <div class="info-row">
                <div class="info-label">Variance:</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $varianceStatus)) }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($reportData->count() > 0)
        @php
            $totalReceivedQty = $reportData->sum('received_quantity');
            $totalInvoicedQty = $reportData->sum('invoiced_quantity');
            $totalVariance = $reportData->sum('variance');
        @endphp
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $totalItems }}</div>
                <div class="stat-label">Total Items</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $matched }}</div>
                <div class="stat-label">Matched</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $notInvoiced }}</div>
                <div class="stat-label">Not Invoiced</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $underInvoiced }}</div>
                <div class="stat-label">Under Invoiced</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $overInvoiced }}</div>
                <div class="stat-label">Over Invoiced</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalReceivedQty, 2) }}</div>
                <div class="stat-label">Total Received Qty</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>GRN Number</th>
                    <th>GRN Date</th>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Branch</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th class="number">Received Qty</th>
                    <th class="number">Invoiced Qty</th>
                    <th class="number">Variance</th>
                    <th class="number">Variance %</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $row)
                    <tr>
                        <td>{{ $row['grn_number'] }}</td>
                        <td>{{ $row['grn_date'] ? \Carbon\Carbon::parse($row['grn_date'])->format('m/d/Y') : 'N/A' }}</td>
                        <td>{{ $row['po_number'] }}</td>
                        <td>{{ $row['supplier_name'] }}</td>
                        <td>{{ $row['branch_name'] }}</td>
                        <td>{{ $row['item_code'] }}</td>
                        <td>{{ $row['item_name'] }}</td>
                        <td class="number">{{ number_format($row['received_quantity'], 2) }}</td>
                        <td class="number">{{ number_format($row['invoiced_quantity'], 2) }}</td>
                        <td class="number {{ $row['variance'] > 0 ? 'text-warning' : ($row['variance'] < 0 ? 'text-danger' : 'text-success') }}">
                            {{ number_format($row['variance'], 2) }}
                        </td>
                        <td class="number">{{ number_format($row['variance_percentage'], 2) }}%</td>
                        <td>
                            @if($row['variance_status'] === 'matched')
                                <span class="badge badge-success">Matched</span>
                            @elseif($row['variance_status'] === 'not_invoiced')
                                <span class="badge badge-warning">Not Invoiced</span>
                            @elseif($row['variance_status'] === 'under_invoiced')
                                <span class="badge badge-danger">Under Invoiced</span>
                            @elseif($row['variance_status'] === 'over_invoiced')
                                <span class="badge badge-info">Over Invoiced</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="7" style="text-align: right;">TOTALS:</td>
                    <td class="number">{{ number_format($totalReceivedQty, 2) }}</td>
                    <td class="number">{{ number_format($totalInvoicedQty, 2) }}</td>
                    <td class="number {{ $totalVariance >= 0 ? 'text-warning' : 'text-danger' }}">{{ number_format($totalVariance, 2) }}</td>
                    <td class="number"></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>

