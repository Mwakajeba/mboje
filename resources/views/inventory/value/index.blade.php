@extends('layouts.main')

@section('title', 'Inventory Value by Location')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Value', 'url' => '#', 'icon' => 'bx bx-dollar-circle'],
        ]" />
        <h6 class="mb-0 text-uppercase">INVENTORY VALUE BY LOCATION</h6>
        <hr />

        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-3">
                    Select a location to view items with current stock, total cost value, and total selling value.
                    Amounts are in <strong>{{ $currency }}</strong>.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Location</th>
                                <th>Branch</th>
                                <th class="text-end">Items</th>
                                <th class="text-end">Total Qty</th>
                                <th class="text-end">Total Cost</th>
                                <th class="text-end">Total Selling</th>
                                <th class="text-center" style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summaries as $summary)
                            <tr>
                                <td>
                                    <strong>{{ $summary['location']->name }}</strong>
                                    @if($summary['location']->description)
                                    <br><small class="text-muted">{{ $summary['location']->description }}</small>
                                    @endif
                                </td>
                                <td>{{ $summary['location']->branch->name ?? '—' }}</td>
                                <td class="text-end">{{ number_format($summary['items_count']) }}</td>
                                <td class="text-end">{{ number_format($summary['total_quantity'], 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($summary['total_cost'], 2) }}</td>
                                <td class="text-end">{{ number_format($summary['total_selling'], 2) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('inventory.value.show', $summary['location']->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bx bx-list-ul me-1"></i> View Items
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bx bx-info-circle me-1"></i> No active inventory locations found for your branch.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($summaries->isNotEmpty())
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Grand Total</th>
                                <th class="text-end">{{ number_format($summaries->sum('total_cost'), 2) }}</th>
                                <th class="text-end">{{ number_format($summaries->sum('total_selling'), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <div class="mt-3">
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Inventory
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
