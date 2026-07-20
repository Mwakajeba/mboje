@extends('layouts.main')

@section('title', 'Usimamizi wa Hesabu')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Hesabu', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        <h6 class="mb-0 text-uppercase">USIMAMIZI WA HESABU</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
                        </div>
                        @endif

                        @if(isset($errors) && $errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            Tafadhali rekebisha makosa yafuatayo:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
                        </div>
                        @endif

                        <div class="row">
                            <!-- Usimamizi wa Makundi -->
                            @can('manage inventory categories')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $categoriesCount }}
                                            <span class="visually-hidden">idadi ya makundi</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-category fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Makundi</h5>
                                        <p class="card-text">Simamia makundi ya bidhaa na aina za uainishaji.</p>
                                        <a href="{{ route('inventory.categories.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Simamia Makundi
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            {{-- Inventory Value (hidden)
                            @canany(['view inventory items', 'manage inventory items'])
                            <div class="col-md-6 col-lg-4 mb-4">
                                <a href="{{ session('location_id') ? route('inventory.value.show', session('location_id')) : route('inventory.value.index') }}" class="text-decoration-none text-body">
                                <div class="card border-teal position-relative h-100">
                                    <div class="card-body text-center">
                                        @if(isset($inventoryValueAtLocation) && $inventoryValueAtLocation !== null)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-teal text-white" title="Thamani ya gharama kwenye eneo la sasa">
                                            {{ number_format($inventoryValueAtLocation, 0) }}
                                            <span class="visually-hidden">thamani ya hesabu</span>
                                        </span>
                                        @endif
                                        <div class="mb-3">
                                            <i class="bx bx-dollar-circle fs-1 text-teal"></i>
                                        </div>
                                        <h5 class="card-title">Thamani ya Bidhaa</h5>
                                        <p class="card-text">Stoo ya sasa kwa eneo pamoja na jumla ya gharama na thamani ya uuzaji kwa kila bidhaa.</p>
                                        <span class="btn btn-teal">
                                            <i class="bx bx-bar-chart-alt-2 me-1"></i>
                                            @if(session('location_id'))
                                                Angalia kwenye eneo hili
                                            @else
                                                Chagua eneo
                                            @endif
                                        </span>
                                        @if(isset($inventoryValueCurrency) && isset($inventoryValueAtLocation))
                                        <p class="small text-muted mt-2 mb-0">
                                            Jumla ya gharama kwa eneo: {{ number_format($inventoryValueAtLocation, 2) }} {{ $inventoryValueCurrency }}
                                        </p>
                                        @endif
                                    </div>
                                </div>
                                </a>
                            </div>
                            @endcanany
                            --}}

                            <!-- Usimamizi wa Bidhaa -->
                            @can('manage inventory items')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $itemsCount }}
                                            <span class="visually-hidden">idadi ya bidhaa</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-box fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Bidhaa</h5>
                                        <p class="card-text">Simamia bidhaa, huduma, na vitu vya hesabu.</p>
                                        <a href="{{ route('inventory.items.index') }}" class="btn btn-success">
                                            <i class="bx bx-package me-1"></i> Simamia Bidhaa
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Stoo ya Muda Mfupi (Wateja) -->
                            @can('manage inventory items')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $customerStorageCount ?? 0 }}
                                            <span class="visually-hidden">idadi ya stoo ya muda mfupi</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-user-pin fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Stoo ya Muda Mfupi (Wateja)</h5>
                                        <p class="card-text">Pokea na fuatilia zao la stoo ya muda mfupi kwa wateja.</p>
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('inventory.customer-storage.index') }}" class="btn btn-info">
                                                <i class="bx bx-archive-in me-1"></i> Simamia Stoo
                                            </a>
                                            <a href="{{ route('inventory.customer-storage.report') }}" class="btn btn-outline-info btn-sm">
                                                <i class="bx bx-bar-chart-alt-2 me-1"></i> Ripoti ya Stoo ya Muda Mfupi (Wateja)
                                            </a>
                                            <a href="#ripoti-stoo" class="btn btn-outline-secondary btn-sm">
                                                <i class="bx bx-package me-1"></i> Angalia Ripoti ya Stoo
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Stoo ya Kudumu -->
                            @can('manage inventory items')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                            {{ $permanentStorageCount ?? 0 }}
                                            <span class="visually-hidden">idadi ya stoo ya kudumu</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-building-house fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Stoo ya Kudumu</h5>
                                        <p class="card-text">Pokea na fuatilia mazao yanayohifadhiwa kwa muda mrefu.</p>
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('inventory.permanent-storage.index') }}" class="btn btn-dark">
                                                <i class="bx bx-archive-in me-1"></i> Simamia Stoo ya Kudumu
                                            </a>
                                            <a href="{{ route('inventory.permanent-storage.report') }}" class="btn btn-outline-dark btn-sm">
                                                <i class="bx bx-bar-chart-alt-2 me-1"></i> Ripoti ya Stoo ya Kudumu
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Miamala ya Stoo kwa Siku -->
                            @can('manage inventory items')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-list-check fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Miamala</h5>
                                        <p class="card-text">Angalia mapato/mauzo, gharama na stoo iliyoingizwa kwa tarehe — kudumu na muda mfupi pamoja.</p>
                                        <a href="{{ route('inventory.miamala') }}" class="btn btn-primary">
                                            <i class="bx bx-calendar me-1"></i> Chagua Tarehe
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            {{-- Stock Movements (hidden)
                            @can('view inventory adjustments')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            {{ $movementsCount }}
                                            <span class="visually-hidden">idadi ya mielekeo</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Mielekeo ya Stoo</h5>
                                        <p class="card-text">Fuatilia mielekeo ya stoo, mapokezi, na marekebisho.</p>
                                        <a href="{{ route('inventory.movements.index') }}" class="btn btn-warning">
                                            <i class="bx bx-transfer-alt me-1"></i> Simamia Mielekeo
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            --}}

                            {{-- Stock Adjustments (hidden)
                            @can('manage inventory movements')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $adjustmentsCount ?? 0 }}
                                            <span class="visually-hidden">idadi ya marekebisho</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-adjust fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Marekebisho ya Stoo</h5>
                                        <p class="card-text">Fanya marekebisho na usahihishaji wa hesabu.</p>
                                        @can('create inventory adjustments')
                                        <a href="{{ route('inventory.movements.create') }}?defaultMovementType=adjustment_in" class="btn btn-danger">
                                            <i class="bx bx-adjust me-1"></i> Fanya Marekebisho
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            @endcan
                            --}}

                            {{-- Branch Transfers (hidden)
                            @can('view inventory transfer')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $transfersCount ?? 0 }}
                                            <span class="visually-hidden">idadi ya uhamishaji</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Uhamishaji wa Tawi</h5>
                                        <p class="card-text">Hamisha bidhaa kati ya matawi.</p>
                                        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-info">
                                            <i class="bx bx-list-ul me-1"></i> Simamia Uhamishaji
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            --}}

                            {{-- Write-offs & Stock-outs (hidden)
                            @can('view inventory write-offs')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                            {{ $writeOffsCount ?? 0 }}
                                            <span class="visually-hidden">idadi ya kuondolewa</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-x-circle fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Kuondolewa na Kutoka Stoo</h5>
                                        <p class="card-text">Rekodi bidhaa zilizoharibika, zilizomalizika muda, au zilizotumika ndani.</p>
                                        <a href="{{ route('inventory.write-offs.index') }}" class="btn btn-dark">
                                            <i class="bx bx-list-ul me-1"></i> Simamia Rekodi
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            --}}

                            <!-- Transfer Requests Management -->
                          

                            {{-- Locations (hidden)
                            @can('manage inventory locations')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $locationsCount }}
                                            <span class="visually-hidden">idadi ya maeneo</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-building fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Maeneo</h5>
                                        <p class="card-text">Simamia maeneo ya ghala na sehemu za kuhifadhi.</p>
                                        <a href="{{ route('settings.inventory.locations.index') }}" class="btn btn-info">
                                            <i class="bx bx-building me-1"></i> Simamia Maeneo
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            --}}

                            {{-- Opening Balance (hidden)
                            @can('manage inventory opening balances')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-layer-plus fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Salio la Kufungua</h5>
                                        <p class="card-text">Weka stoo ya kuanzia kwa eneo. Inatumika kwa eneo la sasa la kuingia.</p>
                                        <a href="{{ route('inventory.opening-balances.index') }}" class="btn btn-warning">
                                            <i class="bx bx-list-ul me-1"></i> Angalia Salio la Kufungua
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            --}}

                            {{-- Stock Taking/Count (hidden)
                            @can('manage inventory locations')
                             <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            {{ $countSessionsCount ?? 0 }}
                                            <span class="visually-hidden">idadi ya hesabu</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-message-square-dots fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Hesabu ya Stoo</h5>
                                        <p class="card-text">Fanya hesabu ya bidhaa na maeneo.</p>
                                        <a href="{{ route('inventory.counts.index') }}" class="btn btn-info">
                                            <i class="bx bx-building me-1"></i> Simamia Hesabu
                                        </a>
                                    </div>
                                </div>
                            </div>
                             @endcan
                            --}}

                            {{-- Inventory Settings (hidden)
                            @can('manage inventory settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            1
                                            <span class="visually-hidden">mipangilio</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-cog fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Mipangilio ya Hesabu</h5>
                                        <p class="card-text">Sanidi mipangilio na mapendeleo ya mfumo wa hesabu.</p>
                                        <a href="{{ route('settings.inventory') }}" class="btn btn-secondary">
                                            <i class="bx bx-cog me-1"></i> Sanidi
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            --}}
                        </div>

                        @include('inventory.partials.storage-report')

                        {{-- Recent Stock Movements (hidden)
                        @if($recentMovements->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bx bx-time-five me-2"></i>Mielekeo ya Stoo ya Hivi Karibuni
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Bidhaa</th>
                                                        <th>Aina ya Mielekeo</th>
                                                        <th>Kiasi</th>
                                                        <th>Mtumiaji</th>
                                                        <th>Tarehe</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentMovements as $movement)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $movement->item->name }}</strong>
                                                            <br>
                                                            <small class="text-muted">{{ $movement->item->code }}</small>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $typeClasses = [
                                                                    'opening_balance' => 'bg-primary',
                                                                    'opening_balance' => 'bg-primary',
                                                                    'transfer_in' => 'bg-success',
                                                                    'transfer_out' => 'bg-info',
                                                                    'sold' => 'bg-danger',
                                                                    'purchased' => 'bg-success',
                                                                    'adjustment_in' => 'bg-warning',
                                                                    'adjustment_out' => 'bg-secondary',
                                                                    'write_off' => ($movement->writeoff_type === 'stock_out' ? 'bg-warning text-dark' : 'bg-dark')
                                                                ];
                                                                $typeLabels = [
                                                                    'opening_balance' => 'Salio la Kufungua',
                                                                    'transfer_in' => 'Uingizaji wa Uhamishaji',
                                                                    'transfer_out' => 'Utoaji wa Uhamishaji',
                                                                    'sold' => 'Imeuzwa',
                                                                    'purchased' => 'Imenunuliwa',
                                                                    'adjustment_in' => 'Marekebisho ya Kuongeza',
                                                                    'adjustment_out' => 'Marekebisho ya Kupunguza',
                                                                    'write_off' => ($movement->writeoff_type === 'stock_out' ? 'Kutoka Stoo' : 'Kuondolewa')
                                                                ];
                                                            @endphp
                                                            <span class="badge {{ $typeClasses[$movement->movement_type] ?? 'bg-secondary' }}">
                                                                {{ $typeLabels[$movement->movement_type] ?? ucfirst($movement->movement_type) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold">{{ number_format($movement->quantity, 2) }}</span>
                                                            {{ $movement->item->unit_of_measure ?? 'vipimo' }}
                                                        </td>
                                                        <td>{{ $movement->user->name }}</td>
                                                        <td>{{ $movement->created_at->format('M j, Y H:i') }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center mt-3">
                                            <a href="{{ route('inventory.movements.index') }}" class="btn btn-outline-primary">
                                                <i class="bx bx-list-ul me-1"></i> Angalia Mielekeo Yote
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end page wrapper -->
<!--start overlay-->
<div class="overlay toggle-icon"></div>
<!--end overlay-->
<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
<!--End Back To Top Button-->
<footer class="page-footer">
    <p class="mb-0">Copyright © {{ date('Y') }}. All right reserved. -- By SAFCO FINTECH</p>
</footer>

@endsection

@push('styles')
<style>
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
    }

    .fs-1 {
        font-size: 3rem !important;
    }

    /* Notification badge positioning */
    .position-relative .badge {
        z-index: 10;
        font-size: 0.7rem;
        min-width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .border-primary { border-color: #0d6efd !important; }
    .border-success { border-color: #198754 !important; }
    .border-warning { border-color: #ffc107 !important; }
    .border-info { border-color: #0dcaf0 !important; }
    .border-danger { border-color: #dc3545 !important; }
    .border-secondary { border-color: #6c757d !important; }
    .border-purple { border-color: #6f42c1 !important; }
    .border-teal { border-color: #20c997 !important; }

    .text-teal { color: #20c997 !important; }
    .bg-teal { background-color: #20c997 !important; }
    .btn-teal {
        background-color: #20c997;
        border-color: #20c997;
        color: #fff;
    }
    .btn-teal:hover {
        background-color: #1aa179;
        border-color: #1aa179;
        color: #fff;
    }
    
    .text-purple { color: #6f42c1 !important; }
    .bg-purple { background-color: #6f42c1 !important; }
    .btn-purple { 
        background-color: #6f42c1; 
        border-color: #6f42c1; 
        color: white; 
    }
    .btn-purple:hover { 
        background-color: #5a32a3; 
        border-color: #5a32a3; 
        color: white; 
    }

    .widgets-icons {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .radius-10 {
        border-radius: 10px;
    }
</style>
@endpush
