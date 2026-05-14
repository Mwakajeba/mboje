@extends('layouts.main')

@section('title', 'Shareholders')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Shareholders', 'url' => '#', 'icon' => 'bx bx-user']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">SHAREHOLDERS</h6>
            <a href="{{ route('accounting.share-capital.shareholders.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> New Shareholder
            </a>
        </div>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Shareholders Table -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Shareholders</h6>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="shareholdersTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Related Party</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        var table = $('#shareholdersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('accounting.share-capital.shareholders.index') }}",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'code_link', name: 'code'},
                {data: 'name', name: 'name'},
                {data: 'type_badge', name: 'type'},
                {data: 'related_party_badge', name: 'is_related_party'},
                {data: 'email', name: 'email'},
                {data: 'phone', name: 'phone'},
                {data: 'status_badge', name: 'is_active'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false},
            ],
            order: [[2, 'asc']],
        });

        // Handle delete button click
        $(document).on('click', '.delete-shareholder-btn', function(e) {
            e.preventDefault();
            const encodedId = $(this).data('encoded-id');
            const shareholderName = $(this).data('name');
            const deleteUrl = "{{ route('accounting.share-capital.shareholders.destroy', ':id') }}".replace(':id', encodedId);
            
            Swal.fire({
                title: 'Delete Shareholder?',
                html: `
                    <div class="text-center">
                        <i class="bx bx-trash text-danger" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p class="mb-3">You are about to delete shareholder:</p>
                        <strong class="text-primary">${shareholderName}</strong>
                        <p class="text-muted mt-2">This action cannot be undone!</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="bx bx-trash me-1"></i>Yes, Delete It!',
                cancelButtonText: '<i class="bx bx-x me-1"></i>Cancel',
                buttonsStyling: true,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message || 'Shareholder has been deleted successfully.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                table.ajax.reload(); // Reload DataTable
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Failed to delete shareholder.',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr) {
                            let message = 'Something went wrong!';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try {
                                    const json = JSON.parse(xhr.responseText);
                                    message = json.message || message;
                                } catch (e) {
                                    message = xhr.responseText;
                                }
                            }
                            Swal.fire({
                                title: 'Error!',
                                text: message,
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush

