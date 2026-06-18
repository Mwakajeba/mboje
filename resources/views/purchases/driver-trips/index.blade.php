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
@endsection

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
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[4, 'desc']],
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
});
</script>
@endpush
