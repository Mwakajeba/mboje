<?php

namespace App\Services;

use App\Models\ApprovalLevel;
use App\Models\ApprovalLevelAssignment;
use App\Models\ApprovalHistory;
use App\Models\User;
use App\Models\Budget;
use App\Models\BankReconciliation;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Provision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BudgetSubmittedForApproval;
use App\Notifications\BudgetApprovalRequired;
use App\Notifications\BudgetApproved;
use App\Notifications\BudgetRejected;
use App\Notifications\BankReconciliationSubmittedForApproval;
use App\Notifications\BankReconciliationApprovalRequired;
use App\Notifications\BankReconciliationApproved;
use App\Notifications\BankReconciliationRejected;

class ApprovalService
{
    /**
     * Submit model for approval
     */
    public function submitForApproval(Model $model, int $userId): bool
    {
        DB::beginTransaction();
        
        try {
            // Get first approval level
            $firstLevel = $this->getFirstApprovalLevel($model);
            
            if (!$firstLevel) {
                // No approval required, auto-approve
                return $this->autoApprove($model, $userId);
            }
            
            // Update model status
            // HFS uses 'in_review' instead of 'pending_approval'
            $status = ($model instanceof \App\Models\Assets\HfsRequest) ? 'in_review' : 'pending_approval';
            $model->update([
                'status' => $status,
                'current_approval_level' => $firstLevel->level,
                'submitted_by' => $userId,
                'submitted_at' => now(),
            ]);
            
            // Create approval history entry
            ApprovalHistory::create([
                'approvable_type' => get_class($model),
                'approvable_id' => $model->id,
                'approval_level_id' => $firstLevel->id,
                'action' => 'submitted',
                'approver_id' => $userId,
            ]);
            
            // Log activity if model uses LogsActivity trait
            if (method_exists($model, 'logActivity')) {
                $modelName = class_basename($model);
                $identifier = $model->order_number ?? $model->reference ?? $model->number ?? $model->credit_note_number ?? $model->invoice_number ?? $model->id;
                $submittedBy = \App\Models\User::find($userId);
                $model->logActivity('update', "Submitted {$modelName} {$identifier} for Approval at Level {$firstLevel->level}", [
                    'Document Type' => $modelName,
                    'Document Reference' => $identifier,
                    'Approval Level' => $firstLevel->level,
                    'Submitted By' => $submittedBy->name ?? 'System',
                    'Submitted At' => now()->format('Y-m-d H:i:s')
                ]);
            }
            
            DB::commit();
            
            // Notify submitter
            $submitter = User::find($userId);
            if ($submitter) {
                try {
                    $this->notifySubmitter($model, $submitter);
                } catch (\Exception $e) {
                    Log::error("Failed to notify submitter after submission", [
                        'model_type' => get_class($model),
                        'model_id' => $model->id,
                        'submitter_id' => $userId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Notify Level 1 approvers
            try {
                $this->notifyApprovers($model, $firstLevel);
            } catch (\Exception $e) {
                Log::error("Failed to notify approvers after submission", [
                    'model_type' => get_class($model),
                    'model_id' => $model->id,
                    'approval_level_id' => $firstLevel->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting for approval: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Approve at current level
     */
    public function approve(Model $model, int $approvalLevelId, int $approverId, ?string $comments = null): bool
    {
        DB::beginTransaction();
        
        try {
            $approvalLevel = ApprovalLevel::findOrFail($approvalLevelId);
            
            // Verify user can approve
            if (!$this->canUserApprove($model, $approverId, $approvalLevel)) {
                throw new \Exception('User does not have permission to approve at this level');
            }
            
            // Verify model is in pending approval status
            // HFS uses 'in_review' instead of 'pending_approval'
            $expectedStatus = ($model instanceof \App\Models\Assets\HfsRequest) ? 'in_review' : 'pending_approval';
            if ($model->status !== $expectedStatus) {
                throw new \Exception('Model is not in pending approval status');
            }
            
            // Verify current approval level matches
            if ($model->current_approval_level !== $approvalLevel->level) {
                throw new \Exception('Approval level mismatch');
            }
            
            // Create approval history
            ApprovalHistory::create([
                'approvable_type' => get_class($model),
                'approvable_id' => $model->id,
                'approval_level_id' => $approvalLevel->id,
                'action' => 'approved',
                'approver_id' => $approverId,
                'comments' => $comments,
            ]);
            
            // Check if all approvers at current level have approved
            $allApproversAtLevel = $this->getCurrentApprovers($model, $approvalLevel);
            
            // If no approvers assigned at this level, consider it auto-approved
            if ($allApproversAtLevel->isEmpty()) {
                // No approvers assigned - auto-approve this level and move to next
                Log::warning("Approval level {$approvalLevel->level} has no approvers assigned - auto-approving", [
                    'model_type' => get_class($model),
                    'model_id' => $model->id,
                    'approval_level_id' => $approvalLevel->id,
                ]);
            } else {
                // Check if all assigned approvers have approved
                $approvedApprovers = ApprovalHistory::where('approvable_type', get_class($model))
                    ->where('approvable_id', $model->id)
                    ->where('approval_level_id', $approvalLevel->id)
                    ->where('action', 'approved')
                    ->pluck('approver_id')
                    ->unique();
                
                $allApproversApproved = $allApproversAtLevel->every(function($approver) use ($approvedApprovers) {
                    return $approvedApprovers->contains($approver->id);
                });
                
                // Only proceed if all approvers at current level have approved
                if (!$allApproversApproved) {
                    // Not all approvers have approved yet - wait for others
                    DB::commit();
                    return true; // Approval recorded, but not moving to next level yet
                }
            }
            
            // All approvers at current level have approved - check if there are more levels
            $nextLevel = $this->getNextApprovalLevel($model, $approvalLevel);
            
            if ($nextLevel) {
                // Move to next level
                $model->update([
                    'current_approval_level' => $nextLevel->level,
                ]);
                
                // Log partial approval if model uses LogsActivity trait
                if (method_exists($model, 'logActivity')) {
                    $modelName = class_basename($model);
                    $identifier = $model->order_number ?? $model->reference ?? $model->number ?? $model->credit_note_number ?? $model->invoice_number ?? $model->id;
                    $approver = \App\Models\User::find($approverId);
                    $model->logActivity('approve', "Partially Approved {$modelName} {$identifier} at Level {$approvalLevel->level}", [
                        'Document Type' => $modelName,
                        'Document Reference' => $identifier,
                        'Current Approval Level' => $approvalLevel->level,
                        'Next Approval Level' => $nextLevel->level,
                        'Approved By' => $approver->name ?? 'System',
                        'Comments' => $comments ?? 'No comments',
                        'Approved At' => now()->format('Y-m-d H:i:s')
                    ]);
                }
                
                DB::commit();
                
                // Notify next level approvers
                $this->notifyApprovers($model, $nextLevel);
                
                // Notify submitter of partial approval (moved to next level)
                $submitter = User::find($model->submitted_by);
                $approver = User::find($approverId);
                if ($submitter && $approver) {
                    $this->notifyPartialApproval($model, $submitter, $approver, false);
                }
            } else {
                // All levels approved
                $this->finalizeApproval($model, $approverId);
                
                // Log final approval if model uses LogsActivity trait
                if (method_exists($model, 'logActivity')) {
                    $modelName = class_basename($model);
                    $identifier = $model->order_number ?? $model->reference ?? $model->number ?? $model->credit_note_number ?? $model->invoice_number ?? $model->id;
                    $approver = \App\Models\User::find($approverId);
                    $model->logActivity('approve', "Fully Approved {$modelName} {$identifier} at Final Level {$approvalLevel->level}", [
                        'Document Type' => $modelName,
                        'Document Reference' => $identifier,
                        'Final Approval Level' => $approvalLevel->level,
                        'Approved By' => $approver->name ?? 'System',
                        'Comments' => $comments ?? 'No comments',
                        'Approved At' => now()->format('Y-m-d H:i:s')
                    ]);
                }
                
                DB::commit();
            }
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Reject at current level
     */
    public function reject(Model $model, int $approvalLevelId, int $approverId, string $reason): bool
    {
        DB::beginTransaction();
        
        try {
            $approvalLevel = ApprovalLevel::findOrFail($approvalLevelId);
            
            // Verify user can reject
            if (!$this->canUserApprove($model, $approverId, $approvalLevel)) {
                throw new \Exception('User does not have permission to reject at this level');
            }
            
            // Verify model is in pending approval status
            // HFS uses 'in_review' instead of 'pending_approval'
            $validStatuses = ['pending_approval'];
            if ($model instanceof \App\Models\Assets\HfsRequest) {
                $validStatuses[] = 'in_review';
            }
            if (!in_array($model->status, $validStatuses)) {
                throw new \Exception('Model is not in pending approval status');
            }
            
            // Verify current approval level matches
            if ($model->current_approval_level !== $approvalLevel->level) {
                throw new \Exception('Approval level mismatch');
            }
            
            // Create rejection history
            ApprovalHistory::create([
                'approvable_type' => get_class($model),
                'approvable_id' => $model->id,
                'approval_level_id' => $approvalLevel->id,
                'action' => 'rejected',
                'approver_id' => $approverId,
                'comments' => $reason,
            ]);
            
            // Update model status
            // HFS uses 'rejected' status (same as others)
            $model->update([
                'status' => 'rejected',
                'rejected_by' => $approverId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'current_approval_level' => null,
            ]);
            
            // Log rejection if model uses LogsActivity trait
            if (method_exists($model, 'logActivity')) {
                $modelName = class_basename($model);
                $identifier = $model->order_number ?? $model->reference ?? $model->number ?? $model->credit_note_number ?? $model->invoice_number ?? $model->id;
                $approver = \App\Models\User::find($approverId);
                $model->logActivity('reject', "Rejected {$modelName} {$identifier} at Level {$approvalLevel->level}", [
                    'Document Type' => $modelName,
                    'Document Reference' => $identifier,
                    'Rejection Level' => $approvalLevel->level,
                    'Rejected By' => $approver->name ?? 'System',
                    'Rejection Reason' => $reason ?? 'No reason provided',
                    'Rejected At' => now()->format('Y-m-d H:i:s')
                ]);
            }
            
            DB::commit();
            
            // Notify submitter
            $rejector = User::find($approverId);
            if ($rejector) {
                $this->notifyRejection($model, $rejector, $reason);
            }
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Reassign to another approver
     */
    public function reassign(Model $model, int $approvalLevelId, int $currentApproverId, int $newApproverId, ?string $comments = null): bool
    {
        DB::beginTransaction();
        
        try {
            $approvalLevel = ApprovalLevel::findOrFail($approvalLevelId);
            
            // Verify current user can reassign
            if (!$this->canUserApprove($model, $currentApproverId, $approvalLevel)) {
                throw new \Exception('User does not have permission to reassign at this level');
            }
            
            // Verify new approver exists and can approve
            $newApprover = User::findOrFail($newApproverId);
            if (!$this->canUserApprove($model, $newApproverId, $approvalLevel)) {
                throw new \Exception('New approver does not have permission to approve at this level');
            }
            
            // Create reassignment history
            ApprovalHistory::create([
                'approvable_type' => get_class($model),
                'approvable_id' => $model->id,
                'approval_level_id' => $approvalLevel->id,
                'action' => 'reassigned',
                'approver_id' => $currentApproverId,
                'comments' => $comments,
                'reassigned_to_user_id' => $newApproverId,
            ]);
            
            DB::commit();
            
            // Notify new approver (will be implemented in Phase 7)
            // Notification::send($newApprover, new ReassignmentNotification($model, $approvalLevel));
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reassigning: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Auto-approve when no approval levels are configured
     */
    protected function autoApprove(Model $model, int $userId): bool
    {
        DB::beginTransaction();
        
        try {
            $model->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'submitted_by' => $userId,
                'submitted_at' => now(),
            ]);
            
            // Log auto-approval if model uses LogsActivity trait
            if (method_exists($model, 'logActivity')) {
                $modelName = class_basename($model);
                $identifier = $model->order_number ?? $model->reference ?? $model->number ?? $model->credit_note_number ?? $model->invoice_number ?? $model->id;
                $user = \App\Models\User::find($userId);
                $model->logActivity('approve', "Auto-Approved {$modelName} {$identifier} (No approval levels configured)", [
                    'Document Type' => $modelName,
                    'Document Reference' => $identifier,
                    'Auto-Approved By' => $user->name ?? 'System',
                    'Reason' => 'No approval levels configured',
                    'Approved At' => now()->format('Y-m-d H:i:s')
                ]);
            }
            
            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error auto-approving: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get next approval level
     */
    public function getNextApprovalLevel(Model $model, ApprovalLevel $currentLevel): ?ApprovalLevel
    {
        $module = $this->getModuleName($model);
        $companyId = $this->getCompanyId($model);
        
        if (!$companyId) {
            return null;
        }
        
        return ApprovalLevel::where('module', $module)
            ->where('company_id', $companyId)
            ->where('approval_order', '>', $currentLevel->approval_order)
            ->where('is_required', true)
            ->orderBy('approval_order')
            ->first();
    }
    
    /**
     * Get current approvers for a model
     */
    public function getCurrentApprovers(Model $model, ?ApprovalLevel $level = null): \Illuminate\Support\Collection
    {
        $currentLevel = $level ?? $this->getCurrentApprovalLevel($model);
        
        if (!$currentLevel) {
            return collect();
        }
        
        $branchId = $model->branch_id ?? null;
        
        // Get assignments for this level, considering branch-specific assignments
        $assignments = ApprovalLevelAssignment::where('approval_level_id', $currentLevel->id)
            ->where(function($query) use ($branchId) {
                // Global assignments (no branch) or branch-specific assignments
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            })
            ->get();
        
        $approvers = collect();
        
        foreach ($assignments as $assignment) {
            if ($assignment->user_id) {
                $user = User::find($assignment->user_id);
                if ($user) {
                    $approvers->push($user);
                }
            } elseif ($assignment->role_id) {
                // Get users with this role
                $role = \App\Models\Role::find($assignment->role_id);
                if ($role) {
                    $roleUsers = User::role($role->name)->get();
                    $approvers = $approvers->merge($roleUsers);
                }
            }
        }
        
        return $approvers->unique('id')->filter();
    }
    
    /**
     * Check if user can approve
     */
    public function canUserApprove(Model $model, int $userId, ?ApprovalLevel $level = null): bool
    {
        $level = $level ?? $this->getCurrentApprovalLevel($model);
        
        if (!$level) {
            return false;
        }
        
        $approvers = $this->getCurrentApprovers($model);
        
        return $approvers->contains('id', $userId);
    }
    
    /**
     * Check if user can submit for approval
     */
    public function canUserSubmit(Model $model, int $userId): bool
    {
        // User can submit if:
        // 1. Model is in draft or rejected status
        // 2. User is the creator or has permission
        if (!in_array($model->status, ['draft', 'rejected'])) {
            return false;
        }
        
        // Check if user is the creator
        if (isset($model->user_id) && $model->user_id === $userId) {
            return true;
        }
        
        // Additional permission checks can be added here
        return true;
    }
    
    /**
     * Finalize approval (all levels approved)
     */
    protected function finalizeApproval(Model $model, int $approverId): void
    {
        $model->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'current_approval_level' => null,
        ]);
        
        // For HFS: trigger reclassification after approval
        if ($model instanceof \App\Models\Assets\HfsRequest) {
            try {
                $hfsService = app(\App\Services\Assets\Hfs\HfsService::class);
                $hfsService->approveHfsRequest($model, 'final', $approverId, null);
            } catch (\Exception $e) {
                Log::error('HFS reclassification error after approval: ' . $e->getMessage());
                // Don't throw - approval is already recorded
            }
        }
        
        // For budget: can be activated
        // For bank reconciliation: can be finalized
        $this->notifyFinalApproval($model);
    }
    
    /**
     * Get module name from model
     */
    protected function getModuleName(Model $model): string
    {
        $class = get_class($model);
        
        if (str_contains($class, 'Budget')) {
            return 'budget';
        } elseif (str_contains($class, 'BankReconciliation')) {
            return 'bank_reconciliation';
        } elseif ($model instanceof PurchaseRequisition) {
            return 'purchase_requisition';
        } elseif ($model instanceof \App\Models\Purchase\PurchaseOrder) {
            return 'purchase_order';
        } elseif ($model instanceof Provision) {
            return 'provision';
        }
        
        throw new \Exception('Unknown module type: ' . $class);
    }
    
    /**
     * Get current approval level
     */
    public function getCurrentApprovalLevel(Model $model): ?ApprovalLevel
    {
        if (!$model->current_approval_level) {
            return null;
        }
        
        $module = $this->getModuleName($model);
        $companyId = $this->getCompanyId($model);
        
        if (!$companyId) {
            return null;
        }
        
        return ApprovalLevel::where('module', $module)
            ->where('company_id', $companyId)
            ->where('level', $model->current_approval_level)
            ->first();
    }
    
    /**
     * Get company ID from model
     * Handles both direct company_id attribute and relationships (e.g., BankReconciliation)
     */
    protected function getCompanyId(Model $model): ?int
    {
        // Try direct attribute first
        if (isset($model->company_id)) {
            return $model->company_id;
        }
        
        // For BankReconciliation, get company_id through relationships
        if ($model instanceof BankReconciliation) {
            // Ensure relationships are loaded
            if (!$model->relationLoaded('bankAccount')) {
                $model->load('bankAccount.chartAccount.accountClassGroup');
            }
            return $model->bankAccount?->chartAccount?->accountClassGroup?->company_id;
        }
        
        // Try accessor if exists
        if (method_exists($model, 'getCompanyIdAttribute')) {
            return $model->company_id;
        }
        
        return null;
    }
    
    /**
     * Get first approval level
     */
    protected function getFirstApprovalLevel(Model $model): ?ApprovalLevel
    {
        $module = $this->getModuleName($model);
        $companyId = $this->getCompanyId($model);
        
        if (!$companyId) {
            Log::warning("Cannot find company_id for model", [
                'model_type' => get_class($model),
                'model_id' => $model->id
            ]);
            return null;
        }
        
        return ApprovalLevel::where('module', $module)
            ->where('company_id', $companyId)
            ->where('is_required', true)
            ->orderBy('approval_order')
            ->first();
    }
    
    /**
     * Get all approval levels for a module
     */
    public function getApprovalLevels(Model $model): \Illuminate\Support\Collection
    {
        $module = $this->getModuleName($model);
        $companyId = $this->getCompanyId($model);
        
        if (!$companyId) {
            return collect([]);
        }
        
        return ApprovalLevel::where('module', $module)
            ->where('company_id', $companyId)
            ->orderBy('approval_order')
            ->get();
    }
    
    /**
     * Get approval history for a model
     */
    public function getApprovalHistory(Model $model): \Illuminate\Support\Collection
    {
        return ApprovalHistory::where('approvable_type', get_class($model))
            ->where('approvable_id', $model->id)
            ->with(['approvalLevel', 'approver', 'reassignedTo'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
    
    /**
     * Get pending approvals for a user
     */
    public function getPendingApprovalsForUser(int $userId, string $module = null): \Illuminate\Support\Collection
    {
        $user = User::findOrFail($userId);
        $pendingItems = collect();
        
        // Get all pending approval levels for user's company
        $query = ApprovalLevel::where('company_id', $user->company_id);
        
        if ($module) {
            $query->where('module', $module);
        }
        
        $levels = $query->get();
        
        foreach ($levels as $level) {
            // Get models pending at this level
            $modelClass = $this->getModelClassFromModule($level->module);
            
            if (!$modelClass) {
                continue;
            }
            
            // HFS uses 'in_review' instead of 'pending_approval'
            $status = ($modelClass === \App\Models\Assets\HfsRequest::class) ? 'in_review' : 'pending_approval';
            $models = $modelClass::where('status', $status)
                ->where('current_approval_level', $level->level)
                ->where('company_id', $user->company_id)
                ->get();
            
            foreach ($models as $model) {
                if ($this->canUserApprove($model, $userId, $level)) {
                    $pendingItems->push([
                        'model' => $model,
                        'level' => $level,
                    ]);
                }
            }
        }
        
        return $pendingItems;
    }
    
    /**
     * Check if model has pending approvals
     */
    public function hasPendingApprovals(Model $model): bool
    {
        // HFS uses 'in_review' instead of 'pending_approval'
        $expectedStatus = ($model instanceof \App\Models\Assets\HfsRequest) ? 'in_review' : 'pending_approval';
        return $model->status === $expectedStatus && $model->current_approval_level !== null;
    }
    
    /**
     * Get approval status summary for a model
     */
    public function getApprovalStatusSummary(Model $model): array
    {
        $currentLevel = $this->getCurrentApprovalLevel($model);
        $allLevels = $this->getApprovalLevels($model);
        $history = $this->getApprovalHistory($model);
        
        $summary = [
            'status' => $model->status,
            'current_level' => $currentLevel ? [
                'id' => $currentLevel->id,
                'level' => $currentLevel->level,
                'name' => $currentLevel->level_name,
            ] : null,
            'total_levels' => $allLevels->count(),
            'completed_levels' => $history->where('action', 'approved')->count(),
            'approvers' => $currentLevel ? $this->getCurrentApprovers($model, $currentLevel)->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            })->values() : collect(),
            'history' => $history->map(function($entry) {
                return [
                    'action' => $entry->action,
                    'level' => $entry->approvalLevel->level_name ?? 'N/A',
                    'approver' => $entry->approver->name ?? 'N/A',
                    'comments' => $entry->comments,
                    'created_at' => $entry->created_at->format('Y-m-d H:i:s'),
                ];
            })->values(),
        ];
        
        return $summary;
    }
    
    /**
     * Get model class from module name
     */
    protected function getModelClassFromModule(string $module): ?string
    {
        return match($module) {
            'budget' => \App\Models\Budget::class,
            'bank_reconciliation' => \App\Models\BankReconciliation::class,
            'purchase_requisition' => \App\Models\Purchase\PurchaseRequisition::class,
            'purchase_order' => \App\Models\Purchase\PurchaseOrder::class,
            'provision' => \App\Models\Provision::class,
            default => null,
        };
    }
    
    /**
     * Notify submitter that item was submitted
     */
    protected function notifySubmitter(Model $model, User $submitter): void
    {
        $module = $this->getModuleName($model);
        
        try {
            if ($module === 'budget') {
                Notification::send($submitter, new BudgetSubmittedForApproval($model, $submitter));
            } elseif ($module === 'bank_reconciliation') {
                Notification::send($submitter, new BankReconciliationSubmittedForApproval($model, $submitter));
            }
        } catch (\Exception $e) {
            Log::error("Failed to send submission notification: " . $e->getMessage());
        }
    }
    
    /**
     * Notify approvers that approval is required
     */
    protected function notifyApprovers(Model $model, ApprovalLevel $level): void
    {
        $approvers = $this->getCurrentApprovers($model);
        $module = $this->getModuleName($model);
        
        Log::info("Notifying approvers for {$module}", [
            'model_id' => $model->id,
            'approval_level_id' => $level->id,
            'approval_level_name' => $level->level_name,
            'approvers_count' => $approvers->count(),
            'approvers' => $approvers->map(function($a) {
                return [
                    'id' => $a->id,
                    'name' => $a->name,
                    'email' => $a->email,
                    'phone' => $a->phone ?? 'N/A'
                ];
            })->toArray()
        ]);
        
        foreach ($approvers as $approver) {
            try {
                // Log notification attempt
                Log::info("Sending approval notification to approver", [
                    'approver_id' => $approver->id,
                    'approver_name' => $approver->name,
                    'approver_phone' => $approver->phone ?? 'No phone',
                    'module' => $module
                ]);
                
                // Send notification (email, database, and SMS if phone exists)
                if ($module === 'budget') {
                    Notification::send($approver, new BudgetApprovalRequired($model, $level));
                } elseif ($module === 'bank_reconciliation') {
                    Notification::send($approver, new BankReconciliationApprovalRequired($model, $level));
                    
                    // Send SMS immediately to approver if they have a phone number
                    if ($approver->phone) {
                        $this->sendSmsToApprover($approver, $model, $level);
                    }
                }
                
                Log::info("Notification sent successfully to approver", [
                    'approver_id' => $approver->id,
                    'approver_name' => $approver->name
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send approval required notification to user {$approver->id}", [
                    'approver_id' => $approver->id,
                    'approver_name' => $approver->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
    
    /**
     * Send SMS directly to approver for bank reconciliation
     */
    protected function sendSmsToApprover(User $approver, Model $model, ApprovalLevel $level): void
    {
        try {
            if (!($model instanceof BankReconciliation)) {
                return;
            }
            
            $phone = $approver->phone;
            if (!$phone) {
                Log::info("SMS skipped: Approver has no phone number", [
                    'approver_id' => $approver->id,
                    'approver_name' => $approver->name
                ]);
                return;
            }
            
            // Format phone number
            $formattedPhone = function_exists('normalize_phone_number') 
                ? normalize_phone_number($phone) 
                : $this->formatPhoneNumber($phone);
            
            // Build SMS message
            $senderName = config('services.sms.senderid', 'SAFCO');
            $date = $model->reconciliation_date ? $model->reconciliation_date->format('M d, Y') : 'N/A';
            $message = "Hello {$approver->name}, Bank reconciliation for {$model->bankAccount->name} ({$date}) requires your approval at {$level->level_name} level. Please review and take action. - {$senderName}";
            
            // Send SMS using SmsHelper
            $response = \App\Helpers\SmsHelper::send($formattedPhone, $message);
            
            Log::info("SMS sent directly to approver", [
                'approver_id' => $approver->id,
                'approver_name' => $approver->name,
                'phone' => $formattedPhone,
                'response' => $response
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send SMS to approver", [
                'approver_id' => $approver->id,
                'approver_name' => $approver->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Remove leading +
        $phone = ltrim($phone, '+');
        
        // If starts with 0, replace with 255 (Tanzania country code)
        if (str_starts_with($phone, '0')) {
            return '255' . substr($phone, 1);
        }
        
        // If doesn't start with 255, add it
        if (!str_starts_with($phone, '255')) {
            return '255' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Notify partial approval (not final) - notifies submitter that it moved to next level
     */
    protected function notifyPartialApproval(Model $model, User $notifiable, User $approver, bool $isFinal): void
    {
        $module = $this->getModuleName($model);
        
        try {
            if ($module === 'budget') {
                Notification::send($notifiable, new BudgetApproved($model, $approver, $isFinal));
            } elseif ($module === 'bank_reconciliation') {
                Notification::send($notifiable, new BankReconciliationApproved($model, $approver, $isFinal));
            }
        } catch (\Exception $e) {
            Log::error("Failed to send partial approval notification: " . $e->getMessage());
        }
    }
    
    /**
     * Notify rejection
     */
    protected function notifyRejection(Model $model, User $rejector, string $reason): void
    {
        $submitter = User::find($model->submitted_by);
        
        if (!$submitter) {
            return;
        }
        
        $module = $this->getModuleName($model);
        
        try {
            if ($module === 'budget') {
                Notification::send($submitter, new BudgetRejected($model, $rejector, $reason));
            } elseif ($module === 'bank_reconciliation') {
                Notification::send($submitter, new BankReconciliationRejected($model, $rejector, $reason));
            }
        } catch (\Exception $e) {
            Log::error("Failed to send rejection notification: " . $e->getMessage());
        }
    }
    
    /**
     * Notify final approval
     */
    protected function notifyFinalApproval(Model $model): void
    {
        $submitter = User::find($model->submitted_by);
        
        if (!$submitter) {
            return;
        }
        
        $approver = User::find($model->approved_by);
        $module = $this->getModuleName($model);
        
        try {
            if ($module === 'budget') {
                Notification::send($submitter, new BudgetApproved($model, $approver, true));
            } elseif ($module === 'bank_reconciliation') {
                Notification::send($submitter, new BankReconciliationApproved($model, $approver, true));
            }
        } catch (\Exception $e) {
            Log::error("Failed to send final approval notification: " . $e->getMessage());
        }
    }
    
    /**
     * Resubmit for approval after changes (e.g., reallocation)
     * Resets approval status and triggers approval workflow again
     * 
     * @param Model $model The model to resubmit
     * @param int $userId User ID who made the change
     * @param string|null $changeReason Reason for the change
     * @param bool $useTransaction Whether to use a transaction (set to false if already in a transaction)
     * @return bool
     */
    public function resubmitForApprovalAfterChange(Model $model, int $userId, string $changeReason = null, bool $useTransaction = true): bool
    {
        if ($useTransaction) {
            DB::beginTransaction();
        }
        
        try {
            // Get first approval level
            $firstLevel = $this->getFirstApprovalLevel($model);
            
            if (!$firstLevel) {
                // No approval required, auto-approve
                if ($useTransaction) {
                    DB::commit();
                }
                return $this->autoApprove($model, $userId);
            }
            
            // Reset approval fields and set to pending
            $model->update([
                'status' => 'pending_approval',
                'current_approval_level' => $firstLevel->level,
                'submitted_by' => $userId,
                'submitted_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
            
            // Create approval history entry for resubmission
            $comments = $changeReason ? "Resubmitted after change: {$changeReason}" : "Resubmitted for approval after changes";
            ApprovalHistory::create([
                'approvable_type' => get_class($model),
                'approvable_id' => $model->id,
                'approval_level_id' => $firstLevel->id,
                'action' => 'submitted',
                'approver_id' => $userId,
                'comments' => $comments,
            ]);
            
            // Log activity if model uses LogsActivity trait
            if (method_exists($model, 'logActivity')) {
                $modelName = class_basename($model);
                $identifier = $model->order_number ?? $model->reference ?? $model->number ?? $model->credit_note_number ?? $model->invoice_number ?? $model->id;
                $submittedBy = \App\Models\User::find($userId);
                $model->logActivity('update', "Resubmitted {$modelName} {$identifier} for Approval at Level {$firstLevel->level} after changes", [
                    'Document Type' => $modelName,
                    'Document Reference' => $identifier,
                    'Approval Level' => $firstLevel->level,
                    'Submitted By' => $submittedBy->name ?? 'System',
                    'Reason' => $changeReason ?? 'Changes made',
                    'Submitted At' => now()->format('Y-m-d H:i:s')
                ]);
            }
            
            if ($useTransaction) {
                DB::commit();
            }
            
            // Notify submitter
            $submitter = User::find($userId);
            if ($submitter) {
                $this->notifySubmitter($model, $submitter);
            }
            
            // Notify Level 1 approvers
            $this->notifyApprovers($model, $firstLevel);
            
            return true;
        } catch (\Exception $e) {
            if ($useTransaction) {
                DB::rollBack();
            }
            Log::error('Error resubmitting for approval after change: ' . $e->getMessage());
            throw $e;
        }
    }
}

