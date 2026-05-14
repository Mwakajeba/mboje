@extends('layouts.main')

@section('title', 'Decoration Equipment Losses')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Loss Handling', 'url' => '#', 'icon' => 'bx bx-error'],
            ]" />

            <h6 class="mb-0 text-uppercase">DECORATION LOSS HANDLING</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-error me-1 font-22 text-danger"></i></div>
                                    <h5 class="mb-0 text-danger">Decoration Equipment Losses</h5>
                                </div>
                                <div>
                                    <a href="{{ route('rental-event-equipment.decoration-losses.create') }}"
                                       class="btn btn-danger">
                                        <i class="bx bx-plus me-1"></i> Record Loss
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

                            <div class="alert alert-info small">
                                <i class="bx bx-info-circle me-1"></i>
                                Use this screen to classify confirmed equipment losses as <strong>Business Expense</strong>
                                or <strong>Employee Liability</strong>. Stock quantities are already adjusted during
                                returns; this is for responsibility and accounting tracking.
                            </div>

                            <div class="table-responsive">
                                <table id="decorationLossesTable" class="table table-striped table-hover">
                                    <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Loss No.</th>
                                        <th>Job</th>
                                        <th>Customer</th>
                                        <th>Equipment</th>
                                        <th class="text-end">Qty Lost</th>
                                        <th>Loss Type</th>
                                        <th>Loss Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <!-- Data via AJAX -->
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
        $(document).ready(function () {
            $('#decorationLossesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("rental-event-equipment.decoration-losses.data") }}',
                    type: 'GET'
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'loss_number', name: 'loss_number'},
                    {data: 'job_number', name: 'job_number'},
                    {data: 'customer_name', name: 'customer_name'},
                    {data: 'equipment_name', name: 'equipment_name'},
                    {data: 'quantity_lost', name: 'quantity_lost', className: 'text-end'},
                    {data: 'loss_type_badge', name: 'loss_type', orderable: false, searchable: false},
                    {data: 'loss_date_formatted', name: 'loss_date'},
                    {data: 'status_badge', name: 'status', orderable: false, searchable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false},
                ],
                pageLength: 25,
                order: [[7, 'desc']],
                language: {
                    processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                }
            });
        });
    </script>
@endpush

