<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Occupancy Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #0d6efd; padding-bottom: 15px; }
        .header-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 30px;
        }
        .logo-section {
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }
        .company-logo {
            height: 100px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        .title-section {
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .header h1 { color: #0d6efd; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .report-info { margin-bottom: 15px; font-size: 11px; }
        .summary-cards { display: flex; gap: 10px; margin-bottom: 20px; }
        .summary-card { flex: 1; padding: 15px; border: 1px solid #ddd; text-align: center; background-color: #f8f9fa; }
        .summary-card h3 { margin: 5px 0; font-size: 18px; }
        .summary-card p { margin: 0; font-size: 12px; color: #666; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 8px; }
        table.data-table th { background-color: #0d6efd; color: white; font-weight: bold; text-align: left; }
        .text-end { text-align: right; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 9px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                @if($company && $company->logo)
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $company->name }}" class="company-logo">
                @endif
            </div>
            <div class="title-section">
                <h1>Daily Occupancy Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                @if($branch)
                    <div class="subtitle">{{ $branch->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ $generatedAt->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <strong>Date:</strong> {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <h3>{{ number_format($totalRooms) }}</h3>
            <p>Total Rooms</p>
        </div>
        <div class="summary-card">
            <h3>{{ number_format($availableRooms) }}</h3>
            <p>Available Rooms</p>
        </div>
        <div class="summary-card">
            <h3>{{ number_format($occupiedRooms) }}</h3>
            <p>Occupied Rooms</p>
        </div>
        <div class="summary-card">
            <h3>{{ number_format($reservedRooms) }}</h3>
            <p>Reserved Rooms</p>
        </div>
        <div class="summary-card">
            <h3>{{ number_format($occupancyRate, 1) }}%</h3>
            <p>Occupancy Rate</p>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-end">Total Rooms</th>
                <th class="text-end">Occupied Rooms</th>
                <th class="text-end">Available Rooms</th>
                <th class="text-end">Reserved Rooms</th>
                <th class="text-end">Occupancy Rate (%)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                <td class="text-end">{{ number_format($totalRooms) }}</td>
                <td class="text-end">{{ number_format($occupiedRooms) }}</td>
                <td class="text-end">{{ number_format($availableRooms) }}</td>
                <td class="text-end">{{ number_format($reservedRooms) }}</td>
                <td class="text-end"><strong>{{ number_format($occupancyRate, 2) }}%</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Daily Occupancy Report - Generated on {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
