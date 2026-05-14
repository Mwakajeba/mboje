@extends('layouts.main')

@section('title', 'Credit Note Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Credit Notes', 'url' => route('sales.credit-notes.index'), 'icon' => 'bx bx-undo'],
            ['label' => $creditNote->credit_note_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="mb-1">{{ $creditNote->credit_note_number }}</h4>
                                <p class="text-muted mb-0">Credit Note Details</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="d-flex gap-2 justify-content-md-end">
                                    @if($creditNote->status === 'draft')
                                        @can('edit credit notes')
                                        <a href="{{ route('sales.credit-notes.edit', $creditNote->encoded_id) }}" class="btn btn-warning">
                                            <i class="bx bx-edit"></i> Edit
                                        </a>
                                        @endcan
                                        @can('approve credit notes')
                                        <button type="button" class="btn btn-success" onclick="approveCreditNote('{{ $creditNote->encoded_id }}', '{{ $creditNote->credit_note_number }}')">
                                            <i class="bx bx-check"></i> Approve
                                        </button>
                                        @endcan
                                        @can('delete credit notes')
                                        <button type="button" class="btn btn-danger" onclick="deleteCreditNote('{{ $creditNote->encoded_id }}', '{{ $creditNote->credit_note_number }}')">
                                            <i class="bx bx-trash"></i> Delete
                                        </button>
                                        @endcan
                                    @elseif($creditNote->status === 'issued')
                                        @can('apply credit notes')
                                        <button type="button" class="btn btn-info" onclick="applyCreditNote('{{ $creditNote->encoded_id }}', '{{ $creditNote->credit_note_number }}', '{{ $creditNote->remaining_amount }}')">
                                            <i class="bx bx-transfer"></i> Apply
                                        </button>
                                        @endcan
                                        @can('cancel credit notes')
                                        <button type="button" class="btn btn-secondary" onclick="cancelCreditNote('{{ $creditNote->encoded_id }}', '{{ $creditNote->credit_note_number }}')">
                                            <i class="bx bx-x"></i> Cancel
                                        </button>
                                        @endcan
                                    @endif
                                    <a href="{{ route('sales.credit-notes.pdf', $creditNote->encoded_id) }}" target="_blank" class="btn btn-outline-primary">
                                        <i class="bx bx-printer"></i> Export PDF
                                    </a>
                                    <a href="{{ route('sales.credit-notes.index') }}" class="btn btn-outline-secondary">
                                        <i class="bx bx-arrow-back"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status and Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            <i class="bx bx-undo fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title mb-1">Status</h5>
                        {!! $creditNote->status_badge !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            <i class="bx bx-money fs-1 text-success"></i>
                        </div>
                        <h5 class="card-title mb-1">Total Amount</h5>
                        <h4 class="text-success mb-0">TZS {{ number_format($creditNote->total_amount, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            <i class="bx bx-transfer fs-1 text-info"></i>
                        </div>
                        <h5 class="card-title mb-1">Applied Amount</h5>
                        <h4 class="text-info mb-0">TZS {{ number_format($creditNote->applied_amount, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <div class="mb-2">
                            <i class="bx bx-time fs-1 text-warning"></i>
                        </div>
                        <h5 class="card-title mb-1">Remaining Amount</h5>
                        <h4 class="text-warning mb-0">TZS {{ number_format($creditNote->remaining_amount, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Credit Note Details -->
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Credit Note Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold" style="width: 40%;">Credit Note Number:</td>
                                        <td>{{ $creditNote->credit_note_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Date:</td>
                                        <td>{{ $creditNote->credit_note_date->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Type:</td>
                                        <td>{!! $creditNote->type_badge !!}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">VAT Rate:</td>
                                        <td>{{ $creditNote->vat_rate }}%</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold" style="width: 40%;">Status:</td>
                                        <td>{!! $creditNote->status_badge !!}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Created By:</td>
                                        <td>{{ $creditNote->createdBy->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Created Date:</td>
                                        <td>{{ $creditNote->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    @if($creditNote->approvedBy)
                                    <tr>
                                        <td class="fw-bold">Approved By:</td>
                                        <td>{{ $creditNote->approvedBy->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Approved Date:</td>
                                        <td>{{ $creditNote->approved_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        @if($creditNote->reason)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Reason:</h6>
                                <p class="text-muted">{{ $creditNote->reason }}</p>
                            </div>
                        </div>
                        @endif

                        @if($creditNote->notes)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Notes:</h6>
                                <p class="text-muted">{{ $creditNote->notes }}</p>
                            </div>
                        </div>
                        @endif

                        @if($creditNote->terms_conditions)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Terms &amp; Conditions:</h6>
                                <p class="text-muted">{{ $creditNote->terms_conditions }}</p>
                            </div>
                        </div>
                        @endif

                        @if($creditNote->attachment)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Attachment:</h6>
                                <a href="{{ asset('storage/' . $creditNote->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-paperclip me-1"></i>View Attachment
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-user me-2"></i>Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold" style="width: 40%;">Customer Name:</td>
                                        <td>{{ $creditNote->customer->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Phone:</td>
                                        <td>{{ $creditNote->customer->phone ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Email:</td>
                                        <td>{{ $creditNote->customer->email ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold" style="width: 40%;">Address:</td>
                                        <td>{{ $creditNote->customer->address ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">City:</td>
                                        <td>{{ $creditNote->customer->city ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Country:</td>
                                        <td>{{ $creditNote->customer->country ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Invoice -->
                @if($creditNote->salesInvoice)
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-receipt me-2"></i>Related Invoice</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold" style="width: 40%;">Invoice Number:</td>
                                        <td>
                                            <a href="{{ route('sales.invoices.show', $creditNote->salesInvoice->encoded_id) }}" class="text-primary">
                                                {{ $creditNote->salesInvoice->invoice_number }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Invoice Date:</td>
                                        <td>{{ $creditNote->salesInvoice->invoice_date->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Invoice Amount:</td>
                                        <td>TZS {{ number_format($creditNote->salesInvoice->total_amount, 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold" style="width: 40%;">Invoice Status:</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'draft' => 'secondary',
                                                    'sent' => 'primary',
                                                    'paid' => 'success',
                                                    'overdue' => 'warning',
                                                    'cancelled' => 'danger'
                                                ];
                                                $color = $statusColors[$creditNote->salesInvoice->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ strtoupper($creditNote->salesInvoice->status) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Due Date:</td>
                                        <td>{{ $creditNote->salesInvoice->due_date ? $creditNote->salesInvoice->due_date->format('M d, Y') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Balance:</td>
                                        <td>TZS {{ number_format($creditNote->salesInvoice->balance, 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Exchange Information -->
                @if($creditNote->type === 'exchange')
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-transfer me-2"></i>Exchange Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 border rounded mb-3">
                                    <i class="bx bx-undo fs-2 text-warning me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Returned Items</h6>
                                        <p class="mb-1 text-muted">{{ $creditNote->items->where('is_replacement', false)->count() }} items returned</p>
                                        <small class="text-muted">Items being returned by customer</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-3 border rounded mb-3">
                                    <i class="bx bx-package fs-2 text-success me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Replacement Items</h6>
                                        <p class="mb-1 text-muted">{{ $creditNote->items->where('is_replacement', true)->count() }} items provided</p>
                                        <small class="text-muted">Items given to customer as replacement</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($creditNote->refund_now)
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Refund Processing:</strong> Customer will receive a refund for the returned items.
                            @if($creditNote->bankAccount)
                                <br><small class="text-muted">Refund will be processed from: {{ $creditNote->bankAccount->name }} ({{ $creditNote->bankAccount->account_number }})</small>
                            @endif
                        </div>
                        @endif
                        @if($creditNote->return_to_stock)
                        <div class="alert alert-success">
                            <i class="bx bx-check-circle me-2"></i>
                            <strong>Stock Return:</strong> Returned items will be added back to inventory.
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Credit Note Items -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-package me-2"></i>Credit Note Items</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Item Name</th>
                                        <th>Type</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>VAT</th>
                                        <th>Discount</th>
                                        <th class="text-end">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($creditNote->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $item->item_name }}</strong>
                                                @if($item->item_code)
                                                    <br><small class="text-muted">Code: {{ $item->item_code }}</small>
                                                @endif
                                                @if($item->description)
                                                    <br><small class="text-muted">{{ $item->description }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($creditNote->type === 'exchange')
                                                @if($item->is_replacement)
                                                    <span class="badge bg-success">
                                                        <i class="bx bx-package me-1"></i>Replacement
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="bx bx-undo me-1"></i>Returned
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-primary">
                                                    <i class="bx bx-package me-1"></i>Credit
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($item->quantity, 2) }}</td>
                                        <td>TZS {{ number_format($item->unit_price, 2) }}</td>
                                        <td>
                                            <small>
                                                @if($item->vat_type == 'no_vat')
                                                    No VAT
                                                @elseif($item->vat_type == 'inclusive')
                                                    Inc {{ $item->vat_rate }}%
                                                @else
                                                    Exc {{ $item->vat_rate }}%
                                                @endif
                                                <br>
                                                <span class="text-muted">TZS {{ number_format($item->vat_amount, 2) }}</span>
                                            </small>
                                        </td>
                                        <td>
                                            @if($item->discount_type !== 'none')
                                                <small>
                                                    {{ ucfirst($item->discount_type) }} {{ $item->discount_rate }}%<br>
                                                    <span class="text-muted">TZS {{ number_format($item->discount_amount, 2) }}</span>
                                                </small>
                                            @else
                                                <span class="text-muted">None</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">TZS {{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bx bx-package fs-1"></i>
                                            <p class="mt-2">No items found</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Summary -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span class="fw-bold">TZS {{ number_format($creditNote->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>VAT Amount:</span>
                            <span class="fw-bold">TZS {{ number_format($creditNote->vat_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount:</span>
                            <span class="fw-bold">TZS {{ number_format($creditNote->discount_amount, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total:</span>
                            <span class="text-primary">TZS {{ number_format($creditNote->total_amount, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Applied:</span>
                            <span class="text-success">TZS {{ number_format($creditNote->applied_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Remaining:</span>
                            <span class="text-warning">TZS {{ number_format($creditNote->remaining_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- GL Transactions -->
                @if($creditNote->glTransactions->count() > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-book me-2"></i>General Ledger Entries</h6>
                    </div>
                    <div class="card-body">
                        @foreach($creditNote->glTransactions as $transaction)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">{{ $transaction->chartAccount->account_name ?? 'N/A' }}</span>
                                <span class="{{ $transaction->nature === 'debit' ? 'text-danger' : 'text-success' }}">
                                    {{ strtoupper($transaction->nature) }} TZS {{ number_format($transaction->amount, 2) }}
                                </span>
                            </div>
                            <small class="text-muted">{{ $transaction->description }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Activity Log -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Activity History</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Credit Note Created</h6>
                                    <p class="text-muted mb-1">{{ $creditNote->created_at->format('M d, Y H:i') }}</p>
                                    <small>By {{ $creditNote->createdBy->name ?? 'System' }}</small>
                                </div>
                            </div>
                            
                            @if($creditNote->approved_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Credit Note Approved</h6>
                                    <p class="text-muted mb-1">{{ $creditNote->approved_at->format('M d, Y H:i') }}</p>
                                    <small>By {{ $creditNote->approvedBy->name ?? 'System' }}</small>
                                </div>
                            </div>
                            @endif

                            @if($creditNote->applied_amount > 0)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Credit Note Applied</h6>
                                    <p class="text-muted mb-1">TZS {{ number_format($creditNote->applied_amount, 2) }} applied</p>
                                    <small>Remaining: TZS {{ number_format($creditNote->remaining_amount, 2) }}</small>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Invoice Section -->
        @if($creditNote->salesInvoice || $creditNote->referenceInvoice)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-receipt me-2"></i>Related Invoice
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($creditNote->salesInvoice)
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Primary Invoice</h6>
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-receipt fs-4 text-primary me-3"></i>
                                    <div>
                                        <h5 class="mb-1">
                                            <a href="{{ route('sales.invoices.show', $creditNote->salesInvoice->encoded_id) }}" 
                                               class="text-primary text-decoration-none">
                                                {{ $creditNote->salesInvoice->invoice_number }}
                                            </a>
                                        </h5>
                                        <p class="text-muted mb-1">
                                            {{ $creditNote->salesInvoice->invoice_date->format('M d, Y') }} • 
                                            <span class="badge bg-{{ $creditNote->salesInvoice->status === 'paid' ? 'success' : ($creditNote->salesInvoice->status === 'overdue' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($creditNote->salesInvoice->status) }}
                                            </span>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Amount:</strong> TZS {{ number_format($creditNote->salesInvoice->total_amount, 2) }} • 
                                            <strong>Balance:</strong> TZS {{ number_format($creditNote->salesInvoice->balance_due, 2) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @if($creditNote->referenceInvoice && $creditNote->referenceInvoice->id !== $creditNote->salesInvoice->id)
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Reference Invoice</h6>
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-receipt fs-4 text-info me-3"></i>
                                    <div>
                                        <h5 class="mb-1">
                                            <a href="{{ route('sales.invoices.show', $creditNote->referenceInvoice->encoded_id) }}" 
                                               class="text-info text-decoration-none">
                                                {{ $creditNote->referenceInvoice->invoice_number }}
                                            </a>
                                        </h5>
                                        <p class="text-muted mb-1">
                                            {{ $creditNote->referenceInvoice->invoice_date->format('M d, Y') }} • 
                                            <span class="badge bg-{{ $creditNote->referenceInvoice->status === 'paid' ? 'success' : ($creditNote->referenceInvoice->status === 'overdue' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($creditNote->referenceInvoice->status) }}
                                            </span>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Amount:</strong> TZS {{ number_format($creditNote->referenceInvoice->total_amount, 2) }} • 
                                            <strong>Balance:</strong> TZS {{ number_format($creditNote->referenceInvoice->balance_due, 2) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @elseif($creditNote->referenceInvoice)
                        <div class="d-flex align-items-center">
                            <i class="bx bx-receipt fs-4 text-info me-3"></i>
                            <div>
                                <h5 class="mb-1">
                                    <a href="{{ route('sales.invoices.show', $creditNote->referenceInvoice->encoded_id) }}" 
                                       class="text-info text-decoration-none">
                                        {{ $creditNote->referenceInvoice->invoice_number }}
                                    </a>
                                </h5>
                                <p class="text-muted mb-1">
                                    {{ $creditNote->referenceInvoice->invoice_date->format('M d, Y') }} • 
                                    <span class="badge bg-{{ $creditNote->referenceInvoice->status === 'paid' ? 'success' : ($creditNote->referenceInvoice->status === 'overdue' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($creditNote->referenceInvoice->status) }}
                                    </span>
                                </p>
                                <p class="mb-0">
                                    <strong>Amount:</strong> TZS {{ number_format($creditNote->referenceInvoice->total_amount, 2) }} • 
                                    <strong>Balance:</strong> TZS {{ number_format($creditNote->referenceInvoice->balance_due, 2) }}
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

<!-- Apply Credit Note Modal -->
<div class="modal fade" id="applyCreditNoteModal" tabindex="-1" aria-labelledby="applyCreditNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applyCreditNoteModalLabel">Apply Credit Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="applyCreditNoteForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="apply_amount" class="form-label">Amount to Apply <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="apply_amount" name="amount" step="0.01" min="0.01" required>
                        <div class="form-text">Maximum amount: <span id="max_amount">TZS 0.00</span></div>
                    </div>
                    <div class="mb-3">
                        <label for="apply_description" class="form-label">Description</label>
                        <textarea class="form-control" id="apply_description" name="description" rows="3" placeholder="Enter description for this application..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Credit Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    margin-left: 10px;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function approveCreditNote(encodedId, creditNoteNumber) {
    Swal.fire({
        title: 'Approve Credit Note?',
        text: `Are you sure you want to approve credit note ${creditNoteNumber}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Approve!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/sales/credit-notes/${encodedId}/approve`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response?.message || 'Failed to approve credit note', 'error');
                }
            });
        }
    });
}

function cancelCreditNote(encodedId, creditNoteNumber) {
    Swal.fire({
        title: 'Cancel Credit Note?',
        text: `Are you sure you want to cancel credit note ${creditNoteNumber}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Cancel!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/sales/credit-notes/${encodedId}/cancel`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response?.message || 'Failed to cancel credit note', 'error');
                }
            });
        }
    });
}

function applyCreditNote(encodedId, creditNoteNumber, remainingAmount) {
    $('#max_amount').text('TZS ' + parseFloat(remainingAmount).toFixed(2));
    $('#apply_amount').attr('max', remainingAmount);
    $('#apply_amount').val(remainingAmount);
    $('#applyCreditNoteModal').modal('show');
}

function deleteCreditNote(encodedId, creditNoteNumber) {
    Swal.fire({
        title: 'Delete Credit Note?',
        text: `Are you sure you want to delete credit note ${creditNoteNumber}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/sales/credit-notes/${encodedId}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            window.location.href = '{{ route("sales.credit-notes.index") }}';
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire('Error!', response?.message || 'Failed to delete credit note', 'error');
                }
            });
        }
    });
}

// Apply Credit Note Form
$('#applyCreditNoteForm').submit(function(e) {
    e.preventDefault();
    
    const encodedId = '{{ $creditNote->encoded_id }}';
    const formData = new FormData(this);
    
    // Add CSRF token to FormData
    formData.append('_token', '{{ csrf_token() }}');
    
    $.ajax({
        url: `/sales/credit-notes/${encodedId}/apply`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#applyCreditNoteModal').modal('hide');
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            Swal.fire('Error!', response?.message || 'Failed to apply credit note', 'error');
        }
    });
});
</script>
@endpush
