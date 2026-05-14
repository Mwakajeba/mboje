@extends('layouts.main')

@section('title', 'Assign Team - Count Session')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => route('inventory.counts.index'), 'icon' => 'bx bx-clipboard-check'],
            ['label' => 'Session: ' . $session->session_number, 'url' => route('inventory.counts.sessions.show', $session->encoded_id), 'icon' => 'bx bx-show'],
            ['label' => 'Assign Team', 'url' => '#', 'icon' => 'bx bx-group']
        ]" />
        
        <h6 class="mb-0 text-uppercase">ASSIGN TEAM MEMBERS</h6>
        <hr />

        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Session: {{ $session->session_number }}</h5>
                                        <p class="text-muted mb-0">Location: {{ $session->location->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="card-body bg-light border-bottom">
                                        <div class="alert alert-info mb-0">
                                            <h6 class="alert-heading">
                                                <i class="bx bx-info-circle me-2"></i>About Assigned Area
                                            </h6>
                                            <p class="mb-0 small">
                                                <strong>Assigned Area</strong> is optional and helps you organize team members by specific sections within the location. 
                                                For example, if your warehouse has multiple zones or sections, you can assign:
                                            </p>
                                            <ul class="mb-0 mt-2 small">
                                                <li><strong>Supervisor:</strong> "Main Floor" or "Entire Warehouse"</li>
                                                <li><strong>Counter 1:</strong> "Section A" or "Zone 1"</li>
                                                <li><strong>Counter 2:</strong> "Section B" or "Zone 2"</li>
                                                <li><strong>Verifier:</strong> "High-Value Items" or "Shelf 1-10"</li>
                                            </ul>
                                            <p class="mb-0 mt-2 small">
                                                <strong>Note:</strong> This is for organization and audit purposes only. You can leave it blank if not needed.
                                            </p>
                                        </div>
                                    </div>
                    <div class="card-body">
                        <form action="{{ route('inventory.counts.sessions.assign-team.store', $session->encoded_id) }}" method="POST" id="team-assignment-form">
                            @csrf
                            
                            <div id="team-members-container">
                                @if($session->teams->count() > 0)
                                    @foreach($session->teams as $index => $team)
                                        <div class="row mb-3 team-member-row" data-index="{{ $index }}">
                                            <div class="col-md-4">
                                                <label class="form-label">User *</label>
                                                <select name="teams[{{ $index }}][user_id]" class="form-select select2-single" required>
                                                    <option value="">Select User</option>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->id }}" {{ $team->user_id == $user->id ? 'selected' : '' }}>
                                                            {{ $user->name }} ({{ $user->email }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Role *</label>
                                                <select name="teams[{{ $index }}][role]" class="form-select" required>
                                                    <option value="counter" {{ $team->role == 'counter' ? 'selected' : '' }}>Counter</option>
                                                    <option value="supervisor" {{ $team->role == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                                    <option value="verifier" {{ $team->role == 'verifier' ? 'selected' : '' }}>Verifier</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">
                                                    Assigned Area
                                                    <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Specific area or section within the location where this team member will work"></i>
                                                </label>
                                                <input type="text" name="teams[{{ $index }}][assigned_area]" class="form-control" 
                                                       value="{{ $team->assigned_area }}" placeholder="e.g., Warehouse A, Section 1, Zone B">
                                                <small class="text-muted d-block mt-1">
                                                    <i class="bx bx-info-circle me-1"></i>
                                                    Optional: Specify the area/section this person will count (e.g., "Warehouse A", "Section 1", "Zone B", "Shelf 1-10")
                                                </small>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="button" class="btn btn-danger w-100 remove-member-btn">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="row mb-3 team-member-row" data-index="0">
                                        <div class="col-md-4">
                                            <label class="form-label">User *</label>
                                            <select name="teams[0][user_id]" class="form-select select2-single" required>
                                                <option value="">Select User</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Role *</label>
                                            <select name="teams[0][role]" class="form-select" required>
                                                <option value="counter">Counter</option>
                                                <option value="supervisor">Supervisor</option>
                                                <option value="verifier">Verifier</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">
                                                Assigned Area
                                                <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Specific area or section within the location where this team member will work"></i>
                                            </label>
                                            <input type="text" name="teams[0][assigned_area]" class="form-control" 
                                                   placeholder="e.g., Warehouse A, Section 1, Zone B">
                                            <small class="text-muted d-block mt-1">
                                                <i class="bx bx-info-circle me-1"></i>
                                                Optional: Specify the area/section this person will count (e.g., "Warehouse A", "Section 1", "Zone B", "Shelf 1-10")
                                            </small>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-danger w-100 remove-member-btn">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-secondary" id="add-member-btn">
                                        <i class="bx bx-plus me-1"></i> Add Team Member
                                    </button>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Save Team Assignment
                                    </button>
                                    <a href="{{ route('inventory.counts.sessions.show', $session->encoded_id) }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    let memberIndex = {{ $session->teams->count() ?? 1 }};

    // Add new team member
    $('#add-member-btn').on('click', function() {
        const html = `
            <div class="row mb-3 team-member-row" data-index="${memberIndex}">
                <div class="col-md-4">
                    <label class="form-label">User *</label>
                    <select name="teams[${memberIndex}][user_id]" class="form-select select2-single" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role *</label>
                    <select name="teams[${memberIndex}][role]" class="form-select" required>
                        <option value="counter">Counter</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="verifier">Verifier</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">
                        Assigned Area
                        <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Specific area or section within the location where this team member will work"></i>
                    </label>
                    <input type="text" name="teams[${memberIndex}][assigned_area]" class="form-control" 
                           placeholder="e.g., Warehouse A, Section 1, Zone B">
                    <small class="text-muted d-block mt-1">
                        <i class="bx bx-info-circle me-1"></i>
                        Optional: Specify the area/section this person will count (e.g., "Warehouse A", "Section 1", "Zone B", "Shelf 1-10")
                    </small>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger w-100 remove-member-btn">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#team-members-container').append(html);
        
        // Initialize Select2 for new row
        $('#team-members-container .team-member-row:last .select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
        
        // Initialize tooltips for new row
        var newTooltipTriggerList = [].slice.call($('#team-members-container .team-member-row:last [data-bs-toggle="tooltip"]'));
        var newTooltipList = newTooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        memberIndex++;
    });

    // Remove team member
    $(document).on('click', '.remove-member-btn', function() {
        if ($('.team-member-row').length > 1) {
            $(this).closest('.team-member-row').remove();
        } else {
            alert('At least one team member is required.');
        }
    });
});
</script>
@endpush
@endsection

