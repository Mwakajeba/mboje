@extends('layouts.main')

@section('title', 'Maelezo ya Akaunti ya Mkopo')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Akaunti za Mikopo', 'url' => route('cash_collateral_types.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => $cashCollateralType->name, 'url' => '#', 'icon' => 'bx bx-info-circle']
        ]" />
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 text-dark fw-bold">
                        <i class="bx bx-bookmark me-2 text-primary"></i>
                        Maelezo ya Akaunti ya Mkopo
                    </h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('cash_collateral_types.edit', $cashCollateralType->id) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i> Badili
                        </a>
                        <a href="{{ route('cash_collateral_types.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Rudi kwa Orodha
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i> Taarifa za Akaunti</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Jina</small>
                                <h6 class="fw-bold text-dark">{{ $cashCollateralType->name }}</h6>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted d-block">Hali</small>
                                <span class="badge {{ $cashCollateralType->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $cashCollateralType->is_active ? 'Hai' : 'Haifanyi kazi' }}
                                </span>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted d-block">Akaunti ya Hesabu</small>
                                @if($cashCollateralType->chartAccount)
                                    <h6 class="fw-bold text-dark">
                                        {{ $cashCollateralType->chartAccount->account_name }}
                                        <span class="text-muted">({{ $cashCollateralType->chartAccount->account_code }})</span>
                                    </h6>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>

                            <div class="col-12">
                                <small class="text-muted d-block">Maelezo</small>
                                <p class="text-dark">{{ $cashCollateralType->description ?? '—' }}</p>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted d-block">Tarehe ya Kusajili</small>
                                <h6 class="fw-bold text-dark">{{ $cashCollateralType->created_at->format('Y-m-d') }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-cog me-2 text-muted"></i> Vitendo vya Haraka</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-grid gap-2">
                            <a href="{{ route('cash_collateral_types.edit', $cashCollateralType->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bx bx-edit me-1"></i> Badili
                            </a>
                            <form action="{{ route('cash_collateral_types.destroy', $cashCollateralType->id) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100" data-name="{{ $cashCollateralType->name }}">
                                    <i class="bx bx-trash me-1"></i> Futa
                                </button>
                            </form>
                            <a href="{{ route('cash_collateral_types.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bx bx-arrow-back me-1"></i> Rudi kwa Orodha
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
