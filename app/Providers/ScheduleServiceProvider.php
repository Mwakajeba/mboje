<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\CheckSubscriptionExpiryJob;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            // Confirm today's pending bookings daily at 00:05
            $schedule->job(new \App\Jobs\ConfirmTodaysBookings())->dailyAt('00:05');

            // Schedule subscription expiry check to run every minute
            $schedule->job(new CheckSubscriptionExpiryJob())
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/subscription-expiry-check.log'));
});
    }
}