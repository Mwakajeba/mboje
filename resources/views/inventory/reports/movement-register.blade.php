@extends('layouts.main')

@section('title', 'Movement Register Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Movement Register', 'url' => '#', 'icon' => 'bx bx-transfer']
        ]" />
        
        <h6 class="mb-0 text-uppercase">MOVEMENT REGISTER REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.movement-register') }}">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="movement_type" class="form-label">Movement Type</label>
                                <select class="form-select" id="movement_type" name="movement_type">
                                    <option value="">All Types</option>
                                    @foreach($movementTypes as $value => $label)
                                        <option value="{{ $value }}" {{ request('movement_type') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
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
                                <label for="location_id" class="form-label">Location</label>
                                <select class="form-select" id="location_id" name="location_id">
                                    <option value="">All Locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="user_id" class="form-label">User</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">All Users</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-search me-1"></i> Filter
                                </button>
                                <a href="{{ route('inventory.reports.movement-register') }}" class="btn btn-secondary">
                                    <i class="bx bx-refresh me-1"></i> Reset
                                </a>
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
                        <a href="{{ route('inventory.reports.movement-register.export.excel', request()->query()) }}" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export to Excel
                        </a>
                        <a href="{{ route('inventory.reports.movement-register.export.pdf', request()->query()) }}" class="btn btn-danger">
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
                    <h5 class="card-title text-primary">Total Movements</h5>
                    <h3 class="text-primary">{{ number_format($totalMovements) }}</h3>
                    <p class="text-muted mb-0">All Transactions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">Stock In</h5>
                    <h3 class="text-success">{{ number_format($totalInQuantity, 2) }}</h3>
                    <p class="text-muted mb-0">{{ number_format($totalInValue, 2) }} TZS</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h5 class="card-title text-danger">Stock Out</h5>
                    <h3 class="text-danger">{{ number_format($totalOutQuantity, 2) }}</h3>
                    <p class="text-muted mb-0">{{ number_format($totalOutValue, 2) }} TZS</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">Net Movement</h5>
                    <h3 class="text-info">{{ number_format($totalInQuantity - $totalOutQuantity, 2) }}</h3>
                    <p class="text-muted mb-0">{{ number_format($totalInValue - $totalOutValue, 2) }} TZS</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Movement Register Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Ref. No</th>
                                    <th>Movement Type</th>
                                    <th class="text-end">In Qty</th>
                                    <th class="text-end">Out Qty</th>
                                    <th class="text-end">Balance Qty</th>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Location</th>
                                    <th>Entered By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movementsWithBalance ?? $movements as $movement)
                                    <tr>
                                        <td>{{ $movement->movement_date ? \Carbon\Carbon::parse($movement->movement_date)->format('Y-m-d') : $movement->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            @if($movement->reference)
                                                {{ $movement->reference }}
                                            @elseif($movement->movement_type == 'opening_balance')
                                                Opening
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $badgeClass = match($movement->movement_type) {
                                                    'opening_balance' => 'primary',
                                                    'transfer_in' => 'success',
                                                    'transfer_out' => 'info',
                                                    'sold' => 'danger',
                                                    'purchased' => 'success',
                                                    'adjustment_in' => 'warning',
                                                    'adjustment_out' => 'secondary',
                                                    'write_off' => 'dark',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badgeClass }}">
                                                {{ $movementTypes[$movement->movement_type] ?? ucfirst(str_replace('_', ' ', $movement->movement_type)) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if(isset($movement->in_qty) && $movement->in_qty > 0)
                                                <span class="text-success">+{{ number_format($movement->in_qty, 2) }}</span>
                                            @else
                                                <span class="text-muted">–</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if(isset($movement->out_qty) && $movement->out_qty > 0)
                                                <span class="text-danger">-{{ number_format($movement->out_qty, 2) }}</span>
                                            @else
                                                <span class="text-muted">–</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($movement->balance_qty ?? 0, 2) }}</strong>
                                        </td>
                                        <td>{{ $movement->item->code ?? 'N/A' }}</td>
                                        <td>{{ $movement->item->name ?? 'N/A' }}</td>
                                        <td>{{ $movement->location->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($movement->user)
                                                {{ $movement->user->name }}
                                                
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No movements found</td>
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
@endsection
