<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <title>Ripoti ya Salio — {{ $customer->name }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        .header {
            border-bottom: 3px solid #16a34a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; padding: 0; }
        .logo-cell { width: 100px; }
        .company-logo {
            max-height: 72px;
            max-width: 96px;
            object-fit: contain;
        }
        .title-cell { text-align: center; }
        .doc-title {
            font-size: 20px;
            font-weight: bold;
            color: #15803d;
            margin: 0 0 4px 0;
            letter-spacing: 0.5px;
        }
        .doc-subtitle {
            font-size: 11px;
            color: #6b7280;
            margin: 0;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin: 0 0 2px 0;
        }
        .meta-cell { text-align: right; font-size: 9px; color: #6b7280; }

        .info-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }
        .info-box td {
            padding: 8px 10px;
            border: 1px solid #bbf7d0;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #166534;
            width: 28%;
            background: #dcfce7;
        }

        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 18px;
        }
        .summary-table td {
            width: 33.33%;
            text-align: center;
            padding: 12px 8px;
            border-radius: 6px;
            vertical-align: middle;
        }
        .summary-mauzo { background: #eff6ff; border: 1px solid #93c5fd; }
        .summary-mikopo { background: #fff7ed; border: 1px solid #fdba74; }
        .summary-salio { background: #f0fdf4; border: 2px solid #16a34a; }
        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }
        .summary-salio .summary-value { color: #15803d; font-size: 18px; }
        .summary-note { font-size: 8px; color: #9ca3af; margin-top: 3px; }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #fff;
            background: #16a34a;
            padding: 7px 10px;
            margin: 14px 0 0 0;
            border-radius: 4px 4px 0 0;
        }
        .section-title.mikopo { background: #d97706; }
        .section-title.malipo { background: #dc2626; }
        .section-title.stoo { background: #0891b2; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            border: 1px solid #d1d5db;
        }
        .data-table thead th {
            background: #f3f4f6;
            color: #374151;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 7px 6px;
            border: 1px solid #d1d5db;
            text-align: left;
        }
        .data-table tbody td {
            padding: 6px;
            border: 1px solid #e5e7eb;
            font-size: 9.5px;
            vertical-align: top;
        }
        .data-table tbody tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .empty-row td {
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            padding: 12px;
        }

        .totals-bar {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            border: 2px solid #16a34a;
        }
        .totals-bar td {
            padding: 10px 12px;
            font-size: 11px;
        }
        .totals-bar .label { font-weight: bold; color: #374151; }
        .totals-bar .value { text-align: right; font-weight: bold; }
        .totals-bar .final-row {
            background: #16a34a;
            color: #fff;
            font-size: 13px;
        }
        .totals-bar .final-row .value { font-size: 15px; }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #d1d5db;
            font-size: 8.5px;
            color: #9ca3af;
            text-align: center;
        }
        .footer strong { color: #6b7280; }
    </style>
</head>
<body>
    @php
        $companyName = $company->name ?? ($customer->company->name ?? 'Kampuni');
        $logoPath = null;
        if (!empty($company->logo)) {
            $fullLogo = public_path('storage/' . $company->logo);
            if (file_exists($fullLogo)) {
                $logoPath = $fullLogo;
            }
        }
        $fmt = fn ($n) => number_format((float) $n, 2);
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoPath)
                        <img src="{{ $logoPath }}" class="company-logo" alt="Logo">
                    @endif
                </td>
                <td class="title-cell">
                    <p class="company-name">{{ $companyName }}</p>
                    <h1 class="doc-title">RIPOTI YA SALIO LA AKAUNTI</h1>
                    <p class="doc-subtitle">Taarifa ya mauzo, mikopo na salio la mteja / wakulima</p>
                </td>
                <td class="meta-cell">
                    <div>Tarehe: <strong>{{ $generated_at }}</strong></div>
                    @if($customer->branch?->name)
                        <div>Tawi: {{ $customer->branch->name }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="info-box">
        <tr>
            <td class="info-label">Mteja / Wakulima</td>
            <td><strong>{{ $customer->name }}</strong></td>
            <td class="info-label">Nambari ya Mteja</td>
            <td>{{ $customer->customerNo ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Simu</td>
            <td>{{ $customer->phone ?? '—' }}</td>
            <td class="info-label">Barua pepe</td>
            <td>{{ $customer->email ?? '—' }}</td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td class="summary-mauzo">
                <div class="summary-label">Jumla ya Mauzo</div>
                <div class="summary-value">TZS {{ $fmt($total_mauzo) }}</div>
                <div class="summary-note">Mauzo yote ya zao</div>
            </td>
            <td class="summary-mikopo">
                <div class="summary-label">Salio la Mikopo</div>
                <div class="summary-value">TZS {{ $fmt($salio_mikopo) }}</div>
                <div class="summary-note">Mkopo − Malipo</div>
            </td>
            <td class="summary-salio">
                <div class="summary-label">Salio la Mteja</div>
                <div class="summary-value">TZS {{ $fmt($salio_mteja) }}</div>
                <div class="summary-note">Mauzo − Mikopo</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Mauzo ya Zao — Yote</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 11%">Tarehe</th>
                <th style="width: 22%">Zao</th>
                <th style="width: 10%">Nambari</th>
                <th class="text-right" style="width: 14%">Idadi</th>
                <th class="text-right" style="width: 14%">Bei</th>
                <th class="text-right" style="width: 16%">Jumla</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mauzo_lines as $line)
            <tr>
                <td>{{ $line['date'] }}</td>
                <td>{{ $line['item_name'] }}</td>
                <td>{{ $line['item_code'] }}</td>
                <td class="text-right">{{ $line['quantity_display'] }}</td>
                <td class="text-right">{{ $fmt($line['price']) }}</td>
                <td class="text-right fw-bold">{{ $fmt($line['total']) }}</td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="6">Hakuna mauzo yaliyorekodiwa.</td></tr>
            @endforelse
        </tbody>
        @if(count($mauzo_lines) > 0)
        <tfoot>
            <tr style="background:#eff6ff;">
                <td colspan="5" class="text-right fw-bold">Jumla ya Mauzo:</td>
                <td class="text-right fw-bold">TZS {{ $fmt($total_mauzo) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="section-title mikopo">Mikopo — Yote</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%">Tarehe</th>
                <th style="width: 18%">Aina ya Mkopo</th>
                <th>Maelezo / Sababu</th>
                <th style="width: 14%">Aliyeingiza</th>
                <th class="text-right" style="width: 14%">Kiasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mikopo_lines as $line)
            <tr>
                <td>{{ $line['date'] }}</td>
                <td>{{ $line['loan_type'] }}</td>
                <td>{{ $line['description'] }}</td>
                <td>{{ $line['entered_by'] }}</td>
                <td class="text-right fw-bold">{{ $fmt($line['amount']) }}</td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="5">Hakuna mikopo iliyorekodiwa.</td></tr>
            @endforelse
        </tbody>
        @if(count($mikopo_lines) > 0)
        <tfoot>
            <tr style="background:#fff7ed;">
                <td colspan="4" class="text-right fw-bold">Jumla ya Mikopo Iliyotolewa:</td>
                <td class="text-right fw-bold">TZS {{ $fmt($total_mikopo_given) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="section-title malipo">Malipo ya Mikopo</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%">Tarehe</th>
                <th>Maelezo</th>
                <th style="width: 14%">Aliyeingiza</th>
                <th class="text-right" style="width: 14%">Kiasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($malipo_lines as $line)
            <tr>
                <td>{{ $line['date'] }}</td>
                <td>{{ $line['description'] }}</td>
                <td>{{ $line['entered_by'] }}</td>
                <td class="text-right fw-bold">{{ $fmt($line['amount']) }}</td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="4">Hakuna malipo ya mikopo yaliyorekodiwa.</td></tr>
            @endforelse
        </tbody>
        @if(count($malipo_lines) > 0)
        <tfoot>
            <tr style="background:#fef2f2;">
                <td colspan="3" class="text-right fw-bold">Jumla ya Malipo:</td>
                <td class="text-right fw-bold">TZS {{ $fmt($total_malipo) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    @if(!empty($storage_balances))
    <div class="section-title stoo">Zao Lililobaki Stoo</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Zao</th>
                <th style="width: 14%">Nambari</th>
                <th class="text-right" style="width: 20%">Idadi</th>
                <th class="text-right" style="width: 20%">Kifurushi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($storage_balances as $balance)
            <tr>
                <td>{{ $balance['item_name'] }}</td>
                <td>{{ $balance['item_code'] }}</td>
                <td class="text-right">{{ $balance['quantity_display'] }}</td>
                <td class="text-right">{{ $balance['package_display'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table class="totals-bar">
        <tr>
            <td class="label">Jumla ya Mauzo ya Zao</td>
            <td class="value">TZS {{ $fmt($total_mauzo) }}</td>
        </tr>
        <tr>
            <td class="label">Salio la Mikopo (Mkopo − Malipo)</td>
            <td class="value">TZS {{ $fmt($salio_mikopo) }}</td>
        </tr>
        <tr class="final-row">
            <td class="label">SALIO LA MTEJA (Mauzo − Mikopo)</td>
            <td class="value">TZS {{ $fmt($salio_mteja) }}</td>
        </tr>
    </table>

    <div class="footer">
        <strong>{{ $companyName }}</strong> — Ripoti hii imetolewa kiotomatiki kutoka mfumo wa usimamizi.<br>
        Mteja anaweza kutumia ripoti hii kujua salio lake la sasa. Asante kwa kufanya biashara nasi.
    </div>
</body>
</html>
