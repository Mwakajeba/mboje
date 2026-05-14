@extends('layouts.main')

@section('title', 'Decoration Jobs Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Decoration Jobs', 'url' => '#', 'icon' => 'bx bx-calendar-event']
        ]" />

            <h6 class="mb-0 text-uppercase">DECORATION JOBS MANAGEMENT</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-calendar-event me-1 font-22 text-info"></i></div>
                                    <h5 class="mb-0 text-info">Decoration Jobs</h5>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('rental-event-equipment.decoration-jobs.create') }}"
                                        class="btn btn-info">
                                        <i class="bx bx-plus me-1"></i> Create Decoration Job
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
                                <table id="decorationJobsTable" class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Job Number</th>
                                            <th>Customer</th>
                                            <th>Event Date</th>
                                            <th>Event Theme</th>
                                            <th>Package</th>
                                            <th>Status</th>
                                            <th>Agreed Price</th>
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

        .card-title {
            font-size: 1rem;
            font-weight: 600;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5em 0.75em;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#decorationJobsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("rental-event-equipment.decoration-jobs.data") }}',
                    type: 'GET'
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'job_number', name: 'job_number' },
                    { data: 'customer_name', name: 'customer_name' },
                    { data: 'event_date_formatted', name: 'event_date' },
                    { data: 'event_theme', name: 'event_theme' },
                    { data: 'package_name', name: 'package_name' },
                    { data: 'status_badge', name: 'status', orderable: false },
                    { data: 'agreed_price_formatted', name: 'agreed_price' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[3, 'desc']],
                responsive: true,
                language: {
                    processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-info" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                }
            });
        });
    </script>
@endpush