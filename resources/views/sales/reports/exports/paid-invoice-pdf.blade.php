<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Received Report</title>
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
        
        .data-table th:nth-child(1) { width: 15%; }  /* Invoice Number */
        .data-table th:nth-child(2) { width: 20%; }  /* Customer Name */
        .data-table th:nth-child(3) { width: 10%; }   /* Invoice Date */
        .data-table th:nth-child(4) { width: 13%; }  /* Total Amount */
        .data-table th:nth-child(5) { width: 13%; }  /* Paid Amount */
        .data-table th:nth-child(6) { width: 13%; }  /* Outstanding */
        .data-table th:nth-child(7) { width: 16%; }  /* Payment Status */
        
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
                <h1>Payment Received Report</h1>
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
                <div class="info-label">Branch:</div>
                <div class="info-value">
                    @if(isset($branchId) && $branchId === 'all')
                        All My Branches
                    @elseif($branch)
                        {{ $branch->name }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
            @if($paymentStatus && $paymentStatus !== 'all')
            <div class="info-row">
                <div class="info-label">Payment Status:</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $paymentStatus)) }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($invoices->count() > 0)
        @php
            $totalAmount = $invoices->sum('total_amount');
            $totalPaid = $invoices->sum('paid_amount');
            $totalOutstanding = $totalAmount - $totalPaid;
            $fullyPaidCount = $invoices->filter(function($invoice) {
                return $invoice->paid_amount >= $invoice->total_amount;
            })->count();
            $partiallyPaidCount = $invoices->filter(function($invoice) {
                return $invoice->paid_amount < $invoice->total_amount && $invoice->paid_amount > 0;
            })->count();
            $collectionRate = $totalAmount > 0 ? ($totalPaid / $totalAmount) * 100 : 0;
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $fullyPaidCount }}</div>
                <div class="stat-label">Fully Paid</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $partiallyPaidCount }}</div>
                <div class="stat-label">Partially Paid</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalPaid, 2) }}</div>
                <div class="stat-label">Total Received (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($collectionRate, 2) }}%</div>
                <div class="stat-label">Collection Rate</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Customer Name</th>
                    <th>Invoice Date</th>
                    <th class="number">Total Amount (TZS)</th>
                    <th class="number">Paid Amount (TZS)</th>
                    <th class="number">Outstanding (TZS)</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                    @php
                        $outstanding = $invoice->total_amount - $invoice->paid_amount;
                        $isFullyPaid = $invoice->paid_amount >= $invoice->total_amount;
                    @endphp
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->customer->name ?? 'Unknown' }}</td>
                        <td>{{ $invoice->invoice_date->format('m/d/Y') }}</td>
                        <td class="number">{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="number">{{ number_format($invoice->paid_amount, 2) }}</td>
                        <td class="number">{{ number_format($outstanding, 2) }}</td>
                        <td>
                            @if($isFullyPaid)
                                <span style="color: #198754; font-weight: bold;">Fully Paid</span>
                            @else
                                <span style="color: #fd7e14; font-weight: bold;">Partially Paid</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No payment data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
