<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Credit Note - {{ $creditNote->credit_note_number }}</title>
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

        /* Credit Note title */
        .credit-note-title {
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

        .credit-to {
            width: 48%;
            font-size: 10px;
        }

        .credit-to strong {
            color: #1e40af;
        }

        .credit-box {
            width: 48%;
            text-align: right;
        }

        .credit-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .credit-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .credit-box td:nth-child(even) {
            text-align: right;
        }

        .credit-box strong {
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

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft {
            background-color: #9ca3af;
            color: #fff;
        }

        .status-issued {
            background-color: #fbbf24;
            color: #000;
        }

        .status-applied {
            background-color: #10b981;
            color: #fff;
        }

        .status-cancelled {
            background-color: #ef4444;
            color: #fff;
        }

        /* Credit application info */
        .credit-application {
            margin-top: 10px;
            padding: 8px;
            background-color: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 3px;
            font-size: 10px;
        }

        .credit-application strong {
            color: #92400e;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @php
            $company = $creditNote->company ?? $company ?? null;
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

        <div class="credit-note-title">CREDIT NOTE</div>
        <hr>
        {{-- Credit To + Credit Note Info --}}
        <div class="info-section">
            <div class="credit-to" style="float: left; width: 48%;">
                <strong>Credit to :</strong><br>
                <strong>{{ $creditNote->customer->name ?? 'N/A' }}</strong><br>
                @if($creditNote->customer && $creditNote->customer->phone)
                {{ $creditNote->customer->phone }}<br>
                @endif
                @if($creditNote->customer && $creditNote->customer->email)
                {{ $creditNote->customer->email }}<br>
                @endif
                @if($creditNote->customer && $creditNote->customer->address)
                {{ $creditNote->customer->address }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $creditNote->createdBy ?? null;
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

            <div class="credit-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Credit Note no:</strong></td>
                        <td>{{ $creditNote->credit_note_number }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $creditNote->credit_note_date->format('d F Y') }}</td>
                    </tr>
                    @if($creditNote->salesInvoice)
                    <tr>
                        <td><strong>Invoice no:</strong></td>
                        <td colspan="3">{{ $creditNote->salesInvoice->invoice_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Currency:</strong></td>
                        <td>{{ $creditNote->currency ?? 'TZS' }}</td>
                        <td><strong>Ex Rate:</strong></td>
                        <td>{{ number_format($creditNote->exchange_rate ?? 1, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Type:</strong></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $creditNote->type ?? 'N/A')) }}</td>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="status-badge status-{{ $creditNote->status ?? 'draft' }}">
                                {{ strtoupper($creditNote->status ?? 'DRAFT') }}
                            </span>
                        </td>
                    </tr>
                    @if($creditNote->customer && $creditNote->customer->tin_number)
                    <tr>
                        <td><strong>TIN:</strong></td>
                        <td>{{ $creditNote->customer->tin_number }}</td>
                        <td><strong>VRN:</strong></td>
                        <td>{{ $creditNote->customer->vat_number ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    @if($creditNote->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $creditNote->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $creditNote->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($creditNote->reason)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Reason:</strong><br>
            {{ $creditNote->reason }}
        </div>
        @endif

        @if($creditNote->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Notes:</strong><br>
            {{ $creditNote->notes }}
        </div>
        @endif

        {{-- Items --}}
        @php
        $hasExpiryDates = $creditNote->items->contains(function($item) {
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
                @foreach($creditNote->items as $item)
                <tr>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ $item->item_name }}</td>
                    @if($hasExpiryDates)
                    <td class="text-center">
                        {{ isset($item->expiry_date) && $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('m/Y') : '' }}
                    </td>
                    @endif
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">{{ $item->unit_of_measure ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        @php
        $vatRate = 0;
        if ($creditNote->vat_amount > 0) {
        if (isset($creditNote->vat_rate) && $creditNote->vat_rate > 0) {
        $vatRate = $creditNote->vat_rate;
        } elseif ($creditNote->subtotal > 0) {
        $vatRate = ($creditNote->vat_amount / $creditNote->subtotal) * 100;
        }
        }
        // Count columns: Qty, Name, (Exp date if exists), Unit price, UOM, Amount
        $colspan = $hasExpiryDates ? 5 : 4;
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Sub Total: </td>
                <td>{{ number_format($creditNote->subtotal, 2) }}</td>
            </tr>
            @if($creditNote->vat_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Tax {{ number_format($vatRate, 1) }}%: </td>
                <td>{{ number_format($creditNote->vat_amount, 2) }}</td>
            </tr>
            @endif
            @if($creditNote->discount_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Total Discount:</td>
                <td>{{ number_format($creditNote->discount_amount ?? 0, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($creditNote->total_amount, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($creditNote, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($creditNote->getAmountInWords()) }}</strong>
        </div>
        @endif

        @if($creditNote->applied_amount > 0 || $creditNote->remaining_amount > 0)
        <div class="credit-application">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span><strong>Applied Amount:</strong></span>
                <span><strong>{{ number_format($creditNote->applied_amount, 2) }}</strong></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span><strong>Remaining Amount:</strong></span>
                <span><strong>{{ number_format($creditNote->remaining_amount, 2) }}</strong></span>
            </div>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your business!</div>
            @if($creditNote->terms_conditions)
            <div style="margin-bottom: 10px;"><strong>Terms and Conditions:</strong><br>{{ $creditNote->terms_conditions }}</div>
            @endif

            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Prepared By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $creditNote->createdBy->name ?? 'N/A' }}
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Approved By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($creditNote->approvedBy)
                        {{ $creditNote->approvedBy->name }}
                        @else
                        <span style="color: #999;">Pending Approval</span>
                        @endif
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Received By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $creditNote->customer->name ?? 'Customer' }}
                    </div>
                </div>
            </div>

            <ol style="margin-top: 15px; padding-left: 20px;">
                <li>This credit note can be applied to future invoices</li>
                <li>Please retain this document for your records</li>
                <li>Credit note is valid until fully applied</li>
            </ol>

            <strong>{{ $creditNote->customer->name ?? 'Customer' }}</strong>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Credit Note No: {{ $creditNote->credit_note_number }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
