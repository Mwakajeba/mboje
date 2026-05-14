<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock on Hand Report</title>
    <style>
        body {
            font-family: dejavu sans, sans-serif;
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
        .data-table th:nth-child(4) { width: 8%; }
        .data-table th:nth-child(5) { width: 10%; }
        .data-table th:nth-child(6) { width: 10%; }
        .data-table th:nth-child(7) { width: 12%; }
        .data-table th:nth-child(8) { width: 20%; }
        
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
            font-family: Courier, monospace;
        }
        
        .locations {
            font-size: 9px;
            line-height: 1.4;
            color: #555;
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
        
        .costing-method {
            margin-top: 20px;
            padding: 12px;
            background: #f8f9fa;
            border-left: 4px solid #17a2b8;
            border-radius: 4px;
            font-size: 11px;
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
                <h1>Stock on Hand Report</h1>
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
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
            @if($location)
            <div class="info-row">
                <div class="info-label">Location:</div>
                <div class="info-value">{{ $location->name }}</div>
            </div>
            @endif
            @if($category)
            <div class="info-row">
                <div class="info-label">Category:</div>
                <div class="info-value">{{ $category->name }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Costing Method:</div>
                <div class="info-value">{{ ucfirst($systemCostMethod) }}</div>
            </div>
        </div>
    </div>

    @if($itemsWithStock->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>UOM</th>
                    <th class="number">Unit Cost</th>
                    <th class="number">Total Stock</th>
                    <th class="number">Total Value</th>
                    <th>Locations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itemsWithStock as $itemData)
                <tr>
                    <td>{{ $itemData['item']->code }}</td>
                    <td>{{ $itemData['item']->name }}</td>
                    <td>{{ $itemData['item']->category->name ?? 'N/A' }}</td>
                    <td>{{ $itemData['item']->unit_of_measure }}</td>
                    <td class="number">{{ number_format($itemData['unit_cost'], 2) }} TZS</td>
                    <td class="number">{{ number_format($itemData['total_stock'], 2) }}</td>
                    <td class="number">{{ number_format($itemData['total_value'], 2) }} TZS</td>
                    <td class="locations">
                        @if(count($itemData['locations']) > 0)
                            @foreach($itemData['locations'] as $locationData)
                                <strong>{{ $locationData['location']->name }}:</strong> {{ number_format($locationData['stock'], 2) }} ({{ number_format($locationData['value'], 0) }} TZS)
                                @if(!$loop->last)<br>@endif
                            @endforeach
                        @else
                            <span style="color: #999;">No stock</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="number">{{ number_format($totalQuantity, 2) }}</td>
                    <td class="number">{{ number_format($totalValue, 2) }} TZS</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="costing-method">
            <strong>Costing Method:</strong> {{ ucfirst($systemCostMethod) }} costing method is being used for unit cost calculations.
            @if($systemCostMethod === 'fifo')
                Values are calculated based on actual cost layers and consumption patterns.
            @else
                Values are calculated using the weighted average cost from the item's cost_price field.
            @endif
        </div>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No items with stock found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
    </div>
</body>
</html>
