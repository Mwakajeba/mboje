<?php

namespace App\Notifications;

use App\Models\Budget;
use App\Models\ApprovalLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetApprovalRequired extends Notification implements ShouldQueue
{
    use Queueable;

    protected $budget;
    protected $approvalLevel;

    /**
     * Create a new notification instance.
     */
    public function __construct(Budget $budget, ApprovalLevel $approvalLevel)
    {
        $this->budget = $budget;
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
            ->subject('Budget Approval Required - ' . $this->approvalLevel->level_name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A budget requires your approval at the ' . $this->approvalLevel->level_name . ' level.')
            ->line('**Budget Name:** ' . $this->budget->name)
            ->line('**Year:** ' . $this->budget->year)
            ->line('**Total Amount:** TZS ' . number_format($this->budget->total_amount, 2))
            ->line('**Approval Level:** ' . $this->approvalLevel->level_name)
            ->line('**Submitted By:** ' . ($this->budget->submittedBy->name ?? 'N/A'))
            ->action('Review and Approve', route('accounting.budgets.show', $this->budget))
            ->line('Please review the budget and take appropriate action.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $amount = number_format($this->budget->total_amount, 2);
        $senderName = config('services.sms.senderid', 'SAFCO');
        
        return "Hello {$notifiable->name}, Budget '{$this->budget->name}' (TZS {$amount}) requires your approval at {$this->approvalLevel->level_name} level. Please review and take action. - {$senderName}";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'budget_approval_required',
            'budget_id' => $this->budget->id,
            'budget_name' => $this->budget->name,
            'year' => $this->budget->year,
            'approval_level_id' => $this->approvalLevel->id,
            'approval_level_name' => $this->approvalLevel->level_name,
            'message' => "Budget '{$this->budget->name}' requires your approval at {$this->approvalLevel->level_name} level.",
            'url' => route('accounting.budgets.show', $this->budget),
        ];
    }
}
