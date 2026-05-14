<?php

namespace App\Notifications;

use App\Models\BankReconciliation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankReconciliationRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bankReconciliation;
    protected $rejector;
    protected $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(BankReconciliation $bankReconciliation, $rejector, string $reason)
    {
        $this->bankReconciliation = $bankReconciliation;
        $this->rejector = $rejector;
        $this->reason = $reason;
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
            ->subject('Bank Reconciliation Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Unfortunately, your bank reconciliation has been rejected.')
            ->line('**Bank Account:** ' . $this->bankReconciliation->bankAccount->name)
            ->line('**Reconciliation Date:** ' . $this->bankReconciliation->formatted_reconciliation_date)
            ->line('**Period:** ' . $this->bankReconciliation->formatted_start_date . ' to ' . $this->bankReconciliation->formatted_end_date)
            ->line('**Rejected By:** ' . ($this->rejector->name ?? 'N/A'))
            ->line('**Rejected At:** ' . ($this->bankReconciliation->rejected_at ? $this->bankReconciliation->rejected_at->format('M d, Y H:i') : 'N/A'))
            ->line('**Rejection Reason:**')
            ->line($this->reason)
            ->action('View Reconciliation', route('accounting.bank-reconciliation.show', $this->bankReconciliation))
            ->line('Please review the rejection reason, make necessary corrections, and resubmit the reconciliation.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $senderName = config('services.sms.senderid', 'SAFCO');
        $shortReason = mb_substr($this->reason, 0, 100);
        if (mb_strlen($this->reason) > 100) {
            $shortReason .= '...';
        }
        
        return "Hello {$notifiable->name}, Bank reconciliation for {$this->bankReconciliation->bankAccount->name} has been rejected. Reason: {$shortReason} Please review and resubmit. - {$senderName}";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bank_reconciliation_rejected',
            'bank_reconciliation_id' => $this->bankReconciliation->id,
            'bank_account_name' => $this->bankReconciliation->bankAccount->name,
            'reconciliation_date' => $this->bankReconciliation->reconciliation_date?->toDateString(),
            'rejector_id' => $this->rejector->id ?? null,
            'rejector_name' => $this->rejector->name ?? 'N/A',
            'rejection_reason' => $this->reason,
            'rejected_at' => $this->bankReconciliation->rejected_at?->toDateTimeString(),
            'message' => "Bank reconciliation for {$this->bankReconciliation->bankAccount->name} has been rejected. Reason: {$this->reason}",
            'url' => route('accounting.bank-reconciliation.show', $this->bankReconciliation),
        ];
    }
}
