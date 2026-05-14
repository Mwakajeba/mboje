@extends('layouts.main')

@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp

@section('title', 'Receipt Voucher Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Receipt Vouchers', 'url' => route('accounting.receipt-vouchers.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Voucher Details', 'url' => '#', 'icon' => 'bx bx-info-circle']
        ]" />

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h6 class="mb-0 text-uppercase">RECEIPT VOUCHER DETAILS</h6>
                    <p class="text-muted mb-0">View receipt voucher information</p>
                </div>
                <div>
                    @if($receiptVoucher->payment_method === 'cheque' && !$receiptVoucher->cheque_deposited)
                    <button type="button" class="btn btn-warning me-2" onclick="showDepositChequeModal()">
                        <i class="bx bx-money me-2"></i>Deposit Cheque
                    </button>
                    @endif
                    @can('edit receipt voucher')
                    <a href="{{ route('accounting.receipt-vouchers.edit', Hashids::encode($receiptVoucher->id)) }}"
                        class="btn btn-primary me-2">
                        <i class="bx bx-edit me-2"></i>Edit Receipt Voucher
                    </a>
                    @endcan
                    <a href="{{ route('accounting.receipt-vouchers.export-pdf', Hashids::encode($receiptVoucher->id)) }}" class="btn btn-success me-2">
                        <i class="bx bx-file me-2"></i>Export PDF
                    </a>
                    @can('delete receipt voucher')
                    <button type="button" class="btn btn-outline-danger me-2" onclick="deleteReceiptVoucher()">
                        <i class="bx bx-trash me-2"></i>Delete
                    </button>
                    @endcan
                    <a href="{{ route('accounting.receipt-vouchers.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-2"></i>Back to Receipt Vouchers
                    </a>
                </div>
            </div>
            <hr />

            <!-- Prominent Header Card -->
            <div class="card radius-10 bg-gradient-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div
                            class="avatar-lg bg-white text-success rounded-circle me-3 d-flex align-items-center justify-content-center">
                            <i class="bx bx-receipt font-size-32"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-1">Receipt Voucher #{{ $receiptVoucher->reference }}</h3>
                            <p class="mb-0 opacity-75">{{ $receiptVoucher->description ?: 'No description provided' }}</p>
                        </div>
                        <div class="d-flex gap-2">
                            {!! $receiptVoucher->status_badge !!}
                            <span class="badge bg-light text-dark">
                                <i class="bx bx-calendar me-1"></i>
                                {{ $receiptVoucher->formatted_date }}
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="bx bx-money me-1"></i>
                                {{ $receiptVoucher->formatted_amount }}
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
                                    <p class="form-control-plaintext">{{ $receiptVoucher->formatted_date }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Reference</label>
                                    <p class="form-control-plaintext">{{ $receiptVoucher->reference }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Bank Account</label>
                                    <p class="form-control-plaintext">{{ $receiptVoucher->bankAccount->name ?? 'N/A' }} -
                                        {{ $receiptVoucher->bankAccount->account_number ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Payment Method</label>
                                    <p class="form-control-plaintext">
                                        @if($receiptVoucher->payment_method === 'cheque')
                                            <span class="badge bg-warning text-dark">
                                                <i class="bx bx-money me-1"></i>Cheque
                                                @if($receiptVoucher->cheque_deposited)
                                                    <span class="badge bg-success ms-2">Deposited</span>
                                                @else
                                                    <span class="badge bg-warning ms-2">In Transit</span>
                                                @endif
                                            </span>
                                        @elseif($receiptVoucher->payment_method === 'cash')
                                            <span class="badge bg-info">Cash</span>
                                        @else
                                            <span class="badge bg-primary">Bank Transfer</span>
                                        @endif
                                    </p>
                                </div>
                                @if($receiptVoucher->payment_method === 'cheque' && $receiptVoucher->cheque_deposited)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Cheque Deposited</label>
                                    <p class="form-control-plaintext">
                                        <i class="bx bx-check-circle text-success me-1"></i>
                                        Deposited on {{ $receiptVoucher->cheque_deposited_at ? $receiptVoucher->cheque_deposited_at->format('d M Y') : 'N/A' }}
                                        @if($receiptVoucher->chequeDepositedBy)
                                            by {{ $receiptVoucher->chequeDepositedBy->name }}
                                        @endif
                                    </p>
                                </div>
                                @endif
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Payee Type</label>
                                    <p class="form-control-plaintext">
                                        <span
                                            class="badge bg-{{ $receiptVoucher->payee_type === 'customer' ? 'info' : ($receiptVoucher->payee_type === 'employee' ? 'primary' : 'secondary') }}">
                                            {{ ucfirst($receiptVoucher->payee_type ?? 'N/A') }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Payee</label>
                                    <p class="form-control-plaintext">
                                        @if($receiptVoucher->payee_type === 'customer' && $receiptVoucher->customer)
                                            {{ $receiptVoucher->customer->name }} ({{ $receiptVoucher->customer->customerNo }})
                                        @elseif($receiptVoucher->payee_type === 'employee' && $receiptVoucher->employee)
                                            {{ $receiptVoucher->employee->full_name }}@if($receiptVoucher->employee->employee_number) ({{ $receiptVoucher->employee->employee_number }})@endif
                                        @elseif($receiptVoucher->payee_type === 'other')
                                            {{ $receiptVoucher->payee_name }}
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Attachment</label>
                                    <p class="form-control-plaintext">
                                        @if($receiptVoucher->attachment)
                                            <a href="{{ route('accounting.receipt-vouchers.download-attachment', Hashids::encode($receiptVoucher->id)) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bx bx-download me-1"></i>Download Attachment
                                            </a>
                                        @else
                                            <span class="text-muted">No attachment</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Description</label>
                                    <p class="form-control-plaintext">
                                        {{ $receiptVoucher->description ?: 'No description provided' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($receiptVoucher->reference_type === 'loan' && $receiptVoucher->loan)
                        <!-- Loan Information -->
                        <div class="card radius-10 mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bx bx-credit-card me-2"></i>Related Loan Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Loan Number</label>
                                        <p class="form-control-plaintext">
                                            <a href="{{ route('loans.show', Hashids::encode($receiptVoucher->loan->id)) }}"
                                                class="text-primary text-decoration-none">
                                                {{ $receiptVoucher->loan->loanNo }}
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Customer</label>
                                        <p class="form-control-plaintext">{{ $receiptVoucher->loan->customer->name ?? 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Loan Product</label>
                                        <p class="form-control-plaintext">{{ $receiptVoucher->loan->product->name ?? 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Loan Amount</label>
                                        <p class="form-control-plaintext text-success">
                                            {{ number_format($receiptVoucher->loan->amount, 2) }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Loan Status</label>
                                        <p class="form-control-plaintext">
                                            <span
                                                class="badge bg-{{ $receiptVoucher->loan->status === 'active' ? 'success' : 'warning' }}">
                                                {{ ucfirst($receiptVoucher->loan->status) }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Date Applied</label>
                                        <p class="form-control-plaintext">{{ $receiptVoucher->loan->date_applied }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

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
                                        @forelse($receiptVoucher->receiptItems as $item)
                                            <tr>
                                                <td>{{ $item->chartAccount->account_name ?? 'N/A' }}
                                                    ({{ $item->chartAccount->account_code ?? 'N/A' }})</td>
                                                <td>{{ $item->description ?: 'No description' }}</td>
                                                <td class="text-end">{{ $item->formatted_amount }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No line items found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2">Total</th>
                                            <th class="text-end fw-bold">
                                                {{ number_format($receiptVoucher->total_amount, 2) }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- GL Transactions -->
                    @if($receiptVoucher->glTransactions->count() > 0)
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
                                                $totalDebit = 0;
                                                $totalCredit = 0;
                                            @endphp
                                            @foreach($receiptVoucher->glTransactions as $glTransaction)
                                                <tr>
                                                    <td>{{ $glTransaction->chartAccount->account_name ?? 'N/A' }}
                                                        ({{ $glTransaction->chartAccount->account_code ?? 'N/A' }})</td>
                                                    <td class="text-end">
                                                        @if($glTransaction->nature === 'debit')
                                                            @php $totalDebit += $glTransaction->amount; @endphp
                                                            {{ number_format($glTransaction->amount, 2) }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if($glTransaction->nature === 'credit')
                                                            @php $totalCredit += $glTransaction->amount; @endphp
                                                            {{ number_format($glTransaction->amount, 2) }}
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
                                                <th class="text-end fw-bold">{{ number_format($totalDebit, 2) }}</th>
                                                <th class="text-end fw-bold">{{ number_format($totalCredit, 2) }}</th>
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
                        <div class="card-header bg-secondary text-dark">
                            <h5 class="mb-0"><i class="bx bx-building me-2"></i>Organization</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bx bx-building me-2"></i>Company
                                </label>
                                <p class="form-control-plaintext">{{ $receiptVoucher->customer->company->name ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bx bx-map-pin me-2"></i>Branch
                                </label>
                                <p class="form-control-plaintext">{{ $receiptVoucher->branch->name ?? 'N/A' }}</p>
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
                                <p class="form-control-plaintext">{{ $receiptVoucher->user->name ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bx bx-calendar me-2"></i>Created Date
                                </label>
                                <p class="form-control-plaintext">
                                    {{ $receiptVoucher->created_at ? $receiptVoucher->created_at->format('M d, Y H:i A') : 'N/A' }}
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bx bx-time me-2"></i>Last Updated
                                </label>
                                <p class="form-control-plaintext">
                                    {{ $receiptVoucher->updated_at ? $receiptVoucher->updated_at->format('M d, Y H:i A') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card radius-10">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bx bx-cog me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-2 flex-wrap">
                                @can('view receipt vouchers')
                                 <a href="{{ route('accounting.receipt-vouchers.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Back
                                </a>
                                @endcan

                                @if($receiptVoucher->reference_type === 'manual')
                                    @can('edit receipt voucher')
                                    <a href="{{ route('accounting.receipt-vouchers.edit', Hashids::encode($receiptVoucher->id)) }}"
                                        class="btn btn-outline-info">
                                        <i class="bx bx-edit me-1"></i>Edit
                                    </a>
                                    @endcan
                                    @can('delete receipt voucher')
                                    <button type="button" class="btn btn-outline-danger" onclick="deleteReceiptVoucher()">
                                        <i class="bx bx-trash me-1"></i>Delete
                                    </button>
                                    @endcan
                                @else
                                    <button type="button" class="btn btn-outline-secondary"
                                        title="Edit/Delete locked: Source is {{ ucfirst($receiptVoucher->reference_type) }} transaction"
                                        disabled>
                                        <i class="bx bx-lock"></i> Locked
                                    </button>
                                @endif
                                
                                <a href="{{ route('accounting.receipt-vouchers.export-pdf', Hashids::encode($receiptVoucher->id)) }}" class="btn btn-outline-success">
                                    <i class="bx bx-file me-1"></i>Export PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        function showDepositChequeModal() {
            Swal.fire({
                title: 'Deposit Cheque',
                html: `
                    <form id="depositChequeForm">
                        <div class="mb-3">
                            <label for="deposit_date" class="form-label">Deposit Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="deposit_date" name="deposit_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            This will move the cheque from "Cheques in Transit" to the bank account.
                        </div>
                    </form>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bx bx-money me-2"></i>Deposit Cheque',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                didOpen: () => {
                    // Set max date to today
                    const depositDateInput = document.getElementById('deposit_date');
                    if (depositDateInput) {
                        depositDateInput.max = new Date().toISOString().split('T')[0];
                    }
                },
                preConfirm: () => {
                    const depositDate = document.getElementById('deposit_date').value;
                    if (!depositDate) {
                        Swal.showValidationMessage('Please select a deposit date');
                        return false;
                    }
                    return depositDate;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    depositCheque(result.value);
                }
            });
        }

        function depositCheque(depositDate) {
            // Show loading
            Swal.fire({
                title: 'Depositing Cheque...',
                text: 'Please wait while we process the deposit.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Make AJAX request
            $.ajax({
                url: '{{ route("accounting.receipt-vouchers.deposit-cheque", Hashids::encode($receiptVoucher->id)) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    deposit_date: depositDate
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cheque Deposited!',
                        text: 'The cheque has been successfully deposited to the bank account.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reload the page to show updated status
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to deposit cheque.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            // If not JSON, use the response text
                            if (xhr.responseText.includes('error')) {
                                errorMessage = xhr.responseText;
                            }
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Deposit Failed',
                        text: errorMessage,
                        confirmButtonText: 'OK'
                    });
                }
            });
        }

        function deleteReceiptVoucher() {
            Swal.fire({
                title: 'Delete Receipt Voucher',
                text: 'Are you sure you want to delete this receipt voucher? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = $('<form>', {
                        'method': 'POST',
                        'action': '{{ route("accounting.receipt-vouchers.destroy", Hashids::encode($receiptVoucher->id)) }}'
                    });

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': '{{ csrf_token() }}'
                    }));

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_method',
                        'value': 'DELETE'
                    }));

                    $('body').append(form);
                    form.submit();
                }
            });
        }
    </script>
@endpush

@push('styles')
    <style>
        .bg-gradient-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .font-size-32 {
            font-size: 2rem;
        }
    </style>
@endpush