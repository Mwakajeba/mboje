<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Petty Cash Reconciliation Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
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
            font-size: 16px;
        }
        .header p {
            margin: 3px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #e9ecef;
            color: #333;
            padding: 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
            font-size: 8px;
        }
        td {
            padding: 4px;
            border: 1px solid #ddd;
            font-size: 8px;
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
        .summary-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        .summary-box h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #333;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .summary-row:last-child {
            border-bottom: none;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>PETTY CASH RECONCILIATION REPORT</h2>
        <p><strong>As of Date:</strong> {{ Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</p>
        @if($branchId)
            <p><strong>Branch:</strong> {{ \App\Models\Branch::find($branchId)->name ?? 'N/A' }}</p>
        @endif
        @if($status)
            <p><strong>Status:</strong> {{ ucfirst($status) }}</p>
        @endif
        <p><strong>Generated:</strong> {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="summary-box">
        <h4>SUMMARY</h4>
        <div class="summary-row">
            <span><strong>Total Units:</strong></span>
            <span>{{ $units->count() }}</span>
        </div>
        <div class="summary-row">
            <span><strong>Total Float Amount:</strong></span>
            <span>TZS {{ number_format($units->sum('float_amount'), 2) }}</span>
        </div>
        <div class="summary-row">
            <span><strong>Total System Balance:</strong></span>
            <span>TZS {{ number_format($units->sum('current_balance'), 2) }}</span>
        </div>
        @php
            $totalVariance = 0;
            foreach($units as $unit) {
                $recon = \App\Services\PettyCashModeService::getReconciliationSummary($unit, $asOfDate);
                $totalVariance += $recon['variance'];
            }
        @endphp
        <div class="summary-row">
            <span><strong>Total Variance:</strong></span>
            <span>TZS {{ number_format($totalVariance, 2) }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Unit Name</th>
                <th>Branch</th>
                <th>Custodian</th>
                <th class="text-right">Opening</th>
                <th class="text-right">Disbursed</th>
                <th class="text-right">Replenished</th>
                <th class="text-right">Closing Cash</th>
                <th class="text-right">System Balance</th>
                <th class="text-right">Variance</th>
                <th class="text-center">Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @foreach($units as $unit)
                @php
                    $reconciliation = \App\Services\PettyCashModeService::getReconciliationSummary($unit, $asOfDate);
                    $outstanding = \App\Models\PettyCash\PettyCashRegister::where('petty_cash_unit_id', $unit->id)
                        ->where('entry_type', 'disbursement')
                        ->where('status', '!=', 'posted')
                        ->where('register_date', '<=', $asOfDate)
                        ->count();
                @endphp
                <tr>
                    <td><strong>{{ $unit->code }}</strong></td>
                    <td>{{ $unit->name }}</td>
                    <td>{{ $unit->branch->name ?? 'N/A' }}</td>
                    <td>{{ $unit->custodian->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($reconciliation['opening_balance'], 2) }}</td>
                    <td class="text-right text-danger">{{ number_format($reconciliation['total_disbursed'], 2) }}</td>
                    <td class="text-right text-success">{{ number_format($reconciliation['total_replenished'], 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($reconciliation['closing_cash'], 2) }}</strong></td>
                    <td class="text-right">{{ number_format($reconciliation['system_balance'], 2) }}</td>
                    <td class="text-right">{{ number_format($reconciliation['variance'], 2) }}</td>
                    <td class="text-center">{{ $outstanding }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated by SmartAccounting System on {{ now()->format('M d, Y H:i') }}
    </div>
</body>
</html>


