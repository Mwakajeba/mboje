<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget vs Actual Report</title>
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
        
        .data-table th:nth-child(1) { width: 8%; }
        .data-table th:nth-child(2) { width: 15%; }
        .data-table th:nth-child(3) { width: 10%; }
        .data-table th:nth-child(4) { width: 12%; }
        .data-table th:nth-child(5) { width: 8%; }
        .data-table th:nth-child(6) { width: 11%; }
        .data-table th:nth-child(7) { width: 11%; }
        .data-table th:nth-child(8) { width: 9%; }
        .data-table th:nth-child(9) { width: 8%; }
        .data-table th:nth-child(10) { width: 8%; }
        
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
        
        .positive {
            color: #28a745;
        }
        
        .negative {
            color: #dc3545;
        }
        
        .achievement-excellent { background-color: #155724; color: white; padding: 3px 6px; border-radius: 3px; font-weight: bold; }
        .achievement-very-good { background-color: #28a745; color: white; padding: 3px 6px; border-radius: 3px; font-weight: bold; }
        .achievement-good { background-color: #5cb85c; color: white; padding: 3px 6px; border-radius: 3px; font-weight: bold; }
        .achievement-fair { background-color: #7cb342; color: white; padding: 3px 6px; border-radius: 3px; font-weight: bold; }
        .achievement-average { background-color: #ffc107; color: #000; padding: 3px 6px; border-radius: 3px; font-weight: bold; }
        .achievement-poor { background-color: #ff9800; color: white; padding: 3px 6px; border-radius: 3px; font-weight: bold; }
        .achievement-very-poor { background-color: #dc3545; color: white; padding: 3px 6px; border-radius: 3px; font-weight: bold; }
        
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
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 600;
        }
        
        .badge-success { background-color: #198754; color: white; }
        .badge-danger { background-color: #dc3545; color: white; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-secondary { background-color: #6c757d; color: white; }
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
                <h1>Budget vs Actual Report</h1>
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
                <div class="info-value">{{ $filters['date_from'] }} to {{ $filters['date_to'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Year:</div>
                <div class="info-value">{{ $filters['year'] }}</div>
            </div>
            @if($filters['budget_id'])
            <div class="info-row">
                <div class="info-label">Budget:</div>
                <div class="info-value">{{ \App\Models\Budget::find($filters['budget_id'])->name ?? 'N/A' }}</div>
            </div>
            @endif
            @if($filters['account_class_id'])
            <div class="info-row">
                <div class="info-label">Account Class:</div>
                <div class="info-value">{{ \DB::table('account_class')->where('id', $filters['account_class_id'])->value('name') ?? 'N/A' }}</div>
            </div>
            @endif
            @if($filters['category'])
            <div class="info-row">
                <div class="info-label">Category:</div>
                <div class="info-value">{{ $filters['category'] }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($items->count() > 0)
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_budgeted'], 2) }}</div>
                <div class="stat-label">Total Budgeted (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_actual'], 2) }}</div>
                <div class="stat-label">Total Actual (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value {{ $summary['total_variance'] >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($summary['total_variance'], 2) }}
                </div>
                <div class="stat-label">Total Variance (TZS)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['total_accounts']) }}</div>
                <div class="stat-label">Total Accounts</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['under_budget_count']) }}</div>
                <div class="stat-label">Under Budget</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($summary['over_budget_count']) }}</div>
                <div class="stat-label">Over Budget</div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th>Account Class</th>
                    <th>Account Group</th>
                    <th>Category</th>
                    <th class="number">Budgeted</th>
                    <th class="number">Actual</th>
                    <th class="number">Variance</th>
                    <th class="number">Variance %</th>
                    <th class="number">Achievement</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td><span class="badge badge-secondary">{{ $item->account_code }}</span></td>
                    <td>{{ $item->account_name }}</td>
                    <td>{{ $item->account_class }}</td>
                    <td>{{ $item->account_group }}</td>
                    <td>
                        @if($item->category == 'Revenue')
                            <span class="badge badge-success">{{ $item->category }}</span>
                        @elseif($item->category == 'Expense')
                            <span class="badge badge-danger">{{ $item->category }}</span>
                        @else
                            <span class="badge badge-warning">{{ $item->category }}</span>
                        @endif
                    </td>
                    <td class="number">{{ number_format($item->budgeted_amount, 2) }}</td>
                    <td class="number">{{ number_format($item->actual_amount, 2) }}</td>
                    <td class="number {{ $item->variance >= 0 ? 'positive' : 'negative' }}">{{ number_format($item->variance, 2) }}</td>
                    <td class="number {{ $item->variance_percentage >= 0 ? 'positive' : 'negative' }}">{{ $item->variance_percentage }}%</td>
                    <td class="number">
                        @php
                            $achievement = $item->achievement_percentage ?? 0;
                            $achievementClass = '';
                            $isExpense = $item->category == 'Expense';
                            
                            // For expenses: less than 50% is good (spending less), more than 50% is bad (spending more)
                            // For revenue: more than 50% is good (earning more), less than 50% is bad (earning less)
                            if ($isExpense) {
                                // Expense logic: lower is better
                                if ($achievement < 50) {
                                    // Good - spending less than budget
                                    if ($achievement < 30) {
                                        $achievementClass = 'achievement-excellent';
                                    } elseif ($achievement < 40) {
                                        $achievementClass = 'achievement-very-good';
                                    } else {
                                        $achievementClass = 'achievement-good';
                                    }
                                } elseif ($achievement >= 45 && $achievement < 55) {
                                    $achievementClass = 'achievement-average';
                                } else {
                                    // Bad - spending more than budget
                                    if ($achievement >= 90) {
                                        $achievementClass = 'achievement-very-poor';
                                    } elseif ($achievement >= 75) {
                                        $achievementClass = 'achievement-poor';
                                    } else {
                                        $achievementClass = 'achievement-poor';
                                    }
                                }
                            } else {
                                // Revenue logic: higher is better (original logic)
                                if ($achievement >= 50) {
                                    if ($achievement >= 90) {
                                        $achievementClass = 'achievement-excellent';
                                    } elseif ($achievement >= 75) {
                                        $achievementClass = 'achievement-very-good';
                                    } elseif ($achievement >= 60) {
                                        $achievementClass = 'achievement-good';
                                    } else {
                                        $achievementClass = 'achievement-fair';
                                    }
                                } elseif ($achievement >= 45 && $achievement < 55) {
                                    $achievementClass = 'achievement-average';
                                } else {
                                    if ($achievement >= 30) {
                                        $achievementClass = 'achievement-poor';
                                    } else {
                                        $achievementClass = 'achievement-very-poor';
                                    }
                                }
                            }
                        @endphp
                        <span class="{{ $achievementClass }}">{{ number_format($achievement, 2) }}%</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No budget data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
