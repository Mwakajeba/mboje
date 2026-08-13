@if(!empty($balanceSummary) || isset($summaryTotalSalio) || isset($summaryTotalMapato) || isset($summaryTotalGharama))
<div class="card mb-4 {{ $borderClass ?? 'border-secondary' }}">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="bx bx-bar-chart-alt-2 me-2"></i>Muhtasari wa Mazao Yaliyohifadhiwa (Salio)
        </h5>
    </div>
    <div class="card-body">
        <div class="row row-cols-1 row-cols-md-3 g-3 mb-3">
            <div class="col">
                <div class="border rounded p-3 h-100 bg-light text-center">
                    <div class="text-muted small text-uppercase mb-1">Jumla ya Mapato</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($summaryTotalMapato ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="col">
                <div class="border rounded p-3 h-100 bg-light text-center">
                    <div class="text-muted small text-uppercase mb-1">Jumla ya Matumizi</div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format($summaryTotalGharama ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="col">
                <div class="border rounded p-3 h-100 bg-light text-center">
                    <div class="text-muted small text-uppercase mb-1">Salio (Mapato − Matumizi − Malipo)</div>
                    <div class="fs-4 fw-bold {{ ($summaryTotalSalio ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($summaryTotalSalio ?? 0, 2) }}
                    </div>
                    @if(isset($summaryTotalMalipo))
                    <div class="text-muted small mt-1">Malipo: {{ number_format($summaryTotalMalipo, 2) }}</div>
                    @endif
                </div>
            </div>
        </div>
        @if(!empty($balanceSummary))
        <div class="row">
            @foreach($balanceSummary as $row)
            <div class="col-md-4 col-lg-3 mb-3">
                <div class="card radius-10 border h-100 shadow-sm">
                    <div class="card-body">
                        <p class="mb-1 text-secondary text-truncate" title="{{ $row['item_name'] }}">
                            <strong>{{ $row['item_name'] }}</strong>
                            @if($row['item_code'])
                                <small class="text-muted">({{ $row['item_code'] }})</small>
                            @endif
                        </p>
                        <h4 class="my-2 {{ $accentClass ?? 'text-dark' }}">
                            {{ $row['summary_display'] }}
                        </h4>
                        <div class="small text-muted">
                            <div>
                                <i class="bx bx-cube me-1"></i>
                                Idadi: <strong>{{ $row['quantity_display'] }}</strong>
                            </div>
                            <div>
                                <i class="bx bx-package me-1"></i>
                                Vifurushi: <strong>{{ $row['package_display'] }}</strong>
                            </div>
                            <div>
                                <i class="bx bx-receipt me-1"></i>
                                Matumizi: <strong class="text-danger">{{ number_format($row['gharama_total'] ?? 0, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endif
