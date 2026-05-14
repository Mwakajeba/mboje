@extends('layouts.main')

@section('title', 'Edit Guest')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Guest Management', 'url' => route('guests.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Guest Details', 'url' => route('guests.show', $guest), 'icon' => 'bx bx-show'],
            ['label' => 'Edit Guest', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">Edit Guest</h4>
                                <p class="card-subtitle text-muted">Update guest information</p>
                            </div>
                            <div>
                                <a href="{{ route('guests.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Guests
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('guests.update', $guest) }}">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $guest->first_name) }}" placeholder="Enter first name">
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $guest->last_name) }}" placeholder="Enter last name">
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $guest->email) }}" placeholder="Enter email address">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $guest->phone) }}" placeholder="Enter phone number">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nationality</label>
                                        <select name="nationality" class="form-select select2-single @error('nationality') is-invalid @enderror">
                                            <option value="">Select Nationality</option>
                                            <option value="Tanzania" {{ old('nationality', $guest->nationality) == 'Tanzania' ? 'selected' : '' }}>Tanzania</option>
                                            <option value="Kenya" {{ old('nationality', $guest->nationality) == 'Kenya' ? 'selected' : '' }}>Kenya</option>
                                            <option value="Uganda" {{ old('nationality', $guest->nationality) == 'Uganda' ? 'selected' : '' }}>Uganda</option>
                                            <option value="United States" {{ old('nationality', $guest->nationality) == 'United States' ? 'selected' : '' }}>United States</option>
                                            <option value="United Kingdom" {{ old('nationality', $guest->nationality) == 'United Kingdom' ? 'selected' : '' }}>United Kingdom</option>
                                            <option value="Other" {{ old('nationality', $guest->nationality) == 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('nationality')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $guest->date_of_birth?->format('Y-m-d')) }}">
                                        @error('date_of_birth')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <select name="gender" class="form-select select2-single @error('gender') is-invalid @enderror">
                                            <option value="">Select Gender</option>
                                            <option value="male" {{ old('gender', $guest->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender', $guest->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ old('gender', $guest->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('gender')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">ID Type</label>
                                        <select name="id_type" class="form-select select2-single @error('id_type') is-invalid @enderror">
                                            <option value="">Select ID Type</option>
                                            <option value="passport" {{ old('id_type', $guest->id_type) == 'passport' ? 'selected' : '' }}>Passport</option>
                                            <option value="national_id" {{ old('id_type', $guest->id_type) == 'national_id' ? 'selected' : '' }}>National ID</option>
                                            <option value="driving_license" {{ old('id_type', $guest->id_type) == 'driving_license' ? 'selected' : '' }}>Driving License</option>
                                            <option value="other" {{ old('id_type', $guest->id_type) == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('id_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">ID Number</label>
                                        <input type="text" name="id_number" class="form-control @error('id_number') is-invalid @enderror" value="{{ old('id_number', $guest->id_number) }}" placeholder="Enter ID number">
                                        @error('id_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $guest->address) }}" placeholder="Enter address">
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select select2-single @error('status') is-invalid @enderror">
                                            <option value="">Select Status</option>
                                            <option value="active" {{ old('status', $guest->status) == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status', $guest->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="blacklisted" {{ old('status', $guest->status) == 'blacklisted' ? 'selected' : '' }}>Blacklisted</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Special Notes</label>
                                <textarea name="special_requests" class="form-control @error('special_requests') is-invalid @enderror" rows="3" placeholder="Any special notes about the guest...">{{ old('special_requests', $guest->special_requests) }}</textarea>
                                @error('special_requests')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Update Guest
                                </button>
                                <a href="{{ route('guests.show', $guest) }}" class="btn btn-secondary">
                                    <i class="bx bx-x me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
