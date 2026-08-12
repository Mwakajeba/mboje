@extends('layouts.main')

@section('title', __('app.dashboard'))

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="mb-0">Muhtasari wa Salio</h4>
                <p class="text-muted small mb-0">Wafanyakazi, Wamachinga, Stoo na Safari za Madereva</p>
            </div>
            @if(isset($branches) && $branches->count() > 1)
            <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2">
                <label for="branch_id" class="form-label mb-0 small text-muted">Tawi</label>
                <select name="branch_id" id="branch_id" class="form-select form-select-sm" style="min-width: 180px" onchange="this.form.submit()">
                    <option value="">Matawi yote</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) ($selectedBranchId ?? '') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </form>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <a href="{{ route('purchases.daily-accounts.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 radius-10 dash-summary-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="text-muted small text-uppercase">Wafanyakazi</div>
                                    <div class="text-muted" style="font-size: 12px">Salio (Baki)</div>
                                </div>
                                <span class="dash-icon bg-success-subtle text-success">
                                    <i class="bx bx-group"></i>
                                </span>
                            </div>
                            <div class="fs-4 fw-bold {{ ($wafanyakaziSalio ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($wafanyakaziSalio ?? 0, 2) }}
                            </div>
                            <div class="mt-3 small text-primary">
                                Fungua Hesabu za Kila Siku <i class="bx bx-right-arrow-alt"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('purchases.supplier-advances.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 radius-10 dash-summary-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="text-muted small text-uppercase">Wamachinga</div>
                                    <div class="text-muted" style="font-size: 12px">Salio (Malipo ya Awali)</div>
                                </div>
                                <span class="dash-icon bg-warning-subtle text-warning">
                                    <i class="bx bx-store"></i>
                                </span>
                            </div>
                            <div class="fs-4 fw-bold {{ ($wamachingaSalio ?? 0) >= 0 ? 'text-dark' : 'text-danger' }}">
                                {{ number_format($wamachingaSalio ?? 0, 2) }}
                            </div>
                            <div class="mt-3 small text-primary">
                                Fungua Malipo ya Awali <i class="bx bx-right-arrow-alt"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('inventory.customer-storage.report') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 radius-10 dash-summary-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="text-muted small text-uppercase">Stoo ya Muda Mfupi (Wateja)</div>
                                    <div class="text-muted" style="font-size: 12px">Salio (Mapato − Gharama − Malipo)</div>
                                </div>
                                <span class="dash-icon bg-info-subtle text-info">
                                    <i class="bx bx-user-pin"></i>
                                </span>
                            </div>
                            <div class="fs-4 fw-bold {{ ($watejaSalio ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($watejaSalio ?? 0, 2) }}
                            </div>
                            <div class="mt-2 pt-2 border-top">
                                <div class="text-muted small">Jumla ya Bidhaa (Stoo)</div>
                                <div class="fw-semibold text-dark lh-sm small">
                                    {{ $watejaStooDisplay ?? '0' }}
                                </div>
                                @if(!empty($watejaStooBreakdown) && count($watejaStooBreakdown) > 1)
                                    <div class="mt-1 small text-muted">
                                        @foreach(array_slice($watejaStooBreakdown, 0, 2) as $row)
                                            <div>{{ $row['item_name'] }}: {{ $row['summary'] }}</div>
                                        @endforeach
                                        @if(count($watejaStooBreakdown) > 2)
                                            <div>+{{ count($watejaStooBreakdown) - 2 }} zaidi…</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="mt-3 small text-primary">
                                Fungua Ripoti ya Stoo ya Muda Mfupi <i class="bx bx-right-arrow-alt"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('inventory.permanent-storage.report') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 radius-10 dash-summary-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="text-muted small text-uppercase">Stoo ya Kudumu</div>
                                    <div class="text-muted" style="font-size: 12px">Salio (Mapato − Gharama − Malipo)</div>
                                </div>
                                <span class="dash-icon bg-dark-subtle text-dark">
                                    <i class="bx bx-building-house"></i>
                                </span>
                            </div>
                            <div class="fs-4 fw-bold {{ ($stooKudumuSalio ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($stooKudumuSalio ?? 0, 2) }}
                            </div>
                            <div class="mt-2 pt-2 border-top">
                                <div class="text-muted small">Jumla ya Bidhaa (Stoo)</div>
                                <div class="fw-semibold text-dark lh-sm small">
                                    {{ $stooKudumuDisplay ?? '0' }}
                                </div>
                                @if(!empty($stooKudumuBreakdown) && count($stooKudumuBreakdown) > 1)
                                    <div class="mt-1 small text-muted">
                                        @foreach(array_slice($stooKudumuBreakdown, 0, 2) as $row)
                                            <div>{{ $row['item_name'] }}: {{ $row['summary'] }}</div>
                                        @endforeach
                                        @if(count($stooKudumuBreakdown) > 2)
                                            <div>+{{ count($stooKudumuBreakdown) - 2 }} zaidi…</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="mt-3 small text-primary">
                                Fungua Ripoti ya Stoo ya Kudumu <i class="bx bx-right-arrow-alt"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <a href="{{ route('purchases.driver-trips.index') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 radius-10 dash-summary-card dash-link-card" style="border-left: 4px solid #0d6efd !important">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase">Safari za Madereva</div>
                                <div class="fs-5 fw-bold text-primary mt-1">Fungua Safari</div>
                                <div class="small text-muted mt-1">Sajili safari, mapato na matumizi ya madereva</div>
                                <div class="small text-primary mt-2">Ingia sasa <i class="bx bx-right-arrow-alt"></i></div>
                            </div>
                            <span class="dash-icon" style="background: #e7f1ff; color: #0d6efd"><i class="bx bx-car"></i></span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <a href="{{ route('inventory.customer-storage.index', ['status' => 'inactive']) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 radius-10" style="border-left: 4px solid #6c757d !important">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase">Stoo ya Muda Mfupi — Imeisha</div>
                                <div class="fs-3 fw-bold text-secondary">{{ (int) ($watejaImeishaCount ?? 0) }}</div>
                                <div class="small text-primary mt-1">Angalia na urudishe Inaendelea <i class="bx bx-right-arrow-alt"></i></div>
                            </div>
                            <span class="dash-icon bg-dark-subtle text-secondary"><i class="bx bx-archive"></i></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ route('inventory.permanent-storage.index', ['status' => 'inactive']) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 radius-10" style="border-left: 4px solid #212529 !important">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small text-uppercase">Stoo ya Kudumu — Imeisha</div>
                                <div class="fs-3 fw-bold text-dark">{{ (int) ($kudumuImeishaCount ?? 0) }}</div>
                                <div class="small text-primary mt-1">Angalia na urudishe Inaendelea <i class="bx bx-right-arrow-alt"></i></div>
                            </div>
                            <span class="dash-icon bg-dark-subtle text-dark"><i class="bx bx-archive"></i></span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .dash-summary-card {
        transition: transform .15s ease, box-shadow .15s ease;
        border-top: 3px solid transparent !important;
    }
    .dash-summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(0,0,0,.08) !important;
    }
    .col-xl-3:nth-child(1) .dash-summary-card { border-top-color: #198754 !important; }
    .col-xl-3:nth-child(2) .dash-summary-card { border-top-color: #ffc107 !important; }
    .col-xl-3:nth-child(3) .dash-summary-card { border-top-color: #0dcaf0 !important; }
    .col-xl-3:nth-child(4) .dash-summary-card { border-top-color: #212529 !important; }
    .dash-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }
    .bg-success-subtle { background: #e8f6ee; }
    .bg-warning-subtle { background: #fff6db; }
    .bg-info-subtle { background: #e7f8fc; }
    .bg-dark-subtle { background: #ececec; }
    .radius-10 { border-radius: 10px; }
</style>
@endpush
