<?php

namespace App\Listeners\Leave;

use App\Events\Leave\LeaveRequested;
use App\Services\Leave\LeaveSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeaveRequestedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        protected LeaveSmsService $smsService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(LeaveRequested $event): void
    {
        $this->smsService->sendRequestSubmitted($event->leaveRequest);
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(LeaveRequested $event): bool
    {
        return true;
    }
}

