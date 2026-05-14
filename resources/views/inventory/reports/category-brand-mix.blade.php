@extends('layouts.main')

@section('title', 'Category/Brand Mix Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Category/Brand Mix', 'url' => '#', 'icon' => 'bx bx-category']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CATEGORY/BRAND MIX REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.category-brand-mix') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.category-brand-mix') }}" class="btn btn-secondary">
                                        <i class="bx bx-refresh me-1"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <a href="{{ route('inventory.reports.category-brand-mix.export.excel', request()->query()) }}" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('inventory.reports.category-brand-mix.export.pdf', request()->query()) }}" class="btn btn-danger">
                            <i class="bx bx-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">Total Quantity</h5>
                    <h3 class="text-primary">{{ number_format($grandTotalQty, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">Total Value</h5>
                    <h3 class="text-success">{{ number_format($grandTotalValue, 2) }} TZS</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Mix Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Category/Brand Mix Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Items Count</th>
                                    <th class="text-end">Total Quantity</th>
                                    <th class="text-end">Total Value</th>
                                    <th class="text-end">Qty %</th>
                                    <th class="text-end">Value %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categoryMix->sortByDesc('total_value') as $categoryName => $data)
                                    <tr>
                                        <td>
                                            <strong>{{ $categoryName }}</strong>
                                        </td>
                                        <td class="text-end">{{ $data['items_count'] }}</td>
                                        <td class="text-end">{{ number_format($data['total_qty'], 2) }}</td>
                                        <td class="text-end">{{ number_format($data['total_value'], 2) }} TZS</td>
                                        <td class="text-end">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: {{ $data['qty_percentage'] }}%">
                                                    {{ number_format($data['qty_percentage'], 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: {{ $data['value_percentage'] }}%">
                                                    {{ number_format($data['value_percentage'], 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No categories found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>TOTAL:</th>
                                    <th class="text-end">{{ $categoryMix->sum('items_count') }}</th>
                                    <th class="text-end">{{ number_format($grandTotalQty, 2) }}</th>
                                    <th class="text-end">{{ number_format($grandTotalValue, 2) }} TZS</th>
                                    <th class="text-end">100.0%</th>
                                    <th class="text-end">100.0%</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection
