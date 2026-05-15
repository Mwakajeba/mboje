@extends('layouts.main')

@section('title', 'Refund Supplier Advance')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Supplier Advances', 'url' => route('purchases.supplier-advances.index'), 'icon' => 'bx bx-wallet-alt'],
            ['label' => 'Refund advance', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

        <h6 class="mb-0 text-uppercase">Supplier advance refund (cash returned)</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-white"><i class="bx bx-money me-2"></i>Record cash returned by supplier</h5>
                <p class="mb-0 opacity-75 small">Posts a receipt: debit bank/cash, credit supplier advance account(s). Reduces the advance balance.</p>
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
                    <strong>Supplier:</strong> {{ $supplier->name }}<br>
                    <strong>Available advance balance:</strong> {{ format_currency($balance) }}
                </div>

                <form id="supplier-advance-pay-form" action="{{ route('purchases.supplier-advances.pay.store', ['encodedSupplierId' => $encodedSupplierId]) }}" method="post">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Supplier</label>
                            <input type="text" class="form-control" value="{{ $supplier->name }}" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="date" class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" id="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                   value="{{ old('date', date('Y-m-d')) }}" required>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bank_account_id" class="form-label fw-bold">Bank / cash account (debited) <span class="text-danger">*</span></label>
                            <select id="bank_account_id" name="bank_account_id" class="form-select select2-single @error('bank_account_id') is-invalid @enderror" required>
                                <option value=""></option>
                                @foreach($bankAccounts as $b)
                                    <option value="{{ $b->id }}" @selected(old('bank_account_id') == $b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('bank_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label fw-bold">Amount received <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" id="amount" name="amount" autocomplete="off"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}" required
                                   data-max-balance="{{ number_format($balance, 2, '.', '') }}"
                                   placeholder="0.00">
                            <small class="text-muted d-block">Thousands separated by commas as you type (e.g. 1,234.56). Max {{ format_currency($balance) }}.</small>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="reference" class="form-label fw-bold">Reference (optional)</label>
                            <input type="text" id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                   value="{{ old('reference') }}" maxlength="64" placeholder="Leave blank to auto-generate">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="description" class="form-label fw-bold">Description (optional)</label>
                            <input type="text" id="description" name="description" class="form-control @error('description') is-invalid @enderror"
                                   value="{{ old('description') }}" maxlength="2000" placeholder="e.g. Cash returned by supplier">
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-check me-1"></i> Post refund
                        </button>
                        <a href="{{ route('purchases.supplier-advances.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var $form = $('#supplier-advance-pay-form');
    var $amt = $('#amount');
    if (!$form.length || !$amt.length) return;

    var maxBalance = parseFloat($amt.data('max-balance')) || 0;

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

    $form.on('submit', function () {
        var raw = stripCommas($amt.val());
        var num = parseFloat(raw);
        if (!isNaN(num) && maxBalance > 0 && num > maxBalance + 0.0001) {
            num = maxBalance;
            raw = maxBalance.toFixed(2);
        }
        $amt.val(raw);
    });
})();
</script>
@endpush
