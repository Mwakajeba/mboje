@props([
    'lines',
    'total',
    'amountLabel' => 'Kiasi',
    'emptyMessage' => 'Hakuna rekodi.',
    'showOpeningBalance' => false,
    'openingBalance' => 0,
    'openingBalanceLabel' => 'Salio la kufungua',
    'totalLabel' => 'Jumla',
    'lineType' => null,
    'canDelete' => false,
    'canManage' => false,
    'sectionDeleteLabel' => null,
])

@php
    $showActions = ($canManage || $canDelete) && $lineType;
@endphp

@if($showActions && $sectionDeleteLabel && $canDelete)
<div class="d-flex justify-content-end mb-2 no-print">
    <button type="button"
            class="btn btn-outline-danger btn-sm btn-delete-report-section"
            data-type="{{ $lineType }}"
            data-label="{{ $sectionDeleteLabel }}">
        <i class="bx bx-trash me-1"></i> Futa {{ $sectionDeleteLabel }} zote
    </button>
</div>
@endif

@if(empty($lines) && ! $showOpeningBalance)
    <p class="text-muted small mb-0">{{ $emptyMessage }}</p>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Maelezo</th>
                    <th class="text-end" style="width: 160px">{{ $amountLabel }}</th>
                    @if($showActions)
                        <th class="text-center no-print" style="width: 88px"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @if($showOpeningBalance)
                    <tr class="table-secondary">
                        <td class="fw-semibold">{{ $openingBalanceLabel }}</td>
                        <td class="text-end fw-semibold">{{ format_currency($openingBalance) }}</td>
                        @if($showActions)
                            <td class="no-print"></td>
                        @endif
                    </tr>
                @endif
                @foreach($lines as $line)
                    <tr data-line-id="{{ $line['id'] ?? '' }}">
                        <td>{{ $line['maelezo'] }}</td>
                        <td class="text-end">{{ format_currency($line['amount']) }}</td>
                        @if($showActions && !empty($line['id']))
                            <td class="text-center no-print text-nowrap">
                                @if($canManage)
                                    @php
                                        $lineEditPayload = json_encode([
                                            'maelezo' => $line['maelezo'],
                                            'amount' => $line['amount'],
                                            'employee_id' => $line['employee_id'] ?? null,
                                            'entry_date' => $line['entry_date'] ?? null,
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT);
                                    @endphp
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm btn-edit-report-line"
                                            data-type="{{ $lineType }}"
                                            data-line-id="{{ $line['id'] }}"
                                            data-payload="{{ $lineEditPayload }}"
                                            title="Hariri">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                @endif
                                @if($canDelete)
                                    <button type="button"
                                            class="btn btn-outline-danger btn-sm btn-delete-report-line"
                                            data-type="{{ $lineType }}"
                                            data-line-id="{{ $line['id'] }}"
                                            title="Futa">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                @endif
                            </td>
                        @elseif($showActions)
                            <td class="no-print"></td>
                        @endif
                    </tr>
                @endforeach
                @if($showOpeningBalance && empty($lines))
                    <tr>
                        <td colspan="{{ $showActions ? 3 : 2 }}" class="text-muted small">Hakuna mauzo mapya kwa siku hii.</td>
                    </tr>
                @endif
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td class="text-end fw-bold">{{ $totalLabel }}</td>
                    <td class="text-end fw-bold">{{ format_currency($total) }}</td>
                    @if($showActions)
                        <td class="no-print"></td>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>
@endif
