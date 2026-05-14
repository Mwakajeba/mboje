@extends('layouts.main')

@section('title', 'Purchase Forecast Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Purchase Forecast', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-line-chart me-2"></i>Purchase Forecast Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">Forecast Period (Months)</label>
                                <input type="number" name="months" value="{{ $months }}" min="1" max="24" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select select2-single">
                                    <option value="">All</option>
                                    @foreach($categories as $c)
                                    <option value="{{ $c->id }}" {{ $categoryId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
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
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Forecast Qty</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_forecast_qty'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Suggested Purchase</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_suggested_purchase'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th class="text-end">Monthly Avg Usage</th>
                                        <th class="text-end">Current Stock</th>
                                        <th class="text-end">Forecast Qty</th>
                                        <th class="text-end">Suggested Purchase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($itemData as $row)
                                        <tr>
                                            <td><strong>{{ $row['item_code'] }}</strong></td>
                                            <td>{{ $row['item_name'] }}</td>
                                            <td>{{ $row['category'] }}</td>
                                            <td class="text-end">{{ number_format($row['monthly_avg_usage'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['current_stock'], 2) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($row['forecast_qty'], 2) }}</td>
                                            <td class="text-end fw-bold text-primary">{{ number_format($row['suggested_purchase'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle fs-1"></i>
                                                <p class="mt-2">No data found for the selected criteria.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($itemData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold">Total:</th>
                                        <th></th>
                                        <th></th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_forecast_qty'], 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_suggested_purchase'], 2) }}</th>
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
