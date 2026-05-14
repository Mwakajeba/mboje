<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Ledger Report</title>
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
        
        .opening-balance-row {
            background: #e3f2fd;
            font-weight: bold;
        }
        
        .account-total-row {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .summary-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #17a2b8;
        }
        
        .summary-box h3 {
            margin: 0 0 10px 0;
            color: #17a2b8;
            font-size: 16px;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        
        .summary-row {
            display: table-row;
        }
        
        .summary-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 15px 5px 0;
            width: 150px;
            color: #555;
        }
        
        .summary-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
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
                $companyModel = isset($company) ? $company : (function_exists('current_company') ? current_company() : null);
                $logoPath = ($companyModel && !empty($companyModel->logo)) ? public_path('storage/' . $companyModel->logo) : null;
            @endphp
            @if($logoPath && file_exists($logoPath))
                <div class="logo-section">
                    <img src="{{ $logoPath }}" alt="{{ $company->name ?? 'Company' }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>General Ledger Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name ?? 'SmartFinance' }}</div>
                @endif
                <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            @if($startDate === $endDate)
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">As at {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</div>
            </div>
            @else
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Basis:</div>
                <div class="info-value">{{ ucfirst($reportType) }}</div>
            </div>
            @if(isset($groupBy))
            <div class="info-row">
                <div class="info-label">Group By:</div>
                <div class="info-value">{{ ucfirst($groupBy) }}</div>
            </div>
            @endif
            @if(isset($branchName))
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branchName }}</div>
            </div>
            @endif
        </div>
    </div>

    @if(isset($generalLedgerData) && count($generalLedgerData['transactions'] ?? []) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Date</th>
                    <th style="width: 8%;">Account Code</th>
                    <th style="width: 15%;">Account Name</th>
                    <th style="width: 12%;">Customer</th>
                    <th style="width: 10%;">Transaction ID</th>
                    <th style="width: 20%;">Description</th>
                    <th class="number" style="width: 9%;">Debit</th>
                    <th class="number" style="width: 9%;">Credit</th>
                    <th class="number" style="width: 9%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentAccount = null;
                    $currentAccountCode = null;
                    $currentAccountName = null;
                    $accountTotalDebit = 0;
                    $accountTotalCredit = 0;
                @endphp

                @foreach($generalLedgerData['transactions'] as $transaction)
                    @if($currentAccount !== $transaction->chart_account_id)
                        @if($currentAccount !== null)
                            <!-- Account Total Row -->
                            <tr class="account-total-row">
                                <td colspan="6"><strong>Total for {{ $currentAccountCode }} - {{ $currentAccountName }}</strong></td>
                                <td class="number"><strong>{{ number_format($accountTotalDebit, 2) }}</strong></td>
                                <td class="number"><strong>{{ number_format($accountTotalCredit, 2) }}</strong></td>
                                <td class="number"><strong>{{ number_format($accountTotalDebit - $accountTotalCredit, 2) }}</strong></td>
                            </tr>
                        @endif

                        @if($generalLedgerData['filters']['show_opening_balance'] && isset($generalLedgerData['opening_balances'][$transaction->chart_account_id]))
                            @php
                                $openingBalance = $generalLedgerData['opening_balances'][$transaction->chart_account_id];
                                $openingAmount = $openingBalance->total_debit - $openingBalance->total_credit;
                            @endphp
                            <tr class="opening-balance-row">
                                <td>{{ \Carbon\Carbon::parse($startDate)->subDay()->format('M d, Y') }}</td>
                                <td>{{ $transaction->account_code }}</td>
                                <td>{{ $transaction->account_name }}</td>
                                <td>N/A</td>
                                <td>OPENING BALANCE</td>
                                <td>Balance brought forward</td>
                                <td class="number">{{ $openingAmount >= 0 ? number_format($openingAmount, 2) : '' }}</td>
                                <td class="number">{{ $openingAmount < 0 ? number_format(abs($openingAmount), 2) : '' }}</td>
                                <td class="number"><strong>{{ number_format($openingAmount, 2) }}</strong></td>
                            </tr>
                        @endif

                        @php
                            $currentAccount = $transaction->chart_account_id;
                            $currentAccountCode = $transaction->account_code;
                            $currentAccountName = $transaction->account_name;
                            $accountTotalDebit = 0;
                            $accountTotalCredit = 0;
                        @endphp
                    @endif

                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction->date)->format('M d, Y') }}</td>
                        <td>{{ $transaction->account_code }}</td>
                        <td>{{ $transaction->account_name }}</td>
                        <td>{{ $transaction->customer_name ?? 'N/A' }}</td>
                        <td>{{ $transaction->transaction_id }}</td>
                        <td>{{ $transaction->description }}</td>
                        <td class="number">{{ $transaction->nature === 'debit' ? number_format($transaction->amount, 2) : '' }}</td>
                        <td class="number">{{ $transaction->nature === 'credit' ? number_format($transaction->amount, 2) : '' }}</td>
                        <td class="number"><strong>{{ number_format($transaction->running_balance, 2) }}</strong></td>
                    </tr>

                    @php
                        if ($transaction->nature === 'debit') {
                            $accountTotalDebit += $transaction->amount;
                        } else {
                            $accountTotalCredit += $transaction->amount;
                        }
                    @endphp
                @endforeach

                @if($currentAccount !== null)
                    <!-- Final Account Total Row -->
                    @php
                        $lastTransaction = end($generalLedgerData['transactions']);
                    @endphp
                    <tr class="account-total-row">
                        <td colspan="6"><strong>Total for {{ $lastTransaction->account_code }} - {{ $lastTransaction->account_name }}</strong></td>
                        <td class="number"><strong>{{ number_format($accountTotalDebit, 2) }}</strong></td>
                        <td class="number"><strong>{{ number_format($accountTotalCredit, 2) }}</strong></td>
                        <td class="number"><strong>{{ number_format($accountTotalDebit - $accountTotalCredit, 2) }}</strong></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="summary-box">
            <h3>Report Summary</h3>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-label">Total Debit:</div>
                    <div class="summary-value">{{ number_format($generalLedgerData['summary']['total_debit'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Total Credit:</div>
                    <div class="summary-value">{{ number_format($generalLedgerData['summary']['total_credit'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Net Movement:</div>
                    <div class="summary-value">{{ number_format($generalLedgerData['summary']['net_movement'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Transaction Count:</div>
                    <div class="summary-value">{{ $generalLedgerData['summary']['transaction_count'] }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Account Count:</div>
                    <div class="summary-value">{{ $generalLedgerData['summary']['account_count'] }}</div>
                </div>
            </div>
        </div>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No general ledger data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">
            General Ledger Report showing all transactions
            @if($startDate === $endDate)
                as at {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}
            @else
                from {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}
            @endif
            - {{ ucfirst($reportType) }} Basis
        </p>
    </div>
</body>
</html>
