@extends('layouts.main')

@section('title', 'Purchase Requisition Details')

@section('content')
@php
    $currencyCode = $requisition->currency ?: 'TZS';
    $currencyCode = strtoupper(trim($currencyCode));
    
    // Status badge colors
    $statusBadges = [
        'draft' => 'bg-secondary',
        'submitted' => 'bg-info',
        'pending_approval' => 'bg-warning',
        'in_review' => 'bg-primary',
        'approved' => 'bg-success',
        'po_created' => 'bg-success',
        'cancelled' => 'bg-dark',
        'rejected' => 'bg-danger',
    ];
    $statusBadge = $statusBadges[$requisition->status] ?? 'bg-secondary';
@endphp
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Requisitions', 'url' => route('purchases.requisitions.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Requisition Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        {{-- Header Card with Status and Actions --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="mb-1">
                                    <i class="bx bx-file-blank text-primary me-2"></i>
                                    {{ $requisition->pr_no }}
                                </h4>
                                <div class="d-flex align-items-center gap-3 mt-2">
                                    <span class="badge {{ $statusBadge }} fs-6 px-3 py-2">
                                        {{ ucfirst(str_replace('_', ' ', $requisition->status)) }}
                                    </span>
                                    @if($requisition->budget)
                                        <span class="badge bg-light text-dark">
                                            <i class="bx bx-wallet me-1"></i>{{ $requisition->budget->name ?? 'Budget ' . $requisition->budget->year }}
                                        </span>
                                    @endif
                                    @if($requisition->purchaseOrder)
                                        <a href="{{ route('purchases.orders.show', $requisition->purchaseOrder->encoded_id) }}" class="badge bg-info text-white text-decoration-none">
                                            <i class="bx bx-shopping-bag me-1"></i>PO: {{ $requisition->purchaseOrder->order_number ?? $requisition->purchaseOrder->po_no ?? 'N/A' }}
                                        </a>
                                    @endif
                                </div>
                                <p class="text-muted mb-0 mt-2">
                                    <i class="bx bx-calendar me-1"></i>
                                    Created: {{ $requisition->created_at->format('M d, Y \a\t h:i A') }}
                                    @if($requisition->required_date)
                                        &middot; Required: {{ $requisition->required_date->format('M d, Y') }}
                                    @endif
                                </p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('purchases.requisitions.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Back
                                </a>

                                @if(in_array($requisition->status, ['draft', 'rejected']))
                                    <form action="{{ route('purchases.requisitions.submit', $requisition->hash_id) }}" method="POST" class="d-inline pr-submit-form">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            <i class="bx bx-send me-1"></i>Submit for Approval
                                        </button>
                                    </form>
                                @endif

                                @if($canApprove ?? false)
                                    <button type="button"
                                            class="btn btn-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#prApproveModal">
                                        <i class="bx bx-check me-1"></i>Approve
                                    </button>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#prRejectModal">
                                        <i class="bx bx-x me-1"></i>Reject
                                    </button>
                                @endif

                                @if($requisition->status === 'approved')
                                    <form action="{{ route('purchases.requisitions.choose-supplier-create-po', $requisition->hash_id) }}" method="POST" class="d-inline pr-create-po-form">
                                        @csrf
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="min-width: 260px;">
                                                <select name="supplier_id" class="form-select select2-single" required>
                                                    <option value="">Select Supplier</option>
                                                    @foreach(\App\Models\Supplier::where('company_id', auth()->user()->company_id)->orderBy('name')->get() as $supplier)
                                                        <option value="{{ $supplier->id }}" @selected($requisition->preferred_supplier_id == $supplier->id)>
                                                            {{ $supplier->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary d-flex align-items-center">
                                                <i class="bx bx-shopping-bag me-1"></i>
                                                <span>Create PO</span>
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="row">
            {{-- Left Column: Main Information --}}
            <div class="col-lg-8">
                {{-- Requisition Lines --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bx bx-list-ul me-2"></i>Requisition Items
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="35%">Item Description</th>
                                        <th width="15%">GL Account</th>
                                        <th width="10%" class="text-center">Qty</th>
                                        <th width="15%" class="text-end">Unit Price</th>
                                        <th width="15%" class="text-end">Line Total</th>
                                        <th width="5%">Tax</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requisition->lines as $index => $line)
                                        @php
                                            $type = $line->item_type;
                                            $badge = '';
                                            if ($type === 'asset') {
                                                $badge = '<span class="badge bg-info">Asset</span>';
                                            } elseif ($type === 'intangible') {
                                                $badge = '<span class="badge bg-warning text-dark">Intangible</span>';
                                            } else {
                                                $badge = '<span class="badge bg-success">Inventory</span>';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    {!! $badge !!}
                                                    <div class="ms-2">
                                                        <strong>{{ $line->description }}</strong>
                                                        @if($line->inventoryItem)
                                                            <br><small class="text-muted">Code: {{ $line->inventoryItem->code }}</small>
                                                        @elseif($line->asset)
                                                            <br><small class="text-muted">Asset: {{ $line->asset->code }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($line->glAccount)
                                                    <small>
                                                        <strong>[{{ $line->glAccount->account_code }}]</strong><br>
                                                        {{ Str::limit($line->glAccount->account_name, 30) }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold">{{ number_format($line->quantity, 2) }}</span>
                                                @if($line->uom)
                                                    <br><small class="text-muted">{{ $line->uom }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <strong>{{ $currencyCode }} {{ number_format($line->unit_price_estimate, 2) }}</strong>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-primary">{{ $currencyCode }} {{ number_format($line->line_total_estimate, 2) }}</strong>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $taxGroup = null;
                                                    try {
                                                        $taxGroup = $line->taxGroup;
                                                    } catch (\Exception $e) {
                                                        // TaxGroup model doesn't exist
                                                    }
                                                @endphp
                                                @if($taxGroup && method_exists($taxGroup, 'name'))
                                                    <small class="badge bg-light text-dark">
                                                        {{ $taxGroup->name }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="bx bx-inbox fs-1 d-block mb-2"></i>
                                                No items added to this requisition.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">Total Amount:</td>
                                        <td class="text-end">
                                            <h5 class="mb-0 text-primary">
                                                {{ $currencyCode }} {{ number_format($requisition->total_amount, 2) }}
                                            </h5>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Supplier Selection & Quotations --}}
                @if(($quotations ?? collect())->isNotEmpty() || $requisition->status === 'approved')
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bx bx-group me-2"></i>Supplier Quotations
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            @if(($quotations ?? collect())->isNotEmpty())
                                <span class="badge bg-primary">
                                    {{ $quotations->count() }} Quotation{{ $quotations->count() > 1 ? 's' : '' }}
                                </span>
                            @endif
                            @can('create purchase quotations')
                                <a href="{{ route('purchases.quotations.create', ['requisition' => $requisition->hash_id]) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-plus me-1"></i>Create RFQ
                                </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        @if(($quotations ?? collect())->isEmpty())
                            <div class="alert alert-info mb-0">
                                <i class="bx bx-info-circle me-2"></i>
                                No quotations linked yet. Create purchase quotations to compare supplier prices.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Supplier</th>
                                            <th>Reference</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-end">Quoted Total</th>
                                            <th class="text-center">Date</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($quotations as $quote)
                                            <tr @class(['table-success' => $requisition->preferred_supplier_id === $quote->supplier_id])>
                                                <td>
                                                    <strong>{{ optional($quote->supplier)->name ?? 'N/A' }}</strong>
                                                    @if(optional($quote->supplier)->phone || optional($quote->supplier)->email)
                                                        <br><small class="text-muted">
                                                            @if(optional($quote->supplier)->phone){{ $quote->supplier->phone }}@endif
                                                            @if(optional($quote->supplier)->phone && optional($quote->supplier)->email) &middot; @endif
                                                            @if(optional($quote->supplier)->email){{ $quote->supplier->email }}@endif
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>{{ $quote->reference ?? ('QT-' . str_pad($quote->id, 5, '0', STR_PAD_LEFT)) }}</td>
                                                <td class="text-center">{!! $quote->status_badge ?? '<span class="badge bg-secondary">N/A</span>' !!}</td>
                                                <td class="text-end">
                                                    <strong>{{ $currencyCode }} {{ number_format($quote->total_amount ?? 0, 2) }}</strong>
                                                </td>
                                                <td class="text-center">
                                                    <small>{{ optional($quote->created_at)->format('M d, Y') }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ route('purchases.quotations.show', $quote->hash_id) }}" class="btn btn-outline-primary" title="View">
                                                            <i class="bx bx-show"></i>
                                                        </a>
                                                        @if(optional($quote->supplier)->email)
                                                            <button type="button"
                                                                    class="btn btn-outline-success send-email-quotation-pr"
                                                                    data-id="{{ $quote->id }}"
                                                                    data-supplier-email="{{ $quote->supplier->email }}"
                                                                    data-quotation-ref="{{ $quote->reference }}"
                                                                    title="Send Email">
                                                                <i class="bx bx-envelope"></i>
                                                            </button>
                                                        @endif
                                                        @if($requisition->status === 'approved')
                                                            <form action="{{ route('purchases.requisitions.set-preferred-supplier', $requisition->hash_id) }}"
                                                                  method="POST"
                                                                  class="d-inline-block ms-1">
                                                                @csrf
                                                                <input type="hidden" name="quotation_id" value="{{ $quote->id }}">
                                                                <button type="submit"
                                                                        class="btn btn-outline-dark"
                                                                        title="Set as Preferred & Use for PO">
                                                                    <i class="bx bx-target-lock"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Right Column: Details & Approval --}}
            <div class="col-lg-4">
                {{-- Requisition Information --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Requisition Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small mb-1 d-block">Department (Cost Center)</label>
                            <div class="fw-medium">
                                {{ $requisition->department->name ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1 d-block">Requestor</label>
                            <div class="fw-medium">
                                {{ $requisition->requestor->name ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1 d-block">Preferred Supplier</label>
                            <div class="fw-medium">
                                {{ $requisition->supplier->name ?? 'Not selected' }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1 d-block">Currency</label>
                            <div>
                                <span class="badge bg-info">{{ $currencyCode }}</span>
                                @if($requisition->exchange_rate && $requisition->exchange_rate != 1)
                                    <small class="text-muted ms-2">Rate: {{ number_format($requisition->exchange_rate, 6) }}</small>
                                @endif
                            </div>
                        </div>
                        @if($requisition->justification)
                        <div class="mb-3">
                            <label class="text-muted small mb-1 d-block">Justification</label>
                            <div class="text-break">
                                {{ $requisition->justification }}
                            </div>
                        </div>
                        @endif
                        @if($requisition->rejection_reason)
                        <div class="mb-3">
                            <label class="text-muted small mb-1 d-block">Rejection Reason</label>
                            <div class="alert alert-danger mb-0">
                                <i class="bx bx-error-circle me-1"></i>
                                {{ $requisition->rejection_reason }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Approval Status & History --}}
                @if($requisition->status !== 'draft')
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bx bx-check-shield me-2"></i>Approval Status
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(isset($approvalSummary))
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Progress</span>
                                    <span class="fw-bold">
                                        {{ $approvalSummary['completed_levels'] }} / {{ $approvalSummary['total_levels'] }} Levels
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    @php
                                        $progress = $approvalSummary['total_levels'] > 0 
                                            ? ($approvalSummary['completed_levels'] / $approvalSummary['total_levels']) * 100 
                                            : 0;
                                    @endphp
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>
                            
                            @if($approvalSummary['current_level'])
                            <div class="mb-3">
                                <label class="text-muted small mb-1 d-block">Current Level</label>
                                <div class="fw-medium">
                                    {{ $approvalSummary['current_level']['name'] }}
                                </div>
                            </div>
                            @endif

                            @if($approvalSummary['approvers']->isNotEmpty())
                            <div class="mb-3">
                                <label class="text-muted small mb-1 d-block">Pending Approvers</label>
                                <div>
                                    @foreach($approvalSummary['approvers'] as $approver)
                                        <div class="badge bg-warning text-dark me-1 mb-1">
                                            {{ $approver['name'] }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        @endif

                        @if(($approvalHistory ?? collect())->isNotEmpty())
                            <hr>
                            <label class="text-muted small mb-2 d-block">Approval History</label>
                            <div class="timeline">
                                @foreach($approvalHistory as $history)
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            @if($history->action === 'approved')
                                                <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center">
                                                    <i class="bx bx-check text-white"></i>
                                                </div>
                                            @elseif($history->action === 'rejected')
                                                <div class="avatar-sm bg-danger rounded-circle d-flex align-items-center justify-content-center">
                                                    <i class="bx bx-x text-white"></i>
                                                </div>
                                            @else
                                                <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center">
                                                    <i class="bx bx-send text-white"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="fw-medium">
                                                {{ ucfirst($history->action) }}
                                                @if($history->approvalLevel)
                                                    - {{ $history->approvalLevel->level_name }}
                                                @endif
                                            </div>
                                            <div class="text-muted small">
                                                by {{ $history->approver->name ?? 'System' }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ $history->created_at->format('M d, Y h:i A') }}
                                            </div>
                                            @if($history->comments)
                                                <div class="mt-1 small text-break">
                                                    <em>"{{ $history->comments }}"</em>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Related Documents --}}
                @if($requisition->purchaseOrder)
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bx bx-link me-2"></i>Related Documents
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between p-2 border rounded">
                            <div>
                                <div class="fw-medium">Purchase Order</div>
                                <small class="text-muted">{{ $requisition->purchaseOrder->order_number ?? $requisition->purchaseOrder->po_no ?? 'N/A' }}</small>
                            </div>
                            <a href="{{ route('purchases.orders.show', $requisition->purchaseOrder->encoded_id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-show"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($canApprove ?? false)
    <!-- Approve Modal -->
    <div class="modal fade" id="prApproveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('purchases.requisitions.approve', $requisition->hash_id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="approval_level_id" value="{{ optional($currentLevel)->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Approval Comment</label>
                            <textarea name="comments" class="form-control" rows="3" placeholder="Optional comment for this approval..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="prRejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('purchases.requisitions.reject', $requisition->hash_id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="approval_level_id" value="{{ optional($currentLevel)->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@push('styles')
<style>
    .avatar-sm {
        width: 32px;
        height: 32px;
        font-size: 16px;
    }
    .timeline .avatar-sm {
        margin-top: 2px;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(function () {
        // Initialize Select2 for supplier dropdown
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // SweetAlert confirmation before submitting PR for approval
        $(document).on('submit', '.pr-submit-form', function (e) {
            e.preventDefault();
            const form = this;

            Swal.fire({
                title: 'Submit for approval?',
                text: 'Once submitted, this requisition will enter the approval workflow.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, submit',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Send quotation email directly from PR details (per supplier)
        $('.send-email-quotation-pr').on('click', function () {
            const quotationId = $(this).data('id');
            const supplierEmail = $(this).data('supplier-email');
            const quotationRef = $(this).data('quotation-ref');

            Swal.fire({
                title: 'Send Quotation Email',
                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <label for="email_subject" class="form-label">Subject</label>
                            <input type="text" id="email_subject" class="form-control"
                                   value="Purchase Quotation #${quotationRef} from {{ config('app.name') }}"
                                   placeholder="Email subject">
                        </div>
                        <div class="mb-3">
                            <label for="email_message" class="form-label">Message</label>
                            <textarea id="email_message" class="form-control" rows="4"
                                      placeholder="Email message">Please find attached purchase quotation #${quotationRef} for your review and pricing.</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="email_address" class="form-label">Email Address</label>
                            <input type="email" id="email_address" class="form-control"
                                   value="${supplierEmail}" placeholder="Email address">
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
                    success: function (response) {
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
                    error: function (xhr) {
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

        // SweetAlert confirmation before creating PO from PR
        $(document).on('submit', '.pr-create-po-form', function (e) {
            e.preventDefault();
            const form = this;

            Swal.fire({
                title: 'Create Purchase Order?',
                text: 'This will create a purchase order from this approved requisition for the selected supplier.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, create PO',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
@endsection
