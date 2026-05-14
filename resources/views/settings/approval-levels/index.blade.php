@extends('layouts.main')

@section('title', 'Approval Levels Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Approval Levels', 'url' => '#', 'icon' => 'bx bx-check-shield']
        ]" />
        <h6 class="mb-0 text-uppercase">APPROVAL LEVELS MANAGEMENT</h6>
        <hr/>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif


        <div class="alert alert-info border-0">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle fs-4 me-3"></i>
                <div>
                    <h6 class="mb-1">Approval Levels System</h6>
                    <p class="mb-1">
                        Configure multi-level approval workflows for <strong>Budget</strong>, <strong>Bank Reconciliation</strong>,
                        <strong>Asset Revaluation</strong>, <strong>Asset Impairment</strong>, <strong>Asset Disposal</strong>,
                        <strong>HFS Requests</strong>, <strong>Purchase Requisitions</strong>, <strong>Purchase Orders</strong>, and <strong>Accruals &amp; Prepayments</strong>.
                    </p>
                    <p class="mb-0">
                        Each level can have multiple approvers assigned by user, role, or branch. Documents move level by level in sequence,
                        and only after the final level is approved will the module-specific actions run
                        (for example, posting to the General Ledger, updating asset balances, or marking items as fully approved).
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">Approval Levels Configuration</h4>
                            <div>
                                <a href="{{ route('settings.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Settings
                                </a>
                            </div>
                        </div>

                        <!-- Navigation Tabs -->
                        <ul class="nav nav-tabs nav-bordered" id="moduleTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="budget-tab" data-bs-toggle="tab" data-bs-target="#budget-content" type="button" role="tab">
                                    <i class="bx bx-chart me-1"></i> Budget
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="bank-reconciliation-tab" data-bs-toggle="tab" data-bs-target="#bank-reconciliation-content" type="button" role="tab">
                                    <i class="bx bx-wallet me-1"></i> Bank Reconciliation
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="asset-revaluation-tab" data-bs-toggle="tab" data-bs-target="#asset-revaluation-content" type="button" role="tab">
                                    <i class="bx bx-trending-up me-1"></i> Asset Revaluation
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="asset-impairment-tab" data-bs-toggle="tab" data-bs-target="#asset-impairment-content" type="button" role="tab">
                                    <i class="bx bx-trending-down me-1"></i> Asset Impairment
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="asset-disposal-tab" data-bs-toggle="tab" data-bs-target="#asset-disposal-content" type="button" role="tab">
                                    <i class="bx bx-trash me-1"></i> Asset Disposal
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="hfs-request-tab" data-bs-toggle="tab" data-bs-target="#hfs-request-content" type="button" role="tab">
                                    <i class="bx bx-transfer me-1"></i> HFS Request
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="purchase-requisition-tab" data-bs-toggle="tab" data-bs-target="#purchase-requisition-content" type="button" role="tab">
                                    <i class="bx bx-file me-1"></i> Purchase Requisition
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="purchase-order-tab" data-bs-toggle="tab" data-bs-target="#purchase-order-content" type="button" role="tab">
                                    <i class="bx bx-shopping-cart me-1"></i> Purchase Order
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="accruals-prepayments-tab" data-bs-toggle="tab" data-bs-target="#accruals-prepayments-content" type="button" role="tab">
                                    <i class="bx bx-time-five me-1"></i> Accruals &amp; Prepayments
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content mt-4" id="moduleTabContent">
                            <!-- Budget Tab -->
                            <div class="tab-pane fade show active" id="budget-content" role="tabpanel">
                                @include('settings.approval-levels.partials.module-levels', [
                                    'module' => 'budget',
                                    'levels' => $budgetLevels,
                                    'users' => $users,
                                    'roles' => $roles,
                                    'branches' => $branches
                                ])
                            </div>

                            <!-- Bank Reconciliation Tab -->
                            <div class="tab-pane fade" id="bank-reconciliation-content" role="tabpanel">
                                @include('settings.approval-levels.partials.module-levels', [
                                    'module' => 'bank_reconciliation',
                                    'levels' => $bankReconciliationLevels,
                                    'users' => $users,
                                    'roles' => $roles,
                                    'branches' => $branches
                                ])
                            </div>

                            <!-- Asset Revaluation Tab -->
                            <div class="tab-pane fade" id="asset-revaluation-content" role="tabpanel">
                                @include('settings.approval-levels.partials.module-levels', [
                                    'module' => 'asset_revaluation',
                                    'levels' => $revaluationLevels,
                                    'users' => $users,
                                    'roles' => $roles,
                                    'branches' => $branches
                                ])
                            </div>

                            <!-- Asset Impairment Tab -->
                            <div class="tab-pane fade" id="asset-impairment-content" role="tabpanel">
                                @include('settings.approval-levels.partials.module-levels', [
                                    'module' => 'asset_impairment',
                                    'levels' => $impairmentLevels,
                                    'users' => $users,
                                    'roles' => $roles,
                                    'branches' => $branches
                                ])
                            </div>

                            <!-- Asset Disposal Tab -->
                            <div class="tab-pane fade" id="asset-disposal-content" role="tabpanel">
                                @include('settings.approval-levels.partials.module-levels', [
                                    'module' => 'asset_disposal',
                                    'levels' => $disposalLevels,
                                    'users' => $users,
                                    'roles' => $roles,
                                    'branches' => $branches
                                ])
                            </div>

                            <!-- HFS Request Tab -->
                            <div class="tab-pane fade" id="hfs-request-content" role="tabpanel">
                                @include('settings.approval-levels.partials.module-levels', [
                                    'module' => 'hfs_request',
                                    'levels' => $hfsLevels,
                                    'users' => $users,
                                    'roles' => $roles,
                                    'branches' => $branches
                                ])
                            </div>

                            <!-- Purchase Requisition Tab -->
                            <div class="tab-pane fade" id="purchase-requisition-content" role="tabpanel">
                                @include('settings.approval-levels.partials.module-levels', [
                                    'module' => 'purchase_requisition',
                                    'levels' => $purchaseRequisitionLevels,
                                    'users' => $users,
                                    'roles' => $roles,
                                    'branches' => $branches
                                ])
                            </div>

                            <!-- Purchase Order Tab -->
                            <div class="tab-pane fade" id="purchase-order-content" role="tabpanel">
                                @include('settings.approval-levels.partials.module-levels', [
                                    'module' => 'purchase_order',
                                    'levels' => $purchaseOrderLevels,
                                    'users' => $users,
                                    'roles' => $roles,
                                    'branches' => $branches
                                ])
                            </div>

                            <!-- Accruals & Prepayments Tab -->
                            <div class="tab-pane fade" id="accruals-prepayments-content" role="tabpanel">
                                @include('settings.approval-levels.partials.module-levels', [
                                    'module' => 'accruals_prepayments',
                                    'levels' => $accrualsPrepaymentsLevels,
                                    'users' => $users,
                                    'roles' => $roles,
                                    'branches' => $branches
                                ])
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Level Modal -->
<div class="modal fade" id="levelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="levelModalTitle">Create Approval Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="levelForm" method="POST">
                @csrf
                <div id="levelFormMethod"></div>
                <div class="modal-body">
                    <input type="hidden" name="module" id="levelModule">
                    <div class="mb-3">
                        <label for="level" class="form-label">Level Number <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="level" name="level" min="1" max="10" required>
                        <small class="text-muted">The approval level number (1-10)</small>
                    </div>
                    <div class="mb-3">
                        <label for="level_name" class="form-label">Level Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="level_name" name="level_name" required>
                        <small class="text-muted">e.g., "Manager Approval", "Director Approval"</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_required" value="0">
                            <input class="form-check-input" type="checkbox" id="is_required" name="is_required" value="1" checked>
                            <label class="form-check-label" for="is_required">Required Level</label>
                            <small class="d-block text-muted">If unchecked, this level can be skipped</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Level</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Approver Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Approver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignForm" method="POST" action="{{ route('settings.approval-levels.assignments.store') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="approval_level_id" id="assignLevelId">
                    <div class="mb-3">
                        <label class="form-label">Assign By</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="assign_type" id="assignUser" value="user" checked>
                            <label class="btn btn-outline-primary" for="assignUser">User</label>
                            
                            <input type="radio" class="btn-check" name="assign_type" id="assignRole" value="role">
                            <label class="btn btn-outline-primary" for="assignRole">Role</label>
                        </div>
                    </div>
                    <div class="mb-3" id="userSelectGroup">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">-- Select User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="roleSelectGroup">
                        <label for="role_id" class="form-label">Select Role</label>
                        <select class="form-select" id="role_id" name="role_id">
                            <option value="">-- Select Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="branch_id" class="form-label">Branch (Optional)</label>
                        <select class="form-select" id="branch_id" name="branch_id">
                            <option value="">-- All Branches (Global) --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Leave blank for global assignment, or select a specific branch</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Approver</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    // Handle assign type toggle
    document.getElementById('assignUser').addEventListener('change', function() {
        document.getElementById('userSelectGroup').classList.remove('d-none');
        document.getElementById('roleSelectGroup').classList.add('d-none');
        document.getElementById('user_id').required = true;
        document.getElementById('role_id').required = false;
        document.getElementById('role_id').value = '';
    });

    document.getElementById('assignRole').addEventListener('change', function() {
        document.getElementById('userSelectGroup').classList.add('d-none');
        document.getElementById('roleSelectGroup').classList.remove('d-none');
        document.getElementById('role_id').required = true;
        document.getElementById('user_id').required = false;
        document.getElementById('user_id').value = '';
    });

    // Open create level modal
    function openCreateLevelModal(module) {
        document.getElementById('levelModalTitle').textContent = 'Create Approval Level';
        document.getElementById('levelForm').action = '{{ route("settings.approval-levels.store") }}';
        document.getElementById('levelFormMethod').innerHTML = '';
        document.getElementById('levelModule').value = module;
        document.getElementById('levelForm').reset();
        document.getElementById('is_required').checked = true;
        new bootstrap.Modal(document.getElementById('levelModal')).show();
    }

    // Open edit level modal
    function openEditLevelModal(levelId, level, levelName, isRequired) {
        document.getElementById('levelModalTitle').textContent = 'Edit Approval Level';
        document.getElementById('levelForm').action = `/settings/approval-levels/${levelId}`;
        document.getElementById('levelFormMethod').innerHTML = '@method("PUT")';
        document.getElementById('level').value = level;
        document.getElementById('level_name').value = levelName;
        document.getElementById('is_required').checked = isRequired == 1;
        document.getElementById('level').disabled = true; // Can't change level number
        new bootstrap.Modal(document.getElementById('levelModal')).show();
    }

    // Reset level form when modal closes
    document.getElementById('levelModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('levelForm').reset();
        document.getElementById('level').disabled = false;
        document.getElementById('levelFormMethod').innerHTML = '';
    });

    // Open assign approver modal
    function openAssignModal(levelId) {
        document.getElementById('assignLevelId').value = levelId;
        document.getElementById('assignForm').reset();
        document.getElementById('assignUser').checked = true;
        document.getElementById('userSelectGroup').classList.remove('d-none');
        document.getElementById('roleSelectGroup').classList.add('d-none');
        document.getElementById('user_id').required = true;
        document.getElementById('role_id').required = false;
        new bootstrap.Modal(document.getElementById('assignModal')).show();
    }

    // Delete level confirmation
    function deleteLevel(levelId, levelName) {
        if (confirm(`Are you sure you want to delete approval level "${levelName}"? This will also delete all assignments for this level.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/settings/approval-levels/${levelId}`;
            form.innerHTML = '@csrf @method("DELETE")';
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Delete assignment confirmation
    function deleteAssignment(assignmentId, approverName) {
        if (confirm(`Are you sure you want to remove "${approverName}" from this approval level?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/settings/approval-levels/assignments/${assignmentId}`;
            form.innerHTML = '@csrf @method("DELETE")';
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Handle form submission for assignments
    document.getElementById('assignForm').addEventListener('submit', function(e) {
        const assignType = document.querySelector('input[name="assign_type"]:checked').value;
        const userId = document.getElementById('user_id').value;
        const roleId = document.getElementById('role_id').value;

        if (assignType === 'user' && !userId) {
            e.preventDefault();
            alert('Please select a user.');
            return false;
        }

        if (assignType === 'role' && !roleId) {
            e.preventDefault();
            alert('Please select a role.');
            return false;
        }

        // Clear the unused field
        if (assignType === 'user') {
            document.getElementById('role_id').value = '';
        } else {
            document.getElementById('user_id').value = '';
        }
    });
</script>
@endpush

