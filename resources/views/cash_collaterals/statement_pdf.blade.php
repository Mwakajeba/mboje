<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cash Deposit Statement - {{ $cashCollateral->customer->name ?? 'N/A' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .report-date {
            font-size: 11px;
            color: #666;
        }
        
        .customer-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        
        .info-row {
            display: inline-block;
            width: 48%;
            margin-bottom: 8px;
            vertical-align: top;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 15px;
        }
        
        .summary-card {
            flex: 1;
            background-color: #fff;
            border: 1px solid #dee2e6;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
        }
        
        .summary-card.deposits {
            border-left: 4px solid #28a745;
        }
        
        .summary-card.withdrawals {
            border-left: 4px solid #dc3545;
        }
        
        .summary-card.balance {
            border-left: 4px solid #007bff;
        }
        
        .summary-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .summary-amount {
            font-size: 16px;
            font-weight: bold;
        }
        
        .summary-amount.positive {
            color: #28a745;
        }
        
        .summary-amount.negative {
            color: #dc3545;
        }
        
        .summary-amount.primary {
            color: #007bff;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }
        
        th {
            background-color: #007bff;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }
        
        th.text-center, td.text-center {
            text-align: center;
        }
        
        th.text-right, td.text-right {
            text-align: right;
        }
        
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .credit-amount {
            color: #28a745;
            font-weight: bold;
        }
        
        .debit-amount {
            color: #dc3545;
            font-weight: bold;
        }
        
        .balance-amount {
            font-weight: bold;
            color: #007bff;
        }
        
        .totals-row {
            background-color: #e9ecef !important;
            font-weight: bold;
        }
        
        .totals-row td {
            border-top: 2px solid #007bff;
            padding: 12px 8px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        
        .no-transactions {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        @media print {
            body { margin: 0; padding: 10px; }
            .summary-cards { flex-direction: column; }
            .summary-card { margin-bottom: 10px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $cashCollateral->company->name ?? 'SmartFinance System' }}</div>
        <div class="report-title">Cash Deposit Statement</div>
        <div class="report-date">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
    </div>

    <div class="customer-info">
        @php
            $customer = $cashCollateral->customer ?? null;
        @endphp
        <div class="info-row">
            <span class="info-label">Customer:</span> {{ $customer ? $customer->name : 'N/A' }}
        </div>
        <div class="info-row">
            <span class="info-label">Customer No:</span> {{ $customer ? $customer->customerNo : 'N/A' }}
        </div>
        <div class="info-row">
            <span class="info-label">Account Type:</span> {{ $cashCollateral->type->name ?? 'N/A' }}
        </div>
        <div class="info-row">
            <span class="info-label">Branch:</span> {{ $cashCollateral->branch->name ?? 'N/A' }}
        </div>
        <div class="info-row">
            <span class="info-label">Phone:</span> {{ $customer ? $customer->phone : 'N/A' }}
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span> {{ $customer ? ($customer->email ?? 'N/A') : 'N/A' }}
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card deposits">
            <div class="summary-label">Total Deposits</div>
            <div class="summary-amount positive">TSHS {{ number_format($transactions->sum('credit'), 2) }}</div>
        </div>
        <div class="summary-card withdrawals">
            <div class="summary-label">Total Withdrawals</div>
            <div class="summary-amount negative">TSHS {{ number_format($transactions->sum('debit'), 2) }}</div>
        </div>
        <div class="summary-card balance">
            <div class="summary-label">Current Balance</div>
            <div class="summary-amount primary">TSHS {{ number_format($calculatedBalance ?? 0, 2) }}</div>
        </div>
    </div>

    @if($transactions->count() > 0)
    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">#</th>
                <th width="12%">Date</th>
                <th width="35%">Description</th>
                <th width="18%">Created By</th>
                <th class="text-right" width="12%">Credit (TSh)</th>
                <th class="text-right" width="12%">Debit (TSh)</th>
                <th class="text-right" width="14%">Balance (TSh)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td class="text-center">{{ $transaction['row_number'] }}</td>
                <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y') }}</td>
                <td>{{ $transaction['narration'] }}</td>
                <td>{{ $transaction['created_by'] }}</td>
                <td class="text-right">
                    @if($transaction['credit'] > 0)
                        <span class="credit-amount">{{ number_format($transaction['credit'], 2) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">
                    @if($transaction['debit'] > 0)
                        <span class="debit-amount">{{ number_format($transaction['debit'], 2) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">
                    <span class="balance-amount">{{ number_format($transaction['balance'], 2) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="4" class="text-right"><strong>TOTALS:</strong></td>
                <td class="text-right">
                    <span class="credit-amount">{{ number_format($transactions->sum('credit'), 2) }}</span>
                </td>
                <td class="text-right">
                    <span class="debit-amount">{{ number_format($transactions->sum('debit'), 2) }}</span>
                </td>
                <td class="text-right">
                    <span class="balance-amount">{{ number_format($calculatedBalance ?? 0, 2) }}</span>
                </td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="no-transactions">
        <h3>No Transaction History</h3>
        <p>This customer account has no recorded transactions yet.</p>
    </div>
    @endif

    <div class="footer">
        <p>
            <strong>{{ $cashCollateral->company->name ?? 'SmartFinance System' }}</strong><br>
            Cash Deposit Statement • Customer: {{ $cashCollateral->customer->name }} • 
            Generated: {{ now()->format('M d, Y g:i A') }}
        </p>
        <p style="margin-top: 10px;">
            Total Transactions: {{ $transactions->count() }} | 
            Statement Period: {{ $transactions->count() > 0 ? 
                \Carbon\Carbon::parse($transactions->first()['date'])->format('M d, Y') . ' to ' . 
                \Carbon\Carbon::parse($transactions->last()['date'])->format('M d, Y') : 'No transactions' }}
        </p>
    </div>
</body>
</html>