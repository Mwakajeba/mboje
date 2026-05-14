@extends('layouts.main')

@section('title','Outstanding Supplier Invoices')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Outstanding Invoices', 'url' => '#', 'icon' => 'bx bx-hourglass']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-hourglass me-2"></i>Outstanding Supplier Invoices
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-select">
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id', $branchId) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>All</option>
                                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                                </select>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary"><i class="bx bx-filter me-1"></i>Filter</button>
                                <a href="{{ route('purchases.reports.outstanding-invoices') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <!-- Summary Stats -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Invoices</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_invoices'], 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Invoice Amount</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_invoice_amount'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Paid Amount</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_paid_amount'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Outstanding</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_outstanding'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Supplier</th>
                                        <th>Invoice No</th>
                                        <th class="text-end">Invoice Amount</th>
                                        <th class="text-end">Paid Amount</th>
                                        <th class="text-end">Credit Notes</th>
                                        <th class="text-end">Outstanding Balance</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $invoice)
                                    <tr>
                                        <td><strong>{{ $invoice['supplier_name'] }}</strong></td>
                                        <td>{{ $invoice['invoice_no'] }}</td>
                                        <td class="text-end">{{ number_format($invoice['invoice_amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($invoice['paid_amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($invoice['credit_notes'], 2) }}</td>
                                        <td class="text-end fw-bold text-danger">{{ number_format($invoice['outstanding_balance'], 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($invoice['due_date'])->format('d-M-Y') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No outstanding invoices found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                @if(count($invoices) > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold">{{ number_format($summary['total_invoice_amount'], 2) }} TZS</td>
                                        <td class="text-end fw-bold">{{ number_format($summary['total_paid_amount'], 2) }} TZS</td>
                                        <td class="text-end fw-bold">{{ number_format($summary['total_credit_notes'], 2) }} TZS</td>
                                        <td class="text-end fw-bold text-danger">{{ number_format($summary['total_outstanding'], 2) }} TZS</td>
                                        <td></td>
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
