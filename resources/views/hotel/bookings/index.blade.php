@extends('layouts.main')

@section('title', 'Booking Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Booking Management', 'url' => '#', 'icon' => 'bx bx-calendar']
        ]" />

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bx bx-calendar font-size-24"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-0">{{ $totalBookings ?? 0 }}</h4>
                                <p class="mb-0">Total Bookings</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bx bx-check-circle font-size-24"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-0">{{ $confirmedBookings ?? 0 }}</h4>
                                <p class="mb-0">Confirmed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bx bx-log-in font-size-24"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-0">{{ $checkedInBookings ?? 0 }}</h4>
                                <p class="mb-0">Checked In</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bx bx-time font-size-24"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-0">{{ $pendingBookings ?? 0 }}</h4>
                                <p class="mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row of Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-purple text-white" style="background-color: #6f42c1 !important;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bx bx-globe font-size-24"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-0">{{ $onlineBookings ?? 0 }}</h4>
                                <p class="mb-0">Online Bookings</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bx bx-x-circle font-size-24"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="mb-0">{{ $cancelledBookings ?? 0 }}</h4>
                                <p class="mb-0">Cancelled</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-end align-items-center">
                    <a href="{{ route('bookings.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> New Booking
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
                                    <i class="bx bx-list-ul me-1"></i> List View
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar" type="button" role="tab">
                                    <i class="bx bx-calendar me-1"></i> Calendar View
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="webportal-tab" data-bs-toggle="tab" data-bs-target="#webportal" type="button" role="tab">
                                    <i class="bx bx-globe me-1"></i> Web Portal Settings
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- List View Tab -->
                            <div class="tab-pane fade show active" id="list" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="bookingsTable" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Guest Name</th>
                                                <th>Room</th>
                                                <th>Check-in</th>
                                                <th>Check-out</th>
                                                <th>Status</th>
                                                <th>Payment Status</th>
                                                <th>Total Amount</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Calendar View Tab -->
                            <div class="tab-pane fade" id="calendar" role="tabpanel">
                                <div id="booking-calendar"></div>
                            </div>

                            <!-- Web Portal Settings Tab -->
                            <div class="tab-pane fade" id="webportal" role="tabpanel">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="bx bx-globe me-2"></i>Hotel Booking Web Portal Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="{{ route('bookings.webportal-settings.update') }}" id="webportal-settings-form">
                                            @csrf
                                            @method('PUT')
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="enable_booking_portal" value="1" id="enable_portal" {{ ($settings['enable_booking_portal'] ?? '1') == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="enable_portal">
                                                            Enable Public Booking Portal
                                                        </label>
                                                        <div class="form-text">Allow guests to book rooms online through the web portal</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="require_admin_approval" value="1" id="require_approval" {{ ($settings['require_admin_approval'] ?? '0') == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="require_approval">
                                                            Require Admin Approval for Online Bookings
                                                        </label>
                                                        <div class="form-text">All online bookings will be pending until admin approval</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Portal URL</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" value="{{ url('/') }}" readonly>
                                                        <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard('{{ url('/') }}')">
                                                            <i class="bx bx-copy"></i> Copy
                                                        </button>
                                                    </div>
                                                    <div class="form-text">Share this URL with your guests for online bookings</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Auto-Expire Online Bookings (Hours) <span class="text-danger">*</span></label>
                                                    <input type="number" name="online_booking_expiry_hours" class="form-control @error('online_booking_expiry_hours') is-invalid @enderror" value="{{ $settings['online_booking_expiry_hours'] ?? '2' }}" min="1" max="168" placeholder="2" required>
                                                    <div class="form-text">Online bookings will be automatically cancelled after this time if not confirmed</div>
                                                    @error('online_booking_expiry_hours')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Booking Availability Time (Hours) <span class="text-danger">*</span></label>
                                                    <input type="number" name="booking_availability_hours" class="form-control @error('booking_availability_hours') is-invalid @enderror" value="{{ $settings['booking_availability_hours'] ?? '24' }}" min="1" max="168" placeholder="24" required>
                                                    <div class="form-text">Time before check-in when bookings will be available for selection (e.g., 24 hours before check-in)</div>
                                                    @error('booking_availability_hours')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="enable_dynamic_pricing" value="1" id="dynamic_pricing" {{ ($settings['enable_dynamic_pricing'] ?? '0') == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="dynamic_pricing">
                                                            Enable Dynamic Pricing
                                                        </label>
                                                        <div class="form-text">Apply different rates for weekends and weekdays</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Weekend Rate Multiplier</label>
                                                    <input type="number" name="weekend_rate_multiplier" class="form-control" value="{{ $settings['weekend_rate_multiplier'] ?? '1.2' }}" min="0.5" max="3" step="0.1" placeholder="1.2">
                                                    <div class="form-text">Weekend rates = Base Rate × Multiplier (e.g., 1.2 = 20% increase)</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="enable_promo_codes" value="1" id="promo_codes" {{ ($settings['enable_promo_codes'] ?? '1') == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="promo_codes">
                                                            Enable Promo Codes
                                                        </label>
                                                        <div class="form-text">Allow guests to apply discount codes during booking</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="enable_captcha" value="1" id="enable_captcha" {{ ($settings['enable_captcha'] ?? '1') == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="enable_captcha">
                                                            Enable CAPTCHA Verification
                                                        </label>
                                                        <div class="form-text">Protect against spam bookings with CAPTCHA</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Notification Email (Front Office)</label>
                                                    <input type="email" name="portal_notification_email" class="form-control" value="{{ $settings['portal_notification_email'] ?? '' }}" placeholder="frontoffice@hotel.com">
                                                    <div class="form-text">Email address to receive booking notifications</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Notification SMS (Reservation Officer)</label>
                                                    <input type="text" name="portal_notification_sms" class="form-control" value="{{ $settings['portal_notification_sms'] ?? '' }}" placeholder="+255712345678">
                                                    <div class="form-text">Phone number to receive SMS notifications</div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="enable_email_verification" value="1" id="email_verification" {{ ($settings['enable_email_verification'] ?? '0') == '1' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="email_verification">
                                                            Require Email Verification
                                                        </label>
                                                        <div class="form-text">Guests must verify their email before booking</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">VAT/Tax Rate (%)</label>
                                                    <input type="number" name="portal_tax_rate" class="form-control" value="{{ $settings['portal_tax_rate'] ?? '18' }}" min="0" max="100" step="0.01" placeholder="18">
                                                    <div class="form-text">Tax rate to apply to booking totals</div>
                                                </div>

                                                <div class="col-md-12">
                                                    <label class="form-label">Portal Terms & Conditions</label>
                                                    <textarea name="portal_terms_conditions" class="form-control" rows="4" placeholder="Enter terms and conditions for online bookings...">{{ $settings['portal_terms_conditions'] ?? '' }}</textarea>
                                                    <div class="form-text">Terms and conditions displayed to guests during booking</div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bx bx-save me-1"></i> Save Settings
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}

/* DataTables custom styling */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_processing,
.dataTables_wrapper .dataTables_paginate {
    margin-bottom: 1rem;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 0.375rem 0.75rem;
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 0.375rem 0.75rem;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
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

    // Get status from URL parameter if present
    const urlParams = new URLSearchParams(window.location.search);
    const statusFilter = urlParams.get('status');

    // Initialize DataTable
    $('#bookingsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('bookings.index') }}",
            data: function(d) {
                if (statusFilter) {
                    d.status = statusFilter;
                }
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        },
        columns: [
            {data: 'booking_number', name: 'booking_number'},
            {data: 'guest_name', name: 'guest_name', orderable: false, searchable: false},
            {data: 'room_info', name: 'room_info', orderable: false, searchable: false},
            {data: 'check_in_formatted', name: 'check_in'},
            {data: 'check_out_formatted', name: 'check_out'},
            {data: 'status_badge', name: 'status', orderable: false, searchable: false},
            {data: 'payment_status_badge', name: 'payment_status', orderable: false, searchable: false},
            {data: 'total_amount_formatted', name: 'total_amount'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']], // Sort by check-in date descending
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: '<div class="text-center p-4"><i class="bx bx-calendar font-24 text-muted"></i><p class="text-muted mt-2">No bookings found.</p></div>'
        }
    });

    // Initialize Calendar View
    let calendar;
    $('#calendar-tab').on('shown.bs.tab', function() {
        if (!calendar) {
            // Load FullCalendar if not already loaded
            if (typeof FullCalendar === 'undefined') {
                $('<link>').attr({
                    rel: 'stylesheet',
                    href: 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css'
                }).appendTo('head');
                
                $.getScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js', function() {
                    initializeCalendar();
                });
            } else {
                initializeCalendar();
            }
        }
    });

    function initializeCalendar() {
        calendar = new FullCalendar.Calendar(document.getElementById('booking-calendar'), {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                $.ajax({
                    url: "{{ route('bookings.index') }}",
                    type: 'GET',
                    data: {
                        calendar: true,
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr
                    },
                    success: function(response) {
                        const events = [];
                        if (response.data) {
                            response.data.forEach(function(booking) {
                                const statusColor = {
                                    'confirmed': '#198754',
                                    'pending': '#ffc107',
                                    'checked_in': '#0d6efd',
                                    'checked_out': '#6c757d',
                                    'cancelled': '#dc3545'
                                }[booking.status] || '#6c757d';
                                
                                events.push({
                                    title: booking.room_number + ' - ' + booking.guest_name,
                                    start: booking.check_in,
                                    end: booking.check_out,
                                    backgroundColor: statusColor,
                                    borderColor: statusColor,
                                    extendedProps: {
                                        booking_number: booking.booking_number,
                                        status: booking.status
                                    }
                                });
                            });
                        }
                        successCallback(events);
                    },
                    error: function() {
                        failureCallback();
                    }
                });
            },
            eventClick: function(info) {
                window.location.href = "{{ url('/hotel/bookings') }}/" + info.event.extendedProps.booking_number;
            },
            eventDisplay: 'block',
            height: 'auto'
        });
        calendar.render();
    }
});

// SweetAlert confirmation function for accepting online booking
function confirmAcceptBooking(bookingId, actionUrl) {
    if (typeof Swal === 'undefined') {
        alert('SweetAlert is not loaded. Please refresh the page.');
        return;
    }
    
    Swal.fire({
        title: 'Accept Booking?',
        text: 'Are you sure you want to accept this online booking? It will be moved to confirmed status and will not be auto-cancelled.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Accept',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Create and submit the form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// SweetAlert confirmation functions for index page
function confirmCheckInIndex(bookingId, actionUrl) {
    console.log('confirmCheckInIndex called for booking:', bookingId);
    
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
            form.action = actionUrl;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function confirmCheckOutIndex(bookingId, actionUrl) {
    console.log('confirmCheckOutIndex called for booking:', bookingId);
    
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
            form.action = actionUrl;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function confirmCancelIndex(bookingId, actionUrl) {
    console.log('confirmCancelIndex called for booking:', bookingId);
    
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
            submitCancelFormIndex(actionUrl);
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
                    submitCancelFormIndex(actionUrl, feeResult.value);
                }
            });
        }
    });
}

function confirmDeleteBookingIndex(bookingId, actionUrl) {
    if (typeof Swal === 'undefined') {
        if (confirm('Delete this booking permanently?')) {
            submitDeleteBookingFormIndex(actionUrl);
        }
        return;
    }

    Swal.fire({
        title: 'Delete Booking?',
        text: 'This will permanently delete this booking and its related records. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            submitDeleteBookingFormIndex(actionUrl);
        }
    });
}

function submitDeleteBookingFormIndex(actionUrl) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = actionUrl;

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';

    form.appendChild(csrfToken);
    form.appendChild(methodInput);
    document.body.appendChild(form);
    form.submit();
}

function submitCancelFormIndex(actionUrl, fee = 0) {
    // Create and submit the cancel form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = actionUrl;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
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

  // Copy to clipboard function
  function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(function() {
          Swal.fire({
              icon: 'success',
              title: 'Copied!',
              text: 'URL copied to clipboard',
              timer: 2000,
              showConfirmButton: false
          });
      }, function(err) {
          console.error('Failed to copy: ', err);
          Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Failed to copy URL to clipboard'
          });
      });
  }
 </script>
 @endpush
