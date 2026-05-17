@extends('layouts.main')

@section('title', 'Hesabu za Fedha — '.$supplier->name)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
                ['label' => 'Malipo ya Awali', 'url' => route('purchases.supplier-advances.index'), 'icon' => 'bx bx-wallet-alt'],
                ['label' => 'Hesabu', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Chapisha
                </button>
                <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">Rudi</a>
            </div>
        </div>

        <div class="card radius-10 statement-card mb-4">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h4 class="mb-1">Hesabu za Fedha</h4>
                    <p class="mb-0 fs-5 fw-semibold">{{ $supplier->name }}</p>
                    @if($supplier->tin_number)
                        <small class="text-muted">TIN: {{ $supplier->tin_number }}</small>
                    @endif
                    @if(!empty($period))
                        <p class="text-muted small mb-0 mt-2">
                            Kipindi: <strong>{{ $period['from'] }}</strong> hadi <strong>{{ $period['to'] }}</strong>
                        </p>
                    @endif
                    <p class="text-muted small mb-0 mt-1">Imetengenezwa {{ now()->format('Y-m-d H:i') }}</p>
                </div>

                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 text-center mb-4 border rounded py-3 bg-light g-2">
                    @if(!empty($period) && isset($totals['opening_balance']))
                        <div class="col">
                            <div class="text-muted small">Salio la kufungua</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['opening_balance']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Malipo/Mauzo (kipindi)</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['advances']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Matumizi/Manunuzi (kipindi)</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['applied']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Salio la kufunga/Baki</div>
                            <div class="fs-5 fw-bold text-primary">{{ format_currency($totals['closing_balance'] ?? $totals['balance']) }}</div>
                        </div>
                    @else
                        <div class="col">
                            <div class="text-muted small">Jumla ya malipo ya awali</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['advances']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Matumizi/Manunuzi</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['applied']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Salio</div>
                            <div class="fs-5 fw-bold text-primary">{{ format_currency($totals['balance']) }}</div>
                        </div>
                    @endif
                    <div class="col">
                        <div class="text-muted small">
                            Jumla ya thamani ya stoo
                            @if($stockLocationName)
                                <br><span style="font-size: 0.7rem;">({{ $stockLocationName }})</span>
                            @endif
                        </div>
                        @if($stockLocationName)
                            <div class="fs-5 fw-bold text-success">{{ format_currency($stockTotals['total_selling']) }}</div>
                        @else
                            <div class="fs-6 text-muted">—</div>
                        @endif
                    </div>
                </div>

                @if(!empty($openingRow))
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr class="table-secondary fw-semibold">
                                <td style="width: 120px">{{ $openingRow['date']->format('Y-m-d') }}</td>
                                <td>{{ $openingRow['description'] }}</td>
                                <td class="text-end" style="width: 160px">{{ format_currency($openingRow['balance']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @elseif($openingBalance !== null && empty($period))
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr class="table-secondary fw-semibold">
                                <td colspan="2">Salio la kufungua</td>
                                <td class="text-end">{{ format_currency($openingBalance) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif

                <h6 class="text-primary mb-2"><i class="bx bx-wallet me-1"></i> Malipo/Mauzo</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Maelezo</th>
                                <th>Aliyeingiza</th>
                                <th class="text-end">Malipo/Mauzo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($malipoLines as $line)
                            <tr>
                                <td>{{ $line['date']->format('Y-m-d') }}</td>
                                <td>{{ $line['description'] }}</td>
                                <td>{{ $line['performed_by'] ?? '—' }}</td>
                                <td class="text-end">{{ format_currency($line['paid']) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Hakuna Malipo/Mauzo katika kipindi hiki.</td>
                            </tr>
                            @endforelse
                            @if($malipoLines->isNotEmpty())
                            <tr class="table-light fw-semibold">
                                <td colspan="3" class="text-end">Jumla ya Malipo/Mauzo</td>
                                <td class="text-end">{{ format_currency($malipoTotal) }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <h6 class="text-primary mb-2"><i class="bx bx-receipt me-1"></i> Matumizi/Manunuzi</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Maelezo</th>
                                <th>Aliyeingiza</th>
                                <th class="text-end">Matumizi/Manunuzi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($matumiziLines as $line)
                            <tr>
                                <td>{{ $line['date']->format('Y-m-d') }}</td>
                                <td>{{ $line['description'] }}</td>
                                <td>{{ $line['performed_by'] ?? '—' }}</td>
                                <td class="text-end">{{ format_currency($line['deducted']) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Hakuna Matumizi/Manunuzi katika kipindi hiki.</td>
                            </tr>
                            @endforelse
                            @if($matumiziLines->isNotEmpty())
                            <tr class="table-light fw-semibold">
                                <td colspan="3" class="text-end">Jumla ya Matumizi/Manunuzi</td>
                                <td class="text-end">{{ format_currency($matumiziTotal) }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if(!empty($closingRow))
                <div class="table-responsive mb-0">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr class="table-secondary fw-semibold">
                                <td style="width: 120px">{{ $closingRow['date']->format('Y-m-d') }}</td>
                                <td>{{ $closingRow['description'] }}</td>
                                <td class="text-end" style="width: 160px">{{ format_currency($closingRow['balance']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="card radius-10 statement-card">
            <div class="card-body">
                <h6 class="text-primary mb-2">
                    <i class="bx bx-package me-1"></i> Stoo ya sasa
                    @if($stockLocationName)
                        <small class="text-muted">({{ $stockLocationName }})</small>
                    @endif
                </h6>
                <p class="text-muted small mb-3">Bidhaa zilizo kwenye stoo na thamani kwa bei ya mauzo.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Bidhaa</th>
                                <th class="text-end">Idadi</th>
                                <th class="text-end">Bei ya mauzo</th>
                                <th class="text-end">Thamani</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockRows as $row)
                            <tr>
                                <td>{{ $row['item']->name }}</td>
                                <td class="text-end">
                                    {{ number_format($row['quantity'], 2) }}
                                    <small class="text-muted">{{ $row['unit_of_measure'] }}</small>
                                </td>
                                <td class="text-end">{{ format_currency($row['unit_selling_price']) }}</td>
                                <td class="text-end fw-semibold">{{ format_currency($row['total_selling_price']) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    @if($stockLocationName)
                                        Hakuna stoo kwenye eneo hili.
                                    @else
                                        Chagua eneo la kuingia ili kuona stoo ya sasa.
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                            @if(count($stockRows) > 0)
                            <tr class="table-light fw-semibold">
                                <td class="text-end">Jumla ({{ $stockTotals['items_count'] }} bidhaa)</td>
                                <td class="text-end">{{ number_format($stockTotals['total_quantity'], 2) }}</td>
                                <td></td>
                                <td class="text-end">{{ format_currency($stockTotals['total_selling']) }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style media="print">
    .no-print, .sidebar-wrapper, .topbar, .page-footer, .breadcrumb { display: none !important; }
    .page-wrapper { margin: 0 !important; padding: 0 !important; }
    .statement-card { border: none !important; box-shadow: none !important; }
    .statement-card { page-break-inside: avoid; }
</style>
@endpush
@endsection
