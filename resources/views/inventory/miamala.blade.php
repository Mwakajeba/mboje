@extends('layouts.main')

@section('title', 'Miamala ya Stoo kwa Siku')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Miamala', 'url' => '#', 'icon' => 'bx bx-list-check']
        ]" />

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="mb-0 text-uppercase">Miamala ya Stoo kwa Siku</h5>
                <p class="text-muted small mb-0">Mapato/Mauzo, Gharama na stoo iliyoingizwa — kudumu na muda mfupi pamoja</p>
            </div>
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-arrow-back me-1"></i> Rudi
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('inventory.miamala') }}" class="row g-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label for="date" class="form-label">Chagua Tarehe</label>
                        <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-search me-1"></i> Angalia Miamala
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row row-cols-2 row-cols-md-4 text-center mb-4 g-3">
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">Mapato / Mauzo</div>
                        <div class="fs-5 fw-bold text-success">{{ number_format($totals['mapato_mauzo'] ?? 0, 2) }}</div>
                        <div class="text-muted small">{{ ($mapatoMauzo ?? collect())->count() }} rekodi</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">Gharama</div>
                        <div class="fs-5 fw-bold text-danger">{{ number_format($totals['gharama'] ?? 0, 2) }}</div>
                        <div class="text-muted small">{{ ($gharama ?? collect())->count() }} rekodi</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">Stoo Iliyoingizwa</div>
                        <div class="fs-5 fw-bold text-primary">{{ (int) ($totals['stoo_ingizo_count'] ?? 0) }}</div>
                        <div class="text-muted small">marekodi ya pokeo</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">Tarehe</div>
                        <div class="fs-6 fw-bold">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d M Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mapato / Mauzo --}}
        <div class="card mb-4">
            <div class="card-header bg-success-subtle">
                <h6 class="mb-0 text-success text-uppercase">
                    <i class="bx bx-trending-up me-1"></i> Mapato / Mauzo
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Stoo</th>
                                <th>Aina</th>
                                <th>Mteja</th>
                                <th>Zao</th>
                                <th>Maelezo</th>
                                <th class="text-end">Kiasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mapatoMauzo as $row)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $row['stoo_label'] }}</span></td>
                                <td>
                                    <span class="badge {{ $row['subtype'] === 'Mauzo' ? 'bg-success' : 'bg-success-subtle text-success border border-success' }}">
                                        {{ $row['subtype'] }}
                                    </span>
                                </td>
                                <td>{{ $row['customer_name'] }}</td>
                                <td>{{ $row['item_name'] }}</td>
                                <td>{{ $row['description'] }}</td>
                                <td class="text-end fw-semibold text-success">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Hakuna mapato wala mauzo siku hii.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(($mapatoMauzo ?? collect())->isNotEmpty())
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end">Jumla</th>
                                <th class="text-end text-success">{{ number_format($totals['mapato_mauzo'] ?? 0, 2) }}</th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Gharama --}}
        <div class="card mb-4">
            <div class="card-header bg-danger-subtle">
                <h6 class="mb-0 text-danger text-uppercase">
                    <i class="bx bx-receipt me-1"></i> Gharama
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Stoo</th>
                                <th>Mteja</th>
                                <th>Zao</th>
                                <th>Sababu</th>
                                <th class="text-end">Kiasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gharama as $row)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $row['stoo_label'] }}</span></td>
                                <td>{{ $row['customer_name'] }}</td>
                                <td>{{ $row['item_name'] }}</td>
                                <td>{{ $row['description'] }}</td>
                                <td class="text-end fw-semibold text-danger">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Hakuna gharama zilizoingizwa siku hii.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(($gharama ?? collect())->isNotEmpty())
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Jumla</th>
                                <th class="text-end text-danger">{{ number_format($totals['gharama'] ?? 0, 2) }}</th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Stoo iliyoingizwa --}}
        <div class="card mb-4">
            <div class="card-header bg-primary-subtle">
                <h6 class="mb-0 text-primary text-uppercase">
                    <i class="bx bx-archive-in me-1"></i> Stoo Iliyoingizwa
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Stoo</th>
                                <th>Mteja</th>
                                <th>Zao</th>
                                <th>Maelezo</th>
                                <th class="text-end">Idadi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stooIngizo as $row)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $row['stoo_label'] }}</span></td>
                                <td>{{ $row['customer_name'] }}</td>
                                <td>{{ $row['item_name'] }}</td>
                                <td>{{ $row['description'] }}</td>
                                <td class="text-end fw-semibold">{{ $row['quantity_display'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Hakuna stoo iliyoingizwa siku hii.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
