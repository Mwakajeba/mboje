@extends('layouts.main')

@section('title', 'Guest Messages')

@push('styles')
<style>
    .message-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .message-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .unread-badge {
        position: absolute;
        top: 10px;
        right: 10px;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-hotel'],
            ['label' => 'Guest Messages', 'url' => '#', 'icon' => 'bx bx-mail']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">GUEST MESSAGES</h6>
            @if($unreadCount > 0)
                <span class="badge bg-danger">
                    {{ $unreadCount }} Unread Message{{ $unreadCount > 1 ? 's' : '' }}
                </span>
            @endif
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

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" data-bs-toggle="tab" href="#all" role="tab">
                    All Messages ({{ $messages->total() }})
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" href="#unread" role="tab">
                    Unread ({{ $unreadCount }})
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" href="#responded" role="tab">
                    Responded
                </a>
            </li>
        </ul>

        <!-- Messages List -->
        @if($messages->count() > 0)
            <div class="row">
                @foreach($messages as $message)
                    <div class="col-12 mb-3">
                        <div class="card message-card {{ !$message->is_read ? 'border-primary' : '' }} h-100">
                            @if(!$message->is_read)
                                <span class="badge bg-danger unread-badge">New</span>
                            @endif
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <i class="bx bx-envelope me-2"></i>{{ $message->subject }}
                                        </h5>
                                        <p class="text-muted mb-1">
                                            <i class="bx bx-user me-1"></i><strong>{{ $message->name }}</strong>
                                            <span class="mx-2">•</span>
                                            <i class="bx bx-envelope me-1"></i>{{ $message->email }}
                                        </p>
                                        @if($message->branch)
                                            <p class="text-muted mb-1">
                                                <i class="bx bx-building me-1"></i>{{ $message->branch->name }}
                                            </p>
                                        @endif
                                        <p class="text-muted mb-0 small">
                                            <i class="bx bx-time me-1"></i>{{ $message->created_at->format('M d, Y g:i A') }}
                                            @if($message->source)
                                                <span class="mx-2">•</span>
                                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $message->source)) }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        @if($message->response)
                                            <span class="badge bg-success">
                                                <i class="bx bx-check-circle me-1"></i>Responded
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="bx bx-time me-1"></i>Pending
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="message-preview mb-3">
                                    <p class="card-text">
                                        {{ Str::limit($message->message, 150) }}
                                    </p>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        @if($message->is_read && $message->readBy)
                                            <small class="text-muted">
                                                Read by {{ $message->readBy->name }} on {{ $message->read_at->format('M d, Y g:i A') }}
                                            </small>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('hotel.guest-messages.show', $message) }}" class="btn btn-primary btn-sm">
                                            <i class="bx bx-show me-1"></i>View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $messages->links() }}
            </div>
        @else
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bx bx-mail text-muted" style="font-size: 4rem;"></i>
                    <h5 class="mt-3">No Messages Found</h5>
                    <p class="text-muted">There are no guest messages at this time.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Filter functionality
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        
        if (target === '#unread') {
            $('.message-card').each(function() {
                if ($(this).find('.unread-badge').length > 0) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        } else if (target === '#responded') {
            $('.message-card').each(function() {
                if ($(this).find('.badge.bg-success').length > 0) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        } else {
            $('.message-card').show();
        }
    });
});
</script>
@endpush
