@extends('layouts.main')

@section('title', 'Create Lease')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-buildings'],
            ['label' => 'Leases', 'url' => route('leases.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">Create Lease</h4>
                    <p class="text-muted mb-0">Fill out the form to create a new lease</p>
                </div>
                <a href="{{ route('leases.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i> Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('leases.store') }}">
                    @csrf

                    <h6 class="text-uppercase text-muted mb-3">Lease Details</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Property <span class="text-danger">*</span></label>
                                <select name="property_id" class="form-select select2-single @error('property_id') is-invalid @enderror" required>
                                    <option value="">Select Property</option>
                                    @foreach(($properties ?? []) as $property)
                                        <option value="{{ $property->id }}" {{ old('property_id') == $property->id ? 'selected' : '' }}>{{ $property->name }}</option>
                                    @endforeach
                                </select>
                                @error('property_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-select select2-single @error('room_id') is-invalid @enderror">
                                    <option value="">Select Room (optional)</option>
                                    @foreach(($rooms ?? []) as $room)
                                        <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                            {{ $room->room_number }}{{ $room->room_name ? ' - ' . $room->room_name : '' }} ({{ ucfirst($room->room_type) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tenant <span class="text-danger">*</span></label>
                                <select name="tenant_id" class="form-select select2-single @error('tenant_id') is-invalid @enderror" required>
                                    <option value="">Select Tenant</option>
                                    @foreach(($tenants ?? []) as $tenant)
                                        <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>{{ $tenant->full_name }} ({{ $tenant->phone }})</option>
                                    @endforeach
                                </select>
                                @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-d')) }}" required>
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Rent Due Day <span class="text-danger">*</span></label>
                                <input type="number" name="rent_due_day" class="form-control @error('rent_due_day') is-invalid @enderror" min="1" max="31" value="{{ old('rent_due_day', 1) }}" required>
                                @error('rent_due_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted mb-3">Financials</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Monthly Rent (TSh) <span class="text-danger">*</span></label>
                                <input type="number" name="monthly_rent" class="form-control @error('monthly_rent') is-invalid @enderror" min="0" value="{{ old('monthly_rent') }}" required>
                                @error('monthly_rent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Security Deposit (TSh)</label>
                                <input type="number" name="security_deposit" class="form-control @error('security_deposit') is-invalid @enderror" min="0" value="{{ old('security_deposit', 0) }}">
                                @error('security_deposit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Late Fee (TSh) / Grace Days</label>
                                <div class="input-group">
                                    <input type="number" name="late_fee_amount" class="form-control @error('late_fee_amount') is-invalid @enderror" min="0" placeholder="0" value="{{ old('late_fee_amount', 0) }}">
                                    <input type="number" name="late_fee_grace_days" class="form-control @error('late_fee_grace_days') is-invalid @enderror" min="0" placeholder="Grace Days" value="{{ old('late_fee_grace_days', 0) }}">
                                </div>
                                @error('late_fee_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @error('late_fee_grace_days')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted mb-3">Terms</h6>
                    <div class="mb-3">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea name="terms_conditions" rows="3" class="form-control @error('terms_conditions') is-invalid @enderror" placeholder="Enter general terms...">{{ old('terms_conditions') }}</textarea>
                        @error('terms_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Special Conditions</label>
                        <textarea name="special_conditions" rows="2" class="form-control @error('special_conditions') is-invalid @enderror" placeholder="Any special conditions...">{{ old('special_conditions') }}</textarea>
                        @error('special_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Internal notes...">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Save Lease</button>
                        <a href="{{ route('leases.index') }}" class="btn btn-secondary"><i class="bx bx-x me-1"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2();
    }
});
</script>
@endpush


