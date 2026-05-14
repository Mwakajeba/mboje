<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Booking vs Collection Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .container {
            width: 100%;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        hr {
            border: none;
            border-top: 2px solid #3b82f6;
            margin: 8px 0;
        }

        /* Header */
        .logo-section {
            margin-bottom: 10px;
        }

        .company-logo {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }

        .company-details {
            font-size: 10px;
        }

        /* Report title */
        .booking-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #1e40af;
        }

        .report-info {
            margin-bottom: 10px;
            font-size: 10px;
        }

        .report-info strong {
            color: #1e40af;
        }

        /* Details table - same as booking export */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }

        .details-table th,
        .details-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }

        .details-table th {
            text-align: left;
            font-weight: bold;
            background-color: #1e3a8a;
            color: #fff;
        }

        .details-table tbody tr:nth-child(even) {
            background-color: #dbeafe;
        }

        .details-table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        .details-table .text-right {
            text-align: right;
        }

        .details-table .text-center {
            text-align: center;
        }

        .details-table tfoot th {
            background-color: #1e3a8a;
            color: #fff;
        }

        .details-table tfoot th.text-right {
            text-align: right;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            font-size: 10px;
        }

        .footer strong {
            color: #1e40af;
        }

        .footer hr {
            border-top: 1px solid #dbeafe;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">

        {{-- Header - same as booking export --}}
        <div class="text-left">
            @if($company && $company->logo)
            @php
                $logo = $company->logo;
                $logoPath = public_path('storage/' . ltrim($logo, '/'));
                $logoBase64 = null;
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageInfo = @getimagesize($logoPath);
                    if ($imageInfo !== false) {
                        $mimeType = $imageInfo['mime'];
                        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                    }
                }
            @endphp
            @if($logoBase64 ?? null)
            <div class="logo-section" style="float: left; width: 45%;">
                <img src="{{ $logoBase64 }}" alt="{{ $company->name }} logo" class="company-logo">
            </div>
            @endif
            @endif
            <div style="float: right; width: 50%; text-align: left; margin-left: 15%;">
                <div class="company-name">{{ $company->name ?? 'Hotel' }}</div>
                <div class="company-details">
                    @if($branch)
                    Address: {{ $branch->address ?? $company->address ?? 'N/A' }} <br>
                    Phone: {{ $branch->phone ?? $company->phone ?? 'N/A' }} <br>
                    Email: {{ $branch->email ?? $company->email ?? 'N/A' }}
                    @else
                    Address: {{ $company->address ?? 'N/A' }} <br>
                    Phone: {{ $company->phone ?? 'N/A' }} <br>
                    Email: {{ $company->email ?? 'N/A' }}
                    @endif
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        <hr>

        <div class="booking-title">BOOKINGS VS COLLECTIONS REPORT</div>

        <div class="report-info">
            <strong>FROM</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
            <strong> TO</strong> {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
            &nbsp;|&nbsp;
            <strong>Amount Expected in Range:</strong> TSh {{ number_format($totalExpectedInRange ?? 0, 2) }}
            &nbsp;|&nbsp;
            <strong>Total Amount Paid:</strong> TSh {{ number_format($totalPaid, 2) }}
            &nbsp;|&nbsp;
            <strong>Total Due Amount:</strong> TSh {{ number_format($totalDue, 2) }}
        </div>

        <table class="details-table">
            <thead>
                <tr>
                    <th>CUSTOMER</th>
                    <th>PROPERTY</th>
                    <th class="text-right">PRICE PER DAY</th>
                    <th class="text-center">BOOKED DAYS</th>
                    <th class="text-center">DAYS SELECTED</th>
                    <th class="text-right">AMOUNT EXPECTED IN RANGE</th>
                    <th class="text-right">AMOUNT PAID</th>
                    <th class="text-right">DUE AMOUNT</th>
                    <th>CHECK-IN</th>
                    <th>CHECK-OUT</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportRows as $row)
                <tr>
                    <td>{{ $row['customer'] }}</td>
                    <td>{{ $row['property'] }}</td>
                    <td class="text-right">{{ number_format($row['price_per_day'], 2) }}</td>
                    <td class="text-center">{{ $row['booked_days'] }}</td>
                    <td class="text-center">{{ $row['days_selected'] }}</td>
                    <td class="text-right">{{ number_format($row['amount_expected_in_range'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($row['amount_paid'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['due_amount'], 2) }}</td>
                    <td>{{ $row['check_in'] }}</td>
                    <td>{{ $row['check_out'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center">No bookings overlap the selected date range.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-right">TOTAL:</th>
                    <th class="text-right">{{ number_format($totalExpectedInRange ?? 0, 2) }}</th>
                    <th class="text-right">{{ number_format($totalPaid, 2) }}</th>
                    <th class="text-right">{{ number_format($totalDue, 2) }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <hr>
            <div style="text-align: center;">
                Generated on {{ $generatedAt->format('F d, Y \a\t g:i A') }}
            </div>
        </div>
    </div>
</body>
</html>
