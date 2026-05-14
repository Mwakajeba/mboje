<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .receipt { width: 320px; margin: 0 auto; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #333; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .right { text-align: right; }
        .small { font-size: 11px; color: #555; }
        @media print {
            @page { margin: 0; }
            body { margin: 6mm; }
            .no-print { display: none; }
        }
    </style>
    <script nonce="{{ $cspNonce ?? '' }}">window.onload = function(){ window.print(); };</script>
    </head>
<body>
    <div class="receipt">
        <div class="center bold">{{ $invoice->company->name ?? config('app.name') }}</div>
        <div class="center small">{{ $invoice->branch->name ?? 'Main Branch' }}</div>
        <div class="center small">Receipt for Invoice {{ $invoice->invoice_number }}</div>
        <div class="line"></div>

        <table>
            <tr>
                <td>Receipt No:</td>
                <td class="right">RCP-{{ $receipt->id }}</td>
            </tr>
            <tr>
                <td>Date:</td>
                <td class="right">{{ $receipt->date->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td>Customer:</td>
                <td class="right">{{ $invoice->customer->name ?? 'Walk-in' }}</td>
            </tr>
        </table>

        <div class="line"></div>
        <table>
            <tr>
                <td class="bold">Amount Paid</td>
                <td class="right bold">TZS {{ number_format($receipt->amount, 2) }}</td>
            </tr>
            <tr>
                <td>Payment Method</td>
                <td class="right">{{ $receipt->bankAccount?->name ? 'Bank - ' . $receipt->bankAccount->name : 'Cash' }}</td>
            </tr>
            <tr>
                <td>Recorded By</td>
                <td class="right">{{ $receipt->user->name ?? 'System' }}</td>
            </tr>
        </table>
        <div class="line"></div>

        <table>
            <tr>
                <td>Total Invoice</td>
                <td class="right">TZS {{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Total Paid</td>
                <td class="right">TZS {{ number_format($invoice->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="bold">Balance</td>
                <td class="right bold">TZS {{ number_format($invoice->balance_due, 2) }}</td>
            </tr>
        </table>

        <div class="line"></div>
        <div class="center small">Thank you for your business!</div>

        <div class="center no-print" style="margin-top: 10px;">
            <button onclick="window.print()">Print</button>
        </div>
    </div>
</body>
</html>


