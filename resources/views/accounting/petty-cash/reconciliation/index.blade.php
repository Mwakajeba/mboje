@extends('layouts.main')

@section('title', 'Petty Cash Reconciliation Report')

@push('styles')
<style>
    .reconciliation-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    
    .reconciliation-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .reconciliation-card.border-primary { border-left-color: #0d6efd; }
    .reconciliation-card.border-success { border-left-color: #198754; }
    .reconciliation-card.border-danger { border-left-color: #dc3545; }
    .reconciliation-card.border-warning { border-left-color: #ffc107; }
    .reconciliation-card.border-info { border-left-color: #0dcaf0; }
    
    .variance-positive {
        color: #198754;
        font-weight: bold;
    }
    
    .variance-negative {
        color: #dc3545;
        font-weight: bold;
    }
    
    .variance-zero {
        color: #6c757d;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Petty Cash Units', 'url' => route('accounting.petty-cash.units.index'), 'icon' => 'bx bx-wallet'],
            ['label' => 'Reconciliation Report', 'url' => '#', 'icon' => 'bx bx-check-square']
        ]" />

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Petty Cash Reconciliation Report</h4>
                        <p class="text-muted mb-0">View reconciliation status for all petty cash units</p>
                    </div>
                    <div class="page-title-right d-flex gap-2">
                        <a href="{{ route('accounting.petty-cash.reconciliation.export.pdf') }}?{{ http_build_query(request()->query()) }}" 
                           class="btn btn-danger" target="_blank">
                            <i class="bx bx-file-blank me-1"></i>Export PDF
                        </a>
                        <a href="{{ route('accounting.petty-cash.reconciliation.export.excel') }}?{{ http_build_query(request()->query()) }}" 
                           class="btn btn-success">
                            <i class="bx bx-spreadsheet me-1"></i>Export Excel
                        </a>
                        <a href="{{ route('accounting.petty-cash.units.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Units
                        </a>
                    </div>
                </div>
                <hr>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('accounting.petty-cash.reconciliation.index') }}" id="filterForm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">As of Date</label>
                                    <input type="date" 
                                           class="form-control" 
                                           name="as_of_date" 
                                           value="{{ request('as_of_date', now()->toDateString()) }}"
                                           onchange="document.getElementById('filterForm').submit()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Branch</label>
                                    <select class="form-select select2-single" 
                                            name="branch_id" 
                                            onchange="document.getElementById('filterForm').submit()">
                                        <option value="">All Branches</option>
                                        @foreach($branches ?? [] as $branch)
                                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" 
                                            name="status" 
                                            onchange="document.getElementById('filterForm').submit()">
                                        <option value="">All Status</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bx bx-search me-1"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card reconciliation-card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Units</h6>
                        <h4 class="mb-0">{{ $units->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card reconciliation-card border-info">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Float Amount</h6>
                        <h4 class="mb-0">TZS {{ number_format($units->sum('float_amount'), 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card reconciliation-card border-success">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total System Balance</h6>
                        <h4 class="mb-0">TZS {{ number_format($units->sum('current_balance'), 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card reconciliation-card border-warning">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Variance</h6>
                        <h4 class="mb-0" id="total-variance">
                            @php
                                $totalVariance = 0;
                                foreach($units as $unit) {
                                    $recon = \App\Services\PettyCashModeService::getReconciliationSummary($unit, request('as_of_date', now()->toDateString()));
                                    $totalVariance += $recon['variance'];
                                }
                            @endphp
                            <span class="{{ $totalVariance > 0 ? 'variance-positive' : ($totalVariance < 0 ? 'variance-negative' : 'variance-zero') }}">
                                TZS {{ number_format($totalVariance, 2) }}
                            </span>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reconciliation Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-table me-2"></i>Reconciliation Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="reconciliation-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Unit Code</th>
                                        <th>Unit Name</th>
                                        <th>Branch</th>
                                        <th>Custodian</th>
                                        <th class="text-end">Opening Balance</th>
                                        <th class="text-end">Total Disbursed</th>
                                        <th class="text-end">Total Replenished</th>
                                        <th class="text-end">Closing Cash</th>
                                        <th class="text-end">System Balance</th>
                                        <th class="text-end">Variance</th>
                                        <th class="text-center">Outstanding</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($units as $unit)
                                        @php
                                            $reconciliation = \App\Services\PettyCashModeService::getReconciliationSummary($unit, request('as_of_date', now()->toDateString()));
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $unit->code }}</strong></td>
                                            <td>{{ $unit->name }}</td>
                                            <td>{{ $unit->branch->name ?? 'N/A' }}</td>
                                            <td>{{ $unit->custodian->name ?? 'N/A' }}</td>
                                            <td class="text-end">TZS {{ number_format($reconciliation['opening_balance'], 2) }}</td>
                                            <td class="text-end text-danger">TZS {{ number_format($reconciliation['total_disbursed'], 2) }}</td>
                                            <td class="text-end text-success">TZS {{ number_format($reconciliation['total_replenished'], 2) }}</td>
                                            <td class="text-end"><strong>TZS {{ number_format($reconciliation['closing_cash'], 2) }}</strong></td>
                                            <td class="text-end">TZS {{ number_format($reconciliation['system_balance'], 2) }}</td>
                                            <td class="text-end">
                                                <span class="{{ $reconciliation['variance'] > 0 ? 'variance-positive' : ($reconciliation['variance'] < 0 ? 'variance-negative' : 'variance-zero') }}">
                                                    TZS {{ number_format($reconciliation['variance'], 2) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $outstanding = \App\Models\PettyCash\PettyCashRegister::where('petty_cash_unit_id', $unit->id)
                                                        ->where('entry_type', 'disbursement')
                                                        ->where('status', '!=', 'posted')
                                                        ->where('register_date', '<=', request('as_of_date', now()->toDateString()))
                                                        ->count();
                                                @endphp
                                                @if($outstanding > 0)
                                                    <span class="badge bg-warning">{{ $outstanding }}</span>
                                                @else
                                                    <span class="badge bg-success">0</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('accounting.petty-cash.register.reconciliation', $unit->encoded_id) }}?as_of_date={{ request('as_of_date', now()->toDateString()) }}" 
                                                   class="btn btn-sm btn-info" title="View Reconciliation">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    $('#reconciliation-table').DataTable({
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...',
            emptyTable: 'No reconciliation data found',
            zeroRecords: 'No matching records found'
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    });
    
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush


