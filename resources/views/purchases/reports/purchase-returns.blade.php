@extends('layouts.main')

@section('title', 'Purchase Returns Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Purchase Returns', 'url' => '#', 'icon' => 'bx bx-undo']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-undo me-2"></i>Purchase Returns Report
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
                                        <h5 class="card-title text-primary">Total Returns</h5>
                                        <h3 class="mb-0">{{ $summary['total_returns'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Items</h5>
                                        <h3 class="mb-0">{{ $summary['total_items'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Total Quantity</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_quantity'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Total Value</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_value'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Return Date</th>
                                        <th>Return No</th>
                                        <th>Supplier</th>
                                        <th>Invoice No</th>
                                        <th>Item Code</th>
                                        <th>Item Description</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end">Return Value</th>
                                        <th>Reason</th>
                                        <th>Condition</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                        <tr>
                                            <td>{{ Carbon\Carbon::parse($row['return_date'])->format('d-M-Y') }}</td>
                                            <td>{{ $row['return_no'] }}</td>
                                            <td><strong>{{ $row['supplier_name'] }}</strong></td>
                                            <td>{{ $row['invoice_no'] }}</td>
                                            <td>{{ $row['item_code'] }}</td>
                                            <td>{{ $row['item_description'] }}</td>
                                            <td class="text-end">{{ number_format($row['quantity'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['unit_cost'], 2) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($row['return_value'], 2) }}</td>
                                            <td>{{ $row['reason'] }}</td>
                                            <td>
                                                @switch($row['return_condition'])
                                                    @case('resellable')
                                                        <span class="badge bg-success">Resellable</span>
                                                        @break
                                                    @case('damaged')
                                                        <span class="badge bg-warning">Damaged</span>
                                                        @break
                                                    @case('scrap')
                                                        <span class="badge bg-danger">Scrap</span>
                                                        @break
                                                    @case('refurbish')
                                                        <span class="badge bg-info">Refurbish</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($row['return_condition']) }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle fs-1"></i>
                                                <p class="mt-2">No returns found for the selected criteria.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($reportData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="6" class="text-end fw-bold">Total:</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_quantity'], 2) }}</th>
                                        <th></th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_value'], 2) }} TZS</th>
                                        <th colspan="2"></th>
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

