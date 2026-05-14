@extends('layouts.main')

@section('title', 'Collection Efficiency Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Collection Efficiency', 'url' => '#', 'icon' => 'bx bx-time']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-time me-2"></i>Collection Efficiency Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.collection-efficiency.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.collection-efficiency.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select class="form-select" name="branch_id">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Credit Sales</h5>
                                        <h3 class="mb-0">{{ number_format($totalCreditSales, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Collected</h5>
                                        <h3 class="mb-0">{{ number_format($totalCollected, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Days Sales Outstanding</h5>
                                        <h3 class="mb-0">{{ number_format($dso, 1) }} days</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Collection Rate %</h5>
                                        <h3 class="mb-0">{{ number_format($collectionRate, 2) }}%</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Collection Performance Analysis -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">On-Time Collections</h5>
                                        <h3 class="mb-0">{{ number_format($onTimeCollections) }}</h3>
                                        <small class="text-muted">{{ number_format($onTimePercentage, 1) }}% of total</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Overdue Collections</h5>
                                        <h3 class="mb-0">{{ number_format($overdueCollections) }}</h3>
                                        <small class="text-muted">{{ number_format($overduePercentage, 1) }}% of total</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Average Collection Period</h5>
                                        <h3 class="mb-0">{{ number_format($averageCollectionPeriod, 1) }} days</h3>
                                        <small class="text-muted">From invoice to payment</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Invoice Number</th>
                                        <th>Customer</th>
                                        <th class="text-end">Invoice Amount (TZS)</th>
                                        <th class="text-end">Paid Amount (TZS)</th>
                                        <th class="text-end">Outstanding (TZS)</th>
                                        <th>Invoice Date</th>
                                        <th>Due Date</th>
                                        <th>Days Outstanding</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($collectionData as $index => $invoice)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <a href="{{ route('sales.invoices.show', $invoice->id) }}" class="text-primary">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->customer->name ?? 'Unknown Customer' }}</td>
                                            <td class="text-end">{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($invoice->paid_amount, 2) }}</td>
                                            <td class="text-end">
                                                <span class="fw-bold {{ $invoice->balance_due > 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ number_format($invoice->balance_due, 2) }}
                                                </span>
                                            </td>
                                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                            <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $invoice->days_outstanding <= 30 ? 'success' : ($invoice->days_outstanding <= 60 ? 'warning' : 'danger') }}">
                                                    {{ number_format($invoice->days_outstanding) }} days
                                                </span>
                                            </td>
                                            <td>
                                                @if($invoice->balance_due <= 0)
                                                    <span class="badge bg-success">Paid</span>
                                                @elseif($invoice->days_outstanding <= 30)
                                                    <span class="badge bg-info">Current</span>
                                                @elseif($invoice->days_outstanding <= 60)
                                                    <span class="badge bg-warning">Overdue</span>
                                                @else
                                                    <span class="badge bg-danger">Very Overdue</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No collection data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3">Total</th>
                                        <th class="text-end">{{ number_format($totalCreditSales, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalCollected, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalOutstanding, 2) }}</th>
                                        <th colspan="4">-</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Collection Efficiency Analysis -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border border-info">
                                    <div class="card-body">
                                        <h5 class="card-title text-info">
                                            <i class="bx bx-trending-up me-2"></i>Collection Efficiency Analysis
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">DSO Performance</h6>
                                                    <div class="progress mb-2" style="height: 20px;">
                                                        <div class="progress-bar bg-{{ $dso <= 30 ? 'success' : ($dso <= 45 ? 'warning' : 'danger') }}" 
                                                             role="progressbar" 
                                                             style="width: {{ min($dso, 60) }}%" 
                                                             aria-valuenow="{{ $dso }}" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="60">
                                                            {{ number_format($dso, 1) }} days
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        @if($dso <= 30)
                                                            Excellent - Industry best practice
                                                        @elseif($dso <= 45)
                                                            Good - Monitor closely
                                                        @else
                                                            Poor - Improve collection process
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Collection Rate</h6>
                                                    <h4 class="text-{{ $collectionRate >= 90 ? 'success' : ($collectionRate >= 75 ? 'warning' : 'danger') }}">
                                                        {{ number_format($collectionRate, 1) }}%
                                                    </h4>
                                                    <small class="text-muted">of credit sales collected</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Recommendation</h6>
                                                    @if($dso <= 30 && $collectionRate >= 90)
                                                        <span class="badge bg-success">Excellent performance</span>
                                                    @elseif($dso <= 45 && $collectionRate >= 75)
                                                        <span class="badge bg-warning">Good performance</span>
                                                    @else
                                                        <span class="badge bg-danger">Improve collection process</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
