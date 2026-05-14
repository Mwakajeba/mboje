<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Reconciliation Statement</title>
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
        
        .section-title {
            background: #17a2b8;
            color: white;
            padding: 10px 15px;
            margin: 20px 0 10px 0;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px 4px 0 0;
        }
        
        .section-subtitle {
            font-size: 11px;
            color: rgba(255,255,255,0.9);
            font-style: italic;
            margin-top: 5px;
            font-weight: normal;
            text-transform: none;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            margin-bottom: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }
        
        .data-table thead {
            background: #17a2b8;
            color: white;
        }
        
        .data-table th {
            padding: 8px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table td {
            padding: 8px 8px;
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
        
        .data-table tfoot {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .data-table tfoot td {
            border-top: 2px solid #17a2b8;
            padding: 10px 6px;
            font-size: 11px;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .text-end {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .text-center {
            text-align: center;
        }
        
        .final-balance-row {
            background: #17a2b8 !important;
            color: white !important;
            font-weight: 700;
        }
        
        .final-balance-row td {
            border: none !important;
            padding: 12px 6px !important;
            font-size: 12px !important;
        }
        
        .reconciliation-status {
            margin-top: 25px;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            background: #f8f9fa;
            border-left: 4px solid #17a2b8;
        }
        
        .status-reconciled {
            background: #d4edda;
            border-left-color: #28a745;
        }
        
        .status-not-reconciled {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        
        .reconciliation-status h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
        }
        
        .status-reconciled h3 {
            color: #155724;
        }
        
        .status-not-reconciled h3 {
            color: #721c24;
        }
        
        .reconciliation-status p {
            font-size: 11px;
            color: #666;
            margin: 0;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 500;
        }
        
        .badge-dnc {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-upc {
            background: #f8d7da;
            color: #721c24;
        }
        
        .entity-info {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
        }
        
        .entity-info-row {
            display: table-row;
        }
        
        .entity-info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 15px 5px 0;
            width: 140px;
            color: #555;
        }
        
        .entity-info-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
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
                    <img src="{{ $logoPath }}" alt="{{ $company->name }}" class="company-logo">
                </div>
            @endif
            <div class="title-section">
                <h1>Bank Reconciliation Statement</h1>
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
                <div class="info-value">{{ $bankReconciliation->start_date->format('M d, Y') }} - {{ $bankReconciliation->end_date->format('M d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Bank Account:</div>
                <div class="info-value">{{ $bankReconciliation->bankAccount->name }} ({{ $bankReconciliation->bankAccount->account_number }})</div>
            </div>
            <div class="info-row">
                <div class="info-label">Prepared By:</div>
                <div class="info-value">{{ $bankReconciliation->user->name ?? 'N/A' }}</div>
            </div>
            @if($bankReconciliation->submittedBy)
            <div class="info-row">
                <div class="info-label">Reviewed By:</div>
                <div class="info-value">{{ $bankReconciliation->submittedBy->name }}</div>
            </div>
            @endif
            @if($bankReconciliation->approvedBy)
            <div class="info-row">
                <div class="info-label">Approved By:</div>
                <div class="info-value">{{ $bankReconciliation->approvedBy->name }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Section A: Bank Balance -->
    <div class="section-title">A. BANK BALANCE AS PER BANK STATEMENT</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 75%;">Item</th>
                <th style="width: 25%;" class="number">Amount (TZS)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Closing balance as per bank statement</td>
                <td class="number">{{ number_format($bankReconciliation->bank_statement_balance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Section B: Deposits Not Credited (DNC) -->
    <div class="section-title">
        B. ADD: DEPOSITS NOT YET CREDITED (DNC)
        <div class="section-subtitle">Cash/cheques/receipts recorded in books but not yet reflected in bank statement</div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Date</th>
                <th style="width: 15%;">Reference</th>
                <th style="width: 60%;">Description</th>
                <th style="width: 15%;" class="number">Amount (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dncItems as $item)
            <tr>
                <td>{{ $item->transaction_date->format('d/m/Y') }}</td>
                <td><span class="badge badge-dnc">{{ $item->reference ?? 'N/A' }}</span></td>
                <td>{{ Str::limit($item->description, 50) }}</td>
                <td class="number">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center" style="font-style: italic; color: #666;">No deposits not yet credited</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="number"><strong>Total DNC</strong></td>
                <td class="number"><strong>{{ number_format($totalDNC, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Section C: Unpresented Cheques (UPC) -->
    <div class="section-title">
        C. LESS: UNPRESENTED CHEQUES (UPC)
        <div class="section-subtitle">Payments recorded in books but not yet cleared by bank</div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Date</th>
                <th style="width: 15%;">Cheque No / Reference</th>
                <th style="width: 60%;">Payee</th>
                <th style="width: 15%;" class="number">Amount (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($upcItems as $item)
            <tr>
                <td>{{ $item->transaction_date->format('d/m/Y') }}</td>
                <td><span class="badge badge-upc">{{ $item->reference ?? 'N/A' }}</span></td>
                <td>{{ Str::limit($item->description, 50) }}</td>
                <td class="number">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center" style="font-style: italic; color: #666;">No unpresented cheques</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="number"><strong>Total UPC</strong></td>
                <td class="number"><strong>{{ number_format($totalUPC, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Section D: Bank Errors -->
    @if($bankErrors->count() > 0)
    <div class="section-title">
        D. ADD / LESS: BANK ERRORS (if any)
        <div class="section-subtitle">Bank posting mistakes – rare but must be shown</div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th style="width: 20%;">Adjustment</th>
                <th style="width: 30%;" class="number">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bankErrors as $item)
            <tr>
                <td>{{ Str::limit($item->description, 40) }}</td>
                <td>{{ $item->nature === 'credit' ? 'Add' : 'Less' }}</td>
                <td class="number">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="number"><strong>Total Bank Errors</strong></td>
                <td class="number"><strong>{{ number_format($totalBankErrors, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
    @endif

    <!-- Section E: Adjusted Bank Balance -->
    <div class="section-title">E. ADJUSTED BANK BALANCE</div>
    <table class="data-table">
        <tbody>
            <tr>
                <td style="width: 75%;">Bank Statement Closing Balance</td>
                <td class="number" style="width: 25%;">{{ number_format($bankReconciliation->bank_statement_balance, 2) }}</td>
            </tr>
            <tr>
                <td>ADD: Deposits Not Credited</td>
                <td class="number">{{ number_format($totalDNC, 2) }}</td>
            </tr>
            <tr>
                <td>LESS: Unpresented Cheques</td>
                <td class="number" style="color: #dc3545;">({{ number_format($totalUPC, 2) }})</td>
            </tr>
            @if($totalBankErrors > 0)
            <tr>
                <td>ADD/LESS: Bank Errors</td>
                <td class="number">{{ number_format($totalBankErrors, 2) }}</td>
            </tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="final-balance-row">
                <td><strong>Adjusted Bank Balance</strong></td>
                <td class="number"><strong>{{ number_format($adjustedBankBalance, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Section F: Book Balance -->
    <div class="section-title">F. BOOK BALANCE AS PER GENERAL LEDGER</div>
    <table class="data-table">
        <tbody>
            <tr>
                <td style="width: 75%;">Closing balance per Cashbook / GL</td>
                <td class="number" style="width: 25%;">{{ number_format($bankReconciliation->book_balance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Reconciliation Status -->
    <div class="reconciliation-status {{ abs($adjustedBankBalance - $bankReconciliation->book_balance) < 0.01 ? 'status-reconciled' : 'status-not-reconciled' }}">
        @if(abs($adjustedBankBalance - $bankReconciliation->book_balance) < 0.01)
            <h3>BANK RECONCILED</h3>
            <p>Adjusted Bank Balance ({{ number_format($adjustedBankBalance, 2) }}) = GL Balance ({{ number_format($bankReconciliation->book_balance, 2) }})</p>
        @else
            <h3> NOT RECONCILED</h3>
            <p>Difference: {{ number_format(abs($adjustedBankBalance - $bankReconciliation->book_balance), 2) }}</p>
        @endif
    </div>

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} | Reconciliation ID: {{ $bankReconciliation->id }}</p>
    </div>
</body>
</html>
