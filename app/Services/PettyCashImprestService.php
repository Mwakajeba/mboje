<?php

namespace App\Services;

use App\Models\ImprestRequest;
use App\Models\ImprestItem;
use App\Models\PettyCash\PettyCashTransaction;
use App\Models\PettyCash\PettyCashUnit;
use App\Models\PettyCash\PettyCashRegister;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PettyCashImprestService
{
    /**
     * Create an imprest request from a petty cash transaction (Sub-Imprest Mode)
     */
    public static function createImprestRequestFromTransaction(PettyCashTransaction $transaction): ?ImprestRequest
    {
        $unit = $transaction->pettyCashUnit;
        $user = $transaction->createdBy;
        
        // Get employee record for the user
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            \Log::warning('Cannot create imprest request: User has no employee record', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id
            ]);
            return null;
        }
        
        $departmentId = $employee->department_id;
        if (!$departmentId) {
            \Log::warning('Cannot create imprest request: Employee has no department', [
                'employee_id' => $employee->id,
                'transaction_id' => $transaction->id
            ]);
            return null;
        }
        
        try {
            DB::beginTransaction();
            
            // Calculate total amount from line items
            $totalAmount = 0;
            $items = [];
            
            if ($transaction->items && $transaction->items->count() > 0) {
                foreach ($transaction->items as $item) {
                    $totalAmount += $item->amount;
                    $items[] = [
                        'chart_account_id' => $item->chart_account_id,
                        'amount' => $item->amount,
                        'notes' => $item->description ?? $transaction->description,
                    ];
                }
            } else {
                // Legacy: Use expense category
                if ($transaction->expenseCategory && $transaction->expenseCategory->expense_account_id) {
                    $totalAmount = $transaction->amount;
                    $items[] = [
                        'chart_account_id' => $transaction->expenseCategory->expense_account_id,
                        'amount' => $transaction->amount,
                        'notes' => $transaction->description,
                    ];
                } else {
                    \Log::warning('Cannot create imprest request: No line items or expense category', [
                        'transaction_id' => $transaction->id
                    ]);
                    DB::rollBack();
                    return null;
                }
            }
            
            // Create imprest request
            $imprestRequest = ImprestRequest::create([
                'request_number' => ImprestRequest::generateRequestNumber(),
                'employee_id' => $user->id,
                'department_id' => $departmentId,
                'company_id' => $unit->company_id,
                'branch_id' => $unit->branch_id ?? $user->branch_id ?? session('branch_id'),
                'purpose' => 'Petty Cash Expense: ' . $transaction->description,
                'amount_requested' => $totalAmount,
                'date_required' => $transaction->transaction_date,
                'description' => 'Petty Cash Transaction: ' . $transaction->transaction_number . ' - ' . $transaction->description,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);
            
            // Create imprest items
            foreach ($items as $item) {
                ImprestItem::create([
                    'imprest_request_id' => $imprestRequest->id,
                    'chart_account_id' => $item['chart_account_id'],
                    'amount' => $item['amount'],
                    'notes' => $item['notes'],
                    'company_id' => $unit->company_id,
                    'branch_id' => $unit->branch_id ?? $user->branch_id ?? session('branch_id'),
                    'created_by' => $user->id,
                ]);
            }
            
            // Link register entry to imprest request
            $registerEntry = PettyCashRegister::where('petty_cash_transaction_id', $transaction->id)->first();
            if ($registerEntry) {
                $registerEntry->update(['imprest_request_id' => $imprestRequest->id]);
            }
            
            // Check if multi-level approval is required
            if ($imprestRequest->requiresApproval()) {
                $imprestRequest->createApprovalRequests();
            }
            
            DB::commit();
            
            \Log::info('Imprest request created from petty cash transaction', [
                'transaction_id' => $transaction->id,
                'imprest_request_id' => $imprestRequest->id,
                'imprest_request_number' => $imprestRequest->request_number
            ]);
            
            return $imprestRequest;
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create imprest request from petty cash transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Check if transaction should create imprest request (Sub-Imprest Mode)
     */
    public static function shouldCreateImprestRequest(PettyCashTransaction $transaction): bool
    {
        $unit = $transaction->pettyCashUnit;
        $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($unit->company_id);
        
        // Only create imprest request in Sub-Imprest mode
        if (!$settings->isSubImprestMode()) {
            return false;
        }
        
        // In Sub-Imprest mode, create imprest request when transaction is submitted (User → Supervisor → Custodian)
        // This creates the PCV automatically
        if (!in_array($transaction->status, ['submitted', 'approved', 'posted'])) {
            return false;
        }
        
        // Don't create if already linked to an imprest request
        $registerEntry = PettyCashRegister::where('petty_cash_transaction_id', $transaction->id)
            ->whereNotNull('imprest_request_id')
            ->first();
        
        if ($registerEntry) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Create an imprest request from a replenishment (Sub-Imprest Mode)
     */
    public static function createImprestRequestFromReplenishment(\App\Models\PettyCash\PettyCashReplenishment $replenishment): ?ImprestRequest
    {
        $unit = $replenishment->pettyCashUnit;
        $user = $replenishment->requestedBy;
        
        // Get employee record for the custodian
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            \Log::warning('Cannot create imprest request from replenishment: User has no employee record', [
                'user_id' => $user->id,
                'replenishment_id' => $replenishment->id
            ]);
            return null;
        }
        
        $departmentId = $employee->department_id;
        if (!$departmentId) {
            \Log::warning('Cannot create imprest request from replenishment: Employee has no department', [
                'employee_id' => $employee->id,
                'replenishment_id' => $replenishment->id
            ]);
            return null;
        }
        
        try {
            DB::beginTransaction();
            
            // Create imprest request for replenishment
            $imprestRequest = ImprestRequest::create([
                'request_number' => ImprestRequest::generateRequestNumber(),
                'employee_id' => $user->id,
                'department_id' => $departmentId,
                'company_id' => $unit->company_id,
                'branch_id' => $unit->branch_id ?? $user->branch_id ?? session('branch_id'),
                'purpose' => 'Petty Cash Replenishment: ' . $replenishment->reason,
                'amount_requested' => $replenishment->approved_amount ?? $replenishment->requested_amount,
                'date_required' => $replenishment->request_date,
                'description' => 'Petty Cash Replenishment Request: ' . $replenishment->replenishment_number . ' - ' . $replenishment->reason,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);
            
            // Link replenishment to imprest request
            $replenishment->update(['imprest_request_id' => $imprestRequest->id]);
            
            // Check if multi-level approval is required
            if ($imprestRequest->requiresApproval()) {
                $imprestRequest->createApprovalRequests();
            }
            
            DB::commit();
            
            \Log::info('Imprest request created from petty cash replenishment', [
                'replenishment_id' => $replenishment->id,
                'imprest_request_id' => $imprestRequest->id,
                'imprest_request_number' => $imprestRequest->request_number
            ]);
            
            return $imprestRequest;
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create imprest request from petty cash replenishment', [
                'replenishment_id' => $replenishment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Get imprest request linked to a transaction
     */
    public static function getLinkedImprestRequest(PettyCashTransaction $transaction): ?ImprestRequest
    {
        $registerEntry = PettyCashRegister::where('petty_cash_transaction_id', $transaction->id)
            ->whereNotNull('imprest_request_id')
            ->with('imprestRequest')
            ->first();
        
        return $registerEntry->imprestRequest ?? null;
    }
}


