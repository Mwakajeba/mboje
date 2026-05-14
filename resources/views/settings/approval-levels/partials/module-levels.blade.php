<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">{{ ucfirst(str_replace('_', ' ', $module)) }} Approval Levels</h5>
    <button type="button" class="btn btn-primary btn-sm" onclick="openCreateLevelModal('{{ $module }}')">
        <i class="bx bx-plus me-1"></i> Add Approval Level
    </button>
</div>

@if($levels->isEmpty())
    <div class="alert alert-info">
        <i class="bx bx-info-circle me-2"></i>
        No approval levels configured for {{ ucfirst(str_replace('_', ' ', $module)) }}. Click "Add Approval Level" to create one.
    </div>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th width="80">Order</th>
                    <th width="100">Level</th>
                    <th>Level Name</th>
                    <th>Description</th>
                    <th width="100">Required</th>
                    <th>Approvers</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody id="levelsTableBody-{{ $module }}">
                @foreach($levels as $level)
                    <tr data-level-id="{{ $level->id }}" data-order="{{ $level->approval_order }}">
                        <td>
                            <span class="badge bg-secondary">{{ $level->approval_order }}</span>
                        </td>
                        <td>
                            <span class="badge bg-primary">Level {{ $level->level }}</span>
                        </td>
                        <td>
                            <strong>{{ $level->level_name }}</strong>
                        </td>
                        <td>
                            <small class="text-muted">-</small>
                        </td>
                        <td>
                            @if($level->is_required)
                                <span class="badge bg-success">Required</span>
                            @else
                                <span class="badge bg-warning">Optional</span>
                            @endif
                        </td>
                        <td>
                            @if($level->assignments->isEmpty())
                                <span class="text-muted">No approvers assigned</span>
                            @else
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($level->assignments as $assignment)
                                        <span class="badge bg-info">
                                            @if($assignment->user)
                                                <i class="bx bx-user me-1"></i>{{ $assignment->user->name }}
                                            @elseif($assignment->role)
                                                <i class="bx bx-shield me-1"></i>{{ $assignment->role->name }}
                                            @endif
                                            @if($assignment->branch)
                                                <small>({{ $assignment->branch->name }})</small>
                                            @else
                                                <small>(Global)</small>
                                            @endif
                                            <button type="button" 
                                                    class="btn-close btn-close-white ms-1" 
                                                    style="font-size: 0.6em;"
                                                    onclick="deleteAssignment({{ $assignment->id }}, '{{ $assignment->user ? $assignment->user->name : ($assignment->role ? $assignment->role->name : 'Approver') }}')"
                                                    title="Remove"></button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" 
                                        class="btn btn-info" 
                                        onclick="openAssignModal({{ $level->id }})"
                                        title="Assign Approver">
                                    <i class="bx bx-user-plus"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-warning" 
                                        onclick="openEditLevelModal({{ $level->id }}, {{ $level->level }}, '{{ addslashes($level->level_name) }}', {{ $level->is_required ? 1 : 0 }})"
                                        title="Edit Level">
                                    <i class="bx bx-edit"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-danger" 
                                        onclick="deleteLevel({{ $level->id }}, '{{ addslashes($level->level_name) }}')"
                                        title="Delete Level">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="alert alert-warning mt-3">
        <i class="bx bx-info-circle me-2"></i>
        <strong>Note:</strong> Approval levels are processed in order (1, 2, 3...). Items must be approved at each required level before moving to the next level.
    </div>
@endif

