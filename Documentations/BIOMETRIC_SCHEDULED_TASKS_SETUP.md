# Biometric Scheduled Tasks Setup

## Overview

To automatically sync biometric devices and process attendance logs, you need to set up scheduled tasks (cron jobs).

## Laravel 12 Setup

### Option 1: Using Laravel Scheduler (Recommended)

Add to `bootstrap/app.php`:

```php
use Illuminate\Console\Scheduling\Schedule;

->withSchedule(function (Schedule $schedule) {
    // Sync biometric devices every 15 minutes
    $schedule->command('biometric:sync')
        ->everyFifteenMinutes()
        ->withoutOverlapping()
        ->runInBackground();
    
    // Process pending biometric logs every 5 minutes
    $schedule->command('biometric:process-logs --limit=200')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->runInBackground();
})
```

### Option 2: Using System Cron

Add to your server's crontab (`crontab -e`):

```bash
# Sync biometric devices every 15 minutes
*/15 * * * * cd /path/to/smartaccounting && php artisan biometric:sync >> /dev/null 2>&1

# Process pending logs every 5 minutes
*/5 * * * * cd /path/to/smartaccounting && php artisan biometric:process-logs --limit=200 >> /dev/null 2>&1
```

### Option 3: Using Supervisor (For Production)

Create `/etc/supervisor/conf.d/biometric-worker.conf`:

```ini
[program:biometric-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/smartaccounting/artisan biometric:process-logs --limit=200
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/smartaccounting/storage/logs/biometric-worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start biometric-worker:*
```

## Manual Execution

### Sync All Devices
```bash
php artisan biometric:sync
```

### Sync Specific Device
```bash
php artisan biometric:sync --device-id=1
```

### Process Pending Logs
```bash
php artisan biometric:process-logs
```

### Process Logs for Specific Device
```bash
php artisan biometric:process-logs --device-id=1 --limit=100
```

## Monitoring

Check logs in:
- `storage/logs/laravel.log` - General application logs
- Device details page - Shows sync status and pending logs count

## Troubleshooting

If scheduled tasks are not running:
1. Verify cron is running: `systemctl status cron`
2. Check Laravel scheduler: `php artisan schedule:list`
3. Test manually: `php artisan biometric:sync`
4. Check logs: `tail -f storage/logs/laravel.log`

