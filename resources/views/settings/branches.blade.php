@extends('layouts.main')

@section('title', 'Branch Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Branch Settings', 'url' => '#', 'icon' => 'bx bx-building']
        ]" />
        <h6 class="mb-0 text-uppercase">BRANCH SETTINGS</h6>
        <hr/>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">Branch Management</h4>
                            <a href="{{ route('settings.branches.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i> Add New Branch
                            </a>
                        </div>
                        
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-2"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(isset($errors) && $errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                Please fix the following errors:
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table id="branches-table" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Branch Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Manager</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- DataTables will populate this -->
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end page wrapper -->
<!--start overlay-->
<div class="overlay toggle-icon"></div>
<!--end overlay-->
<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
<!--End Back To Top Button-->
<footer class="page-footer">
    <p class="mb-0">Copyright © {{ date('Y') }}. All right reserved. -- By SAFCO FINTECH</p>
</footer>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#branches-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("settings.branches.data") }}',
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.error('DataTable AJAX error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'branch_name', name: 'branch_name' },
            { data: 'phone', name: 'phone' },
            { data: 'email', name: 'email' },
            { data: 'manager_name', name: 'manager_name' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        responsive: true,
        language: {
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
            emptyTable: '<div class="text-center p-4"><i class="bx bx-building font-24 text-muted"></i><p class="text-muted mt-2">No branches found.</p></div>'
        },
        drawCallback: function(settings) {
            // Re-initialize delete form handlers after table redraw
            $('.delete-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const branchName = form.find('button[type="submit"]').data('name') || 'this branch';
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You want to delete ${branchName}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.off('submit').submit();
                    }
                });
            });
        }
    });
});
</script>
@endpush

@endsection 