<?php

namespace App\Jobs\Purchase;

use App\Models\Purchase\PurchaseRequisition;
use App\Models\Purchase\PurchaseOrder;
use App\Models\ApprovalHistory;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ApprovalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * SLA threshold in hours (send reminder if pending for more than this)
     */
    protected int $slaThresholdHours = 48;

    /**
     * Execute the job.
     */
    public function handle(ApprovalService $approvalService): void
    {
        $this->processPurchaseRequisitions($approvalService);
        $this->processPurchaseOrders($approvalService);
    }

    /**
     * Process pending purchase requisitions
     */
    protected function processPurchaseRequisitions(ApprovalService $approvalService): void
    {
        $threshold = Carbon::now()->subHours($this->slaThresholdHours);

        $pendingRequisitions = PurchaseRequisition::whereIn('status', ['submitted', 'pending_approval', 'in_review'])
            ->where('submitted_at', '<=', $threshold)
            ->whereNotNull('current_approval_level')
            ->with(['requestor', 'department'])
            ->get();

        Log::info('Approval Reminder: Processing Purchase Requisitions', [
            'found_requisitions' => $pendingRequisitions->count(),
            'threshold_hours' => $this->slaThresholdHours,
        ]);

        foreach ($pendingRequisitions as $requisition) {
            $currentLevel = $approvalService->getCurrentApprovalLevel($requisition);
            if (!$currentLevel) {
                continue;
            }

            $approvers = $approvalService->getCurrentApprovers($requisition, $currentLevel);
            
            foreach ($approvers as $approver) {
                // Check if we already sent a reminder recently (within 12 hours)
                $recentReminder = \App\Models\ApprovalHistory::where('approvable_type', PurchaseRequisition::class)
                    ->where('approvable_id', $requisition->id)
                    ->where('approval_level_id', $currentLevel->id)
                    ->where('action', 'reminder_sent')
                    ->where('approver_id', $approver->id)
                    ->where('created_at', '>=', Carbon::now()->subHours(12))
                    ->exists();

                if (!$recentReminder) {
                    $this->sendReminder($requisition, $approver, $currentLevel, 'purchase_requisition');
                    
                    // Log reminder in approval history
                    \App\Models\ApprovalHistory::create([
                        'approvable_type' => PurchaseRequisition::class,
                        'approvable_id' => $requisition->id,
                        'approval_level_id' => $currentLevel->id,
                        'action' => 'reminder_sent',
                        'approver_id' => $approver->id,
                        'comments' => 'Automated reminder sent - pending for more than ' . $this->slaThresholdHours . ' hours',
                    ]);

                    Log::info('Approval reminder sent for Purchase Requisition', [
                        'requisition_id' => $requisition->id,
                        'pr_no' => $requisition->pr_no,
                        'approver_id' => $approver->id,
                        'approver_name' => $approver->name,
                        'level' => $currentLevel->level,
                    ]);
                }
            }

            // Escalate if pending for more than 72 hours
            if ($requisition->submitted_at <= Carbon::now()->subHours(72)) {
                $this->escalateApproval($requisition, $approvalService, 'purchase_requisition');
            }
        }
    }

    /**
     * Process pending purchase orders
     */
    protected function processPurchaseOrders(ApprovalService $approvalService): void
    {
        $threshold = Carbon::now()->subHours($this->slaThresholdHours);

        $pendingOrders = PurchaseOrder::where('status', 'pending_approval')
            ->where('submitted_at', '<=', $threshold)
            ->whereNotNull('current_approval_level')
            ->with(['supplier', 'requisition'])
            ->get();

        Log::info('Approval Reminder: Processing Purchase Orders', [
            'found_orders' => $pendingOrders->count(),
            'threshold_hours' => $this->slaThresholdHours,
        ]);

        foreach ($pendingOrders as $order) {
            $currentLevel = $approvalService->getCurrentApprovalLevel($order);
            if (!$currentLevel) {
                continue;
            }

            $approvers = $approvalService->getCurrentApprovers($order, $currentLevel);
            
            foreach ($approvers as $approver) {
                // Check if we already sent a reminder recently (within 12 hours)
                $recentReminder = \App\Models\ApprovalHistory::where('approvable_type', PurchaseOrder::class)
                    ->where('approvable_id', $order->id)
                    ->where('approval_level_id', $currentLevel->id)
                    ->where('action', 'reminder_sent')
                    ->where('approver_id', $approver->id)
                    ->where('created_at', '>=', Carbon::now()->subHours(12))
                    ->exists();

                if (!$recentReminder) {
                    $this->sendReminder($order, $approver, $currentLevel, 'purchase_order');
                    
                    // Log reminder in approval history
                    \App\Models\ApprovalHistory::create([
                        'approvable_type' => PurchaseOrder::class,
                        'approvable_id' => $order->id,
                        'approval_level_id' => $currentLevel->id,
                        'action' => 'reminder_sent',
                        'approver_id' => $approver->id,
                        'comments' => 'Automated reminder sent - pending for more than ' . $this->slaThresholdHours . ' hours',
                    ]);

                    Log::info('Approval reminder sent for Purchase Order', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'approver_id' => $approver->id,
                        'approver_name' => $approver->name,
                        'level' => $currentLevel->level,
                    ]);
                }
            }

            // Escalate if pending for more than 72 hours
            if ($order->submitted_at && $order->submitted_at <= Carbon::now()->subHours(72)) {
                $this->escalateApproval($order, $approvalService, 'purchase_order');
            }
        }
    }

    /**
     * Send reminder notification to approver
     */
    protected function sendReminder($model, $approver, $level, string $type): void
    {
        if (!$approver) {
            return;
        }

        try {
            // Create a simple notification
            // You can create a dedicated notification class if needed
            $identifier = $model->pr_no ?? $model->order_number ?? $model->id;
            $levelName = $level ? $level->level_name : 'Approval';
            
            // Send email notification (implement your notification class)
            // Notification::send($approver, new PurchaseApprovalReminder($model, $level));
            
            // Send SMS if phone exists
            if ($approver->phone) {
                $this->sendSmsReminder($approver, $model, $levelName, $type);
            }

            Log::info('Reminder notification sent', [
                'type' => $type,
                'model_id' => $model->id,
                'approver_id' => $approver->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send approval reminder', [
                'type' => $type,
                'model_id' => $model->id,
                'approver_id' => $approver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS reminder
     */
    protected function sendSmsReminder($approver, $model, string $levelName, string $type): void
    {
        try {
            $phone = $approver->phone;
            if (!$phone) {
                return;
            }

            // Format phone number
            $formattedPhone = preg_replace('/[^0-9+]/', '', $phone);
            $formattedPhone = ltrim($formattedPhone, '+');
            if (str_starts_with($formattedPhone, '0')) {
                $formattedPhone = '255' . substr($formattedPhone, 1);
            }
            if (!str_starts_with($formattedPhone, '255')) {
                $formattedPhone = '255' . $formattedPhone;
            }

            $identifier = $model->pr_no ?? $model->order_number ?? 'N/A';
            $senderName = config('services.sms.senderid', 'SAFCO');
            $message = "Hello {$approver->name}, {$type} {$identifier} requires your approval at {$levelName} level. Pending for more than " . $this->slaThresholdHours . " hours. Please review. - {$senderName}";

            if (class_exists(\App\Helpers\SmsHelper::class)) {
                \App\Helpers\SmsHelper::send($formattedPhone, $message);
            }

            Log::info('SMS reminder sent', [
                'approver_id' => $approver->id,
                'phone' => $formattedPhone,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send SMS reminder', [
                'approver_id' => $approver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Escalate approval to next level or manager
     */
    protected function escalateApproval($model, ApprovalService $approvalService, string $type): void
    {
        try {
            $currentLevel = $approvalService->getCurrentApprovalLevel($model);
            if (!$currentLevel) {
                return;
            }

            // Log escalation
            Log::warning('Approval escalated due to delay', [
                'type' => $type,
                'model_id' => $model->id,
                'identifier' => $model->pr_no ?? $model->order_number ?? 'N/A',
                'current_level' => $currentLevel->level,
                'pending_since' => $model->submitted_at?->format('Y-m-d H:i:s'),
            ]);

            // Create escalation history
            \App\Models\ApprovalHistory::create([
                'approvable_type' => get_class($model),
                'approvable_id' => $model->id,
                'approval_level_id' => $currentLevel->id,
                'action' => 'escalated',
                'approver_id' => null,
                'comments' => 'Automated escalation - pending for more than 72 hours',
            ]);

            // Notify management (implement based on your notification system)
            // $this->notifyManagement($model, $currentLevel);
        } catch (\Exception $e) {
            Log::error('Failed to escalate approval', [
                'type' => $type,
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

