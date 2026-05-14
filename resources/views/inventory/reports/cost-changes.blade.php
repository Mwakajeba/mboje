@extends('layouts.main')

@section('title', 'Cost Changes Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Cost Changes Report', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />
        
        <h6 class="mb-0 text-uppercase">COST CHANGES REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.cost-changes') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="item_id" class="form-label">Item</label>
                                <select class="form-select" id="item_id" name="item_id">
                                    <option value="">All Items</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.cost-changes') }}" class="btn btn-secondary">
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
                        <a href="{{ route('inventory.reports.cost-changes.export.excel', request()->query()) }}" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export to Excel
                        </a>
                        <a href="{{ route('inventory.reports.cost-changes.export.pdf', request()->query()) }}" class="btn btn-danger">
                            <i class="bx bx-file-pdf me-1"></i> Export to PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">Total Changes</h5>
                    <h3 class="text-primary">{{ number_format($totalChanges) }}</h3>
                    <p class="text-muted mb-0">All Cost Changes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">Average Cost</h5>
                    <h3 class="text-info">{{ number_format($averageCostChanges) }}</h3>
                    <p class="text-muted mb-0">Movement Changes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">FIFO Layers</h5>
                    <h3 class="text-success">{{ number_format($fifoLayerChanges) }}</h3>
                    <p class="text-muted mb-0">Layer Changes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning">Total Value</h5>
                    <h3 class="text-warning">{{ number_format($totalValue, 2) }} TZS</h3>
                    <p class="text-muted mb-0">Cost Impact</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cost Changes Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Location</th>
                                    <th>Cost Method</th>
                                    <th>Change Type</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Unit Cost</th>
                                    <th class="text-end">Total Cost</th>
                                    <th>Reason</th>
                                    <th>Reference</th>
                                    <th>User</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($costChanges as $change)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($change['date'])->format('Y-m-d H:i') }}</td>
                                        <td>{{ $change['item']->code ?? 'N/A' }}</td>
                                        <td>{{ $change['item']->name ?? 'N/A' }}</td>
                                        <td>{{ $change['location']->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $change['cost_method'] === 'FIFO' ? 'success' : 'info' }}">
                                                {{ $change['cost_method'] }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $badgeClass = match($change['movement_type']) {
                                                    'opening_balance' => 'primary',
                                                    'purchased' => 'success',
                                                    'adjustment_in' => 'warning',
                                                    'adjustment_out' => 'secondary',
                                                    'sale' => 'danger',
                                                    'transfer_in' => 'info',
                                                    'transfer_out' => 'info',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badgeClass }}">
                                                {{ ucfirst(str_replace('_', ' ', $change['movement_type'])) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($change['type'] === 'layer' && isset($change['remaining_quantity']))
                                                {{ number_format($change['quantity'], 2) }}
                                                @if($change['remaining_quantity'] < $change['quantity'])
                                                    <br><small class="text-muted">Remaining: {{ number_format($change['remaining_quantity'], 2) }}</small>
                                                @endif
                                            @else
                                                {{ number_format($change['quantity'], 2) }}
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($change['unit_cost'], 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($change['total_cost'], 2) }} TZS</td>
                                        <td>{{ $change['reason'] }}</td>
                                        <td>{{ $change['reference'] ?? '-' }}</td>
                                        <td>{{ $change['user']->name ?? 'System' }}</td>
                                        <td>
                                            @if($change['type'] === 'layer')
                                                @if($change['is_consumed'])
                                                    <span class="badge bg-danger">Consumed</span>
                                                @else
                                                    <span class="badge bg-success">Active</span>
                                                @endif
                                            @else
                                                <span class="badge bg-primary">Applied</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center">No cost changes found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
@endsection
