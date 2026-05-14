@extends('layouts.main')

@section('title', 'View Purchase Quotation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Purchase Quotations', 'url' => route('purchases.quotations.index'), 'icon' => 'bx bx-file'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">PURCHASE QUOTATION DETAILS</h6>
            <div class="btn-group" role="group">
                @if($quotation->status === 'draft')
                    @can('edit purchase quotations')
                    <a href="{{ route('purchases.quotations.edit', $quotation->id) }}" class="btn btn-warning">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    @endcan
                @endif
                <a href="{{ route('purchases.quotations.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Quotation Details -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-file me-2"></i>
                            Purchase Quotation - {{ $quotation->reference ?? 'QTN-' . str_pad($quotation->id, 6, '0', STR_PAD_LEFT) }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Supplier Information</h6>
                                <p><strong>Name:</strong> {{ $quotation->supplier->name }}</p>
                                <p><strong>Email:</strong> {{ $quotation->supplier->email ?? 'N/A' }}</p>
                                <p><strong>Phone:</strong> {{ $quotation->supplier->phone ?? 'N/A' }}</p>
                                <p><strong>Address:</strong> {{ $quotation->supplier->address ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Quotation Details</h6>
                                <p><strong>Type:</strong> 
                                    @if($quotation->is_request_for_quotation)
                                        <span class="badge bg-warning text-dark">Request for Quotation (RFQ)</span>
                                    @else
                                        <span class="badge bg-info">Purchase Quotation</span>
                                    @endif
                                </p>
                                <p><strong>Start Date:</strong> {{ $quotation->start_date->format('M j, Y') }}</p>
                                <p><strong>Due Date:</strong> {{ $quotation->due_date->format('M j, Y') }}</p>
                                <p><strong>Status:</strong> 
                                    @php
                                        $statusClasses = [
                                            'draft' => 'bg-secondary',
                                            'sent' => 'bg-info',
                                            'approved' => 'bg-success',
                                            'rejected' => 'bg-danger',
                                            'expired' => 'bg-warning'
                                        ];
                                        $statusClass = $statusClasses[$quotation->status] ?? 'bg-secondary';
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($quotation->status) }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="mt-4">
                            <h6 class="text-muted mb-3">Items</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            @if(!$quotation->is_request_for_quotation)
                                                <th>Cost Price</th>
                                                <th>VAT</th>
                                                <th>Total</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($quotation->quotationItems as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->item->name }}</strong><br>
                                                <small class="text-muted">{{ $item->item->code }}</small>
                                            </td>
                                            <td>{{ number_format($item->quantity, 2) }} {{ $item->item->unit_of_measure ?? 'units' }}</td>
                                            @if(!$quotation->is_request_for_quotation)
                                                <td>TZS {{ number_format($item->unit_price, 2) }}</td>
                                                <td>TZS {{ number_format($item->tax_amount, 2) }}</td>
                                                <td>TZS {{ number_format($item->total_amount, 2) }}</td>
                                            @endif
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="{{ $quotation->is_request_for_quotation ? 2 : 5 }}" class="text-center text-muted">
                                                No items found
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    @if(!$quotation->is_request_for_quotation)
                                    <tfoot>
                                        @php
                                            $vatSum = $quotation->quotationItems->sum('tax_amount');
                                            $totalSum = $quotation->quotationItems->sum('total_amount');
                                            $netSum = $totalSum - $vatSum;
                                        @endphp
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Subtotal (Excl. VAT):</strong></td>
                                            <td><strong>TZS {{ number_format($netSum, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>VAT Total:</strong></td>
                                            <td><strong>TZS {{ number_format($vatSum, 2) }}</strong></td>
                                        </tr>
                                        @if($quotation->discount_amount > 0)
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Discount:</strong></td>
                                            <td><strong>TZS {{ number_format($quotation->discount_amount, 2) }}</strong></td>
                                        </tr>
                                        @endif
                                        <tr class="table-info">
                                            <td colspan="4" class="text-end"><strong>Total Amount (Incl. VAT):</strong></td>
                                            <td><strong>TZS {{ number_format($totalSum, 2) }}</strong></td>
                                        </tr>
                                    </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>

                        @if($quotation->notes || $quotation->terms_conditions || $quotation->attachment)
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="bx bx-note me-2"></i>Notes & Terms
                                    @if($quotation->attachment)
                                        <a href="{{ asset('storage/' . $quotation->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary float-end">
                                            <i class="bx bx-paperclip me-1"></i>View Attachment
                                        </a>
                                    @endif
                                </h6>
                            </div>
                            <div class="card-body">
                                @if($quotation->notes)
                                <div class="mb-3">
                                    <h6>Notes:</h6>
                                    <p class="mb-0">{{ $quotation->notes }}</p>
                                </div>
                                @endif
                                @if($quotation->terms_conditions)
                                <div>
                                    <h6>Terms & Conditions:</h6>
                                    <p class="mb-0">{{ $quotation->terms_conditions }}</p>
                                </div>
                                @endif
                                @if(!$quotation->notes && !$quotation->terms_conditions && $quotation->attachment)
                                <p class="mb-0 text-muted">An attachment has been uploaded for this quotation.</p>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quotation Info -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Quotation Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Created:</strong> {{ $quotation->created_at->format('M j, Y H:i') }}</p>
                        <p><strong>Created By:</strong> {{ $quotation->user->name }}</p>
                        <p><strong>Branch:</strong> {{ $quotation->branch->name }}</p>
                        <p><strong>Items Count:</strong> {{ $quotation->quotationItems->count() }}</p>
                        @if(!$quotation->is_request_for_quotation)
                        <p><strong>Total Value:</strong> TZS {{ number_format($quotation->total_amount, 2) }}</p>
                        @endif
                    </div>
                </div>

                <!-- Status Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Status Actions</h6>
                    </div>
                    <div class="card-body">
                        @if($quotation->status === 'draft')
                            @can('manage purchase quotations')
                            <button type="button" class="btn btn-info btn-sm w-100 mb-2" onclick="updateStatus('sent')">
                                <i class="bx bx-send me-1"></i>Send Quotation
                            </button>
                            @endcan
                        @endif
                        @if($quotation->status === 'approved')
                            <span class="badge bg-success w-100 mb-2 d-block" style="font-size: 1rem;">
                                <i class="bx bx-check me-1"></i>Approved
                            </span>
                        @endif
                        
                        @if($quotation->status === 'sent')
                            @can('manage purchase quotations')
                            <button type="button" class="btn btn-success btn-sm w-100 mb-2" onclick="updateStatus('approved')">
                                <i class="bx bx-check me-1"></i>Approve
                            </button>
                            <button type="button" class="btn btn-danger btn-sm w-100 mb-2" onclick="updateStatus('rejected')">
                                <i class="bx bx-x me-1"></i>Reject
                            </button>
                            @endcan
                        @endif

                        @if(in_array($quotation->status, ['draft', 'sent']))
                            @can('manage purchase quotations')
                            <button type="button" class="btn btn-warning btn-sm w-100" onclick="updateStatus('expired')">
                                <i class="bx bx-time me-1"></i>Mark as Expired
                            </button>
                            @endcan
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('purchases.quotations.print', $quotation->id) }}" class="btn btn-outline-primary btn-sm w-100 mb-2" target="_blank">
                            <i class="bx bx-printer me-1"></i>Print Quotation
                        </a>
                        @if($quotation->supplier->email)
                        <button type="button" class="btn btn-outline-success btn-sm w-100 mb-2" onclick="sendQuotationEmail()">
                            <i class="bx bx-envelope me-1"></i>Send via Email
                        </button>
                        @else
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-2" disabled title="No email address available for supplier">
                            <i class="bx bx-envelope me-1"></i>Send via Email
                        </button>
                        @endif
                        @if(!$quotation->is_request_for_quotation && $quotation->status === 'approved')
                            @php $effectiveOrdersCount = isset($ordersCount) ? $ordersCount : ($quotation->orders_count ?? 0); @endphp
                            @if($effectiveOrdersCount === 0)
                                <a href="{{ route('purchases.orders.convert-from-quotation', $quotation->hash_id) }}" class="btn btn-outline-success btn-sm w-100">
                                    <i class="bx bx-shopping-cart me-1"></i>Create Purchase Order
                                </a>
                            @else
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                        <i class="bx bx-info-circle me-1"></i>Order already created ({{ $effectiveOrdersCount }})
                                    </button>
                                    <a href="{{ route('purchases.orders.convert-from-quotation', $quotation->hash_id) }}" class="btn btn-success btn-sm">
                                        <i class="bx bx-plus me-1"></i>Create Another Order
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function updateStatus(status) {
    const statusLabels = {
        'draft': 'Draft',
        'sent': 'Sent',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'expired': 'Expired'
    };

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
            $.ajax({
                url: '{{ route("purchases.quotations.updateStatus", $quotation->id) }}',
                method: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status
                },
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

function sendQuotationEmail() {
    Swal.fire({
        title: 'Send Quotation Email',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label for="email_subject" class="form-label">Subject</label>
                    <input type="text" id="email_subject" class="form-control" value="Purchase Quotation #{{ $quotation->reference }} from {{ config('app.name') }}" placeholder="Email subject">
                </div>
                <div class="mb-3">
                    <label for="email_message" class="form-label">Message</label>
                    <textarea id="email_message" class="form-control" rows="4" placeholder="Email message">Please find attached purchase quotation #{{ $quotation->reference }} for your review and pricing.</textarea>
                </div>
                <div class="mb-3">
                    <label for="email_address" class="form-label">Email Address</label>
                    <input type="email" id="email_address" class="form-control" value="{{ $quotation->supplier->email }}" placeholder="Email address">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Send Email',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const subject = document.getElementById('email_subject').value;
            const message = document.getElementById('email_message').value;
            const email = document.getElementById('email_address').value;
            
            if (!email) {
                Swal.showValidationMessage('Email address is required');
                return false;
            }
            
            return { subject, message, email };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("purchases.quotations.send-email", $quotation->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    subject: result.value.subject,
                    message: result.value.message,
                    email: result.value.email
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Sent!',
                            response.message,
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while sending the email.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire(
                        'Error!',
                        errorMessage,
                        'error'
                    );
                }
            });
        }
    });
}
</script>
@endpush 