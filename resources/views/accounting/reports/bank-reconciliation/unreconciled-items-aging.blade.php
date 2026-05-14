@extends('layouts.main')

@section('title', 'Unreconciled Items Aging Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => route('accounting.reports.bank-reconciliation-report.reports-index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Unreconciled Items Aging Report', 'url' => '#', 'icon' => 'bx bx-time']
        ]" />
        
        <h6 class="mb-0 text-uppercase">UNRECONCILED ITEMS AGING REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card radius-10 mb-4">
            <div class="card-header">
                <h6 class="mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('accounting.reports.bank-reconciliation-report.unreconciled-items-aging') }}">
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
            <a href="{{ route('accounting.reports.bank-reconciliation-report.unreconciled-items-aging', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="btn btn-danger">
                <i class="bx bx-download me-2"></i>Export PDF
            </a>
        </div>

        <!-- Report Table -->
        <div class="card radius-10">
            <div class="card-header">
                <h6 class="mb-0">Unreconciled Items Aging Report</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th class="text-end">Cash Book Amount</th>
                                <th class="text-end">Bank Statement Amount</th>
                                <th>Type</th>
                                <th>Aging in Days</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportItems as $row)
                            <tr>
                                <td>{{ $row['date']->format('d/m/Y') }}</td>
                                <td>{{ Str::limit($row['description'], 50) }}</td>
                                <td>{{ $row['reference'] }}</td>
                                <td class="text-end">
                                    @if($row['cash_book_amount'] !== null)
                                        {{ number_format($row['cash_book_amount'], 2) }}
                                    @else
                                        –
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($row['bank_statement_amount'] !== null)
                                        {{ number_format($row['bank_statement_amount'], 2) }}
                                    @else
                                        –
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $row['type'] === 'Deposit' ? 'success' : 'danger' }}">
                                        {{ $row['type'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $row['aging_days'] >= 90 ? 'danger' : ($row['aging_days'] >= 30 ? 'warning' : 'info') }}">
                                        {{ $row['aging_days'] }} days
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-warning">{{ $row['status'] }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bx bx-check-circle fs-1 mb-2"></i>
                                    <p>No unreconciled items found</p>
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

