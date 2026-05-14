@extends('layouts.main')

@section('title', 'Edit Booking')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Booking Management', 'url' => route('bookings.index'), 'icon' => 'bx bx-calendar'],
            ['label' => 'Booking Details', 'url' => route('bookings.show', $booking), 'icon' => 'bx bx-show'],
            ['label' => 'Edit Booking', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        @if($booking->status === 'checked_out')
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bx bx-info-circle me-2"></i>
                <strong>Cannot Edit Checked-Out Booking:</strong> This booking has been completed and cannot be modified. 
                <a href="{{ route('bookings.show', $booking) }}" class="alert-link">View booking details</a> instead.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit Booking</h4>
                        <p class="card-subtitle text-muted">Update booking information</p>
                    </div>
                    <div class="card-body">
                        @if($booking->status === 'checked_out')
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                This booking has been completed and cannot be edited. All fields are disabled.
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('bookings.update', $booking) }}">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Guest <span class="text-danger">*</span></label>
                                        <select name="guest_id" class="form-select select2-single @error('guest_id') is-invalid @enderror" {{ $booking->status === 'checked_out' ? 'disabled' : '' }}>
                                            <option value="">Select Guest</option>
                                            @foreach($guests as $guest)
                                                <option value="{{ $guest->id }}" {{ old('guest_id', $booking->guest_id) == $guest->id ? 'selected' : '' }}>
                                                    {{ $guest->first_name }} {{ $guest->last_name }} - {{ $guest->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('guest_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Room <span class="text-danger">*</span></label>
                                        <select name="room_id" class="form-select select2-single @error('room_id') is-invalid @enderror" {{ $booking->status === 'checked_out' ? 'disabled' : '' }}>
                                            <option value="">Select Room</option>
                                            @foreach($rooms as $room)
                                                <option value="{{ $room->id }}" {{ old('room_id', $booking->room_id) == $room->id ? 'selected' : '' }}>
                                                    {{ $room->room_number }} - {{ ucfirst($room->room_type) }} ({{ $room->property->name ?? 'No Property' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('room_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Check-in Date <span class="text-danger">*</span></label>
                                        <input type="date" name="check_in" class="form-control @error('check_in') is-invalid @enderror" value="{{ old('check_in', $booking->check_in ? $booking->check_in->format('Y-m-d') : '') }}" {{ $booking->status === 'checked_out' ? 'disabled' : '' }}>
                                        @error('check_in')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Check-out Date <span class="text-danger">*</span></label>
                                        <input type="date" name="check_out" class="form-control @error('check_out') is-invalid @enderror" value="{{ old('check_out', $booking->check_out ? $booking->check_out->format('Y-m-d') : '') }}" {{ $booking->status === 'checked_out' ? 'disabled' : '' }}>
                                        @error('check_out')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Adults <span class="text-danger">*</span></label>
                                        <input type="number" name="adults" class="form-control @error('adults') is-invalid @enderror" value="{{ old('adults', $booking->adults) }}" min="1" max="10">
                                        @error('adults')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Children</label>
                                        <input type="number" name="children" class="form-control @error('children') is-invalid @enderror" value="{{ old('children', $booking->children) }}" min="0" max="10">
                                        @error('children')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Room Rate per Night (TSh) <span class="text-danger">*</span></label>
                                        <input type="number" name="room_rate" class="form-control @error('room_rate') is-invalid @enderror" value="{{ old('room_rate', $booking->room_rate) }}" min="0" step="0.01">
                                        @error('room_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select select2-single @error('status') is-invalid @enderror">
                                            <option value="pending" {{ old('status', $booking->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="confirmed" {{ old('status', $booking->status) == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                            <option value="checked_in" {{ old('status', $booking->status) == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                                            <option value="checked_out" {{ old('status', $booking->status) == 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                                            <option value="cancelled" {{ old('status', $booking->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Status <span class="text-danger">*</span></label>
                                        <select name="payment_status" class="form-select select2-single @error('payment_status') is-invalid @enderror">
                                            <option value="pending" {{ old('payment_status', $booking->payment_status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="partial" {{ old('payment_status', $booking->payment_status) == 'partial' ? 'selected' : '' }}>Partial</option>
                                            <option value="paid" {{ old('payment_status', $booking->payment_status) == 'paid' ? 'selected' : '' }}>Paid</option>
                                        </select>
                                        @error('payment_status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Total Amount (TSh)</label>
                                        <input type="number" class="form-control" value="{{ $booking->total_amount }}" readonly>
                                        <small class="text-muted">Calculated automatically based on room rate and nights</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Special Requests</label>
                                        <textarea name="special_requests" class="form-control @error('special_requests') is-invalid @enderror" rows="3" placeholder="Any special requests or notes">{{ old('special_requests', $booking->special_requests) }}</textarea>
                                        @error('special_requests')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Additional notes">{{ old('notes', $booking->notes) }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" {{ $booking->status === 'checked_out' ? 'disabled' : '' }}>
                                    <i class="bx bx-save me-1"></i> Update Booking
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
</script>
@endpush
@endsection
