@extends('layouts.main')

@section('title', 'Purchase Order Details - ' . $order->order_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Orders', 'url' => route('purchases.orders.index'), 'icon' => 'bx bx-shopping-cart'],
            ['label' => $order->order_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">PURCHASE ORDER DETAILS</h6>
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

        @if(request('grn'))
            @php
                $grn = \App\Models\Purchase\GoodsReceipt::with(['items.item', 'receivedByUser'])->find(request('grn'));
            @endphp
            @if($grn)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bx bx-package fs-3 me-3"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-2">GRN Created Successfully!</h6>
                        <p class="mb-2">Goods Receipt Note <strong>GRN-{{ str_pad($grn->id, 6, '0', STR_PAD_LEFT) }}</strong> has been created.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <small><strong>Receipt Date:</strong> {{ $grn->receipt_date?->format('M j, Y') }}</small><br>
                                <small><strong>Received By:</strong> {{ $grn->receivedByUser->name ?? 'N/A' }}</small>
                            </div>
                            <div class="col-md-6">
                                <small><strong>Total Items:</strong> {{ $grn->items->count() }}</small><br>
                                <small><strong>Total Amount:</strong> TZS {{ number_format($grn->total_amount, 2) }}</small>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            @endif
        @endif

        <!-- Order Header -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="mb-1">Order #{{ $order->order_number }}</h4>
                                <p class="text-muted mb-0">Created on {{ $order->created_at->format('M d, Y \a\t h:i A') }}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $order->status === 'draft' ? 'secondary' : ($order->status === 'pending_approval' ? 'warning' : ($order->status === 'approved' ? 'success' : ($order->status === 'in_production' ? 'info' : ($order->status === 'ready_for_delivery' ? 'primary' : 'danger')))) }} fs-6">
                                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </div>
                        </div>

                        <!-- Supplier Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Supplier Information</h6>
                                <p><strong>Name:</strong> {{ $order->supplier->name }}</p>
                                <p><strong>Email:</strong> {{ $order->supplier->email ?? 'N/A' }}</p>
                                <p><strong>Phone:</strong> {{ $order->supplier->phone ?? 'N/A' }}</p>
                                <p><strong>Address:</strong> {{ $order->supplier->address ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Order Information</h6>
                                <p><strong>Order Date:</strong> {{ $order->order_date->format('M d, Y') }}</p>
                                <p><strong>Expected Delivery:</strong> {{ $order->expected_delivery_date->format('M d, Y') }}</p>
                                <p><strong>Payment Terms:</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_terms)) }}</p>
                                <p><strong>Created By:</strong> {{ $order->createdBy->name ?? 'N/A' }}</p>
                                <p><strong>Branch:</strong> {{ $order->branch->name ?? 'N/A' }}</p>
                            </div>
                        </div>

                        @if($order->notes || $order->terms_conditions || $order->attachment)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-note me-2"></i>Notes & Terms
                                    @if($order->attachment)
                                        <a href="{{ asset('storage/' . $order->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary float-end">
                                            <i class="bx bx-paperclip me-1"></i>View Attachment
                                        </a>
                                    @endif
                                </h5>
                            </div>
                            <div class="card-body">
                                @if($order->notes)
                                <div class="mb-3">
                                    <h6>Notes:</h6>
                                    <p class="mb-0">{{ $order->notes }}</p>
                                </div>
                                @endif
                                @if($order->terms_conditions)
                                <div>
                                    <h6>Terms & Conditions:</h6>
                                    <p class="mb-0">{{ $order->terms_conditions }}</p>
                                </div>
                                @endif
                                @if(!$order->notes && !$order->terms_conditions && $order->attachment)
                                <p class="mb-0 text-muted">An attachment has been uploaded for this order.</p>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Order Summary and Actions -->
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Order Summary</h6>
                        <div class="border-top pt-3">
                            <div class="row mb-2">
                                <div class="col-6">
                                    <strong>Subtotal:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    {{ $order->formatted_subtotal }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">
                                    <strong>VAT:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    {{ $order->formatted_vat_amount }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">
                                    <strong>Discount:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    {{ $order->formatted_discount_amount }}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Grand Total:</strong>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="fw-bold fs-5">{{ $order->formatted_total_amount }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Actions</h6>
                        <div class="d-grid gap-2">
                            @if($order->status === 'draft')
                                @can('edit purchase orders')
                                <a href="{{ route('purchases.orders.edit', $order->encoded_id) }}" class="btn btn-warning">
                                    <i class="bx bx-edit me-1"></i> Edit Order
                                </a>
                                @endcan
                                <button type="button" class="btn btn-info" onclick="updateStatus('pending_approval')">
                                    <i class="bx bx-send me-1"></i> Submit for Approval
                                </button>
                                @can('delete purchase orders')
                                <button type="button" class="btn btn-danger" onclick="deleteOrder()">
                                    <i class="bx bx-trash me-1"></i> Delete Order
                                </button>
                                @endcan
                            @endif

                            @if($order->status === 'pending_approval')
                                @can('approve purchase orders')
                                <button type="button" class="btn btn-success" onclick="updateStatus('approved')">
                                    <i class="bx bx-check-circle me-1"></i> Approve Order
                                </button>
                                <button type="button" class="btn btn-danger" onclick="updateStatus('rejected')">
                                    <i class="bx bx-x-circle me-1"></i> Reject Order
                                </button>
                                @endcan
                            @endif

                            @if($order->status === 'approved')
                                <a href="{{ route('purchases.orders.grn.create', $order->encoded_id) }}" class="btn btn-info">
                                    <i class="bx bx-receipt me-1"></i> Change to Goods Received Note (GRN)
                                </a>
                                <a href="{{ route('purchases.orders.print', $order->encoded_id) }}" class="btn btn-outline-primary" target="_blank">
                                    <i class="bx bx-printer me-1"></i> Print/Export PDF
                                </a>
                            @endif
                            @if($order->canBeCancelled())
                                <button type="button" class="btn btn-danger" onclick="updateStatus('cancelled')">
                                    <i class="bx bx-x me-1"></i> Cancel Order
                                </button>
                            @endif

                            <a href="{{ route('purchases.orders.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            @if(($order->status ?? null) === 'rejected' || !empty($order->rejected_at))
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="text-danger mb-2"><i class="bx bx-x-circle me-1"></i> Rejection Details</h6>
                    <div class="row g-2 small">
                        <div class="col-md-4">
                            <strong>Rejected On:</strong>
                            <div>{{ optional($order->rejected_at)->format('M d, Y H:i') ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <strong>Rejected By:</strong>
                            <div>{{ $order->rejectedBy->name ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <strong>Reason:</strong>
                            <div style="white-space: pre-wrap;">{{ $order->rejection_reason ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Order Items -->
            <div class="card">
                <div class="card-body">
                    <h6 class="text-primary mb-3">Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                                                                <th>Cost Price</th>
                                    <th>VAT Type</th>
                                    <th>VAT Amount</th>
                                    <th>Subtotal</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->item->name }}</strong>
                                        @if($item->description)
                                            <br><small class="text-muted">{{ $item->description }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->formatted_quantity }}</td>
                                    <td>{{ $item->formatted_cost_price }}</td>
                                    <td>
                                        @php $vt = $item->vat_type; @endphp
                                        <span class="badge bg-{{ $vt === 'no_vat' ? 'secondary' : ($vt === 'inclusive' ? 'info' : 'warning') }}">
                                            {{ $item->vat_type_label }}
                                        </span>
                                    </td>
                                    <td>{{ $item->formatted_vat_amount }}</td>
                                    <td>{{ $item->formatted_subtotal }}</td>
                                    <td><strong>{{ $item->formatted_total_amount }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function deleteOrder() {
    Swal.fire({
        title: 'Delete Order?',
        text: "Are you sure you want to permanently delete this order? This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("purchases.orders.destroy", $order->encoded_id) }}',
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Order has been permanently deleted successfully.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = '{{ route("purchases.orders.index") }}';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#d33'
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while deleting the order.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMessage,
                        confirmButtonColor: '#d33'
                    });
                }
            });
        }
    });
}
function updateStatus(status) {
    const statusLabels = {
        'draft': 'Draft',
        'pending_approval': 'Pending Approval',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'in_production': 'In Production',
        'ready_for_delivery': 'Ready for Delivery',
        'delivered': 'Delivered',
        'cancelled': 'Cancelled',
        'on_hold': 'On Hold'
    };

    const doUpdate = (payload) => {
        $.ajax({
            url: '{{ route("purchases.orders.updateStatus", $order->encoded_id) }}',
            method: 'PUT',
            data: payload,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: `Status has been updated to "${statusLabels[status]}".`,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message,
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating the status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    if (errs.rejection_reason && errs.rejection_reason.length) {
                        errorMessage = errs.rejection_reason[0];
                    } else if (errs.status && errs.status.length) {
                        errorMessage = errs.status[0];
                    }
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    confirmButtonColor: '#d33'
                });
            }
        });
    };

    if (status === 'rejected') {
        Swal.fire({
            title: 'Reject Order',
            input: 'textarea',
            inputLabel: 'Please provide a reason for rejection (min 5 characters)',
            inputPlaceholder: 'Enter rejection reason...',
            inputAttributes: { 'aria-label': 'Rejection reason' },
            showCancelButton: true,
            confirmButtonText: 'Reject',
            confirmButtonColor: '#d33',
            preConfirm: (value) => {
                if (!value || value.trim().length < 5) {
                    Swal.showValidationMessage('Rejection reason must be at least 5 characters.');
                    return false;
                }
                return value.trim();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                doUpdate({
                    _token: '{{ csrf_token() }}',
                    status: status,
                    rejection_reason: result.value
                });
            }
        });
        return;
    }

    Swal.fire({
        title: 'Update Status',
        text: `Are you sure you want to update the status to "${statusLabels[status]}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            doUpdate({ _token: '{{ csrf_token() }}', status });
        }
    });
}
</script>
@endsection 