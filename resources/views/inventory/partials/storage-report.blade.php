@php
    $itemDashboard = $storageReport['itemDashboard'] ?? [];
    $hasStorageData = ! empty($storageReport['hasData']);
@endphp

@can('manage inventory items')
<div class="row mt-2" id="ripoti-stoo">
    <div class="col-12">
        <div class="card border-info">
            <div class="card-header bg-info text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">
                    <i class="bx bx-archive me-2"></i>Ripoti ya Stoo ya Wateja
                </h5>
                <a href="{{ route('inventory.customer-storage.index') }}" class="btn btn-sm btn-light">
                    <i class="bx bx-archive-in me-1"></i> Simamia Uhifadhi
                </a>
            </div>
            <div class="card-body">
                @if(! $hasStorageData)
                    <p class="text-muted mb-0 text-center py-3">
                        <i class="bx bx-package fs-3 d-block mb-2"></i>
                        Hakuna zao lililobaki stoo kwa sasa.
                    </p>
                @else
                    @if(! empty($itemDashboard))
                    <h6 class="text-uppercase text-muted mb-3">Muhtasari wa Zao Stoo</h6>
                    <div class="row mb-4">
                        @foreach($itemDashboard as $itemRow)
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="card radius-10 border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <p class="mb-0 text-secondary text-truncate" title="{{ $itemRow['item_name'] }}">
                                                {{ $itemRow['item_name'] }}
                                            </p>
                                            <h4 class="my-1 text-info">
                                                {{ number_format($itemRow['total_quantity'], 0) }}
                                                @if($itemRow['unit'])
                                                    <small class="fs-6 text-muted">{{ $itemRow['unit'] }}</small>
                                                @endif
                                            </h4>
                                            <p class="mb-0 font-13 text-muted">
                                                <i class="bx bx-user me-1"></i>
                                                Wateja {{ $itemRow['customer_count'] }}
                                                @if($itemRow['item_code'])
                                                    · {{ $itemRow['item_code'] }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="widgets-icons bg-light-info text-info ms-2">
                                            <i class="bx bx-package"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <h6 class="text-uppercase text-muted mb-3">Orodha ya Wateja na Zao Zilizobaki</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 w-100" id="storageReportCustomersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Jina la Mteja</th>
                                    <th>Simu</th>
                                    <th>Zao</th>
                                    <th class="text-end">Idadi Iliyobaki</th>
                                    <th class="text-end">Gharama/Mkopo (Jumla)</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <p class="text-muted small mt-3 mb-0">
                        <i class="bx bx-info-circle me-1"></i>
                        Gharama/Mkopo ni jumla ya mikopo iliyokopeshwa mteja (si kwa kila zao pekee).
                        Wateja walio na stoo: <strong>{{ $storageReport['customerCount'] ?? 0 }}</strong>.
                        Jumla idadi: <strong>{{ number_format($storageReport['grandTotalQuantity'] ?? 0, 0) }}</strong>.
                        Jumla mkopo: <strong class="text-warning">{{ number_format($storageReport['grandTotalMikopo'] ?? 0, 2) }}</strong>.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($hasStorageData)
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    $('#storageReportCustomersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('inventory.storage-report.datatable')),
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            error: function (xhr) {
                console.error('Ripoti ya stoo — hitilafu ya DataTables:', xhr.status, xhr.responseText);
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'customer_phone', name: 'customer_phone', orderable: false, searchable: false },
            { data: 'item_display', name: 'item_display' },
            { data: 'quantity_display', name: 'quantity_on_hand', className: 'text-end' },
            { data: 'mikopo_total', name: 'mikopo_total', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[1, 'asc'], [3, 'asc']],
        pageLength: 25,
        language: {
            processing: 'Inapakia...',
            search: 'Tafuta:',
            lengthMenu: 'Onyesha _MENU_ mistari',
            info: 'Inaonyesha _START_ hadi _END_ kati ya _TOTAL_ mistari',
            infoEmpty: 'Hakuna mistari',
            zeroRecords: 'Hakuna rekodi zilizopatikana',
            emptyTable: 'Hakuna zao lililobaki stoo.',
            paginate: {
                first: 'Kwanza',
                last: 'Mwisho',
                next: 'Ijayo',
                previous: 'Iliyotangulia'
            }
        }
    });
});
</script>
@endpush
@endif
@endcan
