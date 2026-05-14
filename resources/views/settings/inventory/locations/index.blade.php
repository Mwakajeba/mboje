@extends('layouts.main')

@section('title', 'Inventory Locations')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Locations', 'url' => '#', 'icon' => 'bx bx-map']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Inventory Locations</h4>
                            <a href="{{ route('settings.inventory.locations.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i> Add New Location
                            </a>
                        </div>

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        <div class="table-responsive">
                            <table id="locationsTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Manager</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
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
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Initialize DataTable
        $('#locationsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('settings.inventory.locations.index') }}",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            },
            columns: [
                {data: 'name', name: 'name'},
                {data: 'description', name: 'description', orderable: false},
                {data: 'manager_name', name: 'manager.name'},
                {data: 'status_badge', name: 'is_active', orderable: false, searchable: false},
                {data: 'created_at', name: 'created_at'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false}
            ],
            order: [[0, 'asc']],
            pageLength: 25,
            responsive: true,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                emptyTable: '<div class="text-center p-4"><i class="bx bx-map font-24 text-muted"></i><p class="text-muted mt-2">No locations found.</p></div>'
            }
        });

        // Handle delete button clicks
        $(document).on('click', '.delete-btn', function() {
            const locationId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("settings.inventory.locations.destroy", ":id") }}'.replace(':id', locationId),
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.message, 'success');
                                $('#locationsTable').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Something went wrong!';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch(e) {}
                            }
                            Swal.fire('Error!', msg, 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
