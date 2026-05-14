<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Deposit - {{ $deposit->deposit_number }}</title>
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
            border-top: 2px solid #ffc107;
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
            color: #ffc107;
        }

        .company-details {
            font-size: 10px;
        }

        /* Deposit title */
        .deposit-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #ffc107;
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

        .bill-to strong {
            color: #ffc107;
        }

        .deposit-box {
            width: 48%;
            text-align: right;
        }

        .deposit-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .deposit-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .deposit-box td:nth-child(even) {
            text-align: right;
        }

        .deposit-box strong {
            color: #ffc107;
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
            background-color: #ffc107;
            color: #000;
        }

        .details-table td {
            background-color: #fff;
        }

        /* Notes section */
        .notes {
            margin-top: 15px;
            font-size: 10px;
        }

        .notes strong {
            color: #ffc107;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            font-size: 9px;
            text-align: center;
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
        }

        .status-draft {
            background-color: #6c757d;
            color: #fff;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-confirmed {
            background-color: #198754;
            color: #fff;
        }

        .status-refunded {
            background-color: #0dcaf0;
            color: #000;
        }

        .status-applied {
            background-color: #0d6efd;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="logo-section text-center">
            @if($company && $company->logo)
                <img src="{{ public_path('storage/' . $company->logo) }}" alt="Company Logo" class="company-logo">
            @endif
            <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
            @if($branch)
                <div class="company-details">{{ $branch->name ?? '' }}</div>
            @endif
            @if($company && $company->address)
                <div class="company-details">{{ $company->address }}</div>
            @endif
            @if($company && $company->phone)
                <div class="company-details">Tel: {{ $company->phone }}</div>
            @endif
            @if($company && $company->email)
                <div class="company-details">Email: {{ $company->email }}</div>
            @endif
        </div>

        <hr>

        <!-- Title -->
        <div class="deposit-title">CUSTOMER DEPOSIT RECEIPT</div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="bill-to">
                <strong>Customer Details:</strong><br>
                {{ $deposit->customer->name ?? 'N/A' }}<br>
                @if($deposit->customer && $deposit->customer->phone)
                    Phone: {{ $deposit->customer->phone }}<br>
                @endif
                @if($deposit->customer && $deposit->customer->email)
                    Email: {{ $deposit->customer->email }}<br>
                @endif
                @if($deposit->customer && $deposit->customer->company_address)
                    {{ $deposit->customer->company_address }}
                @endif
            </div>

            <div class="deposit-box">
                <table>
                    <tr>
                        <td><strong>Deposit Number:</strong></td>
                        <td>{{ $deposit->deposit_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td>{{ $deposit->deposit_date->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="status-badge status-{{ $deposit->status }}">
                                {{ strtoupper($deposit->status) }}
                            </span>
                        </td>
                    </tr>
                    @if($deposit->contract)
                    <tr>
                        <td><strong>Contract:</strong></td>
                        <td>{{ $deposit->contract->contract_number }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Details Table -->
        <table class="details-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Amount</strong></td>
                    <td class="text-right"><strong>TZS {{ number_format($deposit->amount, 2) }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Payment Method</strong></td>
                    <td>{{ $deposit->payment_method === 'bank_transfer' ? 'Bank' : ucfirst(str_replace('_', ' ', $deposit->payment_method)) }}</td>
                </tr>
                @if($deposit->bankAccount)
                <tr>
                    <td><strong>Bank Account</strong></td>
                    <td>{{ $deposit->bankAccount->name }} @if($deposit->bankAccount->account_number)({{ $deposit->bankAccount->account_number }})@endif</td>
                </tr>
                @endif
                @if($deposit->reference_number)
                <tr>
                    <td><strong>Reference Number</strong></td>
                    <td>{{ $deposit->reference_number }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <!-- Notes -->
        @if($deposit->notes)
        <div class="notes">
            <strong>Notes:</strong><br>
            {{ $deposit->notes }}
        </div>
        @endif

        <!-- Deposit Movements -->
        @if(isset($movements) && $movements->count() > 0)
        <div style="margin-top: 20px;">
            <h4 style="color: #ffc107; font-size: 14px; margin-bottom: 10px;">DEPOSIT MOVEMENT HISTORY</h4>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Contract</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $runningBalance = 0;
                    @endphp
                    @foreach($movements as $movement)
                        @php
                            if ($movement['type'] === 'deposit') {
                                $runningBalance += $movement['amount'];
                            } else {
                                $runningBalance -= $movement['amount'];
                            }
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($movement['date'])->format('M d, Y') }}</td>
                            <td>
                                @if($movement['type'] === 'deposit')
                                    <span style="color: #198754; font-weight: bold;">DEPOSIT</span>
                                @else
                                    <span style="color: #dc3545; font-weight: bold;">USAGE</span>
                                @endif
                            </td>
                            <td>{{ $movement['reference'] }}</td>
                            <td>{{ $movement['contract'] ?? 'N/A' }}</td>
                            <td class="text-right">
                                @if($movement['type'] === 'deposit')
                                    <span style="color: #198754;">+TZS {{ number_format($movement['amount'], 2) }}</span>
                                @else
                                    <span style="color: #dc3545;">-TZS {{ number_format($movement['amount'], 2) }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-badge status-{{ $movement['status'] }}">
                                    {{ strtoupper($movement['status']) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td colspan="4" class="text-right">Current Balance:</td>
                        <td class="text-right">TZS {{ number_format($runningBalance, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <hr>
            <p>This is a computer-generated document. No signature is required.</p>
            <p>Generated on: {{ now()->format('M d, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
