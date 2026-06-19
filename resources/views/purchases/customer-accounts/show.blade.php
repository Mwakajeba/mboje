@extends('layouts.main')

@section('title', 'Ripoti — ' . $customer->name)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3 no-print">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
                ['label' => 'Hesabu za Wateja/Wakulima', 'url' => route('purchases.customer-accounts.index'), 'icon' => 'bx bx-user-circle'],
                ['label' => 'Ripoti', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Chapisha
                </button>
                <a href="{{ route('customers.show', $encodedCustomerId) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bx bx-id-card me-1"></i> Wasifu kamili
                </a>
                <a href="{{ route('purchases.customer-accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i> Rudi nyuma
                </a>
            </div>
        </div>

        <div class="card radius-10">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h5 class="mb-1 text-uppercase">Ripoti ya Hesabu za Wateja/Wakulima</h5>
                    <p class="mb-0"><strong>Mteja:</strong> {{ $customer->name }}
                        @if($customer->phone) · {{ $customer->phone }} @endif
                        @if($customer->customerNo) · {{ $customer->customerNo }} @endif
                    </p>
                    <p class="mb-0 text-muted"><strong>Tarehe:</strong> {{ $entry_date_formatted }}</p>
                </div>

                {{-- Mauzo --}}
                <h6 class="text-success text-uppercase border-bottom pb-2 mb-3">
                    <i class="bx bx-cart me-1"></i> Mauzo ya Zao
                </h6>
                @include('purchases.daily-accounts.partials.report-amount-section', [
                    'lines' => $mauzo_lines,
                    'total' => $jumla_mauzo,
                    'amountLabel' => 'Kiasi',
                    'emptyMessage' => 'Hakuna mauzo kwa siku hii.',
                    'showOpeningBalance' => true,
                    'openingBalance' => $opening_mauzo,
                    'openingBalanceLabel' => 'Salio la mauzo la nyuma (mpaka tarehe '.$previous_date_formatted.')',
                    'totalLabel' => 'Jumla ya mauzo',
                    'noNewLinesMessage' => 'Hakuna mauzo mapya kwa siku hii.',
                ])

                {{-- Mikopo mipya --}}
                <h6 class="text-warning text-uppercase border-bottom pb-2 mb-3 mt-4">
                    <i class="bx bxs-wallet me-1"></i> Mikopo Mipya
                </h6>
                @include('purchases.daily-accounts.partials.report-amount-section', [
                    'lines' => $mikopo_lines,
                    'total' => $mikopo_total,
                    'amountLabel' => 'Kiasi',
                    'emptyMessage' => 'Hakuna mikopo mipya kwa siku hii.',
                ])

                {{-- Malipo ya mikopo --}}
                <h6 class="text-danger text-uppercase border-bottom pb-2 mb-3 mt-4">
                    <i class="bx bx-money me-1"></i> Malipo ya Mikopo
                </h6>
                @include('purchases.daily-accounts.partials.report-amount-section', [
                    'lines' => $malipo_lines,
                    'total' => $malipo_total,
                    'amountLabel' => 'Kiasi',
                    'emptyMessage' => 'Hakuna malipo ya mikopo kwa siku hii.',
                ])

                {{-- Muhtasari wa salio --}}
                <div class="card bg-light border-0 mt-4 mb-4">
                    <div class="card-body py-3">
                        <div class="row g-2 small">
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Mauzo ya nyuma (mpaka {{ $previous_date_formatted }})</span>
                                <span class="fw-semibold">{{ format_currency($opening_mauzo) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Mauzo mapya ya {{ $entry_date_formatted }}</span>
                                <span class="fw-semibold">{{ format_currency($mauzo_total) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Jumla ya mauzo</span>
                                <span class="fw-semibold">{{ format_currency($jumla_mauzo) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Mikopo ya nyuma (mpaka {{ $previous_date_formatted }})</span>
                                <span class="fw-semibold">{{ format_currency($opening_mikopo) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Mikopo mipya ya {{ $entry_date_formatted }}</span>
                                <span class="fw-semibold">{{ format_currency($mikopo_total) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Malipo ya mikopo ya {{ $entry_date_formatted }}</span>
                                <span class="fw-semibold">{{ format_currency($malipo_total) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Salio la mikopo (mwisho wa siku)</span>
                                <span class="fw-semibold">{{ format_currency($salio_mikopo) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Salio la mteja la nyuma</span>
                                <span class="fw-semibold">{{ format_currency($opening_salio_mteja) }}</span>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-uppercase">Salio la mteja (Mauzo − Mikopo)</span>
                            <span class="fs-5 fw-bold {{ $salio_mteja >= 0 ? 'text-success' : 'text-danger' }}">{{ format_currency($salio_mteja) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Stoo --}}
                <h6 class="text-info text-uppercase border-bottom pb-2 mb-3">
                    <i class="bx bx-package me-1"></i> Zao lililobaki stoo (mpaka {{ $entry_date_formatted }})
                </h6>

                @if(empty($stoo_balances))
                    <p class="text-muted small mb-3">Hakuna zao lililobaki stoo mpaka tarehe hii.</p>
                @else
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Zao</th>
                                    <th>Nambari</th>
                                    <th>Idadi</th>
                                    <th>Kifurushi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stoo_balances as $balance)
                                <tr>
                                    <td>{{ $balance['item_name'] }}</td>
                                    <td>{{ $balance['item_code'] }}</td>
                                    <td>{{ $balance['quantity_display'] }}</td>
                                    <td>{{ $balance['package_display'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(!empty($stoo_movements))
                <h6 class="text-muted text-uppercase small mb-2">Harakati za stoo siku ya {{ $entry_date_formatted }}</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Zao</th>
                                <th>Maelezo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stoo_movements as $movement)
                            <tr>
                                <td>{{ $movement['item_name'] }}</td>
                                <td class="{{ $movement['type'] === 'in' ? 'text-success' : 'text-danger' }}">
                                    {{ $movement['maelezo'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                    <p class="text-muted small mb-0">Hakuna harakati za stoo kwa siku hii.</p>
                @endif

                @if($customer_cash_collateral ?? null)
                <div class="mt-4 no-print d-flex flex-wrap gap-2">
                    @can('deposit cash collateral')
                    <a href="{{ route('cash_collaterals.deposit', Hashids::encode($customer_cash_collateral->id)) }}" class="btn btn-sm btn-success">
                        <i class="bx bx-plus"></i> Toa Mkopo
                    </a>
                    @endcan
                    <a href="{{ route('inventory.customer-storage.index') }}" class="btn btn-sm btn-primary">
                        <i class="bx bx-plus"></i> Pokea Zao
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
    }
</style>
@endpush
