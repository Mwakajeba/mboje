@extends('layouts.main')

@section('title', $category->name . ' - Category Items')

@push('styles')
<style>
    /* Ensure DataTable controls are visible */
    .dataTables_wrapper .dataTables_filter {
        display: block !important;
        margin-bottom: 10px;
    }
    
    .dataTables_wrapper .dataTables_length {
        display: block !important;
        margin-bottom: 10px;
    }
    
    .dataTables_wrapper .dataTables_info {
        display: block !important;
        margin-top: 10px;
    }
    
    .dataTables_wrapper .dataTables_paginate {
        display: block !important;
        margin-top: 10px;
    }
    
    /* Improve search box styling */
    .dataTables_filter input {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 6px 12px;
        margin-left: 8px;
    }
    
    /* Improve length filter styling */
    .dataTables_length select {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 4px 8px;
        margin: 0 4px;
    }
    
    /* Ensure action column is visible and properly styled */
    #categoryItemsTable th:last-child,
    #categoryItemsTable td:last-child {
        min-width: 120px;
        text-align: center;
    }
    
    /* Style action buttons */
    #categoryItemsTable .btn-group {
        display: flex;
        gap: 2px;
        justify-content: center;
    }
    
    #categoryItemsTable .btn-group .btn {
        padding: 4px 8px;
        font-size: 12px;
    }
    
    /* Ensure table is responsive */
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Fix DataTable responsive issues */
    .dataTables_wrapper .dataTables_scroll {
        overflow-x: auto;
    }
    
    /* Style badges consistently */
    .badge {
        font-size: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => '#', 'icon' => 'bx bx-package'],
            ['label' => 'Categories', 'url' => route('inventory.categories.index'), 'icon' => 'bx bx-category'],
            ['label' => $category->name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">{{ $category->name }} - Category Items</h6>
                <p class="mb-0 text-muted">View all items in this category</p>
            </div>
            <div>
                <a href="{{ route('inventory.categories.index') }}" class="btn btn-secondary me-2">
                    <i class="bx bx-arrow-back me-1"></i>Back to Categories
                </a>
                @can('manage inventory items')
                <a href="{{ route('inventory.items.create', ['category_id' => $category->encoded_id]) }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Add New Item
                </a>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="mb-0">{{ $category->name }}</h4>
                                <p class="text-muted mb-0">{{ $category->description ?? 'No description available' }}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary fs-6">{{ $items->count() }} Items</span>
                            </div>
                        </div>

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

                        <div class="table-responsive">
                            <table id="categoryItemsTable" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Location</th>
                                        <th>Cost Price</th>
                                        <th>Selling Price</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->code }}</td>
                                        <td>{{ $item->location->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($item->resolved_cost_price ?? $item->cost_price, 2) }}</td>
                                        <td>{{ number_format($item->resolved_unit_price ?? $item->unit_price, 2) }}</td>
                                        <td>
                                            <span class="badge rounded-pill text-info bg-light-info p-2 text-uppercase px-3">
                                                <i class="bx bxs-circle me-1"></i>{{ $item->current_stock }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($item->is_active)
                                                <span class="badge rounded-pill text-success bg-light-success p-2 text-uppercase px-3">
                                                    <i class="bx bxs-circle me-1"></i>Active
                                                </span>
                                            @else
                                                <span class="badge rounded-pill text-danger bg-light-danger p-2 text-uppercase px-3">
                                                    <i class="bx bxs-circle me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('inventory.items.show', $item) }}" class="btn btn-sm btn-outline-info" title="View">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                @can('manage inventory items')
                                                <a href="{{ route('inventory.items.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
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
    $('#categoryItemsTable').DataTable({
        responsive: true,
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: '<div class="text-center p-4"><i class="bx bx-package font-24 text-muted"></i><p class="text-muted mt-2">No items found in this category.</p></div>',
            search: "",
            searchPlaceholder: "Search items...",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            zeroRecords: "No matching items found",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        columnDefs: [
            { width: '20%', targets: 0 }, // Name
            { width: '10%', targets: 1 }, // Code
            { width: '15%', targets: 2 }, // Location
            { width: '12%', targets: 3 }, // Cost Price
            { width: '12%', targets: 4 }, // Selling Price
            { width: '12%', targets: 5 }, // Current Stock
            { width: '10%', targets: 6 }, // Status
            { width: '9%', targets: 7, orderable: false, searchable: false } // Actions
        ]
    });
});
</script>
@endpush 