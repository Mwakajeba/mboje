# IFRS 5 Held for Sale (HFS) & Discontinued Operations - Complete Flow Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Pre-Setup Configuration](#pre-setup-configuration)
3. [HFS Request Flow](#hfs-request-flow)
4. [HFS Valuation and Measurement Flow](#hfs-valuation-and-measurement-flow)
5. [HFS Disposal Flow](#hfs-disposal-flow)
6. [Discontinued Operations Flow](#discontinued-operations-flow)
7. [HFS Cancellation Flow](#hfs-cancellation-flow)
8. [GL Posting and Accounting Integration](#gl-posting-and-accounting-integration)
9. [Approval Workflow](#approval-workflow)
10. [Reports and Analytics](#reports-and-analytics)
11. [Integration Points](#integration-points)
12. [User Roles and Permissions](#user-roles-and-permissions)
13. [Best Practices](#best-practices)
14. [Troubleshooting](#troubleshooting)

---

## System Overview

The IFRS 5 Held for Sale (HFS) & Discontinued Operations Management System is a comprehensive module within the SmartAccounting Assets Management system that handles the classification, measurement, and disposal of assets held for sale in compliance with IFRS 5 standards.

### Key Features
- **HFS Classification**: Reclassify assets as Held for Sale when IFRS 5 criteria are met
- **Automatic Depreciation Cessation**: Stop depreciation for HFS assets (except Investment Property at FV)
- **Fair Value Measurement**: Measure assets at fair value less costs to sell
- **Impairment Recognition**: Automatic impairment when FV less costs < carrying amount
- **Impairment Reversals**: Reversal of impairments (limited to original carrying amount)
- **Disposal Processing**: Record disposals with automatic gain/loss calculation
- **Discontinued Operations**: Tag disposal groups as discontinued operations
- **Multi-Currency Support**: Handle foreign currency disposals with FX gain/loss
- **Partial Sales**: Support partial sale of disposal groups
- **GL Integration**: Automatic journal entry generation
- **Approval Workflow**: Multi-level approval for HFS classification
- **Audit Trail**: Complete history of all HFS transactions
- **12-Month Rule Monitoring**: Automatic alerts for items exceeding 12 months

### Compliance Standards
- **IFRS 5**: Non-current Assets Held for Sale and Discontinued Operations
- **IAS 16**: Property, Plant and Equipment (for reclassification)
- **IAS 40**: Investment Property (continues FV measurement when HFS)

---

## Pre-Setup Configuration

### 1. HFS Account Configuration

**Location**: `/asset-management/settings`

**Purpose**: Configure default chart accounts for HFS operations.

**Steps**:

1. Navigate to Asset Management → Settings
2. Scroll to "Default Accounts" section
3. Configure the following accounts:

#### A. Held for Sale Account
- **Purpose**: Account used when assets are reclassified to HFS
- **Account Type**: Asset (Non-current Asset)
- **Example**: "1500 - Assets Held for Sale"

#### B. Impairment Loss Account
- **Purpose**: Account for impairment losses on HFS assets
- **Account Type**: Expense (P&L)
- **Example**: "6500 - Impairment Loss on HFS Assets"

#### C. Gain/Loss on Disposal Accounts
- **Gain on Disposal Account**: Revenue account for disposal gains
- **Loss on Disposal Account**: Expense account for disposal losses
- These are typically already configured in asset settings

#### D. FX Gain/Loss Accounts (if multi-currency)
- **FX Realized Gain Account**: Income account for FX gains
- **FX Realized Loss Account**: Expense account for FX losses

**Example Configuration**:
```
Held for Sale Account: 1500 - Assets Held for Sale
Impairment Loss Account: 6500 - Impairment Loss on HFS Assets
Gain on Disposal: 4100 - Gain on Disposal of Assets
Loss on Disposal: 6100 - Loss on Disposal of Assets
```

### 2. Asset Category HFS Settings

**Location**: `/asset-management/categories/{id}/edit`

**Purpose**: Configure HFS account per asset category (optional, overrides system default).

**Steps**:

1. Navigate to Asset Management → Categories → Edit Category
2. In "Chart Accounts" section, configure:
   - **HFS Account**: Category-specific HFS account (optional)

**Note**: If not configured at category level, system uses default from Asset Settings.

---

## HFS Request Flow

### Step 1: HFS Request Initiation

**Location**: `/asset-management/hfs/requests/create`

**Triggering Events**:
- **Strategic Disposal**: Asset no longer needed for operations
- **Business Restructuring**: Disposal of business segment
- **Regulatory Requirement**: Compliance with IFRS 5 criteria
- **Management Decision**: Board-approved disposal plan

**IFRS 5 Criteria** (must be met):
1. Asset available for immediate sale in present condition
2. Management committed to sale (with evidence)
3. Active program to locate buyer
4. Sale highly probable (>75% probability)
5. Expected within 12 months (or board approval for extension)
6. Asset not already disposed or fully impaired

**Process**:

1. **Select Assets**
   - System displays asset registry with filters
   - Select one or multiple assets
   - For disposal groups, check **"This is a disposal group"**
   - System auto-fetches for each asset:
     - Asset code and name
     - Current carrying amount
     - Accumulated depreciation
     - Asset category
     - HFS status (must be 'none')
     - Location and custodian

2. **Enter HFS Details**:
   - **Intended Sale Date**: Expected date of sale (must be ≤12 months normally)
   - **Expected Close Date**: Target closing date
   - **Buyer Name**: Name of potential buyer (if identified)
   - **Buyer Contact**: Contact information
   - **Buyer Address**: Buyer's address
   - **Justification**: Detailed reason for HFS classification
   - **Expected Fair Value**: Estimated fair value
   - **Expected Costs to Sell**: Estimated costs (legal, brokerage, etc.)
   - **Sale Price Range**: Expected price range
   - **Probability Percentage**: Likelihood of sale (should be >75% for "highly probable")

3. **IFRS 5 Criteria Validation**:
   - **Management Committed**: ☑ Check if management is committed
   - **Management Commitment Date**: Date of commitment
   - **Marketing Actions**: Description of marketing activities
   - **Exceeds 12 Months**: ☑ Check if sale expected beyond 12 months
   - **Extension Justification**: If exceeds 12 months, provide justification
   - **Attachments**: Upload management minutes, board resolutions, marketing materials

4. **Disposal Group Details** (if applicable):
   - **Is Disposal Group**: ☑ Check if this is a disposal group
   - **Group Description**: Description of the disposal group
   - **Mixed Asset Types**: System supports PPE, Inventory, ROU, Investment Property

5. **Submit HFS Request**
   - System generates unique Request Number (e.g., "HFS-2025-00001")
   - Status: `draft`
   - IFRS 5 criteria validation performed automatically
   - System checks asset eligibility

---

### Step 2: IFRS 5 Criteria Validation

**Automatic Process** (performed by system):

1. **Check Asset Availability**:
   - Asset must be available for immediate sale
   - Asset must not be already disposed
   - Asset must not be fully impaired
   - Asset must not be pledged (or bank consent obtained)

2. **Check Management Commitment**:
   - Management commitment evidence required
   - Management commitment date must be provided
   - Attachments should include board minutes or approval

3. **Check Sale Probability**:
   - Probability must be >75% for "highly probable"
   - Buyer should be identified OR active marketing program
   - Marketing actions must be described

4. **Check Timeline**:
   - Intended sale date should be ≤12 months from classification date
   - If >12 months, extension justification and board approval required

5. **Validation Results**:
   - **Valid**: All criteria met, ready for approval
   - **Invalid**: Errors displayed, request cannot proceed
   - **Warnings**: Non-critical issues, can proceed with caution

---

### Step 3: Submit for Approval

**Location**: `/asset-management/hfs/requests/{id}`

**Process**:

1. **Review HFS Request**
   - Verify IFRS 5 criteria are met
   - Check asset selections
   - Review expected fair value and costs
   - Confirm buyer information
   - Review attachments

2. **Submit for Approval**
   - Click "Submit for Approval" button
   - Status changes to `in_review`
   - Notification sent to approvers
   - Request locked for editing

3. **Approval Requirements**:
   - **Level 1**: Asset Custodian approval
   - **Level 2**: Finance Manager approval
   - **Level 3**: CFO/Board approval (for significant items)

---

### Step 4: Approval Process

**Location**: `/asset-management/hfs/requests/{id}`

**Process**:

#### A. Asset Custodian Approval

1. **Review HFS Request**:
   - Verify assets are available for sale
   - Check asset condition
   - Confirm location and custody
   - Review buyer information

2. **Decision Options**:

   **Approve**:
   - Status: `in_review` (moves to next level)
   - `asset_custodian_approved_at` recorded
   - Approval notes saved

   **Reject**:
   - Status: `rejected`
   - Rejection reason recorded
   - Request returned to draft

#### B. Finance Manager Approval

1. **Review HFS Request**:
   - Verify financial aspects
   - Check expected fair value is reasonable
   - Review costs to sell
   - Confirm IFRS 5 compliance
   - Review documentation

2. **Decision Options**:

   **Approve**:
   - Status: `in_review` (if CFO approval required)
   - Status: `approved` (if final approval)
   - `finance_manager_approved_at` recorded
   - Approval notes saved

   **Reject**:
   - Status: `rejected`
   - Rejection reason recorded
   - Request returned to draft

#### C. CFO/Board Approval (if required)

1. **Review HFS Request**:
   - Final review of significant items
   - Verify strategic alignment
   - Confirm board approval (if required)

2. **Approve**:
   - Status: `approved`
   - `cfo_approved_at` recorded
   - Ready for reclassification

---

### Step 5: Reclassification to HFS

**Automatic Process** (triggered on final approval):

1. **Asset Reclassification**:
   - Update asset `hfs_status` to `classified`
   - Set `depreciation_stopped` = true
   - Record `carrying_amount_at_hfs`
   - Link asset to HFS request (`current_hfs_id`)

2. **Create HFS Asset Records**:
   - Create `hfs_assets` record for each asset
   - Record `carrying_amount_at_reclass`
   - Record `accumulated_depreciation_at_reclass`
   - Record `asset_cost_at_reclass`
   - Set `depreciation_stopped` = true
   - Set `reclassified_date` = approval date

3. **Create Reclassification Journal**:
   ```
   Dr. Asset Held for Sale (HFS Account)        XXX
       Cr. Original Asset Account (PPE)         XXX
   ```
   - No change in net book value
   - Just reclassification from PPE to HFS

4. **System Actions**:
   - Journal entry created in `journals` table
   - Journal items created in `journal_items` table
   - GL transactions created
   - `gl_posted`: true
   - `gl_journal_id`: Reference to journal entry
   - Status: `approved`

5. **Depreciation Prevention**:
   - System prevents depreciation postings for HFS assets
   - `DepreciationService` checks `depreciation_stopped` flag
   - Investment Property at FV continues depreciation per IAS 40

---

## HFS Valuation and Measurement Flow

### Step 1: Valuation Initiation

**Location**: `/asset-management/hfs/valuations/create/{hfs_id}`

**Triggering Events**:
- **Initial Classification**: Measure at classification date
- **Periodic Review**: Regular valuation updates
- **Before Disposal**: Final valuation before sale
- **Market Changes**: Significant market value changes

**Process**:

1. **Select HFS Request**
   - System displays HFS request details
   - Shows all linked assets
   - Displays current carrying amounts

2. **Enter Valuation Details**:
   - **Valuation Date**: Date of valuation
   - **Fair Value**: Current fair value of assets
   - **Costs to Sell**: Estimated costs to sell
   - System calculates:
     - FV Less Costs = Fair Value - Costs to Sell
     - Current Carrying Amount
     - Impairment Amount (if FV Less Costs < Carrying Amount)
     - Reversal Amount (if FV Less Costs > Carrying Amount, limited)

3. **Valuator Information**:
   - **Valuator Name**: Name of valuator
   - **Valuator License**: License number
   - **Valuator Company**: Company name
   - **Report Reference**: Reference to valuation report
   - **Upload Valuation Report**: PDF/DOC file

4. **Attachments**:
   - Upload valuation report
   - Upload supporting documents
   - Market analysis documents

5. **Submit Valuation**
   - System generates unique Valuation ID
   - Status: `draft`
   - Valuation calculations performed automatically

---

### Step 2: Measurement Calculation

**Automatic Process** (performed by system):

1. **Calculate FV Less Costs**:
   ```
   FV Less Costs = Fair Value - Costs to Sell
   ```

2. **Compare with Carrying Amount**:
   ```
   If FV Less Costs < Carrying Amount:
       Impairment = Carrying Amount - FV Less Costs
   Else If FV Less Costs > Carrying Amount:
       Reversal = MIN(FV Less Costs - Carrying Amount, 
                      Original Carrying Before HFS Impairment - Current Carrying)
   Else:
       No Change
   ```

3. **Impairment Recognition**:
   - If impairment > 0:
     - Create impairment journal entry
     - Update carrying amount to FV Less Costs
     - Record impairment in `hfs_valuations` table

4. **Reversal Recognition**:
   - If reversal > 0:
     - Reversal limited to original carrying amount before HFS impairment
     - Create reversal journal entry
     - Update carrying amount (up to original limit)
     - Record reversal in `hfs_valuations` table

5. **Update Asset Records**:
   - Update `current_carrying_amount` in `hfs_assets`
   - Update asset `current_nbv` (if applicable)
   - Record valuation history

---

### Step 3: Approval and GL Posting

**Location**: `/asset-management/hfs/valuations/{id}`

**Process**:

1. **Review Valuation**
   - Verify fair value is reasonable
   - Check costs to sell
   - Review valuation report
   - Confirm calculations

2. **Approve Valuation**
   - Status: `approved`
   - Ready for GL posting

3. **Post to General Ledger**

   **For Impairment**:
   ```
   Dr. Impairment Loss (P&L)                    XXX
       Cr. Asset Held for Sale                  XXX
   ```

   **For Reversal**:
   ```
   Dr. Asset Held for Sale                      XXX
       Cr. Impairment Reversal (P&L)            XXX
   ```

4. **System Actions**:
   - Journal entry created
   - `gl_posted`: true
   - `impairment_journal_id`: Reference to journal entry
   - Status: `posted`
   - Asset carrying amounts updated

---

## HFS Disposal Flow

### Step 1: Disposal Initiation

**Location**: `/asset-management/hfs/disposals/create/{hfs_id}`

**Triggering Events**:
- Asset sold
- Disposal completed
- Settlement received

**Process**:

1. **Select HFS Request**
   - System displays HFS request details
   - Shows all linked assets
   - Displays current carrying amounts

2. **Enter Disposal Details**:
   - **Disposal Date**: Date of sale/disposal
   - **Sale Proceeds**: Amount received
   - **Sale Currency**: Currency of sale (if different from functional currency)
   - **Currency Rate**: Exchange rate (if foreign currency)
   - **Costs Sold**: Actual costs incurred in disposal
   - **Buyer Name**: Final buyer name
   - **Buyer Contact**: Contact information
   - **Buyer Address**: Address
   - **Invoice Number**: Sales invoice number
   - **Receipt Number**: Payment receipt number
   - **Settlement Reference**: Payment reference
   - **Bank Account**: Account where proceeds received

3. **Partial Sale** (if applicable):
   - **Is Partial Sale**: ☑ Check if partial sale
   - **Assets Sold**: Select specific assets sold
   - **Partial Sale Percentage**: Percentage of disposal group sold
   - **Reclassify Remaining**: Choose to keep remaining in HFS or reclassify

4. **System Calculations**:
   - Sale Proceeds (in functional currency)
   - Carrying Amount at Disposal
   - Costs Sold
   - Gain/Loss = Sale Proceeds - Carrying Amount - Costs Sold
   - FX Gain/Loss (if foreign currency)

5. **Attachments**:
   - Upload sales agreement
   - Upload payment receipt
   - Upload settlement documents

6. **Submit Disposal**
   - System generates unique Disposal ID
   - Status: `draft`
   - Disposal calculations performed automatically

---

### Step 2: Disposal Calculation

**Automatic Process** (performed by system):

1. **Convert to Functional Currency** (if foreign currency):
   ```
   Sale Proceeds (LCY) = Sale Proceeds (FCY) × Currency Rate
   ```

2. **Calculate Gain/Loss**:
   ```
   Gain/Loss = Sale Proceeds (LCY) - Carrying Amount - Costs Sold
   ```

3. **Calculate FX Gain/Loss** (if foreign currency):
   ```
   FX Gain/Loss = (Sale Proceeds (FCY) × Current Rate) - 
                  (Sale Proceeds (FCY) × Historical Rate)
   ```

4. **Update Asset Records**:
   - Update asset status to `disposed`
   - Update asset `hfs_status` to `sold`
   - Record disposal date
   - Update `hfs_disposals` table

---

### Step 3: Approval and GL Posting

**Location**: `/asset-management/hfs/disposals/{id}`

**Process**:

1. **Review Disposal**
   - Verify sale proceeds
   - Check costs sold
   - Review gain/loss calculation
   - Confirm buyer information

2. **Approve Disposal**
   - Status: `approved`
   - Ready for GL posting

3. **Post to General Ledger**

   **For Full Disposal**:
   ```
   Dr. Bank/Cash (Sale Proceeds)                XXX
   Dr. Disposal Costs (Expense)                XXX
       Cr. Asset Held for Sale                  XXX
       Cr. Gain on Disposal (P&L)              XXX
   OR
       Dr. Loss on Disposal (P&L)              XXX
   ```

   **For FX Gain/Loss** (if foreign currency):
   ```
   Dr. FX Realized Loss (P&L)                 XXX
   OR
       Cr. FX Realized Gain (P&L)              XXX
   ```

   **For Deferred Tax** (if enabled):
   ```
   Dr. Deferred Tax Expense                    XXX
       Cr. Deferred Tax Liability              XXX
   OR
   Dr. Deferred Tax Asset                      XXX
       Cr. Deferred Tax Expense                XXX
   ```

4. **System Actions**:
   - Journal entry created
   - `gl_posted`: true
   - `journal_id`: Reference to journal entry
   - Status: `posted`
   - Asset status updated to `disposed`
   - HFS request status updated to `sold`

---

## Discontinued Operations Flow

### Step 1: Tag as Discontinued Operations

**Location**: `/asset-management/hfs/discontinued/{hfs_id}/tag`

**Triggering Events**:
- Disposal group meets discontinued operations criteria
- Component of entity being disposed
- Separate major line of business
- Separate geographical area

**IFRS 5 Criteria for Discontinued Operations**:
1. Component of entity (separate line of business or geographical area)
2. Part of single coordinated plan
3. Disposed of or classified as HFS
4. Represents major line of business or geographical area

**Process**:

1. **Select HFS Request**
   - System displays HFS request (must be disposal group)
   - Shows all linked assets
   - Displays disposal group details

2. **Check Criteria**
   - System automatically checks IFRS 5 criteria
   - Displays criteria validation results
   - Shows which criteria are met

3. **Enter Discontinued Operations Details**:
   - **Is Discontinued**: ☑ Check to tag as discontinued
   - **Discontinued Date**: Date of classification
   - **Effects on P&L**:
     - Revenue from discontinued operations
     - Expenses from discontinued operations
     - Pre-tax profit/(loss)
     - Tax
     - Post-tax profit/(loss)

4. **Submit**
   - System creates `hfs_discontinued_flags` record
   - `is_discontinued`: true
   - Criteria checked and stored
   - P&L effects recorded

---

### Step 2: Financial Statement Presentation

**Automatic Process**:

1. **Income Statement**:
   - Discontinued operations shown separately
   - Revenue from discontinued operations
   - Profit/(loss) from discontinued operations
   - Gain/(loss) on disposal
   - Tax
   - Total impact to net profit

2. **Cash Flow Statement**:
   - Cash flows from discontinued operations shown separately
   - Operating cash flows
   - Investing cash flows
   - Financing cash flows

3. **Comparative Periods**:
   - Prior periods restated
   - Comparative figures shown separately

4. **Notes to Financial Statements**:
   - Description of discontinued operations
   - Assets and liabilities of discontinued operations
   - Revenue and expenses
   - Gain/(loss) on disposal

---

## HFS Cancellation Flow

### Step 1: Cancellation Initiation

**Location**: `/asset-management/hfs/requests/{id}/cancel`

**Triggering Events**:
- Sale cancelled
- No longer highly probable
- Plan changed
- Asset no longer available for sale

**Process**:

1. **Select HFS Request**
   - System displays HFS request details
   - Shows current status
   - Displays linked assets

2. **Enter Cancellation Details**:
   - **Cancellation Reason**: Detailed reason for cancellation
   - **Cancellation Date**: Date of cancellation

3. **Submit Cancellation**
   - Status: `cancelled`
   - System performs cancellation process

---

### Step 2: Cancellation Process

**Automatic Process**:

1. **Reclassify Assets Back**:
   - Update asset `hfs_status` to `none`
   - Set `depreciation_stopped` = false
   - Clear `current_hfs_id`
   - Resume depreciation

2. **Create Cancellation Journal**:
   ```
   Dr. Original Asset Account (PPE)            XXX
       Cr. Asset Held for Sale                 XXX
   ```

3. **Update HFS Assets**:
   - Set status to `cancelled`
   - Record cancellation date

4. **System Actions**:
   - Journal entry created
   - GL transactions posted
   - Asset records updated
   - Depreciation resumes

---

## GL Posting and Accounting Integration

### HFS Reclassification Journal Entries

#### Scenario 1: Reclassification to HFS

**Journal Entry**:
```
Account                          Debit          Credit
Asset Held for Sale (HFS)        XXX            -
Original Asset Account (PPE)     -              XXX
```

**Impact**:
- Asset reclassified from PPE to HFS
- No change in net book value
- Depreciation stops (except Investment Property at FV)

---

### HFS Impairment Journal Entries

#### Scenario 1: Impairment at Classification

**Journal Entry**:
```
Account                          Debit          Credit
Impairment Loss (P&L)            XXX            -
Asset Held for Sale              -              XXX
```

**Impact**:
- Asset carrying amount: **Decreased** to FV Less Costs
- P&L: **Expense recognized**
- Depreciation basis: **Adjusted**

---

#### Scenario 2: Impairment Reversal

**Journal Entry**:
```
Account                          Debit          Credit
Asset Held for Sale              XXX            -
Impairment Reversal (P&L)        -              XXX
```

**Impact**:
- Asset carrying amount: **Increased** (limited to original)
- P&L: **Income recognized**
- Reversal limited to original carrying before HFS impairment

---

### HFS Disposal Journal Entries

#### Scenario 1: Disposal with Gain

**Journal Entry**:
```
Account                          Debit          Credit
Bank/Cash (Sale Proceeds)        XXX            -
Disposal Costs (Expense)         XXX            -
    Cr. Asset Held for Sale      -              XXX
    Cr. Gain on Disposal (P&L)   -              XXX
```

**Impact**:
- Asset derecognized
- Cash received
- P&L: **Gain recognized**

---

#### Scenario 2: Disposal with Loss

**Journal Entry**:
```
Account                          Debit          Credit
Bank/Cash (Sale Proceeds)        XXX            -
Disposal Costs (Expense)         XXX            -
Loss on Disposal (P&L)           XXX            -
    Cr. Asset Held for Sale      -              XXX
```

**Impact**:
- Asset derecognized
- Cash received
- P&L: **Loss recognized**

---

#### Scenario 3: Foreign Currency Disposal

**Journal Entry**:
```
Account                          Debit          Credit
Bank/Cash (Sale Proceeds - FCY)  XXX            -
FX Realized Loss (P&L)           XXX            -
    Cr. Asset Held for Sale      -              XXX
    Cr. Gain on Disposal (P&L)   -              XXX
OR
    Cr. FX Realized Gain (P&L)   -              XXX
```

**Impact**:
- FX gain/loss recognized
- Asset derecognized
- Cash received in foreign currency

---

### HFS Cancellation Journal Entries

#### Scenario 1: Cancellation and Reclassification

**Journal Entry**:
```
Account                          Debit          Credit
Original Asset Account (PPE)     XXX            -
    Cr. Asset Held for Sale      -              XXX
```

**Impact**:
- Asset reclassified back to PPE
- Depreciation resumes
- HFS status cleared

---

## Approval Workflow

### Workflow Levels

#### Level 1: Asset Custodian
- Reviews asset availability
- Confirms asset condition
- Verifies custody
- Suitable for routine HFS requests

#### Level 2: Finance Manager
- Reviews financial aspects
- Verifies IFRS 5 compliance
- Checks valuation reasonableness
- Suitable for most HFS requests

#### Level 3: CFO/Board
- Final approval for significant items
- Strategic alignment verification
- Board approval (if required)
- Suitable for major disposals

### Approval Process

1. **Draft** → **In Review**
   - Initiator submits for approval
   - Status locked for editing
   - Notification sent to approvers

2. **In Review** → **Approved**
   - Asset Custodian approves (Level 1)
   - Finance Manager approves (Level 2)
   - CFO/Board approves (Level 3, if required)
   - Ready for reclassification

3. **In Review** → **Rejected**
   - Approver rejects with reason
   - Returns to draft (if allowed)
   - OR closed permanently

4. **Approved** → **Reclassified**
   - System automatically reclassifies assets
   - Journal entries created
   - Depreciation stopped
   - Status: `approved`

---

## Reports and Analytics

### Operational Reports

#### 1. HFS Movement Schedule (IFRS 5 Compliant)
- Carrying amount at start of period
- Classified as HFS during period
- Impairment losses recognized
- Reversals of impairment
- Transfers (to/from HFS)
- Disposals (proceeds)
- Carrying amount at end of period
- Filter by: Asset, Category, Date Range, Status

#### 2. HFS Valuation Details
- HFS ID and asset details
- Date classified
- Carrying amount at classification
- Fair value
- Costs to sell
- FV less costs
- Impairment posted (Y/N)
- Journal reference
- Valuator and report reference

#### 3. Discontinued Operations Note
- Revenue from discontinued operations
- Profit/(loss) from discontinued operations
- Gain/(loss) on disposal
- Tax
- Total impact to net profit
- Comparative periods (current and prior year)

#### 4. Overdue HFS Report
- HFS items exceeding 12 months
- Reason for extension
- Approval details
- Buyer progress notes
- Alert status

#### 5. HFS Audit Trail
- Complete change history
- All approvals
- All valuations
- All disposals
- User actions
- Timestamps

### Financial Reports

#### 1. HFS Balance Sheet Impact
- Assets held for sale balance
- Impairment losses
- Reversals
- Net HFS asset value

#### 2. HFS P&L Impact
- Impairment losses by period
- Impairment reversals
- Gain/(loss) on disposal
- Net impact on profit/loss

#### 3. Discontinued Operations Summary
- Revenue and expenses
- Pre-tax profit/(loss)
- Tax
- Gain/(loss) on disposal
- Post-tax impact

---

## Integration Points

### 1. Fixed Assets Module

**Integration**:
- Asset selection in HFS request forms
- Asset status updates on reclassification
- Depreciation prevention for HFS assets
- Asset history updates

**Data Flow**:
- HFS Classification → Asset status update
- HFS Classification → Depreciation stopped
- HFS Disposal → Asset status to "Disposed"
- HFS Cancellation → Asset reclassified, depreciation resumes

---

### 2. General Ledger (GL)

**Integration**:
- Automatic journal entry creation
- Account mapping from settings
- Transaction posting
- Audit trail

**Data Flow**:
- HFS Approval → Reclassification Journal
- HFS Valuation → Impairment/Reversal Journal
- HFS Disposal → Disposal Journal
- HFS Cancellation → Cancellation Journal

---

### 3. Chart of Accounts

**Integration**:
- HFS account
- Impairment loss account
- Gain/loss on disposal accounts
- FX gain/loss accounts
- Account validation

**Data Flow**:
- Asset Settings → Default Accounts
- Category Settings → Category-Specific Accounts
- HFS Operations → Account Selection
- GL Posting → Account Debits/Credits

---

### 4. Multi-Currency Module

**Integration**:
- FX rate conversion
- FX gain/loss calculation
- FX journal posting

**Data Flow**:
- Foreign Currency Disposal → FX Rate Lookup
- FX Rate → Conversion to Functional Currency
- FX Difference → FX Gain/Loss Journal

---

### 5. Tax Module

**Integration**:
- Deferred tax calculation
- Deferred tax journal posting
- Tax base tracking

**Data Flow**:
- HFS Impairment → Deferred Tax Calculation
- HFS Disposal → Deferred Tax Calculation
- Deferred Tax → Journal Entry Creation

---

## User Roles and Permissions

### Required Permissions

1. **HFS Creator**
   - `create hfs requests`
   - `view hfs requests`
   - `edit hfs requests` (draft only)

2. **Asset Custodian**
   - `approve hfs requests` (Level 1)
   - `view hfs requests`

3. **Finance Manager**
   - `approve hfs requests` (Level 2)
   - `view hfs requests`
   - `view hfs valuations`
   - `view hfs disposals`

4. **CFO/Board**
   - `approve hfs requests` (Level 3)
   - `view hfs requests`
   - `view hfs reports`

5. **Finance/Accountant**
   - `post hfs to gl`
   - `view hfs requests`
   - `view hfs valuations`
   - `view hfs disposals`

6. **HFS Administrator**
   - All permissions
   - `manage hfs settings`
   - `delete hfs requests`
   - `cancel hfs requests`

---

## Workflow Summary Diagrams

### HFS Request Workflow

```
┌─────────────────────────────────────────────────────────────┐
│              HFS REQUEST INITIATION                          │
│  - Select Assets                                             │
│  - Enter Sale Plan                                           │
│  - Validate IFRS 5 Criteria                                 │
│  - Upload Documentation                                      │
│  Status: Draft                                               │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              IFRS 5 CRITERIA VALIDATION                       │
│  - Asset Available?                                          │
│  - Management Committed?                                     │
│  - Sale Highly Probable?                                     │
│  - Within 12 Months?                                         │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              SUBMIT FOR APPROVAL                              │
│  Status: In Review                                           │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              APPROVAL WORKFLOW                               │
│                                                               │
│  Level 1: Asset Custodian → Approve                          │
│  Level 2: Finance Manager → Approve                          │
│  Level 3: CFO/Board → Approve                                │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              RECLASSIFICATION TO HFS                           │
│  - Create Reclassification Journal                           │
│  - Update Asset Status                                       │
│  - Stop Depreciation                                         │
│  - Create HFS Asset Records                                  │
│  Status: Approved                                            │
└─────────────────────────────────────────────────────────────┘
```

### HFS Valuation Workflow

```
┌─────────────────────────────────────────────────────────────┐
│              VALUATION INITIATION                            │
│  - Enter Fair Value                                          │
│  - Enter Costs to Sell                                       │
│  - Provide Valuator Information                             │
│  - Upload Valuation Report                                   │
│  Status: Draft                                               │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              MEASUREMENT CALCULATION                          │
│  - Calculate FV Less Costs                                   │
│  - Compare with Carrying Amount                              │
│  - Calculate Impairment/Reversal                             │
│  - Update Asset Records                                      │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              APPROVAL & GL POSTING                            │
│  - Approve Valuation                                         │
│  - Generate Journal Entry:                                   │
│    Dr. Impairment Loss / Asset HFS                           │
│    Cr. Asset HFS / Impairment Reversal                       │
│  - Update Asset Carrying Amount                              │
│  Status: Posted                                              │
└─────────────────────────────────────────────────────────────┘
```

### HFS Disposal Workflow

```
┌─────────────────────────────────────────────────────────────┐
│              DISPOSAL INITIATION                             │
│  - Enter Sale Proceeds                                       │
│  - Enter Costs Sold                                          │
│  - Provide Buyer Information                                │
│  - Upload Settlement Documents                               │
│  Status: Draft                                               │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              DISPOSAL CALCULATION                             │
│  - Convert to Functional Currency (if FCY)                  │
│  - Calculate Gain/Loss                                       │
│  - Calculate FX Gain/Loss (if applicable)                    │
│  - Update Asset Records                                      │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              APPROVAL & GL POSTING                            │
│  - Approve Disposal                                          │
│  - Generate Journal Entry:                                   │
│    Dr. Bank, Disposal Costs                                  │
│    Cr. Asset HFS, Gain/Loss                                  │
│  - Post FX Journal (if applicable)                           │
│  - Post Deferred Tax Journal (if enabled)                    │
│  - Update Asset Status to "Disposed"                         │
│  Status: Posted                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Key Database Tables

1. **hfs_requests**: HFS request header records
2. **hfs_assets**: Assets linked to HFS requests
3. **hfs_valuations**: Valuation history records
4. **hfs_disposals**: Disposal transaction records
5. **hfs_discontinued_flags**: Discontinued operations flags
6. **hfs_approvals**: Approval workflow records
7. **hfs_audit_logs**: Complete audit trail
8. **assets**: Updated with HFS fields
9. **asset_categories**: HFS account configuration
10. **journals**: GL journal entries
11. **journal_items**: GL journal entry line items
12. **gl_transactions**: GL transaction records

---

## Best Practices

### 1. HFS Classification

- **IFRS 5 Compliance**: Ensure all criteria are met before classification
- **Documentation**: Maintain complete documentation (management minutes, marketing evidence)
- **Timeline**: Keep sale timeline within 12 months or obtain board approval for extension
- **Regular Review**: Review HFS items regularly for continued compliance

### 2. Valuation and Measurement

- **Professional Valuations**: Use qualified valuers for significant assets
- **Regular Updates**: Update valuations periodically during holding period
- **FV Less Costs**: Always use fair value less costs to sell (not just fair value)
- **Reversal Limits**: Remember reversals are limited to original carrying amount

### 3. Disposal Management

- **Timely Recording**: Record disposals promptly after sale
- **Complete Information**: Capture all disposal details (proceeds, costs, buyer)
- **Multi-Currency**: Handle foreign currency disposals correctly
- **Partial Sales**: Use partial sale feature for disposal groups

### 4. Discontinued Operations

- **Criteria Check**: Verify disposal group meets discontinued operations criteria
- **P&L Effects**: Record accurate revenue and expense data
- **Comparative Periods**: Ensure prior periods are restated correctly
- **Disclosure**: Prepare complete financial statement disclosures

### 5. System Usage

- **Configuration**: Properly configure HFS accounts before creating requests
- **Approval Workflow**: Follow approval workflow as configured
- **GL Posting**: Post to GL promptly after approval
- **Asset Updates**: Verify asset records are updated correctly after posting
- **12-Month Rule**: Monitor and address overdue HFS items

---

## Troubleshooting

### Common Issues

1. **HFS Request Not Appearing in List**:
   - Check branch filter
   - Verify company_id matches
   - Check status filter
   - Review user permissions

2. **Cannot Post to GL**:
   - Verify HFS request is approved
   - Check HFS accounts are configured
   - Verify journal entry service is working
   - Review error logs

3. **Asset Status Not Updating**:
   - Verify GL posting completed successfully
   - Check asset update logic in service
   - Review reclassification process
   - Check for errors in logs

4. **Depreciation Still Running for HFS Asset**:
   - Verify `depreciation_stopped` flag is set to true
   - Check asset `hfs_status` is 'classified'
   - Review depreciation service logic
   - For Investment Property at FV, depreciation continues per IAS 40

5. **Cannot Create Valuation**:
   - Verify HFS request is approved
   - Check HFS request status
   - Review asset eligibility
   - Check user permissions

6. **Impairment Reversal Limited**:
   - Reversal is limited to original carrying amount before HFS impairment
   - Check original carrying amount
   - Verify reversal calculation
   - Review valuation history

7. **12-Month Rule Alert Not Working**:
   - Check console command is scheduled
   - Verify `hfs:check-overdue` command runs daily
   - Check system settings for last run date
   - Review overdue flag logic

8. **Reports Not Showing Data**:
   - Check date filters
   - Verify company/branch selection
   - Review user permissions
   - Check data exists in database

9. **Multi-Currency Disposal Issues**:
   - Verify FX rate exists for currency pair and date
   - Check FX rate conversion logic
   - Review FX gain/loss calculation
   - Verify FX accounts are configured

10. **Partial Sale Not Working**:
    - Verify HFS request is a disposal group
    - Check asset selection
    - Review partial sale logic
    - Verify remaining assets handling

---

## Conclusion

The IFRS 5 Held for Sale & Discontinued Operations Management System provides a complete end-to-end solution for managing assets held for sale in compliance with IFRS 5 standards. By following this flow, organizations can:

- **Ensure Compliance**: Meet IFRS 5 requirements for HFS classification and measurement
- **Maintain Accuracy**: Keep asset values and measurements current
- **Control Processes**: Implement proper approval workflows and documentation
- **Generate Reports**: Produce required financial statement disclosures
- **Audit Trail**: Maintain complete history of all HFS transactions
- **Monitor Compliance**: Track 12-month rule and overdue items

For technical support or feature requests, contact the development team.

---

**Document Version**: 1.0  
**Last Updated**: November 2025  
**System**: SmartAccounting - IFRS 5 Held for Sale & Discontinued Operations Module

