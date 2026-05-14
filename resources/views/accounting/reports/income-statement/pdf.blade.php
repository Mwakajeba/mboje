<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Statement Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            background: #fff;
        }
        
        .header {
            margin-bottom: 20px;
            border-bottom: 3px solid #17a2b8;
            padding-bottom: 15px;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        
        .logo-section {
            flex-shrink: 0;
        }
        
        .company-logo {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }
        
        .title-section {
            text-align: center;
            flex-grow: 1;
        }
        
        .header h1 {
            color: #17a2b8;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .company-name {
            color: #333;
            margin: 5px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .header .subtitle {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        
        .report-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #17a2b8;
        }
        
        .report-info h3 {
            margin: 0 0 10px 0;
            color: #17a2b8;
            font-size: 16px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 15px 5px 0;
            width: 120px;
            color: #555;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
        }
        
        .summary-stats {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 12px 8px;
            border-right: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #17a2b8;
            margin: 0;
        }
        
        .stat-label {
            font-size: 9px;
            color: #666;
            margin: 3px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .data-table thead {
            background: #17a2b8;
            color: white;
        }
        
        .data-table th {
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
            word-wrap: break-word;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .section-header {
            background: #17a2b8;
            color: white;
            font-weight: bold;
        }
        
        .main-group-header {
            background: #e9ecef;
            font-weight: bold;
        }
        
        .fsli-header {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .total-row {
            background: #e9ecef;
            font-weight: bold;
        }
        
        .total-row td {
            border-top: 2px solid #17a2b8;
        }
        
        .profit-loss-row {
            background: #343a40;
            color: white;
            font-weight: bold;
        }
        
        .account-code {
            font-size: 9px;
            color: #666;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
            $logoData = null;
            if ($company && !empty($company->logo)) {
                $logoPath = storage_path('app/public/' . $company->logo);
                if (file_exists($logoPath)) {
                    $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
                }
            }
            @endphp
            @if($logoData)
                <div class="logo-section">
                    <img src="{{ $logoData }}" alt="{{ $company->name ?? 'Company' }} Logo" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Income Statement Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">
                    @if($incomeStatementData['start_date'] === $incomeStatementData['end_date'])
                        As at {{ \Carbon\Carbon::parse($incomeStatementData['end_date'])->format('M d, Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($incomeStatementData['start_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($incomeStatementData['end_date'])->format('M d, Y') }}
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Reporting Type:</div>
                <div class="info-value">{{ ucfirst($incomeStatementData['reporting_type']) }} Basis</div>
            </div>
        </div>
    </div>

    @php
    $comparatives = $incomeStatementData['comparative'] ?? [];
    $comparativesCount = is_array($comparatives) ? count($comparatives) : 0;
    
    // Calculate summary stats
    $totalRevenue = $incomeStatementData['data']['total_revenue'] ?? 0;
    $totalExpenses = abs($incomeStatementData['data']['total_expenses'] ?? 0);
    $netIncome = $incomeStatementData['data']['profit_loss'] ?? 0;
    @endphp

    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value" style="color: #28a745;">{{ number_format($totalRevenue, 2) }}</div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-item">
            <div class="stat-value" style="color: #dc3545;">{{ number_format($totalExpenses, 2) }}</div>
            <div class="stat-label">Total Expenses</div>
        </div>
        <div class="stat-item">
            <div class="stat-value" style="color: {{ $netIncome >= 0 ? '#28a745' : '#dc3545' }};">{{ number_format($netIncome, 2) }}</div>
            <div class="stat-label">Net Income</div>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Account</th>
                <th class="number">Current Period</th>
                @if($comparativesCount)
                    @foreach($comparatives as $label => $comp)
                        <th class="number">{{ $label }}</th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            <!-- Revenue Section -->
            <tr class="section-header">
                <td colspan="{{ 1 + $comparativesCount + 1 }}">REVENUE</td>
            </tr>
            @php
                $revenueTotalCurrent = 0;
                $compRevenueTotals = [];
            @endphp
            @foreach($incomeStatementData['data']['revenues'] as $mainGroupName => $mainGroup)
                @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                    <tr class="main-group-header">
                        <td colspan="{{ 1 + $comparativesCount + 1 }}">{{ $mainGroupName }}</td>
                    </tr>
                    @if(isset($mainGroup['fslis']))
                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                            @if(isset($fsli['total']) && $fsli['total'] != 0)
                                <tr class="fsli-header">
                                    <td style="padding-left: 20px;">{{ $fsliName }}</td>
                                    <td class="number">{{ number_format($fsli['total'] ?? 0, 2) }}</td>
                                    @if($comparativesCount)
                                        @foreach($comparatives as $label => $ignored)
                                            @php
                                                $prevMainGroup = $comparatives[$label]['revenues'][$mainGroupName] ?? [];
                                                $prevFslis = $prevMainGroup['fslis'] ?? [];
                                                $prevFsli = $prevFslis[$fsliName] ?? [];
                                                $fsliTotal = $prevFsli['total'] ?? 0;
                                            @endphp
                                            <td class="number">{{ number_format($fsliTotal, 2) }}</td>
                                        @endforeach
                                    @endif
                                </tr>
                                @if(isset($fsli['accounts']))
                                    @foreach($fsli['accounts'] as $account)
                                        @if($account['sum'] != 0)
                                            @php
                                                $revenueTotalCurrent += $account['sum'];
                                                $rowComps = [];
                                                foreach ($comparatives as $label => $cdata) {
                                                    $prevMainGroup = $cdata['revenues'][$mainGroupName] ?? [];
                                                    $prevFslis = $prevMainGroup['fslis'] ?? [];
                                                    $prevFsli = $prevFslis[$fsliName] ?? [];
                                                    $prevAccounts = $prevFsli['accounts'] ?? [];
                                                    $prev = collect($prevAccounts)->firstWhere('account_id', $account['account_id'])['sum'] ?? 0;
                                                    $rowComps[$label] = $prev;
                                                    $compRevenueTotals[$label] = ($compRevenueTotals[$label] ?? 0) + $prev;
                                                }
                                            @endphp
                                            <tr>
                                                <td style="padding-left: 40px;">
                                                    @if($account['account_code'] ?? '')
                                                        <span class="account-code">{{ $account['account_code'] }} - </span>
                                                    @endif
                                                    {{ $account['account'] }}
                                                </td>
                                                <td class="number">{{ number_format($account['sum'], 2) }}</td>
                                                @if($comparativesCount)
                                                    @foreach($comparatives as $label => $ignored)
                                                        <td class="number">{{ number_format($rowComps[$label] ?? 0, 2) }}</td>
                                                    @endforeach
                                                @endif
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                        @endforeach
                    @endif
                    <tr class="total-row">
                        <td style="padding-left: 10px;"><strong>Total {{ $mainGroupName }}</strong></td>
                        <td class="number"><strong>{{ number_format($mainGroup['total'] ?? 0, 2) }}</strong></td>
                        @if($comparativesCount)
                            @foreach($comparatives as $label => $ignored)
                                @php
                                    $prevMainGroup = $comparatives[$label]['revenues'][$mainGroupName] ?? [];
                                    $mainGroupTotal = $prevMainGroup['total'] ?? 0;
                                @endphp
                                <td class="number"><strong>{{ number_format($mainGroupTotal, 2) }}</strong></td>
                            @endforeach
                        @endif
                    </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td><strong>Total Revenue</strong></td>
                <td class="number"><strong>{{ number_format($revenueTotalCurrent, 2) }}</strong></td>
                @if($comparativesCount)
                    @foreach($comparatives as $label => $ignored)
                        <td class="number"><strong>{{ number_format($compRevenueTotals[$label] ?? 0, 2) }}</strong></td>
                    @endforeach
                @endif
            </tr>

            <!-- Expense Section -->
            <tr class="section-header" style="background: #dc3545;">
                <td colspan="{{ 1 + $comparativesCount + 1 }}">EXPENSES</td>
            </tr>
            @php
                $expenseTotalCurrent = 0;
                $compExpenseTotals = [];
            @endphp
            @foreach($incomeStatementData['data']['expenses'] as $mainGroupName => $mainGroup)
                @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                    <tr class="main-group-header">
                        <td colspan="{{ 1 + $comparativesCount + 1 }}">{{ $mainGroupName }}</td>
                    </tr>
                    @if(isset($mainGroup['fslis']))
                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                            @if(isset($fsli['total']) && $fsli['total'] != 0)
                                <tr class="fsli-header">
                                    <td style="padding-left: 20px;">{{ $fsliName }}</td>
                                    <td class="number">{{ number_format(abs($fsli['total'] ?? 0), 2) }}</td>
                                    @if($comparativesCount)
                                        @foreach($comparatives as $label => $ignored)
                                            @php
                                                $prevMainGroup = $comparatives[$label]['expenses'][$mainGroupName] ?? [];
                                                $prevFslis = $prevMainGroup['fslis'] ?? [];
                                                $prevFsli = $prevFslis[$fsliName] ?? [];
                                                $fsliTotal = abs($prevFsli['total'] ?? 0);
                                            @endphp
                                            <td class="number">{{ number_format($fsliTotal, 2) }}</td>
                                        @endforeach
                                    @endif
                                </tr>
                                @if(isset($fsli['accounts']))
                                    @foreach($fsli['accounts'] as $account)
                                        @if($account['sum'] != 0)
                                            @php
                                                $expenseTotalCurrent += abs($account['sum']);
                                                $rowComps = [];
                                                foreach ($comparatives as $label => $cdata) {
                                                    $prevMainGroup = $cdata['expenses'][$mainGroupName] ?? [];
                                                    $prevFslis = $prevMainGroup['fslis'] ?? [];
                                                    $prevFsli = $prevFslis[$fsliName] ?? [];
                                                    $prevAccounts = $prevFsli['accounts'] ?? [];
                                                    $prev = abs(collect($prevAccounts)->firstWhere('account_id', $account['account_id'])['sum'] ?? 0);
                                                    $rowComps[$label] = $prev;
                                                    $compExpenseTotals[$label] = ($compExpenseTotals[$label] ?? 0) + $prev;
                                                }
                                            @endphp
                                            <tr>
                                                <td style="padding-left: 40px;">
                                                    @if($account['account_code'] ?? '')
                                                        <span class="account-code">{{ $account['account_code'] }} - </span>
                                                    @endif
                                                    {{ $account['account'] }}
                                                </td>
                                                <td class="number">{{ number_format(abs($account['sum']), 2) }}</td>
                                                @if($comparativesCount)
                                                    @foreach($comparatives as $label => $ignored)
                                                        <td class="number">{{ number_format($rowComps[$label] ?? 0, 2) }}</td>
                                                    @endforeach
                                                @endif
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                        @endforeach
                    @endif
                    <tr class="total-row">
                        <td style="padding-left: 10px;"><strong>Total {{ $mainGroupName }}</strong></td>
                        <td class="number"><strong>{{ number_format(abs($mainGroup['total'] ?? 0), 2) }}</strong></td>
                        @if($comparativesCount)
                            @foreach($comparatives as $label => $ignored)
                                @php
                                    $prevMainGroup = $comparatives[$label]['expenses'][$mainGroupName] ?? [];
                                    $mainGroupTotal = abs($prevMainGroup['total'] ?? 0);
                                @endphp
                                <td class="number"><strong>{{ number_format($mainGroupTotal, 2) }}</strong></td>
                            @endforeach
                        @endif
                    </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td><strong>Total Expenses</strong></td>
                <td class="number"><strong>{{ number_format($expenseTotalCurrent, 2) }}</strong></td>
                @if($comparativesCount)
                    @foreach($comparatives as $label => $ignored)
                        <td class="number"><strong>{{ number_format($compExpenseTotals[$label] ?? 0, 2) }}</strong></td>
                    @endforeach
                @endif
            </tr>

            <!-- Net Income -->
            <tr class="profit-loss-row">
                <td><strong>Net Income</strong></td>
                @php $netCurrent = $revenueTotalCurrent - $expenseTotalCurrent; @endphp
                <td class="number"><strong>{{ number_format($netCurrent, 2) }}</strong></td>
                @if($comparativesCount)
                    @foreach($comparatives as $label => $ignored)
                        @php $netComp = ($compRevenueTotals[$label] ?? 0) - ($compExpenseTotals[$label] ?? 0); @endphp
                        <td class="number"><strong>{{ number_format($netComp, 2) }}</strong></td>
                    @endforeach
                @endif
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
