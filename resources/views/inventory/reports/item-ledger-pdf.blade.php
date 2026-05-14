<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Ledger Report - {{ $item->name }}</title>
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
        
        .item-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #17a2b8;
        }
        
        .item-info h3 {
            margin: 0 0 10px 0;
            color: #17a2b8;
            font-size: 16px;
        }
        
        .item-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .item-detail {
            flex: 1;
            min-width: 150px;
        }
        
        .item-detail strong {
            color: #555;
            margin-right: 5px;
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
        
        .data-table th:nth-child(1) { width: 10%; }
        .data-table th:nth-child(2) { width: 12%; }
        .data-table th:nth-child(3) { width: 12%; }
        .data-table th:nth-child(4) { width: 8%; }
        .data-table th:nth-child(5) { width: 8%; }
        .data-table th:nth-child(6) { width: 10%; }
        .data-table th:nth-child(7) { width: 10%; }
        .data-table th:nth-child(8) { width: 12%; }
        .data-table th:nth-child(9) { width: 18%; }
        
        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #dee2e6;
            font-size: 9px;
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
            font-weight: bold;
        }
        
        .data-table tfoot td {
            border-top: 2px solid #17a2b8;
            padding: 10px 6px;
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
                <h1>Item Ledger Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ $generatedAt->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <h3>Report Parameters</h3>
        <div class="info-grid">
            @if($dateFromCarbon && $dateToCarbon)
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $dateFromCarbon->format('M d, Y') }} - {{ $dateToCarbon->format('M d, Y') }}</div>
            </div>
            @elseif($dateFromCarbon)
            <div class="info-row">
                <div class="info-label">Date From:</div>
                <div class="info-value">{{ $dateFromCarbon->format('M d, Y') }}</div>
            </div>
            @elseif($dateToCarbon)
            <div class="info-row">
                <div class="info-label">Date To:</div>
                <div class="info-value">{{ $dateToCarbon->format('M d, Y') }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Item:</div>
                <div class="info-value">{{ $item->code }} - {{ $item->name }}</div>
            </div>
        </div>
    </div>

    <div class="item-info">
        <h3>Item Information</h3>
        <div class="item-details">
            <div class="item-detail">
                <strong>Item Code:</strong> {{ $item->code }}
            </div>
            <div class="item-detail">
                <strong>Item Name:</strong> {{ $item->name }}
            </div>
            <div class="item-detail">
                <strong>Category:</strong> {{ $item->category->name ?? 'N/A' }}
            </div>
            <div class="item-detail">
                <strong>Unit of Measure:</strong> {{ $item->unit_of_measure }}
            </div>
            <div class="item-detail">
                <strong>Current Stock:</strong> {{ number_format($finalRunningQty, 2) }} {{ $item->unit_of_measure }}
            </div>
        </div>
    </div>

    @if(count($ledgerEntries) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Type</th>
                    <th class="number">In Qty</th>
                    <th class="number">Out Qty</th>
                    <th class="number">Unit Cost</th>
                    <th class="number">Running Qty</th>
                    <th class="number">Running Value</th>
                    <th class="number">Avg Unit Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ledgerEntries as $entry)
                    @php
                        $movement = $entry['movement'];
                        $isIn = in_array($movement->movement_type, [
                            'in', 'adjustment_in', 'purchased', 'opening_balance', 'return_in', 'transfer_in'
                        ]);
                    @endphp
                    <tr>
                        <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $movement->reference_type }} #{{ $movement->reference_id }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}</td>
                        <td class="number">{{ $isIn ? number_format($movement->quantity, 2) : '-' }}</td>
                        <td class="number">{{ !$isIn ? number_format($movement->quantity, 2) : '-' }}</td>
                        <td class="number">{{ number_format($entry['unit_cost'] ?? $movement->unit_cost, 2) }} TZS</td>
                        <td class="number"><strong>{{ number_format($entry['running_qty'], 2) }}</strong></td>
                        <td class="number"><strong>{{ number_format($entry['running_value'], 2) }} TZS</strong></td>
                        <td class="number">{{ number_format($entry['avg_unit_cost'] ?? ($entry['running_qty'] > 0 ? $entry['running_value'] / $entry['running_qty'] : 0), 2) }} TZS</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="number">{{ number_format($totalInQty, 2) }}</td>
                    <td class="number">{{ number_format($totalOutQty, 2) }}</td>
                    <td></td>
                    <td class="number"><strong>{{ number_format($finalRunningQty, 2) }}</strong></td>
                    <td class="number"><strong>{{ number_format($finalRunningValue, 2) }} TZS</strong></td>
                    <td class="number">{{ number_format($finalRunningQty > 0 ? $finalRunningValue / $finalRunningQty : 0, 2) }} TZS</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No ledger entries found for this item in the selected period.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">Item Ledger (Kardex) Report showing all inventory movements for {{ $item->name }} ({{ $item->code }})</p>
    </div>
</body>
</html>
