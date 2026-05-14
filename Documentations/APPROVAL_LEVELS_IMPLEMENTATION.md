# Approval Levels Implementation Flow
## Budget & Bank Reconciliation

---

## Table of Contents
1. [Overview](#overview)
2. [Database Schema](#database-schema)
3. [Approval Workflow](#approval-workflow)
4. [Status Transitions](#status-transitions)
5. [Implementation Steps](#implementation-steps)
6. [Code Structure](#code-structure)

---

## Overview

### Purpose
Implement multi-level approval workflows for:
- **Budget**: Require approvals before budget becomes active
- **Bank Reconciliation**: Require approvals before reconciliation is finalized

### Key Features
- Configurable approval levels (1-5 levels)
- Role-based approvers
- Approval history/audit trail
- Email notifications
- Escalation rules
- Rejection with comments
- Re-submission after rejection

---

## Database Schema

### 1. Approval Levels Configuration Table

```sql
CREATE TABLE `approval_levels` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `module` varchar(50) NOT NULL, -- 'budget' or 'bank_reconciliation'
    `level` tinyint unsigned NOT NULL, -- 1, 2, 3, etc.
    `level_name` varchar(100) NOT NULL, -- 'Department Head', 'Finance Manager', 'CFO'
    `is_required` boolean DEFAULT true,
    `approval_order` tinyint unsigned NOT NULL, -- Order of approval
    `company_id` bigint unsigned NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_module_level_company` (`module`, `level`, `company_id`),
    KEY `approval_levels_company_id_foreign` (`company_id`),
    CONSTRAINT `approval_levels_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Approval Level Assignments (Who can approve at each level)

```sql
CREATE TABLE `approval_level_assignments` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `approval_level_id` bigint unsigned NOT NULL,
    `user_id` bigint unsigned NULL, -- Specific user
    `role_id` bigint unsigned NULL, -- OR specific role
    `branch_id` bigint unsigned NULL, -- Optional: branch-specific approver
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `approval_level_assignments_approval_level_id_foreign` (`approval_level_id`),
    KEY `approval_level_assignments_user_id_foreign` (`user_id`),
    KEY `approval_level_assignments_role_id_foreign` (`role_id`),
    CONSTRAINT `approval_level_assignments_approval_level_id_foreign` FOREIGN KEY (`approval_level_id`) REFERENCES `approval_levels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `approval_level_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `approval_level_assignments_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Approval History Table (Audit Trail)

```sql
CREATE TABLE `approval_histories` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `approvable_type` varchar(255) NOT NULL, -- 'App\Models\Budget' or 'App\Models\BankReconciliation'
    `approvable_id` bigint unsigned NOT NULL,
    `approval_level_id` bigint unsigned NOT NULL,
    `action` enum('submitted', 'approved', 'rejected', 'reassigned') NOT NULL,
    `approver_id` bigint unsigned NULL, -- User who took action
    `comments` text NULL,
    `reassigned_to_user_id` bigint unsigned NULL, -- If reassigned
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `approval_histories_approvable` (`approvable_type`, `approvable_id`),
    KEY `approval_histories_approval_level_id_foreign` (`approval_level_id`),
    KEY `approval_histories_approver_id_foreign` (`approver_id`),
    CONSTRAINT `approval_histories_approval_level_id_foreign` FOREIGN KEY (`approval_level_id`) REFERENCES `approval_levels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `approval_histories_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4. Update Budgets Table

```sql
ALTER TABLE `budgets` 
ADD COLUMN `status` enum('draft', 'pending_approval', 'approved', 'rejected', 'active', 'archived') DEFAULT 'draft' AFTER `description`,
ADD COLUMN `current_approval_level` tinyint unsigned NULL AFTER `status`,
ADD COLUMN `submitted_by` bigint unsigned NULL AFTER `current_approval_level`,
ADD COLUMN `submitted_at` timestamp NULL AFTER `submitted_by`,
ADD COLUMN `approved_by` bigint unsigned NULL AFTER `submitted_at`,
ADD COLUMN `approved_at` timestamp NULL AFTER `approved_by`,
ADD COLUMN `rejected_by` bigint unsigned NULL AFTER `approved_at`,
ADD COLUMN `rejected_at` timestamp NULL AFTER `rejected_by`,
ADD COLUMN `rejection_reason` text NULL AFTER `rejected_at`,
ADD KEY `budgets_status_index` (`status`),
ADD KEY `budgets_submitted_by_foreign` (`submitted_by`),
ADD KEY `budgets_approved_by_foreign` (`approved_by`),
ADD KEY `budgets_rejected_by_foreign` (`rejected_by`),
ADD CONSTRAINT `budgets_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `budgets_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `budgets_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
```

### 5. Update Bank Reconciliations Table

```sql
ALTER TABLE `bank_reconciliations`
MODIFY COLUMN `status` enum('draft', 'pending_approval', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'draft',
ADD COLUMN `current_approval_level` tinyint unsigned NULL AFTER `status`,
ADD COLUMN `submitted_by` bigint unsigned NULL AFTER `current_approval_level`,
ADD COLUMN `submitted_at` timestamp NULL AFTER `submitted_by`,
ADD COLUMN `approved_by` bigint unsigned NULL AFTER `submitted_at`,
ADD COLUMN `approved_at` timestamp NULL AFTER `approved_by`,
ADD COLUMN `rejected_by` bigint unsigned NULL AFTER `approved_at`,
ADD COLUMN `rejected_at` timestamp NULL AFTER `rejected_by`,
ADD COLUMN `rejection_reason` text NULL AFTER `rejected_at`,
ADD KEY `bank_reconciliations_submitted_by_foreign` (`submitted_by`),
ADD KEY `bank_reconciliations_approved_by_foreign` (`approved_by`),
ADD KEY `bank_reconciliations_rejected_by_foreign` (`rejected_by`),
ADD CONSTRAINT `bank_reconciliations_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `bank_reconciliations_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `bank_reconciliations_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
```

---

## Approval Workflow

### Budget Approval Flow

```
┌─────────────┐
│   DRAFT     │ User creates budget
└──────┬──────┘
       │
       │ Submit for Approval
       ▼
┌─────────────────────┐
│ PENDING_APPROVAL    │ Status: pending_approval
│ Level 1             │ current_approval_level: 1
└──────┬──────────────┘
       │
       ├─── Level 1 Approver ───┐
       │                        │
       │                        ▼
       │              ┌─────────────────┐
       │              │   APPROVED L1    │
       │              └────────┬─────────┘
       │                       │
       │                       │ Check if more levels
       │                       │
       │              ┌────────▼─────────┐
       │              │ More Levels?     │
       │              └────┬────────┬───┘
       │                   │        │
       │              YES  │        │ NO
       │                   │        │
       │         ┌─────────▼───┐    │
       │         │ Level 2     │    │
       │         │ Pending     │    │
       │         └──────┬──────┘    │
       │                │           │
       │                │           │
       │         ┌──────▼───────────▼──┐
       │         │  All Levels Approved │
       │         └──────┬───────────────┘
       │                │
       │                ▼
       │         ┌──────────────┐
       │         │   APPROVED   │ Status: approved
       │         └──────┬───────┘
       │                │
       │                │ Activate Budget
       │                ▼
       │         ┌──────────────┐
       │         │    ACTIVE     │ Status: active
       │         └──────────────┘
       │
       │
       └─── REJECTED ───┐
                        │
                        ▼
              ┌─────────────────┐
              │   REJECTED      │ Status: rejected
              │ rejection_reason│
              └────────┬────────┘
                       │
                       │ User can resubmit
                       │
                       ▼
              ┌─────────────────┐
              │ Back to DRAFT   │
              └─────────────────┘
```

### Bank Reconciliation Approval Flow

```
┌─────────────┐
│   DRAFT     │ User creates reconciliation
└──────┬──────┘
       │
       │ Submit for Approval
       ▼
┌─────────────────────┐
│ PENDING_APPROVAL    │ Status: pending_approval
│ Level 1             │ current_approval_level: 1
└──────┬──────────────┘
       │
       ├─── Level 1 Approver ───┐
       │                        │
       │                        ▼
       │              ┌─────────────────┐
       │              │   APPROVED L1    │
       │              └────────┬─────────┘
       │                       │
       │              ┌────────▼─────────┐
       │              │ More Levels?     │
       │              └────┬────────┬───┘
       │                   │        │
       │              YES  │        │ NO
       │                   │        │
       │         ┌─────────▼───┐    │
       │         │ Level 2     │    │
       │         │ Pending     │    │
       │         └──────┬──────┘    │
       │                │           │
       │         ┌──────▼───────────▼──┐
       │         │  All Levels Approved │
       │         └──────┬───────────────┘
       │                │
       │                ▼
       │         ┌──────────────┐
       │         │   APPROVED   │ Status: approved
       │         └──────┬───────┘
       │                │
       │                │ Finalize Reconciliation
       │                ▼
       │         ┌──────────────┐
       │         │  COMPLETED   │ Status: completed
       │         └──────────────┘
       │
       │
       └─── REJECTED ───┐
                        │
                        ▼
              ┌─────────────────┐
              │   REJECTED      │ Status: rejected
              │ rejection_reason│
              └────────┬────────┘
                       │
                       │ User can resubmit
                       │
                       ▼
              ┌─────────────────┐
              │ Back to DRAFT   │
              └─────────────────┘
```

---

## Status Transitions

### Budget Status Transitions

| From Status | To Status | Action | Conditions |
|------------|-----------|--------|------------|
| `draft` | `pending_approval` | Submit | User has permission to submit |
| `pending_approval` | `pending_approval` | Approve Level N | Current approver approves, more levels exist |
| `pending_approval` | `approved` | Approve Final Level | Last approver approves |
| `approved` | `active` | Activate | Auto or manual activation |
| `pending_approval` | `rejected` | Reject | Any approver rejects |
| `rejected` | `draft` | Resubmit | User edits and resubmits |
| `active` | `archived` | Archive | Budget period ended |

### Bank Reconciliation Status Transitions

| From Status | To Status | Action | Conditions |
|------------|-----------|--------|------------|
| `draft` | `pending_approval` | Submit | User has permission to submit |
| `pending_approval` | `pending_approval` | Approve Level N | Current approver approves, more levels exist |
| `pending_approval` | `approved` | Approve Final Level | Last approver approves |
| `approved` | `completed` | Finalize | Auto or manual finalization |
| `pending_approval` | `rejected` | Reject | Any approver rejects |
| `rejected` | `draft` | Resubmit | User edits and resubmits |
| Any | `cancelled` | Cancel | User cancels (only if not completed) |

---

## Implementation Steps

### Phase 1: Database Setup

1. **Create Migration Files**
   ```bash
   php artisan make:migration create_approval_levels_table
   php artisan make:migration create_approval_level_assignments_table
   php artisan make:migration create_approval_histories_table
   php artisan make:migration add_approval_fields_to_budgets_table
   php artisan make:migration add_approval_fields_to_bank_reconciliations_table
   ```

2. **Run Migrations**
   ```bash
   php artisan migrate
   ```

### Phase 2: Model Creation

1. **Create Models**
   ```bash
   php artisan make:model ApprovalLevel
   php artisan make:model ApprovalLevelAssignment
   php artisan make:model ApprovalHistory
   ```

2. **Define Relationships**
   - `ApprovalLevel` → `hasMany` ApprovalLevelAssignments
   - `ApprovalLevel` → `hasMany` ApprovalHistories
   - `Budget` → `morphMany` ApprovalHistories
   - `BankReconciliation` → `morphMany` ApprovalHistories
   - `User` → `hasMany` ApprovalHistories (as approver)

### Phase 3: Service Layer

1. **Create Approval Service**
   ```bash
   php artisan make:service ApprovalService
   ```

   **Key Methods:**
   - `submitForApproval($model, $userId)`
   - `approve($model, $approvalLevelId, $approverId, $comments)`
   - `reject($model, $approvalLevelId, $approverId, $reason)`
   - `getNextApprovalLevel($model)`
   - `getCurrentApprovers($model)`
   - `canUserApprove($model, $userId)`
   - `isFullyApproved($model)`
   - `getApprovalHistory($model)`

### Phase 4: Controller Updates

1. **BudgetController**
   - Add `submitForApproval()` method
   - Add `approve()` method
   - Add `reject()` method
   - Add `approvalHistory()` method
   - Update `store()` to set status to 'draft'
   - Update `update()` to allow editing only if draft/rejected

2. **BankReconciliationController**
   - Add `submitForApproval()` method
   - Add `approve()` method
   - Add `reject()` method
   - Add `approvalHistory()` method
   - Update `store()` to set status to 'draft'
   - Update `update()` to allow editing only if draft/rejected

### Phase 5: Routes

```php
// Budget Approval Routes
Route::post('/budgets/{budget}/submit-approval', [BudgetController::class, 'submitForApproval'])
    ->name('budgets.submit-approval');
Route::post('/budgets/{budget}/approve', [BudgetController::class, 'approve'])
    ->name('budgets.approve');
Route::post('/budgets/{budget}/reject', [BudgetController::class, 'reject'])
    ->name('budgets.reject');
Route::get('/budgets/{budget}/approval-history', [BudgetController::class, 'approvalHistory'])
    ->name('budgets.approval-history');

// Bank Reconciliation Approval Routes
Route::post('/bank-reconciliations/{reconciliation}/submit-approval', [BankReconciliationController::class, 'submitForApproval'])
    ->name('bank-reconciliations.submit-approval');
Route::post('/bank-reconciliations/{reconciliation}/approve', [BankReconciliationController::class, 'approve'])
    ->name('bank-reconciliations.approve');
Route::post('/bank-reconciliations/{reconciliation}/reject', [BankReconciliationController::class, 'reject'])
    ->name('bank-reconciliations.reject');
Route::get('/bank-reconciliations/{reconciliation}/approval-history', [BankReconciliationController::class, 'approvalHistory'])
    ->name('bank-reconciliations.approval-history');
```

### Phase 6: Views

1. **Budget Views**
   - Add "Submit for Approval" button (if draft/rejected)
   - Add approval status badge
   - Add approval history section
   - Add approval/reject buttons (for approvers)
   - Show current approver information

2. **Bank Reconciliation Views**
   - Add "Submit for Approval" button (if draft/rejected)
   - Add approval status badge
   - Add approval history section
   - Add approval/reject buttons (for approvers)
   - Show current approver information

### Phase 7: Notifications

1. **Create Notification Classes**
   ```bash
   php artisan make:notification BudgetSubmittedForApproval
   php artisan make:notification BudgetApprovalRequired
   php artisan make:notification BudgetApproved
   php artisan make:notification BudgetRejected
   php artisan make:notification BankReconciliationSubmittedForApproval
   php artisan make:notification BankReconciliationApprovalRequired
   php artisan make:notification BankReconciliationApproved
   php artisan make:notification BankReconciliationRejected
   ```

2. **Send Notifications**
   - On submit: Notify Level 1 approvers
   - On approve: Notify next level approvers (if any) or submitter (if final)
   - On reject: Notify submitter

### Phase 8: Permissions

Add permissions:
- `submit budget for approval`
- `approve budget`
- `reject budget`
- `submit bank reconciliation for approval`
- `approve bank reconciliation`
- `reject bank reconciliation`
- `view approval history`

---

## Code Structure

### 1. ApprovalService.php

```php
<?php

namespace App\Services;

use App\Models\ApprovalLevel;
use App\Models\ApprovalHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ApprovalService
{
    /**
     * Submit model for approval
     */
    public function submitForApproval(Model $model, int $userId): bool
    {
        // Get first approval level
        $firstLevel = $this->getFirstApprovalLevel($model);
        
        if (!$firstLevel) {
            // No approval required, auto-approve
            return $this->autoApprove($model, $userId);
        }
        
        // Update model status
        $model->update([
            'status' => 'pending_approval',
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
        
        // Notify approvers
        $this->notifyApprovers($model, $firstLevel);
        
        return true;
    }
    
    /**
     * Approve at current level
     */
    public function approve(Model $model, int $approvalLevelId, int $approverId, ?string $comments = null): bool
    {
        $approvalLevel = ApprovalLevel::findOrFail($approvalLevelId);
        
        // Verify user can approve
        if (!$this->canUserApprove($model, $approverId, $approvalLevel)) {
            throw new \Exception('User does not have permission to approve at this level');
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
        
        // Check if there are more levels
        $nextLevel = $this->getNextApprovalLevel($model, $approvalLevel);
        
        if ($nextLevel) {
            // Move to next level
            $model->update([
                'current_approval_level' => $nextLevel->level,
            ]);
            
            // Notify next level approvers
            $this->notifyApprovers($model, $nextLevel);
        } else {
            // All levels approved
            $this->finalizeApproval($model, $approverId);
        }
        
        return true;
    }
    
    /**
     * Reject at current level
     */
    public function reject(Model $model, int $approvalLevelId, int $approverId, string $reason): bool
    {
        $approvalLevel = ApprovalLevel::findOrFail($approvalLevelId);
        
        // Verify user can reject
        if (!$this->canUserApprove($model, $approverId, $approvalLevel)) {
            throw new \Exception('User does not have permission to reject at this level');
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
        $model->update([
            'status' => 'rejected',
            'rejected_by' => $approverId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'current_approval_level' => null,
        ]);
        
        // Notify submitter
        $this->notifyRejection($model, $approverId, $reason);
        
        return true;
    }
    
    /**
     * Get next approval level
     */
    public function getNextApprovalLevel(Model $model, ApprovalLevel $currentLevel): ?ApprovalLevel
    {
        $module = $this->getModuleName($model);
        
        return ApprovalLevel::where('module', $module)
            ->where('company_id', $model->company_id)
            ->where('approval_order', '>', $currentLevel->approval_order)
            ->where('is_required', true)
            ->orderBy('approval_order')
            ->first();
    }
    
    /**
     * Get current approvers for a model
     */
    public function getCurrentApprovers(Model $model): \Illuminate\Support\Collection
    {
        $currentLevel = $this->getCurrentApprovalLevel($model);
        
        if (!$currentLevel) {
            return collect();
        }
        
        $assignments = $currentLevel->assignments()
            ->where(function($query) use ($model) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $model->branch_id);
            })
            ->get();
        
        $approvers = collect();
        
        foreach ($assignments as $assignment) {
            if ($assignment->user_id) {
                $approvers->push(User::find($assignment->user_id));
            } elseif ($assignment->role_id) {
                $roleUsers = User::role($assignment->role_id)->get();
                $approvers = $approvers->merge($roleUsers);
            }
        }
        
        return $approvers->unique('id');
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
        }
        
        throw new \Exception('Unknown module type');
    }
    
    /**
     * Get current approval level
     */
    protected function getCurrentApprovalLevel(Model $model): ?ApprovalLevel
    {
        if (!$model->current_approval_level) {
            return null;
        }
        
        $module = $this->getModuleName($model);
        
        return ApprovalLevel::where('module', $module)
            ->where('company_id', $model->company_id)
            ->where('level', $model->current_approval_level)
            ->first();
    }
    
    /**
     * Get first approval level
     */
    protected function getFirstApprovalLevel(Model $model): ?ApprovalLevel
    {
        $module = $this->getModuleName($model);
        
        return ApprovalLevel::where('module', $module)
            ->where('company_id', $model->company_id)
            ->where('is_required', true)
            ->orderBy('approval_order')
            ->first();
    }
    
    /**
     * Notify approvers
     */
    protected function notifyApprovers(Model $model, ApprovalLevel $level): void
    {
        $approvers = $this->getCurrentApprovers($model);
        
        foreach ($approvers as $approver) {
            // Send notification
            // Notification::send($approver, new ApprovalRequiredNotification($model, $level));
        }
    }
    
    /**
     * Notify rejection
     */
    protected function notifyRejection(Model $model, int $rejectedBy, string $reason): void
    {
        $submitter = User::find($model->submitted_by);
        
        if ($submitter) {
            // Send notification
            // Notification::send($submitter, new RejectionNotification($model, $reason));
        }
    }
    
    /**
     * Notify final approval
     */
    protected function notifyFinalApproval(Model $model): void
    {
        $submitter = User::find($model->submitted_by);
        
        if ($submitter) {
            // Send notification
            // Notification::send($submitter, new FinalApprovalNotification($model));
        }
    }
}
```

### 2. Controller Example (BudgetController)

```php
public function submitForApproval(Budget $budget, Request $request)
{
    $this->authorize('submit budget for approval', $budget);
    
    // Validate budget is complete
    if ($budget->budgetLines->isEmpty()) {
        return redirect()->back()->with('error', 'Budget must have at least one line item');
    }
    
    $approvalService = app(ApprovalService::class);
    $approvalService->submitForApproval($budget, auth()->id());
    
    return redirect()->back()->with('success', 'Budget submitted for approval');
}

public function approve(Budget $budget, Request $request)
{
    $this->authorize('approve budget', $budget);
    
    $request->validate([
        'approval_level_id' => 'required|exists:approval_levels,id',
        'comments' => 'nullable|string|max:1000',
    ]);
    
    $approvalService = app(ApprovalService::class);
    
    try {
        $approvalService->approve(
            $budget,
            $request->approval_level_id,
            auth()->id(),
            $request->comments
        );
        
        return redirect()->back()->with('success', 'Budget approved successfully');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }
}

public function reject(Budget $budget, Request $request)
{
    $this->authorize('reject budget', $budget);
    
    $request->validate([
        'approval_level_id' => 'required|exists:approval_levels,id',
        'reason' => 'required|string|max:1000',
    ]);
    
    $approvalService = app(ApprovalService::class);
    
    try {
        $approvalService->reject(
            $budget,
            $request->approval_level_id,
            auth()->id(),
            $request->reason
        );
        
        return redirect()->back()->with('success', 'Budget rejected');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }
}

public function approvalHistory(Budget $budget)
{
    $history = ApprovalHistory::where('approvable_type', Budget::class)
        ->where('approvable_id', $budget->id)
        ->with(['approvalLevel', 'approver'])
        ->orderBy('created_at')
        ->get();
    
    return view('accounting.budgets.approval-history', compact('budget', 'history'));
}
```

### 3. View Components

**Approval Status Badge Component:**
```blade
@if($budget->status === 'pending_approval')
    <span class="badge bg-warning">
        Pending Approval - Level {{ $budget->current_approval_level }}
    </span>
@elseif($budget->status === 'approved')
    <span class="badge bg-success">Approved</span>
@elseif($budget->status === 'rejected')
    <span class="badge bg-danger">Rejected</span>
@endif
```

**Approval Actions Component:**
```blade
@if($budget->status === 'pending_approval')
    @php
        $approvalService = app(\App\Services\ApprovalService::class);
        $canApprove = $approvalService->canUserApprove($budget, auth()->id());
        $currentLevel = $approvalService->getCurrentApprovalLevel($budget);
    @endphp
    
    @if($canApprove)
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
            Approve
        </button>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
            Reject
        </button>
    @endif
@endif
```

---

## Configuration UI

### Admin Panel for Approval Levels

Create a settings page where admins can:
1. Configure approval levels for each module
2. Assign approvers (users or roles) to each level
3. Set approval order
4. Enable/disable levels
5. Set branch-specific approvers

---

## Testing Checklist

- [ ] Submit budget for approval
- [ ] Approve at Level 1
- [ ] Approve at Level 2 (if exists)
- [ ] Reject at any level
- [ ] Resubmit after rejection
- [ ] View approval history
- [ ] Email notifications sent correctly
- [ ] Permissions enforced
- [ ] Branch-specific approvers work
- [ ] Role-based approvers work
- [ ] Same for Bank Reconciliation

---

## Notes

1. **Escalation**: Can add auto-escalation if approval is pending for X days
2. **Delegation**: Approvers can delegate to other users
3. **Conditional Approval**: Some levels might be skipped based on amount thresholds
4. **Parallel Approval**: Multiple approvers at same level (all must approve)
5. **Partial Approval**: Some items approved, others pending (for complex budgets)

---

This implementation provides a flexible, scalable approval system that can be extended as needed.

