<?php

namespace App\Services\Purchase;

use App\Models\Purchase\PurchaseOrder;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderService
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {
    }

    /**
     * Submit Purchase Order for approval
     * Uses ApprovalService for multi-level approval workflow
     */
    public function submitForApproval(PurchaseOrder $order, int $userId): PurchaseOrder
    {
        DB::transaction(function () use ($order, $userId) {
            // Status guard
            if (!in_array($order->status, ['draft', 'rejected'])) {
                throw new \RuntimeException('Purchase Order must be in draft or rejected status to submit');
            }

            // Validate order has items
            if ($order->items()->count() === 0) {
                throw new \RuntimeException('Purchase Order must have at least one item');
            }

            // Check if approval is required based on value thresholds
            $requiresApproval = $this->requiresApproval($order);
            
            if ($requiresApproval) {
                // Use ApprovalService for multi-level approval
                $this->approvalService->submitForApproval($order, $userId);
            } else {
                // No approval required - auto-approve
                $order->update([
                    'status' => 'approved',
                    'approved_by' => $userId,
                    'approved_at' => now(),
                    'submitted_by' => $userId,
                    'submitted_at' => now(),
                ]);
            }
        });

        return $order->fresh(['items']);
    }

    /**
     * Check if PO requires approval based on value thresholds
     */
    public function requiresApproval(PurchaseOrder $order): bool
    {
        // Get approval threshold from system settings
        $approvalThreshold = \App\Models\SystemSetting::getValue('po_approval_threshold', 0);
        
        if ($approvalThreshold <= 0) {
            return false; // No threshold set, approval not required
        }

        return $order->total_amount >= $approvalThreshold;
    }

    /**
     * Approve Purchase Order at current level
     */
    public function approve(PurchaseOrder $order, int $approvalLevelId, int $approverId, ?string $comments = null): PurchaseOrder
    {
        $this->approvalService->approve($order, $approvalLevelId, $approverId, $comments);
        return $order->fresh(['items']);
    }

    /**
     * Reject Purchase Order at current level
     */
    public function reject(PurchaseOrder $order, int $approvalLevelId, int $rejectorId, string $reason): PurchaseOrder
    {
        $this->approvalService->reject($order, $approvalLevelId, $rejectorId, $reason);
        return $order->fresh(['items']);
    }
}

