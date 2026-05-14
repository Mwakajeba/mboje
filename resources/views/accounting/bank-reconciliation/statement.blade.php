@extends('layouts.main')

@section('title', 'Bank Reconciliation Statement')

@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        .breadcrumb { display: none !important; }
        body { background: white; }
        .card { border: none !important; box-shadow: none !important; }
    }
    .statement-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px 10px 0 0;
        margin-bottom: 0;
    }
    .statement-body {
        background: white;
        padding: 30px;
        border: 1px solid #e0e0e0;
        border-top: none;
    }
    .company-info {
        border-bottom: 2px solid rgba(255,255,255,0.3);
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .section-header {
        background: #f8f9fa;
        border-left: 4px solid #667eea;
        padding: 12px 15px;
        margin: 20px 0 15px 0;
        font-weight: 600;
        font-size: 14px;
        color: #333;
    }
    .section-subtitle {
        font-size: 11px;
        color: #666;
        font-style: italic;
        margin-top: 5px;
    }
    .statement-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 13px;
    }
    .statement-table thead {
        background: #667eea;
        color: white;
    }
    .statement-table thead th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .statement-table tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid #e9ecef;
    }
    .statement-table tbody tr:hover {
        background: #f8f9fa;
    }
    .statement-table tfoot {
        background: #f8f9fa;
        font-weight: 600;
    }
    .statement-table tfoot td {
        padding: 12px;
        border-top: 2px solid #667eea;
    }
    .amount-cell {
        text-align: right;
        font-family: 'Courier New', monospace;
        font-weight: 500;
    }
    .reconciliation-status {
        padding: 20px;
        border-radius: 8px;
        margin-top: 30px;
        text-align: center;
    }
    .status-reconciled {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }
    .status-not-reconciled {
        background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
        color: white;
    }
    .info-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        margin-right: 8px;
    }
    .badge-dnc { background: #d4edda; color: #155724; }
    .badge-upc { background: #f8d7da; color: #721c24; }
</style>

<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation', 'url' => route('accounting.bank-reconciliation.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => $bankReconciliation->bankAccount->name . ' - ' . $bankReconciliation->formatted_reconciliation_date, 'url' => route('accounting.bank-reconciliation.show', $bankReconciliation), 'icon' => 'bx bx-show'],
            ['label' => 'Bank Reconciliation Statement', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />
        
        <!-- Action Buttons -->
        <div class="d-flex justify-content-end mb-3 no-print">
            <a href="{{ route('accounting.bank-reconciliation.export-statement', $bankReconciliation) }}" class="btn btn-danger me-2">
                <i class="bx bx-download me-2"></i>Export PDF
            </a>
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="bx bx-printer me-2"></i>Print
            </button>
            <a href="{{ route('accounting.bank-reconciliation.show', $bankReconciliation) }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-2"></i>Back
            </a>
        </div>

        <!-- Statement Container -->
        <div class="card shadow-sm" style="border: none;">
            <!-- Professional Header -->
            <div class="statement-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        @php
                            $logoPath = null;
                            if ($company && !empty($company->logo)) {
                                $logoPath = asset('storage/' . $company->logo);
                            }
                        @endphp
                        @if($logoPath && file_exists(public_path('storage/' . $company->logo)))
                        <div class="mb-3">
                            <img src="{{ $logoPath }}" alt="{{ $company->name }}" style="max-height: 60px; background: white; padding: 8px; border-radius: 4px;">
                        </div>
                        @endif
                        <h2 class="mb-2 text-white" style="font-weight: 700; font-size: 28px;">
                            BANK RECONCILIATION STATEMENT
                        </h2>
                        <p class="mb-0 text-white" style="opacity: 0.9; font-size: 14px;">
                            For the period ending: {{ $bankReconciliation->end_date->format('d F Y') }}
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="text-white" style="opacity: 0.95;">
                            <p class="mb-1"><strong>Statement Date:</strong><br>{{ $bankReconciliation->end_date->format('d/m/Y') }}</p>
                            <p class="mb-0"><strong>Generated:</strong><br>{{ now()->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statement Body -->
            <div class="statement-body">
                <!-- Entity Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td style="width: 35%;" class="text-muted"><strong>Entity:</strong></td>
                                <td><strong>{{ $company->name ?? '[Company Name]' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Bank:</strong></td>
                                <td>{{ $bankReconciliation->bankAccount->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Account Number:</strong></td>
                                <td>{{ $bankReconciliation->bankAccount->account_number }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td style="width: 40%;" class="text-muted"><strong>Prepared By:</strong></td>
                                <td>{{ $bankReconciliation->user->name ?? '[User]' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Reviewed By:</strong></td>
                                <td>{{ $bankReconciliation->submittedBy->name ?? '[Supervisor]' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Approved By:</strong></td>
                                <td>{{ $bankReconciliation->approvedBy->name ?? '[Finance Manager]' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr style="border-top: 2px solid #e0e0e0; margin: 25px 0;">

                <!-- Section A: Bank Balance -->
                <div class="section-header">
                    A. BANK BALANCE AS PER BANK STATEMENT
                </div>
                <table class="statement-table">
                    <thead>
                        <tr>
                            <th style="width: 70%;">Item</th>
                            <th style="width: 30%;" class="text-end">Amount (TZS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Closing balance as per bank statement</td>
                            <td class="amount-cell">{{ number_format($bankReconciliation->bank_statement_balance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Section B: Deposits Not Credited (DNC) -->
                <div class="section-header">
                    B. ADD: DEPOSITS NOT YET CREDITED (DNC)
                    <div class="section-subtitle">Cash/cheques/receipts recorded in books but not yet reflected in bank statement</div>
                </div>
                <table class="statement-table">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Date</th>
                            <th style="width: 18%;">Reference</th>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 20%;" class="text-end">Amount (TZS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dncItems as $item)
                        <tr>
                            <td>{{ $item->transaction_date->format('d/m/Y') }}</td>
                            <td><span class="info-badge badge-dnc">{{ $item->reference ?? 'N/A' }}</span></td>
                            <td>{{ $item->description }}</td>
                            <td class="amount-cell">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3" style="font-style: italic;">No deposits not yet credited</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total DNC</strong></td>
                            <td class="amount-cell"><strong>{{ number_format($totalDNC, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Section C: Unpresented Cheques (UPC) -->
                <div class="section-header">
                    C. LESS: UNPRESENTED CHEQUES (UPC)
                    <div class="section-subtitle">Payments recorded in books but not yet cleared by bank</div>
                </div>
                <table class="statement-table">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Date</th>
                            <th style="width: 18%;">Cheque No / Reference</th>
                            <th style="width: 50%;">Payee</th>
                            <th style="width: 20%;" class="text-end">Amount (TZS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcItems as $item)
                        <tr>
                            <td>{{ $item->transaction_date->format('d/m/Y') }}</td>
                            <td><span class="info-badge badge-upc">{{ $item->reference ?? 'N/A' }}</span></td>
                            <td>{{ $item->description }}</td>
                            <td class="amount-cell">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3" style="font-style: italic;">No unpresented cheques</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total UPC</strong></td>
                            <td class="amount-cell"><strong>{{ number_format($totalUPC, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Section D: Bank Errors -->
                @if($bankErrors->count() > 0)
                <div class="section-header">
                    D. ADD / LESS: BANK ERRORS (if any)
                    <div class="section-subtitle">Bank posting mistakes – rare but must be shown</div>
                </div>
                <table class="statement-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 20%;">Adjustment</th>
                            <th style="width: 30%;" class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bankErrors as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>
                                <span class="badge bg-{{ $item->nature === 'credit' ? 'success' : 'danger' }}">
                                    {{ $item->nature === 'credit' ? 'Add' : 'Less' }}
                                </span>
                            </td>
                            <td class="amount-cell">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end"><strong>Total Bank Errors</strong></td>
                            <td class="amount-cell"><strong>{{ number_format($totalBankErrors, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
                @endif

                <!-- Section E: Adjusted Bank Balance -->
                <div class="section-header">
                    E. ADJUSTED BANK BALANCE
                </div>
                <table class="statement-table">
                    <tbody>
                        <tr>
                            <td style="width: 70%;">Bank Statement Closing Balance</td>
                            <td class="amount-cell" style="width: 30%;">{{ number_format($bankReconciliation->bank_statement_balance, 2) }}</td>
                        </tr>
                        <tr>
                            <td>ADD: Deposits Not Credited</td>
                            <td class="amount-cell">{{ number_format($totalDNC, 2) }}</td>
                        </tr>
                        <tr>
                            <td>LESS: Unpresented Cheques</td>
                            <td class="amount-cell" style="color: #dc3545;">({{ number_format($totalUPC, 2) }})</td>
                        </tr>
                        @if($totalBankErrors > 0)
                        <tr>
                            <td>ADD/LESS: Bank Errors</td>
                            <td class="amount-cell">{{ number_format($totalBankErrors, 2) }}</td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <td><strong>Adjusted Bank Balance</strong></td>
                            <td class="amount-cell" style="color: white;"><strong>{{ number_format($adjustedBankBalance, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Section F: Book Balance -->
                <div class="section-header">
                    F. BOOK BALANCE AS PER GENERAL LEDGER
                </div>
                <table class="statement-table">
                    <tbody>
                        <tr>
                            <td style="width: 70%;">Closing balance per Cashbook / GL</td>
                            <td class="amount-cell" style="width: 30%;">{{ number_format($bankReconciliation->book_balance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Reconciliation Status -->
                <div class="reconciliation-status {{ abs($adjustedBankBalance - $bankReconciliation->book_balance) < 0.01 ? 'status-reconciled' : 'status-not-reconciled' }}">
                    @if(abs($adjustedBankBalance - $bankReconciliation->book_balance) < 0.01)
                        <h4 class="mb-2" style="font-weight: 700;">
                            <i class="bx bx-check-circle me-2"></i>BANK RECONCILED
                        </h4>
                        <p class="mb-0" style="opacity: 0.95;">
                            Adjusted Bank Balance ({{ number_format($adjustedBankBalance, 2) }}) = GL Balance ({{ number_format($bankReconciliation->book_balance, 2) }})
                        </p>
                    @else
                        <h4 class="mb-2" style="font-weight: 700;">
                            <i class="bx bx-x-circle me-2"></i>NOT RECONCILED
                        </h4>
                        <p class="mb-0" style="opacity: 0.95;">
                            Difference: {{ number_format(abs($adjustedBankBalance - $bankReconciliation->book_balance), 2) }}
                        </p>
                    @endif
                </div>

                <!-- Footer -->
                <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 11px;">
                    <p class="mb-1">This statement was generated by Smart Accounting System</p>
                    <p class="mb-0">Reconciliation ID: {{ $bankReconciliation->id }} | Generated on {{ now()->format('d F Y \a\t H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
