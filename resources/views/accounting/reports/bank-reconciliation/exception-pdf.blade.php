<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Reconciliation Exception Report</title>
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
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .text-end {
            text-align: right;
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
            @if(isset($user) && $user->company && $user->company->logo)
                <div class="logo-section">
                    <img src="{{ public_path('storage/' . $user->company->logo) }}" alt="{{ $user->company->name }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Bank Reconciliation Exception Report</h1>
                <p style="margin: 5px 0; font-size: 14px; color: #666;">(Items uncleared for >15 days)</p>
                @if(isset($user) && $user->company)
                    <div class="company-name">{{ $user->company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            @if($selectedBankAccount)
            <div class="info-row">
                <div class="info-label">Bank Account:</div>
                <div class="info-value">{{ $selectedBankAccount->name }} ({{ $selectedBankAccount->account_number }})</div>
            </div>
            @else
            <div class="info-row">
                <div class="info-label">Bank Account:</div>
                <div class="info-value">All Bank Accounts</div>
            </div>
            @endif
            @if(isset($severity) && $severity)
            <div class="info-row">
                <div class="info-label">Severity:</div>
                <div class="info-value">{{ ucfirst($severity) }}</div>
            </div>
            @endif
        </div>
    </div>

    @if(count($exceptions) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Issue Type</th>
                <th style="width: 25%;">Description</th>
                <th style="width: 12%;">Transaction</th>
                <th style="width: 12%;" class="text-end">Amount</th>
                <th style="width: 12%;">Detected On</th>
                <th style="width: 12%;">Severity</th>
                <th style="width: 15%;">Suggested Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exceptions as $exception)
            <tr>
                <td>{{ $exception['issue_type'] }}</td>
                <td>{{ Str::limit($exception['description'], 40) }}</td>
                <td>{{ $exception['transaction'] }}</td>
                <td class="text-end">{{ number_format(abs($exception['amount']), 2) }}</td>
                <td>{{ $exception['detected_on'] }}</td>
                <td>{{ $exception['severity'] }}</td>
                <td>{{ $exception['suggested_action'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        <p>No exceptions found</p>
    </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Page 1 of 1</p>
    </div>
</body>
</html>
