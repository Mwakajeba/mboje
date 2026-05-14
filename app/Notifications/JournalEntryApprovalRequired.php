<?php

namespace App\Notifications;

use App\Models\Journal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JournalEntryApprovalRequired extends Notification implements ShouldQueue
{
    use Queueable;

    protected $journal;
    protected $approvalLevel;

    /**
     * Create a new notification instance.
     */
    public function __construct(Journal $journal, int $approvalLevel)
    {
        $this->journal = $journal;
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
            ->subject('Journal Entry Approval Required - Level ' . $this->approvalLevel)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A journal entry requires your approval at Level ' . $this->approvalLevel . '.')
            ->line('**Journal Reference:** ' . $this->journal->reference)
            ->line('**Date:** ' . ($this->journal->date ? $this->journal->date->format('M d, Y') : 'N/A'))
            ->line('**Total Amount:** TZS ' . number_format($this->journal->total, 2))
            ->line('**Description:** ' . ($this->journal->description ?? 'N/A'))
            ->line('**Created By:** ' . ($this->journal->user->name ?? 'N/A'))
            ->line('**Approval Level:** Level ' . $this->approvalLevel)
            ->action('Review and Approve', route('accounting.journals.show', $this->journal))
            ->line('Please review the journal entry and take appropriate action.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $amount = number_format($this->journal->total, 2);
        $senderName = config('services.sms.senderid', 'SAFCO');
        
        return "Hello {$notifiable->name}, Journal Entry {$this->journal->reference} (TZS {$amount}) requires your approval at Level {$this->approvalLevel}. Please review and take action. - {$senderName}";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'journal_entry_approval_required',
            'journal_id' => $this->journal->id,
            'journal_reference' => $this->journal->reference,
            'approval_level' => $this->approvalLevel,
            'message' => "Journal Entry '{$this->journal->reference}' requires your approval at Level {$this->approvalLevel}.",
            'url' => route('accounting.journals.show', $this->journal),
        ];
    }
}
