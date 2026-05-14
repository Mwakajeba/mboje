@extends('layouts.main')

@section('title', 'Shareholder Details')

@push('styles')
<style>
    .info-card {
        border-left: 4px solid;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .info-card.border-primary {
        border-left-color: #0d6efd;
    }
    
    .info-card.border-success {
        border-left-color: #198754;
    }
    
    .info-card.border-warning {
        border-left-color: #ffc107;
    }
    
    .info-card.border-info {
        border-left-color: #0dcaf0;
    }
    
    .info-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .info-value {
        color: #212529;
        font-size: 1rem;
    }
    
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 27px;
    }
    
    .bg-light-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-light-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-light-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-light-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Shareholders', 'url' => route('accounting.share-capital.shareholders.index'), 'icon' => 'bx bx-user'],
            ['label' => $shareholder->name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">SHAREHOLDER DETAILS</h6>
            <div>
                <a href="{{ route('accounting.share-capital.shareholders.edit', $shareholder->encoded_id) }}" class="btn btn-primary me-2">
                    <i class="bx bx-edit"></i> Edit
                </a>
                <a href="{{ route('accounting.share-capital.shareholders.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back
                </a>
            </div>
        </div>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-primary radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Total Shares</h6>
                                <h4 class="mb-0">{{ number_format($totalShares ?? 0) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary">
                                <i class="bx bx-trending-up"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-success radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Active Holdings</h6>
                                <h4 class="mb-0">{{ number_format($totalHoldings ?? 0) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-info radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Share Classes</h6>
                                <h4 class="mb-0">{{ $shareholder->shareHoldings()->where('status', 'active')->distinct('share_class_id')->count() }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info">
                                <i class="bx bx-category"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-warning radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Status</h6>
                                <h4 class="mb-0">
                                    @if($shareholder->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning">
                                <i class="bx bx-info-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Left Column - Main Information -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card radius-10 border-0 shadow-sm mb-3">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2 text-primary"></i>Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Code</div>
                                    <div class="info-value"><strong>{{ $shareholder->code ?? 'SH-' . $shareholder->shareholder_id }}</strong></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Name</div>
                                    <div class="info-value">{{ $shareholder->name }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Type</div>
                                    <div class="info-value">
                                        @php
                                            $badgeClass = match($shareholder->type) {
                                                'individual' => 'bg-primary',
                                                'corporate' => 'bg-info',
                                                'government' => 'bg-warning',
                                                'employee' => 'bg-success',
                                                'related_party' => 'bg-danger',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $shareholder->type)) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Related Party</div>
                                    <div class="info-value">
                                        @if($shareholder->is_related_party)
                                            <span class="badge bg-danger">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card radius-10 border-0 shadow-sm mb-3">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="mb-0"><i class="bx bx-envelope me-2 text-primary"></i>Contact Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value">
                                        @if($shareholder->email)
                                            <a href="mailto:{{ $shareholder->email }}">{{ $shareholder->email }}</a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Phone</div>
                                    <div class="info-value">
                                        @if($shareholder->phone)
                                            <a href="tel:{{ $shareholder->phone }}">{{ $shareholder->phone }}</a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Country</div>
                                    <div class="info-value">
                                        @if($shareholder->country)
                                            {{ $shareholder->country }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Tax ID</div>
                                    <div class="info-value">
                                        @if($shareholder->tax_id)
                                            {{ $shareholder->tax_id }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="info-item">
                                    <div class="info-label">Address</div>
                                    <div class="info-value">
                                        @if($shareholder->address)
                                            {{ $shareholder->address }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Party Notes -->
                @if($shareholder->is_related_party && $shareholder->related_party_notes)
                <div class="card radius-10 border-0 shadow-sm mb-3 border-left border-danger" style="border-left-width: 4px;">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="mb-0"><i class="bx bx-error-circle me-2 text-danger"></i>Related Party Notes</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $shareholder->related_party_notes }}</p>
                    </div>
                </div>
                @endif

                <!-- Share Holdings -->
                @if($shareholder->shareHoldings->where('status', 'active')->count() > 0)
                <div class="card radius-10 border-0 shadow-sm mb-3">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="mb-0"><i class="bx bx-list-ul me-2 text-primary"></i>Share Holdings</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Share Class</th>
                                        <th class="text-end">Shares Outstanding</th>
                                        <th class="text-end">Paid Up Amount</th>
                                        <th class="text-end">Unpaid Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shareholder->shareHoldings->where('status', 'active') as $holding)
                                    <tr>
                                        <td>
                                            <strong>{{ $holding->shareClass->name ?? 'N/A' }}</strong>
                                            @if($holding->shareClass)
                                                <br><small class="text-muted">{{ $holding->shareClass->code }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end"><strong>{{ number_format($holding->shares_outstanding) }}</strong></td>
                                        <td class="text-end">{{ number_format($holding->paid_up_amount, 2) }}</td>
                                        <td class="text-end">
                                            @if($holding->unpaid_amount > 0)
                                                <span class="text-danger">{{ number_format($holding->unpaid_amount, 2) }}</span>
                                            @else
                                                <span class="text-success">{{ number_format($holding->unpaid_amount, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ ucfirst($holding->status) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @else
                <div class="card radius-10 border-0 shadow-sm mb-3">
                    <div class="card-body text-center py-5">
                        <i class="bx bx-info-circle text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 mb-0">No active share holdings found for this shareholder.</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Sidebar -->
            <div class="col-lg-4">
                <!-- Audit Information -->
                <div class="card radius-10 border-0 shadow-sm mb-3">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="mb-0"><i class="bx bx-time me-2 text-secondary"></i>Audit Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <div class="info-label">Created</div>
                            <div class="info-value">{{ $shareholder->created_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Created By</div>
                            <div class="info-value">{{ $shareholder->creator->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Last Updated</div>
                            <div class="info-value">{{ $shareholder->updated_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Updated By</div>
                            <div class="info-value">{{ $shareholder->updater->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="mb-0"><i class="bx bx-cog me-2 text-secondary"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('accounting.share-capital.shareholders.edit', $shareholder->encoded_id) }}" class="btn btn-primary">
                                <i class="bx bx-edit me-1"></i> Edit Shareholder
                            </a>
                            <a href="{{ route('accounting.share-capital.share-issues.create') }}?shareholder_id={{ $shareholder->encoded_id }}" class="btn btn-outline-primary">
                                <i class="bx bx-plus me-1"></i> Issue Shares
                            </a>
                            <a href="{{ route('accounting.share-capital.shareholders.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
