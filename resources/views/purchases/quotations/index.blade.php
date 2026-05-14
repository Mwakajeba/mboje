@extends('layouts.main')

@section('title', 'Purchase Quotations')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .bg-gradient-secondary {
        background: linear-gradient(45deg, #6c757d, #5c636a) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .table-light {
        background-color: #f8f9fa;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Purchase Quotations', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-file me-2"></i>Purchase Quotations</h5>
                                <p class="mb-0 text-muted">Manage and track all purchase quotations</p>
                            </div>
                            <div>
                                @can('create purchase quotations')
                                <a href="{{ route('purchases.quotations.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Quotation
                                </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats (will be powered by aggregated data later if needed) -->
        <!-- For now, kept simple or can be enhanced with a separate AJAX endpoint -->

        <!-- Quotations Table -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="quotations-table">
                                <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Supplier</th>
                                    <th>Type</th>
                                    <th>Start Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Total Amount</th>
                                    <th>Created By</th>
                                    <th class="text-center">Actions</th>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this purchase quotation? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">Delete</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const table = $('#quotations-table').DataTable({
        processing: true,
        serverSide: false,
        ajax: '{{ route('purchases.quotations.data') }}',
        order: [[0, 'desc']],
        pageLength: 25,
        columns: [
            { data: 'reference', name: 'reference',
              render: function (data, type, row) {
                  return '<strong>' + data + '</strong>';
              }
            },
            { data: 'supplier', name: 'supplier' },
            { data: 'type', name: 'type',
              render: function (data) {
                  if (data === 'rfq') {
                      return '<span class="badge bg-warning text-dark">RFQ</span>';
                  }
                  return '<span class="badge bg-info">Quotation</span>';
              }
            },
            { data: 'start_date', name: 'start_date' },
            { data: 'due_date', name: 'due_date' },
            { data: 'status', name: 'status',
              render: function (data) {
                  const map = {
                      draft: 'bg-secondary',
                      sent: 'bg-info',
                      approved: 'bg-success',
                      rejected: 'bg-danger',
                      expired: 'bg-warning',
                  };
                  const cls = map[data] || 'bg-secondary';
                  const label = data ? data.charAt(0).toUpperCase() + data.slice(1) : '';
                  return '<span class="badge ' + cls + '">' + label + '</span>';
              }
            },
            { data: 'total_amount', name: 'total_amount',
              className: 'text-end',
              render: function (data, type, row) {
                  if (!data) {
                      return '<span class="text-muted">N/A</span>';
                  }
                  return 'TZS ' + data;
              }
            },
            { data: 'created_by', name: 'created_by' },
            { data: null, orderable: false, searchable: false, className: 'text-center',
              render: function (data, type, row) {
                  let actions = '<div class="btn-group" role="group">';
                  @can('view purchase quotations')
                  actions += `
                    <a href="${row.show_url}" class="btn btn-sm btn-outline-primary" title="View">
                        <i class="bx bx-show"></i>
                    </a>
                  `;
                  @endcan
                  // Email and edit/delete will be wired using delegated handlers
                  actions += `
                    <button type="button"
                            class="btn btn-sm btn-outline-success send-email-quotation"
                            data-id="${row.id}"
                            data-supplier-email=""
                            data-quotation-ref="${row.reference}"
                            title="Send Email">
                        <i class="bx bx-envelope"></i>
                    </button>
                  `;
                  if (row.status === 'draft') {
                      @can('edit purchase quotations')
                      actions += `
                        <a href="${row.edit_url}" class="btn btn-sm btn-outline-warning" title="Edit">
                            <i class="bx bx-edit"></i>
                        </a>
                      `;
                      @endcan
                  }
                  @can('delete purchase quotations')
                  actions += `
                    <button type="button"
                            class="btn btn-sm btn-outline-danger delete-quotation"
                            data-id="${row.hash_id}"
                            title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>
                  `;
                  @endcan
                  actions += '</div>';
                  return actions;
              }
            }
        ],
        language: {
            search: "Search quotations:",
            lengthMenu: "Show _MENU_ quotations per page",
            info: "Showing _START_ to _END_ of _TOTAL_ quotations",
            infoEmpty: "Showing 0 to 0 of 0 quotations",
            infoFiltered: "(filtered from _MAX_ total quotations)"
        }
    });

    // Delete quotation (delegated)
    let quotationToDelete = null;

    $(document).on('click', '.delete-quotation', function() {
        quotationToDelete = $(this).data('id');
        $('#deleteModal').modal('show');
    });

    // Send email quotation (delegated)
    $(document).on('click', '.send-email-quotation', function() {
        const quotationId = $(this).data('id');
        const quotationRef = $(this).data('quotation-ref');
        const supplierEmail = $(this).data('supplier-email') || '';

        Swal.fire({
            title: 'Send Quotation Email',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label for="email_subject" class="form-label">Subject</label>
                        <input type="text" id="email_subject" class="form-control" value="Purchase Quotation #${quotationRef} from {{ config('app.name') }}" placeholder="Email subject">
                    </div>
                    <div class="mb-3">
                        <label for="email_message" class="form-label">Message</label>
                        <textarea id="email_message" class="form-control" rows="4" placeholder="Email message">Please find attached purchase quotation #${quotationRef} for your review and pricing.</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="email_address" class="form-label">Email Address</label>
                        <input type="email" id="email_address" class="form-control" value="${supplierEmail}" placeholder="Email address">
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
                    url: `/purchases/quotations/${quotationId}/send-email`,
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
                            );
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
    });

    $('#confirm-delete').click(function() {
        if (quotationToDelete) {
            $.ajax({
                url: `/purchases/quotations/${quotationToDelete}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the quotation.');
                }
            });
        }
        $('#deleteModal').modal('hide');
    });
});
</script>
@endpush 