# Intangible Assets Management System - Complete Flow Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Pre-Setup Configuration](#pre-setup-configuration)
3. [Intangible Asset Recognition Flow](#intangible-asset-recognition-flow)
4. [Cost Components Management](#cost-components-management)
5. [Amortization Flow](#amortization-flow)
6. [Impairment Testing and Recognition Flow](#impairment-testing-and-recognition-flow)
7. [Impairment Reversal Flow](#impairment-reversal-flow)
8. [Disposal Flow](#disposal-flow)
9. [GL Posting and Accounting Integration](#gl-posting-and-accounting-integration)
10. [Reports and Analytics](#reports-and-analytics)
11. [Integration Points](#integration-points)
12. [User Roles and Permissions](#user-roles-and-permissions)
13. [Best Practices](#best-practices)
14. [Troubleshooting](#troubleshooting)

---

## System Overview

The Intangible Assets Management System is a comprehensive module within the SmartAccounting Assets Management system that handles the complete lifecycle of intangible assets from recognition to disposal in compliance with IAS 38 (Intangible Assets) and IPSAS 31 (Intangible Assets) standards.

### Key Features
- **Asset Recognition**: Initial recognition with IAS 38 criteria validation
- **Cost Components**: Detailed tracking of all costs included in asset recognition
- **Amortization**: Automatic monthly amortization for finite-life assets
- **Indefinite-Life Assets**: Special handling for assets with indefinite useful lives (not amortized)
- **Goodwill**: Special handling for goodwill (not amortized, tested for impairment annually)
- **Impairment Testing**: Recognition and measurement of impairment losses
- **Impairment Reversals**: Reversal of previously recognized impairment losses (except goodwill)
- **Disposal**: Complete disposal process with gain/loss calculation
- **GL Integration**: Automatic journal entry generation
- **Audit Trail**: Complete history of all intangible asset transactions
- **Category Management**: Flexible category system with account mappings

### Compliance Standards
- **IAS 38**: Intangible Assets
- **IAS 36**: Impairment of Assets (applies to intangible assets)
- **IPSAS 31**: Intangible Assets
- **IPSAS 21**: Impairment of Non-Cash-Generating Assets
- **IPSAS 26**: Impairment of Cash-Generating Assets

### Intangible Asset Types
1. **Purchased Intangibles**: Acquired from external parties
2. **Internally Developed**: Developed internally (subject to strict recognition criteria)
3. **Goodwill**: Arising from business combinations
4. **Indefinite-Life Intangibles**: Assets with no foreseeable limit to useful life (e.g., brands, trademarks)

---

## Pre-Setup Configuration

### 1. Intangible Asset Category Configuration

**Location**: `/asset-management/intangible/categories`

**Purpose**: Configure categories with default chart accounts and settings for different types of intangible assets.

**Steps**:

1. Navigate to Asset Management → Intangible Assets → Categories
2. Click "Create New Category"
3. Configure the following:

#### A. Basic Information
- **Category Name**: e.g., "Software Licenses", "Patents", "Trademarks", "Goodwill"
- **Category Code**: Unique identifier (optional)
- **Type**: 
  - `purchased`: Purchased intangible assets
  - `internally_developed`: Internally developed intangibles
  - `goodwill`: Goodwill from business combinations
  - `indefinite_life`: Assets with indefinite useful lives

#### B. Asset Characteristics
- **Is Goodwill**: Check if this category is for goodwill
- **Is Indefinite Life**: Check if assets in this category have indefinite useful lives

#### C. Default Chart Accounts
- **Cost Account**: Asset account for intangible asset cost (e.g., "Intangible Assets - Software")
- **Accumulated Amortisation Account**: Contra-asset account for accumulated amortization (e.g., "Accumulated Amortisation - Software")
- **Accumulated Impairment Account**: Contra-asset account for accumulated impairment (e.g., "Accumulated Impairment - Software")
- **Amortisation Expense Account**: Expense account for amortization charges (e.g., "Amortisation Expense - Software")
- **Impairment Loss Account**: Expense account for impairment losses (e.g., "Impairment Loss - Intangible Assets")
- **Disposal Gain/Loss Account**: Income/expense account for disposal gains/losses (e.g., "Gain/Loss on Disposal of Intangible Assets")

**Example Configuration**:
```
Category: Software Licenses
- Type: purchased
- Is Goodwill: No
- Is Indefinite Life: No
- Cost Account: 1500 - Intangible Assets - Software
- Accumulated Amortisation Account: 1501 - Accumulated Amortisation - Software
- Accumulated Impairment Account: 1502 - Accumulated Impairment - Software
- Amortisation Expense Account: 5100 - Amortisation Expense - Software
- Impairment Loss Account: 5200 - Impairment Loss - Intangible Assets
- Disposal Gain/Loss Account: 5300 - Gain/Loss on Disposal of Intangible Assets
```

**Special Configuration for Goodwill**:
```
Category: Goodwill
- Type: goodwill
- Is Goodwill: Yes
- Is Indefinite Life: Yes
- Cost Account: 1600 - Goodwill
- Accumulated Amortisation Account: (Not applicable - goodwill not amortized)
- Accumulated Impairment Account: 1601 - Accumulated Impairment - Goodwill
- Amortisation Expense Account: (Not applicable - goodwill not amortized)
- Impairment Loss Account: 5201 - Impairment Loss - Goodwill
- Disposal Gain/Loss Account: 5300 - Gain/Loss on Disposal of Intangible Assets
```

**Special Configuration for Indefinite-Life Assets**:
```
Category: Trademarks
- Type: purchased
- Is Goodwill: No
- Is Indefinite Life: Yes
- Cost Account: 1510 - Intangible Assets - Trademarks
- Accumulated Amortisation Account: (Not applicable - indefinite-life not amortized)
- Accumulated Impairment Account: 1511 - Accumulated Impairment - Trademarks
- Amortisation Expense Account: (Not applicable - indefinite-life not amortized)
- Impairment Loss Account: 5200 - Impairment Loss - Intangible Assets
- Disposal Gain/Loss Account: 5300 - Gain/Loss on Disposal of Intangible Assets
```

---

## Intangible Asset Recognition Flow

### Step 1: Recognition Initiation

**Location**: `/asset-management/intangible/assets/create`

**IAS 38 Recognition Criteria**:
An intangible asset must meet ALL of the following criteria to be recognized:
1. **Identifiability**: Asset is separable or arises from contractual/legal rights
2. **Control**: Entity has power to obtain future economic benefits
3. **Future Economic Benefits**: Probable that future economic benefits will flow to the entity
4. **Reliable Measurement**: Cost can be measured reliably

**Process**:

1. **Select Category**
   - Choose from configured intangible asset categories
   - System auto-loads default accounts from category

2. **Enter Basic Information**:
   - **Asset Code**: Unique identifier (e.g., "IA-2025-0001")
   - **Asset Name**: Descriptive name (e.g., "Microsoft Office 365 License")
   - **Source Type**: 
     - `purchased`: Acquired from external party
     - `internally_developed`: Developed internally
     - `goodwill`: Goodwill from business combination
     - `other`: Other source
   - **Acquisition Date**: Date of acquisition/recognition
   - **Description**: Detailed description of the asset

3. **Recognition Criteria Checks** (IAS 38 Compliance):
   - ☑ **Identifiable**: Asset is separable or arises from contractual/legal rights
   - ☑ **Control**: Entity has power to obtain future economic benefits
   - ☑ **Future Economic Benefits**: Probable that future economic benefits will flow to the entity
   - ☑ **Reliable Measurement**: Cost can be measured reliably
   - **Notes**: Additional justification for recognition

4. **Cost Information**:
   - **Total Cost**: Initial cost of the intangible asset
   - **Cost Components**: (See Step 2 for detailed cost component entry)

5. **Useful Life Determination**:
   - **Is Indefinite Life**: Check if asset has indefinite useful life
   - **Is Goodwill**: Check if asset is goodwill
   - **Useful Life (Months)**: Required for finite-life assets (e.g., 60 months for 5-year license)
   - **Note**: Goodwill and indefinite-life assets are NOT amortized

6. **Validation Rules**:
   - If `is_goodwill = true` or `source_type = 'goodwill'`:
     - System automatically sets `is_indefinite_life = true`
     - System automatically sets `useful_life_months = null`
   - If `is_indefinite_life = true`:
     - System automatically sets `useful_life_months = null`
   - For finite-life assets:
     - `useful_life_months` is REQUIRED
     - Must be greater than 0

7. **Submit Recognition**
   - System validates recognition criteria
   - System generates unique asset code (if not provided)
   - Status: `active`
   - Initial journal entry created automatically

---

### Step 2: Cost Components Management

**Location**: `/asset-management/intangible/assets/{id}/cost-components`

**Purpose**: Track all costs that form part of the intangible asset's initial cost.

**IAS 38 Cost Components**:
- Purchase price (less trade discounts and rebates)
- Directly attributable costs:
  - Professional fees (legal, valuation)
  - Registration fees
  - Import duties and non-refundable purchase taxes
  - Costs of testing whether asset is functioning properly
- Initial operating losses (NOT included)
- Training costs (NOT included unless essential for asset to function)

**Process**:

1. **Add Cost Component**:
   - **Date**: Date of cost incurrence
   - **Type**: 
     - `purchase_price`: Purchase price
     - `legal_fees`: Legal fees
     - `registration_fees`: Registration fees
     - `valuation_fees`: Valuation fees
     - `import_duties`: Import duties and taxes
     - `testing_costs`: Testing costs
     - `other`: Other directly attributable costs
   - **Description**: Detailed description
   - **Amount**: Cost amount
   - **Source Document**: Link to purchase invoice, receipt, etc. (optional)

2. **Cost Component Validation**:
   - System validates that total cost components match asset cost
   - System flags discrepancies for review

3. **Cost Component Listing**:
   - View all cost components
   - Edit or delete cost components (if not yet posted to GL)
   - Export cost component breakdown

---

### Step 3: Initial Recognition GL Posting

**Automatic Process** (performed by system upon asset creation):

1. **Journal Entry Creation**:
   ```
   Dr. Intangible Asset - Cost Account        XXX
       Cr. Cash/Payable/Other Source          XXX
   ```

2. **System Actions**:
   - Journal entry created in `journals` table
   - Journal items created in `journal_items` table
   - GL transactions posted to `gl_transactions` table
   - `initial_journal_id`: Reference to journal entry
   - Asset status: `active`
   - Initial balances:
     - `cost`: Total cost
     - `accumulated_amortisation`: 0
     - `accumulated_impairment`: 0
     - `nbv`: Cost (Net Book Value = Cost - Accumulated Amortisation - Accumulated Impairment)

3. **Account Selection**:
   - **Debit Account**: From category `cost_account_id`
   - **Credit Account**: Determined from source document (purchase invoice, cash payment, etc.)

---

## Amortization Flow

### Overview

**IAS 38 Amortization Rules**:
- **Finite-Life Assets**: Must be amortized over useful life
- **Indefinite-Life Assets**: NOT amortized (tested for impairment annually)
- **Goodwill**: NOT amortized (tested for impairment annually or more frequently)

**Amortization Methods**:
- **Straight-Line Method**: Default method (cost / useful life in months)
- **Amortization Period**: Monthly

### Step 1: Automatic Monthly Amortization

**Location**: Automated process (scheduled command)

**Process**:

1. **Amortization Run** (typically run monthly):
   - System identifies eligible assets:
     - Status: `active`
     - `is_indefinite_life = false`
     - `is_goodwill = false`
     - `nbv > 0`
     - `useful_life_months` is set
     - Acquisition date <= period end date

2. **Amortization Calculation**:
   ```
   Monthly Amortization = Cost / Useful Life (in months)
   Actual Amortization = MIN(Monthly Amortization, Current NBV)
   ```
   - System ensures NBV does not go below zero
   - System stops amortization when NBV reaches zero

3. **Journal Entry Creation** (for each asset):
   ```
   Dr. Amortisation Expense Account        XXX
       Cr. Accumulated Amortisation Account    XXX
   ```

4. **System Updates**:
   - `accumulated_amortisation` increased by amortization amount
   - `nbv` recalculated (NBV = Cost - Accumulated Amortisation - Accumulated Impairment)
   - If `nbv <= 0`:
     - Status changed to `fully_amortised`
     - Amortization stops
   - Amortization record created in `intangible_amortisations` table

5. **Amortization History**:
   - Each amortization run creates a record with:
     - Amortization date
     - Amount
     - Accumulated amortization after
     - NBV after
     - Journal reference

### Step 2: Manual Amortization (if needed)

**Location**: `/asset-management/intangible/amortisations/create`

**Use Cases**:
- Adjustments for prior periods
- One-time amortization charges
- Corrective entries

**Process**:

1. **Select Asset**
2. **Enter Amortization Details**:
   - **Amortization Date**: Date of amortization
   - **Amount**: Amortization amount
   - **Reason**: Justification for manual entry

3. **System Validation**:
   - Verifies asset is eligible for amortization
   - Verifies amount does not exceed current NBV
   - Verifies accounts are configured

4. **GL Posting**:
   - Journal entry created automatically
   - Asset balances updated
   - Amortization record created

---

## Impairment Testing and Recognition Flow

### Overview

**IAS 36 Impairment Requirements**:
- **Finite-Life Assets**: Tested for impairment when indicators exist
- **Indefinite-Life Assets**: Tested for impairment annually (or more frequently)
- **Goodwill**: Tested for impairment annually (or more frequently)

**Impairment Indicators**:
- External: Market value decline, legal/regulatory changes, economic environment changes
- Internal: Obsolescence, physical damage, asset performance below expectations

### Step 1: Impairment Testing Initiation

**Location**: `/asset-management/intangible/impairments/create`

**Process**:

1. **Select Asset**
   - System auto-fetches:
     - Asset code and name
     - Current carrying amount (NBV)
     - Asset category
     - Previous impairment history

2. **Enter Impairment Test Details**:
   - **Impairment Date**: Date of impairment test
   - **Method**: 
     - `value_in_use`: Value in use calculation
     - `fair_value_less_costs`: Fair value less costs to sell

3. **Calculate Recoverable Amount**:

   **A. Fair Value Less Costs to Sell**:
   - Enter market value
   - Enter disposal costs
   - Calculate: Market Value - Disposal Costs

   **B. Value in Use** (if applicable):
   - Enter discount rate (%)
   - Enter cash flow projections (Year 1, Year 2, Year 3, etc.)
   - System calculates present value:
     ```
     Value in Use = Σ (Cash Flow Year N / (1 + Discount Rate)^N)
     ```

4. **Recoverable Amount Determination**:
   ```
   Recoverable Amount = Higher of:
   - Fair Value Less Costs to Sell
   - Value in Use
   ```

5. **Impairment Loss Calculation**:
   ```
   Impairment Loss = Carrying Amount (NBV) - Recoverable Amount
   (Only if Carrying Amount > Recoverable Amount)
   ```

6. **Assumptions and Documentation**:
   - Enter assumptions used in calculation
   - Upload impairment test report (optional)
   - Enter notes/justification

7. **Submit Impairment Test**
   - System validates that recoverable amount < carrying amount
   - If impairment exists, system creates impairment record and posts to GL

---

### Step 2: Impairment Recognition GL Posting

**Automatic Process** (performed by system upon impairment recognition):

1. **Journal Entry Creation**:
   ```
   Dr. Impairment Loss Account (P&L)        XXX
       Cr. Accumulated Impairment Account       XXX
   ```

2. **System Actions**:
   - Journal entry created in `journals` table
   - Journal items created in `journal_items` table
   - GL transactions posted to `gl_transactions` table
   - `journal_id`: Reference to journal entry
   - `gl_posted`: true
   - Status: `posted`

3. **Asset Updates**:
   - `accumulated_impairment` increased by impairment loss
   - `nbv` recalculated (NBV = Cost - Accumulated Amortisation - Accumulated Impairment)
   - If `nbv <= 0`:
     - Status changed to `impaired`
   - Impairment record created in `intangible_impairments` table

4. **Account Selection**:
   - **Debit Account**: From category `impairment_loss_account_id`
   - **Credit Account**: From category `accumulated_impairment_account_id`

---

## Impairment Reversal Flow

### Overview

**IAS 36 Reversal Rules**:
- Impairment losses can be reversed (except for goodwill)
- Reversal limited to original carrying amount (before impairment)
- Reversal recognized in P&L

### Step 1: Impairment Reversal Initiation

**Location**: `/asset-management/intangible/impairments/{id}/reverse`

**Process**:

1. **Select Impairment to Reverse**
   - System displays:
     - Original impairment loss
     - Current carrying amount
     - Original carrying amount (before impairment)

2. **Enter Reversal Details**:
   - **Reversal Date**: Date of reversal
   - **New Recoverable Amount**: Updated recoverable amount
   - **Reversal Amount**: Amount to reverse (limited to original impairment loss)
   - **Reason**: Justification for reversal

3. **System Validation**:
   - Verifies recoverable amount > current carrying amount
   - Verifies reversal amount <= original impairment loss
   - Verifies reversal amount <= (original carrying amount - current carrying amount)

4. **Submit Reversal**
   - System creates reversal record
   - System posts reversal to GL

---

### Step 2: Impairment Reversal GL Posting

**Automatic Process** (performed by system upon reversal):

1. **Journal Entry Creation**:
   ```
   Dr. Accumulated Impairment Account       XXX
       Cr. Impairment Reversal Account (P&L)    XXX
   ```

2. **System Actions**:
   - Journal entry created
   - GL transactions posted
   - `is_reversal`: true
   - `reversed_impairment_id`: Reference to original impairment

3. **Asset Updates**:
   - `accumulated_impairment` decreased by reversal amount
   - `nbv` recalculated
   - Status updated (if applicable)

---

## Disposal Flow

### Step 1: Disposal Initiation

**Location**: `/asset-management/intangible/disposals/create`

**Process**:

1. **Select Asset**
   - System auto-fetches:
     - Asset code and name
     - Current NBV
     - Accumulated amortization
     - Accumulated impairment

2. **Enter Disposal Details**:
   - **Disposal Date**: Date of disposal
   - **Proceeds**: Disposal proceeds (if any)
   - **Reason**: Reason for disposal

3. **Gain/Loss Calculation**:
   ```
   Gain/Loss = Disposal Proceeds - NBV at Disposal
   ```
   - If Proceeds > NBV: Gain
   - If Proceeds < NBV: Loss

4. **Submit Disposal**
   - System validates disposal
   - System creates disposal record
   - System posts disposal to GL

---

### Step 2: Disposal GL Posting

**Automatic Process** (performed by system upon disposal):

1. **Journal Entry Creation**:

   **For Disposal with Proceeds**:
   ```
   Dr. Cash/Receivable (Proceeds)          XXX
   Dr./Cr. Gain/Loss on Disposal           XXX/(XXX)
       Cr. Intangible Asset - Cost         XXX
       Cr. Accumulated Amortisation        XXX
       Cr. Accumulated Impairment          XXX
   ```

   **For Disposal without Proceeds (Write-off)**:
   ```
   Dr. Loss on Disposal                    XXX
       Cr. Intangible Asset - Cost         XXX
       Cr. Accumulated Amortisation        XXX
       Cr. Accumulated Impairment          XXX
   ```

2. **System Actions**:
   - Journal entry created
   - GL transactions posted
   - `gl_posted`: true
   - Asset status: `disposed`

3. **Account Selection**:
   - **Debit Account (Proceeds)**: Cash or receivable account
   - **Credit Account (Cost)**: From category `cost_account_id`
   - **Credit Account (Accumulated Amortisation)**: From category `accumulated_amortisation_account_id`
   - **Credit Account (Accumulated Impairment)**: From category `accumulated_impairment_account_id`
   - **Debit/Credit Account (Gain/Loss)**: From category `disposal_gain_loss_account_id`

---

## GL Posting and Accounting Integration

### Journal Entry Types

1. **Initial Recognition**:
   - Debit: Intangible Asset Cost Account
   - Credit: Cash/Payable/Other Source

2. **Amortization** (Monthly):
   - Debit: Amortisation Expense Account
   - Credit: Accumulated Amortisation Account

3. **Impairment**:
   - Debit: Impairment Loss Account
   - Credit: Accumulated Impairment Account

4. **Impairment Reversal**:
   - Debit: Accumulated Impairment Account
   - Credit: Impairment Reversal Account (or Impairment Loss Account with negative amount)

5. **Disposal**:
   - Debit: Cash/Receivable (Proceeds)
   - Debit/Credit: Gain/Loss on Disposal
   - Credit: Intangible Asset Cost Account
   - Credit: Accumulated Amortisation Account
   - Credit: Accumulated Impairment Account

### Account Mapping

All accounts are configured at the **Category Level**:
- Cost Account
- Accumulated Amortisation Account
- Accumulated Impairment Account
- Amortisation Expense Account
- Impairment Loss Account
- Disposal Gain/Loss Account

### GL Transaction Details

Each journal entry creates corresponding GL transactions:
- `transaction_type`: 'journal'
- `transaction_id`: Journal ID
- `date`: Transaction date
- `description`: Transaction description
- `branch_id`: Branch ID
- `user_id`: User who created the transaction

---

## Reports and Analytics

### Available Reports

1. **Intangible Assets Register**:
   - List of all intangible assets
   - Cost, accumulated amortization, accumulated impairment, NBV
   - Status, category, useful life
   - Filter by status, category, branch

2. **Amortization Schedule**:
   - Monthly amortization schedule for each asset
   - Projected amortization for future periods
   - Total amortization by period

3. **Impairment History**:
   - List of all impairment tests and results
   - Impairment losses recognized
   - Impairment reversals

4. **Disposal Report**:
   - List of disposed assets
   - Gain/loss on disposal
   - Disposal proceeds

5. **Category Summary**:
   - Summary by category
   - Total cost, accumulated amortization, NBV by category

6. **Aging Analysis**:
   - Assets by age
   - Remaining useful life analysis

### Export Options

- Excel export
- PDF export
- CSV export

---

## Integration Points

### 1. Purchase Invoice Integration

- Intangible assets can be recognized from purchase invoices
- Cost components automatically populated from invoice line items
- GL posting integrated with accounts payable

### 2. General Ledger Integration

- All transactions automatically posted to GL
- Real-time balance updates
- GL reconciliation support

### 3. Financial Reporting Integration

- Intangible assets included in balance sheet
- Amortization included in income statement
- Impairment losses included in income statement
- Disposal gains/losses included in income statement

### 4. Fixed Assets Integration

- Shared category management (if applicable)
- Shared account structure
- Unified reporting

---

## User Roles and Permissions

### Required Permissions

1. **View Intangible Assets**:
   - `assets.intangible.view`
   - Access to asset register and reports

2. **Create Intangible Assets**:
   - `assets.intangible.create`
   - Create new intangible assets

3. **Edit Intangible Assets**:
   - `assets.intangible.edit`
   - Modify existing assets (before GL posting)

4. **Delete Intangible Assets**:
   - `assets.intangible.delete`
   - Delete assets (before GL posting)

5. **Post Amortization**:
   - `assets.intangible.amortisation.post`
   - Run monthly amortization

6. **Record Impairment**:
   - `assets.intangible.impairment.create`
   - Record impairment tests and losses

7. **Record Disposal**:
   - `assets.intangible.disposal.create`
   - Record asset disposals

8. **Manage Categories**:
   - `assets.intangible.categories.manage`
   - Create and edit categories

---

## Best Practices

### 1. Recognition

- **Document Recognition Criteria**: Always document how each asset meets IAS 38 recognition criteria
- **Cost Components**: Break down all costs included in initial recognition
- **Source Documents**: Link all cost components to source documents (invoices, receipts)

### 2. Useful Life Determination

- **Finite-Life Assets**: Base useful life on:
  - Contractual/legal terms
  - Expected usage
  - Technical obsolescence
  - Economic factors
- **Indefinite-Life Assets**: Only classify as indefinite if:
  - No foreseeable limit to useful life
  - Asset generates cash flows indefinitely
  - Regular impairment testing performed

### 3. Amortization

- **Review Useful Lives**: Regularly review and update useful lives if circumstances change
- **Amortization Method**: Use straight-line method unless another method is more appropriate
- **Residual Value**: Consider residual value (usually zero for intangibles)

### 4. Impairment Testing

- **Annual Testing**: Test indefinite-life assets and goodwill annually
- **Indicator-Based Testing**: Test finite-life assets when impairment indicators exist
- **Documentation**: Document all assumptions and calculations
- **Valuation Reports**: Obtain professional valuations when significant

### 5. Disposal

- **Document Disposal**: Always document reason for disposal
- **Proceeds Recording**: Record all disposal proceeds accurately
- **Gain/Loss Calculation**: Verify gain/loss calculations

### 6. Category Management

- **Consistent Naming**: Use consistent naming conventions for categories
- **Account Mapping**: Ensure all required accounts are mapped
- **Regular Review**: Review category settings periodically

---

## Troubleshooting

### Common Issues and Solutions

#### 1. "Category is missing account mappings"

**Problem**: Category does not have required chart accounts configured.

**Solution**:
- Navigate to Asset Management → Intangible Assets → Categories
- Edit the category
- Configure all required chart accounts:
  - Cost Account
  - Accumulated Amortisation Account
  - Accumulated Impairment Account
  - Amortisation Expense Account
  - Impairment Loss Account
  - Disposal Gain/Loss Account

#### 2. "Useful life is required for finite-life assets"

**Problem**: Asset is marked as finite-life but useful life is not set.

**Solution**:
- Edit the asset
- Enter useful life in months
- Ensure "Is Indefinite Life" is unchecked
- Ensure "Is Goodwill" is unchecked

#### 3. "Amortization not running for eligible assets"

**Problem**: Monthly amortization command not running or assets not eligible.

**Solution**:
- Verify asset status is `active`
- Verify `is_indefinite_life = false`
- Verify `is_goodwill = false`
- Verify `nbv > 0`
- Verify `useful_life_months` is set
- Verify amortization accounts are configured in category
- Check scheduled command is running

#### 4. "Impairment cannot be recorded: recoverable amount >= carrying amount"

**Problem**: Recoverable amount is not less than carrying amount.

**Solution**:
- Verify recoverable amount calculation
- Ensure fair value less costs or value in use is correctly calculated
- If no impairment exists, do not record impairment

#### 5. "Disposal GL posting failed"

**Problem**: Required accounts not configured or asset not found.

**Solution**:
- Verify category has disposal gain/loss account configured
- Verify asset exists and is not already disposed
- Check GL posting logs for specific error

#### 6. "NBV calculation incorrect"

**Problem**: NBV does not match expected value.

**Solution**:
- Verify NBV formula: NBV = Cost - Accumulated Amortisation - Accumulated Impairment
- Check all amortization records are posted
- Check all impairment records are posted
- Recalculate NBV manually and compare

#### 7. "Goodwill/Indefinite-life asset being amortized"

**Problem**: System attempting to amortize assets that should not be amortized.

**Solution**:
- Verify `is_goodwill = true` or `is_indefinite_life = true`
- Verify `useful_life_months = null`
- Check category settings match asset settings

---

## Appendix: IAS 38 Key Requirements

### Recognition Criteria

An intangible asset is recognized if and only if:
1. It is probable that future economic benefits will flow to the entity
2. The cost can be measured reliably

### Measurement

- **Initial Measurement**: At cost
- **Subsequent Measurement**: 
  - Cost model: Cost less accumulated amortization and impairment
  - Revaluation model: Fair value less accumulated amortization and impairment (rarely used for intangibles)

### Amortization

- **Finite-Life Assets**: Amortized over useful life
- **Indefinite-Life Assets**: Not amortized, tested for impairment annually
- **Goodwill**: Not amortized, tested for impairment annually

### Impairment

- **Finite-Life Assets**: Tested when impairment indicators exist
- **Indefinite-Life Assets**: Tested annually (or more frequently)
- **Goodwill**: Tested annually (or more frequently)

### Disposal

- Derecognize when:
  - Asset is disposed
  - No future economic benefits expected
- Gain/loss = Disposal proceeds - Carrying amount

---

**End of Document**

