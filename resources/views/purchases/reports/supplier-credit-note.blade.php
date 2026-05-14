@extends('layouts.main')

@section('title', 'Supplier Credit Note Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Supplier Credit Note', 'url' => '#', 'icon' => 'bx bx-undo']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-undo me-2"></i>Supplier Credit Note Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">From</label>
                                <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To</label>
                                <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select select2-single">
                                    <option value="">All</option>
                                    @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" {{ $supplierId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" {{ $status == 'all' || !$status ? 'selected' : '' }}>All</option>
                                    <option value="draft" {{ $status == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="approved" {{ $status == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="applied" {{ $status == 'applied' ? 'selected' : '' }}>Applied</option>
                                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-select select2-single">
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ ($branchId == $b->id || ($branchId == 'all' && $b->id == 'all')) ? 'selected' : '' }}>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-filter me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Stats -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Notes</h5>
                                        <h3 class="mb-0">{{ $summary['total_notes'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Net Amount</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_net_amount'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Total Tax Amount</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_tax_amount'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Gross Amount</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_gross_amount'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Supplier</th>
                                        <th>Credit Note No</th>
                                        <th>Invoice No</th>
                                        <th>Credit Note Date</th>
                                        <th class="text-end">Net Amount</th>
                                        <th class="text-end">Tax Amount</th>
                                        <th class="text-end">Gross Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($debitNotes as $debitNote)
                                        <tr>
                                            <td><strong>{{ $debitNote->supplier->name ?? 'N/A' }}</strong></td>
                                            <td>{{ $debitNote->debit_note_number }}</td>
                                            <td>{{ $debitNote->purchaseInvoice->invoice_number ?? '-' }}</td>
                                            <td>{{ $debitNote->debit_note_date->format('d-M-Y') }}</td>
                                            <td class="text-end">{{ number_format($debitNote->subtotal, 2) }}</td>
                                            <td class="text-end">{{ number_format($debitNote->vat_amount, 2) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($debitNote->total_amount, 2) }}</td>
                                            <td>
                                                @switch($debitNote->status)
                                                    @case('draft')
                                                        <span class="badge bg-secondary">Draft</span>
                                                        @break
                                                    @case('approved')
                                                        <span class="badge bg-info">Approved</span>
                                                        @break
                                                    @case('applied')
                                                        <span class="badge bg-success">Applied</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-danger">Cancelled</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($debitNote->status) }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle fs-1"></i>
                                                <p class="mt-2">No credit notes found for the selected criteria.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($debitNotes->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end fw-bold">Total:</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_net_amount'], 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_tax_amount'], 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_gross_amount'], 2) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
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
$(document).ready(function() {
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush

