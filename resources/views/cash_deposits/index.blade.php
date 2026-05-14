@extends('layouts.main')

@section('title', 'Cash Deposits')

@push('styles')
<style>
    .customer-group {
        cursor: pointer;
        padding: 6px 10px;
        border-radius: 4px;
        transition: background-color 0.2s;
        font-weight: 500;
        color: #007bff;
        border: 1px solid transparent;
    }
    
    .customer-group:hover {
        background-color: #e3f2fd;
        border-color: #007bff;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Cash Deposits', 'url' => '#', 'icon' => 'bx bx-credit-card']
        ]" />
        <h6 class="mb-0 text-uppercase">CASH DEPOSITS</h6>
        <hr />

        <!-- Stats Card -->
        <div class="row row-cols-1 row-cols-lg-4 mb-4">
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Deposits</p>
                            <h4 class="mb-0" id="total-count">-</h4>
                        </div>
                        <div class="ms-3">
                            <div class="avatar-sm bg-warning text-white rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bx bx-wallet font-size-24"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="card radius-10">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Cash Deposit List</h4>
                    <div>
                        @can('create cash deposit')
                        <a href="{{ route('cash_deposits.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus"></i> Add Deposit
                        </a>
                        @endcan
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered dt-responsive nowrap" id="cash-deposits-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Balance</th>
                                <th>Last Updated</th>
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
    try {
        const table = $('#cash-deposits-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("cash_deposits.index") }}',
            type: 'GET'
        },
        columns: [
            { 
                data: 'customer_info',
                name: 'customer_name',
                render: function(data, type, row, meta) {
                    if (type === 'display') {
                        return `<div class="customer-group" data-customer-id="${row.customer_id}">${data}</div>`;
                    }
                    return data;
                }
            },
            { 
                data: 'formatted_balance',
                name: 'total_balance',
                className: 'text-end'
            },
            { 
                data: 'formatted_date',
                name: 'last_updated'
            },
            { 
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'asc']], // Sort by customer name ascending
        pageLength: 10,
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "Search Deposits:",
            lengthMenu: "Show _MENU_ deposits per page",
            info: "Showing _START_ to _END_ of _TOTAL_ deposits",
            infoEmpty: "Showing 0 to 0 of 0 deposits",
            infoFiltered: "(filtered from _MAX_ total deposits)",
            emptyTable: `
                <div class="text-center text-muted py-4">
                    <i class="bx bx-wallet fs-1 d-block mb-2"></i>
                    <h6>No Cash Deposits Found</h6>
                    <p class="mb-0">Get started by creating your first cash deposit</p>
                    <a href="{{ route('cash_deposits.create') }}" class="btn btn-primary btn-sm mt-2">
                        <i class="bx bx-plus me-1"></i> Add Deposit
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
    window.refreshCashDepositsTable = refreshTable;
    
    // Handle customer group clicks to view transactions
    $(document).on('click', '.customer-group', function(e) {
        e.stopPropagation();
        
        const customerId = $(this).data('customer-id');
        
        // Navigate to customer transactions page using AJAX to get the encoded ID
        $.ajax({
            url: '{{ route("cash_deposits.index") }}',
            type: 'GET',
            data: {
                customer_id: customerId,
                get_encoded_id: true
            },
            success: function(response) {
                if (response.encoded_id) {
                    window.location.href = '{{ route("cash_deposits.customer_transactions", ":customerId") }}'.replace(':customerId', response.encoded_id);
                }
            },
            error: function() {
                // Fallback to direct navigation
                window.location.href = `/cash_deposits/customer/${customerId}/transactions`;
            }
        });
    });
    
    // Handle withdraw button clicks from the table
    $(document).on('click', '.withdraw-btn', function(e) {
        e.stopPropagation();
        
        const customerId = $(this).data('customer-id');
        const customerName = $(this).data('customer-name');
        
        // Get the encoded customer ID for total balance withdrawal
        $.ajax({
            url: '{{ route("cash_deposits.index") }}',
            type: 'GET',
            data: {
                customer_id: customerId,
                get_encoded_id: true
            },
            success: function(response) {
                if (response.encoded_id) {
                    Swal.fire({
                        title: 'Withdraw from Customer',
                        text: `Do you want to withdraw from ${customerName}'s total balance?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Withdraw',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `/cash_deposits/${response.encoded_id}/withdraw`;
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to get customer information.',
                        icon: 'error'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to get customer information.',
                    icon: 'error'
                });
            }
        });
    });
    

    
    // Handle delete form submissions
    $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const customerName = form.find('button').data('name');
        
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete cash deposit for "${customerName}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the cash deposit.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Cash deposit has been deleted successfully.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                table.ajax.reload(); // Reload DataTable
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || 'Failed to delete cash deposit.',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Delete error:', xhr);
                        let errorMessage = 'Failed to delete cash deposit. Please try again.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            errorMessage = xhr.responseText;
                        }
                        
                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });
    } catch (error) {
        console.error('DataTable initialization error:', error);
        alert('Error initializing table. Please refresh the page.');
    }
});
</script>
@endpush