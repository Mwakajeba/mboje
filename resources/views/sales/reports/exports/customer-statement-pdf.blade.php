<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Statement Report</title>
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
            margin-bottom: 15px;
            border: 1px solid #333;
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
            padding: 6px 10px;
            width: 120px;
            color: #555;
            border: 1px solid #333;
        }
        
        .info-value {
            display: table-cell;
            padding: 6px 10px;
            color: #333;
            border: 1px solid #333;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            table-layout: fixed;
            border: 1px solid #333;
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
            border: 1px solid #333;
        }
        
        .data-table th:nth-child(1) { width: 12%; }  /* Date */
        .data-table th:nth-child(2) { width: 40%; }  /* Description */
        .data-table th:nth-child(3) { width: 16%; }  /* Invoiced */
        .data-table th:nth-child(4) { width: 16%; }  /* Payments */
        .data-table th:nth-child(5) { width: 16%; }  /* Balance */
        
        .data-table td {
            padding: 8px 6px;
            font-size: 10px;
            word-wrap: break-word;
            border: 1px solid #333;
            vertical-align: top;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier', 'Courier New', monospace;
        }

        .data-table th.number {
            text-align: right;
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
                <h1>Customer Statement Report</h1>
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
                <div class="info-label">Customer:</div>
                <div class="info-value">{{ $customer->name }}@if($customer->phone) ({{ $customer->phone }})@endif</div>
            </div>
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $dateFrom->format('M d, Y') }} - {{ $dateTo->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Cash deposit balance:</div>
                <div class="info-value">{{ number_format($customer->cash_deposit_balance ?? 0, 2) }} TZS (available)</div>
            </div>
            @if(!empty($branchName))
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branchName }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($transactions->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="number" style="text-align: right;">Invoiced</th>
                    <th class="number" style="text-align: right;">Payments</th>
                    <th class="number" style="text-align: right;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @php $runningBalance = $openingBalance; @endphp
                
                <!-- Opening Balance Row -->
                @if($openingBalance != 0)
                <tr style="background-color: #e3f2fd;">
                    <td>{{ $dateFrom->format('Y-m-d') }}</td>
                    <td>Opening Balance</td>
                    <td class="number">{{ $openingBalance >= 0 ? number_format($openingBalance, 2) : '-' }}</td>
                    <td class="number">-</td>
                    <td class="number" style="font-weight: bold;">{{ number_format($runningBalance, 2) }}</td>
                </tr>
                @endif

                @foreach($transactions as $transaction)
                    @php
                        if ($transaction->type == 'invoice') {
                            $runningBalance += $transaction->amount;
                        } elseif ($transaction->type == 'payment') {
                            $runningBalance -= $transaction->amount;
                        } elseif ($transaction->type == 'credit_note') {
                            $runningBalance -= $transaction->amount;
                        }
                    @endphp
                    <tr>
                        <td>{{ $transaction->date->format('Y-m-d') }}</td>
                        <td>{{ $transaction->description }}</td>
                        <td class="number">
                            @if($transaction->type == 'invoice')
                                {{ number_format($transaction->amount, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="number">
                            @if($transaction->type == 'payment')
                                {{ number_format($transaction->amount, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="number" style="font-weight: bold; color: {{ $runningBalance >= 0 ? '#198754' : '#dc3545' }}">
                            {{ number_format($runningBalance, 2) }}
                        </td>
                    </tr>
                @endforeach

                <!-- Total Row -->
                <tr style="background-color: #d4edda; font-weight: bold;">
                    <td colspan="2">Total</td>
                    <td class="number">{{ number_format($totalInvoices, 2) }}</td>
                    <td class="number">
                        {{ number_format($totalPayments, 2) }}
                        @if(isset($totalCreditNotes) && $totalCreditNotes > 0)
                            <br><small style="color: #856404;">(CN: {{ number_format($totalCreditNotes, 2) }})</small>
                        @endif
                    </td>
                    <td class="number" style="color: {{ $closingBalance >= 0 ? '#198754' : '#dc3545' }}">
                        {{ number_format($closingBalance, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Transactions Found</h3>
            <p>No transactions found for this customer in the selected period.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
