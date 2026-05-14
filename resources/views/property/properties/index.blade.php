@extends('layouts.main')

@section('title', 'Property Management')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #bb2d3b) !important;
    }
    
    .bg-gradient-secondary {
        background: linear-gradient(45deg, #6c757d, #5c636a) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .table-light {
        background-color: #f8f9fa;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Property Management', 'url' => '#', 'icon' => 'bx bx-building']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-building me-2"></i>Property Management</h5>
                                <p class="mb-0 text-muted">Manage and track all your properties</p>
                            </div>
                            <div>
                                <a href="{{ route('properties.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Add New Property
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Properties</p>
                                <h4 class="my-1 text-primary" id="total-properties">{{ number_format($totalProperties) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-building align-middle"></i> All properties</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Active Properties</p>
                                <h4 class="my-1 text-success" id="active-count">{{ number_format($activeProperties) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Operational</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Under Maintenance</p>
                                <h4 class="my-1 text-warning" id="maintenance-count">{{ number_format($maintenanceProperties) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-wrench align-middle"></i> In repair</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-wrench"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Portfolio Value</p>
                                <h4 class="my-1 text-info" id="total-value">TSh {{ number_format($totalValue, 0) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-dollar align-middle"></i> Market value</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-lg-flex align-items-center mb-4 gap-3">
                            <div class="position-relative flex-grow-1">
                                <input type="text" class="form-control ps-5 radius-30" placeholder="Search Properties..." id="search-input">
                                <span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0" id="properties-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Property Name</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Current Value</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
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
        // Initialize DataTable
        const table = $('#properties-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("properties.index") }}',
                type: 'GET'
            },
            columns: [
                { data: 'property_name', name: 'name', orderable: true, searchable: true },
                { data: 'type_badge', name: 'type', orderable: true, searchable: true },
                { data: 'status_badge', name: 'status', orderable: true, searchable: true },
                { data: 'location', name: 'city', orderable: true, searchable: true },
                { 
                    data: 'formatted_value', 
                    name: 'current_value', 
                    orderable: true, 
                    searchable: false, 
                    className: 'text-end' 
                },
                { 
                    data: 'actions', 
                    name: 'actions', 
                    orderable: false, 
                    searchable: false, 
                    className: 'text-center' 
                }
            ],
            order: [[0, 'asc']], // Sort by property name ascending
            pageLength: 10,
            responsive: true,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                search: "Search Properties:",
                lengthMenu: "Show _MENU_ properties per page",
                info: "Showing _START_ to _END_ of _TOTAL_ properties",
                infoEmpty: "Showing 0 to 0 of 0 properties",
                infoFiltered: "(filtered from _MAX_ total properties)",
                emptyTable: `
                    <div class="text-center text-muted py-4">
                        <i class="bx bx-building fs-1 d-block mb-2"></i>
                        <h6>No Properties Found</h6>
                        <p class="mb-0">Get started by adding your first property</p>
                        <a href="{{ route('properties.create') }}" class="btn btn-primary btn-sm mt-2">
                            <i class="bx bx-plus me-1"></i> Add Property
                        </a>
                    </div>
                `
            },
            drawCallback: function(settings) {
                // Initialize tooltips after table redraw
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        // Search functionality
        $('#search-input').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Refresh table when needed
        function refreshTable() {
            table.ajax.reload(null, false);
        }

        // Global function to be called from other scripts
        window.refreshPropertiesTable = refreshTable;
    });
</script>
@endpush
