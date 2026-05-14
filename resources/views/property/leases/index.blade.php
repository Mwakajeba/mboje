@extends('layouts.main')

@section('title', 'Leases')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-buildings'],
            ['label' => 'Leases', 'url' => route('leases.index'), 'icon' => 'bx bx-file']
        ]" />

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-primary bg-gradient">
                                    <div class="avatar-title text-white"><i class="bx bx-file"></i></div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Total Leases</p>
                                <h4 class="mb-0">{{ $totalLeases ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-success bg-gradient">
                                    <div class="avatar-title text-white"><i class="bx bx-check-circle"></i></div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Active</p>
                                <h4 class="mb-0">{{ $activeLeases ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-danger bg-gradient">
                                    <div class="avatar-title text-white"><i class="bx bx-time-five"></i></div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Expired</p>
                                <h4 class="mb-0">{{ $expiredLeases ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-warning bg-gradient">
                                    <div class="avatar-title text-white"><i class="bx bx-error"></i></div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Overdue</p>
                                <h4 class="mb-0">{{ $overdueLeases ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">Leases</h4>
                    <p class="text-muted mb-0">List of property leases</p>
                </div>
                <a href="{{ route('leases.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> New Lease</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="leases-table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Lease #</th>
                                <th>Unit</th>
                                <th>Tenant</th>
                                <th>Period</th>
                                <th>Monthly Rent</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
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
    $('#leases-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('leases.index') }}",
            type: 'GET'
        },
        columns: [
            { data: 'lease_number', name: 'lease_number' },
            { data: 'rental_unit', name: 'room_id' },
            { data: 'tenant_name', name: 'tenant_id' },
            { data: 'period', name: 'start_date' },
            { data: 'monthly_rent_formatted', name: 'monthly_rent' },
            { data: 'status_badge', name: 'status' },
            { data: 'payment_status_badge', name: 'payment_status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading leases...",
            emptyTable: "No leases found",
            zeroRecords: "No leases match your search"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    });
});
</script>
@endpush
