@extends('layouts.main')

@section('title', 'Hesabu za Wasambazaji')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Hesabu za Wasambazaji', 'url' => '#', 'icon' => 'bx bx-wallet-alt']
        ]" />

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="mb-0 text-uppercase">Hesabu za Wasambazaji</h6>
            <div class="d-flex flex-wrap gap-2">
                @can('record purchase payment')
                <a href="{{ route('purchases.supplier-advances.opening-advance.create') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-book-open me-1"></i> Ingiza Malipo Ya Nyuma
                </a>
                <a href="{{ route('purchases.supplier-advances.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus me-1"></i> Ingiza Malipo Mapya
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
                <h6 class="text-primary mb-3"><i class="bx bx-pie-chart-alt me-1"></i> Hesabu za Malipo</h6>
                <p class="text-muted small mb-3">
                    <strong>Malipo ya awali</strong> ni jumla ya malipo yaliyochapishwa; <strong>Matumizi</strong> ni kiasi kilichotumika kwenye ununuzi, matumizi, au marejesho; <strong>Salio</strong> ni malipo ya awali minus matumizi.
                    <strong>Lipa</strong> = fedha zilirudishwa (benki). <strong>Weka Matumizi</strong> = toa matumizi kutoka salio la awali.
                </p>
                <div class="table-responsive">
                    <table id="supplier-advance-balances-table" class="table table-striped table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Msambazaji</th>
                                <th class="text-end">Malipo ya awali</th>
                                <th class="text-end">Matumizi</th>
                                <th class="text-end">Salio</th>
                                <th class="text-end text-nowrap">Vitendo</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card radius-10">
            <div class="card-body">
                <h6 class="text-primary mb-3"><i class="bx bx-receipt me-1"></i> Miamala</h6>
                <p class="text-muted small mb-3">
                    Kila mstari ni malipo ya awali yaliyochapishwa. Tumia vitendo kuhariri, kufuta, au kuona hesabu kamili ya msambazaji.
                </p>
                <div class="table-responsive">
                    <table id="supplier-advances-table" class="table table-striped table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Marejeleo</th>
                                <th>Msambazaji</th>
                                <th>Akaunti ya deni</th>
                                <th>Akaunti ya mkopo</th>
                                <th class="text-end">Kiasi</th>
                                <th class="text-end text-nowrap">Vitendo</th>
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

    $('#supplier-advance-balances-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: indexUrl,
            data: function (d) {
                d.table = 'balances';
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
            emptyTable: 'Hakuna salio la malipo ya awali kwa msambazaji.',
            searchPlaceholder: 'Tafuta msambazaji…'
        }),
        initComplete: function () {
            $(this.api().table().container()).find('.dataTables_filter input').attr('placeholder', 'Tafuta msambazaji…');
        }
    });

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
            emptyTable: @json('Hakuna miamala ya malipo ya awali katika tawi hili.'),
            searchPlaceholder: 'Tafuta msambazaji…'
        }),
        initComplete: function () {
            $(this.api().table().container()).find('.dataTables_filter input').attr('placeholder', 'Tafuta msambazaji…');
        }
    });
});
</script>
@endpush
