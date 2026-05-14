@extends('layouts.main')

@section('title', 'View Message')

@push('styles')
<style>
    .message-box {
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
        padding: 1.5rem;
        border-radius: 0.5rem;
    }
    
    .response-box {
        background-color: #d1e7dd;
        border-left: 4px solid #198754;
        padding: 1.5rem;
        border-radius: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-hotel'],
            ['label' => 'Guest Messages', 'url' => route('hotel.guest-messages.index'), 'icon' => 'bx bx-mail'],
            ['label' => 'View Message', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">VIEW MESSAGE</h6>
            <a href="{{ route('hotel.guest-messages.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to Messages
            </a>
        </div>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <!-- Message Details -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-envelope me-2"></i>Message Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject</label>
                            <p class="form-control-plaintext">{{ $message->subject }}</p>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">From</label>
                                <p class="form-control-plaintext">
                                    <i class="bx bx-user me-1"></i>{{ $message->name }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <p class="form-control-plaintext">
                                    <i class="bx bx-envelope me-1"></i>
                                    <a href="mailto:{{ $message->email }}">{{ $message->email }}</a>
                                </p>
                            </div>
                        </div>

                        @if($message->branch)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Branch</label>
                                <p class="form-control-plaintext">
                                    <i class="bx bx-building me-1"></i>{{ $message->branch->name }}
                                </p>
                            </div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date</label>
                                <p class="form-control-plaintext">
                                    <i class="bx bx-time me-1"></i>{{ $message->created_at->format('M d, Y g:i A') }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Source</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $message->source ?? 'contact_page')) }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Message</label>
                            <div class="message-box">
                                {!! nl2br(e($message->message)) !!}
                            </div>
                        </div>

                        @if($message->is_read && $message->readBy)
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                Read by <strong>{{ $message->readBy->name }}</strong> on {{ $message->read_at->format('M d, Y g:i A') }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Response Section -->
                <div class="card">
                    <div class="card-header {{ $message->response ? 'bg-success text-white' : 'bg-warning' }}">
                        <h5 class="mb-0">
                            <i class="bx bx-reply me-2"></i>
                            {{ $message->response ? 'Response' : 'Send Response' }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($message->response)
                            <div class="response-box mb-3">
                                {!! nl2br(e($message->response)) !!}
                            </div>
                            @if($message->respondedBy)
                                <p class="text-muted small mb-3">
                                    <i class="bx bx-user me-1"></i>Responded by <strong>{{ $message->respondedBy->name }}</strong> 
                                    on {{ $message->responded_at->format('M d, Y g:i A') }}
                                </p>
                            @endif
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#responseModal">
                                <i class="bx bx-edit me-1"></i>Update Response
                            </button>
                        @else
                            <form action="{{ route('hotel.guest-messages.respond', $message) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="response" class="form-label fw-bold">Response Message</label>
                                    <textarea 
                                        class="form-control @error('response') is-invalid @enderror" 
                                        id="response" 
                                        name="response" 
                                        rows="6" 
                                        required
                                        placeholder="Type your response here...">{{ old('response') }}</textarea>
                                    @error('response')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="bx bx-send me-1"></i>Send Response
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if(!$message->is_read)
                                <form action="{{ route('hotel.guest-messages.mark-read', $message) }}" method="POST" class="d-inline" id="markReadForm">
                                    @csrf
                                    <button type="submit" class="btn btn-info w-100">
                                        <i class="bx bx-check me-1"></i>Mark as Read
                                    </button>
                                </form>
                            @endif
                            
                            <a href="mailto:{{ $message->email }}?subject=Re: {{ $message->subject }}" class="btn btn-primary w-100">
                                <i class="bx bx-envelope me-1"></i>Reply via Email
                            </a>

                            <a href="{{ route('hotel.guest-messages.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Message Status -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="badge {{ $message->is_read ? 'bg-success' : 'bg-warning' }}">
                                {{ $message->is_read ? 'Read' : 'Unread' }}
                            </span>
                        </div>
                        <div>
                            <span class="badge {{ $message->response ? 'bg-success' : 'bg-danger' }}">
                                {{ $message->response ? 'Responded' : 'Pending Response' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Response Modal -->
@if($message->response)
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="responseModalLabel">Update Response</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('hotel.guest-messages.respond', $message) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="response_update" class="form-label fw-bold">Response Message</label>
                        <textarea 
                            class="form-control @error('response') is-invalid @enderror" 
                            id="response_update" 
                            name="response" 
                            rows="8" 
                            required>{{ old('response', $message->response) }}</textarea>
                        @error('response')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-send me-1"></i>Update Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle mark as read form submission
    $('#markReadForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            },
            error: function() {
                alert('Failed to mark message as read');
            }
        });
    });
});
</script>
@endpush
