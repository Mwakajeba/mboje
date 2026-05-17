@extends('layouts.main')

@section('title', 'Ingiza Malipo Mapya — Malipo ya Awali')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Hesabu za Wasambazaji', 'url' => route('purchases.supplier-advances.index'), 'icon' => 'bx bx-wallet-alt'],
            ['label' => 'Ingiza Malipo Mapya', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <h6 class="mb-0 text-uppercase">Ingiza Malipo Mapya</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0 text-white"><i class="bx bx-wallet me-2"></i>Vocha ya malipo ya awali</h5>
                <p class="mb-0 opacity-75 small">Fedha zilizolipwa msambazaji mapema: debit akaunti ya malipo ya awali (mali), credit akaunti ya benki/fedha iliyotumika.</p>
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

                <form id="supplier-advance-create-form" action="{{ route('purchases.supplier-advances.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="supplier_id" class="form-label fw-bold">Msambazaji <span class="text-danger">*</span></label>
                            <select id="supplier_id" name="supplier_id" class="form-select select2-single @error('supplier_id') is-invalid @enderror" required>
                                <option value=""></option>
                                @foreach($suppliers as $s)
                                    @php $supAmt = number_format((float) ($s->advances_total ?? 0), 2, '.', ','); @endphp
                                    <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }} — {{ $supAmt }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="advance_date" class="form-label fw-bold">Tarehe <span class="text-danger">*</span></label>
                            <input type="date" id="advance_date" name="advance_date" class="form-control @error('advance_date') is-invalid @enderror"
                                   value="{{ old('advance_date', date('Y-m-d')) }}" required>
                            @error('advance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bank_account_id" class="form-label fw-bold">Akaunti ya benki / fedha (credit) <span class="text-danger">*</span></label>
                            <select id="bank_account_id" name="bank_account_id" class="form-select select2-single @error('bank_account_id') is-invalid @enderror" required>
                                <option value=""></option>
                                @foreach($bankAccounts as $b)
                                    <option value="{{ $b->id }}" @selected(old('bank_account_id') == $b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('bank_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="reference" class="form-label fw-bold">Marejeleo (si lazima)</label>
                            <input type="text" id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                   value="{{ old('reference') }}" maxlength="64" placeholder="Itatengenezwa kiotomatiki ikiwa tupu">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Maelezo <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="2" required placeholder="Maelezo kwa msambazaji au matumizi ya ndani">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="attachment" class="form-label fw-bold">Kiambatisho (si lazima)</label>
                        <input type="file" id="attachment" name="attachment" class="form-control @error('attachment') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">PDF au picha, upeo wa 5 MB.</small>
                        @error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <h6 class="text-uppercase text-muted mb-2">Mstari wa malipo ya awali (debit GL)</h6>
                    <div class="border rounded p-3 bg-light">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6">
                                <label for="debit_chart_account_id" class="form-label fw-bold">Akaunti ya chati <span class="text-danger">*</span></label>
                                <select id="debit_chart_account_id" name="debit_chart_account_id" class="form-select select2-single @error('debit_chart_account_id') is-invalid @enderror" required>
                                    <option value=""></option>
                                    @forelse($chartAccounts as $ca)
                                        <option value="{{ $ca->id }}" @selected(old('debit_chart_account_id') == $ca->id)>
                                            {{ $ca->account_code }} — {{ $ca->account_name }}
                                        </option>
                                    @empty
                                        <option value="" disabled>Hakuna akaunti zenye msimbo unaanza na 1100</option>
                                    @endforelse
                                </select>
                                <small class="text-muted">Akaunti zenye msimbo unaanza na <strong>1100</strong> tu (mf. 1100, 11001).</small>
                                @error('debit_chart_account_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="amount" class="form-label fw-bold">Kiasi <span class="text-danger">*</span></label>
                                <input type="text" inputmode="decimal" id="amount" name="amount" autocomplete="off"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount') }}" required placeholder="0.00">
                                <small class="text-muted">Tumia koma kwa maelfu unapoandika (mf. 1,234.56).</small>
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 p-3 border rounded bg-light">
                        <label class="form-label fw-bold mb-2">Ujumbe wa SMS</label>
                        <input type="hidden" name="sms_message_type" id="sms_message_type" value="{{ old('sms_message_type', 'malipo') }}">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input sms-type-check" type="checkbox" id="sms_chk_malipo" value="malipo"
                                       @checked(old('sms_message_type', 'malipo') === 'malipo')>
                                <label class="form-check-label" for="sms_chk_malipo">Malipo</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input sms-type-check" type="checkbox" id="sms_chk_mauzo" value="mauzo"
                                       @checked(old('sms_message_type') === 'mauzo')>
                                <label class="form-check-label" for="sms_chk_mauzo">Mauzo</label>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">Chagua moja tu. Haihifadhiwi kwenye mfumo — inaathiri ujumbe unaotumwa kwa simu ya kampuni.</small>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Hifadhi Malipo Mapya</button>
                        <a href="{{ route('purchases.supplier-advances.index') }}" class="btn btn-outline-secondary">Ghairi</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #supplier-advance-create-form .select2-container--bootstrap-5 .select2-selection--single {
        min-height: 38px;
        display: flex;
        align-items: center;
    }
    #supplier-advance-create-form #amount {
        min-height: 38px;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var $form = $('#supplier-advance-create-form');
    var $amt = $('#amount');
    if (!$form.length || !$amt.length) return;

    if (typeof $.fn.select2 !== 'undefined') {
        $('#supplier_id').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Chagua msambazaji…' });
        $('#bank_account_id').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Chagua akaunti ya benki…' });
        $('#debit_chart_account_id').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Chagua akaunti ya chati…' });
    }

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
    function applyFormat() {
        if (stripCommas($amt.val()) === '') return;
        $amt.val(formatAmountDisplay($amt.val()));
    }

    $amt.on('input', function () {
        var node = this;
        var before = node.value;
        var formatted = formatAmountDisplay(before);
        if (formatted !== before) {
            node.value = formatted;
            var len = node.value.length;
            node.setSelectionRange(len, len);
        }
    });
    $amt.on('blur', applyFormat);

    if (stripCommas($amt.val()) !== '') {
        applyFormat();
    }

    $form.on('submit', function () { $amt.val(stripCommas($amt.val())); });

    var $hiddenSmsType = $('#sms_message_type');
    var $chkMalipo = $('#sms_chk_malipo');
    var $chkMauzo = $('#sms_chk_mauzo');

    function setSmsType(type) {
        $hiddenSmsType.val(type);
        $chkMalipo.prop('checked', type === 'malipo');
        $chkMauzo.prop('checked', type === 'mauzo');
    }

    $chkMalipo.on('change', function () {
        if (this.checked) {
            setSmsType('malipo');
        } else if (!$chkMauzo.prop('checked')) {
            setSmsType('malipo');
        }
    });

    $chkMauzo.on('change', function () {
        if (this.checked) {
            setSmsType('mauzo');
        } else if (!$chkMalipo.prop('checked')) {
            setSmsType('malipo');
        }
    });

    if (!$chkMalipo.prop('checked') && !$chkMauzo.prop('checked')) {
        setSmsType('malipo');
    } else if ($chkMauzo.prop('checked')) {
        setSmsType('mauzo');
    } else {
        setSmsType('malipo');
    }
})();
</script>
@endpush
