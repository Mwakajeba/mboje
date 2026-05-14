@extends('layouts.main')

@section('title', 'Edit Customer Deposit')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Customer Deposits', 'url' => route('rental-event-equipment.customer-deposits.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT CUSTOMER DEPOSIT</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="bx bx-money me-2"></i>Edit Deposit: {{ $deposit->deposit_number }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('rental-event-equipment.customer-deposits.update', $deposit) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $deposit->customer_id == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} - {{ $customer->phone }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contract_id" class="form-label">Contract (Optional)</label>
                                <select class="form-select select2-single" id="contract_id" name="contract_id">
                                    <option value="">Select Contract</option>
                                    @foreach($contracts as $contract)
                                        <option value="{{ $contract->id }}" {{ $deposit->contract_id == $contract->id ? 'selected' : '' }}>
                                            {{ $contract->contract_number }} - {{ $contract->customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="deposit_date" class="form-label">Deposit Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="deposit_date" name="deposit_date" 
                                       value="{{ $deposit->deposit_date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       value="{{ $deposit->amount }}" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="bank_transfer" selected>Bank</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bank_account_id" class="form-label">Bank Account <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="bank_account_id" name="bank_account_id" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}" {{ $deposit->bank_account_id == $bankAccount->id ? 'selected' : '' }}>
                                            {{ $bankAccount->name }} 
                                            @if($bankAccount->account_number) ({{ $bankAccount->account_number }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reference_number" class="form-label">Reference Number</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                       value="{{ $deposit->reference_number }}" placeholder="Payment reference">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="attachment" class="form-label">Attachment</label>
                                <input type="file" class="form-control" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <small class="text-muted">Accepted formats: PDF, JPG, PNG, DOC, DOCX</small>
                                @if($deposit->attachment)
                                    <div class="mt-2">
                                        <a href="{{ Storage::url($deposit->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-download me-1"></i>Current Attachment
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes...">{{ $deposit->notes }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('rental-event-equipment.customer-deposits.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bx bx-check me-1"></i>Update Deposit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2-single').select2({
        placeholder: 'Select',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });
});
</script>
@endpush
