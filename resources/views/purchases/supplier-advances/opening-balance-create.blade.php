@extends('layouts.main')

@section('title', 'Supplier Opening Balance')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Supplier Advances', 'url' => route('purchases.supplier-advances.index'), 'icon' => 'bx bx-wallet-alt'],
            ['label' => 'Opening Balance', 'url' => '#', 'icon' => 'bx bx-book-open']
        ]" />

        <h6 class="mb-0 text-uppercase">Supplier opening balance</h6>
        <p class="text-muted small">Posted via journal: debit retained earnings, credit selected payable account (no bank account).</p>
        <hr />

        <div class="card radius-10">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0 text-white"><i class="bx bx-book-open me-2"></i>New supplier opening balance</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                @if(!$retainedEarningsAccount)
                    <div class="alert alert-warning">
                        Retained earnings account is not configured. Set <strong>retained_earnings_account_id</strong> in system settings before posting.
                    </div>
                @endif

                <form method="POST" action="{{ route('purchases.supplier-advances.opening-balance.store') }}">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select select2-single" required>
                                <option value=""></option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Opening date <span class="text-danger">*</span></label>
                            <input type="date" name="opening_date" class="form-control" value="{{ old('opening_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payable chart of account (credit) <span class="text-danger">*</span></label>
                            <select name="payable_chart_account_id" class="form-select select2-single" required>
                                <option value=""></option>
                                @foreach($chartAccounts as $ca)
                                    <option value="{{ $ca->id }}" @selected(old('payable_chart_account_id', $defaultPayableId) == $ca->id)>
                                        {{ $ca->account_code }} — {{ $ca->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Amount owed to the supplier is credited to this account.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Retained earnings (debit)</label>
                            @if($retainedEarningsAccount)
                                <input type="text" class="form-control bg-light" readonly
                                       value="{{ $retainedEarningsAccount->account_code }} — {{ $retainedEarningsAccount->account_name }}">
                                <small class="text-muted">From system setting <strong>retained_earnings_account_id</strong>.</small>
                            @else
                                <input type="text" class="form-control bg-light text-danger" readonly value="Not configured">
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            @php
                                $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
                            @endphp
                            <input type="text" name="currency" class="form-control" value="{{ old('currency', $functionalCurrency) }}" maxlength="3">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Exchange rate</label>
                            <input type="number" name="exchange_rate" class="form-control" step="0.000001" min="0.000001" value="{{ old('exchange_rate', '1') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference</label>
                            <input type="text" name="reference" class="form-control" maxlength="100" value="{{ old('reference') }}" placeholder="Auto SOB-# if blank">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('purchases.supplier-advances.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" @disabled(!$retainedEarningsAccount)>
                            <i class="bx bx-check me-1"></i> Post journal &amp; GL
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
