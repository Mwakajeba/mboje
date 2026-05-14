@extends('layouts.main')

@section('title', 'View Equipment Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Equipment Master', 'url' => route('rental-event-equipment.equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">EQUIPMENT DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-show me-1 font-22 text-primary"></i></div>
                                <h5 class="mb-0 text-primary">Equipment Information</h5>
                            </div>
                            <div>
                                <a href="{{ route('rental-event-equipment.equipment.edit', $equipment) }}" class="btn btn-warning btn-sm">
                                    <i class="bx bx-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('rental-event-equipment.equipment.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <hr />

                        <!-- Basic Information -->
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i> Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Equipment Name</label>
                                            <p class="form-control-plaintext">{{ $equipment->name }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Equipment Code</label>
                                            <p class="form-control-plaintext">{{ $equipment->equipment_code ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Category</label>
                                            <p class="form-control-plaintext">{{ $equipment->category->name ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Status</label>
                                            <p class="form-control-plaintext">
                                                @php
                                                    $statusBadge = match($equipment->status) {
                                                        'available' => 'success',
                                                        'reserved' => 'warning',
                                                        'on_rent' => 'info',
                                                        'in_event_use' => 'primary',
                                                        'under_repair' => 'danger',
                                                        'lost' => 'dark',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusBadge }}">
                                                    {{ ucfirst(str_replace('_', ' ', $equipment->status)) }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    @if($equipment->description)
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Description</label>
                                            <p class="form-control-plaintext">{{ $equipment->description }}</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Quantity & Cost Information -->
                        <div class="card border-info mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-calculator me-2"></i> Quantity & Cost Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Quantity Owned</label>
                                            <p class="form-control-plaintext">{{ number_format($equipment->quantity_owned) }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Quantity Available</label>
                                            <p class="form-control-plaintext">{{ number_format($equipment->quantity_available) }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Quantity In Use</label>
                                            <p class="form-control-plaintext">{{ number_format($equipment->quantity_owned - $equipment->quantity_available) }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Replacement Cost</label>
                                            <p class="form-control-plaintext">TZS {{ number_format($equipment->replacement_cost, 2) }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Rental Rate</label>
                                            <p class="form-control-plaintext">{{ $equipment->rental_rate ? 'TZS ' . number_format($equipment->rental_rate, 2) : 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Total Replacement Value</label>
                                            <p class="form-control-plaintext">TZS {{ number_format($equipment->quantity_owned * $equipment->replacement_cost, 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="card border-secondary mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-detail me-2"></i> Additional Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @if($equipment->location)
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Location</label>
                                            <p class="form-control-plaintext">{{ $equipment->location }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    @if($equipment->serial_number)
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Serial Number</label>
                                            <p class="form-control-plaintext">{{ $equipment->serial_number }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    @if($equipment->purchase_date)
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Purchase Date</label>
                                            <p class="form-control-plaintext">{{ $equipment->purchase_date->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    @if($equipment->manufacturer)
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Manufacturer</label>
                                            <p class="form-control-plaintext">{{ $equipment->manufacturer }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    @if($equipment->model)
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Model</label>
                                            <p class="form-control-plaintext">{{ $equipment->model }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    @if($equipment->notes)
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Notes</label>
                                            <p class="form-control-plaintext">{{ $equipment->notes }}</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Timestamps -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Created At</label>
                                    <p class="form-control-plaintext">{{ $equipment->created_at->format('M d, Y \a\t h:i A') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Last Updated</label>
                                    <p class="form-control-plaintext">{{ $equipment->updated_at->format('M d, Y \a\t h:i A') }}</p>
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

@push('styles')
<style>
    .form-control-plaintext {
        padding-top: 0.375rem;
        padding-bottom: 0.375rem;
        margin-bottom: 0;
        line-height: 1.5;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
        padding-left: 0.75rem;
    }
</style>
@endpush
