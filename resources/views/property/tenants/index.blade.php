@extends('layouts.main')

@section('title', 'Tenants')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-buildings'],
            ['label' => 'Tenants', 'url' => route('tenants.index'), 'icon' => 'bx bx-user']
        ]" />

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card"><div class="card-body"><div class="d-flex align-items-center">
                    <div class="flex-shrink-0"><div class="avatar-sm rounded bg-primary bg-gradient"><div class="avatar-title text-white"><i class="bx bx-user"></i></div></div></div>
                    <div class="flex-grow-1 ms-3"><p class="text-uppercase fw-medium text-muted mb-0">Total Tenants</p><h4 class="mb-0">{{ $totalTenants ?? 0 }}</h4></div>
                </div></div></div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card"><div class="card-body"><div class="d-flex align-items-center">
                    <div class="flex-shrink-0"><div class="avatar-sm rounded bg-success bg-gradient"><div class="avatar-title text-white"><i class="bx bx-check-circle"></i></div></div></div>
                    <div class="flex-grow-1 ms-3"><p class="text-uppercase fw-medium text-muted mb-0">Active</p><h4 class="mb-0">{{ $activeTenants ?? 0 }}</h4></div>
                </div></div></div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card"><div class="card-body"><div class="d-flex align-items-center">
                    <div class="flex-shrink-0"><div class="avatar-sm rounded bg-secondary bg-gradient"><div class="avatar-title text-white"><i class="bx bx-pause-circle"></i></div></div></div>
                    <div class="flex-grow-1 ms-3"><p class="text-uppercase fw-medium text-muted mb-0">Inactive</p><h4 class="mb-0">{{ $inactiveTenants ?? 0 }}</h4></div>
                </div></div></div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card"><div class="card-body"><div class="d-flex align-items-center">
                    <div class="flex-shrink-0"><div class="avatar-sm rounded bg-warning bg-gradient"><div class="avatar-title text-white"><i class="bx bx-home"></i></div></div></div>
                    <div class="flex-grow-1 ms-3"><p class="text-uppercase fw-medium text-muted mb-0">Currently Renting</p><h4 class="mb-0">{{ $rentingTenants ?? 0 }}</h4></div>
                </div></div></div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">Tenants</h4>
                    <p class="text-muted mb-0">List of tenants</p>
                </div>
                <a href="{{ route('tenants.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> New Tenant</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tenants-table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tenant #</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Leases</th>
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
    $('#tenants-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('tenants.index') }}",
            type: 'GET'
        },
        columns: [
            { data: 'tenant_number', name: 'tenant_number' },
            { data: 'name', name: 'first_name' },
            { data: 'contact', name: 'phone' },
            { data: 'status_badge', name: 'status' },
            { data: 'leases_count', name: 'leases_count', searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading tenants...",
            emptyTable: "No tenants found",
            zeroRecords: "No tenants match your search"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    });
});
</script>
@endpush


