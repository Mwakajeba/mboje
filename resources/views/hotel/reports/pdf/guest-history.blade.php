<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest History Report</title>
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
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; }
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
                <h1>Guest History Report</h1>
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
        @if($dateFrom || $dateTo)
            <strong>Date Range:</strong> 
            @if($dateFrom) {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} @endif
            @if($dateFrom && $dateTo) to @endif
            @if($dateTo) {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }} @endif
        @else
            <strong>All Time</strong>
        @endif
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Guest Name</th>
                <th>Phone / Email</th>
                <th class="text-end">Visits Count</th>
                <th class="text-end">Total Spent</th>
                <th>Last Visit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($guests as $guestData)
                <tr>
                    <td>{{ $guestData['guest']->first_name }} {{ $guestData['guest']->last_name }}</td>
                    <td>
                        {{ $guestData['guest']->phone ?? 'N/A' }}<br>
                        <small style="color: #666;">{{ $guestData['guest']->email ?? 'N/A' }}</small>
                    </td>
                    <td class="text-end">{{ $guestData['visits_count'] }}</td>
                    <td class="text-end"><strong>{{ number_format($guestData['total_spent'], 2) }}</strong></td>
                    <td>{{ $guestData['last_visit'] ? \Carbon\Carbon::parse($guestData['last_visit'])->format('M d, Y') : 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No guest history found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Guest History Report - Generated on {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
