@extends('layouts.main')

@section('title', 'Supplier Statement - ' . $supplier->name)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Supplier Statement', 'url' => route('purchases.reports.supplier-statement.index'), 'icon' => 'bx bx-file-text'],
            ['label' => $supplier->name, 'url' => '#', 'icon' => 'bx bx-user']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Supplier Statement</h4>
                    <div class="page-title-right d-flex gap-2">
                        <form method="POST" action="{{ route('purchases.reports.supplier-statement.export-pdf') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
                            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                            <input type="hidden" name="date_to" value="{{ $dateTo }}">
                            <button type="submit" class="btn btn-danger">
                                <i class="bx bx-file-pdf me-1"></i>Export PDF
                            </button>
                        </form>
                        <form method="POST" action="{{ route('purchases.reports.supplier-statement.export-excel') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
                            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                            <input type="hidden" name="date_to" value="{{ $dateTo }}">
                            <button type="submit" class="btn btn-success">
                                <i class="bx bx-file me-1"></i>Export Excel
                            </button>
                        </form>
                        <button onclick="window.print()" class="btn btn-outline-primary">
                            <i class="bx bx-printer me-1"></i>Print
                        </button>
                        <a href="{{ route('purchases.reports.supplier-statement.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statement Header -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary">{{ $supplier->name }}</h5>
                        <p class="mb-1"><strong>Address:</strong> {{ $supplier->address ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $supplier->phone ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $supplier->email ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h6 class="text-muted">Statement Period</h6>
                        <p class="mb-1"><strong>From:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }}</p>
                        <p class="mb-1"><strong>To:</strong> {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</p>
                        <p class="mb-1"><strong>Generated:</strong> {{ now()->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opening Balance -->
        @if($openingBalanceDue != 0)
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Opening Balance</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0"><strong>Opening Balance Due:</strong></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h5 class="mb-0 {{ $openingBalanceDue >= 0 ? 'text-danger' : 'text-success' }}">
                            TZS {{ number_format(abs($openingBalanceDue), 2) }}
                            @if($openingBalanceDue >= 0)
                                <small class="text-muted">(Amount Owed)</small>
                            @else
                                <small class="text-muted">(Credit Balance)</small>
                            @endif
                        </h5>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Transactions -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Transaction Details</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction['type'] === 'Payment' ? 'success' : ($transaction['type'] === 'Opening Balance' ? 'info' : 'primary') }}">
                                            {{ $transaction['type'] }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction['reference'] }}</td>
                                    <td>{{ $transaction['description'] }}</td>
                                    <td class="text-end">
                                        @if($transaction['debit'] > 0)
                                            TZS {{ number_format($transaction['debit'], 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($transaction['credit'] > 0)
                                            TZS {{ number_format($transaction['credit'], 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong class="{{ $transaction['balance'] >= 0 ? 'text-danger' : 'text-success' }}">
                                            TZS {{ number_format(abs($transaction['balance']), 2) }}
                                            @if($transaction['balance'] >= 0)
                                                <small class="text-muted">(Owed)</small>
                                            @else
                                                <small class="text-muted">(Credit)</small>
                                            @endif
                                        </strong>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No transactions found for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Totals:</th>
                                <th class="text-end">TZS {{ number_format($totalDebits, 2) }}</th>
                                <th class="text-end">TZS {{ number_format($totalCredits, 2) }}</th>
                                <th class="text-end">
                                    <strong class="{{ $finalBalance >= 0 ? 'text-danger' : 'text-success' }}">
                                        TZS {{ number_format(abs($finalBalance), 2) }}
                                        @if($finalBalance >= 0)
                                            <small class="text-muted">(Amount Owed)</small>
                                        @else
                                            <small class="text-muted">(Credit Balance)</small>
                                        @endif
                                    </strong>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Statement Summary</h6>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Opening Balance</h6>
                            <h5 class="{{ $openingBalanceDue >= 0 ? 'text-danger' : 'text-success' }}">
                                TZS {{ number_format(abs($openingBalanceDue), 2) }}
                            </h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Total Debits</h6>
                            <h5 class="text-danger">TZS {{ number_format($totalDebits, 2) }}</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Total Credits</h6>
                            <h5 class="text-success">TZS {{ number_format($totalCredits, 2) }}</h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6 class="text-muted">Final Balance</h6>
                            <h5 class="{{ $finalBalance >= 0 ? 'text-danger' : 'text-success' }}">
                                TZS {{ number_format(abs($finalBalance), 2) }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .page-title-right,
        .breadcrumb,
        .btn {
            display: none !important;
        }
        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
        }
        .table {
            font-size: 12px;
        }
    }
    
    .badge {
        font-size: 0.75rem;
    }
    
    .table th {
        background-color: #343a40 !important;
        color: white !important;
        border-color: #454d55 !important;
    }
    
    .table-light th {
        background-color: #f8f9fa !important;
        color: #495057 !important;
        border-color: #dee2e6 !important;
    }
</style>
@endpush
