@extends('layouts.main')

@section('title', 'Variance Summary Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Variance Summary', 'url' => '#', 'icon' => 'bx bx-bar-chart-alt-2']
        ]" />
        
        <h6 class="mb-0 text-uppercase">VARIANCE SUMMARY REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.variance-summary') }}">
                            <div class="row g-3">
                                <div class="col-md-4">
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
                                    <label for="variance_type" class="form-label">Variance Type</label>
                                    <select class="form-select" id="variance_type" name="variance_type">
                                        <option value="">All Types</option>
                                        <option value="zero" {{ request('variance_type') == 'zero' ? 'selected' : '' }}>Zero</option>
                                        <option value="positive" {{ request('variance_type') == 'positive' ? 'selected' : '' }}>Positive (Surplus)</option>
                                        <option value="negative" {{ request('variance_type') == 'negative' ? 'selected' : '' }}>Negative (Shortage)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="is_high_value" class="form-label">High Value</label>
                                    <select class="form-select" id="is_high_value" name="is_high_value">
                                        <option value="">All</option>
                                        <option value="1" {{ request('is_high_value') == '1' ? 'selected' : '' }}>High Value Only</option>
                                        <option value="0" {{ request('is_high_value') == '0' ? 'selected' : '' }}>Non-High Value</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
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

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h3 class="text-primary">{{ number_format($summary['total_variances']) }}</h3>
                        <p class="mb-0">Total Variances</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h3 class="text-success">{{ number_format($summary['zero_variances']) }}</h3>
                        <p class="mb-0">Zero Variances</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h3 class="text-info">{{ number_format($summary['positive_variances']) }}</h3>
                        <p class="mb-0">Positive (Surplus)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h3 class="text-danger">{{ number_format($summary['negative_variances']) }}</h3>
                        <p class="mb-0">Negative (Shortage)</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h3 class="text-warning">{{ number_format($summary['high_value_variances']) }}</h3>
                        <p class="mb-0">High-Value Variances</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-secondary">
                    <div class="card-body text-center">
                        <h3 class="text-secondary">{{ number_format($summary['total_variance_value'], 2) }}</h3>
                        <p class="mb-0">Total Variance Value (TZS)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-dark">
                    <div class="card-body text-center">
                        <h3 class="text-dark">{{ number_format($summary['total_positive_value'] - $summary['total_negative_value'], 2) }}</h3>
                        <p class="mb-0">Net Variance Value (TZS)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Variance Details</h5>
                    </div>
                    <div class="card-body">
                        @if($variances->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th>Session</th>
                                            <th>System Qty</th>
                                            <th>Physical Qty</th>
                                            <th>Variance Qty</th>
                                            <th>Variance %</th>
                                            <th>Variance Value</th>
                                            <th>Type</th>
                                            <th>High Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($variances as $variance)
                                            <tr>
                                                <td>{{ $variance->item->item_code ?? 'N/A' }}</td>
                                                <td>{{ $variance->item->name ?? 'N/A' }}</td>
                                                <td>{{ $variance->entry->session->session_number ?? 'N/A' }}</td>
                                                <td>{{ number_format($variance->system_quantity, 2) }}</td>
                                                <td>{{ number_format($variance->physical_quantity, 2) }}</td>
                                                <td class="{{ $variance->variance_quantity > 0 ? 'text-success' : ($variance->variance_quantity < 0 ? 'text-danger' : '') }}">
                                                    {{ number_format($variance->variance_quantity, 2) }}
                                                </td>
                                                <td>{{ number_format($variance->variance_percentage, 2) }}%</td>
                                                <td>{{ number_format($variance->variance_value, 2) }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $variance->variance_type === 'positive' ? 'success' : ($variance->variance_type === 'negative' ? 'danger' : 'secondary') }}">
                                                        {{ ucfirst($variance->variance_type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($variance->is_high_value)
                                                        <span class="badge bg-warning">Yes</span>
                                                    @else
                                                        <span class="badge bg-secondary">No</span>
                                                    @endif
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

