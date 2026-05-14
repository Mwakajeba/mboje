@extends('layouts.main')

@section('title', 'Decoration Equipment Issues')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Equipment Issues', 'url' => '#', 'icon' => 'bx bx-user-check'],
            ]" />

            <h6 class="mb-0 text-uppercase">DECORATION EQUIPMENT ISSUES</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-user-check me-1 font-22 text-warning"></i></div>
                                    <h5 class="mb-0 text-warning">Issues to Decorators</h5>
                                </div>
                                <div>
                                    <a href="{{ route('rental-event-equipment.decoration-equipment-issues.create') }}"
                                       class="btn btn-warning">
                                        <i class="bx bx-plus me-1"></i> New Issue
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
                                <table id="decorationIssuesTable" class="table table-striped table-hover">
                                    <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Issue No.</th>
                                        <th>Job No.</th>
                                        <th>Customer</th>
                                        <th>Decorator</th>
                                        <th>Issue Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <!-- Data loaded via AJAX -->
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

@push('styles')
    <style>
        .table th {
            font-weight: 600;
            font-size: 0.875rem;
        }

        .table td {
            vertical-align: middle;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#decorationIssuesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("rental-event-equipment.decoration-equipment-issues.data") }}',
                    type: 'GET'
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'issue_number', name: 'issue_number'},
                    {data: 'job_number', name: 'job_number'},
                    {data: 'customer_name', name: 'customer_name'},
                    {data: 'decorator_name', name: 'decorator_name'},
                    {data: 'issue_date_formatted', name: 'issue_date'},
                    {data: 'status_badge', name: 'status', orderable: false, searchable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false},
                ],
                pageLength: 25,
                order: [[5, 'desc']],
                language: {
                    processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                }
            });
        });
    </script>
@endpush

