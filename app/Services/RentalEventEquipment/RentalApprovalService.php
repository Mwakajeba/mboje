<?php

namespace App\Services\RentalEventEquipment;

use App\Models\RentalEventEquipment\RentalApprovalSettings;
use App\Models\RentalEventEquipment\RentalApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RentalApprovalService
{
    /**
     * Initialize approval workflow for a rental document
     */
    public function initializeApprovalWorkflow(Model $document): void
    {
        $settings = RentalApprovalSettings::getSettingsForCompany(
            $document->company_id,
            $document->branch_id
        );

        // If no settings or approval not required, auto-approve so document is immediately available
        if (!$settings || !$settings->approval_required) {
            $this->autoApprove($document);
            return;
        }

        // Get amount for threshold checking
        $amount = $this->getDocumentAmount($document);
        
        // Get required approval levels
        $requiredApprovals = $settings->getRequiredApprovalsForAmount($amount);

        if (empty($requiredApprovals)) {
            // No approval required for this amount, auto-approve
            $this->autoApprove($document);
            return;
        }

        // Create approval records for each level
        foreach ($requiredApprovals as $approvalConfig) {
            foreach ($approvalConfig['approvers'] as $approverId) {
                RentalApproval::create([
                    'approvable_type' => get_class($document),
                    'approvable_id' => $document->id,
                    'approval_level' => $approvalConfig['level'],
                    'approver_id' => $approverId,
                    'status' => RentalApproval::STATUS_PENDING,
                ]);
            }
        }

        // Update document status to pending_approval
        $document->update(['status' => 'pending_approval']);
    }

    /**
     * Reinitialize approval workflow (e.g. after editing a rejected document to reapply)
     */
    public function reinitializeApprovalWorkflow(Model $document): void
    {
        try {
            RentalApproval::where('approvable_type', get_class($document))
                ->where('approvable_id', $document->id)
                ->delete();
        } catch (\Exception $e) {
            Log::warning('Could not clear approval records for reinitialize', [
                'error' => $e->getMessage(),
                'document_type' => get_class($document),
                'document_id' => $document->id
            ]);
        }
        $this->initializeApprovalWorkflow($document);
    }

    /**
     * Auto-approve a document (no approval required)
     */
    protected function autoApprove(Model $document): void
    {
        $document->update(['status' => 'approved']);
    }

    /**
     * Get the amount from a document for threshold checking
     */
    protected function getDocumentAmount(Model $document): float
    {
        // Try common amount fields
        if (isset($document->total_amount)) {
            return (float) $document->total_amount;
        }
        if (isset($document->amount)) {
            return (float) $document->amount;
        }
        if (isset($document->subtotal)) {
            return (float) $document->subtotal;
        }
        return 0;
    }

    /**
     * Approve a document at a specific level
     */
    public function approveDocument(Model $document, int $level, int $approverId, ?string $comments = null): bool
    {
        DB::beginTransaction();
        try {
            // Mark this approval as approved
            $approval = null;
            try {
                $approval = RentalApproval::where('approvable_type', get_class($document))
                    ->where('approvable_id', $document->id)
                    ->where('approval_level', $level)
                    ->where('approver_id', $approverId)
                    ->where('status', RentalApproval::STATUS_PENDING)
                    ->first();
            } catch (\Exception $e) {
                // Table doesn't exist - this is okay for super admin approvals
                Log::info('Approval table not available, proceeding with direct approval', [
                    'document_type' => get_class($document),
                    'document_id' => $document->id
                ]);
            }

            // If no approval record exists (e.g., super admin approving), create one
            if (!$approval) {
                try {
                    $approval = RentalApproval::create([
                        'approvable_type' => get_class($document),
                        'approvable_id' => $document->id,
                        'approval_level' => $level,
                        'approver_id' => $approverId,
                        'status' => RentalApproval::STATUS_APPROVED,
                        'comments' => $comments,
                        'approved_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    // If table doesn't exist or creation fails, log and continue
                    Log::warning('Could not create approval record', [
                        'error' => $e->getMessage(),
                        'document_type' => get_class($document),
                        'document_id' => $document->id
                    ]);
                    // Continue with status update even if approval record creation fails
                }
            } else {
                $approval->approve($comments);
            }

            // Check if there are more levels
            $settings = RentalApprovalSettings::getSettingsForCompany(
                $document->company_id,
                $document->branch_id
            );

            // If no approval record was found and no settings/approval required, approve directly (super admin case)
            if (!$approval && (!$settings || !$settings->approval_required)) {
                // Super admin approving without approval workflow - approve directly
                $this->setApprovedStatus($document);
            } else {
                // Check if all approvals for this level are complete
                $levelComplete = $this->isLevelComplete($document, $level);
                
                if ($levelComplete) {
                    if ($settings && $settings->approval_required) {
                        $amount = $this->getDocumentAmount($document);
                        $requiredApprovals = $settings->getRequiredApprovalsForAmount($amount);
                        
                        if (!empty($requiredApprovals)) {
                            $maxLevel = max(array_column($requiredApprovals, 'level'));

                            if ($level < $maxLevel) {
                                // More levels to go, keep as pending_approval
                                // Status remains pending_approval
                            } else {
                                // All levels approved - set appropriate status based on document type
                                $this->setApprovedStatus($document);
                            }
                        } else {
                            // No required approvals, approve
                            $this->setApprovedStatus($document);
                        }
                    } else {
                        // No approval required or no settings, approve directly
                        $this->setApprovedStatus($document);
                    }
                } else {
                    // If super admin approved but there are still pending approvals at this level,
                    // check if we should auto-approve all remaining at this level for super admin
                    // For now, just keep status as pending_approval
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve rental document', [
                'document_type' => get_class($document),
                'document_id' => $document->id,
                'level' => $level,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reject a document
     */
    public function rejectDocument(Model $document, int $level, int $approverId, ?string $rejectionReason = null): bool
    {
        DB::beginTransaction();
        try {
            $approval = RentalApproval::where('approvable_type', get_class($document))
                ->where('approvable_id', $document->id)
                ->where('approval_level', $level)
                ->where('approver_id', $approverId)
                ->where('status', RentalApproval::STATUS_PENDING)
                ->first();

            // If no approval record exists (e.g., super admin rejecting), create one
            if (!$approval) {
                try {
                    $approval = RentalApproval::create([
                        'approvable_type' => get_class($document),
                        'approvable_id' => $document->id,
                        'approval_level' => $level,
                        'approver_id' => $approverId,
                        'status' => RentalApproval::STATUS_REJECTED,
                        'rejection_reason' => $rejectionReason,
                        'rejected_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    // If table doesn't exist or creation fails, log and continue
                    Log::warning('Could not create approval record', [
                        'error' => $e->getMessage(),
                        'document_type' => get_class($document),
                        'document_id' => $document->id
                    ]);
                    // Continue with status update even if approval record creation fails
                }
            } else {
                $approval->reject($rejectionReason);
            }

            // Reject all pending approvals for this document
            try {
                RentalApproval::where('approvable_type', get_class($document))
                    ->where('approvable_id', $document->id)
                    ->where('status', RentalApproval::STATUS_PENDING)
                    ->update([
                        'status' => RentalApproval::STATUS_REJECTED,
                        'rejected_at' => now(),
                    ]);
            } catch (\Exception $e) {
                // If table doesn't exist, just continue with status update
                Log::warning('Could not update pending approvals', [
                    'error' => $e->getMessage(),
                    'document_type' => get_class($document),
                    'document_id' => $document->id
                ]);
            }

            // Update document status to rejected
            $document->update(['status' => 'rejected']);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject rental document', [
                'document_type' => get_class($document),
                'document_id' => $document->id,
                'level' => $level,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if all approvals for a level are complete
     */
    protected function isLevelComplete(Model $document, int $level): bool
    {
        try {
            $pendingCount = RentalApproval::where('approvable_type', get_class($document))
                ->where('approvable_id', $document->id)
                ->where('approval_level', $level)
                ->where('status', RentalApproval::STATUS_PENDING)
                ->count();

            return $pendingCount === 0;
        } catch (\Exception $e) {
            // If table doesn't exist, consider level complete (for super admin approvals)
            Log::warning('Could not check level completion', [
                'error' => $e->getMessage(),
                'document_type' => get_class($document),
                'document_id' => $document->id,
                'level' => $level
            ]);
            return true; // Assume complete if we can't check
        }
    }

    /**
     * Check if user can approve at a specific level
     */
    public function canUserApprove(Model $document, int $userId, int $level): bool
    {
        $settings = RentalApprovalSettings::getSettingsForCompany(
            $document->company_id,
            $document->branch_id
        );

        if (!$settings) {
            return false;
        }

        return $settings->canUserApproveAtLevel($userId, $level);
    }

    /**
     * Set the appropriate approved status based on document type
     */
    protected function setApprovedStatus(Model $document): void
    {
        $documentClass = get_class($document);
        
        // Different document types have different approved statuses
        $approvedStatus = match($documentClass) {
            \App\Models\RentalEventEquipment\RentalContract::class => 'active',
            \App\Models\RentalEventEquipment\RentalQuotation::class => 'approved',
            \App\Models\RentalEventEquipment\CustomerDeposit::class => 'confirmed',
            \App\Models\RentalEventEquipment\RentalDispatch::class => 'dispatched',
            \App\Models\RentalEventEquipment\RentalReturn::class => 'completed',
            \App\Models\RentalEventEquipment\RentalDamageCharge::class => 'confirmed',
            \App\Models\RentalEventEquipment\RentalInvoice::class => 'sent',
            default => 'approved'
        };
        
        $document->update(['status' => $approvedStatus]);
        
        // Post GL transactions for deposits when approved
        if ($documentClass === \App\Models\RentalEventEquipment\CustomerDeposit::class && $approvedStatus === 'confirmed') {
            $this->postDepositGlTransactions($document);
        }
    }
    
    /**
     * Post GL transactions for approved customer deposit
     */
    protected function postDepositGlTransactions(\App\Models\RentalEventEquipment\CustomerDeposit $deposit): void
    {
        try {
            $companyId = $deposit->company_id;
            $branchId = $deposit->branch_id;
            
            $settings = \App\Models\RentalEventEquipment\AccountingSetting::where('company_id', $companyId)
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->first();

            if (!$settings || !$settings->deposits_account_id) {
                Log::warning('No accounting settings found for deposit GL posting', [
                    'deposit_id' => $deposit->id,
                    'company_id' => $companyId
                ]);
                return;
            }

            // Determine the debit account based on payment method
            $debitAccountId = null;
            if (in_array($deposit->payment_method, ['bank_transfer', 'cheque']) && $deposit->bank_account_id) {
                $bankAccount = \App\Models\BankAccount::find($deposit->bank_account_id);
                $debitAccountId = $bankAccount->chart_account_id ?? null;
            } elseif ($deposit->payment_method === 'cash') {
                $cashAccountId = \App\Models\SystemSetting::getValue('default_cash_account', null);
                $debitAccountId = $cashAccountId;
            }

            if (!$debitAccountId) {
                Log::warning('No debit account found for deposit GL posting', [
                    'deposit_id' => $deposit->id,
                    'payment_method' => $deposit->payment_method
                ]);
                return;
            }

            // Create journal entry
            $journal = \App\Models\Journal::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'date' => $deposit->deposit_date,
                'reference' => $deposit->deposit_number,
                'reference_type' => 'customer_deposit',
                'description' => "Customer Deposit: {$deposit->deposit_number}",
                'user_id' => $deposit->created_by,
            ]);

            // Debit: Bank/Cash Account
            \App\Models\JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $debitAccountId,
                'amount' => $deposit->amount,
                'nature' => 'debit',
                'description' => "Customer Deposit: {$deposit->deposit_number}",
            ]);

            // Credit: Customer Deposits Account
            \App\Models\JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $settings->deposits_account_id,
                'amount' => $deposit->amount,
                'nature' => 'credit',
                'description' => "Customer Deposit: {$deposit->deposit_number}",
            ]);

            // Post journal to create GL transactions
            $journal->post();
            
            Log::info('GL transactions posted for approved deposit', [
                'deposit_id' => $deposit->id,
                'deposit_number' => $deposit->deposit_number,
                'journal_id' => $journal->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to post GL transactions for deposit', [
                'deposit_id' => $deposit->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw - approval should still succeed even if GL posting fails
        }
    }

    /**
     * Get pending approvals for a user
     */
    public function getPendingApprovalsForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return RentalApproval::where('approver_id', $userId)
            ->where('status', RentalApproval::STATUS_PENDING)
            ->with('approvable')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
