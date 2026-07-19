@extends('layouts.main')

@section('title', 'Taarifa ya Stoo ya Muda Mfupi (Wateja)')

@section('content')
@php
    $canDelete = !empty($can_delete);
    $financeColspan = $canDelete ? 4 : 3;
@endphp
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Stoo ya Muda Mfupi (Wateja)', 'url' => route('inventory.customer-storage.index'), 'icon' => 'bx bx-user-pin'],
                ['label' => 'Taarifa', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Chapisha
                </button>
                <a href="{{ route('inventory.customer-storage.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i> Rudi
                </a>
            </div>
        </div>

        <div class="card radius-10">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h5 class="mb-1 text-uppercase">Taarifa ya Stoo ya Muda Mfupi (Wateja)</h5>
                    <p class="mb-0"><strong>Mteja:</strong> {{ $customer->name ?? '—' }}</p>
                    <p class="mb-0"><strong>Zao:</strong> {{ $item->name ?? '—' }}@if(!empty($item?->code)) ({{ $item->code }})@endif</p>
                    <p class="mb-0"><strong>Mazunguko:</strong> {{ $mazunguko ?? ($balance->mazunguko ?? 1) }}</p>
                    <p class="mb-0 text-muted small">
                        Salio la zao: <strong>{{ $stock_quantity_display }}</strong>
                        · Vifurushi: <strong>{{ $stock_package_display }}</strong>
                        @if(!empty($customer?->phone))
                            · Simu: <strong>{{ $customer->phone }}</strong>
                        @endif
                    </p>
                </div>

                {{-- Muhtasari juu --}}
                <div class="row row-cols-2 row-cols-md-4 text-center mb-4 border rounded py-3 bg-light g-2">
                    <div class="col">
                        <div class="text-muted small">Mapato / Mauzo</div>
                        <div class="fs-5 fw-bold text-success">{{ number_format($mapatoTotal, 2) }}</div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Gharama</div>
                        <div class="fs-5 fw-bold text-danger">{{ number_format($gharamaTotal, 2) }}</div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Malipo</div>
                        <div class="fs-5 fw-bold text-primary">{{ number_format($malipoTotal, 2) }}</div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">Salio (Mapato − Gharama − Malipo)</div>
                        <div class="fs-5 fw-bold {{ $fedhaBalance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($fedhaBalance, 2) }}
                        </div>
                    </div>
                </div>

                {{-- Historia ya Zao --}}
                <h6 class="text-info text-uppercase border-bottom pb-2 mb-3">
                    <i class="bx bx-transfer me-1"></i> Historia ya Zao (Uletaji / Utoaji)
                </h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Aina</th>
                                <th>Maelezo</th>
                                <th class="text-end">Idadi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockLines as $line)
                            <tr class="{{ $line['type'] === 'out' ? 'table-danger' : '' }}">
                                <td>{{ $line['date']->format('d/m/Y') }}</td>
                                <td>
                                    @if($line['type'] === 'out')
                                        <span class="badge bg-danger">{{ $line['type_label'] }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $line['type_label'] }}</span>
                                    @endif
                                </td>
                                <td>{{ $line['description'] }}</td>
                                <td class="text-end {{ $line['type'] === 'out' ? 'text-danger' : 'text-success' }}">
                                    {{ $line['type'] === 'out' ? '-' : '+' }}{{ number_format($line['quantity'], 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Hakuna historia ya zao.</td>
                            </tr>
                            @endforelse
                            <tr class="table-light fw-semibold">
                                <td colspan="3" class="text-end">Salio la Zao Sasa</td>
                                <td class="text-end">{{ $stock_quantity_display }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- 1. Mapato / Mauzo --}}
                <h6 class="text-success text-uppercase border-bottom pb-2 mb-3">
                    <i class="bx bx-wallet me-1"></i> Mapato / Mauzo
                </h6>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Sababu</th>
                                <th class="text-end">Kiasi</th>
                                @if($canDelete)
                                <th class="text-center no-print" style="width: 72px">Vitendo</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mapatoLines as $line)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }}</td>
                                <td>{{ $line['sababu'] }}</td>
                                <td class="text-end">{{ number_format($line['kiasi'], 2) }}</td>
                                @if($canDelete)
                                <td class="text-center no-print">
                                    @if(!empty($line['id']))
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-taarifa-line"
                                            data-source="{{ $line['source'] }}"
                                            data-line-id="{{ $line['id'] }}"
                                            title="Futa">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $financeColspan }}" class="text-center text-muted py-3">Hakuna mapato/mauzo.</td>
                            </tr>
                            @endforelse
                            @if($mapatoLines->isNotEmpty())
                            <tr class="table-light fw-semibold">
                                <td colspan="2" class="text-end">Jumla ya Mapato / Mauzo</td>
                                <td class="text-end text-success">{{ number_format($mapatoTotal, 2) }}</td>
                                @if($canDelete)<td class="no-print"></td>@endif
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- 2. Gharama --}}
                <h6 class="text-danger text-uppercase border-bottom pb-2 mb-3 mt-4">
                    <i class="bx bx-receipt me-1"></i> Gharama / Matumizi
                </h6>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Sababu</th>
                                <th class="text-end">Kiasi</th>
                                @if($canDelete)
                                <th class="text-center no-print" style="width: 72px">Vitendo</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gharamaLines as $line)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }}</td>
                                <td>{{ $line['sababu'] }}</td>
                                <td class="text-end">{{ number_format($line['kiasi'], 2) }}</td>
                                @if($canDelete)
                                <td class="text-center no-print">
                                    @if(!empty($line['id']))
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-taarifa-line"
                                            data-source="gharama"
                                            data-line-id="{{ $line['id'] }}"
                                            title="Futa gharama">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $financeColspan }}" class="text-center text-muted py-3">Hakuna gharama.</td>
                            </tr>
                            @endforelse
                            @if($gharamaLines->isNotEmpty())
                            <tr class="table-light fw-semibold">
                                <td colspan="2" class="text-end">Jumla ya Gharama</td>
                                <td class="text-end text-danger">{{ number_format($gharamaTotal, 2) }}</td>
                                @if($canDelete)<td class="no-print"></td>@endif
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- 3. Malipo --}}
                <h6 class="text-primary text-uppercase border-bottom pb-2 mb-3 mt-4">
                    <i class="bx bx-money me-1"></i> Malipo
                </h6>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Sababu</th>
                                <th class="text-end">Kiasi</th>
                                @if($canDelete)
                                <th class="text-center no-print" style="width: 72px">Vitendo</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($malipoLines as $line)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }}</td>
                                <td>{{ $line['sababu'] }}</td>
                                <td class="text-end">{{ number_format($line['kiasi'], 2) }}</td>
                                @if($canDelete)
                                <td class="text-center no-print">
                                    @if(!empty($line['id']))
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-taarifa-line"
                                            data-source="malipo"
                                            data-line-id="{{ $line['id'] }}"
                                            title="Futa malipo">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $financeColspan }}" class="text-center text-muted py-3">Hakuna malipo.</td>
                            </tr>
                            @endforelse
                            @if($malipoLines->isNotEmpty())
                            <tr class="table-light fw-semibold">
                                <td colspan="2" class="text-end">Jumla ya Malipo</td>
                                <td class="text-end text-primary">{{ number_format($malipoTotal, 2) }}</td>
                                @if($canDelete)<td class="no-print"></td>@endif
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- 4. Salio --}}
                <div class="card bg-light border-0 mt-4">
                    <div class="card-body py-3">
                        <div class="row g-2 mb-2 small">
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Jumla ya Mapato / Mauzo</span>
                                <span class="fw-semibold text-success">{{ number_format($mapatoTotal, 2) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Jumla ya Gharama</span>
                                <span class="fw-semibold text-danger">{{ number_format($gharamaTotal, 2) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Jumla ya Malipo</span>
                                <span class="fw-semibold text-primary">{{ number_format($malipoTotal, 2) }}</span>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-uppercase">Salio (Mapato − Gharama − Malipo)</span>
                            <span class="fs-4 fw-bold {{ $fedhaBalance >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($fedhaBalance, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style media="print">
    .no-print, .sidebar-wrapper, .topbar, .page-footer, .breadcrumb { display: none !important; }
    .page-wrapper { margin: 0 !important; padding: 0 !important; }
</style>
@endpush

@if($canDelete)
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    var destroyUrl = @json(route('inventory.customer-storage.taarifa.line.destroy'));
    var balanceId = @json((int) $balance->id);
    var labels = {
        mapato: 'mapato',
        mauzo: 'mauzo',
        gharama: 'gharama',
        malipo: 'malipo'
    };

    $(document).on('click', '.btn-delete-taarifa-line', function () {
        var $btn = $(this);
        var source = $btn.data('source');
        var lineId = $btn.data('line-id');
        var label = labels[source] || 'rekodi';

        if (!confirm('Una uhakika unataka kufuta mstari huu wa ' + label + '?')) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            url: destroyUrl,
            method: 'DELETE',
            data: {
                balance_id: balanceId,
                source: source,
                line_id: lineId
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        }).done(function (res) {
            if (res && res.success) {
                window.location.reload();
            } else {
                $btn.prop('disabled', false);
                alert((res && res.message) ? res.message : 'Imeshindikana kufuta.');
            }
        }).fail(function (xhr) {
            $btn.prop('disabled', false);
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Imeshindikana kufuta.';
            alert(msg);
        });
    });
});
</script>
@endpush
@endif
