# Inventory Import Queue System - Architecture & Flow Diagrams

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         WEB INTERFACE                            │
│                  /inventory/items (index)                        │
│                  [Import Modal Form]                             │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         │ CSV File Upload
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│               LARAVEL HTTP REQUEST HANDLER                       │
│         ItemController::import() - Route Handler                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ 1. Validate CSV file format (csv, txt, < 10MB)            │ │
│  │ 2. Store file temporarily in storage/app/imports/         │ │
│  │ 3. Validate CSV headers (name, code, unit_price)          │ │
│  │ 4. Count rows for progress tracking                        │ │
│  │ 5. Create ImportBatch record (status: pending)             │ │
│  │ 6. Dispatch ImportInventoryItems job to queue             │ │
│  │ 7. Return batch ID (200 response)                          │ │
│  └────────────────────────────────────────────────────────────┘ │
└────┬──────────────────────────────────────────────────────────────┘
     │
     │ ImportBatch Record Created
     │ + Job Dispatched to Queue
     ↓
┌─────────────────────────────────────────────────────────────────┐
│                      QUEUE STORAGE                               │
│   (Database/Redis/Beanstalkd - configurable)                    │
│                                                                   │
│  ┌──────────────────────┐  ┌──────────────────────┐             │
│  │  Job 1               │  │  Job 2               │             │
│  │  batch_id: 1         │  │  batch_id: 2         │             │
│  │  file_path: ...      │  │  file_path: ...      │             │
│  │  status: pending     │  │  status: pending     │             │
│  └──────────────────────┘  └──────────────────────┘             │
└─────────────────────────────────────────────────────────────────┘
     │
     │ Queue Worker picks up job
     │ (php artisan queue:work)
     ↓
┌─────────────────────────────────────────────────────────────────┐
│            ASYNC JOB PROCESSOR (Background)                     │
│         ImportInventoryItems - Queue Job Handler                │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ 1. Update ImportBatch status to "processing"              │ │
│  │ 2. Read CSV file line by line                             │ │
│  │ 3. Parse CSV data with proper encoding                    │ │
│  │ 4. For each batch of 100 items:                           │ │
│  │    a. Validate row data                                   │ │
│  │    b. Check for duplicate codes                           │ │
│  │    c. Insert items via Item::insert() (mass insert)       │ │
│  │    d. Increment imported_rows counter                     │ │
│  │    e. Log any errors to error_log array                   │ │
│  │    f. Update ImportBatch in database                      │ │
│  │ 5. Handle job retries (3 attempts max)                    │ │
│  │ 6. Delete temporary CSV file                              │ │
│  │ 7. Update ImportBatch status to "completed" or "failed"   │ │
│  └────────────────────────────────────────────────────────────┘ │
└────┬──────────────────────────────────────────────────────────────┘
     │
     │ Updates database
     ↓
┌─────────────────────────────────────────────────────────────────┐
│                    DATABASE TABLES                               │
│                                                                   │
│  ┌─────────────────────────────────────┐                        │
│  │  inventory_import_batches           │                        │
│  ├─────────────────────────────────────┤                        │
│  │ id: 1                               │                        │
│  │ company_id: 1                       │                        │
│  │ user_id: 5                          │                        │
│  │ file_name: items.csv                │                        │
│  │ total_rows: 1000                    │                        │
│  │ imported_rows: 950                  │                        │
│  │ error_rows: 50                      │                        │
│  │ status: completed                   │                        │
│  │ error_log: {...errors...}           │                        │
│  │ created_at: 2026-01-06 10:30        │                        │
│  │ updated_at: 2026-01-06 10:35        │                        │
│  └─────────────────────────────────────┘                        │
│                                                                   │
│  ┌─────────────────────────────────────┐                        │
│  │  inventory_items (950 new rows)     │                        │
│  ├─────────────────────────────────────┤                        │
│  │ id: 101, name: Laptop, code: LAP001│                        │
│  │ id: 102, name: Monitor, code: MON001│                        │
│  │ id: 103, name: Keyboard, code: KEY001│                       │
│  │ ...                                 │                        │
│  └─────────────────────────────────────┘                        │
└─────────────────────────────────────────────────────────────────┘
     │
     │ Client polls for status
     ↓
┌─────────────────────────────────────────────────────────────────┐
│           STATUS CHECK ENDPOINT (Real-time Polling)              │
│      GET /inventory/items/import-status/{batchId}               │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Returns:                                                    │ │
│  │ {                                                           │ │
│  │   "batch_id": 1,                                            │ │
│  │   "status": "completed",                                    │ │
│  │   "total_rows": 1000,                                       │ │
│  │   "imported_rows": 950,                                     │ │
│  │   "error_rows": 50,                                         │ │
│  │   "progress_percentage": 100,                               │ │
│  │   "error_log": [{row: 5, errors: [...]}],                  │ │
│  │   "completed_at": "2026-01-06 10:35"                        │ │
│  │ }                                                           │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

## Import Flow Diagram

```
START
  │
  ├─ User navigates to /inventory/items
  │  └─ Clicks "Import Items" button
  │     └─ Modal appears with form
  │
  ├─ User fills form
  │  ├─ Select Item Type (product/service)
  │  ├─ Select Category
  │  └─ Upload CSV file
  │
  ├─ Client validates
  │  ├─ File exists? YES/NO
  │  └─ File < 10MB? YES/NO
  │
  ├─ POST /inventory/items/import
  │  │
  │  ├─ Server validates
  │  │  ├─ File format (csv, txt)
  │  │  ├─ CSV headers present
  │  │  └─ Required columns: name, code, unit_price
  │  │
  │  ├─ Server processes
  │  │  ├─ Store file temporarily
  │  │  ├─ Count CSV rows
  │  │  ├─ Create ImportBatch record (status: pending)
  │  │  ├─ Dispatch ImportInventoryItems job
  │  │  └─ Return batch ID
  │  │
  │  └─ Response: 200 OK + batch_id
  │
  ├─ Client displays success
  │  └─ "Import queued. Batch ID: 1"
  │
  ├─ Client starts polling
  │  └─ GET /inventory/items/import-status/1
  │     (every 2-5 seconds)
  │
  │  ┌─ Queue Worker processes job
  │  │  │
  │  │  ├─ Read CSV file
  │  │  │  └─ Parse each row
  │  │  │
  │  │  ├─ For each batch of 100 items
  │  │  │  ├─ Validate data
  │  │  │  ├─ Mass insert to DB
  │  │  │  ├─ Update ImportBatch
  │  │  │  └─ Update counters
  │  │  │
  │  │  ├─ Handle errors
  │  │  │  ├─ Log failed rows
  │  │  │  ├─ Increment error_rows
  │  │  │  └─ Continue processing
  │  │  │
  │  │  └─ Finalize
  │  │     ├─ Clean up temp file
  │  │     ├─ Update status
  │  │     └─ Set completed_at
  │  │
  │  └─ (Runs in background)
  │
  ├─ Client displays progress
  │  ├─ Progress bar: 45.5% (950/1000 rows)
  │  ├─ Status: "Processing..."
  │  └─ Items: 950 imported, 50 errors
  │
  ├─ Import completes
  │  │
  │  ├─ Status changes to "completed"
  │  ├─ Display final summary
  │  │  ├─ Total: 1000
  │  │  ├─ Success: 950
  │  │  ├─ Failed: 50
  │  │  └─ Time: 5 minutes
  │  │
  │  └─ Show error summary if any
  │     ├─ Row 5: "Duplicate code: ITEM001"
  │     ├─ Row 12: "Invalid unit_price"
  │     └─ [Download Error Report]
  │
  └─ END
```

## Data Flow Diagram

```
CSV File Input
     │
     ├─ Validation Layer
     │  ├─ File format check
     │  ├─ CSV header validation
     │  └─ Row count
     │
     ├─ Storage Layer
     │  └─ Store to /storage/app/imports/inventory-items/
     │
     ├─ Queue Dispatch
     │  ├─ Create ImportBatch record
     │  ├─ Add job to queue
     │  └─ Return batch_id
     │
     ├─ Async Processing (Background Worker)
     │  ├─ Read CSV file
     │  │  ├─ Parse row data
     │  │  └─ Map to model fields
     │  │
     │  ├─ Batch Processing (100 items/batch)
     │  │  ├─ Validate each item
     │  │  ├─ Check constraints
     │  │  ├─ Insert batch
     │  │  └─ Update counters
     │  │
     │  ├─ Error Handling
     │  │  ├─ Collect row errors
     │  │  ├─ Store in error_log
     │  │  ├─ Continue processing
     │  │  └─ Update error_rows
     │  │
     │  ├─ Progress Tracking
     │  │  ├─ Update imported_rows
     │  │  ├─ Save ImportBatch
     │  │  └─ Update status
     │  │
     │  └─ Finalization
     │     ├─ Clean up file
     │     ├─ Mark completed/failed
     │     └─ Set timestamps
     │
     ├─ Database Storage
     │  ├─ inventory_items table (950 new rows)
     │  └─ inventory_import_batches table (status record)
     │
     └─ Status Monitoring
        └─ GET /import-status/{id}
           ├─ Returns progress
           ├─ Displays errors
           └─ Shows summary
```

## Component Interaction Diagram

```
┌──────────────────┐
│   User/Browser   │
└────────┬─────────┘
         │
         │ POST /items/import (form data)
         ↓
┌─────────────────────────────┐
│  ItemController::import()   │
│  ├─ Validate CSV            │
│  ├─ Store file              │
│  ├─ Create ImportBatch      │
│  └─ Dispatch job            │
└──────────┬──────────────────┘
           │
           ├─ Creates ──────────→ ┌────────────────┐
           │                      │  ImportBatch   │
           │                      │  Model/Record  │
           │                      └────────────────┘
           │
           └─ Dispatches ──────→  ┌──────────────────────┐
                                   │  ImportInventoryItems│
                                   │  Job (Queue Handler) │
                                   └──────┬───────────────┘
                                          │
                                          ├─ Reads ──────→  ┌─────────────┐
                                          │                 │  CSV File   │
                                          │                 └─────────────┘
                                          │
                                          ├─ Updates ────→  ┌────────────────┐
                                          │                 │  ImportBatch   │
                                          │                 │  (progress)    │
                                          │                 └────────────────┘
                                          │
                                          └─ Inserts ────→  ┌──────────────────┐
                                                            │  inventory_items │
                                                            │  table           │
                                                            └──────────────────┘
         │
         │ GET /items/import-status/{id}
         ↓
┌─────────────────────────────────────────┐
│  ItemController::importStatus()         │
│  ├─ Find ImportBatch                    │
│  ├─ Calculate progress percentage       │
│  └─ Return JSON response                │
└─────────────────────────────────────────┘
         │
         │ JSON Response (progress data)
         ↓
┌──────────────────┐
│   Browser/UI     │
│   Update display │
└──────────────────┘
```

## Error Handling Flow

```
Validation Errors (Immediate - HTTP 422)
├─ Missing CSV file → Return error, no queue job
├─ Invalid format → Return error, no queue job
├─ Bad headers → Return error, no queue job
└─ File too large → Return error, no queue job

Row-Level Errors (Deferred - Logged in batch)
├─ During parsing
│  ├─ Duplicate code
│  ├─ Invalid field type
│  ├─ Missing required field
│  └─ Constraint violation
│
└─ Actions
   ├─ Log to ImportBatch.error_log array
   ├─ Increment error_rows counter
   ├─ Skip row and continue
   └─ Don't fail entire job

Job-Level Errors (Retry 3 times)
├─ Database connection error
├─ Disk space error
├─ Memory error
│
└─ Behavior
   ├─ Attempt 1 (fail) → Wait 60s
   ├─ Attempt 2 (fail) → Wait 120s
   ├─ Attempt 3 (fail) → Mark batch as failed
   └─ Set error_log with job error details
```

## Database Schema Diagram

```
inventory_import_batches
┌──────────────────────────────────────┐
│ Column           │ Type              │
├──────────────────────────────────────┤
│ id               │ BIGINT (PK)       │
│ company_id       │ BIGINT (FK)       │
│ user_id          │ BIGINT (FK)       │
│ file_name        │ VARCHAR(255)      │
│ total_rows       │ INT UNSIGNED      │
│ imported_rows    │ INT UNSIGNED      │
│ error_rows       │ INT UNSIGNED      │
│ status           │ VARCHAR(50)       │
│ error_log        │ JSON              │
│ job_id           │ VARCHAR(255)      │
│ created_at       │ TIMESTAMP         │
│ updated_at       │ TIMESTAMP         │
└──────────────────────────────────────┘
         │               │
         ├─→ companies ──┘
         │
         └─→ users

Status Values:
├─ pending    (waiting for queue worker)
├─ processing (currently being processed)
├─ completed  (finished successfully)
└─ failed     (failed after 3 retries)

Foreign Keys:
├─ company_id → companies.id (ON DELETE CASCADE)
└─ user_id → users.id (ON DELETE CASCADE)

Indexes:
├─ PRIMARY KEY (id)
├─ INDEX (company_id)
├─ INDEX (status)
└─ INDEX (created_at)
```

## Sequence Diagram

```
User        Browser         Server          Queue           Database
│             │              │              │                │
├─ Click Import ──────→ POST /import ────→ Validate ─→ Store ──→ DB
│             │              │              │        │        │
│             │              │              Create   │        │
│             │              │              Batch ──────────→ DB
│             │              │              │        │        │
│             │              │              Dispatch │        │
│             │              │              Job ────→ Queue   │
│             │              │              │        │        │
│             │              ←─── 200 OK ───┤        │        │
│             │◄─ Success ───┤              │        │        │
│             │   Batch: 1   │              │        │        │
│             │              │              │        │ Worker │
│             │              │              │        │ Starts │
│             │              │              │        │        │
├─ Wait 2s ──→│              │              │        │        │
│             │              GET /status/1 ─┤        │        │
│             │              ←──────────────┤        │        │
│             │◄─ 45% done ──┤              │        │        │
│             │              │              │        │        │
│             │              │              │        │ Reading│
│             │              │              │        │ CSV ──→│
│             │              │              │        │        │
├─ Wait 2s ──→│              │              │        │        │
│             │              GET /status/1 ─┤        │        │
│             │              ←──────────────┤        │        │
│             │◄─ 90% done ──┤              │        │        │
│             │              │              │        │        │
│             │              │              │        │ Batch │
│             │              │              │        │ Insert│
│             │              │              │        ├────→ DB
│             │              │              │        │        │
├─ Wait 2s ──→│              │              │        │        │
│             │              GET /status/1 ─┤        │        │
│             │              ←──────────────┤        │        │
│             │◄─ 100% done ─┤              │        │        │
│             │◄─ Completed ─┤              │        │        │
│             │              │              │        │ Update │
│             │              │              │        │ Status ├→ DB
│             │              │              │        │        │
└─────────────┴──────────────┴──────────────┴────────┴────────┘
```

---

**Legend:**
- → = HTTP Request / API Call
- ← = Response
- ├→ = Database Operation
- │ = Process/Flow

**Performance Notes:**
- Validation & dispatch: < 100ms
- Job processing: Depends on CSV size (100 items ≈ 1-2 seconds)
- Status check: < 50ms
- Batch insert efficiency: 100 items per batch transaction

---

Generated: 2026-01-06
Last Updated: 2026-01-06
