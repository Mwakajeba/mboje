# Queue Worker Setup Guide

## Development Setup (Automatic)

### Current Configuration
The queue driver is set to **`sync`** in `.env`:
```
QUEUE_CONNECTION=sync
```

This means queue jobs execute **immediately and synchronously** during the import request - no separate worker needed!

**Benefits:**
- ✅ No manual `php artisan queue:work` command needed
- ✅ Imports execute immediately in the same HTTP request
- ✅ Perfect for development/testing
- ✅ Easier debugging

**Drawbacks:**
- ⚠️ Long-running imports will block the HTTP request
- ⚠️ If import fails, user sees error immediately (no retry)
- ⚠️ Not suitable for very large files (10MB+)

### How to Use (Development)

```bash
# 1. Start Laravel development server
php artisan serve

# 2. Upload CSV file via /inventory/items
# 3. Import executes immediately - no additional worker needed!
# 4. See results right away
```

---

## Production Setup (Background Processing)

For production, switch to **`database`** or **`redis`** driver with Supervisor for persistent background processing.

### Step 1: Configure Queue Driver

Edit `.env`:
```bash
# For database-backed queue (simple, no additional services)
QUEUE_CONNECTION=database

# OR for Redis (faster, recommended for high volume)
QUEUE_CONNECTION=redis
```

### Step 2: Install & Configure Supervisor

Supervisor keeps your queue worker running continuously in the background.

#### Ubuntu/Debian:
```bash
sudo apt-get update
sudo apt-get install supervisor
```

#### macOS:
```bash
brew install supervisor
```

### Step 3: Create Supervisor Configuration

Create `/etc/supervisor/conf.d/laravel-queue.conf`:

```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/smartaccounting/artisan queue:work %(ENV_QUEUE_CONNECTION)s --timeout=3600 --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel-queue.log
stopwaitsecs=3600
startsecs=10
stopasgroup=true
killasgroup=true
priority=998
```

**Configuration Explanation:**
- `process_name` - Unique name for each process
- `command` - The artisan queue:work command to run
- `numprocs=4` - Start 4 worker processes (adjust based on CPU cores)
- `timeout=3600` - 1 hour timeout for long imports
- `tries=3` - Retry failed jobs 3 times
- `autostart=true` - Start automatically on reboot
- `autorestart=true` - Restart if process dies
- `stdout_logfile` - Log file location

### Step 4: Start Supervisor

```bash
# Reload supervisor to recognize new config
sudo supervisorctl reread

# Apply the new configuration
sudo supervisorctl update

# Start the workers
sudo supervisorctl start laravel-queue-worker:*

# Check status
sudo supervisorctl status laravel-queue-worker:*
```

### Step 5: Monitor Queue Workers

```bash
# View worker status
sudo supervisorctl status

# View worker logs
tail -f /var/log/laravel-queue.log

# Restart workers if needed
sudo supervisorctl restart laravel-queue-worker:*

# Stop workers
sudo supervisorctl stop laravel-queue-worker:*
```

### Step 6: Auto-start on Boot

Ensure Supervisor starts on system boot:

```bash
# For systemd (Ubuntu 18.04+)
sudo systemctl enable supervisor
sudo systemctl start supervisor

# For older systems
sudo update-rc.d supervisor enable
```

---

## Production Performance Tuning

### Database Queue vs Redis Queue

#### Database Queue (Default)
```bash
QUEUE_CONNECTION=database
```
**Pros:**
- No additional services needed
- Uses existing MySQL database
- Good for low-volume applications
- Simple to set up

**Cons:**
- Slower than Redis
- Database load increases
- Less suitable for high volume

**Best for:** < 1000 imports/day

#### Redis Queue (Recommended)
```bash
QUEUE_CONNECTION=redis
```
**Pros:**
- Much faster than database
- No impact on MySQL
- Scales better
- Built-in reliability

**Cons:**
- Requires Redis service running
- Additional infrastructure

**Best for:** > 1000 imports/day

### Worker Configuration Tuning

Adjust `numprocs` based on your server:
```ini
# For 2-core server
numprocs=2

# For 4-core server
numprocs=4

# For 8-core server
numprocs=8

# Rule of thumb: numprocs = CPU cores or less
```

### Timeout Configuration

Adjust for your typical import size:
```ini
# For small imports (< 1000 items)
--timeout=600  # 10 minutes

# For medium imports (1000-10000 items)
--timeout=1800  # 30 minutes

# For large imports (10000+ items)
--timeout=3600  # 1 hour
```

---

## Monitoring & Alerting

### Check Queue Status

```bash
# View queue statistics
php artisan queue:failed

# Manually retry failed jobs
php artisan queue:retry

# Clear failed jobs
php artisan queue:flush
```

### Monitor with Horizon (Optional)

For advanced monitoring, install Laravel Horizon:

```bash
composer require laravel/horizon

php artisan horizon:install

php artisan migrate

php artisan serve
```

Then visit: `http://localhost:8000/horizon`

### Set Up Log Rotation

Create `/etc/logrotate.d/laravel-queue`:

```
/var/log/laravel-queue.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        supervisorctl restart laravel-queue-worker:*
    endscript
}
```

---

## Troubleshooting

### Queue Worker Not Running

```bash
# Check Supervisor status
sudo supervisorctl status

# Check logs
tail -f /var/log/laravel-queue.log

# Restart
sudo supervisorctl restart laravel-queue-worker:*
```

### Jobs Not Processing

```bash
# Check database queue table
php artisan tinker
>>> DB::table('jobs')->count()

# Check failed jobs table
>>> DB::table('failed_jobs')->count()

# Retry failed jobs
php artisan queue:retry --all
```

### High Memory Usage

- Reduce `numprocs` value
- Increase `timeout` value
- Check for memory leaks in job code
- Monitor with: `ps aux | grep queue:work`

### Worker Keeps Restarting

- Check logs: `tail -f /var/log/laravel-queue.log`
- Increase `startsecs` value in supervisor config
- Check database/Redis connection
- Verify file permissions

---

## Deployment Checklist

### Before Going Live

- [ ] Test with `QUEUE_CONNECTION=sync` locally
- [ ] Test with `QUEUE_CONNECTION=database` locally with separate worker
- [ ] Test with large CSV file (5000+ items)
- [ ] Verify Supervisor configuration
- [ ] Set up log rotation
- [ ] Create monitoring/alerting
- [ ] Document queue worker setup
- [ ] Train team on monitoring

### Production Deployment

- [ ] Update `.env` with correct QUEUE_CONNECTION
- [ ] Update Supervisor config with correct paths
- [ ] Start Supervisor: `sudo supervisorctl update && sudo supervisorctl start laravel-queue-worker:*`
- [ ] Verify workers are running: `sudo supervisorctl status`
- [ ] Test with real CSV import
- [ ] Monitor logs for 24 hours
- [ ] Set up backup/disaster recovery

---

## Switching Between Sync and Database Driver

### To Use Sync (Development)
```bash
# .env
QUEUE_CONNECTION=sync
```
No queue worker needed - imports run immediately.

### To Use Database (Production)
```bash
# .env
QUEUE_CONNECTION=database

# Start Supervisor
sudo supervisorctl start laravel-queue-worker:*
```

### To Switch Back
```bash
# Stop all workers
sudo supervisorctl stop laravel-queue-worker:*

# Change .env
QUEUE_CONNECTION=sync

# No restart needed
```

---

## Commands Reference

```bash
# Start queue worker
php artisan queue:work

# Process one job
php artisan queue:work --once

# Process with specific connection
php artisan queue:work database
php artisan queue:work redis

# Monitor failed jobs
php artisan queue:failed
php artisan queue:retry all

# Clear all jobs
php artisan queue:flush

# Install Supervisor
sudo apt-get install supervisor

# Control workers
sudo supervisorctl start laravel-queue-worker:*
sudo supervisorctl stop laravel-queue-worker:*
sudo supervisorctl restart laravel-queue-worker:*
sudo supervisorctl status
```

---

## Current Setup Summary

| Setting | Development | Production |
|---------|-------------|-----------|
| QUEUE_CONNECTION | sync | database/redis |
| Auto-start | ✅ Built-in | Supervisor |
| Retry attempts | 0 | 3 |
| Timeout | Unlimited | 3600s |
| Worker processes | 1 (main) | 4+ |
| Monitoring | Console | Supervisor + Logs |
| Best for | Testing | Live usage |

---

**Next Steps:**
1. For **development**: Current setup is ready! Just upload CSV and import runs immediately.
2. For **production**: Follow the Supervisor setup section above.

**Last Updated:** 2026-01-06
**Status:** Development mode active (sync driver)
