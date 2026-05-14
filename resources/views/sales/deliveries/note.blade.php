<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Note - {{ $delivery->delivery_number }}</title>
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

        /* Payment methods */
        .payment-methods {
            font-size: 10px;
            margin: 8px 0;
        }

        .payment-method-bar {
            background-color: #1e3a8a;
            color: #fff;
            padding: 8px;
            font-weight: bold;
            margin-top: 10px;
        }

        .payment-details {
            padding: 8px;
            background-color: #f8fafc;
        }

        .payment-details strong {
            color: #1e40af;
        }

        /* Delivery title */
        .delivery-title {
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

        .deliver-to {
            width: 48%;
            font-size: 10px;
        }

        .deliver-to strong {
            color: #1e40af;
        }

        .delivery-box {
            width: 48%;
            text-align: right;
        }

        .delivery-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .delivery-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .delivery-box td:nth-child(even) {
            text-align: right;
        }

        .delivery-box strong {
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

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .signature-box {
            text-align: center;
            width: 30%;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 2px;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @php
            $company = $delivery->company ?? $company ?? null;
            @endphp
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
                <div class="company-name">{{ $company->name ?? 'SmartFinance' }}</div>
                <div class="company-details">
                    @if($company && $company->address)
                    P.O Box: {{ $company->address }} <br>
                    @endif
                    @if($company && $company->phone)
                    Phone: {{ $company->phone }} <br>
                    @endif
                    @if($company && $company->email)
                    Email: {{ $company->email }}
                    @endif
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        @if(isset($bankAccounts) && $bankAccounts && $bankAccounts->count() > 0)
        <div class="payment-method-bar" style="text-align: center;">
            <strong>PAYMENT METHOD :</strong>
        </div>
        <div class="payment-details">
            @foreach($bankAccounts as $account)
            <strong>{{ strtoupper($account->name ?? $account->bank_name ?? 'BANK') }}:</strong> {{ $account->account_number ?? 'N/A' }} &nbsp;&nbsp;
            @endforeach
        </div>
        @endif

        <div class="delivery-title">DELIVERY NOTE</div>
        <hr>
        {{-- Deliver To + Delivery Info --}}
        <div class="info-section">
            <div class="deliver-to" style="float: left; width: 48%;">
                <strong>Deliver to :</strong><br>
                <strong>{{ $delivery->customer->name ?? 'N/A' }}</strong><br>
                @if($delivery->customer && $delivery->customer->phone)
                {{ $delivery->customer->phone }}<br>
                @endif
                @if($delivery->customer && $delivery->customer->email)
                {{ $delivery->customer->email }}<br>
                @endif
                @if($delivery->delivery_address)
                {{ $delivery->delivery_address }}<br>
                @elseif($delivery->customer && $delivery->customer->address)
                {{ $delivery->customer->address }}<br>
                @endif
                @if($delivery->contact_person)
                <br><strong>Contact Person:</strong><br>
                {{ $delivery->contact_person }}<br>
                @endif
                @if($delivery->contact_phone)
                <strong>Phone:</strong> {{ $delivery->contact_phone }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $delivery->createdBy ?? null;
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

            <div class="delivery-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Delivery no:</strong></td>
                        <td>{{ $delivery->delivery_number }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $delivery->delivery_date ? \Carbon\Carbon::parse($delivery->delivery_date)->format('d F Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Currency:</strong></td>
                        <td>TZS</td>
                        <td><strong>Time:</strong></td>
                        <td>{{ $delivery->delivery_time ? \Carbon\Carbon::parse($delivery->delivery_time)->format('H:i:s') : ($delivery->created_at->format('H:i:s')) }}</td>
                    </tr>
                    @if($delivery->customer && $delivery->customer->tin_number)
                    <tr>
                        <td><strong>TIN:</strong></td>
                        <td>{{ $delivery->customer->tin_number }}</td>
                        <td><strong>VRN:</strong></td>
                        <td>{{ $delivery->customer->vat_number ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    @if($delivery->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $delivery->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td colspan="3">{{ ucfirst($delivery->status ?? 'Draft') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($delivery->delivery_instructions)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Delivery Instructions:</strong><br>
            {{ $delivery->delivery_instructions }}
        </div>
        @endif

        @if($delivery->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Notes:</strong><br>
            {{ $delivery->notes }}
        </div>
        @endif

        {{-- Items --}}
        @php
        $hasExpiryDates = $delivery->items->contains(function($item) {
        return $item->expiry_date !== null;
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
                @foreach($delivery->items as $item)
                <tr>
                    <td class="text-center">{{ number_format($item->quantity ?? $item->delivered_quantity ?? 0, 2) }}</td>
                    <td>{{ $item->item_name }}</td>
                    @if($hasExpiryDates)
                    <td class="text-center">
                        {{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('m/Y') : '' }}
                    </td>
                    @endif
                    <td class="text-right">{{ number_format($item->unit_price ?? 0, 2) }}</td>
                    <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->line_total ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        @php
        $subtotal = $delivery->items->sum('line_total') - $delivery->items->sum('vat_amount');
        $vatTotal = $delivery->items->sum('vat_amount');
        $transportCost = ($delivery->has_transport_cost && $delivery->transport_cost) ? $delivery->transport_cost : 0;
        $grandTotal = $delivery->items->sum('line_total') + $transportCost;
        // Count columns: Qty, Name, (Exp date if exists), Unit price, UOM, Amount
        $colspan = $hasExpiryDates ? 5 : 4;
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Sub Total: </td>
                <td>{{ number_format($subtotal, 2) }}</td>
            </tr>
            @if($vatTotal > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Tax: </td>
                <td>{{ number_format($vatTotal, 2) }}</td>
            </tr>
            @endif
            @if($transportCost > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Transport Cost:</td>
                <td>{{ number_format($transportCost, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($grandTotal, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($delivery, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($delivery->getAmountInWords()) }}</strong>
        </div>
        @endif

        @if($delivery->driver_name || $delivery->vehicle_number)
        <div style="margin-top:10px; font-size: 10px;">
            @if($delivery->driver_name)
            <strong>Driver:</strong> {{ $delivery->driver_name }}
            @if($delivery->driver_phone)
            | <strong>Phone:</strong> {{ $delivery->driver_phone }}
            @endif
            <br>
            @endif
            @if($delivery->vehicle_number)
            <strong>Vehicle:</strong> {{ $delivery->vehicle_number }}
            @endif
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your business!</div>

            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Driver Signature</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($delivery->driver_name)
                        {{ $delivery->driver_name }}
                        @else
                        ________________
                        @endif
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Customer Signature</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($delivery->received_by_name)
                        {{ $delivery->received_by_name }}
                        @if($delivery->received_at)
                        <br>{{ \Carbon\Carbon::parse($delivery->received_at)->format('d/m/Y H:i') }}
                        @endif
                        @else
                        ________________
                        @endif
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Received By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($delivery->received_by_name)
                        {{ $delivery->received_by_name }}
                        @else
                        <span style="color: #999;">Pending</span>
                        @endif
                    </div>
                </div>
            </div>

            <ol style="margin-top: 15px; padding-left: 20px;">
                <li>Please verify quantities and condition of goods upon receipt</li>
                <li>Report any discrepancies or damages immediately</li>
                <li>Goods received in good order and condition</li>
            </ol>

            <strong>{{ $delivery->customer->name ?? 'Customer' }}</strong>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Delivery Note No: {{ $delivery->delivery_number }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
