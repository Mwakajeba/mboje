@extends('layouts.main')

@section('title', 'Bank Accounts')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Bank Accounts', 'url' => '#', 'icon' => 'bx bx-bank']
            ]" />
            
            <div class="row row-cols-1 row-cols-lg-4">
                <div class="col">
                    <div class="card radius-10">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0">Total Accounts</p>
                                    <h4 class="font-weight-bold">{{ $totalAccounts }}</h4>
                                </div>
                                <div class="widgets-icons bg-gradient-cosmic text-white"><i class='bx bx-dollar'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0">Total Balance</p>
                                    <h4 class="font-weight-bold">{{ number_format($totalBalance ?? 0, 2) }}</h4>
                                </div>
                                <div class="widgets-icons bg-gradient-cosmic text-white"><i class='bx bx-wallet'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0">Positive Balance</p>
                                    <h4 class="font-weight-bold text-success">{{ $positiveBalanceAccounts }}</h4>
                                </div>
                                <div class="widgets-icons bg-gradient-success text-white"><i class='bx bx-trending-up'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0">Negative Balance</p>
                                    <h4 class="font-weight-bold text-danger">{{ $negativeBalanceAccounts }}</h4>
                                </div>
                                <div class="widgets-icons bg-gradient-danger text-white"><i class='bx bx-trending-down'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                <!--end row-->

                <h6 class="mb-0 text-uppercase">BANK ACCOUNTS</h6>
                <hr />
                <div class="card">
                    <div class="card-body">
                        @can('create bank account')
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Bank Accounts</h5>
                            <a href="{{ route('accounting.bank-accounts.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus"></i> Add New Bank Account
                            </a>
                        </div>
                        @endcan
                        <div class="table-responsive">
                            <table id="bankAccountsTable" class="table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Bank Name</th>
                                        <th>Account Number</th>
                                        <th>Chart Account</th>
                                        <th>Account Class</th>
                                        <th>Account Group</th>
                                        <th>Balance</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
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
        <!--end page wrapper -->
        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        <!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->
        <footer class="page-footer">
            <p class="mb-0">Copyright © 2021. All right reserved.</p>
        </footer>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        $(document).ready(function () {
            // Initialize DataTable with AJAX
            const table = $('#bankAccountsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('accounting.bank-accounts.data') }}",
                    type: 'GET'
                },
                columns: [
                    { 
                        data: 'DT_RowIndex', 
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '5%'
                    },
                    { 
                        data: 'name', 
                        name: 'name',
                        width: '15%'
                    },
                    { 
                        data: 'account_number', 
                        name: 'account_number',
                        width: '12%'
                    },
                    { 
                        data: 'chart_account', 
                        name: 'chart_account',
                        width: '15%'
                    },
                    { 
                        data: 'account_class', 
                        name: 'account_class',
                        width: '12%'
                    },
                    { 
                        data: 'account_group', 
                        name: 'account_group',
                        width: '12%'
                    },
                    { 
                        data: 'balance', 
                        name: 'balance',
                        width: '10%',
                        render: function(data, type, row) {
                            if (data >= 0) {
                                return '<span class="text-success fw-bold">' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';
                            } else {
                                return '<span class="text-danger fw-bold">' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';
                            }
                        }
                    },
                    { 
                        data: 'created_at', 
                        name: 'created_at',
                        width: '10%'
                    },
                    { 
                        data: 'actions', 
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: '15%'
                    }
                ],
                order: [[7, 'desc']], // Order by created_at desc
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, 500, -1], [10, 25, 50, 100, 500, "All"]],
                responsive: true,
                language: {
                    processing: "Loading bank accounts...",
                    emptyTable: "No bank accounts found",
                    zeroRecords: "No matching bank accounts found",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: 'lfrtip'
            });

            // Delete confirmation (using event delegation for dynamically loaded content)
            $(document).on('submit', '.delete-form', function (e) {
                e.preventDefault();
                const form = $(this);
                const name = form.find('button[type="submit"]').data('name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to delete "${name}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form[0].submit();
                    }
                });
            });

            // Refresh table after successful operations
            window.refreshBankAccountsTable = function() {
                table.ajax.reload();
            };
        });
    </script>
@endpush