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
                    <strong>Lipa</strong> = fedha zilirudishwa (benki).
                    {{-- <strong>Weka Matumizi</strong> = toa matumizi kutoka salio la awali. --}}
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

{{-- Weka stoo modal — disabled; use Hesabu za Kila Siku (Wafanyakazi) instead
<div class="modal fade" id="wekaStooModal" tabindex="-1" aria-labelledby="wekaStooModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="wekaStooModalLabel">
                    <i class="bx bx-package me-1"></i> Weka stoo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Funga"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    <strong>Msambazaji:</strong> <span id="weka-stoo-supplier-name">—</span>
                </p>
                <form id="weka-stoo-form" novalidate>
                    <input type="hidden" id="weka_stoo_encoded_supplier_id" value="">
                    <div id="weka-stoo-form-errors" class="alert alert-danger d-none small py-2"></div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="weka_stoo_bidhaa" class="form-label fw-bold">Bidhaa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="weka_stoo_bidhaa" name="bidhaa" placeholder="Andika jina la bidhaa" required>
                        </div>
                        <div class="col-md-4">
                            <label for="weka_stoo_entry_date" class="form-label fw-bold">Tarehe <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="weka_stoo_entry_date" name="entry_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="weka_stoo_description" class="form-label fw-bold">Maelezo</label>
                        <textarea class="form-control" id="weka_stoo_description" name="description" rows="2" maxlength="2000" placeholder="Maelezo ya ziada (si lazima)"></textarea>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Aina ya Muamala</th>
                                    <th class="text-end" style="width: 140px">Idadi</th>
                                    <th class="text-end" style="width: 160px">Thamani</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold">Zilizouzwa</td>
                                    <td><input type="text" class="form-control form-control-sm" name="lines[zilizouzwa][idadi]" placeholder="Idadi" required></td>
                                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" name="lines[zilizouzwa][thamani]" value="0" required></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Zizonunuliwa</td>
                                    <td><input type="text" class="form-control form-control-sm" name="lines[zizonunuliwa][idadi]" placeholder="Idadi" required></td>
                                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" name="lines[zizonunuliwa][thamani]" value="0" required></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Baki</td>
                                    <td><input type="text" class="form-control form-control-sm" name="lines[baki][idadi]" placeholder="Idadi" required></td>
                                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" name="lines[baki][thamani]" value="0" required></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Funga</button>
                @can('record purchase payment')
                <button type="submit" form="weka-stoo-form" class="btn btn-success btn-sm" id="weka-stoo-submit">
                    <i class="bx bx-save me-1"></i> Hifadhi
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>
--}}

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    var indexUrl = @json(route('purchases.supplier-advances.index'));
    /* Weka stoo — disabled
    var wekaStooModalEl = document.getElementById('wekaStooModal');
    var wekaStooModal = wekaStooModalEl ? new bootstrap.Modal(wekaStooModalEl) : null;
    */

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

    /* Weka stoo — disabled
    var stockStoreUrlTemplate = @json(route('purchases.supplier-advances.stock.store', ['encodedSupplierId' => '__ID__']));

    $(document).on('click', '.btn-weka-stoo', function () {
        var $btn = $(this);
        $('#weka_stoo_encoded_supplier_id').val($btn.data('encoded-supplier-id') || '');
        $('#weka-stoo-supplier-name').text($btn.data('supplier-name') || '—');
        $('#weka-stoo-form-errors').addClass('d-none').empty();
        $('#weka-stoo-form')[0].reset();
        $('#weka_stoo_encoded_supplier_id').val($btn.data('encoded-supplier-id') || '');
        var today = new Date().toISOString().slice(0, 10);
        $('#weka_stoo_entry_date').val(today);
        if (wekaStooModal) {
            wekaStooModal.show();
        }
    });

    $('#weka-stoo-form').on('submit', function (e) {
        e.preventDefault();
        var encodedId = $('#weka_stoo_encoded_supplier_id').val();
        if (!encodedId) {
            return;
        }
        var $errors = $('#weka-stoo-form-errors');
        var $submit = $('#weka-stoo-submit');
        $errors.addClass('d-none').empty();
        $submit.prop('disabled', true);
        $.ajax({
            url: stockStoreUrlTemplate.replace('__ID__', encodedId),
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        }).done(function (res) {
            if (wekaStooModal) {
                wekaStooModal.hide();
            }
            var msg = (res && res.message) ? res.message : 'Stoo imehifadhiwa.';
            var $alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">'
                + $('<div>').text(msg).html()
                + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
            $('.page-content').prepend($alert);
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var list = [];
                $.each(xhr.responseJSON.errors, function (_, msgs) {
                    list = list.concat(msgs);
                });
                $errors.removeClass('d-none').html('<ul class="mb-0"><li>' + list.join('</li><li>') + '</li></ul>');
            } else {
                var errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Imeshindikana kuhifadhi stoo.';
                $errors.removeClass('d-none').text(errMsg);
            }
        }).always(function () {
            $submit.prop('disabled', false);
        });
    });
    */
});
</script>
@endpush
