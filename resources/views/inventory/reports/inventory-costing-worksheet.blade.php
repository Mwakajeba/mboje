@extends('layouts.main')

@section('title', 'Inventory Costing Calculation Worksheet')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Inventory Costing Calculation Worksheet', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />
        
        <h6 class="mb-0 text-uppercase">INVENTORY COSTING CALCULATION WORKSHEET</h6>
        <hr />

        <!-- Purpose and Use Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6 class="alert-heading mb-2"><i class="bx bx-info-circle me-2"></i>Purpose</h6>
                    <p class="mb-2">Provides detailed calculation of item cost using a selected valuation method from drop down list.</p>
                    <h6 class="alert-heading mb-2"><i class="bx bx-target-lock me-2"></i>Use</h6>
                    <p class="mb-0">Ensures compliance with accounting policies and audit transparency.</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.inventory-costing-worksheet') }}">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="costing_method" class="form-label">Costing Method</label>
                                    <select class="form-select select2-single" id="costing_method" name="costing_method">
                                        <option value="weighted_average" {{ request('costing_method', 'weighted_average') == 'weighted_average' ? 'selected' : '' }}>
                                            Weighted Average
                                        </option>
                                        <option value="fifo" {{ request('costing_method') == 'fifo' ? 'selected' : '' }}>
                                            FIFO (First In, First Out)
                                        </option>
                                    </select>
                                    <small class="text-muted">System Method: {{ ucfirst(str_replace('_', ' ', $systemCostMethod)) }}</small>
                                </div>
                                <div class="col-md-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select select2-single" id="category_id" name="category_id">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-search me-1"></i> Generate
                                        </button>
                                        <a href="{{ route('inventory.reports.inventory-costing-worksheet') }}" class="btn btn-secondary">
                                            <i class="bx bx-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('inventory.reports.inventory-costing-worksheet.export.pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                                            <i class="bx bx-file me-1"></i> Export PDF
                                        </a>
                                        <a href="{{ route('inventory.reports.inventory-costing-worksheet.export.excel', request()->all()) }}" class="btn btn-success" target="_blank">
                                            <i class="bx bx-file me-1"></i> Export Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($reportData) && $reportData->count() > 0)
        <!-- Report Data -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-calculator me-2"></i>Inventory Costing Calculation Worksheet</h5>
                        <small>Method: {{ ucfirst(str_replace('_', ' ', $costingMethod)) }}</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Average Cost</th>
                                        <th class="text-end">Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalQty = 0;
                                        $totalCost = 0;
                                    @endphp
                                    @foreach($reportData as $data)
                                        @php
                                            $totalQty += $data['quantity'];
                                            $totalCost += $data['total_cost'];
                                        @endphp
                                        <tr>
                                            <td>{{ $data['item']->code }}</td>
                                            <td>{{ $data['item']->name }}</td>
                                            <td>{{ $data['item']->category->name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($data['quantity'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['average_cost'], 4) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($data['total_cost'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end">TOTAL:</td>
                                        <td class="text-end">{{ number_format($totalQty, 2) }}</td>
                                        <td class="text-end">-</td>
                                        <td class="text-end">{{ number_format($totalCost, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @elseif(isset($reportData))
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="bx bx-info-circle me-2"></i>No data found for the selected criteria.
                </div>
            </div>
        </div>
        @endif
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

        // Auto-submit form when costing method changes
        $('#costing_method').on('change', function() {
            $('form').submit();
        });
    });
</script>
@endpush

