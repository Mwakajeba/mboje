# Inventory Import Queue System - Quick Start Guide

## What's New?

The inventory import feature now uses Laravel's queue system to handle bulk imports asynchronously. This prevents timeout errors when importing large CSV files (100+ items).

## Key Benefits

✅ **No More Timeouts** - Process imports in the background without web request limits
✅ **Progress Tracking** - Monitor import progress in real-time
✅ **Better Error Handling** - Detailed error logs for troubleshooting
✅ **Performance** - Batch insertion for faster processing
✅ **Reliability** - Automatic retry logic (3 attempts with backoff)

## Quick Start (5 minutes)

### 1. Queue Already Configured ✅

The queue driver is set to **`sync`** in `.env`:
```
QUEUE_CONNECTION=sync
```

This means imports execute **automatically and immediately** - no manual worker startup needed!

### 2. Just Upload and Import

```bash
# Terminal 1: Start the web server
php artisan serve

# Terminal 2: NOT NEEDED - Queue runs automatically!
# No "php artisan queue:work" required for development
```

### 3. Test the Import

```bash
# Navigate to: /inventory/items
# Click "Import Items" button
# Upload CSV file with columns: name, code, unit_price
# Import runs immediately - you'll see results right away!
# Check status via GET /inventory/items/import-status/{batchId}
```

## CSV Format

Create a CSV file with these columns:

```csv
name,code,unit_price,description,cost_price,unit_of_measure
Laptop,LAP001,1500,Dell Laptop,1000,piece
Monitor,MON001,400,21" Monitor,300,piece
Keyboard,KEY001,50,USB Keyboard,30,piece
```

### Required Columns
- `name` - Item name (max 255 chars)
- `code` - Item code (max 255 chars, unique)
- `unit_price` - Selling price (numeric)

### Optional Columns
- `description`, `cost_price`, `minimum_stock`, `maximum_stock`, `reorder_level`, `unit_of_measure`, `track_stock`, `track_expiry`

## API Endpoints

### Submit Import
```http
POST /inventory/items/import

Content-Type: multipart/form-data

Form Data:
  csv_file: <file>
  category_id: 1
  item_type: product
```

**Response:**
```json
{
  "success": true,
  "message": "Import job queued for 100 items. You will receive a notification when the import is complete.",
  "batch_id": 1
}
```

### Check Status
```http
GET /inventory/items/import-status/1
```

**Response:**
```json
{
  "success": true,
  "batch_id": 1,
  "status": "processing",
  "total_rows": 100,
  "imported_rows": 45,
  "error_rows": 2,
  "progress_percentage": 47,
  "file_name": "items.csv",
  "created_at": "Jan 06, 2026 10:30",
  "completed_at": null,
  "error_log": [
    {"row": 5, "errors": ["Duplicate code: ITEM001"]},
    {"row": 12, "errors": ["Invalid unit_price"]}
  ]
}
```

## Job Status Values

- **pending** - Waiting to be processed by queue worker
- **processing** - Currently being processed
- **completed** - Successfully finished
- **failed** - Failed after 3 retry attempts

## Monitoring Import Progress

### Option 1: Manual Status Check
```bash
# Get ImportBatch record
php artisan tinker
>>> \App\Models\Inventory\ImportBatch::find(1)->toArray()
```

### Option 2: API Endpoint
```bash
curl http://localhost:8000/inventory/items/import-status/1
```

### Option 3: Check Queue Jobs Table
```bash
# See pending jobs
php artisan tinker
>>> \DB::table('jobs')->count()
```

## Common Errors & Solutions

### Error: "CSV file is required"
- Ensure you've selected a CSV file
- File must be `.csv` or `.txt` format
- File must be less than 10MB

### Error: "Missing required columns: name, code, unit_price"
- Check CSV header row has exactly: name, code, unit_price
- Column names are case-sensitive
- No extra spaces in column names

### Error: "Duplicate code: ITEM001"
- Item code already exists in database
- Each item code must be unique
- Check import log for duplicate row number

### Error: "Invalid unit_price"
- unit_price must be a number (e.g., 100, 99.99)
- Not: "100 KES" or "one hundred"
- Use period (.) for decimals, not comma

### Import Takes Too Long
- With `sync` driver, import blocks the request
- For very large files (10MB+), consider switching to `database` driver with Supervisor
- See QUEUE_WORKER_SETUP.md for production setup

### Jobs Not Processing (Production Only)
```bash
# Check if queue worker is running
ps aux | grep "queue:work"

# If not running, start it
php artisan queue:work

# Check for errors in logs
tail -f storage/logs/laravel.log
```

### How to Debug Failed Imports

```bash
php artisan tinker

# Find the failed batch
$batch = \App\Models\Inventory\ImportBatch::where('status', 'failed')->latest()->first();

# View errors
$batch->error_log;

# View batch details
$batch->toArray();
```

## Batch Processing Details

- Items are processed in batches of **100** for optimal performance
- Each batch is a single database transaction
- If a row fails, it's skipped and logged; processing continues
- Total processing time depends on:
  - Number of items (larger = longer)
  - Database performance
  - Server resources
  - Queue load

## Production Deployment

### 1. Set Queue Driver
```bash
QUEUE_CONNECTION=redis  # or database
```

### 2. Configure Supervisor (Process Manager)

Create `/etc/supervisor/conf.d/laravel-queue.conf`:
```ini
[program:laravel-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/smartaccounting/artisan queue:work redis --timeout=3600
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel-queue.log
```

### 3. Reload Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue:*
```

### 4. Verify Queue Worker
```bash
sudo supervisorctl status laravel-queue:*
```

## Troubleshooting Checklist

- [ ] Queue worker is running: `php artisan queue:work`
- [ ] CSV file format is correct (name, code, unit_price)
- [ ] All item codes are unique
- [ ] All numeric fields are valid numbers
- [ ] File size is less than 10MB
- [ ] User has permission to create items
- [ ] Database has enough disk space
- [ ] Check application logs: `storage/logs/laravel.log`
- [ ] Check ImportBatch record: `inventory_import_batches` table
- [ ] Check queue jobs: `jobs` table

## Files Modified

1. **Created:**
   - `app/Jobs/ImportInventoryItems.php` - Queue job handler
   - `app/Models/Inventory/ImportBatch.php` - Batch tracking model
   - `database/migrations/2026_01_06_create_inventory_import_batches_table.php` - Database table
   - `INVENTORY_IMPORT_QUEUE_SYSTEM.md` - Full documentation
   - `INVENTORY_IMPORT_QUEUE_IMPLEMENTATION_CHECKLIST.md` - Implementation status

2. **Modified:**
   - `app/Http/Controllers/Inventory/ItemController.php` - Updated import() method, added importStatus()
   - `routes/web.php` - Added import-status route

## Next Steps

1. Configure queue driver in `.env`
2. Start queue worker: `php artisan queue:work`
3. Test with sample CSV file
4. Monitor imports via status endpoint
5. Set up Supervisor for production

## Related Documentation

- Full Documentation: [INVENTORY_IMPORT_QUEUE_SYSTEM.md](INVENTORY_IMPORT_QUEUE_SYSTEM.md)
- Implementation Checklist: [INVENTORY_IMPORT_QUEUE_IMPLEMENTATION_CHECKLIST.md](INVENTORY_IMPORT_QUEUE_IMPLEMENTATION_CHECKLIST.md)

## Support

For issues:
1. Check `storage/logs/laravel.log` for errors
2. Review ImportBatch record for job status
3. Verify CSV format matches documentation
4. Check queue worker is running

---

**Quick Tip:** Start with small imports (10-20 items) to test the system before doing bulk imports.

**Ready to go!** Your inventory import system is now queue-enabled and production-ready.
