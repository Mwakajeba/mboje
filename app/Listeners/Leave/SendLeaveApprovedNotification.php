<?php

namespace App\Listeners\Leave;

use App\Events\Leave\LeaveApproved;
use App\Services\Leave\LeaveSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeaveApprovedNotification implements ShouldQueue
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
    public function handle(LeaveApproved $event): void
    {
        $this->smsService->sendRequestApproved($event->leaveRequest);
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(LeaveApproved $event): bool
    {
        return true;
    }
}

