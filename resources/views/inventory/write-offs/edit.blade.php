@extends('layouts.main')

@section('title', 'Edit Record')

@section('content')
@php
    $writeoffType  = $movement->writeoff_type ?? 'write_off';
    $typeLabel     = $writeoffType === 'stock_out' ? 'Stock-out' : 'Write-off';
    $typeBadgeClass = $writeoffType === 'stock_out' ? 'bg-warning text-dark' : 'bg-dark';
@endphp
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Write-offs & Stock-outs', 'url' => route('inventory.write-offs.index'), 'icon' => 'bx bx-x-circle'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-edit me-2"></i>Edit {{ $typeLabel }}
                                    <span class="badge {{ $typeBadgeClass }} ms-2">{{ $typeLabel }}</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            Please fix the following errors:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-info-circle me-2 fs-4"></i>
                                <div>
                                    <strong>Note:</strong> You can only edit the reference, reason, notes, and date.
                                    Quantity and costs cannot be changed. To reverse, delete this record and create a new adjustment.
                                </div>
                            </div>
                        </div>

                        <!-- Item Information (Read-only) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Item Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>Type:</strong><br>
                                                <span class="badge {{ $typeBadgeClass }}">{{ $typeLabel }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Item:</strong><br>
                                                <span>{{ $movement->item->name }}</span>
                                                <br><small class="text-muted">{{ $movement->item->code }}</small>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Quantity:</strong><br>
                                                <span class="fw-bold">{{ number_format($movement->quantity, 2) }} {{ $movement->item->unit_of_measure }}</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Total Cost:</strong><br>
                                                <span class="fw-bold text-danger">{{ number_format($movement->total_cost, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('inventory.write-offs.update', $movement->hash_id) }}" method="POST" id="writeOffForm">
                            @csrf
                            @method('PUT')

                            <!-- Record Information -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-uppercase">{{ $typeLabel }} Information</h6>
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Reference -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reference</label>
                                    <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                        value="{{ old('reference', $movement->reference) }}"
                                        placeholder="Enter reference (optional)">
                                    @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <!-- Date -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ $typeLabel }} Date <span class="text-danger">*</span></label>
                                    <input type="date" name="movement_date" class="form-control @error('movement_date') is-invalid @enderror"
                                        value="{{ old('movement_date', $movement->movement_date ? $movement->movement_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                                    @error('movement_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-uppercase">Additional Information</h6>
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Reason -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                                              rows="3" placeholder="Enter reason for this {{ strtolower($typeLabel) }}" required>{{ old('reason', $movement->reason) }}</textarea>
                                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <!-- Notes -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                              rows="2" placeholder="Additional notes (optional)">{{ old('notes', $movement->notes) }}</textarea>
                                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-dark px-5">
                                        <i class="bx bx-save me-1"></i>Update {{ $typeLabel }}
                                    </button>
                                    <a href="{{ route('inventory.write-offs.index') }}" class="btn btn-secondary px-5 ms-2">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
