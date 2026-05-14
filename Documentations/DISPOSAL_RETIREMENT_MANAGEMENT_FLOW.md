# Asset Disposal & Retirement Management System - Complete Flow Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Pre-Setup Configuration](#pre-setup-configuration)
3. [Asset Disposal Flow](#asset-disposal-flow)
4. [Disposal Types](#disposal-types)
5. [GL Posting and Accounting Integration](#gl-posting-and-accounting-integration)
6. [Partial Payments and Receivables](#partial-payments-and-receivables)
7. [VAT and Withholding Tax](#vat-and-withholding-tax)
8. [Revaluation Reserve Transfer](#revaluation-reserve-transfer)
9. [Insurance Recovery](#insurance-recovery)
10. [Approval Workflow](#approval-workflow)
11. [Reports and Analytics](#reports-and-analytics)
12. [Integration Points](#integration-points)
13. [User Roles and Permissions](#user-roles-and-permissions)
14. [Best Practices](#best-practices)
15. [Troubleshooting](#troubleshooting)

---

## System Overview

The Asset Disposal & Retirement Management System is a comprehensive module within the SmartAccounting Assets Management system that handles the disposal, retirement, and derecognition of assets in compliance with IAS 16 (Property, Plant and Equipment) and other relevant accounting standards.

### Key Features
- **Multiple Disposal Types**: Sale, Scrap, Write-off, Donation, Loss/Theft
- **Automatic NBV Calculation**: Net Book Value calculated automatically
- **Gain/Loss Calculation**: Automatic calculation of gain or loss on disposal
- **Partial Payments**: Support for partial payments with receivable tracking
- **VAT and WHT**: Automatic VAT and Withholding Tax calculation and posting
- **Revaluation Reserve Transfer**: Automatic transfer to retained earnings
- **Insurance Recovery**: Track insurance recoveries for lost/damaged assets
- **GL Integration**: Automatic journal entry generation
- **Approval Workflow**: Multi-level approval for disposals
- **Audit Trail**: Complete history of all disposal transactions
- **Receivable Management**: Track and record remaining receivables

### Disposal Types
1. **Sale**: Asset sold to a buyer (with or without payment)
2. **Scrap**: Asset scrapped (no proceeds, loss recognized)
3. **Write-off**: Asset written off (no proceeds, loss recognized)
4. **Donation**: Asset donated (no proceeds, donation expense)
5. **Loss/Theft**: Asset lost or stolen (insurance recovery possible)

### Compliance Standards
- **IAS 16**: Property, Plant and Equipment (Derecognition)
- **IAS 36**: Impairment of Assets (for write-offs)
- **Local Tax Regulations**: VAT and Withholding Tax compliance

---

## Pre-Setup Configuration

### 1. Chart Accounts Configuration

**Location**: `/asset-management/settings`

**Purpose**: Configure default chart accounts for disposal operations.

**Steps**:

1. Navigate to Asset Management → Settings
2. Scroll to "Default Accounts" section
3. Configure the following accounts:

#### A. Asset Accounts (per Category)
- **Asset Account**: Original asset cost account
- **Accumulated Depreciation Account**: Contra-asset account for depreciation
- **Accumulated Impairment Account**: Contra-asset account for impairment

#### B. Disposal Accounts
- **Gain on Disposal Account**: Revenue account for disposal gains
- **Loss on Disposal Account**: Expense account for disposal losses
- **Donation Expense Account**: Expense account for donations
- **Loss Account**: Expense account for losses/theft

#### C. Proceeds Accounts
- **Cash Account**: Default cash account for proceeds
- **Receivable Account**: Default receivable account for unpaid balances
- **Bank Accounts**: Configure bank accounts for payments

#### D. Tax Accounts
- **VAT Output Account**: Liability account for VAT on sales
- **WHT Receivable Account**: Asset account for withholding tax

#### E. Other Accounts
- **Retained Earnings Account**: Equity account for revaluation reserve transfer
- **Insurance Recovery Account**: Income account for insurance recoveries

**Example Configuration**:
```
Gain on Disposal: 4100 - Gain on Disposal of Assets
Loss on Disposal: 6100 - Loss on Disposal of Assets
Donation Expense: 6200 - Donation Expense
Loss Account: 6300 - Loss on Assets
VAT Output: 2200 - VAT Payable
WHT Receivable: 1300 - Withholding Tax Receivable
Retained Earnings: 3500 - Retained Earnings
```

### 2. Disposal Reason Codes

**Location**: `/asset-management/disposals/reason-codes`

**Purpose**: Configure standardized reason codes for disposals.

**Steps**:

1. Navigate to Asset Management → Disposals → Reason Codes
2. Create reason codes for common disposal reasons:
   - **OBS**: Obsolete
   - **DAM**: Damaged beyond repair
   - **REP**: Replaced
   - **END**: End of useful life
   - **STR**: Strategic disposal
   - **THF**: Theft
   - **LOS**: Lost
   - **DON**: Donation

3. For each reason code, configure:
   - **Code**: Unique code
   - **Name**: Descriptive name
   - **Description**: Detailed description
   - **Disposal Type**: Applicable disposal types
   - **Is Active**: Active status

---

## Asset Disposal Flow

### Step 1: Disposal Initiation

**Location**: `/asset-management/disposals/create`

**Triggering Events**:
- Asset no longer needed
- Asset obsolete or damaged
- Asset replaced
- Strategic disposal
- Asset lost or stolen
- Asset donated

**Process**:

1. **Select Asset**
   - System displays asset registry with filters
   - Select asset to dispose
   - System auto-fetches:
     - Asset code and name
     - Current carrying amount (NBV)
     - Asset cost
     - Accumulated depreciation
     - Accumulated impairment
     - Asset category
     - Asset status (must be 'active')

2. **Select Disposal Type**:
   - **Sale**: Asset sold to buyer
   - **Scrap**: Asset scrapped
   - **Write-off**: Asset written off
   - **Donation**: Asset donated
   - **Loss/Theft**: Asset lost or stolen

3. **Enter Disposal Details**:
   - **Reason Code**: Select from predefined reason codes
   - **Disposal Reason**: Detailed reason for disposal
   - **Proposed Disposal Date**: Expected disposal date
   - **Actual Disposal Date**: Actual disposal date (if different)

4. **Enter Financial Information** (varies by disposal type):

   **For Sale**:
   - **Disposal Proceeds**: Sale amount
   - **Amount Paid**: Amount received upfront
   - **Bank Account**: Account where payment received
   - **Customer**: Buyer (if customer record exists)
   - **Buyer Name**: Name of buyer
   - **Buyer Contact**: Contact information
   - **Buyer Address**: Address
   - **Invoice Number**: Sales invoice number
   - **Receipt Number**: Payment receipt number

   **For Scrap/Write-off**:
   - **Fair Value**: Fair value (if any scrap value)
   - **Disposal Proceeds**: Scrap proceeds (if any)

   **For Donation**:
   - **Fair Value**: Fair value of donated asset
   - **Recipient Name**: Name of recipient
   - **Recipient Contact**: Contact information

   **For Loss/Theft**:
   - **Insurance Recovery Amount**: Amount recovered from insurance
   - **Insurance Claim Number**: Claim reference
   - **Insurance Recovery Date**: Date of recovery

5. **VAT and Tax Information** (for Sale):
   - **VAT Type**: Exclusive or Inclusive
   - **VAT Rate**: VAT percentage
   - **VAT Amount**: Calculated automatically
   - **Withholding Tax Enabled**: ☑ Check if WHT applies
   - **Withholding Tax Rate**: WHT percentage
   - **Withholding Tax Type**: Percentage or Fixed Amount
   - **Withholding Tax Amount**: Calculated automatically

6. **Attachments**:
   - Upload disposal documents
   - Sales agreement (for sale)
   - Scrap certificate (for scrap)
   - Donation letter (for donation)
   - Police report (for loss/theft)
   - Insurance claim documents

7. **Submit Disposal**
   - System generates unique Disposal Number (e.g., "DSP-2025-00001")
   - Status: `draft`
   - NBV and gain/loss calculated automatically

---

### Step 2: NBV and Gain/Loss Calculation

**Automatic Process** (performed by system):

1. **Calculate Net Book Value (NBV)**:
   ```
   NBV = Asset Cost - Accumulated Depreciation - Accumulated Impairment
   ```
   - System fetches asset cost from asset record
   - System calculates accumulated depreciation up to disposal date
   - System fetches accumulated impairment from asset record
   - NBV is calculated and stored

2. **Calculate Gain/Loss**:
   ```
   For Sale:
   Gain/Loss = Disposal Proceeds - NBV
   
   For Scrap/Write-off:
   Gain/Loss = Scrap Proceeds (or 0) - NBV
   (Usually a loss)
   
   For Donation:
   Gain/Loss = 0 - NBV
   (Loss = Donation Expense)
   
   For Loss/Theft:
   Gain/Loss = Insurance Recovery - NBV
   (Loss if no recovery, or reduced loss if recovery)
   ```

3. **Update Disposal Record**:
   - `asset_cost`: Asset cost
   - `accumulated_depreciation`: Accumulated depreciation
   - `accumulated_impairment`: Accumulated impairment
   - `net_book_value`: Calculated NBV
   - `gain_loss`: Calculated gain/loss

---

### Step 3: Submit for Approval

**Location**: `/asset-management/disposals/{id}`

**Process**:

1. **Review Disposal**
   - Verify disposal type
   - Check NBV calculation
   - Review gain/loss calculation
   - Confirm disposal proceeds (if applicable)
   - Review buyer/recipient information
   - Check attachments

2. **Submit for Approval**
   - Click "Submit for Approval" button
   - Status changes to `pending_approval`
   - Notification sent to approvers
   - Disposal locked for editing

3. **Approval Requirements**:
   - **Level 1**: Finance Manager approval
   - **Level 2**: CFO/Board approval (for significant items)

---

### Step 4: Approval Process

**Location**: `/asset-management/disposals/{id}`

**Process**:

#### A. Finance Manager Approval

1. **Review Disposal**:
   - Verify disposal type is appropriate
   - Check NBV calculation
   - Review gain/loss calculation
   - Confirm disposal proceeds (if applicable)
   - Review documentation

2. **Decision Options**:

   **Approve**:
   - Status: `approved` (if single-level approval)
   - Status: `pending_approval` (if two-level approval)
   - `finance_manager_approved_at` recorded
   - Approval notes saved

   **Reject**:
   - Status: `rejected`
   - Rejection reason recorded
   - Disposal returned to draft

#### B. CFO/Board Approval (if required)

1. **Review Disposal**:
   - Final review of significant disposals
   - Verify strategic alignment
   - Confirm board approval (if required)

2. **Approve**:
   - Status: `approved`
   - `cfo_approved_at` recorded
   - Ready for GL posting

---

### Step 5: GL Posting

**Location**: `/asset-management/disposals/{id}`

**Process**:

1. **Post to General Ledger**
   - Click "Post to GL" button
   - System generates journal entries automatically

2. **Journal Entry Creation** (varies by disposal type - see GL Posting section)

3. **System Actions**:
   - Journal entry created in `journals` table
   - Journal items created in `journal_items` table
   - GL transactions created
   - `gl_posted`: true
   - `gl_journal_id`: Reference to journal entry
   - `gl_posted_at`: Timestamp
   - Status: `completed`

4. **Asset Updates**:
   - Asset status updated to `disposed`
   - Asset disposal date recorded
   - Asset removed from active asset register

---

## Disposal Types

### 1. Sale Disposal

**Purpose**: Asset sold to a buyer for consideration.

**Key Features**:
- Disposal proceeds recorded
- Gain/loss calculated
- VAT and WHT handled
- Partial payments supported
- Receivable tracking

**Process**:
1. Select asset
2. Choose disposal type: "Sale"
3. Enter sale proceeds
4. Enter buyer information
5. Enter payment details (amount paid, bank account)
6. Configure VAT and WHT (if applicable)
7. Submit for approval
8. Post to GL

**Journal Entries** (see GL Posting section)

---

### 2. Scrap Disposal

**Purpose**: Asset scrapped (no longer usable, minimal or no value).

**Key Features**:
- Usually results in loss
- May have minimal scrap proceeds
- Loss recognized in P&L

**Process**:
1. Select asset
2. Choose disposal type: "Scrap"
3. Enter scrap proceeds (if any)
4. Enter reason for scrapping
5. Submit for approval
6. Post to GL

**Journal Entries**:
```
Dr. Accumulated Depreciation        XXX
Dr. Loss on Disposal               XXX
    Cr. Asset Account (Cost)        XXX
```

---

### 3. Write-off Disposal

**Purpose**: Asset written off (no value, no proceeds).

**Key Features**:
- Results in loss
- No proceeds
- Loss recognized in P&L

**Process**:
1. Select asset
2. Choose disposal type: "Write-off"
3. Enter reason for write-off
4. Submit for approval
5. Post to GL

**Journal Entries**:
```
Dr. Accumulated Depreciation        XXX
Dr. Loss on Disposal               XXX
    Cr. Asset Account (Cost)        XXX
```

---

### 4. Donation Disposal

**Purpose**: Asset donated to charity or organization.

**Key Features**:
- No proceeds
- Donation expense recognized
- Fair value may be recorded for reference

**Process**:
1. Select asset
2. Choose disposal type: "Donation"
3. Enter recipient information
4. Enter fair value (for reference)
5. Submit for approval
6. Post to GL

**Journal Entries**:
```
Dr. Accumulated Depreciation        XXX
Dr. Donation Expense               XXX
    Cr. Asset Account (Cost)        XXX
```

---

### 5. Loss/Theft Disposal

**Purpose**: Asset lost or stolen.

**Key Features**:
- Results in loss
- Insurance recovery possible
- Loss recognized in P&L
- Insurance recovery recorded separately

**Process**:
1. Select asset
2. Choose disposal type: "Loss/Theft"
3. Enter loss details
4. Enter insurance recovery (if applicable)
5. Submit for approval
6. Post to GL

**Journal Entries**:
```
Dr. Accumulated Depreciation        XXX
Dr. Loss on Disposal               XXX
    Cr. Asset Account (Cost)        XXX

If Insurance Recovery:
Dr. Insurance Recovery Account     XXX
    Cr. Loss on Disposal           XXX
```

---

## GL Posting and Accounting Integration

### Common Journal Entries (All Disposal Types)

**Step 1: Remove Asset Cost and Accumulated Depreciation**
```
Dr. Accumulated Depreciation        XXX
    Cr. Asset Account (Cost)        XXX
```

This entry removes both the asset cost and accumulated depreciation from the books.

---

### Sale Disposal Journal Entries

#### Scenario 1: Sale with Full Payment (No VAT, No WHT)

**Journal Entry**:
```
Account                          Debit          Credit
Bank/Cash (Proceeds)             XXX            -
Accumulated Depreciation         XXX            -
Gain on Disposal (if gain)      -              XXX
OR
Loss on Disposal (if loss)       XXX            -
    Cr. Asset Account (Cost)     -              XXX
```

**Impact**:
- Asset derecognized
- Cash received
- Gain or loss recognized in P&L

---

#### Scenario 2: Sale with Partial Payment

**Journal Entry**:
```
Account                          Debit          Credit
Bank Account (Amount Paid)       XXX            -
Receivable Account (Balance)     XXX            -
Accumulated Depreciation         XXX            -
Gain on Disposal (if gain)      -              XXX
OR
Loss on Disposal (if loss)       XXX            -
    Cr. Asset Account (Cost)     -              XXX
```

**Impact**:
- Asset derecognized
- Partial cash received
- Receivable created for balance
- Gain or loss recognized

---

#### Scenario 3: Sale with VAT (Exclusive)

**Journal Entry**:
```
Account                          Debit          Credit
Bank/Cash (Proceeds + VAT)       XXX            -
Accumulated Depreciation         XXX            -
    Cr. Asset Account (Cost)     -              XXX
    Cr. VAT Output Account       -              XXX
    Cr. Gain on Disposal         -              XXX
OR
    Dr. Loss on Disposal         XXX            -
```

**Calculation**:
```
Net Proceeds = Sale Amount
VAT = Net Proceeds × VAT Rate
Gross Proceeds = Net Proceeds + VAT
Gain/Loss = Net Proceeds - NBV
```

---

#### Scenario 4: Sale with VAT (Inclusive)

**Journal Entry**:
```
Account                          Debit          Credit
Bank/Cash (Gross Amount)         XXX            -
Accumulated Depreciation         XXX            -
    Cr. Asset Account (Cost)     -              XXX
    Cr. VAT Output Account       -              XXX
    Cr. Gain on Disposal         -              XXX
OR
    Dr. Loss on Disposal         XXX            -
```

**Calculation**:
```
Gross Proceeds = Sale Amount (includes VAT)
VAT = Gross Proceeds × (VAT Rate / (100 + VAT Rate))
Net Proceeds = Gross Proceeds - VAT
Gain/Loss = Net Proceeds - NBV
```

---

#### Scenario 5: Sale with Withholding Tax

**Journal Entry**:
```
Account                          Debit          Credit
Bank/Cash (Net after WHT)        XXX            -
WHT Receivable                   XXX            -
Accumulated Depreciation         XXX            -
    Cr. Asset Account (Cost)     -              XXX
    Cr. Gain on Disposal         -              XXX
OR
    Dr. Loss on Disposal         XXX            -
```

**Calculation**:
```
WHT = Net Proceeds × WHT Rate
Amount Received = Net Proceeds - WHT
Gain/Loss = Net Proceeds - NBV
```

---

#### Scenario 6: Sale with VAT and WHT

**Journal Entry**:
```
Account                          Debit          Credit
Bank/Cash (Net after taxes)     XXX            -
WHT Receivable                   XXX            -
Accumulated Depreciation         XXX            -
    Cr. Asset Account (Cost)     -              XXX
    Cr. VAT Output Account       -              XXX
    Cr. Gain on Disposal         -              XXX
OR
    Dr. Loss on Disposal         XXX            -
```

**Calculation**:
```
Net Proceeds = Sale Amount (exclusive VAT)
VAT = Net Proceeds × VAT Rate
WHT = Net Proceeds × WHT Rate
Amount Received = Net Proceeds - WHT
Gain/Loss = Net Proceeds - NBV
```

---

### Scrap/Write-off Journal Entries

**Journal Entry**:
```
Account                          Debit          Credit
Accumulated Depreciation         XXX            -
Loss on Disposal                XXX            -
    Cr. Asset Account (Cost)     -              XXX

If Scrap Proceeds:
Dr. Cash/Scrap Proceeds          XXX            -
    Cr. Loss on Disposal         -              XXX
```

**Impact**:
- Asset derecognized
- Loss recognized in P&L
- Scrap proceeds (if any) reduce loss

---

### Donation Journal Entries

**Journal Entry**:
```
Account                          Debit          Credit
Accumulated Depreciation         XXX            -
Donation Expense                XXX            -
    Cr. Asset Account (Cost)     -              XXX
```

**Impact**:
- Asset derecognized
- Donation expense recognized in P&L
- No proceeds received

---

### Loss/Theft Journal Entries

**Journal Entry**:
```
Account                          Debit          Credit
Accumulated Depreciation         XXX            -
Loss on Disposal                XXX            -
    Cr. Asset Account (Cost)     -              XXX

If Insurance Recovery:
Dr. Insurance Recovery Account  XXX            -
    Cr. Loss on Disposal         -              XXX
```

**Impact**:
- Asset derecognized
- Loss recognized in P&L
- Insurance recovery reduces loss

---

### Revaluation Reserve Transfer

**When Applicable**: Asset has revaluation reserve balance

**Journal Entry**:
```
Account                          Debit          Credit
Revaluation Reserve              XXX            -
    Cr. Retained Earnings        -              XXX
```

**Impact**:
- Revaluation reserve transferred to retained earnings
- No impact on P&L
- Equity reclassified

---

## Partial Payments and Receivables

### Scenario: Sale with Partial Payment

**Example**:
- Disposal Proceeds: 1,000,000
- Amount Paid: 600,000
- Remaining Balance: 400,000

**Process**:

1. **Initial Disposal Posting**:
   - Bank Account: 600,000 (Dr)
   - Receivable Account: 400,000 (Dr)
   - Asset derecognized
   - Gain/loss calculated on full proceeds

2. **Record Remaining Receivable Payment**:
   - Navigate to Disposal Details
   - Click "Repay Remaining Receivable"
   - Enter payment date
   - Select bank account
   - Enter amount paid
   - System creates receipt and GL transactions

3. **Receipt GL Transactions**:
   ```
   Dr. Bank Account               400,000
       Cr. Receivable Account     400,000
   ```

4. **Update Disposal**:
   - `amount_paid` updated to 1,000,000
   - Receivable cleared

---

## VAT and Withholding Tax

### VAT Calculation

#### Exclusive VAT
```
VAT Amount = Sale Amount × VAT Rate
Gross Amount = Sale Amount + VAT Amount
```

#### Inclusive VAT
```
VAT Amount = Sale Amount × (VAT Rate / (100 + VAT Rate))
Net Amount = Sale Amount - VAT Amount
```

### Withholding Tax Calculation

#### Percentage WHT
```
WHT Amount = Net Proceeds × WHT Rate
Amount Received = Net Proceeds - WHT Amount
```

#### Fixed WHT
```
WHT Amount = Fixed Amount
Amount Received = Net Proceeds - Fixed Amount
```

### Combined VAT and WHT

**Order of Calculation**:
1. Calculate Net Proceeds (exclusive VAT)
2. Calculate VAT on Net Proceeds
3. Calculate WHT on Net Proceeds
4. Amount Received = Net Proceeds - WHT

**Example**:
- Sale Amount: 1,000,000 (exclusive VAT)
- VAT Rate: 18%
- WHT Rate: 5%

**Calculation**:
```
VAT = 1,000,000 × 18% = 180,000
WHT = 1,000,000 × 5% = 50,000
Amount Received = 1,000,000 - 50,000 = 950,000
Gross Amount = 1,000,000 + 180,000 = 1,180,000
```

---

## Revaluation Reserve Transfer

### When Transfer Occurs

**Condition**: Asset has `revaluation_reserve_balance > 0`

**Process**:
1. On disposal posting, system checks revaluation reserve balance
2. If balance > 0, transfer journal is created
3. Revaluation reserve transferred to retained earnings
4. Asset reserve balance reset to 0

### Journal Entry

```
Dr. Revaluation Reserve              XXX
    Cr. Retained Earnings            XXX
```

### Impact

- **Balance Sheet**: Revaluation reserve decreases, retained earnings increases
- **P&L**: No impact (equity reclassification)
- **Asset**: Reserve balance reset to 0

---

## Insurance Recovery

### Process

1. **Record Loss/Theft Disposal**:
   - Disposal type: "Loss/Theft"
   - Loss recognized immediately
   - Insurance claim number recorded

2. **Record Insurance Recovery**:
   - Enter insurance recovery amount
   - Enter insurance claim number
   - Enter recovery date
   - System creates recovery journal

3. **Recovery Journal Entry**:
   ```
   Dr. Insurance Recovery Account    XXX
       Cr. Loss on Disposal          XXX
   ```

### Impact

- **Initial Loss**: Full loss recognized
- **Recovery**: Loss reduced by recovery amount
- **Net Loss**: Loss - Recovery

---

## Approval Workflow

### Workflow Levels

#### Level 1: Finance Manager Only
- Finance Manager reviews and approves
- Suitable for routine disposals
- Lower value thresholds

#### Level 2: Finance Manager + CFO/Board
- Finance Manager reviews first
- CFO/Board final approval required
- Suitable for significant disposals
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

4. **Approved** → **Posted**
   - Finance posts to GL
   - Journal entries created
   - Asset status updated to "Disposed"

---

## Reports and Analytics

### Operational Reports

#### 1. Disposal Register
- All disposals by period
- Disposal type breakdown
- Gain/loss summary
- Filter by: Asset, Category, Type, Date Range, Status

#### 2. Disposal Summary Report
- Total disposals by type
- Total proceeds
- Total gains/losses
- Average gain/loss
- Filter by: Period, Category, Branch

#### 3. Gain/Loss Analysis
- Gains by period
- Losses by period
- Net gain/loss
- By disposal type
- By asset category

#### 4. Receivable Aging Report
- Outstanding receivables from disposals
- Aging buckets (0-30, 31-60, 61-90, 90+ days)
- Total outstanding
- Payment history

### Financial Reports

#### 1. Disposal Impact on P&L
- Gains on disposal
- Losses on disposal
- Net impact on profit/loss
- By period

#### 2. Disposal Impact on Balance Sheet
- Assets derecognized
- Proceeds received
- Receivables created
- Net impact

#### 3. Revaluation Reserve Transfer Report
- Transfers to retained earnings
- By disposal
- Total transfers by period

---

## Integration Points

### 1. Fixed Assets Module

**Integration**:
- Asset selection in disposal forms
- Asset status updates on disposal
- Asset history updates
- NBV calculation

**Data Flow**:
- Disposal Creation → Asset selection
- Disposal Posting → Asset status to "Disposed"
- Disposal Posting → Asset derecognized

---

### 2. General Ledger (GL)

**Integration**:
- Automatic journal entry creation
- Account mapping from settings
- Transaction posting
- Audit trail

**Data Flow**:
- Disposal Approval → Journal Entry Creation
- Journal Entry → GL Transactions
- GL Transactions → Financial Statements

---

### 3. Chart of Accounts

**Integration**:
- Gain/loss accounts
- Donation expense account
- Loss account
- VAT and WHT accounts
- Account validation

**Data Flow**:
- Asset Settings → Default Accounts
- Category Settings → Category-Specific Accounts
- Disposal → Account Selection
- GL Posting → Account Debits/Credits

---

### 4. Customer Module

**Integration**:
- Customer selection for sales
- Customer receivable account
- Customer payment tracking

**Data Flow**:
- Sale Disposal → Customer Selection
- Customer → Receivable Account
- Partial Payment → Receivable Tracking

---

### 5. Bank Accounts Module

**Integration**:
- Bank account selection for payments
- Bank account chart account mapping
- Payment tracking

**Data Flow**:
- Sale Disposal → Bank Account Selection
- Bank Account → Chart Account
- Payment → Bank Account Debit

---

## User Roles and Permissions

### Required Permissions

1. **Disposal Creator**
   - `create asset disposals`
   - `view asset disposals`
   - `edit asset disposals` (draft only)

2. **Finance Manager**
   - `approve asset disposals`
   - `view asset disposals`
   - `view disposal reports`

3. **CFO/Board**
   - `approve asset disposals` (Level 2)
   - `view asset disposals`
   - `view disposal reports`

4. **Finance/Accountant**
   - `post asset disposals to gl`
   - `view asset disposals`
   - `view disposal reports`
   - `record disposal receivables`

5. **Disposal Administrator**
   - All permissions
   - `manage disposal reason codes`
   - `delete asset disposals`

---

## Workflow Summary Diagrams

### Disposal Workflow

```
┌─────────────────────────────────────────────────────────────┐
│              DISPOSAL INITIATION                             │
│  - Select Asset                                              │
│  - Select Disposal Type                                      │
│  - Enter Disposal Details                                    │
│  - Enter Financial Information                              │
│  - Configure VAT/WHT (if applicable)                        │
│  Status: Draft                                               │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              NBV & GAIN/LOSS CALCULATION                     │
│  - Calculate Net Book Value                                  │
│  - Calculate Gain/Loss                                       │
│  - Update Disposal Record                                    │
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
│              APPROVAL WORKFLOW                               │
│                                                               │
│  Level 1: Finance Manager → Approve                          │
│  OR                                                           │
│  Level 2: Finance Manager → CFO/Board → Approve              │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              GL POSTING                                       │
│  - Generate Journal Entries                                  │
│  - Post to General Ledger                                    │
│  - Update Asset Status to "Disposed"                          │
│  - Transfer Revaluation Reserve (if applicable)              │
│  Status: Completed                                           │
└─────────────────────────────────────────────────────────────┘
```

### Sale Disposal with Partial Payment

```
┌─────────────────────────────────────────────────────────────┐
│              INITIAL DISPOSAL POSTING                        │
│  - Dr. Bank Account (Amount Paid)                            │
│  - Dr. Receivable Account (Balance)                          │
│  - Dr. Accumulated Depreciation                              │
│  - Cr. Asset Account (Cost)                                  │
│  - Cr./Dr. Gain/Loss                                         │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              RECEIVABLE PAYMENT                               │
│  - Record Payment Date                                        │
│  - Select Bank Account                                        │
│  - Enter Amount Paid                                          │
│  - Create Receipt                                             │
└───────────────────────┬───────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              RECEIPT GL POSTING                               │
│  - Dr. Bank Account                                           │
│  - Cr. Receivable Account                                     │
│  - Update Disposal Amount Paid                                │
└─────────────────────────────────────────────────────────────┘
```

---

## Key Database Tables

1. **asset_disposals**: Disposal transaction records
2. **disposal_reason_codes**: Standardized reason codes
3. **disposal_approvals**: Approval workflow records
4. **assets**: Updated with disposal status
5. **asset_categories**: Disposal account configuration
6. **journals**: GL journal entries
7. **journal_items**: GL journal entry line items
8. **gl_transactions**: GL transaction records
9. **receipts**: Receipt records for receivable payments
10. **receipt_items**: Receipt line items

---

## Best Practices

### 1. Disposal Management

- **Timely Recording**: Record disposals promptly after occurrence
- **Complete Documentation**: Maintain disposal documents (agreements, certificates)
- **Accurate Valuation**: Use fair value for donations and scrap
- **Reason Codes**: Use standardized reason codes for consistency

### 2. Financial Accuracy

- **NBV Verification**: Verify NBV calculation before posting
- **Gain/Loss Review**: Review gain/loss calculations
- **Account Mapping**: Ensure correct account mapping
- **VAT/WHT Compliance**: Ensure VAT and WHT are calculated correctly

### 3. Receivable Management

- **Timely Collection**: Follow up on outstanding receivables
- **Payment Recording**: Record payments promptly
- **Aging Review**: Regularly review receivable aging
- **Write-off Policy**: Establish policy for uncollectible receivables

### 4. Compliance

- **Accounting Standards**: Ensure compliance with IAS 16
- **Tax Regulations**: Comply with local VAT and WHT regulations
- **Documentation**: Maintain complete disposal documentation
- **Audit Trail**: Ensure complete audit trail

### 5. System Usage

- **Configuration**: Properly configure disposal accounts before creating disposals
- **Approval Workflow**: Follow approval workflow as configured
- **GL Posting**: Post to GL promptly after approval
- **Asset Updates**: Verify asset records are updated correctly after posting

---

## Troubleshooting

### Common Issues

1. **Disposal Not Appearing in List**:
   - Check branch filter
   - Verify company_id matches
   - Check status filter
   - Review user permissions

2. **Cannot Post to GL**:
   - Verify disposal is approved
   - Check disposal accounts are configured
   - Verify journal entry service is working
   - Review error logs

3. **NBV Calculation Incorrect**:
   - Verify asset cost is correct
   - Check accumulated depreciation calculation
   - Review accumulated impairment
   - Verify disposal date

4. **Gain/Loss Calculation Incorrect**:
   - Verify disposal proceeds
   - Check NBV calculation
   - Review VAT/WHT impact
   - Verify calculation formula

5. **VAT/WHT Not Calculating**:
   - Check VAT/WHT is enabled
   - Verify VAT/WHT rates
   - Review VAT type (exclusive/inclusive)
   - Check WHT type (percentage/fixed)

6. **Receivable Not Created**:
   - Verify amount paid < disposal proceeds
   - Check receivable account is configured
   - Review partial payment logic
   - Check customer receivable account

7. **Revaluation Reserve Not Transferring**:
   - Verify asset has revaluation reserve balance
   - Check retained earnings account is configured
   - Review transfer logic
   - Verify transfer journal creation

8. **Insurance Recovery Not Recording**:
   - Verify disposal type is "Loss/Theft"
   - Check insurance recovery account is configured
   - Review recovery journal creation
   - Verify recovery amount

9. **Reports Not Showing Data**:
   - Check date filters
   - Verify company/branch selection
   - Review user permissions
   - Check data exists in database

10. **Partial Payment Not Working**:
    - Verify disposal type is "Sale"
    - Check amount paid < disposal proceeds
    - Review bank account selection
    - Verify receivable account configuration

---

## Conclusion

The Asset Disposal & Retirement Management System provides a complete end-to-end solution for managing asset disposals and retirements in compliance with IAS 16 and other relevant accounting standards. By following this flow, organizations can:

- **Ensure Compliance**: Meet IAS 16 requirements for asset derecognition
- **Maintain Accuracy**: Keep disposal records and calculations accurate
- **Control Processes**: Implement proper approval workflows and documentation
- **Generate Reports**: Produce required financial statement disclosures
- **Audit Trail**: Maintain complete history of all disposal transactions
- **Manage Receivables**: Track and collect outstanding disposal receivables

For technical support or feature requests, contact the development team.

---

**Document Version**: 1.0  
**Last Updated**: November 2025  
**System**: SmartAccounting - Asset Disposal & Retirement Management Module

