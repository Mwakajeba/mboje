<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Confirmation - {{ $booking->booking_number ?? 'BK' . $booking->id }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
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

        .text-left {
            text-align: left;
        }

        /* Header Section */
        .header-section {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .logo-section {
            width: 45%;
        }

        .company-logo {
            max-height: 80px;
            max-width: 150px;
            object-fit: contain;
        }

        .company-info {
            width: 50%;
            text-align: right;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        /* Payment Method Section */
        .payment-method-section {
            margin: 15px 0;
        }

        .payment-method-bar {
            background-color: #1e3a8a;
            color: #fff;
            padding: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .payment-details {
            padding: 10px;
            background-color: #f8fafc;
            font-size: 10px;
        }

        .payment-item {
            margin: 5px 0;
            padding: 5px 0;
        }

        /* Booking Details Section */
        .booking-details-section {
            margin: 20px 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            color: #1e40af;
            margin: 15px 0;
        }

        .guest-info {
            margin-bottom: 15px;
        }

        .guest-info-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .guest-details {
            font-size: 11px;
            line-height: 1.6;
        }

        .booking-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .booking-summary-table td {
            padding: 6px 0;
            font-size: 11px;
        }

        .booking-summary-table td:first-child {
            color: #1e40af;
            font-weight: bold;
            width: 40%;
        }

        .booking-summary-table td:last-child {
            text-align: right;
        }

        /* Room Details Table */
        .room-details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .room-details-table th {
            background-color: #1e3a8a;
            color: #fff;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }

        .room-details-table td {
            padding: 10px;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        /* Financial Summary */
        .financial-summary {
            margin: 20px 0;
        }

        .financial-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 11px;
        }

        .financial-item-label {
            color: #333;
        }

        .financial-item-value {
            font-weight: bold;
        }

        .financial-total {
            border-top: 2px solid #1e3a8a;
            padding-top: 10px;
            margin-top: 10px;
        }

        .balance-due {
            background-color: #e0e7ff;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .balance-due-label {
            background-color: #1e3a8a;
            color: #fff;
            padding: 8px;
            font-weight: bold;
            display: inline-block;
            margin-right: 10px;
        }

        .balance-due-amount {
            background-color: #bfdbfe;
            color: #1e3a8a;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
        }

        /* Footer */
        .footer {
            text-align: right;
            color: #1e40af;
            font-weight: bold;
            margin-top: 30px;
            font-size: 12px;
        }

        hr {
            border: none;
            border-top: 2px solid #1e40af;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header with Logo and Company Info --}}
        <div class="header-section">
            <div class="logo-section">
                @php
                    $company = $booking->company ?? null;
                    $logoBase64 = null;
                    if ($company && $company->logo) {
                        $logo = $company->logo;
                        $logoPath = public_path('storage/' . ltrim($logo, '/'));
                        if (file_exists($logoPath)) {
                            $imageData = file_get_contents($logoPath);
                            $imageInfo = getimagesize($logoPath);
                            if ($imageInfo !== false) {
                                $mimeType = $imageInfo['mime'];
                                $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                            }
                        }
                    }
                @endphp
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="{{ ($company->name ?? 'Company') . ' logo' }}" class="company-logo">
                @endif
            </div>
            <div class="company-info">
                <div class="company-name">{{ $company->name ?? 'SAFCO FINTECH LTD' }}</div>
                <div class="company-details">
                    @if($company && $company->address)
                        {{ $company->address }}<br>
                    @else
                        City Center, Dar es Salaam<br>
                    @endif
                    @if($company && $company->phone)
                        {{ $company->phone }}<br>
                    @else
                        255754111111<br>
                    @endif
                    @if($company && $company->email)
                        {{ $company->email }}
                    @else
                        main@safco.com
                    @endif
                </div>
            </div>
        </div>

        {{-- Payment Method Section --}}
        <div class="payment-method-section">
            <div class="payment-method-bar">
                PAYMENT METHOD:
            </div>
            <div class="payment-details">
                <div class="payment-item">
                    <strong>CASH REGISTER:</strong> CASH-001
                </div>
                @if(isset($bankAccounts) && $bankAccounts->count() > 0)
                    @foreach($bankAccounts as $account)
                        <div class="payment-item">
                            <strong>{{ strtoupper($account['bank_name'] ?? $account['name']) }}:</strong> {{ $account['account_number'] }}
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Booking Details Section --}}
        <div class="booking-details-section">
            <div class="section-title">BOOKING DETAILS</div>
            
            {{-- Guest Information --}}
            <div class="guest-info">
                <div class="guest-info-title">Guest Information:</div>
                <div class="guest-details">
                    <strong>Name:</strong> {{ $booking->guest->first_name ?? '' }} {{ $booking->guest->last_name ?? '' }}<br>
                    @if($booking->guest->email)
                        <strong>Email:</strong> {{ $booking->guest->email }}<br>
                    @endif
                    @if($booking->guest->phone)
                        <strong>Phone:</strong> {{ $booking->guest->phone }}<br>
                    @endif
                </div>
            </div>

            {{-- Booking Summary Table --}}
            <table class="booking-summary-table">
                <tr>
                    <td>Booking Number:</td>
                    <td>{{ $booking->booking_number ?? 'BK' . str_pad($booking->id, 8, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr>
                    <td>Booking Date:</td>
                    <td>{{ $booking->created_at->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</td>
                </tr>
                <tr>
                    <td>Payment Status:</td>
                    <td>{{ $booking->payment_status ?? 'Pending' }}</td>
                </tr>
            </table>
        </div>

        {{-- Room Details Table --}}
        <table class="room-details-table">
            <thead>
                <tr>
                    <th>Room Details</th>
                    <th>Check-in Date</th>
                    <th>Check-out Date</th>
                    <th>Nights</th>
                    <th>Guests</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @if($booking->room)
                            {{ $booking->room->room_number ?? 'Room ' . $booking->room_id }}
                            @if($booking->room->name)
                                - {{ $booking->room->name }}
                            @endif
                        @else
                            Room {{ $booking->room_id }}
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}</td>
                    <td>{{ $booking->nights ?? \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out)) }}</td>
                    <td>
                        {{ $booking->adults }} {{ $booking->adults == 1 ? 'Adult' : 'Adults' }}
                        @if($booking->children > 0)
                            , {{ $booking->children }} {{ $booking->children == 1 ? 'Child' : 'Children' }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Financial Summary --}}
        <div class="financial-summary">
            @php
                $nights = $booking->nights ?? \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out));
                $ratePerNight = $booking->room_rate ?? (($booking->total_amount ?? 0) / max($nights, 1));
                $totalAmount = $booking->total_amount ?? 0;
                $paidAmount = $booking->paid_amount ?? 0;
                $balanceDue = $booking->balance_due ?? ($totalAmount - $paidAmount);
            @endphp

            <div class="financial-item">
                <span class="financial-item-label">Rate per Night:</span>
                <span class="financial-item-value">TSh {{ number_format($ratePerNight, 2) }}</span>
            </div>
            <div class="financial-item">
                <span class="financial-item-label">Total Nights:</span>
                <span class="financial-item-value">{{ $nights }}</span>
            </div>
            <div class="financial-item financial-total">
                <span class="financial-item-label">Total Amount:</span>
                <span class="financial-item-value">TSh {{ number_format($totalAmount, 2) }}</span>
            </div>
            <div class="financial-item">
                <span class="financial-item-label">Paid Amount:</span>
                <span class="financial-item-value">TSh {{ number_format($paidAmount, 2) }}</span>
            </div>
            <div class="balance-due">
                <span class="balance-due-label">Balance Due:</span>
                <span class="balance-due-amount">TSh {{ number_format($balanceDue, 2) }}</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            Thank you for your booking!
        </div>
    </div>
</body>
</html>
