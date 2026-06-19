@extends('layouts.main')

@section('title', 'Hesabu za Wateja/Wakulima')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Hesabu za Wateja/Wakulima', 'url' => '#', 'icon' => 'bx bx-user-circle']
        ]" />

        <h6 class="mb-0 text-uppercase">Hesabu za Wateja/Wakulima</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-body">
                <p class="text-muted">
                    Chagua mteja/wakulima na tarehe. Ripoti inaonyesha mauzo (salio la nyuma + mapya), mikopo (salio la nyuma + mipya − malipo), salio la mteja, na zao lililobaki stoo mpaka siku hiyo.
                </p>

                <form id="customer-accounts-form" method="get" action="" target="_blank">
                    <div class="mb-3">
                        <label for="customer_accounts_customer_id" class="form-label fw-bold">
                            Mteja / Wakulima <span class="text-danger">*</span>
                        </label>
                        <select id="customer_accounts_customer_id" name="customer_id" class="form-select customer-accounts-select" required>
                            <option value=""></option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-encoded="{{ \Vinkla\Hashids\Facades\Hashids::encode($c->id) }}">
                                    {{ $c->name }}@if($c->customerNo) ({{ $c->customerNo }})@endif@if($c->phone) — {{ $c->phone }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="customer_accounts_entry_date" class="form-label fw-bold">
                            Tarehe <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="customer_accounts_entry_date" name="entry_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-show me-1"></i> Angalia hesabu
                    </button>
                    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary ms-2">
                        <i class="bx bx-arrow-back me-1"></i> Rudi
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    var $sel = $('#customer_accounts_customer_id');
    var baseUrl = @json(url('purchases/customer-accounts'));

    if ($sel.length && $.fn.select2) {
        $sel.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Tafuta mteja kwa jina, nambari au simu…',
            allowClear: true
        });
    }

    $('#customer-accounts-form').on('submit', function (e) {
        var customerId = $sel.val();
        var entryDate = $('#customer_accounts_entry_date').val();

        if (!customerId) {
            e.preventDefault();
            alert('Chagua mteja / wakulima.');
            return;
        }
        if (!entryDate) {
            e.preventDefault();
            alert('Chagua tarehe.');
            return;
        }

        var enc = $sel.find('option:selected').data('encoded');
        if (!enc) {
            e.preventDefault();
            alert('Mteja hajapatikana.');
            return;
        }

        this.action = baseUrl + '/' + encodeURIComponent(enc) + '?entry_date=' + encodeURIComponent(entryDate);
    });
});
</script>
@endpush
