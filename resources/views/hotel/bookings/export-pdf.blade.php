<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking - {{ $booking->booking_number }}</title>
    <style>
        @page {
            size: A4;
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

        /* Booking title */
        .booking-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #1e40af;
        }

        /* Info section */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .guest-info {
            width: 48%;
            font-size: 10px;
        }

        .guest-info strong {
            color: #1e40af;
        }

        .booking-box {
            width: 48%;
            text-align: right;
        }

        .booking-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .booking-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .booking-box td:nth-child(even) {
            text-align: right;
        }

        .booking-box strong {
            color: #1e40af;
        }

        /* Details table */
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

        /* Totals */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 4px 5px;
            border: none;
        }

        .totals-table td:last-child {
            text-align: right;
            padding-right: 5px;
        }

        .totals-table tr:last-child td {
            background-color: #1e3a8a;
            color: #fff;
            font-weight: bold;
            padding: 8px 5px;
        }

        .totals-table tr:last-child td:last-child {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 8px;
            border-radius: 3px;
        }

        /* Receipt History */
        .receipt-history {
            margin-top: 15px;
            font-size: 10px;
        }

        .receipt-history-title {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .receipt-history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 5px;
        }

        .receipt-history-table th {
            background-color: #1e3a8a;
            color: #fff;
            padding: 5px;
            text-align: left;
            border: 1px solid #cbd5e1;
            font-weight: bold;
        }

        .receipt-history-table td {
            padding: 5px;
            border: 1px solid #cbd5e1;
        }

        .receipt-history-table tbody tr:nth-child(even) {
            background-color: #dbeafe;
        }

        .receipt-history-table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        .receipt-history-table .text-right {
            text-align: right;
        }

        .receipt-history-table .text-center {
            text-align: center;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            font-size: 10px;
        }

        .footer strong {
            color: #1e40af;
        }

        .signature {
            margin-top: 20px;
        }

        .footer hr {
            border-top: 1px solid #dbeafe;
            margin: 15px 0;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @if($booking->company && $booking->company->logo)
            @php
            $logo = $booking->company->logo;
            $logoPath = public_path('storage/' . ltrim($logo, '/'));

            $logoBase64 = null;
            if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $imageInfo = getimagesize($logoPath);
            if ($imageInfo !== false) {
            $mimeType = $imageInfo['mime'];
            $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
            }
            }
            @endphp
            @if($logoBase64)
            <div class="logo-section" style="float: left; width: 45%;">
                <img src="{{ $logoBase64 }}" alt="{{ $booking->company->name . ' logo' }}" class="company-logo">
            </div>
            @endif
            @endif
            <div style="float: right; width: 50%; text-align: left; margin-left: 15%;">
                <div class="company-name">{{ $booking->company->name ?? 'Hotel' }}</div>
                <div class="company-details">
                    @if($booking->branch)
                    Address: {{ $booking->branch->address ?? $booking->company->address ?? 'N/A' }} <br>
                    Phone: {{ $booking->branch->phone ?? $booking->company->phone ?? 'N/A' }} <br>
                    Email: {{ $booking->branch->email ?? $booking->company->email ?? 'N/A' }}
                    @else
                    Address: {{ $booking->company->address ?? 'N/A' }} <br>
                    Phone: {{ $booking->company->phone ?? 'N/A' }} <br>
                    Email: {{ $booking->company->email ?? 'N/A' }}
                    @endif
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        @if($bankAccounts && $bankAccounts->count() > 0)
        <div class="payment-method-bar" style="text-align: center; background-color: #1e3a8a; color: #fff; padding: 8px; font-weight: bold; margin-top: 10px;">
            <strong>PAYMENT METHOD :</strong>
        </div>
        <div class="payment-details" style="padding: 8px; background-color: #f8fafc; font-size: 10px; margin-bottom: 10px;">
            @foreach($bankAccounts as $account)
            <strong style="color: #1e40af;">{{ strtoupper($account->name ?? $account->bank_name ?? 'BANK') }}:</strong> {{ $account->account_number ?? 'N/A' }} &nbsp;&nbsp;
            @endforeach
        </div>
        @endif

        <hr>

        <div class="booking-title">BOOKING DETAILS</div>

        {{-- Booking Info --}}
        <div class="info-section">
            <div class="guest-info">
                <strong>Guest Information:</strong><br>
                {{ $booking->guest->first_name ?? '' }} {{ $booking->guest->last_name ?? '' }}<br>
                @if($booking->guest->email)
                Email: {{ $booking->guest->email }}<br>
                @endif
                @if($booking->guest->phone)
                Phone: {{ $booking->guest->phone }}<br>
                @endif
            </div>
            <div class="booking-box">
                <table>
                    <tr>
                        <td><strong>Booking Number:</strong></td>
                        <td>{{ $booking->booking_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Booking Date:</strong></td>
                        <td>{{ $booking->created_at->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Payment Status:</strong></td>
                        <td>{{ ucfirst($booking->payment_status ?? 'pending') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Room & Dates --}}
        <table class="details-table">
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
                        {{ $booking->room->room_number ?? 'N/A' }}
                        @if($booking->room->room_name)
                        - {{ $booking->room->room_name }}
                        @endif
                        <br>
                        <small>{{ ucfirst($booking->room->room_type ?? 'N/A') }}</small>
                    </td>
                    <td>{{ $booking->check_in->format('M d, Y') }}</td>
                    <td>{{ $booking->check_out->format('M d, Y') }}</td>
                    <td>{{ $booking->nights ?? 0 }}</td>
                    <td>
                        {{ $booking->adults ?? 0 }} Adults
                        @if($booking->children > 0)
                        <br>{{ $booking->children }} Children
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Financial Summary --}}
        <table class="totals-table">
            <tr>
                <td>Rate per Night:</td>
                <td>TSh {{ number_format($booking->room_rate ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Total Nights:</td>
                <td>{{ $booking->nights ?? 0 }}</td>
            </tr>
            @if($booking->discount_amount > 0)
            <tr>
                <td>Discount:</td>
                <td>- TSh {{ number_format($booking->discount_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>Total Amount:</strong></td>
                <td><strong>TSh {{ number_format($booking->total_amount ?? 0, 2) }}</strong></td>
            </tr>
            <tr>
                <td>Paid Amount:</td>
                <td>TSh {{ number_format($booking->paid_amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Balance Due:</strong></td>
                <td><strong>TSh {{ number_format($booking->balance_due ?? 0, 2) }}</strong></td>
            </tr>
        </table>

        {{-- Receipt History --}}
        @if($booking->receipts && $booking->receipts->count() > 0)
        <div class="receipt-history">
            <div class="receipt-history-title">Payment History</div>
            <table class="receipt-history-table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($booking->receipts as $receipt)
                    <tr>
                        <td>{{ $receipt->reference_number }}</td>
                        <td>{{ $receipt->date->format('M d, Y') }}</td>
                        <td class="text-right">TSh {{ number_format($receipt->amount, 2) }}</td>
                        <td>{{ $receipt->bankAccount ? $receipt->bankAccount->name : 'Cash' }}</td>
                        <td class="text-center">
                            @if($receipt->approved)
                            <span style="color: green;">Approved</span>
                            @else
                            <span style="color: orange;">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <hr>
            @if($booking->special_requests)
            <div style="margin-top: 10px;">
                <strong>Special Requests:</strong><br>
                {{ $booking->special_requests }}
            </div>
            @endif
            @if($booking->notes)
            <div style="margin-top: 10px;">
                <strong>Notes:</strong><br>
                {{ $booking->notes }}
            </div>
            @endif
            @if(!empty($termsAndConditions))
            <div class="terms-and-conditions" style="margin-top: 15px; font-size: 9px; color: #374151;">
                <strong style="color: #1e40af;">Terms and Conditions</strong><br>
                {!! nl2br(e($termsAndConditions)) !!}
            </div>
            @endif
            <div style="margin-top: 15px; text-align: center;">
                <strong>Thank you for your booking!</strong>
            </div>
        </div>

    </div>
</body>
</html>
