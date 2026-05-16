@extends('layouts.main')

@section('title', 'Inventory Value — ' . $location->name)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Value', 'url' => route('inventory.value.index', ['pick' => 1]), 'icon' => 'bx bx-dollar-circle'],
            ['label' => $location->name, 'url' => '#', 'icon' => 'bx bx-building'],
        ]" />
        <h6 class="mb-0 text-uppercase">INVENTORY VALUE — {{ strtoupper($location->name) }}</h6>
        <hr />

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Total Cost Value</small>
                        <h4 class="mb-0 text-primary">{{ number_format($totals['total_cost'], 2) }} {{ $currency }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Total Selling Value</small>
                        <h4 class="mb-0 text-success">{{ number_format($totals['total_selling'], 2) }} {{ $currency }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Items / Total Qty</small>
                        <h4 class="mb-0 text-info">{{ $totals['items_count'] }} / {{ number_format($totals['total_quantity'], 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        @if($location->branch)
        <p class="text-muted small mb-3">
            <i class="bx bx-map me-1"></i> Branch: <strong>{{ $location->branch->name }}</strong>
        </p>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0">Items at this location</h5>
                <a href="{{ route('inventory.value.index', ['pick' => 1]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-building me-1"></i> Change location
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Item</th>
                                <th>Category</th>
                                <th class="text-end">Current Stock</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Total Cost</th>
                                <th class="text-end">Unit Selling</th>
                                <th class="text-end">Total Selling</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                            <tr>
                                <td>{{ $row['item']->code }}</td>
                                <td>
                                    <a href="{{ route('inventory.items.show', $row['item']) }}" class="fw-semibold text-decoration-none">
                                        {{ $row['item']->name }}
                                    </a>
                                </td>
                                <td>{{ $row['item']->category->name ?? '—' }}</td>
                                <td class="text-end">
                                    {{ number_format($row['quantity'], 2) }}
                                    <small class="text-muted">{{ $row['unit_of_measure'] }}</small>
                                </td>
                                <td class="text-end">{{ number_format($row['unit_cost'], 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($row['total_cost'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['unit_selling_price'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['total_selling_price'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No stock on hand at this location.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($rows) > 0)
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Totals</th>
                                <th class="text-end">{{ number_format($totals['total_quantity'], 2) }}</th>
                                <th></th>
                                <th class="text-end">{{ number_format($totals['total_cost'], 2) }}</th>
                                <th></th>
                                <th class="text-end">{{ number_format($totals['total_selling'], 2) }}</th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to Inventory
            </a>
        </div>
    </div>
</div>
@endsection
