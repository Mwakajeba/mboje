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

        <div class="card radius-10 statement-card">
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

                <div class="row text-center mb-4 border rounded py-3 bg-light g-2">
                    @if(!empty($period) && isset($totals['opening_balance']))
                        <div class="col-md-3 col-6">
                            <div class="text-muted small">Salio la kufungua</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['opening_balance']) }}</div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-muted small">Malipo ya awali (kipindi)</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['advances']) }}</div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-muted small">Matumizi (kipindi)</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['applied']) }}</div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-muted small">Salio la kufunga</div>
                            <div class="fs-5 fw-bold text-primary">{{ format_currency($totals['closing_balance'] ?? $totals['balance']) }}</div>
                        </div>
                    @else
                        <div class="col-md-4">
                            <div class="text-muted small">Jumla ya malipo ya awali</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['advances']) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Matumizi</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['applied']) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Salio</div>
                            <div class="fs-5 fw-bold text-primary">{{ format_currency($totals['balance']) }}</div>
                        </div>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Maelezo</th>
                                <th>Aliyeingiza</th>
                                <th class="text-end">Malipo</th>
                                <th class="text-end">Matumizi</th>
                                <th class="text-end">Salio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $line)
                                <tr class="{{ !empty($line['is_opening']) || !empty($line['is_closing']) ? 'table-secondary fw-semibold' : '' }}">
                                    <td>{{ $line['date']->format('Y-m-d') }}</td>
                                    <td>{{ $line['description'] }}</td>
                                    <td>{{ $line['performed_by'] ?? '—' }}</td>
                                    <td class="text-end">{{ ($line['debit'] ?? $line['paid'] ?? 0) > 0 ? format_currency($line['debit'] ?? $line['paid']) : '—' }}</td>
                                    <td class="text-end">{{ ($line['credit'] ?? $line['deducted'] ?? 0) > 0 ? format_currency($line['credit'] ?? $line['deducted']) : '—' }}</td>
                                    <td class="text-end fw-semibold">{{ format_currency($line['balance']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Hakuna miamala kwa msambazaji huyu katika tawi hili.</td>
                                </tr>
                            @endforelse
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
</style>
@endpush
@endsection
