<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Transfer Movement Report</title>
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
            font-size: 9px;
        }
        
        .data-table thead {
            background: #17a2b8;
            color: white;
        }
        
        .data-table th {
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            word-wrap: break-word;
        }
        
        .data-table td {
            padding: 6px 4px;
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
            padding: 8px 4px;
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
                <h1>Inventory Transfer Movement Report</h1>
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
                <div class="info-value">{{ $dateFromCarbon->format('M d, Y') }} - {{ $dateToCarbon->format('M d, Y') }}</div>
            </div>
            @if($branch)
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->name }}</div>
            </div>
            @endif
            @if($item)
            <div class="info-row">
                <div class="info-label">Item:</div>
                <div class="info-value">{{ $item->code }} - {{ $item->name }}</div>
            </div>
            @endif
            @if($fromLocation)
            <div class="info-row">
                <div class="info-label">From Location:</div>
                <div class="info-value">{{ $fromLocation->name }}</div>
            </div>
            @endif
            @if($toLocation)
            <div class="info-row">
                <div class="info-label">To Location:</div>
                <div class="info-value">{{ $toLocation->name }}</div>
            </div>
            @endif
        </div>
    </div>

    @if(count($reportData) > 0)
        @php
            $totalQty = collect($reportData)->sum('quantity');
            $totalValue = collect($reportData)->sum('total_value');
        @endphp

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Transfer ID</th>
                    <th style="width: 8%;">Date</th>
                    <th style="width: 8%;">Item Code</th>
                    <th style="width: 15%;">Item Name</th>
                    <th style="width: 10%;">Category</th>
                    <th style="width: 12%;">From Location</th>
                    <th style="width: 12%;">To Location</th>
                    <th class="number" style="width: 8%;">Quantity</th>
                    <th class="number" style="width: 9%;">Unit Cost</th>
                    <th class="number" style="width: 10%;">Total Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $data)
                <tr>
                    <td>{{ $data['transfer_id'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($data['date'])->format('Y-m-d') }}</td>
                    <td>{{ $data['item']->code ?? 'N/A' }}</td>
                    <td>{{ $data['item']->name ?? 'N/A' }}</td>
                    <td>{{ $data['item']->category->name ?? 'N/A' }}</td>
                    <td>{{ $data['from_location']->name ?? 'N/A' }}</td>
                    <td>{{ $data['to_location']->name ?? 'N/A' }}</td>
                    <td class="number">{{ number_format($data['quantity'], 2) }}</td>
                    <td class="number">{{ number_format($data['unit_cost'], 2) }}</td>
                    <td class="number">{{ number_format($data['total_value'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="number">{{ number_format($totalQty, 2) }}</td>
                    <td class="number">-</td>
                    <td class="number">{{ number_format($totalValue, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No transfer data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }} </p>
    </div>
</body>
</html>

