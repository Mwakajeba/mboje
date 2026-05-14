<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet Report</title>
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
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed;
        }
        
        .data-table thead {
            background: #17a2b8;
            color: white;
        }
        
        .data-table th {
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table th.number {
            text-align: right;
        }
        
        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 9px;
            word-wrap: break-word;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tfoot {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .data-table tfoot td {
            border-top: 2px solid #17a2b8;
            padding: 10px 6px;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .section-header-row {
            background: #e9ecef;
            font-weight: bold;
            font-size: 10px;
        }
        
        .main-group-row {
            background: #d1ecf1;
            font-weight: bold;
            font-size: 9px;
        }
        
        .fsli-group-row {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 9px;
        }
        
        .account-row {
            font-size: 9px;
        }
        
        .account-row td:first-child {
            padding-left: 20px;
        }
        
        .total-row {
            background: #e9ecef;
            font-weight: bold;
            font-size: 10px;
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
        
        @media print {
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @if($company && isset($company->logo) && $company->logo)
                <div class="logo-section">
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $company->name ?? 'Company' }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Balance Sheet Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
                @endif
                <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">As of Date:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Method:</div>
                <div class="info-value">{{ ucfirst($reportingType) }} Basis</div>
            </div>
            <div class="info-row">
                <div class="info-label">Type:</div>
                <div class="info-value">{{ ucfirst($reportType) }}</div>
            </div>
            @php
                $currentBranchName = 'All Branches';
                if ($branchId && $branchId !== 'all') {
                    $currentBranchName = optional($branches->firstWhere('id', $branchId))->name ?? 'All Branches';
                }
            @endphp
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $currentBranchName }}</div>
            </div>
            @if(!empty($comparativeData))
            <div class="info-row">
                <div class="info-label">Comparatives:</div>
                <div class="info-value">
                    @foreach($comparativeData as $compName => $compInfo)
                        {{ $compName }} ({{ \Carbon\Carbon::parse($compInfo['asOfDate'])->format('M d, Y') }})@if(!$loop->last), @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Assets Section -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Account</th>
                <th class="number" style="width: 15%;">Current ({{ \Carbon\Carbon::parse($asOfDate)->format('d-m-Y') }})</th>
                <th class="number" style="width: 15%;">Previous Year ({{ $previousYearData['year'] }})</th>
                @foreach($comparativeData ?? [] as $compName => $compInfo)
                <th class="number" style="width: 12%;">{{ $compName }}</th>
                @endforeach
                <th class="number" style="width: 13%;">Change</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $sumAsset = 0; 
                $sumAssetPrev = 0;
                $comparativeAssetTotals = [];
                foreach ($comparativeData ?? [] as $compName => $compInfo) {
                    $comparativeAssetTotals[$compName] = 0;
                }
            @endphp
            
            <tr class="section-header-row">
                <td colspan="{{ 3 + count($comparativeData ?? []) + 1 }}"><strong>ASSETS</strong></td>
            </tr>
            
            @foreach($financialReportData['chartAccountsAssets'] ?? [] as $mainGroupName => $mainGroup)
                @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                <tr class="main-group-row">
                    <td colspan="{{ 3 + count($comparativeData ?? []) + 1 }}"><strong>{{ $mainGroupName }}</strong></td>
                </tr>
                
                @if(isset($mainGroup['fslis']))
                    @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                        @if(isset($fsli['total']) && $fsli['total'] != 0)
                            @if($reportType === 'detailed' && isset($fsli['accounts']))
                                @foreach($fsli['accounts'] as $account)
                                    @if($account['sum'] != 0)
                                        @php 
                                            $sumAsset += $account['sum'];
                                            $prevYearMainGroup = $previousYearData['chartAccountsAssets'][$mainGroupName] ?? [];
                                            $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                            $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                            $prevYearAccounts = $prevYearFsli['accounts'] ?? [];
                                            $prevYearAccount = collect($prevYearAccounts)->firstWhere('account_id', $account['account_id']);
                                            $prevYearAmount = $prevYearAccount['sum'] ?? 0;
                                            $sumAssetPrev += $prevYearAmount;
                                            
                                            $comparativeAmounts = [];
                                            foreach ($comparativeData ?? [] as $compName => $compInfo) {
                                                $compMainGroup = $compInfo['data']['chartAccountsAssets'][$mainGroupName] ?? [];
                                                $compFslis = $compMainGroup['fslis'] ?? [];
                                                $compFsli = $compFslis[$fsliName] ?? [];
                                                $compAccounts = $compFsli['accounts'] ?? [];
                                                $compAccount = collect($compAccounts)->firstWhere('account_id', $account['account_id']);
                                                $compAmount = $compAccount['sum'] ?? 0;
                                                $comparativeAmounts[$compName] = $compAmount;
                                                $comparativeAssetTotals[$compName] += $compAmount;
                                            }
                                        @endphp
                                        <tr class="account-row">
                                            <td>{{ ($account['account_code'] ?? '') ? $account['account_code'] . ' - ' : '' }}{{ $account['account'] }}</td>
                                            <td class="number">{{ number_format($account['sum'], 2) }}</td>
                                            <td class="number">{{ number_format($prevYearAmount, 2) }}</td>
                                            @foreach($comparativeData ?? [] as $compName => $compInfo)
                                            <td class="number">{{ number_format($comparativeAmounts[$compName] ?? 0, 2) }}</td>
                                            @endforeach
                                            <td class="number">{{ ($account['sum'] - $prevYearAmount) >= 0 ? '+' : '' }}{{ number_format($account['sum'] - $prevYearAmount, 2) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @else
                                @php 
                                    $fsliTotal = $fsli['total'] ?? 0;
                                    $sumAsset += $fsliTotal;
                                    $prevYearMainGroup = $previousYearData['chartAccountsAssets'][$mainGroupName] ?? [];
                                    $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                    $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                    $prevYearFsliTotal = $prevYearFsli['total'] ?? 0;
                                    $sumAssetPrev += $prevYearFsliTotal;
                                    
                                    $comparativeTotals = [];
                                    foreach ($comparativeData ?? [] as $compName => $compInfo) {
                                        $compMainGroup = $compInfo['data']['chartAccountsAssets'][$mainGroupName] ?? [];
                                        $compFslis = $compMainGroup['fslis'] ?? [];
                                        $compFsli = $compFslis[$fsliName] ?? [];
                                        $compFsliTotal = $compFsli['total'] ?? 0;
                                        $comparativeTotals[$compName] = $compFsliTotal;
                                        $comparativeAssetTotals[$compName] += $compFsliTotal;
                                    }
                                @endphp
                                <tr class="fsli-group-row">
                                    <td><strong>{{ $fsliName }}</strong></td>
                                    <td class="number"><strong>{{ number_format($fsliTotal, 2) }}</strong></td>
                                    <td class="number"><strong>{{ number_format($prevYearFsliTotal, 2) }}</strong></td>
                                    @foreach($comparativeData ?? [] as $compName => $compInfo)
                                    <td class="number"><strong>{{ number_format($comparativeTotals[$compName] ?? 0, 2) }}</strong></td>
                                    @endforeach
                                    <td class="number"><strong>{{ ($fsliTotal - $prevYearFsliTotal) >= 0 ? '+' : '' }}{{ number_format($fsliTotal - $prevYearFsliTotal, 2) }}</strong></td>
                                </tr>
                            @endif
                        @endif
                    @endforeach
                @endif
                @endif
            @endforeach
            
            @php 
                // Calculate comparative totals for assets
                foreach ($comparativeData ?? [] as $compName => $compInfo) {
                    $compTotal = 0;
                    foreach ($compInfo['data']['chartAccountsAssets'] ?? [] as $compMainGroup) {
                        if (isset($compMainGroup['total'])) {
                            $compTotal += $compMainGroup['total'];
                        }
                    }
                    $comparativeAssetTotals[$compName] = $compTotal;
                }
                $assetChange = $sumAsset - $sumAssetPrev;
            @endphp
            
            <tr class="total-row">
                <td><strong>TOTAL ASSETS</strong></td>
                <td class="number"><strong>{{ number_format($sumAsset, 2) }}</strong></td>
                <td class="number"><strong>{{ number_format($sumAssetPrev, 2) }}</strong></td>
                @foreach($comparativeData ?? [] as $compName => $compInfo)
                <td class="number"><strong>{{ number_format($comparativeAssetTotals[$compName] ?? 0, 2) }}</strong></td>
                @endforeach
                <td class="number"><strong>{{ $assetChange >= 0 ? '+' : '' }}{{ number_format($assetChange, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Equity Section -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Account</th>
                <th class="number" style="width: 15%;">Current ({{ \Carbon\Carbon::parse($asOfDate)->format('d-m-Y') }})</th>
                <th class="number" style="width: 15%;">Previous Year ({{ $previousYearData['year'] }})</th>
                @foreach($comparativeData ?? [] as $compName => $compInfo)
                <th class="number" style="width: 12%;">{{ $compName }}</th>
                @endforeach
                <th class="number" style="width: 13%;">Change</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $sumEquity = 0; 
                $sumEquityPrev = 0;
                $comparativeEquityTotals = [];
                foreach ($comparativeData ?? [] as $compName => $compInfo) {
                    $comparativeEquityTotals[$compName] = 0;
                }
            @endphp
            
            <tr class="section-header-row">
                <td colspan="{{ 3 + count($comparativeData ?? []) + 1 }}"><strong>EQUITY</strong></td>
            </tr>
            
            @foreach($financialReportData['chartAccountsEquitys'] ?? [] as $mainGroupName => $mainGroup)
                @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                <tr class="main-group-row">
                    <td colspan="{{ 3 + count($comparativeData ?? []) + 1 }}"><strong>{{ $mainGroupName }}</strong></td>
                </tr>
                
                @if(isset($mainGroup['fslis']))
                    @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                        @if(isset($fsli['total']) && $fsli['total'] != 0)
                            @if($reportType === 'detailed' && isset($fsli['accounts']))
                                @foreach($fsli['accounts'] as $account)
                                    @if($account['sum'] != 0)
                                        @php 
                                            $sumEquity += $account['sum'];
                                            $prevYearMainGroup = $previousYearData['chartAccountsEquitys'][$mainGroupName] ?? [];
                                            $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                            $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                            $prevYearAccounts = $prevYearFsli['accounts'] ?? [];
                                            $prevYearAccount = collect($prevYearAccounts)->firstWhere('account_id', $account['account_id']);
                                            $prevYearAmount = $prevYearAccount['sum'] ?? 0;
                                            $sumEquityPrev += $prevYearAmount;
                                            
                                            $comparativeAmounts = [];
                                            foreach ($comparativeData ?? [] as $compName => $compInfo) {
                                                $compMainGroup = $compInfo['data']['chartAccountsEquitys'][$mainGroupName] ?? [];
                                                $compFslis = $compMainGroup['fslis'] ?? [];
                                                $compFsli = $compFslis[$fsliName] ?? [];
                                                $compAccounts = $compFsli['accounts'] ?? [];
                                                $compAccount = collect($compAccounts)->firstWhere('account_id', $account['account_id']);
                                                $compAmount = $compAccount['sum'] ?? 0;
                                                $comparativeAmounts[$compName] = $compAmount;
                                                $comparativeEquityTotals[$compName] += $compAmount;
                                            }
                                        @endphp
                                        <tr class="account-row">
                                            <td>{{ ($account['account_code'] ?? '') ? $account['account_code'] . ' - ' : '' }}{{ $account['account'] }}</td>
                                            <td class="number">{{ number_format($account['sum'], 2) }}</td>
                                            <td class="number">{{ number_format($prevYearAmount, 2) }}</td>
                                            @foreach($comparativeData ?? [] as $compName => $compInfo)
                                            <td class="number">{{ number_format($comparativeAmounts[$compName] ?? 0, 2) }}</td>
                                            @endforeach
                                            <td class="number">{{ ($account['sum'] - $prevYearAmount) >= 0 ? '+' : '' }}{{ number_format($account['sum'] - $prevYearAmount, 2) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @else
                                @php 
                                    $fsliTotal = $fsli['total'] ?? 0;
                                    $sumEquity += $fsliTotal;
                                    $prevYearMainGroup = $previousYearData['chartAccountsEquitys'][$mainGroupName] ?? [];
                                    $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                    $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                    $prevYearFsliTotal = $prevYearFsli['total'] ?? 0;
                                    $sumEquityPrev += $prevYearFsliTotal;
                                    
                                    $comparativeTotals = [];
                                    foreach ($comparativeData ?? [] as $compName => $compInfo) {
                                        $compMainGroup = $compInfo['data']['chartAccountsEquitys'][$mainGroupName] ?? [];
                                        $compFslis = $compMainGroup['fslis'] ?? [];
                                        $compFsli = $compFslis[$fsliName] ?? [];
                                        $compFsliTotal = $compFsli['total'] ?? 0;
                                        $comparativeTotals[$compName] = $compFsliTotal;
                                        $comparativeEquityTotals[$compName] += $compFsliTotal;
                                    }
                                @endphp
                                <tr class="fsli-group-row">
                                    <td><strong>{{ $fsliName }}</strong></td>
                                    <td class="number"><strong>{{ number_format($fsliTotal, 2) }}</strong></td>
                                    <td class="number"><strong>{{ number_format($prevYearFsliTotal, 2) }}</strong></td>
                                    @foreach($comparativeData ?? [] as $compName => $compInfo)
                                    <td class="number"><strong>{{ number_format($comparativeTotals[$compName] ?? 0, 2) }}</strong></td>
                                    @endforeach
                                    <td class="number"><strong>{{ ($fsliTotal - $prevYearFsliTotal) >= 0 ? '+' : '' }}{{ number_format($fsliTotal - $prevYearFsliTotal, 2) }}</strong></td>
                                </tr>
                            @endif
                        @endif
                    @endforeach
                @endif
                @endif
            @endforeach
            
            @php 
                // Calculate comparative totals for equity
                foreach ($comparativeData ?? [] as $compName => $compInfo) {
                    $compTotal = 0;
                    foreach ($compInfo['data']['chartAccountsEquitys'] ?? [] as $compMainGroup) {
                        if (isset($compMainGroup['total'])) {
                            $compTotal += $compMainGroup['total'];
                        }
                    }
                    $comparativeEquityTotals[$compName] = $compTotal;
                }
            @endphp
            
            <tr>
                <td>Profit And Loss (YTD)</td>
                <td class="number">{{ number_format($netProfitYtd, 2) }}</td>
                <td class="number">{{ number_format($previousYearData['profitLoss'], 2) }}</td>
                @foreach($comparativeData ?? [] as $compName => $compInfo)
                <td class="number">{{ number_format($compInfo['netProfitYtd'] ?? 0, 2) }}</td>
                @endforeach
                <td class="number">{{ ($netProfitYtd - $previousYearData['profitLoss']) >= 0 ? '+' : '' }}{{ number_format($netProfitYtd - $previousYearData['profitLoss'], 2) }}</td>
            </tr>
            
            <tr class="total-row">
                <td><strong>TOTAL EQUITY</strong></td>
                <td class="number"><strong>{{ number_format($sumEquity + $netProfitYtd, 2) }}</strong></td>
                <td class="number"><strong>{{ number_format($sumEquityPrev + $previousYearData['profitLoss'], 2) }}</strong></td>
                @foreach($comparativeData ?? [] as $compName => $compInfo)
                <td class="number"><strong>{{ number_format($comparativeEquityTotals[$compName] + ($compInfo['netProfitYtd'] ?? 0), 2) }}</strong></td>
                @endforeach
                <td class="number"><strong>{{ (($sumEquity + $netProfitYtd) - ($sumEquityPrev + $previousYearData['profitLoss'])) >= 0 ? '+' : '' }}{{ number_format(($sumEquity + $netProfitYtd) - ($sumEquityPrev + $previousYearData['profitLoss']), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Liabilities Section -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Account</th>
                <th class="number" style="width: 15%;">Current ({{ \Carbon\Carbon::parse($asOfDate)->format('d-m-Y') }})</th>
                <th class="number" style="width: 15%;">Previous Year ({{ $previousYearData['year'] }})</th>
                @foreach($comparativeData ?? [] as $compName => $compInfo)
                <th class="number" style="width: 12%;">{{ $compName }}</th>
                @endforeach
                <th class="number" style="width: 13%;">Change</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $sumLiability = 0; 
                $sumLiabilityPrev = 0;
                $comparativeLiabilityTotals = [];
                foreach ($comparativeData ?? [] as $compName => $compInfo) {
                    $comparativeLiabilityTotals[$compName] = 0;
                }
            @endphp
            
            <tr class="section-header-row">
                <td colspan="{{ 3 + count($comparativeData ?? []) + 1 }}"><strong>LIABILITIES</strong></td>
            </tr>
            
            @foreach($financialReportData['chartAccountsLiabilities'] ?? [] as $mainGroupName => $mainGroup)
                @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                <tr class="main-group-row">
                    <td colspan="{{ 3 + count($comparativeData ?? []) + 1 }}"><strong>{{ $mainGroupName }}</strong></td>
                </tr>
                
                @if(isset($mainGroup['fslis']))
                    @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                        @if(isset($fsli['total']) && $fsli['total'] != 0)
                            @if($reportType === 'detailed' && isset($fsli['accounts']))
                                @foreach($fsli['accounts'] as $account)
                                    @if($account['sum'] != 0)
                                        @php 
                                            $sumLiability += $account['sum'];
                                            $prevYearMainGroup = $previousYearData['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                            $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                            $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                            $prevYearAccounts = $prevYearFsli['accounts'] ?? [];
                                            $prevYearAccount = collect($prevYearAccounts)->firstWhere('account_id', $account['account_id']);
                                            $prevYearAmount = $prevYearAccount['sum'] ?? 0;
                                            $sumLiabilityPrev += $prevYearAmount;
                                            
                                            $comparativeAmounts = [];
                                            foreach ($comparativeData ?? [] as $compName => $compInfo) {
                                                $compMainGroup = $compInfo['data']['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                                $compFslis = $compMainGroup['fslis'] ?? [];
                                                $compFsli = $compFslis[$fsliName] ?? [];
                                                $compAccounts = $compFsli['accounts'] ?? [];
                                                $compAccount = collect($compAccounts)->firstWhere('account_id', $account['account_id']);
                                                $compAmount = $compAccount['sum'] ?? 0;
                                                $comparativeAmounts[$compName] = $compAmount;
                                                $comparativeLiabilityTotals[$compName] += $compAmount;
                                            }
                                        @endphp
                                        <tr class="account-row">
                                            <td>{{ ($account['account_code'] ?? '') ? $account['account_code'] . ' - ' : '' }}{{ $account['account'] }}</td>
                                            <td class="number">{{ number_format($account['sum'], 2) }}</td>
                                            <td class="number">{{ number_format($prevYearAmount, 2) }}</td>
                                            @foreach($comparativeData ?? [] as $compName => $compInfo)
                                            <td class="number">{{ number_format($comparativeAmounts[$compName] ?? 0, 2) }}</td>
                                            @endforeach
                                            <td class="number">{{ ($account['sum'] - $prevYearAmount) >= 0 ? '+' : '' }}{{ number_format($account['sum'] - $prevYearAmount, 2) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @else
                                @php 
                                    $fsliTotal = $fsli['total'] ?? 0;
                                    $sumLiability += $fsliTotal;
                                    $prevYearMainGroup = $previousYearData['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                    $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                    $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                    $prevYearFsliTotal = $prevYearFsli['total'] ?? 0;
                                    $sumLiabilityPrev += $prevYearFsliTotal;
                                    
                                    $comparativeTotals = [];
                                    foreach ($comparativeData ?? [] as $compName => $compInfo) {
                                        $compMainGroup = $compInfo['data']['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                        $compFslis = $compMainGroup['fslis'] ?? [];
                                        $compFsli = $compFslis[$fsliName] ?? [];
                                        $compFsliTotal = $compFsli['total'] ?? 0;
                                        $comparativeTotals[$compName] = $compFsliTotal;
                                        $comparativeLiabilityTotals[$compName] += $compFsliTotal;
                                    }
                                @endphp
                                <tr class="fsli-group-row">
                                    <td><strong>{{ $fsliName }}</strong></td>
                                    <td class="number"><strong>{{ number_format($fsliTotal, 2) }}</strong></td>
                                    <td class="number"><strong>{{ number_format($prevYearFsliTotal, 2) }}</strong></td>
                                    @foreach($comparativeData ?? [] as $compName => $compInfo)
                                    <td class="number"><strong>{{ number_format($comparativeTotals[$compName] ?? 0, 2) }}</strong></td>
                                    @endforeach
                                    <td class="number"><strong>{{ ($fsliTotal - $prevYearFsliTotal) >= 0 ? '+' : '' }}{{ number_format($fsliTotal - $prevYearFsliTotal, 2) }}</strong></td>
                                </tr>
                            @endif
                        @endif
                    @endforeach
                @endif
                @endif
            @endforeach
            
            @php 
                // Calculate comparative totals for liabilities
                foreach ($comparativeData ?? [] as $compName => $compInfo) {
                    $compTotal = 0;
                    foreach ($compInfo['data']['chartAccountsLiabilities'] ?? [] as $compMainGroup) {
                        if (isset($compMainGroup['total'])) {
                            $compTotal += $compMainGroup['total'];
                        }
                    }
                    $comparativeLiabilityTotals[$compName] = $compTotal;
                }
            @endphp
            
            <tr class="total-row">
                <td><strong>TOTAL LIABILITIES</strong></td>
                <td class="number"><strong>{{ number_format($sumLiability, 2) }}</strong></td>
                <td class="number"><strong>{{ number_format($sumLiabilityPrev, 2) }}</strong></td>
                @foreach($comparativeData ?? [] as $compName => $compInfo)
                <td class="number"><strong>{{ number_format($comparativeLiabilityTotals[$compName] ?? 0, 2) }}</strong></td>
                @endforeach
                <td class="number"><strong>{{ ($sumLiability - $sumLiabilityPrev) >= 0 ? '+' : '' }}{{ number_format($sumLiability - $sumLiabilityPrev, 2) }}</strong></td>
            </tr>
            
            <tr class="total-row">
                <td><strong>TOTAL EQUITY & LIABILITY</strong></td>
                <td class="number"><strong>{{ number_format($sumLiability + $sumEquity + $netProfitYtd, 2) }}</strong></td>
                <td class="number"><strong>{{ number_format($sumLiabilityPrev + $sumEquityPrev + $previousYearData['profitLoss'], 2) }}</strong></td>
                @foreach($comparativeData ?? [] as $compName => $compInfo)
                <td class="number"><strong>{{ number_format($comparativeLiabilityTotals[$compName] + $comparativeEquityTotals[$compName] + ($compInfo['netProfitYtd'] ?? 0), 2) }}</strong></td>
                @endforeach
                <td class="number"><strong>{{ (($sumLiability + $sumEquity + $netProfitYtd) - ($sumLiabilityPrev + $sumEquityPrev + $previousYearData['profitLoss'])) >= 0 ? '+' : '' }}{{ number_format(($sumLiability + $sumEquity + $netProfitYtd) - ($sumLiabilityPrev + $sumEquityPrev + $previousYearData['profitLoss']), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">Balance Sheet Report showing financial position as of {{ \Carbon\Carbon::parse($asOfDate)->format('F d, Y') }} - {{ ucfirst($reportingType) }} Basis</p>
    </div>
</body>
</html>
