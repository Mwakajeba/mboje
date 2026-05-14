@extends('layouts.main')

@section('title', 'Decoration Equipment Plans')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Equipment Plans', 'url' => '#', 'icon' => 'bx bx-list-check'],
            ]" />

            <h6 class="mb-0 text-uppercase">DECORATION EQUIPMENT PLANNING</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-list-check me-1 font-22 text-info"></i></div>
                                    <h5 class="mb-0 text-info">Equipment Plans</h5>
                                </div>
                                <div>
                                    <a href="{{ route('rental-event-equipment.decoration-equipment-plans.create') }}"
                                       class="btn btn-info">
                                        <i class="bx bx-plus me-1"></i> New Plan
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
                                Use equipment plans to prepare internal pick lists for decoration jobs before issuing
                                equipment to decorators. Plans are for internal coordination only and do not affect stock
                                until items are issued.
                            </div>

                            <div class="table-responsive">
                                <table id="decorationEquipmentPlansTable" class="table table-striped table-hover">
                                    <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Job</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Created At</th>
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
            $('#decorationEquipmentPlansTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("rental-event-equipment.decoration-equipment-plans.data") }}',
                    type: 'GET'
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'job_number', name: 'job_number'},
                    {data: 'customer_name', name: 'customer_name'},
                    {data: 'status_badge', name: 'status', orderable: false, searchable: false},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false},
                ],
                pageLength: 25,
                order: [[4, 'desc']],
                language: {
                    processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-info" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                }
            });
        });
    </script>
@endpush

