<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Invoice Register Report</title>
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
            font-size: 9px;
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
            font-size: 9px;
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
                <h1>Supplier Invoice Register Report</h1>
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
            @if($status && $status !== 'all')
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst($status) }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($invoices->count() > 0)
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $summary['total_invoices'] }}</div>
                <div class="stat-label">Total Invoices</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_value'], 2) }}</div>
                <div class="stat-label">Total Amount (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_paid'], 2) }}</div>
                <div class="stat-label">Total Paid (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_outstanding'], 2) }}</div>
                <div class="stat-label">Outstanding (TZS)</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Supplier</th>
                    <th>Branch</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th class="number">Subtotal</th>
                    <th class="number">VAT</th>
                    <th class="number">Discount</th>
                    <th class="number">Total</th>
                    <th class="number">Paid</th>
                    <th class="number">Outstanding</th>
                    <th>Status</th>
                    <th>Currency</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->supplier->name ?? 'Unknown' }}</td>
                        <td>{{ $invoice->branch->name ?? 'N/A' }}</td>
                        <td>{{ $invoice->invoice_date->format('m/d/Y') }}</td>
                        <td>{{ $invoice->due_date ? $invoice->due_date->format('m/d/Y') : 'N/A' }}</td>
                        <td class="number">{{ number_format($invoice->subtotal, 2) }}</td>
                        <td class="number">{{ number_format($invoice->vat_amount, 2) }}</td>
                        <td class="number">{{ number_format($invoice->discount_amount, 2) }}</td>
                        <td class="number">{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="number">{{ number_format($invoice->total_paid, 2) }}</td>
                        <td class="number">{{ number_format($invoice->outstanding_amount, 2) }}</td>
                        <td>{{ ucfirst($invoice->status) }}</td>
                        <td>{{ $invoice->currency ?? 'TZS' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="5" style="text-align: right;">TOTALS:</td>
                    <td class="number">{{ number_format($summary['total_subtotal'], 2) }}</td>
                    <td class="number">{{ number_format($summary['total_vat'], 2) }}</td>
                    <td class="number">{{ number_format($summary['total_discount'], 2) }}</td>
                    <td class="number">{{ number_format($summary['total_value'], 2) }}</td>
                    <td class="number">{{ number_format($summary['total_paid'], 2) }}</td>
                    <td class="number">{{ number_format($summary['total_outstanding'], 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No invoice data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>

