<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receivables Aging Report</title>
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
            width: 120px;
            color: #555;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #333;
        }
        
        .section-title {
            font-size: 14px;
            color: #17a2b8;
            margin: 20px 0 10px 0;
            font-weight: bold;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        
        .data-table .totals {
            font-weight: bold;
            background: #f8f9fa;
        }
        
        .number {
            text-align: right;
            font-family: 'Courier', 'Courier New', monospace;
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
        
        .mt-2 {
            margin-top: 8px;
        }
        
        .mt-3 {
            margin-top: 12px;
        }
        
        .mb-2 {
            margin-bottom: 8px;
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
                <h1>Receivables Aging Report</h1>
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
                <div class="info-label">As of Date:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</div>
            </div>
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
            @if(isset($viewType))
            <div class="info-row">
                <div class="info-label">View Type:</div>
                <div class="info-value">{{ ucfirst($viewType) }}</div>
            </div>
            @endif
        </div>
    </div>

    @php $vt = strtolower($viewType ?? 'summary'); @endphp

    @if($vt === 'summary')
    <!-- 1. Executive Summary -->
    <div class="section-title">1. Executive Summary</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Aging Category</th>
                <th style="width: 20%;" class="number">No. of Invoices</th>
                <th style="width: 25%;" class="number">Outstanding Amount (TZS)</th>
                <th style="width: 25%;" class="number">% of Total Receivables</th>
            </tr>
        </thead>
        <tbody>
            @php
                $labels = ['0-30' => '0 – 30 Days', '31-60' => '31 – 60 Days', '61-90' => '61 – 90 Days', '90+' => 'Over 90 Days'];
                $sumCount = collect($summary)->reduce(function($carry, $r){
                    return $carry + ($r['count'] ?? 0);
                }, 0);
                $sumAmount = collect($summary)->reduce(function($carry, $r){
                    return $carry + ($r['amount'] ?? 0);
                }, 0);
            @endphp
            @foreach($labels as $key => $label)
                @php
                    $row = $summary[$key] ?? ['count' => 0, 'amount' => 0, 'pct' => 0];
                @endphp
                <tr>
                    <td>{{ $label }}</td>
                    <td class="number">{{ number_format($row['count']) }}</td>
                    <td class="number">{{ number_format($row['amount'], 2) }}</td>
                    <td class="number">{{ number_format($row['pct'], 1) }}%</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td>Total Outstanding</td>
                <td class="number">{{ number_format($sumCount) }}</td>
                <td class="number">{{ number_format($totalOutstanding, 2) }}</td>
                <td class="number">{{ number_format(100, 1) }}%</td>
            </tr>
        </tbody>
    </table>
    @endif

    @if($vt === 'detailed')
        @foreach($detailedAllBuckets as $bucketData)
            <div class="section-title mt-3">2. Detailed Invoice Aging ({{ $bucketData['label'] }})</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Customer Name</th>
                        <th style="width: 12%;">Invoice #</th>
                        <th style="width: 10%;">Invoice Date</th>
                        <th style="width: 10%;">Due Date</th>
                        <th style="width: 12%;" class="number">Amount (TZS)</th>
                        <th style="width: 10%;">Days Overdue</th>
                        <th style="width: 8%;">Status</th>
                        <th style="width: 18%;">Collection Note / Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @php $bucketGrand = 0; @endphp
                    @foreach($bucketData['groups'] as $group)
                        @php $first = true; @endphp
                        @foreach($group['invoices'] as $inv)
                            @php
                                $invDate = \Carbon\Carbon::parse($inv['invoice_date']);
                                $dueDate = isset($inv['due_date']) ? \Carbon\Carbon::parse($inv['due_date']) : $invDate->copy()->addDays(30);
                                $daysText = ($inv['days_overdue'] ?? 0) > 0 ? ($inv['days_overdue']) : 'Not yet due';
                                $status = ucfirst($inv['status'] ?? 'draft');
                                $bucketGrand += $inv['outstanding_amount'];
                            @endphp
                            <tr>
                                <td>{{ $first ? $group['customer_name'] : '' }}</td>
                                <td>{{ $inv['invoice_number'] }}</td>
                                <td>{{ $invDate->format('m/d/Y') }}</td>
                                <td>{{ $dueDate->format('m/d/Y') }}</td>
                                <td class="number">{{ number_format($inv['outstanding_amount'], 2) }}</td>
                                <td>{{ is_numeric($daysText) ? $daysText : $daysText }}</td>
                                <td>{{ $status }}</td>
                                <td></td>
                            </tr>
                            @php $first = false; @endphp
                        @endforeach
                        <tr class="totals">
                            <td colspan="4">Subtotal — {{ $group['customer_name'] }}</td>
                            <td class="number">{{ number_format($group['subtotal'], 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    @endforeach
                    <tr class="totals">
                        <td colspan="4">TOTAL OUTSTANDING ({{ $bucketData['label'] }})</td>
                        <td class="number">{{ number_format($bucketGrand, 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endif

    @if($vt === 'trend')
    <!-- 3. Aging Trend Comparison -->
    <div class="section-title mt-3">3. Aging Trend Comparison</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Aging Bucket</th>
                <th style="width: 25%;" class="number">Current Month (TZS)</th>
                <th style="width: 25%;" class="number">Previous Month (TZS)</th>
                <th style="width: 20%;" class="number">% Change</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trend as $row)
                <tr>
                    <td>
                        @if($row['bucket']==='0-30') 0 – 30 Days
                        @elseif($row['bucket']==='31-60') 31 – 60 Days
                        @elseif($row['bucket']==='61-90') 61 – 90 Days
                        @else Over 90 Days @endif
                    </td>
                    <td class="number">{{ number_format($row['current'], 2) }}</td>
                    <td class="number">{{ number_format($row['previous'], 2) }}</td>
                    <td class="number">{{ ($row['pct_change']>0?'+':'') . number_format($row['pct_change'], 1) }}%</td>
                </tr>
            @endforeach
            @php
                $currentTotal = array_sum(array_map(fn($r)=>$r['current'], $trend->toArray()));
                $prevTotal = array_sum(array_map(fn($r)=>$r['previous'], $trend->toArray()));
                $totalChange = $prevTotal>0 ? (($currentTotal - $prevTotal)/$prevTotal)*100 : ($currentTotal>0?100:0);
            @endphp
            <tr class="totals">
                <td>Total</td>
                <td class="number">{{ number_format($currentTotal, 2) }}</td>
                <td class="number">{{ number_format($prevTotal, 2) }}</td>
                <td class="number">{{ ($totalChange>0?'+':'') . number_format($totalChange, 1) }}% overall</td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
