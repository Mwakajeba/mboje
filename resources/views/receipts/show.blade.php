@extends('layouts.main')

@section('title', 'Receipt #' . ($receipt->reference_number ?? $receipt->id))

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Cash Deposits', 'url' => route('cash_deposits.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => optional($receipt->customer)->name ?? 'Customer', 'url' => isset($receipt->customer_id) ? route('cash_deposits.customer_transactions', \Vinkla\Hashids\Facades\Hashids::encode($receipt->customer_id)) : '#', 'icon' => 'bx bx-user'],
            ['label' => 'Receipt', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card radius-10 border-top border-0 border-4 border-primary">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 d-flex align-items-center"><i class="bx bx-receipt text-primary me-2"></i>Receipt Details</h5>
                        <div class="d-flex gap-1">
                            <span class="badge bg-{{ $receipt->approved ? 'success' : 'secondary' }}">{{ $receipt->approved ? 'Approved' : 'Draft' }}</span>
                            @if($receipt->approved)
                                @if($receipt->gl_posted ?? false)
                                    <span class="badge bg-success">GL Posted</span>
                                @else
                                    <span class="badge bg-warning text-dark" title="Receipt is approved but not yet posted to GL. This may be due to a locked period or configuration issue.">
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
                                    <div class="fw-semibold">{{ $receipt->reference_number ?? $receipt->reference }}</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Date</small>
                                    <div class="fw-semibold">{{ optional($receipt->date)->format('M d, Y') }}</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Amount</small>
                                    <div class="fw-bold text-success">TSHS {{ number_format($receipt->amount, 2) }}</div>
                                </div>
                                <div class="mb-0">
                                    <small class="text-muted d-block">Payment Source</small>
                                    <div class="fw-semibold">
                                        @if($receipt->bank_account_id)
                                            <span class="badge bg-info">{{ optional($receipt->bankAccount)->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">Customer Cash Deposit</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Customer</small>
                                    <div class="fw-semibold">{{ optional($receipt->customer)->name ?? $receipt->payee_name ?? 'N/A' }}</div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Recorded By</small>
                                    <div class="fw-semibold">{{ optional($receipt->user)->name ?? 'N/A' }}</div>
                                </div>
                                <div class="mb-0">
                                    <small class="text-muted d-block">Description</small>
                                    <div class="fw-normal">{{ $receipt->description ?? 'N/A' }}</div>
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
                            <span>Reference Type</span>
                            <span class="fw-semibold">{{ $receipt->reference_type }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Branch</span>
                            <span class="fw-semibold">{{ optional($receipt->branch)->name ?? '-' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Created</span>
                            <span class="text-muted">{{ optional($receipt->created_at)->format('M d, Y H:i') }}</span>
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


