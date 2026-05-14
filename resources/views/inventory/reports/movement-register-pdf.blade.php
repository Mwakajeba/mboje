<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movement Register Report</title>
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
        .data-table th:nth-child(2) { width: 7%; }
        .data-table th:nth-child(3) { width: 15%; }
        .data-table th:nth-child(4) { width: 10%; }
        .data-table th:nth-child(5) { width: 10%; }
        .data-table th:nth-child(6) { width: 8%; }
        .data-table th:nth-child(7) { width: 8%; }
        .data-table th:nth-child(8) { width: 10%; }
        .data-table th:nth-child(9) { width: 14%; }
        .data-table th:nth-child(10) { width: 10%; }
        
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
            font-family: Courier, monospace;
        }
        
        .text-success {
            color: #28a745;
            font-weight: 600;
        }
        
        .text-danger {
            color: #dc3545;
            font-weight: 600;
        }
        
        .text-info {
            color: #17a2b8;
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
        
        .reference-info {
            font-size: 8px;
            color: #666;
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
                <h1>Movement Register Report</h1>
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
            @if($dateFrom && $dateTo)
            <div class="info-row">
                <div class="info-label">Period:</div>
                <div class="info-value">{{ $dateFrom->format('M d, Y') }} - {{ $dateTo->format('M d, Y') }}</div>
            </div>
            @elseif($dateFrom)
            <div class="info-row">
                <div class="info-label">Date From:</div>
                <div class="info-value">{{ $dateFrom->format('M d, Y') }}</div>
            </div>
            @elseif($dateTo)
            <div class="info-row">
                <div class="info-label">Date To:</div>
                <div class="info-value">{{ $dateTo->format('M d, Y') }}</div>
            </div>
            @endif
            @if($item)
            <div class="info-row">
                <div class="info-label">Item:</div>
                <div class="info-value">{{ $item->code }} - {{ $item->name }}</div>
            </div>
            @endif
            @if($location)
            <div class="info-row">
                <div class="info-label">Location:</div>
                <div class="info-value">{{ $location->name }}</div>
            </div>
            @endif
            @if($selectedMovementType)
            <div class="info-row">
                <div class="info-label">Movement Type:</div>
                <div class="info-value">{{ $movementTypes[$selectedMovementType] ?? ucfirst(str_replace('_', ' ', $selectedMovementType)) }}</div>
            </div>
            @endif
            @if($user)
            <div class="info-row">
                <div class="info-label">User:</div>
                <div class="info-value">{{ $user->name }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($movements->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Ref. No</th>
                    <th>Movement Type</th>
                    <th class="number">In Qty</th>
                    <th class="number">Out Qty</th>
                    <th class="number">Balance Qty</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Location</th>
                    <th>Entered By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $movement)
                <tr>
                    <td>{{ $movement->movement_date ? \Carbon\Carbon::parse($movement->movement_date)->format('Y-m-d') : $movement->created_at->format('Y-m-d') }}</td>
                    <td>
                        @if($movement->reference)
                            {{ $movement->reference }}
                        @elseif($movement->movement_type == 'opening_balance')
                            Opening
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td>{{ $movementTypes[$movement->movement_type] ?? ucfirst(str_replace('_', ' ', $movement->movement_type)) }}</td>
                    <td class="number">
                        @if(isset($movement->in_qty) && $movement->in_qty > 0)
                            <span style="color: #28a745;">+{{ number_format($movement->in_qty, 2) }}</span>
                        @else
                            <span style="color: #999;">–</span>
                        @endif
                    </td>
                    <td class="number">
                        @if(isset($movement->out_qty) && $movement->out_qty > 0)
                            <span style="color: #dc3545;">-{{ number_format($movement->out_qty, 2) }}</span>
                        @else
                            <span style="color: #999;">–</span>
                        @endif
                    </td>
                    <td class="number"><strong>{{ number_format($movement->balance_qty ?? 0, 2) }}</strong></td>
                    <td>{{ $movement->item->code ?? 'N/A' }}</td>
                    <td>{{ $movement->item->name ?? 'N/A' }}</td>
                    <td>{{ $movement->location->name ?? 'N/A' }}</td>
                    <td>
                        @if($movement->user)
                            {{ $movement->user->name }}
                            @if($movement->user->roles && $movement->user->roles->first())
                               
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL:</td>
                    <td class="number" style="font-weight: bold;">
                        <span style="color: #28a745;">+{{ number_format($totalInQuantity, 2) }}</span>
                    </td>
                    <td class="number" style="font-weight: bold;">
                        <span style="color: #dc3545;">-{{ number_format($totalOutQuantity, 2) }}</span>
                    </td>
                    <td class="number" style="font-weight: bold;">{{ number_format($totalInQuantity - $totalOutQuantity, 2) }}</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No inventory movements found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated by Smart Accounting System</p>
        <p>Report ID: {{ strtoupper(uniqid()) }}</p>
        <p style="font-size: 10px; margin-top: 5px;">Movement types are color-coded: Green (+) for stock increases, Red (-) for stock decreases.</p>
    </div>
</body>
</html>
