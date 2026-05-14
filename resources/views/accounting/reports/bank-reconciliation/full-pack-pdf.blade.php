<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Bank Reconciliation Pack</title>
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
            page-break-after: avoid;
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
        
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
            border-bottom: 2px solid #17a2b8;
            padding-bottom: 8px;
            color: #17a2b8;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        
        .text-center {
            text-align: center;
        }
        
        .page-break {
            page-break-before: always;
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
            @if(isset($company) && $company->logo)
                <div class="logo-section">
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $company->name }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Full Bank Reconciliation Pack</h1>
                @if(isset($company))
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ $generated_at->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Reconciliation Details</h3>
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
                <div class="info-value">{{ ucfirst($bankReconciliation->status) }}</div>
            </div>
        </div>
    </div>

    <!-- 1. Bank Reconciliation Statement -->
    <div class="section">
        <div class="section-title">1. BANK RECONCILIATION STATEMENT</div>
        <table class="data-table">
            <tbody>
                <tr>
                    <td style="width: 50%;"><strong>Bank Statement Balance</strong></td>
                    <td class="text-end" style="width: 50%;">{{ number_format($bankReconciliation->bank_statement_balance, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Book Balance</strong></td>
                    <td class="text-end">{{ number_format($bankReconciliation->book_balance, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Difference</strong></td>
                    <td class="text-end">{{ number_format($bankReconciliation->difference, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Status</strong></td>
                    <td>{{ ucfirst($bankReconciliation->status) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 2. Cleared Items Report -->
    <div class="section page-break">
        <div class="section-title">2. CLEARED ITEMS REPORT</div>
        @if(count($clearedItems) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 25%;">Description</th>
                    <th style="width: 12%;">Reference</th>
                    <th style="width: 12%;" class="text-end">Amount</th>
                    <th style="width: 15%;">Source</th>
                    <th style="width: 12%;">Cleared Date</th>
                    <th style="width: 12%;">Cleared By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clearedItems as $item)
                <tr>
                    <td>{{ $item->transaction_date->format('d/m/Y') }}</td>
                    <td>{{ Str::limit($item->description, 30) }}</td>
                    <td>{{ $item->reference ?? '-' }}</td>
                    <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                    <td>{{ $item->is_book_entry ? 'Cash Book' : 'Bank' }}</td>
                    <td>{{ $item->reconciled_at ? $item->reconciled_at->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->reconciledBy ? $item->reconciledBy->name : 'Auto' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">
            <p>No cleared items</p>
        </div>
        @endif
    </div>

    <!-- 3. Unreconciled Items Aging -->
    <div class="section page-break">
        <div class="section-title">3. UNRECONCILED ITEMS AGING</div>
        @if(count($unreconciledItems) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 25%;">Description</th>
                    <th style="width: 10%;">Reference</th>
                    <th style="width: 12%;" class="text-end">Cash Book Amount</th>
                    <th style="width: 12%;" class="text-end">Bank Statement Amount</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 8%;" class="text-center">Aging (Days)</th>
                    <th style="width: 13%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unreconciledItems as $item)
                @php
                    if (!$item->age_days) {
                        $item->calculateAging();
                    }
                    $type = $item->is_book_entry && $item->nature === 'debit' ? 'Deposit' : 'Payment';
                    $status = $item->is_bank_statement_item ? 'Not in Books' : 'Unpresented';
                @endphp
                <tr>
                    <td>{{ $item->transaction_date ? $item->transaction_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ Str::limit($item->description ?? '', 30) }}</td>
                    <td>{{ $item->reference ?? '-' }}</td>
                    <td class="text-end">{{ $item->is_book_entry ? number_format($item->nature === 'debit' ? $item->amount : -$item->amount, 2) : '–' }}</td>
                    <td class="text-end">{{ $item->is_bank_statement_item ? number_format($item->nature === 'credit' ? $item->amount : -$item->amount, 2) : '–' }}</td>
                    <td>{{ $type }}</td>
                    <td class="text-center">{{ $item->age_days ?? 0 }}</td>
                    <td>{{ $status }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">
            <p>No unreconciled items</p>
        </div>
        @endif
    </div>

    <!-- 4. Adjustments Journal -->
    <div class="section page-break">
        <div class="section-title">4. BANK RECONCILIATION ADJUSTMENTS — AUTO JOURNAL ENTRIES</div>
        @if(count($adjustmentTransactions) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 12%;">Journal No</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 18%;">Debit Account</th>
                    <th style="width: 18%;">Credit Account</th>
                    <th style="width: 10%;" class="text-end">Amount</th>
                    <th style="width: 12%;">Description</th>
                    <th style="width: 10%;">Posted By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustmentTransactions as $adj)
                <tr>
                    <td>{{ $adj['date'] }}</td>
                    <td>{{ $adj['journal_no'] }}</td>
                    <td>{{ $adj['type'] }}</td>
                    <td>{{ $adj['debit_account'] }}</td>
                    <td>{{ $adj['credit_account'] }}</td>
                    <td class="text-end">{{ number_format($adj['amount'], 2) }}</td>
                    <td>{{ Str::limit($adj['description'], 30) }}</td>
                    <td>{{ $adj['posted_by'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">
            <p>No adjustments</p>
        </div>
        @endif
    </div>

    <!-- 5. Exceptions Report -->
    <div class="section page-break">
        <div class="section-title">5. BANK RECONCILIATION EXCEPTION REPORT</div>
        <p style="margin: 5px 0 15px 0; font-size: 11px; color: #666;"><em>(Items uncleared for >15 days)</em></p>
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
                    <td>{{ Str::limit($exception['description'], 30) }}</td>
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
            <p>No exceptions</p>
        </div>
        @endif
    </div>

    <!-- 6. Audit Trail Report -->
    <div class="section page-break">
        <div class="section-title">6. RECONCILIATION APPROVAL & AUDIT TRAIL</div>
        @if(count($auditTrail) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Step</th>
                    <th style="width: 20%;">Action</th>
                    <th style="width: 18%;">User</th>
                    <th style="width: 18%;">Timestamp</th>
                    <th style="width: 12%;">IP / Device</th>
                    <th style="width: 24%;">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($auditTrail as $trail)
                <tr>
                    <td>{{ $trail['step'] }}</td>
                    <td>{{ $trail['action'] }}</td>
                    <td>{{ $trail['user'] }}</td>
                    <td>{{ $trail['timestamp']->format('d/m/Y H:i') }}</td>
                    <td>{{ $trail['ip_device'] }}</td>
                    <td>{{ $trail['notes'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">
            <p>No audit trail data</p>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Page 1 of 1</p>
    </div>
</body>
</html>
