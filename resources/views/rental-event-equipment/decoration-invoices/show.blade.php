@extends('layouts.main')

@section('title', 'Decoration Service Invoice Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Service Invoices', 'url' => route('rental-event-equipment.decoration-invoices.index'), 'icon' => 'bx bx-receipt'],
                ['label' => $invoice->invoice_number, 'url' => '#', 'icon' => 'bx bx-show'],
            ]" />

            <h6 class="mb-0 text-uppercase">DECORATION SERVICE INVOICE DETAILS</h6>
            <hr />

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1 text-info">
                                <i class="bx bx-receipt me-2"></i>Invoice {{ $invoice->invoice_number }}
                            </h5>
                            <p class="mb-1 small text-muted">
                                Customer: <strong>{{ optional($invoice->customer)->name ?? 'N/A' }}</strong>
                            </p>
                            <p class="mb-0 small text-muted">
                                Invoice Date: <strong>{{ optional($invoice->invoice_date)->format('M d, Y') }}</strong>
                                @if($invoice->due_date)
                                    | Due Date: <strong>{{ $invoice->due_date->format('M d, Y') }}</strong>
                                @endif
                            </p>
                        </div>
                        <div class="text-end">
                            @php
                                $badgeClass = match ($invoice->status) {
                                    'draft' => 'secondary',
                                    'sent' => 'info',
                                    'paid' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $badgeClass }} mb-2">{{ ucfirst($invoice->status) }}</span>
                            <div class="mt-1">
                                <span class="h5 mb-0">TZS {{ number_format($invoice->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    @if($invoice->job)
                        <hr />
                        <p class="mb-1 small">
                            <strong>Decoration Job:</strong>
                            {{ $invoice->job->job_number }} – {{ optional($invoice->job->customer)->name }}
                        </p>
                        @if($invoice->job->event_date)
                            <p class="mb-0 small">
                                <strong>Event Date:</strong> {{ $invoice->job->event_date->format('M d, Y') }}
                                @if($invoice->job->event_location)
                                    | <strong>Location:</strong> {{ $invoice->job->event_location }}
                                @endif
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Service Details</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <p class="mb-1"><strong>Description</strong></p>
                            <p class="mb-0">
                                {{ $invoice->service_description ?? 'Decoration service as agreed.' }}
                            </p>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">Service Amount</td>
                                    <td class="text-end">TZS {{ number_format($invoice->service_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tax</td>
                                    <td class="text-end">TZS {{ number_format($invoice->tax_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">TZS {{ number_format($invoice->total_amount, 2) }}</th>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($invoice->notes)
                        <hr />
                        <p class="mb-0 small">
                            <strong>Internal Notes:</strong><br>
                            {{ $invoice->notes }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

