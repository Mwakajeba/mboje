<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Reconciliation Report</title>
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
        
        .stat-value.balanced {
            color: #28a745;
        }
        
        .stat-value.unbalanced {
            color: #dc3545;
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
        
        .text-center {
            text-align: center;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #17a2b8;
            border-bottom: 2px solid #17a2b8;
            padding-bottom: 5px;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-draft { background-color: #6c757d; color: white; }
        .status-in_progress { background-color: #ffc107; color: black; }
        .status-completed { background-color: #28a745; color: white; }
        .status-cancelled { background-color: #dc3545; color: white; }
        
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
    <div class="header">
        <div class="header-content">
            @if($company && $company->logo)
                <div class="logo-section">
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $company->name }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Bank Reconciliation Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ $generated_at->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name ?? 'All Branches' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Generated By:</div>
                <div class="info-value">{{ $user->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Report Period:</div>
                <div class="info-value">
                    @if(isset($filters['start_date']) && $filters['start_date'] && isset($filters['end_date']) && $filters['end_date'])
                        {{ \Carbon\Carbon::parse($filters['start_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($filters['end_date'])->format('M d, Y') }}
                    @else
                        All Time
                    @endif
                </div>
            </div>
            @if(isset($filters['bank_account_id']) && $filters['bank_account_id'])
            <div class="info-row">
                <div class="info-label">Bank Account:</div>
                <div class="info-value">
                    @php
                        $bankAccount = $reconciliations->first()->bankAccount ?? null;
                    @endphp
                    {{ $bankAccount ? $bankAccount->name : 'Specific Account' }}
                </div>
            </div>
            @endif
            @if(isset($filters['status']) && $filters['status'])
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $filters['status'])) }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value">{{ $reportStats['total_reconciliations'] }}</div>
            <div class="stat-label">Total Reconciliations</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($reportStats['total_bank_balance'], 2) }}</div>
            <div class="stat-label">Total Bank Balance</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($reportStats['total_book_balance'], 2) }}</div>
            <div class="stat-label">Total Book Balance</div>
        </div>
        <div class="stat-item">
            <div class="stat-value {{ $reportStats['total_difference'] == 0 ? 'balanced' : 'unbalanced' }}">
                {{ number_format($reportStats['total_difference'], 2) }}
            </div>
            <div class="stat-label">Total Difference</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $reportStats['completed_count'] }}</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $reportStats['pending_count'] }}</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>

    @if($reconciliations->count() > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18%;">Bank Account</th>
                <th style="width: 12%;">Reconciliation Date</th>
                <th style="width: 18%;">Period</th>
                <th style="width: 13%;" class="number">Bank Statement Balance</th>
                <th style="width: 13%;" class="number">Book Balance</th>
                <th style="width: 10%;" class="number">Difference</th>
                <th style="width: 8%;" class="text-center">Status</th>
                <th style="width: 8%;">Created By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reconciliations as $reconciliation)
            <tr>
                <td>
                    <strong>{{ $reconciliation->bankAccount->name }}</strong><br>
                    <small>{{ $reconciliation->bankAccount->account_number }}</small>
                </td>
                <td>{{ $reconciliation->reconciliation_date->format('M d, Y') }}</td>
                <td>
                    {{ $reconciliation->start_date->format('M d, Y') }}<br>
                    <small>{{ $reconciliation->end_date->format('M d, Y') }}</small>
                </td>
                <td class="number">{{ number_format($reconciliation->bank_statement_balance, 2) }}</td>
                <td class="number">{{ number_format($reconciliation->book_balance, 2) }}</td>
                <td class="number {{ $reconciliation->difference == 0 ? 'balanced' : 'unbalanced' }}">
                    @if($reconciliation->difference == 0)
                        Balanced
                    @else
                        {{ number_format($reconciliation->difference, 2) }}
                    @endif
                </td>
                <td class="text-center">
                    <span class="status-badge status-{{ $reconciliation->status }}">
                        {{ ucfirst(str_replace('_', ' ', $reconciliation->status)) }}
                    </span>
                </td>
                <td>
                    {{ $reconciliation->user->name }}<br>
                    <small>{{ $reconciliation->created_at->format('M d, Y') }}</small>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        <h3>No Data Available</h3>
        <p>No reconciliations found for the selected criteria.</p>
    </div>
    @endif

    @if($reconciliations->count() > 0)
    <div class="page-break"></div>
    <div class="section-title">Summary by Status</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Status</th>
                <th style="width: 15%;" class="text-center">Count</th>
                <th style="width: 20%;" class="number">Total Bank Balance</th>
                <th style="width: 20%;" class="number">Total Book Balance</th>
                <th style="width: 20%;" class="number">Total Difference</th>
            </tr>
        </thead>
        <tbody>
            @php
                $statusGroups = $reconciliations->groupBy('status');
            @endphp
            @foreach($statusGroups as $status => $group)
            <tr>
                <td>
                    <span class="status-badge status-{{ $status }}">
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </span>
                </td>
                <td class="text-center">{{ $group->count() }}</td>
                <td class="number">{{ number_format($group->sum('bank_statement_balance'), 2) }}</td>
                <td class="number">{{ number_format($group->sum('book_balance'), 2) }}</td>
                <td class="number {{ $group->sum('difference') == 0 ? 'balanced' : 'unbalanced' }}">
                    {{ number_format($group->sum('difference'), 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
