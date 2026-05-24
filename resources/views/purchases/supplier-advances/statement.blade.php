@extends('layouts.main')

@section('title', 'Hesabu za Machinga — '.$supplier->name)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
                ['label' => 'Malipo ya Awali', 'url' => route('purchases.supplier-advances.index'), 'icon' => 'bx bx-wallet-alt'],
                ['label' => 'Hesabu', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Chapisha
                </button>
                <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">Rudi</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card radius-10 statement-card mb-4">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h4 class="mb-1">Hesabu za Machinga</h4>
                    <p class="mb-0 fs-5 fw-semibold">{{ $supplier->name }}</p>
                    @if($supplier->tin_number)
                        <small class="text-muted">TIN: {{ $supplier->tin_number }}</small>
                    @endif
                    @if(!empty($period))
                        <p class="text-muted small mb-0 mt-2">
                            Kipindi: <strong>{{ $period['from'] }}</strong> hadi <strong>{{ $period['to'] }}</strong>
                        </p>
                    @endif
                    <p class="text-muted small mb-0 mt-1">Imetengenezwa {{ now()->format('Y-m-d H:i') }}</p>
                </div>

                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 text-center mb-4 border rounded py-3 bg-light g-2">
                    @if(!empty($period) && isset($totals['opening_balance']))
                        <div class="col">
                            <div class="text-muted small">Salio la kufungua</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['opening_balance']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Malipo/Mauzo (kipindi)</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['advances']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Matumizi/Manunuzi (kipindi)</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['applied']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Salio la kufunga/Baki</div>
                            <div class="fs-5 fw-bold text-primary">{{ format_currency($totals['closing_balance'] ?? $totals['balance']) }}</div>
                        </div>
                    @else
                        <div class="col">
                            <div class="text-muted small">Jumla ya malipo ya awali</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['advances']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Matumizi/Manunuzi</div>
                            <div class="fs-5 fw-bold">{{ format_currency($totals['applied']) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Salio</div>
                            <div class="fs-5 fw-bold text-primary">{{ format_currency($totals['balance']) }}</div>
                        </div>
                    @endif
                </div>

                @if(!empty($openingRow))
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr class="table-secondary fw-semibold">
                                <td style="width: 120px">{{ $openingRow['date']->format('Y-m-d') }}</td>
                                <td>{{ $openingRow['description'] }}</td>
                                <td class="text-end" style="width: 160px">{{ format_currency($openingRow['balance']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @elseif($openingBalance !== null && empty($period))
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr class="table-secondary fw-semibold">
                                <td colspan="2">Salio la kufungua</td>
                                <td class="text-end">{{ format_currency($openingBalance) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif

                <h6 class="text-primary mb-2"><i class="bx bx-wallet me-1"></i> Malipo/Mauzo</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Maelezo</th>
                                <th>Aliyeingiza</th>
                                <th class="text-end">Malipo/Mauzo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($malipoLines as $line)
                            <tr>
                                <td>{{ $line['date']->format('Y-m-d') }}</td>
                                <td>{{ $line['description'] }}</td>
                                <td>{{ $line['performed_by'] ?? '—' }}</td>
                                <td class="text-end">{{ format_currency($line['paid']) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Hakuna Malipo/Mauzo katika kipindi hiki.</td>
                            </tr>
                            @endforelse
                            @if($malipoLines->isNotEmpty())
                            <tr class="table-light fw-semibold">
                                <td colspan="3" class="text-end">Jumla ya Malipo/Mauzo</td>
                                <td class="text-end">{{ format_currency($malipoTotal) }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <h6 class="text-primary mb-2"><i class="bx bx-receipt me-1"></i> Matumizi / Manunuzi</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tarehe</th>
                                <th>Maelezo</th>
                                <th>Aliyeingiza</th>
                                <th class="text-end">Matumizi</th>
                                @if($canDeleteStatementItems)
                                <th class="text-end no-print text-nowrap" style="width: 90px">Vitendo</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php $expenseDeleteShown = []; @endphp
                            @forelse($matumiziLines as $line)
                            <tr>
                                <td>{{ $line['date']->format('Y-m-d') }}</td>
                                <td>{{ $line['description'] }}</td>
                                <td>{{ $line['performed_by'] ?? '—' }}</td>
                                <td class="text-end">{{ format_currency($line['deducted']) }}</td>
                                @if($canDeleteStatementItems)
                                <td class="text-end no-print">
                                    @if(!empty($line['can_delete']) && !empty($line['entry_id']))
                                        <form method="post" action="{{ route('purchases.supplier-advances.statement.manunuzi.destroy', ['encodedSupplierId' => $encodedSupplierId, 'encodedEntryId' => Vinkla\Hashids\Facades\Hashids::encode($line['entry_id'])]) }}" class="d-inline" onsubmit="return confirm('Una uhakika unataka kufuta manunuzi haya?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Futa">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $canDeleteStatementItems ? 5 : 4 }}" class="text-center text-muted py-3">Hakuna matumizi/manunuzi katika kipindi hiki.</td>
                            </tr>
                            @endforelse
                            @if($matumiziLines->isNotEmpty())
                            <tr class="table-light fw-semibold">
                                <td colspan="3" class="text-end">Jumla ya Matumizi / Manunuzi</td>
                                <td class="text-end">{{ format_currency($matumiziTotal) }}</td>
                                @if($canDeleteStatementItems)<td class="no-print"></td>@endif
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if(!empty($closingRow))
                <div class="table-responsive mb-0">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr class="table-secondary fw-semibold">
                                <td style="width: 120px">{{ $closingRow['date']->format('Y-m-d') }}</td>
                                <td>{{ $closingRow['description'] }}</td>
                                <td class="text-end" style="width: 160px">{{ format_currency($closingRow['balance']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="card radius-10 statement-card">
            <div class="card-body">
                <h6 class="text-primary mb-2">
                    <i class="bx bx-package me-1"></i> Stoo
                </h6>
                <p class="text-muted small mb-3">Stoo iliyoingizwa kwa msambazaji huyu (Weka stoo).</p>
                @forelse($stockRecords as $record)
                <div class="border rounded p-3 mb-3 position-relative">
                    @if($canDeleteStatementItems)
                    <div class="no-print position-absolute top-0 end-0 m-2">
                        <form method="post" action="{{ route('purchases.supplier-advances.statement.stock.destroy', ['encodedSupplierId' => $encodedSupplierId, 'encodedStockRecordId' => Vinkla\Hashids\Facades\Hashids::encode($record->id)]) }}" class="d-inline" onsubmit="return confirm('Una uhakika unataka kufuta stoo hii?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Futa stoo">
                                <i class="bx bx-trash me-1"></i> Futa
                            </button>
                        </form>
                    </div>
                    @endif
                    <h6 class="mb-1 fw-bold">{{ $record->bidhaa }}</h6>
                    <p class="text-muted small mb-2">
                        Tarehe: <strong>{{ $record->entry_date->format('Y-m-d') }}</strong>
                        @if($record->user)
                            · Aliyeingiza: {{ $record->user->name }}
                        @endif
                    </p>
                    @if(filled($record->description))
                    <p class="small mb-2">{{ $record->description }}</p>
                    @endif
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Aina ya Muamala</th>
                                <th class="text-end">Idadi</th>
                                <th class="text-end">Thamani</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($record->lines as $line)
                            <tr>
                                <td>{{ $line->transactionTypeLabel() }}</td>
                                <td class="text-end">{{ $line->idadi }}</td>
                                <td class="text-end fw-semibold">{{ format_currency((float) $line->thamani) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @empty
                <p class="text-center text-muted py-4 mb-0">Hakuna stoo iliyoingizwa kwa msambazaji huyu.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('styles')
<style media="print">
    .no-print, .sidebar-wrapper, .topbar, .page-footer, .breadcrumb { display: none !important; }
    .page-wrapper { margin: 0 !important; padding: 0 !important; }
    .statement-card { border: none !important; box-shadow: none !important; }
    .statement-card { page-break-inside: avoid; }
</style>
@endpush
@endsection
