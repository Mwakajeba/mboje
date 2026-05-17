@extends('layouts.main')

@section('title', 'Weka Matumizi — Malipo ya Awali')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Hesabu za Wasambazaji', 'url' => route('purchases.supplier-advances.index'), 'icon' => 'bx bx-wallet-alt'],
            ['label' => 'Weka Matumizi', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        <h6 class="mb-0 text-uppercase">Weka Matumizi</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>Weka Matumizi</h5>
                <p class="mb-0 small">Inaandika jarida: debit akaunti za matumizi, credit malipo ya awali ya msambazaji. Hakuna miamala ya benki.</p>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="alert alert-info mb-4">
                    <strong>Msambazaji:</strong> {{ $supplier->name }}<br>
                    <strong>Salio la malipo ya awali:</strong> {{ format_currency($balance) }}
                </div>

                <form id="supplier-advance-expense-form" action="{{ route('purchases.supplier-advances.expense.store', ['encodedSupplierId' => $encodedSupplierId]) }}" method="post">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Msambazaji</label>
                            <input type="text" class="form-control" value="{{ $supplier->name }}" readonly disabled>
                        </div>
                        <div class="col-md-4">
                            <label for="date" class="form-label fw-bold">Tarehe <span class="text-danger">*</span></label>
                            <input type="date" id="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                   value="{{ old('date', date('Y-m-d')) }}" required>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="reference" class="form-label fw-bold">Marejeleo (si lazima)</label>
                            <input type="text" id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                   value="{{ old('reference') }}" maxlength="64" placeholder="Itatengenezwa kiotomatiki ikiwa tupu">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Maelezo <span class="text-danger">*</span></label>
                        <input type="text" id="description" name="description" class="form-control @error('description') is-invalid @enderror"
                               value="{{ old('description') }}" maxlength="2000" required placeholder="mf. Usafiri umetozwa kwenye malipo ya awali">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <h6 class="text-uppercase text-muted mb-2">Mistari ya matumizi</h6>
                    <div class="table-responsive border rounded mb-2">
                        <table class="table table-sm align-middle mb-0" id="expense-lines-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:280px">Akaunti ya matumizi <span class="text-danger">*</span></th>
                                    <th style="width:160px">Kiasi <span class="text-danger">*</span></th>
                                    <th>Maelezo ya mstari</th>
                                    <th style="width:48px"></th>
                                </tr>
                            </thead>
                            <tbody id="expense-lines-body">
                                @php
                                    $oldLines = old('line_items', [['chart_account_id' => '', 'amount' => '', 'description' => '']]);
                                @endphp
                                @foreach($oldLines as $idx => $line)
                                <tr class="expense-line-row">
                                    <td>
                                        <select name="line_items[{{ $idx }}][chart_account_id]" class="form-select form-select-sm line-account select2-single" required>
                                            <option value=""></option>
                                            @foreach($expenseAccounts as $ca)
                                                <option value="{{ $ca->id }}" @selected(($line['chart_account_id'] ?? '') == $ca->id)>
                                                    {{ $ca->account_code }} — {{ $ca->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" name="line_items[{{ $idx }}][amount]"
                                               class="form-control form-control-sm line-amount" autocomplete="off"
                                               value="{{ $line['amount'] ?? '' }}" required placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="text" name="line_items[{{ $idx }}][description]"
                                               class="form-control form-control-sm" maxlength="500"
                                               value="{{ $line['description'] ?? '' }}" placeholder="Si lazima">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line" title="Ondoa mstari" @if(count($oldLines) <= 1) disabled @endif>
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="1" class="text-end fw-bold">Jumla</td>
                                    <td class="fw-bold" id="expense-total-display">0.00</td>
                                    <td colspan="2">
                                        <small class="text-muted">Kiwango cha juu {{ format_currency($balance) }}</small>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="btn-add-expense-line">
                        <i class="bx bx-plus"></i> Ongeza mstari
                    </button>
                    @error('line_items')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning text-dark">
                            <i class="bx bx-check me-1"></i> Hifadhi Matumizi
                        </button>
                        <a href="{{ route('purchases.supplier-advances.index') }}" class="btn btn-outline-secondary">Ghairi</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<template id="expense-line-template">
    <tr class="expense-line-row">
        <td>
            <select name="line_items[__IDX__][chart_account_id]" class="form-select form-select-sm line-account" required>
                <option value=""></option>
                @foreach($expenseAccounts as $ca)
                    <option value="{{ $ca->id }}">{{ $ca->account_code }} — {{ $ca->account_name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" inputmode="decimal" name="line_items[__IDX__][amount]"
                   class="form-control form-control-sm line-amount" autocomplete="off" required placeholder="0.00">
        </td>
        <td>
            <input type="text" name="line_items[__IDX__][description]" class="form-control form-control-sm" maxlength="500" placeholder="Si lazima">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line" title="Ondoa mstari">
                <i class="bx bx-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var maxBalance = {{ number_format($balance, 2, '.', '') }};
    var $form = $('#supplier-advance-expense-form');
    var $body = $('#expense-lines-body');
    var lineIndex = $body.find('.expense-line-row').length;

    function stripCommas(v) { return String(v || '').replace(/,/g, ''); }
    function formatAmountDisplay(v) {
        var s = stripCommas(v).replace(/[^\d.]/g, '');
        var parts = s.split('.');
        var intp = parts[0] || '';
        var dec = parts.length > 1 ? '.' + parts.slice(1).join('').replace(/\./g, '').slice(0, 2) : '';
        intp = intp.replace(/^0+(\d)/, '$1');
        intp = intp.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return intp + dec;
    }
    function parseAmount(v) {
        var n = parseFloat(stripCommas(v));
        return isNaN(n) ? 0 : n;
    }
    function reindexLines() {
        $body.find('.expense-line-row').each(function (i) {
            $(this).find('[name^="line_items"]').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/line_items\[\d+\]/, 'line_items[' + i + ']'));
                }
            });
        });
        lineIndex = $body.find('.expense-line-row').length;
        var rows = $body.find('.expense-line-row').length;
        $body.find('.btn-remove-line').prop('disabled', rows <= 1);
    }
    function updateTotal() {
        var total = 0;
        $body.find('.line-amount').each(function () {
            total += parseAmount($(this).val());
        });
        $('#expense-total-display').text(total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        return total;
    }
    function initSelect2($el) {
        if (typeof $.fn.select2 === 'undefined') return;
        $el.each(function () {
            var $s = $(this);
            if ($s.hasClass('select2-hidden-accessible')) return;
            $s.addClass('select2-single').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Chagua akaunti…' });
        });
    }
    function bindAmountInput($input) {
        $input.off('input.sa-exp').on('input.sa-exp', function () {
            var node = this;
            var before = node.value;
            var formatted = formatAmountDisplay(before);
            if (formatted !== before) {
                node.value = formatted;
                var len = node.value.length;
                node.setSelectionRange(len, len);
            }
            updateTotal();
        });
    }

    initSelect2($body.find('.line-account'));
    $body.find('.line-amount').each(function () { bindAmountInput($(this)); });
    updateTotal();

    $('#btn-add-expense-line').on('click', function () {
        var tpl = $('#expense-line-template').html().replace(/__IDX__/g, String(lineIndex));
        var $row = $(tpl);
        $body.append($row);
        initSelect2($row.find('.line-account'));
        bindAmountInput($row.find('.line-amount'));
        reindexLines();
        updateTotal();
    });

    $body.on('click', '.btn-remove-line', function () {
        if ($body.find('.expense-line-row').length <= 1) return;
        var $row = $(this).closest('.expense-line-row');
        $row.find('.line-account.select2-hidden-accessible').select2('destroy');
        $row.remove();
        reindexLines();
        updateTotal();
    });

    $form.on('submit', function (e) {
        $body.find('.line-amount').each(function () {
            $(this).val(stripCommas($(this).val()));
        });
        var total = updateTotal();
        if (total <= 0) {
            e.preventDefault();
            alert('Ongeza angalau mstari mmoja wa matumizi wenye kiasi zaidi ya sifuri.');
            return false;
        }
        if (total > maxBalance + 0.0001) {
            e.preventDefault();
            alert('Jumla haiwezi kuzidi salio la malipo ya awali (' + maxBalance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ').');
            return false;
        }
    });
})();
</script>
@endpush
