<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reorder Report</title>
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
        .data-table th:nth-child(2) { width: 20%; }
        .data-table th:nth-child(3) { width: 12%; }
        .data-table th:nth-child(4) { width: 10%; }
        .data-table th:nth-child(5) { width: 10%; }
        .data-table th:nth-child(6) { width: 10%; }
        .data-table th:nth-child(7) { width: 10%; }
        .data-table th:nth-child(8) { width: 10%; }
        .data-table th:nth-child(9) { width: 10%; }
        
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
        
        .warning {
            color: #dc3545;
            font-weight: 600;
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
                <h1>Reorder Report</h1>
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
            @if($category)
            <div class="info-row">
                <div class="info-label">Category:</div>
                <div class="info-value">{{ $category->name }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">Items below or at reorder level</div>
            </div>
        </div>
    </div>

    @if($reorderItems->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th class="number">Current Stock</th>
                    <th class="number">Reorder Level</th>
                    <th class="number">Suggested Qty</th>
                    <th>Unit</th>
                    <th class="number">Unit Cost</th>
                    <th class="number">Suggested Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reorderItems as $item)
                <tr>
                    <td>{{ $item['item']->code }}</td>
                    <td>{{ $item['item']->name }}</td>
                    <td>{{ $item['item']->category->name ?? 'N/A' }}</td>
                    <td class="number warning">{{ number_format($item['available'], 2) }}</td>
                    <td class="number">{{ number_format($item['item']->reorder_level ?? 0, 2) }}</td>
                    <td class="number">{{ number_format($item['suggested_qty'], 2) }}</td>
                    <td>{{ $item['item']->unit_of_measure }}</td>
                    <td class="number">{{ number_format($item['item']->resolved_cost_price ?? $item['item']->cost_price ?? 0, 2) }} TZS</td>
                    <td class="number">{{ number_format($item['suggested_qty'] * ($item['item']->resolved_cost_price ?? $item['item']->cost_price ?? 0), 2) }} TZS</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="number">{{ number_format($reorderItems->sum('available'), 2) }}</td>
                    <td class="number">{{ number_format($reorderItems->sum(function($item) { return $item['item']->reorder_level ?? 0; }), 2) }}</td>
                    <td class="number">{{ number_format($totalSuggestedQty, 2) }}</td>
                    <td></td>
                    <td></td>
                    <td class="number">{{ number_format($totalSuggestedValue, 2) }} TZS</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No items require reordering at this time. All items are above their reorder levels.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">This report shows items that are at or below their reorder level and suggests quantities to order.</p>
    </div>
</body>
</html>
