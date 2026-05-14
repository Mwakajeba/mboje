@extends('layouts.main')

@section('title', 'Debit Note Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Debit Notes', 'url' => route('purchases.debit-notes.index'), 'icon' => 'bx bx-minus-circle'],
            ['label' => $debitNote->debit_note_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">DEBIT NOTE DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Debit Note #{{ $debitNote->debit_note_number }}</h5>
                            <div>
                                @can('edit debit notes')
                                @if($debitNote->canEdit())
                                <a href="{{ route('purchases.debit-notes.edit', $debitNote->encoded_id) }}" class="btn btn-primary btn-sm">
                                    <i class="bx bx-edit me-1"></i> Edit
                                </a>
                                @endif
                                @endcan
                                
                                @can('delete debit notes')
                                @if($debitNote->canDelete())
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteDebitNote('{{ $debitNote->encoded_id }}', '{{ $debitNote->debit_note_number }}')">
                                    <i class="bx bx-trash me-1"></i> Delete
                                </button>
                                @endif
                                @endcan
                                
                                <a href="{{ route('purchases.debit-notes.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Status and Type -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <span class="badge {{ $debitNote->status_badge_class }} ms-2">{{ $debitNote->status_text }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Type:</strong>
                                <span class="badge bg-info ms-2">{{ $debitNote->type_text }}</span>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong>Supplier:</strong>
                                    <p class="mb-0">{{ $debitNote->supplier->name ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <strong>Date:</strong>
                                    <p class="mb-0">{{ $debitNote->debit_note_date ? $debitNote->debit_note_date->format('d/m/Y') : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong>Reference Invoice:</strong>
                                    <p class="mb-0">{{ $debitNote->purchaseInvoice->invoice_number ?? 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <strong>Reason Code:</strong>
                                    <p class="mb-0">{{ $debitNote->reason_code ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <strong>Reason:</strong>
                                <p class="mb-0">{{ $debitNote->reason }}</p>
                            </div>
                        </div>

                        <!-- Items -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6>Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Description</th>
                                                <th>Quantity</th>
                                                <th>Unit Cost</th>
                                                <th>VAT Type</th>
                                                <th>VAT Rate</th>
                                                <th>VAT Amount</th>
                                                <th>Line Total</th>
                                                <th>Return to Stock</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($debitNote->items as $item)
                                            <tr>
                                                <td>{{ $item->item_name }}</td>
                                                <td>{{ $item->description ?? 'N/A' }}</td>
                                                <td>{{ number_format($item->quantity, 2) }}</td>
                                                <td>TZS {{ number_format($item->unit_cost, 2) }}</td>
                                                <td>{{ ucfirst($item->vat_type) }}</td>
                                                <td>{{ number_format($item->vat_rate, 2) }}%</td>
                                                <td>TZS {{ number_format($item->vat_amount, 2) }}</td>
                                                <td>TZS {{ number_format($item->line_total, 2) }}</td>
                                                <td>
                                                    @if($item->return_to_stock)
                                                        <span class="badge bg-success">Yes</span>
                                                    @else
                                                        <span class="badge bg-secondary">No</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="9" class="text-center">No items found</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="row mb-4">
                            <div class="col-md-6 offset-md-6">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Subtotal:</strong></td>
                                            <td class="text-end">TZS {{ number_format($debitNote->subtotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>VAT Amount:</strong></td>
                                            <td class="text-end">TZS {{ number_format($debitNote->vat_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Discount Amount:</strong></td>
                                            <td class="text-end">TZS {{ number_format($debitNote->discount_amount, 2) }}</td>
                                        </tr>
                                        <tr class="table-primary">
                                            <td><strong>Total Amount:</strong></td>
                                            <td class="text-end"><strong>TZS {{ number_format($debitNote->total_amount, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Applied Amount:</strong></td>
                                            <td class="text-end">TZS {{ number_format($debitNote->applied_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Remaining Amount:</strong></td>
                                            <td class="text-end">TZS {{ number_format($debitNote->remaining_amount, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Notes, Terms & Attachment -->
                        @if($debitNote->notes || $debitNote->terms_conditions || $debitNote->attachment)
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="bx bx-note me-2"></i>Notes & Terms
                                    @if($debitNote->attachment)
                                        <a href="{{ asset('storage/' . $debitNote->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary float-end">
                                            <i class="bx bx-paperclip me-1"></i>View Attachment
                                        </a>
                                    @endif
                                </h6>
                            </div>
                            <div class="card-body">
                                @if($debitNote->notes)
                                <div class="mb-3">
                                    <h6>Notes:</h6>
                                    <p class="mb-0">{{ $debitNote->notes }}</p>
                                </div>
                                @endif
                                @if($debitNote->terms_conditions)
                                <div>
                                    <h6>Terms & Conditions:</h6>
                                    <p class="mb-0">{{ $debitNote->terms_conditions }}</p>
                                </div>
                                @endif
                                @if(!$debitNote->notes && !$debitNote->terms_conditions && $debitNote->attachment)
                                <p class="mb-0 text-muted">An attachment has been uploaded for this debit note.</p>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    @can('approve debit notes')
                                    @if($debitNote->canApprove())
                                    <button type="button" class="btn btn-success" onclick="approveDebitNote('{{ $debitNote->encoded_id }}')">
                                        <i class="bx bx-check me-1"></i> Approve
                                    </button>
                                    @endif
                                    @endcan
                                    
                                    @can('apply debit notes')
                                    @if($debitNote->canApply())
                                    <button type="button" class="btn btn-warning" onclick="applyDebitNote('{{ $debitNote->encoded_id }}')">
                                        <i class="bx bx-credit-card me-1"></i> Apply
                                    </button>
                                    @endif
                                    @endcan
                                    
                                    @can('cancel debit notes')
                                    @if($debitNote->canCancel())
                                    <button type="button" class="btn btn-secondary" onclick="cancelDebitNote('{{ $debitNote->encoded_id }}')">
                                        <i class="bx bx-x me-1"></i> Cancel
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </div>
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
// Approve Debit Note
function approveDebitNote(id) {
    Swal.fire({
        title: 'Approve Debit Note?',
        text: "Are you sure you want to approve this debit note?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, approve it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/purchases/debit-notes/${id}/approve`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while approving the debit note.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', errorMessage, 'error');
                }
            });
        }
    });
}

// Apply Debit Note
function applyDebitNote(id) {
    const remaining = {{ (float) $debitNote->remaining_amount }};
    const referenceInvoiceId = {{ $debitNote->purchase_invoice_id ? (int) $debitNote->purchase_invoice_id : 'null' }};

    if (!remaining || remaining <= 0) {
        Swal.fire('Info', 'Nothing to apply. Remaining amount is zero.', 'info');
        return;
    }

    // Simple apply: apply full remaining to reference invoice if available, else prompt error
    if (!referenceInvoiceId) {
        Swal.fire('Action needed', 'No reference invoice to apply against. Please add an application from the edit flow.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Apply Debit Note?',
        text: `Apply TZS ${remaining.toFixed(2)} to the reference invoice?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f0ad4e',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, apply',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: `/purchases/debit-notes/${id}/apply`,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                application_type: 'invoice',
                purchase_invoice_id: referenceInvoiceId,
                amount_applied: remaining,
                application_date: new Date().toISOString().slice(0,10),
                description: 'Applied from Debit Note '
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Applied!',
                        text: response.message || 'Debit note applied successfully.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => { location.reload(); });
                } else {
                    Swal.fire('Error', response.message || 'Failed to apply debit note', 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while applying the debit note.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire('Error', errorMessage, 'error');
            }
        });
    });
}

// Cancel Debit Note
function cancelDebitNote(id) {
    Swal.fire({
        title: 'Cancel Debit Note?',
        text: "Are you sure you want to cancel this debit note?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/purchases/debit-notes/${id}/cancel`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while cancelling the debit note.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', errorMessage, 'error');
                }
            });
        }
    });
}

// Delete Debit Note
function deleteDebitNote(id, name) {
    Swal.fire({
        title: 'Delete Debit Note?',
        text: `Are you sure you want to permanently delete debit note "${name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete permanently!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        $.ajax({
            url: `/purchases/debit-notes/${id}`,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '{{ route("purchases.debit-notes.index") }}';
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while deleting the debit note.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire('Error!', errorMessage, 'error');
            }
        });
    });
}
</script>
@endpush
