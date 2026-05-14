<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accrual Schedule Report</title>
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
            width: 140px;
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
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table th:nth-child(1) { width: 12%; }
        .data-table th:nth-child(2) { width: 14%; }
        .data-table th:nth-child(3) { width: 14%; }
        .data-table th:nth-child(4) { width: 10%; }
        .data-table th:nth-child(5) { width: 15%; }
        .data-table th:nth-child(6) { width: 12%; }
        .data-table th:nth-child(7) { width: 13%; }
        .data-table th:nth-child(8) { width: 10%; }
        
        .data-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #dee2e6;
            font-size: 11px;
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
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .badge-primary {
            background-color: #007bff;
            color: white;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            @php
                $logoPath = null;
                if ($company && !empty($company->logo)) {
                    $logoPath = public_path('storage/' . $company->logo);
                }
            @endphp
            @if($logoPath && file_exists($logoPath))
                <div class="logo-section">
                    <img src="{{ $logoPath }}" alt="{{ $company->name ?? 'Company' }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Accrual Schedule Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Schedule Information</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Schedule Number:</div>
                <div class="info-value"><strong>{{ $schedule->schedule_number }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Schedule Type:</div>
                <div class="info-value">{{ ucfirst($schedule->schedule_type) }} - {{ ucfirst($schedule->nature) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $schedule->start_date->format('M d, Y') }} - {{ $schedule->end_date->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Frequency:</div>
                <div class="info-value">{{ ucfirst($schedule->frequency) }}</div>
            </div>
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
            @if($schedule->vendor)
            <div class="info-row">
                <div class="info-label">Vendor:</div>
                <div class="info-value">{{ $schedule->vendor->name }}</div>
            </div>
            @endif
            @if($schedule->customer)
            <div class="info-row">
                <div class="info-label">Customer:</div>
                <div class="info-value">{{ $schedule->customer->name }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    @php
                        $badgeClass = match($schedule->status) {
                            'draft' => 'badge-secondary',
                            'submitted' => 'badge-info',
                            'approved' => 'badge-primary',
                            'active' => 'badge-success',
                            'completed' => 'badge-secondary',
                            'cancelled' => 'badge-danger',
                            default => 'badge-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ ucfirst($schedule->status) }}</span>
                </div>
            </div>
            @if($schedule->description)
            <div class="info-row">
                <div class="info-label">Description:</div>
                <div class="info-value">{{ $schedule->description }}</div>
            </div>
            @endif
        </div>
    </div>

    @php
        $totalAmount = $schedule->total_amount;
        $amortisedAmount = $schedule->amortised_amount ?? 0;
        $remainingAmount = $schedule->remaining_amount ?? $totalAmount;
        $postedJournals = $schedule->journals->where('status', 'posted')->count();
        $totalJournals = $schedule->journals->count();
        $pendingJournals = $totalJournals - $postedJournals;
        $completionPercentage = $totalAmount > 0 ? ($amortisedAmount / $totalAmount) * 100 : 0;
    @endphp

    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value">{{ number_format($totalAmount, 2) }}</div>
            <div class="stat-label">Total Amount ({{ $schedule->currency_code }})</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($amortisedAmount, 2) }}</div>
            <div class="stat-label">Amortised ({{ $schedule->currency_code }})</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($remainingAmount, 2) }}</div>
            <div class="stat-label">Remaining ({{ $schedule->currency_code }})</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($completionPercentage, 1) }}%</div>
            <div class="stat-label">Completion</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $postedJournals }}</div>
            <div class="stat-label">Posted Journals</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $pendingJournals }}</div>
            <div class="stat-label">Pending Journals</div>
        </div>
    </div>

    @if(count($amortisationSchedule) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th class="number">Days</th>
                    <th class="number">Amount ({{ $schedule->currency_code }})</th>
                    <th>Status</th>
                    <th>Journal #</th>
                    <th>Posted Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($amortisationSchedule as $period)
                    @php
                        $journal = $schedule->journals->where('period', $period['period'])->first();
                    @endphp
                    <tr>
                        <td><strong>{{ $period['period'] }}</strong></td>
                        <td>{{ $period['period_start_date']->format('M d, Y') }}</td>
                        <td>{{ $period['period_end_date']->format('M d, Y') }}</td>
                        <td class="number">{{ $period['days_in_period'] }}</td>
                        <td class="number">{{ number_format($period['amortisation_amount'], 2) }}</td>
                        <td>
                            @if($journal)
                                @php
                                    $statusBadge = match($journal->status) {
                                        'posted' => 'badge-success',
                                        'pending' => 'badge-warning',
                                        'cancelled' => 'badge-danger',
                                        'reversed' => 'badge-secondary',
                                        default => 'badge-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ ucfirst($journal->status) }}</span>
                            @else
                                <span class="badge badge-secondary">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($journal && $journal->journal)
                                {{ $journal->journal->reference }}
                            @else
                                <span style="color: #999;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($journal && $journal->posted_at)
                                {{ $journal->posted_at->format('M d, Y') }}
                            @else
                                <span style="color: #999;">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="4" class="number">Total:</td>
                    <td class="number">{{ number_format($totalAmount, 2) }} {{ $schedule->currency_code }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No amortisation schedule data found for this accrual schedule.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        @if($user)
            <p>Generated by: {{ $user->name }}</p>
        @endif
    </div>
</body>
</html>


