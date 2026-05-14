# Login Trigger Service - Log Interpretation Guide

## Log Location
All logs are stored in: `storage/logs/laravel.log`

## Log Levels Explained

### ✅ SUCCESS Indicators (INFO level)
- **`Job dispatched on login`** - Job was successfully queued/dispatched
- **`Command queued on login`** - Command was successfully queued for execution
- **`Command executed successfully on login`** - Command completed with exit code 0
- **`Skipping job dispatch - already executed recently`** - Job skipped due to lock (this is normal, prevents duplicates)

### ⚠️ WARNING Indicators (WARNING level)
- **`Command completed with non-zero exit code on login`** - Command ran but returned an error code

### ❌ FAILURE Indicators (ERROR level)
- **`Failed to dispatch job on login`** - Job dispatch failed (exception occurred)
- **`Failed to queue command on login`** - Command queueing failed (exception occurred)
- **`Command execution failed on login - Command execution threw an exception

## How to Check Logs

### 1. View Recent Logs
```bash
tail -f storage/logs/laravel.log
```

### 2. Search for Success Messages
```bash
grep "Job dispatched on login\|Command queued on login\|Command executed successfully" storage/logs/laravel.log
```

### 3. Search for Error Messages
```bash
grep "ERROR\|Failed" storage/logs/laravel.log
```

### 4. Search for Specific Job/Command
```bash
grep "CheckSubscriptionExpiryJob\|invoices:apply-late-fees" storage/logs/laravel.log
```

### 5. View Last 50 Lines
```bash
tail -n 50 storage/logs/laravel.log
```

## Log Entry Format

Each log entry follows this format:
```
[YYYY-MM-DD HH:MM:SS] ENVIRONMENT.LEVEL: Message {"key":"value","key2":"value2"}
```

Example:
```
[2025-11-26 15:32:19] local.INFO: Job dispatched on login {"job":"App\\Jobs\\CheckSubscriptionExpiryJob","lock_key":"login:subscription-check","lock_duration_minutes":60}
```

## Understanding Log Messages

### Job Logs
- **Success**: `Job dispatched on login` with `status: dispatched`
- **Failure**: `Failed to dispatch job on login` with `status: failed` and error details

### Command Logs
- **Queued**: `Command queued on login` - Command was added to queue
- **Executing**: Check for `Command executed successfully` or `Command execution failed`
- **Success**: `Command executed successfully` with `exit_code: 0`
- **Warning**: `Command completed with non-zero exit code` with `exit_code: X` (where X != 0)
- **Failure**: `Command execution failed` with error message and trace

## Example Log Analysis

### Successful Execution
```
[2025-11-26 15:32:19] local.INFO: Job dispatched on login {"job":"App\\Jobs\\CheckSubscriptionExpiryJob","status":"dispatched"}
[2025-11-26 15:32:19] local.INFO: Command queued on login {"command":"invoices:apply-late-fees"}
[2025-11-26 15:32:20] local.INFO: Command executed successfully on login {"command":"invoices:apply-late-fees","exit_code":0}
```
✅ All operations succeeded

### Failed Execution
```
[2025-11-26 15:32:19] local.ERROR: Failed to dispatch job on login {"job":"App\\Jobs\\SomeJob","error":"Class not found","status":"failed"}
[2025-11-26 15:32:20] local.ERROR: Command execution failed on login {"command":"some:command","error":"Database connection failed"}
```
❌ Operations failed - check error messages

### Skipped (Due to Lock)
```
[2025-11-26 15:32:19] local.INFO: Skipping job dispatch - already executed recently {"job":"App\\Jobs\\CheckSubscriptionExpiryJob"}
```
ℹ️ Normal behavior - prevents duplicate execution

## Quick Status Check Commands

### Check if all jobs/commands succeeded in last hour
```bash
grep -E "(Job dispatched|Command queued|Command executed successfully)" storage/logs/laravel.log | tail -20
```

### Check for any failures in last hour
```bash
grep -E "(ERROR|Failed)" storage/logs/laravel.log | tail -20
```

### Count successful dispatches today
```bash
grep "Job dispatched on login\|Command queued on login" storage/logs/laravel.log | grep "$(date +%Y-%m-%d)" | wc -l
```

### Count failures today
```bash
grep "Failed to\|Command execution failed" storage/logs/laravel.log | grep "$(date +%Y-%m-%d)" | wc -l
```

