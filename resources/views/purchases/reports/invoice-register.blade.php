@extends('layouts.main')

@section('title','Supplier Invoice Register')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Invoice Register', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />
        
        <h6 class="mb-0 text-uppercase">SUPPLIER INVOICE REGISTER</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-receipt me-2"></i>Supplier Invoice Register Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('purchases.reports.invoice-register.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('purchases.reports.invoice-register.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Report Importance Information -->
                        <div class="alert alert-info border-0 border-start border-4 border-info mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading mb-2">Why This Report Matters</h6>
                                    <p class="mb-2">The Supplier Invoice Register provides a comprehensive view of all accounts payable invoices, enabling you to:</p>
                                    <ul class="mb-0">
                                        <li><strong>Accounts Payable Management:</strong> Track all supplier invoices, their payment status, and outstanding amounts to manage cash flow effectively</li>
                                        <li><strong>Payment Planning:</strong> Identify due dates and outstanding balances to prioritize payments and avoid late payment penalties</li>
                                        <li><strong>Financial Control:</strong> Monitor total payables, paid amounts, and outstanding balances for accurate financial reporting</li>
                                        <li><strong>Supplier Relationship Management:</strong> Analyze payment patterns and maintain good supplier relationships through timely payments</li>
                                        <li><strong>Budget Compliance:</strong> Compare actual expenses against budgets and identify cost trends for better financial planning</li>
                                        <li><strong>Audit Trail:</strong> Maintain complete records of all supplier invoices for internal audits and compliance requirements</li>
                                        <li><strong>Tax Compliance:</strong> Track VAT amounts and ensure proper tax reporting and documentation</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom instanceof \Carbon\Carbon ? $dateFrom->format('Y-m-d') : $dateFrom }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo instanceof \Carbon\Carbon ? $dateTo->format('Y-m-d') : $dateTo }}">
                            </div>
                            <div class="col-md-2">
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
                                <label class="form-label">Supplier</label>
                                <select class="form-select select2-single" name="supplier_id">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="draft" {{ $status == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="posted" {{ $status == 'posted' ? 'selected' : '' }}>Posted</option>
                                    <option value="paid" {{ $status == 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
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
                                        <h5 class="card-title text-primary">Total Invoices</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_invoices']) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Value</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_value'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Paid</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_paid'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Outstanding</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_outstanding'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Summary -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border border-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-secondary">Subtotal</h5>
                                        <h4 class="mb-0">{{ number_format($summary['total_subtotal'], 2) }} TZS</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Total VAT</h5>
                                        <h4 class="mb-0">{{ number_format($summary['total_vat'], 2) }} TZS</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Discount</h5>
                                        <h4 class="mb-0">{{ number_format($summary['total_discount'], 2) }} TZS</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Invoice Date</th>
                                        <th>Supplier</th>
                                        <th>PO No</th>
                                        <th class="text-end">Net Amount</th>
                                        <th class="text-end">Tax Amount</th>
                                        <th class="text-end">Gross Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $invoice)
                                        <tr>
                                            <td>
                                                <a href="{{ route('purchases.purchase-invoices.show', $invoice->encoded_id) }}" class="text-primary fw-bold">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->invoice_date->format('d-M-Y') }}</td>
                                            <td><strong>{{ $invoice->supplier->name ?? 'Unknown Supplier' }}</strong></td>
                                            <td>{{ $invoice->po_number }}</td>
                                            <td class="text-end">{{ number_format($invoice->subtotal, 2) }}</td>
                                            <td class="text-end">{{ number_format($invoice->vat_amount, 2) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td>{{ $invoice->due_date ? $invoice->due_date->format('d-M-Y') : '-' }}</td>
                                            <td>
                                                @switch($invoice->status)
                                                    @case('draft')
                                                        <span class="badge bg-secondary">Draft</span>
                                                        @break
                                                    @case('posted')
                                                        <span class="badge bg-info">Posted</span>
                                                        @break
                                                    @case('paid')
                                                        <span class="badge bg-success">Paid</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-danger">Cancelled</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bx bx-info-circle font-size-48 mb-3"></i>
                                                    <h6>No invoices found</h6>
                                                    <p class="mb-0">Try adjusting your filters to see more results.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($invoices->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end fw-bold">TOTALS:</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_subtotal'], 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_vat'], 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_value'], 2) }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
