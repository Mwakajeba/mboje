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
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mapato_lines as $line)
                                <tr>
                                    <td>{{ $line['maelezo'] }}</td>
                                    <td class="text-end">{{ format_currency($line['amount']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td class="text-end fw-bold">Jumla ya Mapato</td>
                                    <td class="text-end fw-bold text-success">{{ format_currency($mapato_total) }}</td>
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
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matumizi_lines as $line)
                                <tr>
                                    <td>{{ $line['maelezo'] }}</td>
                                    <td class="text-end">{{ format_currency($line['amount']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td class="text-end fw-bold">Jumla ya Matumizi</td>
                                    <td class="text-end fw-bold text-warning">{{ format_currency($matumizi_total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                <div class="card border-0 mt-4 {{ $faida >= 0 ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10' }}">
                    <div class="card-body py-3">
                        <div class="row g-2 small mb-2">
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Jumla ya Mapato</span>
                                <span class="fw-semibold">{{ format_currency($mapato_total) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Jumla ya Matumizi</span>
                                <span class="fw-semibold">{{ format_currency($matumizi_total) }}</span>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-uppercase">Faida (Mapato − Matumizi)</span>
                            <span class="fs-5 fw-bold {{ $faida >= 0 ? 'text-success' : 'text-danger' }}">{{ format_currency($faida) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
