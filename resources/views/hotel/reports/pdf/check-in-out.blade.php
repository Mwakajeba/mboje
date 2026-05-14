<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check-In & Check-Out Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #dc3545; padding-bottom: 15px; }
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
        .header h1 { color: #dc3545; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .report-info { margin-bottom: 15px; font-size: 11px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; }
        table.data-table th { background-color: #dc3545; color: white; font-weight: bold; text-align: left; }
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
                <h1>Check-In & Check-Out Report</h1>
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
        <strong>Date Range:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Guest Name</th>
                <th>Room No</th>
                <th>Check-in Time</th>
                <th>Check-out Time</th>
                <th>Stay Duration</th>
                <th>Processed By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $movement)
                <tr>
                    <td>{{ $movement['guest_name'] }}</td>
                    <td>{{ $movement['room_no'] }}</td>
                    <td>
                        @if($movement['type'] == 'Check-In')
                            {{ \Carbon\Carbon::parse($movement['time'])->format('M d, Y H:i') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($movement['type'] == 'Check-Out')
                            {{ \Carbon\Carbon::parse($movement['time'])->format('M d, Y H:i') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $movement['stay_duration'] }}</td>
                    <td>{{ $movement['processed_by'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No check-in/check-out data found for the selected date range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Check-In & Check-Out Report - Generated on {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
