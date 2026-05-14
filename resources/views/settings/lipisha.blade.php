@extends('layouts.main')

@section('title', 'LIPISHA Payment Gateway Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'LIPISHA Settings', 'url' => '#', 'icon' => 'bx bx-credit-card']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">LIPISHA PAYMENT GATEWAY SETTINGS</h6>
                <p class="text-muted mb-0">Configure LIPISHA payment gateway credentials and connection settings</p>
            </div>
            <div>
                <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Settings
                </a>
            </div>
        </div>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">
                            <i class="bx bx-credit-card me-2"></i>LIPISHA Configuration
                        </h4>

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

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

                        <form action="{{ route('settings.lipisha.update') }}" method="POST" id="lipishaForm">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-12 mb-4">
                                    <div class="card border-primary">
                                        <div class="card-body">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="enabled" 
                                                       name="enabled" 
                                                       value="1"
                                                       {{ old('enabled', $settings['enabled']) ? 'checked' : '' }}
                                                       onchange="toggleLipishaFields()">
                                                <label class="form-check-label" for="enabled">
                                                    <strong>Enable LIPISHA Integration</strong>
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                When enabled, the system will automatically create LIPISHA customers for students and generate control numbers for fee invoices.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row" id="lipishaFields">
                                <div class="col-12">
                                    <h5 class="mb-3 text-primary">
                                        <i class="bx bx-key me-2"></i>API Credentials
                                    </h5>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="business_id" class="form-label">
                                        Business ID <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('business_id') is-invalid @enderror" 
                                           id="business_id" 
                                           name="business_id" 
                                           value="{{ old('business_id', $settings['business_id']) }}" 
                                           placeholder="e.g., f0790106-639e-4395-a601-469d74e1d6ca"
                                           required>
                                    @error('business_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Your LIPISHA business identifier</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="business_name" class="form-label">
                                        Business Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('business_name') is-invalid @enderror" 
                                           id="business_name" 
                                           name="business_name" 
                                           value="{{ old('business_name', $settings['business_name']) }}" 
                                           placeholder="e.g., SAFCO"
                                           required>
                                    @error('business_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Your registered business name with LIPISHA</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="business_key" class="form-label">
                                        Business Key <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control @error('business_key') is-invalid @enderror" 
                                               id="business_key" 
                                               name="business_key" 
                                               value="{{ old('business_key', $settings['business_key']) }}" 
                                               placeholder="Enter your business key"
                                               required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleBusinessKey">
                                            <i class="bx bx-show" id="businessKeyIcon"></i>
                                        </button>
                                    </div>
                                    @error('business_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Your LIPISHA API business key</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="verify_token" class="form-label">
                                        Verify Token <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control @error('verify_token') is-invalid @enderror" 
                                               id="verify_token" 
                                               name="verify_token" 
                                               value="{{ old('verify_token', $settings['verify_token']) }}" 
                                               placeholder="Enter your verify token"
                                               required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleVerifyToken">
                                            <i class="bx bx-show" id="verifyTokenIcon"></i>
                                        </button>
                                    </div>
                                    @error('verify_token')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Token for verifying webhook requests from LIPISHA</small>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>Important:</strong> The network test will verify connectivity to LIPISHA servers and validate your API credentials. 
                                        <ul class="mb-0 mt-2">
                                            <li>If you get a 404 error, the API endpoint may have changed. Please verify the correct endpoint with LIPISHA documentation.</li>
                                            <li>If you get authentication errors, double-check your Business ID, Business Key, and Verify Token.</li>
                                            <li>Ensure your LIPISHA account is active and API access is enabled.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button type="button" class="btn btn-info" id="testNetworkBtn" style="display: none;">
                                            <i class="bx bx-network-chart me-1"></i> Test Network Connection
                                        </button>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-save me-1"></i> Save Settings
                                            </button>
                                        </div>
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
    // Toggle LIPISHA fields based on enabled checkbox
    function toggleLipishaFields() {
        const enabled = document.getElementById('enabled').checked;
        const fields = document.getElementById('lipishaFields');
        const testBtn = document.getElementById('testNetworkBtn');
        const inputs = fields.querySelectorAll('input[type="text"], input[type="password"]');
        
        if (enabled) {
            fields.style.display = 'block';
            testBtn.style.display = 'inline-block';
            inputs.forEach(input => {
                input.required = true;
            });
        } else {
            fields.style.display = 'none';
            testBtn.style.display = 'none';
            inputs.forEach(input => {
                input.required = false;
            });
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleLipishaFields();
    });

    // Toggle password visibility for Business Key
    document.getElementById('toggleBusinessKey').addEventListener('click', function() {
        const input = document.getElementById('business_key');
        const icon = document.getElementById('businessKeyIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bx-show');
            icon.classList.add('bx-hide');
        } else {
            input.type = 'password';
            icon.classList.remove('bx-hide');
            icon.classList.add('bx-show');
        }
    });

    // Toggle password visibility for Verify Token
    document.getElementById('toggleVerifyToken').addEventListener('click', function() {
        const input = document.getElementById('verify_token');
        const icon = document.getElementById('verifyTokenIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bx-show');
            icon.classList.add('bx-hide');
        } else {
            input.type = 'password';
            icon.classList.remove('bx-hide');
            icon.classList.add('bx-show');
        }
    });

    // Test Network Connection
    document.getElementById('testNetworkBtn').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        
        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...';

        // Make AJAX request
        fetch('{{ route("settings.lipisha.test-network") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (data.success) {
                let message = data.message;
                if (data.token) {
                    message += '\n\nToken: ' + data.token;
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Connection Successful!',
                    html: message.replace(/\n/g, '<br>'),
                    confirmButtonText: 'OK',
                    width: '600px'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Failed',
                    text: data.message,
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Failed to test connection: ' + error.message,
                confirmButtonText: 'OK'
            });
        });
    });
</script>
@endpush

