@extends('layouts.main')

@section('title', 'Ripoti ya Uhifadhi wa Wateja')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Ripoti ya Uhifadhi wa Wateja', 'url' => '#', 'icon' => 'bx bx-bar-chart-alt-2']
            ]" />
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('inventory.customer-storage.report.pdf') }}" class="btn btn-danger btn-sm" target="_blank">
                    <i class="bx bx-file-pdf me-1"></i> Export PDF
                </a>
                <a href="{{ route('inventory.customer-storage.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-archive-in me-1"></i> Simamia Uhifadhi
                </a>
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i> Rudi
                </a>
            </div>
        </div>

        <h6 class="mb-0 text-uppercase">Ripoti ya Uhifadhi wa Wateja</h6>
        <hr>

        {{-- Muhtasari --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card border-info h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small text-uppercase mb-1">Jumla ya Stoo ya Mazao</div>
                        <div class="fs-3 fw-bold text-info">{{ $total_quantity_display }}</div>
                        @if(!empty($stock_summary))
                        <div class="mt-3 text-start small">
                            @foreach($stock_summary as $row)
                            <div class="d-flex justify-content-between border-bottom py-1">
                                <span>{{ $row['item_name'] }}</span>
                                <span class="fw-semibold">{{ $row['summary_display'] }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card border-primary h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small text-uppercase mb-1">Jumla ya Gharama Zinazodaiwa (Salio)</div>
                        <div class="fs-3 fw-bold {{ $total_salio >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($total_salio, 2) }}
                        </div>
                        <p class="text-muted small mb-0 mt-2">
                            Mapato/Mauzo {{ number_format($total_mapato, 2) }}
                            − Gharama {{ number_format($total_gharama, 2) }}
                            − Malipo {{ number_format($total_malipo, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Orodha ya wateja --}}
        <div class="card radius-10">
            <div class="card-header bg-transparent">
                <h6 class="mb-0 text-uppercase">Orodha ya Wateja</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 48px">#</th>
                                <th>Jina la Mteja</th>
                                <th>Idadi / Kifurushi (Stoo)</th>
                                <th class="text-end">Kiasi cha Salio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $row['name'] }}
                                    @if(!empty($row['phone']))
                                        <div class="text-muted small">{{ $row['phone'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $row['quantity_display'] }}</td>
                                <td class="text-end fw-semibold {{ $row['salio'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($row['salio'], 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Hakuna wateja wenye uhifadhi wa kudumu.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($customers->isNotEmpty())
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td colspan="2" class="text-end">Jumla</td>
                                <td>{{ $total_quantity_display }}</td>
                                <td class="text-end {{ $total_salio >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($total_salio, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
