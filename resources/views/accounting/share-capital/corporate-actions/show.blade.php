@extends('layouts.main')

@section('title', 'Corporate Action Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Corporate Actions', 'url' => route('accounting.share-capital.corporate-actions.index'), 'icon' => 'bx bx-refresh'],
            ['label' => $action->reference_number ?? 'CA-' . $action->id, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">CORPORATE ACTION DETAILS</h6>
            <div>
                @if($action->status === 'draft')
                    <a href="{{ route('accounting.share-capital.corporate-actions.edit', $action->encoded_id) }}" class="btn btn-primary me-2">
                        <i class="bx bx-edit"></i> Edit
                    </a>
                @endif
                <a href="{{ route('accounting.share-capital.corporate-actions.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back
                </a>
            </div>
        </div>
        <hr />
        <h6 class="mb-0 text-uppercase">CORPORATE ACTION DETAILS</h6>
        <hr />

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

        <!-- Status Card -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">{{ $action->shareClass->name ?? 'All Share Classes' }}</h5>
                                <p class="mb-0 text-muted">Reference: {{ $action->reference_number ?? 'CA-' . $action->id }}</p>
                            </div>
                            <div class="text-end">
                                @php
                                    $badgeClass = match($action->status) {
                                        'draft' => 'bg-secondary',
                                        'pending_approval' => 'bg-warning',
                                        'approved' => 'bg-primary',
                                        'executed' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }} fs-6 px-3 py-2">{{ strtoupper(str_replace('_', ' ', $action->status)) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle"></i> Corporate Action Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Share Class:</th>
                                <td><strong>{{ $action->shareClass->name ?? 'All Classes' }}</strong></td>
                            </tr>
                            <tr>
                                <th>Action Type:</th>
                                <td>
                                    @php
                                        $typeBadge = match($action->action_type) {
                                            'split' => 'bg-info',
                                            'reverse_split' => 'bg-warning',
                                            'buyback' => 'bg-danger',
                                            'conversion' => 'bg-primary',
                                            'bonus' => 'bg-success',
                                            'rights' => 'bg-secondary',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $typeBadge }}">{{ ucfirst(str_replace('_', ' ', $action->action_type)) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Effective Date:</th>
                                <td>{{ $action->effective_date->format('M d, Y') }}</td>
                            </tr>
                            @if($action->record_date)
                            <tr>
                                <th>Record Date:</th>
                                <td>{{ $action->record_date->format('M d, Y') }}</td>
                            </tr>
                            @endif
                            @if($action->ex_date)
                            <tr>
                                <th>Ex-Date:</th>
                                <td>{{ $action->ex_date->format('M d, Y') }}</td>
                            </tr>
                            @endif
                            @if($action->ratio_numerator && $action->ratio_denominator)
                            <tr>
                                <th>Ratio:</th>
                                <td><strong>{{ $action->ratio_numerator }}:{{ $action->ratio_denominator }}</strong></td>
                            </tr>
                            @endif
                            @if($action->price_per_share)
                            <tr>
                                <th>Price Per Share:</th>
                                <td><strong>{{ number_format($action->price_per_share, 6) }}</strong></td>
                            </tr>
                            @endif
                            @if($action->notes)
                            <tr>
                                <th>Notes:</th>
                                <td>{{ $action->notes }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Actions -->
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0"><i class="bx bx-cog"></i> Actions</h6>
                    </div>
                    <div class="card-body">
                        @if(in_array($action->status, ['draft', 'pending_approval']))
                            <form action="{{ route('accounting.share-capital.corporate-actions.approve', $action->encoded_id) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bx bx-check"></i> Approve Action
                                </button>
                            </form>
                        @endif
                        
                        @if($action->status === 'approved')
                            @if($action->action_type === 'buyback')
                                <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#executeBuybackModal">
                                    <i class="bx bx-check"></i> Execute Buyback
                                </button>
                            @else
                                <form action="{{ route('accounting.share-capital.corporate-actions.execute', $action->encoded_id) }}" method="POST" class="mb-2" onsubmit="return confirm('Are you sure you want to execute this corporate action? This action cannot be undone.');">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bx bx-check"></i> Execute Action
                                    </button>
                                </form>
                            @endif
                        @endif
                        
                        @if($action->is_executed)
                            <div class="alert alert-success mb-0">
                                <i class="bx bx-check-circle"></i> Action Executed
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Audit Information -->
                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-time"></i> Audit Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th>Created:</th>
                                <td>{{ $action->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $action->creator->name ?? 'N/A' }}</td>
                            </tr>
                            @if($action->approver)
                            <tr>
                                <th>Approved:</th>
                                <td>{{ $action->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Approved By:</th>
                                <td>{{ $action->approver->name ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if($action->executor)
                            <tr>
                                <th>Executed:</th>
                                <td>{{ $action->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Executed By:</th>
                                <td>{{ $action->executor->name ?? 'N/A' }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Execute Buyback Modal -->
@if($action->action_type === 'buyback' && $action->status === 'approved')
<div class="modal fade" id="executeBuybackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('accounting.share-capital.corporate-actions.execute', $action->encoded_id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Execute Share Buyback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Total Shares to Buyback <span class="text-danger">*</span></label>
                        <input type="number" name="total_shares" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Cost <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="total_cost" class="form-control" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="form-select" required>
                            <option value="">Select Bank Account</option>
                            <!-- Options should be loaded from backend -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Treasury Shares Account <span class="text-danger">*</span></label>
                        <select name="treasury_shares_account_id" class="form-select" required>
                            <option value="">Select Account</option>
                            <!-- Options should be loaded from backend -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Execute Buyback</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

