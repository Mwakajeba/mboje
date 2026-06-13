@extends('layouts.main')

@section('title', 'Uhifadhi wa Wateja')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Uhifadhi wa Wateja', 'url' => '#', 'icon' => 'bx bx-user-pin']
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
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <label for="inventory_item_id" class="form-label">Zao <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="inventory_item_id" id="inventory_item_id" class="form-select select2-single @error('inventory_item_id') is-invalid @enderror" required>
                                    <option value="">— Chagua Zao —</option>
                                    @foreach($items as $item)
                                    <option value="{{ $item->id }}" {{ (string) old('inventory_item_id') === (string) $item->id ? 'selected' : '' }}>
                                        {{ $item->name }} ({{ $item->code }})
                                    </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-success" id="btn-add-item" title="Ongeza zao jipya">
                                    <i class="bx bx-plus"></i>
                                </button>
                            </div>
                            @error('inventory_item_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                    <h5 class="mb-0">Salio la Zao la Wateja</h5>
                    <a href="{{ route('inventory.customer-storage.history') }}" class="btn btn-outline-info btn-sm">
                        <i class="bx bx-history me-1"></i> Angalia Historia Yote
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="balancesTable" class="table table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Mteja</th>
                                <th>Simu</th>
                                <th>Zao</th>
                                <th>Idadi</th>
                                <th>Kifurushi</th>
                                <th>Historia</th>
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
                    <p class="text-muted small mb-1">Usichague chochote kwa <strong>matawi yote</strong>.</p>
                    <select id="quick_item_branches" class="form-select quick-item-select2" multiple data-placeholder="Matawi yote">
                        @foreach($assignableBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
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
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Chagua...',
        allowClear: true
    });

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
        $('#quick_item_branches').val(null).trigger('change');
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
            url: "{{ route('inventory.customer-storage.index') }}",
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
            { data: 'quantity_display', name: 'quantity_on_hand' },
            { data: 'package_display', name: 'package_display', orderable: false, searchable: false },
            { data: 'history_link', name: 'history_link', orderable: false, searchable: false }
        ],
        order: [[3, 'desc']],
        pageLength: 25,
        language: {
            processing: 'Inapakia...',
            emptyTable: 'Hakuna zao la wateja lililohifadhiwa.',
            zeroRecords: 'Hakuna rekodi zilizopatikana.'
        }
    });
});
</script>
@endpush
