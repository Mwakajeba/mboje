@extends('layouts.main')

@section('title', 'New Supplier Advance')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Supplier Advances', 'url' => route('purchases.supplier-advances.index'), 'icon' => 'bx bx-wallet-alt'],
            ['label' => 'New advance', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <h6 class="mb-0 text-uppercase">New supplier advance</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0 text-white"><i class="bx bx-wallet me-2"></i>Supplier advance voucher</h5>
                <p class="mb-0 opacity-75 small">Money paid to the supplier in advance: debit the advance (asset) account, credit the bank/cash account used.</p>
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
                            <label for="supplier_id" class="form-label fw-bold">Supplier <span class="text-danger">*</span></label>
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
                            <label for="advance_date" class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" id="advance_date" name="advance_date" class="form-control @error('advance_date') is-invalid @enderror"
                                   value="{{ old('advance_date', date('Y-m-d')) }}" required>
                            @error('advance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bank_account_id" class="form-label fw-bold">Bank / cash account (credited) <span class="text-danger">*</span></label>
                            <select id="bank_account_id" name="bank_account_id" class="form-select select2-single @error('bank_account_id') is-invalid @enderror" required>
                                <option value=""></option>
                                @foreach($bankAccounts as $b)
                                    <option value="{{ $b->id }}" @selected(old('bank_account_id') == $b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('bank_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="reference" class="form-label fw-bold">Reference (optional)</label>
                            <input type="text" id="reference" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                   value="{{ old('reference') }}" maxlength="64" placeholder="Leave blank to auto-generate">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Description (optional)</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="Notes for the supplier or internal use">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="attachment" class="form-label fw-bold">Attachment (optional)</label>
                        <input type="file" id="attachment" name="attachment" class="form-control @error('attachment') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">PDF or image, max 5 MB.</small>
                        @error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <h6 class="text-uppercase text-muted mb-2">Advance line (GL debit)</h6>
                    <div class="border rounded p-3 bg-light">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6">
                                <label for="debit_chart_account_id" class="form-label fw-bold">Chart of account <span class="text-danger">*</span></label>
                                <select id="debit_chart_account_id" name="debit_chart_account_id" class="form-select select2-single @error('debit_chart_account_id') is-invalid @enderror" required>
                                    <option value=""></option>
                                    @forelse($chartAccounts as $ca)
                                        <option value="{{ $ca->id }}" @selected(old('debit_chart_account_id') == $ca->id)>
                                            {{ $ca->account_code }} — {{ $ca->account_name }}
                                        </option>
                                    @empty
                                        <option value="" disabled>No accounts with code starting 1100</option>
                                    @endforelse
                                </select>
                                <small class="text-muted">Only accounts whose code begins with <strong>1100</strong> (e.g. 1100, 11001).</small>
                                @error('debit_chart_account_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="amount" class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                                <input type="text" inputmode="decimal" id="amount" name="amount" autocomplete="off"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount') }}" required placeholder="0.00">
                                <small class="text-muted">Thousands separated by commas as you type (e.g. 1,234.56).</small>
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Save &amp; post to GL</button>
                        <a href="{{ route('purchases.supplier-advances.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
})();
</script>
@endpush
