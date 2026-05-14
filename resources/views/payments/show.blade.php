@extends('layouts.main')

@section('title', 'Payment #' . $payment->id)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Cash Deposits', 'url' => route('cash_deposits.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => optional($payment->customer)->name ?? 'Customer', 'url' => isset($payment->customer_id) ? route('cash_deposits.customer_transactions', \Vinkla\Hashids\Facades\Hashids::encode($payment->customer_id)) : '#', 'icon' => 'bx bx-user'],
            ['label' => 'Payment', 'url' => '#', 'icon' => 'bx bx-wallet']
        ]" />

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card radius-10 border-top border-0 border-4 border-primary">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 d-flex align-items-center"><i class="bx bx-wallet text-primary me-2"></i>Payment Details</h5>
                        <div class="d-flex gap-1">
                            <span class="badge bg-{{ $payment->approved ? 'success' : 'secondary' }}">{{ $payment->approved ? 'Approved' : 'Draft' }}</span>
                            @if($payment->approved)
                                @if($payment->gl_posted)
                                    <span class="badge bg-success">GL Posted</span>
                                @else
                                    <span class="badge bg-warning text-dark" title="Payment is approved but not yet posted to GL. This may be due to a locked period or configuration issue.">
                                        Not Posted to GL
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Reference</small>
                                    <div class="fw-semibold">{{ $payment->reference }} ({{ $payment->reference_type }})</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Date</small>
                                    <div class="fw-semibold">{{ optional($payment->date)->format('M d, Y') }}</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Amount</small>
                                    <div class="fw-bold text-danger">TSHS {{ number_format($payment->amount, 2) }}</div>
                                </div>
                                <div class="mb-0">
                                    <small class="text-muted d-block">Payment Source</small>
                                    <div class="fw-semibold">
                                        @if($payment->cash_deposit_id)
                                            <span class="badge bg-secondary">Customer Cash Deposit</span>
                                        @else
                                            <span class="badge bg-info">{{ optional($payment->bankAccount)->name ?? 'Customer Balance' }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Customer</small>
                                    <div class="fw-semibold">{{ optional($payment->customer)->name ?? $payment->payee_name ?? 'N/A' }}</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Recorded By</small>
                                    <div class="fw-semibold">{{ optional($payment->user)->name ?? 'N/A' }}</div>
                                </div>
                                <div class="mb-0">
                                    <small class="text-muted d-block">Description</small>
                                    <div class="fw-normal">{{ $payment->description ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card radius-10 h-100">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i>Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Branch</span>
                            <span class="fw-semibold">{{ optional($payment->branch)->name ?? '-' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Created</span>
                            <span class="text-muted">{{ optional($payment->created_at)->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Approved At</span>
                            <span class="text-muted">{{ optional($payment->approved_at)->format('M d, Y H:i') ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Back</a>
        </div>
    </div>
</div>
@endsection


