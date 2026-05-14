@extends('layouts.main')

@section('title', 'Share Classes')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Share Classes', 'url' => '#', 'icon' => 'bx bx-category']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">SHARE CLASSES</h6>
            <a href="{{ route('accounting.share-capital.share-classes.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> New Share Class
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

        <!-- Share Classes Table -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Share Classes</h6>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="shareClassesTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Par Value</th>
                                <th>Authorized Shares</th>
                                <th>Classification</th>
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
        var table = $('#shareClassesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('accounting.share-capital.share-classes.index') }}",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'code_link', name: 'code'},
                {data: 'name', name: 'name'},
                {data: 'share_type', name: 'share_type'},
                {data: 'formatted_par_value', name: 'par_value'},
                {data: 'formatted_authorized_shares', name: 'authorized_shares'},
                {data: 'classification', name: 'classification'},
                {data: 'status_badge', name: 'is_active'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false},
            ],
            order: [[1, 'asc']],
        });

        // Handle delete button click
        $(document).on('click', '.delete-share-class-btn', function(e) {
            e.preventDefault();
            const encodedId = $(this).data('encoded-id');
            const shareClassName = $(this).data('name');
            const deleteUrl = "{{ route('accounting.share-capital.share-classes.destroy', ':id') }}".replace(':id', encodedId);
            
            Swal.fire({
                title: 'Delete Share Class?',
                html: `
                    <div class="text-center">
                        <i class="bx bx-trash text-danger" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p class="mb-3">You are about to delete share class:</p>
                        <strong class="text-primary">${shareClassName}</strong>
                        <p class="text-muted mt-2">This action cannot be undone!</p>
                        <p class="text-warning mt-3"><small><i class="bx bx-info-circle"></i> Note: Share class cannot be deleted if it has active issues, holdings, corporate actions, or dividends.</small></p>
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
                                    text: response.message || 'Share class has been deleted successfully.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                table.ajax.reload(); // Reload DataTable
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Failed to delete share class.',
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

