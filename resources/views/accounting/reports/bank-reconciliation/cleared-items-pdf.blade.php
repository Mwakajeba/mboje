<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cleared Items from Previous Month</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 9px;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary {
            margin-bottom: 15px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>📌 Report 2: Cleared Items from Previous Month</h2>
        <p>Generated: {{ now()->format('d F Y H:i:s') }}</p>
        <p>Clearing Month: {{ date('F Y', strtotime($clearingMonth . '-01')) }}</p>
        @if($bankAccountId)
            <p>Bank Account: {{ $bankAccounts->find($bankAccountId)->name ?? 'N/A' }}</p>
        @endif
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-row">
            <strong>Total Cleared Items:</strong> {{ $items->count() }}
        </div>
        <div class="summary-row">
            <strong>DNC Items Cleared:</strong> {{ $dncItems->count() }} (Total: {{ number_format($dncItems->sum('amount'), 2) }})
        </div>
        <div class="summary-row">
            <strong>UPC Items Cleared:</strong> {{ $upcItems->count() }} (Total: {{ number_format($upcItems->sum('amount'), 2) }})
        </div>
        <div class="summary-row">
            <strong>Total Amount Cleared:</strong> {{ number_format($items->sum('amount'), 2) }}
        </div>
    </div>

    <!-- Purpose -->
    <p style="font-size: 9px; margin-bottom: 10px;">
        <strong>Purpose:</strong> Transparency, for auditors, for CFO monthly review
    </p>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Type</th>
                <th style="width: 12%;">Ref</th>
                <th style="width: 12%;" class="text-end">Amount</th>
                <th style="width: 12%;">GL Date</th>
                <th style="width: 12%;">Bank Clear Date</th>
                <th style="width: 12%;">Clearing Month</th>
                <th style="width: 12%;">Age</th>
                <th style="width: 20%;">Cleared By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>{{ $item->item_type }}</td>
                <td>{{ $item->reference ?? 'N/A' }}</td>
                <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                <td>{{ $item->transaction_date->format('d-M-Y') }}</td>
                <td>{{ $item->clearing_date ? $item->clearing_date->format('d-M-Y') : 'N/A' }}</td>
                <td>{{ $item->clearing_month ? $item->clearing_month->format('M Y') : 'N/A' }}</td>
                <td>
                    @if(isset($item->age_at_clearing_days))
                        {{ $item->age_at_clearing_days }} days
                        @if($item->age_at_clearing_months >= 1)
                            ({{ number_format($item->age_at_clearing_months, 1) }} months)
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $item->clearedBy->name ?? 'System' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">No cleared items found for {{ date('F Y', strtotime($clearingMonth . '-01')) }}</td>
            </tr>
            @endforelse
        </tbody>
        @if($items->count() > 0)
        <tfoot>
            <tr style="background-color: #e9ecef;">
                <td colspan="2" class="text-end"><strong>Total:</strong></td>
                <td class="text-end"><strong>{{ number_format($items->sum('amount'), 2) }}</strong></td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>

