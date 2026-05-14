@extends('layouts.main')

@section('title', 'Supplier Statement Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
                ['label' => 'Supplier Statement', 'url' => '#', 'icon' => 'bx bx-user-check']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-user-check me-2"></i>Supplier Statement Report
                            </h4>
                            @if($supplierId && $supplier)
                            <div class="btn-group">
                                <a href="{{ route('purchases.reports.supplier-statement.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('purchases.reports.supplier-statement.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                            @endif
                        </div>

                        <!-- Report Importance Information -->
                        <div class="alert alert-info border-0 border-start border-4 border-info mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading mb-2">Why This Report Matters</h6>
                                    <p class="mb-2">The Supplier Statement Report provides a detailed account of all transactions with a specific supplier, essential for:</p>
                                    <ul class="mb-0">
                                        <li><strong>Account Reconciliation:</strong> Verify supplier account balances and reconcile discrepancies between your records and supplier statements</li>
                                        <li><strong>Payment Verification:</strong> Track all invoices, payments, and debit notes to ensure accurate payment processing and avoid duplicate payments</li>
                                        <li><strong>Dispute Resolution:</strong> Quickly identify and resolve billing disputes by providing a complete transaction history with running balances</li>
                                        <li><strong>Cash Flow Management:</strong> Monitor outstanding balances and payment obligations to plan cash flow and optimize payment timing</li>
                                        <li><strong>Supplier Communication:</strong> Share statements with suppliers to confirm account status and resolve any discrepancies efficiently</li>
                                        <li><strong>Audit and Compliance:</strong> Maintain comprehensive records of all supplier transactions for internal and external audits</li>
                                        <li><strong>Financial Reporting:</strong> Accurately report accounts payable balances and supplier liabilities in financial statements</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Error Message Display -->
                        @if(isset($errorMessage))
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Notice:</strong> {{ $errorMessage }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <!-- Filters - Always shown at top -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Supplier</label>
                                <select class="form-select select2-single" name="supplier_id" id="supplier_select">
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}" {{ $supplierId == $sup->id ? 'selected' : '' }}>
                                            {{ $sup->name }} @if($sup->email)({{ $sup->email }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom ?? now()->startOfMonth()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo ?? now()->endOfMonth()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select class="form-select select2-single" name="branch_id" id="branch_select">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ ($branchId == $branch->id || ($branchId == 'all' && $branch->id == 'all')) ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        @if($supplierId && $supplier)
                            <!-- Supplier Information -->
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <div class="card border border-primary">
                                        <div class="card-body">
                                            <h5 class="card-title text-primary">
                                                <i class="bx bx-user me-2"></i>Supplier Information
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Name:</strong> {{ $supplier->name }}</p>
                                                    <p><strong>Email:</strong> {{ $supplier->email ?? 'N/A' }}</p>
                                                    <p><strong>Phone:</strong> {{ $supplier->phone ?? 'N/A' }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Address:</strong> {{ $supplier->address ?? 'N/A' }}</p>
                                                    <p><strong>Payment Terms:</strong> {{ ucfirst($supplier->payment_terms ?? 'N/A') }}</p>
                                                    <p><strong>TIN:</strong> {{ $supplier->tin ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Cards -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-2">
                                    <div class="card border border-primary">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-primary">Opening Balance</h5>
                                            <h3 class="mb-0">{{ number_format($openingBalance, 2) }} TZS</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card border border-success">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-success">Total Invoices</h5>
                                            <h3 class="mb-0">{{ number_format($totalInvoices, 2) }} TZS</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card border border-info">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-info">Total Payments</h5>
                                            <h3 class="mb-0">{{ number_format($totalPayments, 2) }} TZS</h3>
                                        </div>
                                    </div>
                                </div>
                                @if(isset($totalDebitNotes) && $totalDebitNotes > 0)
                                <div class="col-md-2">
                                    <div class="card border border-warning">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-warning">Debit Notes</h5>
                                            <h3 class="mb-0">{{ number_format($totalDebitNotes, 2) }} TZS</h3>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-{{ isset($totalDebitNotes) && $totalDebitNotes > 0 ? '2' : '4' }}">
                                    <div class="card border border-{{ $closingBalance >= 0 ? 'success' : 'danger' }}">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-{{ $closingBalance >= 0 ? 'success' : 'danger' }}">Closing Balance</h5>
                                            <h3 class="mb-0">{{ number_format($closingBalance, 2) }} TZS</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Transaction Details -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Document Type</th>
                                            <th>Reference No</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                            <th class="text-end">Running Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Opening Balance Row -->
                                        @if($openingBalance != 0)
                                        <tr class="table-info">
                                            <td>{{ Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }}</td>
                                            <td>Opening Balance</td>
                                            <td>-</td>
                                            <td class="text-end">{{ $openingBalance >= 0 ? number_format($openingBalance, 2) : '-' }}</td>
                                            <td class="text-end">-</td>
                                            <td class="text-end fw-bold">{{ number_format($openingBalance, 2) }}</td>
                                        </tr>
                                        @endif

                                        @forelse($transactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->date->format('d-M-Y') }}</td>
                                                <td><strong>{{ $transaction->document_type }}</strong></td>
                                                <td>{{ $transaction->reference_no }}</td>
                                                <td class="text-end">
                                                    @if($transaction->debit > 0)
                                                        {{ number_format($transaction->debit, 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($transaction->credit > 0)
                                                        <span class="text-success">{{ number_format($transaction->credit, 2) }}</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-end fw-bold">
                                                    {{ number_format($transaction->running_balance, 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No transactions found for the selected period</td>
                                            </tr>
                                        @endforelse

                                        <!-- Total Row -->
                                        @if($transactions->count() > 0)
                                        <tr class="table-success fw-bold">
                                            <td colspan="3" class="text-end">Total:</td>
                                            <td class="text-end">{{ number_format($totalInvoices, 2) }}</td>
                                            <td class="text-end">{{ number_format($totalPayments + ($totalDebitNotes ?? 0), 2) }}</td>
                                            <td class="text-end">{{ number_format($closingBalance, 2) }}</td>
                                        </tr>
                                        @endif
                                            </td>
                                            <td class="text-end">{{ number_format($closingBalance, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                Please select a supplier, date range, and branch to generate the supplier statement report.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for single select dropdowns
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Handle branch change to update suppliers
    const branchSelect = $('#branch_select');
    const supplierSelect = $('#supplier_select');
    
    if (branchSelect.length && supplierSelect.length) {
        branchSelect.on('change', function() {
            const branchId = $(this).val();
            
            // Clear supplier selection
            supplierSelect.empty().append('<option value="">Select Supplier</option>');
            
            if (branchId && branchId !== 'all') {
                // Fetch suppliers for selected branch
                fetch(`/api/suppliers-by-branch/${branchId}`)
                    .then(response => response.json())
                    .then(suppliers => {
                        suppliers.forEach(supplier => {
                            const option = $('<option></option>')
                                .attr('value', supplier.id)
                                .text(`${supplier.name}${supplier.email ? ' (' + supplier.email + ')' : ''}`);
                            supplierSelect.append(option);
                        });
                        // Trigger Select2 update to refresh the dropdown
                        supplierSelect.trigger('change');
                    })
                    .catch(error => {
                        console.error('Error fetching suppliers:', error);
                    });
            } else {
                // Trigger Select2 update even when branch is cleared
                supplierSelect.trigger('change');
            }
        });
    }
});
</script>
@endpush
@endsection
