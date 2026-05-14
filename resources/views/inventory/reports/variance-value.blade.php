@extends('layouts.main')

@section('title', 'Variance Value Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Variance Value', 'url' => '#', 'icon' => 'bx bx-dollar']
        ]" />
        
        <h6 class="mb-0 text-uppercase">VARIANCE VALUE REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.variance-value') }}">
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
                                    <label for="min_value" class="form-label">Min Value (TZS)</label>
                                    <input type="number" class="form-control" id="min_value" name="min_value" value="{{ request('min_value') }}" step="0.01">
                                </div>
                                <div class="col-md-3">
                                    <label for="max_value" class="form-label">Max Value (TZS)</label>
                                    <input type="number" class="form-control" id="max_value" name="max_value" value="{{ request('max_value') }}" step="0.01">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bx bx-search me-1"></i> Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Variance Values (Sorted by Value)</h5>
                        <span class="badge bg-primary">Total: {{ number_format($variances->sum('variance_value'), 2) }} TZS</span>
                    </div>
                    <div class="card-body">
                        @if($variances->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th>Session</th>
                                            <th>Location</th>
                                            <th>Variance Qty</th>
                                            <th>Unit Cost</th>
                                            <th>Variance Value (TZS)</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($variances as $index => $variance)
                                            <tr>
                                                <td><strong>#{{ $index + 1 }}</strong></td>
                                                <td>{{ $variance->item->item_code ?? 'N/A' }}</td>
                                                <td>{{ $variance->item->name ?? 'N/A' }}</td>
                                                <td>{{ $variance->entry->session->session_number ?? 'N/A' }}</td>
                                                <td>{{ $variance->entry->location->name ?? 'N/A' }}</td>
                                                <td class="{{ $variance->variance_quantity > 0 ? 'text-success' : ($variance->variance_quantity < 0 ? 'text-danger' : '') }}">
                                                    {{ number_format($variance->variance_quantity, 2) }}
                                                </td>
                                                <td>{{ number_format($variance->unit_cost, 2) }}</td>
                                                <td><strong>{{ number_format($variance->variance_value, 2) }}</strong></td>
                                                <td>
                                                    <span class="badge bg-{{ $variance->variance_type === 'positive' ? 'success' : ($variance->variance_type === 'negative' ? 'danger' : 'secondary') }}">
                                                        {{ ucfirst($variance->variance_type) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>No variances found matching the criteria.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

