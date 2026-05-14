@extends('layouts.main')

@section('title', 'PO vs Invoice Variance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'PO vs Invoice Variance', 'url' => '#', 'icon' => 'bx bx-error']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-error me-2"></i>Supplier Invoice Variance Report (PO vs Invoice)
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">From</label>
                                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To</label>
                                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
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
                            <div class="col-md-4">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Items</h5>
                                        <h3 class="mb-0">{{ $summary['total_items'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Total Qty Variance</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_qty_variance'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Total Value Variance</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_value_variance'], 2) }} TZS</h3>
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
                                        <th>PO No</th>
                                        <th>Invoice No</th>
                                        <th class="text-end">PO Qty</th>
                                        <th class="text-end">Invoice Qty</th>
                                        <th class="text-end">PO Unit Cost</th>
                                        <th class="text-end">Invoice Unit Cost</th>
                                        <th class="text-end">Qty Variance</th>
                                        <th class="text-end">Price Variance</th>
                                        <th class="text-end">Value Variance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                        <tr>
                                            <td><strong>{{ $row['supplier_name'] }}</strong></td>
                                            <td>{{ $row['po_number'] }}</td>
                                            <td>{{ $row['invoice_no'] }}</td>
                                            <td class="text-end">{{ number_format($row['po_qty'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['invoice_qty'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['po_unit_cost'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['invoice_unit_cost'], 2) }}</td>
                                            <td class="text-end {{ $row['qty_variance'] != 0 ? 'text-warning' : 'text-success' }}">
                                                {{ number_format($row['qty_variance'], 2) }}
                                            </td>
                                            <td class="text-end {{ $row['price_variance'] != 0 ? 'text-warning' : 'text-success' }}">
                                                {{ number_format($row['price_variance'], 2) }}
                                            </td>
                                            <td class="text-end fw-bold {{ $row['value_variance'] != 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($row['value_variance'], 2) }} TZS
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle fs-1"></i>
                                                <p class="mt-2">No data found for the selected criteria.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($reportData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold">Total:</th>
                                        <th class="text-end fw-bold">{{ number_format($reportData->sum('po_qty'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($reportData->sum('invoice_qty'), 2) }}</th>
                                        <th colspan="2"></th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_qty_variance'], 2) }}</th>
                                        <th></th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_value_variance'], 2) }} TZS</th>
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
