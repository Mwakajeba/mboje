@extends('layouts.main')

@section('title', 'Expiry Alerts')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home-alt'],
                ['label' => 'Expiry Alerts', 'url' => '#', 'icon' => 'bx bx-error-circle']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-error-circle me-2"></i>
                                    Expiry Alerts - Loading...
                                </h5>
                                <p class="mb-0 text-muted">Items that will expire within the configured warning period</p>
                            </div>
                            <div class="d-flex gap-2">
                                <!-- Branch Filter -->
                                @if($branches->count() > 1)
                                <div class="dropdown">
                                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bx bx-filter me-1"></i>
                                        @if($selectedBranchId)
                                            {{ $branches->where('id', $selectedBranchId)->first()->name ?? 'All Branches' }}
                                        @else
                                            All Branches
                                        @endif
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('expiry-alerts') }}">All Branches</a></li>
                                        @foreach($branches as $branch)
                                        <li><a class="dropdown-item" href="{{ route('expiry-alerts', ['branch_id' => $branch->id]) }}">{{ $branch->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                
                                <a href="{{ route('inventory.items.index') }}" class="btn btn-light">
                                    <i class="bx bx-package me-1"></i>Manage Inventory
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="expiryAlertsTable" class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Batch Number</th>
                                        <th>Location</th>
                                        <th>Expiry Date</th>
                                        <th>Days Left</th>
                                        <th>Available Qty</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
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
    // Initialize DataTable
    var table = $('#expiryAlertsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('expiry-alerts.data') }}",
            data: function(d) {
                d.branch_id = "{{ $selectedBranchId }}";
            }
        },
        columns: [
            { data: 'item_name', name: 'item_name' },
            { data: 'batch_number', name: 'batch_number' },
            { data: 'location_name', name: 'location_name' },
            { data: 'expiry_date', name: 'expiry_date' },
            { data: 'days_until_expiry', name: 'days_until_expiry' },
            { data: 'available_quantity', name: 'available_quantity' },
            { data: 'status', name: 'status' }
        ],
        order: [[4, 'asc']], // Sort by days until expiry
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading expiry alerts...",
            emptyTable: "No expiring items found",
            zeroRecords: "No expiring items match your search"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function(settings) {
            // Update page title with count
            var info = table.page.info();
            var count = info.recordsTotal;
            document.title = 'Expiry Alerts - ' + count + ' items expiring soon';
            
            // Update header count
            $('.card-title').html('<i class="bx bx-error-circle me-2"></i>Expiry Alerts - ' + count + ' items expiring soon');
        }
    });

    // Auto-refresh every 5 minutes
    setInterval(function() {
        table.ajax.reload(null, false); // Reload without resetting pagination
    }, 300000);
});
</script>
@endpush
