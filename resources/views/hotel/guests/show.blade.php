@extends('layouts.main')

@section('title', 'Guest Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Guest Management', 'url' => route('guests.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Guest Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">Guest Details</h4>
                                <p class="card-subtitle text-muted">View guest information and booking history</p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('guests.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Guests
                                </a>
                                <a href="{{ route('guests.edit', $guest) }}" class="btn btn-primary">
                                    <i class="bx bx-edit me-1"></i> Edit Guest
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Guest ID</label>
                                            <p class="form-control-plaintext">{{ $guest->guest_number ?? '#' . $guest->id }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Full Name</label>
                                            <p class="form-control-plaintext">{{ $guest->full_name }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Email</label>
                                            <p class="form-control-plaintext">{{ $guest->email ?? 'Not provided' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Phone Number</label>
                                            <p class="form-control-plaintext">{{ $guest->phone ?? 'Not provided' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nationality</label>
                                            <p class="form-control-plaintext">{{ $guest->nationality ?? 'Not provided' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Date of Birth</label>
                                            <p class="form-control-plaintext">{{ $guest->date_of_birth ? $guest->date_of_birth->format('Y-m-d') : 'Not provided' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Gender</label>
                                            <p class="form-control-plaintext">{{ ucfirst($guest->gender ?? 'Not provided') }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">ID Type</label>
                                            <p class="form-control-plaintext">{{ ucfirst(str_replace('_', ' ', $guest->id_type ?? 'Not provided')) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">ID Number</label>
                                            <p class="form-control-plaintext">{{ $guest->id_number ?? 'Not provided' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Address</label>
                                            <p class="form-control-plaintext">{{ $guest->full_address ?? 'Not provided' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Special Notes</label>
                                    <p class="form-control-plaintext">{{ $guest->special_requests ?? 'No special requests' }}</p>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Guest Statistics</h6>
                                        <div class="mb-3">
                                            <small class="text-muted">Total Bookings</small>
                                            <h5 class="mb-0">{{ $guest->total_bookings }}</h5>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted">Total Nights</small>
                                            <h5 class="mb-0">{{ $guest->bookings->sum('nights') }}</h5>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted">Total Spent</small>
                                            <h5 class="mb-0">TSh {{ number_format($guest->total_spent, 0) }}</h5>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted">Last Visit</small>
                                            <h5 class="mb-0">{{ $guest->last_booking ? $guest->last_booking->check_in->format('M d, Y') : 'Never' }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking History -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Booking History</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center text-muted py-4">
                            <i class="bx bx-calendar font-size-48 mb-3 d-block"></i>
                            <h5>No bookings found</h5>
                            <p>This guest has not made any bookings yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
