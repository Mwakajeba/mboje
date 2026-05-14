# Supervisor Setup - Process All Queues

## Overview

This guide shows you how to configure Supervisor to process **all queues** in your Laravel application, including:
- `default` queue (general jobs)
- `purchase-invoice` queue (purchase invoice processing)
- Any other queues you may add in the future

## Current Configuration

The Supervisor config has been updated to listen to multiple queues:
```ini
command=php /home/efron/smartaccounting/artisan queue:work database --queue=default,purchase-invoice --sleep=3 --tries=3 --max-time=3600 --timeout=300
```

## Installation Steps

### 1. Update Supervisor Config File

The config file is located at: `/home/efron/smartaccounting/supervisor/laravel-worker.conf`

**Current queues being processed:**
- `default` - General jobs
- `purchase-invoice` - Purchase invoice item processing

### 2. Copy Config to Supervisor Directory

```bash
sudo cp /home/efron/smartaccounting/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf
```

### 3. Verify Paths in Config

Make sure the paths in the config match your system:
- Project path: `/home/efron/smartaccounting`
- User: `www-data` (or your web server user)
- Log file: `/home/efron/smartaccounting/storage/logs/worker.log`

Edit if needed:
```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

### 4. Reload and Start Supervisor

```bash
# Reload supervisor to recognize new/updated config
sudo supervisorctl reread

# Apply the configuration
sudo supervisorctl update

# Start the workers
sudo supervisorctl start laravel-worker:*

# Check status
sudo supervisorctl status laravel-worker:*
```

You should see output like:
```
laravel-worker:laravel-worker_00   RUNNING   pid 12345, uptime 0:00:05
laravel-worker:laravel-worker_01   RUNNING   pid 12346, uptime 0:00:05
```

## How It Works

### Queue Priority

When you specify multiple queues like `--queue=default,purchase-invoice`, Laravel processes them in order:
1. First processes jobs from `default` queue
2. Then processes jobs from `purchase-invoice` queue
3. Repeats this cycle

### Adding More Queues

If you add new queues in the future, update the config:

```ini
command=php /home/efron/smartaccounting/artisan queue:work database --queue=default,purchase-invoice,sales-invoice,other-queue --sleep=3 --tries=3 --max-time=3600 --timeout=300
```

Then reload:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart laravel-worker:*
```

## Monitoring

### Check Worker Status
```bash
sudo supervisorctl status laravel-worker:*
```

### View Logs
```bash
tail -f /home/efron/smartaccounting/storage/logs/worker.log
```

### Check Queue Status
```bash
# See pending jobs
php artisan queue:work --once

# See failed jobs
php artisan queue:failed

# Count jobs in queue
php artisan tinker --execute="echo DB::table('jobs')->count();"
```

### Restart Workers
```bash
# Restart all workers
sudo supervisorctl restart laravel-worker:*

# Stop workers
sudo supervisorctl stop laravel-worker:*

# Start workers
sudo supervisorctl start laravel-worker:*
```

## Troubleshooting

### Workers Not Starting

1. **Check Supervisor logs:**
   ```bash
   sudo tail -f /var/log/supervisor/supervisord.log
   ```

2. **Check worker logs:**
   ```bash
   tail -f /home/efron/smartaccounting/storage/logs/worker.log
   ```

3. **Verify paths:**
   - Make sure `/home/efron/smartaccounting` exists
   - Make sure `storage/logs/worker.log` is writable
   - Check user permissions (should be `www-data` or your web server user)

4. **Test command manually:**
   ```bash
   cd /home/efron/smartaccounting
   php artisan queue:work database --queue=default,purchase-invoice --once
   ```

### Jobs Not Processing

1. **Check if workers are running:**
   ```bash
   ps aux | grep "queue:work"
   ```

2. **Check queue connection:**
   ```bash
   php artisan tinker --execute="echo config('queue.default');"
   ```
   Should output: `database`

3. **Check for failed jobs:**
   ```bash
   php artisan queue:failed
   ```

4. **Process pending jobs manually:**
   ```bash
   php artisan queue:work database --queue=default,purchase-invoice --stop-when-empty
   ```

### Permission Issues

If you get permission errors:

```bash
# Make sure log directory is writable
sudo chown -R www-data:www-data /home/efron/smartaccounting/storage
sudo chmod -R 775 /home/efron/smartaccounting/storage
```

## Alternative: Process All Queues Without Specifying Names

If you want to process ALL queues without listing them explicitly, you can use separate workers for each queue, or use a wildcard approach with multiple supervisor programs.

### Option 1: Separate Workers (Recommended for High Volume)

Create multiple supervisor programs, one for each queue:

```ini
[program:laravel-worker-default]
command=php /home/efron/smartaccounting/artisan queue:work database --queue=default --sleep=3 --tries=3 --max-time=3600 --timeout=300
numprocs=1
...

[program:laravel-worker-purchase-invoice]
command=php /home/efron/smartaccounting/artisan queue:work database --queue=purchase-invoice --sleep=3 --tries=3 --max-time=3600 --timeout=300
numprocs=1
...
```

### Option 2: Single Worker for All Queues (Current Setup)

The current setup processes multiple queues in priority order, which works well for most cases.

## Configuration Options Explained

- `--queue=default,purchase-invoice` - List of queues to process (comma-separated)
- `--sleep=3` - Seconds to wait when no jobs available
- `--tries=3` - Number of retry attempts for failed jobs
- `--max-time=3600` - Maximum seconds a worker runs before restarting (prevents memory leaks)
- `--timeout=300` - Maximum seconds a single job can run
- `numprocs=2` - Number of worker processes to run (adjust based on CPU cores)

## Best Practices

1. **Monitor regularly** - Check logs and status frequently
2. **Adjust numprocs** - Based on your server's CPU cores (2-4 is usually good)
3. **Set appropriate timeouts** - Based on your longest-running jobs
4. **Use separate queues** - For different job types (as you're doing with purchase-invoice)
5. **Monitor failed jobs** - Set up alerts for failed jobs

## Auto-Start on Boot

Supervisor should auto-start on boot, but verify:

```bash
# Check if supervisor is enabled
sudo systemctl status supervisor

# Enable if not already
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

