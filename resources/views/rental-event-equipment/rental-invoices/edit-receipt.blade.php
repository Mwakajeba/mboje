@extends('layouts.main')

@section('title', 'Edit Receipt - Rental Invoice')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Invoices', 'url' => route('rental-event-equipment.rental-invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Edit Receipt', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT RECEIPT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Receipt</h5>
                    </div>
                    <div class="card-body">
                        <!-- Invoice Summary -->
                        <div class="card border-info mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Invoice Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
                                        <p class="mb-1"><strong>Customer:</strong> {{ $invoice->customer->name ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('M d, Y') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Total Amount:</strong> TZS {{ number_format($invoice->total_amount, 2) }}</p>
                                        <p class="mb-1"><strong>Paid Amount:</strong> TZS {{ number_format($invoice->deposit_applied ?? 0, 2) }}</p>
                                        @php
                                            $balanceDue = $invoice->total_amount - ($invoice->deposit_applied ?? 0) + $receipt->amount;
                                        @endphp
                                        <p class="mb-0"><strong>Balance Due (after edit):</strong> <span class="badge bg-warning">TZS {{ number_format($balanceDue, 2) }}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('rental-event-equipment.rental-invoices.receipt.update', Hashids::encode($receipt->id)) }}">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="payment_amount" name="payment_amount" 
                                            value="{{ $receipt->amount }}" min="0.01" max="{{ $balanceDue }}" step="0.01" required>
                                        <small class="text-muted">Maximum: TZS {{ number_format($balanceDue, 2) }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                            value="{{ $receipt->date->format('Y-m-d') }}" required>
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
                                                <option value="{{ $bankAccount->id }}" {{ $receipt->bank_account_id == $bankAccount->id ? 'selected' : '' }}>
                                                    {{ $bankAccount->name }} 
                                                    @if($bankAccount->account_number) ({{ $bankAccount->account_number }}) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reference_number" class="form-label">Reference Number</label>
                                        <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                            value="{{ $receipt->reference_number }}" placeholder="Payment reference number">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                            placeholder="Additional notes...">{{ $receipt->description }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="{{ route('rental-event-equipment.rental-invoices.show', $invoice->getRouteKey()) }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="bx bx-check me-1"></i>Update Receipt
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
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
