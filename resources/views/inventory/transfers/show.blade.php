@extends('layouts.main')

@section('title', 'Transfer Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Transfers', 'url' => route('inventory.transfers.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Transfer Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">Transfer Details</h6>
                <p class="mb-0 text-muted">View transfer information and details</p>
            </div>
            <div>
                @can('edit inventory adjustments')
                    @if($transfer->movement_type !== 'transfer_in')
                    <a href="{{ route('inventory.transfers.edit', $transfer->hash_id) }}" class="btn btn-warning me-2">
                        <i class="bx bx-edit me-1"></i>Edit Transfer
                    </a>
                    @endif
                @endcan
                
                @can('delete inventory adjustments')
                    @if($transfer->movement_type !== 'transfer_in')
                    <button type="button" class="btn btn-danger me-2 delete-transfer" 
                            data-url="{{ route('inventory.transfers.destroy', $transfer->hash_id) }}" 
                            data-reference="{{ $transfer->reference ?? 'N/A' }}">
                        <i class="bx bx-trash me-1"></i>Delete Transfer
                    </button>
                    @endif
                @endcan
                
                <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Transfers
                </a>
            </div>
        </div>

        @if($transfer->movement_type === 'transfer_in')
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bx bx-info-circle me-2"></i>
            <strong>Transfer In Movement:</strong> This is a "Transfer In" movement which cannot be edited or deleted. Only "Transfer Out" movements can be modified.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <!-- Transfer Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-transfer me-2"></i>Transfer Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Reference</label>
                                    <p class="mb-0">{{ $transfer->reference ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Transfer Date</label>
                                    <p class="mb-0">{{ $transfer->movement_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Movement Type</label>
                                    <p class="mb-0">
                                        @if($transfer->movement_type === 'transfer_in')
                                            <span class="badge bg-success">Transfer In</span>
                                        @else
                                            <span class="badge bg-info">Transfer Out</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Created By</label>
                                    <p class="mb-0">{{ $transfer->user->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        @if($transfer->notes)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes</label>
                            <p class="mb-0">{{ $transfer->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Item Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-package me-2"></i>Item Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Item Name</label>
                                    <p class="mb-0">{{ $transfer->item->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Item Code</label>
                                    <p class="mb-0">{{ $transfer->item->code }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Category</label>
                                    <p class="mb-0">{{ $transfer->item->category->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Unit of Measure</label>
                                    <p class="mb-0">{{ $transfer->item->unit_of_measure }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Transfer Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-detail me-2"></i>Transfer Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <p class="mb-0 fs-5">{{ number_format($transfer->quantity, 2) }} {{ $transfer->item->unit_of_measure }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Unit Cost</label>
                            <p class="mb-0 fs-5">{{ number_format($transfer->unit_cost, 2) }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Total Cost</label>
                            <p class="mb-0 fs-5 fw-bold text-primary">{{ number_format($transfer->total_cost, 2) }}</p>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Balance Before</label>
                            <p class="mb-0">{{ number_format($transfer->balance_before, 2) }} {{ $transfer->item->unit_of_measure }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Balance After</label>
                            <p class="mb-0 fw-bold">{{ number_format($transfer->balance_after, 2) }} {{ $transfer->item->unit_of_measure }}</p>
                        </div>
                    </div>
                </div>

                <!-- Branch Information -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-building me-2"></i>Branch Information
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($sourceBranch)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Source Branch</label>
                            <p class="mb-0">
                                <i class="bx bx-building me-1"></i>{{ $sourceBranch->name }}
                                @if($sourceLocation)
                                    <br><small class="text-muted">
                                        <i class="bx bx-map me-1"></i>{{ $sourceLocation->name }}
                                    </small>
                                @endif
                            </p>
                        </div>
                        @endif
                        
                        @if($destinationBranch)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Destination Branch</label>
                            <p class="mb-0">
                                <i class="bx bx-building me-1"></i>{{ $destinationBranch->name }}
                                @if($destinationLocation)
                                    <br><small class="text-muted">
                                        <i class="bx bx-map me-1"></i>{{ $destinationLocation->name }}
                                    </small>
                                @endif
                            </p>
                        </div>
                        @endif
                        
                        @if(!$sourceBranch && !$destinationBranch)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Branch</label>
                            <p class="mb-0">
                                <i class="bx bx-building me-1"></i>{{ $transfer->item->location && $transfer->item->location->branch ? $transfer->item->location->branch->name : 'N/A' }}
                                @if($transfer->item->location)
                                    <br><small class="text-muted">
                                        <i class="bx bx-map me-1"></i>{{ $transfer->item->location->name }}
                                    </small>
                                @endif
                            </p>
                        </div>
                        @endif
                        
                        @if($transfer->movement_type === 'transfer_out')
                            <div class="alert alert-info alert-sm">
                                <i class="bx bx-info-circle me-1"></i>
                                <small>This is a transfer out movement from the source location.</small>
                            </div>
                        @else
                            <div class="alert alert-success alert-sm">
                                <i class="bx bx-check-circle me-1"></i>
                                <small>This is a transfer in movement to the destination location.</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle delete button click with SweetAlert
    $('.delete-transfer').click(function() {
        const deleteUrl = $(this).data('url');
        const reference = $(this).data('reference');
        
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete transfer "${reference}". This action cannot be undone and will reverse all stock changes.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the transfer.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send AJAX request
                $.ajax({
                    url: deleteUrl,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Redirect to transfers index
                                window.location.href = "{{ route('inventory.transfers.index') }}";
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to delete transfer. Please try again.',
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

@endsection
