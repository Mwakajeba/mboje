<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reconciliation Summary Movement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
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
            padding: 4px;
            text-align: left;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 8px;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .bg-success {
            background-color: #28a745;
            color: white;
        }
        .bg-danger {
            background-color: #dc3545;
            color: white;
        }
        .text-success {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>📌 Report 3: Reconciliation Summary Movement</h2>
        <p>Generated: {{ now()->format('d F Y H:i:s') }}</p>
        <p>Period: {{ date('F Y', strtotime($startMonth . '-01')) }} - {{ date('F Y', strtotime($endMonth . '-01')) }}</p>
        @if($bankAccountId)
            <p>Bank Account: {{ $bankAccounts->find($bankAccountId)->name ?? 'N/A' }}</p>
        @endif
    </div>

    <!-- Purpose -->
    <p style="font-size: 9px; margin-bottom: 10px;">
        <strong>Purpose:</strong> Monthly control, board reporting, fraud detection
    </p>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;">Month</th>
                <th colspan="4" class="text-center bg-success">DNC (Deposits Not Credited)</th>
                <th colspan="4" class="text-center bg-danger">UPC (Unpresented Cheques)</th>
            </tr>
            <tr>
                <th class="bg-success" style="width: 11%;">Opening Outstanding</th>
                <th class="bg-success" style="width: 11%;">Cleared This Month</th>
                <th class="bg-success" style="width: 11%;">New Uncleared</th>
                <th class="bg-success" style="width: 11%;">Closing Outstanding</th>
                <th class="bg-danger" style="width: 11%;">Opening Outstanding</th>
                <th class="bg-danger" style="width: 11%;">Cleared This Month</th>
                <th class="bg-danger" style="width: 11%;">New Uncleared</th>
                <th class="bg-danger" style="width: 11%;">Closing Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @forelse($monthlyData as $month => $data)
            <tr>
                <td class="fw-bold">{{ $data['month'] }}</td>
                <td class="text-end">{{ number_format($data['dnc']['opening'], 2) }}</td>
                <td class="text-end text-success">{{ number_format($data['dnc']['cleared'], 2) }}</td>
                <td class="text-end">{{ number_format($data['dnc']['new'], 2) }}</td>
                <td class="text-end fw-bold">{{ number_format($data['dnc']['closing'], 2) }}</td>
                <td class="text-end">{{ number_format($data['upc']['opening'], 2) }}</td>
                <td class="text-end text-success">{{ number_format($data['upc']['cleared'], 2) }}</td>
                <td class="text-end">{{ number_format($data['upc']['new'], 2) }}</td>
                <td class="text-end fw-bold">{{ number_format($data['upc']['closing'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center">No reconciliation data found for the selected period</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($monthlyData) > 0)
        <tfoot>
            <tr style="background-color: #e9ecef;">
                <td class="fw-bold">Totals</td>
                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('dnc.opening'), 2) }}</td>
                <td class="text-end fw-bold text-success">{{ number_format(collect($monthlyData)->sum('dnc.cleared'), 2) }}</td>
                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('dnc.new'), 2) }}</td>
                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('dnc.closing'), 2) }}</td>
                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('upc.opening'), 2) }}</td>
                <td class="text-end fw-bold text-success">{{ number_format(collect($monthlyData)->sum('upc.cleared'), 2) }}</td>
                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('upc.new'), 2) }}</td>
                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('upc.closing'), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>

