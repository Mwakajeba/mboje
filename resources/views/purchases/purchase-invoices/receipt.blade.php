<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: monospace; margin: 0; padding: 10px; }
        .container { max-width: 380px; margin: 0 auto; }
        h3 { margin: 0; text-align: center; }
        hr { border: 0; border-top: 1px dashed #999; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { font-size: 12px; padding: 2px 0; }
        .center { text-align: center; }
        .small { font-size: 11px; }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="center">
            <h3>PAYMENT RECEIPT</h3>
            <div class="small">{{ config('app.name') }}</div>
        </div>
        <hr>
        <div class="small">
            <div><strong>Invoice:</strong> {{ $invoice->invoice_number }}</div>
            <div><strong>Date:</strong> {{ optional($p->date)->format('d M Y H:i') }}</div>
            <div><strong>Supplier:</strong> {{ optional($invoice->supplier)->name ?? 'N/A' }}</div>
        </div>
        <hr>
        <table class="small">
            <tr>
                <td>Amount Paid</td>
                <td style="text-align:right;">{{ number_format($p->amount, 2) }}</td>
            </tr>
            <tr>
                <td>Method</td>
                <td style="text-align:right;">{{ $p->bank_account_id ? ('Bank - ' . (optional($p->bankAccount)->name ?? '')) : 'Cash' }}</td>
            </tr>
            <tr>
                <td>Reference</td>
                <td style="text-align:right;">{{ $p->reference ?? 'N/A' }}</td>
            </tr>
        </table>
        @if($p->description)
        <hr>
        <div class="small">{{ $p->description }}</div>
        @endif
        <hr>
        <div class="center small">Thank you</div>
    </div>
</body>
</html>
