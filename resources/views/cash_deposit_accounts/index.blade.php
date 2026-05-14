@extends('layouts.main')

@section('title', 'Cash Deposit Accounts')

@section('content')
<div class="page-wrapper">
    <div class="page-content">

        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Cash Deposit Accounts', 'url' => '#', 'icon' => 'bx bx-credit-card']
        ]" /> 
        <h6 class="mb-0 text-uppercase">CASH COLLATERAL TYPES</h6>
        <hr/>

        <!-- Stats Card -->
        <div class="row row-cols-1 row-cols-lg-4 mb-4">
            <div class="col">
                <div class="card radius-10 shadow-sm border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Types</p>
                            <h4 class="mb-0 fw-bold" id="total-count">-</h4>
                        </div>
                        <div class="widgets-icons bg-gradient-warning text-primary"><i class='bx bx-refresh'></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="card radius-10">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Cash Deposit Accounts</h4>
                    <a href="{{ route('cash_deposit_accounts.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add Type
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered dt-responsive nowrap" id="cash-deposit-accounts-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Chart Account</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th class="text-center">Actions</th>
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
    const table = $('#cash-deposit-accounts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("cash_deposit_accounts.index") }}',
            type: 'GET'
        },
        columns: [
            { data: 'name' },
            { data: 'chartAccount.account_name' },
            { data: 'description' },
            { data: 'status_badge' },
            { data: 'formatted_date' },
            { 
                data: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'asc']], // Sort by name ascending
        pageLength: 10,
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "Search Types:",
            lengthMenu: "Show _MENU_ types per page",
            info: "Showing _START_ to _END_ of _TOTAL_ types",
            infoEmpty: "Showing 0 to 0 of 0 types",
            infoFiltered: "(filtered from _MAX_ total types)",
            emptyTable: `
                <div class="text-center text-muted py-4">
                    <i class="bx bx-credit-card fs-1 d-block mb-2"></i>
                    <h6>No Cash Deposit Types Found</h6>
                    <p class="mb-0">Get started by creating your first cash deposit type</p>
                    <a href="{{ route('cash_deposit_accounts.create') }}" class="btn btn-primary btn-sm mt-2">
                        <i class="bx bx-plus me-1"></i> Add Type
                    </a>
                </div>
            `
        },
        drawCallback: function(settings) {
            // Update total count
            $('#total-count').text(settings.json.recordsTotal || 0);
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
    window.refreshCashDepositAccountsTable = refreshTable;
    
    // Handle delete form submissions
    $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const typeName = form.find('button').data('name');
        
        if (!confirm(`Are you sure you want to delete cash deposit type "${typeName}"? This action cannot be undone.`)) {
            return;
        }

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    alert('Cash deposit type deleted successfully');
                    table.ajax.reload(); // Reload DataTable
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error deleting cash deposit type. Please try again.');
            }
        });
    });
});
</script>
@endpush
