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
        
        .nature-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        
        .nature-debit { background-color: #dc3545; color: white; }
        .nature-credit { background-color: #28a745; color: white; }
        
        .reconciled-item {
            background-color: #f8f9fa;
        }
        
        .unreconciled-item {
            background-color: #fff3cd;
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
        <h3>Reconciliation Information</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Bank Account:</div>
                <div class="info-value">{{ $bankReconciliation->bankAccount->name }} ({{ $bankReconciliation->bankAccount->account_number }})</div>
            </div>
            <div class="info-row">
                <div class="info-label">Reconciliation Date:</div>
                <div class="info-value">{{ $bankReconciliation->reconciliation_date->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $bankReconciliation->start_date->format('M d, Y') }} - {{ $bankReconciliation->end_date->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $bankReconciliation->status }}">
                        {{ ucfirst(str_replace('_', ' ', $bankReconciliation->status)) }}
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Created By:</div>
                <div class="info-value">{{ $bankReconciliation->user->name }}</div>
            </div>
            @if($bankReconciliation->branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $bankReconciliation->branch->name }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value">{{ number_format($bankReconciliation->bank_statement_balance, 2) }}</div>
            <div class="stat-label">Bank Statement Balance (TZS)</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($bankReconciliation->book_balance, 2) }}</div>
            <div class="stat-label">Book Balance (TZS)</div>
        </div>
        <div class="stat-item">
            <div class="stat-value {{ $bankReconciliation->difference == 0 ? 'balanced' : 'unbalanced' }}">
                {{ number_format($bankReconciliation->difference, 2) }}
            </div>
            <div class="stat-label">Difference (TZS)</div>
        </div>
        <div class="stat-item">
            <div class="stat-value {{ $bankReconciliation->isBalanced() ? 'balanced' : 'unbalanced' }}">
                {{ $bankReconciliation->isBalanced() ? 'Balanced' : 'Unbalanced' }}
            </div>
            <div class="stat-label">Status</div>
        </div>
        @if($bankReconciliation->adjusted_bank_balance)
        <div class="stat-item">
            <div class="stat-value">{{ number_format($bankReconciliation->adjusted_bank_balance, 2) }}</div>
            <div class="stat-label">Adjusted Bank Balance (TZS)</div>
        </div>
        @endif
        @if($bankReconciliation->adjusted_book_balance)
        <div class="stat-item">
            <div class="stat-value">{{ number_format($bankReconciliation->adjusted_book_balance, 2) }}</div>
            <div class="stat-label">Adjusted Book Balance (TZS)</div>
        </div>
        @endif
    </div>

    @if(isset($broughtForwardItems) && $broughtForwardItems->count() > 0)
    <div class="section-title">Brought Forward Uncleared Items (Prior Month)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 8%;" class="text-center">Type</th>
                <th style="width: 10%;">Reference</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 15%;" class="number">Amount</th>
                <th style="width: 10%;" class="text-center">Age</th>
                <th style="width: 15%;" class="text-center">Origin Month</th>
            </tr>
        </thead>
        <tbody>
            @foreach($broughtForwardItems as $item)
            @php
                if ($item->uncleared_status === 'UNCLEARED' && $item->transaction_date) {
                    $item->calculateAging();
                }
                $agingColor = $item->uncleared_status === 'UNCLEARED' && $item->age_days ? $item->getAgingFlagColor() : 'secondary';
            @endphp
            <tr class="unreconciled-item">
                <td>{{ $item->transaction_date->format('M d, Y') }}</td>
                <td class="text-center">
                    @if($item->item_type)
                        <span class="status-badge {{ $item->item_type === 'DNC' ? 'status-completed' : 'status-draft' }}">
                            {{ $item->item_type }}
                        </span>
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $item->reference ?? 'N/A' }}</td>
                <td>{{ $item->description }}</td>
                <td class="number">{{ number_format($item->amount, 2) }}</td>
                <td class="text-center">
                    @if($item->uncleared_status === 'UNCLEARED' && $item->age_days)
                        <span class="status-badge status-{{ $agingColor }}">
                            {{ $item->age_days }} days
                            @if($item->age_months >= 1)
                                ({{ number_format($item->age_months, 1) }}m)
                            @endif
                        </span>
                    @else
                        N/A
                    @endif
                </td>
                <td class="text-center">{{ $item->origin_month ? $item->origin_month->format('M Y') : 'N/A' }}</td>
            </tr>
            @endforeach
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td colspan="4" class="text-end">Total Brought Forward:</td>
                <td class="number">{{ number_format($broughtForwardItems->sum('amount'), 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
    @endif

    @if($unreconciledBankItems->count() > 0)
    <div class="section-title">Unreconciled Bank Statement Items</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 15%;">Reference</th>
                <th style="width: 35%;">Description</th>
                <th style="width: 10%;" class="text-center">Nature</th>
                <th style="width: 25%;" class="number">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($unreconciledBankItems as $item)
            <tr class="unreconciled-item">
                <td>{{ $item->transaction_date->format('M d, Y') }}</td>
                <td>{{ $item->reference ?? 'N/A' }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">
                    <span class="nature-badge nature-{{ $item->nature }}">
                        {{ strtoupper($item->nature) }}
                    </span>
                </td>
                <td class="number">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($unreconciledBookItems->count() > 0)
    <div class="section-title">Unreconciled Book Entry Items</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 12%;">Reference</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 10%;" class="text-center">Type</th>
                <th style="width: 8%;" class="text-center">Nature</th>
                <th style="width: 13%;" class="number">Amount</th>
                <th style="width: 15%;" class="text-center">Aging</th>
            </tr>
        </thead>
        <tbody>
            @foreach($unreconciledBookItems as $item)
            @php
                $isBroughtForward = $item->is_brought_forward ?? false;
                if ($item->uncleared_status === 'UNCLEARED' && $item->transaction_date) {
                    $item->calculateAging();
                }
                $agingColor = $item->uncleared_status === 'UNCLEARED' && $item->age_days ? $item->getAgingFlagColor() : 'secondary';
            @endphp
            <tr class="unreconciled-item" style="{{ $isBroughtForward ? 'background-color: #fff3cd;' : '' }}">
                <td>{{ $item->transaction_date->format('M d, Y') }}</td>
                <td>{{ $item->reference ?? 'N/A' }}</td>
                <td>
                    {{ $item->description }}
                    @if($isBroughtForward)
                        <br><small style="color: #856404;">(Brought Forward)</small>
                    @endif
                    @if($item->origin_date && $item->origin_date != $item->transaction_date)
                        <br><small style="color: #666;">Origin: {{ $item->origin_date->format('d/m/Y') }}</small>
                    @endif
                </td>
                <td class="text-center">
                    @if($item->item_type)
                        <span class="status-badge {{ $item->item_type === 'DNC' ? 'status-completed' : 'status-draft' }}">
                            {{ $item->item_type }}
                        </span>
                    @else
                        N/A
                    @endif
                </td>
                <td class="text-center">
                    <span class="nature-badge nature-{{ $item->nature }}">
                        {{ strtoupper($item->nature) }}
                    </span>
                </td>
                <td class="number">{{ number_format($item->amount, 2) }}</td>
                <td class="text-center">
                    @if($item->uncleared_status === 'UNCLEARED' && $item->age_days)
                        <span class="status-badge status-{{ $agingColor }}">
                            {{ $item->age_days }} days
                            @if($item->age_months >= 1)
                                ({{ number_format($item->age_months, 1) }}m)
                            @endif
                        </span>
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($unclearedItemsSummary) && ($unclearedItemsSummary['dnc']['count'] > 0 || $unclearedItemsSummary['upc']['count'] > 0))
    <div class="page-break"></div>
    <div class="section-title">Uncleared Items Summary</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Item Type</th>
                <th style="width: 15%;" class="text-center">Count</th>
                <th style="width: 25%;" class="number">Total Amount</th>
                <th style="width: 15%;" class="text-center">Brought Forward</th>
                <th style="width: 15%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @if($unclearedItemsSummary['dnc']['count'] > 0)
            <tr>
                <td><strong>DNC (Deposits Not Credited)</strong></td>
                <td class="text-center">{{ $unclearedItemsSummary['dnc']['count'] }}</td>
                <td class="number">{{ number_format($unclearedItemsSummary['dnc']['total_amount'], 2) }}</td>
                <td class="text-center">{{ $unclearedItemsSummary['brought_forward']['items']->where('item_type', 'DNC')->count() }}</td>
                <td class="text-center"><span class="status-badge status-in_progress">Uncleared</span></td>
            </tr>
            @endif
            @if($unclearedItemsSummary['upc']['count'] > 0)
            <tr>
                <td><strong>UPC (Unpresented Cheques)</strong></td>
                <td class="text-center">{{ $unclearedItemsSummary['upc']['count'] }}</td>
                <td class="number">{{ number_format($unclearedItemsSummary['upc']['total_amount'], 2) }}</td>
                <td class="text-center">{{ $unclearedItemsSummary['brought_forward']['items']->where('item_type', 'UPC')->count() }}</td>
                <td class="text-center"><span class="status-badge status-in_progress">Uncleared</span></td>
            </tr>
            @endif
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td><strong>Total Uncleared</strong></td>
                <td class="text-center">{{ $unclearedItemsSummary['total_uncleared']['count'] }}</td>
                <td class="number">{{ number_format($unclearedItemsSummary['total_uncleared']['total_amount'], 2) }}</td>
                <td class="text-center">{{ $unclearedItemsSummary['brought_forward']['count'] }}</td>
                <td class="text-center"><span class="status-badge status-in_progress">Uncleared</span></td>
            </tr>
        </tbody>
    </table>
    @endif

    @if($reconciledItems->count() > 0)
    <div class="page-break"></div>
    <div class="section-title">Reconciled Items</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Date</th>
                <th style="width: 12%;">Reference</th>
                <th style="width: 25%;">Description</th>
                <th style="width: 8%;" class="text-center">Type</th>
                <th style="width: 8%;" class="text-center">Nature</th>
                <th style="width: 12%;" class="number">Amount</th>
                <th style="width: 15%;">Matched With</th>
                <th style="width: 10%;">Reconciled By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reconciledItems as $item)
            <tr class="reconciled-item">
                <td>{{ $item->transaction_date->format('M d, Y') }}</td>
                <td>{{ $item->reference ?? 'N/A' }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">
                    @if($item->is_bank_statement_item)
                        <span class="status-badge status-completed">Bank</span>
                    @else
                        <span class="status-badge status-draft">Book</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="nature-badge nature-{{ $item->nature }}">
                        {{ strtoupper($item->nature) }}
                    </span>
                </td>
                <td class="number">{{ number_format($item->amount, 2) }}</td>
                <td>
                    @if($item->matchedWithItem)
                        {{ strlen($item->matchedWithItem->description) > 30 ? substr($item->matchedWithItem->description, 0, 30) . '...' : $item->matchedWithItem->description }}
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if($item->reconciledBy)
                        {{ $item->reconciledBy->name }}<br>
                        <small>{{ $item->reconciled_at->format('M d, Y H:i') }}</small>
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="page-break"></div>
    <div class="section-title">Reconciliation Summary</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40%;">Item Type</th>
                <th style="width: 15%;" class="text-center">Count</th>
                <th style="width: 25%;" class="number">Total Amount</th>
                <th style="width: 20%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Bank Statement Items</td>
                <td class="text-center">{{ $unreconciledBankItems->count() + $reconciledItems->where('is_bank_statement_item', true)->count() }}</td>
                <td class="number">{{ number_format($unreconciledBankItems->sum('amount') + $reconciledItems->where('is_bank_statement_item', true)->sum('amount'), 2) }}</td>
                <td class="text-center">
                    @if($unreconciledBankItems->count() > 0)
                        <span class="status-badge status-in_progress">Unreconciled</span>
                    @else
                        <span class="status-badge status-completed">All Reconciled</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td>Book Entry Items</td>
                <td class="text-center">{{ $unreconciledBookItems->count() + $reconciledItems->where('is_book_entry', true)->count() }}</td>
                <td class="number">{{ number_format($unreconciledBookItems->sum('amount') + $reconciledItems->where('is_book_entry', true)->sum('amount'), 2) }}</td>
                <td class="text-center">
                    @if($unreconciledBookItems->count() > 0)
                        <span class="status-badge status-in_progress">Unreconciled</span>
                    @else
                        <span class="status-badge status-completed">All Reconciled</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td>Reconciled Items</td>
                <td class="text-center">{{ $reconciledItems->count() }}</td>
                <td class="number">{{ number_format($reconciledItems->sum('amount'), 2) }}</td>
                <td class="text-center">
                    <span class="status-badge status-completed">Reconciled</span>
                </td>
            </tr>
        </tbody>
    </table>

    @if($bankReconciliation->notes)
    <div class="section-title">Notes</div>
    <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; border-left: 4px solid #17a2b8; margin-bottom: 20px;">
        {{ $bankReconciliation->notes }}
    </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
