@extends('layouts.main')

@section('title', 'Opening Balance Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Opening Balances', 'url' => route('sales.opening-balances.index'), 'icon' => 'bx bx-book'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Opening Balance Details</h4>
                    <div class="page-title-right d-flex gap-2">
                        @php
                            $isPaid = ($balance->status === 'paid' || (float)($balance->balance_due ?? 0) <= 0);
                            $invoiceHash = $balance->invoice ? Vinkla\Hashids\Facades\Hashids::encode($balance->invoice->id) : null;
                        @endphp
                        @if($invoiceHash)
                            @can('record sales payment')
                            <a href="{{ $isPaid ? '#' : route('sales.invoices.payment-form', $invoiceHash) }}" class="btn btn-success {{ $isPaid ? 'disabled' : '' }}" {{ $isPaid ? 'aria-disabled=true tabindex=-1' : '' }}>
                                <i class="bx bx-money me-1"></i>Record Payment
                            </a>
                            @endcan
                        @endif
                        <a href="{{ route('sales.opening-balances.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Opening Balances
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Opening Balance</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div><strong>Customer:</strong> {{ $balance->customer->name ?? 'N/A' }}</div>
                        <div><strong>Date:</strong> {{ optional($balance->opening_date)->format('Y-m-d') }}</div>
                        <div><strong>Status:</strong> <span class="badge bg-secondary">{{ ucfirst($balance->status) }}</span></div>
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <div class="fw-bold text-muted">Amount</div>
                                <div class="fs-5">{{ $balance->currency ?? 'TZS' }} {{ number_format($balance->amount, 2) }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="fw-bold text-muted">Paid</div>
                                <div class="fs-5 text-success">{{ $balance->currency ?? 'TZS' }} {{ number_format($balance->paid_amount, 2) }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="fw-bold text-muted">Balance Due</div>
                                <div class="fs-5 text-primary">{{ $balance->currency ?? 'TZS' }} {{ number_format($balance->balance_due, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div><strong>Currency:</strong> {{ $balance->currency ?? 'TZS' }}</div>
                        <div><strong>Exchange Rate:</strong> {{ number_format((float)($balance->exchange_rate ?? 1), 6) }}</div>
                    </div>
                    <div class="col-md-6">
                        <div><strong>Reference:</strong> {{ $balance->reference ?? '-' }}</div>
                        <div><strong>Notes:</strong> {{ $balance->notes ?? '-' }}</div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    @if($balance->invoice)
                    @php $invoiceHash = Vinkla\Hashids\Facades\Hashids::encode($balance->invoice->id); @endphp
                    <a class="btn btn-outline-info" href="{{ route('sales.invoices.show', $invoiceHash) }}"><i class="bx bx-link-external me-1"></i>View Opening Invoice</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


