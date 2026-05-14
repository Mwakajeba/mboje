<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #ffc107; padding-bottom: 15px; }
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
        .header h1 { color: #ffc107; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .report-info { margin-bottom: 15px; font-size: 11px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; }
        table.data-table th { background-color: #ffc107; color: #333; font-weight: bold; text-align: left; }
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
                <h1>Booking Report</h1>
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
                <th>Booking ID</th>
                <th>Guest Name</th>
                <th>Room Type</th>
                <th>Check-in Date</th>
                <th>Check-out Date</th>
                <th class="text-end">Nights</th>
                <th>Booking Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td>{{ $booking->booking_number }}</td>
                    <td>{{ $booking->guest->first_name }} {{ $booking->guest->last_name }}</td>
                    <td>{{ $booking->room->room_type ?? 'N/A' }}</td>
                    <td>{{ $booking->check_in->format('M d, Y') }}</td>
                    <td>{{ $booking->check_out->format('M d, Y') }}</td>
                    <td class="text-end">{{ $booking->nights }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No bookings found for the selected date range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Booking Report - Generated on {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
