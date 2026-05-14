# Automatic Queue Worker Setup - Alternatives

You have **5 alternatives** to manually running `php artisan queue:work`. Choose the best option for your setup:

---

## Option 1: Supervisor (Recommended for Production) ⭐

**Best for:** Production servers, automatic restart, reliability

### Setup Steps:

1. **Copy the config file:**
   ```bash
   sudo cp /home/anselim/smartaccounting/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf
   ```

2. **Update the paths in the config** (if different):
   ```bash
   sudo nano /etc/supervisor/conf.d/laravel-worker.conf
   ```
   Change `/home/anselim/smartaccounting` to your actual project path if different.

3. **Reload and start:**
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-worker:*
   ```

4. **Check status:**
   ```bash
   sudo supervisorctl status laravel-worker:*
   ```

**Benefits:**
- ✅ Auto-starts on server boot
- ✅ Auto-restarts if worker crashes
- ✅ Runs in background
- ✅ Can run multiple workers
- ✅ Production-ready

---

## Option 2: Systemd Service (Alternative to Supervisor)

**Best for:** Modern Linux systems, systemd-based servers

### Setup Steps:

1. **Copy the service file:**
   ```bash
   sudo cp /home/anselim/smartaccounting/systemd/laravel-worker.service /etc/systemd/system/laravel-worker.service
   ```

2. **Update the paths** in the service file:
   ```bash
   sudo nano /etc/systemd/system/laravel-worker.service
   ```
   Change `/home/anselim/smartaccounting` to your actual project path.

3. **Reload systemd and start:**
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable laravel-worker
   sudo systemctl start laravel-worker
   ```

4. **Check status:**
   ```bash
   sudo systemctl status laravel-worker
   ```

**Benefits:**
- ✅ Auto-starts on boot
- ✅ Auto-restarts on failure
- ✅ Integrated with systemd
- ✅ Easy to manage

---

## Option 3: Lower the Threshold (Process More Items Synchronously)

**Best for:** If you rarely have 50+ items, want simpler setup

### Setup Steps:

1. **Edit `.env` file:**
   ```bash
   nano .env
   ```

2. **Add this line:**
   ```env
   PURCHASE_INVOICE_JOB_THRESHOLD=100
   ```
   Or set to `9999` to almost always process synchronously.

3. **Clear config cache:**
   ```bash
   php artisan config:clear
   ```

**How it works:**
- If threshold = `100`: Only invoices with 100+ items use jobs
- If threshold = `9999`: Almost all invoices process synchronously (no queue worker needed)
- If threshold = `0`: All invoices use jobs (requires queue worker)

**Benefits:**
- ✅ No queue worker needed for most invoices
- ✅ Simpler setup
- ⚠️ May still hit limits with very large invoices (100+ items)

---

## Option 4: Use Sync Queue Driver (No Worker Needed)

**Best for:** Development, small deployments, testing

### Setup Steps:

1. **Edit `.env` file:**
   ```bash
   nano .env
   ```

2. **Change queue connection:**
   ```env
   QUEUE_CONNECTION=sync
   ```

3. **Set threshold to 0:**
   ```env
   PURCHASE_INVOICE_JOB_THRESHOLD=0
   ```

4. **Clear config:**
   ```bash
   php artisan config:clear
   ```

**How it works:**
- Jobs run immediately (synchronously)
- No queue worker needed
- ⚠️ **Warning:** This defeats the purpose of async processing - may timeout on large batches

**Benefits:**
- ✅ No queue worker setup
- ✅ Immediate processing
- ⚠️ May timeout on very large invoices

---

## Option 5: Cron-Based Processing (Less Ideal)

**Best for:** Simple setups where supervisor/systemd not available

### Setup Steps:

1. **Edit crontab:**
   ```bash
   crontab -e
   ```

2. **Add this line** (runs every minute):
   ```cron
   * * * * * cd /home/anselim/smartaccounting && php artisan queue:work --stop-when-empty --tries=3 --timeout=300 >> /dev/null 2>&1
   ```

**Benefits:**
- ✅ Simple setup
- ✅ No additional software needed
- ⚠️ Jobs may wait up to 1 minute before processing
- ⚠️ Less efficient than continuous worker

---

## Quick Comparison

| Option | Auto-Start | Auto-Restart | Best For | Setup Difficulty |
|--------|-----------|--------------|----------|------------------|
| **Supervisor** | ✅ | ✅ | Production | Medium |
| **Systemd** | ✅ | ✅ | Modern Linux | Medium |
| **Lower Threshold** | N/A | N/A | Small batches | Easy |
| **Sync Driver** | N/A | N/A | Development | Easy |
| **Cron** | ✅ | ❌ | Simple setups | Easy |

---

## Recommended Setup

**For Production:**
1. Use **Supervisor** (Option 1) or **Systemd** (Option 2)
2. Set threshold to `50` (default) or adjust based on your needs

**For Development:**
1. Use **Lower Threshold** (Option 3) with `PURCHASE_INVOICE_JOB_THRESHOLD=9999`
2. Or use **Sync Driver** (Option 4) for immediate testing

---

## Troubleshooting

### Check if worker is running:
```bash
# Supervisor
sudo supervisorctl status laravel-worker:*

# Systemd
sudo systemctl status laravel-worker

# Manual check
ps aux | grep "queue:work"
```

### View logs:
```bash
tail -f storage/logs/worker.log
tail -f storage/logs/laravel.log | grep ProcessPurchaseInvoiceItemsJob
```

### Restart worker:
```bash
# Supervisor
sudo supervisorctl restart laravel-worker:*

# Systemd
sudo systemctl restart laravel-worker
```

---

## Which Option Should I Choose?

- **Production server with many large invoices?** → Use **Supervisor** or **Systemd**
- **Small invoices (< 50 items)?** → Use **Lower Threshold** (set to 100+)
- **Development/testing?** → Use **Sync Driver** or **Lower Threshold**
- **Simple VPS without supervisor?** → Use **Cron** or **Lower Threshold**

