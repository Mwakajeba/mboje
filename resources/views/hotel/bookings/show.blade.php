@extends('layouts.main')

@section('title', 'Booking Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Booking Management', 'url' => route('bookings.index'), 'icon' => 'bx bx-calendar'],
            ['label' => 'Booking Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="mb-1 text-primary">
                                    <i class="bx bx-calendar-check me-2"></i>
                                    Booking #{{ $booking->booking_number ?? '#' . $booking->id }}
                                </h2>
                                <p class="text-muted mb-0">
                                    <i class="bx bx-time me-1"></i>
                                    Created {{ $booking->created_at ? $booking->created_at->format('M d, Y \a\t g:i A') : 'N/A' }}
                                </p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Bookings
                                </a>
                                <a href="{{ route('bookings.export-pdf', $booking) }}" class="btn btn-success" target="_blank">
                                    <i class="bx bx-file-pdf me-1"></i> Export PDF
                                </a>
                                @if($booking->status === 'pending')
                                <button type="button" class="btn btn-info" onclick="confirmBooking()">
                                    <i class="bx bx-check-circle me-1"></i> Confirm Booking
                                </button>
                                @endif
                                @if($booking->status !== 'checked_out')
                                <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-primary">
                                    <i class="bx bx-edit me-1"></i> Edit Booking
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Booking Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-info-circle me-2"></i>Booking Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="form-label fw-semibold text-muted">Guest Name</label>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-user text-primary me-2"></i>
                                        <span class="fw-semibold">{{ $booking->guest->first_name ?? '' }} {{ $booking->guest->last_name ?? '' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="form-label fw-semibold text-muted">Room Details</label>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-bed text-primary me-2"></i>
                                        <span class="fw-semibold">{{ $booking->room->room_number ?? 'N/A' }} - {{ ucfirst($booking->room->room_type ?? 'N/A') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                                <div class="row">
                                    <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="form-label fw-semibold text-muted">Check-in Date</label>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-calendar text-success me-2"></i>
                                        <span class="fw-semibold">{{ $booking->check_in ? $booking->check_in->format('M d, Y') : 'N/A' }}</span>
                                    </div>
                                </div>
                                        </div>
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="form-label fw-semibold text-muted">Check-out Date</label>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-calendar text-danger me-2"></i>
                                        <span class="fw-semibold">{{ $booking->check_out ? $booking->check_out->format('M d, Y') : 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                                    <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="form-label fw-semibold text-muted">Number of Guests</label>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-group text-info me-2"></i>
                                        <span class="fw-semibold">{{ $booking->adults ?? 0 }} Adults{{ $booking->children > 0 ? ', ' . $booking->children . ' Children' : '' }}</span>
                                    </div>
                                </div>
                                        </div>
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="form-label fw-semibold text-muted">Booking Source</label>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-source text-warning me-2"></i>
                                        <span class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $booking->booking_source ?? 'walk_in')) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($booking->special_requests)
                        <div class="info-item mb-3">
                            <label class="form-label fw-semibold text-muted">Special Requests</label>
                            <div class="d-flex align-items-start">
                                <i class="bx bx-message-dots text-info me-2 mt-1"></i>
                                <span>{{ $booking->special_requests }}</span>
                            </div>
                        </div>
                        @endif

                        @if($booking->notes)
                        <div class="info-item mb-3">
                            <label class="form-label fw-semibold text-muted">Notes</label>
                            <div class="d-flex align-items-start">
                                <i class="bx bx-note text-secondary me-2 mt-1"></i>
                                <span>{{ $booking->notes }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- GL Double Entry Transactions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-calculator me-2"></i>GL Double Entry Transactions
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($booking->glTransactions->count() > 0 || $booking->paymentGlTransactions->count() > 0)
                            <!-- Booking GL Transactions -->
                            @if($booking->glTransactions->count() > 0)
                            <div class="mb-4">
                                <h6 class="text-primary mb-3">
                                    <i class="bx bx-receipt me-1"></i>Booking Revenue Entries
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Account</th>
                                                <th>Description</th>
                                                <th class="text-end">Debit</th>
                                                <th class="text-end">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($booking->glTransactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->date->format('M d, Y') }}</td>
                                                <td>{{ $transaction->chartAccount->account_name ?? 'N/A' }}</td>
                                                <td>{{ $transaction->description }}</td>
                                                <td class="text-end">
                                                    @if($transaction->nature === 'debit')
                                                        <span class="text-danger fw-semibold">TSh {{ number_format($transaction->amount, 0) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($transaction->nature === 'credit')
                                                        <span class="text-success fw-semibold">TSh {{ number_format($transaction->amount, 0) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif

                            <!-- Payment GL Transactions -->
                            @if($booking->paymentGlTransactions->count() > 0)
                            <div class="mb-4">
                                <h6 class="text-success mb-3">
                                    <i class="bx bx-credit-card me-1"></i>Payment Entries
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Account</th>
                                                <th>Description</th>
                                                <th class="text-end">Debit</th>
                                                <th class="text-end">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($booking->paymentGlTransactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->date->format('M d, Y') }}</td>
                                                <td>{{ $transaction->chartAccount->account_name ?? 'N/A' }}</td>
                                                <td>{{ $transaction->description }}</td>
                                                <td class="text-end">
                                                    @if($transaction->nature === 'debit')
                                                        <span class="text-danger fw-semibold">TSh {{ number_format($transaction->amount, 0) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($transaction->nature === 'credit')
                                                        <span class="text-success fw-semibold">TSh {{ number_format($transaction->amount, 0) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-calculator text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No GL transactions found for this booking.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Payment History -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-history me-2"></i>Payment History
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($booking->receipts->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Receipt #</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Bank Account</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($booking->receipts as $receipt)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">#{{ $receipt->reference_number }}</span>
                                            </td>
                                            <td>{{ $receipt->date->format('M d, Y') }}</td>
                                            <td>
                                                <span class="text-success fw-semibold">TSh {{ number_format($receipt->amount, 0) }}</span>
                                            </td>
                                            <td>
                                                @if($receipt->bankAccount)
                                                    <span class="badge bg-light text-dark">
                                                        {{ $receipt->bankAccount->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">Cash</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($receipt->approved)
                                                    <span class="badge bg-success">Approved</span>
                                                @else
                                                    <span class="badge bg-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <!-- Edit Button -->
                                                    <a href="{{ route('bookings.receipts.edit', $receipt->getEncodedId()) }}" 
                                                       class="btn btn-outline-primary btn-sm" 
                                                       title="Edit Receipt">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                    
                                                    <!-- Print Button -->
                                                    <a href="{{ route('bookings.receipts.print', $receipt->getEncodedId()) }}" 
                                                       class="btn btn-outline-info btn-sm" 
                                                       title="Print Receipt"
                                                       target="_blank">
                                                        <i class="bx bx-printer"></i>
                                                    </a>
                                                    
                                                    <!-- Delete Button -->
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm" 
                                                            title="Delete Receipt"
                                                            onclick="deleteReceipt('{{ $receipt->getEncodedId() }}', '{{ $receipt->reference_number }}')">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-credit-card text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No payment history found for this booking.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Status Cards -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">Booking Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Status</span>
                            <span class="badge bg-{{ $booking->status === 'confirmed' ? 'success' : ($booking->status === 'pending' ? 'warning' : ($booking->status === 'cancelled' ? 'danger' : 'info')) }} fs-6">
                                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Payment Status</span>
                            <span class="badge bg-{{ $booking->payment_status === 'paid' ? 'success' : ($booking->payment_status === 'partial' ? 'warning' : 'danger') }} fs-6">
                                {{ ucfirst($booking->payment_status ?? 'pending') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">Financial Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Duration</span>
                            <span class="fw-semibold">{{ $booking->nights ?? 0 }} nights</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Rate per Night</span>
                            <span class="fw-semibold">TSh {{ number_format($booking->room_rate ?? 0, 0) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Amount</span>
                            <span class="fw-bold text-primary fs-5">TSh {{ number_format($booking->total_amount ?? 0, 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Paid Amount</span>
                            <span class="fw-bold text-success fs-5">TSh {{ number_format($booking->paid_amount ?? 0, 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Balance Due</span>
                            <span class="fw-bold text-{{ $booking->balance_due > 0 ? 'danger' : 'success' }} fs-5">
                                TSh {{ number_format($booking->balance_due ?? 0, 0) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">Payment Progress</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $paymentPercentage = $booking->total_amount > 0 ? ($booking->paid_amount / $booking->total_amount) * 100 : 0;
                        @endphp
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-{{ $paymentPercentage >= 100 ? 'success' : ($paymentPercentage > 0 ? 'warning' : 'danger') }}" 
                                 role="progressbar" 
                                 style="width: {{ $paymentPercentage }}%">
                            </div>
                        </div>
                        <div class="text-center">
                            <span class="fw-semibold">{{ number_format($paymentPercentage, 1) }}% Paid</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions: status actions (only when not checked_out) + Record Payment (only when not fully paid) -->
                @if($booking->status !== 'checked_out' || $booking->balance_due > 0)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if($booking->status === 'confirmed')
                                <button type="button" class="btn btn-success w-100" onclick="confirmCheckIn()">
                                    <i class="bx bx-log-in me-1"></i> Check In
                                </button>
                            @endif
                            
                            @if($booking->status === 'checked_in')
                                <button type="button" class="btn btn-warning w-100" onclick="confirmCheckOut()">
                                    <i class="bx bx-log-out me-1"></i> Check Out
                                </button>
                            @endif
                            
                            @if(in_array($booking->status, ['pending', 'confirmed']))
                                <button type="button" class="btn btn-danger w-100" onclick="confirmCancel()">
                                    <i class="bx bx-x me-1"></i> Cancel Booking
                                </button>
                            @endif
                            
                            @if($booking->balance_due > 0)
                                <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                                    <i class="bx bx-credit-card me-1"></i> Record Payment
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recordPaymentModalLabel">
                    <i class="bx bx-credit-card me-2"></i>Record Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('bookings.record-payment', $booking) }}">
                @csrf
                <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                <label class="form-label">Booking Number</label>
                                <input type="text" class="form-control" value="{{ $booking->booking_number }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                <label class="form-label">Guest Name</label>
                                <input type="text" class="form-control" value="{{ $booking->guest->first_name }} {{ $booking->guest->last_name }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                <label class="form-label">Total Amount</label>
                                <input type="text" class="form-control" value="TSh {{ number_format($booking->total_amount, 0) }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                <label class="form-label">Balance Due</label>
                                <input type="text" class="form-control" value="TSh {{ number_format($booking->balance_due, 0) }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                <input type="number" name="payment_amount" class="form-control @error('payment_amount') is-invalid @enderror" 
                                       value="{{ old('payment_amount') }}" placeholder="0" min="0" step="0.01" max="{{ $booking->balance_due }}" required>
                                @error('payment_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" 
                                       value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                        </div>
                                    </div>
                                </div>

                    <div class="row">
                        <div class="col-md-12">
                                <div class="mb-3">
                                <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                                <select name="bank_account_id" class="form-select select2-single @error('bank_account_id') is-invalid @enderror" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach(\App\Models\BankAccount::orderBy('name')->get() as $bankAccount)
                                        <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
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
                                <label class="form-label">Payment Description</label>
                                <textarea name="payment_description" class="form-control @error('payment_description') is-invalid @enderror" 
                                          rows="3" placeholder="Additional payment for booking #{{ $booking->booking_number }}">{{ old('payment_description') }}</textarea>
                                @error('payment_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Record Payment
                    </button>
            </div>
            </form>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    // Display flash messages using SweetAlert
    @if(session('success'))
        Swal.fire({
            title: 'Success!',
            text: '{{ session('success') }}',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#28a745'
        });
    @endif

    @if(session('error'))
        Swal.fire({
            title: 'Error!',
            text: '{{ session('error') }}',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
        });
    @endif

    @if($errors->any())
        Swal.fire({
            title: 'Validation Error!',
            text: '{{ $errors->first() }}',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
        });
    @endif

    // Initialize Select2 for bank account selection in modal
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({
            dropdownParent: $('#recordPaymentModal')
        });
    }
    
    // Auto-calculate remaining balance when payment amount changes
    const paymentAmountInput = document.querySelector('input[name="payment_amount"]');
    const balanceDueDisplay = document.querySelector('input[readonly][value*="Balance Due"]');
    
    if (paymentAmountInput && balanceDueDisplay) {
        const originalBalance = {{ $booking->balance_due }};
        
        paymentAmountInput.addEventListener('input', function() {
            const paymentAmount = parseFloat(this.value) || 0;
            const remainingBalance = originalBalance - paymentAmount;
            
            // Update the max attribute to prevent overpayment
            this.setAttribute('max', originalBalance);
            
            // Show warning if overpayment
            if (paymentAmount > originalBalance) {
                this.classList.add('is-invalid');
                if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'Payment amount cannot exceed balance due';
                    this.parentNode.appendChild(errorDiv);
                }
            } else {
                this.classList.remove('is-invalid');
                const errorDiv = this.parentNode.querySelector('.invalid-feedback');
                if (errorDiv) {
                    errorDiv.remove();
                }
            }
        });
    }
    
    // Clear form when modal is closed
    $('#recordPaymentModal').on('hidden.bs.modal', function() {
        const form = this.querySelector('form');
        if (form) {
            form.reset();
            // Reset validation classes
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        }
    });
});

// SweetAlert confirmation functions
function confirmCheckIn() {
    console.log('confirmCheckIn function called'); // Debug log
    console.log('SweetAlert available:', typeof Swal !== 'undefined'); // Debug log
    
    if (typeof Swal === 'undefined') {
        alert('SweetAlert is not loaded. Please refresh the page.');
        return;
    }
    
    Swal.fire({
        title: 'Check In Guest?',
        text: 'Are you sure you want to check in this guest? This will mark the room as occupied.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Check In',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Create and submit the form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("bookings.check-in", $booking) }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function confirmBooking() {
    if (typeof Swal === 'undefined') {
        if (confirm('Confirm this booking?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("bookings.confirm", $booking) }}';
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
        return;
    }

    Swal.fire({
        title: 'Confirm Booking?',
        text: 'This will mark the booking as confirmed and reserve the room.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("bookings.confirm", $booking) }}';
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function confirmCheckOut() {
    console.log('confirmCheckOut function called'); // Debug log
    console.log('SweetAlert available:', typeof Swal !== 'undefined'); // Debug log
    
    if (typeof Swal === 'undefined') {
        alert('SweetAlert is not loaded. Please refresh the page.');
        return;
    }
    
    Swal.fire({
        title: 'Check Out Guest?',
        text: 'Are you sure you want to check out this guest? This will mark the room as available.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Check Out',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Create and submit the form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("bookings.check-out", $booking) }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function confirmCancel() {
    console.log('confirmCancel function called'); // Debug log
    console.log('SweetAlert available:', typeof Swal !== 'undefined'); // Debug log
    
    if (typeof Swal === 'undefined') {
        alert('SweetAlert is not loaded. Please refresh the page.');
        return;
    }
    
    Swal.fire({
        title: 'Cancel Booking?',
        text: 'Are you sure you want to cancel this booking? This will delete all receipts and payments related to this booking.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Cancel Booking',
        cancelButtonText: 'Keep Booking',
        reverseButtons: true,
        showDenyButton: true,
        denyButtonText: 'Cancel with Fee',
        denyButtonColor: '#fd7e14'
    }).then((result) => {
        if (result.isConfirmed) {
            // Cancel without fee
            submitCancelForm();
        } else if (result.isDenied) {
            // Cancel with fee - show input dialog
            Swal.fire({
                title: 'Cancellation Fee',
                text: 'Enter the cancellation fee amount:',
                input: 'number',
                inputAttributes: {
                    min: 0,
                    step: 0.01,
                    placeholder: '0.00'
                },
                showCancelButton: true,
                confirmButtonText: 'Cancel with Fee',
                cancelButtonText: 'Back',
                inputValidator: (value) => {
                    if (!value || value < 0) {
                        return 'Please enter a valid fee amount (0 or greater)';
                    }
                }
            }).then((feeResult) => {
                if (feeResult.isConfirmed) {
                    submitCancelForm(feeResult.value);
                }
            });
        }
    });
}

function submitCancelForm(fee = 0) {
    // Create and submit the cancel form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("bookings.cancel", $booking) }}';
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    
    const feeInput = document.createElement('input');
    feeInput.type = 'hidden';
    feeInput.name = 'cancellation_fee';
    feeInput.value = fee;
    
    const reasonInput = document.createElement('input');
    reasonInput.type = 'hidden';
    reasonInput.name = 'cancellation_reason';
    reasonInput.value = 'Booking cancelled by user';
    
    form.appendChild(csrfToken);
    form.appendChild(feeInput);
    form.appendChild(reasonInput);
    document.body.appendChild(form);
    form.submit();
}
</script>

<style>
.info-item {
    padding: 0.5rem 0;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
</style>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function deleteReceipt(encodedReceiptId, receiptNumber) {
    if (confirm(`Are you sure you want to delete receipt #${receiptNumber}? This action cannot be undone.`)) {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/bookings/receipts/${encodedReceiptId}`;
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Add method override for DELETE
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection