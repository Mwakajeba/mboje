<?php

namespace App\Jobs\Leave;

use App\Models\Hr\LeaveRequest;
use App\Services\Leave\LeaveSmsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SlaReminderSweepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * SLA threshold in hours (send reminder if pending for more than this)
     */
    protected int $slaThresholdHours = 24;

    /**
     * Execute the job.
     */
    public function handle(LeaveSmsService $smsService): void
    {
        // Find leave requests pending for more than SLA threshold
        $pendingRequests = LeaveRequest::whereIn('status', ['pending_manager', 'pending_hr'])
            ->where('requested_at', '<=', Carbon::now()->subHours($this->slaThresholdHours))
            ->with(['approvals' => function ($q) {
                $q->where('decision', 'pending');
            }])
            ->get();

        Log::info('SLA Reminder Sweep', [
            'found_requests' => $pendingRequests->count(),
            'threshold_hours' => $this->slaThresholdHours,
        ]);

        foreach ($pendingRequests as $request) {
            foreach ($request->approvals as $approval) {
                if ($approval->isPending()) {
                    // Check if we already sent a reminder recently (within 12 hours)
                    $recentReminder = $request->smsLogs()
                        ->where('recipient_id', $approval->approver_id)
                        ->where('type', 'pending_approval')
                        ->where('created_at', '>=', Carbon::now()->subHours(12))
                        ->exists();

                    if (!$recentReminder) {
                        $smsService->sendPendingApprovalReminder($request, $approval->approver);

                        Log::info('SLA reminder sent', [
                            'request_id' => $request->id,
                            'approver_id' => $approval->approver_id,
                        ]);
                    }
                }
            }
        }
    }
}

