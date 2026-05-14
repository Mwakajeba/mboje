@extends('layouts.main')

@section('title', 'Cleared Transactions Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => route('accounting.reports.bank-reconciliation-report.reports-index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Cleared Transactions Report', 'url' => '#', 'icon' => 'bx bx-check-circle']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CLEARED TRANSACTIONS REPORT</h6>
        <p class="text-muted mb-3">This report shows items that were outstanding in the previous month's bank reconciliation but have been cleared in the current month.</p>
        <hr />

        <!-- Filters -->
        <div class="card radius-10 mb-4">
            <div class="card-header">
                <h6 class="mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('accounting.reports.bank-reconciliation-report.cleared-transactions') }}">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Bank Account</label>
                            <select name="bank_account_id" id="bank_account_id" class="form-select select2-single">
                                <option value="">All Bank Accounts</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ $selectedBankAccount && $selectedBankAccount->id == $account->id ? 'selected' : '' }}>
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

        @if($selectedBankAccount)
        <div class="alert alert-info">
            <strong>Bank Account:</strong> {{ $selectedBankAccount->name }} ({{ $selectedBankAccount->account_number }})
        </div>
        @endif

        <!-- Export Button -->
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('accounting.reports.bank-reconciliation-report.cleared-transactions', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="btn btn-danger">
                <i class="bx bx-download me-2"></i>Export PDF
            </a>
        </div>

        <!-- Report Table -->
        <div class="card radius-10">
            <div class="card-header">
                <h6 class="mb-0">Cleared Transactions Report</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Transaction Date</th>
                                <th>Item Type</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th class="text-end">Amount</th>
                                <th>Origin Month</th>
                                <th>Clearing Month</th>
                                <th>Cleared Date</th>
                                <th>Age at Clearing</th>
                                <th>Cleared By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportItems as $row)
                            <tr>
                                <td>{{ $row['date']->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $row['item_type'] === 'DNC' ? 'success' : ($row['item_type'] === 'UPC' ? 'danger' : 'secondary') }}">
                                        {{ $row['item_type'] }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($row['description'], 50) }}</td>
                                <td>{{ $row['reference'] }}</td>
                                <td class="text-end {{ $row['amount'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format(abs($row['amount']), 2) }}
                                </td>
                                <td>{{ $row['origin_month'] }}</td>
                                <td>{{ $row['clearing_month'] }}</td>
                                <td>{{ $row['cleared_date'] }}</td>
                                <td>
                                    @if($row['age_days'] > 0)
                                        {{ $row['age_days'] }} days
                                        @if($row['age_months'] >= 1)
                                            ({{ number_format($row['age_months'], 1) }} months)
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $row['cleared_by'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="bx bx-check-circle fs-1 mb-2"></i>
                                    <p>No cleared transactions found for the selected period</p>
                                    <small>This report shows items that were outstanding in the previous month but cleared in the current month.</small>
                                </td>
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

