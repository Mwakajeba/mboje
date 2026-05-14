# What Happens When You Complete Counting?

## Overview

When you click "Complete Counting" and confirm, the system performs several automatic actions. **No inventory balances are updated automatically** - you must create and post adjustments separately.

---

## ✅ Immediate Effects

### 1. **Session Status Changes**
- **Status**: Changes from `counting` → `completed`
- **Count End Time**: Records the exact timestamp when counting was completed
- **Session Locked**: The session can no longer be edited (status is now `completed`)

### 2. **Variance Calculation (Automatic)**
For **every entry** that has a physical quantity entered, the system:

#### Calculates:
- **Variance Quantity** = Physical Quantity - System Quantity
- **Variance Percentage** = (Variance Quantity / System Quantity) × 100
- **Variance Value** = |Variance Quantity| × Unit Cost
- **Variance Type**:
  - `positive` = Surplus (physical > system)
  - `negative` = Shortage (physical < system)
  - `zero` = No variance (physical = system)

#### Flags High-Value Variances:
- **High-Value Flag**: Set to `true` if:
  - Variance Value ≥ TZS 50,000 **OR**
  - Variance Percentage ≥ 5%
- **Requires Recount**: Automatically set to `true` for high-value variances
- **Status**: 
  - `pending` if high-value (requires investigation)
  - `resolved` if not high-value

#### Creates/Updates Records:
- Creates a `CountVariance` record for each entry with variance
- Stores all calculated values for reporting and analysis

### 3. **Batch/Lot Mismatch Detection (Automatic)**
The system automatically:
- Compares system lot/batch numbers (from latest movement) with physical lot/batch numbers
- Detects any mismatches
- Adds mismatch details to variance investigation notes
- Flags items that need attention

---

## ⚠️ What Does NOT Happen Automatically

### ❌ Inventory Balances Are NOT Updated
- **Stock levels remain unchanged**
- **No journal entries are created**
- **No movements are recorded**
- **No adjustments are posted**

### Why?
- You need to **review variances first**
- **Investigate** high-value variances
- **Create adjustments** only for approved variances
- **Get approvals** before posting to GL

---

## 📋 What You Can Do After Completion

### 1. **View Variances**
- Navigate to "View Variances" page
- See all variances categorized by type
- Filter by high-value, type, session, etc.

### 2. **Investigate Variances**
- Add investigation notes
- Update variance status
- Document reasons for variances

### 3. **Request Recounts** (if needed)
- For high-value variances
- Supervisor can request recount
- Enter recount quantity

### 4. **Create Adjustments**
- Select variances to adjust
- Provide reason codes:
  - `wrong_posting`
  - `theft`
  - `damage`
  - `expired`
  - `unrecorded_issue`
  - `unrecorded_receipt`
- Upload supporting documents
- Add supervisor/finance comments

### 5. **Approve Adjustments** (Multi-Level)
- **Level 1**: Store Supervisor approves
- **Level 2**: Inventory Manager approves
- **Level 3**: Finance Manager approves
- **Level 4**: CFO/Internal Auditor approves

### 6. **Post Adjustments to GL**
- Only after all 4 approval levels are complete
- Creates journal entries:
  - **Shortage**: Dr Inventory Loss Expense, Cr Inventory
  - **Surplus**: Dr Inventory, Cr Inventory Gain Income
- Creates movement records
- **Updates stock levels** (only at this point)
- Updates inventory balances

### 7. **Generate Reports**
- Full Inventory Count Report
- Variance Summary Report
- Variance Value Report
- High-Value Items Scorecard
- Expiry & Damaged Stock Report
- Cycle Count Performance Report
- Year-end Stock Valuation Report

---

## 🔄 Complete Workflow After Completion

```
Complete Counting
    ↓
Variances Calculated
    ↓
Review Variances
    ↓
Investigate High-Value Variances
    ↓
Create Adjustments (for approved variances)
    ↓
Multi-Level Approval (4 levels)
    ↓
Post Adjustments to GL
    ↓
✅ Inventory Balances Updated
```

---

## 📊 Example Scenario

### Before Completion:
- **System Quantity**: 100 units
- **Physical Quantity**: 95 units (entered during counting)
- **Stock Level**: 100 units (unchanged)

### After Completion:
- **Variance Calculated**:
  - Variance Quantity: -5 units (shortage)
  - Variance Percentage: -5%
  - Variance Value: TZS 50,000 (if unit cost = TZS 10,000)
  - High-Value: Yes (≥ 5%)
  - Requires Recount: Yes
- **Stock Level**: Still 100 units (NOT updated yet)

### After Creating & Posting Adjustment:
- **Adjustment Created**: Shortage of 5 units
- **Approvals**: All 4 levels approved
- **Posted to GL**:
  - Dr Inventory Loss Expense: TZS 50,000
  - Cr Inventory: TZS 50,000
- **Movement Created**: Adjustment out of 5 units
- **Stock Level Updated**: Now 95 units ✅

---

## 🎯 Key Points

1. **Completion is a milestone**, not the end
2. **Variances are calculated automatically** for analysis
3. **No automatic inventory updates** - you control when to adjust
4. **Multi-level approval required** before posting
5. **Full audit trail** maintained throughout

---

## 📝 Summary

**When you complete counting:**
- ✅ Session status changes to `completed`
- ✅ All variances are calculated automatically
- ✅ High-value variances are flagged
- ✅ Batch/lot mismatches are detected
- ❌ Inventory balances are NOT updated
- ❌ No journal entries are created
- ❌ No stock levels are changed

**To update inventory:**
- Create adjustments from variances
- Get multi-level approvals
- Post adjustments to GL
- Then inventory balances are updated

This design ensures **proper control** and **audit trail** before any inventory adjustments are made.

