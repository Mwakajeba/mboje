@extends('layouts.main')

@section('title', 'High-Value Items Scorecard')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'High-Value Scorecard', 'url' => '#', 'icon' => 'bx bx-trophy']
        ]" />
        
        <h6 class="mb-0 text-uppercase">HIGH-VALUE ITEMS SCORECARD</h6>
        <hr />

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.high-value-scorecard') }}">
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
                                <div class="col-md-8">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-search me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('inventory.reports.high-value-scorecard') }}" class="btn btn-secondary">
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

        <!-- Report Data -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">High-Value Items Scorecard</h5>
                    </div>
                    <div class="card-body">
                        @if($itemScorecard->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th>Variance Count</th>
                                            <th>Total Variance Value (TZS)</th>
                                            <th>Avg Variance %</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itemScorecard as $index => $scorecard)
                                            <tr>
                                                <td><strong>#{{ $index + 1 }}</strong></td>
                                                <td>{{ $scorecard['item']->item_code ?? 'N/A' }}</td>
                                                <td>{{ $scorecard['item']->name ?? 'N/A' }}</td>
                                                <td><span class="badge bg-info">{{ $scorecard['variance_count'] }}</span></td>
                                                <td><strong class="text-danger">{{ number_format($scorecard['total_variance_value'], 2) }}</strong></td>
                                                <td>{{ number_format($scorecard['avg_variance_percentage'], 2) }}%</td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="showVariances({{ $scorecard['item']->id }})">
                                                        <i class="bx bx-show me-1"></i> View Details
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>No high-value items found.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

