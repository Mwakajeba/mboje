@extends('layouts.main')

@section('title', 'Rental Dispatches')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Dispatches', 'url' => '#', 'icon' => 'bx bx-send']
        ]" />
        <h6 class="mb-0 text-uppercase">RENTAL DISPATCHES MANAGEMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-send me-1 font-22 text-info"></i></div>
                                <h5 class="mb-0 text-info">Rental Dispatches
                                    @if(isset($dueSoonDispatches) && count($dueSoonDispatches) > 0)
                                        <span class="badge bg-warning ms-2">Due Soon ({{ count($dueSoonDispatches) }})</span>
                                    @endif
                                </h5>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('rental-event-equipment.rental-dispatches.create') }}" class="btn btn-info">
                                    <i class="bx bx-plus me-1"></i> Create Dispatch
                                </a>
                            </div>
                        </div>
                        <hr />

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(isset($overdueDispatches) && count($overdueDispatches) > 0)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <h6 class="alert-heading"><i class="bx bx-error-circle me-2"></i>Overdue Returns ({{ count($overdueDispatches) }})</h6>
                                <ul class="mb-0">
                                    @foreach($overdueDispatches as $item)
                                        <li>
                                            <strong>{{ $item['dispatch']->dispatch_number }}</strong> - 
                                            {{ $item['dispatch']->customer->name ?? 'N/A' }} - 
                                            <strong>{{ $item['days_overdue'] }} day(s) overdue</strong> 
                                            (Expected: {{ $item['dispatch']->expected_return_date->format('M d, Y') }})
                                        </li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(isset($dueSoonDispatches) && count($dueSoonDispatches) > 0)
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <h6 class="alert-heading"><i class="bx bx-time me-2"></i>Due Soon ({{ count($dueSoonDispatches) }})</h6>
                                <ul class="mb-0">
                                    @foreach($dueSoonDispatches as $item)
                                        <li>
                                            <strong>{{ $item['dispatch']->dispatch_number }}</strong> - 
                                            {{ $item['dispatch']->customer->name ?? 'N/A' }} - 
                                            <strong>{{ $item['days_remaining'] }} day(s) remaining</strong> 
                                            (Due: {{ $item['dispatch']->expected_return_date->format('M d, Y') }})
                                        </li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table id="dispatchesTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Dispatch Number</th>
                                        <th>Customer</th>
                                        <th>Contract</th>
                                        <th>Dispatch Date</th>
                                        <th>Expected Return</th>
                                        <th>Status</th>
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
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#dispatchesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("rental-event-equipment.rental-dispatches.data") }}',
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'dispatch_number', name: 'dispatch_number' },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'contract_number', name: 'contract_number' },
            { data: 'dispatch_date_formatted', name: 'dispatch_date' },
            { data: 'expected_return_date_formatted', name: 'expected_return_date', orderable: true },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[4, 'desc']],
        responsive: true
    });
});
</script>
@endpush
