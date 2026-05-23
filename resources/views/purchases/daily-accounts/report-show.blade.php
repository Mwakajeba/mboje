@extends('layouts.main')

@section('title', 'Ripoti — Hesabu za Kila Siku')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3 no-print">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
                ['label' => 'Hesabu za Kila Siku', 'url' => route('purchases.daily-accounts.index'), 'icon' => 'bx bx-calendar-check'],
                ['label' => 'Ripoti', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Chapisha
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.close()">
                    <i class="bx bx-x me-1"></i> Funga
                </button>
            </div>
        </div>

        <div class="card radius-10">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h5 class="mb-1 text-uppercase">Ripoti ya Hesabu za Kila Siku</h5>
                    <p class="mb-0"><strong>Mfanyakazi:</strong> {{ $employee_name }}</p>
                    <p class="mb-0 text-muted"><strong>Tarehe:</strong> {{ $entry_date_formatted }}</p>
                </div>

                {{-- Mauzo --}}
                <h6 class="text-success text-uppercase border-bottom pb-2 mb-3">
                    <i class="bx bx-wallet me-1"></i> Mauzo
                </h6>
                @include('purchases.daily-accounts.partials.report-amount-section', [
                    'lines' => $mauzo_lines,
                    'total' => $mauzo_total,
                    'amountLabel' => 'Kiasi',
                    'emptyMessage' => 'Hakuna mauzo kwa siku hii.',
                ])

                {{-- Matumizi --}}
                <h6 class="text-warning text-uppercase border-bottom pb-2 mb-3 mt-4">
                    <i class="bx bx-receipt me-1"></i> Matumizi
                </h6>
                @include('purchases.daily-accounts.partials.report-amount-section', [
                    'lines' => $matumizi_lines,
                    'total' => $matumizi_total,
                    'amountLabel' => 'Kiasi',
                    'emptyMessage' => 'Hakuna matumizi kwa siku hii.',
                ])

                {{-- Manunuzi --}}
                <h6 class="text-secondary text-uppercase border-bottom pb-2 mb-3 mt-4">
                    <i class="bx bx-cart me-1"></i> Manunuzi
                </h6>
                @include('purchases.daily-accounts.partials.report-amount-section', [
                    'lines' => $manunuzi_lines,
                    'total' => $manunuzi_total,
                    'amountLabel' => 'Kiasi',
                    'emptyMessage' => 'Hakuna manunuzi kwa siku hii.',
                ])

                {{-- Baki --}}
                <div class="card bg-light border-0 mt-4 mb-4">
                    <div class="card-body py-3">
                        <div class="row g-2 small">
                            <div class="col-sm-4 d-flex justify-content-between">
                                <span>Jumla ya Mauzo</span>
                                <span class="fw-semibold">{{ format_currency($mauzo_total) }}</span>
                            </div>
                            <div class="col-sm-4 d-flex justify-content-between">
                                <span>Matumizi + Manunuzi</span>
                                <span class="fw-semibold">{{ format_currency($matumizi_manunuzi_total) }}</span>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-uppercase">Baki (Mauzo − Matumizi − Manunuzi)</span>
                            <span class="fs-5 fw-bold {{ $baki >= 0 ? 'text-success' : 'text-danger' }}">{{ format_currency($baki) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Stoo --}}
                <h6 class="text-info text-uppercase border-bottom pb-2 mb-3">
                    <i class="bx bx-package me-1"></i> Taarifa ya stoo
                </h6>

                @if(empty($stoo_groups))
                    <p class="text-muted small mb-0">Hakuna taarifa za stoo kwa siku hii.</p>
                @else
                    @foreach($stoo_groups as $group)
                        <div class="mb-3">
                            <p class="fw-bold mb-2"><i class="bx bx-box me-1"></i> {{ $group['bidhaa'] }}</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Maelezo</th>
                                            <th class="text-end" style="width: 160px">Thamani</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($group['lines'] as $line)
                                            <tr>
                                                <td>{{ $line['maelezo'] }}</td>
                                                <td class="text-end">{{ format_currency($line['thamani']) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-muted small">Hakuna mistari.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .page-wrapper { padding: 0; }
}
</style>
@endpush
