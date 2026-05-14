# Supervisor Setup for Server

## Quick Setup Commands

Run these commands on your server as root:

```bash
# 1. Install Supervisor (if not already installed)
apt-get update
apt-get install -y supervisor

# 2. Copy the supervisor config
cp /var/www/html/smartaccounting/supervisor/laravel-worker-server.conf /etc/supervisor/conf.d/laravel-worker.conf

# 3. Make sure log directory exists and is writable
mkdir -p /var/www/html/smartaccounting/storage/logs
chown -R www-data:www-data /var/www/html/smartaccounting/storage
chmod -R 775 /var/www/html/smartaccounting/storage

# 4. Reload Supervisor
supervisorctl reread
supervisorctl update

# 5. Start the workers
supervisorctl start laravel-worker:*

# 6. Check status
supervisorctl status laravel-worker:*
```

## What This Does

- **Processes all queues**: `default` and `purchase-invoice` queues
- **Auto-starts**: Workers start automatically on server reboot
- **Auto-restarts**: Workers restart if they crash
- **2 workers**: Runs 2 worker processes for better performance
- **Logs**: All output goes to `/var/www/html/smartaccounting/storage/logs/worker.log`

## Verify It's Working

```bash
# Check if workers are running
supervisorctl status laravel-worker:*

# Should show:
# laravel-worker:laravel-worker_00   RUNNING   pid 12345, uptime 0:05:00
# laravel-worker:laravel-worker_01   RUNNING   pid 12346, uptime 0:05:00

# Check logs
tail -f /var/www/html/smartaccounting/storage/logs/worker.log

# Check pending jobs (should decrease as jobs are processed)
cd /var/www/html/smartaccounting
php artisan tinker --execute="echo DB::table('jobs')->count();"
```

## Managing Workers

```bash
# Restart workers (after code changes)
supervisorctl restart laravel-worker:*

# Stop workers
supervisorctl stop laravel-worker:*

# Start workers
supervisorctl start laravel-worker:*

# View all supervisor programs
supervisorctl status
```

## Adding More Queues

If you add new queues in the future, edit the config:

```bash
nano /etc/supervisor/conf.d/laravel-worker.conf
```

Change this line:
```ini
command=php /var/www/html/smartaccounting/artisan queue:work database --queue=default,purchase-invoice --sleep=3 --tries=3 --max-time=3600 --timeout=300
```

To include your new queue:
```ini
command=php /var/www/html/smartaccounting/artisan queue:work database --queue=default,purchase-invoice,sales-invoice --sleep=3 --tries=3 --max-time=3600 --timeout=300
```

Then reload:
```bash
supervisorctl reread
supervisorctl update
supervisorctl restart laravel-worker:*
```

## Troubleshooting

### Workers Not Starting

```bash
# Check supervisor logs
tail -f /var/log/supervisor/supervisord.log

# Check worker logs
tail -f /var/www/html/smartaccounting/storage/logs/worker.log

# Test the command manually
cd /var/www/html/smartaccounting
php artisan queue:work database --queue=default,purchase-invoice --once
```

### Permission Issues

```bash
# Fix permissions
chown -R www-data:www-data /var/www/html/smartaccounting/storage
chmod -R 775 /var/www/html/smartaccounting/storage
```

### Jobs Not Processing

```bash
# Check if queue connection is correct
cd /var/www/html/smartaccounting
php artisan tinker --execute="echo config('queue.default');"
# Should output: database

# Process pending jobs manually
php artisan queue:work database --queue=default,purchase-invoice --stop-when-empty
```

## Auto-Start on Boot

Supervisor should auto-start on boot. Verify:

```bash
systemctl status supervisor
systemctl enable supervisor
```

