<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Deposit Statement - {{ $customer->name }}</title>
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

        /* Statement title */
        .statement-title {
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

        .customer-info {
            width: 48%;
            font-size: 10px;
        }

        .customer-info strong {
            color: #ffc107;
        }

        .summary-box {
            width: 48%;
            text-align: right;
        }

        .summary-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .summary-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .summary-box td:nth-child(even) {
            text-align: right;
        }

        .summary-box strong {
            color: #ffc107;
        }

        /* Movements table */
        .movements-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 15px;
        }

        .movements-table th,
        .movements-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }

        .movements-table th {
            text-align: left;
            font-weight: bold;
            background-color: #ffc107;
            color: #000;
        }

        .movements-table td {
            background-color: #fff;
        }

        .movements-table .text-right {
            text-align: right;
        }

        .deposit-amount {
            color: #198754;
            font-weight: bold;
        }

        .usage-amount {
            color: #dc3545;
            font-weight: bold;
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
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8px;
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

        .status-sent {
            background-color: #0d6efd;
            color: #fff;
        }

        .status-paid {
            background-color: #198754;
            color: #fff;
        }

        .status-overdue {
            background-color: #dc3545;
            color: #fff;
        }

        .status-cancelled {
            background-color: #000;
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
        <div class="statement-title">CUSTOMER DEPOSIT STATEMENT</div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="customer-info">
                <strong>Customer Details:</strong><br>
                {{ $customer->name }}<br>
                Customer #{{ $customer->customerNo }}<br>
                @if($customer->phone)
                    Phone: {{ $customer->phone }}<br>
                @endif
                @if($customer->email)
                    Email: {{ $customer->email }}<br>
                @endif
                @if($customer->company_address)
                    {{ $customer->company_address }}
                @endif
            </div>

            <div class="summary-box">
                <table>
                    <tr>
                        <td><strong>Total Deposited:</strong></td>
                        <td>TZS {{ number_format($totalDeposited, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Used:</strong></td>
                        <td>TZS {{ number_format($totalUsed, 2) }}</td>
                    </tr>
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td><strong>Remaining Balance:</strong></td>
                        <td>TZS {{ number_format($remainingBalance, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Deposit Movements Table -->
        @if($movements->count() > 0)
        <table class="movements-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Contract</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                    <th>Description</th>
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
                        <td>
                            @if($movement['date'] instanceof \Carbon\Carbon)
                                {{ $movement['date']->format('M d, Y') }}
                            @else
                                {{ \Carbon\Carbon::parse($movement['date'])->format('M d, Y') }}
                            @endif
                        </td>
                        <td>
                            @if($movement['type'] === 'deposit')
                                <span class="status-badge status-confirmed">DEPOSIT</span>
                            @else
                                <span class="status-badge status-overdue">USAGE</span>
                            @endif
                        </td>
                        <td>{{ $movement['reference'] }}</td>
                        <td>{{ $movement['contract'] ?? 'N/A' }}</td>
                        <td class="text-right">
                            @if($movement['type'] === 'deposit')
                                <span class="deposit-amount">+TZS {{ number_format($movement['amount'], 2) }}</span>
                            @else
                                <span class="usage-amount">-TZS {{ number_format($movement['amount'], 2) }}</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusClass = match($movement['status']) {
                                    'draft' => 'status-draft',
                                    'pending' => 'status-pending',
                                    'confirmed' => 'status-confirmed',
                                    'refunded' => 'status-refunded',
                                    'applied' => 'status-applied',
                                    'sent' => 'status-sent',
                                    'paid' => 'status-paid',
                                    'overdue' => 'status-overdue',
                                    'cancelled' => 'status-cancelled',
                                    default => 'status-draft'
                                };
                            @endphp
                            <span class="status-badge {{ $statusClass }}">
                                {{ strtoupper($movement['status']) }}
                            </span>
                        </td>
                        <td>{{ $movement['description'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="4" class="text-right">Current Balance:</td>
                    <td class="text-right">TZS {{ number_format($runningBalance, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        @else
        <p style="text-align: center; margin-top: 20px; color: #666;">No deposit movements found.</p>
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
