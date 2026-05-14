<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Book Report</title>
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
        
        .data-table tfoot {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .data-table tfoot td {
            border-top: 2px solid #17a2b8;
            padding: 10px 6px;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .opening-balance-row {
            background: #fff3cd;
            font-weight: bold;
        }
        
        .total-row {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .closing-balance-row {
            background: #343a40;
            color: white;
            font-weight: bold;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $companyModel = isset($company) ? $company : (function_exists('current_company') ? current_company() : null);
                $logoPath = ($companyModel && !empty($companyModel->logo)) ? public_path('storage/' . $companyModel->logo) : null;
            @endphp
            @if($logoPath && file_exists($logoPath))
                <div class="logo-section">
                    <img src="{{ $logoPath }}" alt="{{ $company->name ?? 'Company' }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Cash Book Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name ?? 'SmartFinance' }}</div>
                @endif
                <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            @if($cashBookData['start_date'] === $cashBookData['end_date'])
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">As at {{ \Carbon\Carbon::parse($cashBookData['end_date'])->format('M d, Y') }}</div>
            </div>
            @else
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($cashBookData['start_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($cashBookData['end_date'])->format('M d, Y') }}</div>
            </div>
            @endif
            @if(isset($branchName))
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branchName }}</div>
            </div>
            @endif
            @if(isset($cashBookData['bank_name']))
            <div class="info-row">
                <div class="info-label">Bank Account:</div>
                <div class="info-value">{{ $cashBookData['bank_name'] }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Opening Balance:</div>
                <div class="info-value">{{ number_format($cashBookData['opening_balance'], 2) }}</div>
            </div>
        </div>
    </div>

    @if(count($cashBookData['transactions'] ?? []) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Date</th>
                    <th style="width: 20%;">Description</th>
                    <th style="width: 15%;">Customer</th>
                    <th style="width: 15%;">Bank Account</th>
                    <th style="width: 12%;">Transaction No</th>
                    <th style="width: 12%;">Reference No.</th>
                    <th class="number" style="width: 9%;">Debit</th>
                    <th class="number" style="width: 9%;">Credit</th>
                    <th class="number" style="width: 10%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening-balance-row">
                    <td colspan="6" class="number"><strong>Opening Balance</strong></td>
                    <td class="number"></td>
                    <td class="number"></td>
                    <td class="number"><strong>{{ number_format($cashBookData['opening_balance'], 2) }}</strong></td>
                </tr>
                
                @php
                    $running_balance = $cashBookData['opening_balance'];
                    $total_receipts = 0;
                    $total_payments = 0;
                @endphp
                
                @foreach($cashBookData['transactions'] as $transaction)
                    @php
                        $debit = $transaction['debit'];
                        $credit = $transaction['credit'];

                        $total_receipts += $debit;
                        $total_payments += $credit;

                        $running_balance += $debit - $credit;
                    @endphp
                    
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('Y-m-d') }}</td>
                        <td>{{ $transaction['description'] }}</td>
                        <td>{{ $transaction['customer_name'] }}</td>
                        <td>{{ $transaction['bank_account'] }}</td>
                        <td>{{ $transaction['transaction_no'] }}</td>
                        <td>{{ $transaction['reference_no'] }}</td>
                        <td class="number">{{ $debit > 0 ? number_format($debit, 2) : '' }}</td>
                        <td class="number">{{ $credit > 0 ? number_format($credit, 2) : '' }}</td>
                        <td class="number"><strong>{{ number_format($running_balance, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6" class="number"><strong>Total Debit</strong></td>
                    <td class="number"><strong>{{ number_format($total_receipts, 2) }}</strong></td>
                    <td class="number"></td>
                    <td class="number"></td>
                </tr>
                
                <tr class="total-row">
                    <td colspan="6" class="number"><strong>Total Credit</strong></td>
                    <td class="number"></td>
                    <td class="number"><strong>{{ number_format($total_payments, 2) }}</strong></td>
                    <td class="number"></td>
                </tr>
                
                <tr class="total-row">
                    <td colspan="6" class="number"><strong>Final Balance</strong></td>
                    <td class="number"></td>
                    <td class="number"></td>
                    <td class="number"><strong>{{ number_format($running_balance, 2) }}</strong></td>
                </tr>
                
                <tr class="closing-balance-row">
                    <td colspan="8" class="number"><strong>Closing Balance</strong></td>
                    <td class="number"><strong>{{ number_format($running_balance, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No transactions found for the selected period.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">
            Cash Book Report showing all cash transactions
            @if($cashBookData['start_date'] === $cashBookData['end_date'])
                as at {{ \Carbon\Carbon::parse($cashBookData['end_date'])->format('F d, Y') }}
            @else
                from {{ \Carbon\Carbon::parse($cashBookData['start_date'])->format('F d, Y') }} to {{ \Carbon\Carbon::parse($cashBookData['end_date'])->format('F d, Y') }}
            @endif
        </p>
    </div>
</body>
</html>
