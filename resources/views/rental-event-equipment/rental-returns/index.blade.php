@extends('layouts.main')

@section('title', 'Rental Returns')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Returns', 'url' => '#', 'icon' => 'bx bx-undo']
        ]" />
        <h6 class="mb-0 text-uppercase">RENTAL RETURNS MANAGEMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-undo me-1 font-22 text-primary"></i></div>
                                <h5 class="mb-0 text-primary">Rental Returns</h5>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('rental-event-equipment.rental-returns.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> Record Return
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

                        <div class="table-responsive">
                            <table id="returnsTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Return Number</th>
                                        <th>Customer</th>
                                        <th>Contract</th>
                                        <th>Dispatch</th>
                                        <th>Return Date</th>
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
    $('#returnsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("rental-event-equipment.rental-returns.data") }}',
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'return_number', name: 'return_number' },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'contract_number', name: 'contract_number' },
            { data: 'dispatch_number', name: 'dispatch_number' },
            { data: 'return_date_formatted', name: 'return_date' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[5, 'desc']],
        responsive: true
    });
});
</script>
@endpush
