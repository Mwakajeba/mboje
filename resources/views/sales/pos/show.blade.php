@extends('layouts.main')

@section('title', 'POS Sale Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales Management', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'POS Sales List', 'url' => route('sales.pos.list'), 'icon' => 'bx bx-list-ul'],
            ['label' => 'POS Sale Details', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        <!-- Header with Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">POS Sale #{{ $posSale->sale_number }}</h4>
                        <p class="text-muted mb-0">
                            <i class="bx bx-calendar me-1"></i>
                            {{ $posSale->sale_date ? $posSale->sale_date->format('l, F d, Y \a\t h:i A') : 'N/A' }}
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('sales.pos.edit', $posSale->encoded_id) }}" class="btn btn-warning">
                            <i class="bx bx-edit me-1"></i>Edit Sale
                        </a>
                        <a href="{{ route('sales.pos.receipt', $posSale->encoded_id) }}" class="btn btn-primary" target="_blank">
                            <i class="bx bx-printer me-1"></i>Print Receipt
                        </a>
                        <a href="{{ route('sales.pos.list') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Total Amount</h6>
                                <h4 class="mb-0 text-primary">TZS {{ number_format($posSale->total_amount, 2) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-primary bg-opacity-10">
                                    <i class="bx bx-dollar-circle font-size-24 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Items Sold</h6>
                                <h4 class="mb-0 text-success">{{ $posSale->items->count() }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-success bg-opacity-10">
                                    <i class="bx bx-package font-size-24 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">VAT Amount</h6>
                                <h4 class="mb-0 text-info">TZS {{ number_format($posSale->vat_amount, 2) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-info bg-opacity-10">
                                    <i class="bx bx-receipt font-size-24 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Discount</h6>
                                <h4 class="mb-0 text-warning">TZS {{ number_format($posSale->discount_amount, 2) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-warning bg-opacity-10">
                                    <i class="bx bx-discount font-size-24 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sale Information -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-shopping-cart me-2"></i>Sale Items
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">VAT</th>
                                        <th>Expiry Date</th>
                                        <th class="text-end">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($posSale->items as $item)
                                    <tr>
                                        <td>
                                            <div>
                                                <h6 class="mb-1">{{ $item->inventoryItem->name ?? 'N/A' }}</h6>
                                                <small class="text-muted">{{ $item->inventoryItem->code ?? 'N/A' }}</small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $item->quantity }}</span>
                                        </td>
                                        <td class="text-end">TZS {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">TZS {{ number_format($item->vat_amount, 2) }}</td>
                                        <td>
                                            @if($item->expiry_date)
                                                <span class="badge bg-info">
                                                    <i class="bx bx-calendar me-1"></i>{{ $item->expiry_date->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">TZS {{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals Summary -->
                        <div class="row mt-4">
                            <div class="col-md-6 offset-md-6">
                                <div class="card border-light">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">Payment Summary</h6>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Subtotal:</span>
                                            <span>TZS {{ number_format($posSale->subtotal, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">VAT ({{ $posSale->vat_rate ?? 0 }}%):</span>
                                            <span>TZS {{ number_format($posSale->vat_amount, 2) }}</span>
                                        </div>
                                        @if($posSale->discount_amount > 0)
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Discount:</span>
                                            <span class="text-danger">-TZS {{ number_format($posSale->discount_amount, 2) }}</span>
                                        </div>
                                        @endif
                                        @if($posSale->withholding_tax_amount > 0)
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Withholding Tax:</span>
                                            <span>TZS {{ number_format($posSale->withholding_tax_amount, 2) }}</span>
                                        </div>
                                        @endif
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">Total Amount:</span>
                                            <span class="fw-bold text-primary fs-5">TZS {{ number_format($posSale->total_amount, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Information -->
            <div class="col-lg-4">
                <!-- Sale Details -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-info-circle me-2"></i>Sale Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Sale Number</label>
                            <p class="mb-0 fw-bold">{{ $posSale->sale_number }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Sale Date</label>
                            <p class="mb-0">{{ $posSale->sale_date ? $posSale->sale_date->format('M d, Y h:i A') : 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Customer</label>
                            <p class="mb-0">
                                @if($posSale->customer)
                                    <span class="badge bg-success">{{ $posSale->customer->name }}</span>
                                @else
                                    <span class="badge bg-secondary">Walk-in Customer</span>
                                @endif
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Operator</label>
                            <p class="mb-0">{{ $posSale->operator->name ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Branch</label>
                            <p class="mb-0">{{ $posSale->branch->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-credit-card me-2"></i>Payment Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Payment Method</label>
                            <p class="mb-0">
                                <span class="badge bg-{{ $posSale->payment_method == 'cash' ? 'success' : ($posSale->payment_method == 'card' ? 'info' : ($posSale->payment_method == 'bank' ? 'primary' : 'warning')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $posSale->payment_method)) }}
                                </span>
                            </p>
                        </div>
                        @if($posSale->bank_account)
                        <div class="mb-3">
                            <label class="form-label text-muted small">Bank Account</label>
                            <p class="mb-0">{{ $posSale->bank_account->name ?? 'N/A' }}</p>
                        </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label text-muted small">Amount Paid</label>
                            <p class="mb-0 fw-bold text-success">TZS {{ number_format($posSale->total_amount, 2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                @if($posSale->notes)
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-note me-2"></i>Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $posSale->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- GL Transactions -->
        @if($posSale->glTransactions && $posSale->glTransactions->count() > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-book me-2"></i>General Ledger Transactions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th class="text-end">Debit (TZS)</th>
                                        <th class="text-end">Credit (TZS)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalDebit = 0;
                                        $totalCredit = 0;
                                    @endphp
                                    @foreach($posSale->glTransactions as $transaction)
                                    @php
                                        if ($transaction->nature === 'debit') {
                                            $totalDebit += $transaction->amount;
                                        } else {
                                            $totalCredit += $transaction->amount;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $transaction->date ? $transaction->date->format('d M Y') : 'N/A' }}</td>
                                        <td>
                                            @if($transaction->chartAccount)
                                                <strong>{{ $transaction->chartAccount->account_code }}</strong><br>
                                                <small class="text-muted">{{ $transaction->chartAccount->account_name }}</small>
                                            @else
                                                <span class="text-warning">Account Not Found</span><br>
                                                <small class="text-muted">ID: {{ $transaction->chart_account_id }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->description ?? 'N/A' }}</td>
                                        <td class="text-end">
                                            @if($transaction->nature === 'debit')
                                                <span class="text-danger fw-bold">TZS {{ number_format($transaction->amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($transaction->nature === 'credit')
                                                <span class="text-success fw-bold">TZS {{ number_format($transaction->amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end text-danger">TZS {{ number_format($totalDebit, 2) }}</th>
                                        <th class="text-end text-success">TZS {{ number_format($totalCredit, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.font-size-24 {
    font-size: 24px;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
}

.fs-5 {
    font-size: 1.25rem !important;
}
</style>
@endpush 