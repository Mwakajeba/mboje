# Period-End Closing — Complete User Guide

## Table of Contents
1. [Overview](#overview)
2. [Key Concepts](#key-concepts)
3. [System Setup](#system-setup)
4. [Module Features](#module-features)
5. [Period-End Closing Process](#period-end-closing-process)
6. [How Period Closing Affects P&L](#how-period-closing-affects-pl)
7. [Step-by-Step Examples](#step-by-step-examples)
8. [Year-End Closing](#year-end-closing)
9. [Best Practices](#best-practices)
10. [Troubleshooting](#troubleshooting)
11. [Glossary](#glossary)

---

## Overview

Period-End Closing is a critical accounting process that ensures accurate financial reporting by:
- **Locking completed periods** to prevent unauthorized changes
- **Capturing immutable snapshots** of account balances at period-end
- **Posting adjusting entries** for accruals, prepayments, and corrections
- **Rolling P&L accounts to Retained Earnings** at year-end
- **Maintaining audit trails** for compliance and review
- **Enforcing sequential closing** to maintain data integrity
- **Pre-close validation** to ensure all transactions are complete

### Why Period-End Closing Matters

1. **Data Integrity**: Prevents backdating transactions into closed periods
2. **Accurate Reporting**: Ensures financial statements reflect true period-end balances
3. **Compliance**: Meets accounting standards and audit requirements
4. **Control**: Provides management oversight of period closing activities
5. **Audit Trail**: Maintains complete history of all closing activities
6. **Sequential Control**: Ensures periods are closed in proper order

---

## Key Concepts

### Period Statuses

#### **OPEN**
- **Meaning**: Period is active and accepting transactions
- **When**: Default status when period is created
- **Transactions**: All transactions allowed
- **Can Close**: Yes, if previous periods are closed
- **Visual Indicator**: Green badge in the system

#### **LOCKED**
- **Meaning**: Period is locked, no new transactions allowed
- **When**: Set when close batch is approved
- **Transactions**: Blocked (receipts, payments, journals, invoices, etc.)
- **Can Reopen**: Yes, with proper authorization and reason
- **Use Case**: Regular month-end closing
- **Visual Indicator**: Yellow/warning badge in the system
- **Locked By**: Records which user locked the period
- **Locked At**: Timestamp of when period was locked

#### **CLOSED**
- **Meaning**: Period is permanently closed/finalized
- **When**: Set manually or automatically for year-end
- **Transactions**: Blocked (same as LOCKED)
- **Can Reopen**: Yes (with authorization), but indicates final closure
- **Use Case**: Year-end periods or when permanently finalized
- **Visual Indicator**: Red/danger badge in the system

**Important**: Both LOCKED and CLOSED periods block transactions. The difference is semantic—CLOSED indicates a more permanent state.

### Close Batch Statuses

#### **DRAFT**
- Close batch is being prepared
- Adjustments can be added/modified/deleted
- Snapshot not yet generated
- Can be submitted for review
- Can be deleted if not needed

#### **REVIEW**
- Close batch submitted for review
- Adjustments cannot be modified or deleted
- Snapshot has been generated (immutable balances captured)
- Awaiting approval
- Cannot be edited

#### **LOCKED**
- Close batch approved
- Period is locked (status: OPEN → LOCKED)
- Adjustments posted to GL as journal entries
- Immutable (cannot be changed)
- Historical record maintained

#### **REOPENED**
- Period was reopened
- Close batch marked as reopened
- Historical record maintained
- Audit trail preserved

---

## System Setup

### Prerequisites

Before using Period-End Closing, ensure:

1. **Fiscal Years Created**
   - Go to: Settings → Period-End Closing → Fiscal Years
   - Create fiscal year with start and end dates
   - System automatically generates monthly periods
   - Each period has a unique label (e.g., "2025-01", "2025-02")

2. **Chart of Accounts Configured**
   - All accounts properly set up
   - Retained Earnings account configured (required for year-end closing)
   - Revenue and Expense accounts identified
   - Account classes properly configured (Revenue, Income, Expense, Cost of Sales)

3. **User Permissions**
   - **Preparer**: Can create and submit close batches
   - **Reviewer**: Can review close batches
   - **Approver**: Can approve and lock periods (requires "manage system settings" permission)
   - **System Admin**: Can reopen locked/closed periods

4. **Branch Configuration** (if multi-branch)
   - Branch settings configured
   - Users assigned to appropriate branches
   - Branch context set in session

### Required System Settings

Ensure these accounts are configured in System Settings:
- `inventory_default_opening_balance_account` (Retained Earnings) - Required for year-end roll
- Other default accounts as needed (e.g., `inventory_default_cash_account` for cash transactions)

---

## Module Features

### 1. Period-End Closing Dashboard

**Location**: Settings → Period-End Closing

**Features**:
- **Overview of Fiscal Years**: List of all fiscal years with status indicators
- **Current Open Period**: Displays the currently active period
- **Pending Close Batches**: Shows batches in DRAFT or REVIEW status
- **Quick Actions**: Direct links to create close batches, view periods, manage fiscal years

### 2. Fiscal Year Management

**Location**: Settings → Period-End Closing → Fiscal Years

**Features**:
- **DataTables Integration**: Server-side processing for large datasets
- **Create Fiscal Year**: 
  - Fiscal Year Label (e.g., "FY2025")
  - Start Date and End Date
  - Automatic period generation (monthly periods)
- **View Periods**: See all periods for a fiscal year
- **Year-End Wizard**: Access to guided year-end closing process
- **Status Indicators**: Visual badges showing fiscal year status (OPEN/CLOSED)
- **Period Count**: Shows number of periods in each fiscal year
- **Duration Display**: Calculates and displays fiscal year duration

### 3. Periods Management

**Location**: Settings → Period-End Closing → Periods

**Features**:
- **DataTables Integration**: Server-side processing with AJAX
- **Filter by Fiscal Year**: Filter periods by specific fiscal year
- **Period Information**:
  - Period Label
  - Fiscal Year
  - Start Date and End Date
  - Period Type (MONTH, QUARTER, YEAR)
  - Status (OPEN, LOCKED, CLOSED)
  - Locked By (user who locked the period)
  - Locked At (timestamp)
- **Actions**:
  - **Close**: Create close batch for OPEN periods (if previous periods are closed)
  - **Reopen**: Reopen LOCKED/CLOSED periods (requires authorization)
- **Search Functionality**: Search across period labels, fiscal years, status
- **Sorting**: Sort by date, fiscal year, status

### 4. Year-End Wizard

**Location**: Settings → Period-End Closing → Fiscal Years → Year-End

**Features**:
- **Progress Tracking**: Visual progress bar showing closing completion percentage
- **Period Status Overview**: 
  - **Ready** (Green): Period can be closed (previous periods are closed)
  - **Blocked** (Yellow): Period cannot be closed yet (waiting for earlier periods)
  - **Locked** (Gray): Period is already closed
- **Sequential Guidance**: Shows which period to close next
- **Quick Access**: Direct "Create Close Batch" buttons for ready periods
- **Real-time Updates**: AJAX-powered status updates
- **Period Details**: Shows period dates and status for each period

### 5. Close Batch Creation

**Location**: Settings → Period-End Closing → Close Batch → Create

**Features**:
- **Pre-Close Checklist**: Automatic validation before closing
  - Unposted Journals Check
  - Unreconciled Bank Items Check
  - Unallocated Receipts Check
  - Unallocated Payments Check
  - Inventory Valuation Check
  - Depreciation Run Check
  - Tax/VAT Booked Check
- **Batch Information**:
  - Batch Label (e.g., "2025-01 Close Batch")
  - Notes (optional documentation)
- **Sequential Validation**: Ensures previous periods are closed
- **Visual Indicators**: Color-coded checklist (green = passed, red = failed)

### 6. Close Batch Management

**Location**: Settings → Period-End Closing → Close Batch → View

**Features**:
- **Batch Details**: 
  - Batch Label and Status
  - Period Information
  - Prepared By, Reviewed By, Approved By
  - Timestamps for each stage
  - Notes
- **Period Snapshots**: 
  - Immutable account balances
  - Opening Balance, Period Activity, Closing Balance
  - DataTables view with search and sort
- **Adjustments Management**:
  - Add Adjustment (DRAFT status only)
  - Delete Adjustment (DRAFT status only, if not posted)
  - View Adjustment Details
  - Adjustment List with accounts and amounts
- **Workflow Actions**:
  - Submit for Review (DRAFT → REVIEW)
  - Approve & Lock Period (REVIEW → LOCKED)
  - Roll to Retained Earnings (Year-end only)

### 7. Adjustments

**Features**:
- **Add Adjustment**:
  - Adjustment Date (usually period end date)
  - Debit Account (from Chart of Accounts)
  - Credit Account (from Chart of Accounts, must be different)
  - Amount (must be positive)
  - Description (required, for audit trail)
  - Source Document (optional reference)
- **Adjustment Types**:
  - Accruals (expenses incurred but not recorded)
  - Prepayments (expenses paid in advance)
  - Corrections (error corrections)
  - Provisions (estimated liabilities)
  - Depreciation
  - Other period-end adjustments
- **Validation**:
  - Debit and Credit accounts must be different
  - Amount must be positive
  - Can only add/delete in DRAFT status
  - Cannot delete if already posted to GL
- **Posting**: Adjustments are automatically posted as journal entries when batch is approved

### 8. Period Snapshots

**Features**:
- **Automatic Generation**: Created when batch is submitted for review
- **Immutable Records**: Cannot be changed once generated
- **Account Balances**:
  - Opening Balance (from previous period or fiscal year start)
  - Period Debits (total debits in the period)
  - Period Credits (total credits in the period)
  - Closing Balance (calculated: Opening + Debits - Credits)
- **DataTables View**: Searchable, sortable table of all account snapshots
- **Audit Trail**: Permanent record of period-end balances

### 9. Period Reopening

**Features**:
- **Authorization Required**: Only users with "manage system settings" permission
- **Reason Required**: Must provide reason for reopening (audit trail)
- **Reopen Process**:
  1. Click "Reopen" button on locked/closed period
  2. Enter reason for reopening (required)
  3. Confirm action
  4. Period status changes: LOCKED/CLOSED → OPEN
- **Use Cases**:
  - Need to post late transactions
  - Error corrections
  - Audit adjustments
- **Audit Trail**: Reason is logged for compliance

### 10. Year-End Roll to Retained Earnings

**Features**:
- **Automatic Calculation**: 
  - Identifies all Revenue accounts
  - Identifies all Expense accounts
  - Calculates Net Income (Revenue - Expenses)
- **Journal Entry Creation**:
  - Automatically creates journal entry
  - Closes all Revenue accounts (debit to zero)
  - Closes all Expense accounts (credit to zero)
  - Transfers Net Income to Retained Earnings
- **Retained Earnings Detection**:
  - Searches for "Retained Earnings" in account name
  - Falls back to system setting: `inventory_default_opening_balance_account`
- **Branch Support**: Includes branch_id in journal entry
- **Auto-Approval**: Journal entry is automatically approved
- **Result**: All P&L accounts reset to zero, ready for next fiscal year

### 11. Date Lock Checking

**Features**:
- **AJAX Endpoint**: `/accounting/period-closing/check-date`
- **Real-time Validation**: Check if a date falls in a locked period
- **Transaction Prevention**: Prevents posting transactions to locked periods
- **User Feedback**: Returns locked period information if date is in locked period
- **Integration**: Used by transaction forms (invoices, payments, journals, etc.)

### 12. Sequential Closing Validation

**Features**:
- **Automatic Validation**: System checks if previous periods are closed
- **Error Messages**: Clear messages indicating which periods must be closed first
- **Visual Indicators**: Year-End Wizard shows "Ready" vs "Blocked" status
- **Enforcement**: Cannot create close batch if previous periods are open
- **Workflow**: Ensures chronological closing (Jan → Feb → Mar → ... → Dec)

---

## Period-End Closing Process

### Workflow Overview

```
1. Create Fiscal Year
   ↓
2. Generate Periods (Automatic)
   ↓
3. Close Periods Sequentially (Month by Month)
   ├─ Create Close Batch
   ├─ Review Pre-Close Checklist
   ├─ Add Adjustments (if needed)
   ├─ Submit for Review
   ├─ Approve & Lock Period
   └─ Period Status: OPEN → LOCKED
   ↓
4. Year-End Closing (After All Periods Closed)
   ├─ Roll P&L to Retained Earnings
   └─ Period Status: LOCKED → CLOSED (Optional)
```

### Step-by-Step Process

#### **Step 1: Create Fiscal Year**

1. Navigate to: **Settings → Period-End Closing → Fiscal Years**
2. Click **"Create Fiscal Year"**
3. Enter:
   - **Fiscal Year Label**: e.g., "FY2025"
   - **Start Date**: First day of fiscal year (e.g., Jan 1, 2025)
   - **End Date**: Last day of fiscal year (e.g., Dec 31, 2025)
4. Click **"Create Fiscal Year"**
5. System automatically generates 12 monthly periods
6. Each period is created with:
   - Period Label (e.g., "2025-01", "2025-02")
   - Start Date (first day of month)
   - End Date (last day of month)
   - Period Type: "MONTH"
   - Status: "OPEN"

#### **Step 2: Close Periods Sequentially**

**Important**: You must close periods in chronological order (January, then February, then March, etc.)

**Option A: Using Year-End Wizard (Recommended)**

1. Navigate to: **Settings → Period-End Closing → Fiscal Years**
2. Click **"Year-End"** button for your fiscal year
3. The wizard shows:
   - Progress bar (how many periods closed)
   - List of open periods
   - Which periods are **"Ready"** to close (green badge)
   - Which periods are **"Blocked"** (yellow badge - waiting for earlier periods)
4. Find the first **"Ready"** period (usually January)
5. Click **"Create Close Batch"** for that period

**Option B: Direct Period Closing**

1. Navigate to: **Settings → Period-End Closing → Periods**
2. Use search/filter to find the period you want to close
3. Click **"Close"** button (only visible if period is OPEN and previous periods are closed)
4. If previous periods are not closed, you'll see an error message

#### **Step 3: Create Close Batch**

1. **Review Pre-Close Checklist**
   - System automatically checks:
     - ✅ Unposted journal entries
     - ✅ Unreconciled bank items
     - ✅ Unallocated receipts/payments
     - ✅ Inventory valuation
     - ✅ Depreciation run
     - ✅ Tax/VAT entries
   - **Green checkmarks** = Passed
   - **Red X marks** = Failed (must resolve before closing)
   - Address any failed checks before proceeding

2. **Enter Batch Information**
   - **Batch Label**: e.g., "2025-01 Close Batch"
   - **Notes**: Optional notes about the closing (for audit trail)

3. **Click "Create Close Batch"**
   - System creates close batch with status: **DRAFT**
   - You can now add adjustments

#### **Step 4: Add Adjustments (If Needed)**

Adjustments are used for:
- **Accruals**: Expenses incurred but not yet recorded
- **Prepayments**: Expenses paid in advance
- **Corrections**: Error corrections
- **Provisions**: Estimated liabilities
- **Depreciation**: Monthly depreciation entries

**To Add an Adjustment:**

1. In the close batch detail page, click **"Add Adjustment"**
2. Enter:
   - **Adjustment Date**: Usually the last day of the period
   - **Debit Account**: Select from Chart of Accounts dropdown
   - **Credit Account**: Select from Chart of Accounts dropdown (must be different)
   - **Amount**: Adjustment amount (must be positive)
   - **Description**: Clear description of the adjustment (required)
   - **Source Document**: Optional reference to supporting document
3. Click **"Add Adjustment"**
4. Adjustment appears in the adjustments list
5. Repeat for all adjustments needed

**To Delete an Adjustment:**

1. Click **"Delete"** button next to the adjustment
2. Only works if:
   - Batch is in DRAFT status
   - Adjustment has not been posted to GL
3. Adjustment is removed from the batch

**Example Adjustments:**

- **Accrued Utilities**: 
  - Debit: Utilities Expense
  - Credit: Accrued Expenses
  - Amount: TZS 50,000
  
- **Prepaid Insurance**: 
  - Debit: Insurance Expense
  - Credit: Prepaid Insurance
  - Amount: TZS 10,000
  
- **Depreciation**: 
  - Debit: Depreciation Expense
  - Credit: Accumulated Depreciation
  - Amount: TZS 25,000

#### **Step 5: Submit for Review**

1. Review all adjustments
2. Verify pre-close checklist is passed
3. Click **"Submit for Review"** button
4. System:
   - Generates period snapshot (immutable balances captured)
   - Changes batch status: **DRAFT → REVIEW**
   - Locks adjustments (cannot be modified)
5. Batch is now ready for review/approval

#### **Step 6: Approve & Lock Period**

1. **Reviewer/Approver** reviews the close batch:
   - Checks snapshot balances
   - Verifies adjustments are correct
   - Reviews notes and documentation
2. Clicks **"Approve & Lock Period"** button
3. System:
   - Posts all adjustments as journal entries (with branch_id)
   - Each adjustment becomes a journal entry with:
     - Reference: "ADJ-{Batch Label}"
     - Reference Type: "Period Close"
     - Auto-approved status
   - Locks the period (status: **OPEN → LOCKED**)
   - Records who locked it and when
   - Updates batch status: **REVIEW → LOCKED**
4. Period is now locked—no new transactions allowed
5. Transaction forms will prevent posting to this period

#### **Step 7: Repeat for Next Period**

1. Return to Year-End Wizard
2. Next period (e.g., February) should now be **"Ready"**
3. Repeat Steps 3-6 for each period
4. Continue until all 12 months are closed
5. Progress bar updates automatically

---

## How Period Closing Affects P&L

### Understanding P&L Impact

Period closing itself does **not directly change P&L accounts**. However, it:

1. **Locks the period** (prevents new transactions)
2. **Captures snapshots** (immutable period-end balances)
3. **Posts adjustments** (which DO affect P&L)

### Adjustments and P&L

**Adjustments posted during period closing directly affect P&L:**

#### Example 1: Accrued Expense Adjustment

**Scenario**: Utilities expense of TZS 50,000 incurred in January but invoice received in February.

**Adjustment Entry (Posted during January closing):**
```
Debit:  Utilities Expense          TZS 50,000
Credit: Accrued Expenses           TZS 50,000
```

**P&L Impact**:
- **Utilities Expense** increases by TZS 50,000
- **Net Income** decreases by TZS 50,000
- **Accrued Expenses** (liability) increases by TZS 50,000

#### Example 2: Prepaid Expense Adjustment

**Scenario**: Insurance of TZS 120,000 paid in advance for 12 months. January closing recognizes 1 month.

**Adjustment Entry:**
```
Debit:  Insurance Expense          TZS 10,000
Credit: Prepaid Insurance          TZS 10,000
```

**P&L Impact**:
- **Insurance Expense** increases by TZS 10,000
- **Net Income** decreases by TZS 10,000
- **Prepaid Insurance** (asset) decreases by TZS 10,000

#### Example 3: Depreciation Adjustment

**Scenario**: Monthly depreciation of TZS 25,000 for fixed assets.

**Adjustment Entry:**
```
Debit:  Depreciation Expense       TZS 25,000
Credit: Accumulated Depreciation  TZS 25,000
```

**P&L Impact**:
- **Depreciation Expense** increases by TZS 25,000
- **Net Income** decreases by TZS 25,000
- **Accumulated Depreciation** (contra-asset) increases by TZS 25,000

### Year-End: Rolling P&L to Retained Earnings

At year-end, after closing the last period (December), you must **roll P&L accounts to Retained Earnings**.

**What This Does:**
- Closes all Revenue accounts (sets balance to zero)
- Closes all Expense accounts (sets balance to zero)
- Transfers Net Income (or Loss) to Retained Earnings

**Example Year-End Roll:**

**Before Roll:**
- Sales Revenue: TZS 1,000,000 (credit balance)
- Cost of Sales: TZS 400,000 (debit balance)
- Operating Expenses: TZS 300,000 (debit balance)
- Net Income: TZS 300,000

**Roll Entry (Automatically Generated):**
```
Debit:  Sales Revenue              TZS 1,000,000
Credit: Cost of Sales             TZS 400,000
Credit: Operating Expenses         TZS 300,000
Credit: Retained Earnings         TZS 300,000
```

**After Roll:**
- Sales Revenue: TZS 0
- Cost of Sales: TZS 0
- Operating Expenses: TZS 0
- Retained Earnings: Increased by TZS 300,000

**P&L Impact:**
- All P&L accounts reset to zero
- Net Income transferred to equity (Retained Earnings)
- Ready for next fiscal year

---

## Step-by-Step Examples

### Example 1: Closing January 2025

**Scenario**: Closing January 2025, the first month of FY2025.

#### Step 1: Access Year-End Wizard
- Navigate to: **Settings → Period-End Closing → Fiscal Years**
- Click **"Year-End"** for FY2025
- January 2025 should show as **"Ready"** (green badge)

#### Step 2: Create Close Batch
- Click **"Create Close Batch"** for January
- Review pre-close checklist:
  - ✅ All journals posted
  - ✅ Bank reconciliation complete
  - ✅ All receipts/payments allocated
- Enter batch label: **"2025-01 Close Batch"**
- Click **"Create Close Batch"**

#### Step 3: Add Adjustments

**Adjustment 1: Accrued Salaries**
- Date: January 31, 2025
- Debit: Salary Expense - TZS 500,000
- Credit: Salaries Payable - TZS 500,000
- Description: "Accrued salaries for January"

**Adjustment 2: Depreciation**
- Date: January 31, 2025
- Debit: Depreciation Expense - TZS 50,000
- Credit: Accumulated Depreciation - TZS 50,000
- Description: "Monthly depreciation for January"

#### Step 4: Submit for Review
- Review all adjustments
- Click **"Submit for Review"**
- System generates snapshot

#### Step 5: Approve & Lock
- Approver reviews and clicks **"Approve & Lock Period"**
- System:
  - Posts adjustments to GL as journal entries
  - Locks January period
  - January status: **OPEN → LOCKED**

**Result**: January is locked. February becomes "Ready" to close.

---

### Example 2: Closing February 2025

**Prerequisite**: January must be closed first.

#### Step 1: Check Status
- Return to Year-End Wizard
- February should now be **"Ready"**
- January shows as **"LOCKED"**

#### Step 2: Create Close Batch
- Click **"Create Close Batch"** for February
- Review pre-close checklist
- Create batch: **"2025-02 Close Batch"**

#### Step 3: Add Adjustments

**Adjustment 1: Reverse January Accrual**
- Date: February 1, 2025
- Debit: Salaries Payable - TZS 500,000
- Credit: Salary Expense - TZS 500,000
- Description: "Reversal of January salary accrual (now paid)"

**Adjustment 2: Accrue February Salaries**
- Date: February 28, 2025
- Debit: Salary Expense - TZS 500,000
- Credit: Salaries Payable - TZS 500,000
- Description: "Accrued salaries for February"

**Adjustment 3: Depreciation**
- Date: February 28, 2025
- Debit: Depreciation Expense - TZS 50,000
- Credit: Accumulated Depreciation - TZS 50,000
- Description: "Monthly depreciation for February"

#### Step 4: Submit & Approve
- Submit for review
- Approve & lock period
- February status: **OPEN → LOCKED**

**Result**: February is locked. March becomes "Ready" to close.

---

### Example 3: Year-End Closing (December 2025)

**Prerequisite**: All months January through November must be closed.

#### Step 1: Close December
- Follow same process as previous months
- Close December period
- December status: **OPEN → LOCKED**

#### Step 2: Roll to Retained Earnings
- Navigate to December's close batch detail page
- Click **"Roll to Retained Earnings"** button
- System automatically:
  - Calculates net income from all revenue and expense accounts
  - Creates journal entry closing all P&L accounts
  - Transfers net income to Retained Earnings
  - Includes branch_id in journal entry

**Example Roll Entry:**

**If Net Income is TZS 3,600,000:**

```
Debit:  Sales Revenue              TZS 12,000,000
Debit:  Other Income               TZS 500,000
Credit: Cost of Sales              TZS 4,800,000
Credit: Operating Expenses        TZS 3,600,000
Credit: Other Expenses            TZS 500,000
Credit: Retained Earnings          TZS 3,600,000
```

**Result**:
- All P&L accounts reset to zero
- Retained Earnings increased by TZS 3,600,000
- Ready for next fiscal year

---

## Year-End Closing

### Complete Year-End Process

1. **Close All Months Sequentially**
   - Close January through November
   - Each month must be closed before the next
   - Use Year-End Wizard to track progress

2. **Close December (Last Period)**
   - Follow standard closing process
   - December becomes LOCKED

3. **Roll to Retained Earnings**
   - Navigate to December's close batch
   - Click **"Roll to Retained Earnings"**
   - System generates closing entries automatically
   - All P&L accounts reset to zero
   - Net Income transferred to Retained Earnings

4. **Mark Period as CLOSED (Optional)**
   - For year-end periods, you may want to mark as CLOSED
   - Indicates permanent closure
   - Can still be reopened if needed

### Year-End Wizard Benefits

The Year-End Wizard provides:
- **Progress Tracking**: See how many periods are closed (percentage)
- **Sequential Guidance**: Shows which period to close next
- **Status Overview**: Visual representation of closing progress
- **Quick Access**: Direct links to create close batches
- **Real-time Updates**: AJAX-powered status refresh

---

## Best Practices

### 1. Sequential Closing
- ✅ **Always close periods in order** (Jan → Feb → Mar → ... → Dec)
- ❌ **Never skip periods** (e.g., don't close March before February)
- ✅ **Use Year-End Wizard** to track progress
- ✅ **Verify previous periods are closed** before closing next period

### 2. Pre-Close Checklist
- ✅ **Complete all transactions** before closing
- ✅ **Post all journals** for the period
- ✅ **Reconcile bank accounts**
- ✅ **Run depreciation** (if applicable)
- ✅ **Book all tax/VAT entries**
- ✅ **Resolve all failed checks** before proceeding

### 3. Adjustments
- ✅ **Document all adjustments** clearly
- ✅ **Reference source documents** (invoices, contracts, etc.)
- ✅ **Review adjustments** before submitting
- ✅ **Keep adjustment descriptions** detailed for audit trail
- ✅ **Use appropriate accounts** (debit and credit must be different)
- ✅ **Verify amounts** are correct before submitting

### 4. Review Process
- ✅ **Have multiple reviewers** check close batches
- ✅ **Verify snapshot balances** match expectations
- ✅ **Check adjustment calculations** for accuracy
- ✅ **Ensure all adjustments are necessary**
- ✅ **Review notes and documentation**

### 5. Timing
- ✅ **Close periods promptly** after month-end
- ✅ **Allow time for review** before locking
- ✅ **Don't rush** the closing process
- ✅ **Document any delays** in notes
- ✅ **Set closing deadlines** and stick to them

### 6. Year-End
- ✅ **Close all months** before year-end roll
- ✅ **Verify all P&L accounts** before rolling
- ✅ **Review retained earnings** after roll
- ✅ **Backup data** before year-end closing
- ✅ **Verify Retained Earnings account** is configured

### 7. Period Reopening
- ✅ **Use sparingly** (only when necessary)
- ✅ **Document reason clearly** (required for audit trail)
- ✅ **Get proper authorization** (requires system settings permission)
- ✅ **Re-close after adjustments** if needed

### 8. Branch Support
- ✅ **Set branch context** before creating close batches
- ✅ **Verify branch_id** is included in journal entries
- ✅ **Review branch-specific** transactions before closing

---

## Troubleshooting

### Issue: "Cannot close this period. Previous periods must be closed first."

**Cause**: You're trying to close a period out of order.

**Solution**:
1. Check which periods are still open
2. Use Year-End Wizard to see which period is "Ready"
3. Close the earliest open period first
4. Continue sequentially

**Example**: If February is open, close February before March.

---

### Issue: "Period is locked. Transactions are not allowed."

**Cause**: You're trying to post a transaction to a locked/closed period.

**Solution**:
1. Check the transaction date
2. If the date is in a locked period, change the date to an open period
3. If you need to post to a locked period, reopen it first (requires authorization)
4. Use the date lock check endpoint to verify before posting

---

### Issue: "Pre-close checklist shows unposted journals"

**Cause**: There are journal entries in the period that haven't been posted to GL.

**Solution**:
1. Navigate to Journal Entries
2. Find unposted journals for the period
3. Approve and post them
4. Or delete them if not needed
5. Retry closing

---

### Issue: "Cannot reopen period"

**Cause**: You don't have permission to reopen periods.

**Solution**:
1. Ensure you have "manage system settings" permission
2. Or contact an administrator
3. Provide a reason for reopening (required)

---

### Issue: "Retained Earnings roll failed"

**Cause**: Retained Earnings account not configured.

**Solution**:
1. Go to System Settings
2. Configure `inventory_default_opening_balance_account`
3. Or ensure a "Retained Earnings" account exists in Chart of Accounts
4. Verify account name contains "Retained Earnings" or is set in system settings
5. Retry the roll

---

### Issue: "Period snapshots show zero balances"

**Cause**: No GL transactions in the period, or snapshot generated incorrectly.

**Solution**:
1. Verify GL transactions exist for the period
2. Check if transactions are dated correctly (within period dates)
3. Regenerate snapshot if needed (delete and recreate close batch)
4. Check account balances in GL directly

---

### Issue: "Cannot delete adjustment"

**Cause**: Adjustment cannot be deleted because:
- Batch is not in DRAFT status
- Adjustment has already been posted to GL

**Solution**:
1. Only DRAFT batches allow adjustment deletion
2. If batch is in REVIEW or LOCKED status, you cannot delete adjustments
3. If adjustment is posted, it's part of GL and cannot be deleted
4. Create a reversing adjustment if needed

---

### Issue: "Branch ID error when approving close batch"

**Cause**: Journal entries require branch_id but it's not set.

**Solution**:
1. System automatically resolves branch_id from:
   - Session (`session('branch_id')`)
   - User's default branch (`Auth::user()->branch_id`)
   - CloseBatch's branch_id (if available)
2. Ensure user has a branch assigned
3. Set branch context in session if using multi-branch

---

### Issue: "Year-End Wizard shows incorrect status"

**Cause**: Cache or real-time update issue.

**Solution**:
1. Refresh the page
2. Check period status directly in Periods page
3. Verify periods are closed sequentially
4. Clear browser cache if needed

---

## Glossary

### Accounting Period
A specific time period (usually a month) within a fiscal year for which financial transactions are recorded and reported.

### Close Batch
A record that tracks the closing process for a specific period, including adjustments and snapshots. Has statuses: DRAFT, REVIEW, LOCKED, REOPENED.

### Period Snapshot
An immutable record of account balances at period-end, captured when a close batch is submitted for review. Includes opening balance, period activity, and closing balance.

### Adjustment
A journal entry posted during period closing to account for accruals, prepayments, corrections, or provisions. Automatically converted to journal entries when batch is approved.

### Retained Earnings
An equity account that accumulates net income (or loss) over time. At year-end, all P&L accounts are closed to Retained Earnings.

### Fiscal Year
A 12-month period used for financial reporting and tax purposes. May or may not align with calendar year.

### Period Lock
A status that prevents new transactions from being posted to a period. Both LOCKED and CLOSED periods block transactions.

### Sequential Closing
The requirement to close periods in chronological order (January before February, etc.). Enforced by the system.

### Pre-Close Checklist
A validation process that checks for unposted journals, unreconciled items, and other issues before allowing period closing.

### Year-End Roll
The process of closing all P&L accounts to Retained Earnings at the end of a fiscal year. Automatically generates journal entries.

### Branch Support
Multi-branch functionality that includes branch_id in journal entries and supports branch-specific period closing.

---

## Additional Resources

### Related Modules
- **Journal Entries**: For posting adjustments and period-end entries
- **Bank Reconciliation**: Must be complete before closing
- **Fixed Assets**: Depreciation must be run
- **Inventory**: Valuation must be complete
- **Chart of Accounts**: Required for adjustments and year-end roll

### System Settings
- Period-End Closing configuration
- Default account settings (Retained Earnings)
- Approval workflow settings
- Branch configuration

### Reports
- Period closing reports
- Snapshot reports
- Adjustment reports
- Year-end closing reports
- Period status reports

### Technical Features
- **DataTables Integration**: Server-side processing for fiscal years and periods
- **AJAX Date Lock Checking**: Real-time validation of transaction dates
- **Sequential Validation**: Automatic enforcement of chronological closing
- **Immutable Snapshots**: Permanent record of period-end balances
- **Audit Trail**: Complete history of all closing activities
- **Branch Support**: Multi-branch period closing with branch_id tracking

---

## Support

For additional assistance:
1. Check system logs for error messages
2. Review this guide for common issues
3. Contact system administrator
4. Refer to accounting standards (IFRS/GAAP) for guidance
5. Review pre-close checklist for specific issues

---

**Document Version**: 2.0  
**Last Updated**: December 2025  
**System Version**: Smart Accounting System  
**Module**: Period-End Closing
