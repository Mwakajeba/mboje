@extends('layouts.main')

@section('title', 'Tax Invoice Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Tax Invoice Report', 'url' => '#', 'icon' => 'bx bx-receipt']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-receipt me-2"></i>Tax Invoice Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.tax-invoice.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.tax-invoice.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo->format('Y-m-d') }}">
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
                            <div class="col-md-3">
                                <label class="form-label">Tax Type</label>
                                <select class="form-select" name="tax_type" required>
                                    <option value="vat" {{ $taxType == 'vat' ? 'selected' : '' }}>VAT Only</option>
                                    <option value="wht" {{ $taxType == 'wht' ? 'selected' : '' }}>WHT Only</option>
                                    <option value="both" {{ $taxType == 'both' ? 'selected' : '' }}>Both VAT & WHT</option>
                                    <option value="none" {{ $taxType == 'none' ? 'selected' : '' }}>No Tax</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Invoices</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_invoices']) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Taxable Amount</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_taxable_amount'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">VAT Amount</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_vat_amount'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">WHT Amount</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_wht_amount'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tax Analysis -->
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="card border border-info">
                                    <div class="card-body">
                                        <h5 class="card-title text-info">
                                            <i class="bx bx-pie-chart me-2"></i>Tax Analysis
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Tax Rate</h6>
                                                    <h4 class="text-primary">{{ number_format($summary['total_taxable_amount'] > 0 ? ($summary['total_tax_amount'] / $summary['total_taxable_amount']) * 100 : 0, 1) }}%</h4>
                                                    <small class="text-muted">Effective tax rate</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Tax Efficiency</h6>
                                                    <h4 class="text-success">{{ number_format($summary['total_invoices'] > 0 ? ($summary['total_tax_amount'] / $summary['total_invoices']) : 0, 2) }} TZS</h4>
                                                    <small class="text-muted">Tax per invoice</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Compliance</h6>
                                                    <h4 class="text-info">{{ number_format($summary['total_invoices']) }}</h4>
                                                    <small class="text-muted">Tax invoices issued</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Status</h6>
                                                    <span class="badge bg-success">Compliant</span>
                                                    <small class="text-muted d-block">All invoices taxed</small>
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
                                        <th>Taxpayer ID</th>
                                        <th>Invoice Date</th>
                                        <th class="text-end">Taxable Amount (TZS)</th>
                                        <th>Tax Type</th>
                                        <th class="text-end">Tax Rate (%)</th>
                                        <th class="text-end">Tax Amount (TZS)</th>
                                        <th class="text-end">Total Amount (TZS)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($taxInvoices as $index => $invoice)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $invoice->invoice_number }}</div>
                                                <small class="text-muted">{{ $invoice->reference_number ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $invoice->customer->name ?? 'Unknown Customer' }}</div>
                                                <small class="text-muted">{{ $invoice->customer->email ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    {{ $invoice->customer->tax_number ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                            <td class="text-end">{{ number_format($invoice->subtotal, 2) }}</td>
                                            <td>
                                                <div class="fw-bold">
                                                    @if($invoice->withholding_tax_amount > 0 && $invoice->vat_amount > 0)
                                                        <span class="badge bg-danger">BOTH</span>
                                                        <small class="text-muted d-block">VAT + WHT</small>
                                                    @elseif($invoice->withholding_tax_amount > 0)
                                                        <span class="badge bg-warning">WHT</span>
                                                        <small class="text-muted d-block">Withholding Tax</small>
                                                    @elseif($invoice->vat_amount > 0)
                                                        <span class="badge bg-primary">VAT</span>
                                                        <small class="text-muted d-block">Value Added Tax</small>
                                                    @else
                                                        <span class="badge bg-secondary">No Tax</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                @if($invoice->withholding_tax_amount > 0 && $invoice->vat_amount > 0)
                                                    <div>
                                                        <span class="badge bg-info">VAT: 18.0%</span><br>
                                                        <span class="badge bg-warning">WHT: {{ number_format($invoice->withholding_tax_rate, 1) }}%</span>
                                                    </div>
                                                @elseif($invoice->withholding_tax_amount > 0)
                                                    <span class="badge bg-warning">
                                                        {{ number_format($invoice->withholding_tax_rate, 1) }}%
                                                    </span>
                                                @elseif($invoice->vat_amount > 0)
                                                    <span class="badge bg-info">18.0%</span>
                                                @else
                                                    <span class="badge bg-secondary">0.0%</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($invoice->withholding_tax_amount > 0 && $invoice->vat_amount > 0)
                                                    <div class="fw-bold">
                                                        <span class="text-primary">VAT: {{ number_format($invoice->vat_amount, 2) }}</span><br>
                                                        <span class="text-warning">WHT: {{ number_format($invoice->withholding_tax_amount, 2) }}</span>
                                                    </div>
                                                @elseif($invoice->withholding_tax_amount > 0)
                                                    <span class="text-warning fw-bold">{{ number_format($invoice->withholding_tax_amount, 2) }}</span>
                                                    <small class="text-muted d-block">WHT Amount</small>
                                                @elseif($invoice->vat_amount > 0)
                                                    <span class="text-primary fw-bold">{{ number_format($invoice->vat_amount, 2) }}</span>
                                                    <small class="text-muted d-block">VAT Amount</small>
                                                @else
                                                    <span class="text-muted">0.00</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="text-primary fw-bold">{{ number_format($invoice->total_amount, 2) }}</span>
                                            </td>
                                            <td>
                                                @if($invoice->status == 'paid')
                                                    <span class="badge bg-success">
                                                        <i class="bx bx-check-circle me-1"></i>Paid
                                                    </span>
                                                @elseif($invoice->status == 'pending')
                                                    <span class="badge bg-warning">
                                                        <i class="bx bx-time me-1"></i>Pending
                                                    </span>
                                                @elseif($invoice->status == 'overdue')
                                                    <span class="badge bg-danger">
                                                        <i class="bx bx-x-circle me-1"></i>Overdue
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="bx bx-info-circle me-1"></i>{{ ucfirst($invoice->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No tax invoices found for the selected criteria</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5">Total</th>
                                        <th class="text-end">{{ number_format($taxInvoices->sum('subtotal'), 2) }}</th>
                                        <th>-</th>
                                        <th>-</th>
                                        <th class="text-end">
                                            <div class="fw-bold">
                                                <span class="text-primary">VAT: {{ number_format($taxInvoices->sum('vat_amount'), 2) }}</span><br>
                                                <span class="text-warning">WHT: {{ number_format($taxInvoices->sum('withholding_tax_amount'), 2) }}</span>
                                            </div>
                                        </th>
                                        <th class="text-end">{{ number_format($taxInvoices->sum('total_amount'), 2) }}</th>
                                        <th>-</th>
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
