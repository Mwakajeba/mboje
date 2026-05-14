<?php

namespace App\Notifications;

use App\Models\BankReconciliation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankReconciliationApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bankReconciliation;
    protected $approver;
    protected $isFinalApproval;

    /**
     * Create a new notification instance.
     */
    public function __construct(BankReconciliation $bankReconciliation, $approver, bool $isFinalApproval = false)
    {
        $this->bankReconciliation = $bankReconciliation;
        $this->approver = $approver;
        $this->isFinalApproval = $isFinalApproval;
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
        $message = (new MailMessage)
            ->subject($this->isFinalApproval ? 'Bank Reconciliation Fully Approved' : 'Bank Reconciliation Approved at Current Level')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->isFinalApproval 
                ? 'The bank reconciliation has been fully approved and is now ready for completion.'
                : 'The bank reconciliation has been approved at the current level and moved to the next approval level.')
            ->line('**Bank Account:** ' . $this->bankReconciliation->bankAccount->name)
            ->line('**Reconciliation Date:** ' . $this->bankReconciliation->formatted_reconciliation_date)
            ->line('**Period:** ' . $this->bankReconciliation->formatted_start_date . ' to ' . $this->bankReconciliation->formatted_end_date)
            ->line('**Approved By:** ' . ($this->approver->name ?? 'N/A'))
            ->line('**Approved At:** ' . ($this->bankReconciliation->approved_at ? $this->bankReconciliation->approved_at->format('M d, Y H:i') : 'N/A'))
            ->action('View Reconciliation', route('accounting.bank-reconciliation.show', $this->bankReconciliation));

        if ($this->isFinalApproval) {
            $message->line('The reconciliation is now approved and can be marked as completed.');
        }

        return $message;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $senderName = config('services.sms.senderid', 'SAFCO');
        
        if ($this->isFinalApproval) {
            return "Hello {$notifiable->name}, Bank reconciliation for {$this->bankReconciliation->bankAccount->name} has been fully approved and is ready for completion. - {$senderName}";
        } else {
            return "Hello {$notifiable->name}, Bank reconciliation for {$this->bankReconciliation->bankAccount->name} has been approved at current level and moved to next approval level. - {$senderName}";
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bank_reconciliation_approved',
            'bank_reconciliation_id' => $this->bankReconciliation->id,
            'bank_account_name' => $this->bankReconciliation->bankAccount->name,
            'reconciliation_date' => $this->bankReconciliation->reconciliation_date?->toDateString(),
            'approver_id' => $this->approver->id ?? null,
            'approver_name' => $this->approver->name ?? 'N/A',
            'is_final_approval' => $this->isFinalApproval,
            'approved_at' => $this->bankReconciliation->approved_at?->toDateTimeString(),
            'message' => $this->isFinalApproval
                ? "Bank reconciliation for {$this->bankReconciliation->bankAccount->name} has been fully approved."
                : "Bank reconciliation for {$this->bankReconciliation->bankAccount->name} has been approved at the current level.",
            'url' => route('accounting.bank-reconciliation.show', $this->bankReconciliation),
        ];
    }
}
