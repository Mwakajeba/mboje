@extends('layouts.main')

@section('title', 'Cash Purchase Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-cart'],
            ['label' => 'Cash Purchases', 'url' => route('purchases.cash-purchases.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Cash Purchase Details</h4>
                        <p class="text-muted mb-0">Supplier: {{ $purchase->supplier->name ?? 'N/A' }}</p>
                    </div>
                    <div class="page-title-right d-flex gap-2">
                        <a href="{{ route('purchases.cash-purchases.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back
                        </a>
                        @can('edit cash purchases')
                        <a href="{{ route('purchases.cash-purchases.edit', $purchase->encoded_id) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i>Edit
                        </a>
                        @endcan
                        <a href="{{ route('purchases.cash-purchases.export-pdf', $purchase->encoded_id) }}" class="btn btn-secondary">
                            <i class="bx bx-download me-1"></i>Export PDF
                        </a>
                        @can('delete cash purchases')
                        <button type="button" class="btn btn-danger" onclick="deleteCashPurchase('{{ $purchase->encoded_id }}')">
                            <i class="bx bx-trash me-1"></i>Delete
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>Purchase Information</h5>
                        <span class="badge bg-light text-primary fs-6">{{ optional($purchase->purchase_date)->format('Y-m-d') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Supplier</label>
                                    <div class="fw-bold">{{ $purchase->supplier->name ?? 'N/A' }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Purchase Date</label>
                                    <div class="fw-bold">{{ optional($purchase->purchase_date)->format('F d, Y') }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Settlement</label>
                                    <div><span class="badge bg-info fs-6">Supplier advance</span></div>
                                </div>
                                @if($purchase->journal)
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Journal</label>
                                    <div class="fw-bold">#{{ $purchase->journal->id }}</div>
                                    <small class="text-muted">{{ $purchase->journal->description }}</small>
                                </div>
                                @endif
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Advance applied</label>
                                    <div class="fw-bold">{{ number_format((float)($purchase->supplier_advance_applied_amount ?? 0), 2) }} {{ $purchase->currency ?? 'TZS' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-package me-2"></i>Items</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Expiry Date</th>
                                        <th>Batch Number</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end">VAT</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchase->items as $line)
                                    <tr>
                                        <td>
                                            @php
                                                $displayName = $line->inventoryItem->name ?? $line->description ?? 'Item';
                                                $displayCode = $line->inventoryItem->code ?? '';
                                            @endphp
                                            <div class="fw-bold">
                                                <span class="badge bg-success me-1">Inventory</span>
                                                {{ $displayName }}
                                            </div>
                                            @if($displayCode)
                                                <small class="text-muted">{{ $displayCode }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($line->expiry_date)
                                                <span class="badge bg-info">{{ $line->expiry_date->format('d M Y') }}</span>
                                                @if($line->expiry_date < now())
                                                    <span class="badge bg-danger ms-1">Expired</span>
                                                @elseif($line->expiry_date < now()->addDays(30))
                                                    <span class="badge bg-warning ms-1">Expiring Soon</span>
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($line->batch_number)
                                                <span class="badge bg-secondary">{{ $line->batch_number }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($line->quantity, 2) }}</td>
                                        <td class="text-end">TZS {{ number_format($line->unit_cost, 2) }}</td>
                                        <td class="text-end">
                                            @if($line->vat_type === 'no_vat')
                                                <span class="badge bg-secondary">No VAT</span>
                                            @else
                                                TZS {{ number_format($line->vat_amount, 2) }}
                                                <br><small class="text-muted">{{ number_format($line->vat_rate,2) }}%</small>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">TZS {{ number_format($line->line_total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bx bx-book-open me-2"></i>Journal / General Ledger</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $transactions = $purchase->journal
                                ? $purchase->journal->glTransactions()->with('chartAccount')->get()
                                : $purchase->glTransactions()->with('chartAccount')->get();
                        @endphp
                        @if($transactions->count() > 0)
                            @php
                                $debitTotal = $transactions->where('nature','debit')->sum('amount');
                                $creditTotal = $transactions->where('nature','credit')->sum('amount');
                                $balance = $debitTotal - $creditTotal;
                            @endphp
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40%">Account</th>
                                            <th width="20%" class="text-center">Type</th>
                                            <th width="20%" class="text-end">Debit</th>
                                            <th width="20%" class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transactions as $t)
                                        <tr>
                                            <td>
                                                @if($t->chartAccount)
                                                <div class="fw-bold">{{ $t->chartAccount->account_name }}</div>
                                                <small class="text-muted">{{ $t->chartAccount->account_code }}</small>
                                                @else
                                                <div class="fw-bold text-warning">Account Not Found</div>
                                                <small class="text-muted">ID: {{ $t->chart_account_id }}</small>
                                                @endif
                                                @if($t->description)
                                                <br><small class="text-info">{{ $t->description }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $t->nature == 'debit' ? 'danger' : 'success' }} fs-6">{{ ucfirst($t->nature) }}</span>
                                            </td>
                                            <td class="text-end">@if($t->nature=='debit')<span class="fw-bold text-danger">TZS {{ number_format($t->amount,2) }}</span>@else<span class="text-muted">-</span>@endif</td>
                                            <td class="text-end">@if($t->nature=='credit')<span class="fw-bold text-success">TZS {{ number_format($t->amount,2) }}</span>@else<span class="text-muted">-</span>@endif</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">Totals:</th>
                                            <th class="text-end text-danger">TZS {{ number_format($debitTotal,2) }}</th>
                                            <th class="text-end text-success">TZS {{ number_format($creditTotal,2) }}</th>
                                        </tr>
                                        <tr>
                                            <th colspan="2" class="text-end">Balance:</th>
                                            <th colspan="2" class="text-end">
                                                <span class="badge bg-{{ $balance == 0 ? 'success' : 'danger' }} fs-6">{{ $balance == 0 ? 'Balanced' : 'Unbalanced' }}</span>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-book-open text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No GL transactions found for this cash purchase.</p>
                                <small class="text-muted">GL is posted via a journal entry when the purchase is saved.</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Amount Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span class="fw-bold">TZS {{ number_format($purchase->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">VAT Amount:</span>
                            <span class="fw-bold">TZS {{ number_format($purchase->vat_amount, 2) }}</span>
                        </div>
                        @if(($purchase->discount_amount ?? 0) > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Discount:</span>
                            <span class="fw-bold text-danger">-TZS {{ number_format($purchase->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="h6 mb-0">Total Amount:</span>
                            <span class="h5 mb-0 text-success">TZS {{ number_format($purchase->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Additional Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Created By</label>
                            <div class="fw-bold">{{ $purchase->createdBy->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Created At</label>
                            <div class="fw-bold">{{ optional($purchase->created_at)->format('M d, Y H:i') }}</div>
                        </div>
                        @if($purchase->updatedBy)
                        <div class="mb-3">
                            <label class="form-label text-muted small">Updated At</label>
                            <div class="fw-bold">{{ optional($purchase->updated_at)->format('M d, Y H:i') }}</div>
                        </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label text-muted small">Branch</label>
                            <div class="fw-bold">{{ $purchase->branch->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Company</label>
                            <div class="fw-bold">{{ $purchase->company->name ?? 'N/A' }}</div>
                        </div>

                        @if($purchase->notes || $purchase->terms_conditions || $purchase->attachment)
                        <hr>
                        <h6 class="mb-2"><i class="bx bx-note me-2"></i>Notes & Terms</h6>
                        @if($purchase->notes)
                        <div class="mb-2">
                            <label class="form-label text-muted small">Notes</label>
                            <p class="mb-0">{{ $purchase->notes }}</p>
                        </div>
                        @endif
                        @if($purchase->terms_conditions)
                        <div class="mb-2">
                            <label class="form-label text-muted small">Terms & Conditions</label>
                            <p class="mb-0">{{ $purchase->terms_conditions }}</p>
                        </div>
                        @endif
                        @if($purchase->attachment)
                        <div class="mt-2">
                            <a href="{{ asset('storage/' . $purchase->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-paperclip me-1"></i>View Attachment
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
function deleteCashPurchase(encodedId){
    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result)=>{
        if(result.isConfirmed){
            $.ajax({
                url: '{{ route('purchases.cash-purchases.index') }}' + '/' + encodedId,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(resp){
                    if(resp.success){
                        Swal.fire('Deleted!', resp.message || 'Cash purchase deleted', 'success');
                        setTimeout(function(){ window.location.href = '{{ route('purchases.cash-purchases.index') }}'; }, 800);
                    } else {
                        Swal.fire('Error!', resp.message || 'Failed to delete', 'error');
                    }
                },
                error: function(xhr){
                    let msg = 'An error occurred while deleting the cash purchase.';
                    if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
                    else if (xhr.responseText) { try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch(e) {} }
                    Swal.fire('Error!', msg, 'error');
                }
            });
        }
    });
}
</script>
@endpush

