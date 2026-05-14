<?php

namespace App\Notifications;

use App\Models\BankReconciliation;
use App\Models\ApprovalLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankReconciliationApprovalRequired extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bankReconciliation;
    protected $approvalLevel;

    /**
     * Create a new notification instance.
     */
    public function __construct(BankReconciliation $bankReconciliation, ApprovalLevel $approvalLevel)
    {
        $this->bankReconciliation = $bankReconciliation;
        $this->approvalLevel = $approvalLevel;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];
        
        // Add SMS if user has phone number
        if ($notifiable->phone) {
            $channels[] = \App\Channels\SmsChannel::class;
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bank Reconciliation Approval Required - ' . $this->approvalLevel->level_name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A bank reconciliation requires your approval at the ' . $this->approvalLevel->level_name . ' level.')
            ->line('**Bank Account:** ' . $this->bankReconciliation->bankAccount->name)
            ->line('**Reconciliation Date:** ' . $this->bankReconciliation->formatted_reconciliation_date)
            ->line('**Period:** ' . $this->bankReconciliation->formatted_start_date . ' to ' . $this->bankReconciliation->formatted_end_date)
            ->line('**Approval Level:** ' . $this->approvalLevel->level_name)
            ->line('**Difference:** TZS ' . number_format($this->bankReconciliation->difference, 2))
            ->line('**Submitted By:** ' . ($this->bankReconciliation->submittedBy->name ?? 'N/A'))
            ->action('Review and Approve', route('accounting.bank-reconciliation.show', $this->bankReconciliation))
            ->line('Please review the reconciliation and take appropriate action.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $senderName = config('services.sms.senderid', 'SAFCO');
        $date = $this->bankReconciliation->reconciliation_date ? $this->bankReconciliation->reconciliation_date->format('M d, Y') : 'N/A';
        
        return "Hello {$notifiable->name}, Bank reconciliation for {$this->bankReconciliation->bankAccount->name} ({$date}) requires your approval at {$this->approvalLevel->level_name} level. Please review and take action. - {$senderName}";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bank_reconciliation_approval_required',
            'bank_reconciliation_id' => $this->bankReconciliation->id,
            'bank_account_name' => $this->bankReconciliation->bankAccount->name,
            'reconciliation_date' => $this->bankReconciliation->reconciliation_date?->toDateString(),
            'approval_level_id' => $this->approvalLevel->id,
            'approval_level_name' => $this->approvalLevel->level_name,
            'message' => "Bank reconciliation for {$this->bankReconciliation->bankAccount->name} requires your approval at {$this->approvalLevel->level_name} level.",
            'url' => route('accounting.bank-reconciliation.show', $this->bankReconciliation),
        ];
    }
}
