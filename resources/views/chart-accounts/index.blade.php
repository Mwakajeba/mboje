@extends('layouts.main')

@section('title', 'Chart of Accounts')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Breadcrumbs -->
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Chart of Accounts', 'url' => '#', 'icon' => 'bx bx-spreadsheet']
             ]" />
            <!-- End Breadcrumbs -->

            <!-- Page Title Box -->
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="page-title mb-0">Chart of Accounts</h4>
                       
                    </div>
                    <div class="col-md-6">
                        <div class="float-end">
                            <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bx bx-import me-1"></i>Import Accounts
                            </button>
                            <a href="{{ route('accounting.chart-accounts.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i>Add New Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card mini-stat">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Total Accounts</p>
                                    <h4 class="mb-0" id="total-accounts">0</h4>
                                </div>
                                <div class="flex-shrink-0 text-end">
                                    <div class="avatar-sm rounded-circle bg-primary align-self-center mini-stat-icon">
                                        <span class="avatar-title">
                                            <i class="bx bx-spreadsheet font-size-24"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card mini-stat">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Cash Flow Accounts</p>
                                    <h4 class="mb-0" id="cash-flow-accounts">0</h4>
                                </div>
                                <div class="flex-shrink-0 text-end">
                                    <div class="avatar-sm rounded-circle bg-success align-self-center mini-stat-icon">
                                        <span class="avatar-title">
                                            <i class="bx bx-money-withdraw font-size-24"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card mini-stat">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Equity Accounts</p>
                                    <h4 class="mb-0" id="equity-accounts">0</h4>
                                </div>
                                <div class="flex-shrink-0 text-end">
                                    <div class="avatar-sm rounded-circle bg-warning align-self-center mini-stat-icon">
                                        <span class="avatar-title">
                                            <i class="bx bx-pie-chart-alt font-size-24"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card mini-stat">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="text-muted fw-medium">Active Accounts</p>
                                    <h4 class="mb-0" id="active-accounts">0</h4>
                                </div>
                                <div class="flex-shrink-0 text-end">
                                    <div class="avatar-sm rounded-circle bg-info align-self-center mini-stat-icon">
                                        <span class="avatar-title">
                                            <i class="bx bx-check-circle font-size-24"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="chart-accounts-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Account Class</th>
                                    <th>Account Group</th>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Cash Flow</th>
                                    <th>Cash Flow Category</th>
                                    <th>Equity</th>
                                    <th>Equity Category</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <!-- ... -->
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Chart of Accounts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('accounting.chart-accounts.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="file" class="form-label">Choose Excel File</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".xlsx, .xls, .csv" required>
                        </div>
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-1"></i>
                            Download the template below to ensure your data is in the correct format.
                            <br><br>
                            <a href="{{ route('accounting.chart-accounts.template') }}" class="btn btn-sm btn-outline-info">
                                <i class="bx bx-download me-1"></i>Download Template
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Import Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Set up CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize DataTable
    var table = $('#chart-accounts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("accounting.chart-accounts.index") }}',
            type: 'GET',
            error: function (xhr, error, thrown) {
                console.error('DataTables AJAX Error:', error);
                console.error('XHR Status:', xhr.status);
                console.error('XHR Response:', xhr.responseText);
                
                // Show user-friendly error message
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to load chart accounts data. Please refresh the page and try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        columns: [
            {data: 'account_class', name: 'account_class'},
            {data: 'account_group', name: 'account_group'},
            {data: 'account_code', name: 'account_code'},
            {data: 'account_name', name: 'account_name'},
            {data: 'cash_flow_badge', name: 'has_cash_flow', orderable: false, searchable: false},
            {data: 'cash_flow_category', name: 'cash_flow_category', orderable: false, searchable: false},
            {data: 'equity_badge', name: 'has_equity', orderable: false, searchable: false},
            {data: 'equity_category', name: 'equity_category', orderable: false, searchable: false},
            {data: 'created_at_formatted', name: 'created_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[2, 'asc']], // Sort by account code by default
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        },
        drawCallback: function(settings) {
            // Update stats after data is loaded
            updateStats();
        }
    });

    // Function to update statistics
    function updateStats() {
        $.ajax({
            url: '{{ route("accounting.chart-accounts.index") }}',
            type: 'GET',
            data: { stats: true },
            success: function(response) {
                $('#total-accounts').text(response.total || 0);
                $('#cash-flow-accounts').text(response.cash_flow || 0);
                $('#equity-accounts').text(response.equity || 0);
                $('#active-accounts').text(response.active || 0);
            },
            error: function(xhr, error, thrown) {
                console.error('Stats AJAX Error:', error);
                console.error('XHR Status:', xhr.status);
                console.error('XHR Response:', xhr.responseText);
                
                // Set default values on error
                $('#total-accounts').text('0');
                $('#cash-flow-accounts').text('0');
                $('#equity-accounts').text('0');
                $('#active-accounts').text('0');
            }
        });
    }

    // Delete account function
    window.deleteChartAccount = function(encodedId, accountName) {
        $('#account-name').text(accountName);
        $('#delete-form').attr('action', '{{ url("accounting/chart-accounts") }}/' + encodedId);
        $('#deleteModal').modal('show');
    };

    // Handle delete form submission
    $('#delete-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#deleteModal').modal('hide');
                table.ajax.reload();
                
                // Show success message
                Swal.fire({
                    title: 'Success!',
                    text: 'Chart account deleted successfully',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            },
            error: function(xhr) {
                $('#deleteModal').modal('hide');
                
                // Show error message
                Swal.fire({
                    title: 'Error!',
                    text: xhr.responseJSON?.message || 'An error occurred while deleting the account',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});
</script>
@endpush