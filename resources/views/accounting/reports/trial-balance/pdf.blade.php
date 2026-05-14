<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Balance Report</title>
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
        
        .text-end {
            text-align: right;
        }
        
        .account-code {
            font-size: 9px;
            color: #666;
        }
        
        .total-row {
            background: #e9ecef;
            font-weight: bold;
        }
        
        .total-row td {
            border-top: 2px solid #17a2b8;
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
        
        .page-break {
            page-break-before: always;
        }
        
        .section-header {
            background: #17a2b8;
            color: white;
            font-weight: bold;
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
                <h1>Trial Balance Report</h1>
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
                    @if($startDate == $endDate)
                        As at {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Reporting Type:</div>
                <div class="info-value">{{ ucfirst($reportingType) }} Basis</div>
            </div>
            <div class="info-row">
                <div class="info-label">Layout:</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', ($layout ?? 'single_column'))) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Level of Detail:</div>
                <div class="info-value">{{ ucfirst($levelOfDetail ?? 'detailed') }}</div>
            </div>
            @if(isset($branchId) && $branchId != 'all')
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ collect($branches)->where('id', $branchId)->first()['name'] ?? 'N/A' }}</div>
            </div>
            @endif
        </div>
    </div>

    @php
    $comparatives = $trialBalanceData['comparative'] ?? [];
    $comparativesCount = is_array($comparatives) ? count($comparatives) : 0;
    $layoutKey = $trialBalanceData['layout'] ?? $layout ?? 'single_column';
    
    // Calculate summary stats
    $totalDebit = 0;
    $totalCredit = 0;
    $totalOpening = 0;
    $totalChange = 0;
    $totalClosing = 0;
    
    foreach ($trialBalanceData['data'] as $class => $accounts) {
        foreach ($accounts as $account) {
            if ($layoutKey === 'multi_column') {
                $openingDr = isset($account->opening_debit) ? floatval($account->opening_debit) : 0;
                $openingCr = isset($account->opening_credit) ? floatval($account->opening_credit) : 0;
                $changeDr = isset($account->change_debit) ? floatval($account->change_debit) : 0;
                $changeCr = isset($account->change_credit) ? floatval($account->change_credit) : 0;
                $closingDr = isset($account->closing_debit) ? floatval($account->closing_debit) : 0;
                $closingCr = isset($account->closing_credit) ? floatval($account->closing_credit) : 0;
                
                if (($openingDr + $openingCr + $changeDr + $changeCr + $closingDr + $closingCr) == 0 && isset($account->sum)) {
                    $sumVal = floatval($account->sum);
                    $changeDr = $sumVal > 0 ? $sumVal : 0;
                    $changeCr = $sumVal < 0 ? abs($sumVal) : 0;
                    $closingDr = $changeDr;
                    $closingCr = $changeCr;
                }
                
                $totalOpening += ($openingDr - $openingCr);
                $totalChange += ($changeDr - $changeCr);
                $totalClosing += ($closingDr - $closingCr);
            } else {
                $sumVal = isset($account->sum) ? floatval($account->sum) : 0;
                if ($sumVal != 0) {
                    if ($layoutKey === 'double_column') {
                        $isCredit = isset($account->nature) ? ($account->nature === 'credit') : ($sumVal < 0);
                        $currentDebit = $isCredit ? 0 : abs($sumVal);
                        $currentCredit = $isCredit ? abs($sumVal) : 0;
                        $totalDebit += $currentDebit;
                        $totalCredit += $currentCredit;
                    } else {
                        if ($sumVal < 0) {
                            $totalCredit += abs($sumVal);
                        } else {
                            $totalDebit += $sumVal;
                        }
                    }
                }
            }
        }
    }
    
    $netBalance = $totalDebit - $totalCredit;
    if ($layoutKey === 'multi_column') {
        $netBalance = $totalClosing;
    }
    @endphp

    @if($layoutKey === 'multi_column')
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalOpening, 2) }}</div>
                <div class="stat-label">Opening Balance</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalChange, 2) }}</div>
                <div class="stat-label">Current Change</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalClosing, 2) }}</div>
                <div class="stat-label">Closing Balance</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: {{ $netBalance == 0 ? '#28a745' : '#dc3545' }};">{{ number_format($netBalance, 2) }}</div>
                <div class="stat-label">Net Balance</div>
            </div>
        </div>
    @else
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalDebit, 2) }}</div>
                <div class="stat-label">Total Debit</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($totalCredit, 2) }}</div>
                <div class="stat-label">Total Credit</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: {{ $netBalance == 0 ? '#28a745' : '#dc3545' }};">{{ number_format($netBalance, 2) }}</div>
                <div class="stat-label">Net Balance</div>
            </div>
        </div>
    @endif

    <table class="data-table">
        @if($layoutKey === 'double_column')
            <thead>
                <tr>
                    <th>Account Name</th>
                    <th>Account Code</th>
                    <th class="number">Debit</th>
                    <th class="number">Credit</th>
                    @if($comparativesCount > 0)
                        @foreach($comparatives as $columnName => $comparativeData)
                            <th class="number">Debit ({{ $columnName }})</th>
                            <th class="number">Credit ({{ $columnName }})</th>
                        @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @php
                $totalDebit = 0;
                $totalCredit = 0;
                $comparativeTotals = [];
                @endphp
                @foreach($trialBalanceData['data'] as $class => $accounts)
                    @foreach($accounts as $account)
                        @if(isset($account->sum) && floatval($account->sum) != 0)
                            @php
                            $sumVal = floatval($account->sum ?? 0);
                            $isCredit = isset($account->nature) ? ($account->nature === 'credit') : ($sumVal < 0);
                            $currentDebit = $isCredit ? 0 : abs($sumVal);
                            $currentCredit = $isCredit ? abs($sumVal) : 0;
                            $totalDebit += $currentDebit;
                            $totalCredit += $currentCredit;
                            @endphp
                            <tr>
                                <td>{{ $account->account }}</td>
                                <td class="account-code">{{ $account->account_code }}</td>
                                <td class="number">{{ $currentDebit ? number_format($currentDebit, 2) : '-' }}</td>
                                <td class="number">{{ $currentCredit ? number_format($currentCredit, 2) : '-' }}</td>
                                @if($comparativesCount > 0)
                                    @foreach($comparatives as $columnName => $compData)
                                        @php
                                        $compAccount = collect($compData[$class] ?? [])->first(function($a) use ($account) {
                                            return isset($a->account_code) && $a->account_code == $account->account_code;
                                        });
                                        $compDebit = 0;
                                        $compCredit = 0;
                                        if ($compAccount) {
                                            $compSum = floatval($compAccount->sum ?? 0);
                                            $compIsCredit = isset($compAccount->nature) ? ($compAccount->nature === 'credit') : ($compSum < 0);
                                            $compDebit = $compIsCredit ? 0 : abs($compSum);
                                            $compCredit = $compIsCredit ? abs($compSum) : 0;
                                        }
                                        $comparativeTotals[$columnName]['debit'] = ($comparativeTotals[$columnName]['debit'] ?? 0) + $compDebit;
                                        $comparativeTotals[$columnName]['credit'] = ($comparativeTotals[$columnName]['credit'] ?? 0) + $compCredit;
                                        @endphp
                                        <td class="number">{{ $compDebit ? number_format($compDebit, 2) : '-' }}</td>
                                        <td class="number">{{ $compCredit ? number_format($compCredit, 2) : '-' }}</td>
                                    @endforeach
                                @endif
                            </tr>
                        @endif
                    @endforeach
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-end"><strong>TOTAL</strong></td>
                    <td class="number"><strong>{{ number_format($totalDebit, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalCredit, 2) }}</strong></td>
                    @if($comparativesCount > 0)
                        @foreach($comparatives as $columnName => $ignored)
                            <td class="number"><strong>{{ number_format($comparativeTotals[$columnName]['debit'] ?? 0, 2) }}</strong></td>
                            <td class="number"><strong>{{ number_format($comparativeTotals[$columnName]['credit'] ?? 0, 2) }}</strong></td>
                        @endforeach
                    @endif
                </tr>
                <tr class="total-row">
                    <td colspan="{{ 2 + ($comparativesCount ? ($comparativesCount*2) : 0) }}" class="text-end"><strong>Net Balance (Debit - Credit)</strong></td>
                    <td class="number" style="color: {{ ($totalDebit - $totalCredit) == 0 ? '#28a745' : '#dc3545' }};">
                        <strong>{{ number_format($totalDebit - $totalCredit, 2) }}</strong>
                    </td>
                </tr>
            </tbody>
        @elseif($layoutKey === 'single_column')
            <thead>
                <tr>
                    <th>Account Name</th>
                    <th>Account Code</th>
                    <th class="number">Balance</th>
                    @if($comparativesCount > 0)
                        @foreach($comparatives as $columnName => $comparativeData)
                            <th class="number">Balance ({{ $columnName }})</th>
                        @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @php
                $totalDebit = 0;
                $totalCredit = 0;
                $comparativeTotals = [];
                @endphp
                @foreach($trialBalanceData['data'] as $class => $accounts)
                    @foreach($accounts as $account)
                        @if(isset($account->sum) && floatval($account->sum) != 0)
                            @php
                            $sumVal = floatval($account->sum ?? 0);
                            $balance = $sumVal;
                            if ($balance < 0) {
                                $totalCredit += abs($balance);
                            } else {
                                $totalDebit += $balance;
                            }
                            @endphp
                            <tr>
                                <td>{{ $account->account }}</td>
                                <td class="account-code">{{ $account->account_code }}</td>
                                <td class="number">{{ $balance < 0 ? '('.number_format(abs($balance), 2).')' : number_format($balance, 2) }}</td>
                                @if($comparativesCount > 0)
                                    @foreach($comparatives as $columnName => $compData)
                                        @php
                                        $compAccount = collect($compData[$class] ?? [])->first(function($a) use ($account) {
                                            return isset($a->account_code) && $a->account_code == $account->account_code;
                                        });
                                        $compBal = 0;
                                        if ($compAccount) {
                                            $compBal = floatval($compAccount->sum ?? 0);
                                        }
                                        $comparativeTotals[$columnName] = ($comparativeTotals[$columnName] ?? 0) + $compBal;
                                        @endphp
                                        <td class="number">{{ $compBal < 0 ? '('.number_format(abs($compBal), 2).')' : number_format($compBal, 2) }}</td>
                                    @endforeach
                                @endif
                            </tr>
                        @endif
                    @endforeach
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-end"><strong>Net Balance (Debit - Credit)</strong></td>
                    <td class="number" style="color: {{ ($totalDebit - $totalCredit) == 0 ? '#28a745' : '#dc3545' }};">
                        <strong>{{ number_format($totalDebit - $totalCredit, 2) }}</strong>
                    </td>
                    @if($comparativesCount > 0)
                        @foreach($comparatives as $columnName => $ignored)
                            @php $tv = $comparativeTotals[$columnName] ?? 0; @endphp
                            <td class="number" style="color: {{ $tv == 0 ? '#28a745' : '#dc3545' }};">
                                <strong>{{ $tv < 0 ? '('.number_format(abs($tv), 2).')' : number_format($tv, 2) }}</strong>
                            </td>
                        @endforeach
                    @endif
                </tr>
            </tbody>
        @elseif($layoutKey === 'multi_column')
            <thead>
                <tr>
                    <th>Account Name</th>
                    <th>Account Code</th>
                    <th class="number">Opening Balance</th>
                    <th class="number">Current Year Change</th>
                    <th class="number">Closing Balance</th>
                    <th class="number">Difference</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalOpening = 0;
                $totalChange = 0;
                $totalClosing = 0;
                $totalDiff = 0;
                @endphp
                @foreach($trialBalanceData['data'] as $class => $accounts)
                    @foreach($accounts as $account)
                        @php
                        $openingDr = isset($account->opening_debit) ? floatval($account->opening_debit) : 0;
                        $openingCr = isset($account->opening_credit) ? floatval($account->opening_credit) : 0;
                        $changeDr = isset($account->change_debit) ? floatval($account->change_debit) : 0;
                        $changeCr = isset($account->change_credit) ? floatval($account->change_credit) : 0;
                        $closingDr = isset($account->closing_debit) ? floatval($account->closing_debit) : 0;
                        $closingCr = isset($account->closing_credit) ? floatval($account->closing_credit) : 0;
                        
                        if (($openingDr + $openingCr + $changeDr + $changeCr + $closingDr + $closingCr) == 0 && isset($account->sum)) {
                            $sumVal = floatval($account->sum);
                            $changeDr = $sumVal > 0 ? $sumVal : 0;
                            $changeCr = $sumVal < 0 ? abs($sumVal) : 0;
                            $closingDr = $changeDr;
                            $closingCr = $changeCr;
                        }
                        
                        // Calculate net balances (Debit - Credit), credits will be negative
                        $openingBalance = $openingDr - $openingCr;
                        $changeBalance = $changeDr - $changeCr;
                        $closingBalance = $closingDr - $closingCr;
                        $difference = $closingBalance;
                        
                        $totalOpening += $openingBalance;
                        $totalChange += $changeBalance;
                        $totalClosing += $closingBalance;
                        $totalDiff += $difference;
                        @endphp
                        @if($openingBalance != 0 || $changeBalance != 0 || $closingBalance != 0)
                            <tr>
                                <td>{{ $account->account ?? $account->account_name ?? '' }}</td>
                                <td class="account-code">{{ $account->account_code ?? '' }}</td>
                                <td class="number">{{ $openingBalance != 0 ? number_format($openingBalance, 2) : '-' }}</td>
                                <td class="number">{{ $changeBalance != 0 ? number_format($changeBalance, 2) : '-' }}</td>
                                <td class="number">{{ $closingBalance != 0 ? number_format($closingBalance, 2) : '-' }}</td>
                                <td class="number">{{ $difference != 0 ? number_format($difference, 2) : '-' }}</td>
                            </tr>
                        @endif
                    @endforeach
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-end"><strong>TOTAL</strong></td>
                    <td class="number"><strong>{{ number_format($totalOpening, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalChange, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalClosing, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($totalDiff, 2) }}</strong></td>
                </tr>
            </tbody>
        @endif
    </table>

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
