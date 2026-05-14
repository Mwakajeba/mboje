<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Petty Cash Reconciliation Report - {{ $unit->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #0dcaf0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h2 {
            color: #0dcaf0;
            margin: 0;
            font-size: 18px;
            text-align: center;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        .info-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            color: #333;
        }
        .info-value {
            display: table-cell;
            color: #666;
        }
        .summary-section {
            margin: 20px 0;
        }
        .summary-title {
            background-color: #0dcaf0;
            color: white;
            padding: 8px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
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
            width: 50%;
        }
        .summary-value {
            display: table-cell;
            padding: 8px;
            border: 1px solid #ddd;
            text-align: right;
            width: 50%;
        }
        .cash-count-section {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .cash-count-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            color: #856404;
        }
        .variance {
            font-size: 14px;
            font-weight: bold;
            padding: 10px;
            margin-top: 10px;
            text-align: center;
        }
        .variance-positive {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .variance-negative {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .variance-zero {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #0dcaf0;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #0dcaf0;
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
        .text-center {
            text-align: center;
        }
        .notes-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 30px;
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
        <h2>PETTY CASH RECONCILIATION REPORT</h2>
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Unit Name:</div>
                <div class="info-value">{{ $unit->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Unit Code:</div>
                <div class="info-value">{{ $unit->code }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">As of Date:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Custodian:</div>
                <div class="info-value">{{ $unit->custodian->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Generated:</div>
                <div class="info-value">{{ now()->format('M d, Y H:i') }}</div>
            </div>
        </div>
    </div>

    <!-- Reconciliation Summary -->
    <div class="summary-section">
        <div class="summary-title">RECONCILIATION SUMMARY</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell">Opening Balance</div>
                <div class="summary-value">TZS {{ number_format($reconciliation['opening_balance'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Total Disbursed</div>
                <div class="summary-value">TZS {{ number_format($reconciliation['total_disbursed'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Total Replenished</div>
                <div class="summary-value">TZS {{ number_format($reconciliation['total_replenished'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Closing Cash (Calculated)</div>
                <div class="summary-value">TZS {{ number_format($reconciliation['closing_cash'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">System Balance</div>
                <div class="summary-value">TZS {{ number_format($reconciliation['system_balance'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Cash Count Section -->
    @if($cashCounted !== null)
    <div class="cash-count-section">
        <div class="cash-count-title">CASH COUNT</div>
        <div class="summary-grid">
            <!-- <div class="summary-row">
                <div class="summary-cell">Physical Cash Counted</div>
                <div class="summary-value">TZS {{ number_format($cashCounted, 2) }}</div>
            </div> -->
            <div class="summary-row">
                <div class="summary-cell">Calculated Balance</div>
                <div class="summary-value">TZS {{ number_format($reconciliation['closing_cash'] ?? 0, 2) }}</div>
            </div>
        </div>
        @if($variance !== null)
        <div class="variance {{ $variance > 0 ? 'variance-positive' : ($variance < 0 ? 'variance-negative' : 'variance-zero') }}">
            Variance: TZS {{ number_format($variance, 2) }}
            @if($variance > 0)
                (Surplus)
            @elseif($variance < 0)
                (Shortage)
            @else
                (Balanced)
            @endif
        </div>
        @endif
    </div>
    @endif

    <!-- Outstanding Vouchers -->
    @if($outstandingVouchers && $outstandingVouchers->count() > 0)
    <div class="summary-section">
        <div class="summary-title">OUTSTANDING VOUCHERS (PENDING RECEIPTS)</div>
        <table>
            <thead>
                <tr>
                    <th>PCV Number</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-right">Amount (TZS)</th>
                    <th>Requested By</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @php $totalOutstanding = 0; @endphp
                @foreach($outstandingVouchers as $voucher)
                    @php $totalOutstanding += $voucher->amount; @endphp
                    <tr>
                        <td>{{ $voucher->pcv_number ?? 'N/A' }}</td>
                        <td>{{ $voucher->register_date->format('M d, Y') }}</td>
                        <td>{{ $voucher->description }}</td>
                        <td class="text-right">{{ number_format($voucher->amount, 2) }}</td>
                        <td>{{ $voucher->requestedBy->name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($voucher->status) }}</td>
                    </tr>
                @endforeach
                <tr style="background-color: #fff3cd; font-weight: bold;">
                    <td colspan="3"><strong>Total Outstanding</strong></td>
                    <td class="text-right"><strong>TZS {{ number_format($totalOutstanding, 2) }}</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Notes -->
    @if($notes)
    <div class="notes-section">
        <div class="notes-title">RECONCILIATION NOTES</div>
        <div>{{ $notes }}</div>
    </div>
    @endif

    <div class="footer">
        <p>This report was generated on {{ now()->format('M d, Y H:i') }} by {{ Auth::user()->name ?? 'System' }}</p>
        <p>{{ $unit->company->name ?? 'Company' }} - Petty Cash Management System</p>
    </div>
</body>
</html>


