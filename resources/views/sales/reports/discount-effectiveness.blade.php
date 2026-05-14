@extends('layouts.main')

@section('title', 'Discount Effectiveness Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Discount Effectiveness', 'url' => '#', 'icon' => 'bx bx-discount']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-discount me-2"></i>Discount Effectiveness Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.discount-effectiveness.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.discount-effectiveness.export.excel', request()->query()) }}" 
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
                                        <h5 class="card-title text-primary">Total Sales</h5>
                                        <h3 class="mb-0">{{ number_format($totalSales, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Discounts</h5>
                                        <h3 class="mb-0">{{ number_format($totalDiscounts, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Discount %</h5>
                                        <h3 class="mb-0">{{ number_format($discountPercentage, 2) }}%</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Invoices with Discounts</h5>
                                        <h3 class="mb-0">{{ number_format($invoicesWithDiscounts) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Discount Analysis -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Average Discount per Invoice</h5>
                                        <h3 class="mb-0">{{ number_format($averageDiscountPerInvoice, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Average Discount Rate</h5>
                                        <h3 class="mb-0">{{ number_format($averageDiscountRate, 2) }}%</h3>
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
                                        <th class="text-end">Gross Sales (TZS)</th>
                                        <th class="text-end">Discount Amount (TZS)</th>
                                        <th class="text-end">Discount %</th>
                                        <th class="text-end">Net Sales (TZS)</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($discountData as $index => $invoice)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <a href="{{ route('sales.invoices.show', $invoice->id) }}" class="text-primary">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->customer->name ?? 'Unknown Customer' }}</td>
                                            <td class="text-end">{{ number_format($invoice->gross_sales, 2) }}</td>
                                            <td class="text-end">
                                                <span class="text-danger fw-bold">{{ number_format($invoice->discount_amount, 2) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $invoice->discount_percentage >= 20 ? 'danger' : ($invoice->discount_percentage >= 10 ? 'warning' : 'info') }}">
                                                    {{ number_format($invoice->discount_percentage, 1) }}%
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($invoice->net_sales, 2) }}</td>
                                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No discount data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3">Total</th>
                                        <th class="text-end">{{ number_format($totalSales, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalDiscounts, 2) }}</th>
                                        <th class="text-end">{{ number_format($discountPercentage, 2) }}%</th>
                                        <th class="text-end">{{ number_format($totalSales - $totalDiscounts, 2) }}</th>
                                        <th>-</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Discount Effectiveness Analysis -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border border-info">
                                    <div class="card-body">
                                        <h5 class="card-title text-info">
                                            <i class="bx bx-info-circle me-2"></i>Discount Effectiveness Analysis
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Discount Impact</h6>
                                                    <div class="progress mb-2" style="height: 20px;">
                                                        <div class="progress-bar bg-{{ $discountPercentage <= 5 ? 'success' : ($discountPercentage <= 15 ? 'warning' : 'danger') }}" 
                                                             role="progressbar" 
                                                             style="width: {{ min($discountPercentage, 30) }}%" 
                                                             aria-valuenow="{{ $discountPercentage }}" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="30">
                                                            {{ number_format($discountPercentage, 1) }}%
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        @if($discountPercentage <= 5)
                                                            Low impact - Good margin control
                                                        @elseif($discountPercentage <= 15)
                                                            Moderate impact - Monitor closely
                                                        @else
                                                            High impact - Review discount policy
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Discount Frequency</h6>
                                                    <h4 class="text-{{ $invoicesWithDiscounts >= 50 ? 'success' : ($invoicesWithDiscounts >= 25 ? 'warning' : 'danger') }}">
                                                        {{ number_format(($invoicesWithDiscounts / max($totalInvoices, 1)) * 100, 1) }}%
                                                    </h4>
                                                    <small class="text-muted">of invoices have discounts</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Recommendation</h6>
                                                    @if($discountPercentage <= 5)
                                                        <span class="badge bg-success">Continue current policy</span>
                                                    @elseif($discountPercentage <= 15)
                                                        <span class="badge bg-warning">Review discount limits</span>
                                                    @else
                                                        <span class="badge bg-danger">Implement discount controls</span>
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
