@extends('layouts.main')

@section('title', 'Petty Cash Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Petty Cash Settings', 'url' => '#', 'icon' => 'bx bx-wallet']
        ]" />
        
        <h6 class="mb-0 text-uppercase">PETTY CASH OPERATION MODE CONFIGURATION</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-wallet me-2"></i>Petty Cash System Settings</h5>
                    </div>
                    <div class="card-body">
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

                        <form action="{{ route('settings.petty-cash.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Operation Mode Selection -->
                            <div class="card mb-4 border-primary">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Operation Mode</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Petty Cash Operation Mode <span class="text-danger">*</span></label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card border {{ old('operation_mode', $settings->operation_mode) == 'sub_imprest' ? 'border-primary' : '' }}" style="cursor: pointer;" onclick="selectMode('sub_imprest')">
                                                        <div class="card-body">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="operation_mode" id="mode_sub_imprest" value="sub_imprest" {{ old('operation_mode', $settings->operation_mode) == 'sub_imprest' ? 'checked' : '' }}>
                                                                <label class="form-check-label fw-bold" for="mode_sub_imprest">
                                                                    Sub-Imprest Mode (Linked to Imprest Module)
                                                                </label>
                                                            </div>
                                                            <p class="text-muted small mb-0 mt-2">
                                                                Petty cash is controlled inside the Imprest Management System. All expenses must be retired through imprest workflow. Recommended for organizations with strict financial controls.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card border {{ old('operation_mode', $settings->operation_mode) == 'standalone' ? 'border-primary' : '' }}" style="cursor: pointer;" onclick="selectMode('standalone')">
                                                        <div class="card-body">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="operation_mode" id="mode_standalone" value="standalone" {{ old('operation_mode', $settings->operation_mode) == 'standalone' ? 'checked' : '' }}>
                                                                <label class="form-check-label fw-bold" for="mode_standalone">
                                                                    Standalone Petty Cash (Not Linked to Imprest Module)
                                                                </label>
                                                            </div>
                                                            <p class="text-muted small mb-0 mt-2">
                                                                Petty cash operates independently. Replenishment is directly from bank. Simpler workflow, suitable for SMEs, NGOs, and schools.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('operation_mode')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- General Settings -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bx bx-slider me-2"></i>General Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="default_float_amount" class="form-label">Default Petty Cash Float</label>
                                            <div class="input-group">
                                                <span class="input-group-text">TZS</span>
                                                <input type="number" 
                                                       class="form-control @error('default_float_amount') is-invalid @enderror" 
                                                       id="default_float_amount" 
                                                       name="default_float_amount" 
                                                       value="{{ old('default_float_amount', $settings->default_float_amount) }}" 
                                                       step="0.01" 
                                                       min="0">
                                            </div>
                                            <small class="text-muted">Default float amount for new petty cash units</small>
                                            @error('default_float_amount')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="max_transaction_amount" class="form-label">Maximum Petty Cash per Transaction</label>
                                            <div class="input-group">
                                                <span class="input-group-text">TZS</span>
                                                <input type="number" 
                                                       class="form-control @error('max_transaction_amount') is-invalid @enderror" 
                                                       id="max_transaction_amount" 
                                                       name="max_transaction_amount" 
                                                       value="{{ old('max_transaction_amount', $settings->max_transaction_amount) }}" 
                                                       step="0.01" 
                                                       min="0">
                                            </div>
                                            <small class="text-muted">Maximum amount allowed per transaction (0 = no limit)</small>
                                            @error('max_transaction_amount')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="maximum_limit" class="form-label">Maximum Limit (Unit Balance)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">TZS</span>
                                                <input type="number" 
                                                       class="form-control @error('maximum_limit') is-invalid @enderror" 
                                                       id="maximum_limit" 
                                                       name="maximum_limit" 
                                                       value="{{ old('maximum_limit', $settings->maximum_limit) }}" 
                                                       step="0.01" 
                                                       min="0">
                                            </div>
                                            <small class="text-muted">Maximum allowed balance for petty cash units (0 = no limit)</small>
                                            @error('maximum_limit')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="minimum_balance_trigger" class="form-label">Minimum Balance Trigger</label>
                                            <div class="input-group">
                                                <span class="input-group-text">TZS</span>
                                                <input type="number" 
                                                       class="form-control @error('minimum_balance_trigger') is-invalid @enderror" 
                                                       id="minimum_balance_trigger" 
                                                       name="minimum_balance_trigger" 
                                                       value="{{ old('minimum_balance_trigger', $settings->minimum_balance_trigger) }}" 
                                                       step="0.01" 
                                                       min="0">
                                            </div>
                                            <small class="text-muted">Trigger replenishment when balance falls below this amount</small>
                                            @error('minimum_balance_trigger')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Expense Categories -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Allowed Expense Categories (GL Accounts)</h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Select which GL expense accounts are allowed for petty cash transactions. Leave empty to allow all expense accounts.</p>
                                    <div class="mb-3">
                                        <select class="form-select select2-multiple" 
                                                id="allowed_expense_categories" 
                                                name="allowed_expense_categories[]" 
                                                multiple>
                                            @foreach($expenseAccounts as $account)
                                                <option value="{{ $account->id }}" 
                                                    {{ in_array($account->id, old('allowed_expense_categories', $settings->allowed_expense_categories ?? [])) ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple accounts. Leave empty to allow all expense accounts.</small>
                                    @error('allowed_expense_categories.*')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Approval & Receipt Settings -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bx bx-check-shield me-2"></i>Approval & Receipt Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="require_receipt" 
                                                       name="require_receipt" 
                                                       value="1" 
                                                       {{ old('require_receipt', $settings->require_receipt) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="require_receipt">
                                                    Require Receipt for All Transactions
                                                </label>
                                                <small class="form-text text-muted d-block">If enabled, all transactions must have a receipt attachment</small>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="auto_approve_below_threshold" 
                                                       name="auto_approve_below_threshold" 
                                                       value="1" 
                                                       {{ old('auto_approve_below_threshold', $settings->auto_approve_below_threshold) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="auto_approve_below_threshold">
                                                    Auto-Approve Below Threshold
                                                </label>
                                                <small class="form-text text-muted d-block">Automatically approve transactions below unit's approval threshold</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Petty Cash Unit Approval Levels -->
                            <div class="card mb-4 border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bx bx-shield-quarter me-2"></i>Petty Cash Unit Approval Levels</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="approval_required" 
                                                       name="approval_required" 
                                                       value="1" 
                                                       {{ old('approval_required', $settings->approval_required ?? false) ? 'checked' : '' }}
                                                       onchange="toggleApprovalConfig()">
                                                <label class="form-check-label fw-bold" for="approval_required">
                                                    Require Approval for Petty Cash Unit Creation
                                                </label>
                                                <small class="form-text text-muted d-block">If enabled, newly created petty cash units will require approval before activation</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Approval Configuration (visible only if approval_required is checked) -->
                                    <div id="approval_config" style="display: {{ old('approval_required', $settings->approval_required ?? false) ? 'block' : 'none' }};">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="approval_levels" class="form-label">Number of Approval Levels</label>
                                                <select class="form-select" id="approval_levels" name="approval_levels" onchange="updateApprovalLevels()">
                                                    <option value="1" {{ old('approval_levels', $settings->approval_levels ?? 2) == '1' ? 'selected' : '' }}>1 Level</option>
                                                    <option value="2" {{ old('approval_levels', $settings->approval_levels ?? 2) == '2' ? 'selected' : '' }}>2 Levels</option>
                                                    <option value="3" {{ old('approval_levels', $settings->approval_levels ?? 2) == '3' ? 'selected' : '' }}>3 Levels</option>
                                                    <option value="4" {{ old('approval_levels', $settings->approval_levels ?? 2) == '4' ? 'selected' : '' }}>4 Levels</option>
                                                    <option value="5" {{ old('approval_levels', $settings->approval_levels ?? 2) == '5' ? 'selected' : '' }}>5 Levels</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="auto_approval_limit" class="form-label">Auto-Approval Limit (TZS)</label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="auto_approval_limit" 
                                                       name="auto_approval_limit" 
                                                       value="{{ old('auto_approval_limit', $settings->auto_approval_limit ?? 100000) }}" 
                                                       step="0.01" 
                                                       min="0">
                                                <small class="text-muted">Units with float amount below this will be auto-approved</small>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           id="require_approval_for_all" 
                                                           name="require_approval_for_all" 
                                                           value="1" 
                                                           {{ old('require_approval_for_all', $settings->require_approval_for_all ?? false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="require_approval_for_all">
                                                        Require Approval for All Units (Ignore Thresholds)
                                                    </label>
                                                    <small class="form-text text-muted d-block">If enabled, all units require approval regardless of float amount</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Approval Thresholds -->
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <h6 class="mb-3">Approval Thresholds (TZS)</h6>
                                                <p class="text-muted small">Set amount thresholds for each approval level. Units above threshold will require that level of approval.</p>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label for="approval_threshold_1" class="form-label">Level 1 Threshold</label>
                                                <input type="number" class="form-control" id="approval_threshold_1" name="approval_threshold_1" 
                                                       value="{{ old('approval_threshold_1', $settings->approval_threshold_1) }}" step="0.01" min="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label for="approval_threshold_2" class="form-label">Level 2 Threshold</label>
                                                <input type="number" class="form-control" id="approval_threshold_2" name="approval_threshold_2" 
                                                       value="{{ old('approval_threshold_2', $settings->approval_threshold_2) }}" step="0.01" min="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label for="approval_threshold_3" class="form-label">Level 3 Threshold</label>
                                                <input type="number" class="form-control" id="approval_threshold_3" name="approval_threshold_3" 
                                                       value="{{ old('approval_threshold_3', $settings->approval_threshold_3) }}" step="0.01" min="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label for="approval_threshold_4" class="form-label">Level 4 Threshold</label>
                                                <input type="number" class="form-control" id="approval_threshold_4" name="approval_threshold_4" 
                                                       value="{{ old('approval_threshold_4', $settings->approval_threshold_4) }}" step="0.01" min="0">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label for="approval_threshold_5" class="form-label">Level 5 Threshold</label>
                                                <input type="number" class="form-control" id="approval_threshold_5" name="approval_threshold_5" 
                                                       value="{{ old('approval_threshold_5', $settings->approval_threshold_5) }}" step="0.01" min="0">
                                            </div>
                                        </div>

                                        <!-- Approval Assignments -->
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <h6 class="mb-3">Approval Assignments</h6>
                                                <p class="text-muted small">Assign roles or specific users to each approval level</p>
                                                
                                                @php
                                                    $roles = \Spatie\Permission\Models\Role::all();
                                                    $users = \App\Models\User::where('company_id', auth()->user()->company_id)->get();
                                                @endphp

                                                @for($level = 1; $level <= 5; $level++)
                                                <div class="card mb-3 level-config" id="level_{{ $level }}_config" style="display: {{ $level <= ($settings->approval_levels ?? 2) ? 'block' : 'none' }};">
                                                    <div class="card-header">
                                                        <h6 class="mb-0">Level {{ $level }} Approvers</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label for="level{{ $level }}_approval_type" class="form-label">Approval Type</label>
                                                                <select class="form-select" id="level{{ $level }}_approval_type" name="level{{ $level }}_approval_type" onchange="updateApproverOptions({{ $level }})">
                                                                    <option value="role" {{ old("level{$level}_approval_type", $settings->{"level{$level}_approval_type"} ?? 'role') == 'role' ? 'selected' : '' }}>By Role</option>
                                                                    <option value="user" {{ old("level{$level}_approval_type", $settings->{"level{$level}_approval_type"} ?? 'role') == 'user' ? 'selected' : '' }}>By User</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label for="level{{ $level }}_approvers" class="form-label">Approvers</label>
                                                                <select class="form-select select2-approvers" id="level{{ $level }}_approvers" name="level{{ $level }}_approvers[]" multiple data-level="{{ $level }}">
                                                                    @php
                                                                        $currentType = old("level{$level}_approval_type", $settings->{"level{$level}_approval_type"} ?? 'role');
                                                                        $currentApprovers = (array) old("level{$level}_approvers", $settings->{"level{$level}_approvers"} ?? []);
                                                                    @endphp
                                                                    @if($currentType == 'role')
                                                                        @foreach($roles as $role)
                                                                            <option value="{{ $role->name }}" {{ in_array($role->name, $currentApprovers) ? 'selected' : '' }} data-type="role">
                                                                                {{ ucfirst($role->name) }} (Role)
                                                                            </option>
                                                                        @endforeach
                                                                    @else
                                                                        @foreach($users as $user)
                                                                            <option value="{{ $user->id }}" {{ in_array($user->id, $currentApprovers) ? 'selected' : '' }} data-type="user">
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
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bx bx-note me-2"></i>Additional Notes</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                  id="notes" 
                                                  name="notes" 
                                                  rows="4" 
                                                  placeholder="Enter any additional notes or instructions...">{{ old('notes', $settings->notes) }}</textarea>
                                        @error('notes')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Information Alert -->
                            <div class="alert alert-info border-0 border-start border-4 border-info mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                    <div>
                                        <h6 class="alert-heading mb-2">Mode Selection Guide</h6>
                                        <p class="mb-2"><strong>Sub-Imprest Mode:</strong> Recommended for organizations requiring strict financial controls. All petty cash expenses must be retired through the imprest workflow, ensuring comprehensive audit trails and compliance.</p>
                                        <p class="mb-0"><strong>Standalone Mode:</strong> Suitable for smaller organizations, NGOs, or schools. Simpler workflow with direct bank replenishment. No integration with imprest module required.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Save Settings
                                </button>
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
    // Initialize Select2 for multiple select
    if (typeof $().select2 !== 'undefined') {
        $('.select2-multiple').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select expense accounts (leave empty to allow all)'
        });
        
        $('.select2-approvers').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select approvers'
        });
    }

    // Handle mode selection
    $('input[name="operation_mode"]').on('change', function() {
        updateModeCards();
    });

    function updateModeCards() {
        const selectedMode = $('input[name="operation_mode"]:checked').val();
        $('.card[onclick*="selectMode"]').removeClass('border-primary');
        if (selectedMode === 'sub_imprest') {
            $('#mode_sub_imprest').closest('.card').addClass('border-primary');
        } else {
            $('#mode_standalone').closest('.card').addClass('border-primary');
        }
    }

    // Initial update
    updateModeCards();
    updateApprovalLevels();
});

function selectMode(mode) {
    $('input[name="operation_mode"][value="' + mode + '"]').prop('checked', true).trigger('change');
}

function toggleApprovalConfig() {
    const approvalRequired = $('#approval_required').is(':checked');
    $('#approval_config').toggle(approvalRequired);
}

function updateApprovalLevels() {
    const levels = parseInt($('#approval_levels').val()) || 1;
    for (let i = 1; i <= 5; i++) {
        if (i <= levels) {
            $('#level_' + i + '_config').show();
        } else {
            $('#level_' + i + '_config').hide();
            $('#level' + i + '_approvers').val(null).trigger('change');
        }
    }
}

function updateApproverOptions(level) {
    const type = $('#level' + level + '_approval_type').val();
    const select = $('#level' + level + '_approvers');
    
    // Store current values
    const currentValues = select.val() || [];
    
    // Destroy Select2 first
    if (select.hasClass('select2-hidden-accessible')) {
        select.select2('destroy');
    }
    
    // Remove options that don't match the type
    select.find('option').each(function() {
        const optionType = $(this).data('type');
        if (optionType && optionType !== type) {
            $(this).remove();
        }
    });
    
    // Add options for the selected type if they don't exist
    if (type === 'role') {
        @foreach($roles as $role)
            if (select.find('option[value="{{ $role->name }}"][data-type="role"]').length === 0) {
                select.append(new Option('{{ ucfirst($role->name) }} (Role)', '{{ $role->name }}', false, false).attr('data-type', 'role'));
            }
        @endforeach
    } else {
        @foreach($users as $user)
            if (select.find('option[value="{{ $user->id }}"][data-type="user"]').length === 0) {
                select.append(new Option('{{ $user->name }} ({{ $user->email }})', '{{ $user->id }}', false, false).attr('data-type', 'user'));
            }
        @endforeach
    }
    
    // Reinitialize Select2
    select.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select approvers'
    });
    
    // Restore selected values if they match the type
    const validValues = currentValues.filter(val => {
        const option = select.find('option[value="' + val + '"]');
        return option.length > 0 && option.data('type') === type;
    });
    if (validValues.length > 0) {
        select.val(validValues).trigger('change');
    }
}
</script>
@endpush
@endsection


