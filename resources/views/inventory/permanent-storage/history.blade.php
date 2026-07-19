@extends('layouts.main')

@section('title', 'Historia ya Stoo ya Kudumu')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Stoo ya Kudumu', 'url' => route('inventory.permanent-storage.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Historia', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">Historia ya Zao</h5>
                        <p class="text-muted mb-0 small">
                            <span class="badge bg-success me-1">Uletaji</span>
                            <span class="badge bg-danger">Utoaji</span>
                        </p>
                        @if($customer)
                        <p class="text-muted mb-0 small">Mteja: <strong>{{ $customer->name }}</strong></p>
                        @endif
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('inventory.permanent-storage.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-arrow-back me-1"></i> Rudi
                        </a>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="filter_customer_id" class="form-label">Chuja kwa Mteja</label>
                        <select id="filter_customer_id" class="form-select select2-single">
                            <option value="">Wateja Wote</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ (string) ($customerId ?? '') === (string) $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="historyTable" class="table table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Tarehe</th>
                                <th>Aina</th>
                                <th>Mteja</th>
                                <th>Zao</th>
                                <th>Idadi</th>
                                <th>Sababu</th>
                                <th>Kifurushi</th>
                                <th>Aliyeingiza</th>
                                <th>Imerekodiwa</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(function () {
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Chagua...',
        allowClear: true
    });

    const table = $('#historyTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('inventory.permanent-storage.history') }}",
            type: 'GET',
            data: function (d) {
                d.customer_id = $('#filter_customer_id').val();
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            error: function (xhr) {
                console.error('Historia ya zao — hitilafu ya DataTables:', xhr.status, xhr.responseText);
            }
        },
        columns: [
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'type_badge', name: 'movement_type', orderable: false, searchable: false },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'item_name', name: 'item_name' },
            { data: 'quantity_display', name: 'quantity', orderable: false, searchable: false },
            { data: 'reason_display', name: 'reason', orderable: false, searchable: false },
            { data: 'package_display', name: 'package_display', orderable: false, searchable: false },
            { data: 'recorded_by', name: 'recorded_by', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' }
        ],
        order: [[0, 'desc'], [8, 'desc']],
        pageLength: 25,
        language: {
            processing: 'Inapakia...',
            emptyTable: 'Hakuna historia ya zao.',
            zeroRecords: 'Hakuna rekodi zilizopatikana.'
        }
    });

    $('#filter_customer_id').on('change', function () {
        table.ajax.reload();
    });
});
</script>
@endpush
