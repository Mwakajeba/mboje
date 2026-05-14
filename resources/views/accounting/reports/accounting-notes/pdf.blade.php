<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Notes Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'DejaVu Sans', sans-serif;
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
            width: 150px;
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
            font-size: 14px;
            font-weight: bold;
            color: #17a2b8;
            margin: 0;
        }
        
        .stat-label {
            font-size: 8px;
            color: #666;
            margin: 3px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .section-title {
            background: #17a2b8;
            color: white;
            padding: 10px 15px;
            margin: 20px 0 0 0;
            border-radius: 8px 8px 0 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .group-header {
            background: #e9ecef;
            padding: 8px 15px;
            border-bottom: 1px solid #dee2e6;
            display: table;
            width: 100%;
            box-sizing: border-box;
        }
        
        .group-name {
            display: table-cell;
            font-weight: bold;
            color: #333;
            font-size: 12px;
            width: 50%;
        }
        
        .group-totals {
            display: table-cell;
            text-align: right;
            font-size: 10px;
            color: #555;
        }
        
        .group-totals .net-amount {
            color: #28a745;
            font-weight: bold;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 0 0 8px 8px;
            overflow: hidden;
            table-layout: fixed;
            margin-bottom: 15px;
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
            font-family: 'Courier', 'Courier New', monospace;
        }
        
        .text-center {
            text-align: center;
        }
        
        .account-code {
            font-family: 'Courier', 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
        }
        
        .class-container {
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .summary-notice {
            padding: 15px;
            text-align: center;
            color: #666;
            font-style: italic;
            background: #f8f9fa;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .page-break {
            page-break-before: always;
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
    @php
        $companyModel = isset($company) ? $company : (function_exists('current_company') ? current_company() : null);
        $logoPath = ($companyModel && !empty($companyModel->logo)) ? public_path('storage/' . $companyModel->logo) : null;
    @endphp

    <div class="header">
        @if($logoPath && file_exists($logoPath))
            <div class="logo-section" style="text-align: center; margin-bottom: 10px;">
                <img src="{{ $logoPath }}" alt="{{ $companyModel->name ?? 'Company' }}" class="company-logo">
            </div>
        @endif
        <div class="title-section" style="text-align: center;">
            <h1>Accounting Notes Report</h1>
            @if($companyModel)
                <div class="company-name">{{ $companyModel->name }}</div>
            @endif
            <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">As of Date:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($accountingNotesData['as_of_date'])->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Reporting Basis:</div>
                <div class="info-value">{{ ucfirst($accountingNotesData['reporting_type']) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Detail Level:</div>
                <div class="info-value">{{ ucfirst($accountingNotesData['account_classes_data']['level_of_detail']) }}</div>
            </div>
            @if(isset($branchName))
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branchName }}</div>
            </div>
            @endif
        </div>
    </div>

    @php
        $summary = $accountingNotesData['account_classes_data']['summary'];
    @endphp

    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary['total_classes']) }}</div>
            <div class="stat-label">Account Classes</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary['total_groups']) }}</div>
            <div class="stat-label">Account Groups</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary['total_accounts']) }}</div>
            <div class="stat-label">Chart Accounts</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary['total_transactions']) }}</div>
            <div class="stat-label">Transactions</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary['total_debit'], 2) }}</div>
            <div class="stat-label">Total Debit</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary['total_credit'], 2) }}</div>
            <div class="stat-label">Total Credit</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary['total_net'], 2) }}</div>
            <div class="stat-label">Net Amount</div>
        </div>
    </div>

    @php
        $groupedData = collect($accountingNotesData['account_classes_data']['data'])->groupBy('class_name');
    @endphp

    @if($groupedData->count() > 0)
        @foreach($groupedData as $className => $classData)
            <div class="class-container">
                <div class="section-title">{{ $className }}</div>
                
                @php
                    $groupedByGroup = $classData->groupBy('group_name');
                @endphp
                
                @foreach($groupedByGroup as $groupName => $groupData)
                    @php
                        $groupTotalDebit = $groupData->sum('total_debit');
                        $groupTotalCredit = $groupData->sum('total_credit');
                        $groupNetAmount = $groupTotalDebit - $groupTotalCredit;
                        $groupTransactionCount = $groupData->sum('transaction_count');
                    @endphp
                    
                    <div class="group-header">
                        <div class="group-name">{{ $groupName }}</div>
                        <div class="group-totals">
                            Debit: {{ number_format($groupTotalDebit, 2) }} | 
                            Credit: {{ number_format($groupTotalCredit, 2) }} | 
                            <span class="net-amount">Net: {{ number_format($groupNetAmount, 2) }}</span> | 
                            {{ number_format($groupTransactionCount) }} Transactions
                        </div>
                    </div>
                    
                    @if($accountingNotesData['account_classes_data']['level_of_detail'] === 'detailed')
                        <table class="data-table" style="border-radius: 0; margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Account Code</th>
                                    <th style="width: 35%;">Account Name</th>
                                    <th style="width: 15%;" class="number">Total Debit</th>
                                    <th style="width: 15%;" class="number">Total Credit</th>
                                    <th style="width: 12%;" class="number">Net Amount</th>
                                    <th style="width: 8%;" class="text-center">Trans.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupData as $item)
                                    <tr>
                                        <td><span class="account-code">{{ $item->account_code }}</span></td>
                                        <td>{{ $item->account_name }}</td>
                                        <td class="number">{{ number_format($item->total_debit, 2) }}</td>
                                        <td class="number">{{ number_format($item->total_credit, 2) }}</td>
                                        <td class="number"><strong>{{ number_format($item->net_amount, 2) }}</strong></td>
                                        <td class="text-center">{{ number_format($item->transaction_count) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endforeach
                
                @if($accountingNotesData['account_classes_data']['level_of_detail'] === 'summary')
                    <div class="summary-notice">
                        Summary view - Group totals shown in headers above
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No account data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
