<?php

namespace App\Console\Commands;

use App\Jobs\Purchase\ApprovalReminderJob;
use Illuminate\Console\Command;

class SendPurchaseApprovalReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchase:send-approval-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated reminders for pending purchase requisitions and purchase orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending purchase approval reminders...');

        try {
            ApprovalReminderJob::dispatch();
            $this->info('Approval reminder job dispatched successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to dispatch approval reminder job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

