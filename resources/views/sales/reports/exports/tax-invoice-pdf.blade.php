<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice Report</title>
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
        
        .data-table th:nth-child(1) { width: 12%; }  /* Invoice # */
        .data-table th:nth-child(2) { width: 20%; }  /* Customer */
        .data-table th:nth-child(3) { width: 9%; }   /* Date */
        .data-table th:nth-child(4) { width: 8%; }   /* Tax Type */
        .data-table th:nth-child(5) { width: 8%; }   /* Tax Rate */
        .data-table th:nth-child(6) { width: 10%; }  /* Subtotal */
        .data-table th:nth-child(7) { width: 10%; }  /* VAT */
        .data-table th:nth-child(8) { width: 10%; }  /* WHT */
        .data-table th:nth-child(9) { width: 13%; }  /* Total */
        
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
            padding: 8px 6px;
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
        
        .badge-vat {
            background: #007bff;
            color: white;
        }
        
        .badge-wht {
            background: #ffc107;
            color: #000;
        }
        
        .badge-both {
            background: #6f42c1;
            color: white;
        }
        
        .badge-none {
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
                <h1>Tax Invoice Report</h1>
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
            @if($taxType && $taxType !== 'all')
            <div class="info-row">
                <div class="info-label">Tax Type:</div>
                <div class="info-value">{{ ucfirst($taxType) }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($taxInvoices->count() > 0)
        @php
            $totalAmount = $taxInvoices->sum('total_amount');
            $totalVatAmount = $taxInvoices->sum('vat_amount');
            $totalWhtAmount = $taxInvoices->sum('withholding_tax_amount');
            $invoiceCount = $taxInvoices->count();
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $invoiceCount }}</div>
                <div class="stat-label">Total Invoices</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalAmount, 2) }}</div>
                <div class="stat-label">Total Amount (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalVatAmount, 2) }}</div>
                <div class="stat-label">VAT Amount (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalWhtAmount, 2) }}</div>
                <div class="stat-label">WHT Amount (TZS)</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Tax Type</th>
                    <th>Tax Rate</th>
                    <th class="number">Subtotal (TZS)</th>
                    <th class="number">VAT (TZS)</th>
                    <th class="number">WHT (TZS)</th>
                    <th class="number">Total (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($taxInvoices as $invoice)
                    @php
                        $hasVat = $invoice->vat_amount > 0;
                        $hasWht = $invoice->withholding_tax_amount > 0;
                        $taxType = $hasVat && $hasWht ? 'both' : ($hasVat ? 'vat' : ($hasWht ? 'wht' : 'none'));
                        $taxRate = $invoice->vat_rate ?? 0;
                        $whtRate = $invoice->withholding_tax_rate ?? 0;
                    @endphp
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->customer->name ?? 'Unknown' }}</td>
                        <td>{{ $invoice->invoice_date->format('m/d/Y') }}</td>
                        <td>
                            @if($taxType === 'vat')
                                <span class="badge badge-vat">VAT</span>
                            @elseif($taxType === 'wht')
                                <span class="badge badge-wht">WHT</span>
                            @elseif($taxType === 'both')
                                <span class="badge badge-both">Both</span>
                            @else
                                <span class="badge badge-none">None</span>
                            @endif
                        </td>
                        <td>
                            @if($taxType === 'vat')
                                {{ number_format($taxRate, 1) }}%
                            @elseif($taxType === 'wht')
                                {{ number_format($whtRate, 1) }}%
                            @elseif($taxType === 'both')
                                V:{{ number_format($taxRate, 1) }}%<br>W:{{ number_format($whtRate, 1) }}%
                            @else
                                -
                            @endif
                        </td>
                        <td class="number">{{ number_format($invoice->subtotal, 2) }}</td>
                        <td class="number">{{ number_format($invoice->vat_amount, 2) }}</td>
                        <td class="number">{{ number_format($invoice->withholding_tax_amount, 2) }}</td>
                        <td class="number">{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right;">TOTALS:</td>
                    <td class="number">{{ number_format($taxInvoices->sum('subtotal'), 2) }}</td>
                    <td class="number">{{ number_format($totalVatAmount, 2) }}</td>
                    <td class="number">{{ number_format($totalWhtAmount, 2) }}</td>
                    <td class="number">{{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No tax invoice data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
