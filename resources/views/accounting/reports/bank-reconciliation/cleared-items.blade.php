@extends('layouts.main')

@section('title', 'Cleared Items from Previous Month')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => route('accounting.reports.bank-reconciliation-report.reports-index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Cleared Items from Previous Month', 'url' => '#', 'icon' => 'bx bx-check-circle']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CLEARED ITEMS FROM PREVIOUS MONTH</h6>
        <hr />

        <!-- Filters -->
        <div class="card radius-10 mb-4">
            <div class="card-header">
                <h6 class="mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('accounting.reports.bank-reconciliation-report.cleared-items') }}">
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
                        <div class="col-md-4">
                            <label class="form-label">Clearing Month</label>
                            <input type="month" name="clearing_month" class="form-control" value="{{ $clearingMonth }}">
                        </div>
                        <div class="col-md-4">
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
                                <p class="mb-1 text-secondary">Total Cleared Items</p>
                                <h4 class="my-1 text-success">{{ $items->count() }}</h4>
                            </div>
                            <div class="ms-auto fs-1 text-success">
                                <i class="bx bx-check-circle"></i>
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
                                <p class="mb-1 text-secondary">DNC Items Cleared</p>
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
                                <p class="mb-1 text-secondary">UPC Items Cleared</p>
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
                                <p class="mb-1 text-secondary">Total Amount Cleared</p>
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
            <a href="{{ route('accounting.reports.bank-reconciliation-report.cleared-items', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="btn btn-danger">
                <i class="bx bx-download me-2"></i>Export PDF
            </a>
        </div>

        <!-- Report Table -->
        <div class="card radius-10">
            <div class="card-header">
                <h6 class="mb-0">Cleared Items from Previous Month</h6>
                <small class="text-muted">Shows items that were outstanding in previous months and cleared in {{ date('F Y', strtotime($clearingMonth . '-01')) }}</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Ref</th>
                                <th>Amount (TZS)</th>
                                <th>GL Date</th>
                                <th>Bank Clear Date</th>
                                <th>Clearing Month</th>
                                <th>Age</th>
                                <th>Cleared By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $item->item_type === 'DNC' ? 'success' : 'danger' }}">
                                        {{ $item->item_type }}
                                    </span>
                                </td>
                                <td>{{ $item->reference ?? 'N/A' }}</td>
                                <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                <td>{{ $item->transaction_date->format('d-M-Y') }}</td>
                                <td>{{ $item->clearing_date ? $item->clearing_date->format('d-M-Y') : 'N/A' }}</td>
                                <td>{{ $item->clearing_month ? $item->clearing_month->format('M Y') : 'N/A' }}</td>
                                <td>
                                    @if(isset($item->age_at_clearing_days))
                                        {{ $item->age_at_clearing_days }} days
                                        @if($item->age_at_clearing_months >= 1)
                                            ({{ number_format($item->age_at_clearing_months, 1) }} months)
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $item->clearedBy->name ?? 'System' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bx bx-info-circle fs-1 mb-2"></i>
                                    <p>No cleared items found for {{ date('F Y', strtotime($clearingMonth . '-01')) }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($items->count() > 0)
                        <tfoot>
                            <tr class="table-secondary">
                                <td colspan="2" class="text-end fw-bold">Total:</td>
                                <td class="text-end fw-bold">{{ number_format($items->sum('amount'), 2) }}</td>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Purpose Note -->
                <div class="alert alert-info mt-3 mb-0">
                    <strong>Purpose:</strong> Transparency, for auditors, for CFO monthly review
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

