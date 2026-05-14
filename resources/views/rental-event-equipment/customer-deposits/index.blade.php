@extends('layouts.main')

@section('title', 'Customer Deposits')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Customer Deposits', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />
        <h6 class="mb-0 text-uppercase">CUSTOMER DEPOSITS MANAGEMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-money me-1 font-22 text-warning"></i></div>
                                <h5 class="mb-0 text-warning">Customer Deposits</h5>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('rental-event-equipment.customer-deposits.create') }}" class="btn btn-warning">
                                    <i class="bx bx-plus me-1"></i> Record Deposit
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
                            <table id="depositsTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Customer Number</th>
                                        <th>Customer Name</th>
                                        <th>Total Deposited</th>
                                        <th>Total Used</th>
                                        <th>Remaining Balance</th>
                                        <th>First Deposit Date</th>
                                        <th>Last Deposit Date</th>
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
    $('#depositsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("rental-event-equipment.customer-deposits.data") }}',
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'customer_number', name: 'customerNo' },
            { data: 'customer_name', name: 'name' },
            { data: 'total_deposited', name: 'total_deposited', orderable: false },
            { data: 'total_used', name: 'total_used', orderable: false },
            { data: 'remaining_balance', name: 'remaining_balance', orderable: false },
            { data: 'first_deposit_date', name: 'first_deposit_date', orderable: false },
            { data: 'last_deposit_date', name: 'last_deposit_date', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[2, 'asc']],
        responsive: true
    });
});
</script>
@endpush
