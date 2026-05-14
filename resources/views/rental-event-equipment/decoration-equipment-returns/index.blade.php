@extends('layouts.main')

@section('title', 'Decoration Equipment Returns')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Equipment Returns', 'url' => '#', 'icon' => 'bx bx-undo'],
            ]" />

            <h6 class="mb-0 text-uppercase">DECORATION EQUIPMENT RETURNS</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-undo me-1 font-22 text-primary"></i></div>
                                    <h5 class="mb-0 text-primary">Decoration Returns</h5>
                                </div>
                                <div>
                                    <a href="{{ route('rental-event-equipment.decoration-equipment-returns.create') }}"
                                       class="btn btn-primary">
                                        <i class="bx bx-plus me-1"></i> New Return
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
                                <table id="decorationReturnsTable" class="table table-striped table-hover">
                                    <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Return No.</th>
                                        <th>Issue No.</th>
                                        <th>Job No.</th>
                                        <th>Customer</th>
                                        <th>Return Date</th>
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
            $('#decorationReturnsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("rental-event-equipment.decoration-equipment-returns.data") }}',
                    type: 'GET'
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'return_number', name: 'return_number'},
                    {data: 'issue_number', name: 'issue_number'},
                    {data: 'job_number', name: 'job_number'},
                    {data: 'customer_name', name: 'customer_name'},
                    {data: 'return_date_formatted', name: 'return_date'},
                    {data: 'status_badge', name: 'status', orderable: false, searchable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false},
                ],
                pageLength: 25,
                order: [[5, 'desc']],
                language: {
                    processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                }
            });
        });
    </script>
@endpush

