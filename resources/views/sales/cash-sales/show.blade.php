@extends('layouts.main')

@section('title', 'Cash Sale Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Cash Sales', 'url' => route('sales.cash-sales.index'), 'icon' => 'bx bx-dollar-circle'],
            ['label' => 'Cash Sale Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Cash Sale Details</h4>
                        <p class="text-muted mb-0">{{ $cashSale->sale_number }}</p>
                    </div>
                    <div class="page-title-right d-flex gap-2">
                        @can('edit cash sales')
                        <a href="{{ route('sales.cash-sales.edit', $cashSale->encoded_id) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i>Edit
                        </a>
                        @endcan
                        <a href="{{ route('sales.cash-sales.print', $cashSale->encoded_id) }}" class="btn btn-danger" target="_blank">
                            <i class="bx bx-printer me-1"></i>Print
                        </a>
                        <a href="{{ route('sales.cash-sales.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            @if($cashSale->notes)
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="bx bx-info-circle me-2"></i>
                    <div>
                        <strong>Origin:</strong> {{ $cashSale->notes }}
                    </div>
                </div>
            </div>
            @endif
            <!-- Left Column - Sale Information -->
            <div class="col-lg-8">
                <!-- Sale Information Card -->
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bx bx-dollar-circle me-2"></i>Sale Information</h5>
                        <span class="badge bg-light text-primary fs-6">{{ $cashSale->sale_number }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Customer</label>
                                    <div class="fw-bold">{{ $cashSale->customer->name }}</div>
                                    <small class="text-muted">{{ $cashSale->customer->phone ?? 'No phone' }}</small>
                                </div>
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Sale Date</label>
                                    <div class="fw-bold">{{ $cashSale->sale_date->format('F d, Y') }}</div>
                                </div>
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Payment Method</label>
                                    <div>
                                        <span class="badge bg-{{ $cashSale->payment_method == 'cash' ? 'success' : ($cashSale->payment_method == 'bank' ? 'primary' : 'info') }} fs-6">
                                            <i class="bx bx-{{ $cashSale->payment_method == 'cash' ? 'money' : ($cashSale->payment_method == 'bank' ? 'bank' : 'wallet') }} me-1"></i>
                                            {{ $cashSale->payment_method_text }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if($cashSale->bankAccount)
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Bank Account</label>
                                    <div class="fw-bold">{{ $cashSale->bankAccount->name }}</div>
                                    <small class="text-muted">{{ $cashSale->bankAccount->account_number }}</small>
                                </div>
                                @endif
                                @if($cashSale->cashDeposit)
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Customer Account</label>
                                    <div class="fw-bold">{{ $cashSale->cashDeposit->type->name ?? 'Customer Balance' }}</div>
                                </div>
                                @endif
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Status</label>
                                    <div>
                                        <span class="badge bg-success fs-6">
                                            <i class="bx bx-check-circle me-1"></i>Paid
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table Card -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-package me-2"></i>Items</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Item</th>
                                        <th class="border-0 text-center">Qty</th>
                                        <th class="border-0 text-end">Unit Price</th>
                                        <th class="border-0 text-end">VAT</th>
                                        <th class="border-0 text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cashSale->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $item->item_name }}</div>
                                            <small class="text-muted">{{ $item->item_code }}</small>
                                            @if($item->notes)
                                            <br><small class="text-info">{{ $item->notes }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-end">TZS {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">
                                            @if($item->vat_type == 'no_vat')
                                                <span class="badge bg-secondary">No VAT</span>
                                            @else
                                                TZS {{ number_format($item->vat_amount, 2) }}
                                                <br><small class="text-muted">{{ $item->vat_rate }}%</small>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">TZS {{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @if($cashSale->discount_amount > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold text-danger">
                                            <i class="bx bx-minus-circle me-1"></i>Total Discount:
                                        </td>
                                        <td class="text-end fw-bold text-danger">
                                            -TZS {{ number_format($cashSale->discount_amount, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                <!-- GL Double Entry Card -->
                <div class="card mt-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bx bx-book-open me-2"></i>General Ledger Double Entry</h6>
                    </div>
                    <div class="card-body">
                        @if($cashSale->glTransactions->count() > 0)
                            @php
                                $debitTotal = $cashSale->glTransactions->where('nature', 'debit')->sum('amount');
                                $creditTotal = $cashSale->glTransactions->where('nature', 'credit')->sum('amount');
                                $balance = $debitTotal - $creditTotal;
                            @endphp
                            
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40%">Account</th>
                                            <th width="20%" class="text-center">Type</th>
                                            <th width="20%" class="text-end">Debit</th>
                                            <th width="20%" class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cashSale->glTransactions as $transaction)
                                            <tr>
                                                <td>
                                                    @if($transaction->chartAccount)
                                                        <div class="fw-bold">{{ $transaction->chartAccount->account_name }}</div>
                                                        <small class="text-muted">{{ $transaction->chartAccount->account_code }}</small>
                                                    @else
                                                        <div class="fw-bold text-warning">Account Not Found</div>
                                                        <small class="text-muted">ID: {{ $transaction->chart_account_id }}</small>
                                                    @endif
                                                    @if($transaction->description)
                                                        <br><small class="text-info">{{ $transaction->description }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $transaction->nature == 'debit' ? 'danger' : 'success' }} fs-6">
                                                        {{ ucfirst($transaction->nature) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($transaction->nature == 'debit')
                                                        <span class="fw-bold text-danger">TZS {{ number_format($transaction->amount, 2) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($transaction->nature == 'credit')
                                                        <span class="fw-bold text-success">TZS {{ number_format($transaction->amount, 2) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">Totals:</th>
                                            <th class="text-end text-danger">TZS {{ number_format($debitTotal, 2) }}</th>
                                            <th class="text-end text-success">TZS {{ number_format($creditTotal, 2) }}</th>
                                        </tr>
                                        <tr>
                                            <th colspan="2" class="text-end">Balance:</th>
                                            <th colspan="2" class="text-end">
                                                <span class="badge bg-{{ $balance == 0 ? 'success' : 'danger' }} fs-6">
                                                    {{ $balance == 0 ? 'Balanced' : 'Unbalanced' }}
                                                </span>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Transaction Date: {{ $cashSale->glTransactions->first()->date->format('M d, Y H:i') }}
                                </small>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-book-open text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No GL transactions found for this cash sale.</p>
                                <small class="text-muted">GL transactions are created automatically when the sale is processed.</small>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Notes, Terms & Attachment -->
                @if($cashSale->notes || $cashSale->terms_conditions || $cashSale->attachment)
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-note me-2"></i>Notes & Terms
                            @if($cashSale->attachment)
                                <a href="{{ asset('storage/' . $cashSale->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary float-end">
                                    <i class="bx bx-paperclip me-1"></i>View Attachment
                                </a>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($cashSale->notes)
                        <div class="mb-3">
                            <label class="form-label text-muted small">Notes</label>
                            <p class="mb-0">{{ $cashSale->notes }}</p>
                        </div>
                        @endif
                        @if($cashSale->terms_conditions)
                        <div>
                            <label class="form-label text-muted small">Terms & Conditions</label>
                            <p class="mb-0">{{ $cashSale->terms_conditions }}</p>
                        </div>
                        @endif
                        @if(!$cashSale->notes && !$cashSale->terms_conditions && $cashSale->attachment)
                        <p class="mb-0 text-muted">An attachment has been uploaded for this cash sale.</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Summary & Actions -->
            <div class="col-lg-4">
                <!-- Amount Summary Card -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Amount Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span class="fw-bold">TZS {{ number_format($cashSale->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">VAT Amount:</span>
                            <span class="fw-bold">TZS {{ number_format($cashSale->vat_amount, 2) }}</span>
                        </div>
                        @if($cashSale->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Discount:</span>
                            <span class="fw-bold text-danger">-TZS {{ number_format($cashSale->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        @if($cashSale->withholding_tax_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Withholding Tax:</span>
                            <span class="fw-bold">TZS {{ number_format($cashSale->withholding_tax_amount, 2) }}</span>
                        </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="h6 mb-0">Total Amount:</span>
                            <span class="h5 mb-0 text-success">TZS {{ number_format($cashSale->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted small">Paid Amount:</span>
                            <span class="fw-bold text-success">TZS {{ number_format($cashSale->paid_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Additional Information Card -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Additional Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="info-group mb-3">
                            <label class="form-label text-muted small">Created By</label>
                            <div class="fw-bold">{{ $cashSale->createdBy->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-group mb-3">
                            <label class="form-label text-muted small">Created Date</label>
                            <div class="fw-bold">{{ $cashSale->created_at->format('M d, Y H:i') }}</div>
                        </div>
                        @if($cashSale->updatedBy)
                        <div class="info-group mb-3">
                            <label class="form-label text-muted small">Last Updated</label>
                            <div class="fw-bold">{{ $cashSale->updated_at->format('M d, Y H:i') }}</div>
                        </div>
                        @endif
                        <div class="info-group mb-3">
                            <label class="form-label text-muted small">Branch</label>
                            <div class="fw-bold">{{ $cashSale->branch->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-group">
                            <label class="form-label text-muted small">Company</label>
                            <div class="fw-bold">{{ $cashSale->company->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>



                <!-- Quick Actions Card -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @can('edit cash sales')
                            <a href="{{ route('sales.cash-sales.edit', $cashSale->encoded_id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bx bx-edit me-1"></i>Edit Sale
                            </a>
                            @endcan
                            <a href="{{ route('sales.cash-sales.print', $cashSale->encoded_id) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                                <i class="bx bx-printer me-1"></i>Print Receipt
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .page-title-right, .btn, .card-header .btn {
        display: none !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    .card-header {
        background-color: #f8f9fa !important;
        color: #333 !important;
    }
}
</style>
@endsection 