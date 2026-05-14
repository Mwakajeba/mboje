# Asset Maintenance Management System - Complete Flow Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Pre-Setup Configuration](#pre-setup-configuration)
3. [Maintenance Request Flow](#maintenance-request-flow)
4. [Work Order Creation and Execution](#work-order-creation-and-execution)
5. [Cost Capture and Recording](#cost-capture-and-recording)
6. [Cost Classification Logic](#cost-classification-logic)
7. [GL Posting and Asset Updates](#gl-posting-and-asset-updates)
8. [Preventive Maintenance Scheduling](#preventive-maintenance-scheduling)
9. [Approval Workflow](#approval-workflow)
10. [Reports and Analytics](#reports-and-analytics)
11. [Integration Points](#integration-points)
12. [User Roles and Permissions](#user-roles-and-permissions)
13. [Best Practices](#best-practices)
14. [Troubleshooting](#troubleshooting)

---

## System Overview

The Asset Maintenance Management System is a comprehensive module within the SmartAccounting Assets Management system that handles the complete lifecycle of asset maintenance from request initiation to cost classification and accounting integration in compliance with IAS 16 (Property, Plant and Equipment) and best practices for asset management.

### Key Features
- **Preventive Maintenance**: Scheduled maintenance based on calendar or usage
- **Corrective Maintenance**: Breakdown and repair requests
- **Major Overhaul**: Significant improvements and capital upgrades
- **Cost Tracking**: Material, labor, and other direct costs
- **Automatic Classification**: Expense vs. Capitalization logic based on thresholds
- **GL Integration**: Automatic journal entry posting
- **Asset Updates**: Automatic cost and depreciation adjustments
- **Work Order Management**: Complete work order lifecycle
- **Vendor Management**: External vendor tracking and performance
- **Downtime Tracking**: Asset availability monitoring

### Maintenance Types
1. **Preventive**: Routine scheduled maintenance (oil changes, inspections)
2. **Corrective**: Breakdown repairs (component replacements, fixes)
3. **Major Overhaul**: Significant improvements (engine rebuilds, major upgrades)

### Compliance Standards
- **IAS 16**: Property, Plant and Equipment (Capitalization of improvements)
- **Best Practices**: Maintenance management and asset reliability

---

## Pre-Setup Configuration

### 1. Maintenance Types Setup

**Location**: `/asset-management/maintenance/types`

**Purpose**: Define maintenance categories that determine expense/capitalization logic.

**Steps**:
1. Navigate to Asset Management → Maintenance → Types
2. Click "Create New Maintenance Type"
3. Fill in the form:
   - **Code**: Unique identifier (e.g., "PM-001")
   - **Name**: Descriptive name (e.g., "Preventive Maintenance - Monthly")
   - **Type**: Select from:
     - `preventive`: Routine scheduled maintenance
     - `corrective`: Breakdown repairs
     - `major_overhaul`: Significant improvements
   - **Description**: Detailed explanation
   - **Default Classification**: Expense or Capitalized (suggestion)
   - **Status**: Active/Inactive
4. Save

**Example Types**:
- **Preventive**: Oil changes, filter replacements, inspections, calibration
- **Corrective**: Engine repairs, component replacements, troubleshooting
- **Major Overhaul**: Engine rebuild, major upgrades, life extension projects

---

### 2. Maintenance Settings Configuration

**Location**: `/asset-management/maintenance/settings`

**Purpose**: Configure default GL accounts and capitalization thresholds.

**Settings to Configure**:

#### A. GL Accounts
- **Maintenance Expense Account**: Default P&L account for routine maintenance expenses
- **Maintenance WIP Account**: Work-in-Progress account for costs during execution
- **Asset Capitalization Account**: GL account for capitalized maintenance costs (typically the asset account)

#### B. Capitalization Thresholds
- **Capitalization Threshold Amount**: Minimum cost (TZS) to qualify for capitalization
  - Default: TZS 2,000,000
  - Example: Any maintenance costing more than 2M TZS may be capitalized
  
- **Capitalization Life Extension Threshold**: Minimum life extension (months) to qualify
  - Default: 12 months
  - Example: If maintenance extends asset life by 12+ months, it may be capitalized

**Steps**:
1. Navigate to Asset Management → Maintenance → Settings
2. Select appropriate Chart of Accounts for each setting
3. Enter threshold values
4. Save settings

**Example Configuration**:
```
Maintenance Expense Account: 6400 - Maintenance Expense
Maintenance WIP Account: 1400 - Maintenance Work in Progress
Asset Capitalization Account: 1500 - Property, Plant and Equipment
Capitalization Threshold: 2,000,000 TZS
Life Extension Threshold: 12 months
```

---

### 3. Approval Workflow Setup

**Current Implementation**: Single-level supervisor approval

**Workflow**:
1. **Initiator**: Creates maintenance request
2. **Supervisor**: Reviews and approves/rejects
3. **System**: Converts approved request to Work Order

**Future Enhancement**: Multi-level approval (Supervisor → Finance → Final Approver)

---

## Maintenance Request Flow

### Step 1: Request Initiation

**Location**: `/asset-management/maintenance/requests/create`

**Triggering Events**:
- **Preventive**: Scheduled maintenance based on calendar or usage
- **Corrective**: Asset breakdown or malfunction
- **Planned Improvement**: Upgrades or enhancements

**Process**:

1. **Select Asset**
   - System displays asset registry with filters
   - Select asset requiring maintenance
   - System auto-fetches:
     - Asset ID and name
     - Asset code
     - Location
     - Custodian
     - Cost center
     - Department
     - Last maintenance date
     - Maintenance history

2. **Select Maintenance Type**
   - Choose from configured maintenance types
   - System determines default classification logic
   - Type determines workflow and cost treatment

3. **Enter Request Details**:
   - **Trigger Type**: Preventive, Corrective, or Planned Improvement
   - **Priority**: Low, Medium, High, Urgent
   - **Description**: What needs to be done
   - **Issue Details**: Problem description (for corrective)
   - **Requested Date**: When maintenance is needed
   - **Preferred Start Date**: Preferred start date
   - **Estimated Cost**: Rough cost estimate (optional)
   - **Attachments**: Upload relevant documents/photos

4. **Submit Request**
   - System generates unique Request Number (e.g., "MR-2025-001")
   - Status: `pending`
   - Notification sent to supervisor
   - Request locked for editing

---

### Step 2: Supervisor Approval

**Location**: `/asset-management/maintenance/requests/{id}`

**Process**:

1. **Supervisor Reviews Request**
   - Views asset details
   - Reviews issue description
   - Checks attachments
   - Verifies necessity
   - Reviews estimated cost
   - Checks budget availability

2. **Decision Options**:

   **A. Approve**:
   - Status changes to `approved`
   - `supervisor_approved_by` and `supervisor_approved_at` recorded
   - Supervisor notes saved
   - Request ready for Work Order creation
   - Notification sent to maintenance team

   **B. Reject**:
   - Status changes to `rejected`
   - Rejection reason recorded
   - Request closed
   - Notification sent to initiator

   **C. Request More Information**:
   - Add notes requesting clarification
   - Request remains `pending`
   - Notification sent to initiator

---

### Step 3: Work Order Creation

**Location**: `/asset-management/maintenance/work-orders/create`

**Process**:

1. **Link to Maintenance Request** (if applicable)
   - Select approved maintenance request
   - System auto-populates:
     - Asset details
     - Maintenance type
     - Request details
     - Priority

2. **Work Order Details**:
   - **WO Number**: Auto-generated (e.g., "WO-2025-001")
   - **Asset**: Selected from approved request
   - **Maintenance Type**: From request
   - **Execution Type**:
     - `in_house`: Internal technicians
     - `external_vendor`: External contractor
     - `mixed`: Combination of internal and external
   - **Vendor/Technician**: 
     - Select supplier (for external)
     - Select internal user (for in-house)
   - **Estimated Dates**:
     - Start date
     - Completion date
   - **Estimated Costs**:
     - Labor cost
     - Material cost
     - Other costs
     - Total estimated cost
   - **Estimated Downtime**: Hours asset will be unavailable
   - **Cost Center**: Cost center for allocation
   - **Budget Reference**: Link to budget if applicable
   - **Work Description**: Detailed work plan

3. **Submit Work Order**
   - Status: `draft`
   - Requires approval before execution

---

### Step 4: Work Order Approval

**Location**: `/asset-management/maintenance/work-orders/{id}/approve`

**Process**:

1. **Review Work Order**
   - Verify estimates
   - Check budget availability
   - Confirm vendor/technician assignment
   - Review work description
   - Check estimated downtime

2. **Approve**
   - Status: `approved`
   - `approved_by` and `approved_at` recorded
   - Approval notes saved
   - Notifications sent to:
     - Assigned technician/vendor
     - Asset custodian
     - Department head

3. **Ready for Execution**

---

## Work Order Creation and Execution

### Step 5: Work Order Execution

**Location**: `/asset-management/maintenance/work-orders/{id}/execute`

**Process**:

1. **Start Work**
   - Update status to `in_progress`
   - Record `actual_start_date`
   - Begin cost capture
   - Notifications sent

2. **Cost Capture During Execution**

   **A. Material Costs**:
   - Navigate to Work Order → Execute → Add Cost
   - Select cost type: `material`
   - Select inventory item (if integrated)
   - Enter quantity and unit cost
   - System calculates total cost
   - **Optional**: Link to Purchase Order or Invoice
   - System creates Work Order Cost record
   - **Journal Entry** (if integrated):
     ```
     Dr. Maintenance WIP Account
         Cr. Inventory Account
     ```

   **B. Labor Costs**:
   - **Internal**: 
     - Select cost type: `labor`
     - Select technician (user)
     - Enter hours worked
     - System applies hourly rate (from HR/Payroll if integrated)
     - Calculate: hours × rate = labor cost
   - **External**: 
     - Select cost type: `labor`
     - Select supplier/vendor
     - Link to Purchase Invoice
     - System pulls amount from invoice
   - System creates Work Order Cost record
   - **Journal Entry**:
     ```
     Dr. Maintenance WIP Account
         Cr. Payables/Accrued Expense
     ```

   **C. Other Direct Costs**:
   - Select cost type: `other`
   - Enter description (fuel, subcontracting, logistics, etc.)
   - Enter amount
   - **Optional**: Link to supplier invoice
   - System creates Work Order Cost record
   - **Journal Entry**:
     ```
     Dr. Maintenance WIP Account
         Cr. Payables/Cash
     ```

3. **Track Progress**
   - Update work performed notes
   - Record actual downtime hours
   - Add technician notes
   - Upload completion photos
   - Update cost estimates if needed

---

### Step 6: Work Order Completion

**Location**: `/asset-management/maintenance/work-orders/{id}/complete`

**Process**:

1. **Technician Marks Complete**
   - Update status to `completed`
   - Record `actual_completion_date`
   - Enter `work_performed` details
   - Finalize all costs
   - Enter `technician_notes`
   - Upload completion documentation

2. **System Actions**:
   - Calculate total actual costs:
     - `actual_cost` = sum of all cost records
     - `actual_labor_cost` = sum of labor costs
     - `actual_material_cost` = sum of material costs
     - `actual_other_cost` = sum of other costs
   - Update `completed_by` and `completed_at`
   - Status: `completed`
   - Cost Classification: `pending_review`
   - Compare estimated vs. actual costs

3. **Notification Sent**:
   - Finance/Asset Accountant for review
   - Asset custodian
   - Department head
   - Request initiator

---

## Cost Capture and Recording

### Cost Types

#### 1. Material Costs

**Source**: Inventory items or purchased materials

**Process**:
1. Navigate to Work Order → Execute → Add Cost
2. Select cost type: `material`
3. Select inventory item (if integrated) OR enter material description
4. Enter quantity and unit cost
5. System calculates total cost
6. **Optional**: Link to Purchase Order or Invoice
7. Save cost record

**Database Record**:
- Table: `asset_maintenance_work_order_costs`
- Fields:
  - `work_order_id`: Link to work order
  - `cost_type`: 'material'
  - `inventory_item_id`: (if from inventory)
  - `description`: Material description
  - `quantity`: Quantity used
  - `unit_cost`: Cost per unit
  - `amount`: Total cost (quantity × unit_cost)
  - `supplier_id`: (if from supplier)
  - `purchase_invoice_id`: (if linked to invoice)
  - `status`: 'actual'

**Integration**:
- **Inventory Module**: Deducts stock (if integrated)
- **Procurement Module**: Links to PO/Invoice

---

#### 2. Labor Costs

**Source**: Internal technicians or external vendors

**Process**:

**Internal Labor**:
1. Select cost type: `labor`
2. Select technician (user)
3. Enter hours worked
4. System applies hourly rate (from HR/Payroll if integrated)
5. Calculate: hours × rate = labor cost
6. Save cost record

**External Labor**:
1. Select cost type: `labor`
2. Select supplier/vendor
3. Link to Purchase Invoice
4. System pulls amount from invoice
5. Save cost record

**Database Record**:
- `cost_type`: 'labor'
- `supplier_id`: (for external)
- `employee_id`: (for internal)
- `hours_worked`: (for internal)
- `hourly_rate`: (for internal)
- `purchase_invoice_id`: (if from vendor)
- `amount`: Total labor cost

---

#### 3. Other Direct Costs

**Source**: Various (fuel, subcontracting, logistics, etc.)

**Process**:
1. Select cost type: `other`
2. Enter description
3. Enter amount
4. **Optional**: Link to supplier invoice
5. Save cost record

**Database Record**:
- `cost_type`: 'other'
- `description`: Cost description
- `amount`: Cost amount
- `supplier_id`: (if applicable)
- `purchase_invoice_id`: (if applicable)

---

### Cost Aggregation

**Work Order Cost Fields**:
- `estimated_cost`: Sum of estimated costs
- `actual_cost`: Sum of all actual cost records
- `estimated_labor_cost` / `actual_labor_cost`
- `estimated_material_cost` / `actual_material_cost`
- `estimated_other_cost` / `actual_other_cost`

**Calculation**:
```php
$workOrder->actual_cost = $workOrder->workOrderCosts()
    ->where('status', 'actual')
    ->sum('amount');
```

---

## Cost Classification Logic

### Step 7: Finance Review and Classification

**Location**: `/asset-management/maintenance/work-orders/{id}/review`

**Purpose**: Determine if maintenance costs should be expensed or capitalized.

---

### Classification Rules

#### A. Routine Maintenance (Expense)

**Criteria** (all must be met):
- Cost < Capitalization Threshold Amount (default: 2M TZS)
- Life extension < Capitalization Life Extension Threshold (default: 12 months)
- Standard preventive or corrective maintenance
- No significant improvement to asset
- Does not extend useful life materially

**Classification**: `expense`

**Accounting Treatment**:
```
Dr. Maintenance Expense (P&L)        XXX
    Cr. Maintenance WIP              XXX
```

**Asset Impact**:
- Asset cost: **No change**
- Depreciation: **No change**
- Asset history: Recorded as expense
- P&L: Expense recognized

---

#### B. Capital Improvement/Major Overhaul (Capitalized)

**Criteria** (any of the following):
- Cost ≥ Capitalization Threshold Amount
- Life extension ≥ Capitalization Life Extension Threshold
- Significant improvement to asset functionality
- Major overhaul that extends useful life
- Enhances asset capacity or efficiency materially

**Classification**: `capitalized`

**Accounting Treatment**:
```
Dr. Fixed Asset (Improvement)        XXX
    Cr. Maintenance WIP               XXX
```

**Asset Impact**:
- Asset cost: **Increased** by capitalized amount
- Depreciation: **Recalculated** based on new cost
- Useful life: **Extended** (if applicable)
- Depreciation basis: **Adjusted**
- TRA Tax Book: **Updated** (if applicable)

---

### Classification Process

1. **Finance Reviews Work Order**
   - Reviews total cost
   - Checks work performed description
   - Verifies life extension (if claimed)
   - Reviews vendor invoices
   - Compares estimated vs. actual costs

2. **Apply Classification Rules**
   - System suggests classification based on thresholds
   - Finance can override if needed
   - Review against IAS 16 criteria

3. **Enter Classification Details**:
   - **Cost Classification**: Expense or Capitalized
   - **Is Capital Improvement**: Yes/No
   - **Life Extension Months**: If capitalized, how many months
   - **Capitalization Threshold Used**: Which threshold triggered capitalization
   - **Review Notes**: Justification for classification

4. **Save Classification**
   - Status: Ready for GL posting
   - `reviewed_by` and `reviewed_at` recorded
   - `cost_classification` updated
   - Notification sent

---

## GL Posting and Asset Updates

### Step 8: GL Posting

**Location**: Automatic after classification (or manual trigger)

**Process**:

#### A. Expense Classification

**Journal Entry**:
```
Account                          Debit          Credit
Maintenance Expense              XXX            -
Maintenance WIP                  -              XXX
```

**Database Updates**:
- `gl_posted`: true
- `gl_journal_id`: Reference to journal entry
- `gl_posted_at`: Timestamp
- Journal entry created in `journals` table
- Journal items created in `journal_items` table
- GL transactions created

**Asset Impact**:
- Asset cost: **No change**
- Depreciation: **No change**
- Maintenance history: Recorded

---

#### B. Capitalized Classification

**Journal Entry**:
```
Account                          Debit          Credit
Fixed Asset (Improvement)        XXX            -
Maintenance WIP                  -              XXX
```

**Database Updates**:
- `gl_posted`: true
- `gl_journal_id`: Reference to journal entry
- `gl_posted_at`: Timestamp
- Journal entry created
- GL transactions created

**Asset Updates**:
1. **Update Asset Cost**:
   ```php
   $asset->purchase_cost += $capitalizedAmount;
   $asset->save();
   ```

2. **Recalculate Depreciation**:
   - New depreciation basis = Original cost + Capitalized amount
   - Recalculate annual/monthly depreciation
   - Update depreciation schedule
   - Future depreciation entries adjusted

3. **Update Useful Life** (if extended):
   ```php
   $asset->useful_life_months += $lifeExtensionMonths;
   $asset->save();
   ```

4. **TRA Tax Book Update** (if applicable):
   - Update tax book value
   - Adjust tax depreciation
   - Update tax depreciation schedule

---

### Step 9: Maintenance History Creation

**Location**: Automatic after GL posting

**Process**:

1. **Create History Record**:
   - Table: `maintenance_history`
   - Links to:
     - Asset
     - Work Order
     - Maintenance Type
   - Records:
     - Completion date
     - Total cost
     - Cost classification
     - Life extension (if capitalized)
     - Vendor/Technician
     - Work performed
     - Notes
     - GL journal reference

2. **Update Asset History**:
   - Last maintenance date
   - Last maintenance cost
   - Maintenance frequency tracking
   - Next maintenance schedule (for preventive)
   - Total maintenance cost YTD

---

## Preventive Maintenance Scheduling

### Scheduled Maintenance

**Purpose**: Automate preventive maintenance based on time or usage

**Process**:

1. **Configure Maintenance Schedule**:
   - Asset level: Set maintenance interval (months or usage hours)
   - Maintenance type: Link to preventive maintenance type
   - Next maintenance date: Calculated automatically

2. **System Alerts**:
   - Alert when maintenance is due
   - Alert when maintenance is overdue
   - Dashboard shows upcoming maintenance

3. **Auto-Create Requests**:
   - System can auto-create maintenance requests
   - Based on schedule or usage
   - Requires approval before work order creation

---

## Approval Workflow

### Workflow Levels

#### Level 1: Supervisor Only
- Supervisor reviews and approves
- Suitable for routine maintenance
- Lower cost thresholds

#### Level 2: Supervisor + Finance (Future)
- Supervisor reviews first
- Finance approval for significant costs
- Higher cost thresholds

### Approval Process

1. **Request** → **Pending Approval**
   - Initiator submits request
   - Status locked for editing

2. **Pending Approval** → **Approved**
   - Supervisor approves
   - Ready for work order creation

3. **Pending Approval** → **Rejected**
   - Supervisor rejects with reason
   - Request closed

4. **Work Order** → **Approved**
   - Work order approved
   - Ready for execution

---

## Reports and Analytics

### Dashboard KPIs

**Location**: `/asset-management/maintenance`

**Metrics**:
1. **Total Requests**: All maintenance requests
2. **Pending Requests**: Awaiting approval
3. **Open Work Orders**: Approved, In Progress, On Hold
4. **Completed This Month**: Completed work orders
5. **Total Cost YTD**: Year-to-date maintenance costs
6. **Capitalized vs. Expensed**: Percentage breakdown
7. **Average Cost per Asset**: Cost efficiency metric
8. **Downtime Hours**: Total asset downtime
9. **Preventive vs. Corrective Ratio**: Maintenance strategy effectiveness

---

### Operational Reports

#### 1. Upcoming Maintenance Schedule
- Preventive maintenance due
- Sorted by due date
- Asset, type, estimated cost
- Filter by asset, department, type

#### 2. Open Work Orders
- Status: Approved, In Progress, On Hold
- Filter by asset, department, vendor
- Estimated vs. actual costs
- Downtime tracking

#### 3. Downtime Report
- Asset downtime by period
- Impact on operations
- Trend analysis
- By asset, department, type

#### 4. Vendor Performance
- Vendor response time
- Cost comparison
- Quality ratings
- Completion time

#### 5. Maintenance History
- Complete maintenance history by asset
- Cost trends
- Frequency analysis
- Classification breakdown

---

### Financial Reports

#### 1. Cost per Asset/Department
- Total maintenance cost by asset
- Cost by department/cost center
- Budget vs. actual
- Cost per asset category

#### 2. Capitalized vs. Expensed
- Breakdown by classification
- Trend over time
- Impact on P&L and Balance Sheet
- Capitalization ratio

#### 3. Life Extension Analysis
- Assets with extended useful life
- Capitalized amounts
- ROI on capital improvements
- Depreciation impact

#### 4. Budget vs. Actual
- Maintenance budget performance
- Variance analysis
- Forecast vs. actual
- By department, asset category

#### 5. Depreciation Forecast
- Impact of capitalized maintenance
- Revised depreciation schedules
- Tax implications
- Future depreciation projections

---

## Integration Points

### 1. Fixed Assets Module

**Integration**:
- Asset selection in maintenance requests
- Asset cost updates for capitalized maintenance
- Depreciation recalculation
- Asset history updates
- Next maintenance schedule updates

**Data Flow**:
- Maintenance → Asset cost increase
- Maintenance → Depreciation basis update
- Maintenance → Asset history record
- Maintenance → Useful life extension

---

### 2. Inventory Module

**Integration**:
- Material requisition from inventory
- Stock deduction for materials used
- Cost tracking from inventory items

**Data Flow**:
- Work Order → Material Requisition
- Material Issue → Inventory deduction
- Material Cost → Work Order Cost record

---

### 3. Procurement Module

**Integration**:
- Vendor selection for external work
- Purchase Order creation
- Purchase Invoice linking
- Vendor performance tracking

**Data Flow**:
- Work Order → Vendor selection
- Vendor Invoice → Work Order Cost
- PO/Invoice → GL posting

---

### 4. Accounts Payable

**Integration**:
- Vendor invoice posting
- Payment tracking
- Accrual management

**Data Flow**:
- Vendor Invoice → AP posting
- Payment → Invoice settlement
- Accrual → GL adjustment

---

### 5. Finance (GL)

**Integration**:
- Automatic journal entry creation
- Account mapping from settings
- Cost classification posting

**Data Flow**:
- Work Order Completion → Classification
- Classification → Journal Entry
- Journal Entry → GL Transactions

---

### 6. HR/Payroll

**Integration** (Future):
- Technician timesheet integration
- Labor cost allocation
- Employee cost rates

**Data Flow**:
- Technician Assignment → Timesheet
- Timesheet → Labor Cost
- Labor Cost → Work Order Cost

---

### 7. Budgeting/Cost Control

**Integration**:
- Budget validation
- Cost center allocation
- Budget vs. actual tracking

**Data Flow**:
- Work Order → Budget Check
- Cost Center → Budget Allocation
- Actual Cost → Budget Variance

---

## User Roles and Permissions

### Required Permissions

1. **Maintenance Request Creator**
   - `create maintenance requests`
   - `view maintenance requests`
   - `edit maintenance requests` (own requests, draft only)

2. **Supervisor**
   - `approve maintenance requests`
   - `view maintenance requests`
   - `view work orders`
   - `create work orders`

3. **Maintenance Technician**
   - `view work orders`
   - `execute work orders`
   - `add work order costs`
   - `complete work orders`
   - `view maintenance history`

4. **Finance/Asset Accountant**
   - `review work orders`
   - `classify work orders`
   - `post to GL`
   - `view maintenance reports`
   - `view maintenance history`

5. **Maintenance Administrator**
   - All permissions
   - `manage maintenance types`
   - `manage maintenance settings`
   - `delete maintenance records`
   - `approve work orders`

---

## Workflow Summary Diagrams

### Maintenance Request to Completion Workflow

```
┌─────────────────────────────────────────────────────────────┐
│              MAINTENANCE REQUEST                            │
│  (Preventive/Corrective/Planned Improvement)                │
│  Status: Pending                                            │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              SUPERVISOR APPROVAL                            │
│  Status: Pending → Approved/Rejected                       │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼ (If Approved)
┌─────────────────────────────────────────────────────────────┐
│                    WORK ORDER CREATION                       │
│  - Link to Request                                          │
│  - Assign Vendor/Technician                                 │
│  - Set Estimates                                            │
│  Status: Draft → Approved                                    │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                  WORK ORDER EXECUTION                        │
│  - Start Work (Status: In Progress)                          │
│  - Capture Costs:                                           │
│    • Material (from Inventory)                             │
│    • Labor (Internal/External)                              │
│    • Other Direct Costs                                     │
│  - Journal: Dr. Maintenance WIP, Cr. Inventory/Payables   │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                  WORK ORDER COMPLETION                       │
│  - Technician Marks Complete                                │
│  - Finalize All Costs                                        │
│  - Status: Completed                                        │
│  - Cost Classification: Pending Review                     │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              FINANCE REVIEW & CLASSIFICATION                 │
│  Classification Rules:                                      │
│  • Cost < Threshold → Expense                              │
│  • Cost ≥ Threshold OR Life Extension ≥ 12mo → Capitalized │
└───────────────────────┬───────────────────────────────────────┘
                        │
            ┌───────────┴───────────┐
            │                       │
            ▼                       ▼
┌───────────────────┐    ┌──────────────────────────────┐
│   EXPENSE         │    │    CAPITALIZED               │
│                   │    │                              │
│ Dr. Maintenance   │    │ Dr. Fixed Asset              │
│    Expense        │    │    (Improvement)             │
│ Cr. Maintenance   │    │ Cr. Maintenance WIP          │
│    WIP            │    │                              │
│                   │    │ Asset Cost: +XXX             │
│ Asset Cost:       │    │ Depreciation: Recalculated  │
│   No Change       │    │ Useful Life: Extended        │
└───────────────────┘    └──────────────────────────────┘
            │                       │
            └───────────┬───────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              MAINTENANCE HISTORY CREATION                    │
│  - Record in maintenance_history                            │
│  - Update Asset History                                     │
│  - Update Next Maintenance Schedule (Preventive)            │
└─────────────────────────────────────────────────────────────┘
```

---

## Key Database Tables

1. **maintenance_types**: Maintenance type definitions
2. **maintenance_requests**: Maintenance request records
3. **asset_maintenance_work_orders**: Work order records
4. **asset_maintenance_work_order_costs**: Individual cost records
5. **maintenance_history**: Completed maintenance history
6. **maintenance_settings**: System configuration
7. **assets**: Updated with maintenance costs and history

---

## Best Practices

### 1. Preventive Maintenance

- **Schedule Regular Maintenance**: Schedule regular maintenance to prevent breakdowns
- **Track Maintenance Intervals**: Monitor and adjust intervals based on actual usage
- **Update Schedules**: Update schedules based on asset performance
- **Document Everything**: Maintain complete maintenance records

### 2. Cost Tracking

- **Capture All Costs**: Capture all costs in real-time during execution
- **Link to Source Documents**: Link to POs, invoices, and receipts
- **Maintain Audit Trail**: Ensure complete audit trail of all costs
- **Review Estimates**: Compare estimated vs. actual costs regularly

### 3. Classification

- **Review All Work Orders**: Review all completed work orders promptly
- **Apply Rules Consistently**: Apply capitalization rules consistently
- **Document Decisions**: Document classification decisions and rationale
- **IAS 16 Compliance**: Ensure compliance with IAS 16 for capitalization

### 4. Asset Management

- **Update Asset Costs Promptly**: Update asset costs promptly after capitalization
- **Recalculate Depreciation**: Recalculate depreciation after capitalization
- **Maintain Accurate History**: Maintain accurate asset maintenance history
- **Track Life Extensions**: Track and document useful life extensions

### 5. Reporting

- **Review Costs Regularly**: Review maintenance costs regularly
- **Analyze Trends**: Analyze trends and patterns
- **Optimize Strategies**: Optimize maintenance strategies based on data
- **Budget Management**: Monitor budget vs. actual performance

---

## Troubleshooting

### Common Issues

1. **Maintenance Request Not Appearing**:
   - Check branch filter
   - Verify company_id matches
   - Check status filter
   - Review user permissions

2. **Work Order Not Creating**:
   - Verify maintenance request is approved
   - Check work order approval status
   - Review user permissions
   - Check for errors in logs

3. **Costs Not Posting to GL**:
   - Verify maintenance settings (GL accounts)
   - Check work order completion status
   - Ensure classification is complete
   - Review GL posting logic

4. **Asset Cost Not Updating**:
   - Verify capitalization classification
   - Check GL posting status
   - Review asset update logic
   - Verify capitalized amount

5. **Depreciation Not Recalculating**:
   - Verify asset cost was updated
   - Check depreciation service is triggered
   - Review depreciation basis calculation
   - Verify useful life updates

6. **Classification Not Working**:
   - Check capitalization thresholds
   - Verify cost amounts
   - Review classification rules
   - Check life extension values

7. **Reports Not Showing Data**:
   - Check date filters
   - Verify company/branch selection
   - Review user permissions
   - Check data exists in database

8. **Material Costs Not Deducting from Inventory**:
   - Verify inventory integration
   - Check inventory item selection
   - Review inventory deduction logic
   - Check inventory module configuration

---

## Conclusion

The Asset Maintenance Management System provides a complete end-to-end solution for managing asset maintenance from request to accounting integration. By following this flow, organizations can:

- **Improve Asset Reliability**: Through preventive maintenance scheduling
- **Control Costs**: By tracking and classifying all maintenance expenses
- **Ensure Compliance**: Through proper accounting treatment per IAS 16
- **Optimize Operations**: Through analytics and reporting
- **Extend Asset Life**: Through proper maintenance and capital improvements

For technical support or feature requests, contact the development team.

---

**Document Version**: 1.0  
**Last Updated**: November 2025  
**System**: SmartAccounting - Asset Maintenance Management Module
