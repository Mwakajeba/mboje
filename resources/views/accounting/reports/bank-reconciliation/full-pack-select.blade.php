@extends('layouts.main')

@section('title', 'Full Bank Reconciliation Pack')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => route('accounting.reports.bank-reconciliation-report.reports-index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Full Bank Reconciliation Pack', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        
        <h6 class="mb-0 text-uppercase">FULL BANK RECONCILIATION PACK</h6>
        <hr />

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card radius-10">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-package me-2"></i>Select Reconciliation for Full Pack</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Select a bank account or specific reconciliation to generate the complete PDF bundle combining all reconciliation reports for auditors.
                        </p>

                        <form method="POST" action="{{ route('accounting.reports.bank-reconciliation-report.full-pack-download') }}" id="packForm">
                            @csrf
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Bank Account</label>
                                <select name="bank_account_id" id="bank_account_id" class="form-select select2-single">
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->name }} - {{ $account->account_number }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">The latest reconciliation for the selected bank account will be used.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">OR Select Specific Reconciliation</label>
                                <select name="reconciliation_id" id="reconciliation_id" class="form-select select2-single">
                                    <option value="">Select Reconciliation (Optional)</option>
                                    @foreach($reconciliations as $reconciliation)
                                        <option value="{{ $reconciliation->id }}" data-bank-account="{{ $reconciliation->bank_account_id }}">
                                            {{ $reconciliation->bankAccount->name }} - {{ $reconciliation->reconciliation_date->format('M d, Y') }} ({{ ucfirst($reconciliation->status) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">If selected, this specific reconciliation will be used instead of the latest one.</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-download me-2"></i>Generate & Download Pack
                                </button>
                                <a href="{{ route('accounting.reports.bank-reconciliation-report.reports-index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-2"></i>Back to Reports
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card radius-10 mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>What's Included in the Pack</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-2"></i>Bank Reconciliation Statement</li>
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-2"></i>Cleared Items Report</li>
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-2"></i>Unreconciled Items Aging</li>
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-2"></i>Adjustments Journal</li>
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-2"></i>Exceptions Report</li>
                            <li class="mb-0"><i class="bx bx-check-circle text-success me-2"></i>Audit Trail Report</li>
                        </ul>
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
    // Initialize Select2 for bank account
    $('#bank_account_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select Bank Account',
        allowClear: true
    });

    // Initialize Select2 for reconciliation
    $('#reconciliation_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select Reconciliation (Optional)',
        allowClear: true
    });

    // When reconciliation is selected, clear bank account
    $('#reconciliation_id').on('change', function() {
        if ($(this).val()) {
            $('#bank_account_id').val('').trigger('change');
        }
    });

    // When bank account is selected, clear reconciliation
    $('#bank_account_id').on('change', function() {
        if ($(this).val()) {
            $('#reconciliation_id').val('').trigger('change');
        }
    });

    // Form validation
    $('#packForm').on('submit', function(e) {
        const bankAccount = $('#bank_account_id').val();
        const reconciliation = $('#reconciliation_id').val();
        
        if (!bankAccount && !reconciliation) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Selection Required',
                text: 'Please select either a bank account or a specific reconciliation.'
            });
            return false;
        }
    });
});
</script>
@endpush

