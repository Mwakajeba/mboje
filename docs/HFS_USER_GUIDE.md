# IFRS 5 Held for Sale (HFS) Module - User Guide

## Table of Contents
1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Creating an HFS Request](#creating-an-hfs-request)
4. [Approval Workflow](#approval-workflow)
5. [Valuation and Measurement](#valuation-and-measurement)
6. [Recording Disposal](#recording-disposal)
7. [Discontinued Operations](#discontinued-operations)
8. [Reports](#reports)
9. [Common Scenarios](#common-scenarios)
10. [FAQ](#faq)

---

## Overview

The Held for Sale (HFS) module allows you to:
- Classify assets as Held for Sale when IFRS 5 criteria are met
- Stop depreciation automatically
- Measure assets at fair value less costs to sell
- Record impairments and reversals
- Process asset disposals with automatic gain/loss calculation
- Tag disposal groups as discontinued operations
- Generate IFRS 5 compliant reports

---

## Getting Started

### Prerequisites
- Assets must be registered in the Asset Registry
- Required chart accounts must be configured in Asset Settings
- User must have appropriate permissions

### Accessing the Module
Navigate to: **Assets Management → Held for Sale**

---

## Creating an HFS Request

### Step 1: Select Assets
1. Click **"Create HFS Request"**
2. Select one or more assets from the asset registry
3. For disposal groups, check **"This is a disposal group"**
4. Review selected assets and their carrying amounts

### Step 2: Enter Sale Plan
- **Intended Sale Date**: Expected date of sale (must be ≤12 months normally)
- **Expected Close Date**: Target closing date
- **Buyer Name**: Name of potential buyer (if identified)
- **Expected Fair Value**: Estimated fair value
- **Expected Costs to Sell**: Estimated costs (legal, brokerage, etc.)
- **Marketing Actions**: Description of marketing activities
- **Probability**: Likelihood of sale (should be >75% for "highly probable")

### Step 3: Documentation
- **Justification**: Reason for HFS classification
- **Management Commitment**: Attach management minutes or approval
- **Attachments**: Upload supporting documents (valuer reports, marketing materials)

### Step 4: Review and Submit
- Review all information
- System validates IFRS 5 criteria
- Click **"Submit for Approval"**

---

## Approval Workflow

### Approval Levels
1. **Initiator**: Creates and submits request
2. **Asset Custodian**: Reviews asset availability
3. **Finance Manager**: Reviews financial aspects
4. **CFO/Board**: Final approval

### Approval Process
1. Request moves through approval levels
2. Each approver can:
   - **Approve**: Move to next level or finalize
   - **Reject**: Return to initiator with comments
   - **Request Modification**: Ask for changes

### On Approval
- Assets are automatically reclassified to HFS
- Depreciation stops (except Investment Property at FV)
- Reclassification journal is posted
- Assets appear in HFS dashboard

---

## Valuation and Measurement

### When to Measure
- At initial classification (if FV less costs < carrying amount)
- Periodically during holding period
- Before disposal

### Recording Valuation
1. Navigate to HFS Request → **"Record Valuation"**
2. Enter:
   - **Fair Value**: Current fair value
   - **Costs to Sell**: Estimated costs
   - **Valuation Date**: Date of valuation
   - **Valuator Details**: Name, license, company
   - **Report Reference**: Reference to valuation report
3. System calculates:
   - **FV Less Costs**: Fair Value - Costs to Sell
   - **Impairment Amount**: If FV Less Costs < Carrying Amount
   - **Reversal Amount**: If FV increases (limited to original carrying)

### Impairment
- If impairment is calculated, system automatically posts impairment journal
- Impairment reduces carrying amount to FV less costs
- Recorded in P&L

### Reversal
- If FV less costs increases, reversal can be recorded
- Reversal is limited to original carrying amount before HFS impairment
- Reversal is recorded in P&L

---

## Recording Disposal

### Full Disposal
1. Navigate to HFS Request → **"Record Disposal"**
2. Enter:
   - **Disposal Date**: Date of sale
   - **Sale Proceeds**: Amount received
   - **Sale Currency**: Currency of sale (if different from functional currency)
   - **Currency Rate**: Exchange rate (if foreign currency)
   - **Costs Sold**: Actual costs incurred
   - **Buyer Information**: Name, contact, address
   - **Settlement Reference**: Payment reference
   - **Bank Account**: Account where proceeds were received
3. System calculates:
   - **Gain/Loss**: Sale Proceeds - Carrying Amount - Costs
   - **FX Gain/Loss**: If foreign currency (automatic)
4. Click **"Record Disposal"**
5. System automatically:
   - Posts disposal journal
   - Posts FX gain/loss journal (if applicable)
   - Posts deferred tax journal (if enabled)
   - Updates asset status to "Disposed"

### Partial Disposal
For disposal groups:
1. Select specific assets to sell
2. Enter sale proceeds for selected assets
3. Choose to:
   - **Keep remaining assets in HFS**: If still meeting criteria
   - **Reclassify remaining assets**: Back to original category

---

## Discontinued Operations

### When to Tag
A disposal group should be tagged as discontinued operation if:
- It represents a component of the entity
- It represents a separate major line of business or geographical area
- It is part of a single coordinated plan
- It is disposed of or classified as HFS

### Tagging Process
1. Navigate to HFS Request → **"Tag as Discontinued"**
2. System checks criteria automatically
3. Enter effects on P&L:
   - Revenue
   - Expenses
   - Tax
4. Click **"Tag as Discontinued"**

### Presentation
- Discontinued operations appear separately in:
  - Income Statement
  - Cash Flow Statement
  - Notes to Financial Statements
- Comparative periods are restated

---

## Reports

### Available Reports
1. **Movement Schedule**: IFRS 5 compliant movement table
2. **Valuation Details**: Detailed valuation history
3. **Discontinued Operations Note**: Comparative P&L lines
4. **Overdue HFS Report**: Items exceeding 12 months
5. **Audit Trail**: Complete change history

### Generating Reports
1. Navigate to **Assets Management → HFS → Reports**
2. Select report type
3. Choose date range
4. Click **"Generate Report"**
5. Export to PDF or Excel

---

## Common Scenarios

### Scenario 1: Simple Asset Disposal
1. Create HFS request for single asset
2. Get approval
3. Record valuation (if needed)
4. Record disposal when sold
5. System posts all journals automatically

### Scenario 2: Disposal Group with Impairment
1. Create HFS request for multiple assets (disposal group)
2. Get approval
3. Record valuation showing impairment
4. System posts impairment journal
5. Record disposal when sold
6. Tag as discontinued operation if criteria met

### Scenario 3: Foreign Currency Sale
1. Create HFS request
2. Get approval
3. Record disposal in foreign currency
4. System automatically:
   - Converts to functional currency
   - Calculates FX gain/loss
   - Posts FX journal

### Scenario 4: Partial Sale
1. Create HFS request for disposal group
2. Get approval
3. Record partial sale (select specific assets)
4. Remaining assets stay in HFS or reclassify

### Scenario 5: Cancellation
1. If sale is cancelled, navigate to HFS Request
2. Click **"Cancel HFS"**
3. Enter reason
4. System automatically:
   - Reclassifies assets back to original category
   - Resumes depreciation
   - Posts cancellation journal

---

## FAQ

### Q: What happens if sale takes longer than 12 months?
**A:** System flags as overdue. Senior approval is required to remain open. Extension justification must be provided.

### Q: Can I edit an HFS request after approval?
**A:** Only draft requests can be edited. Approved requests require cancellation and recreation.

### Q: What if fair value increases after impairment?
**A:** You can record a reversal, but it's limited to the original carrying amount before HFS impairment.

### Q: How is depreciation handled for Investment Property?
**A:** Investment Property at Fair Value continues depreciation per IAS 40, even when classified as HFS.

### Q: What accounts are used for HFS?
**A:** HFS account is configured per asset category. System uses:
- HFS Control Account (from category settings)
- Impairment Loss Account (from category or system settings)
- Gain/Loss on Disposal Account (from category or system settings)

### Q: Can I sell part of a disposal group?
**A:** Yes, use the partial sale feature. Select specific assets to sell.

### Q: How are FX gains/losses calculated?
**A:** System compares sale proceeds currency with carrying amount currency, converts both to functional currency, and calculates the difference.

### Q: What reports are required for auditors?
**A:** 
- IFRS 5 Movement Schedule
- Valuation Details
- Discontinued Operations Note (if applicable)
- Audit Trail

---

## Support

For additional help or questions, contact your system administrator or refer to the technical documentation.

