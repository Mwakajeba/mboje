@extends('layouts.main')

@section('title', 'Cash Deposit Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Customers', 'url' => route('customers.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Customer', 'url' => route('customers.show', Hashids::encode($cashDeposit->customer_id)), 'icon' => 'bx bx-user'],
            ['label' => 'Cash Deposit', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="text-primary mb-2">Cash Deposit Details</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Customer:</strong> {{ $cashDeposit->customer->name }}</p>
                                        <p class="mb-1"><strong>Type:</strong> {{ $cashDeposit->type->name }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Current Balance:</strong>
                                            <span class="badge bg-success fs-6">TSHS {{ number_format($cashDeposit->amount, 2) }}</span>
                                        </p>
                                        <p class="mb-1"><strong>Branch:</strong> {{ $cashDeposit->branch->name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group" role="group">
                                    @can('deposit cash deposit')
                                    <a href="{{ route('cash_deposits.deposit', Hashids::encode($cashDeposit->id)) }}"
                                        class="btn btn-success">
                                        <i class="bx bx-plus me-1"></i> Deposit
                                    </a>
                                    @endcan

                                    @can('withdraw cash deposit')
                                    <a href="{{ route('cash_deposits.withdraw', Hashids::encode($cashDeposit->id)) }}"
                                        class="btn btn-warning">
                                        <i class="bx bx-minus me-1"></i> Withdraw
                                    </a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">Transaction History</h5>
                            </div>
                            @can('print transactions')
                            <div class="col-md-6 text-end">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary" onclick="printTransactions()">
                                        <i class="bx bx-printer me-1"></i> Print
                                    </button>

                                </div>
                            </div>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        @if($transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered dt-responsive nowrap" id="transactionsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Balance</th>
                                        <th>Payment Source</th>
                                        <th>Processed By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction['date']->format('d/m/Y') }}</td>
                                        <td>
                                            @if($transaction['type'] === 'Deposit')
                                            <span class="badge bg-success">
                                                <i class="bx bx-plus me-1"></i> Deposit
                                            </span>
                                            @else
                                            <span class="badge bg-warning">
                                                <i class="bx bx-minus me-1"></i> Withdrawal
                                            </span>
                                            @endif
                                        </td>
                                        <td>{{ $transaction['description'] }}</td>
                                        <td>
                                            <span class="fw-bold {{ $transaction['type'] === 'Deposit' ? 'text-success' : 'text-danger' }}">
                                                TSHS {{ number_format($transaction['amount'], 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold {{ $transaction['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                TSHS {{ number_format($transaction['balance'], 2) }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction['payment_source'] ?? $transaction['bank_account'] }}</td>
                                        <td>{{ $transaction['user'] }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if($transaction['type'] === 'Deposit')
                                                @can('edit transaction')
                                                <a href="{{ route('receipts.edit', Hashids::encode($transaction['id'])) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Edit Deposit">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                @else
                                                <a href="{{ route('payments.edit', Hashids::encode($transaction['id'])) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Edit Withdrawal">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                @endcan
                                                @endif

                                                @php
                                                $encodedId = Hashids::encode($transaction['id']);
                                                @endphp



                                                @can('delete transaction')
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteTransaction('{{ $encodedId }}', '{{ $transaction['type'] }}', '{{ $transaction['transaction_type'] }}')"
                                                    title="Delete Transaction">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                                @endcan

                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-5">
                            <i class="bx bx-money bx-lg text-muted mb-3"></i>
                            <h5 class="text-muted">No transactions found</h5>
                            <p class="text-muted">No deposits or withdrawals have been made for this cash deposit yet.</p>
                                                          @can('deposit cash deposit')
                            <a href="{{ route('cash_deposits.deposit', Hashids::encode($cashDeposit->id)) }}"
                                class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i> Make First Deposit
                            </a>
                            @endcan
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Section -->
        @if($transactions->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Transaction Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h6>Total Deposits</h6>
                                        <h4>TSHS {{ number_format($transactions->where('type', 'Deposit')->sum('amount'), 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h6>Total Withdrawals</h6>
                                        <h4>TSHS {{ number_format($transactions->where('type', 'Withdrawal')->sum('amount'), 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h6>Total Transactions</h6>
                                        <h4>{{ $transactions->count() }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h6>Current Balance</h6>
                                        <h4>TSHS {{ number_format($cashDeposit->amount, 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Hidden div for PDF content -->
<div id="pdfContent" style="display: none;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h2>Cash Deposit Transaction Report</h2>
        <p><strong>Customer:</strong> {{ $cashDeposit->customer->name }}</p>
        <p><strong>Type:</strong> {{ $cashDeposit->type->name }}</p>
        <p><strong>Current Balance:</strong> TSHS {{ number_format($cashDeposit->amount, 2) }}</p>
        <p><strong>Report Date:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    @if($transactions->count() > 0)
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Date</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Type</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Description</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Amount</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $transaction['date']->format('d/m/Y') }}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $transaction['type'] }}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">{{ $transaction['description'] }}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">
                    TSHS {{ number_format($transaction['amount'], 2) }}
                </td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">
                    TSHS {{ number_format($transaction['balance'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    function printTransactions() {
        const printContent = document.getElementById('pdfContent').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Cash Deposit Transactions - {{ $cashDeposit->customer->name }}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f8f9fa; }
                        .text-center { text-align: center; }
                        .text-right { text-align: right; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    function deleteTransaction(encodedId, type, transactionTypeName) {
        Swal.fire({
            title: 'Are you sure?',
            text: `This ${transactionTypeName} will be permanently deleted.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                const url = type === 'Deposit' ?
                    `/receipts/${encodedId}` :
                    `/payments/${encodedId}`;

                fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(async response => {
                        if (response.ok) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Transaction deleted successfully.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                            }).then(() => location.reload());
                        } else {
                            const text = await response.text(); // get raw response
                            console.error('Raw response:', text);

                            let message = 'Failed to delete transaction.';
                            try {
                                const data = JSON.parse(text);
                                message = data.message || message;
                            } catch (e) {
                                // fallback to raw HTML snippet in case of unexpected error
                                message = text.slice(0, 200); // avoid flooding
                            }

                            Swal.fire('Error', message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        Swal.fire('Error', error.message || 'Unexpected error.', 'error');
                    });

            }
        });
    }
</script>
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        $('#transactionsTable').DataTable({
            responsive: true,
            order: [
                [1, 'desc']
            ],
            pageLength: 10,
            language: {
                search: "",
                searchPlaceholder: "Search transactions..."
            },
            columnDefs: [{
                    targets: -1,
                    responsivePriority: 1,
                    orderable: false,
                    searchable: false
                },
                {
                    targets: [0, 1, 2],
                    responsivePriority: 2
                }
            ]
        });
    });
</script>
@endpush