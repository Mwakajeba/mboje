<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <title>Ripoti ya Stoo ya Kudumu</title>
    <style>
        body {
            font-family: dejavu sans, sans-serif;
            margin: 0;
            padding: 18px;
            color: #222;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #212529;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-logo {
            max-height: 70px;
            max-width: 110px;
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 14px;
            font-weight: 600;
            margin: 0;
        }
        .meta {
            color: #555;
            font-size: 11px;
            margin-top: 4px;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .summary td {
            width: 50%;
            border: 1px solid #ccc;
            padding: 10px;
            vertical-align: top;
        }
        .summary .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 4px;
        }
        .summary .value {
            font-size: 16px;
            font-weight: bold;
        }
        .stock-line {
            font-size: 10px;
            margin-top: 4px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 8px 0;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th,
        table.data td {
            border: 1px solid #bbb;
            padding: 6px 8px;
            font-size: 11px;
        }
        table.data th {
            background: #212529;
            color: #fff;
            text-align: left;
            text-transform: uppercase;
            font-size: 10px;
        }
        table.data .num {
            text-align: right;
        }
        table.data tfoot td {
            font-weight: bold;
            background: #f3f3f3;
        }
        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($company?->logo) && file_exists(public_path('storage/'.$company->logo)))
            <img src="{{ public_path('storage/'.$company->logo) }}" alt="{{ $company->name }}" class="company-logo">
        @endif
        @if(!empty($company?->name))
            <div class="company-name">{{ $company->name }}</div>
        @endif
        <p class="report-title">Ripoti ya Stoo ya Kudumu</p>
        <div class="meta">Imetengenezwa: {{ ($generatedAt ?? now())->format('d/m/Y H:i') }}</div>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Jumla ya Stoo ya Mazao</div>
                <div class="value">{{ $total_quantity_display }}</div>
                @foreach($stock_summary as $row)
                    <div class="stock-line">{{ $row['item_name'] }}: {{ $row['summary_display'] }}</div>
                @endforeach
            </td>
            <td>
                <div class="label">Jumla ya Gharama Zinazodaiwa (Salio)</div>
                <div class="value">{{ number_format($total_salio, 2) }}</div>
                <div class="stock-line">
                    Mapato/Mauzo {{ number_format($total_mapato, 2) }}
                    − Gharama {{ number_format($total_gharama, 2) }}
                    − Malipo {{ number_format($total_malipo, 2) }}
                </div>
            </td>
        </tr>
    </table>

    <p class="section-title">Orodha ya Wateja</p>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 36px">#</th>
                <th>Jina la Mteja</th>
                <th>Idadi / Kifurushi (Stoo)</th>
                <th class="num">Kiasi cha Salio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $row['name'] }}
                    @if(!empty($row['phone']))
                        <br><span style="color:#666;font-size:10px">{{ $row['phone'] }}</span>
                    @endif
                </td>
                <td>{{ $row['quantity_display'] }}</td>
                <td class="num">{{ number_format($row['salio'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center;color:#666">Hakuna wateja wenye uhifadhi wa kudumu.</td>
            </tr>
            @endforelse
        </tbody>
        @if($customers->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right">Jumla</td>
                <td>{{ $total_quantity_display }}</td>
                <td class="num">{{ number_format($total_salio, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        {{ $company->name ?? 'Mboje' }} — Ripoti ya Stoo ya Kudumu
    </div>
</body>
</html>
