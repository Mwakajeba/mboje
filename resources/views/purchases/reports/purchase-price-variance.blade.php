@extends('layouts.main')

@section('title', 'Purchase Price Variance (PPV) Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Purchase Price Variance', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-trending-up me-2"></i>Purchase Price Variance (PPV) Report
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
                                        <h5 class="card-title text-primary">Total Items</h5>
                                        <h3 class="mb-0">{{ $summary['total_items'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Favorable Variance</h5>
                                        <h3 class="mb-0">{{ number_format(abs($summary['total_favorable_variance']), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Unfavorable Variance</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_unfavorable_variance'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-{{ $summary['net_variance'] < 0 ? 'success' : ($summary['net_variance'] > 0 ? 'warning' : 'info') }}">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-{{ $summary['net_variance'] < 0 ? 'success' : ($summary['net_variance'] > 0 ? 'warning' : 'info') }}">Net Variance</h5>
                                        <h3 class="mb-0">{{ number_format($summary['net_variance'], 2) }} TZS</h3>
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
                                        <th>Invoice No</th>
                                        <th>PO No</th>
                                        <th>Item Code</th>
                                        <th>Item Description</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">PO Unit Price</th>
                                        <th class="text-end">Invoice Unit Price</th>
                                        <th class="text-end">Price Variance</th>
                                        <th class="text-end">Variance %</th>
                                        <th class="text-end">Total Variance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                        <tr>
                                            <td><strong>{{ $row['supplier_name'] }}</strong></td>
                                            <td>{{ $row['invoice_no'] }}</td>
                                            <td>{{ $row['po_no'] }}</td>
                                            <td>{{ $row['item_code'] }}</td>
                                            <td>{{ $row['item_description'] }}</td>
                                            <td class="text-end">{{ number_format($row['quantity'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['po_unit_price'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['invoice_unit_price'], 2) }}</td>
                                            <td class="text-end {{ $row['price_variance'] < 0 ? 'text-success' : ($row['price_variance'] > 0 ? 'text-danger' : 'text-muted') }}">
                                                {{ number_format($row['price_variance'], 2) }}
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $row['variance_percent'] < 0 ? 'bg-success' : ($row['variance_percent'] > 0 ? 'bg-danger' : 'bg-secondary') }}">
                                                    {{ number_format($row['variance_percent'], 2) }}%
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold {{ $row['total_variance'] < 0 ? 'text-success' : ($row['total_variance'] > 0 ? 'text-danger' : 'text-muted') }}">
                                                {{ number_format($row['total_variance'], 2) }} TZS
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle fs-1"></i>
                                                <p class="mt-2">No data found for the selected criteria.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($reportData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="10" class="text-end fw-bold">Net Variance:</th>
                                        <th class="text-end fw-bold {{ $summary['net_variance'] < 0 ? 'text-success' : ($summary['net_variance'] > 0 ? 'text-danger' : 'text-muted') }}">
                                            {{ number_format($summary['net_variance'], 2) }} TZS
                                        </th>
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

