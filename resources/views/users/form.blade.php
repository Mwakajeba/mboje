@extends('layouts.main')

@section('title', isset($user) ? 'Edit User Role' : 'Update User Role')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'User Management', 'url' => route('users.index'), 'icon' => 'bx bx-user'],
            ['label' => isset($user) ? 'Edit User Role' : 'Update User Role', 'url' => '#', 'icon' => isset($user) ? 'bx bx-edit' : 'bx bx-user-circle']
        ]" />

        <h6 class="mb-0 text-uppercase">{{ isset($user) ? 'EDIT USER ROLE' : 'UPDATE USER ROLE' }}</h6>
        <hr/>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ isset($user) ? 'Edit User Role & Status' : 'Update User Role & Status' }}</h5>
                </div>

                        <form action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}"
                              method="POST" id="userForm">
                            @csrf
                            @if(isset($user))
                                @method('PUT')
                            @endif

                            <!-- General Error Display -->
                            @if($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bx bx-error-circle me-2"></i>
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <!-- User Creation/Update Section -->
                            <div id="directSection">
                                <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                <i class="bx {{ isset($user) ? 'bx-user' : 'bx-user-plus' }} fs-4"></i>
                                            </div>
                                            <div>
                                                <h5 class="text-white mb-0">{{ isset($user) ? 'Update User Account' : 'Create New User Account' }}</h5>
                                                <small class="text-white-50">{{ isset($user) ? 'Update user details directly' : 'Enter user details to create a new account' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <h6 class="mb-4 text-primary">
                                            <i class="bx bx-info-circle me-2"></i>User Information
                                        </h6>
                                        
                                        <div class="row g-4">
                                            <!-- Full Name Field -->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name" class="form-label fw-semibold mb-2">
                                                        <i class="bx bx-user text-primary me-2"></i>Full Name 
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group input-group-lg">
                                                        <span class="input-group-text bg-light border-end-0">
                                                            <i class="bx bx-user text-muted"></i>
                                                        </span>
                                                        <input type="text" 
                                                               class="form-control border-start-0 @error('name') is-invalid @enderror"
                                                               id="name" 
                                                               name="name" 
                                                               value="{{ old('name', isset($user) ? $user->name : '') }}" 
                                                               placeholder="Enter full name"
                                                               required>
                                                        @error('name')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <small class="form-text text-muted mt-1">
                                                        <i class="bx bx-info-circle me-1"></i>Enter the user's complete name
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- Email Field -->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email" class="form-label fw-semibold mb-2">
                                                        <i class="bx bx-envelope text-primary me-2"></i>Email Address 
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group input-group-lg">
                                                        <span class="input-group-text bg-light border-end-0">
                                                            <i class="bx bx-envelope text-muted"></i>
                                                        </span>
                                                        <input type="email" 
                                                               class="form-control border-start-0 @error('email') is-invalid @enderror"
                                                               id="email" 
                                                               name="email" 
                                                               value="{{ old('email', isset($user) ? $user->email : '') }}" 
                                                               placeholder="user@example.com"
                                                               required>
                                                        @error('email')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <small class="form-text text-muted mt-1">
                                                        <i class="bx bx-info-circle me-1"></i>Used for login and notifications
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- Phone Field -->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="phone" class="form-label fw-semibold mb-2">
                                                        <i class="bx bx-phone text-primary me-2"></i>Phone Number 
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group input-group-lg">
                                                        <span class="input-group-text bg-light border-end-0">
                                                            <i class="bx bx-phone text-muted"></i>
                                                        </span>
                                                        <input type="text" 
                                                               class="form-control border-start-0 @error('phone') is-invalid @enderror"
                                                               id="phone" 
                                                               name="phone" 
                                                               value="{{ old('phone', isset($user) ? $user->phone : '') }}" 
                                                               placeholder="255XXXXXXXXX"
                                                               required>
                                                        @error('phone')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <small class="form-text text-muted mt-1">
                                                        <i class="bx bx-info-circle me-1"></i>Format: 255XXXXXXXXX (Tanzania)
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- Info Card -->
                                            <div class="col-md-6">
                                                <div class="card bg-light border-0 h-100">
                                                    <div class="card-body d-flex align-items-center">
                                                        <div class="flex-shrink-0 me-3">
                                                            <div class="avatar bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                <i class="bx bx-info-circle fs-4"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1">Quick Note</h6>
                                                            <p class="mb-0 text-muted small">
                                                                Branch assignment and additional settings can be configured after user creation.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select @error('status') is-invalid @enderror"
                                                id="status" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="active" {{ old('status', isset($user) ? $user->status : 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status', isset($user) ? $user->status : 'active') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Role Assignment -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                        <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                                            <option value="">Select Role</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}"
                                                        {{ old('role_id', isset($user) ? ($user->roles->first()->id ?? '') : '') == $role->id ? 'selected' : '' }}>
                                                    {{ ucfirst($role->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            Password
                                            @if(!isset($user))<span class="text-danger">*</span>@else<span class="text-muted">(Optional)</span>@endif
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                                   id="password" name="password"
                                                   placeholder="{{ isset($user) ? 'Leave blank to keep current password' : 'Enter password' }}"
                                                   {{ !isset($user) ? 'required' : '' }}>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bx bx-show"></i>
                                            </button>
                                        </div>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <x-password-strength-meter input-id="password" />
                                        <small class="form-text text-muted">
                                            @php
                                                $securityConfig = \App\Services\SystemSettingService::getSecurityConfig();
                                                $minLength = $securityConfig['password_min_length'] ?? 8;
                                                $requirements = ["Minimum {$minLength} characters"];

                                                if ($securityConfig['password_require_uppercase'] ?? true) {
                                                    $requirements[] = 'At least one uppercase letter';
                                                }
                                                if ($securityConfig['password_require_numbers'] ?? true) {
                                                    $requirements[] = 'At least one number';
                                                }
                                                if ($securityConfig['password_require_special'] ?? true) {
                                                    $requirements[] = 'At least one special character';
                                                }
                                            @endphp
                                            @if(isset($user))
                                                Leave blank to keep current password. If filled, password will be updated.
                                            @endif
                                            <br>Password requirements: {{ implode(', ', $requirements) }}
                                        </small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password_confirmation" class="form-label">
                                            Confirm New Password
                                            <span class="text-muted">(Required if changing password)</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control"
                                                   id="password_confirmation" name="password_confirmation"
                                                   placeholder="Confirm new password">
                                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                <i class="bx bx-show"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Current User Info (only for edit) -->
                            @if(isset($user) && isset($employee))
                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        <h6 class="alert-heading"><i class="bx bx-info-circle me-1"></i>Current User Information</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>User ID:</strong> {{ $user->id }}</p>
                                                <p class="mb-1"><strong>Employee Number:</strong> {{ $employee->employee_number }}</p>
                                                <p class="mb-1"><strong>Created:</strong> {{ $user->created_at->format('M d, Y H:i') }}</p>
                                                <p class="mb-1"><strong>Last Updated:</strong> {{ $user->updated_at->format('M d, Y H:i') }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>Current Branch:</strong> {{ $employee->branch->name ?? 'N/A' }}</p>
                                                <p class="mb-1"><strong>Current Role:</strong>
                                                    @if($user->roles->first())
                                                        <span class="badge bg-primary me-1">{{ ucfirst($user->roles->first()->name) }}</span>
                                                    @else
                                                        <span class="text-muted">No role assigned</span>
                                                    @endif
                                                </p>
                                                <p class="mb-1"><strong>Status:</strong>
                                                    @if($user->status === 'active')
                                                        <span class="badge bg-success">Active</span>
                                                    @elseif($user->status === 'inactive')
                                                        <span class="badge bg-warning">Inactive</span>
                                                    @else
                                                        <span class="badge bg-danger">Suspended</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Password Strength Indicator -->
                            <div class="row" id="passwordStrengthSection" style="display: none;">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Password Strength</label>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small class="form-text text-muted" id="passwordFeedback"></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        @can('view users')
                                        <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-arrow-back me-1"></i> Cancel
                                        </a>
                                        @endcan
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-save me-1"></i> {{ isset($user) ? 'Update User Role' : 'Update User Role' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Employee selection change handler
document.getElementById('employee_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        // Update display fields
        document.getElementById('display_name').textContent = selectedOption.dataset.name || 'N/A';
        document.getElementById('display_email').textContent = selectedOption.dataset.email || 'N/A';
        document.getElementById('display_phone').textContent = selectedOption.dataset.phone || 'N/A';
    } else {
        document.getElementById('display_name').textContent = 'Select employee above';
        document.getElementById('display_email').textContent = 'Select employee above';
        document.getElementById('display_phone').textContent = 'Select employee above';
    }
});

// Password toggle functionality
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    }
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password_confirmation');
    const icon = this.querySelector('i');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    }
});

// Toggle between creation modes
document.addEventListener('DOMContentLoaded', function() {
    const modeEmployee = document.getElementById('mode_employee');
    const modeDirect = document.getElementById('mode_direct');
    const employeeSection = document.getElementById('employeeSection');
    const directSection = document.getElementById('directSection');
    const employeeInfoSection = document.getElementById('employeeInfoSection');
    const employeeSelect = document.getElementById('employee_id');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');

    function toggleSections() {
        if (modeEmployee && modeEmployee.checked) {
            // Show employee section, hide direct section
            if (employeeSection) employeeSection.style.display = '';
            if (directSection) directSection.style.display = 'none';
            if (employeeInfoSection) employeeInfoSection.style.display = '';
            
            // Make employee_id optional (can be empty for direct users), direct fields not required
            if (employeeSelect) employeeSelect.required = false;
            if (nameInput) nameInput.required = false;
            if (emailInput) emailInput.required = false;
            if (phoneInput) phoneInput.required = false;
        } else if (modeDirect && modeDirect.checked) {
            // Show direct section, hide employee section
            if (employeeSection) employeeSection.style.display = 'none';
            if (directSection) directSection.style.display = '';
            if (employeeInfoSection) employeeInfoSection.style.display = 'none';
            
            // Make direct fields required, employee_id not required
            if (employeeSelect) employeeSelect.required = false;
            if (nameInput) nameInput.required = true;
            if (emailInput) emailInput.required = true;
            if (phoneInput) phoneInput.required = true;
        }
    }

    if (modeEmployee) {
        modeEmployee.addEventListener('change', toggleSections);
    }
    if (modeDirect) {
        modeDirect.addEventListener('change', toggleSections);
    }

    // Initialize on page load - check if user exists and has employee
    const hasEmployee = {{ isset($user) && isset($employee) ? 'true' : 'false' }};
    
    // If editing a user without employee, default to direct mode
    if ({{ isset($user) ? 'true' : 'false' }} && !hasEmployee && modeDirect) {
        modeDirect.checked = true;
    }
    
    toggleSections();

    // Initialize employee select display
    if (employeeSelect && employeeSelect.value) {
        employeeSelect.dispatchEvent(new Event('change'));
    }
});

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthSection = document.getElementById('passwordStrengthSection');
    const strengthBar = document.getElementById('passwordStrength');
    const feedback = document.getElementById('passwordFeedback');

    if (password.length > 0) {
        strengthSection.style.display = 'block';

        let strength = 0;
        let feedbackText = '';

        // Get system settings for password requirements
        const securityConfig = @json(\App\Services\SystemSettingService::getSecurityConfig());
        const minLength = securityConfig.password_min_length || 8;
        const requireUppercase = securityConfig.password_require_uppercase || true;
        const requireNumbers = securityConfig.password_require_numbers || true;
        const requireSpecial = securityConfig.password_require_special || true;

        // Check length
        if (password.length >= minLength) strength += 25;
        if (password.length >= minLength + 4) strength += 25;

        // Check for lowercase
        if (/[a-z]/.test(password)) strength += 25;

        // Check for uppercase (if required)
        if (requireUppercase && /[A-Z]/.test(password)) strength += 25;
        else if (!requireUppercase) strength += 25; // Give points even if not required

        // Check for numbers (if required)
        if (requireNumbers && /[0-9]/.test(password)) strength += 25;
        else if (!requireNumbers) strength += 25; // Give points even if not required

        // Check for special characters (if required)
        if (requireSpecial && /[^A-Za-z0-9]/.test(password)) strength += 25;
        else if (!requireSpecial) strength += 25; // Give points even if not required

        // Cap at 100%
        strength = Math.min(strength, 100);

        // Update progress bar
        strengthBar.style.width = strength + '%';

        // Update color and feedback
        if (strength < 25) {
            strengthBar.className = 'progress-bar bg-danger';
            feedbackText = 'Very Weak';
        } else if (strength < 50) {
            strengthBar.className = 'progress-bar bg-warning';
            feedbackText = 'Weak';
        } else if (strength < 75) {
            strengthBar.className = 'progress-bar bg-info';
            feedbackText = 'Good';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            feedbackText = 'Strong';
        }

        feedback.textContent = feedbackText;
    } else {
        strengthSection.style.display = 'none';
    }
});

// Password field change handler - make confirmation required only if password is filled
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const confirmField = document.getElementById('password_confirmation');

    if (password) {
        confirmField.setAttribute('required', 'required');
    } else {
        confirmField.removeAttribute('required');
        confirmField.value = ''; // Clear confirmation if password is cleared
    }
});

// Form validation
document.getElementById('userForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;
    const role = document.getElementById('role_id').value;
    const employeeId = document.getElementById('employee_id');
    const employeeIdValue = employeeId ? employeeId.value : '';
    
    // Check creation mode (only for new users)
    const modeEmployee = document.getElementById('mode_employee');
    const modeDirect = document.getElementById('mode_direct');
    const isEditMode = {{ isset($user) ? 'true' : 'false' }};
    const isEmployeeMode = modeEmployee && modeEmployee.checked;
    const isDirectMode = modeDirect && modeDirect.checked;

    // Employee validation - not required, can be empty for direct users
    // Only validate if employee is selected and it's invalid

    // Direct creation/update validation - check name, email, phone
    if (isDirectMode) {
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();

        if (!name) {
            e.preventDefault();
            alert('Please enter the user\'s full name!');
            return false;
        }

        if (!email) {
            e.preventDefault();
            alert('Please enter the user\'s email address!');
            return false;
        }

        if (!phone) {
            e.preventDefault();
            alert('Please enter the user\'s phone number!');
            return false;
        }
    }

    // Role validation
    if (!role) {
        e.preventDefault();
        alert('Please select a role!');
        return false;
    }

    // Password validation - required for new users, optional for updates
    if (!isEditMode && !password) {
        e.preventDefault();
        alert('Password is required for new users!');
        return false;
    }

    // Password validation - only if password is provided
    if (password) {
        if (!confirmPassword) {
            e.preventDefault();
            alert('Please confirm the password!');
            return false;
        }

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }

        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            return false;
        }
    } else {
        // If password is empty, clear confirmation field
        document.getElementById('password_confirmation').value = '';
    }
});
</script>
@endpush

