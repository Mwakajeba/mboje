@extends('layouts.main')

@section('title', 'Inventory Quantity by Location')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Inventory Quantity by Location', 'url' => '#', 'icon' => 'bx bx-map']
        ]" />
        
        <h6 class="mb-0 text-uppercase">INVENTORY QUANTITY BY LOCATION</h6>
        <hr />

        <!-- Purpose and Use Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6 class="alert-heading mb-2"><i class="bx bx-info-circle me-2"></i>Purpose</h6>
                    <p class="mb-2">Shows stock distribution across multiple warehouses/branches.</p>
                    <h6 class="alert-heading mb-2"><i class="bx bx-target-lock me-2"></i>Use</h6>
                    <p class="mb-0">Helps with logistics, transfer planning, and stock optimization.</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.inventory-quantity-by-location') }}">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-select select2-single" id="branch_id" name="branch_id">
                                        @if($hasMultipleBranches)
                                            <option value="all_my_branches" {{ request('branch_id', 'all_my_branches') == 'all_my_branches' ? 'selected' : '' }}>
                                                All My Branches
                                            </option>
                                        @else
                                            <option value="">All Branches</option>
                                        @endif
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ request('branch_id', $hasMultipleBranches ? 'all_my_branches' : (session('branch_id') ?? '')) == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
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
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-search me-1"></i> Generate
                                        </button>
                                        <a href="{{ route('inventory.reports.inventory-quantity-by-location') }}" class="btn btn-secondary">
                                            <i class="bx bx-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('inventory.reports.inventory-quantity-by-location.export.pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                                            <i class="bx bx-file me-1"></i> Export PDF
                                        </a>
                                        <a href="{{ route('inventory.reports.inventory-quantity-by-location.export.excel', request()->all()) }}" class="btn btn-success" target="_blank">
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
                        <h5 class="mb-0"><i class="bx bx-map me-2"></i>Inventory Quantity by Location Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        @foreach($locations as $location)
                                            <th class="text-end">{{ $location->name }}</th>
                                        @endforeach
                                        <th class="text-end">Total Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $locationTotals = [];
                                        foreach($locations as $location) {
                                            $locationTotals[$location->id] = 0;
                                        }
                                        $grandTotal = 0;
                                    @endphp
                                    @foreach($reportData as $data)
                                        @php
                                            $rowTotal = 0;
                                        @endphp
                                        <tr>
                                            <td>{{ $data['item']->code }}</td>
                                            <td>{{ $data['item']->name }}</td>
                                            <td>{{ $data['item']->category->name ?? 'N/A' }}</td>
                                            @foreach($locations as $location)
                                                @php
                                                    $qty = $data['location_quantities'][$location->id] ?? 0;
                                                    $locationTotals[$location->id] += $qty;
                                                    $rowTotal += $qty;
                                                @endphp
                                                <td class="text-end">{{ number_format($qty, 2) }}</td>
                                            @endforeach
                                            @php
                                                $grandTotal += $rowTotal;
                                            @endphp
                                            <td class="text-end fw-bold">{{ number_format($rowTotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end">TOTAL:</td>
                                        @foreach($locations as $location)
                                            <td class="text-end">{{ number_format($locationTotals[$location->id], 2) }}</td>
                                        @endforeach
                                        <td class="text-end">{{ number_format($grandTotal, 2) }}</td>
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
    });
</script>
@endpush

