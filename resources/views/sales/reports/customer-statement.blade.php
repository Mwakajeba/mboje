@extends('layouts.main')

@section('title', 'Customer Statement Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Customer Statement', 'url' => '#', 'icon' => 'bx bx-user-check']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-user-check me-2"></i>Customer Statement Report
                            </h4>
                            @if($customerId && $customer)
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.customer-statement.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.customer-statement.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                            @endif
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
                                <label class="form-label">Customer</label>
                                <select class="form-select select2-single" name="customer_id" id="customer_select">
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $cust)
                                        <option value="{{ $cust->id }}" {{ $customerId == $cust->id ? 'selected' : '' }}>
                                            {{ $cust->name }} @if($cust->email)({{ $cust->email }})@endif
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

                        @if($customerId && $customer)
                            <!-- Customer Information -->
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <div class="card border border-primary">
                                        <div class="card-body">
                                            <h5 class="card-title text-primary">
                                                <i class="bx bx-user me-2"></i>Customer Information
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Name:</strong> {{ $customer->name }}</p>
                                                    <p><strong>Email:</strong> {{ $customer->email }}</p>
                                                    <p><strong>Phone:</strong> {{ $customer->phone ?? 'N/A' }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Address:</strong> {{ $customer->address ?? 'N/A' }}</p>
                                                    <p><strong>Credit Limit:</strong> {{ number_format($customer->credit_limit ?? 0, 2) }} TZS</p>
                                                    <p><strong>Payment Terms:</strong> {{ ucfirst($customer->payment_terms ?? 'N/A') }}</p>
                                                    <p><strong>Available cash deposit balance:</strong> {{ number_format($customer->cash_deposit_balance ?? 0, 2) }} TZS</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Cards (equal-width columns, uniform card height) -->
                            <div class="row row-cols-1 row-cols-sm-2 {{ (isset($totalCreditNotes) && $totalCreditNotes > 0) ? 'row-cols-lg-5' : 'row-cols-lg-4' }} g-3 mb-4">
                                <div class="col d-flex">
                                    <div class="card border border-primary flex-fill w-100">
                                        <div class="card-body text-center py-3 d-flex flex-column justify-content-center">
                                            <h6 class="card-title text-primary mb-2">Opening Balance</h6>
                                            <p class="mb-0 fs-6 fw-semibold text-break">{{ number_format($openingBalance, 2) }} TZS</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col d-flex">
                                    <div class="card border border-success flex-fill w-100">
                                        <div class="card-body text-center py-3 d-flex flex-column justify-content-center">
                                            <h6 class="card-title text-success mb-2">Total Invoices</h6>
                                            <p class="mb-0 fs-6 fw-semibold text-break">{{ number_format($totalInvoices, 2) }} TZS</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col d-flex">
                                    <div class="card border border-info flex-fill w-100">
                                        <div class="card-body text-center py-3 d-flex flex-column justify-content-center">
                                            <h6 class="card-title text-info mb-2">Total Payments</h6>
                                            <p class="mb-0 fs-6 fw-semibold text-break">{{ number_format($totalPayments, 2) }} TZS</p>
                                        </div>
                                    </div>
                                </div>
                                @if(isset($totalCreditNotes) && $totalCreditNotes > 0)
                                <div class="col d-flex">
                                    <div class="card border border-warning flex-fill w-100">
                                        <div class="card-body text-center py-3 d-flex flex-column justify-content-center">
                                            <h6 class="card-title text-warning mb-2">Credit Notes</h6>
                                            <p class="mb-0 fs-6 fw-semibold text-break">{{ number_format($totalCreditNotes, 2) }} TZS</p>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <div class="col d-flex">
                                    <div class="card border border-{{ $closingBalance >= 0 ? 'success' : 'danger' }} flex-fill w-100">
                                        <div class="card-body text-center py-3 d-flex flex-column justify-content-center">
                                            <h6 class="card-title text-{{ $closingBalance >= 0 ? 'success' : 'danger' }} mb-2">Closing Balance</h6>
                                            <p class="mb-0 fs-6 fw-semibold text-break">{{ number_format($closingBalance, 2) }} TZS</p>
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
                                            <th>Description</th>
                                            <th class="text-end">Invoiced</th>
                                            <th class="text-end">Payments</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $runningBalance = $openingBalance; @endphp
                                        
                                        <!-- Opening Balance Row -->
                                        @if($openingBalance != 0)
                                        <tr class="table-info">
                                            <td>{{ $dateFrom }}</td>
                                            <td>Opening Balance</td>
                                            <td class="text-end">{{ $openingBalance >= 0 ? number_format($openingBalance, 2) : '-' }}</td>
                                            <td class="text-end">-</td>
                                            <td class="text-end fw-bold">{{ number_format($runningBalance, 2) }}</td>
                                        </tr>
                                        @endif

                                        @forelse($transactions as $transaction)
                                            @php
                                                if ($transaction->type == 'invoice') {
                                                    $runningBalance += $transaction->amount;
                                                } elseif ($transaction->type == 'payment') {
                                                    $runningBalance -= $transaction->amount;
                                                } elseif ($transaction->type == 'credit_note') {
                                                    $runningBalance -= $transaction->amount;
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $transaction->date->format('Y-m-d') }}</td>
                                                <td>
                                                    @if($transaction->type == 'invoice')
                                                        Invoice#{{ $transaction->reference_id }} --- {{ $transaction->description }}
                                                    @elseif($transaction->type == 'payment')
                                                        @if(!empty($transaction->payment_source) && $transaction->payment_source === 'journal_cash_deposit')
                                                            Journal#{{ $transaction->reference_id }} — {{ $transaction->description }}
                                                        @else
                                                            Receipt#{{ $transaction->reference_id }} — {{ $transaction->description }}
                                                        @endif
                                                    @elseif($transaction->type == 'credit_note')
                                                        Credit Note#{{ $transaction->reference_id }} --- {{ $transaction->description }}
                                                    @else
                                                        {{ $transaction->description }}
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($transaction->type == 'invoice')
                                                        {{ number_format($transaction->amount, 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($transaction->type == 'payment')
                                                        {{ number_format($transaction->amount, 2) }}
                                                    @elseif($transaction->type == 'credit_note')
                                                        <span class="text-warning">{{ number_format($transaction->amount, 2) }}</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-end fw-bold">
                                                    {{ number_format($runningBalance, 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No transactions found for the selected period</td>
                                            </tr>
                                        @endforelse

                                        <!-- Total Row -->
                                        <tr class="table-success fw-bold">
                                            <td colspan="2">Total</td>
                                            <td class="text-end">{{ number_format($totalInvoices, 2) }}</td>
                                            <td class="text-end">
                                                {{ number_format($totalPayments, 2) }}
                                                @if(isset($totalCreditNotes) && $totalCreditNotes > 0)
                                                    <br><small class="text-warning">(CN: {{ number_format($totalCreditNotes, 2) }})</small>
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
                                Please select a customer, date range, and branch to generate the customer statement report.
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
    // Initialize Select2 for single select dropdowns (same as sales invoice)
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Handle branch change to update customers
    const branchSelect = $('#branch_select');
    const customerSelect = $('#customer_select');
    
    if (branchSelect.length && customerSelect.length) {
        branchSelect.on('change', function() {
            const branchId = $(this).val();
            
            // Clear customer selection
            customerSelect.empty().append('<option value="">Select Customer</option>');
            
            if (branchId) {
                // Fetch customers for selected branch
                fetch(`/api/customers-by-branch/${branchId}`)
                    .then(response => response.json())
                    .then(customers => {
                        customers.forEach(customer => {
                            const option = $('<option></option>')
                                .attr('value', customer.id)
                                .text(`${customer.name}${customer.email ? ' (' + customer.email + ')' : ''}`);
                            customerSelect.append(option);
                        });
                        // Trigger Select2 update to refresh the dropdown
                        customerSelect.trigger('change');
                    })
                    .catch(error => {
                        console.error('Error fetching customers:', error);
                    });
            } else {
                // Trigger Select2 update even when branch is cleared
                customerSelect.trigger('change');
            }
        });
    }
});
</script>
@endpush
@endsection
