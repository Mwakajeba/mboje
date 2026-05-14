@extends('layouts.main')

@section('title', 'Damage & Loss Charge Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Damage & Loss Charges', 'url' => route('rental-event-equipment.damage-charges.index'), 'icon' => 'bx bx-error-circle'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">DAMAGE & LOSS CHARGE DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-error-circle me-2"></i>Charge: {{ $charge->charge_number }}</h5>
                            <div>
                                <a href="{{ route('rental-event-equipment.damage-charges.index') }}" class="btn btn-light btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i>Back to Charges
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Charge Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Charge Number:</th>
                                        <td><strong>{{ $charge->charge_number }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Charge Date:</th>
                                        <td>{{ $charge->charge_date->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            @php
                                                $badgeClass = match($charge->status) {
                                                    'draft' => 'secondary',
                                                    'invoiced' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($charge->status) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created By:</th>
                                        <td>{{ $charge->creator->name ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Related Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Customer:</th>
                                        <td>{{ $charge->customer->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Contract:</th>
                                        <td>
                                            @if($charge->contract)
                                                <a href="{{ route('rental-event-equipment.contracts.show', $charge->contract) }}" class="text-decoration-none">
                                                    {{ $charge->contract->contract_number }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Return:</th>
                                        <td>
                                            @if($charge->return)
                                                <a href="{{ route('rental-event-equipment.rental-returns.show', $charge->return) }}" class="text-decoration-none">
                                                    {{ $charge->return->return_number }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if($charge->notes)
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-2">Notes</h6>
                                <div class="alert alert-info">
                                    {{ $charge->notes }}
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Charge Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Equipment</th>
                                                <th>Quantity</th>
                                                <th>Charge Type</th>
                                                <th>Unit Charge</th>
                                                <th>Total Charge</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($charge->items as $item)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $item->equipment->name ?? 'N/A' }}</strong><br>
                                                        <small class="text-muted">{{ $item->equipment->equipment_code ?? 'N/A' }}</small>
                                                    </td>
                                                    <td>{{ $item->quantity }}</td>
                                                    <td>
                                                        @php
                                                            $typeBadge = match($item->charge_type) {
                                                                'damage' => 'warning',
                                                                'loss' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        @endphp
                                                        <span class="badge bg-{{ $typeBadge }}">{{ ucfirst($item->charge_type) }}</span>
                                                    </td>
                                                    <td>TZS {{ number_format($item->unit_charge, 2) }}</td>
                                                    <td><strong>TZS {{ number_format($item->total_charge, 2) }}</strong></td>
                                                    <td>{{ $item->description ?? '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">No items found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="4" class="text-end">Total Damage Charges:</th>
                                                <th>TZS {{ number_format($charge->total_damage_charges, 2) }}</th>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <th colspan="4" class="text-end">Total Loss Charges:</th>
                                                <th>TZS {{ number_format($charge->total_loss_charges, 2) }}</th>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <th colspan="4" class="text-end">Total Charges:</th>
                                                <th class="text-danger">TZS {{ number_format($charge->total_charges, 2) }}</th>
                                                <td></td>
                                            </tr>
                                        </tfoot>
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
