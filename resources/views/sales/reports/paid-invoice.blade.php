@extends('layouts.main')

@section('title', 'Paid Invoice Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Paid Invoice', 'url' => '#', 'icon' => 'bx bx-check-circle']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-check-circle me-2"></i>Payment Received Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.paid-invoice.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.paid-invoice.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom instanceof \Carbon\Carbon ? $dateFrom->format('Y-m-d') : \Carbon\Carbon::parse($dateFrom)->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo instanceof \Carbon\Carbon ? $dateTo->format('Y-m-d') : \Carbon\Carbon::parse($dateTo)->format('Y-m-d') }}">
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
                            <div class="col-md-2">
                                <label class="form-label">Customer</label>
                                <select class="form-select" name="customer_id">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ ($customerId ?? '') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
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
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Fully Paid Invoices</h5>
                                        <h3 class="mb-0">{{ number_format($summary['fully_paid_count']) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Partially Paid</h5>
                                        <h3 class="mb-0">{{ number_format($summary['partially_paid_count']) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Received</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_paid_value'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Collection Rate</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_invoice_value'] > 0 ? ($summary['total_paid_value'] / $summary['total_invoice_value']) * 100 : 0, 1) }}%</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Analysis -->
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="card border border-info">
                                    <div class="card-body">
                                        <h5 class="card-title text-info">
                                            <i class="bx bx-pie-chart me-2"></i>Payment Analysis
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Payment Efficiency</h6>
                                                    <h4 class="text-{{ $summary['fully_paid_count'] == $summary['total_paid_invoices'] ? 'success' : 'warning' }}">
                                                        {{ $summary['total_paid_invoices'] > 0 ? number_format(($summary['fully_paid_count'] / $summary['total_paid_invoices']) * 100, 1) : 0 }}%
                                                    </h4>
                                                    <small class="text-muted">Fully paid invoices</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Collection Rate</h6>
                                                    <h4 class="text-primary">{{ number_format($summary['total_invoice_value'] > 0 ? ($summary['total_paid_value'] / $summary['total_invoice_value']) * 100 : 0, 1) }}%</h4>
                                                    <small class="text-muted">Amount collected</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Outstanding Amount</h6>
                                                    <h4 class="text-{{ ($summary['total_invoice_value'] - $summary['total_paid_value']) > 0 ? 'warning' : 'success' }}">
                                                        {{ number_format($summary['total_invoice_value'] - $summary['total_paid_value'], 2) }} TZS
                                                    </h4>
                                                    <small class="text-muted">Still to be collected</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Status</h6>
                                                    @if($summary['partially_paid_count'] == 0)
                                                        <span class="badge bg-success">All Fully Paid</span>
                                                    @elseif($summary['fully_paid_count'] > $summary['partially_paid_count'])
                                                        <span class="badge bg-warning">Mostly Complete</span>
                                                    @else
                                                        <span class="badge bg-info">Mixed Status</span>
                                                    @endif
                                                    <small class="text-muted d-block">{{ $summary['partially_paid_count'] }} partial payments</small>
                                                </div>
                                            </div>
                                        </div>
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
                                        <th>Invoice Date</th>
                                        <th>Due Date</th>
                                        <th class="text-end">Invoice Amount (TZS)</th>
                                        <th class="text-end">Paid Amount (TZS)</th>
                                        <th class="text-end">Outstanding (TZS)</th>
                                        <th>Payment Status</th>
                                        <th>Payment Terms</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($paidInvoices as $index => $invoice)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <a href="{{ route('sales.invoices.show', $invoice->id) }}" class="text-primary fw-bold">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $invoice->customer->name ?? 'Unknown Customer' }}</div>
                                                <small class="text-muted">{{ $invoice->customer->email ?? 'N/A' }}</small>
                                            </td>
                                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                            <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                                            <td class="text-end">{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td class="text-end">
                                                <span class="text-success fw-bold">{{ number_format($invoice->paid_amount, 2) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold {{ $invoice->balance_due > 0 ? 'text-warning' : 'text-success' }}">
                                                    {{ number_format($invoice->balance_due, 2) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($invoice->status == 'paid')
                                                    <span class="badge bg-success">
                                                        <i class="bx bx-check-circle me-1"></i>Fully Paid
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="bx bx-time me-1"></i>Partially Paid
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    {{ ucfirst(str_replace('_', ' ', $invoice->payment_terms)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No paid invoices found for the selected criteria</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5">Total</th>
                                        <th class="text-end">{{ number_format($paidInvoices->sum('total_amount'), 2) }}</th>
                                        <th class="text-end">{{ number_format($paidInvoices->sum('paid_amount'), 2) }}</th>
                                        <th class="text-end">{{ number_format($paidInvoices->sum('balance_due'), 2) }}</th>
                                        <th colspan="2">-</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
