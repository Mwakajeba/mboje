<?php

namespace App\Listeners\Leave;

use App\Events\Leave\LeaveReturned;
use App\Services\Leave\LeaveSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeaveReturnedNotification implements ShouldQueue
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
    public function handle(LeaveReturned $event): void
    {
        $this->smsService->sendRequestReturned($event->leaveRequest);
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(LeaveReturned $event): bool
    {
        return true;
    }
}

