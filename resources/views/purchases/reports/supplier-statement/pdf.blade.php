<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Statement - {{ $supplier->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .supplier-info {
            margin-bottom: 20px;
        }
        .statement-info {
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th,
        .table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .table .text-right {
            text-align: right;
        }
        .table .text-center {
            text-align: center;
        }
        .summary {
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .summary-label {
            font-weight: bold;
        }
        .summary-value {
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SUPPLIER STATEMENT</h1>
    </div>

    <div class="supplier-info">
        <h3>{{ $supplier->name }}</h3>
        <p><strong>Address:</strong> {{ $supplier->address ?? 'N/A' }}</p>
        <p><strong>Phone:</strong> {{ $supplier->phone ?? 'N/A' }}</p>
        <p><strong>Email:</strong> {{ $supplier->email ?? 'N/A' }}</p>
    </div>

    <div class="statement-info">
        <p><strong>Statement Period:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</p>
        <p><strong>Generated:</strong> {{ now()->format('M d, Y H:i') }}</p>
    </div>

    @if($openingBalanceDue != 0)
    <div class="opening-balance">
        <h4>Opening Balance</h4>
        <p><strong>Opening Balance Due:</strong> TZS {{ number_format(abs($openingBalanceDue), 2) }}
        @if($openingBalanceDue >= 0)
            (Amount Owed)
        @else
            (Credit Balance)
        @endif
        </p>
    </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('M d, Y') }}</td>
                    <td>{{ $transaction['type'] }}</td>
                    <td>{{ $transaction['reference'] }}</td>
                    <td>{{ $transaction['description'] }}</td>
                    <td class="text-right">
                        @if($transaction['debit'] > 0)
                            TZS {{ number_format($transaction['debit'], 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if($transaction['credit'] > 0)
                            TZS {{ number_format($transaction['credit'], 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        TZS {{ number_format(abs($transaction['balance']), 2) }}
                        @if($transaction['balance'] >= 0)
                            (Owed)
                        @else
                            (Credit)
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No transactions found for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f5f5f5; font-weight: bold;">
                <td colspan="4" class="text-right">Totals:</td>
                <td class="text-right">TZS {{ number_format($totalDebits, 2) }}</td>
                <td class="text-right">TZS {{ number_format($totalCredits, 2) }}</td>
                <td class="text-right">TZS {{ number_format(abs($finalBalance), 2) }}
                    @if($finalBalance >= 0)
                        (Amount Owed)
                    @else
                        (Credit Balance)
                    @endif
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <h4>Statement Summary</h4>
        <div class="summary-row">
            <span class="summary-label">Opening Balance:</span>
            <span class="summary-value">TZS {{ number_format(abs($openingBalanceDue), 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Debits:</span>
            <span class="summary-value">TZS {{ number_format($totalDebits, 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Credits:</span>
            <span class="summary-value">TZS {{ number_format($totalCredits, 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Final Balance:</span>
            <span class="summary-value">TZS {{ number_format(abs($finalBalance), 2) }}
                @if($finalBalance >= 0)
                    (Amount Owed)
                @else
                    (Credit Balance)
                @endif
            </span>
        </div>
    </div>

    <div class="footer">
        <p>This statement was generated on {{ now()->format('M d, Y H:i') }}</p>
        <p>For any queries regarding this statement, please contact our accounts department.</p>
    </div>
</body>
</html>
