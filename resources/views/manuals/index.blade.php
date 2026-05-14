@extends('layouts.main')

@section('title', 'User Manuals')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'User Manuals', 'url' => '#', 'icon' => 'bx bx-book']
        ]" />

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">
                    <i class="bx bx-book me-2"></i>User Manuals
                </h5>
                <small class="text-muted">Download and view system documentation</small>
            </div>
            @if(auth()->user()->hasRole('Admin'))
            <div>
                <button type="button" class="btn btn-primary" onclick="generateManual()">
                    <i class="bx bx-refresh me-1"></i> Regenerate Manuals
                </button>
            </div>
            @endif
        </div>

        <!-- Manuals Grid -->
        <div class="row">
            @foreach($manuals as $manual)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <i class="bx bx-file-doc text-primary" style="font-size: 3rem;"></i>
                        </div>
                        
                        <h6 class="card-title text-center">{{ $manual['title'] }}</h6>
                        <p class="card-text text-muted text-center flex-grow-1">
                            {{ $manual['description'] }}
                        </p>
                        
                        <div class="manual-info mb-3">
                            <small class="text-muted d-block">
                                <i class="bx bx-file me-1"></i>Size: {{ $manual['size'] }}
                            </small>
                            <small class="text-muted d-block">
                                <i class="bx bx-time me-1"></i>Updated: {{ $manual['last_updated'] }}
                            </small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route($manual['view_route']) }}" 
                               class="btn btn-outline-primary" 
                               target="_blank">
                                <i class="bx bx-show me-1"></i>View Online
                            </a>
                            <a href="{{ route($manual['download_route']) }}" 
                               class="btn btn-primary">
                                <i class="bx bx-download me-1"></i>Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if(empty($manuals))
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bx bx-book-open text-muted" style="font-size: 4rem;"></i>
                <h5 class="mt-3 text-muted">No Manuals Available</h5>
                <p class="text-muted">User manuals will appear here when they are generated.</p>
                @if(auth()->user()->hasRole('Admin'))
                <button type="button" class="btn btn-primary mt-3" onclick="generateManual()">
                    <i class="bx bx-plus me-1"></i> Generate Manuals
                </button>
                @endif
            </div>
        </div>
        @endif

        <!-- Manual Information -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>About User Manuals
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">What are User Manuals?</h6>
                                <p class="text-muted">
                                    User manuals provide comprehensive documentation for each system module, 
                                    including step-by-step instructions, screenshots, and best practices.
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">How to Use</h6>
                                <ul class="text-muted">
                                    <li><strong>View Online:</strong> Read manuals directly in your browser</li>
                                    <li><strong>Download PDF:</strong> Save manuals for offline reference</li>
                                    <li><strong>Search:</strong> Use Ctrl+F to find specific topics</li>
                                    <li><strong>Print:</strong> Print sections you need for training</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function generateManual() {
    Swal.fire({
        title: 'Generate Manuals',
        text: 'This will regenerate all user manuals. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, generate!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Generating Manuals...',
                text: 'Please wait while the manuals are being generated.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Make AJAX request
            $.ajax({
                url: "{{ route('manuals.generate') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Manuals have been generated successfully.',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    let message = 'Failed to generate manuals.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', message, 'error');
                }
            });
        }
    });
}
</script>
@endpush