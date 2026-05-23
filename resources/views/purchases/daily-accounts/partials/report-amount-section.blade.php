@props([
    'lines',
    'total',
    'amountLabel' => 'Kiasi',
    'emptyMessage' => 'Hakuna rekodi.',
    'showOpeningBalance' => false,
    'openingBalance' => 0,
    'openingBalanceLabel' => 'Salio la kufungua',
    'totalLabel' => 'Jumla',
])

@if(empty($lines) && ! $showOpeningBalance)
    <p class="text-muted small mb-0">{{ $emptyMessage }}</p>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Maelezo</th>
                    <th class="text-end" style="width: 160px">{{ $amountLabel }}</th>
                </tr>
            </thead>
            <tbody>
                @if($showOpeningBalance)
                    <tr class="table-secondary">
                        <td class="fw-semibold">{{ $openingBalanceLabel }}</td>
                        <td class="text-end fw-semibold">{{ format_currency($openingBalance) }}</td>
                    </tr>
                @endif
                @foreach($lines as $line)
                    <tr>
                        <td>{{ $line['maelezo'] }}</td>
                        <td class="text-end">{{ format_currency($line['amount']) }}</td>
                    </tr>
                @endforeach
                @if($showOpeningBalance && empty($lines))
                    <tr>
                        <td colspan="2" class="text-muted small">Hakuna mauzo mapya kwa siku hii.</td>
                    </tr>
                @endif
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td class="text-end fw-bold">{{ $totalLabel }}</td>
                    <td class="text-end fw-bold">{{ format_currency($total) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
