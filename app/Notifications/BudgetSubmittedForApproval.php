<?php

namespace App\Notifications;

use App\Models\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetSubmittedForApproval extends Notification implements ShouldQueue
{
    use Queueable;

    protected $budget;
    protected $submitter;

    /**
     * Create a new notification instance.
     */
    public function __construct(Budget $budget, $submitter)
    {
        $this->budget = $budget;
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
            ->subject('Budget Submitted for Approval')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A budget has been submitted for your approval.')
            ->line('**Budget Name:** ' . $this->budget->name)
            ->line('**Year:** ' . $this->budget->year)
            ->line('**Total Amount:** TZS ' . number_format($this->budget->total_amount, 2))
            ->line('**Submitted By:** ' . ($this->submitter->name ?? 'N/A'))
            ->line('**Submitted At:** ' . ($this->budget->submitted_at ? $this->budget->submitted_at->format('M d, Y H:i') : 'N/A'))
            ->action('Review Budget', route('accounting.budgets.show', $this->budget))
            ->line('Please review and approve or reject this budget as appropriate.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $amount = number_format($this->budget->total_amount, 2);
        $senderName = config('services.sms.senderid', 'SAFCO');
        
        return "Hello {$notifiable->name}, Budget '{$this->budget->name}' for year {$this->budget->year} (TZS {$amount}) has been submitted for approval. Please review and take action. - {$senderName}";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'budget_submitted_for_approval',
            'budget_id' => $this->budget->id,
            'budget_name' => $this->budget->name,
            'year' => $this->budget->year,
            'total_amount' => $this->budget->total_amount,
            'submitter_id' => $this->submitter->id ?? null,
            'submitter_name' => $this->submitter->name ?? 'N/A',
            'submitted_at' => $this->budget->submitted_at?->toDateTimeString(),
            'message' => "Budget '{$this->budget->name}' for year {$this->budget->year} has been submitted for approval.",
            'url' => route('accounting.budgets.show', $this->budget),
        ];
    }
}
