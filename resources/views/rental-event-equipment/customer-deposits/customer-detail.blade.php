@extends('layouts.main')

@section('title', 'Customer Deposit Account')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Customer Deposits', 'url' => route('rental-event-equipment.customer-deposits.index'), 'icon' => 'bx bx-money'],
            ['label' => $customer->name, 'url' => '#', 'icon' => 'bx bx-user']
        ]" />
        <h6 class="mb-0 text-uppercase">CUSTOMER DEPOSIT ACCOUNT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <!-- Customer Info Card -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1 text-warning">{{ $customer->name }}</h5>
                                <p class="text-muted mb-0">Customer #{{ $customer->customerNo }}</p>
                            </div>
                            <div>
                                <a href="{{ route('rental-event-equipment.customer-deposits.export-customer-pdf', Hashids::encode($customer->id)) }}" class="btn btn-danger btn-sm">
                                    <i class="bx bx-file me-1"></i> Export PDF
                                </a>
                                <a href="{{ route('rental-event-equipment.customer-deposits.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-1">Total Deposited</p>
                                        <h4 class="mb-0 text-success">TZS {{ number_format($totalDeposited, 2) }}</h4>
                                    </div>
                                    <div class="ms-3">
                                        <div class="avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bx bx-plus font-size-24"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-1">Total Used</p>
                                        <h4 class="mb-0 text-danger">TZS {{ number_format($totalUsed, 2) }}</h4>
                                    </div>
                                    <div class="ms-3">
                                        <div class="avatar-sm bg-danger text-white rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bx bx-minus font-size-24"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-1">Remaining Balance</p>
                                        <h4 class="mb-0 text-primary">TZS {{ number_format($remainingBalance, 2) }}</h4>
                                    </div>
                                    <div class="ms-3">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="bx bx-wallet font-size-24"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deposit Movements -->
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0"><i class="bx bx-transfer me-2"></i> Deposit Movement History</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Reference</th>
                                        <th>Contract</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $runningBalance = 0;
                                    @endphp
                                    @foreach($movements as $movement)
                                        @php
                                            if ($movement['type'] === 'deposit') {
                                                $runningBalance += $movement['amount'];
                                            } else {
                                                $runningBalance -= $movement['amount'];
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($movement['date'] instanceof \Carbon\Carbon)
                                                    {{ $movement['date']->format('M d, Y') }}
                                                @else
                                                    {{ \Carbon\Carbon::parse($movement['date'])->format('M d, Y') }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($movement['type'] === 'deposit')
                                                    <span class="badge bg-success">DEPOSIT</span>
                                                @else
                                                    <span class="badge bg-danger">USAGE</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($movement['type'] === 'deposit')
                                                    @php
                                                        $deposit = \App\Models\RentalEventEquipment\CustomerDeposit::where('deposit_number', $movement['reference'])->first();
                                                    @endphp
                                                    @if($deposit)
                                                        <a href="{{ route('rental-event-equipment.customer-deposits.show', Hashids::encode($deposit->id)) }}" target="_blank">
                                                            {{ $movement['reference'] }}
                                                        </a>
                                                    @else
                                                        {{ $movement['reference'] }}
                                                    @endif
                                                @else
                                                    @php
                                                        $invoice = \App\Models\RentalEventEquipment\RentalInvoice::where('invoice_number', $movement['reference'])->first();
                                                    @endphp
                                                    @if($invoice)
                                                        <a href="{{ route('rental-event-equipment.rental-invoices.show', Hashids::encode($invoice->id)) }}" target="_blank">
                                                            {{ $movement['reference'] }}
                                                        </a>
                                                    @else
                                                        {{ $movement['reference'] }}
                                                    @endif
                                                @endif
                                            </td>
                                            <td>{{ $movement['contract'] ?? 'N/A' }}</td>
                                            <td class="text-end">
                                                @if($movement['type'] === 'deposit')
                                                    <span class="text-success"><strong>+TZS {{ number_format($movement['amount'], 2) }}</strong></span>
                                                @else
                                                    <span class="text-danger"><strong>-TZS {{ number_format($movement['amount'], 2) }}</strong></span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $statusBadge = match($movement['status']) {
                                                        'draft' => 'secondary',
                                                        'pending' => 'warning',
                                                        'confirmed' => 'success',
                                                        'refunded' => 'info',
                                                        'applied' => 'primary',
                                                        'sent' => 'primary',
                                                        'paid' => 'success',
                                                        'overdue' => 'danger',
                                                        'cancelled' => 'dark',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($movement['status']) }}</span>
                                            </td>
                                            <td>{{ $movement['description'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Current Balance:</th>
                                        <th class="text-end">
                                            <span class="badge bg-primary">TZS {{ number_format($runningBalance, 2) }}</span>
                                        </th>
                                        <th colspan="2"></th>
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
@endsection

@push('scripts')
@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp
@endpush
