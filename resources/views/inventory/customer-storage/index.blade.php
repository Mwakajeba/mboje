@extends('layouts.main')

@section('title', 'Stoo ya Muda Mfupi (Wateja)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Stoo ya Muda Mfupi (Wateja)', 'url' => '#', 'icon' => 'bx bx-user-pin']
        ]" />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            Tafadhali rekebisha makosa yafuatayo:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
        </div>
        @endif

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-archive-in me-2"></i>Pokea Zao la Mteja</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('inventory.customer-storage.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="customer_id" class="form-label">Mteja <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="customer_id" id="customer_id" class="form-select select2-single @error('customer_id') is-invalid @enderror" required>
                                    <option value="">— Chagua Mteja —</option>
                                    @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}@if($customer->phone) ({{ $customer->phone }})@endif
                                    </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="btn-add-customer" title="Ongeza mteja mpya">
                                    <i class="bx bx-plus"></i>
                                </button>
                            </div>
                            @error('customer_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label for="inventory_item_id" class="form-label">Zao <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="inventory_item_id" id="inventory_item_id" class="form-select select2-single @error('inventory_item_id') is-invalid @enderror" required>
                                    <option value="">— Chagua Zao —</option>
                                    @forelse($items as $item)
                                    <option value="{{ $item->id }}" {{ (string) old('inventory_item_id') === (string) $item->id ? 'selected' : '' }}>
                                        {{ $item->name }} ({{ $item->code }})
                                    </option>
                                    @empty
                                    <option value="" disabled>Hakuna zao katika tawi hili — ongeza kwenye Hesabu kwanza</option>
                                    @endforelse
                                </select>
                                <button type="button" class="btn btn-outline-success" id="btn-add-item" title="Ongeza zao jipya">
                                    <i class="bx bx-plus"></i>
                                </button>
                            </div>
                            @error('inventory_item_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @if($items->isEmpty())
                            <small class="text-muted">Hakuna bidhaa zinazoonekana kwa tawi la sasa. Angalia <a href="{{ route('inventory.items.index') }}">Orodha ya Bidhaa</a> au ongeza zao jipya.</small>
                            @endif
                        </div>
                        <div class="col-md-2">
                            <label for="mazunguko" class="form-label">Mazunguko <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" name="mazunguko" id="mazunguko"
                                   class="form-control @error('mazunguko') is-invalid @enderror"
                                   value="{{ old('mazunguko', 1) }}" required
                                   placeholder="mf. 1, 2, 3">
                            @error('mazunguko')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Mzunguko mpya = uhifadhi mpya</small>
                        </div>
                        <div class="col-md-2">
                            <label for="quantity" class="form-label">Idadi <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" name="quantity" id="quantity"
                                   class="form-control @error('quantity') is-invalid @enderror"
                                   value="{{ old('quantity') }}" required>
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label for="received_date" class="form-label">Tarehe Aliyoleta <span class="text-danger">*</span></label>
                            <input type="date" name="received_date" id="received_date"
                                   class="form-control @error('received_date') is-invalid @enderror"
                                   value="{{ old('received_date', date('Y-m-d')) }}" required>
                            @error('received_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-10">
                            <label for="notes" class="form-label">Maelezo</label>
                            <input type="text" name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                                   value="{{ old('notes') }}" placeholder="Maelezo ya hiari">
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bx bx-save me-1"></i> Hifadhi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="mb-0">Salio la Zao — Stoo ya Muda Mfupi</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('inventory.customer-storage.report') }}" class="btn btn-outline-dark btn-sm">
                            <i class="bx bx-bar-chart-alt-2 me-1"></i> Ripoti
                        </a>
                        <a href="{{ route('inventory.customer-storage.history') }}" class="btn btn-outline-info btn-sm">
                            <i class="bx bx-history me-1"></i> Angalia Historia Yote
                        </a>
                    </div>
                </div>

                <ul class="nav nav-pills gap-2 mb-3">
                    <li class="nav-item">
                        <a class="nav-link {{ ($listStatus ?? 'active') === 'active' ? 'active' : '' }}"
                           href="{{ route('inventory.customer-storage.index') }}">
                            Inaendelea
                            <span class="badge bg-light text-dark ms-1">{{ $statusCounts['active'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($listStatus ?? '') === 'inactive' ? 'active' : '' }}"
                           href="{{ route('inventory.customer-storage.index', ['status' => 'inactive']) }}">
                            Imeisha
                            <span class="badge bg-light text-dark ms-1">{{ $statusCounts['inactive'] ?? 0 }}</span>
                        </a>
                    </li>
                </ul>

                <div class="table-responsive">
                    <table id="balancesTable" class="table table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Mteja</th>
                                <th>Simu</th>
                                <th>Zao</th>
                                <th>Mazunguko</th>
                                <th>Idadi</th>
                                <th>Kifurushi</th>
                                <th>Hali</th>
                                <th>Vitendo</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Ongeza Mteja --}}
<div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-labelledby="quickCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickCustomerModalLabel">Ongeza Mteja Mpya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Funga"></button>
            </div>
            <div class="modal-body">
                <div id="quick-customer-errors" class="alert alert-danger d-none"></div>

                <h6 class="text-uppercase text-muted mb-2">Taarifa za Msingi</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="quick_customer_name" class="form-label">Jina Kamili <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_customer_name" maxlength="255" placeholder="Weka jina kamili">
                    </div>
                    <div class="col-md-6">
                        <label for="quick_customer_phone" class="form-label">Namba ya Simu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_customer_phone" maxlength="20" placeholder="255712345678">
                        <small class="text-muted">Tarakimu 12, ikianza na 255</small>
                    </div>
                    <div class="col-md-6">
                        <label for="quick_customer_id_type" class="form-label">Aina ya Kitambulisho</label>
                        <select id="quick_customer_id_type" class="form-select">
                            <option value="">Chagua aina ya kitambulisho</option>
                            @foreach(\App\Models\Customer::idTypeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="quick_customer_id_number" class="form-label">Namba ya Kitambulisho</label>
                        <input type="text" class="form-control" id="quick_customer_id_number" maxlength="100" placeholder="Weka namba ya kitambulisho">
                    </div>
                    <div class="col-md-4">
                        <label for="quick_customer_bank_name" class="form-label">Jina la Benki</label>
                        <input type="text" class="form-control" id="quick_customer_bank_name" maxlength="255" placeholder="Weka jina la benki">
                    </div>
                    <div class="col-md-4">
                        <label for="quick_customer_bank_account" class="form-label">Akaunti ya Benki</label>
                        <input type="text" class="form-control" id="quick_customer_bank_account" maxlength="50" placeholder="Weka namba ya akaunti">
                    </div>
                    <div class="col-md-4">
                        <label for="quick_customer_account_name" class="form-label">Jina la Akaunti</label>
                        <input type="text" class="form-control" id="quick_customer_account_name" maxlength="255" placeholder="Weka jina la akaunti">
                    </div>
                    <div class="col-md-6">
                        <label for="quick_customer_status" class="form-label">Hali <span class="text-danger">*</span></label>
                        <select id="quick_customer_status" class="form-select">
                            <option value="active" selected>Hai</option>
                            <option value="inactive">Haifanyi kazi</option>
                            <option value="suspended">Imesimamishwa</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="quick_customer_send_welcome_sms" value="1">
                            <label class="form-check-label" for="quick_customer_send_welcome_sms">Tuma SMS ya kukaribisha</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="quick_customer_description" class="form-label">Maelezo</label>
                        <textarea class="form-control" id="quick_customer_description" rows="3" placeholder="Weka maelezo ya mteja"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Funga</button>
                <button type="button" class="btn btn-primary" id="save-quick-customer">
                    <i class="bx bx-save me-1"></i> Hifadhi Mteja
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Ongeza Zao / Bidhaa --}}
<div class="modal fade" id="quickItemModal" tabindex="-1" aria-labelledby="quickItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickItemModalLabel">Ongeza Zao Jipya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Funga"></button>
            </div>
            <div class="modal-body">
                <div id="quick-item-errors" class="alert alert-danger d-none"></div>

                <h6 class="text-uppercase text-muted mb-2">Taarifa za Msingi</h6>
                @if(isset($assignableBranches) && $assignableBranches->isNotEmpty())
                <div class="mb-3">
                    <label for="quick_item_branches" class="form-label">Inaonekana katika matawi</label>
                    <p class="text-muted small mb-1">Tawi la sasa limechaguliwa kiotomatiki.</p>
                    <select id="quick_item_branches" class="form-select quick-item-select2" multiple data-placeholder="Chagua matawi">
                        @foreach($assignableBranches as $branch)
                        <option value="{{ $branch->id }}" {{ (int) ($branchId ?? 0) === (int) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Aina ya Bidhaa</label>
                        <input type="text" class="form-control" value="Bidhaa" readonly disabled>
                        <input type="hidden" id="quick_item_type" value="product">
                    </div>
                    <div class="col-md-6">
                        <label for="quick_item_code" class="form-label">Nambari ya Bidhaa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_item_code" maxlength="255" placeholder="Weka nambari ya bidhaa">
                    </div>
                    <div class="col-md-6">
                        <label for="quick_item_name" class="form-label">Jina la Bidhaa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quick_item_name" maxlength="255" placeholder="Weka jina la bidhaa">
                    </div>
                    <div class="col-md-6">
                        <label for="quick_item_category" class="form-label">Kategoria <span class="text-danger">*</span></label>
                        <select id="quick_item_category" class="form-select quick-item-select2" data-placeholder="Chagua kategoria">
                            <option value=""></option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="quick_item_unit" class="form-label">Kipimo <span class="text-danger">*</span></label>
                        <select id="quick_item_unit" class="form-select quick-item-select2">
                            <option value="">Chagua kipimo</option>
                            @foreach($unitOptions as $value => $label)
                            <option value="{{ $value }}" {{ $value === 'kg' ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="quick_item_package_name" class="form-label">Jina la Kifurushi</label>
                        <input type="text" class="form-control" id="quick_item_package_name" placeholder="mf. gunia, boksi">
                    </div>
                    <div class="col-md-4">
                        <label for="quick_item_package_quantity" class="form-label">Idadi ya Kifurushi</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="quick_item_package_quantity" placeholder="mf. 12">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Funga</button>
                <button type="button" class="btn btn-success" id="save-quick-item">
                    <i class="bx bx-save me-1"></i> Hifadhi Zao
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toa Zao --}}
<div class="modal fade" id="withdrawStorageModal" tabindex="-1" aria-labelledby="withdrawStorageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="withdrawStorageModalLabel">Toa Zao kutoka Stoo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Funga"></button>
            </div>
            <div class="modal-body">
                <div id="withdraw-storage-errors" class="alert alert-danger d-none"></div>

                <p class="mb-2">
                    <strong>Mteja:</strong> <span id="withdraw_customer_name">—</span><br>
                    <strong>Zao:</strong> <span id="withdraw_item_name">—</span><br>
                    <strong>Salio:</strong> <span id="withdraw_balance_display">—</span>
                </p>

                <input type="hidden" id="withdraw_balance_id">

                <div class="mb-3">
                    <label for="withdraw_quantity" class="form-label">Idadi ya Kutoa <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="withdraw_quantity" placeholder="Weka idadi">
                    <small class="text-muted">Haiwezi kuzidi salio lililopo</small>
                </div>
                <div class="mb-3">
                    <label for="withdraw_reason" class="form-label">Sababu <span class="text-danger">*</span></label>
                    <select id="withdraw_reason" class="form-select">
                        <option value="">— Chagua Sababu —</option>
                        @foreach(\App\Models\Inventory\CustomerStorageWithdrawal::reasonOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3 d-none" id="withdraw_price_wrapper">
                    <label for="withdraw_price" class="form-label">Bei kwa Kila Kilo <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="withdraw_price" placeholder="Weka bei kwa kilo">
                    <div class="form-text mt-2">
                        <strong>Jumla:</strong> <span id="withdraw_total_preview">0.00</span>
                    </div>
                </div>
                <div class="mb-0">
                    <label for="withdraw_notes" class="form-label">Maelezo</label>
                    <textarea class="form-control" id="withdraw_notes" rows="3" placeholder="Maelezo ya ziada (hiari)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ghairi</button>
                <button type="button" class="btn btn-warning" id="save-withdraw-storage">
                    <i class="bx bx-export me-1"></i> Toa Zao
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Mapato / Gharama / Malipo --}}
@foreach([
    ['id' => 'mapato', 'title' => 'Ingiza Mapato', 'btn' => 'btn-success', 'icon' => 'bx-wallet', 'label' => 'Hifadhi Mapato'],
    ['id' => 'gharama', 'title' => 'Ingiza Gharama', 'btn' => 'btn-danger', 'icon' => 'bx-receipt', 'label' => 'Hifadhi Gharama'],
    ['id' => 'malipo', 'title' => 'Ingiza Malipo', 'btn' => 'btn-primary', 'icon' => 'bx-money', 'label' => 'Hifadhi Malipo'],
] as $financeModal)
<div class="modal fade" id="{{ $financeModal['id'] }}FinanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $financeModal['title'] }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Funga"></button>
            </div>
            <div class="modal-body">
                <div id="{{ $financeModal['id'] }}-finance-errors" class="alert alert-danger d-none"></div>
                <p class="mb-3">
                    <strong>Mteja:</strong> <span id="{{ $financeModal['id'] }}_customer_name">—</span><br>
                    <strong>Zao:</strong> <span id="{{ $financeModal['id'] }}_item_name">—</span>
                </p>
                <input type="hidden" id="{{ $financeModal['id'] }}_balance_id">
                <div class="mb-3">
                    <label for="{{ $financeModal['id'] }}_sababu" class="form-label">Sababu <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="{{ $financeModal['id'] }}_sababu" placeholder="Weka sababu" maxlength="500">
                </div>
                <div class="mb-3">
                    <label for="{{ $financeModal['id'] }}_entry_date" class="form-label">Tarehe <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="{{ $financeModal['id'] }}_entry_date" value="{{ date('Y-m-d') }}">
                </div>
                <div class="mb-0">
                    <label for="{{ $financeModal['id'] }}_kiasi" class="form-label">Kiasi <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="{{ $financeModal['id'] }}_kiasi" placeholder="Weka kiasi">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ghairi</button>
                <button type="button" class="btn {{ $financeModal['btn'] }}" id="save-{{ $financeModal['id'] }}-finance">
                    <i class="bx {{ $financeModal['icon'] }} me-1"></i> {{ $financeModal['label'] }}
                </button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('styles')
<style>
    .input-group > .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
        min-width: 0;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(function () {
    const currentBranchId = {{ (int) ($branchId ?? 0) }};

    function initSelect2InCard($el, placeholder) {
        $el.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: placeholder || 'Chagua...',
            allowClear: true,
            minimumResultsForSearch: 0,
            dropdownParent: $el.closest('.card-body').length ? $el.closest('.card-body') : $(document.body)
        });
    }

    initSelect2InCard($('#customer_id'), 'Chagua mteja...');
    initSelect2InCard($('#inventory_item_id'), 'Chagua zao...');

    function showValidationErrors(containerId, xhr) {
        const $box = $(containerId);
        let html = '';
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            Object.values(xhr.responseJSON.errors).forEach(function (errs) {
                html += '<div>' + errs[0] + '</div>';
            });
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
            html = '<div>' + xhr.responseJSON.message + '</div>';
        } else {
            html = '<div>Imeshindikana kuhifadhi. Jaribu tena.</div>';
        }
        $box.removeClass('d-none').html(html);
    }

    function resetQuickCustomerForm() {
        $('#quick-customer-errors').addClass('d-none').empty();
        $('#quick_customer_name, #quick_customer_phone, #quick_customer_id_number').val('');
        $('#quick_customer_bank_name, #quick_customer_bank_account, #quick_customer_account_name').val('');
        $('#quick_customer_description').val('');
        $('#quick_customer_id_type').val('');
        $('#quick_customer_status').val('active');
        $('#quick_customer_send_welcome_sms').prop('checked', false);
    }

    $('#btn-add-customer').on('click', function () {
        resetQuickCustomerForm();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('quickCustomerModal')).show();
    });

    function initQuickItemSelect2() {
        $('#quickItemModal .quick-item-select2').each(function () {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $el.data('placeholder') || 'Chagua...',
                allowClear: !$el.prop('multiple'),
                dropdownParent: $('#quickItemModal')
            });
        });
    }

    function resetQuickItemForm() {
        $('#quick-item-errors').addClass('d-none').empty();
        $('#quick_item_code, #quick_item_name, #quick_item_package_name').val('');
        $('#quick_item_package_quantity').val('');
        $('#quick_item_unit').val('kg');
        $('#quick_item_category').val('').trigger('change');
        if (currentBranchId) {
            $('#quick_item_branches').val([String(currentBranchId)]).trigger('change');
        } else {
            $('#quick_item_branches').val(null).trigger('change');
        }
    }

    $('#btn-add-item').on('click', function () {
        resetQuickItemForm();
        const modalEl = document.getElementById('quickItemModal');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    document.getElementById('quickItemModal').addEventListener('shown.bs.modal', function () {
        initQuickItemSelect2();
    });

    $('#save-quick-customer').on('click', function () {
        const $btn = $(this);
        $('#quick-customer-errors').addClass('d-none').empty();

        const payload = {
            name: $('#quick_customer_name').val().trim(),
            phone: $('#quick_customer_phone').val().trim(),
            id_type: $('#quick_customer_id_type').val(),
            id_number: $('#quick_customer_id_number').val().trim(),
            bank_name: $('#quick_customer_bank_name').val().trim(),
            bank_account_number: $('#quick_customer_bank_account').val().trim(),
            account_name: $('#quick_customer_account_name').val().trim(),
            status: $('#quick_customer_status').val(),
            description: $('#quick_customer_description').val().trim(),
            send_welcome_sms: $('#quick_customer_send_welcome_sms').is(':checked') ? 1 : 0
        };

        $btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('inventory.customer-storage.quick-customer') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: payload
        }).done(function (res) {
            if (res.customer && res.customer.id) {
                const label = res.customer.phone
                    ? res.customer.name + ' (' + res.customer.phone + ')'
                    : res.customer.name;
                const option = new Option(label, res.customer.id, true, true);
                $('#customer_id').append(option).trigger('change');
                bootstrap.Modal.getInstance(document.getElementById('quickCustomerModal')).hide();
                Swal.fire({ icon: 'success', title: 'Imefanikiwa', text: res.message || 'Mteja amesajiliwa.', timer: 2000, showConfirmButton: false });
            }
        }).fail(function (xhr) {
            showValidationErrors('#quick-customer-errors', xhr);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $('#save-quick-item').on('click', function () {
        const $btn = $(this);
        $('#quick-item-errors').addClass('d-none').empty();

        const payload = {
            item_type: 'product',
            code: $('#quick_item_code').val().trim(),
            name: $('#quick_item_name').val().trim(),
            category_id: $('#quick_item_category').val(),
            unit_of_measure: $('#quick_item_unit').val(),
            package_name: $('#quick_item_package_name').val().trim(),
            package_quantity: $('#quick_item_package_quantity').val(),
            branch_ids: $('#quick_item_branches').val() || []
        };

        $btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('inventory.customer-storage.quick-item') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: payload
        }).done(function (res) {
            if (res.item && res.item.id) {
                const label = res.item.name + ' (' + res.item.code + ')';
                const option = new Option(label, res.item.id, true, true);
                $('#inventory_item_id').append(option).trigger('change');
                bootstrap.Modal.getInstance(document.getElementById('quickItemModal')).hide();
                Swal.fire({ icon: 'success', title: 'Imefanikiwa', text: res.message || 'Zao limesajiliwa.', timer: 2000, showConfirmButton: false });
            }
        }).fail(function (xhr) {
            showValidationErrors('#quick-item-errors', xhr);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $('#balancesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('inventory.customer-storage.index', ['status' => $listStatus ?? 'active']) }}",
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            error: function (xhr) {
                console.error('Salio la zao — hitilafu ya DataTables:', xhr.status, xhr.responseText);
            }
        },
        columns: [
            { data: 'customer_name', name: 'customer_name', orderable: false },
            { data: 'customer_phone', name: 'customer_phone', orderable: false, searchable: false },
            { data: 'item_name', name: 'item_name', orderable: false },
            { data: 'mazunguko', name: 'mazunguko', className: 'text-center' },
            { data: 'quantity_display', name: 'quantity_on_hand' },
            { data: 'package_display', name: 'package_display', orderable: false, searchable: false },
            { data: 'status_label', name: 'status', orderable: false, searchable: false, className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        language: {
            processing: 'Inapakia...',
            emptyTable: @json(($listStatus ?? 'active') === 'inactive' ? 'Hakuna uhifadhi uliomaliza.' : 'Hakuna zao la wateja lililohifadhiwa.'),
            zeroRecords: 'Hakuna rekodi zilizopatikana.'
        }
    });

    $(document).on('click', '.btn-change-storage-status', function () {
        var $btn = $(this);
        var balanceId = $btn.data('balance-id');
        var status = $btn.data('status');
        var label = status === 'inactive' ? 'Imeisha' : 'Inaendelea';
        if (!confirm('Una uhakika unataka kubadilisha hali kuwa "' + label + '"?')) {
            return;
        }
        $btn.prop('disabled', true);
        $.ajax({
            url: @json(route('inventory.customer-storage.status')),
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: { balance_id: balanceId, status: status }
        }).done(function (res) {
            Swal.fire({ icon: 'success', title: 'Imefanikiwa', text: res.message || 'Hali imebadilishwa.', timer: 1800, showConfirmButton: false });
            $('#balancesTable').DataTable().ajax.reload(null, false);
            setTimeout(function () { window.location.reload(); }, 900);
        }).fail(function (xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Imeshindikana kubadilisha hali.');
            $btn.prop('disabled', false);
        });
    });

    function resetWithdrawForm() {
        $('#withdraw-storage-errors').addClass('d-none').empty();
        $('#withdraw_balance_id').val('');
        $('#withdraw_quantity, #withdraw_price').val('');
        $('#withdraw_reason').val('');
        $('#withdraw_notes').val('');
        $('#withdraw_customer_name, #withdraw_item_name, #withdraw_balance_display').text('—');
        $('#withdraw_price_wrapper').addClass('d-none');
        $('#withdraw_total_preview').text('0.00');
    }

    function updateWithdrawTotalPreview() {
        const qty = parseFloat($('#withdraw_quantity').val()) || 0;
        const price = parseFloat($('#withdraw_price').val()) || 0;
        $('#withdraw_total_preview').text((qty * price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    function toggleWithdrawPriceField() {
        const isSale = $('#withdraw_reason').val() === 'kuuza';
        $('#withdraw_price_wrapper').toggleClass('d-none', !isSale);
        if (!isSale) {
            $('#withdraw_price').val('');
        }
        updateWithdrawTotalPreview();
    }

    $('#withdraw_reason').on('change', toggleWithdrawPriceField);
    $('#withdraw_quantity, #withdraw_price').on('input', updateWithdrawTotalPreview);

    $(document).on('click', '.btn-withdraw-storage', function () {
        const $btn = $(this);
        resetWithdrawForm();

        const onHand = parseFloat($btn.data('quantity-on-hand')) || 0;
        const unit = $btn.data('unit') || '';

        $('#withdraw_balance_id').val($btn.data('balance-id'));
        $('#withdraw_customer_name').text($btn.data('customer-name'));
        $('#withdraw_item_name').text($btn.data('item-name'));
        $('#withdraw_balance_display').text(onHand.toLocaleString('en-US', { maximumFractionDigits: 2 }) + (unit ? ' ' + unit : ''));
        $('#withdraw_quantity').attr('max', onHand);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('withdrawStorageModal')).show();
    });

    $('#save-withdraw-storage').on('click', function () {
        const $btn = $(this);
        $('#withdraw-storage-errors').addClass('d-none').empty();

        const payload = {
            balance_id: $('#withdraw_balance_id').val(),
            quantity: $('#withdraw_quantity').val(),
            reason: $('#withdraw_reason').val(),
            price: $('#withdraw_reason').val() === 'kuuza' ? $('#withdraw_price').val() : null,
            notes: $('#withdraw_notes').val().trim()
        };

        $btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('inventory.customer-storage.withdraw') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: payload
        }).done(function (res) {
            bootstrap.Modal.getInstance(document.getElementById('withdrawStorageModal')).hide();
            Swal.fire({ icon: 'success', title: 'Imefanikiwa', text: res.message || 'Zao limetolewa.', timer: 2000, showConfirmButton: false });
            $('#balancesTable').DataTable().ajax.reload(null, false);
        }).fail(function (xhr) {
            showValidationErrors('#withdraw-storage-errors', xhr);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    function openFinanceModal(type, $btn) {
        $('#' + type + '-finance-errors').addClass('d-none').empty();
        $('#' + type + '_balance_id').val($btn.data('balance-id'));
        $('#' + type + '_customer_name').text($btn.data('customer-name'));
        $('#' + type + '_item_name').text($btn.data('item-name'));
        $('#' + type + '_sababu').val('');
        $('#' + type + '_kiasi').val('');
        $('#' + type + '_entry_date').val(@json(date('Y-m-d')));
        bootstrap.Modal.getOrCreateInstance(document.getElementById(type + 'FinanceModal')).show();
    }

    $(document).on('click', '.btn-customer-mapato', function () {
        openFinanceModal('mapato', $(this));
    });
    $(document).on('click', '.btn-customer-gharama', function () {
        openFinanceModal('gharama', $(this));
    });
    $(document).on('click', '.btn-customer-malipo', function () {
        openFinanceModal('malipo', $(this));
    });

    function saveFinanceEntry(type, url) {
        const $btn = $('#save-' + type + '-finance');
        $('#' + type + '-finance-errors').addClass('d-none').empty();

        const payload = {
            balance_id: $('#' + type + '_balance_id').val(),
            sababu: $('#' + type + '_sababu').val().trim(),
            kiasi: $('#' + type + '_kiasi').val(),
            entry_date: $('#' + type + '_entry_date').val()
        };

        $btn.prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: payload
        }).done(function (res) {
            bootstrap.Modal.getInstance(document.getElementById(type + 'FinanceModal')).hide();
            Swal.fire({
                icon: 'success',
                title: 'Imefanikiwa',
                text: res.message || 'Imehifadhiwa.',
                timer: 2000,
                showConfirmButton: false
            });
        }).fail(function (xhr) {
            showValidationErrors('#' + type + '-finance-errors', xhr);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    $('#save-mapato-finance').on('click', function () {
        saveFinanceEntry('mapato', @json(route('inventory.customer-storage.mapato.store')));
    });
    $('#save-gharama-finance').on('click', function () {
        saveFinanceEntry('gharama', @json(route('inventory.customer-storage.gharama.store')));
    });
    $('#save-malipo-finance').on('click', function () {
        saveFinanceEntry('malipo', @json(route('inventory.customer-storage.malipo.store')));
    });
});
</script>
@endpush
