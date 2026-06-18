@extends('layouts.main')

@section('title', 'Safari na Madereva')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Safari na Madereva', 'url' => '#', 'icon' => 'bx bx-car']
        ]" />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
        </div>
        @endif

        @can('record purchase payment')
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-plus-circle me-2"></i>Sajili Safari Mpya</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('purchases.driver-trips.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4 col-lg-3">
                            <label for="trip_name" class="form-label">Jina la Safari <span class="text-danger">*</span></label>
                            <input type="text" name="trip_name" id="trip_name" class="form-control @error('trip_name') is-invalid @enderror"
                                   value="{{ old('trip_name') }}" required maxlength="255" placeholder="Mf. Safari DSM - Arusha">
                            @error('trip_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <label for="driver_name" class="form-label">Dereva <span class="text-danger">*</span></label>
                            <input type="text" name="driver_name" id="driver_name" class="form-control @error('driver_name') is-invalid @enderror"
                                   value="{{ old('driver_name') }}" required maxlength="255" placeholder="Jina la dereva">
                            @error('driver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 col-lg-3">
                            <label for="vehicle_info" class="form-label">Taarifa za Gari</label>
                            <input type="text" name="vehicle_info" id="vehicle_info" class="form-control @error('vehicle_info') is-invalid @enderror"
                                   value="{{ old('vehicle_info') }}" maxlength="2000" placeholder="Nambari ya gari, aina, n.k.">
                            @error('vehicle_info')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <label for="trip_price" class="form-label">Bei ya Safari <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="trip_price" id="trip_price"
                                   class="form-control @error('trip_price') is-invalid @enderror"
                                   value="{{ old('trip_price', '0') }}" required>
                            @error('trip_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <label for="trip_date" class="form-label">Tarehe <span class="text-danger">*</span></label>
                            <input type="date" name="trip_date" id="trip_date"
                                   class="form-control @error('trip_date') is-invalid @enderror"
                                   value="{{ old('trip_date', date('Y-m-d')) }}" required>
                            @error('trip_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 col-lg-2">
                            <label for="status" class="form-label">Hali</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach(\App\Models\Purchase\DriverTrip::statusOptions() as $value => $label)
                                <option value="{{ $value }}" {{ old('status', \App\Models\Purchase\DriverTrip::STATUS_ACTIVE) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 col-lg-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bx bx-save me-1"></i> Hifadhi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Orodha ya Safari</h5>
                <div class="table-responsive">
                    <table id="driverTripsTable" class="table table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Jina la Safari</th>
                                <th>Dereva</th>
                                <th>Gari</th>
                                <th>Bei</th>
                                <th>Tarehe</th>
                                <th>Hali</th>
                                <th class="text-center">Vitendo</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@include('purchases.driver-trips.partials.trip-lines-modal', [
    'modalId' => 'tripMapatoModal',
    'modalTitle' => 'Ingiza Mapato',
    'modalIcon' => 'bx bx-wallet text-success',
    'formId' => 'trip-mapato-form',
    'errorsId' => 'trip-mapato-errors',
    'submitId' => 'trip-mapato-submit',
    'tripIdInputId' => 'mapato_trip_id',
    'tripNameDisplayId' => 'mapato_trip_name',
    'entryDateId' => 'mapato_entry_date',
    'linesBodyId' => 'mapato-lines-body',
    'linesTotalId' => 'mapato-lines-total',
    'addLineBtnId' => 'mapato-add-line',
    'linesLabel' => 'Mistari ya mapato',
    'linePlaceholder' => 'Maelezo ya mapato',
    'submitBtnClass' => 'btn-success',
])

@include('purchases.driver-trips.partials.trip-lines-modal', [
    'modalId' => 'tripMatumiziModal',
    'modalTitle' => 'Ingiza Matumizi',
    'modalIcon' => 'bx bx-receipt text-warning',
    'formId' => 'trip-matumizi-form',
    'errorsId' => 'trip-matumizi-errors',
    'submitId' => 'trip-matumizi-submit',
    'tripIdInputId' => 'matumizi_trip_id',
    'tripNameDisplayId' => 'matumizi_trip_name',
    'entryDateId' => 'matumizi_entry_date',
    'linesBodyId' => 'matumizi-lines-body',
    'linesTotalId' => 'matumizi-lines-total',
    'addLineBtnId' => 'matumizi-add-line',
    'linesLabel' => 'Mistari ya matumizi',
    'linePlaceholder' => 'Maelezo ya matumizi',
    'submitBtnClass' => 'btn-warning',
])

@can('record purchase payment')
<div class="modal fade" id="editTripModal" tabindex="-1" aria-labelledby="editTripModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTripModalLabel">
                    <i class="bx bx-edit me-1"></i> Badili Safari
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Funga"></button>
            </div>
            <form id="edit-trip-form" method="POST" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div id="edit-trip-errors" class="alert alert-danger d-none small py-2"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_trip_name" class="form-label">Jina la Safari <span class="text-danger">*</span></label>
                            <input type="text" name="trip_name" id="edit_trip_name" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_driver_name" class="form-label">Dereva <span class="text-danger">*</span></label>
                            <input type="text" name="driver_name" id="edit_driver_name" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-12">
                            <label for="edit_vehicle_info" class="form-label">Taarifa za Gari</label>
                            <input type="text" name="vehicle_info" id="edit_vehicle_info" class="form-control" maxlength="2000">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_trip_price" class="form-label">Bei ya Safari <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="trip_price" id="edit_trip_price" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_trip_date" class="form-label">Tarehe <span class="text-danger">*</span></label>
                            <input type="date" name="trip_date" id="edit_trip_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label">Hali</label>
                            <select name="status" id="edit_status" class="form-select">
                                @foreach(\App\Models\Purchase\DriverTrip::statusOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Ghairi</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="edit-trip-submit">
                        <i class="bx bx-save me-1"></i> Hifadhi Mabadiliko
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('styles')
<style>
    #driverTripsTable tr.trip-completed td:not(:last-child) {
        text-decoration: line-through;
        text-decoration-color: #198754;
        text-decoration-thickness: 2px;
        color: #495057;
    }
    #driverTripsTable tr.trip-completed td:not(:last-child) .badge {
        text-decoration: none;
    }
</style>
@endpush

@push('scripts')
@include('purchases.driver-trips.partials.trip-lines-form-init')

<script nonce="{{ $cspNonce ?? '' }}">
$(function () {
    window.driverTripsTable = $('#driverTripsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('purchases.driver-trips.index') }}",
        columns: [
            { data: 'trip_name', name: 'trip_name' },
            { data: 'driver_name', name: 'driver_name' },
            { data: 'vehicle_info_short', name: 'vehicle_info', orderable: false },
            { data: 'trip_price_display', name: 'trip_price', className: 'text-end' },
            { data: 'trip_date_display', name: 'trip_date' },
            { data: 'status_display', name: 'status', className: 'text-center', orderable: true, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[5, 'desc']],
        pageLength: 25,
        language: {
            processing: 'Inapakia...',
            emptyTable: 'Hakuna safari zilizosajiliwa.',
            zeroRecords: 'Hakuna safari zilizopatikana.'
        }
    });

    initTripLinesForm({
        modalId: 'tripMapatoModal',
        form: '#trip-mapato-form',
        errors: '#trip-mapato-errors',
        submit: '#trip-mapato-submit',
        tripIdInput: '#mapato_trip_id',
        tripNameDisplay: '#mapato_trip_name',
        entryDate: '#mapato_entry_date',
        linesBody: '#mapato-lines-body',
        linesTotal: '#mapato-lines-total',
        addLineBtn: '#mapato-add-line',
        openBtnSelector: '.btn-trip-mapato',
        storeUrl: @json(route('purchases.driver-trips.mapato.store')),
        linePlaceholder: 'Maelezo ya mapato',
        successDefault: 'Mapato yamehifadhiwa.',
        reloadTable: true
    });

    initTripLinesForm({
        modalId: 'tripMatumiziModal',
        form: '#trip-matumizi-form',
        errors: '#trip-matumizi-errors',
        submit: '#trip-matumizi-submit',
        tripIdInput: '#matumizi_trip_id',
        tripNameDisplay: '#matumizi_trip_name',
        entryDate: '#matumizi_entry_date',
        linesBody: '#matumizi-lines-body',
        linesTotal: '#matumizi-lines-total',
        addLineBtn: '#matumizi-add-line',
        openBtnSelector: '.btn-trip-matumizi',
        storeUrl: @json(route('purchases.driver-trips.matumizi.store')),
        linePlaceholder: 'Maelezo ya matumizi',
        successDefault: 'Matumizi yamehifadhiwa.',
        reloadTable: true
    });

    var editTripModalEl = document.getElementById('editTripModal');
    if (editTripModalEl) {
        var editTripModal = bootstrap.Modal.getOrCreateInstance(editTripModalEl);
        var editTripUrlTemplate = @json(route('purchases.driver-trips.update', ['trip' => 0]));
        var deleteTripUrlTemplate = @json(route('purchases.driver-trips.destroy', ['trip' => 0]));

        function tripUrl(template, tripId) {
            return template.replace(/\/0$/, '/' + tripId);
        }

        function showPageAlert(message, type) {
            var $alert = $('<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">'
                + $('<div>').text(message).html()
                + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
            $('.page-content').prepend($alert);
        }

        $(document).on('click', '.btn-trip-edit', function () {
            var $btn = $(this);
            $('#edit-trip-errors').addClass('d-none').empty();
            $('#edit-trip-form').attr('action', tripUrl(editTripUrlTemplate, $btn.data('trip-id')));
            $('#edit_trip_name').val($btn.data('trip-name') || '');
            $('#edit_driver_name').val($btn.data('driver-name') || '');
            $('#edit_vehicle_info').val($btn.data('vehicle-info') || '');
            $('#edit_trip_price').val($btn.data('trip-price') || '0');
            $('#edit_trip_date').val($btn.data('trip-date') || '');
            $('#edit_status').val($btn.data('trip-status') || 'hai');
            editTripModal.show();
        });

        $('#edit-trip-form').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $errors = $('#edit-trip-errors');
            var $submit = $('#edit-trip-submit');

            $errors.addClass('d-none').empty();
            $submit.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            }).done(function (res) {
                editTripModal.hide();
                showPageAlert((res && res.message) ? res.message : 'Safari imesasishwa.', 'success');
                if (window.driverTripsTable) {
                    window.driverTripsTable.ajax.reload(null, false);
                }
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var list = [];
                    $.each(xhr.responseJSON.errors, function (_, msgs) {
                        list = list.concat(msgs);
                    });
                    $errors.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>' + list.map(function (m) {
                        return $('<div>').text(m).html();
                    }).join('</li><li>') + '</li></ul>');
                } else {
                    $errors.removeClass('d-none').text('Imeshindikana kusasisha safari. Jaribu tena.');
                }
            }).always(function () {
                $submit.prop('disabled', false);
            });
        });

        $(document).on('click', '.btn-trip-delete', function () {
            var $btn = $(this);
            var tripId = $btn.data('trip-id');
            var tripName = $btn.data('trip-name') || 'safari hii';

            var runDelete = function () {
                $.ajax({
                    url: tripUrl(deleteTripUrlTemplate, tripId),
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' },
                    headers: { 'Accept': 'application/json' }
                }).done(function (res) {
                    showPageAlert((res && res.message) ? res.message : 'Safari imefutwa.', 'success');
                    if (window.driverTripsTable) {
                        window.driverTripsTable.ajax.reload(null, false);
                    }
                }).fail(function () {
                    showPageAlert('Imeshindikana kufuta safari. Jaribu tena.', 'danger');
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Futa safari?',
                    text: 'Una uhakika unataka kufuta "' + tripName + '"? Mapato na matumizi yote ya safari hii pia yatafutwa.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Ndiyo, futa',
                    cancelButtonText: 'Ghairi'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        runDelete();
                    }
                });
            } else if (confirm('Una uhakika unataka kufuta "' + tripName + '"?')) {
                runDelete();
            }
        });
    }
});
</script>
@endpush
