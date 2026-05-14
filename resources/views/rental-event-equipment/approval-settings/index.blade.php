@extends('layouts.main')

@section('title', 'Rental Approval Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Approval Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Rental Approval Settings</h5>
                <small class="text-muted">Configure multi-level approval workflow for rental operations</small>
            </div>
            <div>
                <a href="{{ route('rental-event-equipment.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back
                </a>
            </div>
        </div>

        <div class="alert alert-info border-0">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle fs-4 me-3"></i>
                <div>
                    <h6 class="mb-1">Rental Approval Workflow</h6>
                    <p class="mb-0"><strong>When enabled:</strong> New documents (quotations, contracts, etc.) are set to <strong>Pending for approval</strong>. Approvers must approve before actions like Convert to Contract are available. Rejected documents can be edited and resubmitted for approval.</p>
                    <p class="mb-0 mt-2"><strong>When disabled:</strong> New documents are <strong>approved automatically</strong> and immediately available for use.</p>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('rental-event-equipment.approval-settings.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <!-- Basic Settings Column -->
                        <div class="col-lg-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 text-primary">
                                        <i class="bx bx-cog me-2"></i>Basic Configuration
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="approval_required" 
                                                   name="approval_required" value="1"
                                                   {{ old('approval_required', $settings?->approval_required) ? 'checked' : '' }}
                                                   onchange="toggleApprovalLevels()">
                                            <label class="form-check-label" for="approval_required">
                                                <strong>Enable Approval System</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">Require approval for all rental operations</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="approval_levels" class="form-label">
                                            <i class="bx bx-layer-plus me-1"></i>Number of Approval Levels
                                        </label>
                                        <select class="form-select" id="approval_levels" name="approval_levels" onchange="updateLevelVisibility()">
                                            @for($i = 1; $i <= 5; $i++)
                                                <option value="{{ $i }}" {{ old('approval_levels', $settings?->approval_levels ?? 1) == $i ? 'selected' : '' }}>
                                                    {{ $i }} Level{{ $i > 1 ? 's' : '' }}
                                                </option>
                                            @endfor
                                        </select>
                                        <small class="text-muted">How many approval levels required</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">
                                            <i class="bx bx-note me-1"></i>Notes
                                        </label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                                  placeholder="Additional notes about the approval workflow">{{ old('notes', $settings?->notes) }}</textarea>
                                    </div>

                                    @if($settings)
                                        <div class="mt-4 p-3 bg-light rounded">
                                            <h6 class="text-primary mb-3">Current Status</h6>
                                            <div class="mb-2">
                                                <span class="text-muted small">Status:</span>
                                                <span class="badge {{ $settings->approval_required ? 'bg-success' : 'bg-secondary' }} ms-2">
                                                    {{ $settings->approval_required ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="text-muted small">Levels:</span>
                                                <span class="badge bg-primary ms-2">{{ $settings->approval_levels }}</span>
                                            </div>
                                            <div class="text-muted small">
                                                Last Updated: {{ $settings->updated_at?->diffForHumans() ?? 'Never' }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Approval Levels Configuration Column -->
                        <div class="col-lg-8">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 text-primary">
                                        <i class="bx bx-user-check me-2"></i>Level Configuration
                                    </h6>
                                </div>
                                <div class="card-body" id="approval_levels_config">
                                    <div class="alert alert-primary mb-4">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>How it works:</strong> Set amount thresholds and select approvers for each level. Documents above the threshold will require approval at that level.
                                    </div>
                                    
                                    @for($level = 1; $level <= 5; $level++)
                                        <div class="approval-level-config mb-4 p-3 border rounded" id="level_{{ $level }}_config"
                                             style="{{ $level <= old('approval_levels', $settings?->approval_levels ?? 1) ? '' : 'display: none;' }}">
                                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                                <h6 class="mb-0 text-primary">
                                                    <span class="badge bg-primary me-2">{{ $level }}</span>
                                                    Level {{ $level }} Approval
                                                </h6>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="level{{ $level }}_amount_threshold" class="form-label">
                                                        <i class="bx bx-money me-1"></i>Amount Threshold
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">TZS</span>
                                                        @php
                                                            $thresholdProperty = 'level' . $level . '_amount_threshold';
                                                            $thresholdValue = old('level' . $level . '_amount_threshold', $settings ? ($settings->$thresholdProperty ?? null) : null);
                                                        @endphp
                                                        <input type="number" class="form-control"
                                                               id="level{{ $level }}_amount_threshold"
                                                               name="level{{ $level }}_amount_threshold"
                                                               min="0" step="0.01"
                                                               value="{{ $thresholdValue }}"
                                                               placeholder="0.00">
                                                    </div>
                                                    <small class="text-muted">Documents above this amount need this level approval</small>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="level{{ $level }}_approvers" class="form-label">
                                                        <i class="bx bx-user-circle me-1"></i>Approvers
                                                    </label>
                                                    <select class="form-select" id="level{{ $level }}_approvers" 
                                                            name="level{{ $level }}_approvers[]" multiple size="5">
                                                        @foreach($users as $user)
                                                            @php
                                                                $approverProperty = 'level' . $level . '_approvers';
                                                                $savedApprovers = $settings ? ($settings->$approverProperty ?? []) : [];
                                                                if (is_string($savedApprovers)) {
                                                                    $savedApprovers = json_decode($savedApprovers, true) ?? [];
                                                                }
                                                                if (!is_array($savedApprovers)) {
                                                                    $savedApprovers = [];
                                                                }
                                                                $isSelected = in_array($user->id, $savedApprovers);
                                                            @endphp
                                                            <option value="{{ $user->id }}" {{ $isSelected ? 'selected' : '' }}>
                                                                {{ $user->name }} ({{ $user->email }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple approvers</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('rental-event-equipment.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-x me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleApprovalLevels() {
    const approvalRequired = document.getElementById('approval_required').checked;
    const configSection = document.getElementById('approval_levels_config');
    
    if (approvalRequired) {
        configSection.style.display = 'block';
    } else {
        configSection.style.display = 'none';
    }
}

function updateLevelVisibility() {
    const selectedLevels = parseInt(document.getElementById('approval_levels').value);
    
    for (let i = 1; i <= 5; i++) {
        const levelConfig = document.getElementById('level_' + i + '_config');
        if (levelConfig) {
            if (i <= selectedLevels) {
                levelConfig.style.display = 'block';
            } else {
                levelConfig.style.display = 'none';
            }
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleApprovalLevels();
    updateLevelVisibility();
});
</script>
@endpush
