@extends('layouts.main')

@section('title', 'Inventory Quantity Summary')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Inventory Quantity Summary', 'url' => '#', 'icon' => 'bx bx-list-ul']
        ]" />
        
        <h6 class="mb-0 text-uppercase">INVENTORY QUANTITY SUMMARY</h6>
        <hr />

        <!-- Purpose and Use Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6 class="alert-heading mb-2"><i class="bx bx-info-circle me-2"></i>Purpose</h6>
                    <p class="mb-2">Provides total stock counts by item, category, or location without valuation.</p>
                    <h6 class="alert-heading mb-2"><i class="bx bx-target-lock me-2"></i>Use</h6>
                    <p class="mb-0">Operational insight for stock planning and warehouse management.</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.inventory-quantity-summary') }}">
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
                                    <label for="location_id" class="form-label">Location</label>
                                    <select class="form-select select2-single" id="location_id" name="location_id">
                                        <option value="">All Locations</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
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
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $dateFrom ?? now()->startOfMonth()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $dateTo ?? now()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-search me-1"></i> Generate
                                        </button>
                                        <a href="{{ route('inventory.reports.inventory-quantity-summary') }}" class="btn btn-secondary">
                                            <i class="bx bx-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('inventory.reports.inventory-quantity-summary.export.pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                                            <i class="bx bx-file me-1"></i> Export PDF
                                        </a>
                                        <a href="{{ route('inventory.reports.inventory-quantity-summary.export.excel', request()->all()) }}" class="btn btn-success" target="_blank">
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
                        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Inventory Quantity Summary Report</h5>
                        <small>Period: {{ \Carbon\Carbon::parse($dateFrom ?? now()->startOfMonth())->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo ?? now())->format('M d, Y') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th class="text-end">Opening Balance</th>
                                        <th class="text-end">Purchases</th>
                                        <th class="text-end">Production Orders</th>
                                        <th class="text-end">Cost of Sales</th>
                                        <th class="text-end">Adjustments</th>
                                        <th class="text-end">Closing Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalOpening = 0;
                                        $totalPurchases = 0;
                                        $totalProduction = 0;
                                        $totalCostOfSales = 0;
                                        $totalAdjustments = 0;
                                        $totalClosing = 0;
                                    @endphp
                                    @foreach($reportData as $data)
                                        @php
                                            $totalOpening += $data['opening_balance'];
                                            $totalPurchases += $data['purchases'];
                                            $totalProduction += $data['production_orders'];
                                            $totalCostOfSales += $data['cost_of_sales'];
                                            $totalAdjustments += $data['adjustments'];
                                            $totalClosing += $data['closing_balance'];
                                        @endphp
                                        <tr>
                                            <td>{{ $data['item']->code }}</td>
                                            <td>{{ $data['item']->name }}</td>
                                            <td>{{ $data['item']->category->name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($data['opening_balance'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['purchases'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['production_orders'], 2) }}</td>
                                            <td class="text-end text-danger">{{ number_format($data['cost_of_sales'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['adjustments'], 2) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($data['closing_balance'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end">TOTAL:</td>
                                        <td class="text-end">{{ number_format($totalOpening, 2) }}</td>
                                        <td class="text-end">{{ number_format($totalPurchases, 2) }}</td>
                                        <td class="text-end">{{ number_format($totalProduction, 2) }}</td>
                                        <td class="text-end text-danger">{{ number_format($totalCostOfSales, 2) }}</td>
                                        <td class="text-end">{{ number_format($totalAdjustments, 2) }}</td>
                                        <td class="text-end">{{ number_format($totalClosing, 2) }}</td>
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

        // Update locations when branch changes
        $('#branch_id').on('change', function() {
            const branchId = $(this).val();
            const locationSelect = $('#location_id');
            
            locationSelect.find('option:not(:first)').remove();
            
            $.ajax({
                url: '{{ route("inventory.reports.inventory-quantity-summary") }}',
                method: 'GET',
                data: {
                    branch_id: branchId,
                    get_locations: true
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.locations && response.locations.length > 0) {
                        response.locations.forEach(function(location) {
                            locationSelect.append(new Option(location.name, location.id));
                        });
                    }
                    locationSelect.trigger('change');
                },
                error: function() {
                    console.error('Failed to load locations');
                }
            });
        });
        
        @if(request('branch_id') || ($hasMultipleBranches && !request('branch_id')))
            $('#branch_id').trigger('change');
        @endif
    });
</script>
@endpush

