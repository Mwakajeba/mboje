<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Cash Flows - {{ $company->name ?? 'SmartAccounting' }}</title>
    <style>
        @page {
            margin: 1.5cm 1.5cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .report-period {
            font-size: 10pt;
            margin-bottom: 3px;
        }
        .report-method {
            font-size: 9pt;
            font-style: italic;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .section-header {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #000;
        }
        
        .line-item {
            padding: 3px 0;
        }
        
        .line-item td {
            padding: 3px 5px;
        }
        
        .line-item.level-1 td:first-child {
            padding-left: 15px;
        }
        
        .line-item.level-2 td:first-child {
            padding-left: 30px;
        }
        
        .line-item.level-3 td:first-child {
            padding-left: 45px;
        }
        
        .line-item.header-row td {
            font-weight: bold;
            padding-left: 30px;
            font-style: italic;
        }
        
        .line-item.subtotal {
            border-top: 1px solid #ccc;
            font-weight: bold;
        }
        
        .section-total {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 5px !important;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .grand-total {
            background-color: #e0e0e0;
            font-weight: bold;
            padding: 6px !important;
            border-top: 2px solid #000;
            border-bottom: 3px double #000;
        }
        
        .amount {
            text-align: right;
            white-space: nowrap;
            width: 120px;
        }
        
        .negative {
            color: #d32f2f;
        }
        
        .positive {
            color: #388e3c;
        }
        
        .summary-section {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 3px solid #000;
        }
        
        .notes {
            margin-top: 30px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            font-size: 8pt;
            page-break-inside: avoid;
        }
        
        .notes h4 {
            font-size: 9pt;
            margin: 0 0 10px 0;
            font-weight: bold;
        }
        
        .notes ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .notes li {
            margin-bottom: 5px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'SmartAccounting' }}</div>
        <div class="report-title">Statement of Cash Flows</div>
        <div class="report-period">
            For the period from {{ \Carbon\Carbon::parse($fromDate)->format('F d, Y') }} 
            to {{ \Carbon\Carbon::parse($toDate)->format('F d, Y') }}
        </div>
        <div class="report-method">{{ ucfirst($method) }} Method (IAS 7)</div>
        @if(isset($branchName) && $branchName !== 'All Branches')
            <div class="report-method">{{ $branchName }}</div>
        @endif
    </div>

    <!-- Operating Activities -->
    <div class="section-header">CASH FLOWS FROM OPERATING ACTIVITIES</div>
    <table>
        @foreach($cashFlowData['cash_flows']['operating']['line_items'] as $item)
            @if(isset($item['is_header']) && $item['is_header'])
                <tr class="line-item header-row">
                    <td>{{ $item['name'] }}</td>
                    <td class="amount"></td>
                </tr>
            @else
                <tr class="line-item level-{{ $item['level'] ?? 1 }} {{ isset($item['is_subtotal']) && $item['is_subtotal'] ? 'subtotal' : '' }}">
                    <td>{{ $item['name'] }}</td>
                    <td class="amount {{ ($item['amount'] ?? 0) < 0 ? 'negative' : '' }}">
                        @if($item['amount'] !== null)
                            {{ number_format(abs($item['amount']), 2) }}
                            @if($item['amount'] < 0)
                                ({{ number_format(abs($item['amount']), 2) }})
                            @endif
                        @endif
                    </td>
                </tr>
            @endif
        @endforeach
        <tr class="section-total">
            <td>Net cash from operating activities</td>
            <td class="amount {{ $cashFlowData['cash_flows']['operating']['net'] < 0 ? 'negative' : 'positive' }}">
                @if($cashFlowData['cash_flows']['operating']['net'] < 0)
                    ({{ number_format(abs($cashFlowData['cash_flows']['operating']['net']), 2) }})
                @else
                    {{ number_format($cashFlowData['cash_flows']['operating']['net'], 2) }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Investing Activities -->
    <div class="section-header">CASH FLOWS FROM INVESTING ACTIVITIES</div>
    <table>
        @foreach($cashFlowData['cash_flows']['investing']['line_items'] as $item)
            @if(abs($item['amount'] ?? 0) > 0.01)
                <tr class="line-item level-1">
                    <td>{{ $item['name'] }}</td>
                    <td class="amount {{ ($item['amount'] ?? 0) < 0 ? 'negative' : '' }}">
                        @if($item['amount'] < 0)
                            ({{ number_format(abs($item['amount']), 2) }})
                        @else
                            {{ number_format($item['amount'], 2) }}
                        @endif
                    </td>
                </tr>
            @endif
        @endforeach
        <tr class="section-total">
            <td>Net cash from investing activities</td>
            <td class="amount {{ $cashFlowData['cash_flows']['investing']['net'] < 0 ? 'negative' : 'positive' }}">
                @if($cashFlowData['cash_flows']['investing']['net'] < 0)
                    ({{ number_format(abs($cashFlowData['cash_flows']['investing']['net']), 2) }})
                @else
                    {{ number_format($cashFlowData['cash_flows']['investing']['net'], 2) }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Financing Activities -->
    <div class="section-header">CASH FLOWS FROM FINANCING ACTIVITIES</div>
    <table>
        @foreach($cashFlowData['cash_flows']['financing']['line_items'] as $item)
            @if(abs($item['amount'] ?? 0) > 0.01)
                <tr class="line-item level-1">
                    <td>{{ $item['name'] }}</td>
                    <td class="amount {{ ($item['amount'] ?? 0) < 0 ? 'negative' : '' }}">
                        @if($item['amount'] < 0)
                            ({{ number_format(abs($item['amount']), 2) }})
                        @else
                            {{ number_format($item['amount'], 2) }}
                        @endif
                    </td>
                </tr>
            @endif
        @endforeach
        <tr class="section-total">
            <td>Net cash from financing activities</td>
            <td class="amount {{ $cashFlowData['cash_flows']['financing']['net'] < 0 ? 'negative' : 'positive' }}">
                @if($cashFlowData['cash_flows']['financing']['net'] < 0)
                    ({{ number_format(abs($cashFlowData['cash_flows']['financing']['net']), 2) }})
                @else
                    {{ number_format($cashFlowData['cash_flows']['financing']['net'], 2) }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
        <table>
            <tr class="line-item">
                <td style="font-weight: bold; text-transform: uppercase;">NET INCREASE/(DECREASE) IN CASH AND CASH EQUIVALENTS</td>
                <td class="amount" style="font-weight: bold; {{ $cashFlowData['net_cash_flow'] < 0 ? 'color: #d32f2f;' : 'color: #388e3c;' }}">
                    @if($cashFlowData['net_cash_flow'] < 0)
                        ({{ number_format(abs($cashFlowData['net_cash_flow']), 2) }})
                    @else
                        {{ number_format($cashFlowData['net_cash_flow'], 2) }}
                    @endif
                </td>
            </tr>
            <tr class="line-item" style="margin-top: 10px;">
                <td style="padding-top: 10px;">Cash and cash equivalents at beginning of period</td>
                <td class="amount" style="padding-top: 10px;">{{ number_format($cashFlowData['opening_cash'], 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Cash and cash equivalents at end of period</td>
                <td class="amount">{{ number_format($cashFlowData['closing_cash'], 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Notes -->
    @if(isset($cashFlowData['notes']) && count($cashFlowData['notes']) > 0)
        <div class="notes">
            <h4>NOTES TO THE STATEMENT OF CASH FLOWS:</h4>
            <ol>
                @foreach($cashFlowData['notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ol>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Generated on {{ now()->format('F d, Y \a\t H:i') }} | 
        {{ $company->name ?? 'SmartAccounting' }} | 
        IFRS Compliant Report (IAS 7)
    </div>
</body>
</html>
