@extends('layouts.main')

@section('title', 'Expiry & Damaged Stock Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Expiry & Damaged Stock', 'url' => '#', 'icon' => 'bx bx-error-circle']
        ]" />
        
        <h6 class="mb-0 text-uppercase">EXPIRY & DAMAGED STOCK REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.expiry-damaged-stock') }}">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="session_id" class="form-label">Count Session</label>
                                    <select class="form-select" id="session_id" name="session_id">
                                        <option value="">All Sessions</option>
                                        @foreach($sessions as $session)
                                            <option value="{{ $session->id }}" {{ request('session_id') == $session->id ? 'selected' : '' }}>
                                                {{ $session->session_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="condition" class="form-label">Condition</label>
                                    <select class="form-select" id="condition" name="condition">
                                        <option value="">All Conditions</option>
                                        <option value="damaged" {{ request('condition') == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                        <option value="expired" {{ request('condition') == 'expired' ? 'selected' : '' }}>Expired</option>
                                        <option value="obsolete" {{ request('condition') == 'obsolete' ? 'selected' : '' }}>Obsolete</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="expiry_from" class="form-label">Expiry From</label>
                                    <input type="date" class="form-control" id="expiry_from" name="expiry_from" value="{{ request('expiry_from') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="expiry_to" class="form-label">Expiry To</label>
                                    <input type="date" class="form-control" id="expiry_to" name="expiry_to" value="{{ request('expiry_to') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h3 class="text-danger">{{ $expired->count() }}</h3>
                        <p class="mb-0">Expired Items</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h3 class="text-warning">{{ $expiringSoon->count() }}</h3>
                        <p class="mb-0">Expiring Soon (30 days)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-secondary">
                    <div class="card-body text-center">
                        <h3 class="text-secondary">{{ $byCondition->get('damaged')['count'] ?? 0 }}</h3>
                        <p class="mb-0">Damaged Items</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-dark">
                    <div class="card-body text-center">
                        <h3 class="text-dark">{{ $byCondition->get('obsolete')['count'] ?? 0 }}</h3>
                        <p class="mb-0">Obsolete Items</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Expiry & Damaged Stock Details</h5>
                    </div>
                    <div class="card-body">
                        @if($entries->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th>Session</th>
                                            <th>Location</th>
                                            <th>Condition</th>
                                            <th>Physical Qty</th>
                                            <th>Lot Number</th>
                                            <th>Batch Number</th>
                                            <th>Expiry Date</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($entries as $entry)
                                            <tr>
                                                <td>{{ $entry->item->item_code ?? 'N/A' }}</td>
                                                <td>{{ $entry->item->name ?? 'N/A' }}</td>
                                                <td>{{ $entry->session->session_number ?? 'N/A' }}</td>
                                                <td>{{ $entry->location->name ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $entry->condition === 'damaged' ? 'danger' : ($entry->condition === 'expired' ? 'warning' : ($entry->condition === 'obsolete' ? 'dark' : 'secondary')) }}">
                                                        {{ ucfirst($entry->condition ?? 'good') }}
                                                    </span>
                                                </td>
                                                <td>{{ number_format($entry->physical_quantity, 2) }}</td>
                                                <td>{{ $entry->lot_number ?? 'N/A' }}</td>
                                                <td>{{ $entry->batch_number ?? 'N/A' }}</td>
                                                <td>
                                                    @if($entry->expiry_date)
                                                        <span class="{{ $entry->expiry_date->isPast() ? 'text-danger' : ($entry->expiry_date->diffInDays(now()) <= 30 ? 'text-warning' : '') }}">
                                                            {{ $entry->expiry_date->format('M d, Y') }}
                                                        </span>
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>{{ $entry->remarks ?? 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>No expired or damaged stock found.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

