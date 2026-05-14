# Revaluation & Impairment Management System - Complete Flow Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Pre-Setup Configuration](#pre-setup-configuration)
3. [Asset Revaluation Flow](#asset-revaluation-flow)
4. [Asset Impairment Flow](#asset-impairment-flow)
5. [Impairment Reversal Flow](#impairment-reversal-flow)
6. [GL Posting and Accounting Integration](#gl-posting-and-accounting-integration)
7. [Approval Workflow](#approval-workflow)
8. [Reports and Analytics](#reports-and-analytics)
9. [Integration Points](#integration-points)
10. [User Roles and Permissions](#user-roles-and-permissions)
11. [Best Practices](#best-practices)
12. [Troubleshooting](#troubleshooting)

---

## System Overview

The Revaluation & Impairment Management System is a comprehensive module within the SmartAccounting Assets Management system that handles asset value adjustments, fair value measurements, and impairment testing in compliance with IFRS (IAS 16, IAS 36) and IPSAS (IPSAS 17, IPSAS 21, IPSAS 26) standards.

### Key Features
- **Asset Revaluation**: Upward and downward revaluations based on fair value
- **Impairment Testing**: Recognition and measurement of impairment losses
- **Impairment Reversals**: Reversal of previously recognized impairment losses
- **Automatic Depreciation Recalculation**: Adjusting depreciation based on new carrying amounts
- **GL Integration**: Automatic journal entry generation
- **Approval Workflow**: Multi-level approval for revaluation/impairment actions
- **Audit Trail**: Complete history of all revaluation and impairment transactions
- **Document Management**: Support for valuation reports and supporting documents

### Compliance Standards
- **IAS 16**: Property, Plant and Equipment (Revaluation Model)
- **IAS 36**: Impairment of Assets
- **IPSAS 17**: Property, Plant and Equipment
- **IPSAS 21**: Impairment of Non-Cash-Generating Assets
- **IPSAS 26**: Impairment of Cash-Generating Assets

---

## Pre-Setup Configuration

### 1. Revaluation Settings Configuration

**Location**: `/asset-management/revaluations/settings`

**Purpose**: Configure revaluation models, frequencies, and default chart accounts per asset category.

**Steps**:

1. Navigate to Asset Management → Revaluations → Settings
2. For each Asset Category, configure:

#### A. Valuation Model
- **Cost Model**: Assets carried at cost less accumulated depreciation
- **Revaluation Model**: Assets carried at fair value less accumulated depreciation

#### B. Revaluation Frequency
- **Annual**: Automatic revaluation every year
- **Biennial**: Automatic revaluation every 2 years
- **Ad Hoc**: Manual revaluation as needed

#### C. Revaluation Interval
- Number of years between automatic revaluations (1-10 years)
- Only applicable for Annual/Biennial frequency

#### D. Default Chart Accounts
- **Revaluation Reserve Account**: Equity account for revaluation gains
- **Impairment Loss Account**: Expense account for impairment losses
- **Impairment Reversal Account**: Income account for impairment reversals
- **Accumulated Impairment Account**: Contra-asset account for accumulated impairment

#### E. Approval & Workflow Settings
- **Require Valuation Report**: Yes/No
- **Require Approval**: Yes/No
- **Minimum Approval Levels**: 1 (Finance Manager) or 2 (Finance Manager + CFO/Board)

**Example Configuration**:
```
Category: Buildings
- Valuation Model: Revaluation
- Frequency: Annual
- Interval: 1 year
- Revaluation Reserve Account: 3100 - Revaluation Reserve
- Require Valuation Report: Yes
- Require Approval: Yes
- Min Approval Levels: 2
```

---

## Asset Revaluation Flow

### Step 1: Revaluation Initiation

**Location**: `/asset-management/revaluations/create`

**Triggering Events**:
- **Scheduled Revaluation**: Based on configured frequency (Annual/Biennial)
- **Ad Hoc Revaluation**: Market value changes, inflation adjustments, asset improvements
- **Regulatory Requirement**: Compliance with accounting standards

**Process**:

1. **Select Asset**
   - System auto-fetches:
     - Asset code and name
     - Current carrying amount
     - Accumulated depreciation
     - Asset category
     - Current valuation model

2. **Enter Revaluation Details**:
   - **Revaluation Date**: Date of revaluation
   - **Valuation Model**: Cost or Revaluation (from category default)
   - **Fair Value**: New fair value of the asset
   - **Reason**: Detailed reason for revaluation
   - System calculates:
     - Carrying amount before revaluation
     - Revaluation increase/decrease
     - New carrying amount after revaluation

3. **Valuer Information** (if required):
   - Valuer name
   - Valuer license number
   - Valuer company
   - Valuation report reference
   - Upload valuation report (PDF/DOC)

4. **Asset Adjustments** (Optional):
   - **Useful Life After**: Updated useful life in months
   - **Residual Value After**: Updated residual value
   - **Revaluation Reserve Account**: Override category default if needed

5. **Attachments**:
   - Upload supporting documents
   - Valuation reports
   - Market analysis documents

6. **Submit Revaluation**
   - System generates unique Revaluation Number (e.g., "REV-2025-00001")
   - Status: `draft`
   - Revaluation calculations performed automatically

---

### Step 2: Revaluation Calculation

**Automatic Process** (performed by system):

1. **Calculate Carrying Amount Before**:
   ```
   Carrying Amount = Asset Cost - Accumulated Depreciation
   ```

2. **Calculate Revaluation Difference**:
   ```
   Revaluation Increase = Fair Value - Carrying Amount (if Fair Value > Carrying Amount)
   Revaluation Decrease = Carrying Amount - Fair Value (if Fair Value < Carrying Amount)
   ```

3. **Determine Accounting Treatment**:

   **A. Revaluation Increase**:
   - Credit to Revaluation Reserve (Equity)
   - Increase asset carrying amount
   - Recalculate depreciation basis

   **B. Revaluation Decrease**:
   - First: Write off against Revaluation Reserve (if available)
   - Remaining: Charge to P&L (Revaluation Loss)
   - Decrease asset carrying amount
   - Recalculate depreciation basis

4. **Update Asset Records**:
   - New carrying amount
   - Updated depreciation basis
   - Useful life adjustment (if provided)
   - Residual value adjustment (if provided)

---

### Step 3: Submit for Approval

**Location**: `/asset-management/revaluations/{id}`

**Process**:

1. **Review Revaluation**
   - Verify fair value
   - Check calculations
   - Review supporting documents
   - Confirm reason for revaluation

2. **Submit for Approval**
   - Status changes to `pending_approval`
   - Notification sent to approvers
   - Revaluation locked for editing

3. **Approval Requirements**:
   - **Level 1**: Finance Manager approval (if min_approval_levels = 1)
   - **Level 2**: Finance Manager + CFO/Board approval (if min_approval_levels = 2)

---

### Step 4: Approval Process

**Location**: `/asset-management/revaluations/{id}`

**Process**:

#### A. Finance Manager Approval

1. **Review Revaluation**:
   - Verify fair value is reasonable
   - Check valuation report (if required)
   - Review calculations
   - Confirm compliance with accounting standards

2. **Decision Options**:

   **Approve**:
   - Status: `approved` (if single-level approval)
   - Status: `pending_approval` (if two-level approval)
   - `finance_manager_id` and `finance_manager_approved_at` recorded
   - Approval notes saved

   **Reject**:
   - Status: `rejected`
   - Rejection reason recorded
   - Revaluation returned to draft (if allowed)

#### B. CFO/Board Approval (if required)

1. **Review Revaluation**:
   - Final review of significant revaluations
   - Verify strategic alignment
   - Confirm board approval (if required)

2. **Approve**:
   - Status: `approved`
   - `cfo_approver_id` and `approved_at` recorded
   - Ready for GL posting

---

### Step 5: GL Posting

**Location**: `/asset-management/revaluations/{id}`

**Process**:

1. **Post to General Ledger**
   - Click "Post to GL" button
   - System generates journal entries automatically

2. **Journal Entry Creation**:

   **For Revaluation Increase**:
   ```
   Dr. Fixed Asset (Revaluation)        XXX
       Cr. Revaluation Reserve           XXX
   ```

   **For Revaluation Decrease** (First against Reserve):
   ```
   Dr. Revaluation Reserve               XXX
       Cr. Fixed Asset (Revaluation)     XXX
   ```

   **For Revaluation Decrease** (Excess to P&L):
   ```
   Dr. Revaluation Loss (P&L)            XXX
       Cr. Fixed Asset (Revaluation)     XXX
   ```

3. **System Actions**:
   - Journal entry created in `journals` table
   - Journal items created in `journal_items` table
   - `gl_posted`: true
   - `gl_journal_id`: Reference to journal entry
   - `gl_posted_at`: Timestamp
   - Status: `posted`

4. **Asset Updates**:
   - Asset carrying amount updated
   - Depreciation basis recalculated
   - Depreciation schedule updated
   - Revaluation reserve balance updated

---

## Asset Impairment Flow

### Step 1: Impairment Testing Initiation

**Location**: `/asset-management/impairments/create`

**Triggering Events**:
- **External Indicators**:
  - Market value decline
  - Technological obsolescence
  - Legal/regulatory changes
  - Economic environment changes
- **Internal Indicators**:
  - Physical damage
  - Asset idle or discontinued use
  - Asset performance below expectations
  - Significant decrease in asset value

**Process**:

1. **Select Asset**
   - System auto-fetches:
     - Asset code and name
     - Current carrying amount
     - Asset category
     - Previous impairment history

2. **Enter Impairment Details**:
   - **Impairment Date**: Date of impairment test
   - **Impairment Type**: Individual Asset or Cash Generating Unit (CGU)
   - **Impairment Loss Account**: Override category default if needed

3. **Select Impairment Indicators**:
   - ☑ Physical Damage
   - ☑ Obsolescence
   - ☑ Technological Change
   - ☑ Idle Asset
   - ☑ Market Decline
   - ☑ Legal/Regulatory Changes
   - Other indicators (text field)

4. **Calculate Recoverable Amount**:

   **A. Fair Value Less Costs to Sell**:
   - Enter market value
   - Enter disposal costs
   - Calculate: Market Value - Disposal Costs

   **B. Value in Use** (Choose one):
   
   **Option 1: Manual Entry**
   - Enter value in use directly

   **Option 2: Calculate from Cash Flows**:
   - Enter discount rate (%)
   - Enter annual cash flow projections (Year 1, Year 2, Year 3, etc.)
   - System calculates present value:
     ```
     Value in Use = Σ (Cash Flow Year N / (1 + Discount Rate)^N)
     ```

5. **Recoverable Amount Determination**:
   ```
   Recoverable Amount = Higher of:
   - Fair Value Less Costs to Sell
   - Value in Use
   ```

6. **Impairment Loss Calculation**:
   ```
   Impairment Loss = Carrying Amount - Recoverable Amount
   (Only if Carrying Amount > Recoverable Amount)
   ```

7. **Asset Adjustments** (Optional):
   - **Useful Life After**: Updated useful life
   - **Residual Value After**: Updated residual value

8. **Documentation**:
   - Upload impairment test report
   - Upload supporting documents
   - Enter notes/justification

9. **Submit Impairment**
   - System generates unique Impairment Number (e.g., "IMP-2025-00001")
   - Status: `draft`
   - Impairment calculations performed automatically

---

### Step 2: Impairment Calculation

**Automatic Process** (performed by system):

1. **Calculate Carrying Amount**:
   ```
   Carrying Amount = Asset Cost - Accumulated Depreciation - Accumulated Impairment
   ```

2. **Determine Recoverable Amount**:
   ```
   Recoverable Amount = MAX(Fair Value Less Costs, Value in Use)
   ```

3. **Calculate Impairment Loss**:
   ```
   If Carrying Amount > Recoverable Amount:
       Impairment Loss = Carrying Amount - Recoverable Amount
   Else:
       No Impairment (Impairment Loss = 0)
   ```

4. **Update Asset Records**:
   - New carrying amount (after impairment)
   - Accumulated impairment increased
   - Depreciation basis adjusted
   - Useful life/residual value updated (if provided)

---

### Step 3: Submit for Approval

**Location**: `/asset-management/impairments/{id}`

**Process**:

1. **Review Impairment**
   - Verify recoverable amount calculation
   - Check impairment indicators
   - Review supporting documents
   - Confirm impairment loss amount

2. **Submit for Approval**
   - Status changes to `pending_approval`
   - Notification sent to approvers
   - Impairment locked for editing

---

### Step 4: Approval Process

**Similar to Revaluation Approval** (see Step 4 in Revaluation Flow)

---

### Step 5: GL Posting

**Location**: `/asset-management/impairments/{id}`

**Process**:

1. **Post to General Ledger**
   - Click "Post to GL" button
   - System generates journal entries automatically

2. **Journal Entry Creation**:

   **For Impairment Loss**:
   ```
   Dr. Impairment Loss (P&L)             XXX
       Cr. Accumulated Impairment        XXX
   ```

   **Alternative (if using direct write-down)**:
   ```
   Dr. Impairment Loss (P&L)             XXX
       Cr. Fixed Asset                    XXX
   ```

3. **System Actions**:
   - Journal entry created
   - `gl_posted`: true
   - `gl_journal_id`: Reference to journal entry
   - `gl_posted_at`: Timestamp
   - Status: `posted`

4. **Asset Updates**:
   - Asset carrying amount decreased
   - Accumulated impairment increased
   - Depreciation recalculated based on new carrying amount

---

## Impairment Reversal Flow

### Step 1: Reversal Initiation

**Location**: `/asset-management/impairments/{id}/create-reversal`

**Triggering Events**:
- Asset value recovered
- Improved market conditions
- Asset performance improved
- Change in use of asset

**Prerequisites**:
- Original impairment must be posted
- Recoverable amount now exceeds carrying amount
- Reversal amount cannot exceed original impairment loss

**Process**:

1. **Select Original Impairment**
   - System displays:
     - Original impairment number
     - Original impairment loss
     - Total previously reversed
     - Remaining reversible amount

2. **Enter Reversal Details**:
   - **Reversal Date**: Date of reversal
   - **Reversal Amount**: Amount to reverse (cannot exceed remaining reversible)
   - **Impairment Reversal Account**: Override category default if needed
   - **Notes**: Reason for reversal

3. **System Validation**:
   - Check reversal amount ≤ remaining reversible amount
   - Verify original impairment is posted
   - Confirm asset still exists and is active

4. **Submit Reversal**
   - System generates unique Reversal Number (e.g., "IMP-2025-00002")
   - Status: `draft`
   - Marked as `is_reversal = true`
   - Linked to original impairment

---

### Step 2: Reversal Calculation

**Automatic Process**:

1. **Calculate New Carrying Amount**:
   ```
   New Carrying Amount = Current Carrying Amount + Reversal Amount
   ```

2. **Update Accumulated Impairment**:
   ```
   Accumulated Impairment = Previous Accumulated Impairment - Reversal Amount
   ```

3. **Limitations**:
   - Reversal cannot increase carrying amount above original cost (before impairment)
   - Reversal cannot exceed original impairment loss
   - Reversal cannot exceed remaining reversible amount

---

### Step 3: Approval and GL Posting

**Similar to Impairment Flow** (Steps 4-5)

**Journal Entry for Reversal**:
```
Dr. Accumulated Impairment               XXX
    Cr. Impairment Reversal (P&L)        XXX
```

---

## GL Posting and Accounting Integration

### Revaluation Journal Entries

#### Scenario 1: Revaluation Increase

**Journal Entry**:
```
Account                          Debit          Credit
Fixed Asset (Revaluation)        XXX            -
Revaluation Reserve              -              XXX
```

**Impact**:
- Asset carrying amount: **Increased**
- Revaluation Reserve: **Increased** (Equity)
- Depreciation basis: **Recalculated**

---

#### Scenario 2: Revaluation Decrease (First against Reserve)

**Journal Entry**:
```
Account                          Debit          Credit
Revaluation Reserve              XXX            -
Fixed Asset (Revaluation)        -              XXX
```

**Impact**:
- Asset carrying amount: **Decreased**
- Revaluation Reserve: **Decreased** (Equity)
- Depreciation basis: **Recalculated**

---

#### Scenario 3: Revaluation Decrease (Excess to P&L)

**Journal Entry**:
```
Account                          Debit          Credit
Revaluation Loss (P&L)           XXX            -
Fixed Asset (Revaluation)        -              XXX
```

**Impact**:
- Asset carrying amount: **Decreased**
- P&L: **Expense recognized**
- Depreciation basis: **Recalculated**

---

### Impairment Journal Entries

#### Impairment Loss Recognition

**Journal Entry**:
```
Account                          Debit          Credit
Impairment Loss (P&L)            XXX            -
Accumulated Impairment           -              XXX
```

**Impact**:
- Asset carrying amount: **Decreased**
- Accumulated Impairment: **Increased** (Contra-asset)
- P&L: **Expense recognized**
- Depreciation basis: **Adjusted**

---

#### Impairment Reversal

**Journal Entry**:
```
Account                          Debit          Credit
Accumulated Impairment           XXX            -
Impairment Reversal (P&L)        -              XXX
```

**Impact**:
- Asset carrying amount: **Increased**
- Accumulated Impairment: **Decreased**
- P&L: **Income recognized**

---

### Depreciation Recalculation

**After Revaluation/Impairment**:

1. **New Depreciation Basis**:
   ```
   New Depreciation Basis = New Carrying Amount - Residual Value
   ```

2. **Recalculate Annual Depreciation**:
   ```
   Annual Depreciation = New Depreciation Basis / Remaining Useful Life
   ```

3. **Update Depreciation Schedule**:
   - Future depreciation entries adjusted
   - Historical depreciation unchanged
   - Depreciation forecast updated

---

## Approval Workflow

### Workflow Levels

#### Level 1: Finance Manager Only
- Finance Manager reviews and approves
- Suitable for routine revaluations/impairments
- Lower value thresholds

#### Level 2: Finance Manager + CFO/Board
- Finance Manager reviews first
- CFO/Board final approval required
- Suitable for significant revaluations/impairments
- Higher value thresholds

### Approval Process

1. **Draft** → **Pending Approval**
   - Initiator submits for approval
   - Status locked for editing

2. **Pending Approval** → **Approved**
   - Finance Manager approves (Level 1)
   - OR Finance Manager + CFO approve (Level 2)
   - Ready for GL posting

3. **Pending Approval** → **Rejected**
   - Approver rejects with reason
   - Returns to draft (if allowed)
   - OR closed permanently

4. **Approved** → **Posted**
   - Finance posts to GL
   - Journal entries created
   - Asset records updated

---

## Reports and Analytics

### Operational Reports

#### 1. Revaluation Summary Report
- All revaluations by period
- Revaluation increases/decreases
- Assets revalued
- Total revaluation impact
- Filter by: Asset, Category, Date Range, Status

#### 2. Impairment Register
- All impairments by period
- Impairment losses recognized
- Reversals recorded
- Net impairment impact
- Filter by: Asset, Category, Date Range, Type

#### 3. Revaluation Reserve Movements
- Opening balance
- Additions (revaluation increases)
- Deductions (revaluation decreases)
- Closing balance
- By asset category

#### 4. Asset Carrying Amount History
- Historical carrying amounts
- Revaluation adjustments
- Impairment adjustments
- Depreciation impact
- Timeline view

### Financial Reports

#### 1. Revaluation Impact on Balance Sheet
- Asset values before/after revaluation
- Revaluation reserve balance
- Impact on equity

#### 2. Impairment Impact on P&L
- Impairment losses by period
- Impairment reversals
- Net impact on profit/loss

#### 3. Compliance Reports
- Revaluation frequency compliance
- Impairment testing compliance
- Documentation completeness
- Approval workflow compliance

---

## Integration Points

### 1. Fixed Assets Module

**Integration**:
- Asset selection in revaluation/impairment forms
- Asset carrying amount updates
- Depreciation recalculation
- Asset history updates

**Data Flow**:
- Revaluation → Asset carrying amount increase
- Impairment → Asset carrying amount decrease
- Both → Depreciation basis update

---

### 2. General Ledger (GL)

**Integration**:
- Automatic journal entry creation
- Account mapping from category settings
- Transaction posting
- Audit trail

**Data Flow**:
- Revaluation/Impairment Approval → Journal Entry Creation
- Journal Entry → GL Transactions
- GL Transactions → Financial Statements

---

### 3. Chart of Accounts

**Integration**:
- Revaluation reserve account
- Impairment loss/reversal accounts
- Accumulated impairment account
- Account validation

**Data Flow**:
- Category Settings → Default Accounts
- Revaluation/Impairment → Account Selection
- GL Posting → Account Debits/Credits

---

### 4. Document Management

**Integration**:
- Valuation report storage
- Impairment test report storage
- Supporting document attachments
- File upload/download

**Data Flow**:
- Form Upload → Storage System
- Storage → Database Reference
- View → File Download

---

## User Roles and Permissions

### Required Permissions

1. **Revaluation Creator**
   - `create asset revaluations`
   - `view asset revaluations`
   - `edit asset revaluations` (draft only)

2. **Impairment Creator**
   - `create asset impairments`
   - `view asset impairments`
   - `edit asset impairments` (draft only)

3. **Finance Manager**
   - `approve asset revaluations`
   - `approve asset impairments`
   - `view asset revaluations`
   - `view asset impairments`

4. **CFO/Board**
   - `approve asset revaluations` (Level 2)
   - `approve asset impairments` (Level 2)
   - `view asset revaluations`
   - `view asset impairments`

5. **Finance/Accountant**
   - `post asset revaluations`
   - `post asset impairments`
   - `view asset revaluations`
   - `view asset impairments`

6. **Revaluation Administrator**
   - All permissions
   - `manage revaluation settings`
   - `delete asset revaluations`
   - `delete asset impairments`

---

## Workflow Summary Diagrams

### Revaluation Workflow

```
┌─────────────────────────────────────────────────────────────┐
│              REVALUATION INITIATION                           │
│  - Select Asset                                               │
│  - Enter Fair Value                                           │
│  - Provide Valuer Information                                 │
│  - Upload Valuation Report                                    │
│  Status: Draft                                                │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              REVALUATION CALCULATION                          │
│  - Calculate Carrying Amount Before                           │
│  - Calculate Revaluation Increase/Decrease                    │
│  - Determine Accounting Treatment                             │
│  - Update Asset Records                                       │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              SUBMIT FOR APPROVAL                              │
│  Status: Pending Approval                                     │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              APPROVAL WORKFLOW                                │
│                                                               │
│  Level 1: Finance Manager → Approve                          │
│  OR                                                           │
│  Level 2: Finance Manager → CFO/Board → Approve              │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              GL POSTING                                       │
│  - Generate Journal Entries                                   │
│  - Post to General Ledger                                     │
│  - Update Asset Carrying Amount                               │
│  - Recalculate Depreciation                                   │
│  Status: Posted                                               │
└─────────────────────────────────────────────────────────────┘
```

### Impairment Workflow

```
┌─────────────────────────────────────────────────────────────┐
│              IMPAIRMENT TESTING INITIATION                    │
│  - Select Asset                                               │
│  - Select Impairment Indicators                               │
│  - Calculate Recoverable Amount:                              │
│    • Fair Value Less Costs to Sell                            │
│    • Value in Use (from Cash Flows)                           │
│  - Calculate Impairment Loss                                  │
│  Status: Draft                                                │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              IMPAIRMENT CALCULATION                           │
│  - Carrying Amount > Recoverable Amount?                      │
│  - If Yes: Impairment Loss = Difference                      │
│  - Update Asset Records                                       │
│  - Update Accumulated Impairment                              │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              SUBMIT FOR APPROVAL                              │
│  Status: Pending Approval                                     │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              APPROVAL WORKFLOW                                │
│  (Similar to Revaluation)                                     │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              GL POSTING                                       │
│  - Generate Journal Entry:                                    │
│    Dr. Impairment Loss                                        │
│    Cr. Accumulated Impairment                                 │
│  - Update Asset Carrying Amount                               │
│  - Recalculate Depreciation                                   │
│  Status: Posted                                               │
└─────────────────────────────────────────────────────────────┘
```

### Impairment Reversal Workflow

```
┌─────────────────────────────────────────────────────────────┐
│              REVERSAL INITIATION                              │
│  - Select Original Impairment                                 │
│  - Enter Reversal Amount                                      │
│  - Provide Reason for Reversal                                │
│  Status: Draft                                                │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              REVERSAL VALIDATION                               │
│  - Reversal Amount ≤ Remaining Reversible?                     │
│  - Original Impairment Posted?                                │
│  - Asset Still Active?                                         │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              APPROVAL & GL POSTING                            │
│  - Approve Reversal                                           │
│  - Generate Journal Entry:                                    │
│    Dr. Accumulated Impairment                                 │
│    Cr. Impairment Reversal                                    │
│  - Update Asset Carrying Amount                               │
│  Status: Posted                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## Key Database Tables

1. **asset_revaluations**: Revaluation transaction records
2. **asset_impairments**: Impairment transaction records
3. **revaluation_reserves**: Revaluation reserve balance tracking
4. **assets**: Updated with revaluation/impairment fields
5. **asset_categories**: Revaluation settings per category
6. **journals**: GL journal entries
7. **journal_items**: GL journal entry line items

---

## Best Practices

### 1. Revaluation Management

- **Regular Reviews**: Conduct revaluations at least annually for revaluation model assets
- **Professional Valuations**: Use qualified valuers for significant assets
- **Documentation**: Maintain complete valuation reports and supporting documents
- **Consistency**: Apply revaluation model consistently across asset classes
- **Timing**: Perform revaluations at consistent dates (e.g., year-end)

### 2. Impairment Testing

- **Regular Testing**: Test for impairment indicators at each reporting date
- **Comprehensive Indicators**: Consider both external and internal indicators
- **Recoverable Amount**: Always use the higher of Fair Value Less Costs or Value in Use
- **Cash Flow Projections**: Use realistic, supportable cash flow forecasts
- **Discount Rates**: Use appropriate discount rates reflecting asset-specific risks

### 3. Documentation

- **Valuation Reports**: Maintain professional valuation reports for all revaluations
- **Impairment Test Reports**: Document impairment testing methodology and assumptions
- **Supporting Documents**: Keep market analysis, cash flow projections, discount rate calculations
- **Approval Records**: Maintain complete approval workflow documentation

### 4. Compliance

- **Accounting Standards**: Ensure compliance with IAS 16, IAS 36, IPSAS 17, IPSAS 21, IPSAS 26
- **Disclosure Requirements**: Prepare required financial statement disclosures
- **Audit Trail**: Maintain complete audit trail of all transactions
- **Review Process**: Regular review of revaluation/impairment policies and procedures

### 5. System Usage

- **Configuration**: Properly configure category settings before creating transactions
- **Approval Workflow**: Follow approval workflow as configured
- **GL Posting**: Post to GL promptly after approval
- **Asset Updates**: Verify asset records are updated correctly after posting

---

## Troubleshooting

### Common Issues

1. **Revaluation Not Appearing in List**:
   - Check branch filter
   - Verify company_id matches
   - Check status filter
   - Review user permissions

2. **Cannot Post to GL**:
   - Verify revaluation/impairment is approved
   - Check GL accounts are configured
   - Verify journal entry service is working
   - Review error logs

3. **Asset Carrying Amount Not Updating**:
   - Verify GL posting completed successfully
   - Check asset update logic in service
   - Review revaluation/impairment calculations
   - Check for errors in logs

4. **Depreciation Not Recalculating**:
   - Verify asset carrying amount was updated
   - Check depreciation service is triggered
   - Review depreciation basis calculation
   - Verify useful life/residual value updates

5. **Cannot Create Reversal**:
   - Verify original impairment is posted
   - Check remaining reversible amount > 0
   - Confirm reversal amount ≤ remaining reversible
   - Review asset status

6. **Reports Not Showing Data**:
   - Check date filters
   - Verify company/branch selection
   - Review user permissions
   - Check data exists in database

---

## Conclusion

The Revaluation & Impairment Management System provides a complete end-to-end solution for managing asset value adjustments in compliance with IFRS and IPSAS standards. By following this flow, organizations can:

- **Ensure Compliance**: Meet IAS 16, IAS 36, IPSAS 17, IPSAS 21, IPSAS 26 requirements
- **Maintain Accuracy**: Keep asset values and depreciation calculations current
- **Control Processes**: Implement proper approval workflows and documentation
- **Generate Reports**: Produce required financial statement disclosures
- **Audit Trail**: Maintain complete history of all value adjustments

For technical support or feature requests, contact the development team.

---

**Document Version**: 1.0  
**Last Updated**: November 2025  
**System**: SmartAccounting - Revaluation & Impairment Management Module

