<?php

namespace App\Notifications;

use App\Models\BankReconciliation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankReconciliationSubmittedForApproval extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bankReconciliation;
    protected $submitter;

    /**
     * Create a new notification instance.
     */
    public function __construct(BankReconciliation $bankReconciliation, $submitter)
    {
        $this->bankReconciliation = $bankReconciliation;
        $this->submitter = $submitter;
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
            ->subject('Bank Reconciliation Submitted for Approval')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A bank reconciliation has been submitted for your approval.')
            ->line('**Bank Account:** ' . $this->bankReconciliation->bankAccount->name)
            ->line('**Reconciliation Date:** ' . $this->bankReconciliation->formatted_reconciliation_date)
            ->line('**Period:** ' . $this->bankReconciliation->formatted_start_date . ' to ' . $this->bankReconciliation->formatted_end_date)
            ->line('**Bank Statement Balance:** TZS ' . number_format($this->bankReconciliation->bank_statement_balance, 2))
            ->line('**Book Balance:** TZS ' . number_format($this->bankReconciliation->book_balance, 2))
            ->line('**Difference:** TZS ' . number_format($this->bankReconciliation->difference, 2))
            ->line('**Submitted By:** ' . ($this->submitter->name ?? 'N/A'))
            ->line('**Submitted At:** ' . ($this->bankReconciliation->submitted_at ? $this->bankReconciliation->submitted_at->format('M d, Y H:i') : 'N/A'))
            ->action('Review Reconciliation', route('accounting.bank-reconciliation.show', $this->bankReconciliation))
            ->line('Please review and approve or reject this reconciliation as appropriate.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $senderName = config('services.sms.senderid', 'SAFCO');
        $date = $this->bankReconciliation->reconciliation_date ? $this->bankReconciliation->reconciliation_date->format('M d, Y') : 'N/A';
        
        return "Hello {$notifiable->name}, Bank reconciliation for {$this->bankReconciliation->bankAccount->name} ({$date}) has been submitted for approval. Please review and take action. - {$senderName}";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bank_reconciliation_submitted_for_approval',
            'bank_reconciliation_id' => $this->bankReconciliation->id,
            'bank_account_name' => $this->bankReconciliation->bankAccount->name,
            'reconciliation_date' => $this->bankReconciliation->reconciliation_date?->toDateString(),
            'submitter_id' => $this->submitter->id ?? null,
            'submitter_name' => $this->submitter->name ?? 'N/A',
            'submitted_at' => $this->bankReconciliation->submitted_at?->toDateTimeString(),
            'message' => "Bank reconciliation for {$this->bankReconciliation->bankAccount->name} has been submitted for approval.",
            'url' => route('accounting.bank-reconciliation.show', $this->bankReconciliation),
        ];
    }
}
