@extends('layouts.main')

@section('title', 'Purchase Analysis by Item/Category')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Purchase Analysis by Item', 'url' => '#', 'icon' => 'bx bx-basket']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-basket me-2"></i>Purchase Analysis by Item/Category
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
                                        <h5 class="card-title text-primary">Total Categories</h5>
                                        <h3 class="mb-0">{{ $summary['total_categories'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Quantity</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_qty'], 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Value</h5>
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
                                        <th>Category</th>
                                        <th class="text-end">Total Qty</th>
                                        <th class="text-end">Total Value</th>
                                        <th class="text-end">Avg Unit Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categoryData as $row)
                                        <tr>
                                            <td><strong>{{ $row['category_name'] }}</strong></td>
                                            <td class="text-end">{{ number_format($row['total_qty'], 2) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($row['total_value'], 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($row['avg_unit_cost'], 2) }} TZS</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle fs-1"></i>
                                                <p class="mt-2">No data found for the selected criteria.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($categoryData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th class="text-end fw-bold">Total:</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_qty'], 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_value'], 2) }} TZS</th>
                                        <th class="text-end fw-bold">{{ $summary['total_qty'] > 0 ? number_format($summary['total_value'] / $summary['total_qty'], 2) : 0 }} TZS</th>
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
