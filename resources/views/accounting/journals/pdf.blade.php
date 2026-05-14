<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Journal Entry - {{ $journal->reference }}</title>
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

        /* Journal title */
        .journal-title {
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

        .journal-info-left {
            width: 48%;
            font-size: 10px;
        }

        .journal-info-left strong {
            color: #1e40af;
        }

        .journal-box {
            width: 48%;
            text-align: right;
        }

        .journal-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .journal-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .journal-box td:nth-child(even) {
            text-align: right;
        }

        .journal-box strong {
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

        .debit-amount {
            color: #059669;
            font-weight: bold;
        }

        .credit-amount {
            color: #dc2626;
            font-weight: bold;
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

        .nature-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .nature-debit {
            background-color: #d1fae5;
            color: #059669;
        }

        .nature-credit {
            background-color: #fee2e2;
            color: #dc2626;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @php
            $company = $journal->user->company ?? null;
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

        <div class="journal-title">JOURNAL ENTRY</div>
        <hr>
        {{-- Journal Info --}}
        <div class="info-section">
            <div class="journal-info-left" style="float: left; width: 48%;">
                <strong>Created By:</strong><br>
                @php
                $creator = $journal->user ?? null;
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
                <br><br>
                @if($journal->branch)
                <strong>Branch:</strong><br>
                {{ $journal->branch->name }}<br>
                @endif
            </div>

            <div class="journal-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Journal no:</strong></td>
                        <td>{{ $journal->reference }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $journal->date ? $journal->date->format('d F Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Currency:</strong></td>
                        <td>TZS</td>
                        <td><strong>Time:</strong></td>
                        <td>{{ $journal->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($journal->description)
        <div class="notes" style="clear: both;">
            <strong>Description:</strong><br>
            {{ $journal->description }}
        </div>
        @endif

        {{-- Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Account</th>
                    <th>Account Code</th>
                    <th>Nature</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($journal->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->chartAccount->account_name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->chartAccount->account_code ?? 'N/A' }}</td>
                    <td class="text-center">
                        <span class="nature-badge nature-{{ $item->nature }}">
                            {{ ucfirst($item->nature) }}
                        </span>
                    </td>
                    <td>{{ $item->description ?: '-' }}</td>
                    <td class="text-right {{ $item->nature == 'debit' ? 'debit-amount' : 'credit-amount' }}">
                        {{ number_format($item->amount, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        @php
        $colspan = 5;
        $debitTotal = $journal->items->where('nature', 'debit')->sum('amount');
        $creditTotal = $journal->items->where('nature', 'credit')->sum('amount');
        $balance = abs($debitTotal - $creditTotal);
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Total Debit: </td>
                <td class="debit-amount">{{ number_format($debitTotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Total Credit: </td>
                <td class="credit-amount">{{ number_format($creditTotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>Balance: </strong></td>
                <td><strong>{{ number_format($balance, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($journal, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>Total Debit Amount: {{ ucwords($journal->getAmountInWords()) }}</strong>
        </div>
        @endif

        @if($balance != 0)
        <div style="margin-top:5px;font-style:italic;color:#dc2626;">
            <strong>Warning: Journal is not balanced! Debit and Credit totals do not match.</strong>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Prepared By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $journal->user->name ?? 'N/A' }}
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Approved By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($journal->approved && $journal->approvedBy)
                        {{ $journal->approvedBy->name }}
                        @else
                        <span style="color: #999;">Pending Approval</span>
                        @endif
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Posted By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($journal->glPosted)
                        Posted to GL
                        @else
                        <span style="color: #999;">Not Posted</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Journal Entry No: {{ $journal->reference }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
