<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Aging Report</title>
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
            font-size: 8px;
        }
        
        .data-table thead {
            background: #17a2b8;
            color: white;
        }
        
        .data-table th {
            padding: 6px 3px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table td {
            padding: 6px 3px;
            border-bottom: 1px solid #dee2e6;
            font-size: 8px;
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
            padding: 8px 3px;
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
                <h1>Inventory Aging Report</h1>
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
        </div>
    </div>

    @if($reportData->count() > 0)
        @php
            $totalQty = $reportData->sum('quantity');
            $totalValue = $reportData->sum('value');
            $total0_30 = $reportData->sum('age_0_30');
            $total31_60 = $reportData->sum('age_31_60');
            $total61_90 = $reportData->sum('age_61_90');
            $total91_180 = $reportData->sum('age_91_180');
            $totalOver180 = $reportData->sum('age_over_180');
        @endphp

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 7%;">Item Code</th>
                    <th style="width: 12%;">Item Name</th>
                    <th style="width: 8%;">Category</th>
                    <th style="width: 10%;">Location</th>
                    <th class="number" style="width: 6%;">Qty on Hand</th>
                    <th class="number" style="width: 6%;">Unit Cost</th>
                    <th class="number" style="width: 8%;">Value (TZS)</th>
                    <th style="width: 8%;">Last Movement</th>
                    <th class="number" style="width: 6%;">0-30 Days</th>
                    <th class="number" style="width: 6%;">31-60 Days</th>
                    <th class="number" style="width: 6%;">61-90 Days</th>
                    <th class="number" style="width: 6%;">91-180 Days</th>
                    <th class="number" style="width: 5%;">>180 Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $data)
                <tr>
                    <td>{{ $data['item']->code }}</td>
                    <td>{{ $data['item']->name }}</td>
                    <td>{{ $data['item']->category->name ?? 'N/A' }}</td>
                    <td>{{ $data['location']->name }}</td>
                    <td class="number">{{ number_format($data['quantity'], 2) }}</td>
                    <td class="number">{{ number_format($data['unit_cost'], 2) }}</td>
                    <td class="number">{{ number_format($data['value'], 2) }}</td>
                    <td>{{ $data['last_movement_date'] ? \Carbon\Carbon::parse($data['last_movement_date'])->format('Y-m-d') : 'N/A' }}</td>
                    <td class="number">{{ number_format($data['age_0_30'], 2) }}</td>
                    <td class="number">{{ number_format($data['age_31_60'], 2) }}</td>
                    <td class="number">{{ number_format($data['age_61_90'], 2) }}</td>
                    <td class="number">{{ number_format($data['age_91_180'], 2) }}</td>
                    <td class="number">{{ number_format($data['age_over_180'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="number">{{ number_format($totalQty, 2) }}</td>
                    <td class="number">-</td>
                    <td class="number">{{ number_format($totalValue, 2) }}</td>
                    <td>-</td>
                    <td class="number">{{ number_format($total0_30, 2) }}</td>
                    <td class="number">{{ number_format($total31_60, 2) }}</td>
                    <td class="number">{{ number_format($total61_90, 2) }}</td>
                    <td class="number">{{ number_format($total91_180, 2) }}</td>
                    <td class="number">{{ number_format($totalOver180, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No inventory aging data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} </p>
    </div>
</body>
</html>

