@extends('layouts.main')

@section('title', 'Bank Reconciliation Exception Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => route('accounting.reports.bank-reconciliation-report.reports-index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Bank Reconciliation Exception Report', 'url' => '#', 'icon' => 'bx bx-error-circle']
        ]" />
        
        <h6 class="mb-0 text-uppercase">BANK RECONCILIATION EXCEPTION REPORT (items uncleared for >15 days)</h6>
        <hr />

        <!-- Filters -->
        <div class="card radius-10 mb-4">
            <div class="card-header">
                <h6 class="mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('accounting.reports.bank-reconciliation-report.exception') }}">
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
                        <div class="col-md-4">
                            <label class="form-label">Severity</label>
                            <select name="severity" class="form-select">
                                <option value="">All Severities</option>
                                <option value="high" {{ $severity === 'high' ? 'selected' : '' }}>High</option>
                                <option value="medium" {{ $severity === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="low" {{ $severity === 'low' ? 'selected' : '' }}>Low</option>
                            </select>
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

        @if($selectedBankAccount)
        <div class="alert alert-info">
            <strong>Bank Account:</strong> {{ $selectedBankAccount->name }} ({{ $selectedBankAccount->account_number }})
        </div>
        @endif

        <!-- Export Button -->
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('accounting.reports.bank-reconciliation-report.exception', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="btn btn-danger">
                <i class="bx bx-download me-2"></i>Export PDF
            </a>
        </div>

        <!-- Report Table -->
        <div class="card radius-10">
            <div class="card-header">
                <h6 class="mb-0">Bank Reconciliation Exception Report</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Issue Type</th>
                                <th>Description</th>
                                <th>Transaction</th>
                                <th class="text-end">Amount</th>
                                <th>Detected On</th>
                                <th>Severity</th>
                                <th>Suggested Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exceptions as $exception)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $exception['issue_type'] === 'Cash Book Only' ? 'warning' : 'info' }}">
                                        {{ $exception['issue_type'] }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($exception['description'], 50) }}</td>
                                <td>{{ $exception['transaction'] }}</td>
                                <td class="text-end {{ $exception['amount'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format(abs($exception['amount']), 2) }}
                                </td>
                                <td>{{ $exception['detected_on'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $exception['severity'] === 'High' ? 'danger' : ($exception['severity'] === 'Medium' ? 'warning' : 'info') }}">
                                        {{ $exception['severity'] }}
                                    </span>
                                </td>
                                <td>{{ $exception['suggested_action'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bx bx-check-circle fs-1 mb-2"></i>
                                    <p>No exceptions found</p>
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

