<?php

namespace App\Listeners\Leave;

use App\Events\Leave\LeaveRejected;
use App\Services\Leave\LeaveSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeaveRejectedNotification implements ShouldQueue
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
    public function handle(LeaveRejected $event): void
    {
        $this->smsService->sendRequestRejected($event->leaveRequest);
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(LeaveRejected $event): bool
    {
        return true;
    }
}

