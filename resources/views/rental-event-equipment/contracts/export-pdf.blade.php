<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rental Contract - {{ $contract->contract_number }}</title>
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
            border-top: 2px solid #198754;
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
            color: #198754;
        }

        .company-details {
            font-size: 10px;
        }

        /* Contract title */
        .contract-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #198754;
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
            color: #198754;
        }

        .contract-box {
            width: 48%;
            text-align: right;
        }

        .contract-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .contract-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .contract-box td:nth-child(even) {
            text-align: right;
        }

        .contract-box strong {
            color: #198754;
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
            background-color: #198754;
            color: #fff;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #d1e7dd;
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
            background-color: #198754;
            color: #fff;
            font-weight: bold;
            padding: 8px 5px;
        }

        .totals-table tr:last-child td:last-child {
            background-color: #d1e7dd;
            color: #198754;
            padding: 8px;
            border-radius: 3px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            font-size: 10px;
        }

        .footer strong {
            color: #198754;
        }

        .signature {
            margin-top: 20px;
        }

        .footer hr {
            border-top: 1px solid #d1e7dd;
            margin: 15px 0;
        }

        .status-badge {
            margin-top: 10px;
            padding: 8px;
            background-color: #d1e7dd;
            border: 1px solid #198754;
            border-radius: 3px;
            font-size: 10px;
            text-align: center;
        }

        .status-badge strong {
            color: #198754;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @if($company && $company->logo)
            @php
            // Logo is stored in storage/app/public (via "public" disk)
            $logo = $company->logo;
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
                    @if($company->address)
                    P.O Box: {{ $company->address }} <br>
                    @endif
                    @if($company->phone)
                    Phone: {{ $company->phone }} <br>
                    @endif
                    @if($company->email)
                    Email: {{ $company->email }}
                    @endif
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        <div class="contract-title">RENTAL CONTRACT</div>
        <hr>
        {{-- Bill To + Contract Info --}}
        <div class="info-section">
            <div class="bill-to" style="float: left; width: 48%;">
                <strong>Contract to :</strong><br>
                <strong>{{ $contract->customer->name }}</strong><br>
                @if($contract->customer->phone)
                {{ $contract->customer->phone }}<br>
                @endif
                @if($contract->customer->email)
                {{ $contract->customer->email }}<br>
                @endif
                @if($contract->customer->company_address)
                {{ $contract->customer->company_address }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $contract->creator ?? null;
                @endphp
                @if($creator)
                {{ $creator->name }}
                @else
                System
                @endif
            </div>

            <div class="contract-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Contract no:</strong></td>
                        <td>{{ $contract->contract_number }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $contract->contract_date->format('d F Y') }}</td>
                    </tr>
                    @if($contract->quotation)
                    <tr>
                        <td><strong>Quotation no:</strong></td>
                        <td colspan="3">{{ $contract->quotation->quotation_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Currency:</strong></td>
                        <td>TZS</td>
                        <td><strong>Status:</strong></td>
                        <td>{{ ucfirst($contract->status) }}</td>
                    </tr>
                    @if($contract->event_date)
                    <tr>
                        <td><strong>Event Date:</strong></td>
                        <td colspan="3">{{ $contract->event_date->format('d F Y') }}</td>
                    </tr>
                    @endif
                    @if($contract->rental_start_date)
                    <tr>
                        <td><strong>Rental Start:</strong></td>
                        <td>{{ $contract->rental_start_date->format('d F Y') }}</td>
                        <td><strong>Rental End:</strong></td>
                        <td>{{ $contract->rental_end_date ? $contract->rental_end_date->format('d F Y') : 'N/A' }}</td>
                    </tr>
                    @endif
                    @if($contract->event_location)
                    <tr>
                        <td><strong>Event Location:</strong></td>
                        <td colspan="3">{{ $contract->event_location }}</td>
                    </tr>
                    @endif
                    @if($branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $branch->name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $contract->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($contract->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Description:</strong><br>
            {{ $contract->notes }}
        </div>
        @endif

        {{-- Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>Qty</th>
                    <th>Equipment</th>
                    <th>Category</th>
                    <th>Rate/Day</th>
                    <th>Days</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contract->items as $item)
                <tr>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td>{{ $item->equipment->name ?? 'N/A' }}</td>
                    <td>{{ $item->equipment->category->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($item->rental_rate, 2) }}</td>
                    <td class="text-center">{{ $item->rental_days }}</td>
                    <td class="text-right">{{ number_format($item->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <table class="totals-table">
            <tr>
                <td colspan="5" style="text-align: right;">Sub Total: </td>
                <td>{{ number_format($contract->subtotal, 2) }}</td>
            </tr>
            @if($contract->discount_amount > 0)
            <tr>
                <td colspan="5" style="text-align: right;">Discount: </td>
                <td>{{ number_format($contract->discount_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="5" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($contract->total_amount, 2) }}</strong></td>
            </tr>
        </table>

        <div class="status-badge">
            <strong>Status: {{ ucfirst($contract->status) }} - Equipment Reserved</strong>
        </div>

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">This is a legally binding rental contract. Equipment has been reserved for the customer.</div>
            @if($contract->terms_conditions)
            <div style="margin-bottom: 10px;"><strong>Terms and Conditions:</strong><br>{{ $contract->terms_conditions }}</div>
            @endif

            <div class="signature">
                <strong>Customer Signature:</strong> ________________________________<br>
                <strong>Company Signature:</strong> ________________________________
            </div>

            <ol>
                <li>This contract confirms the reservation of equipment as listed above</li>
                <li>Equipment status has been changed from Available to Reserved</li>
                <li>Customer is responsible for equipment during rental period</li>
                <li>Any damages or losses will be charged to the customer</li>
                <li>Contract date: {{ $contract->contract_date->format('d F Y') }}</li>
            </ol>

            <strong>{{ $contract->customer->name }}</strong>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Contract No: {{ $contract->contract_number }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
