<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cost Changes Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .report-info {
            font-size: 10px;
            color: #666;
            margin-top: 10px;
        }
        .summary {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }
        .summary-item {
            text-align: center;
            border: 1px solid #ddd;
            padding: 8px;
            flex: 1;
            margin: 0 3px;
        }
        .summary-label {
            font-weight: bold;
            color: #333;
            font-size: 9px;
        }
        .summary-value {
            font-size: 12px;
            font-weight: bold;
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
            font-size: 8px;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
        <div class="report-title">COST CHANGES REPORT</div>
        <div class="report-info">
            Generated on: {{ $generatedAt->format('F j, Y \a\t g:i A') }}
        </div>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="summary-label">Total Changes</div>
            <div class="summary-value">{{ number_format($totalChanges) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Average Cost</div>
            <div class="summary-value">{{ number_format($averageCostChanges) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">FIFO Layers</div>
            <div class="summary-value">{{ number_format($fifoLayerChanges) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Value</div>
            <div class="summary-value">{{ number_format($totalValue, 2) }} TZS</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>Location</th>
                <th>Cost Method</th>
                <th>Change Type</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Total Cost</th>
                <th>Reason</th>
                <th>Reference</th>
                <th>User</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($costChanges as $change)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($change['date'])->format('Y-m-d H:i') }}</td>
                    <td>{{ $change['item']->code ?? 'N/A' }}</td>
                    <td>{{ $change['item']->name ?? 'N/A' }}</td>
                    <td>{{ $change['location']->name ?? 'N/A' }}</td>
                    <td>{{ $change['cost_method'] }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $change['movement_type'])) }}</td>
                    <td class="text-right">
                        @if($change['type'] === 'layer' && isset($change['remaining_quantity']))
                            {{ number_format($change['quantity'], 2) }}
                            @if($change['remaining_quantity'] < $change['quantity'])
                                <br><small>Rem: {{ number_format($change['remaining_quantity'], 2) }}</small>
                            @endif
                        @else
                            {{ number_format($change['quantity'], 2) }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($change['unit_cost'], 2) }} TZS</td>
                    <td class="text-right">{{ number_format($change['total_cost'], 2) }} TZS</td>
                    <td>{{ $change['reason'] }}</td>
                    <td>{{ $change['reference'] ?? '-' }}</td>
                    <td>{{ $change['user']->name ?? 'System' }}</td>
                    <td>
                        @if($change['type'] === 'layer')
                            @if($change['is_consumed'])
                                Consumed
                            @else
                                Active
                            @endif
                        @else
                            Applied
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center">No cost changes found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        This report shows the history of average cost and FIFO layer changes with reasons.<br>
        Cost changes include opening balances, purchases, adjustments, and FIFO layer consumption.
    </div>
</body>
</html>
