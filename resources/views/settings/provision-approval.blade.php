@extends('layouts.main')

@section('title', 'Provision Approval Settings (IAS 37)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Provision Approval (IAS 37)', 'url' => '#', 'icon' => 'bx bx-check-shield']
        ]" />
        <h6 class="mb-0 text-uppercase">PROVISION APPROVAL SETTINGS (IAS 37)</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @can('manage system settings')
                        <h4 class="card-title mb-4">Provision Approval Workflow</h4>

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(isset($errors) && $errors->any())
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

                        @php
                            $maxLevels = 5;
                            $existingLevels = $levels ?? collect();
                            $currentLevelCount = $existingLevels->count();
                        @endphp

                        <form action="{{ route('settings.provision-approval.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="approval_levels" class="form-label">Number of Approval Levels</label>
                                    <select class="form-select" id="approval_levels" name="approval_levels">
                                        @for($i = 0; $i <= $maxLevels; $i++)
                                            <option value="{{ $i }}" {{ old('approval_levels', $currentLevelCount) == $i ? 'selected' : '' }}>
                                                {{ $i === 0 ? 'No approval (auto-approve provisions)' : $i . ' Level(s)' }}
                                            </option>
                                        @endfor
                                    </select>
                                    <small class="text-muted">
                                        0 = auto-approve all provisions; 1–5 = number of approval layers required before provisions become approved.
                                    </small>
                                </div>
                            </div>

                            <div id="approval_levels_container">
                                @for($level = 1; $level <= $maxLevels; $level++)
                                    @php
                                        $levelModel = $existingLevels->firstWhere('level', $level);
                                        $levelOld = old("levels.$level", []);
                                        $levelName = $levelOld['level_name'] ?? ($levelModel->level_name ?? "Level $level Approval");

                                        // Detect default approval type from assignments if possible
                                        $defaultType = 'role';
                                        if ($levelModel && $levelModel->assignments->count() > 0) {
                                            $hasUser = $levelModel->assignments->whereNotNull('user_id')->count() > 0;
                                            $defaultType = $hasUser ? 'user' : 'role';
                                        }
                                        $approvalType = $levelOld['approval_type'] ?? $defaultType;

                                        // Build default selected approvers from assignments (role_X / user_Y)
                                        $defaultApprovers = [];
                                        if ($levelModel) {
                                            foreach ($levelModel->assignments as $assignment) {
                                                if ($assignment->role_id) {
                                                    $role = $roles->firstWhere('id', $assignment->role_id);
                                                    if ($role) {
                                                        $defaultApprovers[] = 'role_'.$role->name;
                                                    }
                                                } elseif ($assignment->user_id) {
                                                    $defaultApprovers[] = 'user_'.$assignment->user_id;
                                                }
                                            }
                                        }

                                        $selectedApprovers = $levelOld['approvers'] ?? $defaultApprovers;
                                    @endphp
                                    <div class="card mb-3 approval-level-card" data-level="{{ $level }}">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Level {{ $level }} Approvers</h6>
                                            <span class="badge bg-secondary">Step {{ $level }}</span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Level Name</label>
                                                    <input type="text" name="levels[{{ $level }}][level_name]" class="form-control"
                                                        value="{{ $levelName }}">
                                                    <small class="text-muted">e.g. “Finance Manager Approval”, “CFO Approval”.</small>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Approval Type</label>
                                                    <select class="form-select approval-type-select" name="levels[{{ $level }}][approval_type]" data-level="{{ $level }}">
                                                        <option value="role" {{ $approvalType === 'role' ? 'selected' : '' }}>By Role</option>
                                                        <option value="user" {{ $approvalType === 'user' ? 'selected' : '' }}>By User</option>
                                                    </select>
                                                    <small class="text-muted">Choose whether approvers are defined by role or specific user accounts.</small>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Approvers</label>
                                                    <select
                                                        class="form-select approvers-select"
                                                        name="levels[{{ $level }}][approvers][]"
                                                        multiple
                                                        data-level="{{ $level }}"
                                                        data-selected='@json($selectedApprovers)'>
                                                    </select>
                                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple approvers.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Update Provision Approval
                                    </button>
                                    <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-arrow-back me-1"></i> Back to Settings
                                    </a>
                                </div>
                            </div>
                        </form>
                        @else
                        <div class="alert alert-warning" role="alert">
                            <i class="bx bx-lock me-2"></i>
                            You don't have permission to manage provision approval settings.
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function () {
        const approvalLevelsSelect = document.getElementById('approval_levels');
        const maxLevels = {{ $maxLevels ?? 5 }};

        function toggleLevelCards() {
            const selected = parseInt(approvalLevelsSelect.value || '0', 10);
            document.querySelectorAll('.approval-level-card').forEach(function (card) {
                const level = parseInt(card.getAttribute('data-level'), 10);
                card.style.display = level <= selected ? 'block' : 'none';
            });
        }

        toggleLevelCards();
        approvalLevelsSelect.addEventListener('change', toggleLevelCards);

        // Dynamic approver options based on approval type (role vs user)
        @php
            $rolesData = isset($roles) ? $roles->pluck('name')->values() : collect();
            $usersData = isset($users) ? $users->map(function($u){ return ['id'=>$u->id,'name'=>$u->name,'email'=>$u->email]; })->values() : collect();
        @endphp
        const rolesData = @json($rolesData);
        const usersData = @json($usersData);

        function buildOptionsForLevel(level, type, selectedValues) {
            const select = document.querySelector('.approvers-select[data-level="' + level + '"]');
            if (!select) return;

            // Clear current options
            select.innerHTML = '';

            if (type === 'role') {
                rolesData.forEach(function (name) {
                    const value = 'role_' + name;
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = (name.charAt(0).toUpperCase() + name.slice(1)) + ' (Role)';
                    if (selectedValues && selectedValues.includes(value)) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });
            } else if (type === 'user') {
                usersData.forEach(function (u) {
                    const value = 'user_' + u.id;
                    const opt = document.createElement('option');
                    opt.value = value;
                    opt.textContent = u.name + (u.email ? ' (' + u.email + ')' : '');
                    if (selectedValues && selectedValues.includes(value)) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });
            }
        }

        // Initialisation for each level
        const levelCards = document.querySelectorAll('.approval-level-card');
        levelCards.forEach(function (card) {
            const level = parseInt(card.getAttribute('data-level'), 10);
            const typeSelect = card.querySelector('.approval-type-select');
            const approversSelect = card.querySelector('.approvers-select');

            if (!typeSelect || !approversSelect) {
                return;
            }

            // Read selected values from data-selected attribute
            let selectedValues = [];
            const dataSelected = approversSelect.getAttribute('data-selected');
            if (dataSelected) {
                try {
                    selectedValues = JSON.parse(dataSelected);
                } catch (e) {
                    selectedValues = [];
                }
            }

            buildOptionsForLevel(level, typeSelect.value, selectedValues);

            typeSelect.addEventListener('change', function () {
                // When type changes, clear previous selections and rebuild options
                buildOptionsForLevel(level, this.value, []);
            });
        });
    });
</script>
@endpush


