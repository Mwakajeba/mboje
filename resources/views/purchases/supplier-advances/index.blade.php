@extends('layouts.main')

@section('title', 'Supplier Advances')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Supplier Advances', 'url' => '#', 'icon' => 'bx bx-wallet-alt']
        ]" />

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="mb-0 text-uppercase">Supplier advances</h6>
            <div class="d-flex flex-wrap gap-2">
                @if(request()->boolean('all'))
                    <a href="{{ route('purchases.supplier-advances.index') }}" class="btn btn-outline-secondary btn-sm">Show suppliers with activity only (summary)</a>
                @else
                    <a href="{{ route('purchases.supplier-advances.index', ['all' => 1]) }}" class="btn btn-outline-secondary btn-sm">Show all suppliers (summary)</a>
                @endif
                @can('record purchase payment')
                <a href="{{ route('purchases.supplier-advances.opening-advance.create') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-book-open me-1"></i> Opening balance advance payment
                </a>
                <a href="{{ route('purchases.supplier-advances.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus me-1"></i> Add new advance
                </a>
                @endcan
            </div>
        </div>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card radius-10 mb-4">
            <div class="card-body">
                <h6 class="text-primary mb-3"><i class="bx bx-receipt me-1"></i> Advance vouchers</h6>
                <p class="text-muted small mb-3">
                    Each row is one posted advance (debit advance account; credit bank/cash or retained earnings for opening balance). Use actions to edit, delete, or open the supplier's full statement.
                </p>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Supplier</th>
                                <th>Debit account</th>
                                <th>Credit account</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end text-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($advances as $advance)
                                @php $enc = \Vinkla\Hashids\Facades\Hashids::encode($advance->id); $encSup = \Vinkla\Hashids\Facades\Hashids::encode($advance->supplier_id); @endphp
                                <tr>
                                    <td>{{ $advance->advance_date->format('Y-m-d') }}</td>
                                    <td>{{ $advance->reference }}</td>
                                    <td>{{ $advance->supplier->name ?? '—' }}</td>
                                    <td class="small">
                                        @if($advance->debitChartAccount)
                                            {{ $advance->debitChartAccount->account_code }} — {{ $advance->debitChartAccount->account_name }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($advance->isOpeningJournalAdvance())
                                            <span class="text-muted">Retained earnings (journal #{{ $advance->journal_id }})</span>
                                        @else
                                            {{ $advance->bankAccount->name ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ format_currency((float) $advance->amount) }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                                            @can('record purchase payment')
                                            <a href="{{ route('purchases.supplier-advances.edit', $enc) }}" class="btn btn-outline-primary" title="Edit">
                                                <i class="bx bx-edit-alt"></i>
                                            </a>
                                            <form action="{{ route('purchases.supplier-advances.destroy', $enc) }}" method="post" class="d-inline"
                                                  onsubmit="return confirm('Delete this advance? Posted GL entries for this voucher will be removed.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                            @can('view purchases')
                                            <a href="{{ route('purchases.supplier-advances.statement', ['encodedSupplierId' => $encSup]) }}"
                                               class="btn btn-outline-secondary" title="Statement" target="_blank" rel="noopener">
                                                <i class="bx bx-file"></i>
                                            </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No supplier advances in this branch yet.
                                        @can('record purchase payment')
                                            <a href="{{ route('purchases.supplier-advances.create') }}">Record a new advance</a>.
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card radius-10">
            <div class="card-body">
                <h6 class="text-primary mb-3"><i class="bx bx-pie-chart-alt me-1"></i> Balances by supplier</h6>
                <p class="text-muted small mb-3">
                    <strong>Advances</strong> total posted prepayments; <strong>Applied</strong> is amounts offset on purchases, expenses, or refunds; <strong>Balance</strong> is advances minus applied.
                    <strong>Pay</strong> = cash returned (bank). <strong>Expense</strong> = charge expense accounts against advance.
                </p>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Supplier</th>
                                <th class="text-end">Advances</th>
                                <th class="text-end">Applied</th>
                                <th class="text-end">Balance</th>
                                <th class="text-end text-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suppliers as $supplier)
                                @php
                                    $adv = (float) ($supplier->advances_total ?? 0);
                                    $app = (float) ($supplier->applied_total ?? 0);
                                    $bal = $adv - $app;
                                    $encSup = \Vinkla\Hashids\Facades\Hashids::encode($supplier->id);
                                @endphp
                                <tr>
                                    <td>{{ $supplier->name }}</td>
                                    <td class="text-end">{{ format_currency($adv) }}</td>
                                    <td class="text-end">{{ format_currency($app) }}</td>
                                    <td class="text-end fw-semibold">{{ format_currency($bal) }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            @can('record purchase payment')
                                                @if($bal > 0.005)
                                                <a href="{{ route('purchases.supplier-advances.pay', ['encodedSupplierId' => $encSup]) }}"
                                                   class="btn btn-outline-primary" title="Record cash returned by supplier">
                                                    <i class="bx bx-money"></i> Pay
                                                </a>
                                                <a href="{{ route('purchases.supplier-advances.expense', ['encodedSupplierId' => $encSup]) }}"
                                                   class="btn btn-outline-warning" title="Apply advance to expense accounts">
                                                    <i class="bx bx-receipt"></i> Expense
                                                </a>
                                                @endif
                                            @endcan
                                            @can('view purchases')
                                            <a href="{{ route('purchases.supplier-advances.statement', ['encodedSupplierId' => $encSup]) }}"
                                               class="btn btn-outline-secondary" title="Statement" target="_blank" rel="noopener">
                                                <i class="bx bx-file"></i> Statement
                                            </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No supplier advance activity in this summary.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
