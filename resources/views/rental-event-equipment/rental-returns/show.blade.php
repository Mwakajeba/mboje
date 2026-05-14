@extends('layouts.main')

@section('title', 'Rental Return Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Returns', 'url' => route('rental-event-equipment.rental-returns.index'), 'icon' => 'bx bx-undo'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">RENTAL RETURN DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-undo me-2"></i>Return: {{ $return->return_number }}</h5>
                            <div>
                                <a href="{{ route('rental-event-equipment.rental-returns.index') }}" class="btn btn-light btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i>Back to Returns
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Return Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Return Number:</th>
                                        <td><strong>{{ $return->return_number }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Return Date:</th>
                                        <td>{{ $return->return_date->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            @php
                                                $badgeClass = match($return->status) {
                                                    'draft' => 'secondary',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($return->status) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created By:</th>
                                        <td>{{ $return->creator->name ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Related Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Customer:</th>
                                        <td>{{ $return->customer->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Contract:</th>
                                        <td>
                                            @if($return->contract)
                                                <a href="{{ route('rental-event-equipment.contracts.show', $return->contract) }}" class="text-decoration-none">
                                                    {{ $return->contract->contract_number }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Dispatch:</th>
                                        <td>
                                            @if($return->dispatch)
                                                <a href="{{ route('rental-event-equipment.rental-dispatches.show', $return->dispatch) }}" class="text-decoration-none">
                                                    {{ $return->dispatch->dispatch_number }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if($return->notes)
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-2">Notes</h6>
                                <div class="alert alert-info">
                                    {{ $return->notes }}
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Returned Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Equipment</th>
                                                <th>Quantity Returned</th>
                                                <th>Condition</th>
                                                <th>Condition Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($return->items as $item)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $item->equipment->name ?? 'N/A' }}</strong><br>
                                                        <small class="text-muted">{{ $item->equipment->equipment_code ?? 'N/A' }}</small>
                                                    </td>
                                                    <td>{{ $item->quantity_returned }}</td>
                                                    <td>
                                                        @php
                                                            $conditionBadge = match($item->condition) {
                                                                'good' => 'success',
                                                                'damaged' => 'warning',
                                                                'lost' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        @endphp
                                                        <span class="badge bg-{{ $conditionBadge }}">{{ ucfirst($item->condition) }}</span>
                                                    </td>
                                                    <td>{{ $item->condition_notes ?? '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No items found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
