<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }} — {{ $customer->name ?? '' }}</title>
    <style>
        @page {
            margin: 36mm 14mm 16mm 14mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #111;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Repeats on every page */
        .page-header {
            position: fixed;
            top: -30mm;
            left: 0;
            right: 0;
            height: 26mm;
            border-bottom: 1.5px solid #111;
            padding-bottom: 4px;
        }
        .page-header-table { width: 100%; border-collapse: collapse; }
        .page-header-table td { vertical-align: middle; padding: 0; }
        .header-logo-cell { width: 70px; }
        .header-logo {
            max-height: 48px;
            max-width: 64px;
            object-fit: contain;
        }
        .header-center { text-align: center; }
        .header-company {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 0 0 2px 0;
        }
        .header-branch {
            font-size: 10px;
            font-weight: 600;
            color: #333;
            margin: 0 0 4px 0;
        }
        .header-doc-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            margin: 0;
            color: #111;
        }
        .header-meta-cell {
            width: 80px;
            text-align: right;
            font-size: 8px;
            color: #444;
            line-height: 1.35;
        }

        .page-footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            border-top: 1px solid #ccc;
            padding-top: 4px;
            font-size: 7.5px;
            color: #666;
            text-align: center;
        }
        .pagenum:before { content: counter(page); }
        .totalpages:before { content: counter(pages); }

        .content { padding-top: 2mm; }

        .account-block {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1px solid #111;
        }
        .account-block td {
            padding: 6px 8px;
            border: 1px solid #ccc;
            vertical-align: top;
        }
        .account-block .lbl {
            width: 22%;
            font-weight: bold;
            background: #f5f5f5;
            color: #222;
        }

        .summary-block {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border: 1px solid #111;
        }
        .summary-block th,
        .summary-block td {
            padding: 7px 8px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
        }
        .summary-block th {
            background: #f5f5f5;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #333;
        }
        .summary-block td {
            font-size: 11px;
            font-weight: bold;
        }
        .summary-block .highlight {
            background: #fafafa;
            border: 2px solid #111;
        }

        .section-heading {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #111;
            border-bottom: 1px solid #111;
            padding-bottom: 3px;
            margin: 12px 0 6px 0;
        }

        .stmt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
            border: 1px solid #111;
        }
        .stmt-table thead th {
            background: #f5f5f5;
            color: #111;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            padding: 5px 6px;
            border: 1px solid #ccc;
            text-align: left;
        }
        .stmt-table tbody td {
            padding: 5px 6px;
            border: 1px solid #ddd;
            font-size: 9px;
            vertical-align: top;
        }
        .stmt-table tbody tr:nth-child(even) td { background: #fafafa; }
        .stmt-table tfoot td {
            padding: 5px 6px;
            border: 1px solid #ccc;
            font-weight: bold;
            background: #f5f5f5;
            font-size: 9px;
        }
        .num { text-align: right; white-space: nowrap; }
        .empty td {
            text-align: center;
            color: #777;
            font-style: italic;
            padding: 10px;
        }

        .closing-balance {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            border: 1.5px solid #111;
        }
        .closing-balance td {
            padding: 6px 10px;
            border: 1px solid #ccc;
            font-size: 9.5px;
        }
        .closing-balance .lbl { color: #333; }
        .closing-balance .val { text-align: right; font-weight: bold; }
        .closing-balance .final td {
            background: #f5f5f5;
            font-size: 11px;
            font-weight: bold;
            border-top: 1.5px solid #111;
        }
        .closing-balance .final .val { font-size: 12px; }

        .note {
            margin-top: 10px;
            font-size: 8px;
            color: #555;
            line-height: 1.45;
        }
    </style>
</head>
<body>
@php
    $logoPath = null;
    if (!empty($company?->logo)) {
        $full = public_path('storage/'.$company->logo);
        if (file_exists($full)) {
            $logoPath = $full;
        }
    }
    $fmt = fn ($n) => number_format((float) $n, 2);
@endphp

{{-- Header on every page --}}
<div class="page-header">
    <table class="page-header-table">
        <tr>
            <td class="header-logo-cell">
                @if($logoPath)
                    <img src="{{ $logoPath }}" alt="" class="header-logo">
                @endif
            </td>
            <td class="header-center">
                <p class="header-company">{{ $company->name ?? 'Kampuni' }}</p>
                @if(!empty($branch?->name))
                    <p class="header-branch">{{ $branch->name }}</p>
                @endif
                <p class="header-doc-title">{{ $docTitle }}</p>
            </td>
            <td class="header-meta-cell">
                <div>Tarehe: {{ ($generatedAt ?? now())->format('d/m/Y') }}</div>
                <div>Ukurasa <span class="pagenum"></span> / <span class="totalpages"></span></div>
            </td>
        </tr>
    </table>
</div>

<div class="page-footer">
    {{ $company->name ?? '' }}@if(!empty($branch?->name)) · {{ $branch->name }}@endif
    · {{ $docTitle }} · Mteja: {{ $customer->name ?? '—' }}
    · Imetengenezwa {{ ($generatedAt ?? now())->format('d/m/Y H:i') }}
</div>

<div class="content">
    <table class="account-block">
        <tr>
            <td class="lbl">Mteja</td>
            <td>{{ $customer->name ?? '—' }}@if(!empty($customer?->phone)) · {{ $customer->phone }}@endif</td>
            <td class="lbl">Zao</td>
            <td>{{ $item->name ?? '—' }}@if(!empty($item?->code)) ({{ $item->code }})@endif</td>
        </tr>
        <tr>
            @if(!empty($showMazunguko))
            <td class="lbl">Mazunguko</td>
            <td>{{ $mazunguko ?? ($balance->mazunguko ?? 1) }}</td>
            <td class="lbl">Salio la Zao</td>
            <td>{{ $stock_quantity_display }} · Vifurushi: {{ $stock_package_display }}</td>
            @else
            <td class="lbl">Salio la Zao</td>
            <td>{{ $stock_quantity_display }}</td>
            <td class="lbl">Vifurushi</td>
            <td>{{ $stock_package_display }}</td>
            @endif
        </tr>
    </table>

    <table class="summary-block">
        <thead>
            <tr>
                <th>Mapato / Mauzo</th>
                <th>Gharama</th>
                <th>Malipo</th>
                <th class="highlight">Salio la Fedha</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $fmt($mapatoTotal) }}</td>
                <td>{{ $fmt($gharamaTotal) }}</td>
                <td>{{ $fmt($malipoTotal) }}</td>
                <td class="highlight">{{ $fmt($fedhaBalance) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-heading">Historia ya Zao (Uletaji / Utoaji)</div>
    <table class="stmt-table">
        <thead>
            <tr>
                <th style="width:13%">Tarehe</th>
                <th style="width:11%">Aina</th>
                <th>Maelezo</th>
                <th class="num" style="width:14%">Idadi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stockLines as $line)
            <tr>
                <td>{{ $line['date']->format('d/m/Y') }}</td>
                <td>{{ $line['type_label'] }}</td>
                <td>{{ $line['description'] }}</td>
                <td class="num">{{ $line['type'] === 'out' ? '-' : '' }}{{ $fmt($line['quantity']) }}</td>
            </tr>
            @empty
            <tr class="empty"><td colspan="4">Hakuna historia ya zao.</td></tr>
            @endforelse
        </tbody>
        @if($stockLines->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3" class="num">Salio la Zao Sasa</td>
                <td class="num">{{ $stock_quantity_display }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="section-heading">Mapato / Mauzo</div>
    <table class="stmt-table">
        <thead>
            <tr>
                <th style="width:13%">Tarehe</th>
                <th>Maelezo</th>
                <th class="num" style="width:16%">Kiasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mapatoLines as $line)
            <tr>
                <td>{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }}</td>
                <td>{{ $line['sababu'] }}</td>
                <td class="num">{{ $fmt($line['kiasi']) }}</td>
            </tr>
            @empty
            <tr class="empty"><td colspan="3">Hakuna mapato/mauzo.</td></tr>
            @endforelse
        </tbody>
        @if($mapatoLines->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2" class="num">Jumla</td>
                <td class="num">{{ $fmt($mapatoTotal) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="section-heading">Gharama / Matumizi</div>
    <table class="stmt-table">
        <thead>
            <tr>
                <th style="width:13%">Tarehe</th>
                <th>Maelezo</th>
                <th class="num" style="width:16%">Kiasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gharamaLines as $line)
            <tr>
                <td>{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }}</td>
                <td>{{ $line['sababu'] }}</td>
                <td class="num">{{ $fmt($line['kiasi']) }}</td>
            </tr>
            @empty
            <tr class="empty"><td colspan="3">Hakuna gharama.</td></tr>
            @endforelse
        </tbody>
        @if($gharamaLines->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2" class="num">Jumla</td>
                <td class="num">{{ $fmt($gharamaTotal) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="section-heading">Malipo</div>
    <table class="stmt-table">
        <thead>
            <tr>
                <th style="width:13%">Tarehe</th>
                <th>Maelezo</th>
                <th class="num" style="width:16%">Kiasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($malipoLines as $line)
            <tr>
                <td>{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }}</td>
                <td>{{ $line['sababu'] }}</td>
                <td class="num">{{ $fmt($line['kiasi']) }}</td>
            </tr>
            @empty
            <tr class="empty"><td colspan="3">Hakuna malipo.</td></tr>
            @endforelse
        </tbody>
        @if($malipoLines->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2" class="num">Jumla</td>
                <td class="num">{{ $fmt($malipoTotal) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <table class="closing-balance">
        <tr>
            <td class="lbl">Jumla ya Mapato / Mauzo</td>
            <td class="val">{{ $fmt($mapatoTotal) }}</td>
        </tr>
        <tr>
            <td class="lbl">Jumla ya Gharama</td>
            <td class="val">({{ $fmt($gharamaTotal) }})</td>
        </tr>
        <tr>
            <td class="lbl">Jumla ya Malipo</td>
            <td class="val">({{ $fmt($malipoTotal) }})</td>
        </tr>
        <tr class="final">
            <td class="lbl">SALIO LA FEDHA (Mapato − Gharama − Malipo)</td>
            <td class="val">{{ $fmt($fedhaBalance) }}</td>
        </tr>
    </table>

    <p class="note">
        Taarifa hii ni muhtasari wa miamala ya stoo na fedha kwa mteja na zao lililotajwa hapo juu.
        Salio la fedha = Mapato/Mauzo − Gharama − Malipo. Kwa maswali, wasiliana na tawi lako.
    </p>
</div>
</body>
</html>
