<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Petty Cash Voucher - {{ $unit->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #dc3545;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #dc3545;
            margin: 0;
            font-size: 24px;
            text-align: center;
        }
        .header h2 {
            color: #333;
            margin: 5px 0;
            font-size: 16px;
            text-align: center;
        }
        .header p {
            margin: 3px 0;
            color: #666;
            text-align: center;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            font-weight: bold;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            padding: 8px;
            border: 1px solid #dee2e6;
            border-left: none;
        }
        .summary-box {
            border: 2px solid #dc3545;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            background-color: #fff5f5;
        }
        .summary-box h3 {
            margin: 0 0 15px 0;
            color: #dc3545;
            font-size: 16px;
            border-bottom: 2px solid #dc3545;
            padding-bottom: 8px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-row {
            display: table-row;
        }
        .summary-cell {
            display: table-cell;
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .summary-cell:first-child {
            font-weight: bold;
            width: 50%;
        }
        .summary-cell:last-child {
            text-align: right;
            font-weight: bold;
            color: #dc3545;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #dc3545;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #dc3545;
            font-size: 10px;
        }
        td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-danger {
            color: #dc3545;
        }
        .text-success {
            color: #198754;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        .status-active {
            background-color: #198754;
            color: white;
        }
        .status-inactive {
            background-color: #6c757d;
            color: white;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .balance-highlight {
            font-size: 20px;
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PETTY CASH VOUCHER</h1>
        <h2>{{ $unit->name }}</h2>
        <p><strong>Code:</strong> {{ $unit->code }} | <strong>Generated:</strong> {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Unit Information -->
    <div class="info-section">
        <h3 style="margin-bottom: 10px; color: #333; font-size: 14px;">Unit Information</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Unit Name</div>
                <div class="info-value">{{ $unit->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Unit Code</div>
                <div class="info-value">{{ $unit->code }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Branch</div>
                <div class="info-value">{{ $unit->branch->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Custodian</div>
                <div class="info-value">{{ $unit->custodian->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Supervisor</div>
                <div class="info-value">{{ $unit->supervisor->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Operation Mode</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $unit->operation_mode)) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="status-badge {{ $unit->is_active ? 'status-active' : 'status-inactive' }}">
                        {{ $unit->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="summary-box">
        <h3>Balance Summary</h3>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell">Float Amount</div>
                <div class="summary-cell">{{ number_format($unit->float_amount, 2) }} TZS</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Current Balance</div>
                <div class="summary-cell balance-highlight">{{ number_format($unit->current_balance, 2) }} TZS</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Available Balance</div>
                <div class="summary-cell">{{ number_format($unit->current_balance, 2) }} TZS</div>
            </div>
            @if($unit->maximum_limit)
            <div class="summary-row">
                <div class="summary-cell">Maximum Limit</div>
                <div class="summary-cell">{{ number_format($unit->maximum_limit, 2) }} TZS</div>
            </div>
            @endif
            @if($unit->approval_threshold)
            <div class="summary-row">
                <div class="summary-cell">Approval Threshold</div>
                <div class="summary-cell">{{ number_format($unit->approval_threshold, 2) }} TZS</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Statistics -->
    <div class="info-section">
        <h3 style="margin-bottom: 10px; color: #333; font-size: 14px;">Statistics</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Total Transactions</div>
                <div class="info-value">{{ $totalTransactions }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Replenishments</div>
                <div class="info-value">{{ $totalReplenishments }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Disbursed</div>
                <div class="info-value text-danger">{{ number_format($totalDisbursed, 2) }} TZS</div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    @if($recentTransactions->count() > 0)
    <div class="info-section">
        <h3 style="margin-bottom: 10px; color: #333; font-size: 14px;">Recent Transactions (Last 20)</h3>
        <table>
            <thead>
                <tr>
                    <th>Transaction #</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                    <th>Requested By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentTransactions as $transaction)
                <tr>
                    <td>{{ $transaction->transaction_number }}</td>
                    <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                    <td>{{ Str::limit($transaction->description, 40) }}</td>
                    <td class="text-right">{{ number_format($transaction->amount, 2) }} TZS</td>
                    <td>
                        <span class="status-badge 
                            @if($transaction->status == 'posted') status-active
                            @elseif($transaction->status == 'rejected') status-inactive
                            @else status-active
                            @endif">
                            {{ ucfirst($transaction->status) }}
                        </span>
                    </td>
                    <td>{{ $transaction->createdBy->name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Recent Replenishments -->
    @if($recentReplenishments->count() > 0)
    <div class="info-section">
        <h3 style="margin-bottom: 10px; color: #333; font-size: 14px;">Recent Replenishments (Last 10)</h3>
        <table>
            <thead>
                <tr>
                    <th>Replenishment #</th>
                    <th>Date</th>
                    <th class="text-right">Requested Amount</th>
                    <th class="text-right">Approved Amount</th>
                    <th>Status</th>
                    <th>Requested By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentReplenishments as $replenishment)
                <tr>
                    <td>{{ $replenishment->replenishment_number }}</td>
                    <td>{{ $replenishment->requested_at->format('M d, Y') }}</td>
                    <td class="text-right">{{ number_format($replenishment->requested_amount, 2) }} TZS</td>
                    <td class="text-right">{{ number_format($replenishment->approved_amount ?? 0, 2) }} TZS</td>
                    <td>
                        <span class="status-badge 
                            @if($replenishment->status == 'approved' || $replenishment->status == 'posted') status-active
                            @elseif($replenishment->status == 'rejected') status-inactive
                            @else status-active
                            @endif">
                            {{ ucfirst($replenishment->status) }}
                        </span>
                    </td>
                    <td>{{ $replenishment->requestedBy->name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p><strong>{{ $unit->company->name ?? 'Company' }}</strong></p>
        <p>This is a system-generated petty cash voucher. Generated on {{ now()->format('F d, Y \a\t H:i:s') }}</p>
    </div>
</body>
</html>

