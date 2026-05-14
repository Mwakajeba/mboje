@extends('layouts.main')

@section('title', 'Petty Cash Replenishment Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Petty Cash Units', 'url' => route('accounting.petty-cash.units.index'), 'icon' => 'bx bx-wallet'],
            ['label' => $replenishment->pettyCashUnit->name, 'url' => route('accounting.petty-cash.units.show', $replenishment->pettyCashUnit->encoded_id), 'icon' => 'bx bx-show'],
            ['label' => 'Replenishment #' . $replenishment->replenishment_number, 'url' => '#', 'icon' => 'bx bx-refresh']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">PETTY CASH REPLENISHMENT DETAILS</h6>
                <p class="text-muted mb-0">View replenishment information</p>
            </div>
            <div>
                <a href="{{ route('accounting.petty-cash.units.show', $replenishment->pettyCashUnit->encoded_id) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-2"></i>Back to Unit
                </a>
            </div>
        </div>
        <hr />

        <!-- Status Badge -->
        @php
            $statusColors = [
                'draft' => 'secondary',
                'submitted' => 'info',
                'approved' => 'success',
                'posted' => 'primary',
                'rejected' => 'danger'
            ];
            $statusColor = $statusColors[$replenishment->status] ?? 'secondary';
        @endphp

        <!-- Prominent Header Card -->
        <div class="card radius-10 bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-lg bg-white text-info rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="bx bx-refresh font-size-32"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="mb-1">Replenishment #{{ $replenishment->replenishment_number }}</h3>
                        <p class="mb-0 opacity-75">{{ $replenishment->reason ?: 'No reason provided' }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-{{ $statusColor }}">
                            {{ ucfirst($replenishment->status) }}
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="bx bx-calendar me-1"></i>
                            {{ $replenishment->request_date->format('M d, Y') }}
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="bx bx-dollar me-1"></i>
                            TZS {{ number_format($replenishment->approved_amount ?? $replenishment->requested_amount, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Main Information -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Replenishment Number</label>
                                <p class="form-control-plaintext">{{ $replenishment->replenishment_number }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Request Date</label>
                                <p class="form-control-plaintext">{{ $replenishment->request_date->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Petty Cash Unit</label>
                                <p class="form-control-plaintext">
                                    <a href="{{ route('accounting.petty-cash.units.show', $replenishment->pettyCashUnit->encoded_id) }}" class="text-primary">
                                        {{ $replenishment->pettyCashUnit->name }}
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Requested Amount</label>
                                <p class="form-control-plaintext text-danger fw-bold">
                                    TZS {{ number_format($replenishment->requested_amount, 2) }}
                                </p>
                            </div>
                            @if($replenishment->approved_amount)
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Approved Amount</label>
                                <p class="form-control-plaintext text-success fw-bold">
                                    TZS {{ number_format($replenishment->approved_amount, 2) }}
                                </p>
                            </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Source Bank Account</label>
                                <p class="form-control-plaintext">
                                    @if($replenishment->sourceAccount)
                                        {{ $replenishment->sourceAccount->name }} ({{ $replenishment->sourceAccount->account_number }})
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Reason</label>
                                <p class="form-control-plaintext">{{ $replenishment->reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- General Ledger Entries -->
                @if($replenishment->journal_id && $replenishment->journal)
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-book me-2"></i>General Ledger Entries</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account Code</th>
                                        <th>Account Name</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($replenishment->journal->items as $item)
                                    <tr>
                                        <td>{{ $item->chartAccount->account_code }}</td>
                                        <td>{{ $item->chartAccount->account_name }}</td>
                                        <td class="text-end">
                                            @if($item->nature === 'debit')
                                                <span class="text-danger fw-bold">TZS {{ number_format($item->amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($item->nature === 'credit')
                                                <span class="text-success fw-bold">TZS {{ number_format($item->amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    <tr class="table-light fw-bold">
                                        <td colspan="2" class="text-end">Total:</td>
                                        <td class="text-end text-danger">
                                            TZS {{ number_format($replenishment->journal->items->where('nature', 'debit')->sum('amount'), 2) }}
                                        </td>
                                        <td class="text-end text-success">
                                            TZS {{ number_format($replenishment->journal->items->where('nature', 'credit')->sum('amount'), 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Status & Actions -->
            <div class="col-lg-4">
                <!-- Status Information -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-{{ $statusColor }} text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Status Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Status</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-{{ $statusColor }}">{{ ucfirst($replenishment->status) }}</span>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Requested By</label>
                            <p class="form-control-plaintext">
                                {{ $replenishment->requestedBy->name ?? 'N/A' }}
                                <br>
                                <small class="text-muted">{{ $replenishment->request_date->format('M d, Y') }}</small>
                            </p>
                        </div>
                        @if($replenishment->approved_by)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Approved By</label>
                            <p class="form-control-plaintext">
                                {{ $replenishment->approvedBy->name ?? 'N/A' }}
                                <br>
                                <small class="text-muted">
                                    {{ $replenishment->approved_at ? $replenishment->approved_at->format('M d, Y H:i') : 'N/A' }}
                                </small>
                            </p>
                        </div>
                        @endif
                        @if($replenishment->approval_notes)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Approval Notes</label>
                            <p class="form-control-plaintext">{{ $replenishment->approval_notes }}</p>
                        </div>
                        @endif
                        @if($replenishment->rejection_reason)
                        <div class="mb-3">
                            <label class="form-label fw-bold text-danger">Rejection Reason</label>
                            <p class="form-control-plaintext text-danger">{{ $replenishment->rejection_reason }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Unit Information -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-wallet me-2"></i>Unit Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Unit Name</label>
                            <p class="form-control-plaintext">
                                <a href="{{ route('accounting.petty-cash.units.show', $replenishment->pettyCashUnit->encoded_id) }}" class="text-primary">
                                    {{ $replenishment->pettyCashUnit->name }}
                                </a>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Balance</label>
                            <p class="form-control-plaintext fw-bold">
                                TZS {{ number_format($replenishment->pettyCashUnit->current_balance, 2) }}
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Float Amount</label>
                            <p class="form-control-plaintext">
                                TZS {{ number_format($replenishment->pettyCashUnit->float_amount, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

