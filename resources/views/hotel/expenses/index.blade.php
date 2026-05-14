@extends('layouts.main')

@section('title', 'Payment Vouchers')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Breadcrumb -->
            <div class="row">
                <div class="col-12">
                    <div class="page-breadcrumb d-flex align-items-center">
                        <div class="me-auto">
                            <x-breadcrumbs-with-icons :links="[
                                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                                ['label' => 'Payment Vouchers', 'url' => '#', 'icon' => 'bx bx-receipt']
                            ]" />
                        </div>
                        @can('create payment voucher')
                        <div class="ms-auto d-flex gap-2">
                             <a href="{{ route('hotel.expenses.create') }}" class="btn btn-primary">
                                 <i class="bx bx-plus"></i> Record Expense
                            </a>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
             <h6 class="mb-0 text-uppercase">HOTEL EXPENSES</h6>
            <hr />

            <!-- Dashboard Stats -->
            <div class="row row-cols-1 row-cols-lg-4 mb-4">
                <div class="col mb-4">
                    <div class="card radius-10">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Total Payments</p>
                                <h4 class="mb-0">{{ $stats['total'] ?? 0 }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-burning text-white"><i class='bx bx-receipt'></i></div>
                        </div>
                    </div>
                </div>

                <div class="col mb-4">
                    <div class="card radius-10">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">This Month</p>
                                <h4 class="mb-0">{{ $stats['this_month'] ?? 0 }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-cosmic text-white"><i class='bx bx-calendar'></i></div>
                        </div>
                    </div>
                </div>

                <div class="col mb-4">
                    <div class="card radius-10">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Total Amount</p>
                                <h4 class="mb-0">{{ number_format($stats['total_amount'] ?? 0, 2) }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-primary text-white"><i class='bx bx-dollar'></i></div>
                        </div>
                    </div>
                </div>

                <div class="col mb-4">
                    <div class="card radius-10">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">This Month Amount</p>
                                <h4 class="mb-0">{{ number_format($stats['this_month_amount'] ?? 0, 2) }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-success text-white"><i class='bx bx-money'></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card radius-10">
                        <div class="card-body">
                            <div class="table-responsive">
                                 <table class="table table-bordered dt-responsive nowrap w-100" id="hotelExpensesTable">
                                    <thead>
                                        <tr>
                                            <th width="10%">Date</th>
                                            <th width="12%">Reference</th>
                                             <th width="12%">Bank Account</th>
                                             <th width="12%">Scope</th>
                                            <th width="12%">Description</th>
                                            <th width="10%">Amount</th>
                                            <th width="10%">Status</th>
                                            <th width="8%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via Ajax -->
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

@push("scripts")
    <script nonce="{{ $cspNonce ?? '' }}">
        $(document).ready(function () {
            // Initialize DataTable with Ajax
             var table = $('#hotelExpensesTable').DataTable({
                processing: true,
                serverSide: true,
                 ajax: {
                     url: '{{ url('accounting/payment-vouchers/datatable') }}',
                     data: function(d){
                         d.payee_type = 'hotel';
                     },
                    type: 'GET',
                    error: function(xhr, error, code) {
                        console.error('DataTables Ajax Error:', error, code);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to load expenses data. Please refresh the page.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                 columns: [
                    { data: 'formatted_date', name: 'date', title: 'Date', orderable: true, searchable: true },
                    { data: 'reference_link', name: 'reference', title: 'Reference', orderable: true, searchable: true },
                    { data: 'bank_account_name', name: 'bankAccount.name', title: 'Bank Account', orderable: true, searchable: true },
                     { data: 'payee_info', name: 'payee_info', title: 'Scope', orderable: false, searchable: false },
                    { data: 'description_limited', name: 'description', title: 'Description', orderable: false, searchable: true },
                    { data: 'formatted_amount', name: 'amount', title: 'Amount', orderable: true, searchable: false },
                    { data: 'status_badge', name: 'approved', title: 'Status', orderable: true, searchable: false },
                    { data: 'actions', name: 'actions', title: 'Actions', orderable: false, searchable: false }
                ],
                responsive: true,
                order: [[0, 'desc']], // Sort by date descending by default
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    search: "",
                    searchPlaceholder: "Search payment vouchers...",
                    processing: '<div class="d-flex justify-content-center align-items-center p-3"><div class="spinner-border text-primary me-2" role="status"><span class="visually-hidden">Loading...</span></div><span class="text-primary">Loading payment vouchers...</span></div>',
                    emptyTable: "No payment vouchers found",
                    info: "Showing _START_ to _END_ of _TOTAL_ payment vouchers",
                    infoEmpty: "Showing 0 to 0 of 0 payment vouchers",
                    infoFiltered: "(filtered from _MAX_ total payment vouchers)",
                    lengthMenu: "Show _MENU_ payment vouchers per page",
                    zeroRecords: "No matching payment vouchers found"
                },
                columnDefs: [
                    {
                        targets: -1, // Actions column
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        responsivePriority: 1
                    },
                    {
                        targets: 5, // Amount column
                        className: 'text-end',
                        responsivePriority: 2
                    },
                    {
                        targets: [0, 1, 2], // Priority columns for responsive
                        responsivePriority: 3
                    }
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                drawCallback: function(settings) {
                    // Reinitialize tooltips after each draw
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Handle delete button clicks for hotel expenses
            $('#hotelExpensesTable').on('click', '.delete-hotel-expense-btn', function () {
                const expenseId = $(this).data('expense-id');
                const expenseReference = $(this).data('expense-reference');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to delete expense "${expenseReference}"? This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait while we delete the expense.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Use AJAX instead of form submission to maintain loading state
                        $.ajax({
                            url: `/hotel/expenses/${expenseId}`,
                            type: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Expense has been deleted successfully.",
                                    icon: "success",
                                    confirmButtonText: "OK"
                                }).then(() => {
                                    table.ajax.reload(null, false); // Reload table without resetting pagination
                                });
                            },
                            error: function(xhr) {
                                let errorMessage = "An error occurred while deleting the expense.";
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.responseText) {
                                    // Try to extract error from response
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(xhr.responseText, 'text/html');
                                    const errorElement = doc.querySelector('.error, .alert-danger');
                                    if (errorElement) {
                                        errorMessage = errorElement.textContent.trim();
                                    }
                                }
                                
                                Swal.fire({
                                    title: "Error!",
                                    text: errorMessage,
                                    icon: "error",
                                    confirmButtonText: "OK"
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush