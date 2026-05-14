<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Uncleared Items Aging Report</title>
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
        .info-section {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
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
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
        }
        .bg-success { background-color: #28a745; color: white; }
        .bg-danger { background-color: #dc3545; color: white; }
        .bg-warning { background-color: #ffc107; color: black; }
        .bg-info { background-color: #17a2b8; color: white; }
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
        <h2>📌 Report 1: Uncleared Items Aging Report</h2>
        <p>Generated: {{ now()->format('d F Y H:i:s') }}</p>
        @if($bankAccountId)
            <p>Bank Account: {{ $bankAccounts->find($bankAccountId)->name ?? 'N/A' }}</p>
        @endif
        @if($startDate || $endDate)
            <p>Period: {{ $startDate ? date('d/m/Y', strtotime($startDate)) : 'All' }} - {{ $endDate ? date('d/m/Y', strtotime($endDate)) : 'All' }}</p>
        @endif
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-row">
            <strong>Total Uncleared Items:</strong> {{ $items->count() }}
        </div>
        <div class="summary-row">
            <strong>DNC Items:</strong> {{ $dncItems->count() }} (Total: {{ number_format($dncItems->sum('amount'), 2) }})
        </div>
        <div class="summary-row">
            <strong>UPC Items:</strong> {{ $upcItems->count() }} (Total: {{ number_format($upcItems->sum('amount'), 2) }})
        </div>
        <div class="summary-row">
            <strong>Total Amount:</strong> {{ number_format($items->sum('amount'), 2) }}
        </div>
    </div>

    <!-- Purpose -->
    <p style="font-size: 9px; margin-bottom: 10px;">
        <strong>Purpose:</strong> Identify possible errors, detect fraud risks, audit compliance
    </p>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Type</th>
                <th style="width: 10%;">Date in GL</th>
                <th style="width: 12%;">Reference</th>
                <th style="width: 25%;">Description</th>
                <th style="width: 12%;" class="text-end">Amount</th>
                <th style="width: 8%;">Age (days)</th>
                <th style="width: 8%;">Age (months)</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 9%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            @php
                $agingColor = $item->getAgingFlagColor();
            @endphp
            <tr>
                <td>{{ $item->item_type }}</td>
                <td>{{ $item->transaction_date->format('d-M-Y') }}</td>
                <td>{{ $item->reference ?? 'N/A' }}</td>
                <td>{{ Str::limit($item->description, 40) }}</td>
                <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                <td class="text-center">{{ $item->age_days }}</td>
                <td class="text-center">{{ number_format($item->age_months, 1) }}</td>
                <td class="text-center">Unclear</td>
                <td style="font-size: 8px;">
                    @if($item->age_days >= 180)
                        Critical Alert
                    @elseif($item->age_days >= 90)
                        Red Flag
                    @elseif($item->age_days >= 60)
                        Orange
                    @elseif($item->age_days >= 30)
                        Yellow warning
                    @else
                        Normal
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center">No uncleared items found</td>
            </tr>
            @endforelse
        </tbody>
        @if($items->count() > 0)
        <tfoot>
            <tr style="background-color: #e9ecef;">
                <td colspan="4" class="text-end"><strong>Total:</strong></td>
                <td class="text-end"><strong>{{ number_format($items->sum('amount'), 2) }}</strong></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>

