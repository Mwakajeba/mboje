@php
    $itemDashboard = $storageReport['itemDashboard'] ?? [];
    $customerLines = $storageReport['customerLines'] ?? [];
    $hasStorageData = ! empty($itemDashboard) || ! empty($customerLines);
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

                    <h6 class="text-uppercase text-muted mb-3">Orodha ya Wateja na Zao Zilizobaki</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
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
                            <tbody>
                                @foreach($customerLines as $index => $line)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $line['customer_name'] }}</strong>
                                    </td>
                                    <td>{{ $line['customer_phone'] ?: '—' }}</td>
                                    <td>
                                        {{ $line['item_name'] }}
                                        @if($line['item_code'])
                                            <br><small class="text-muted">{{ $line['item_code'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">{{ $line['quantity_display'] }}</td>
                                    <td class="text-end text-warning fw-semibold">
                                        {{ number_format($line['mikopo_total'], 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            @if(count($customerLines) > 0)
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">Jumla</th>
                                    <th class="text-end">
                                        {{ number_format($storageReport['grandTotalQuantity'] ?? 0, 0) }}
                                    </th>
                                    <th class="text-end text-warning">
                                        {{ number_format($storageReport['grandTotalMikopo'] ?? 0, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>

                    <p class="text-muted small mt-3 mb-0">
                        <i class="bx bx-info-circle me-1"></i>
                        Gharama/Mkopo ni jumla ya mikopo iliyokopeshwa mteja (si kwa kila zao pekee).
                        Wateja walio na stoo: <strong>{{ $storageReport['customerCount'] ?? 0 }}</strong>.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endcan
