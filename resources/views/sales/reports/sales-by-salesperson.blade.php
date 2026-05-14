@extends('layouts.main')

@section('title', 'Sales by Salesperson Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Sales by Salesperson', 'url' => '#', 'icon' => 'bx bx-user']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-user me-2"></i>Sales by Salesperson Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.sales-by-salesperson.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.sales-by-salesperson.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom instanceof \Carbon\Carbon ? $dateFrom->format('Y-m-d') : $dateFrom }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo instanceof \Carbon\Carbon ? $dateTo->format('Y-m-d') : $dateTo }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select class="form-select" name="branch_id">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ ($branchId == $branch->id || ($branchId == 'all' && $branch->id == 'all')) ? 'selected' : '' }}>
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
                                        <h5 class="card-title text-success">Total Salespeople</h5>
                                        <h3 class="mb-0">{{ number_format($salespersonData->count()) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Invoices</h5>
                                        <h3 class="mb-0">{{ number_format($salespersonData->sum('invoice_count')) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Avg Sales per Person</h5>
                                        <h3 class="mb-0">{{ number_format($salespersonData->avg('total_sales'), 2) }} TZS</h3>
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
                                        <th>Salesperson</th>
                                        <th class="text-end">Total Sales (TZS)</th>
                                        <th class="text-end">Amount Collected (TZS)</th>
                                        <th class="text-end">Outstanding Amount (TZS)</th>
                                        <th class="text-end">Collection %</th>
                                        <th class="text-end">Invoice Count</th>
                                        <th class="text-end">Customers Served</th>
                                        <th class="text-end">Contribution %</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($salespersonData as $index => $salesperson)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $salesperson->createdBy->name ?? 'Unknown User' }}</div>
                                                <small class="text-muted">{{ $salesperson->createdBy->email ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-end">{{ number_format($salesperson->total_sales, 2) }}</td>
                                            <td class="text-end">
                                                <span class="text-success fw-bold">{{ number_format($salesperson->amount_collected, 2) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-danger fw-bold">{{ number_format($salesperson->outstanding_amount, 2) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $salesperson->collection_percentage >= 90 ? 'success' : ($salesperson->collection_percentage >= 70 ? 'warning' : 'danger') }}">
                                                    {{ number_format($salesperson->collection_percentage, 1) }}%
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewInvoices({{ $salesperson->created_by }}, '{{ $salesperson->createdBy->name ?? 'Unknown User' }}', {{ $salesperson->invoice_count }})"
                                                        title="View {{ $salesperson->invoice_count }} invoices">
                                                    {{ number_format($salesperson->invoice_count) }}
                                                </button>
                                            </td>
                                            <td class="text-end">{{ number_format($salesperson->customers_served) }}</td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $salesperson->contribution_percentage >= 30 ? 'success' : ($salesperson->contribution_percentage >= 15 ? 'warning' : 'info') }}">
                                                    {{ number_format($salesperson->contribution_percentage, 1) }}%
                                                </span>
                                            </td>
                                            <td>
                                                @if($salesperson->contribution_percentage >= 30)
                                                    <span class="badge bg-success">Top Performer</span>
                                                @elseif($salesperson->contribution_percentage >= 15)
                                                    <span class="badge bg-warning">Good Performer</span>
                                                @else
                                                    <span class="badge bg-info">Average Performer</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No sales data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th class="text-end">{{ number_format($totalSales, 2) }}</th>
                                        <th class="text-end">
                                            <span class="text-success fw-bold">{{ number_format($totalCollected, 2) }}</span>
                                        </th>
                                        <th class="text-end">
                                            <span class="text-danger fw-bold">{{ number_format($totalOutstanding, 2) }}</span>
                                        </th>
                                        <th class="text-end">
                                            @php
                                                $overallCollectionPercentage = $totalSales > 0 ? ($totalCollected / $totalSales) * 100 : 0;
                                            @endphp
                                            <span class="badge bg-{{ $overallCollectionPercentage >= 90 ? 'success' : ($overallCollectionPercentage >= 70 ? 'warning' : 'danger') }}">
                                                {{ number_format($overallCollectionPercentage, 1) }}%
                                            </span>
                                        </th>
                                        <th class="text-end">{{ number_format($salespersonData->sum('invoice_count')) }}</th>
                                        <th class="text-end">{{ number_format($salespersonData->sum('customers_served')) }}</th>
                                        <th class="text-end">100.0%</th>
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

<!-- Invoices Modal -->
<div class="modal fade" id="invoicesModal" tabindex="-1" aria-labelledby="invoicesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoicesModalLabel">Invoices by Salesperson</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="invoicesContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function viewInvoices(salespersonId, salespersonName, invoiceCount) {
    // Update modal title
    document.getElementById('invoicesModalLabel').textContent = `Invoices by ${salespersonName} (${invoiceCount} invoices)`;
    
    // Show loading spinner
    document.getElementById('invoicesContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('invoicesModal'));
    modal.show();
    
    // Fetch invoices data
    const dateFrom = '{{ $dateFrom instanceof \Carbon\Carbon ? $dateFrom->format('Y-m-d') : $dateFrom }}';
    const dateTo = '{{ $dateTo instanceof \Carbon\Carbon ? $dateTo->format('Y-m-d') : $dateTo }}';
    const branchId = '{{ $branchId }}';
    
    const url = `/api/salesperson-invoices/${salespersonId}?date_from=${dateFrom}&date_to=${dateTo}&branch_id=${branchId}`;
    console.log('Fetching URL:', url);
    console.log('Parameters:', { dateFrom, dateTo, branchId });
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                displayInvoices(data.invoices);
            } else {
                document.getElementById('invoicesContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bx bx-error me-2"></i>Error loading invoices: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            document.getElementById('invoicesContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bx bx-error me-2"></i>Error loading invoices: ${error.message}
                </div>
            `;
        });
}

function displayInvoices(invoices) {
    if (invoices.length === 0) {
        document.getElementById('invoicesContent').innerHTML = `
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-2"></i>No invoices found for this salesperson in the selected period.
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Type</th>
                        <th>Number</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Outstanding</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    invoices.forEach(invoice => {
        const statusBadge = getStatusBadge(invoice.status);
        const typeBadge = invoice.type === 'invoice' ? '<span class="badge bg-primary">Invoice</span>' :
                          (invoice.type === 'pos' ? '<span class="badge bg-success">POS</span>' :
                          '<span class="badge bg-info">Cash Sale</span>');
        const totalAmount = parseFloat(invoice.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
        const paidAmount = parseFloat(invoice.paid_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
        const outstandingAmount = parseFloat(invoice.balance_due || 0).toLocaleString('en-US', {minimumFractionDigits: 2});
        const invoiceDate = new Date(invoice.date || invoice.invoice_date).toLocaleDateString('en-GB');
        
        html += `
            <tr>
                <td>${typeBadge}</td>
                <td><strong>${invoice.number || invoice.invoice_number}</strong></td>
                <td>${invoice.customer_name || 'N/A'}</td>
                <td>${invoiceDate}</td>
                <td>${statusBadge}</td>
                <td class="text-end">${totalAmount} TZS</td>
                <td class="text-end">${paidAmount} TZS</td>
                <td class="text-end">${outstandingAmount} TZS</td>
                <td>
                    ${invoice.type === 'invoice' ? `<a href="/sales/invoices/${invoice.encoded_id}" class="btn btn-sm btn-outline-primary" target="_blank">` :
                    (invoice.type === 'pos' ? `<a href="/sales/pos/${invoice.encoded_id}" class="btn btn-sm btn-outline-primary" target="_blank">` :
                    `<a href="/sales/cash-sales/${invoice.encoded_id}" class="btn btn-sm btn-outline-primary" target="_blank">`)}
                        <i class="bx bx-show me-1"></i>View
                    </a>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    document.getElementById('invoicesContent').innerHTML = html;
}

function getStatusBadge(status) {
    const statusMap = {
        'draft': '<span class="badge bg-secondary">Draft</span>',
        'pending': '<span class="badge bg-warning">Pending</span>',
        'approved': '<span class="badge bg-success">Approved</span>',
        'paid': '<span class="badge bg-success">Paid</span>',
        'partially_paid': '<span class="badge bg-info">Partially Paid</span>',
        'overdue': '<span class="badge bg-danger">Overdue</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>'
    };
    
    return statusMap[status] || `<span class="badge bg-secondary">${status}</span>`;
}
</script>
@endsection
