<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proforma Invoice - {{ $proforma->proforma_number }}</title>
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

        /* Proforma title */
        .proforma-title {
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

        .bill-to {
            width: 48%;
            font-size: 10px;
        }

        .notes {
            margin-bottom: 10px;
            font-size: 10px;
        }

        .bill-to strong {
            color: #1e40af;
        }

        .proforma-box {
            width: 48%;
            text-align: right;
        }

        .proforma-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .proforma-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .proforma-box td:nth-child(even) {
            text-align: right;
        }

        .proforma-box strong {
            color: #1e40af;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }

        .items-table th {
            text-align: center;
            font-weight: bold;
            background-color: #1e3a8a;
            color: #fff;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #dbeafe;
        }

        .items-table tbody tr:nth-child(odd) {
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

        .valid-until {
            margin-top: 10px;
            padding: 8px;
            background-color: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 3px;
            font-size: 10px;
            text-align: center;
        }

        .valid-until strong {
            color: #92400e;
        }

    </style>

</head>
<body>
    <div class="container">
        @php
            $currencyCode = $proforma->currency ?? 'TZS';
        @endphp

        {{-- Header --}}
        <div class="text-left">
            @if($company && $company->logo)
            @php
            // Logo is stored in storage/app/public (via "public" disk)
            $logo = $company->logo; // e.g. "uploads/companies/company_1_1768466462.png"
            $logoPath = public_path('storage/' . ltrim($logo, '/'));

            // Convert image to base64 for DomPDF compatibility
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
                <img src="{{ $logoBase64 }}" alt="{{ $company->name . ' logo' }}" class="company-logo">
            </div>
            @endif
            @endif
            <div style="float: right; width: 50%; text-align: left; margin-left: 15%;">
                <div class="company-name">{{ $company->name }}</div>
                <div class="company-details">
                    P.O Box: {{ $company->address }} <br>
                    Phone: {{ $company->phone }} <br>
                    Email: {{ $company->email }}
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        <div class="proforma-title">PROFORMA INVOICE</div>
        <hr>
        {{-- Bill To + Proforma Info --}}
        <div class="info-section">
            <div class="bill-to" style="float: left; width: 48%;">
                <strong>Proforma to :</strong><br>
                <strong>{{ $proforma->customer->name }}</strong><br>
                @if($proforma->customer->phone)
                {{ $proforma->customer->phone }}<br>
                @endif
                @if($proforma->customer->email)
                {{ $proforma->customer->email }}<br>
                @endif
                @if($proforma->customer->address)
                {{ $proforma->customer->address }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $proforma->createdBy ?? null;
                $creatorRole = $creator && method_exists($creator, 'roles') ? $creator->roles->first() : null;
                @endphp
                @if($creator)
                {{ $creator->name }}
                @if($creatorRole)
                ({{ $creatorRole->name }})
                @endif
                @else
                System
                @endif
            </div>

            <div class="proforma-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Proforma no:</strong></td>
                        <td>{{ $proforma->proforma_number }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $proforma->proforma_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Currency:</strong></td>
                        <td>{{ $currencyCode }}</td>
                        <td><strong>Valid Until:</strong></td>
                        <td>{{ $proforma->valid_until->format('d F Y') }}</td>
                    </tr>
                    @if($branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $branch->name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $proforma->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($proforma->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Description:</strong><br>
            {{ $proforma->notes }}
        </div>
        @endif

        {{-- Items --}}
        @php
        $hasExpiryDates = $proforma->items->contains(function($item) {
        return isset($item->expiry_date) && $item->expiry_date !== null;
        });
        @endphp
        <table class="items-table">
            <thead>
                <tr>
                    <th>Qty</th>
                    <th>Name</th>
                    @if($hasExpiryDates)
                    <th>Exp date</th>
                    @endif
                    <th>Unit price</th>
                    <th>UOM</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proforma->items as $item)
                <tr>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td>{{ $item->item_name }}</td>
                    @if($hasExpiryDates)
                    <td class="text-center">
                        {{ isset($item->expiry_date) && $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('m/Y') : '' }}
                    </td>
                    @endif
                    <td class="text-right">{{ $currencyCode }} {{ number_format($item->unit_price,2) }}</td>
                    <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
                    <td class="text-right">{{ $currencyCode }} {{ number_format($item->line_total,2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        @php
        $vatRate = 0;
        if ($proforma->vat_amount > 0) {
        if (isset($proforma->vat_rate) && $proforma->vat_rate > 0) {
        $vatRate = $proforma->vat_rate;
        } elseif ($proforma->subtotal > 0) {
        $vatRate = ($proforma->vat_amount / $proforma->subtotal) * 100;
        }
        }
        // Count columns: Qty, Name, (Exp date if exists), Unit price, UOM, Amount
        $colspan = $hasExpiryDates ? 5 : 4;
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Sub Total: </td>
                <td>{{ $currencyCode }} {{ number_format($proforma->subtotal,2) }}</td>
            </tr>
            @if($proforma->vat_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Tax {{ number_format($vatRate, 1) }}%: </td>
                <td>{{ $currencyCode }} {{ number_format($proforma->vat_amount,2) }}</td>
            </tr>
            @endif
            @if($proforma->tax_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Additional Tax:</td>
                <td>{{ $currencyCode }} {{ number_format($proforma->tax_amount,2) }}</td>
            </tr>
            @endif
            @if($proforma->discount_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Total Discount:</td>
                <td>-{{ $currencyCode }} {{ number_format($proforma->discount_amount ?? 0,2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ $currencyCode }} {{ number_format($proforma->total_amount,2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($proforma, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($proforma->getAmountInWords()) }}</strong>
        </div>
        @endif

        <div class="valid-until">
            <strong>Valid Until: {{ $proforma->valid_until->format('d F Y') }}</strong>
        </div>

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your interest in our products!</div>
            @if($proforma->terms_conditions)
            <div style="margin-bottom: 10px;"><strong>Terms and Conditions:</strong><br>{{ $proforma->terms_conditions }}</div>
            @endif

            <div class="signature">
                <strong>Signature:</strong> ________________________________
            </div>

            <ol>
                <li>This is a proforma invoice and does not constitute a final invoice</li>
                <li>Prices and availability are subject to change</li>
                <li>Valid until {{ $proforma->valid_until->format('d F Y') }}</li>
            </ol>

            <strong>{{ $proforma->customer->name }}</strong>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Proforma No: {{ $proforma->proforma_number }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
