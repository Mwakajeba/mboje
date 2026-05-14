@extends('layouts.main')

@section('title', 'Uncleared Items Aging Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => route('accounting.reports.bank-reconciliation-report.reports-index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Uncleared Items Aging Report', 'url' => '#', 'icon' => 'bx bx-time']
        ]" />
        
        <h6 class="mb-0 text-uppercase">UNCLEARED ITEMS AGING REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card radius-10 mb-4">
            <div class="card-header">
                <h6 class="mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('accounting.reports.bank-reconciliation-report.uncleared-items-aging') }}">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Bank Account</label>
                            <select name="bank_account_id" id="bank_account_id" class="form-select select2-single">
                                <option value="">All Bank Accounts</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ $bankAccountId == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }} - {{ $account->account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row row-cols-1 row-cols-lg-4 mb-4">
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="">
                                <p class="mb-1 text-secondary">Total Uncleared Items</p>
                                <h4 class="my-1 text-dark">{{ $items->count() }}</h4>
                            </div>
                            <div class="ms-auto fs-1 text-warning">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="">
                                <p class="mb-1 text-secondary">DNC Items</p>
                                <h4 class="my-1 text-success">{{ $dncItems->count() }}</h4>
                                <small class="text-muted">Total: {{ number_format($dncItems->sum('amount'), 2) }}</small>
                            </div>
                            <div class="ms-auto fs-1 text-success">
                                <i class="bx bx-up-arrow-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="">
                                <p class="mb-1 text-secondary">UPC Items</p>
                                <h4 class="my-1 text-danger">{{ $upcItems->count() }}</h4>
                                <small class="text-muted">Total: {{ number_format($upcItems->sum('amount'), 2) }}</small>
                            </div>
                            <div class="ms-auto fs-1 text-danger">
                                <i class="bx bx-down-arrow-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="">
                                <p class="mb-1 text-secondary">Total Amount</p>
                                <h4 class="my-1 text-dark">{{ number_format($items->sum('amount'), 2) }}</h4>
                            </div>
                            <div class="ms-auto fs-1 text-primary">
                                <i class="bx bx-money"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Button -->
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('accounting.reports.bank-reconciliation-report.uncleared-items-aging', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="btn btn-danger">
                <i class="bx bx-download me-2"></i>Export PDF
            </a>
        </div>

        <!-- Report Table -->
        <div class="card radius-10">
            <div class="card-header">
                <h6 class="mb-0">Uncleared Items Aging Report</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Date in GL</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-end">Amount (TZS)</th>
                                <th>Age (days)</th>
                                <th>Age (months)</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            @php
                                $agingColor = $item->getAgingFlagColor();
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $item->item_type === 'DNC' ? 'success' : 'danger' }}">
                                        {{ $item->item_type }}
                                    </span>
                                </td>
                                <td>{{ $item->transaction_date->format('d-M-Y') }}</td>
                                <td>{{ $item->reference ?? 'N/A' }}</td>
                                <td>{{ Str::limit($item->description, 50) }}</td>
                                <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $agingColor }}">
                                        {{ $item->age_days }}
                                    </span>
                                </td>
                                <td>{{ number_format($item->age_months, 1) }}</td>
                                <td>
                                    <span class="badge bg-warning">Uncleared</span>
                                </td>
                                <td>
                                    @if($item->age_days >= 180)
                                        <span class="text-danger">Critical Alert - Possible fraud/stale</span>
                                    @elseif($item->age_days >= 90)
                                        <span class="text-danger">Red Flag - Long Outstanding</span>
                                    @elseif($item->age_days >= 60)
                                        <span class="text-warning">Orange - Needs Attention</span>
                                    @elseif($item->age_days >= 30)
                                        <span class="text-warning">Yellow Warning</span>
                                    @else
                                        <span class="text-muted">Normal</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bx bx-check-circle fs-1 mb-2"></i>
                                    <p>No uncleared items found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($items->count() > 0)
                        <tfoot>
                            <tr class="table-secondary">
                                <td colspan="4" class="text-end fw-bold">Total:</td>
                                <td class="text-end fw-bold">{{ number_format($items->sum('amount'), 2) }}</td>
                                <td colspan="4"></td>
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for bank account
    $('#bank_account_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select Bank Account',
        allowClear: true
    });
});
</script>
@endpush

