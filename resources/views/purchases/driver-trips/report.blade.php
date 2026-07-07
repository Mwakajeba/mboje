@extends('layouts.main')

@section('title', 'Ripoti ya Safari')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3 no-print">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
                ['label' => 'Safari na Madereva', 'url' => route('purchases.driver-trips.index'), 'icon' => 'bx bx-car'],
                ['label' => 'Ripoti', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Chapisha
                </button>
                <a href="{{ route('purchases.driver-trips.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i> Rudi nyuma
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card radius-10">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h5 class="mb-1 text-uppercase">Ripoti ya Safari / Trip</h5>
                    <p class="mb-0"><strong>Safari:</strong> {{ $trip->trip_name }}</p>
                </div>

                <div class="card bg-light border-0 mb-4">
                    <div class="card-body py-3">
                        <h6 class="text-uppercase text-muted mb-3">Taarifa za Safari</h6>
                        <div class="row g-2 small">
                            <div class="col-md-6 d-flex justify-content-between">
                                <span>Dereva</span>
                                <span class="fw-semibold">{{ $trip->driver_name }}</span>
                            </div>
                            <div class="col-md-6 d-flex justify-content-between">
                                <span>Tarehe</span>
                                <span class="fw-semibold">{{ $trip_date_formatted }}</span>
                            </div>
                            <div class="col-md-6 d-flex justify-content-between">
                                <span>Bei ya Safari</span>
                                <span class="fw-semibold">{{ format_currency($trip->trip_price) }}</span>
                            </div>
                            <div class="col-md-6 d-flex justify-content-between">
                                <span>Hali</span>
                                <span class="fw-semibold">
                                    @if(($trip->status ?? 'hai') === 'imekwisha')
                                        <span class="badge bg-success">Imekwisha</span>
                                    @else
                                        <span class="badge bg-primary">Hai</span>
                                    @endif
                                </span>
                            </div>
                            <div class="col-12">
                                <span class="d-block text-muted mb-1">Taarifa za Gari</span>
                                <span class="fw-semibold">{{ $trip->vehicle_info ?: '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="text-success text-uppercase border-bottom pb-2 mb-3">
                    <i class="bx bx-wallet me-1"></i> Mapato
                </h6>

                @if(empty($mapato_lines))
                    <p class="text-muted small mb-0">Hakuna mapato yaliyorekodiwa kwa safari hii.</p>
                @else
                    <div class="table-responsive mb-2">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Maelezo</th>
                                    <th class="text-end" style="width: 160px">Kiasi</th>
                                    @if(!empty($can_delete))
                                    <th class="text-center no-print" style="width: 90px">Vitendo</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mapato_lines as $line)
                                <tr>
                                    <td>{{ $line['maelezo'] }}</td>
                                    <td class="text-end">{{ format_currency($line['amount']) }}</td>
                                    @if(!empty($can_delete))
                                    <td class="text-center no-print">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-delete-trip-report-line"
                                                data-type="mapato"
                                                data-line-id="{{ $line['id'] }}"
                                                title="Futa mapato">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td class="text-end fw-bold">Jumla ya Mapato</td>
                                    <td class="text-end fw-bold text-success">{{ format_currency($mapato_total) }}</td>
                                    @if(!empty($can_delete))<td class="no-print"></td>@endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                <h6 class="text-warning text-uppercase border-bottom pb-2 mb-3 mt-4">
                    <i class="bx bx-receipt me-1"></i> Matumizi
                </h6>

                @if(empty($matumizi_lines))
                    <p class="text-muted small mb-0">Hakuna matumizi yaliyorekodiwa kwa safari hii.</p>
                @else
                    <div class="table-responsive mb-2">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Maelezo</th>
                                    <th class="text-end" style="width: 160px">Kiasi</th>
                                    @if(!empty($can_delete))
                                    <th class="text-center no-print" style="width: 90px">Vitendo</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matumizi_lines as $line)
                                <tr>
                                    <td>{{ $line['maelezo'] }}</td>
                                    <td class="text-end">{{ format_currency($line['amount']) }}</td>
                                    @if(!empty($can_delete))
                                    <td class="text-center no-print">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-delete-trip-report-line"
                                                data-type="matumizi"
                                                data-line-id="{{ $line['id'] }}"
                                                title="Futa matumizi">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td class="text-end fw-bold">Jumla ya Matumizi</td>
                                    <td class="text-end fw-bold text-warning">{{ format_currency($matumizi_total) }}</td>
                                    @if(!empty($can_delete))<td class="no-print"></td>@endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                <div class="card border-0 mt-4 driver-trip-report-summary">
                    <div class="card-body py-3">
                        <div class="row g-2 mb-2">
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span class="fw-bold text-dark">Jumla ya Mapato</span>
                                <span class="fw-bold text-dark">{{ format_currency($mapato_total) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span class="fw-bold text-dark">Jumla ya Matumizi</span>
                                <span class="fw-bold text-dark">{{ format_currency($matumizi_total) }}</span>
                            </div>
                        </div>
                        <hr class="my-2 border-dark opacity-25">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark text-uppercase">Faida (Mapato − Matumizi)</span>
                            <span class="fs-5 fw-bold text-dark">{{ format_currency($faida) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .driver-trip-report-summary {
        background-color: #f5f5f5 !important;
    }
    .driver-trip-report-summary,
    .driver-trip-report-summary .card-body {
        color: #000;
    }
</style>
@endpush

@if(!empty($can_delete))
@push('scripts')
@php
    $mapatoDestroyUrlTemplate = route('purchases.driver-trips.report.mapato.destroy', ['trip' => $trip->id, 'line' => '__LINE__']);
    $matumiziDestroyUrlTemplate = route('purchases.driver-trips.report.matumizi.destroy', ['trip' => $trip->id, 'line' => '__LINE__']);
@endphp
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    var mapatoDestroyUrl = @json($mapatoDestroyUrlTemplate);
    var matumiziDestroyUrl = @json($matumiziDestroyUrlTemplate);

    function lineDestroyUrl(type, lineId) {
        var template = type === 'matumizi' ? matumiziDestroyUrl : mapatoDestroyUrl;
        return template.replace('__LINE__', lineId);
    }

    $(document).on('click', '.btn-delete-trip-report-line', function () {
        var $btn = $(this);
        var type = $btn.data('type');
        var lineId = $btn.data('line-id');
        var label = type === 'matumizi' ? 'matumizi' : 'mapato';

        if (!confirm('Una uhakika unataka kufuta mstari huu wa ' + label + '?')) {
            return;
        }

        $.ajax({
            url: lineDestroyUrl(type, lineId),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        }).done(function (res) {
            if (res && res.success) {
                window.location.reload();
            } else {
                alert((res && res.message) ? res.message : 'Imeshindikana kufuta.');
            }
        }).fail(function (xhr) {
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
