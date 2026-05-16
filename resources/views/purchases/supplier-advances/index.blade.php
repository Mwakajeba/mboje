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
                @if($showAll)
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
                    <table id="supplier-advances-table" class="table table-striped table-hover align-middle w-100">
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
                        <tbody></tbody>
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
                    <table id="supplier-advance-balances-table" class="table table-striped table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Supplier</th>
                                <th class="text-end">Advances</th>
                                <th class="text-end">Applied</th>
                                <th class="text-end">Balance</th>
                                <th class="text-end text-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    var indexUrl = @json(route('purchases.supplier-advances.index'));
    var showAllSuppliers = @json($showAll);

    var dtLang = {
        processing: '<div class="spinner-border text-primary spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>',
        search: 'Search:',
        lengthMenu: 'Show _MENU_ entries',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        infoEmpty: 'Showing 0 to 0 of 0 entries',
        infoFiltered: '(filtered from _MAX_ total entries)',
        emptyTable: 'No records found',
        zeroRecords: 'No matching records found'
    };

    $('#supplier-advances-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: indexUrl,
            data: function (d) {
                d.table = 'advances';
            }
        },
        columns: [
            { data: 'advance_date_formatted', name: 'advance_date', searchable: false },
            { data: 'reference', name: 'reference', searchable: false },
            { data: 'supplier_name', name: 'supplier_name', searchable: true },
            { data: 'debit_account', name: 'debit_account', orderable: false, searchable: false },
            { data: 'credit_account', name: 'credit_account', orderable: false, searchable: false },
            { data: 'amount_formatted', name: 'amount', className: 'text-end', searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: Object.assign({}, dtLang, {
            emptyTable: @json('No supplier advances in this branch yet.'),
            searchPlaceholder: 'Search supplier…'
        }),
        initComplete: function () {
            $(this.api().table().container()).find('.dataTables_filter input').attr('placeholder', 'Search supplier…');
        }
    });

    $('#supplier-advance-balances-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: indexUrl,
            data: function (d) {
                d.table = 'balances';
                d.show_all = showAllSuppliers ? 1 : 0;
            }
        },
        columns: [
            { data: 'name', name: 'name', searchable: true },
            { data: 'advances_formatted', name: 'advances_total', className: 'text-end', searchable: false, orderable: true },
            { data: 'applied_formatted', name: 'applied_total', className: 'text-end', searchable: false, orderable: true },
            { data: 'balance_formatted', name: 'balance_formatted', className: 'text-end', searchable: false, orderable: true },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end text-nowrap' }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        language: Object.assign({}, dtLang, {
            emptyTable: 'No supplier advance activity in this summary.',
            searchPlaceholder: 'Search supplier…'
        }),
        initComplete: function () {
            $(this.api().table().container()).find('.dataTables_filter input').attr('placeholder', 'Search supplier…');
        }
    });
});
</script>
@endpush
