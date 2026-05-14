<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Changes in Equity - {{ $company->name ?? 'SmartAccounting' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm 1cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .report-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .report-period {
            font-size: 9pt;
            margin-bottom: 2px;
        }
        .report-method {
            font-size: 8pt;
            font-style: italic;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 5px 3px;
            text-align: right;
            font-weight: bold;
            font-size: 8pt;
        }
        
        th:first-child {
            text-align: left;
            min-width: 120px;
        }
        
        td {
            border: 1px solid #ccc;
            padding: 4px 3px;
            text-align: right;
            font-size: 8pt;
        }
        
        td:first-child {
            text-align: left;
            font-weight: normal;
        }
        
        .opening-balance {
            background-color: #e8f5e9;
            font-weight: bold;
        }
        
        .closing-balance {
            background-color: #c8e6c9;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .section-header {
            background-color: #fff9c4;
            font-weight: bold;
            border-top: 1px solid #000;
        }
        
        .section-header td {
            text-align: left;
        }
        
        .subtotal {
            background-color: #f0f0f0;
            font-weight: bold;
            border-top: 1px solid #666;
        }
        
        .line-item td:first-child {
            padding-left: 15px;
        }
        
        .total-column {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        
        .negative {
            color: #d32f2f;
        }
        
        .amount {
            white-space: nowrap;
        }
        
        .notes {
            margin-top: 20px;
            padding: 8px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            font-size: 7pt;
            page-break-inside: avoid;
        }
        
        .notes h4 {
            font-size: 8pt;
            margin: 0 0 8px 0;
            font-weight: bold;
        }
        
        .notes ol {
            margin: 0;
            padding-left: 15px;
        }
        
        .notes li {
            margin-bottom: 3px;
        }
        
        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            font-size: 7pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'SmartAccounting' }}</div>
        <div class="report-title">Statement of Changes in Equity</div>
        <div class="report-period">
            For the year ended {{ \Carbon\Carbon::parse($toDate)->format('F d, Y') }}
        </div>
        <div class="report-method">IAS 1 Compliant Format</div>
        @if(isset($branchName) && $branchName !== 'All Branches')
            <div class="report-method">{{ $branchName }}</div>
        @endif
    </div>

    <!-- Equity Statement Table -->
    <table>
        <thead>
            <tr>
                <th></th>
                @foreach($equityStatementData['equity_components'] as $component)
                    <th>{{ $component['name'] }}</th>
                @endforeach
                <th class="total-column">Total<br>Equity</th>
            </tr>
        </thead>
        <tbody>
            <!-- Opening Balance -->
            <tr class="opening-balance">
                <td><strong>Balance at {{ \Carbon\Carbon::parse($fromDate)->format('M d, Y') }}</strong></td>
                @foreach($equityStatementData['equity_components'] as $component)
                    <td class="amount">
                        {{ number_format($equityStatementData['opening_balances'][$component['key']] ?? 0, 2) }}
                    </td>
                @endforeach
                <td class="amount total-column">
                    {{ number_format($equityStatementData['total_opening'], 2) }}
                </td>
            </tr>

            <!-- Empty Row -->
            <tr>
                <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" style="border: none; padding: 3px;"></td>
            </tr>

            <!-- Changes Header -->
            <tr class="section-header">
                <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}">
                    <strong>Changes in equity for the year:</strong>
                </td>
            </tr>

            @php
                // Collect all line items
                $allLineItems = [];
                foreach ($equityStatementData['equity_components'] as $component) {
                    $movements = $equityStatementData['movements'][$component['key']];
                    foreach ($movements['line_items'] as $item) {
                        if (!isset($allLineItems[$item['name']])) {
                            $allLineItems[$item['name']] = [
                                'name' => $item['name'],
                                'category' => $item['category'],
                                'amounts' => []
                            ];
                        }
                        $allLineItems[$item['name']]['amounts'][$component['key']] = $item['amount'];
                    }
                }
                
                // Group by category
                $comprehensiveIncomeItems = array_filter($allLineItems, fn($item) => $item['category'] === 'comprehensive_income');
                $transactionsWithOwnersItems = array_filter($allLineItems, fn($item) => $item['category'] === 'transactions_with_owners');
            @endphp

            <!-- Comprehensive Income Items -->
            @if(count($comprehensiveIncomeItems) > 0)
                @foreach($comprehensiveIncomeItems as $item)
                    <tr class="line-item">
                        <td>{{ $item['name'] }}</td>
                        @php $rowTotal = 0; @endphp
                        @foreach($equityStatementData['equity_components'] as $component)
                            @php
                                $amount = $item['amounts'][$component['key']] ?? 0;
                                $rowTotal += $amount;
                            @endphp
                            <td class="amount">
                                @if(abs($amount) > 0.01)
                                    <span class="{{ $amount < 0 ? 'negative' : '' }}">
                                        {{ number_format($amount, 2) }}
                                    </span>
                                @else
                                    --
                                @endif
                            </td>
                        @endforeach
                        <td class="amount total-column">
                            <span class="{{ $rowTotal < 0 ? 'negative' : '' }}">
                                {{ number_format($rowTotal, 2) }}
                            </span>
                        </td>
                    </tr>
                @endforeach

                <!-- Total Comprehensive Income -->
                <tr class="subtotal">
                    <td><strong>Total comprehensive income</strong></td>
                    @php $totalCompIncome = 0; @endphp
                    @foreach($equityStatementData['equity_components'] as $component)
                        @php
                            $total = 0;
                            foreach ($comprehensiveIncomeItems as $item) {
                                $total += $item['amounts'][$component['key']] ?? 0;
                            }
                            $totalCompIncome += $total;
                        @endphp
                        <td class="amount">{{ number_format($total, 2) }}</td>
                    @endforeach
                    <td class="amount total-column">{{ number_format($totalCompIncome, 2) }}</td>
                </tr>
            @endif

            <!-- Transactions with Owners -->
            @if(count($transactionsWithOwnersItems) > 0)
                <!-- Empty Row -->
                <tr>
                    <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" style="border: none; padding: 3px;"></td>
                </tr>

                <tr class="section-header">
                    <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}">
                        <strong>Transactions with owners:</strong>
                    </td>
                </tr>

                @foreach($transactionsWithOwnersItems as $item)
                    <tr class="line-item">
                        <td>{{ $item['name'] }}</td>
                        @php $rowTotal = 0; @endphp
                        @foreach($equityStatementData['equity_components'] as $component)
                            @php
                                $amount = $item['amounts'][$component['key']] ?? 0;
                                $rowTotal += $amount;
                            @endphp
                            <td class="amount">
                                @if(abs($amount) > 0.01)
                                    <span class="{{ $amount < 0 ? 'negative' : '' }}">
                                        {{ number_format($amount, 2) }}
                                    </span>
                                @else
                                    --
                                @endif
                            </td>
                        @endforeach
                        <td class="amount total-column">
                            <span class="{{ $rowTotal < 0 ? 'negative' : '' }}">
                                {{ number_format($rowTotal, 2) }}
                            </span>
                        </td>
                    </tr>
                @endforeach

                <!-- Total Transactions with Owners -->
                <tr class="subtotal">
                    <td><strong>Total transactions with owners</strong></td>
                    @php $totalTxnOwners = 0; @endphp
                    @foreach($equityStatementData['equity_components'] as $component)
                        @php
                            $total = 0;
                            foreach ($transactionsWithOwnersItems as $item) {
                                $total += $item['amounts'][$component['key']] ?? 0;
                            }
                            $totalTxnOwners += $total;
                        @endphp
                        <td class="amount">{{ number_format($total, 2) }}</td>
                    @endforeach
                    <td class="amount total-column">{{ number_format($totalTxnOwners, 2) }}</td>
                </tr>
            @endif

            <!-- Empty Row -->
            <tr>
                <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" style="border: none; padding: 3px;"></td>
            </tr>

            <!-- Closing Balance -->
            <tr class="closing-balance">
                <td><strong>Balance at {{ \Carbon\Carbon::parse($toDate)->format('M d, Y') }}</strong></td>
                @foreach($equityStatementData['equity_components'] as $component)
                    <td class="amount">
                        {{ number_format($equityStatementData['closing_balances'][$component['key']] ?? 0, 2) }}
                    </td>
                @endforeach
                <td class="amount total-column">
                    {{ number_format($equityStatementData['total_closing'], 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Notes -->
    @if(isset($equityStatementData['notes']) && count($equityStatementData['notes']) > 0)
        <div class="notes">
            <h4>NOTES TO THE STATEMENT OF CHANGES IN EQUITY:</h4>
            <ol>
                @foreach($equityStatementData['notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ol>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Generated on {{ now()->format('F d, Y \a\t H:i') }} | 
        {{ $company->name ?? 'SmartAccounting' }} | 
        IFRS Compliant Report (IAS 1)
    </div>
</body>
</html>
