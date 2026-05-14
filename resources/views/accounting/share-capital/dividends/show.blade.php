@extends('layouts.main')

@section('title', 'Dividend Details')

@push('styles')
<style>
    .info-card {
        border-left: 4px solid;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .info-card.border-primary {
        border-left-color: #0d6efd;
    }
    
    .info-card.border-success {
        border-left-color: #198754;
    }
    
    .info-card.border-warning {
        border-left-color: #ffc107;
    }
    
    .info-card.border-info {
        border-left-color: #0dcaf0;
    }
    
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 27px;
    }
    
    .bg-light-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-light-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-light-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-light-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Dividends', 'url' => route('accounting.share-capital.dividends.index'), 'icon' => 'bx bx-money'],
            ['label' => 'DIV-' . $dividend->id, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">DIVIDEND DETAILS</h6>
            <div>
                @if($dividend->status === 'draft')
                    <a href="{{ route('accounting.share-capital.dividends.edit', $dividend->encoded_id) }}" class="btn btn-primary me-2">
                        <i class="bx bx-edit"></i> Edit
                    </a>
                @endif
                <a href="{{ route('accounting.share-capital.dividends.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Statistics Cards -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 mb-4">
            <div class="col">
                <div class="card info-card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                <i class="bx bx-money"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Total Amount</h6>
                                <h4 class="mb-0">{{ $dividend->total_amount ? number_format($dividend->total_amount, 2) : 'N/A' }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card info-card border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                                <i class="bx bx-pie-chart-alt"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Per Share Amount</h6>
                                <h4 class="mb-0">{{ $dividend->per_share_amount ? number_format($dividend->per_share_amount, 6) : 'N/A' }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card info-card border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info me-3">
                                <i class="bx bx-group"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Payments</h6>
                                <h4 class="mb-0">{{ $dividend->dividendPayments->count() }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card info-card border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning me-3">
                                <i class="bx bx-check-circle"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Status</h6>
                                @php
                                    $badgeClass = match($dividend->status) {
                                        'draft' => 'badge bg-secondary',
                                        'approved' => 'badge bg-primary',
                                        'declared' => 'badge bg-info',
                                        'paying' => 'badge bg-warning',
                                        'paid' => 'badge bg-success',
                                        'cancelled' => 'badge bg-danger',
                                        default => 'badge bg-secondary',
                                    };
                                @endphp
                                <h4 class="mb-0"><span class="{{ $badgeClass }}">{{ strtoupper($dividend->status) }}</span></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dividend Details -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Dividend Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Share Class</label>
                                    <div>
                                        <strong>{{ $dividend->shareClass->name ?? 'All Classes' }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Dividend Type</label>
                                    <div>
                                        @php
                                            $typeBadge = match($dividend->dividend_type) {
                                                'cash' => 'bg-success',
                                                'bonus' => 'bg-info',
                                                'scrip' => 'bg-warning',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $typeBadge }}">{{ ucfirst($dividend->dividend_type) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Declaration Date</label>
                                    <div><strong>{{ $dividend->declaration_date->format('M d, Y') }}</strong></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Record Date</label>
                                    <div><strong>{{ $dividend->record_date->format('M d, Y') }}</strong></div>
                                </div>
                            </div>
                            @if($dividend->ex_date)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Ex-Dividend Date</label>
                                    <div><strong>{{ $dividend->ex_date->format('M d, Y') }}</strong></div>
                                </div>
                            </div>
                            @endif
                            @if($dividend->payment_date)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Payment Date</label>
                                    <div><strong>{{ $dividend->payment_date->format('M d, Y') }}</strong></div>
                                </div>
                            </div>
                            @endif
                            @if($dividend->per_share_amount)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Per Share Amount</label>
                                    <div><strong>{{ number_format($dividend->per_share_amount, 6) }}</strong></div>
                                </div>
                            </div>
                            @endif
                            @if($dividend->total_amount)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Total Amount</label>
                                    <div><strong>{{ number_format($dividend->total_amount, 2) }} {{ $dividend->currency_code ?? 'USD' }}</strong></div>
                                </div>
                            </div>
                            @endif
                            @if($dividend->description)
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Description</label>
                                    <div>{{ $dividend->description }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Dividend Payments (for cash dividends) -->
                @if($dividend->dividend_type === 'cash' && $dividend->dividendPayments->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-group me-2"></i>Dividend Payments</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Shareholder</th>
                                        <th class="text-end">Gross Amount</th>
                                        <th class="text-end">Withholding Tax</th>
                                        <th class="text-end">Net Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dividend->dividendPayments as $payment)
                                    <tr>
                                        <td>{{ $payment->shareholder->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($payment->gross_amount, 2) }}</td>
                                        <td class="text-end">{{ number_format($payment->withholding_tax_amount, 2) }}</td>
                                        <td class="text-end"><strong>{{ number_format($payment->net_amount, 2) }}</strong></td>
                                        <td>
                                            @php
                                                $statusBadge = match($payment->status) {
                                                    'paid' => 'bg-success',
                                                    'pending' => 'bg-warning',
                                                    'failed' => 'bg-danger',
                                                    'cancelled' => 'bg-secondary',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $statusBadge }}">{{ ucfirst($payment->status) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">{{ number_format($dividend->dividendPayments->sum('gross_amount'), 2) }}</th>
                                        <th class="text-end">{{ number_format($dividend->dividendPayments->sum('withholding_tax_amount'), 2) }}</th>
                                        <th class="text-end"><strong>{{ number_format($dividend->dividendPayments->sum('net_amount'), 2) }}</strong></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- GL Entries Section -->
                @if(isset($journal) && $journal && $journal->items->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-book me-2"></i>General Ledger Entries</h6>
                    </div>
                    <div class="card-body">
                        <!-- Journal Information -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted">Journal Reference:</small>
                                <div><strong>{{ $journal->reference }}</strong></div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Journal Date:</small>
                                <div><strong>{{ $journal->date->format('M d, Y') }}</strong></div>
                            </div>
                            @if($journal->approved)
                            <div class="col-md-6 mt-2">
                                <small class="text-muted">Approved:</small>
                                <div>
                                    <span class="badge bg-success">Yes</span>
                                    @if($journal->approved_at)
                                        <small class="text-muted">({{ $journal->approved_at->format('M d, Y H:i') }})</small>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Journal Items Table -->
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account Code</th>
                                        <th>Account Name</th>
                                        <th>Description</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalDebit = 0;
                                        $totalCredit = 0;
                                    @endphp
                                    @foreach($journal->items as $item)
                                    @php
                                        if ($item->nature == 'debit') {
                                            $totalDebit += $item->amount;
                                        } else {
                                            $totalCredit += $item->amount;
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <small class="text-muted">{{ $item->chartAccount->account_code ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $item->chartAccount->account_name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ $item->description ?? $journal->description }}</small>
                                        </td>
                                        <td class="text-end">
                                            @if($item->nature == 'debit')
                                                <span class="text-danger fw-semibold">{{ number_format($item->amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($item->nature == 'credit')
                                                <span class="text-success fw-semibold">{{ number_format($item->amount, 2) }}</span>
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
                                        <th class="text-end text-danger">{{ number_format($totalDebit, 2) }}</th>
                                        <th class="text-end text-success">{{ number_format($totalCredit, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- GL Transactions (if posted) -->
                        @if(isset($glTransactions) && $glTransactions->count() > 0)
                        <div class="mt-4">
                            <h6 class="mb-3"><i class="bx bx-list-ul me-2"></i>Posted GL Transactions</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Account</th>
                                            <th>Description</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($glTransactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->date->format('M d, Y') }}</td>
                                            <td>
                                                <small class="text-muted">{{ $transaction->chartAccount->account_code ?? 'N/A' }}</small><br>
                                                <strong>{{ $transaction->chartAccount->account_name ?? 'N/A' }}</strong>
                                            </td>
                                            <td>
                                                <small>{{ $transaction->description }}</small>
                                            </td>
                                            <td class="text-end">
                                                @if($transaction->nature == 'debit')
                                                    <span class="text-danger fw-semibold">{{ number_format($transaction->amount, 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($transaction->nature == 'credit')
                                                    <span class="text-success fw-semibold">{{ number_format($transaction->amount, 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @elseif(in_array($dividend->status, ['declared','paid']))
                <div class="card mb-3">
                    <div class="card-body text-center text-muted">
                        <i class="bx bx-info-circle fs-1 mb-2"></i>
                        <p class="mb-0">No GL entries found for this dividend yet.</p>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-lg-4">
                <!-- Actions -->
                <div class="card mb-3">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0"><i class="bx bx-cog"></i>Actions</h6>
                    </div>
                    <div class="card-body">
                        @if($dividend->status === 'draft')
                            <form id="approveDividendForm" action="{{ route('accounting.share-capital.dividends.approve', $dividend->encoded_id) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bx bx-check"></i> Approve Dividend
                                </button>
                            </form>
                        @endif
                        
                        @if($dividend->status === 'approved')
                            <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#declareModal">
                                <i class="bx bx-check"></i> Declare & Post to GL
                            </button>
                        @endif
                        
                        @if($dividend->status === 'declared' && $dividend->dividend_type === 'cash')
                            <button type="button" class="btn btn-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="bx bx-money"></i> Process Payment
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Audit Information -->
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-time"></i>Audit Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Created</small>
                            <div>{{ $dividend->created_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Created By</small>
                            <div><strong>{{ $dividend->creator->name ?? 'N/A' }}</strong></div>
                        </div>
                        @if($dividend->approver)
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Approved</small>
                            <div>{{ $dividend->updated_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Approved By</small>
                            <div><strong>{{ $dividend->approver->name ?? 'N/A' }}</strong></div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Declare Modal -->
<div class="modal fade" id="declareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="declareDividendForm" action="{{ route('accounting.share-capital.dividends.declare', $dividend->encoded_id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Declare Dividend & Post to GL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($dividend->dividend_type === 'cash')
                        <div class="mb-3">
                            <label class="form-label">Retained Earnings Account <span class="text-danger">*</span></label>
                            <select name="retained_earnings_account_id" class="form-select select2-single" required>
                                <option value="">Select Account</option>
                                @foreach($equityAccounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_code ?? '' }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dividend Payable Account <span class="text-danger">*</span></label>
                            <select name="dividend_payable_account_id" class="form-select select2-single" required>
                                <option value="">Select Account</option>
                                @foreach($equityAccounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_code ?? '' }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Withholding Tax Account</label>
                            <select name="withholding_tax_account_id" class="form-select select2-single">
                                <option value="">Select Account</option>
                                @foreach($withholdingTaxAccounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_code ?? '' }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label">Source Account (Retained Earnings/Share Premium) <span class="text-danger">*</span></label>
                            <select name="source_account_id" class="form-select select2-single" required>
                                <option value="">Select Account</option>
                                @foreach($equityAccounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_code ?? '' }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Share Capital Account <span class="text-danger">*</span></label>
                            <select name="share_capital_account_id" class="form-select select2-single" required>
                                <option value="">Select Account</option>
                                @foreach($equityAccounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_code ?? '' }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Declare & Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="paymentDividendForm" action="{{ route('accounting.share-capital.dividends.process-payment', $dividend->encoded_id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Process Dividend Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="form-select select2-single" required>
                            <option value="">Select Bank Account</option>
                            @foreach($bankAccounts as $bank)
                                <option value="{{ $bank->id }}">
                                    {{ $bank->account_number ?? '' }} - {{ $bank->name ?? ($bank->chartAccount->account_name ?? 'Bank') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dividend Payable Account <span class="text-danger">*</span></label>
                        <select name="dividend_payable_account_id" class="form-select select2-single" required>
                            <option value="">Select Account</option>
                            @foreach($equityAccounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_code ?? '' }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <strong>Total Payment:</strong> {{ number_format($dividend->dividendPayments->sum('net_amount'), 2) }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize select2-single in modals
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#declareModal').on('shown.bs.modal', function () {
                $(this).find('.select2-single').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#declareModal'),
                    width: '100%'
                });
            });

            $('#paymentModal').on('shown.bs.modal', function () {
                $(this).find('.select2-single').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#paymentModal'),
                    width: '100%'
                });
            });
        }

        const approveDividendForm = document.getElementById('approveDividendForm');
        if (approveDividendForm) {
            approveDividendForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (typeof Swal === 'undefined') {
                    return approveDividendForm.submit();
                }
                Swal.fire({
                    title: 'Approve this dividend?',
                    text: 'This will mark the dividend as approved and allow declaration / posting to GL.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        approveDividendForm.submit();
                    }
                });
            });
        }

        const declareDividendForm = document.getElementById('declareDividendForm');
        if (declareDividendForm) {
            declareDividendForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (typeof Swal === 'undefined') {
                    return declareDividendForm.submit();
                }
                Swal.fire({
                    title: 'Declare dividend and post to GL?',
                    text: 'This will create a journal for the dividend declaration and post GL entries.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, declare & post',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        declareDividendForm.submit();
                    }
                });
            });
        }

        const paymentDividendForm = document.getElementById('paymentDividendForm');
        if (paymentDividendForm) {
            paymentDividendForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (typeof Swal === 'undefined') {
                    return paymentDividendForm.submit();
                }
                Swal.fire({
                    title: 'Process dividend payment and post to GL?',
                    text: 'This will create a payment journal and mark payments as paid.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, process payment',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        paymentDividendForm.submit();
                    }
                });
            });
        }
    });
</script>
@endpush

