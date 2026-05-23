@props([
    'lines',
    'total',
    'amountLabel' => 'Kiasi',
    'emptyMessage' => 'Hakuna rekodi.',
])

@if(empty($lines))
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
                @foreach($lines as $line)
                    <tr>
                        <td>{{ $line['maelezo'] }}</td>
                        <td class="text-end">{{ format_currency($line['amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td class="text-end fw-bold">Jumla</td>
                    <td class="text-end fw-bold">{{ format_currency($total) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
