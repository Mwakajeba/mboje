@extends('layouts.main')

@section('title', 'Inventory Aging Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Inventory Aging Report', 'url' => '#', 'icon' => 'bx bx-time']
        ]" />
        
        <h6 class="mb-0 text-uppercase">INVENTORY AGING REPORT</h6>
        <hr />

        <!-- Purpose and Use Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6 class="alert-heading mb-2"><i class="bx bx-info-circle me-2"></i>Purpose</h6>
                    <p class="mb-2">Identify how long stock items have been in inventory to detect slow-moving or obsolete items.</p>
                    <h6 class="alert-heading mb-2"><i class="bx bx-target-lock me-2"></i>Use</h6>
                    <p class="mb-0">Items accumulating in ">180 Days" are slow-moving and tie up cash.</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.inventory-aging') }}">
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
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-search me-1"></i> Generate
                                        </button>
                                        <a href="{{ route('inventory.reports.inventory-aging') }}" class="btn btn-secondary">
                                            <i class="bx bx-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('inventory.reports.inventory-aging.export.pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                                            <i class="bx bx-file me-1"></i> Export PDF
                                        </a>
                                        <a href="{{ route('inventory.reports.inventory-aging.export.excel', request()->all()) }}" class="btn btn-success" target="_blank">
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
                        <h5 class="mb-0"><i class="bx bx-time me-2"></i>Inventory Aging Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th class="text-end">Quantity on Hand</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end">Value (TZS)</th>
                                        <th>Last Movement Date</th>
                                        <th class="text-end">0-30 Days</th>
                                        <th class="text-end">31-60 Days</th>
                                        <th class="text-end">61-90 Days</th>
                                        <th class="text-end">91-180 Days</th>
                                        <th class="text-end">>180 Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalQty = 0;
                                        $totalValue = 0;
                                        $total0_30 = 0;
                                        $total31_60 = 0;
                                        $total61_90 = 0;
                                        $total91_180 = 0;
                                        $totalOver180 = 0;
                                    @endphp
                                    @foreach($reportData as $data)
                                        @php
                                            $totalQty += $data['quantity'];
                                            $totalValue += $data['value'];
                                            $total0_30 += $data['age_0_30'];
                                            $total31_60 += $data['age_31_60'];
                                            $total61_90 += $data['age_61_90'];
                                            $total91_180 += $data['age_91_180'];
                                            $totalOver180 += $data['age_over_180'];
                                        @endphp
                                        <tr>
                                            <td>{{ $data['item']->code }}</td>
                                            <td>{{ $data['item']->name }}</td>
                                            <td>{{ $data['item']->category->name ?? 'N/A' }}</td>
                                            <td>{{ $data['location']->name }}</td>
                                            <td class="text-end">{{ number_format($data['quantity'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['unit_cost'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['value'], 2) }}</td>
                                            <td>{{ $data['last_movement_date'] ? \Carbon\Carbon::parse($data['last_movement_date'])->format('Y-m-d') : 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($data['age_0_30'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['age_31_60'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['age_61_90'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['age_91_180'], 2) }}</td>
                                            <td class="text-end text-danger fw-bold">{{ number_format($data['age_over_180'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="4" class="text-end">TOTAL:</td>
                                        <td class="text-end">{{ number_format($totalQty, 2) }}</td>
                                        <td class="text-end">-</td>
                                        <td class="text-end">{{ number_format($totalValue, 2) }}</td>
                                        <td>-</td>
                                        <td class="text-end">{{ number_format($total0_30, 2) }}</td>
                                        <td class="text-end">{{ number_format($total31_60, 2) }}</td>
                                        <td class="text-end">{{ number_format($total61_90, 2) }}</td>
                                        <td class="text-end">{{ number_format($total91_180, 2) }}</td>
                                        <td class="text-end text-danger">{{ number_format($totalOver180, 2) }}</td>
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

        $('#branch_id').on('change', function() {
            const branchId = $(this).val();
            const locationSelect = $('#location_id');
            
            locationSelect.find('option:not(:first)').remove();
            
            $.ajax({
                url: '{{ route("inventory.reports.inventory-aging") }}',
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

