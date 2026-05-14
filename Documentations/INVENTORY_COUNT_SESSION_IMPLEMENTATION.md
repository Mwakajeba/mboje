# Inventory Count Session Module - Complete Implementation Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [Database Structure](#database-structure)
3. [Workflow States](#workflow-states)
4. [Complete Workflow](#complete-workflow)
5. [Key Features](#key-features)
6. [API Endpoints](#api-endpoints)
7. [Models & Relationships](#models--relationships)
8. [Business Logic](#business-logic)

---

## Overview

The Inventory Count Session module provides a complete cycle counting solution with the following capabilities:
- **Count Period Management**: Create and manage count periods (Monthly, Quarterly, Annual, Cycle Count)
- **Session Management**: Create multiple count sessions per period
- **Blind Count Support**: Hide system quantities for unbiased counting
- **Real-time Variance Calculation**: Live variance updates as quantities are entered
- **Variance Analysis**: Automatic variance calculation with high-value detection
- **Adjustment Processing**: Create inventory adjustments from variances
- **Export Capabilities**: PDF and Excel export of counting sheets

---

## Database Structure

### 1. `inventory_count_periods`
```sql
- id
- company_id
- branch_id
- period_name
- count_type (monthly, quarterly, annual, cycle_count)
- frequency
- count_start_date
- count_end_date
- inventory_location_id (nullable)
- responsible_staff_id
- status (draft, in_progress, completed, cancelled)
- notes
- timestamps
```

### 2. `inventory_count_sessions`
```sql
- id
- count_period_id
- company_id
- inventory_location_id
- session_number (unique)
- snapshot_date (when system qty was captured)
- count_start_time (nullable)
- count_end_time (nullable)
- status (draft, frozen, counting, completed, cancelled)
- is_blind_count (boolean)
- created_by
- supervisor_id (nullable)
- notes
- timestamps
```

### 3. `inventory_count_entries`
```sql
- id
- count_session_id
- item_id
- inventory_location_id
- bin_location (nullable)
- system_quantity
- physical_quantity (nullable)
- recount_quantity (nullable)
- condition (good, damaged, expired, obsolete, missing)
- lot_number (nullable)
- batch_number (nullable)
- expiry_date (nullable)
- remarks (nullable)
- counted_by (nullable)
- counted_at (nullable)
- recounted_by (nullable)
- recounted_at (nullable)
- verified_by (nullable)
- verified_at (nullable)
- status (pending, counted, verified)
- timestamps
```

### 4. `inventory_count_variances`
```sql
- id
- count_entry_id
- item_id
- system_quantity
- physical_quantity
- variance_quantity (physical - system)
- variance_percentage
- unit_cost
- variance_value (abs(variance_qty * unit_cost))
- variance_type (positive, negative, zero)
- is_high_value (boolean)
- requires_recount (boolean)
- recount_tolerance_percentage (default: 5%)
- recount_tolerance_value (default: 50,000)
- investigation_notes (nullable)
- status (pending, resolved, requires_recount)
- timestamps
```

### 5. `inventory_count_adjustments`
```sql
- id
- count_session_id
- variance_id
- item_id
- inventory_location_id
- adjustment_number
- adjustment_quantity
- adjustment_value
- adjustment_type (increase, decrease)
- reason_code
- reason_description
- supporting_documents (json)
- supervisor_comments (nullable)
- finance_comments (nullable)
- status (pending_approval, approved, rejected, posted)
- created_by
- approved_by (nullable)
- approved_at (nullable)
- posted_by (nullable)
- posted_at (nullable)
- journal_id (nullable)
- movement_id (nullable)
- timestamps
```

---

## Workflow States

### Count Session Status Flow:
```
DRAFT → FROZEN → COUNTING → COMPLETED
  ↓        ↓         ↓          ↓
  └────────┴─────────┴──────────┘
         (Can be cancelled at any time)
```

### Count Entry Status Flow:
```
PENDING → COUNTED → VERIFIED
```

---

## Complete Workflow

### STEP 1: Create Count Period
**Route**: `GET /inventory/counts/periods/create`  
**Controller**: `InventoryCountController@createPeriod`  
**View**: `resources/views/inventory/counts/periods/create.blade.php`

**User Actions**:
1. Navigate to Inventory Count Management
2. Click "Create Count Period"
3. Fill in:
   - Period Name (e.g., "Monthly Count - January 2025")
   - Count Type (Monthly, Quarterly, Annual, Cycle Count)
   - Frequency (if applicable)
   - Start Date & End Date
   - Location (optional - can be "All Locations")
   - Responsible Staff
   - Notes

**System Actions**:
- Validates input
- Creates `CountPeriod` record
- Status: `draft`

---

### STEP 2: Create Count Session
**Route**: `GET /inventory/counts/sessions/create/{periodEncodedId}`  
**Controller**: `InventoryCountController@createSession`  
**View**: `resources/views/inventory/counts/sessions/create.blade.php`

**User Actions**:
1. From Period Details page, click "Create Session"
2. Select:
   - Location (with branch name in parentheses)
   - Supervisor (optional)
   - Blind Count checkbox (optional)
   - Notes (optional)

**System Actions** (`storeSession`):
```php
1. Validates location, supervisor, blind count flag
2. Generates unique session number: CNT-YYYYMMDD-XXXX
3. Creates CountSession with:
   - status: 'draft'
   - snapshot_date: now()
   - is_blind_count: from checkbox
4. Calls generateCountingSheets($session)
5. Redirects to session details page
```

**generateCountingSheets() Logic**:
```php
1. Gets all active items that track stock for the company
2. For each item:
   - Calculates system_quantity from movements (Item::getStockAtLocation())
   - Falls back to StockLevel if movements return 0
   - If blind_count = false: Include ALL items (even 0 stock)
   - If blind_count = true: Only include items with stock > 0
3. Creates CountEntry for each item with:
   - system_quantity (calculated)
   - status: 'pending'
```

---

### STEP 3: Freeze Session
**Route**: `POST /inventory/counts/sessions/{encodedId}/freeze`  
**Controller**: `InventoryCountController@freezeSession`  
**Confirmation**: SweetAlert2 dialog

**User Actions**:
1. Review session details
2. Click "Freeze Session" button
3. Confirm in SweetAlert2 dialog

**System Actions**:
```php
1. Validates session is in 'draft' status
2. Updates session:
   - status: 'frozen'
   - snapshot_date: now() (re-capture current stock)
   - count_start_time: now()
3. Updates all entries with current system quantities:
   - Recalculates from movements
   - Updates system_quantity field
4. Returns success message
```

**Purpose**: 
- Locks the session for counting
- Captures current stock snapshot
- Prevents further modifications until counting starts

---

### STEP 4: Start Counting
**Route**: `POST /inventory/counts/sessions/{encodedId}/start-counting`  
**Controller**: `InventoryCountController@startCounting`

**User Actions**:
1. Click "Start Counting" button (appears when status = 'frozen')

**System Actions**:
```php
1. Validates session is in 'frozen' status
2. Updates session:
   - status: 'counting'
3. Returns success message
```

**Result**: 
- Physical quantity input fields become editable
- Real-time variance calculation activates

---

### STEP 5: Enter Physical Quantities
**Route**: `POST /inventory/counts/entries/{encodedId}/update-physical-qty`  
**Controller**: `InventoryCountController@updatePhysicalQuantity`  
**View**: Real-time updates in `show.blade.php`

**User Actions**:
1. Navigate to session details page
2. Find item in "Counting Entries" table
3. Type physical quantity in "Physical Qty" column
4. Click outside field or press Tab (auto-saves)

**System Actions** (Real-time):
```javascript
// Frontend (show.blade.php)
1. On 'input' event:
   - Calculates variance = physical_qty - system_qty
   - Updates variance badge in real-time
   - Color coding:
     * Green (success) = Positive variance
     * Red (danger) = Negative variance
     * Gray (secondary) = Zero variance

2. On 'blur' event:
   - Sends AJAX request to update-physical-qty
   - Updates entry:
     * physical_quantity
     * counted_by = current user
     * counted_at = now()
     * status = 'counted'
   - Shows success toast
   - Reloads page to refresh data
```

**Backend Actions**:
```php
1. Validates:
   - Entry exists and belongs to user's company
   - Session status is 'frozen' or 'counting'
   - physical_quantity is numeric and >= 0

2. Updates CountEntry:
   - physical_quantity
   - counted_by
   - counted_at
   - status = 'counted'

3. Returns JSON response
```

**Blind Count Behavior**:
- **Disabled**: System Qty column visible, variance calculated in real-time
- **Enabled**: System Qty column hidden, variance still calculated but not displayed

---

### STEP 6: Complete Counting
**Route**: `POST /inventory/counts/sessions/{encodedId}/complete-counting`  
**Controller**: `InventoryCountController@completeCounting`  
**Confirmation**: SweetAlert2 dialog

**User Actions**:
1. After entering all physical quantities
2. Click "Complete Counting" button
3. Confirm in SweetAlert2 dialog

**System Actions**:
```php
1. Validates session is in 'counting' status
2. Updates session:
   - status: 'completed'
   - count_end_time: now()

3. Calls calculateVariances($session):
   For each entry with physical_quantity:
   - Calculates variance_quantity = physical - system
   - Calculates variance_percentage = (variance / system) * 100
   - Gets unit_cost from item
   - Calculates variance_value = abs(variance_qty * unit_cost)
   - Determines variance_type (positive/negative/zero)
   - Checks if high_value:
     * variance_value >= 50,000 OR
     * abs(variance_percentage) >= 5%
   - Creates/updates CountVariance record
   - Sets requires_recount = true if high_value

4. Returns success message
```

**Variance Calculation Logic**:
```php
variance_quantity = physical_quantity - system_quantity
variance_percentage = (variance_quantity / system_quantity) * 100
variance_value = abs(variance_quantity * unit_cost)
is_high_value = (variance_value >= 50000) OR (abs(variance_percentage) >= 5)
requires_recount = is_high_value
```

---

### STEP 7: View Variances
**Route**: `GET /inventory/counts/sessions/{encodedId}/variances`  
**Controller**: `InventoryCountController@showVariances`  
**View**: `resources/views/inventory/counts/sessions/variances.blade.php`

**Features**:
- Lists all variances with details
- Highlights high-value variances
- Shows items requiring recount
- Allows investigation notes
- Links to create adjustments

---

### STEP 8: Create Adjustments (Optional)
**Route**: `POST /inventory/counts/adjustments`  
**Controller**: `InventoryCountController@storeAdjustment`

**User Actions**:
1. From variances page, select variances to adjust
2. Fill adjustment details:
   - Reason code
   - Reason description
   - Supporting documents
   - Supervisor comments

**System Actions**:
```php
1. Creates CountAdjustment record
2. Status: 'pending_approval'
3. Links to variance and session
4. Waits for approval workflow
```

---

### STEP 9: Post Adjustments (Optional)
**Route**: `POST /inventory/counts/adjustments/{encodedId}/post`  
**Controller**: `InventoryCountController@postAdjustment`

**System Actions**:
```php
1. Validates adjustment is approved
2. Creates Journal Entry:
   - Dr/Cr Inventory Asset Account
   - Cr/Dr Inventory Adjustment Expense/Income
3. Creates Movement record:
   - movement_type: 'adjustment_in' or 'adjustment_out'
   - Updates stock levels
4. Updates adjustment:
   - status: 'posted'
   - journal_id
   - movement_id
   - posted_by, posted_at
```

---

## Key Features

### 1. Blind Count
**Purpose**: Prevent bias by hiding system quantities from counters

**Implementation**:
- `is_blind_count` flag on CountSession
- System Qty column hidden in views when enabled
- System Qty excluded from PDF/Excel exports
- Variance still calculated but not displayed during counting

**When to Use**:
- Annual full inventory count
- High-value items
- When accuracy is critical

### 2. Real-Time Variance Calculation
**Implementation**:
```javascript
// Frontend JavaScript
$(document).on('input keyup change', '.physical-qty-input', function() {
    const physicalQty = parseFloat($(this).val()) || 0;
    const systemQty = parseFloat($(this).data('system-qty')) || 0;
    const variance = physicalQty - systemQty;
    
    // Update variance badge with color coding
    // Green = positive, Red = negative, Gray = zero
});
```

### 3. System Quantity Calculation
**Primary Method**: From Movements
```php
$systemQuantity = $item->getStockAtLocation($locationId);
// Calculates: SUM(in) - SUM(out) from inventory_movements
```

**Fallback Method**: StockLevel Table
```php
if ($systemQuantity == 0) {
    $stockLevel = StockLevel::where('item_id', $item->id)
        ->where('inventory_location_id', $locationId)
        ->first();
    if ($stockLevel && $stockLevel->quantity > 0) {
        $systemQuantity = $stockLevel->quantity;
    }
}
```

### 4. High-Value Variance Detection
**Thresholds**:
- **Value Threshold**: TZS 50,000
- **Percentage Threshold**: 5%

**Logic**:
```php
$isHighValue = ($varianceValue >= 50000) || (abs($variancePercentage) >= 5);
$requiresRecount = $isHighValue;
```

### 5. Entry Status Tracking
- **pending**: Entry created, not yet counted
- **counted**: Physical quantity entered
- **verified**: Entry verified by supervisor

---

## API Endpoints

### Count Periods
```
GET    /inventory/counts/periods/create          - Create period form
POST   /inventory/counts/periods                - Store period
GET    /inventory/counts/periods/{encodedId}    - Show period details
```

### Count Sessions
```
GET    /inventory/counts/sessions/create/{periodEncodedId}  - Create session form
POST   /inventory/counts/sessions/{periodEncodedId}           - Store session
GET    /inventory/counts/sessions/{encodedId}                 - Show session details
POST   /inventory/counts/sessions/{encodedId}/freeze         - Freeze session
POST   /inventory/counts/sessions/{encodedId}/start-counting  - Start counting
POST   /inventory/counts/sessions/{encodedId}/complete-counting - Complete counting
GET    /inventory/counts/sessions/{encodedId}/variances      - Show variances
GET    /inventory/counts/sessions/{encodedId}/export-counting-sheets-pdf  - Export PDF
GET    /inventory/counts/sessions/{encodedId}/export-counting-sheets-excel - Export Excel
```

### Count Entries
```
GET    /inventory/counts/entries/{encodedId}                - Show entry details (AJAX)
POST   /inventory/counts/entries/{encodedId}/update-physical-qty - Update physical qty
POST   /inventory/counts/entries/{encodedId}/recount        - Request recount
POST   /inventory/counts/entries/{encodedId}/verify         - Verify entry
```

### Variances
```
POST   /inventory/counts/variances/{encodedId}/investigation - Update investigation
```

### Adjustments
```
GET    /inventory/counts/adjustments/create/{sessionEncodedId} - Create adjustment form
POST   /inventory/counts/adjustments                          - Store adjustment
POST   /inventory/counts/adjustments/{encodedId}/approve     - Approve adjustment
POST   /inventory/counts/adjustments/{encodedId}/post        - Post adjustment (create JE)
```

---

## Models & Relationships

### CountPeriod
```php
Relationships:
- belongsTo: Company, Branch, InventoryLocation, User (responsibleStaff)
- hasMany: CountSession

Scopes:
- forCompany($companyId)
```

### CountSession
```php
Relationships:
- belongsTo: CountPeriod, Company, InventoryLocation, User (createdBy, supervisor)
- hasMany: CountEntry, CountTeam, CountAdjustment

Attributes:
- encoded_id (Hashids)

Scopes:
- forCompany($companyId)
```

### CountEntry
```php
Relationships:
- belongsTo: CountSession, Item, InventoryLocation, User (countedBy, recountedBy, verifiedBy)
- hasOne: CountVariance

Attributes:
- encoded_id (Hashids)
```

### CountVariance
```php
Relationships:
- belongsTo: CountEntry, Item
- hasOne: CountAdjustment
```

### CountAdjustment
```php
Relationships:
- belongsTo: CountSession, CountVariance, Item, InventoryLocation, User (createdBy, approvedBy, postedBy)
- belongsTo: Journal, Movement (nullable)
```

---

## Business Logic

### 1. Session Number Generation
```php
Format: CNT-YYYYMMDD-XXXX
Example: CNT-20251208-0001

Logic:
- CNT prefix
- Date in YYYYMMDD format
- Sequential number (4 digits, zero-padded) for sessions created on same day
```

### 2. Counting Sheet Generation
```php
Conditions:
- Only active items (is_active = true)
- Only items that track stock (track_stock = true)
- If blind_count = false: Include ALL items (even 0 stock)
- If blind_count = true: Only include items with stock > 0

System Quantity Source Priority:
1. Movements table (Item::getStockAtLocation())
2. StockLevel table (fallback)
```

### 3. Variance Calculation
```php
Formulas:
variance_quantity = physical_quantity - system_quantity
variance_percentage = (variance_quantity / system_quantity) * 100
variance_value = abs(variance_quantity * unit_cost)

High-Value Detection:
is_high_value = (variance_value >= 50000) OR (abs(variance_percentage) >= 5)
```

### 4. Adjustment Posting
```php
Journal Entry Structure:
If variance is positive (gain):
  Dr Inventory Asset Account
  Cr Inventory Adjustment Income Account

If variance is negative (loss):
  Dr Inventory Adjustment Expense Account
  Cr Inventory Asset Account

Movement Record:
- movement_type: 'adjustment_in' or 'adjustment_out'
- Updates StockLevel
- Updates Item movements history
```

---

## Frontend Features

### 1. Real-Time Variance Display
- Updates as user types in Physical Qty field
- Color-coded badges (green/red/gray)
- No page reload required

### 2. SweetAlert2 Confirmations
- Freeze Session confirmation
- Complete Counting confirmation
- Better UX than browser confirm dialogs

### 3. Select2 Dropdowns
- Location selection with branch names
- Supervisor selection
- Searchable and user-friendly

### 4. DataTables AJAX
- Count Periods table
- Count Sessions table
- Server-side processing for performance

### 5. Auto-Save Physical Quantities
- Saves on blur event
- Shows loading state
- Success/error notifications
- Auto-reloads on success

---

## File Structure

```
app/
├── Http/Controllers/Inventory/
│   └── InventoryCountController.php (Main controller)
├── Models/Inventory/
│   ├── CountPeriod.php
│   ├── CountSession.php
│   ├── CountEntry.php
│   ├── CountVariance.php
│   ├── CountAdjustment.php
│   └── CountTeam.php
└── Exports/
    └── InventoryCountSheetsExport.php

resources/views/inventory/counts/
├── index.blade.php (Main dashboard)
├── periods/
│   ├── create.blade.php
│   └── show.blade.php
└── sessions/
    ├── create.blade.php
    ├── show.blade.php (Main counting interface)
    ├── variances.blade.php
    └── export/
        └── counting-sheets-pdf.blade.php

database/migrations/
├── 2025_12_06_200847_create_inventory_count_periods_table.php
├── 2025_12_06_200848_create_inventory_count_sessions_table.php
├── 2025_12_06_200849_create_inventory_count_entries_table.php
└── (variance and adjustment migrations)
```

---

## Security & Validation

### Authorization Checks:
- All methods check `company_id` matches user's company
- Encoded IDs prevent direct ID access
- Status validation at each workflow step

### Validation Rules:
- Location must exist and belong to company
- Supervisor must exist and belong to company
- Physical quantities must be numeric and >= 0
- Session status must be correct for each action

---

## Export Features

### PDF Export
- Counting sheets with all item details
- System Qty hidden if blind count enabled
- Professional formatting
- Print-ready

### Excel Export
- Same structure as PDF
- Editable format
- Can be used for manual counting
- System Qty column conditionally included

---

## Best Practices

1. **Always freeze before counting**: Ensures accurate snapshot
2. **Use blind count for critical counts**: Prevents bias
3. **Review high-value variances**: Investigate before posting
4. **Complete counting promptly**: Prevents stale data
5. **Document adjustments**: Always provide reason codes and descriptions

---

## Troubleshooting

### Issue: System Quantity shows 0.00
**Solution**: System recalculates from movements. If still 0, check:
- Item has movements at that location
- StockLevel table has records
- Item is active and tracks stock

### Issue: Can't type in Physical Qty
**Solution**: 
- Check session status is 'counting' or 'frozen'
- Ensure JavaScript is enabled
- Check browser console for errors

### Issue: Variance not updating
**Solution**:
- Check `data-system-qty` attribute on input
- Verify JavaScript event handlers are attached
- Check browser console for errors

---

## Future Enhancements (Potential)

1. **Barcode Scanning**: Mobile app integration for scanning
2. **Team Assignment**: Assign counting teams to specific areas
3. **Recount Workflow**: Automated recount requests for high variances
4. **Approval Workflows**: Multi-level approval for adjustments
5. **Reporting**: Variance analysis reports, aging reports
6. **Integration**: Link to purchase/sales to identify root causes

---

## Summary

The Inventory Count Session module provides a complete, IFRS-compliant inventory counting solution with:
- ✅ Full workflow from creation to completion
- ✅ Blind count support
- ✅ Real-time variance calculation
- ✅ High-value variance detection
- ✅ Adjustment processing
- ✅ Export capabilities
- ✅ Audit trail (who counted, when, verified by)
- ✅ Status tracking at every step

This implementation ensures accurate inventory counts while maintaining full traceability and compliance.

