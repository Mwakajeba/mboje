@extends('layouts.main')

@section('title', 'Cashflow Forecasting')

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
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Cashflow Forecasting', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-trending-up me-2"></i>Cashflow Forecasting</h5>
                                <p class="mb-0 text-muted">Predict future cash positions and plan strategically</p>
                            </div>
                            <div>
                                <a href="{{ route('accounting.cashflow-forecasts.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>New Forecast
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
                                <p class="mb-0 text-secondary">Total Forecasts</p>
                                <h4 class="my-1 text-primary" id="total-forecasts">{{ number_format($totalForecasts) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-trending-up align-middle"></i> All forecasts</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-trending-up"></i>
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
                                <p class="mb-0 text-secondary">Active Forecasts</p>
                                <h4 class="my-1 text-success" id="active-forecasts">{{ number_format($activeForecasts) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Currently active</span>
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
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Inflows</p>
                                <h4 class="my-1 text-info" id="total-inflows">TZS {{ number_format($totalInflows, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-up-arrow-alt align-middle"></i> Expected income</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-up-arrow-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-{{ $netCashflow >= 0 ? 'success' : 'danger' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Net Cashflow</p>
                                <h4 class="my-1 text-{{ $netCashflow >= 0 ? 'success' : 'danger' }}" id="net-cashflow">
                                    <i class="bx {{ $netCashflow >= 0 ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' }}"></i>
                                    TZS {{ number_format(abs($netCashflow), 2) }}
                                </h4>
                                <p class="mb-0 font-13">
                                    <span class="text-{{ $netCashflow >= 0 ? 'success' : 'danger' }}">
                                        <i class="bx {{ $netCashflow >= 0 ? 'bx-trending-up' : 'bx-trending-down' }} align-middle"></i> 
                                        {{ $netCashflow >= 0 ? 'Positive' : 'Negative' }} balance
                                    </span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-{{ $netCashflow >= 0 ? 'success' : 'danger' }} text-white ms-auto">
                                <i class="bx {{ $netCashflow >= 0 ? 'bx-trending-up' : 'bx-trending-down' }}"></i>
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
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select id="branch-filter" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Scenario</label>
                                <select id="scenario-filter" class="form-select">
                                    <option value="">All Scenarios</option>
                                    <option value="best_case">Best Case</option>
                                    <option value="base_case">Base Case</option>
                                    <option value="worst_case">Worst Case</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="button" id="reset-filters" class="btn btn-secondary w-100">
                                        <i class="bx bx-refresh me-1"></i>Reset Filters
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="cashflow-forecasts-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Forecast Name</th>
                                        <th>Scenario</th>
                                        <th>Timeline</th>
                                        <th>Branch</th>
                                        <th>Period</th>
                                        <th>Starting Balance</th>
                                        <th>Total Inflows</th>
                                        <th>Total Outflows</th>
                                        <th>Net Cashflow</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#cashflow-forecasts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("accounting.cashflow-forecasts.index") }}',
            type: 'GET',
            data: function(d) {
                d.branch_id = $('#branch-filter').val();
                d.scenario = $('#scenario-filter').val();
            }
        },
        columns: [
            {data: 'forecast_name', name: 'forecast_name'},
            {data: 'scenario_badge', name: 'scenario'},
            {data: 'timeline_badge', name: 'timeline'},
            {data: 'branch_name', name: 'branch_name'},
            {data: 'period', name: 'period'},
            {data: 'starting_balance_formatted', name: 'starting_cash_balance'},
            {data: 'total_inflows', name: 'total_inflows'},
            {data: 'total_outflows', name: 'total_outflows'},
            {data: 'net_cashflow', name: 'net_cashflow'},
            {data: 'status_badge', name: 'is_active'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[0, 'desc']], // Sort by forecast name descending
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            emptyTable: "No cashflow forecasts found",
            zeroRecords: "No matching cashflow forecasts found"
        }
    });

    // Filter handlers
    $('#branch-filter, #scenario-filter').on('change', function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#reset-filters').on('click', function() {
        $('#branch-filter').val('');
        $('#scenario-filter').val('');
        table.ajax.reload();
    });

    // Handle regenerate forecast
    $(document).on('click', '.regenerate-btn', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var forecastId = $(this).data('forecast-id');
        
        Swal.fire({
            title: 'Regenerate Forecast?',
            text: "This will delete all existing forecast items and regenerate them from source data.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, regenerate!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        Swal.fire(
                            'Regenerated!',
                            'Forecast has been regenerated successfully.',
                            'success'
                        );
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while regenerating the forecast.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        
                        Swal.fire(
                            'Error!',
                            errorMessage,
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
@endpush
@endsection
