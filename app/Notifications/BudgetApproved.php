<?php

namespace App\Notifications;

use App\Models\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected $budget;
    protected $approver;
    protected $isFinalApproval;

    /**
     * Create a new notification instance.
     */
    public function __construct(Budget $budget, $approver, bool $isFinalApproval = false)
    {
        $this->budget = $budget;
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
            ->subject($this->isFinalApproval ? 'Budget Fully Approved' : 'Budget Approved at Current Level')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->isFinalApproval 
                ? 'The budget has been fully approved and is now ready for activation.'
                : 'The budget has been approved at the current level and moved to the next approval level.')
            ->line('**Budget Name:** ' . $this->budget->name)
            ->line('**Year:** ' . $this->budget->year)
            ->line('**Total Amount:** TZS ' . number_format($this->budget->total_amount, 2))
            ->line('**Approved By:** ' . ($this->approver->name ?? 'N/A'))
            ->line('**Approved At:** ' . ($this->budget->approved_at ? $this->budget->approved_at->format('M d, Y H:i') : 'N/A'))
            ->action('View Budget', route('accounting.budgets.show', $this->budget));

        if ($this->isFinalApproval) {
            $message->line('The budget is now active and can be used for financial planning.');
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
            return "Hello {$notifiable->name}, Budget '{$this->budget->name}' has been fully approved and is ready for activation. - {$senderName}";
        } else {
            return "Hello {$notifiable->name}, Budget '{$this->budget->name}' has been approved at current level and moved to next approval level. - {$senderName}";
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'budget_approved',
            'budget_id' => $this->budget->id,
            'budget_name' => $this->budget->name,
            'year' => $this->budget->year,
            'approver_id' => $this->approver->id ?? null,
            'approver_name' => $this->approver->name ?? 'N/A',
            'is_final_approval' => $this->isFinalApproval,
            'approved_at' => $this->budget->approved_at?->toDateTimeString(),
            'message' => $this->isFinalApproval
                ? "Budget '{$this->budget->name}' has been fully approved and is ready for activation."
                : "Budget '{$this->budget->name}' has been approved at the current level.",
            'url' => route('accounting.budgets.show', $this->budget),
        ];
    }
}
