<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Petty Cash Register - {{ $unit->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #17a2b8;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h2 {
            color: #17a2b8;
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-row {
            display: table-row;
        }
        .summary-cell {
            display: table-cell;
            padding: 8px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .summary-value {
            display: table-cell;
            padding: 8px;
            border: 1px solid #ddd;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #17a2b8;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #17a2b8;
        }
        td {
            padding: 6px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-danger {
            color: #dc3545;
        }
        .text-success {
            color: #198754;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>PETTY CASH REGISTER</h2>
        <p><strong>Unit:</strong> {{ $unit->name }} ({{ $unit->code }})</p>
        <p><strong>Period:</strong> {{ $dateFrom->format('M d, Y') }} to {{ $dateTo->format('M d, Y') }}</p>
        <p><strong>Generated:</strong> {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="summary">
        <div class="summary-row">
            <div class="summary-cell">Opening Balance</div>
            <div class="summary-value">{{ number_format($reconciliation['opening_balance'] ?? 0, 2) }}</div>
            <div class="summary-cell">Total Disbursed</div>
            <div class="summary-value text-danger">{{ number_format($reconciliation['total_disbursed'] ?? 0, 2) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-cell">Total Replenished</div>
            <div class="summary-value text-success">{{ number_format($reconciliation['total_replenished'] ?? 0, 2) }}</div>
            <div class="summary-cell">Closing Cash</div>
            <div class="summary-value">{{ number_format($reconciliation['closing_cash'] ?? 0, 2) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>PCV Number</th>
                <th>Date</th>
                <th>Entry Type</th>
                <th>Description</th>
                <th class="text-right">Amount</th>
                <th>Nature</th>
                <th>GL Account</th>
                <th>Requested By</th>
                <th>Approved By</th>
                <th>Status</th>
                <th class="text-right">Balance After</th>
            </tr>
        </thead>
        <tbody>
            @php
                $runningBalance = $reconciliation['opening_balance'] ?? 0;
            @endphp
            @foreach($entries as $entry)
                @php
                    if ($entry->nature === 'debit') {
                        $runningBalance -= $entry->amount;
                    } else {
                        $runningBalance += $entry->amount;
                    }
                @endphp
                <tr>
                    <td>{{ $entry->pcv_number ?? 'N/A' }}</td>
                    <td>{{ $entry->register_date->format('M d, Y') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $entry->entry_type)) }}</td>
                    <td>{{ $entry->description }}</td>
                    <td class="text-right {{ $entry->nature === 'debit' ? 'text-danger' : 'text-success' }}">
                        {{ $entry->nature === 'debit' ? '-' : '+' }}{{ number_format($entry->amount, 2) }}
                    </td>
                    <td>{{ ucfirst($entry->nature) }}</td>
                    <td>{{ $entry->glAccount->account_name ?? 'N/A' }}</td>
                    <td>{{ $entry->requestedBy->name ?? 'N/A' }}</td>
                    <td>{{ $entry->approvedBy->name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($entry->status) }}</td>
                    <td class="text-right">{{ number_format($entry->balance_after ?? $runningBalance, 2) }}</td>
                </tr>
            @endforeach
            @if($entries->count() === 0)
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px; color: #999;">
                        No register entries found for the selected period
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <p>Generated by {{ Auth::user()->name ?? 'System' }} on {{ now()->format('M d, Y H:i') }}</p>
    </div>
</body>
</html>


