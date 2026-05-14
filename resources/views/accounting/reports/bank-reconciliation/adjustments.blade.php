@extends('layouts.main')

@section('title', 'Bank Reconciliation Adjustments Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => route('accounting.reports.bank-reconciliation-report.reports-index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Bank Reconciliation Adjustments', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <h6 class="mb-0 text-uppercase">BANK RECONCILIATION ADJUSTMENTS — AUTO JOURNAL ENTRIES</h6>
        <hr />

        <!-- Filters -->
        <div class="card radius-10 mb-4">
            <div class="card-header">
                <h6 class="mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('accounting.reports.bank-reconciliation-report.adjustments') }}">
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
            <a href="{{ route('accounting.reports.bank-reconciliation-report.adjustments', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="btn btn-danger">
                <i class="bx bx-download me-2"></i>Export PDF
            </a>
        </div>

        <!-- Report Table -->
        <div class="card radius-10">
            <div class="card-header">
                <h6 class="mb-0">Bank Reconciliation Adjustments — Auto Journal Entries</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Journal No</th>
                                <th>Type</th>
                                <th>Debit Account</th>
                                <th>Credit Account</th>
                                <th class="text-end">Amount</th>
                                <th>Description</th>
                                <th>Posted By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustmentTransactions as $adj)
                            <tr>
                                <td>{{ $adj['date'] }}</td>
                                <td>{{ $adj['journal_no'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $adj['type'] === 'Interest Income' ? 'success' : 'warning' }}">
                                        {{ $adj['type'] }}
                                    </span>
                                </td>
                                <td>{{ $adj['debit_account'] }}</td>
                                <td>{{ $adj['credit_account'] }}</td>
                                <td class="text-end">{{ number_format($adj['amount'], 2) }}</td>
                                <td>{{ Str::limit($adj['description'], 50) }}</td>
                                <td>{{ $adj['posted_by'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bx bx-info-circle fs-1 mb-2"></i>
                                    <p>No adjustment journal entries found</p>
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

