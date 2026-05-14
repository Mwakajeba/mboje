# IFRS 5 Held for Sale (HFS) Module - Auditor Documentation

## Table of Contents
1. [IFRS 5 Compliance Overview](#ifrs-5-compliance-overview)
2. [System Controls](#system-controls)
3. [Disclosure Requirements](#disclosure-requirements)
4. [Audit Trail](#audit-trail)
5. [Sample Reports](#sample-reports)
6. [Testing Procedures](#testing-procedures)
7. [Key Controls Checklist](#key-controls-checklist)

---

## IFRS 5 Compliance Overview

This module implements IFRS 5 - Non-current Assets Held for Sale and Discontinued Operations, ensuring:
- ✅ Proper classification criteria validation
- ✅ Automatic depreciation cessation (except Investment Property at FV)
- ✅ Measurement at lower of carrying amount and fair value less costs to sell
- ✅ Impairment recognition and reversal limits
- ✅ Discontinued operations presentation
- ✅ Comprehensive audit trail

---

## System Controls

### 1. Classification Controls
- **IFRS 5 Criteria Validation**: System validates all criteria before approval:
  - Asset available for immediate sale
  - Management commitment (with evidence)
  - Active program to locate buyer
  - Sale highly probable (>75%)
  - Expected within 12 months (or board approval for extension)

### 2. Approval Controls
- **Multi-level Approval**: Required approvals from:
  - Asset Custodian
  - Finance Manager
  - CFO/Board
- **Audit Trail**: All approvals logged with user, date, time, comments
- **Immutable Records**: Once approved and posted, records cannot be modified

### 3. Measurement Controls
- **FV Less Costs Calculation**: Automatic calculation
- **Impairment Limits**: Reversals limited to original carrying amount
- **Valuation Evidence**: Requires valuator details and report reference
- **Override Controls**: Valuation overrides require justification and approver

### 4. Depreciation Controls
- **Automatic Cessation**: Depreciation stops on HFS classification
- **Investment Property Exception**: Continues depreciation per IAS 40
- **Prevention**: System prevents depreciation postings for HFS assets

### 5. 12-Month Rule Controls
- **Automatic Flagging**: Items exceeding 12 months are flagged
- **Extension Approval**: Extensions require board approval and justification
- **Alerts**: System alerts approaching deadline (11 months)

---

## Disclosure Requirements

### Required Disclosures (IFRS 5.41-44)

#### 1. Movement Schedule
The system generates an IFRS 5 compliant movement schedule showing:
- Carrying amount at start of period
- Classified as HFS during period
- Impairment losses recognized
- Reversals of impairment
- Transfers (to/from HFS)
- Disposals (proceeds)
- Carrying amount at end of period

**Report Location**: Assets Management → HFS → Reports → Movement Schedule

#### 2. Valuation Details
For each HFS asset, the system tracks:
- Date classified
- Carrying amount at classification
- Fair value
- Costs to sell
- FV less costs
- Impairment posted (Y/N)
- Journal reference
- Valuator and report reference

**Report Location**: Assets Management → HFS → Reports → Valuation Details

#### 3. Discontinued Operations Note
If disposal group meets discontinued operations criteria:
- Revenue from discontinued operations
- Profit/(loss) from discontinued operations
- Gain/(loss) on disposal
- Tax
- Total impact to net profit
- Comparative periods

**Report Location**: Assets Management → HFS → Reports → Discontinued Operations Note

#### 4. Overdue HFS Items
List of HFS items exceeding 12 months with:
- Reason for extension
- Approval details
- Buyer progress notes

**Report Location**: Assets Management → HFS → Reports → Overdue HFS

---

## Audit Trail

### What is Logged
Every action in the HFS module is logged in `hfs_audit_logs` table:
- **User**: Who performed the action
- **Action**: Type of action (created, approved, measured, sold, etc.)
- **Description**: Detailed description
- **Old Values**: Previous state (JSON)
- **New Values**: New state (JSON)
- **IP Address**: User's IP address
- **User Agent**: Browser/device information
- **Timestamp**: Exact date and time

### Audit Trail Report
**Location**: Assets Management → HFS → Reports → Audit Trail

**Filters**:
- Date range
- User
- Action type
- HFS Request

**Export**: PDF or Excel

### Immutability
- Once GL transactions are posted, records are locked
- Audit logs cannot be modified or deleted
- All changes require new entries (append-only)

---

## Sample Reports

### 1. IFRS 5 Movement Schedule

| Asset/Disposal Group | Carrying at Start | Classified | Impairment | Reversals | Transfers | Disposals | Carrying at End |
|---------------------|-------------------|------------|------------|-----------|-----------|-----------|-----------------|
| Building A          | 0                 | 1,000,000  | 200,000    | 0         | 0         | 0         | 800,000         |
| Equipment Group B   | 500,000           | 0          | 0          | 50,000    | 0         | 450,000   | 100,000         |

### 2. Valuation Details

| HFS ID | Asset | Date Classified | Carrying Amount | Fair Value | Costs to Sell | FV Less Costs | Impairment | Journal Ref |
|--------|-------|----------------|-----------------|------------|---------------|---------------|------------|-------------|
| HFS-001| AST-001| 2025-01-15    | 1,000,000       | 800,000    | 50,000        | 750,000       | 250,000    | JRN-12345   |

### 3. Discontinued Operations Note

| Line Item | Current Year | Prior Year |
|-----------|--------------|------------|
| Revenue   | 5,000,000    | 6,000,000  |
| Expenses  | (4,200,000)  | (5,100,000)|
| Profit/(Loss) | 800,000   | 900,000    |
| Gain/(Loss) on Disposal | 200,000 | 0 |
| Tax | (300,000) | (270,000) |
| **Net Impact** | **700,000** | **630,000** |

---

## Testing Procedures

### Test 1: Classification Criteria
1. Attempt to create HFS request without management commitment
2. **Expected**: System rejects with error message
3. Add management commitment evidence
4. **Expected**: System accepts

### Test 2: Depreciation Prevention
1. Classify asset as HFS
2. Run depreciation process
3. **Expected**: Asset is skipped in depreciation run
4. Verify `depreciation_stopped = true` in database

### Test 3: Impairment Calculation
1. Create HFS request with carrying amount = 1,000,000
2. Record valuation: FV = 800,000, Costs = 50,000
3. **Expected**: Impairment = 250,000 (1,000,000 - 750,000)
4. Verify impairment journal posted

### Test 4: Reversal Limit
1. After impairment, record new valuation: FV = 1,100,000, Costs = 50,000
2. **Expected**: Reversal limited to 250,000 (original impairment)
3. Carrying amount = 1,000,000 (not 1,050,000)

### Test 5: 12-Month Rule
1. Create HFS request with intended sale date >12 months
2. **Expected**: System requires extension justification and board approval
3. After 12 months, check overdue flag
4. **Expected**: System flags as overdue

### Test 6: Multi-Currency
1. Record disposal in foreign currency (USD)
2. Set exchange rate
3. **Expected**: System calculates FX gain/loss and posts journal

### Test 7: Partial Sale
1. Create disposal group with 3 assets
2. Record partial sale of 1 asset
3. **Expected**: 1 asset disposed, 2 remain in HFS

### Test 8: Cancellation
1. Cancel approved HFS request
2. **Expected**: 
   - Assets reclassified to original category
   - Depreciation resumes
   - Cancellation journal posted

---

## Key Controls Checklist

### Pre-Classification
- [ ] Management commitment evidence attached
- [ ] Buyer identified OR active marketing program
- [ ] Sale expected within 12 months (or extension approved)
- [ ] Probability >75%
- [ ] All required fields completed

### Classification
- [ ] IFRS 5 criteria validated
- [ ] Multi-level approval obtained
- [ ] Reclassification journal posted
- [ ] Depreciation stopped (except Investment Property at FV)
- [ ] Audit log created

### Measurement
- [ ] Valuation evidence attached
- [ ] FV less costs calculated correctly
- [ ] Impairment posted if applicable
- [ ] Reversal limited to original carrying
- [ ] Journal references recorded

### Disposal
- [ ] Sale proceeds recorded
- [ ] Costs of disposal recorded
- [ ] Gain/loss calculated correctly
- [ ] FX gain/loss posted (if applicable)
- [ ] Deferred tax posted (if enabled)
- [ ] Asset status updated to "Disposed"

### Discontinued Operations
- [ ] Criteria checked
- [ ] Tagged if criteria met
- [ ] P&L effects recorded
- [ ] Comparative periods restated

### Reporting
- [ ] Movement schedule generated
- [ ] Valuation details available
- [ ] Discontinued ops note generated (if applicable)
- [ ] Audit trail complete
- [ ] All disclosures IFRS 5 compliant

---

## Data Integrity

### Foreign Key Constraints
- `hfs_requests.company_id` → `companies.id`
- `hfs_assets.hfs_id` → `hfs_requests.id`
- `hfs_assets.asset_id` → `assets.id`
- `hfs_valuations.hfs_id` → `hfs_requests.id`
- `hfs_disposals.hfs_id` → `hfs_requests.id`

### Transaction Integrity
- All critical operations wrapped in database transactions
- Rollback on any error
- No partial updates

### Audit Trail Integrity
- All changes logged before commit
- Immutable audit logs
- Complete before/after state capture

---

## System Settings

### Required Configuration
1. **Chart Accounts** (in Asset Settings):
   - Asset Account (per category)
   - Accumulated Depreciation Account (per category)
   - HFS Account (per category)
   - Impairment Loss Account
   - Gain/Loss on Disposal Account

2. **Deferred Tax** (if enabled):
   - Deferred Tax Asset Account
   - Deferred Tax Liability Account
   - Tax Rate

3. **FX Accounts** (for multi-currency):
   - FX Realized Gain Account
   - FX Realized Loss Account

---

## Contact

For technical questions or system access, contact the system administrator.

For accounting/IFRS questions, refer to IFRS 5 standard or consult with accounting advisors.

