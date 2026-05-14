<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule FX revaluation auto-reversal (runs on the 1st of each month at 00:00)
Schedule::command('fx:auto-reverse')
    ->monthlyOn(1, '00:00')
    ->description('Auto-reverse previous month\'s FX revaluation entries')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule month-end FX revaluation processing (runs on the 1st of each month at 00:05)
// This will reverse previous month's revaluation and create new revaluation for previous month-end
Schedule::command('fx:process-month-end-revaluation')
    ->monthlyOn(1, '00:05')
    ->description('Process month-end FX revaluation (reverses previous month and creates new revaluation)')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule purchase approval reminders (runs every 12 hours at 8:00 and 20:00)
Schedule::command('purchase:send-approval-reminders')
    ->twiceDaily(8, 20)
    ->description('Send automated reminders for pending purchase requisitions and purchase orders')
    ->withoutOverlapping();

// Schedule accrual auto-posting (runs on the 1st of each month at 00:00 to post previous month's accruals)
Schedule::command('accruals:auto-post')
    ->monthlyOn(1, '00:00')
    ->description('Auto-post pending accrual journals at month-end (IFRS compliant)')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule accrual auto-reversal (runs on the 1st of each month at 00:30)
Schedule::command('accruals:auto-reverse')
    ->monthlyOn(1, '00:30')
    ->description('Auto-reverse previous month\'s accrual entries (IFRS compliant)')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule deletion of expired online bookings (runs every hour)
Schedule::command('bookings:cancel-expired-online')
    ->hourly()
    ->description('Delete online bookings that are older than 2 hours and not confirmed')
    ->withoutOverlapping();
