@extends('layouts.main')

@section('title', 'Supplier advance statement — '.$supplier->name)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
                ['label' => 'Supplier Advances', 'url' => route('purchases.supplier-advances.index'), 'icon' => 'bx bx-wallet-alt'],
                ['label' => 'Statement', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Print
                </button>
                <a href="{{ route('purchases.supplier-advances.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
        </div>

        <div class="card radius-10 statement-card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h4 class="mb-1">Supplier advance statement</h4>
                    <p class="mb-0 fs-5 fw-semibold">{{ $supplier->name }}</p>
                    @if($supplier->tin_number)
                        <small class="text-muted">TIN: {{ $supplier->tin_number }}</small>
                    @endif
                    <p class="text-muted small mb-0 mt-2">Generated {{ now()->format('Y-m-d H:i') }}</p>
                </div>

                <div class="row text-center mb-4 border rounded py-3 bg-light">
                    <div class="col-md-4">
                        <div class="text-muted small">Total advances</div>
                        <div class="fs-5 fw-bold">{{ format_currency($totals['advances']) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Applied</div>
                        <div class="fs-5 fw-bold">{{ format_currency($totals['applied']) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Balance</div>
                        <div class="fs-5 fw-bold text-primary">{{ format_currency($totals['balance']) }}</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-end">Advance (+)</th>
                                <th class="text-end">Applied (−)</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $line)
                                <tr>
                                    <td>{{ $line['date']->format('Y-m-d') }}</td>
                                    <td>{{ $line['type'] === 'advance' ? 'Advance' : 'Applied' }}</td>
                                    <td>{{ $line['reference'] }}</td>
                                    <td>{{ $line['description'] }}</td>
                                    <td class="text-end">{{ $line['debit'] > 0 ? format_currency($line['debit']) : '—' }}</td>
                                    <td class="text-end">{{ $line['credit'] > 0 ? format_currency($line['credit']) : '—' }}</td>
                                    <td class="text-end fw-semibold">{{ format_currency($line['balance']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No movements on file for this supplier in the current branch scope.</td>
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
