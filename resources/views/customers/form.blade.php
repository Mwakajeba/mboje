@php
$isEdit = isset($customer);
@endphp

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

<form action="{{ $isEdit ? route('customers.update', \Vinkla\Hashids\Facades\Hashids::encode($customer->id)) : route('customers.store') }}"
      method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row">
        <!-- Basic Information Section -->
        <div class="col-12">
            <div class="card radius-10 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $customer->name ?? '') }}" placeholder="Enter full name">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>



                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $customer->phone ?? '') }}" placeholder="Enter phone number">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $customer->email ?? '') }}" placeholder="Enter email address">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credit Limit</label>
                            <input type="number" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror"
                                value="{{ old('credit_limit', $customer->credit_limit ?? '') }}" placeholder="Enter credit limit" step="0.01" min="0">
                            @error('credit_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" value="1" name="send_welcome_sms"
                                    id="send_welcome_sms" {{ old('send_welcome_sms') ? 'checked' : '' }}>
                                <label class="form-check-label" for="send_welcome_sms">Send Welcome SMS</label>
                            </div>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-control @error('status') is-invalid @enderror">
                <option value="active" {{ old('status', $customer->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $customer->status ?? 'active') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ old('status', $customer->status ?? 'active') == 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

                        <div class="col-12 mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                      rows="3" placeholder="Enter customer description">{{ old('description', $customer->description ?? '') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>


        </div>
                </div>
            </div>
        </div>



        <!-- Company Information Section -->
        <div class="col-12">
            <div class="card radius-10 mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bx bx-building me-2"></i>Company Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                                value="{{ old('company_name', $customer->company_name ?? '') }}" placeholder="Enter company name">
                            @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6 mb-3">
                            <label class="form-label">Company Registration Number</label>
                            <input type="text" name="company_registration_number" class="form-control @error('company_registration_number') is-invalid @enderror"
                                value="{{ old('company_registration_number', $customer->company_registration_number ?? '') }}" placeholder="Enter registration number">
                            @error('company_registration_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">TIN Number</label>
                            <input type="text" name="tin_number" class="form-control @error('tin_number') is-invalid @enderror"
                                value="{{ old('tin_number', $customer->tin_number ?? '') }}" placeholder="Enter TIN number" pattern="[0-9]+">
                            <div class="form-text">Enter numbers only (no dashes or special characters)</div>
                            @error('tin_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">VAT Number</label>
                            <input type="text" name="vat_number" class="form-control @error('vat_number') is-invalid @enderror"
                                value="{{ old('vat_number', $customer->vat_number ?? '') }}" placeholder="Enter VAT number">
                            <div class="form-text">Enter letters and numbers (no dashes or special characters)</div>
                            @error('vat_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <hr class="my-4">

        <div class="d-flex justify-content-between">
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to Customers
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i> {{ $isEdit ? 'Update Customer' : 'Create Customer' }}
            </button>
        </div>
</form>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function() {
        // TIN number validation - only allow numbers
        document.querySelectorAll('input[name="tin_number"]').forEach(function (input) {
            input.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                e.target.value = value;
            });
        });
        
        // VAT number validation - allow letters and numbers (alphanumeric)
        document.querySelectorAll('input[name="vat_number"]').forEach(function (input) {
            input.addEventListener('input', function (e) {
                let value = e.target.value.replace(/[^A-Za-z0-9]/g, '');
                e.target.value = value;
            });
        });
    });
</script>
@endpush
