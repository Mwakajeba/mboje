<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\SystemSetting;
use Carbon\Carbon;

class LoginTriggerService
{
    /**
     * Jobs to dispatch on login
     * Format: ['job_class' => ['lock_key' => 'lock_key', 'lock_duration' => 60]]
     */
    private $jobsToDispatch = [
        \App\Jobs\CheckSubscriptionExpiryJob::class => [
            'lock_key' => 'login:subscription-check',
            'lock_duration' => 60, // Prevent running again for 60 minutes
        ],
        \App\Jobs\ConfirmTodaysBookings::class => [
            'lock_key' => 'login:confirm-bookings',
            'lock_duration' => 60, // Prevent running again for 60 minutes
        ],
        \App\Jobs\Leave\RunMonthlyAccrualsJob::class => [
            'lock_key' => 'login:monthly-accruals',
            'lock_duration' => 1440, // Prevent running again for 24 hours
        ],
        \App\Jobs\Leave\ProcessYearEndCarryoverJob::class => [
            'lock_key' => 'login:year-end-carryover',
            'lock_duration' => 1440, // Prevent running again for 24 hours
        ],
        \App\Jobs\Leave\CarryoverExpiryJob::class => [
            'lock_key' => 'login:carryover-expiry',
            'lock_duration' => 60, // Prevent running again for 60 minutes
        ],
        \App\Jobs\Leave\SlaReminderSweepJob::class => [
            'lock_key' => 'login:sla-reminder',
            'lock_duration' => 60, // Prevent running again for 60 minutes
        ],
    ];

    /**
     * Commands to run on login
     * Format: ['command_signature' => ['lock_key' => 'lock_key', 'lock_duration' => 60, 'sync' => false]]
     * Set 'sync' => true to run synchronously (immediately) instead of queued
     */
    private $commandsToRun = [
        'invoices:apply-late-fees' => [
            'lock_key' => 'login:apply-late-fees',
            'lock_duration' => 60, // Prevent running again for 60 minutes
            'sync' => true, // Run synchronously to ensure immediate GL posting
        ],
        'fx:auto-reverse' => [
            'lock_key' => 'login:fx-auto-reverse',
            'lock_duration' => 1440, // Prevent running again for 24 hours
        ],
        'inventory:check-expiry-alerts' => [
            'lock_key' => 'login:check-expiry-alerts',
            'lock_duration' => 60, // Prevent running again for 60 minutes
        ],
        'inventory:write-off-expired' => [
            'lock_key' => 'login:write-off-expired',
            'lock_duration' => 1440, // Prevent running again for 24 hours
        ],
        'hfs:check-overdue' => [
            'lock_key' => 'login:hfs-check-overdue',
            'lock_duration' => 1440, // Prevent running again for 24 hours
            'sync' => false, // Run asynchronously
            'handle_missed_days' => true, // Special handling for missed days
        ],
        // Note: Test commands excluded (TestBackup, TestBankReconciliationAutoUpdate, TestSystemConfigPermissions)
        // Note: One-time setup commands excluded (InitializeSystemSettings, AddSalesMenu, MigrateInventoryCostLayers)
        // Note: CleanupPayrollData excluded as it's typically run manually
    ];

    /**
     * Trigger jobs and commands on login
     * Prevents double execution using cache locks
     */
    public function triggerOnLogin(): void
    {
        // Dispatch jobs
        $this->dispatchJobs();

        // Run commands
        $this->runCommands();
    }

    /**
     * Dispatch queued jobs
     */
    private function dispatchJobs(): void
    {
        foreach ($this->jobsToDispatch as $jobClass => $config) {
            $lockKey = $config['lock_key'] ?? 'login:job:' . class_basename($jobClass);
            $lockDuration = ($config['lock_duration'] ?? 60) * 60; // Convert minutes to seconds

            // Check if job is already running or recently executed
            if (Cache::has($lockKey)) {
                Log::info("Skipping job dispatch - already executed recently", [
                    'job' => $jobClass,
                    'lock_key' => $lockKey,
                ]);
                continue;
            }

            try {
                // Set lock to prevent duplicate execution
                Cache::put($lockKey, true, $lockDuration);

                // Dispatch the job
                $jobClass::dispatch();
                
                Log::info("Job dispatched on login", [
                    'job' => $jobClass,
                    'lock_key' => $lockKey,
                    'lock_duration_minutes' => $config['lock_duration'] ?? 60,
                    'status' => 'dispatched',
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to dispatch job on login", [
                    'job' => $jobClass,
                    'lock_key' => $lockKey,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'status' => 'failed',
                ]);
            }
        }
    }

    /**
     * Run console commands
     */
    private function runCommands(): void
    {
        foreach ($this->commandsToRun as $commandSignature => $config) {
            $lockKey = $config['lock_key'] ?? 'login:command:' . $commandSignature;
            $lockDuration = ($config['lock_duration'] ?? 60) * 60; // Convert minutes to seconds

            // Special handling for commands that need to catch up on missed days
            if (($config['handle_missed_days'] ?? false) && $commandSignature === 'hfs:check-overdue') {
                $this->runCommandWithMissedDays($commandSignature, $config, $lockKey, $lockDuration);
                continue;
            }

            // Check if command is already running or recently executed
            if (Cache::has($lockKey)) {
                Log::info("Skipping command execution - already executed recently", [
                    'command' => $commandSignature,
                    'lock_key' => $lockKey,
                ]);
                continue;
            }

            try {
                // Set lock to prevent duplicate execution
                Cache::put($lockKey, true, $lockDuration);

                $runSync = $config['sync'] ?? false;

                if ($runSync) {
                    // Run command synchronously (immediately) for critical operations like GL posting
                    try {
                        $exitCode = Artisan::call($commandSignature);
                        if ($exitCode === 0) {
                            Log::info("Command executed successfully on login (synchronous)", [
                                'command' => $commandSignature,
                                'lock_key' => $lockKey,
                                'exit_code' => $exitCode,
                            ]);
                        } else {
                            Log::warning("Command completed with non-zero exit code on login (synchronous)", [
                                'command' => $commandSignature,
                                'lock_key' => $lockKey,
                                'exit_code' => $exitCode,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error("Command execution failed on login (synchronous)", [
                            'command' => $commandSignature,
                            'lock_key' => $lockKey,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } else {
                    // Run command asynchronously (non-blocking) using queue
                    // This prevents blocking the login process
                    dispatch(function () use ($commandSignature, $lockKey) {
                        try {
                            $exitCode = Artisan::call($commandSignature);
                            if ($exitCode === 0) {
                                Log::info("Command executed successfully on login", [
                                    'command' => $commandSignature,
                                    'lock_key' => $lockKey,
                                    'exit_code' => $exitCode,
                                ]);
                            } else {
                                Log::warning("Command completed with non-zero exit code on login", [
                                    'command' => $commandSignature,
                                    'lock_key' => $lockKey,
                                    'exit_code' => $exitCode,
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error("Command execution failed on login", [
                                'command' => $commandSignature,
                                'lock_key' => $lockKey,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                        }
                    })->afterResponse();
                    
                    Log::info("Command queued on login", [
                        'command' => $commandSignature,
                        'lock_key' => $lockKey,
                        'lock_duration_minutes' => $config['lock_duration'] ?? 60,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to execute/queue command on login", [
                    'command' => $commandSignature,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * Run command with handling for missed days
     * For HFS check, if no one logged in for multiple days, run the check for each missed day
     */
    private function runCommandWithMissedDays(string $commandSignature, array $config, string $lockKey, int $lockDuration): void
    {
        // Get last run date from system settings
        $lastRunDate = SystemSetting::getValue('hfs_check_last_run_date', null);
        
        if ($lastRunDate) {
            $lastRun = Carbon::parse($lastRunDate);
        } else {
            // First time running - set to yesterday to check today
            $lastRun = Carbon::yesterday();
        }

        $today = Carbon::today();
        $missedDays = [];

        // Calculate missed days (excluding today, as we'll run it for today)
        if ($lastRun->lt($today)) {
            $current = $lastRun->copy()->addDay();
            while ($current->lt($today)) {
                $missedDays[] = $current->copy();
                $current->addDay();
            }
        }

        // Run for each missed day (asynchronously to not block login)
        if (count($missedDays) > 0) {
            Log::info("HFS check: Found missed days, running for each missed day", [
                'command' => $commandSignature,
                'missed_days_count' => count($missedDays),
                'last_run_date' => $lastRun->format('Y-m-d'),
                'today' => $today->format('Y-m-d'),
            ]);

            dispatch(function () use ($commandSignature, $missedDays, $today) {
                foreach ($missedDays as $missedDay) {
                    try {
                        $exitCode = Artisan::call($commandSignature, [
                            '--date' => $missedDay->format('Y-m-d'),
                            '--silent' => true,
                        ]);
                        
                        Log::info("HFS check executed for missed day", [
                            'command' => $commandSignature,
                            'date' => $missedDay->format('Y-m-d'),
                            'exit_code' => $exitCode,
                        ]);
                    } catch (\Exception $e) {
                        Log::error("HFS check failed for missed day", [
                            'command' => $commandSignature,
                            'date' => $missedDay->format('Y-m-d'),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Run for today as well
                try {
                    $exitCode = Artisan::call($commandSignature, [
                        '--silent' => true,
                    ]);
                    
                    Log::info("HFS check executed for today", [
                        'command' => $commandSignature,
                        'date' => $today->format('Y-m-d'),
                        'exit_code' => $exitCode,
                    ]);
                } catch (\Exception $e) {
                    Log::error("HFS check failed for today", [
                        'command' => $commandSignature,
                        'date' => $today->format('Y-m-d'),
                        'error' => $e->getMessage(),
                    ]);
                }

                // Update last run date to today
                SystemSetting::setValue('hfs_check_last_run_date', $today->format('Y-m-d'), 'string', 'hfs', 'HFS Check Last Run Date', 'Last date when HFS overdue check was executed');
            })->afterResponse();
        } else {
            // No missed days, just run for today (if not already run today)
            if (!$lastRun->isToday()) {
                // Check if already run today (using cache lock)
                if (!Cache::has($lockKey)) {
                    Cache::put($lockKey, true, $lockDuration);
                    
                    dispatch(function () use ($commandSignature, $today, $lockKey) {
                        try {
                            $exitCode = Artisan::call($commandSignature, [
                                '--silent' => true,
                            ]);
                            
                            // Update last run date to today
                            SystemSetting::setValue('hfs_check_last_run_date', $today->format('Y-m-d'), 'string', 'hfs', 'HFS Check Last Run Date', 'Last date when HFS overdue check was executed');
                            
                            Log::info("HFS check executed for today", [
                                'command' => $commandSignature,
                                'date' => $today->format('Y-m-d'),
                                'exit_code' => $exitCode,
                            ]);
                        } catch (\Exception $e) {
                            Log::error("HFS check failed for today", [
                                'command' => $commandSignature,
                                'date' => $today->format('Y-m-d'),
                                'error' => $e->getMessage(),
                            ]);
                        }
                    })->afterResponse();
                    
                    Log::info("HFS check queued for today", [
                        'command' => $commandSignature,
                        'lock_key' => $lockKey,
                    ]);
                }
            } else {
                Log::info("HFS check already run today, skipping", [
                    'command' => $commandSignature,
                    'last_run_date' => $lastRun->format('Y-m-d'),
                ]);
            }
        }
    }

    /**
     * Get list of configured jobs
     */
    public function getConfiguredJobs(): array
    {
        return $this->jobsToDispatch;
    }

    /**
     * Get list of configured commands
     */
    public function getConfiguredCommands(): array
    {
        return $this->commandsToRun;
    }

    /**
     * Add a job to be dispatched on login
     */
    public function addJob(string $jobClass, string $lockKey, int $lockDurationMinutes = 60): void
    {
        $this->jobsToDispatch[$jobClass] = [
            'lock_key' => $lockKey,
            'lock_duration' => $lockDurationMinutes,
        ];
    }

    /**
     * Add a command to be run on login
     */
    public function addCommand(string $commandSignature, string $lockKey, int $lockDurationMinutes = 60): void
    {
        $this->commandsToRun[$commandSignature] = [
            'lock_key' => $lockKey,
            'lock_duration' => $lockDurationMinutes,
        ];
    }

    /**
     * Get log status summary for debugging
     * Returns array with counts of success/failure messages
     */
    public static function getLogStatusSummary(int $hours = 24): array
    {
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) {
            return ['error' => 'Log file not found'];
        }

        $logContent = file_get_contents($logFile);
        
        // Count success messages
        $successPattern = '/Job dispatched on login|Command queued on login|Command executed successfully/';
        $successCount = preg_match_all($successPattern, $logContent);
        
        // Count failure messages
        $failurePattern = '/Failed to dispatch job|Failed to queue command|Command execution failed/';
        $failureCount = preg_match_all($failurePattern, $logContent);
        
        // Count skipped messages
        $skippedPattern = '/Skipping job dispatch|Skipping command execution/';
        $skippedCount = preg_match_all($skippedPattern, $logContent);
        
        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'skipped_count' => $skippedCount,
            'total_operations' => $successCount + $failureCount + $skippedCount,
            'hours_analyzed' => $hours,
        ];
    }
}

