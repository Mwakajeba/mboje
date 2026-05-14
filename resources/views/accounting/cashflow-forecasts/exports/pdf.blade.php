<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashflow Forecast Report</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            background: #fff;
        }
        
        .header {
            margin-bottom: 20px;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #0d6efd;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }
        
        .header-info {
            text-align: center;
            margin-top: 10px;
            color: #666;
            font-size: 12px;
        }
        
        .summary-section {
            margin-bottom: 20px;
        }
        
        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
            background: #f8f9fa;
        }
        
        .summary-card h6 {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .summary-card h4 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th {
            background-color: #0d6efd;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        
        .table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-end {
            text-align: right;
        }
        
        .text-success {
            color: #198754;
        }
        
        .text-danger {
            color: #dc3545;
        }
        
        .fw-bold {
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Cashflow Forecast Report</h1>
        <div class="header-info">
            <strong>{{ $forecast->forecast_name }}</strong><br>
            Period: {{ $forecast->start_date->format('d M Y') }} - {{ $forecast->end_date->format('d M Y') }}<br>
            Scenario: {{ ucfirst(str_replace('_', ' ', $forecast->scenario)) }} | Timeline: {{ ucfirst($forecast->timeline) }}<br>
            @if($forecast->branch)
                Branch: {{ $forecast->branch->name }}<br>
            @endif
            Generated: {{ now()->format('d M Y H:i:s') }}
        </div>
    </div>

    <div class="summary-section">
        <div class="summary-cards">
            <div class="summary-card">
                <h6>Starting Balance</h6>
                <h4>{{ number_format($forecast->starting_cash_balance, 2) }} TZS</h4>
            </div>
            <div class="summary-card">
                <h6>Total Inflows</h6>
                <h4 class="text-success">{{ number_format($forecast->getTotalInflows(), 2) }} TZS</h4>
            </div>
            <div class="summary-card">
                <h6>Total Outflows</h6>
                <h4 class="text-danger">{{ number_format($forecast->getTotalOutflows(), 2) }} TZS</h4>
            </div>
            <div class="summary-card">
                <h6>Net Cashflow</h6>
                <h4 class="{{ $forecast->getNetCashflow() >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($forecast->getNetCashflow(), 2) }} TZS
                </h4>
            </div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-end">Inflows (TZS)</th>
                <th class="text-end">Outflows (TZS)</th>
                <th class="text-end">Net Cashflow (TZS)</th>
                <th class="text-end">Closing Balance (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $date => $data)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td>
                    <td class="text-end text-success">{{ number_format($data['inflows'], 2) }}</td>
                    <td class="text-end text-danger">{{ number_format($data['outflows'], 2) }}</td>
                    <td class="text-end {{ $data['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($data['net'], 2) }}
                    </td>
                    <td class="text-end fw-bold {{ $data['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($data['balance'], 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #e3f2fd; font-weight: bold;">
                <td>Totals</td>
                <td class="text-end text-success">{{ number_format($forecast->getTotalInflows(), 2) }}</td>
                <td class="text-end text-danger">{{ number_format($forecast->getTotalOutflows(), 2) }}</td>
                <td class="text-end {{ $forecast->getNetCashflow() >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($forecast->getNetCashflow(), 2) }}
                </td>
                <td class="text-end">
                    @php
                        $endingBalance = $forecast->starting_cash_balance + $forecast->getNetCashflow();
                    @endphp
                    <span class="{{ $endingBalance >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($endingBalance, 2) }}
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>

    @if($forecast->notes)
        <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-left: 4px solid #0dcaf0;">
            <strong>Notes:</strong> {{ $forecast->notes }}
        </div>
    @endif

    <div class="footer">
        <p>This report was generated on {{ now()->format('d M Y H:i:s') }}</p>
        @if($company)
            <p>{{ $company->name }}</p>
        @endif
    </div>
</body>
</html>

