@extends('layouts.main')

@section('title', 'Account Transfer Approval Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Account Transfer Approval', 'url' => '#', 'icon' => 'bx bx-check-shield']
        ]" />
        <h6 class="mb-0 text-uppercase">ACCOUNT TRANSFER APPROVAL SETTINGS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @can('manage system settings')
                        <h4 class="card-title mb-4">Account Transfer Approval - Simple Configuration</h4>

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

                        <!-- Current Assignments Section -->
                        @if($settings && $settings->require_approval_for_all)
                        <div class="card mb-4 border-info">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bx bx-list-check me-2"></i>Current Approval Assignments</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Approval Required:</strong> 
                                        <span class="badge bg-success">Yes - All Transfers</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Approval Levels:</strong> 
                                        <span class="badge bg-primary">{{ $settings->approval_levels ?? 0 }} Level(s)</span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    @for($level = 1; $level <= ($settings->approval_levels ?? 0); $level++)
                                        @php
                                            $approvalType = $settings->{"level{$level}_approval_type"} ?? null;
                                            $approvers = $settings->{"level{$level}_approvers"} ?? [];
                                        @endphp
                                        @if($approvalType)
                                        <div class="col-md-6 mb-3">
                                            <div class="card border-primary">
                                                <div class="card-header bg-primary text-white py-2">
                                                    <h6 class="mb-0">Level {{ $level }} Approvers</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-2">
                                                        <strong>Type:</strong> 
                                                        <span class="badge bg-secondary">{{ ucfirst($approvalType) }}</span>
                                                    </div>
                                                    <div>
                                                        <strong>Assigned:</strong>
                                                        <ul class="list-unstyled mb-0 mt-2">
                                                            @if($approvalType === 'role')
                                                                @php
                                                                    // Handle both formats: "role_admin" or just "admin"
                                                                    $roleNames = [];
                                                                    foreach($approvers as $approver) {
                                                                        if (is_string($approver)) {
                                                                            if (strpos($approver, 'role_') === 0) {
                                                                                // Format: "role_admin"
                                                                                $roleNames[] = str_replace('role_', '', $approver);
                                                                            } else {
                                                                                // Format: "admin" (already processed)
                                                                                $roleNames[] = $approver;
                                                                            }
                                                                        }
                                                                    }
                                                                    $roleNames = array_unique($roleNames);
                                                                @endphp
                                                                @if(count($roleNames) > 0)
                                                                    @foreach($roleNames as $roleName)
                                                                        @php
                                                                            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                                                                        @endphp
                                                                        @if($role)
                                                                            <li class="mb-1">
                                                                                <i class="bx bx-user-check me-1 text-primary"></i>
                                                                                <strong>{{ ucfirst($role->name) }}</strong> (Role)
                                                                                @php
                                                                                    $usersWithRole = \App\Models\User::role($roleName)->where('company_id', $settings->company_id)->get();
                                                                                @endphp
                                                                                @if($usersWithRole->count() > 0)
                                                                                    <br><small class="text-muted ms-3">
                                                                                        Users: {{ $usersWithRole->pluck('name')->join(', ') }}
                                                                                    </small>
                                                                                @endif
                                                                            </li>
                                                                        @endif
                                                                    @endforeach
                                                                @else
                                                                    <li class="text-muted"><em>No roles assigned</em></li>
                                                                @endif
                                                            @elseif($approvalType === 'user')
                                                                @php
                                                                    // Handle both formats: "user_123" or just 123 (integer)
                                                                    $userIds = [];
                                                                    foreach($approvers as $approver) {
                                                                        if (is_numeric($approver)) {
                                                                            // Format: 123 (already processed integer)
                                                                            $userIds[] = (int)$approver;
                                                                        } elseif (is_string($approver) && strpos($approver, 'user_') === 0) {
                                                                            // Format: "user_123"
                                                                            $userIds[] = (int)str_replace('user_', '', $approver);
                                                                        }
                                                                    }
                                                                    $userIds = array_unique($userIds);
                                                                @endphp
                                                                @if(count($userIds) > 0)
                                                                    @foreach($userIds as $userId)
                                                                        @php
                                                                            $user = \App\Models\User::find($userId);
                                                                        @endphp
                                                                        @if($user)
                                                                            <li class="mb-1">
                                                                                <i class="bx bx-user me-1 text-success"></i>
                                                                                <strong>{{ $user->name }}</strong>
                                                                                <br><small class="text-muted ms-3">{{ $user->email }}</small>
                                                                            </li>
                                                                        @endif
                                                                    @endforeach
                                                                @else
                                                                    <li class="text-muted"><em>No users assigned</em></li>
                                                                @endif
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    @endfor
                                </div>
                                
                                @if(($settings->approval_levels ?? 0) == 0)
                                <div class="alert alert-warning mb-0">
                                    <i class="bx bx-info-circle me-2"></i>
                                    No approval levels configured. Please configure approval settings below.
                                </div>
                                @endif
                            </div>
                        </div>
                        @else
                        <div class="card mb-4 border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Current Approval Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-0">
                                    <strong>Approval Required:</strong> 
                                    <span class="badge bg-secondary">No - Direct Posting</span>
                                    <br>
                                    <small class="text-muted">All account transfers are currently saved directly to GL transactions without requiring approval.</small>
                                </div>
                            </div>
                        </div>
                        @endif

                        <form action="{{ route('settings.account-transfer-approval.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <!-- Require Approval for All -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="require_approval_for_all" name="require_approval_for_all" value="1" {{ old('require_approval_for_all', $settings->require_approval_for_all ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="require_approval_for_all">
                                            Require Approval for All Transfers
                                        </label>
                                        <small class="form-text text-muted d-block">If checked, all transfers require approval regardless of amount</small>
                                    </div>
                                </div>
                            </div>
                                
                            <!-- Direct posting note when approvals are disabled -->
                            <div class="row" id="direct_post_note" style="display:none;">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="bx bx-info-circle me-2"></i>
                                        All account transfers will be saved directly to GL transactions. No approvals will be required.
                                    </div>
                                </div>
                            </div>

                            <!-- Approval Configuration (visible only if require_approval_for_all is checked) -->
                            <div id="approval_config">
                            <div class="row">
                                <div class="col-md-6 mb-3" id="levels_block">
                                    <label for="approval_levels" class="form-label">Number of Approval Levels</label>
                                    <select class="form-select" id="approval_levels" name="approval_levels">
                                        <option value="">Select Levels</option>
                                        <option value="1" {{ old('approval_levels', $settings->approval_levels ?? 2) == '1' ? 'selected' : '' }}>1 Level</option>
                                        <option value="2" {{ old('approval_levels', $settings->approval_levels ?? 2) == '2' ? 'selected' : '' }}>2 Levels</option>
                                        <option value="3" {{ old('approval_levels', $settings->approval_levels ?? 2) == '3' ? 'selected' : '' }}>3 Levels</option>
                                        <option value="4" {{ old('approval_levels', $settings->approval_levels ?? 2) == '4' ? 'selected' : '' }}>4 Levels</option>
                                        <option value="5" {{ old('approval_levels', $settings->approval_levels ?? 2) == '5' ? 'selected' : '' }}>5 Levels</option>
                                    </select>
                                </div>
                            </div>
                            

                            <!-- Approval Assignments -->
                            <div class="row mt-4" id="assignments_block">
                                <div class="col-12">
                                    <h6 class="mb-3">Approval Assignments</h6>
                                    <p class="text-muted">Assign roles or specific users to each approval level</p>
                                    
                                    <!-- Level 1 Approvers -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">Level 1 Approvers</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="level1_approval_type" class="form-label">Approval Type</label>
                                                    <select class="form-select" id="level1_approval_type" name="level1_approval_type">
                                                        <option value="role" {{ old('level1_approval_type', $settings->level1_approval_type ?? 'role') == 'role' ? 'selected' : '' }}>By Role</option>
                                                        <option value="user" {{ old('level1_approval_type', $settings->level1_approval_type ?? 'role') == 'user' ? 'selected' : '' }}>By User</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="level1_approvers" class="form-label">Approvers</label>
                                                    <select class="form-select" id="level1_approvers" name="level1_approvers[]" multiple>
                                                        @if(isset($roles))
                                                            @foreach($roles as $role)
                                                                <option value="role_{{ $role->name }}" {{ in_array('role_' . $role->name, (array) old('level1_approvers', is_array($settings->level1_approvers ?? null) ? $settings->level1_approvers : [])) ? 'selected' : '' }}>
                                                                    {{ ucfirst($role->name) }} (Role)
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                        @if(isset($users))
                                                            @foreach($users as $user)
                                                                <option value="user_{{ $user->id }}" {{ in_array('user_' . $user->id, (array) old('level1_approvers', is_array($settings->level1_approvers ?? null) ? $settings->level1_approvers : [])) ? 'selected' : '' }}>
                                                                    {{ $user->name }} ({{ $user->email }})
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Level 2 Approvers -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">Level 2 Approvers</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="level2_approval_type" class="form-label">Approval Type</label>
                                                    <select class="form-select" id="level2_approval_type" name="level2_approval_type">
                                                        <option value="role" {{ old('level2_approval_type', $settings->level2_approval_type ?? 'role') == 'role' ? 'selected' : '' }}>By Role</option>
                                                        <option value="user" {{ old('level2_approval_type', $settings->level2_approval_type ?? 'role') == 'user' ? 'selected' : '' }}>By User</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="level2_approvers" class="form-label">Approvers</label>
                                                    <select class="form-select" id="level2_approvers" name="level2_approvers[]" multiple>
                                                        @if(isset($roles))
                                                            @foreach($roles as $role)
                                                                <option value="role_{{ $role->name }}" {{ in_array('role_' . $role->name, (array) old('level2_approvers', is_array($settings->level2_approvers ?? null) ? $settings->level2_approvers : [])) ? 'selected' : '' }}>
                                                                    {{ ucfirst($role->name) }} (Role)
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                        @if(isset($users))
                                                            @foreach($users as $user)
                                                                <option value="user_{{ $user->id }}" {{ in_array('user_' . $user->id, old('level2_approvers', $settings->level2_approvers ?? [])) ? 'selected' : '' }}>
                                                                    {{ $user->name }} ({{ $user->email }})
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Level 3 Approvers -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">Level 3 Approvers</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="level3_approval_type" class="form-label">Approval Type</label>
                                                    <select class="form-select" id="level3_approval_type" name="level3_approval_type">
                                                        <option value="role" {{ old('level3_approval_type', $settings->level3_approval_type ?? 'role') == 'role' ? 'selected' : '' }}>By Role</option>
                                                        <option value="user" {{ old('level3_approval_type', $settings->level3_approval_type ?? 'role') == 'user' ? 'selected' : '' }}>By User</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="level3_approvers" class="form-label">Approvers</label>
                                                    <select class="form-select" id="level3_approvers" name="level3_approvers[]" multiple>
                                                        @if(isset($roles))
                                                            @foreach($roles as $role)
                                                                <option value="role_{{ $role->name }}" {{ in_array('role_' . $role->name, old('level3_approvers', $settings->level3_approvers ?? ['role_admin'])) ? 'selected' : '' }}>
                                                                    {{ ucfirst($role->name) }} (Role)
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                        @if(isset($users))
                                                            @foreach($users as $user)
                                                                <option value="user_{{ $user->id }}" {{ in_array('user_' . $user->id, old('level3_approvers', $settings->level3_approvers ?? [])) ? 'selected' : '' }}>
                                                                    {{ $user->name }} ({{ $user->email }})
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Level 4 Approvers -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">Level 4 Approvers</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="level4_approval_type" class="form-label">Approval Type</label>
                                                    <select class="form-select" id="level4_approval_type" name="level4_approval_type">
                                                        <option value="role" {{ old('level4_approval_type', $settings->level4_approval_type ?? 'role') == 'role' ? 'selected' : '' }}>By Role</option>
                                                        <option value="user" {{ old('level4_approval_type', $settings->level4_approval_type ?? 'role') == 'user' ? 'selected' : '' }}>By User</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="level4_approvers" class="form-label">Approvers</label>
                                                    <select class="form-select" id="level4_approvers" name="level4_approvers[]" multiple>
                                                        @if(isset($roles))
                                                            @foreach($roles as $role)
                                                                <option value="role_{{ $role->name }}" {{ in_array('role_' . $role->name, old('level4_approvers', $settings->level4_approvers ?? ['role_super-admin'])) ? 'selected' : '' }}>
                                                                    {{ ucfirst($role->name) }} (Role)
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                        @if(isset($users))
                                                            @foreach($users as $user)
                                                                <option value="user_{{ $user->id }}" {{ in_array('user_' . $user->id, old('level4_approvers', $settings->level4_approvers ?? [])) ? 'selected' : '' }}>
                                                                    {{ $user->name }} ({{ $user->email }})
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Level 5 Approvers -->
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="mb-0">Level 5 Approvers</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="level5_approval_type" class="form-label">Approval Type</label>
                                                    <select class="form-select" id="level5_approval_type" name="level5_approval_type">
                                                        <option value="role" {{ old('level5_approval_type', $settings->level5_approval_type ?? 'role') == 'role' ? 'selected' : '' }}>By Role</option>
                                                        <option value="user" {{ old('level5_approval_type', $settings->level5_approval_type ?? 'role') == 'user' ? 'selected' : '' }}>By User</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="level5_approvers" class="form-label">Approvers</label>
                                                    <select class="form-select" id="level5_approvers" name="level5_approvers[]" multiple>
                                                        @if(isset($roles))
                                                            @foreach($roles as $role)
                                                                <option value="role_{{ $role->name }}" {{ in_array('role_' . $role->name, old('level5_approvers', $settings->level5_approvers ?? ['role_super-admin'])) ? 'selected' : '' }}>
                                                                    {{ ucfirst($role->name) }} (Role)
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                        @if(isset($users))
                                                            @foreach($users as $user)
                                                                <option value="user_{{ $user->id }}" {{ in_array('user_' . $user->id, old('level5_approvers', $settings->level5_approvers ?? [])) ? 'selected' : '' }}>
                                                                    {{ $user->name }} ({{ $user->email }})
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div> <!-- /#approval_config -->

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Update Approval Settings
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
                            You don't have permission to manage account transfer approval settings.
                        </div>
                        @endcan
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
document.addEventListener('DOMContentLoaded', function() {
    const approvalLevelsSelect = document.getElementById('approval_levels');
    const requireAllCheckbox = document.getElementById('require_approval_for_all');
    const approvalConfig = document.getElementById('approval_config');
    const directPostNote = document.getElementById('direct_post_note');
    const levelsBlock = document.getElementById('levels_block');
    const assignmentsBlock = document.getElementById('assignments_block');
    
    function toggleApprovalConfig() {
        const enabled = requireAllCheckbox.checked;
        approvalConfig.style.display = enabled ? 'block' : 'none';
        directPostNote.style.display = enabled ? 'none' : 'block';
        // Enable/disable inputs inside config
        approvalConfig.querySelectorAll('input, select, textarea').forEach(el => {
            el.disabled = !enabled;
        });
        // Explicitly hide/show blocks
        if (levelsBlock) levelsBlock.style.display = enabled ? 'block' : 'none';
        if (assignmentsBlock) assignmentsBlock.style.display = enabled ? 'block' : 'none';
    }

    function toggleApprovalLevels() {
        const selectedLevels = parseInt(approvalLevelsSelect.value);
        
        // Show/hide approval level cards based on selection
        for (let i = 1; i <= 5; i++) {
            const levelCard = document.querySelector(`[id*="level${i}"]`)?.closest('.card');
            if (levelCard) {
                if (i <= selectedLevels) {
                    levelCard.style.display = 'block';
                } else {
                    levelCard.style.display = 'none';
                }
            }
        }
    }
    
    // Initial call
    toggleApprovalConfig();
    toggleApprovalLevels();
    
    // Listen for changes
    requireAllCheckbox.addEventListener('change', toggleApprovalConfig);
    approvalLevelsSelect.addEventListener('change', toggleApprovalLevels);
    
    // Dynamic approver loading based on approval type
    @php
        $rolesNames = isset($roles) ? $roles->pluck('name')->values() : collect();
        $usersArr = isset($users) ? $users->map(function($u){ return ['id'=>$u->id,'name'=>$u->name,'email'=>$u->email]; })->values() : collect();
    @endphp
    const rolesData = @json($rolesNames);
    const usersData = @json($usersArr);
    const approvalTypeSelects = document.querySelectorAll('[id$="_approval_type"]');
    const approverSelects = document.querySelectorAll('[id$="_approvers"]');
    
    function updateApproverOptions(approvalTypeSelect, approverSelect) {
        const selectedType = approvalTypeSelect.value;
        
        // Clear current options
        approverSelect.innerHTML = '';
        
        if (selectedType === 'role') {
            rolesData.forEach(function(name){
                let opt = document.createElement('option');
                opt.value = 'role_' + name;
                opt.textContent = (name.charAt(0).toUpperCase() + name.slice(1)) + ' (Role)';
                approverSelect.appendChild(opt);
            });
        } else if (selectedType === 'user') {
            usersData.forEach(function(u){
                let opt = document.createElement('option');
                opt.value = 'user_' + u.id;
                opt.textContent = u.name + ' (' + (u.email || '') + ')';
                approverSelect.appendChild(opt);
            });
        }
    }
    
    // Initialize and add event listeners
    approvalTypeSelects.forEach((typeSelect, index) => {
        const approverSelect = approverSelects[index];
        updateApproverOptions(typeSelect, approverSelect);
        
        typeSelect.addEventListener('change', function() {
            updateApproverOptions(this, approverSelect);
        });
    });
});
</script>
@endpush

