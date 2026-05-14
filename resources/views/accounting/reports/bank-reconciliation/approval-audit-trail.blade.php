@extends('layouts.main')

@section('title', 'Reconciliation Approval & Audit Trail Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => route('accounting.reports.bank-reconciliation-report.reports-index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Reconciliation Approval & Audit Trail', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />
        
        <h6 class="mb-0 text-uppercase">RECONCILIATION APPROVAL & AUDIT TRAIL — [Month/Year]</h6>
        <hr />

        <!-- Filters -->
        <div class="card radius-10 mb-4">
            <div class="card-header">
                <h6 class="mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('accounting.reports.bank-reconciliation-report.approval-audit-trail') }}">
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
                            <label class="form-label">Month</label>
                            <input type="month" name="month" class="form-control" value="{{ $month }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" class="form-control" value="{{ $year }}" min="2020" max="2099">
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
            <a href="{{ route('accounting.reports.bank-reconciliation-report.approval-audit-trail', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="btn btn-danger">
                <i class="bx bx-download me-2"></i>Export PDF
            </a>
        </div>

        <!-- Report Tables -->
        @forelse($auditTrails as $auditData)
        <div class="card radius-10 mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    Bank Account: {{ $auditData['reconciliation']->bankAccount->name }} 
                    ({{ $auditData['reconciliation']->reconciliation_date->format('M Y') }})
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Step</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>Timestamp</th>
                                <th>IP / Device</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($auditData['trail'] as $trail)
                            <tr>
                                <td>{{ $trail['step'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $trail['action'] === 'Approved' ? 'success' : ($trail['action'] === 'Rejected' ? 'danger' : 'info') }}">
                                        {{ $trail['action'] }}
                                    </span>
                                </td>
                                <td>{{ $trail['user'] }}</td>
                                <td>{{ $trail['timestamp']->format('d/m/Y H:i') }}</td>
                                <td>{{ $trail['ip_device'] }}</td>
                                <td>{{ $trail['notes'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @empty
        <div class="card radius-10">
            <div class="card-body text-center py-5">
                <i class="bx bx-info-circle fs-1 mb-2 text-muted"></i>
                <p class="text-muted">No audit trail data found</p>
            </div>
        </div>
        @endforelse
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

