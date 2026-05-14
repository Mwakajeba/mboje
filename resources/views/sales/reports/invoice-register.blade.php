@extends('layouts.main')

@section('title', 'Invoice Register Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Invoice Register', 'url' => '#', 'icon' => 'bx bx-receipt']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-receipt me-2"></i>Invoice Register Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.invoice-register.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.invoice-register.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="draft" {{ $status == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="sent" {{ $status == 'sent' ? 'selected' : '' }}>Sent</option>
                                    <option value="paid" {{ $status == 'paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="overdue" {{ $status == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                                        <h5 class="card-title text-info">Paid Value</h5>
                                        <h3 class="mb-0">{{ number_format($summary['paid_value'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Outstanding</h5>
                                        <h3 class="mb-0">{{ number_format($summary['outstanding_value'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Breakdown -->
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="card border border-info">
                                    <div class="card-body">
                                        <h5 class="card-title text-info">
                                            <i class="bx bx-pie-chart me-2"></i>Invoice Status Breakdown
                                        </h5>
                                        <div class="row">
                                            @php
                                                $statusCounts = $invoices->groupBy('status')->map->count();
                                                $totalInvoices = $invoices->count();
                                            @endphp
                                            @foreach(['draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'] as $statusKey => $statusLabel)
                                                <div class="col-md-2">
                                                    <div class="text-center">
                                                        <h6 class="text-muted">{{ $statusLabel }}</h6>
                                                        <h4 class="text-{{ $statusKey == 'paid' ? 'success' : ($statusKey == 'overdue' ? 'danger' : ($statusKey == 'cancelled' ? 'secondary' : 'info')) }}">
                                                            {{ number_format($statusCounts[$statusKey] ?? 0) }}
                                                        </h4>
                                                        <small class="text-muted">
                                                            {{ $totalInvoices > 0 ? number_format((($statusCounts[$statusKey] ?? 0) / $totalInvoices) * 100, 1) : 0 }}%
                                                        </small>
                                                    </div>
                                                </div>
                                            @endforeach
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
                                        <th class="text-end">Gross Amount (TZS)</th>
                                        <th class="text-end">VAT Amount (TZS)</th>
                                        <th class="text-end">Discount (TZS)</th>
                                        <th class="text-end">Net Amount (TZS)</th>
                                        <th class="text-end">Paid Amount (TZS)</th>
                                        <th class="text-end">Balance (TZS)</th>
                                        <th>Status</th>
                                        <th>Payment Terms</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $index => $invoice)
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
                                            <td class="text-end">{{ number_format($invoice->subtotal + $invoice->vat_amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($invoice->vat_amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($invoice->discount_amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td class="text-end">
                                                <span class="text-success fw-bold">{{ number_format($invoice->paid_amount, 2) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold {{ $invoice->balance_due > 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ number_format($invoice->balance_due, 2) }}
                                                </span>
                                            </td>
                                            <td>
                                                @switch($invoice->status)
                                                    @case('draft')
                                                        <span class="badge bg-secondary">Draft</span>
                                                        @break
                                                    @case('sent')
                                                        <span class="badge bg-info">Sent</span>
                                                        @break
                                                    @case('paid')
                                                        <span class="badge bg-success">Paid</span>
                                                        @break
                                                    @case('overdue')
                                                        <span class="badge bg-danger">Overdue</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-dark">Cancelled</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-light text-dark">{{ ucfirst($invoice->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    {{ ucfirst(str_replace('_', ' ', $invoice->payment_terms)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="13" class="text-center text-muted">No invoices found for the selected criteria</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5">Total</th>
                                        <th class="text-end">{{ number_format($invoices->sum(function($i) { return $i->subtotal + $i->vat_amount; }), 2) }}</th>
                                        <th class="text-end">{{ number_format($invoices->sum('vat_amount'), 2) }}</th>
                                        <th class="text-end">{{ number_format($invoices->sum('discount_amount'), 2) }}</th>
                                        <th class="text-end">{{ number_format($invoices->sum('total_amount'), 2) }}</th>
                                        <th class="text-end">{{ number_format($invoices->sum('paid_amount'), 2) }}</th>
                                        <th class="text-end">{{ number_format($invoices->sum('balance_due'), 2) }}</th>
                                        <th colspan="2">-</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Export Options -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border border-success">
                                    <div class="card-body">
                                        <h5 class="card-title text-success">
                                            <i class="bx bx-download me-2"></i>Export Options
                                        </h5>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-primary" onclick="exportToExcel()">
                                                <i class="bx bx-file me-1"></i>Export to Excel
                                            </button>
                                            <button class="btn btn-outline-success" onclick="exportToPDF()">
                                                <i class="bx bx-file-pdf me-1"></i>Export to PDF
                                            </button>
                                            <button class="btn btn-outline-info" onclick="printReport()">
                                                <i class="bx bx-printer me-1"></i>Print Report
                                            </button>
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

<script nonce="{{ $cspNonce ?? '' }}">
function exportToExcel() {
    // Implementation for Excel export
    alert('Excel export functionality will be implemented');
}

function exportToPDF() {
    // Implementation for PDF export
    alert('PDF export functionality will be implemented');
}

function printReport() {
    window.print();
}
</script>
@endsection
