<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Sales / Revenue Report</title>
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

        /* Header - same as booking export */
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

        <div class="booking-title">DAILY SALES / REVENUE REPORT</div>

        <div class="report-info">
            <strong>Date:</strong> {{ \Carbon\Carbon::parse($date)->format('F d, Y') }} (bookings created on this day)
            &nbsp;|&nbsp;
            <strong>Amount Created:</strong> TSh {{ number_format($totalAmountCreated ?? 0, 2) }}
            &nbsp;|&nbsp;
            <strong>Amount Paid:</strong> TSh {{ number_format($totalAmountPaid ?? 0, 2) }}
            &nbsp;|&nbsp;
            <strong>Amount Due:</strong> TSh {{ number_format($totalAmountDue ?? 0, 2) }}
        </div>

        <table class="details-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Booking No</th>
                    <th>Guest Name</th>
                    <th>Room No</th>
                    <th>Payment Method</th>
                    <th class="text-right">Amount Created</th>
                    <th class="text-right">Amount Paid</th>
                    <th class="text-right">Amount Due</th>
                    <th>Received By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesData as $sale)
                <tr>
                    <td>{{ $sale['booking']->created_at->format('M d, Y') }}</td>
                    <td>{{ $sale['booking']->booking_number ?? 'N/A' }}</td>
                    <td>{{ $sale['guest_name'] }}</td>
                    <td>{{ $sale['room_no'] }}</td>
                    <td>{{ $sale['payment_method'] }}</td>
                    <td class="text-right">{{ number_format($sale['amount_created'], 2) }}</td>
                    <td class="text-right">{{ number_format($sale['amount_paid'], 2) }}</td>
                    <td class="text-right">{{ number_format($sale['amount_due'], 2) }}</td>
                    <td>{{ $sale['received_by'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No bookings created on the selected date.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-right">TOTAL:</th>
                    <th class="text-right">{{ number_format($totalAmountCreated ?? 0, 2) }}</th>
                    <th class="text-right">{{ number_format($totalAmountPaid ?? 0, 2) }}</th>
                    <th class="text-right">{{ number_format($totalAmountDue ?? 0, 2) }}</th>
                    <th></th>
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
