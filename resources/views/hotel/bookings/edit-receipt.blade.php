@extends('layouts.main')

@section('title', 'Edit Receipt')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Booking Management', 'url' => route('bookings.index'), 'icon' => 'bx bx-calendar'],
            ['label' => 'Booking Details', 'url' => route('bookings.show', $booking), 'icon' => 'bx bx-show'],
            ['label' => 'Edit Receipt', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">Edit Receipt</h4>
                                <p class="card-subtitle text-muted">Update receipt information for booking #{{ $booking->booking_number }}</p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Booking
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('bookings.receipts.update', $receipt) }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Receipt Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="{{ $receipt->reference_number }}" readonly>
                                        <small class="text-muted">Receipt number cannot be changed</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" 
                                               value="{{ old('date', $receipt->date->format('Y-m-d')) }}" required>
                                        @error('date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                                        <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" 
                                               value="{{ old('amount', $receipt->amount) }}" min="0" step="0.01" required>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                                        <select name="bank_account_id" class="form-select select2-single @error('bank_account_id') is-invalid @enderror" required>
                                            <option value="">Select Bank Account</option>
                                            @foreach($bankAccounts as $bankAccount)
                                                <option value="{{ $bankAccount->id }}" 
                                                        {{ old('bank_account_id', $receipt->bank_account_id) == $bankAccount->id ? 'selected' : '' }}>
                                                    {{ $bankAccount->name }} - {{ $bankAccount->account_number }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('bank_account_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                                  rows="3" placeholder="Enter receipt description...">{{ old('description', $receipt->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Booking Information -->
                            <div class="card bg-light mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Related Booking Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-2">
                                                <label class="form-label text-muted">Guest:</label>
                                                <p class="mb-0 fw-semibold">{{ $booking->guest->first_name }} {{ $booking->guest->last_name }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-2">
                                                <label class="form-label text-muted">Room:</label>
                                                <p class="mb-0 fw-semibold">{{ $booking->room->room_number }} - {{ ucfirst($booking->room->room_type) }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-2">
                                                <label class="form-label text-muted">Total Amount:</label>
                                                <p class="mb-0 fw-semibold text-primary">TSh {{ number_format($booking->total_amount, 0) }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-2">
                                                <label class="form-label text-muted">Current Paid:</label>
                                                <p class="mb-0 fw-semibold text-success">TSh {{ number_format($booking->paid_amount, 0) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Update Receipt
                                </button>
                                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-secondary">
                                    <i class="bx bx-x me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush
@endsection
