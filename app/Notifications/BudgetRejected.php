<?php

namespace App\Notifications;

use App\Models\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected $budget;
    protected $rejector;
    protected $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Budget $budget, $rejector, string $reason)
    {
        $this->budget = $budget;
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
            ->subject('Budget Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Unfortunately, your budget has been rejected.')
            ->line('**Budget Name:** ' . $this->budget->name)
            ->line('**Year:** ' . $this->budget->year)
            ->line('**Total Amount:** TZS ' . number_format($this->budget->total_amount, 2))
            ->line('**Rejected By:** ' . ($this->rejector->name ?? 'N/A'))
            ->line('**Rejected At:** ' . ($this->budget->rejected_at ? $this->budget->rejected_at->format('M d, Y H:i') : 'N/A'))
            ->line('**Rejection Reason:**')
            ->line($this->reason)
            ->action('View Budget', route('accounting.budgets.show', $this->budget))
            ->line('Please review the rejection reason, make necessary corrections, and resubmit the budget.');
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
        
        return "Hello {$notifiable->name}, Budget '{$this->budget->name}' has been rejected. Reason: {$shortReason} Please review and resubmit. - {$senderName}";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'budget_rejected',
            'budget_id' => $this->budget->id,
            'budget_name' => $this->budget->name,
            'year' => $this->budget->year,
            'rejector_id' => $this->rejector->id ?? null,
            'rejector_name' => $this->rejector->name ?? 'N/A',
            'rejection_reason' => $this->reason,
            'rejected_at' => $this->budget->rejected_at?->toDateTimeString(),
            'message' => "Budget '{$this->budget->name}' has been rejected. Reason: {$this->reason}",
            'url' => route('accounting.budgets.show', $this->budget),
        ];
    }
}
