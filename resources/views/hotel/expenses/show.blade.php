@extends('layouts.main')

@section('title', 'Hotel Expense Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Hotel Expenses', 'url' => route('hotel.expenses.index'), 'icon' => 'bx bx-wallet'],
            ['label' => 'Expense Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">HOTEL EXPENSE DETAILS</h6>
                <p class="text-muted mb-0">View expense information</p>
            </div>
            <div>
                <a href="{{ route('hotel.expenses.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-2"></i>Back to Expenses
                </a>
                @if(!$expense->approved)
                <a href="{{ route('hotel.expenses.edit', $expense->hash_id) }}" class="btn btn-primary">
                    <i class="bx bx-edit me-2"></i>Edit
                </a>
                @endif
                <a href="{{ route('hotel.expenses.export-pdf', $expense->hash_id) }}" class="btn btn-success">
                    <i class="bx bx-file me-2"></i>Export PDF
                </a>
                @if(!$expense->approved)
                <button type="button" class="btn btn-outline-danger" onclick="deleteExpense()">
                    <i class="bx bx-trash me-2"></i>Delete
                </button>
                @endif
            </div>
        </div>
        <hr />

        @if($expense->approved)
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-lock font-size-24 me-3"></i>
                <div>
                    <h6 class="alert-heading mb-1">Expense Approved</h6>
                    <p class="mb-0">This expense has been approved and is locked for editing.</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Header Card -->
        <div class="card radius-10 bg-primary text-white mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-lg bg-white text-danger rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="bx bx-wallet font-size-32"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="mb-1">Expense #{{ $expense->reference_number }}</h3>
                        <p class="mb-0 opacity-75">{{ $expense->description ?: 'No description provided' }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if($expense->approved)
                        <span class="badge bg-success">Approved</span>
                        @else
                        <span class="badge bg-warning">Pending</span>
                        @endif
                        <span class="badge bg-light text-dark">
                            <i class="bx bx-calendar me-1"></i>
                            {{ $expense->date->format('M d, Y') }}
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="bx bx-dollar me-1"></i>
                            TSh {{ number_format($expense->amount, 2) }}
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
                                <label class="form-label fw-bold">Date</label>
                                <p class="form-control-plaintext">{{ $expense->date->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Reference Number</label>
                                <p class="form-control-plaintext">{{ $expense->reference_number }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Bank Account</label>
                                <p class="form-control-plaintext">
                                    {{ $expense->bankAccount->name ?? 'N/A' }}
                                    @if($expense->bankAccount && $expense->bankAccount->account_number)
                                    - {{ $expense->bankAccount->account_number }}
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p class="form-control-plaintext">
                                    @if($expense->approved)
                                    <span class="badge bg-success">Approved</span>
                                    @else
                                    <span class="badge bg-warning">Pending</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <p class="form-control-plaintext">
                                    {{ $expense->description ?: 'No description provided' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Line Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="35%">Account</th>
                                        <th width="35%">Description</th>
                                        <th width="30%" class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($expense->paymentItems as $item)
                                    <tr>
                                        <td>{{ $item->chartAccount->account_name ?? 'N/A' }}
                                            @if($item->chartAccount && $item->chartAccount->account_code)
                                            ({{ $item->chartAccount->account_code }})
                                            @endif
                                        </td>
                                        <td>{{ $item->description ?: 'No description' }}</td>
                                        <td class="text-end">TSh {{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No line items found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th></th>
                                        <th class="text-end fw-bold">
                                            TSh {{ number_format($expense->amount, 2) }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- GL Transactions -->
                @if($expense->glTransactions && $expense->glTransactions->count() > 0)
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-book me-2"></i>General Ledger Entries</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40%">Account</th>
                                        <th width="30%" class="text-end">Debit</th>
                                        <th width="30%" class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $totalDebitGL = 0;
                                    $totalCreditGL = 0;
                                    @endphp
                                    @foreach($expense->glTransactions as $glTransaction)
                                    <tr>
                                        <td>{{ $glTransaction->chartAccount->account_name ?? 'N/A' }}
                                            @if($glTransaction->chartAccount && $glTransaction->chartAccount->account_code)
                                            ({{ $glTransaction->chartAccount->account_code }})
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($glTransaction->nature === 'debit')
                                            @php $totalDebitGL += $glTransaction->amount; @endphp
                                            TSh {{ number_format($glTransaction->amount, 2) }}
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($glTransaction->nature === 'credit')
                                            @php $totalCreditGL += $glTransaction->amount; @endphp
                                            TSh {{ number_format($glTransaction->amount, 2) }}
                                            @else
                                            -
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end fw-bold">TSh {{ number_format($totalDebitGL, 2) }}</th>
                                        <th class="text-end fw-bold">TSh {{ number_format($totalCreditGL, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Sidebar Information -->
            <div class="col-lg-4">
                <!-- Organization Information -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-building me-2"></i>Organization</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bx bx-map-pin me-2"></i>Branch
                            </label>
                            <p class="form-control-plaintext">{{ $expense->branch->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Audit Information -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-history me-2"></i>Audit Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bx bx-user me-2"></i>Created By
                            </label>
                            <p class="form-control-plaintext">{{ $expense->user->name ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bx bx-calendar me-2"></i>Created Date
                            </label>
                            <p class="form-control-plaintext">
                                {{ $expense->created_at ? $expense->created_at->format('M d, Y H:i A') : 'N/A' }}
                            </p>
                        </div>
                        @if($expense->updated_at && $expense->updated_at != $expense->created_at)
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bx bx-edit me-2"></i>Last Updated
                            </label>
                            <p class="form-control-plaintext">
                                {{ $expense->updated_at->format('M d, Y H:i A') }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="card radius-10">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-calculator me-2"></i>Financial Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Amount</span>
                            <span class="fw-bold text-primary fs-5">TSh {{ number_format($expense->amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Line Items</span>
                            <span class="fw-semibold">{{ $expense->paymentItems->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function deleteExpense() {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to delete this expense? This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('hotel.expenses.destroy', $expense->hash_id) }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
@endsection
