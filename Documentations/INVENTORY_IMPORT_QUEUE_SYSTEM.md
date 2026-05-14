# Inventory Import Queue System Documentation

## Overview

The inventory import system has been enhanced to use Laravel's queue system to handle bulk item imports asynchronously, preventing timeouts on large CSV files.

## Architecture

### Components

#### 1. **ImportInventoryItems Job** ([app/Jobs/ImportInventoryItems.php](app/Jobs/ImportInventoryItems.php))
- **Purpose**: Asynchronously processes CSV imports in the background
- **Queue Driver**: Supports database, Redis, or sync drivers
- **Timeout**: 3600 seconds (1 hour)
- **Retries**: 3 attempts with exponential backoff (60s, 120s, 300s)
- **Batch Size**: Processes items in batches of 100 for optimal performance

**Key Features:**
```php
// Read and validate CSV
// Process items in batches of 100
// Update ImportBatch record with progress
// Log errors for each failed row
// Clean up temporary files on completion
```

#### 2. **ImportBatch Model** ([app/Models/Inventory/ImportBatch.php](app/Models/Inventory/ImportBatch.php))
- **Purpose**: Tracks the status and progress of import batches
- **Fields:**
  - `company_id` - Company performing the import
  - `user_id` - User who initiated the import
  - `file_name` - Original CSV file name
  - `total_rows` - Total rows in CSV
  - `imported_rows` - Successfully imported count
  - `error_rows` - Failed import count
  - `status` - pending, processing, completed, failed
  - `error_log` - JSON array of row-level errors
  - `job_id` - Laravel job ID for tracking

**Status Transitions:**
```
pending → processing → completed
         ↓
         failed
```

**Helper Methods:**
- `markAsProcessing()` - Set status to processing
- `markAsCompleted($imported, $errors)` - Finalize with success
- `markAsFailed($errorLog)` - Mark as failed with error details

#### 3. **ItemController Import Method** ([app/Http/Controllers/Inventory/ItemController.php](app/Http/Controllers/Inventory/ItemController.php))
- **Route**: `POST /inventory/items/import`
- **Validates:**
  - CSV file exists and is valid format (csv, txt)
  - Max 10MB file size
  - CSV header has required columns: `name`, `code`, `unit_price`

**Process:**
```
1. Validate CSV and headers
2. Store file temporarily
3. Count rows for batch record
4. Create ImportBatch record (status: pending)
5. Dispatch ImportInventoryItems job to queue
6. Return batchId for progress tracking
```

#### 4. **Import Status Endpoint** ([app/Http/Controllers/Inventory/ItemController.php#L941](app/Http/Controllers/Inventory/ItemController.php#L941))
- **Route**: `GET /inventory/items/import-status/{batchId}`
- **Returns:** Current import progress and status

**Response:**
```json
{
  "success": true,
  "batch_id": 1,
  "status": "processing",
  "total_rows": 1000,
  "imported_rows": 450,
  "error_rows": 5,
  "progress_percentage": 45.5,
  "file_name": "inventory_items.csv",
  "created_at": "Jan 06, 2026 10:30",
  "completed_at": null,
  "error_log": [
    {"row": 5, "errors": ["Duplicate code: ITEM001"]},
    {"row": 12, "errors": ["Invalid unit_price"]}
  ]
}
```

## CSV Format

### Required Columns
- `name` - Item name (max 255 chars)
- `code` - Item code (max 255 chars, must be unique)
- `unit_price` - Selling price (numeric)

### Optional Columns
- `description` - Item description
- `cost_price` - Item cost (numeric)
- `minimum_stock` - Minimum stock level (numeric)
- `maximum_stock` - Maximum stock level (numeric)
- `reorder_level` - Reorder point (numeric)
- `unit_of_measure` - Unit (e.g., kg, liter, piece)
- `track_stock` - 1 or 0 for stock tracking
- `track_expiry` - 1 or 0 for expiry tracking

### Example CSV
```csv
name,code,unit_price,description,cost_price,unit_of_measure
Laptop,LAP001,1500,Dell Laptop,1000,piece
Monitor,MON001,400,21" Monitor,300,piece
Keyboard,KEY001,50,USB Keyboard,30,piece
```

## Usage Flow

### Frontend (Import Modal)
```
1. User opens import modal in inventory items page
2. Selects item type (product/service)
3. Selects item category
4. Uploads CSV file
5. System validates and displays confirmation
6. User confirms import
7. Job is queued and batch ID is returned
```

### Backend (Queue Processing)
```
1. Request hits POST /inventory/items/import
2. CSV is validated and stored temporarily
3. ImportBatch record created (status: pending)
4. ImportInventoryItems job dispatched to queue
5. Job processes CSV in batches of 100 items
6. Each successful item is inserted into database
7. Failed rows are logged to ImportBatch.error_log
8. ImportBatch status updated to completed/failed
9. Temporary file cleaned up
```

### Progress Monitoring
```
Frontend polls GET /inventory/items/import-status/{batchId}
Response shows:
- Progress percentage (imported_rows + error_rows) / total_rows * 100
- Current status (pending, processing, completed, failed)
- Number of successful and failed items
- Detailed error messages for debugging
```

## Queue Configuration

### Environment Setup
Set in `.env`:
```
QUEUE_CONNECTION=database
# or: redis, sync, beanstalkd, sqs
```

### Supported Drivers
- **database** - Uses a jobs table (recommended for simple setups)
- **redis** - Uses Redis for better performance
- **sync** - Processes immediately (not recommended for large imports)

### Process Queue (Development)
```bash
# Process one job
php artisan queue:work --once

# Process jobs continuously
php artisan queue:work

# With specific connection/timeout
php artisan queue:work database --timeout=3600
```

### Process Queue (Production)
Use a process manager like Supervisor to keep queue worker running:
```bash
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work database --timeout=3600
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stopwaitsecs=3600
```

## Error Handling

### Validation Errors (Returned Immediately)
- Missing required CSV columns
- Invalid file format or size
- Permission issues

### Row-Level Errors (Logged to ImportBatch)
- Duplicate item code
- Invalid numeric fields
- Missing required data
- Database constraint violations

### Job Failures
- Automatic retry 3 times with backoff
- If all retries fail, batch marked as failed
- Error details logged for investigation

## Security

### Authorization
- Only users with `create` permission on Items can import
- Import is scoped to user's company
- ImportBatch is company-isolated

### File Handling
- Files stored in `storage/app/imports/inventory-items/`
- Temporary files cleaned up after processing
- File size limited to 10MB

### Data Validation
- CSV headers validated before queuing
- Each row validated during processing
- Invalid rows skipped with error logging
- Database constraints enforced

## Performance Considerations

### Batch Size
- Items processed in batches of 100
- Uses mass insert (`Item::insert()`) for efficiency
- Each batch creates one database transaction

### Database Load
- Batch insertion reduces query count
- Indexes on `code` and `company_id` used for lookups
- Duplicate check on item code

### Memory Usage
- Large CSV files read line-by-line
- Batch processing keeps memory footprint low
- ~2-3MB typical for 1000 items

## Testing the System

### Manual Test (Development)
```bash
# 1. Start queue worker
php artisan queue:work --once

# 2. Upload CSV via web UI
# POST /inventory/items/import

# 3. Check import status
# GET /inventory/items/import-status/1

# 4. Check ImportBatch record
php artisan tinker
>>> \App\Models\Inventory\ImportBatch::find(1)->toArray()
```

### CSV Test Template
```csv
name,code,unit_price,description,cost_price
Test Item 1,TEST001,100,Description 1,75
Test Item 2,TEST002,200,Description 2,150
Test Item 3,TEST003,300,Description 3,225
```

## Troubleshooting

### Jobs Not Processing
1. Check queue worker is running: `php artisan queue:work`
2. Check jobs table: `SELECT * FROM jobs;`
3. Check job status in ImportBatch: `status` field
4. Review logs: `storage/logs/laravel.log`

### Import Fails Silently
1. Check ImportBatch.error_log for details
2. Verify CSV format matches documentation
3. Check database constraints on inventory_items
4. Verify user has create permission on Items

### Slow Processing
1. Check database indexes on inventory_items
2. Monitor database query performance
3. Consider adjusting batch size in ImportInventoryItems job
4. Profile with Laravel Debugbar if available

### File Not Found
1. Check temp storage path exists: `storage/app/imports/inventory-items/`
2. Verify file permissions on storage directory
3. Check disk space availability

## Routes

```php
// Submit import
POST /inventory/items/import

// Check import status
GET /inventory/items/import-status/{batchId}

// Download import template
GET /inventory/items/download-template
```

## Database Schema

### ImportBatch Table
```sql
CREATE TABLE inventory_import_batches (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  company_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  total_rows INT UNSIGNED DEFAULT 0,
  imported_rows INT UNSIGNED DEFAULT 0,
  error_rows INT UNSIGNED DEFAULT 0,
  status VARCHAR(50) DEFAULT 'pending', -- pending, processing, completed, failed
  error_log JSON NULL,
  job_id VARCHAR(255) NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_company_id (company_id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
);
```

## Future Enhancements

1. **Batch Update** - Support updating existing items
2. **Template Validation** - Validate CSV before upload
3. **Preview** - Show first 5 rows before processing
4. **Notifications** - Email user when import completes
5. **Export Results** - Download import success/failure report
6. **Scheduled Imports** - Schedule imports for off-peak hours
7. **Item Relationships** - Import item variants and bundles
8. **Stock Levels** - Initialize opening balances during import
9. **Webhooks** - Notify external systems on completion
10. **API Import** - Direct API import without web UI

## Related Files

- [ItemController](app/Http/Controllers/Inventory/ItemController.php)
- [ImportInventoryItems Job](app/Jobs/ImportInventoryItems.php)
- [ImportBatch Model](app/Models/Inventory/ImportBatch.php)
- [Migration](database/migrations/2026_01_06_create_inventory_import_batches_table.php)
- [Routes](routes/web.php#L1782)

## Changelog

### Version 1.0 (2026-01-06)
- Initial implementation with queue-based async processing
- Batch insertion for performance
- ImportBatch tracking model
- Status monitoring endpoint
- Error logging and retry logic

---

**Last Updated:** 2026-01-06
**Maintained By:** Development Team
